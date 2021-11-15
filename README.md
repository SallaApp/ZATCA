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
    // TODO :: Support others tags
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
    // TODO :: Support others tags
])->render();

// now you can inject the output to src of html img tag :)
// <img src="$displayQRCodeAsBase64" alt="QR Code" />
```
<p align="right">(<a href="#top">back to top</a>)</p>

## TODO

We'll continue work on this package until support the whole cycle of QR code implementation.

- [ ] Support the digital signature for the QR code.

## Testing

```bash
composer test
```
<p align="right">(<a href="#top">back to top</a>)</p>

## Support

Please don't hesitate to contact us using the [</Salla Developers>](https://t.me/salladev) at telegram

- https://t.me/salladev


## Contributing

Contributions are what makes the open-source community such an amazing place to learn, inspire, and create. 
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

If you discover any securitysrelated issues, please email security@salla.sa instead of using the issue tracker.


## Credits

- [Salla](https://github.com/sallaApp)
- [All Contributors](../../contributors)


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

<p align="right">(<a href="#top">back to top</a>)</p>
