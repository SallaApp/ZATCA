<div id="top"></div>
<div align="center"> 
  <a href="https://salla.dev"> 
    <img src="https://salla.dev/wp-content/uploads/2023/03/1-Light.png" alt="Logo"> 
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

* PHP >= 8.0
* A mbstring extension
* An ext-dom extension

## Installation

You can install the package via composer:

```bash
$ composer require salla/zatca
```
<p align="right">(<a href="#top">back to top</a>)</p>

## Usage

This library supports both Phase 1 and Phase 2.

Phase 2, include mandates integration of a taxpayer's system with the ZATCA, along with the transmission of e-invoices and e-notes to the ZATCA.

### Generating CSR

You need to onboard the merchant via the ZATCA APIs to had the authority to signing the invoice on behalf of the merchant.

````php
use Salla\ZATCA\GenerateCSR;
use Salla\ZATCA\Models\CSRRequest;

$data = CSRRequest::make()
    ->setUID('string $OrganizationIdentifier')
    ->setSerialNumber('string $solutionName', 'string $version', 'string $serialNumber')
    ->setCommonName('string $commonName')
    ->setCountryName('SA')
    ->setOrganizationName('string $organizationName')
    ->setOrganizationalUnitName('string $organizationalUnitName')
    ->setRegisteredAddress('string $registeredAddress')
    ->setInvoiceType(true, true) //invoice types , the default is true, true
    ->setCurrentZatcaEnv('string $currentEnv') //support all modes ['sandbox','simulation','core']
    ->setBusinessCategory('string $businessCategory');

$CSR = GenerateCSR::fromRequest($data)->initialize()->generate();

// writing the private_key to file
openssl_pkey_export_to_file($CSR->getPrivateKey(), 'output file path name');

//writing the csr_content to file
file_put_contents('output file path name', $CSR->getCsrContent());
````

At this stage you need to share the CSR to the ZATCA via APIs to get the certification for the current merchant

### Signing Invoices & Generate QA code

```php
use Salla\ZATCA\Helpers\Certificate;
use Salla\ZATCA\Models\InvoiceSign;

$xmlInvoice = 'xml invoice text';

$certificate = (new Certificate(
    'certificate plain text (base64 decoded)', // get from ZATCA when you exchange the CSR via APIs
    'private key plain text' // generated at stage one
))->setSecretKey('secret key text'); // get from ZATCA when you exchange the CSR via APIs

$invoice = (new InvoiceSign($xmlInvoice, $certificate))->sign();

// invoice Hash: $invoice->getHash()
// invoice signed as XML: $invoice->getInvoice()
// Invoice QR code as base64: $invoice->getQRCode()
```

### Generating QR Code As Base64

> it's better to use `InvoiceSign` class to sign the invoice and generate the QR code for it in the same process

```php
<?php

use Salla\ZATCA\GenerateQrCode;
use Salla\ZATCA\Helpers\UXML;
use Salla\ZATCA\Helpers\Certificate;

$xmlInvoice = 'xml invoice text';

$certificate = (new Certificate(
    'certificate plain text (base64 decoded)', // get from ZATCA when you exchange the CSR via APIs
    'private key plain text' // generated at stage one
))->setSecretKey('secret key text'); // get from ZATCA when you exchange the CSR via APIs

$tags = UXML::fromString($xmlInvoice)->toTagsArray($certificate);

$QRCodeAsBase64 = GenerateQrCode::fromArray($tags)->toBase64();

// Invoice Hash: $tags[5]->getValue()
// Digital Signature: $tags[6]->getValue()

//$QRCodeAsBase64 output will be like this
//AQ1TYWxsYSAtIFNhbGxhAg8zMTA0NjE0MzU3MDAwMDMDFDIwMjMtMTItMzFUMjE6MDA6MDBaBAY0MDguNjkFBTUzLjMxBiw1TXZmVmZTWGRSZzgyMWU4Q0E3bE1WcDdNS1J4Q2FBWWZHTm90THlHNUg4PQdgTUVRQ0lEOGthSTF1Z29EcWJkN3NEVmFCVE9yOUswWlVwRkZNY2hON2FsNGgyTEhrQWlCYnZxZktkK0xaN0hEc0FMakxmeTA0dTBMNVRhcjhxenowYjBlb0EzMUtIdz09CFgwVjAQBgcqhkjOPQIBBgUrgQQACgNCAATmBleqoCAfxDveLQVAKCvHSjNxoudWhRNQ8zThTxzBtgjAqZQ7vBJWvu2Ut0MxYa8vq7O4tgusgmcLBDhK/xNCCUcwRQIhAIhuJ6o4ETNSosMEf/OLVbp+TZqi2IGSxsgyC54yZgQAAiB3lwym6zpkPspQrT+luMte/ifw4THG+waV+SmXNSukmQ==
```


### Generate Base64 (phase one)

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
    // .....
])->toTLV();

// Render A QR Code Image
// data:image/png;base64, .........
$displayQRCodeAsBase64 = GenerateQrCode::fromArray([
    new Seller('Salla'), // seller name        
    new TaxNumber('1234567891'), // seller tax number
    new InvoiceDate('2021-07-12T14:25:09Z'), // invoice date as Zulu ISO8601 @see https://en.wikipedia.org/wiki/ISO_8601
    new InvoiceTotalAmount('100.00'), // invoice total amount
    new InvoiceTaxAmount('15.00') // invoice tax amount
    // .......
])->render();

// now you can inject the output to src of html img tag :)
// <img src="$displayQRCodeAsBase64" alt="QR Code" />
```

<p align="right">(<a href="#top">back to top</a>)</p>

## Read The QR-Code

The output of the QR-Code is not readable for the human 👀, and some of QR-Code readers may show an invalid output because this QR-Code will be scanned by the ZATCA apps later after all the steps of integration are completed.
If you are interested to see the output of your final QR-Code Image, you can use the following website:

`https://www.onlinebarcodereader.com/`

![image](https://user-images.githubusercontent.com/10876587/142364186-f7d5b523-07fc-4776-8b96-9a75f4a455d1.png)


## Testing

```bash
composer test
```
<p align="right">(<a href="#top">back to top</a>)</p>

## Support

The team is always here to help you. Happen to face an issue? Want to report a bug? You can submit one here on Github using the [Issue Tracker](https://github.com/SallaApp/Salla-CLI/issues/new). If you still have any questions, please contact us by joining the Salla Global Developer Community on [Telegram](https://t.me/salladev) or via the [Support Email](mailto:support@salla.dev)


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
