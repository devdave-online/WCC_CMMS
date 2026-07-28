# WCC CMMS REST API Documentation

## Overview
The WCC CMMS provides a RESTful API for programmatic access to core resources. This allows integration with mobile apps, IoT devices, external systems, reporting tools, or custom frontends.

**Base URL:** `http://localhost/api/v1/`

**Access Methods (XAMPP):**
- Clean: `http://localhost/api/v1/users` (requires mod_rewrite + .htaccess)
- Fallback: `http://localhost/api/v1/index.php/users`

**Authentication:** API Key (recommended) or Basic Auth (username/password for testing).

All responses are JSON.

## Authentication

### 1. API Key (Preferred for production)
Send in header:
```
X-API-Key: your-api-key-here
```

For demo purposes, you can use a username as the API key (the bootstrap falls back to looking up users).

### 2. Basic Auth (Easy for testing)
```
Authorization: Basic base64(username:password)
```

Example with curl:
```bash
curl -u admin:password http://localhost/api/v1/equipment
```

**Note:** To generate real API keys, add an `api_key` column to users table and populate it.

## General Conventions

- **Methods:**
  - `GET` - List or retrieve
  - `POST` - Create
  - `PUT` - Full update
  - `PATCH` - Partial update
  - `DELETE` - Delete

- **Response Format:**
  ```json
  {
    "success": true,
    "data": { ... },
    "message": "Optional message",
    "meta": { "page": 1, "per_page": 20, "total": 150 }
  }
  ```

- **Error Response:**
  ```json
  {
    "success": false,
    "error": {
      "code": 404,
      "message": "Resource not found"
    }
  }
  ```

- **Pagination:** Use `?page=1&per_page=20`
- **Filtering:** Query parameters e.g. `?status=open&priority=high`
- **Sorting:** `?sort=created_at&order=desc`

## Resources

All endpoints support:
- Pagination: `?page=1&per_page=20`
- Many support filters (see below)
- Auth: X-API-Key header or Basic Auth
- RBAC via permissions

### Users
- `GET /users` (pagination, ?status=)
- `GET /users/{id}`
- `POST /users`
- `PUT /users/{id}` (incl. password reset)
- `DELETE /users/{id}`

### Roles
- `GET /roles`
- `GET /roles/{id}` (role_level)
- `PUT /roles/{id}` (update presets)

### Equipment
- `GET /equipment` (pagination, ?category=, ?is_active=)
- `GET /equipment/{id}`
- `POST /equipment`
- `PUT /equipment/{id}`
- `DELETE /equipment/{id}`

### Toolings (`view_toolings` / `manage_toolings`)
- `GET /toolings` — pagination; filters: `search`, `barcode`, `asset_tag`, `tooling_code`, `category`, `status`, `is_active`, `linked_equip_id`, `workshop_id`
- `GET /toolings/{id}`
- `POST /toolings` — body: `tooling_name` (required), optional `tooling_code` (auto-generated if omitted), category, barcode, status, condition_rating, location, linked_equip_id, …
- `PUT /toolings/{id}` / `PATCH /toolings/{id}`
- `DELETE /toolings/{id}` — **soft-delete** (`deleted_at`, status Retired)
- `GET /toolings/{id}/bom` — linked parts (+ sku, stock)
- `POST /toolings/{id}/bom` — `{ "part_id", "quantity"?, "notes"? }`
- `PUT /toolings/{id}/bom/{bom_id}`
- `DELETE /toolings/{id}/bom/{bom_id}`
- `GET /toolings/{id}/documents` — document metadata
- `POST /toolings/{id}/documents` — `{ "doc_title", "file_path", "doc_type"? }` (binary upload remains `/api/upload_document.php`)
- `DELETE /toolings/{id}/documents/{doc_id}`

> **Companion note:** the mobile companion package uses `/api/companion/toolings.php` and is a separate surface. Do not remove or break companion routes when extending REST.

### Production Lines
- `GET /production-lines` (pagination, ?workshop_id=)
- `GET /production-lines/{id}`
- `POST /production-lines`
- `PUT /production-lines/{id}`
- `DELETE /production-lines/{id}`

### Tickets
- `GET /tickets` (pagination, ?status=, ?equip_id=)
- `GET /tickets/{id}`
- `POST /tickets`
- `PUT /tickets/{id}`
- `DELETE /tickets/{id}`

### Ticket Actions
- `GET /ticket-actions?ticket_id=XX`
- `GET /ticket-actions/{id}`
- `POST /ticket-actions`

### Work Orders
- `GET /work-orders` (pagination, ?status=, ?equip_id=)
- `GET /work-orders/{id}`
- `POST /work-orders`
- `PUT /work-orders/{id}`
- `DELETE /work-orders/{id}`

### Inventory
- `GET /inventory` (pagination, ?search=)
- `GET /inventory/{id}`
- `POST /inventory`
- `PUT /inventory/{id}`
- `DELETE /inventory/{id}`

