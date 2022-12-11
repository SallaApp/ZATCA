<?php


namespace Salla\ZATCA\Helpers;


use phpseclib3\File\X509;
use UXML\UXML;

/**
 * Class UblExtension
 * @package Salla\ZATCA\Helpers
 */
class UblExtension
{

    const SAC                      = 'urn:oasis:names:specification:ubl:schema:xsd:SignatureAggregateComponents-2';
    const INVOICE_SIGNATURE        = "urn:oasis:names:specification:ubl:signature:Invoice";
    const INVOICE_SIGNATURE_METHOD = "urn:oasis:names:specification:ubl:dsig:enveloped:xades";
    const SBS                      = 'urn:oasis:names:specification:ubl:schema:xsd:SignatureBasicComponents-2';
    const SIG                      = 'urn:oasis:names:specification:ubl:schema:xsd:CommonSignatureComponents-2';

    /**
     * @var Certificate $certificate
     */
    protected $certificate;

    protected $ublExtensionXml;

    protected $signingXmlPart;


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
    public function populateUblSignature()
    {
        $signedProprietiesXml = $this->formattedSingPropertiesXml();
        $ublProprietiesXML    = $this->formattedUblExtensionPropertiesXml();

        return $this->formattedUblExtensionXml($signedProprietiesXml, $ublProprietiesXML);
    }



    private function formattedSingPropertiesXml()
    {
        // remove the first line "<?xml version="1.0" encoding="UTF-8\" and return the string as pure
            $signPart = $this->buildSignaturePart();
            $signPart = str_replace(' xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" xmlns:ds="http://www.w3.org/2000/09/xmldsig#"',"",$signPart);
        return preg_replace('!^[^>]+>(\r\n|\n)!', '', $signPart);
    }


    /**
     * @throws \DOMException
     */
    private function formattedUblExtensionPropertiesXml(): string
    {
        //remove the first line "<?xml version="1.0" encoding="UTF-8\" and return the string as pure
        $formatted = preg_replace('!^[^>]+>(\r\n|\n)!', '', $this->buildUblExtension());

        return str_replace('<ext:UBLExtension xmlns:sig="urn:oasis:names:specification:ubl:schema:xsd:CommonSignatureComponents-2" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xades="http://uri.etsi.org/01903/v1.3.2#">', '<ext:UBLExtension>', $formatted);
    }

    private function formattedUblExtensionXml($signedProprietiesXml, $ublProprietiesXML)
    {
        $allUblExtension = str_replace([
            'SET_SIGNED_PROPERTIES_XML',
            'SET_SIGNED_PROPERTIES_HASH'
        ],
            [
                $signedProprietiesXml,
                base64_encode(hash('sha256', $signedProprietiesXml)),
            ], $ublProprietiesXML);


        /*  $doc = new \DOMDocument();
      $doc->loadXML(\Salla\ZATCA\Helpers\UXML::fromString($allUblExtension)->asXML());
      $doc->formatOutput = true;*/
        return preg_replace(
            '/^[ ]+(?=<)/m',
            '$0$0',
            $allUblExtension
        );
    }

