<?php

namespace Salla\ZATCA\Tags;

use Salla\ZATCA\Tag;

class InvoiceDigitalSignature extends Tag
{
    public function __construct($value)
    {
        parent::__construct(7, $value);
    }
}
