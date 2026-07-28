# REST API for WCC

**Base**: `/api/v1/`

**Full documentation**: See `rest_api_core.md` in project root (always the authoritative source).

## Quick Start for Agents
- Authentication: `X-API-Key` header (preferred) or Basic Auth.
- All responses: `{ "success": bool, "data": ..., "message": "...", "meta": {...}, "timestamp": "..." }`
- Use pagination: `?page=1&per_page=20`
- Most list endpoints support relevant filters.

## Core Resources (as of OB1.0.0)
- /users
- /roles
- /equipment
- **/toolings** — full CRUD (soft-delete), filters `search`, `barcode`, `asset_tag`, `tooling_code`, `category`, `status`, `linked_equip_id`
  - `/toolings/{id}/bom` — GET list; POST `{part_id, quantity?, notes?}`; PUT/DELETE `/bom/{bom_id}`
  - `/toolings/{id}/documents` — GET list; POST metadata `{doc_title, file_path, doc_type?}`; DELETE `/documents/{doc_id}`
  - Perms: `view_toolings` / `manage_toolings`
  - Multipart file upload stays on web `/api/upload_document.php` (entity=tooling); companion list stays on `/api/companion/toolings.php`
- /production-lines
- /tickets
- /ticket-actions
- /work-orders
- /inventory
- /vendors
- /purchase-orders (lifecycle; approve vs fulfill perms)
- /purchase-requests (same `purchase_orders` table as web PR UI — not a separate table)
- /stats?type=overview | ?type=kpi
- /audit
- /api-keys (POST to generate)
- /me

See the main `rest_api_core.md` for exact parameters, permissions, and examples.

## Usage for AI Agents
When an agent needs live data:
1. Call the appropriate GET endpoint.
2. Use the returned data instead of guessing from code.
3. For mutations, respect RBAC (the API will return 403 if permission missing).

The API is the recommended way for agents to interact with current application state.

## AI-Specific Endpoint
- `GET /ai-context` returns links + optional `context_json` and live data.
- `GET /ai-context?section=CONVENTIONS` returns full markdown for a section.
- `GET /ai-context?live=1` returns safe live counts/samples + the context_json.
