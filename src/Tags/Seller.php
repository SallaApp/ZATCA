<?php

namespace Salla\ZATCA\Tags;

use Salla\ZATCA\Tag;

class Seller extends Tag
{
    public function __construct($value)
    {
        parent::__construct(1, $value);
    }
}
