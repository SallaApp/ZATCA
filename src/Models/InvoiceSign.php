<?php


namespace Salla\ZATCA\Models;

use DOMDocument;
use DOMXPath;
use InvalidArgumentException;
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

        $this->assertSingleton($this->xmlDom->element()->ownerDocument, ['cbc:ProfileID', 'cac:AccountingSupplierParty']);

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
        $signedInvoice = preg_replace('/^[ \t]*[\r\n]+/m', '', $signedInvoice);

        $signedDoc = new DOMDocument();
        if (!$signedDoc->loadXML($signedInvoice)) {
            throw new InvalidArgumentException('Signed invoice is not valid XML');
        }
        $this->assertSingleton($signedDoc, ['ext:UBLExtensions', 'cac:Signature', 'cbc:ProfileID', 'cac:AccountingSupplierParty']);
        $this->assertSingleton($signedDoc, ['cac:AdditionalDocumentReference[cbc:ID="QR"]']);

        return new \Salla\ZATCA\Models\Invoice(
            $signedInvoice,
            $this->invoiceHash,
            $QRCode,
            $this->certificate
        );
    }

    /**
     * Each element must occur exactly once as a direct child of the invoice root.
     *
     * @throws InvalidArgumentException
     */
    private function assertSingleton(DOMDocument $doc, array $paths): void
    {
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $xpath->registerNamespace('ext', 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2');

        foreach ($paths as $path) {
            $count = $xpath->query('/*/' . $path)->length;
            if ($count !== 1) {
                throw new InvalidArgumentException("Invoice must contain exactly one <$path> element, found $count");
            }
        }
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
