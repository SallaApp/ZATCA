# AGENTS.md

See `CLAUDE.md` for the full guide. Quick facts:

- **Purpose:** PHP library providing ZATCA (Fatoora) e-invoicing: Phase 1 TLV QR code generation and Phase 2 CSR/invoice signing with EC cryptography.
- **Stack:** PHP >= 8.0, phpseclib3, chillerlan/php-qrcode, robrichards/xmlseclibs — pure composer library, no Laravel.
- **Run/test:** `composer install && composer test`
- **Don't:** push to `master` (default branch) · commit secrets (certificates/private keys belong in Doppler/env, not source) · change the public signatures of `GenerateQrCode`, `GenerateCSR`, `InvoiceSign`, or `Certificate` without reviewing impact on `services/Core` and `services/zatca-einvoicing`.
- **Conventions:** PSR-4 under `Salla\ZATCA\`; validate all CSR inputs at boundaries (see `CSRRequest`); no hardcoded env-specific values — full rules in workspace `CLAUDE.md`.
