<?php


namespace Salla\ZATCA\Test\Unit;

use phpseclib3\Crypt\EC;
use phpseclib3\File\X509;
use Salla\ZATCA\GenerateCSR;
use Salla\ZATCA\GenerateQrCode;
use Salla\ZATCA\Models\CSRRequest;
use Salla\ZATCA\Tag;
use Salla\ZATCA\Tags\InvoiceDate;
use Salla\ZATCA\Tags\InvoiceTaxAmount;
use Salla\ZATCA\Tags\InvoiceTotalAmount;
use Salla\ZATCA\Tags\Seller;
use Salla\ZATCA\Tags\TaxNumber;

class GenerateCSRTest extends \PHPUnit\Framework\TestCase
{
    /** @test */
    public function shouldGenerateACsrWithProperiteis()
    {
        try {
            $csr_request = GenerateCSR::fromRequest(
                CSRRequest::make()
                    ->setUID('311111111101113')
                    ->setSerialNumber('200000', 'Salla Store', 'Merchant Name')
                    ->setCommonName('Salla')
                    ->setCountryName('SA')
                    ->setOrganizationName('Salla Store')
                    ->setOrganizationalUnitName('3311111111')
                    ->setRegisteredAddress('3355  - حي الملك فهد مكة المكرمة 24347 - 7192')
                    ->setInvoiceType(true, true)
                    ->setIsSandBoxEnv(true)
                    ->setBusinessCategory('company')
            )->initialize()
                ->generate();

        } catch (\Exception $exception) {
            //throw new \Exception($exception->getMessage());
        }

        openssl_pkey_export($csr_request->getPrivateKey(), $exported);

        $arraySubject = openssl_csr_get_subject($csr_request->getCsrContent());
        $publicKey    = openssl_pkey_get_details(openssl_csr_get_public_key($csr_request->getCsrContent()))['key'];

        $X509         = new X509();
        $X509->loadCSR($csr_request->getCsrContent());

        $X509->setPrivateKey(EC::loadPrivateKey($exported));

        $this->assertEquals('OpenSSL key', get_resource_type($csr_request->getPrivateKey()));

        $this->assertIsArray($X509->getCurrentCert());

        $publicKeyX509 = $X509->getPublicKey()->toString('PKCS8');

        $this->assertIsString($publicKeyX509);

        $this->assertIsString($publicKey);

        $this->assertEquals(str_replace("\r\n","",$publicKeyX509),str_replace("\n","",$publicKey));
    }
}
