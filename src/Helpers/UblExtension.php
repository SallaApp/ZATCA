<?php

namespace Salla\ZATCA\Helpers;

/**
 * Class UblExtension
 * @package Salla\ZATCA\Helpers
 */
class UblExtension
{
    const SAC = 'urn:oasis:names:specification:ubl:schema:xsd:SignatureAggregateComponents-2';
    const SBC = 'urn:oasis:names:specification:ubl:schema:xsd:SignatureBasicComponents-2';
    const SIG = 'urn:oasis:names:specification:ubl:schema:xsd:CommonSignatureComponents-2';


    /**
     * @var Certificate $certificate
     */
    protected $certificate;

    /**
     * @var string
     */
    protected $invoiceHash;

    /**
     * @var string
     */
    protected $digitalSignature;

    /**
     * @throws \DOMException
     */
    public function populateUblSignature(): string
    {
        $signingTime = date('Y-m-d') . 'T' . date('H:m:s');

        $signedProprietiesXml = $this->buildSignedProperties($signingTime);

        $xml = \Salla\ZATCA\Helpers\UXML::newInstance("ext:UBLExtension");
        $xml->add('ext:ExtensionURI', 'urn:oasis:names:specification:ubl:dsig:enveloped:xades');
        $content = $xml->add('ext:ExtensionContent');
        $singInformation = $content->add('sig:UBLDocumentSignatures', null, [
            'xmlns:sig' => self::SIG,
            'xmlns:sac' => self::SAC,
            'xmlns:sbc' => self::SBC]);


        $contentSignature = $singInformation->add('sac:SignatureInformation');
        $contentSignature->add('cbc:ID', 'urn:oasis:names:specification:ubl:signature:1');
        $contentSignature->add('sbc:ReferencedSignatureID', 'urn:oasis:names:specification:ubl:signature:Invoice');

        $signature = $contentSignature->add('ds:Signature', null, [
            'xmlns:ds' => 'http://www.w3.org/2000/09/xmldsig#',
            'Id'       => 'signature'
        ]);

        $signInfo = $signature->add('ds:SignedInfo');
        $signInfo->add('ds:CanonicalizationMethod', null, [
            'Algorithm' => 'http://www.w3.org/2006/12/xml-c14n11'
        ]);
        $signInfo->add('ds:SignatureMethod', null, [
            'Algorithm' => 'http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha256'
        ]);

        $reference = $signInfo->add('ds:Reference', null, [
            'Id'  => 'invoiceSignedData',
            'URI' => ''
        ]);

        $transforms = $reference->add('ds:Transforms');
        $xPath = $transforms->add('ds:Transform', null, [
            'Algorithm' => "http://www.w3.org/TR/1999/REC-xpath-19991116"
        ]);
        $xPath->add('ds:XPath', 'not(//ancestor-or-self::ext:UBLExtensions)');

        $xPath = $transforms->add('ds:Transform', null, [
            'Algorithm' => "http://www.w3.org/TR/1999/REC-xpath-19991116"
        ]);
        $xPath->add('ds:XPath', 'not(//ancestor-or-self::cac:Signature)');


        $xPath = $transforms->add('ds:Transform', null, [
            'Algorithm' => "http://www.w3.org/TR/1999/REC-xpath-19991116"
        ]);
        $xPath->add('ds:XPath', 'not(//ancestor-or-self::cac:AdditionalDocumentReference[cbc:ID=\'QR\'])');

        $transforms->add('ds:Transform', null, [
            'Algorithm' => "http://www.w3.org/2006/12/xml-c14n11"
        ]);

        $reference->add('ds:DigestMethod', null, [
            'Algorithm' => "http://www.w3.org/2001/04/xmlenc#sha256"
        ]);

        $reference->add('ds:DigestValue', $this->invoiceHash);

        $digestValue = $signInfo->add('ds:Reference', null, [
            'Type' => "http://www.w3.org/2000/09/xmldsig#SignatureProperties",
            'URI'  => "#xadesSignedProperties"
        ]);

        $digestValue->add('ds:DigestMethod', null, [
            'Algorithm' => "http://www.w3.org/2001/04/xmlenc#sha256"
        ]);

        $digestValue->add('ds:DigestValue', base64_encode(hash('sha256', $signedProprietiesXml)));

        $signature->add('ds:SignatureValue', $this->digitalSignature);

        $keyInfo = $signature->add('ds:KeyInfo');
        $x509Data = $keyInfo->add('ds:X509Data');
        $x509Data->add('ds:X509Certificate', $this->certificate->getPlainCertificate());
        $dsObject = $signature->add('ds:Object');
        $this->buildSignatureObject($dsObject, $signingTime);

        //We need to remove the first line "<?xml version="1.0" encoding="UTF-8\" and return the string as pure
        $formatted = preg_replace('!^[^>]+>(\r\n|\n)!', '', $xml->asXML());

        //During building ublExtension there is an extra props added to xml, We must remove it.
        $formatted = str_replace([' xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" xmlns:ds="http://www.w3.org/2000/09/xmldsig#"',
            '<ext:UBLExtension xmlns:sig="urn:oasis:names:specification:ubl:schema:xsd:CommonSignatureComponents-2" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xades="http://uri.etsi.org/01903/v1.3.2#">'],
            ["", '<ext:UBLExtension>'], $formatted);

        //Finally we need to make sure the built xml have 4 indentation
        return preg_replace('/^[ ]+(?=<)/m', '$0$0', $formatted);
    }

