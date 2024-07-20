<?php

namespace Salla\ZATCA\Helpers;

use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\EC;
use phpseclib3\File\X509;

/**
 * @package Salla\ZATCA\Helpers
 * @mixin X509
 */
class Certificate
{
    /**
     * @var string
     */
    protected string $plain_certificate;

    /**
     * @var X509
     */
    protected X509 $certificate;

    /**
     * @var PrivateKey
     */
    protected PrivateKey $privateKey;

    /**
     * @var string|null
     */
    protected ?string $secret_key;

    public function __construct(string $certificate, string $privateKey)
    {
        $this->plain_certificate = $certificate;
        $this->certificate       = (new X509());
        $this->certificate->loadX509($certificate);
        $this->privateKey = EC::loadPrivateKey($privateKey);
    }

    public function __call($name, $arguments)
    {
        return $this->certificate->{$name}(...$arguments);
    }

    public function getPrivateKey(): PrivateKey
    {
        return $this->privateKey;
    }

    public function setSecretKey(?string $secret_key): Certificate
    {
        $this->secret_key = $secret_key;

        return $this;
    }

    public function getPlainCertificate(): string
    {
        return $this->plain_certificate;
    }

    public function getCertificate(): X509
    {
        return $this->certificate;
    }

    /**
     * return Authorization bearer token by combine plain_certificate and secret_key
     * @return string
     */
    public function getAuthorizationHeader(): string
    {
        return 'Basic ' . base64_encode(base64_encode($this->getPlainCertificate()) . ':' . $this->getSecretKey());
    }

    /**
     * Generate a hash for the certificate
     */
    public function getHash(): string
    {
        return base64_encode(hash('sha256', $this->plain_certificate));
    }

    /**
     * Get public key as plain base64 text.
     *
     * @return string
     */
    public function getPlainPublicKey()
    {
        return str_replace([
            "-----BEGIN PUBLIC KEY-----\r\n",
            "\r\n-----END PUBLIC KEY-----", "\r\n"
        ], '', $this->getCertificate()->getPublicKey()->toString('PKCS8'));
    }

    /**
     * Get the secret key for Certificate
     * Witch used for auth http.
     *
     * @return string|null
     */
    public function getSecretKey(): ?string
    {
        return $this->secret_key;
    }

    /**
     * Get the signature of certificate
     */
    public function getCertificateSignature(): string
    {
        // the X509 add one byte as prefix for the signature, we'll remove it
        return substr($this->getCurrentCert()['signature'], 1);
    }

    /**
     * The DN rerun from certificate not formatted as well , so reformat it
     * @return string
     */
    public function getFormattedIssuerDN(): string
    {
        $dnArray = explode(",", str_replace(
                ["0.9.2342.19200300.100.1.25", "/", ", "], ["DC", ",", ","],
                $this->certificate->getIssuerDN(X509::DN_STRING)
            )
        );

        return implode(", ", array_reverse($dnArray));
    }
}
