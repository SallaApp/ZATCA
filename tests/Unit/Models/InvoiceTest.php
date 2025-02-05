<?php

namespace Salla\ZATCA\Test\Unit\Models;

use PHPUnit\Framework\TestCase;
use Salla\ZATCA\Models\Invoice;
use Salla\ZATCA\Helpers\Certificate;

class InvoiceTest extends TestCase
{
    private $certificate;
    private $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->certificate = $this->createMock(Certificate::class);
        $this->invoice = new Invoice(
            'test_invoice_content',
            'test_hash',
            'test_qr_code',
            $this->certificate
        );
    }

    /** @test */
    public function it_returns_hash_correctly()
    {
        $this->assertEquals('test_hash', $this->invoice->getHash());
    }

    /** @test */
    public function it_returns_invoice_content_correctly()
    {
        $this->assertEquals('test_invoice_content', $this->invoice->getInvoice());
    }

    /** @test */
    public function it_returns_qr_code_correctly()
    {
        $this->assertEquals('test_qr_code', $this->invoice->getQRCode());
    }

    /** @test */
    public function it_returns_certificate_correctly()
    {
        $this->assertSame($this->certificate, $this->invoice->getCertificate());
    }
}
