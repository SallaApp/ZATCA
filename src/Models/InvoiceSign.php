<?php


namespace Salla\ZATCA\Models;

use Salla\ZATCA\GenerateQrCode;
use Salla\ZATCA\Helpers\Certificate;
use Salla\ZATCA\Helpers\UblExtension;
use Salla\ZATCA\Tag;
use UXML\UXML;

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

    public function __construct(string $xmlInvoice, Certificate $certificate)
    {
        $this->certificate = $certificate;
        $this->xmlInvoice  = $xmlInvoice;
    }

    public function signInvoice()
    {
        $this->xmlDom = UXML::fromString($this->xmlInvoice);

        // remove unwanted tags
        optional($this->xmlDom->get('ext:UBLExtensions'))->remove();
        optional($this->xmlDom->get('cac:Signature'))->remove();
        optional($this->xmlDom->get('cac:AdditionalDocumentReference/cbc:ID[. = "QR"]'))->parent()->remove();

        /**
         * @see https://zatca.gov.sa/ar/E-Invoicing/Introduction/Guidelines/Documents/E-invoicing%20Detailed%20Technical%20Guidelines.pdf
         * @see page 52
         */
        $invoiceHash = $this->generateInvoiceHash();

        /**
         * @see page 53
         */
        $digitalSignature = base64_encode(
            $this->certificate->getPrivateKey()->sign(base64_decode($invoiceHash))
        );

        $ublExtSigned = (new UblExtension)
            ->setCertificate($this->certificate)
            ->setInvoiceHash($invoiceHash)
            ->setDigitalSignature($digitalSignature)
            ->populateUblSignature();

        $qr = $this->generateQRCode(
            $invoiceHash,
            $digitalSignature
        );

        // now merge the xmlInvoice with UBLExt and add the qr code value
        $this->xmlInvoice = str_replace(
            [
                'SET_UBL_EXTENSIONS_STRING',
                'SET_QR_CODE_DATA'
            ],
            [
                $ublExtSigned,
                $qr
            ],
            $this->xmlInvoice);

        return [
            'signedInvoice' => $this->xmlInvoice,
            'invoiceHash'   => $invoiceHash,
            'qr'            => $qr
        ];
    }


    private function generateInvoiceHash(): string
    {
        $pureString = $this->getPureInvoiceString();

        return base64_encode(hash('sha256', $pureString, true));
    }

    private function getPureInvoiceString(): string
    {
        /** @see  https://zatca.gov.sa/ar/E-Invoicing/Introduction/Guidelines/Documents/E-invoicing%20Detailed%20Technical%20Guidelines.pdf page 52 */
        $doc       = new \DOMDocument();
        $xmlString = $this->xmlDom->asXML();

        if ($doc->loadXML($xmlString, LIBXML_NOERROR) === false) {
            throw new InvalidArgumentException('Failed to parse XML string');
        }

        return $doc->C14N(false, false);
    }

    public function generateQRCode($invoiceHash, $digitalSignature): string
    {
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
