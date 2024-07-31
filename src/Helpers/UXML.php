<?php

namespace Salla\ZATCA\Helpers;

use DOMDocument;
use DOMElement;
use DOMException;
use DOMXPath;
use InvalidArgumentException;
use Salla\ZATCA\Tags\CertificateSignature;
use Salla\ZATCA\Tags\InvoiceDate;
use Salla\ZATCA\Tags\InvoiceDigitalSignature;
use Salla\ZATCA\Tags\InvoiceHash;
use Salla\ZATCA\Tags\InvoiceTaxAmount;
use Salla\ZATCA\Tags\InvoiceTotalAmount;
use Salla\ZATCA\Tags\PublicKey;
use Salla\ZATCA\Tags\Seller;
use Salla\ZATCA\Tags\TaxNumber;
use WeakMap;

use function class_exists;
use function count;
use function preg_replace_callback;
use function strpos;
use function strstr;

class UXML
{
    const NS_PREFIX = '__uxml_ns_';

    /**
     * DOMElement instances
     *
     * Map of DOMElement references used from PHP 8.0 to avoid "Creation of dynamic property" deprecation warning.
     * In previous versions, a custom DOMElement::$uxml property is used to keep a reference to the UXML instance.
     *
     * @var WeakMap<DOMElement,self>|null|false
     */
    private static $elements = null;

    /** @var DOMElement */
    protected $element;

    /**
     * Create instance from XML string
     *
     * @param string $xmlString XML string
     * @return self              Root XML element
     * @throws InvalidArgumentException if failed to parse XML
     */
    public static function fromString(string $xmlString): self
    {
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = true;
        $doc->formatOutput = true;

        // we need to make sure the original xml have 4 indentation in its lines
        if (!str_contains($xmlString, '    <cbc:ProfileID>')) {
            $xmlString = preg_replace('/^[ ]+(?=<)/m', '$0$0', $xmlString);
        }

        if ($doc->loadXML($xmlString) === false) {
            throw new InvalidArgumentException('Failed to parse XML string');
        }
        return new self($doc->documentElement);
    }

    /**
     * Create instance from DOM element
     *
     * @param DOMElement $element DOM element
     * @return self                Wrapped element as a UXML instance
     * @suppress PhanUndeclaredProperty,PhanPossiblyNonClassMethodCall
     */
    public static function fromElement(DOMElement $element): self
    {
        // For PHP versions supporting WeakMap
        if (self::$elements) {
            return self::$elements->offsetExists($element) ?
                self::$elements->offsetGet($element) :
                new self($element);
        }

        // Fallback to dynamic properties
        return $element->uxml ?? new self($element);
    }

    /**
     * Create new instance
     *
     * @param string $name Element tag name
     * @param string|null $value Element value or `null` for empty
     * @param array<string,string> $attrs Element attributes
     * @param DOMDocument|null $doc Document instance
     * @return self                        New instance
     * @throws DOMException if failed to create new instance
     */
    public static function newInstance(string $name, ?string $value = null, array $attrs = [], ?DOMDocument $doc = null): self
    {
        $targetDoc = ($doc === null) ? new DOMDocument() : $doc;

        // Get namespace
        $prefix = strstr($name, ':', true) ?: '';
        $namespace = $attrs[empty($prefix) ? 'xmlns' : "xmlns:$prefix"] ?? $targetDoc->lookupNamespaceUri($prefix);
        try {
            // Create element
            $domElement = ($namespace === null) ?
                $targetDoc->createElement($name) :
                $targetDoc->createElementNS($namespace, $name);
            if ($domElement === false) {
                throw new DOMException('Failed to create DOMElement');
            }

            // Append element to document (in case of new document)
            if ($doc === null) {
                $targetDoc->appendChild($domElement);
            }

            // Set content
            if ($value !== null) {
                $domElement->textContent = $value;
            }

            // Set attributes
            foreach ($attrs as $attrName => $attrValue) {
                if ($attrName === 'xmlns' || strpos($attrName, 'xmlns:') === 0) {
                    $domElement->setAttributeNS('http://www.w3.org/2000/xmlns/', $attrName, $attrValue);
                } else {
                    $domElement->setAttribute($attrName, $attrValue);
                }
            }
        } catch (\Exception $ex) {
            throw new \Exception('errorr' . $ex->getMessage() . ', name:' . $name);
        }
        // Create instance
        return new self($domElement);
    }

