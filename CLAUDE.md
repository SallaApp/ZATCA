# ZATCA (`salla/zatca`)

A pure-PHP Composer library that implements ZATCA (Fatoora) e-invoicing primitives for Saudi Arabia:
Phase 1 TLV-encoded QR code generation, and Phase 2 CSR generation, certificate handling, and XAdES
signing of UBL 2.1 invoices with the QR code embedded. It does **not** build the UBL XML (that is
`salla/einvoicing`) and does **not** call the ZATCA API (that is `services/zatca-einvoicing`).

## Stack
- PHP >= 8.0 · `ext-mbstring` · `ext-dom` · `ext-openssl` with EC support (used at runtime by `GenerateCSR`)
- `phpseclib/phpseclib ~3.0` — EC keys, ECDSA signing, X.509 parsing
- `robrichards/xmlseclibs ^3.1` — XML digital-signature helpers
- `josemmo/uxml ^0.1.4` — UBL XML DOM/XPath manipulation
- `chillerlan/php-qrcode ^5.0` — QR image rendering (v5 since release 4.0.0)
- `phpunit/phpunit ~8.0` (dev) · no Laravel, no framework coupling

## Run / test / build
```bash
composer install
composer test                                  # = phpunit, config in phpunit.xml
./vendor/bin/phpunit --filter SignInvoiceTest
```
This repo has no unit-test workflow (`.github/workflows/` only has `lint-pr.yaml`). The suite also runs in
`services/zatca-einvoicing` CI, whose `phpunit.xml` includes `vendor/salla/zatca/tests` as the `zatca`
testsuite, so run that service's tests against your branch before tagging a release.

## Architecture
- `src/GenerateQrCode.php` — `fromArray(Tag[])` then `toTLV()`, `toBase64()`, or `render($options, $file)` (image via chillerlan).
- `src/Tag.php` — base TLV tag (tag id + UTF-8 value, packed binary in `__toString`).
- `src/Tags/` — `Seller`(1), `TaxNumber`(2), `InvoiceDate`(3), `InvoiceTotalAmount`(4), `InvoiceTaxAmount`(5), `InvoiceHash`(6), `InvoiceDigitalSignature`(7), `PublicKey`(8), `CertificateSignature`(9, simplified invoices only).
- `src/GenerateCSR.php` — `GenerateCSR::fromRequest(CSRRequest)->initialize()->generate()` returns `Models/CSR` (secp256k1 private key + CSR). Writes a temp OpenSSL config to `sys_get_temp_dir()`, swaps the certificate-template string per environment, then deletes it.
- `src/Models/CSRRequest.php` — fluent CSR subject builder (`make()`, `setUID()`, `setSerialNumber()`, `setInvoiceType()`, `setCurrentZatcaEnv()`, …); throws `Exception/CSRValidationException` on the few checks it has. Those checks are shallow: `setUID()` checks only length 15 and a leading/trailing `3` (it does not check that the value is all digits), and `setCountryName()` checks only length 2. The `setOrganizationalUnitName()` "11th digit is `1`, so OU must be 10 digits" rule never fires, because `strpos($uid, '1', 10) == 1` can never be true. Callers must enforce the ZATCA rules themselves: the UID (VAT number) is exactly 15 digits, starting and ending with `3`; if its 11th digit is `1` (a VAT group), the organizational unit name must be the 10-digit TIN of the group member.
- `src/Models/InvoiceSign.php` — `(new InvoiceSign($xml, Certificate))->sign()`: removes `ext:UBLExtensions`, `cac:Signature`, and the QR `AdditionalDocumentReference`; hashes the C14N XML (SHA-256); ECDSA-signs; builds `UBLExtensions`; rebuilds the QR; returns `Models/Invoice` (`getInvoice()`, `getHash()`, `getQRCode()`, `getCertificate()`).
- `src/Helpers/Certificate.php` — wraps the CSID certificate + private key; `getHash()`, `getPlainPublicKey()`, `getAuthorizationHeader()` (Basic auth for the ZATCA API), `getCertificateSignature()`, `getFormattedIssuerDN()`.
- `src/Helpers/UXML.php` — UXML wrapper; `toTagsArray()` reads UBL fields and returns the 8 or 9 `Tag[]` for the QR.
- `src/Helpers/UblExtension.php` — builds the `<ext:UBLExtensions>` XAdES signature block.
- Namespace `Salla\ZATCA\` maps to `src/`; tests `Salla\ZATCA\Test\` map to `tests/` (fixtures in `tests/Unit/files/`).

## Cross-repo
- **Provides:** `salla/zatca` (Composer name; the repo is `SallaApp/ZATCA`)
- **Depends on:** no internal `salla/*` packages
- **Used by:** `services/zatca-einvoicing` (`InvoiceSign`, `Certificate`, `GenerateCSR`, `CSRRequest`, `GenerateQrCode`), the only active consumer. `packages/Core` still requires `salla/zatca ^1.0` but has no code references. **Check before changing any public API.**
- **Sibling:** `packages/E-Invoicing` (`salla/einvoicing`) produces the unsigned UBL XML that `InvoiceSign` signs. Its `UblWriter` emits the empty `ext:UBLExtensions`, `cac:Signature`, and QR placeholder nodes this library replaces.
- Treat `GenerateQrCode`, `GenerateCSR`, `CSRRequest`, `InvoiceSign`, `Models\Invoice`, `Certificate`, and the `Tag` subclasses as public API.

## Conventions
- Default branch is **`master`** (not `main`). Branch `feature/PROJ-123-desc`; PR title `feat(scope): desc` (checked by `lint-pr.yaml`); Jira link in the PR body.
- Releases are manual semver tags (currently `4.x`). A breaking change needs a major tag and `!` in the PR title, e.g. `feat(CPD-31586)!: …`.
- PSR-4 and PSR-12. No fixer is configured, so format by hand.
- Keep it framework-free: no Laravel helpers, facades, `config()`, or `env()`. Callers pass everything in.
- See workspace `CLAUDE.md` and `packages/CLAUDE.md` for the global branch/PR/Jira/secrets rules.

## Gotchas
- **Not Laravel:** `php artisan test` does not exist here; use `composer test`.
- **Environment template:** `CSRRequest::setCurrentZatcaEnv()` selects `TSTZATCA-Code-Signing` (sandbox), `PREZATCA-Code-Signing` (simulation), or `ZATCA-Code-Signing` (production). A wrong value means ZATCA rejects the CSR.
- **secp256k1 only:** ZATCA requires this curve, so the PHP OpenSSL build must support it.
- **Tag 9:** `CertificateSignature` is appended only when `InvoiceTypeCode@name` starts with `02` (simplified). Standard (`01`) invoices use 8 tags.
- **Hash is whitespace-sensitive:** `UXML::fromString` doubles leading indentation when the XML doesn't contain `    <cbc:ProfileID>` (4-space indent). Changing canonicalisation, node removal, or indentation changes the invoice hash, which breaks ZATCA validation and the previous-invoice-hash (PIH) chain in the service.
- **String-based splice:** `InvoiceSign::sign()` injects the signature and QR by `str_replace` on the literal `<cbc:ProfileID>` and `<cac:AccountingSupplierParty>`. The input XML must contain both tags exactly like that.
- **Major version not yet adopted:** `services/zatca-einvoicing` master still requires `salla/zatca ~3.0`, so 4.x (chillerlan v5) must be adopted there explicitly.
- **Test keys only:** `tests/Unit/files/privateKey.pem` is a fixture. Never commit real taxpayer keys, CSIDs, or OTPs.
- CODEOWNERS: `* @SallaApp/opensource`.
