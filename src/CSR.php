<?php

namespace Salla\ZATCA;

use App\Traits\HasParameters;
use OpenSSLAsymmetricKey;
use Symfony\Component\HttpFoundation\ParameterBag;

class CSR
{
    protected $subject = [];

    /**
     * The card parameters.
     *
     * @var \Symfony\Component\HttpFoundation\ParameterBag
     */
    protected $parameters;


    protected $tempFile;


    protected $opensslConfig;


    protected $tempConf = <<<EOL
# ------------------------------------------------------------------
# Default section for "req" command options
# ------------------------------------------------------------------
[req]
# Password for reading in existing private key file
# input_password = SET_PRIVATE_KEY_PASS
# Prompt for DN field values and CSR attributes in ASCII
prompt = no
utf8 = no

# Section pointer for DN field options
distinguished_name = dn

# ------------------------------------------------------------------
# Section for prompting DN field values to create "subject"
# ------------------------------------------------------------------
[ dn ]

[ v3_req ]
#basicConstraints=CA:FALSE
#keyUsage = digitalSignature, keyEncipherment
# Production or Testing Template (TSTZATCA-Code-Signing - ZATCA-Code-Signing)
1.3.6.1.4.1.311.20.2 = ASN1:UTF8String:ZATCA-Code-Signing
subjectAltName=dirName:subject

[ subject ]
EOL;

    final public function __construct(array $parameters = [])
    {
        $this->initialize($parameters);
        $this->initCsrGeneration();

    }

    public function initialize(array $parameters = null): self
    {
        $this->parameters = new ParameterBag($parameters);

        return $this;
    }


    private function initCsrGeneration(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), "zakact_openssl_config_");

        $this->opensslConfig = [
            "digest_alg"       => "sha256",
            "private_key_bits" => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name'       => 'secp256k1',
            'config'           => $this->tempFile,
            'req_extensions'   => 'v3_req'
        ];

        $this->initSubject();
    }


    public function initSubject()
    {
        $this->subject = [
            # EGS Serial number (1-SolutionName|2-ModelOrVersion|3-serialNumber)
            "SN"                => $this->getParameter('SN'),
            # VAT Registration number of TaxPayer (Organization identifier [15 digits begins with 3 and ends with 3])
            "UID"               => $this->getParameter('UID'),
            # Invoice type (TSCZ)(1 = supported, 0 not supported) (Tax, Simplified, future use, future use)
            "title"             => 1100,
            # Location (branch address or website)
            "registeredAddress" => $this->getParameter('registeredAddress'),
            # Industry (industry sector name)
            "businessCategory"  => "company",
        ];

        $this->subject = implode("\n", array_map(function ($name, $value) {
            return "{$name} = {$value}";
        }, array_keys($this->subject), $this->subject));

        return file_put_contents($this->tempFile, $this->tempConf . "\n" . $this->subject . "\n");
    }


    public function generateCsr():string
    {
        /**@var OpenSSLAsymmetricKey $privateKey */
        $privateKey = openssl_pkey_new($this->opensslConfig);

        $csr = openssl_csr_new($this->getBaseCsrInfo(), $privateKey, $this->opensslConfig);

        openssl_csr_export($csr, $csrAsString);

        unlink($this->tempFile);

        return $csrAsString;
    }

    private function getBaseCsrInfo(): array
    {
        return [
            "commonName"             =>  $this->getParameter('commonName'),// Common name (EGS TaxPayer PROVIDED ID [FREE TEXT])
            "organizationName"       =>  $this->getParameter('organizationName'),// Organization name (Tax payer name)
            "organizationalUnitName" =>  $this->getParameter('organizationalUnitName'), // Organization Unit (Branch name)
            "countryName"            => $this->getParameter('countryName'),//ISO2 country code is required with US as default
        ];
    }


    /**
     * Get one parameter.
     *
     * @return mixed A single parameter value.
     */
    protected function getParameter($key)
    {
        return $this->parameters->get($key);
    }

}
