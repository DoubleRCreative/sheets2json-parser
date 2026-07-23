# Agents Guide (Sheets2JSON — Core Parsing API)

This is a Laravel 12 app that turns **remote tabular documents** (CSV/XLSX/public Google Sheets/SharePoint links) into **on-the-fly JSON** via a simple REST API.

## Quick Orientation

**Single flow: Document parsing (no persistence)**

- Entry: `app/Http/Controllers/Api/DocumentController.php`
- Fetch/cache temp file: `app/Components/Document/DocumentService.php` → `documents` table
- Parse/stream rows: `app/Components/Document/DocumentProcessor.php` → `app/Components/Parser/*`
- Three output modes: JSON response (v1 legacy), JSON response (v2), NDJSON stream (v2).

## Running Locally (no Docker)

**Requirements**
- PHP `^8.2`, Composer
- DB: SQLite works for local/tests.

**Common commands**
- Install PHP deps: `composer install`
- App key + env: `cp .env.example .env && php artisan key:generate`
- Migrate: `php artisan migrate`
- Run HTTP server: `php artisan serve`
- Run scheduler (document cleanup): `php artisan schedule:work`

**Host/domain gotcha**
- API routes are wrapped in `api.domain` middleware and only allow hosts in `config/domains.php`.
- Use `http://localhost` / `http://127.0.0.1` locally, or update `config/domains.php` for your dev hostname.

## Running With Docker

- Dev (bind-mount code): `docker compose -f docker-compose.dev.yml up --build`
  - Nginx exposes `http://localhost:3000`
  - Scheduler is a separate service in the compose file
- Prod-ish image: `docker compose up --build` (mounts `public/` and uses a tmpfs `/tmp` volume)

## API Routing Notes

- Routes are registered in `routes/api/v1.php` and `routes/api/v2.php`.
- Prefix behavior depends on host (`routes/api.php`):
  - If host starts with `api.` → routes are served without the `/api` prefix
  - Otherwise → routes are served under `/api/...`

## Document Fetching + Cleanup

- Remote documents are streamed to temp files (`/tmp/doc_<sha256>`) in `app/Components/Document/DocumentService.php`.
- Hard limits:
  - `DocumentService::MAX_BYTE_SIZE` is `50MB` (hard cap on downloaded bytes)
  - Record limits can be set per request
- Cleanup:
  - Expired `documents` rows are deleted by `app/Console/Commands/DocumentCleanup.php`
  - Scheduled via `routes/console.php` every 5 minutes

## Adding/Changing Parsers

Parsers live in `app/Components/Parser/`:
- `Csv.php` (League CSV)
- `Xls.php` (OpenSpout XLSX)
- `Google.php` (Google "gviz" JSON stream via `JsonMachine`)

When adding a new document type, you typically need to update:
- `app/Components/Document/DocumentService.php` (content-type detection / client selection)
- `app/Components/Document/DocumentProcessor.php` (switch to new parser)
- `app/Components/Document/Document.php` (`TYPE_*` and `getTypes()`)

## Tests

- Run: `php artisan test`
- PHPUnit config: `phpunit.xml`
- Test DB connection is `tests` (SQLite file): `tests/Data/Database/database.sqlite`
- `tests/TestCase.php` migrates + seeds on every test and resets after; keep tests focused to avoid long runs.

## Where to Look First (Common Tasks)

- Remote download limits/timeouts: `app/Components/Document/DocumentService.php`
- Google/Microsoft URL handling: `app/Components/Http/GoogleClient.php`, `app/Components/Http/MicrosoftClient.php`
- Document request parameters: `app/Http/Requests/DocumentRequest.php`
- Stream endpoint: `app/Http/Controllers/Api/DocumentStreamController.php`
