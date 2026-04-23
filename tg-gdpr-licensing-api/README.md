# TG GDPR Licensing API

Laravel 12 SaaS backend for licensing, site settings, consent records, analytics, DSAR workflows, and admin operations for the TG GDPR CMP.

## Local Bootstrap

Use the one-command local bootstrap when you want a working API without depending on your existing MySQL service.

```bash
bash scripts/local-bootstrap.sh up
```

What it does:

- Creates `.env` if it does not exist.
- Builds Vite assets if `public/build/manifest.json` is missing.
- Starts an isolated temporary MySQL instance on `127.0.0.1:3307`.
- Creates and migrates `tg_gdpr_licensing_api_local`.
- Seeds fresh license records with `LicenseSeeder`.
- Starts Laravel on the first free port from `8000` upward.
- Runs the licensing smoke test automatically.

Default local runtime:

- API: `http://127.0.0.1:<auto-port>`
- MySQL: `127.0.0.1:3307`
- Database: `tg_gdpr_licensing_api_local`
- Runtime files: `storage/mysql-local/`

Related commands:

```bash
bash scripts/local-bootstrap.sh smoke-test
bash scripts/local-bootstrap.sh down
```

Useful overrides:

```bash
APP_PORT=8010 bash scripts/local-bootstrap.sh up
DB_PORT=3310 DB_DATABASE=tg_gdpr_api_dev bash scripts/local-bootstrap.sh up
RUN_SMOKE_TEST=0 bash scripts/local-bootstrap.sh up
BUILD_ASSETS=0 bash scripts/local-bootstrap.sh up
```

## Vite Manifest Error

If you see `Vite manifest not found at public/build/manifest.json`, the frontend assets have not been built yet. The local bootstrap command above now handles that automatically. If you want to fix it manually, run:

```bash
npm ci
npm run build
```

## API Smoke Test

After bootstrap, rerun the smoke test through the wrapper so it uses the same temporary database settings as the local API:

```bash
bash scripts/local-bootstrap.sh smoke-test
```

It validates:

- single-site activation
- verification
- deactivation
- 3-site activation success
- activation limit rejection on the 4th site

## Stack

- PHP 8.2+
- Laravel 12
- MySQL
- Vite
- Tailwind CSS
