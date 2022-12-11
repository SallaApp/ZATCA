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

    /** @var \UXML\UXML  */
    protected $xmlDom;

    public function __construct(string $xmlInvoice, Certificate $certificate)
    {
        $this->certificate = $certificate;
        $this->xmlInvoice = $xmlInvoice;
    }

    public function sign()
    {
        /** @see  https://zatca.gov.sa/ar/E-Invoicing/Introduction/Guidelines/Documents/E-invoicing%20Detailed%20Technical%20Guidelines.pdf page 52 */
        $this->xmlDom = UXML::fromString($this->xmlInvoice);

        // remove unwanted tags
        $this->xmlDom->removeByXpath('ext:UBLExtensions');
        $this->xmlDom->removeByXpath('cac:Signature');
        $this->xmlDom->removeParentByXpath('cac:AdditionalDocumentReference/cbc:ID[. = "QR"]');

        file_put_contents(__DIR__.'/../../tests/newPure.xml', $this->getPureInvoiceString());

       $invoiceHash = base64_encode(hash('sha256', $this->getPureInvoiceString(), true));

        /**
         * @see https://zatca.gov.sa/ar/E-Invoicing/Introduction/Guidelines/Documents/E-invoicing%20Detailed%20Technical%20Guidelines.pdf
         * @see page 53
         */
        $digitalSignature = base64_encode(
            $this->certificate->getPrivateKey()->sign(base64_decode($invoiceHash))
        );

       $ublExtension = (new UblExtension)
            ->setCertificate($this->certificate)
            ->setInvoiceHash($invoiceHash)
            ->setDigitalSignature($digitalSignature)
            ->populateUblSignature();


        $signedInvoice = $this->xmlDom->getSingedInvoice(
            $this->xmlInvoice,
            $ublExtension,
            $this->certificate,
            $invoiceHash,
            $digitalSignature
        );

        return ['hash'    => $invoiceHash,'invoice' => $signedInvoice];
    }


    private function getPureInvoiceString(): string
    {

        $doc = new \DOMDocument();
        if ($doc->loadXML($this->xmlDom->asXML(), LIBXML_NOERROR) === false) {
            throw new InvalidArgumentException('Failed to parse XML string');
        }
        $doc->formatOutput = true;

        $formatted =  preg_replace(
            '/^[ ]+(?=<)/m',
            '$0$0',
            $doc->C14N(false, false)
        );


        return str_replace(
            array("<cbc:ProfileID>", "<cac:AccountingSupplierParty>"),
            array("\n    <cbc:ProfileID>", "\n    \n    <cac:AccountingSupplierParty>"), $formatted);
    }


    private function generateQRCode(string $invoiceHash, string $digitalSignature, $publicKey,$certificateSignature): string
    {
        // todo :: make sure you coverd all types
        $isSimplified = $this->xmlDom->get("cbc:InvoiceTypeCode")->asText() === "388";

        $issueDate = $this->xmlDom->get("cbc:IssueDate")->asText();
        $issueTime = $this->xmlDom->get("cbc:IssueTime")->asText();

        $qrArray = [
            new Tag(1, $this->xmlDom->get("cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName")->asText()),
            new Tag(2, $this->xmlDom->get("cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID")->asText()),
            new Tag(3, $issueDate . 'T' . $issueTime . 'Z'),
            new Tag(4, $this->xmlDom->get("cac:LegalMonetaryTotal/cbc:TaxInclusiveAmount")->asText()),
            new Tag(5, $this->xmlDom->get("cac:TaxTotal")->asText()),
            new Tag(6, $invoiceHash),
            new Tag(7, $digitalSignature),
            new Tag(8, base64_decode($this->certificate->getPlainPublicKey()))
        ];

        if ($isSimplified) {
            $qrArray = array_merge($qrArray, [new Tag(9, $this->certificate->getCertificateSignature())]);
        }

        return GenerateQrCode::fromArray($qrArray)->toBase64();
    }
}
