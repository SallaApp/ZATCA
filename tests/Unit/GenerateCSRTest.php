<?php


namespace Salla\ZATCA\Test\Unit;

use Salla\ZATCA\GenerateQrCode;
use Salla\ZATCA\Models\CSRRequest;
use Salla\ZATCA\Tag;
use Salla\ZATCA\Tags\InvoiceDate;
use Salla\ZATCA\Tags\InvoiceTaxAmount;
use Salla\ZATCA\Tags\InvoiceTotalAmount;
use Salla\ZATCA\Tags\Seller;
use Salla\ZATCA\Tags\TaxNumber;
use Salla\ZATCA\Test\TestCase;

class GenerateCSRTest extends TestCase
{
    /** @test */
    public function shouldGenerateAQrCode()
    {
        try {
            $csr_request = CSRRequest::make()
                ->setInvoiceType()
                ->setSerialNumber()
                ->setVatRegistrationNumber();

           $arrayRequest =  GenerateQrCode::fromRequest($csr_request);
        } catch (\Exception $exception) {
            // ......
        }

        $this->assertEquals(
            'AQVTYWxsYQIKMTIzNDU2Nzg5MQMUMjAyMS0wNy0xMlQxNDoyNTowOVoEBjEwMC4wMAUFMTUuMDA=', $generatedString);
    }
}
