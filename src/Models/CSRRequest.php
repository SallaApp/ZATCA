<?php

namespace Salla\ZATCA\Models;

class CSRRequest
{
    /**
     * EGS Serial number (1-SolutionName|2-ModelOrVersion|3-serialNumber)
     *
     * @var string
     */
    protected $serial_number;

    protected $is_sandbox_env = false;

    protected $vat_registration_number;

    protected $invoiceType = 1100;

    public static function make()
    {
        return new static();
    }


    public function setSerialNumber(string $solutionName, string $version, string $serialNumber): CSRRequest
    {
        $this->serial_number = sprintf('1-%s|2-%s|3-%s', $solutionName, $version, $serialNumber);

        return $this;
    }

    public function getSerialNumber(): string
    {
        return $this->serial_number;
    }

    public function setInvoiceType(bool $taxInvoice, bool $simplified): CSRRequest
    {
        $this->invoiceType = sprintf('%s%s00', (int)$taxInvoice, (int)$simplified);

        return $this;
    }


    public function setVatRegistrationNumber(string $vat_registration_number)
    {
        $this->vat_registration_number = $vat_registration_number;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getVatRegistrationNumber()
    {
        return $this->vat_registration_number;
    }

    public function toArray(): array
    {
        return [
            'dn' => [
                "commonName" => $this->getParameter('commonName'),// Common name (EGS TaxPayer PROVIDED ID [FREE TEXT])
                "organizationName" => $this->getParameter('organizationName'),// Organization name (Tax payer name)
                "organizationalUnitName" => $this->getParameter('organizationalUnitName'), // Organization Unit (Branch name)
                "countryName" => $this->getParameter('countryName'),//ISO2 country code is required with US as default
            ],
            'subject' => [
                # EGS Serial number (1-SolutionName|2-ModelOrVersion|3-serialNumber)
                "SN" => $this->getSerialNumber(),
                # VAT Registration number of TaxPayer (Organization identifier [15 digits begins with 3 and ends with 3])
                "UID" => $this->getParameter('UID'),
                # Invoice type (TSCZ)(1 = supported, 0 not supported) (Tax, Simplified, future use, future use)
                "title" => $this->getParameter('invoiceType'),
                # Location (branch address or website)
                "registeredAddress" => $this->getParameter('registeredAddress'),
                # Industry (industry sector name)
                "businessCategory" => "company", //$this->getParameter('businessCategory'), //todo make it
            ]
        ];
    }
}
