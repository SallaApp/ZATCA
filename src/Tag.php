<?php

namespace Salla\ZATCA;

use InvalidArgumentException;

class Tag
{
    /**
     * ZATCA stores the tag in one byte.
     *
     * @see E-Invoice Security Features Implementation Standards, section 4.1.
     */
    const MAX_TAG = 255;

    /**
     * And the length in one byte, so a value can not exceed 255 bytes once it
     * is UTF-8 encoded. Kept separate from MAX_TAG so narrowing one limit
     * later cannot silently narrow the other.
     */
    const MAX_LENGTH = 255;

    protected $tag;

    protected $value;

    public function __construct($tag, $value)
    {
        $this->tag = $tag;
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * its important to get the number of bytes of a string instated of number of characters
     *
     * @return false|int
     */
    public function getLength()
    {
        return strlen((string) $this->getValue());
    }

    /**
     * @return string Returns a string representing the encoded TLV data structure.
     *
     * @throws InvalidArgumentException If the tag or the value does not fit in
     *         the single byte ZATCA allows for each.
     */
    public function __toString()
    {
        $value = (string) $this->getValue();

        return $this->toTagByte().$this->toLengthByte().($value);
    }

    /**
     * To convert the tag to a single unsigned byte.
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function toTagByte()
    {
        $tag = $this->getTag();

        if ($tag < 0 || $tag > self::MAX_TAG) {
            throw new InvalidArgumentException(sprintf(
                'Tag id %d is out of range, ZATCA stores the tag in a single byte (0 to %d).',
                $tag,
                self::MAX_TAG
            ));
        }

        return chr($tag);
    }

    /**
     * To convert the length of the value to a single unsigned byte.
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function toLengthByte()
    {
        $length = $this->getLength();

        if ($length > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'Tag %d: the value is %d bytes once UTF-8 encoded, but ZATCA stores the length in a single byte (max %d).',
                $this->getTag(),
                $length,
                self::MAX_LENGTH
            ));
        }

        return chr($length);
    }
}
