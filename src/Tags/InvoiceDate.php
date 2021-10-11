<?php

namespace Salla\ZATCA\Tags;

use Salla\ZATCA\Tag;

class InvoiceDate extends Tag
{
    public function __construct($value)
    {
        parent::__construct(3, $value);
    }
}
