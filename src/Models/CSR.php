<?php

namespace Salla\ZATCA\Models;

class CSR
{
    private $csrContent;

    private $privateKey;

    public function __construct(string $csrContent, $privateKey)
    {
        $this->csrContent = $csrContent;
        $this->privateKey = $privateKey;
    }


    public function getCsrContent(): string
    {
        return $this->csrContent;
    }

    public function getPrivateKey()
    {
        return $this->privateKey;
    }

}
