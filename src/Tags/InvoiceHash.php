<?php

namespace Salla\ZATCA\Tags;

use Salla\ZATCA\Tag;

class InvoiceHash extends Tag
{
    public function __construct($value)
    {
        parent::__construct(6, $value);
    }
}
