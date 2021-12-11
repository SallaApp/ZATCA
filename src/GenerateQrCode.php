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
     * @return string
     */
    public function render($scale = 5): string
    {
        $options = new QROptions(
            [
              'scale' => $scale,
            ]
          );
        return (new QRCode($options))->render($this->toBase64());
    }
}
