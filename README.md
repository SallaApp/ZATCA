<div id="top"></div>
<div align="center"> 
  <a href="https://salla.dev"> 
    <img src="https://salla.dev/wp-content/themes/salla-portal/dist/img/salla-logo.svg" alt="Logo" width="80" height="80"> 
  </a>
  <h1 align="center">ZATCA (Fatoora) QR-Code Implementation</h1>
  <p align="center">
    An unofficial package maintained by <a href="https://salla.dev">Salla</a> to help developers to implement ZATCA (Fatoora) QR code easily which required for e-invoicing
    <br />
    <a href="https://salla.dev/"><strong>Explore our blogs »</strong></a>
    <br />
    <br />
    <a href="https://github.com/SallaApp/ZATCA/issues/new">Report Bug</a> · 
    <a href="https://github.com/SallaApp/ZATCA/discussions/new">Request Feature</a>
       · <a href="https://t.me/salladev">&lt;/Salla Developers&gt;</a>
  </p>
</div>

## Requirements

* PHP >= 7.2
* An mbstring extension

## Installation

You can install the package via composer:

```bash
$ composer require salla/zatca
```
<p align="right">(<a href="#top">back to top</a>)</p>

## Usage

### Generate Base64

```php
use Salla\ZATCA\GenerateQrCode;
use Salla\ZATCA\Tags\InvoiceDate;
use Salla\ZATCA\Tags\InvoiceTaxAmount;
use Salla\ZATCA\Tags\InvoiceTotalAmount;
use Salla\ZATCA\Tags\Seller;
use Salla\ZATCA\Tags\TaxNumber;

$generatedString = GenerateQrCode::fromArray([
    new Seller('Salla'), // seller name        
    new TaxNumber('1234567891'), // seller tax number
    new InvoiceDate('2021-07-12T14:25:09Z'), // invoice date as Zulu ISO8601 @see https://en.wikipedia.org/wiki/ISO_8601
    new InvoiceTotalAmount('100.00'), // invoice total amount
    new InvoiceTaxAmount('15.00') // invoice tax amount
])->toBase64();

// > Output
// AQVTYWxsYQIKMTIzNDU2Nzg5MQMUMjAyMS0wNy0xMlQxNDoyNTowOVoEBjEwMC4wMAUFMTUuMDA=
```

### Generate Plain

```php
use Salla\ZATCA\GenerateQrCode;
use Salla\ZATCA\Tags\InvoiceDate;
use Salla\ZATCA\Tags\InvoiceTaxAmount;
use Salla\ZATCA\Tags\InvoiceTotalAmount;
use Salla\ZATCA\Tags\Seller;
use Salla\ZATCA\Tags\TaxNumber;

$generatedString = GenerateQrCode::fromArray([
    new Seller('Salla'), // seller name        
    new TaxNumber('1234567891'), // seller tax number
    new InvoiceDate('2021-07-12T14:25:09Z'), // invoice date as Zulu ISO8601 @see https://en.wikipedia.org/wiki/ISO_8601
    new InvoiceTotalAmount('100.00'), // invoice total amount
    new InvoiceTaxAmount('15.00') // invoice tax amount
    // TODO :: Support others tags
])->toTLV();
```
<p align="right">(<a href="#top">back to top</a>)</p>

### Render A QR Code Image

You can render the tags as QR code image easily


```php
use Salla\ZATCA\GenerateQrCode;
use Salla\ZATCA\Tags\InvoiceDate;
use Salla\ZATCA\Tags\InvoiceTaxAmount;
use Salla\ZATCA\Tags\InvoiceTotalAmount;
use Salla\ZATCA\Tags\Seller;
use Salla\ZATCA\Tags\TaxNumber;

// data:image/png;base64, .........
$displayQRCodeAsBase64 = GenerateQrCode::fromArray([
    new Seller('Salla'), // seller name        
    new TaxNumber('1234567891'), // seller tax number
    new InvoiceDate('2021-07-12T14:25:09Z'), // invoice date as Zulu ISO8601 @see https://en.wikipedia.org/wiki/ISO_8601
    new InvoiceTotalAmount('100.00'), // invoice total amount
    new InvoiceTaxAmount('15.00') // invoice tax amount
])->render();

// now you can inject the output to src of html img tag :)
// <img src="$displayQRCodeAsBase64" alt="QR Code" />
```
<p align="right">(<a href="#top">back to top</a>)</p>

