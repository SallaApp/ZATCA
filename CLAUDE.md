# ZATCA

A PHP composer library (`salla/zatca`) that implements ZATCA (Fatoora) e-invoicing for Saudi Arabia: Phase 1 TLV-encoded QR code generation and Phase 2 CSR/certificate onboarding + XML invoice signing with embedded QR.

## Stack
- PHP >= 8.0 (no Laravel/artisan — pure composer library)
- `phpseclib/phpseclib ~3.0` — EC key handling and X.509 certificate parsing
- `robrichards/xmlseclibs ^3.1` — XML digital signature support
- `josemmo/uxml ^0.1.4` — UBL XML parsing/manipulation
- `chillerlan/php-qrcode ^4.3` — QR code image rendering
- `phpunit/phpunit ~8.0` (dev)

## Run / test / build
```bash
composer install
composer test          # runs: phpunit
```

## Architecture
- `src/GenerateQrCode.php` — entry point for QR generation; accepts `Tag[]`, encodes as TLV, outputs base64 string or rendered image via `chillerlan/php-qrcode`.
- `src/Tag.php` — base TLV tag: tag ID + value → hex-packed binary string (`__toString`).
- `src/Tags/` — nine concrete tag types: `Seller`, `TaxNumber`, `InvoiceDate`, `InvoiceTotalAmount`, `InvoiceTaxAmount`, `InvoiceHash`, `InvoiceDigitalSignature`, `PublicKey`, `CertificateSignature` (Phase 2 simplified invoices only).
- `src/GenerateCSR.php` — generates secp256k1 EC private key + CSR using PHP OpenSSL with a dynamically written temp config; env-aware (`sandbox`/`simulation`/`production`) to set the correct OID signing string.
- `src/Models/CSRRequest.php` — fluent builder for CSR subject fields; validates UID (15 digits, starts/ends with 3), country code (2 chars), and branch naming rules.
- `src/Models/InvoiceSign.php` — signs a UBL XML invoice: strips unwanted nodes, computes SHA-256 C14N hash, creates ECDSA digital signature, builds `UBLExtensions`, embeds QR code node, returns `Invoice`.
- `src/Helpers/Certificate.php` — wraps plaintext certificate + private key (phpseclib3 EC); exposes `getHash()`, `getPlainPublicKey()`, `getAuthorizationHeader()`, `getCertificateSignature()`, `getFormattedIssuerDN()`.
- `src/Helpers/UXML.php` — DOM/XPath wrapper over `josemmo/uxml`; provides `toTagsArray()` which reads UBL fields and builds the 8–9 element `Tag[]` array for QR encoding.
- `src/Helpers/UblExtension.php` — builds the `<ext:UBLExtensions>` block with the XAdES signature for inclusion in the signed invoice XML.

## Cross-repo
- **Provides:** `salla/zatca`
- **Depends on:** no internal `salla/*` dependencies
- **Used by:** `services/Core`, `services/zatca-einvoicing` ← check these before changing any public API (`GenerateQrCode`, `GenerateCSR`, `InvoiceSign`, `Certificate`, `Tag` subtypes)

## Conventions
- PSR-4 autoload under `Salla\ZATCA\` → `src/`; tests under `Salla\ZATCA\Test\` → `tests/`.
- No PSR-12 fixer configured in composer.json — lint manually if needed.
- See ancestor `services/CLAUDE.md` and workspace `CLAUDE.md` for global rules (branch/PR/Jira/secrets).

## Gotchas
- **No Laravel**: `php artisan test` will fail — this is a plain library. Use `composer test`.
- **OpenSSL secp256k1**: the CSR generator writes a temp config to `sys_get_temp_dir()` and then unlinks it. Requires `openssl` PHP extension with EC support compiled in.
- **Phase env OID**: the `currentEnv` on `CSRRequest` controls which OID string is injected into the OpenSSL config (`TSTZATCA-Code-Signing` for sandbox, `PREZATCA-Code-Signing` for simulation, `ZATCA-Code-Signing` for production). Wrong env = rejected CSR by ZATCA portal.
- **Simplified invoice extra tag**: `CertificateSignature` (tag 9) is appended to the QR array only for simplified invoices (InvoiceTypeCode name starts with "02"). Standard invoices use 8 tags.
- **C14N whitespace rule**: `UXML::fromString` doubles leading indentation if the invoice doesn't already use 4-space indent — this is required for the SHA-256 hash to match ZATCA's expected format.
- **Default branch is `master`** (not `main`).
- CODEOWNERS: `* @SallaApp/opensource` — PR needs review from that team.
