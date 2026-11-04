<?php


namespace Salla\ZATCA\Test\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use OpenSSLAsymmetricKey;
use phpseclib3\Crypt\EC;
use phpseclib3\File\X509;
use Salla\ZATCA\Exception\CSRValidationException;
use Salla\ZATCA\GenerateCSR;
use Salla\ZATCA\Models\CSRRequest;

class GenerateCSRTest extends \PHPUnit\Framework\TestCase
{
    #[Test]
    public function shouldGenerateACsrWithProperiteis()
    {
        $CN                     = 'Salla';
        $country                = 'SA';
        $organizationName       = "Salla Store";
        $organizationalUnitName = '3311111111';
        try {
            $csr_request = GenerateCSR::fromRequest(
                CSRRequest::make()
                    ->setUID('311111111101113')
                    ->setSerialNumber('200000', 'Salla Store', 'Merchant Name')
                    ->setCommonName($CN)
                    ->setCountryName($country)
                    ->setOrganizationName($organizationName)
                    ->setOrganizationalUnitName($organizationalUnitName)
                    ->setRegisteredAddress('3355  - حي الملك فهد مكة المكرمة 24347 - 7192')
                    ->setInvoiceType(true, true)
                    ->setCurrentZatcaEnv(CSRRequest::SANDBOX)
                    ->setBusinessCategory('company')
            )->initialize()
                ->generate();

        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }

        openssl_pkey_export($csr_request->getPrivateKey(), $exported);

        $publicKey = openssl_pkey_get_details(openssl_csr_get_public_key($csr_request->getCsrContent()))['key'];
        if (version_compare(phpversion(), '8.0', '<')) {
            $this->assertEquals('OpenSSL key', get_resource_type($csr_request->getPrivateKey()));
        } else {
            $this->assertInstanceOf(OpenSSLAsymmetricKey::class, $csr_request->getPrivateKey());
        }



        $csrSubject = openssl_csr_get_subject($csr_request->getCsrContent());
        $this->assertEquals($csrSubject['CN'], $CN);
        $this->assertEquals($csrSubject['O'], $organizationName);
        $this->assertEquals($csrSubject['OU'], $organizationalUnitName);
        $this->assertEquals($csrSubject['C'], $country);


        $X509 = new X509();
        $X509->loadCSR($csr_request->getCsrContent());
        $X509->setPrivateKey(EC::loadPrivateKey($exported));

        $this->assertIsArray($X509->getCurrentCert());
        $this->assertTrue($X509->validateSignature());

        $this->assertEquals('ecdsa-with-SHA256',$X509->getCurrentCert()['signatureAlgorithm']['algorithm']);

        $publicKeyX509 = $X509->getPublicKey()->toString('PKCS8');

        $this->assertIsString($publicKeyX509);

        $this->assertIsString($publicKey);

        $this->assertEquals(str_replace("\r\n", "", $publicKeyX509), str_replace("\n", "", $publicKey));
    }

    public static function environmentTemplates(): array
    {
        return [
            'sandbox'    => [CSRRequest::SANDBOX, 'TSTZATCA-Code-Signing'],
            'simulation' => [CSRRequest::SIMULATION, 'PREZATCA-Code-Signing'],
            'production' => [CSRRequest::PRODUCTION, 'ZATCA-Code-Signing'],
            'core'       => ['core', 'ZATCA-Code-Signing'],
        ];
    }

    #[Test]
    #[DataProvider('environmentTemplates')]
    public function shouldUseTheCertificateTemplateOfTheEnvironment(string $env, string $template)
    {
        $csr = GenerateCSR::fromRequest($this->csrRequest()->setCurrentZatcaEnv($env))
            ->initialize()
            ->generate();

        $this->assertSame($template, $this->certificateTemplate($csr->getCsrContent()));
    }

    #[Test]
    public function shouldRejectAnUnknownEnvironment()
    {
        $this->expectException(CSRValidationException::class);

        $this->csrRequest()->setCurrentZatcaEnv('live');
    }

    private function csrRequest(): CSRRequest
    {
        return CSRRequest::make()
            ->setUID('311111111101113')
            ->setSerialNumber('200000', 'Salla Store', 'Merchant Name')
            ->setCommonName('Salla')
            ->setCountryName('SA')
            ->setOrganizationName('Salla Store')
            ->setOrganizationalUnitName('3311111111')
            ->setRegisteredAddress('Riyadh')
            ->setInvoiceType(true, true)
            ->setBusinessCategory('company');
    }

    /**
     * The certificate template name (extension 1.3.6.1.4.1.311.20.2) in a PEM CSR:
     * a PrintableString or UTF8String whose length byte matches the name.
     */
    private function certificateTemplate(string $pem): ?string
    {
        $der = base64_decode(preg_replace('/-----[^-]+-----|\s+/', '', $pem));
        preg_match_all('/[\x0C\x13]([\x00-\x7F])((?:TST|PRE)?ZATCA-Code-Signing)/', $der, $matches, PREG_SET_ORDER);
        foreach ($matches as [, $length, $name]) {
            if (ord($length) === strlen($name)) {
                return $name;
            }
        }

        return null;
    }
}
