<?php

namespace Salla\ZATCA;

use chillerlan\QRCode\QRCode;
use InvalidArgumentException;
use chillerlan\QRCode\QROptions;

class GenerateQrCode
{
    /**
     * @var Tag|Tag[] $data The data or list of tags
     */
    protected $data = [];

    /**
     * @param  Tag|Tag[]  $data  The data or list of tags
     *
     * @throws InvalidArgumentException If the TLV data structure
     *         contains other data than arrays and Tag instances.
     */
    private function __construct($data)
    {
        $this->data = array_filter($data, function ($tag) {
            return $tag instanceof Tag;
        });

        if (\count($this->data) === 0) {
            throw new InvalidArgumentException('malformed data structure');
        }
    }

    /**
     * Initial the generator from list of tags.
     *
     * @param  Tag[]  $data  The list of tags
     *
     * @return GenerateQrCode
     */
    public static function fromArray(array $data): GenerateQrCode
    {
        return new self($data);
    }

    /**
     * Encodes an TLV data structure.
     *
     * @return string Returns a string representing the encoded TLV data structure.
     */
    public function toTLV(): string
    {
        return implode('', array_map(function ($tag) {
            return (string) $tag;
        }, $this->data));
    }

    /**
     * Encodes an TLV as base64
     *
     * @return string Returns the TLV as base64 encode.
     */
    public function toBase64(): string
    {
        return base64_encode($this->toTLV());
    }

    /**
     * Render the QR code as base64 data image.
     *
     * @param  array  $options  The list of options for QROption (https://github.com/chillerlan/php-qrcode)
     * @param  string|null  $file  File string represent file path,name and extension
     *
     * @return string
     */
    public function render(array $options = [], string $file = null): string
    {
        $options = new QROptions($options);
        return (new QRCode($options))->render($this->toBase64(), $file);
    }
}
