<?php

namespace Salla\ZATCA;

use Salla\ZATCA\Models\CSR;
use Salla\ZATCA\Models\CSRRequest;

class GenerateCSR
{
    protected $opensslConfig = [
        "digest_alg"       => "sha256",
        "private_key_bits" => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_EC,
        'curve_name'       => 'secp256k1',
        'req_extensions'   => 'v3_req'
    ];

    protected $tempConf = <<<EOL
# ------------------------------------------------------------------
# Default section for "req" command options -
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
1.3.6.1.4.1.311.20.2 = ASN1:UTF8String:TSTZATCA-Code-Signing
subjectAltName=dirName:subject

[ subject ]
EOL;

    protected $data = [];
    protected $CSRRequest ;

    final public function __construct(CSRRequest $CSRRequest)
    {
        $this->CSRRequest = $CSRRequest;
        $this->data = $CSRRequest->toArray();
    }

    public function initialize(): self
    {
        $this->opensslConfig['config'] = tempnam(sys_get_temp_dir(), "zakact_openssl_config_");

        // prepare openssl config file.
        $subject = implode("\n", array_map(function ($name, $value) {
            return "{$name} = {$value}";
        }, array_keys($this->data['subject']), $this->data['subject']));


        //replace the "1.3.6.1.4.1.311.20.2 = ASN1:UTF8String:TSTZATCA-Code-Signing"  with target env signing letter
        //@see https://zatca.gov.sa/en/E-Invoicing/Introduction/Guidelines/Documents/Fatoora_Portal_User_Manual_English.pdf page 30
        if ($this->CSRRequest->isSimulationEnv()) {
            $this->tempConf =  str_replace('ASN1:UTF8String:TSTZATCA-Code-Signing', 'ASN1:PRINTABLESTRING:PREZATCA-Code-Signing', $this->tempConf);
        }
        else if ($this->CSRRequest->isProduction()) {
            $this->tempConf =  str_replace('ASN1:UTF8String:TSTZATCA-Code-Signing', 'ASN1:PRINTABLESTRING:ZATCA-Code-Signing', $this->tempConf);
        }

        file_put_contents($this->opensslConfig['config'], $this->tempConf . "\n" . $subject . "\n");

        return $this;
    }


    public function generate(): CSR
    {
        if (! ($privateKey = openssl_pkey_new($this->opensslConfig))) {
            while (($error = openssl_error_string()) !== false) {
                $this->logErrors(function () use ($error) {
                    return $error . "\n";
                });
            }
            throw new \RuntimeException('Error Generating New Private Key');
        }

        $csr = openssl_csr_new($this->data['dn'], $privateKey, $this->opensslConfig);

        if (! ($csr)) {
            while (($error = openssl_error_string()) !== false) {
                $this->logErrors(function () use ($error) {
                    return $error . "\n";
                });
            }
            throw new \Exception('Error Generating New Certificate Signing Request');
        }

        openssl_csr_export($csr, $csrAsString);

        @unlink($this->opensslConfig['config']);


        return new CSR($csrAsString, $privateKey);
    }

    public static function fromRequest(CSRRequest $CSRRequest): GenerateCSR
    {
        return new static($CSRRequest);
    }

    private function logErrors($msg): void
    {
        openlog('CSRErrLog', LOG_CONS | LOG_PID | LOG_PERROR, LOG_USER);
        syslog(LOG_ERR, $msg);
        closelog();
    }
}
