<?php

namespace Salla\ZATCA\Models;

class CSR
{
    /**
     * @var string
     */
    private $scrContent;


    /**
     * @var string
     */
    private $privateKey;

    public  function __construct(string $scrContent, string $privateKey)
    {
        $this->scrContent = $scrContent;
        $this->privateKey = $privateKey;
    }

    /**
     * @return string
     */
    public function getScrContent(): string
    {
        return $this->scrContent;
    }

    /**
     * @return string
     */
    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

}