## Implement ZATCA's E-Invoicing requirements Phase 2

This library supports both Phase 1 and Phase 2.

Phase 2,include mandates integration of a taxpayer's system with the ZATCA, along with the transmission of e-invoices and e-notes to the ZATCA.

# Phase 2 Usage

### 1- Generating CSR content, based on parameters

````php
use Salla\ZATCA\GenerateCSR;
use Salla\ZATCA\Models\CSRRequest;

$privateKeyFilename = 'output file path name';
$csrFilename = 'output file path name';
$CSR = GenerateCSR::fromRequest(
    CSRRequest::make()
        ->setUID('string $OrganizationIdentifier')
        ->setSerialNumber('string $solutionName', 'string $version', 'string $serialNumber')
        ->setCommonName('string $commonName')
        ->setCountryName('SA')
        ->setOrganizationName('string $organizationName')
        ->setOrganizationalUnitName('string $organizationalUnitName')
        ->setRegisteredAddress('string $registeredAddress')
        ->setInvoiceType(true, true) //invoice types , the default is true, true
        ->setCurrentZatcaEnv('string $currentEnv') //support all modes ['sandbox','simulation','core']
        ->setBusinessCategory('string $businessCategory')
)->initialize()
    ->generate();

// writing the private_key to file 
openssl_pkey_export_to_file($CSR->getPrivateKey(), $privateKeyFilename);

//writing the csr_content to file
file_put_contents($csrFilename, $CSR->getCsrContent());

````


### 2- Signing Invoices 
```php
use Salla\ZATCA\Helpers\Certificate;
use Salla\ZATCA\Models\InvoiceSign;

$xmlInvoice = 'xml invoice text';

$certificate = new Certificate(
    'certificate plain text (base64 decoded)',
    'private key plain text'
);

$certificate->setSecretKey('secret key text');

$invoice = (new InvoiceSign($xmlInvoice, $certificate))->sign();

// invoice Hash: $invoice->getHash()
// invoice signed as XML: $invoice->getInvoice()
// Invoice QR code as base64: $invoice->getQRCode()
```
### 3- Generating QR Code As Base64

