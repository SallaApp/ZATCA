<?php


namespace Salla\ZATCA\Models;

use Salla\ZATCA\GenerateQrCode;
use Salla\ZATCA\Helpers\Certificate;
use Salla\ZATCA\Helpers\UblExtension;
use Salla\ZATCA\Helpers\UXML;
use Salla\ZATCA\Tag;

class InvoiceSign
{
    /**
     * @var string
     */
    protected $xmlInvoice;

    /**
     * @var \Salla\ZATCA\Helpers\Certificate
     */
    protected $certificate;

    /** @var \UXML\UXML */
    protected $xmlDom;

    public function __construct(string $xmlInvoice, Certificate $certificate)
    {
        $this->certificate = $certificate;
        $this->xmlInvoice  = $xmlInvoice;
    }

    public function sign(): Invoice
    {

        // we need to make sure the orignal xml have 4 indentation
        if (! str_contains($this->xmlInvoice, '    <cbc:ProfileID>')) {
            $this->xmlInvoice = preg_replace('/^[ ]+(?=<)/m', '$0$0', $this->xmlInvoice);
        }

        /** @see  https://zatca.gov.sa/ar/E-Invoicing/Introduction/Guidelines/Documents/E-invoicing%20Detailed%20Technical%20Guidelines.pdf page 52 */
        $this->xmlDom = UXML::fromString($this->xmlInvoice);

        // remove unwanted tags
        $this->xmlDom->removeByXpath('ext:UBLExtensions');
        $this->xmlDom->removeByXpath('cac:Signature');
        $this->xmlDom->removeParentByXpath('cac:AdditionalDocumentReference/cbc:ID[. = "QR"]');
        $invoiceHashBinary = hash('sha256', $this->xmlDom->element()->C14N(false, false), true);
        $invoiceHash       = base64_encode($invoiceHashBinary);
        /**
         * @see https://zatca.gov.sa/ar/E-Invoicing/Introduction/Guidelines/Documents/E-invoicing%20Detailed%20Technical%20Guidelines.pdf
         * @see page 53
         */
        $digitalSignature = base64_encode(
            $this->certificate->getPrivateKey()->sign($invoiceHashBinary)
        );

        $ublExtension = (new UblExtension)
            ->setCertificate($this->certificate)
            ->setInvoiceHash($invoiceHash)
            ->setDigitalSignature($digitalSignature)
            ->populateUblSignature();

        $QRCode =  $this->generateQRCode($invoiceHash, $digitalSignature);
        $signedInvoice = str_replace(
            [
                "<cbc:ProfileID>",
                '<cac:AccountingSupplierParty>'
            ],
            [
                "<ext:UBLExtensions>" . $ublExtension . "</ext:UBLExtensions>".PHP_EOL."    <cbc:ProfileID>",
                $this->getQRNode($QRCode) .PHP_EOL. "    <cac:AccountingSupplierParty>"
            ], $this->xmlDom->asXML());

        //after replace we want to remove any blank line
        return new \Salla\ZATCA\Models\Invoice(preg_replace('/^[ \t]*[\r\n]+/m', '', $signedInvoice), $invoiceHash,$QRCode,$this->certificate);
    }

    private function generateQRCode(string $invoiceHash, string $digitalSignature): string
    {
        $isSimplified = $this->xmlDom->get("cbc:InvoiceTypeCode")->asText() === "388";

        $issueDate = $this->xmlDom->get("cbc:IssueDate")->asText();
        $issueTime = $this->xmlDom->get("cbc:IssueTime")->asText();

        $qrArray = [
            new Tag(1, $this->xmlDom->get("cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName")->asText()),
            new Tag(2, $this->xmlDom->get("cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID")->asText()),
            new Tag(3, $issueDate . 'T' . $issueTime . 'Z'),
            new Tag(4, $this->xmlDom->get("cac:LegalMonetaryTotal/cbc:TaxInclusiveAmount")->asText()),
            new Tag(5, trim($this->xmlDom->get("cac:TaxTotal")->asText())),
            new Tag(6, $invoiceHash),
            new Tag(7, $digitalSignature),
            new Tag(8, base64_decode($this->certificate->getPlainPublicKey()))
        ];

        if ($isSimplified) {
            $qrArray = array_merge($qrArray, [new Tag(9, $this->certificate->getCertificateSignature())]);
        }

        return GenerateQrCode::fromArray($qrArray)->toBase64();
    }

    /**
     * Dont edit this string , will effect the signature of invoice
     * @param string $QRCode
     * @return string
     */
    private function getQRNode(string $QRCode):string
    {
        return "<cac:AdditionalDocumentReference>
        <cbc:ID>QR</cbc:ID>
        <cac:Attachment>
            <cbc:EmbeddedDocumentBinaryObject mimeCode=\"text/plain\">$QRCode</cbc:EmbeddedDocumentBinaryObject>
        </cac:Attachment>
    </cac:AdditionalDocumentReference>
    <cac:Signature>
        <cbc:ID>urn:oasis:names:specification:ubl:signature:Invoice</cbc:ID>
        <cbc:SignatureMethod>urn:oasis:names:specification:ubl:dsig:enveloped:xades</cbc:SignatureMethod>
    </cac:Signature>";
    }
}
