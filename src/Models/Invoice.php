<?php


namespace Salla\ZATCA\Models;


class Invoice
{
    private $hash;

    private $invoice;

    public function __construct(string $invoice, string $hash)
    {
        $this->invoice = $invoice;
        $this->hash    = $hash;
    }


    public function getHash(): string
    {
        return $this->hash;
    }

    public function getInvoice(): string
    {
        return $this->invoice;
    }

}
