<?php

namespace Salla\ZATCA\Tags;

use Salla\ZATCA\Tag;

class TaxNumber extends Tag
{
    public function __construct($value)
    {
        parent::__construct(2, $value);
    }
}