    /**
     * Class constructor
     *
     * @param DOMElement $element DOM Element instance
     * @suppress PhanUndeclaredProperty
     */
    private function __construct(DOMElement $element)
    {
        // Initialize map of elements (if needed)
        if (self::$elements === null) {
            self::$elements = class_exists(WeakMap::class) ? new WeakMap() : false;
        }

        // Setup new instance
        $this->element = $element;
        if (self::$elements) {
            self::$elements->offsetSet($this->element, $this); // @phan-suppress-current-line PhanPossiblyNonClassMethodCall
        } else {
            $this->element->uxml = $this;
        }
    }

    /**
     * Get DOM element instance
     *
     * @return DOMElement DOM element instance
     */
    public function element(): DOMElement
    {
        return $this->element;
    }

    /**
     * Get parent element
     *
     * @return self Parent element instance or this instance if it has no parent
     */
    public function parent(): self
    {
        $parentNode = $this->element->parentNode;
        return ($parentNode !== null && $parentNode instanceof DOMElement) ? self::fromElement($parentNode) : $this;
    }

    /**
     * Is empty
     *
     * @return boolean `true` if the element has no inner content, `false` otherwise
     */
    public function isEmpty(): bool
    {
        return ($this->element->childNodes->length === 0);
    }

    /**
     * Add child element
     *
     * @param string $name New element tag name
     * @param string|null $value New element value or `null` for empty
     * @param array $attrs New element attributes
     * @return self               New element instance
     * @throws DOMException if failed to create child element
     */
    public function add(string $name, ?string $value = null, array $attrs = []): self
    {
        $child = self::newInstance($name, $value, $attrs, $this->element->ownerDocument);
        $this->element->appendChild($child->element);
        return $child;
    }

    /**
     * Find elements
     *
     * @param string $xpath XPath query relative to this element
     * @param int|null $limit Maximum number of results to return
     * @return self[]          Matched elements
     */
    public function getAll(string $xpath, ?int $limit = null): array
    {
        $namespaces = [];
        $xpath = preg_replace_callback('/{(.+?)}/', static function ($match) use (&$namespaces) {
            $ns = $match[1];
            if (!isset($namespaces[$ns])) {
                $namespaces[$ns] = self::NS_PREFIX . count($namespaces);
            }
            return $namespaces[$ns] . ':';
        }, $xpath);

        // Create instance
        // @phan-suppress-next-line PhanTypeMismatchArgumentNullableInternal
        $xpathInstance = new DOMXPath($this->element->ownerDocument);
        foreach ($namespaces as $ns => $prefix) {
            $xpathInstance->registerNamespace($prefix, $ns);
        }

        // Parse results
        $res = [];
        $domNodes = $xpathInstance->query($xpath, $this->element);
        foreach ($domNodes as $domNode) {
            if (!$domNode instanceof DOMElement) continue;
            $res[] = self::fromElement($domNode);
            if ($limit !== null && --$limit <= 0) break;
        }

        return $res;
    }

    /**
     * Find one element
     *
     * @param string $xpath XPath query relative to this element
     * @return self|null        First matched element or NULL if not found
     */
    public function get(string $xpath): ?self
    {
        $res = $this->getAll($xpath, 1);
        return $res[0] ?? null;
    }

    /**
     * Remove this element
     *
     * After calling this method on an instance it will become unusable.
     * Calling it on a root element will have no effect.
     */
    public function remove(): void
    {
        $parent = $this->element->parentNode;
        if ($parent !== null) {
            $parent->removeChild($this->element);
        }
    }

    public function removeByXpath($xpath): self
    {
        if ($node = $this->get($xpath)) {
            $node->remove();
        }

        return $this;
    }

    public function removeParentByXpath($xpath): self
    {
        if ($node = $this->get($xpath)) {
            $node->parent()->remove();
        }

        return $this;
    }

    /**
     * Export element and children as text
     *
     * @return string Text representation
     */
    public function asText(): string
    {
        return trim($this->element->textContent);
    }

