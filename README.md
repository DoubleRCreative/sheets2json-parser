# Sheets2JSON — Core Document Parsing API

Turn public CSV/XLSX/Google Sheets URLs into JSON on the fly.

## What It Does
- Fetches remote tabular documents (CSV, XLSX, Google Sheets, SharePoint links).
- Parses and returns JSON (paginated) or NDJSON (streaming).
- Supports header rows, sheet selection, column filtering, range/offset pagination, and skip-empty rows.
- Caches fetched documents temporarily (5-minute TTL) for repeat requests.

## API Surface

Root:
- `GET /` — API info
- `GET /openapi.json` — OpenAPI spec

Document Parsing:
- `GET /v1/doc` — Parse document, return JSON (legacy)
- `GET /v2/doc` — Parse document, return JSON (current)
- `GET /v2/doc/stream` — Parse document, return NDJSON stream

All routes serve under `/api/...` on web domains, or directly on `api.*` subdomains.

## Architecture Overview

1. `DocumentController` validates request parameters (URL, headers, sheet, range, columns, etc).
2. `DocumentService` fetches the remote URL via the appropriate HTTP client (Google/Microsoft/generic), streams to a temp file, caches in the `documents` table.
3. `DocumentProcessor` selects the correct parser based on MIME type.
4. The parser streams rows as a generator, applying filters (header normalization, offset, range, column selection, skip-empty).
5. Response includes data plus metadata (count, size, hash, first/last row), or raw NDJSON for the stream endpoint.

## Supported Sources

- **CSV** — via `league/csv`
- **XLSX** — via `openspout/openspout`
- **Google Sheets** — via the deprecated GViz JSON endpoint + `halaxa/json-machine`
- **Microsoft Excel Online / SharePoint** — resolved to XLSX download via `MicrosoftClient`

## Hard Limits

- Maximum download size: **50 MB** (configured in `DocumentService::MAX_BYTE_SIZE`)
- Record limits can be set per request via options

## Project Structure

```
app/
  Components/
    Document/       — Remote document fetching, caching, processing
      Document.php         — Eloquent model (documents table)
      DocumentService.php  — Fetch + cache logic
      DocumentProcessor.php— Parser selection + orchestration
    Http/
      Client.php           — Base HTTP client
      GoogleClient.php     — Google Sheets URL handling
      MicrosoftClient.php  — SharePoint/OneDrive URL handling
    Parser/
      Parser.php           — Abstract parser (header/offset/range/column logic)
      Csv.php              — CSV parser
      Xls.php              — XLSX parser
      Google.php           — Google Sheets parser
    Utility/
      Data.php             — Schema inference, array utilities
      JsonStreamWrapper.php— Strips GViz wrapper for JsonMachine
  Console/Commands/
    DocumentCleanup.php    — Deletes expired documents (every 5 min)
  Http/
    Controllers/Api/
      DocumentController.php       — v1/v2 document endpoints
      DocumentStreamController.php — NDJSON stream endpoint
    Middleware/
      EnsureApiDomain.php  — Host validation + JSON Accept header
      EnsureWebDomain.php  — Host validation
    Requests/
      DocumentRequest.php  — Request validation
  Mcp/
    Servers/
      Sheets2JsonServer.php — MCP server registration
    Tools/
      DocumentParseTool.php — MCP document.parse tool
routes/
  api.php           — Domain-based routing (api.* vs web)
  api-domain.php    — Shared route group (root, openapi, auth info)
  api/
    v1.php          — Legacy v1 document route
    v2.php          — Current v2 document + stream routes
  console.php       — Document cleanup schedule
```

## Running Locally

**Requirements:** PHP ^8.2, Composer, SQLite (or PostgreSQL)

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate
php artisan serve
```

No queue worker or scheduler required unless you want document cleanup to run automatically (the cleanup command runs via `php artisan app:document-cleanup` manually or `php artisan schedule:work` for automatic scheduling).

## Running With Docker

```bash
# Dev (bind-mount)
docker compose -f docker-compose.dev.yml up --build

# Prod-like
docker compose up --build
```

## Tests

```bash
php artisan test
```

## MCP Server

A `sheets2json` MCP server is available via `laravel/mcp`, exposing the `document.parse` tool.

- Local: `php artisan mcp:start sheets2json`
- Web: `POST /mcp/document` on API domains

**Environment config:**
- `SHEETS2JSON_API_BASE_URL` (default: `${APP_URL}/api`)
- `SHEETS2JSON_DOCUMENT_PATH` (default: `/v1/data/document`)
- `SHEETS2JSON_API_TOKEN` (optional bearer token)
- `SHEETS2JSON_API_TIMEOUT` (default: `60`)
