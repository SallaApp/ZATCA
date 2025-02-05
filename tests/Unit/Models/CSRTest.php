<?php

namespace Salla\ZATCA\Test\Unit\Models;

use PHPUnit\Framework\TestCase;
use Salla\ZATCA\Models\CSR;

class CSRTest extends TestCase
{
    private $csrContent = '-----BEGIN CERTIFICATE REQUEST-----\nMIIBXTCCAQMCAQAwgYkxCzAJBgNVBAYTAlNBMQ8wDQYDVQQKDAZTYWxsYWgxHjAc\nBgNVBAsMFVNhbGxhaCBCcmFuY2ggQ29tcGFueTEeMBwGA1UEAwwVVFNULTEyMzQ1\nNjc4OS0xMjM0NTY3ODkxKTAnBgNVBGEMIFRTVHwyLVRTVHwzLTEyMzQ1Njc4OS0x\nMjM0NTY3ODkwWTATBgcqhkjOPQIBBggqhkjOPQMBBwNCAAQNbXrW7v3CbbtScrJU\nkqDWDzFukqZyHklZ5bPXCMg6h3ZKUsjwkYkEz4JHXhF1VYhpZKRc1PZ2/gn4W9Zh\noAAwCgYIKoZIzj0EAwIDSAAwRQIhAJ6BtcRsGEzH2E6HHGd8VqoWh1r8XNY4YqhV\nT/8vP9PvAiAyS5JxYBxhqwzaH+M+tvm3x5RyCQ5Nm/LYg4YKO4JhCA==\n-----END CERTIFICATE REQUEST-----';
    private $privateKey = '-----BEGIN EC PRIVATE KEY-----\nMHcCAQEEIHMtXBWaG1DFXZXo1eqYyWTZA9jvkUQYwT0Ar1NhTkPooAoGCCqGSM49\nAwEHoUQDQgAEDW161u79wm27UnKyVJKg1g8xbpKmch5JWeWz1wjIOod2SlLI8JGJ\nBM+CR14RdVWIaWSkXNT2dv4J+FvWYaAA\n-----END EC PRIVATE KEY-----';
    private $csr;

    protected function setUp(): void
    {
        parent::setUp();
        $this->csr = new CSR($this->csrContent, $this->privateKey);
    }

    /** @test */
    public function it_returns_csr_content_correctly()
    {
        $this->assertEquals($this->csrContent, $this->csr->getCsrContent());
    }

    /** @test */
    public function it_returns_private_key_correctly()
    {
        $this->assertEquals($this->privateKey, $this->csr->getPrivateKey());
    }
}