```php

<?php

use Salla\ZATCA\GenerateQrCode;
use Salla\ZATCA\Tag;
use Salla\ZATCA\Helpers\UXML;
use Salla\ZATCA\Helpers\Certificate;

    $xml = UXML::fromString('xml invoice text');
    $invoiceHashBinary = hash('sha256', $xml->element()->C14N(false, false), true);
    
    $certificate = new Certificate('certificate plain text (base64 decoded)', 'private key plain text');
    $certificate->setSecretKey('secret key text');
    
    $digitalSignature = base64_encode($certificate->getPrivateKey()->sign($invoiceHashBinary));
    
    $issueDate = trim($xml->get("cbc:IssueDate")->asText());
    $issueTime = trim($xml->get("cbc:IssueTime")->asText());
    $issueTime = stripos($issueTime, 'Z') === false ? $issueTime . 'Z' : $issueTime;
    
    $qrArray = [
        new Tag(1, trim($xml->get("cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName")->asText())), // Seller׳s name 
        new Tag(2, trim($xml->get("cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID")->asText())), // VAT registration number of the seller
        new Tag(3, $issueDate . 'T' . $issueTime), // invoice date as Zulu ISO8601 - Time stamp of the invoice (date and time)
        new Tag(4, trim($xml->get("cac:LegalMonetaryTotal/cbc:TaxInclusiveAmount")->asText())), //Invoice total (with VAT)
        new Tag(5, trim($xml->get("cac:TaxTotal")->asText())), // VAT total
        new Tag(6, base64_encode($invoiceHashBinary)), //Hash of XML invoice
        new Tag(7, $digitalSignature), // ECDSA signature
        new Tag(8, base64_decode($certificate->getPlainPublicKey())) //ECDSA public key
    ];
    
    
    /**
     * For Simplified Tax Invoices and their associated notes, the ECDSA signature of the cryptographic stamp’s public key by ZATCA’s technical CA
     * @link https://zatca.gov.sa/ar/E-Invoicing/SystemsDevelopers/Documents/20220624_ZATCA_ElectronicE-invoicing_Detailed_Technical_Guidelines.pdf page 61
     */
    
    $startOfInvoiceTypeCode = $xml->get("cbc:InvoiceTypeCode");
    $isSimplified = $startOfInvoiceTypeCode && strpos($startOfInvoiceTypeCode->element()->getAttribute('name'), "02") === 0;
    
    if ($isSimplified) {
        $qrArray = array_merge($qrArray, [new Tag(9, $certificate->getCertificateSignature())]);
    }
    
    $QRCodeAsBase64 = GenerateQrCode::fromArray($qrArray)->toBase64();

    //$QRCodeAsBase64 output will be like this 
    //AQ1TYWxsYSAtIFNhbGxhAg8zMTA0NjE0MzU3MDAwMDMDFDIwMjMtMTItMzFUMjE6MDA6MDBaBAY0MDguNjkFBTUzLjMxBiw1TXZmVmZTWGRSZzgyMWU4Q0E3bE1WcDdNS1J4Q2FBWWZHTm90THlHNUg4PQdgTUVRQ0lEOGthSTF1Z29EcWJkN3NEVmFCVE9yOUswWlVwRkZNY2hON2FsNGgyTEhrQWlCYnZxZktkK0xaN0hEc0FMakxmeTA0dTBMNVRhcjhxenowYjBlb0EzMUtIdz09CFgwVjAQBgcqhkjOPQIBBgUrgQQACgNCAATmBleqoCAfxDveLQVAKCvHSjNxoudWhRNQ8zThTxzBtgjAqZQ7vBJWvu2Ut0MxYa8vq7O4tgusgmcLBDhK/xNCCUcwRQIhAIhuJ6o4ETNSosMEf/OLVbp+TZqi2IGSxsgyC54yZgQAAiB3lwym6zpkPspQrT+luMte/ifw4THG+waV+SmXNSukmQ==
```



<p align="right">(<a href="#top">back to top</a>)</p>

## Read The QR-Code

The output of QR-Code is not readable for the human 👀, and some of QR-Code readers maybe show a invalid output because this QR-Code will be scan by the ZATCA apps later after the all steps of integration compete.
If you interested to see the output of your final QR-Code Image you can use the following website

https://www.onlinebarcodereader.com/

![image](https://user-images.githubusercontent.com/10876587/142364186-f7d5b523-07fc-4776-8b96-9a75f4a455d1.png)


## TODO

We'll continue work on this package until support the whole cycle of QR code implementation.

- [ ] Support the digital signature for the QR code.

## Testing

```bash
composer test
```
<p align="right">(<a href="#top">back to top</a>)</p>

## Support

The team is always here to help you. Happen to face an issue? Want to report a bug? You can submit one here on Github using the [Issue Tracker](https://github.com/SallaApp/Salla-CLI/issues/new). If you still have any questions, please contact us via the [Telegram Bot](https://t.me/SallaSupportBot) or join in the Global Developer Community on [Telegram](https://t.me/salladev).


## Contributing

Contributions are what make the open-source community such an amazing place to learn, inspire, and create. 
Any contributions you make are **greatly appreciated**.

If you have a suggestion that would make this better, please fork the repo and create a pull request. 
You can also simply open an issue with the tag "enhancement". Don't forget to give the project a star! Thanks again!

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

<p align="right">(<a href="#top">back to top</a>)</p>


## Security

If you discover any securitys-related issues, please email security@salla.sa instead of using the issue tracker.


## Credits

- [Salla](https://github.com/sallaApp)
- [All Contributors](../../contributors)


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

<p align="right">(<a href="#top">back to top</a>)</p>
