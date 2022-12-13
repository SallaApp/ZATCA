<?php

namespace Salla\ZATCA\Helpers;

use phpseclib3\File\X509;

/**
 * Class UblExtension
 * @package Salla\ZATCA\Helpers
 */
class UblExtension
{
    const SAC = 'urn:oasis:names:specification:ubl:schema:xsd:SignatureAggregateComponents-2';
    const SBS = 'urn:oasis:names:specification:ubl:schema:xsd:SignatureBasicComponents-2';
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
        $signedProprietiesXml = $this->buildSignatureObject();

        $xml = \Salla\ZATCA\Helpers\UXML::newInstance("ext:UBLExtension");
        $xml->add('ext:ExtensionURI', 'urn:oasis:names:specification:ubl:dsig:enveloped:xades');
        $content = $xml->add('ext:ExtensionContent');
        $singInformation = $content->add('sig:UBLDocumentSignatures', null, [
            'xmlns:sig' => self::SIG,
            'xmlns:sac' => self::SAC,
            'xmlns:sbc' => self::SBS]);


        $contentSignature = $singInformation->add('sac:SignatureInformation');
        $contentSignature->add('cbc:ID', 'urn:oasis:names:specification:ubl:signature:1');
        $contentSignature->add('sbc:ReferencedSignatureID', 'urn:oasis:names:specification:ubl:signature:Invoice');

        $signature = $contentSignature->add('ds:Signature', null, [
            'xmlns:ds' => 'http://www.w3.org/2000/09/xmldsig#',
            'Id' => 'signature'
        ]);

        $signInfo = $signature->add('ds:SignedInfo');
        $signInfo->add('ds:CanonicalizationMethod', null, [
            'Algorithm' => 'http://www.w3.org/2006/12/xml-c14n11'
        ]);
        $signInfo->add('ds:SignatureMethod', null, [
            'Algorithm' => 'http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha256'
        ]);

        $reference = $signInfo->add('ds:Reference', null, [
            'Id' => 'invoiceSignedData',
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
            'URI' => "#xadesSignedProperties"
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
        $this->buildSignatureObject($dsObject);

        //We need to remove the first line "<?xml version="1.0" encoding="UTF-8\" and return the string as pure
        $formatted = preg_replace('!^[^>]+>(\r\n|\n)!', '', $xml->asXML());

        //During building ublExtension there is an extra props added to xml, We must remove it.
        $formatted = str_replace([' xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" xmlns:ds="http://www.w3.org/2000/09/xmldsig#"',
                                  '<ext:UBLExtension xmlns:sig="urn:oasis:names:specification:ubl:schema:xsd:CommonSignatureComponents-2" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xades="http://uri.etsi.org/01903/v1.3.2#">'],
            ["", '<ext:UBLExtension>'], $formatted);

        //Finally we need to make sure the built xml have 4 indentation
        return preg_replace('/^[ ]+(?=<)/m', '$0$0', $formatted);
    }

    private function buildSignatureObject($dsObject = null):? string
    {
        $xml = null;
        if ($dsObject) {
            $xml = $dsObject->add('xades:QualifyingProperties', null, [
                'xmlns:xades' => "http://uri.etsi.org/01903/v1.3.2#",
                'Target'      => "signature"
            ]);

            $signedProperties =  $xml->add("xades:SignedProperties", null, [
                'xmlns:xades' => "http://uri.etsi.org/01903/v1.3.2#",
                'Id'          => 'xadesSignedProperties'
            ])->add('xades:SignedSignatureProperties');
        }
        else {
            $signedProperties = $xml= \Salla\ZATCA\Helpers\UXML::newInstance("xades:SignedProperties", null, [
                'xmlns:xades' => "http://uri.etsi.org/01903/v1.3.2#",
                'Id'          => 'xadesSignedProperties'
            ])->add('xades:SignedSignatureProperties');
        }

        $signedProperties->add('xades:SigningTime', now()->format('Y-m-d') . 'T' . now()->format('H:m:s') . 'Z');
        $signingCertificate = $signedProperties->add('xades:SigningCertificate');
        $cert               = $signingCertificate->add('xades:Cert');

        $certDigest = $cert->add('xades:CertDigest');

        $arrDigest = [
            'xmlns:ds'  => "http://www.w3.org/2000/09/xmldsig#",
            'Algorithm' => "http://www.w3.org/2001/04/xmlenc#sha256"
        ];
        if($dsObject){
           unset($arrDigest['xmlns:ds']);
        }
        $certDigest->add('ds:DigestMethod', null,$arrDigest );
        $certDigest->add('ds:DigestValue', $this->certificate->getHash(),
           $dsObject ? [] : ['xmlns:ds' => "http://www.w3.org/2000/09/xmldsig#"]);

        $issuerSerial = $cert->add('xades:IssuerSerial');
        $issuerSerial->add('ds:X509IssuerName', $this->certificate->getIssuerDN(X509::DN_STRING),
            $dsObject ? [] :  ['xmlns:ds' => "http://www.w3.org/2000/09/xmldsig#"]);
        $issuerSerial->add('ds:X509SerialNumber', $this->certificate->getCurrentCert()['tbsCertificate']['serialNumber']->toString(),
            $dsObject ? [] :  ['xmlns:ds' => "http://www.w3.org/2000/09/xmldsig#"]);

        if ($dsObject) {
            return null;
        }

        $signPart = str_replace(' xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" xmlns:ds="http://www.w3.org/2000/09/xmldsig#"', "", $signedProperties->asXML());
        // remove the first line "<?xml version="1.0" encoding="UTF-8\" and return the string as pure
        return preg_replace('!^[^>]+>(\r\n|\n)!', '', $signPart);
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
