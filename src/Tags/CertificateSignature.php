<?php

namespace Salla\ZATCA\Tags;

use Salla\ZATCA\Tag;

class CertificateSignature extends Tag
{
    public function __construct($value)
    {
        parent::__construct(9, $value);
    }
}
