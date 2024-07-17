<?php


namespace Salla\ZATCA\Test\Unit;

use phpseclib3\File\X509;
use Salla\ZATCA\Helpers\Certificate;
use Salla\ZATCA\Models\InvoiceSign;

class SignInvoiceTest extends \PHPUnit\Framework\TestCase
{
    protected const CERTIFICATE  = 'MIID6zCCA5CgAwIBAgITbwAAgLTUs0JsZqZVAQABAACAtDAKBggqhkjOPQQDAjBjMRUwEwYKCZImiZPyLGQBGRYFbG9jYWwxEzARBgoJkiaJk/IsZAEZFgNnb3YxFzAVBgoJkiaJk/IsZAEZFgdleHRnYXp0MRwwGgYDVQQDExNUU1pFSU5WT0lDRS1TdWJDQS0xMB4XDTIyMTAwNjEyNTcyNloXDTI0MTAwNTEyNTcyNlowTjELMAkGA1UEBhMCU0ExEzARBgNVBAoTCjM5OTk5OTk5OTkxDDAKBgNVBAsTA1RTVDEcMBoGA1UEAxMTVFNULTM5OTk5OTk5OTkwMDAwMzBWMBAGByqGSM49AgEGBSuBBAAKA0IABGGDDKDmhWAITDv7LXqLX2cmr6+qddUkpcLCvWs5rC2O29W/hS4ajAK4Qdnahym6MaijX75Cg3j4aao7ouYXJ9GjggI5MIICNTCBmgYDVR0RBIGSMIGPpIGMMIGJMTswOQYDVQQEDDIxLVRTVHwyLVRTVHwzLTA3MzBlZThlLTA4OWQtNDQ1OS1hMzg3LWIxMTg5NGJmMTQyOTEfMB0GCgmSJomT8ixkAQEMDzM5OTk5OTk5OTkwMDAwMzENMAsGA1UEDAwEMTEwMDEMMAoGA1UEGgwDVFNUMQwwCgYDVQQPDANUU1QwHQYDVR0OBBYEFDuWYlOzWpFN3no1WtyNktQdrA8JMB8GA1UdIwQYMBaAFHZgjPsGoKxnVzWdz5qspyuZNbUvME4GA1UdHwRHMEUwQ6BBoD+GPWh0dHA6Ly90c3RjcmwuemF0Y2EuZ292LnNhL0NlcnRFbnJvbGwvVFNaRUlOVk9JQ0UtU3ViQ0EtMS5jcmwwga0GCCsGAQUFBwEBBIGgMIGdMG4GCCsGAQUFBzABhmJodHRwOi8vdHN0Y3JsLnphdGNhLmdvdi5zYS9DZXJ0RW5yb2xsL1RTWkVpbnZvaWNlU0NBMS5leHRnYXp0Lmdvdi5sb2NhbF9UU1pFSU5WT0lDRS1TdWJDQS0xKDEpLmNydDArBggrBgEFBQcwAYYfaHR0cDovL3RzdGNybC56YXRjYS5nb3Yuc2Evb2NzcDAOBgNVHQ8BAf8EBAMCB4AwHQYDVR0lBBYwFAYIKwYBBQUHAwIGCCsGAQUFBwMDMCcGCSsGAQQBgjcVCgQaMBgwCgYIKwYBBQUHAwIwCgYIKwYBBQUHAwMwCgYIKoZIzj0EAwIDSQAwRgIhAOZ8oJnliPhdWvCiokPmStz2niL+1Rbw6y9asAh229z7AiEA0r6l1qnq6vzRjVvr9Hnbtq/9Aki0R4rF64EFNY4XACM=';
    protected const PRIVATE_KEY   = 'MHQCAQEEIP0tXvA0mhzTBgjZaAGt+V3tWIr79nG/gs56jKFJb6gboAcGBSuBBAAKoUQDQgAE+39UxFUCaF5p51RTvwXL+YODEpITlTdI27S72pSPJEAjQs2jBb1sLS/xg8/y5555+d19KoLmLo6gMrxvINXaHw==';
    protected const SERIAL_NUMBER = '2475382888117010136950089026926167642744062132';

    /** @test */
    public function isValidCertificate()
    {
        $x509 = new X509();
        $x509->loadX509(self::CERTIFICATE);

        $this->assertIsArray($x509->getCurrentCert());

        $this->assertEquals($x509->getCurrentCert()['tbsCertificate']['serialNumber']->toString(), self::SERIAL_NUMBER);

        $this->assertEquals($x509->getCurrentCert()['signatureAlgorithm']['algorithm'], 'ecdsa-with-SHA256');

        $this->assertEquals($x509->getIssuerDNProp('CN')[0], 'TSZEINVOICE-SubCA-1');

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
