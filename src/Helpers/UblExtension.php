<?php


namespace Salla\ZATCA\Helpers;


use phpseclib3\File\X509;

/**
 * Class UblExtension
 * @package Salla\ZATCA\Helpers
 */
class UblExtension
{
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
     *
     */
    public const template = <<<XML
    <ext:UBLExtension>
        <ext:ExtensionURI>urn:oasis:names:specification:ubl:dsig:enveloped:xades</ext:ExtensionURI>
        <ext:ExtensionContent>
            <sig:UBLDocumentSignatures xmlns:sig="urn:oasis:names:specification:ubl:schema:xsd:CommonSignatureComponents-2" xmlns:sac="urn:oasis:names:specification:ubl:schema:xsd:SignatureAggregateComponents-2" xmlns:sbc="urn:oasis:names:specification:ubl:schema:xsd:SignatureBasicComponents-2">
                <sac:SignatureInformation>
                    <cbc:ID>urn:oasis:names:specification:ubl:signature:1</cbc:ID>
                    <sbc:ReferencedSignatureID>urn:oasis:names:specification:ubl:signature:Invoice</sbc:ReferencedSignatureID>
                    <ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#" Id="signature">
                        <ds:SignedInfo>
                            <ds:CanonicalizationMethod Algorithm="http://www.w3.org/2006/12/xml-c14n11"/>
                            <ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha256"/>
                            <ds:Reference Id="invoiceSignedData" URI="">
                                <ds:Transforms>
                                    <ds:Transform Algorithm="http://www.w3.org/TR/1999/REC-xpath-19991116">
                                        <ds:XPath>not(//ancestor-or-self::ext:UBLExtensions)</ds:XPath>
                                    </ds:Transform>
                                    <ds:Transform Algorithm="http://www.w3.org/TR/1999/REC-xpath-19991116">
                                        <ds:XPath>not(//ancestor-or-self::cac:Signature)</ds:XPath>
                                    </ds:Transform>
                                    <ds:Transform Algorithm="http://www.w3.org/TR/1999/REC-xpath-19991116">
                                        <ds:XPath>not(//ancestor-or-self::cac:AdditionalDocumentReference[cbc:ID='QR'])</ds:XPath>
                                    </ds:Transform>
                                    <ds:Transform Algorithm="http://www.w3.org/2006/12/xml-c14n11"/>
                                </ds:Transforms>
                                <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
                                <ds:DigestValue>SET_INVOICE_HASH</ds:DigestValue>
                            </ds:Reference>
                            <ds:Reference Type="http://www.w3.org/2000/09/xmldsig#SignatureProperties" URI="#xadesSignedProperties">
                                <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
                                <ds:DigestValue>SET_SIGNED_PROPERTIES_HASH</ds:DigestValue>
                            </ds:Reference>
                        </ds:SignedInfo>
                        <ds:SignatureValue>SET_DIGITAL_SIGNATURE</ds:SignatureValue>
                        <ds:KeyInfo>
                            <ds:X509Data>
                                <ds:X509Certificate>SET_CERTIFICATE</ds:X509Certificate>
                            </ds:X509Data>
                        </ds:KeyInfo>
                        <ds:Object>
                            <xades:QualifyingProperties xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" Target="signature">
                            SET_SIGNED_PROPERTIES_XML
                            </xades:QualifyingProperties>
                        </ds:Object>
                    </ds:Signature>
                </sac:SignatureInformation>
            </sig:UBLDocumentSignatures>
        </ext:ExtensionContent>
    </ext:UBLExtension>
XML;

    public function populateUblSignature()
    {
        $signedProprietiesXml = str_replace(
            [
                'SET_CERTIFICATE_HASH',
                'SET_SIGN_TIMESTAMP',
                'SET_CERTIFICATE_ISSUER',
                'SET_CERTIFICATE_SERIAL_NUMBER'
            ],
            [
                $this->certificate->getHash(),
                now()->format('Y-m-d') . 'T' . now()->format('H:m:s') . 'Z',
                $this->certificate->getIssuerDN(X509::DN_STRING),
                $this->certificate->getCurrentCert()['tbsCertificate']['serialNumber']->toString()
            ],
            '<xades:SignedProperties xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" Id="xadesSignedProperties">
         <xades:SignedSignatureProperties>
          <xades:SigningTime>SET_SIGN_TIMESTAMP</xades:SigningTime>
          <xades:SigningCertificate>
           <xades:Cert>
            <xades:CertDigest>
             <ds:DigestMethod xmlns:ds="http://www.w3.org/2000/09/xmldsig#" Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
             <ds:DigestValue xmlns:ds="http://www.w3.org/2000/09/xmldsig#">SET_CERTIFICATE_HASH</ds:DigestValue>
            </xades:CertDigest>
            <xades:IssuerSerial>
             <ds:X509IssuerName xmlns:ds="http://www.w3.org/2000/09/xmldsig#">SET_CERTIFICATE_ISSUER</ds:X509IssuerName>
             <ds:X509SerialNumber xmlns:ds="http://www.w3.org/2000/09/xmldsig#">SET_CERTIFICATE_SERIAL_NUMBER</ds:X509SerialNumber>
            </xades:IssuerSerial>
           </xades:Cert>
          </xades:SigningCertificate>
         </xades:SignedSignatureProperties>
         </xades:SignedProperties>');

        return str_replace(
            [
                'SET_DIGITAL_SIGNATURE',
                'SET_CERTIFICATE',
                'SET_SIGNED_PROPERTIES_HASH',
                'SET_INVOICE_HASH',
                'SET_SIGNED_PROPERTIES_XML'
            ],
            [
                $this->digitalSignature,
                $this->certificate->getPlainCertificate(),
                base64_encode(hash('sha256', $signedProprietiesXml)), // a hash for signed proprieties xml
                $this->invoiceHash,
                $signedProprietiesXml
            ],
            self::template);
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
