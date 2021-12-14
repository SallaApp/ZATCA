# Changelog

All notable changes to `salla/zatca` will be documented in this file

## 2.0.0 - 2021-12-14

- Allow passing mutliple QROptions #10 by @ali-alharthi


### 🚨  Brack Change 

The `render` function now accept a array of options for render the QR, you can find the all available options at (https://github.com/chillerlan/php-qrcode)

#### Before

```
$displayQRCodeAsBase64 = GenerateQrCode::fromArray([
.....
])->render(3);
```

#### After

```php
$displayQRCodeAsBase64 = GenerateQrCode::fromArray([
.....
])->render(['scale' => 3]);
```

## 1.0.2 - 2021-10-14

- Support Arabic

## 1.0.0 - 2021-10-11

- initial release
