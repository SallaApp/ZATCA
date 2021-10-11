<?php

namespace Salla\ZATCA\Tags;

use Salla\ZATCA\Tag;

class InvoiceTotalAmount extends Tag
{
    public function __construct($value)
    {
        parent::__construct(4, $value);
    }
}
