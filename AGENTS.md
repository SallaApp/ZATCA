# AGENTS.md

See `CLAUDE.md` for the full guide. Quick facts:

- **Purpose:** Pure-PHP library (`salla/zatca`) for ZATCA (Fatoora) e-invoicing: Phase 1 TLV QR codes, Phase 2 secp256k1 CSR generation, CSID certificate handling, and XAdES signing of UBL invoices with the embedded QR.
- **Stack:** PHP >= 8.0 · phpseclib3 · robrichards/xmlseclibs · josemmo/uxml · chillerlan/php-qrcode v5 · PHPUnit 8 · no Laravel.
- **Run/test:** `composer install && composer test`. The suite also runs inside `services/zatca-einvoicing` CI (`zatca` testsuite).
- **Default branch:** `master` (not `main`). Releases are manual semver tags (currently `4.x`).
- **Don't:** push to `master` · commit real private keys, CSIDs, or OTPs · add framework coupling (`config()`, facades) · change public signatures of `GenerateQrCode`, `GenerateCSR`, `CSRRequest`, `InvoiceSign`, `Certificate`, or `Tag` subclasses without checking `services/zatca-einvoicing` · change XML canonicalisation or indentation handling (breaks the invoice hash and PIH chain).
- **Conventions:** PSR-4 under `Salla\ZATCA\`, PSR-12; branch `feature/PROJ-123-desc`; PR title `feat(scope): desc` (`!` for breaking); Jira link required in PR body. Full rules are in workspace `CLAUDE.md` and `packages/CLAUDE.md`.
