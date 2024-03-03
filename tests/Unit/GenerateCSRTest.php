<?php


namespace Salla\ZATCA\Test\Unit;

use OpenSSLAsymmetricKey;
use phpseclib3\Crypt\EC;
use phpseclib3\File\X509;
use Salla\ZATCA\GenerateCSR;
use Salla\ZATCA\Models\CSRRequest;

class GenerateCSRTest extends \PHPUnit\Framework\TestCase
{
    /** @test */
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
}