    private function buildSignaturePart(): string
    {
        $xml              = \Salla\ZATCA\Helpers\UXML::newInstance("xades:SignedProperties", null, [
            'xmlns:xades' => "http://uri.etsi.org/01903/v1.3.2#",
            'Id'          => 'xadesSignedProperties'
        ]);
        $signedProperties = $xml->add('xades:SignedSignatureProperties');

        $signedProperties->add('xades:SigningTime', now()->format('Y-m-d') . 'T' . now()->format('H:m:s') . 'Z',);
        $signingCertificate = $signedProperties->add('xades:SigningCertificate');
        $cert               = $signingCertificate->add('xades:Cert');

        $certDigest = $cert->add('xades:CertDigest');
        $certDigest->add('ds:DigestMethod', null, [
            'xmlns:ds'  => "http://www.w3.org/2000/09/xmldsig#",
            'Algorithm' => "http://www.w3.org/2001/04/xmlenc#sha256"
        ]);
        $certDigest->add('ds:DigestValue', $this->certificate->getHash(), [
            'xmlns:ds' => "http://www.w3.org/2000/09/xmldsig#"
        ]);

        $issuerSerial = $cert->add('xades:IssuerSerial');
        $issuerSerial->add('ds:X509IssuerName', $this->certificate->getIssuerDN(X509::DN_STRING), [
            'xmlns:ds' => "http://www.w3.org/2000/09/xmldsig#"
        ]);
        $issuerSerial->add('ds:X509SerialNumber', $this->certificate->getCurrentCert()['tbsCertificate']['serialNumber']->toString(), [
            'xmlns:ds' => "http://www.w3.org/2000/09/xmldsig#"
        ]);

        //file_put_contents(__DIR__.'/../../tests/signPart.xml',$xml->asXML());

       // $this->signingXmlPart = $xml;

        return $xml->asXML();
    }


    /**
     * @throws \DOMException
     */
    private function buildUblExtension(): string
    {
        $xml = \Salla\ZATCA\Helpers\UXML::newInstance("ext:UBLExtension");
        $xml->add('ext:ExtensionURI', 'urn:oasis:names:specification:ubl:dsig:enveloped:xades');
        $content         = $xml->add('ext:ExtensionContent');
        $singInformation = $content->add('sig:UBLDocumentSignatures', null, [
            'xmlns:sig' => self::SIG,
            'xmlns:sac' => self::SAC,
            'xmlns:sbc' => self::SBS]);


        $contentSignature = $singInformation->add('sac:SignatureInformation');
        $contentSignature->add('cbc:ID', 'urn:oasis:names:specification:ubl:signature:1');
        $contentSignature->add('sbc:ReferencedSignatureID', 'urn:oasis:names:specification:ubl:signature:Invoice');
        $signIatur = $contentSignature->add('ds:Signature', null, [
            'xmlns:ds' => 'http://www.w3.org/2000/09/xmldsig#',
            'Id'       => 'signature'
        ]);

        $signInfo = $signIatur->add('ds:SignedInfo');
      /*  $signInfo->add('ds:SignedInfo');*/
        $signInfo->add('ds:CanonicalizationMethod', null, [
            'Algorithm' => 'http://www.w3.org/2006/12/xml-c14n11'
        ]);
        $signInfo->add('ds:SignatureMethod', null, [
            'Algorithm' => 'http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha256'
        ]);
        $reference  = $signInfo->add('ds:Reference', null, [
            'Id'  => 'invoiceSignedData',
            'URI' => ''
        ]);
        $transforms = $reference->add('ds:Transforms');
        $xPath      = $transforms->add('ds:Transform', null, [
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

        $digistValue = $signInfo->add('ds:Reference', null, [
            'Type' => "http://www.w3.org/2000/09/xmldsig#SignatureProperties",
            'URI'  => "#xadesSignedProperties"
        ]);

        $digistValue->add('ds:DigestMethod', null, [
            'Algorithm' => "http://www.w3.org/2001/04/xmlenc#sha256"
        ]);

        $digistValue->add('ds:DigestValue', 'SET_SIGNED_PROPERTIES_HASH');

        $signIatur->add('ds:SignatureValue', $this->digitalSignature);

        $keyInfo  = $signIatur->add('ds:KeyInfo');
        $x509Data = $keyInfo->add('ds:X509Data');
        $x509Data->add('ds:X509Certificate', $this->certificate->getPlainCertificate());

        $dsObject = $signIatur->add('ds:Object');
        $dsObject->add('xades:QualifyingProperties', 'SET_SIGNED_PROPERTIES_XML', [
            'xmlns:xades' => "http://uri.etsi.org/01903/v1.3.2#",
            'Target'      => "signature"
        ]);


        return $xml->asXML();
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
