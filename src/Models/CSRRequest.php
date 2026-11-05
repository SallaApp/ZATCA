<?php

namespace Salla\ZATCA\Models;

use Salla\ZATCA\Exception\CSRValidationException;

class CSRRequest
{
    const SANDBOX = 'sandbox';

    const SIMULATION = 'simulation';

    const PRODUCTION = 'production';

    /**
     * EGS Serial number (1-SolutionName|2-ModelOrVersion|3-serialNumber)
     *
     * @var string
     */
    protected $serial_number;

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


    /**
     * the current Active environment in zatca
     * will support three modes (sandbox, simulation, production)
     * @var string
     */
    protected $currentEnv = 'sandbox';


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
        $this->assertVatGroupMemberTin($this->UID, $organizationalUnitName);

        $this->organizationalUnitName = $organizationalUnitName;
        return $this;
    }

    /**
     * A VAT group's number has `1` as its 11th digit; its organizational unit name
     * must then be the 10-digit TIN of the group member. Checked once both are set,
     * whichever setter runs last.
     */
    private function assertVatGroupMemberTin(?string $UID, ?string $organizationalUnitName): void
    {
        if ($UID === null || $organizationalUnitName === null || $UID[10] !== '1') {
            return;
        }

        if (! preg_match('/^\d{10}$/', $organizationalUnitName)) {
            throw new CSRValidationException('The Organization Unit Name Must Match this (If 11th digit of Organization Identifier(UID) = 1 then needs to be 10 digit number)', 422);
        }
    }


    public function setCountryName(string $countryName): self
    {
        if (strlen($countryName) !== 2) {
            throw new CSRValidationException('The Country name must be Two chars only', 422);
        }

        $this->countryName = $countryName;
        return $this;
    }


    public function setUID(string $UID): self
    {
        if (! preg_match('/^3\d{13}3$/', $UID)) {
            throw new CSRValidationException('The Organization Identifier must be 15 digits, starting andending with 3 ', 422);
        }

        $this->assertVatGroupMemberTin($UID, $this->organizationalUnitName);

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

    public function setCurrentZatcaEnv(string $currentEnv): CSRRequest
    {
        $this->currentEnv = $currentEnv;

        return $this;
    }

    public function isSandboxEnv(): bool
    {
        return $this->currentEnv === self::SANDBOX;
    }

    public function isSimulationEnv(): bool
    {
        return $this->currentEnv === self::SIMULATION;
    }

    public function isProduction(): bool
    {
        return $this->currentEnv === self::PRODUCTION;
    }

    public function toArray(): array
    {
        return [
            'dn' => [
                "CN" => $this->getCommonName(),
                "organizationName" => $this->getOrganizationName(),
                "organizationalUnitName" => $this->getOrganizationalUnitName(),
                "C" => $this->getCountry()
            ],
            'subject' => [
                "SN" => $this->getSerialNumber(),
                "UID" => $this->getUID(),
                "title" => $this->getInvoiceType(),
                "registeredAddress" => $this->getRegisteredAddress(),
                "businessCategory" => $this->getBusinessCategory()
            ]
        ];
    }

    public function getSerialNumber(): string
    {
        return $this->serial_number;
    }

    public function getCommonName(): string
    {
        return $this->commonName;
    }

    public function getOrganizationName(): string
    {
        return $this->organizationName;
    }

    public function getOrganizationalUnitName(): string
    {
        return $this->organizationalUnitName;
    }

    public function getUID(): string
    {
        return $this->UID;
    }

    public function getInvoiceType(): string
    {
        return $this->invoiceType;
    }

    private function getRegisteredAddress(): string
    {
        return $this->registeredAddress;
    }

    public function getBusinessCategory(): string
    {
        return $this->businessCategory;
    }

    public function getCountry(): string
    {
        return $this->countryName;
    }
}
