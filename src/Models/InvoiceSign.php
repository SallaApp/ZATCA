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

    protected $digitalSignature;

    protected $invoiceHash;

    public function __construct(string $xmlInvoice, Certificate $certificate)
    {
        $this->certificate = $certificate;
        $this->xmlInvoice = $xmlInvoice;
    }

    public function sign(): Invoice
    {
        // we need to make sure the original xml have 4 indentation in it's lines
        if (!str_contains($this->xmlInvoice, '    <cbc:ProfileID>')) {
            $this->xmlInvoice = preg_replace('/^[ ]+(?=<)/m', '$0$0', $this->xmlInvoice);
        }

        $this->xmlDom = UXML::fromString($this->xmlInvoice);

        /**
         * remove unwanted tags
         *
         * @link https://zatca.gov.sa/ar/E-Invoicing/Introduction/Guidelines/Documents/E-invoicing%20Detailed%20Technical%20Guidelines.pdf
         * @link page 53
         */
        $this->xmlDom->removeByXpath('ext:UBLExtensions');
        $this->xmlDom->removeByXpath('cac:Signature');
        $this->xmlDom->removeParentByXpath('cac:AdditionalDocumentReference/cbc:ID[. = "QR"]');

        $invoiceHashBinary = hash('sha256', $this->xmlDom->element()->C14N(false, false), true);

        $this->invoiceHash = base64_encode($invoiceHashBinary);

        /**
         * @link https://zatca.gov.sa/ar/E-Invoicing/Introduction/Guidelines/Documents/E-invoicing%20Detailed%20Technical%20Guidelines.pdf
         * @link page 53
         */
        $this->digitalSignature = base64_encode(
            $this->certificate->getPrivateKey()->sign($invoiceHashBinary)
        );

        $ublExtension = (new UblExtension)
            ->setCertificate($this->certificate)
            ->setInvoiceHash($this->invoiceHash)
            ->setDigitalSignature($this->digitalSignature)
            ->populateUblSignature();

        $QRCode = $this->generateQRCode();

        $signedInvoice = str_replace(
            [
                "<cbc:ProfileID>",
                '<cac:AccountingSupplierParty>'
            ],
            [
                "<ext:UBLExtensions>" . $ublExtension . "</ext:UBLExtensions>" . PHP_EOL . "    <cbc:ProfileID>",
                $this->getQRNode($QRCode) . PHP_EOL . "    <cac:AccountingSupplierParty>"
            ], $this->xmlDom->asXML());

        // after replace we want to remove any blank line from invoice.
        return new \Salla\ZATCA\Models\Invoice(
            preg_replace('/^[ \t]*[\r\n]+/m', '', $signedInvoice),
            $this->invoiceHash,
            $QRCode,
            $this->certificate
        );
    }

    private function generateQRCode(): string
    {
        $issueDate = trim($this->xmlDom->get("cbc:IssueDate")->asText());
        $issueTime = trim($this->xmlDom->get("cbc:IssueTime")->asText());
        $issueTime = stripos($issueTime, 'Z') === false ? $issueTime .'Z' : $issueTime;

        $qrArray = [
            new Tag(1, trim($this->xmlDom->get("cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName")->asText())),
            new Tag(2, trim($this->xmlDom->get("cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID")->asText())),
            new Tag(3, $issueDate . 'T' . $issueTime),
            new Tag(4, trim($this->xmlDom->get("cac:LegalMonetaryTotal/cbc:TaxInclusiveAmount")->asText())),
            new Tag(5, trim($this->xmlDom->get("cac:TaxTotal")->asText())),
            new Tag(6, $this->invoiceHash),
            new Tag(7, $this->digitalSignature),
            new Tag(8, base64_decode($this->certificate->getPlainPublicKey()))
        ];

        /**
         * NOTE on UN/EDIFACT code list 1001 compliance:
         * For Simplified Tax Invoice, code is 388 and subtype is 02. ex. <cbc:InvoiceTypeCode name=”020000”>388</cbc:InvoiceTypeCode>
         * For simplified debit note, code is 383 and subtype is 02. ex. <cbc:InvoiceTypeCode name=”020000”>383</cbc:InvoiceTypeCode>
         * For simplified credit note, code is 381 and subtype is 02. ex. <cbc:InvoiceTypeCode name=”020000”>381</cbc:InvoiceTypeCode>
         *
         * @link https://zatca.gov.sa/ar/E-Invoicing/SystemsDevelopers/Documents/20220624_ZATCA_Electronic_Invoice_XML_Implementation_Standard_vF.pdf page 39
         */
        $startOfInvoiceTypeCode = $this->xmlDom->get("cbc:InvoiceTypeCode");
        $isSimplified = $startOfInvoiceTypeCode && strpos($startOfInvoiceTypeCode->element()->getAttribute('name'), "02") === 0;

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
    private function getQRNode(string $QRCode): string
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
