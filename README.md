# Sheets2JSON API (Laravel)

Turn public CSV/XLSX/Google Sheets URLs into fast, queryable JSON APIs. Supports on-the-fly parsing and persistent, versioned collections with tokenized access and rate limits.

## What It Does
- Imports CSV, XLSX, and Google Sheets from public URLs.
- Parses data on demand or stores it as versioned collections.
- Exposes a REST API with filtering, sorting, pagination, and schema metadata.
- Supports public or token-gated datasets with per-plan limits.

## Core Features
- **Document Parsing API**: Parse a public document URL on the fly and return JSON with metadata (count, size, hash).
- **Collections**: Persist a document URL as a collection, with versioning and optional public access.
- **Collection Data API**: Query collection data by JSON fields with rich filters and ordering.
- **Schema Endpoint**: Retrieve schema and collection/version metadata for a given dataset.
- **Token Access**: Issue collection-scoped tokens and enforce access control.
- **Rate Limits & Quotas**: Plan-based limits for document size, record count, and API request rates.

## API Surface (Concise)
All routes live under `/api/v1` (or no `/api` prefix when using an `api.*` subdomain).

Collections:
- `GET /api/v1/collections`
- `POST /api/v1/collections`
- `GET /api/v1/collections/{collection}`
- `PUT /api/v1/collections/{collection}`
- `DELETE /api/v1/collections/{collection}`

Collection Versions:
- `GET /api/v1/collections/{collection}/versions`
- `POST /api/v1/collections/{collection}/versions`
- `GET /api/v1/collections/{collection}/versions/{version}`
- `DELETE /api/v1/collections/{collection}/versions/{version}`

Collection Tokens:
- `GET /api/v1/collections/{collection}/tokens`
- `POST /api/v1/collections/{collection}/tokens`
- `PUT /api/v1/collections/{collection}/tokens/{token}`
- `DELETE /api/v1/collections/{collection}/tokens/{token}`

Collection Data:
- `GET /api/v1/data/collection/{collection}`
- `GET /api/v1/data/collection/{collection}/schema`
- `GET /api/v1/data/collection/{collection}/{collectionItem}`

Document Parsing:
- `GET /api/v1/data/document`
- `GET /api/v1/doc` (legacy)

## Query Capabilities (Collection Data)
Supports:
- `where`, `whereNot`, `whereIn`, `whereNotIn`
- `whereEmpty`, `whereNotEmpty`
- `whereGreater`, `whereLess`, `whereBetween`
- `whereTrue`, `whereNotTrue`
- `orderBy`, `limit`, `page`, `select`, `selectNot`

Filtering and ordering are enforced against the dataset schema to prevent invalid field access.

## Architecture Overview
**Flow 1: On-the-fly Document Parsing**
1. `DocumentController` validates request parameters.
2. `DocumentService` fetches the remote URL, caches to temp storage, detects MIME type.
3. `DocumentProcessor` selects the parser (CSV/XLSX/Google) and streams results.
4. Response includes data plus metadata (count, size, hash, first/last row).

**Flow 2: Collections**
1. Collections store a document URL and parsing settings.
2. Versions represent processed datasets with schema and metadata.
3. Collection data endpoints query `collection_items` by JSON field filters and schema-aware sorting.

**Access + Rate Limits**
- Access tokens (collection or user scope) are issued via Sanctum.
- Public collections bypass token auth.
- Middleware enforces per-plan rate limits for API and data endpoints.

## Project Structure (Key Areas)
- `app/Http/Controllers/Api`: API endpoints (collections, versions, data, documents).
- `app/Http/Controllers/Web`: Web UI endpoints (dashboard, explorer).
- `app/Http/Requests`: Validation and request transformation.
- `app/Http/Resources`: JSON response shaping.
- `app/Components/Document`: Remote document fetching and processing.
- `app/Components/Parser`: CSV/XLSX/Google Sheets parsers.
- `app/Models`: Core models (Collection, CollectionVersion, CollectionItem, AccessToken, SubscriptionPlan).
- `routes`: API routing and web routing configuration.

## Local Notes
This project uses Laravel with Sanctum for token auth and Cashier for subscription plan configuration.

## MCP Server (Laravel MCP)
- Package: `laravel/mcp`
- Local handle: `sheets2json` (run with `php artisan mcp:start sheets2json`)
- Web endpoint: `POST /mcp/sheets2json` on domains listed in `config/domains.php` under `api`

### Exposed Tool (current)
- `document.parse`: wraps the public document endpoint and returns parsed JSON payloads.

### MCP Environment Config
- `SHEETS2JSON_API_BASE_URL` (default: `${APP_URL}/api`)
- `SHEETS2JSON_DOCUMENT_PATH` (default: `/v1/data/document`)
- `SHEETS2JSON_API_TOKEN` (optional bearer token)
- `SHEETS2JSON_API_TIMEOUT` (default: `60`)

### Future Extension
The MCP server is intentionally structured to add `collection.*` tools next without changing server registration (`app/Mcp/Servers/Sheets2JsonServer.php`).
