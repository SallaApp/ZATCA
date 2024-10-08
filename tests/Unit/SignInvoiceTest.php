<?php


namespace Salla\ZATCA\Test\Unit;

use phpseclib3\File\X509;
use Salla\ZATCA\Helpers\Certificate;
use Salla\ZATCA\Models\InvoiceSign;

class SignInvoiceTest extends \PHPUnit\Framework\TestCase
{
    protected const CERTIFICATE  = 'MIID3jCCA4SgAwIBAgITEQAAOAPF90Ajs/xcXwABAAA4AzAKBggqhkjOPQQDAjBiMRUwEwYKCZImiZPyLGQBGRYFbG9jYWwxEzARBgoJkiaJk/IsZAEZFgNnb3YxFzAVBgoJkiaJk/IsZAEZFgdleHRnYXp0MRswGQYDVQQDExJQUlpFSU5WT0lDRVNDQTQtQ0EwHhcNMjQwMTExMDkxOTMwWhcNMjkwMTA5MDkxOTMwWjB1MQswCQYDVQQGEwJTQTEmMCQGA1UEChMdTWF4aW11bSBTcGVlZCBUZWNoIFN1cHBseSBMVEQxFjAUBgNVBAsTDVJpeWFkaCBCcmFuY2gxJjAkBgNVBAMTHVRTVC04ODY0MzExNDUtMzk5OTk5OTk5OTAwMDAzMFYwEAYHKoZIzj0CAQYFK4EEAAoDQgAEoWCKa0Sa9FIErTOv0uAkC1VIKXxU9nPpx2vlf4yhMejy8c02XJblDq7tPydo8mq0ahOMmNo8gwni7Xt1KT9UeKOCAgcwggIDMIGtBgNVHREEgaUwgaKkgZ8wgZwxOzA5BgNVBAQMMjEtVFNUfDItVFNUfDMtZWQyMmYxZDgtZTZhMi0xMTE4LTliNTgtZDlhOGYxMWU0NDVmMR8wHQYKCZImiZPyLGQBAQwPMzk5OTk5OTk5OTAwMDAzMQ0wCwYDVQQMDAQxMTAwMREwDwYDVQQaDAhSUlJEMjkyOTEaMBgGA1UEDwwRU3VwcGx5IGFjdGl2aXRpZXMwHQYDVR0OBBYEFEX+YvmmtnYoDf9BGbKo7ocTKYK1MB8GA1UdIwQYMBaAFJvKqqLtmqwskIFzVvpP2PxT+9NnMHsGCCsGAQUFBwEBBG8wbTBrBggrBgEFBQcwAoZfaHR0cDovL2FpYTQuemF0Y2EuZ292LnNhL0NlcnRFbnJvbGwvUFJaRUludm9pY2VTQ0E0LmV4dGdhenQuZ292LmxvY2FsX1BSWkVJTlZPSUNFU0NBNC1DQSgxKS5jcnQwDgYDVR0PAQH/BAQDAgeAMDwGCSsGAQQBgjcVBwQvMC0GJSsGAQQBgjcVCIGGqB2E0PsShu2dJIfO+xnTwFVmh/qlZYXZhD4CAWQCARIwHQYDVR0lBBYwFAYIKwYBBQUHAwMGCCsGAQUFBwMCMCcGCSsGAQQBgjcVCgQaMBgwCgYIKwYBBQUHAwMwCgYIKwYBBQUHAwIwCgYIKoZIzj0EAwIDSAAwRQIhALE/ichmnWXCUKUbca3yci8oqwaLvFdHVjQrveI9uqAbAiA9hC4M8jgMBADPSzmd2uiPJA6gKR3LE03U75eqbC/rXA==';
    protected const PRIVATE_KEY   = 'MHQCAQEEIP0tXvA0mhzTBgjZaAGt+V3tWIr79nG/gs56jKFJb6gboAcGBSuBBAAKoUQDQgAE+39UxFUCaF5p51RTvwXL+YODEpITlTdI27S72pSPJEAjQs2jBb1sLS/xg8/y5555+d19KoLmLo6gMrxvINXaHw==';
    protected const SERIAL_NUMBER = '379112742831380471835263969587287663520528387';

    /** @test */
    public function isValidCertificate()
    {
        $x509 = new X509();
        $x509->loadX509(self::CERTIFICATE);
        $this->assertIsArray($x509->getCurrentCert());

        $this->assertEquals($x509->getCurrentCert()['tbsCertificate']['serialNumber']->toString(), self::SERIAL_NUMBER);

        $this->assertEquals($x509->getCurrentCert()['signatureAlgorithm']['algorithm'], 'ecdsa-with-SHA256');

        $this->assertEquals($x509->getIssuerDNProp('CN')[0], 'PRZEINVOICESCA4-CA');

        $this->assertTrue($x509->validateDate());

    }

    /** @test */
    public function canGeneratInvoiceHash()
    {
        $xmlInvoice = file_get_contents(__DIR__ . '/files/simplified_invoice.xml');

        $this->assertIsString($xmlInvoice);

        $signInfo = (new InvoiceSign($xmlInvoice, new Certificate(self::CERTIFICATE, self::PRIVATE_KEY)))->sign();

        //ensure that the hash written to invoice
        $this->assertStringContainsString($signInfo->getHash(), $signInfo->getInvoice());

        //ensure that the QR written to invoice
        $this->assertStringContainsString($signInfo->getQRCode(), $signInfo->getInvoice());

        //ensure that the Certificate written to the invoice
        $certificate = $signInfo->getCertificate();
        $this->assertStringContainsString($certificate->getPlainCertificate(), $signInfo->getInvoice());
        $this->assertStringContainsString($certificate->getHash(), $signInfo->getInvoice());


        // $this->assertStringContainsString($certificate->getCertificateSignature(),base64_decode($signInfo->getQRCode()));
    }

}
