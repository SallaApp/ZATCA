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
# Default section for "req" command options - ZAY
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

    protected $data = [];

    final public function __construct(CSRRequest $CSRRequest)
    {
        $this->data = $CSRRequest->toArray();
    }

    public function initialize(): self
    {
        $this->opensslConfig['config'] = tempnam(sys_get_temp_dir(), "zakact_openssl_config_");
        // prepare openssl config file
        $subject = implode("\n", array_map(function ($name, $value) {
            return "{$name} = {$value}";
        }, array_keys($this->data['subject']), $this->data['subject']));

        // todo :: throw expetions if is failed
        file_put_contents($this->opensslConfig['config'], $this->tempConf . "\n" . $subject . "\n");

        return $this;
    }


    public function generate(): CSR
    {
        // todo :: handling throw expetions
        $privateKey = openssl_pkey_new($this->opensslConfig);

        // todo :: handling
        $csr = openssl_csr_new($this->data['dn'], $privateKey, $this->opensslConfig);

        openssl_csr_export($csr, $csrAsString);

        unlink($this->opensslConfig['config']);

        return new CSR($csrAsString, $privateKey);
    }
}
