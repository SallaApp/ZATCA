<?php

namespace Salla\ZATCA\Models;

class CSR
{
    private $scrContent;

    private $privateKey;

    public  function __construct($scrContent, $privateKey)
    {
        $this->scrContent = $scrContent;
        $this->privateKey = $privateKey;
    }


    public function getScrContent()
    {
        return $this->scrContent;
    }

    public function getPrivateKey()
    {
        return $this->privateKey;
    }

}
