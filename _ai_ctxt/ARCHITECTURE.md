# WCC Architecture & Code Organization

## High-Level Design Principles
- **Domain-driven modularization**: Code is organized by business domain using `_` prefixed folders (e.g. `_maint/`, `_prod/`, `_eam/`).
- **Minimal root**: Only true shared bootstrap and infrastructure files stay at root.
- **No framework**: Raw PHP + PDO. Direct control, maximum performance and auditability.
- **Shared concerns extracted**: Auth, RBAC, DB connection, nav, theming.
- **Progressive enhancement**: Core works with sessions; REST API for machines/agents.
- **Technician-first**: Every flow is optimized for speed on the shop floor.

## Folder Structure (Current)

```
C:\xampp\htdocs\
├── (Root - Bootstrap & Shared)
│   ├── index.php, login.php, register.php, my_profile.php, change_password.php
│   ├── auth.php, rbac.php, nav.php, _about_modal.php
│   ├── inc/
│   │   ├── db.php          # get_wcc_db_connection()
│   │   ├── audit.php       # wcc_audit_log()
│   │   ├── csrf.php        # wcc_csrf_token() / _valid() / _require()
│   │   ├── head.php        # shared <head> shell ($page_title + versioned assets)
│   │   └── version.php     # WCC_UI_VERSION cache-bust — bump on CSS/JS change
│   ├── api/v1/             # REST API
│   ├── css/, img/
│   ├── migrations/
│   ├── schema.sql
│   └── ...
│
├── _mgmt/                  # Management & Admin
│   ├── admin_panel.php     # Tile board ($ADMIN_TILES registry, per-user drag-reorder via users.admin_layout_json)
│   ├── app_settings.php, users.php, users_list.php, admin_backup.php, branding.php, setup_vault_departments.php
│
├── _prod/                  # Production
│   └── setup_vault_lines.php
│
├── _eam/                   # Enterprise Asset Management
│   ├── equipment.php, equipment_list.php, setup_vault_equipment.php
│   ├── toolings.php, setup_vault_toolings.php   # Tooling ledger + vault (view_toolings / manage_toolings)
│   ├── equipment_labels.php  # Label print/preview page + JSON API (Zebra ZPL / browser sheet)
│   ├── label_lib.php         # Pure label builders: settings, payload, ZPL, sheet grid
│
├── _maint/                 # Maintenance Operations (Core CMMS)
│   ├── active_tickets.php, work_orders.php, pm_calendar.php, takeover.php, closeout.php, quick_resolve.php, repair_closeout.php ...
│
├── _logi/                  # Logistics & Procurement
│   ├── inventory.php, purchase_*.php, vendors*.php, pr_document.php, setup_vault_vendors.php
│
├── _rpt/                   # Reports & Analytics
│   ├── statistics.php, history.php, setup_vault_analytics.php
│
├── _trck/                  # Tracking
│   └── tracking_stepper.php
│
├── _qual/, _cmms/, _erp/, _mes/   # Reserved for future modules
│
└── _ai_ctxt/               # AI Agent Context Layer (this folder)
```

## Shared Infrastructure
- **auth.php**: Session handling, login enforcement.
- **rbac.php**: `can($perm)`, `require_perm()`, role definitions, permission merging. **24 permissions** (includes `view_toolings` / `manage_toolings` independent of equipment); DB `role_definitions` (levels 1-6) is the editable authority, hardcoded `ROLE_PERMISSIONS` is the 1-4 fallback.
- **inc/i18n.php**: JSON language packs (`lang/*.json`), 34 locales, English fallback; profile locale on `users.locale`.
- **nav.php**: Dynamic sidebar based on permissions. Uses absolute paths like `/_maint/...`.
- **inc/db.php**: Single source of truth for PDO connection (`get_wcc_db_connection()`).
- **inc/csrf.php**: `wcc_csrf_token()` / `wcc_csrf_valid()` / `wcc_csrf_require()` — required on state-changing GET links and JSON POST endpoints.
- **inc/notifications.php**: per-user notification center (`wcc_notify`, `wcc_notify_perm`, unread count/list). Nav bell + `api/notifications.php` + `wccNotifModal` overlay.
- **inc/procurement.php** (`wcc_procurement_route`) + **inc/reorder.php** (`wcc_check_and_reorder`): shared PR routing + event-driven auto-reorder, hooked into consumption points.
- **inc/dbadmin.php**: Data Administration engine — `wcc_db_backup` (full mysqldump→`/backups`), `wcc_db_restore` (mysql client via proc_open, streamed stdin), `wcc_db_tables` (grouped flush whitelist), `wcc_db_flush` (FK-safe TRUNCATE), `wcc_list_backups`/`wcc_backup_path`. Surfaced by `_mgmt/admin_backup.php` (renamed "Data Administration": Backup / Restore / Flush — manage_settings-gated, CSRF, auto-backup + type-to-confirm on destructive actions, audit-logged as `data.*`). `/backups` has a deny-all `.htaccess` (dumps contain hashes).
- **js/xmb-wave.js**: WebGL "silk string" animated background (`#wccWaveBg`, z-index -1); accent-trio, theme-aware, perf-guarded (0.6 internal res, 24fps, pause-on-hidden), graceful WebGL-absent fallback. Per-user on/off via `localStorage 'wccWaveBg'` + `window.wccSetWaveBg()`; toggle lives in my_profile.php → Visual Preferences (so every user, incl. non-admins on weak PCs, can disable it).
- **inc/head.php + inc/version.php + js/wcc-ui.js + css/global.css**: Unified Design System v2 shell (see CONVENTIONS.md).
- **Theme system**: CSS variables, dark default + `.light-theme`, localStorage persisted.
- **inc/session.php**: hardened session bootstrap — EVERY entry point starts its session through this, never raw `session_start()`. Sets `use_strict_mode`, `use_only_cookies`, HttpOnly + SameSite=Lax, and Secure only when the request is actually TLS (the shop-floor intranet has no HTTPS). Done in code, not php.ini, so the hardening ships with the app.
- **inc/ratelimit.php**: brute-force throttle on the previously-unused `rate_limit` table. `wcc_rate_status/blocked/hit/clear($endpoint)`, fixed window keyed on (ip, endpoint), atomic `ON DUPLICATE KEY`. Wired into login.php at 10 failures / 15 min; cleared on success. **Fails open** — a DB hiccup must never lock a technician out of a shop-floor terminal.

