<?php

namespace Salla\ZATCA\Helpers;

class UXML extends \UXML\UXML
{
    /**
     * Create instance from XML string
     *
     * @param string $xmlString XML string
     * @return self              Root XML element
     * @throws InvalidArgumentException if failed to parse XML
     */
    public static function fromString(string $xmlString): self
    {
        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;

        if ($doc->loadXML($xmlString) === false) {
            throw new InvalidArgumentException('Failed to parse XML string');
        }
        return new static($doc->documentElement);
    }

    /**
     * Delete element by xpath
     *
     * @param string $xpath XPath query relative to this element
     * @return self|null        First matched element or NULL if not found
     */
    public function removeByXpath(string $xpath)
    {
        if ($node = $this->get($xpath)) {
            $node->remove();
        }

        return $this;
    }
}
