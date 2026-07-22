# Agents Guide (Sheets2JSON API)

This is a Laravel 12 app that turns **remote tabular documents** (CSV/XLSX/public Google Sheets/SharePoint links) into:

- **On-the-fly JSON** via the Document endpoint, and/or
- **Persisted “collections”** with **versioned imports** you can query through SQL-like filters against JSON fields.

## Quick Orientation

**Two main flows**

1) **Document parsing (no persistence)**
- Entry: `app/Http/Controllers/Api/DocumentController.php`
- Fetch/cache temp file: `app/Components/Document/DocumentService.php` → `documents` table
- Parse/stream rows: `app/Components/Document/DocumentProcessor.php` → `app/Components/Parser/*`

2) **Collections (persist + query)**
- Collections API: `app/Http/Controllers/Api/CollectionController.php`
- Versions API: `app/Http/Controllers/Api/CollectionVersionController.php`
- Import job: `app/Jobs/CollectionDataImport.php`
- Query API: `app/Http/Controllers/Api/CollectionDataController.php`
- Storage: `collection_items.data` is `jsonb` (see migrations in `database/migrations`)

## Running Locally (no Docker)

**Requirements**
- PHP `^8.2`, Composer
- Node (for Vite UI assets)
- DB: SQLite works for local/tests; the query layer relies on JSON `->` / `->>` operators (see “Query Notes”).

**Common commands**
- Install PHP deps: `composer install`
- Install JS deps (optional for API-only work): `npm install`
- App key + env: `cp .env.example .env && php artisan key:generate`
- Migrate: `php artisan migrate`
- Run HTTP server: `php artisan serve`
- Run queue worker (required for collection version imports): `php artisan queue:work`
- Run scheduler (document cleanup): `php artisan schedule:work`

**Host/domain gotcha**
- API routes are wrapped in `api.domain` middleware and only allow hosts in `config/domains.php`.
- Use `http://localhost` / `http://127.0.0.1` locally, or update `config/domains.php` for your dev hostname.

## Running With Docker

- Dev (bind-mount code): `docker compose -f docker-compose.dev.yml up --build`
  - Nginx exposes `http://localhost:3000`
  - Queue worker + scheduler are separate services in the compose file
- Prod-ish image: `docker compose up --build` (mounts `public/` and uses a tmpfs `/tmp` volume)

## API Routing Notes

- Routes are registered in `routes/api/v1.php`.
- Prefix behavior depends on host (`routes/api.php`):
  - If host starts with `api.` → routes are served without the `/api` prefix
  - Otherwise → routes are served under `/api/...`

## Auth, Tokens, and Access Control

- “App” APIs (`/v1/collections...`) require `auth:api` (Sanctum) and `throttle:api`.
- “Data” APIs (`/v1/data/...`) use:
  - `data-api` middleware (`app/Http/Middleware/CollectionDataAuthenticate.php`)
  - `throttle:data-api` and `throttle:data-api-document` (rate limits depend on subscription plan)
- Collections can be marked `public` to bypass bearer-token checks for data endpoints.
- Collection-scoped bearer tokens are created in `app/Http/Controllers/Api/CollectionTokenController.php` using Sanctum.

## How Collection Imports Work

- Creating a `Collection` triggers `app/Models/Observers/CollectionObserver.php`:
  - Automatically creates an initial “Default” `CollectionVersion` with inherited document settings.
- Creating a `CollectionVersion` triggers `app/Models/Observers/CollectionVersionObserver.php`:
  - Dispatches `app/Jobs/CollectionDataImport.php` to fetch/parse the remote source and write `CollectionItem` rows.
- Schema discovery:
  - If `document_headers` is enabled, the import samples up to 100 parsed rows and infers a per-key schema type via `app/Components/Utility/Data.php`.

## Query Notes (Collection Data API)

The collection query endpoint is implemented in `app/Http/Controllers/Api/CollectionDataController.php` and supports:

- `where`, `whereNot` (string matching, supports `*` wildcard)
- `whereIn`, `whereNotIn` (comma-separated values)
- `whereEmpty`, `whereNotEmpty`
- `whereGreater`, `whereLess`, `whereBetween` (casts based on version schema types)
- `whereTrue`, `whereNotTrue` (casts to boolean)
- `orderBy=field:asc|desc` (casts based on schema type)

Important:
- The controller uses JSON `->` / `->>` operators in raw SQL (e.g. `data->>?`, `data->>'key'`), which work on PostgreSQL and on modern SQLite builds (via JSON1).
- If you change DB engines, re-validate these `whereRaw`/`orderByRaw` fragments.

## Document Fetching + Cleanup

- Remote documents are streamed to temp files (`/tmp/doc_<sha256>`) in `app/Components/Document/DocumentService.php`.
- Hard limits:
  - `DocumentService::MAX_BYTE_SIZE` is `50MB` (hard cap on downloaded bytes)
  - Default parse limits come from user plan (`SubscriptionPlan`) or request options
- Cleanup:
  - Expired `documents` rows are deleted by `app/Console/Commands/DocumentCleanup.php`
  - Scheduled via `routes/console.php` every 5 minutes

## IDs and Migrations

- Most core models use **non-incrementing bigint IDs** generated at create-time:
  - Trait: `app/Traits/HasSimpleflake.php`
  - Hook: `app/Models/Observers/ModelObserver.php`
- Migrations intentionally use `bigInteger('id')->primary()` for these models.

## Models (What They Represent)

- `app/Models/User.php`: Account owner of collections; uses Cashier (`Billable`) for subscription status and Sanctum tokens via `HasAccessTokens`.
- `app/Models/Collection.php`: A persisted dataset “handle” (remote URL + parsing settings + access flags). Owns many versions/items/tokens and has an optional `version_id` pointing at the active version.
- `app/Models/CollectionVersion.php`: A snapshot/import run for a collection. Stores import stats (`data_count`, `data_size`, etc), `status`, optional `data_schema` (used for type-aware filtering/sorting), and inherited document config.
- `app/Models/CollectionItem.php`: A single row/record within a version, stored as JSON in `data` (`jsonb` column). Query endpoints filter/sort by JSON fields inside this column.
- `app/Models/AccessToken.php`: Custom Sanctum PersonalAccessToken model (still uses `personal_access_tokens` table) that supports user tokens and collection-scoped tokens; used for bearer access to protected data endpoints.
- `app/Models/SubscriptionPlan.php`: Static “plan config” source (not persisted) defining quotas/limits and rate limits used by middleware and import/document parsing logic.
- `app/Components/Document/Document.php`: Temporary “document cache” model backed by the `documents` table; represents a fetched remote file stored on disk with an expiry (`expires_at`) for later parsing/reuse.

## Adding/Changing Parsers

Parsers live in `app/Components/Parser/`:
- `Csv.php` (League CSV)
- `Xls.php` (OpenSpout XLSX)
- `Google.php` (Google “gviz” JSON stream via `JsonMachine`)

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

- API behavior/filters: `app/Http/Controllers/Api/CollectionDataController.php`
- Import behavior/perf: `app/Jobs/CollectionDataImport.php`
- Remote download limits/timeouts: `app/Components/Document/DocumentService.php`
- Google/Microsoft URL handling: `app/Components/Http/GoogleClient.php`, `app/Components/Http/MicrosoftClient.php`
- Rate limits: `app/Http/Middleware/*RateLimit.php`, `app/Models/SubscriptionPlan.php`