    private function buildSignatureObject($dsObject, $signingTime): void
    {
        $xml = $dsObject->add('xades:QualifyingProperties', null, [
            'xmlns:xades' => "http://uri.etsi.org/01903/v1.3.2#",
            'Target'      => "signature"
        ]);

        $signedProperties = $xml->add("xades:SignedProperties", null, [
            'xmlns:xades' => "http://uri.etsi.org/01903/v1.3.2#",
            'Id'          => 'xadesSignedProperties'
        ])->add('xades:SignedSignatureProperties');

        $signedProperties->add('xades:SigningTime', $signingTime);
        $signingCertificate = $signedProperties->add('xades:SigningCertificate');

        $cert = $signingCertificate->add('xades:Cert');

        $certDigest = $cert->add('xades:CertDigest');
        $certDigest->add('ds:DigestMethod', null, ['Algorithm' => "http://www.w3.org/2001/04/xmlenc#sha256"]);
        $certDigest->add('ds:DigestValue', $this->certificate->getHash());

        $issuerSerial = $cert->add('xades:IssuerSerial');

        $issuerSerial->add('ds:X509IssuerName', $this->certificate->getFormattedIssuerDN());
        $issuerSerial->add('ds:X509SerialNumber', $this->certificate->getCurrentCert()['tbsCertificate']['serialNumber']->toString());
    }

    /**
     * Build qualified SignedProperties string like zatca SDK does, Don't change it.
     * Any space decreasing|increasing will cause wrong "error xadesSignedPropertiesDigestValue".
     * @param $signingTime
     * @return string
     */
    private function buildSignedProperties($signingTime): string
    {
        $signaturePart = '<xades:SignedProperties xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" Id="xadesSignedProperties">' . PHP_EOL .
'                                <xades:SignedSignatureProperties>' . PHP_EOL .
'                                    <xades:SigningTime>SIGNING_TIME_VALUE</xades:SigningTime>' . PHP_EOL .
'                                    <xades:SigningCertificate>' . PHP_EOL .
'                                        <xades:Cert>' . PHP_EOL .
'                                            <xades:CertDigest>' . PHP_EOL .
'                                                <ds:DigestMethod xmlns:ds="http://www.w3.org/2000/09/xmldsig#" Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>' . PHP_EOL .
'                                                <ds:DigestValue xmlns:ds="http://www.w3.org/2000/09/xmldsig#">HASH_DIGEST_VALUE</ds:DigestValue>' . PHP_EOL .
'                                            </xades:CertDigest>' . PHP_EOL .
'                                            <xades:IssuerSerial>' . PHP_EOL .
'                                                <ds:X509IssuerName xmlns:ds="http://www.w3.org/2000/09/xmldsig#">ISSUER_NAME</ds:X509IssuerName>' . PHP_EOL .
'                                                <ds:X509SerialNumber xmlns:ds="http://www.w3.org/2000/09/xmldsig#">SERIAL_NUMBER</ds:X509SerialNumber>' . PHP_EOL .
'                                            </xades:IssuerSerial>' . PHP_EOL .
'                                        </xades:Cert>' . PHP_EOL .
'                                    </xades:SigningCertificate>' . PHP_EOL .
'                                </xades:SignedSignatureProperties>' . PHP_EOL .
'                            </xades:SignedProperties>';

        return str_replace([
            'SIGNING_TIME_VALUE',
            'HASH_DIGEST_VALUE',
            'ISSUER_NAME',
            'SERIAL_NUMBER',

        ], [
                $signingTime,
                $this->certificate->getHash(),
                $this->certificate->getFormattedIssuerDN(),
                $this->certificate->getCurrentCert()['tbsCertificate']['serialNumber']->toString()
            ], $signaturePart);
    }

    public function setDigitalSignature(string $digitalSignature): UblExtension
    {
        $this->digitalSignature = $digitalSignature;
        return $this;
    }

    public function setInvoiceHash(string $invoiceHash): UblExtension
    {
        $this->invoiceHash = $invoiceHash;
        return $this;
    }

    public function setCertificate(Certificate $certificate): UblExtension
    {
        $this->certificate = $certificate;
        return $this;
    }
}