### Vendors
- `GET /vendors` (pagination, ?search=)
- `GET /vendors/{id}`
- `POST /vendors`
- `PUT /vendors/{id}`
- `DELETE /vendors/{id}`

### Purchase Orders
- `GET /purchase-orders` (pagination, ?status=, ?vendor_id=)
- `GET /purchase-orders/{id}`
- `POST /purchase-orders`
- `PUT /purchase-orders/{id}`
- `DELETE /purchase-orders/{id}`

### Purchase Requests (same table as POs)
There is **no** separate `purchase_requests` table. PRs are rows in `purchase_orders`
(typically `po_number` like `PR-YYYYMMDD-####`), matching the web UI.

- `GET /purchase-requests` (pagination, ?status=) — perm: `view_purchase_requests`
- `GET /purchase-requests/{po_id}` — includes `items[]`
- `POST /purchase-requests` — body: `{ "vendor_id", "dept_id?", "items":[{ "part_id", "qty" }] }`  
  Uses `wcc_procurement_route()` (auto-approve rules). Perm: `create_purchase_requests`
- `PATCH /purchase-requests/{po_id}` — cost path only: `status` = `Issued` | `Cancelled`. Perm: `approve_purchase_orders`
- `DELETE /purchase-requests/{po_id}` — only Draft / Pending Approval / Cancelled. Perm: `approve_purchase_orders`

### Purchase Orders (lifecycle / fulfilment)
- `GET /purchase-orders` — view if any of view/approve/fulfill procurement perms
- `PATCH /purchase-orders/{po_id}` — status transitions:  
  `Issued` needs `approve_purchase_orders`;  
  `Shipped` / `In Transit` / receive / `Closed` need `fulfill_purchase_orders`;  
  `Cancelled` needs either


### Stats
- `GET /stats?type=overview`
- `GET /stats?type=kpi`

### Audit
- `GET /audit` (pagination, ?entity_type=, ?action=)
- `GET /audit/{id}`

### API Keys
- `POST /api-keys?user_id=XX` (generate/refresh - requires manage_users)

### AI Context (for other AI agents)
- `GET /ai-context`
- `GET /ai-context?section=OVERVIEW|ARCHITECTURE|DATA_MODEL|KEY_FLOWS`
- `GET /ai-context?live=1` — includes safe live counts & samples

### Me
- `GET /me` - Current authenticated user info

## Notes
- Use leading `/` for clean URLs if .htaccess is set (recommended: `RewriteRule ^api/v1/(.*)$ api/v1/index.php/$1 [L,QSA]`).
- All list endpoints return `meta` with page info (page, per_page, returned). Some have total.
- Full coverage of main entities: users/roles, assets (equipment/toolings/lines), tickets+actions, work orders, inventory, vendors, purchase (orders+requests), stats, audit, api keys.
- Core is complete. Add more sub-resources or fields as app evolves.

### Inventory
- `GET /inventory`
- `GET /inventory/{id}`
- `POST /inventory`
- `PUT /inventory/{id}`

### Vendors
- `GET /vendors`
- `GET /vendors/{id}`

### Purchase Orders
- `GET /purchase-orders`
- `GET /purchase-orders/{id}`

**Note:** Not all legacy endpoints have been migrated to v1 yet. Start with the above.

## Error Codes
- 200 - Success
- 201 - Created
- 400 - Bad Request
- 401 - Unauthorized
- 403 - Forbidden
- 404 - Not Found
- 500 - Server Error

## Rate Limiting
Currently not implemented. Future versions may add per-user limits.

## Versioning
Current version: v1. All endpoints are under `/api/v1/`

## Examples

### Create Ticket
```bash
curl -X POST http://localhost/api/v1/tickets \
  -H "X-API-Key: yourkey" \
  -H "Content-Type: application/json" \
  -d '{
    "equip_id": 5,
    "priority": "high",
    "fault_desc": "Machine overheating",
    "announced_by": 3
  }'
```

### List Equipment
```bash
curl http://localhost/api/v1/equipment?status=active \
  -H "X-API-Key: yourkey"
```

### Get current user (no key needed if using session in browser)
```bash
curl http://localhost/api/v1/me
```

## Security Notes
- All endpoints respect existing RBAC permissions.
- Use HTTPS in production.
- API keys should be rotated periodically.
- Sensitive fields (passwords) are never returned.

## Implementation Notes
- Built with plain PHP + PDO (no framework).
- Centralized router in `api/v1/index.php`.
- Authentication and permission checks in `api/v1/bootstrap.php`.
- Uses existing `inc/db.php`, `auth.php`, `rbac.php`.
- Follows same data models as web UI.

## XAMPP Setup Notes
1. Enable mod_rewrite in `C:\xampp\apache\conf\httpd.conf`:
   - Uncomment: `LoadModule rewrite_module modules/mod_rewrite.so`
   - In `<Directory>` section for htdocs, set `AllowOverride All`
2. Restart Apache.
3. For clean URLs use `/api/v1/tickets`
4. Fallback always works: `/api/v1/index.php/tickets`

See source code in `api/v1/` for implementation details.
