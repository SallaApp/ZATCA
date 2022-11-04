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


    /**
     *  Invoice type (TSCZ)(1 = supported, 0 not supported) (Tax, Simplified, future use, future use)
     *
     * @var string
     */
    protected $invoiceType = 1100;

    /**
     * Common name (EGS TaxPayer PROVIDED ID [FREE TEXT])
     *
     * @var string
     */
    protected $commonName;

    /**
     *  Organization name (Tax payer name)
     *
     * @var string
     */
    protected $organizationName;

    /**
     * Organization Unit (Branch name)
     *
     * @var string
     */
    protected $organizationalUnitName;

    /**
     * ISO2 country code is required with US as default
     *
     * @var string
     */
    protected $countryName;

    /**
     * VAT Registration number of TaxPayer (Organization identifier [15 digits begins with 3 and ends with 3])
     *
     * @var string
     */
    protected $UID;


    /**
     * Location (branch address or website)
     *
     * @var string
     */
    protected $registeredAddress;


    /**
     * Industry (industry sector name)
     *
     * @var string
     */
    protected $businessCategory = 'company';


    public function setCommonName(string $commonName): self
    {
        $this->commonName = $commonName;
        return $this;
    }


    public function setOrganizationName(string $organizationName): self
    {
        $this->organizationName = $organizationName;
        return $this;
    }


    public function setOrganizationalUnitName(string $organizationalUnitName): self
    {
        $this->organizationalUnitName = $organizationalUnitName;
        return $this;
    }

    public function setCountryName(string $countryName): self
    {
        $this->countryName = $countryName;
        return $this;
    }


    public function setUID(string $UID): self
    {
        $this->UID = $UID;
        return $this;
    }


    public function setRegisteredAddress(string $registeredAddress): self
    {
        $this->registeredAddress = $registeredAddress;
        return $this;
    }


    public function setBusinessCategory(string $businessCategory): self
    {
        $this->businessCategory = $businessCategory;
        return $this;
    }


    public static function make()
    {
        return new static();
    }


    public function setSerialNumber(string $solutionName, string $version, string $serialNumber): CSRRequest
    {
        $this->serial_number = sprintf('1-%s|2-%s|3-%s', $solutionName, $version, $serialNumber);

        return $this;
    }


    public function setInvoiceType(bool $taxInvoice, bool $simplified): CSRRequest
    {
        $this->invoiceType = sprintf('%s%s00', (int)$taxInvoice, (int)$simplified);

        return $this;
    }


    public function toArray(): array
    {
        return [
            'dn'      => [
                "commonName"             => $this->getCommonName(),
                "organizationName"       => $this->getOrganizationName(),
                "organizationalUnitName" => $this->getOrganizationalUnitName(),
                "countryName"            => $this->getCommonName()
            ],
            'subject' => [
                "SN"                => $this->getSerialNumber(),
                "UID"               => $this->getUID(),
                "title"             => $this->getInvoiceType(),
                "registeredAddress" => $this->getRegisteredAddress(),
                "businessCategory"  => $this->getBusinessCategory(),
            ]
        ];
    }


    public function getSerialNumber(): string
    {
        return $this->serial_number;
    }

    private function getCommonName(): string
    {
        return $this->commonName;
    }

    private function getOrganizationName(): string
    {
        return $this->organizationName;
    }

    private function getOrganizationalUnitName(): string
    {
        return $this->organizationalUnitName;
    }

    private function getUID(): string
    {
        return $this->UID;
    }

    private function getInvoiceType(): string
    {
        return $this->invoiceType;
    }

    private function getRegisteredAddress(): string
    {
        return $this->registeredAddress;
    }

    private function getBusinessCategory(): string
    {
        return $this->businessCategory;
    }
}
