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

    /** @var UXML */
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
        return GenerateQrCode::fromArray(
            $this->xmlDom->toTagsArray($this->certificate, $this->invoiceHash, $this->digitalSignature)
        )->toBase64();
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