## Documentation (`docs.php`)
- **Public by design** — no `auth.php`, no `require_perm()`. Documentation is the primary evaluation surface for a self-hosted product; gating it behind a login means nobody assessing WCC can read it, and it describes how the system works, never what is in anyone's database.
- Registry-driven: `docs/registry.php` defines parts → chapters → sections, and drives the sidebar, the scroll-spy targets and the include order. Adding a chapter = one array entry + one file in `docs/chapters/`. A registry entry with no file renders an explicit "not written yet" rather than an empty chapter that looks finished.
- 23 chapters / 86 sections across Orientation, Architecture, Data, Security, Workflows, Analysis, Operations. Linked from the About modal.
- Scroll-spy is a **geometry calculation on a rAF-throttled scroll handler, not IntersectionObserver** — IO callbacks are throttled while a document is hidden (background tab, embedded preview), which leaves the sidebar highlighting nothing. `window.wccDocsSpy()` is exposed so the highlight can be asserted in a headless check.
- `docs/` has a deny-all `.htaccess`: chapter fragments are included from disk and would render as orphan HTML if fetched directly.

## Webroot Hardening (config layer, no PHP behaviour change)
- Root `.htaccess`: `Options -Indexes` globally; denies `.sql|.md|.ini|.log|.bak|.old|.dist|.sh|.bat|.yml|.yaml|.lock`, dotfiles and `composer/package.json`; sets `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`; `ServerSignature Off`.
- Deny-all `.htaccess` in `inc/`, `migrations/`, `_ai_ctxt/`, `_dev_artifacts/`, `backups/`, `archive/` — all server-side only.
- `uploads/` is NOT deny-all (invoice PDFs and checklist photos are linked directly as `/uploads/...`): listing off + **PHP execution disabled** there, so a smuggled `.php` cannot run.
- `archive/` holds retired dev/test/migration scripts (`archive/dev_scripts/`) and old DB/source dumps (`archive/data_dumps/`), moved out of the webroot's reachable surface.

## Data Access Pattern
- Almost all pages do direct prepared statements via central `$pdo`.
- No ORM. Queries are explicit.
- Important tables have soft-delete (`deleted_at`) and audit support.

## REST API (api/v1/)
- Router: `index.php`
- Bootstrap: auth (X-API-Key or Basic + session fallback), helpers, `require_api_perm()`
- Resources are thin handlers: `handle_xxx($method, $id, $input)`
- **Toolings:** `resources/toolings.php` — CRUD (soft-delete), nested `bom` + `documents`
- Clean URLs supported via .htaccess
- See `rest_api_core.md` and `_ai_ctxt/REST_API.md`

## Companion API (api/companion/) — separate hive
- Used by the Android companion package (session + `api_guard_*`).
- Includes `toolings.php`, `scan_lookup.php`, `work_order.php`, `factory_health.php`, …
- **Do not remove or force-migrate** these when extending REST unless product owner asks.
- REST and companion are parallel contracts over the same database.

## Key Navigation for Agents
1. Start with root `nav.php` to understand current modules and permission gates.
2. Look at a typical page in `_maint/` or `_eam/` to see include pattern + permission check.
3. `register.php` and `active_tickets.php` show the main ticket creation flow.
4. `schema.sql` + `migrations/` for data model.
5. `rbac.php` for permission system.

## How Pages Are Typically Structured
```php
<?php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('some_permission');

require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

// ... business logic + queries ...

<body>
<?php include __DIR__ . '/../nav.php'; ?>
... content ...
```

## Important Conventions
- Use leading `/` for all HTML hrefs and asset links after the reorg (e.g. `/_maint/work_orders.php`, `/css/global.css`).
- Self-references inside module files can be relative.
- All new major pages should live in an appropriate `_module/`.
- Update the module's README.md when adding features.

## Code Dependencies & Relations
- **Core Dependency Graph**:
  - Every protected page → auth.php + rbac.php
  - Data access → inc/db.php (get_wcc_db_connection)
  - UI Shell → nav.php (includes _about_modal.php)
- **Module Interdependencies**:
  - _maint depends on _eam (equipment) and _logi (inventory for parts)
  - _logi depends on vendors and equipment (for PO context)
  - _rpt consumes data from almost everywhere (tickets, actions, ledger, work_orders)
- **No circular dependencies** by design — modules are mostly independent except for shared core.
- **When refactoring**: Update all absolute paths and module READMEs. Run context generator after DB changes.
- Run the context generator after schema changes.
- Prefer explicit over magic.

This structure makes the system easy to reason about both for humans and AI agents.