    /**
     * Export as XML string
     *
     * @param string|null $version Document version, `null` for no declaration
     * @param string $encoding Document encoding
     * @param boolean $format Format output
     * @return string                XML string
     */
    public function asXML(?string $version = '1.0', string $encoding = 'UTF-8', bool $format = true): string
    {
        $doc = new DOMDocument();

        // Define document properties
        if ($version === null) {
            $doc->xmlStandalone = true;
        } else {
            $doc->xmlVersion = $version;
        }
        $doc->encoding = $encoding;
        $doc->formatOutput = $format;

        // Export XML string
        $rootNode = $doc->importNode($this->element, true);
        if ($rootNode !== false) {
            $doc->appendChild($rootNode);
        }
        $res = ($version === null) ? $doc->saveXML($doc->documentElement) : $doc->saveXML();
        unset($doc);

        return $res;
    }

    /**
     * Generate Tags array for QR generation base in the current invoice xml
     *
     * @param Certificate $certificate
     * @param string|null $invoiceHash the base64 encoded string of the binary invoice hash
     * @param string|null $digitalSignature the digital signature for the invoice hash
     * @return array
     */
    public function toTagsArray(Certificate $certificate, ?string $invoiceHash, ?string $digitalSignature): array
    {
        if (!$invoiceHash) {
            $invoiceHash = $this->getXmlHash();
        }

        if (!$digitalSignature) {
            $digitalSignature = base64_encode($certificate->getPrivateKey()->sign(base64_decode($invoiceHash)));
        }

        $issueDate = $this->get("cbc:IssueDate")->asText();
        $issueTime = $this->get("cbc:IssueTime")->asText();
        $issueTime = stripos($issueTime, 'Z') === false ? $issueTime . 'Z' : $issueTime;

        $qrArray = [
            new Seller($this->get("cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName")->asText()), // Seller׳s name
            new TaxNumber($this->get("cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID")->asText()), // VAT registration number of the seller
            new InvoiceDate($issueDate . 'T' . $issueTime), // invoice date as Zulu ISO8601 - Time stamp of the invoice (date and time)
            new InvoiceTotalAmount($this->get("cac:LegalMonetaryTotal/cbc:TaxInclusiveAmount")->asText()), //Invoice total (with VAT)
            new InvoiceTaxAmount($this->get("cac:TaxTotal")->asText()), // VAT total
            new InvoiceHash($invoiceHash), // Hash of XML invoice
            new InvoiceDigitalSignature($digitalSignature), // ECDSA signature
            new PublicKey(base64_decode($certificate->getPlainPublicKey())) //ECDSA public key
        ];

        /**
         * For Simplified Tax Invoices and their associated notes, the ECDSA signature of the cryptographic stamp’s public key by ZATCA’s technical CA
         * @link https://zatca.gov.sa/ar/E-Invoicing/SystemsDevelopers/Documents/20220624_ZATCA_ElectronicE-invoicing_Detailed_Technical_Guidelines.pdf page 61
         */
        $startOfInvoiceTypeCode = $this->get("cbc:InvoiceTypeCode");
        $isSimplified = $startOfInvoiceTypeCode && str_starts_with($startOfInvoiceTypeCode->element()->getAttribute('name'), "02");

        if ($isSimplified) {
            $qrArray = array_merge($qrArray, [new CertificateSignature($certificate->getCertificateSignature())]);
        }

        return $qrArray;
    }

    /**
     * Generate invoice hash for the current xml as base64 encoded.
     *
     * @return string
     */
    public function getXmlHash(): string
    {
        $clonedXML = clone $this;

        /**
         * remove unwanted tags
         *
         * @link https://zatca.gov.sa/ar/E-Invoicing/Introduction/Guidelines/Documents/E-invoicing%20Detailed%20Technical%20Guidelines.pdf
         * @link page 53
         */
        $clonedXML->removeByXpath('ext:UBLExtensions');
        $clonedXML->removeByXpath('cac:Signature');
        $clonedXML->removeParentByXpath('cac:AdditionalDocumentReference/cbc:ID[. = "QR"]');

        return base64_encode(
            hash('sha256', $clonedXML->element()->C14N(false, false), true)
        );
    }

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        return $this->asXML(null, 'UTF-8', false);
    }
}
