# Coding & Project Conventions

## General
- This is a **raw PHP** application. No Laravel, Symfony, etc.
- Prefer explicit, readable code over clever abstractions.
- All pages should start with auth + rbac check + db connection.
- Use prepared statements only. Never concatenate user input into SQL.

## Folder & File Rules
- New major UI pages → appropriate `_module/` folder.
- Shared utilities → root or `inc/`.
- Never put business logic in `nav.php` or `auth.php`.
- Module folders have a `README.md` — keep it updated when adding features.

## Includes (after reorg)
Inside a module page:
```php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('...');

require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();
```

HTML links (in shared files or cross-module):
- Always use root-absolute: `href="/_maint/work_orders.php"`
- Assets: `href="/css/global.css"`, `src="/timer.js"`

Inside same module: relative filenames are acceptable for simplicity.

## Permissions
- Check with `can('permission_name')` for conditional UI.
- Enforce with `require_perm('permission_name')`.
- For API: `require_api_perm(...)`.
- Permissions live in `role_definitions` + user overrides. **24 keys**; tooling splits `view_toolings` / `manage_toolings` from equipment; procurement splits `approve_purchase_orders` (cost sign-off) from `fulfill_purchase_orders` (logistics).
- Gate every UI control AND its server-side handler — hiding a button is never enough.
- **Feature settings live with their feature**: put a feature's settings on the feature's own page, gated by that feature's permission (e.g. the procurement workflow modal on `_logi/purchase_orders.php` behind `approve_purchase_orders`) — NOT in the generic `app_settings.php` behind `manage_settings`.
- State-changing GET links and JSON POST endpoints must carry a CSRF token (`inc/csrf.php`: `wcc_csrf_token()` / `wcc_csrf_valid()` / `wcc_csrf_require()`).

## Navigation
- Every full page reachable from an admin panel tile carries the standard header link, guarded so it only shows to users who can actually open the panel:
  `<?php if (can('manage_settings')): ?><a href="/_mgmt/admin_panel.php" class="nav-btn">← Return to Admin Panel</a><?php endif; ?>`
- Modal/overlay tiles need no back link.
- The admin panel board itself is a `$ADMIN_TILES` registry + render loop in `_mgmt/admin_panel.php` — add new tiles to the registry, never as raw HTML; per-user order persists in `users.admin_layout_json`.

## Database
- Use central connection.
- Always use prepared statements with `?` placeholders.
- Prefer `SELECT` with explicit columns.
- For new tables/columns: add to `schema.sql` and create a migration in `migrations/`.

## Theming & UI (Styling Standards) — Unified Design System v2 (2026-07-17)
- **Single stylesheet**: `css/global.css`. Three token tiers in `:root`:
  1. Primitives (`--slate-*`, `--sky-*`, status hues) — internal, don't use in pages.
  2. Semantic tokens — the stable API: `--text-primary/-secondary/-muted/-accent`, `--panel-bg/-border`, `--surface-1/2`, `--btn-*`, `--input-*`, `--modal-*`, `--danger/-warning/-success/-info` (+`-bg`/`-border`), `--status-*`, `--focus-ring`.
  3. Structure: `--radius-sm..xl`, `--space-1..8`, `--fs-xs..2xl` (**12px floor**), `--shadow-1..3`, `--sidebar-w`.
- **Themes**: Dark default; light via `.light-theme` on `<html>`+`<body>`. Toggle button lives in the sidebar footer (`nav.php` → `toggleTheme()` in `js/wcc-ui.js`). Persisted in localStorage key `theme`. **Theme Lab was removed** — `users.theme_prefs_json` is a dormant column.
- **Page shell**: every page starts its HTML with `$page_title = '...'; require_once .../inc/head.php;` (emits doctype/head/opening body incl. anti-flash + versioned assets), then optional page `<style>`, then `nav.php`. Cache-busting via `WCC_UI_VERSION` in `inc/version.php` — bump it on every CSS/JS deploy.
- **Component set** (use these, don't invent new families): `.wcc-card` (+`-hover`, accent variants), `.wcc-stat`, `.btn` + `.btn-primary/.btn-danger/.btn-success/.btn-ghost/.btn-sm/.btn-block`, `.page-header` (exactly one `<h1>` per page), `.toolbar`, `.field` (label+input), `.wcc-modal` + `-sm/-md/-lg` (open with `openWccModal(id)`), `showToast(msg, type)`, `.wcc-tabs`, `.table-cards` (opt-in mobile card-collapse — give each `<td>` a `data-label`), `.wcc-empty`, `.visually-hidden`.
- **Accessibility contract**: every `<label>` has `for=`; icon-only buttons get `aria-label`; modals are `role="dialog" aria-modal="true"`; nothing renders below 12px; status is never color-only (pair with text/glyph); `:focus-visible` ring is global.
- **Best Practices**:
  - Never hardcode colors — always use `var(--name)`. Muted text is `var(--text-muted)`, never `#94a3b8`/`#64748b`.
  - Support both dark (default) and light; test both.
  - UI must remain fast and usable on shop floor (44px touch targets under 768px, minimal animations, `prefers-reduced-motion` respected).
  - The LEGACY COMPAT section at the bottom of `global.css` only shrinks — never add to it.
- **Related Files**: `css/global.css`, `inc/head.php`, `inc/version.php`, `js/wcc-ui.js`, `nav.php`.

## Workflows
See dedicated `KEY_FLOWS.md` for detailed state machines and user journeys (Ticket Lifecycle, Work Orders/PM, Procurement, Inventory Reorder, etc.).

## Relations and Dependencies
- **Code Relations**: Most pages follow pattern: include `auth.php` + `rbac.php` + `inc/db.php` + `nav.php`.
  - After reorg, use `__DIR__ . '/../auth.php'` style includes inside `_module/` pages.
  - HTML links use root-absolute paths (`/_maint/xxx.php`, `/css/global.css`).
- **Module Dependencies**: 
  - `_maint/` depends on equipment and inventory (via joins and actions).
  - `_logi/` (inventory/purchasing) feeds into maintenance and assets.
  - `_eam/` and `_prod/` provide the asset hierarchy used everywhere.
- **DB Relations**: Documented in `DATA_MODEL.md` (foreign keys, self-refs like equipment.parent_asset_id, production_lines ↔ equipment).
- **Runtime Dependencies**: RBAC (`rbac.php`), central DB (`inc/db.php`), audit logging (`inc/audit.php`).
- **When adding features**: Update relevant module README, _ai_ctxt files, and ensure permission checks + path updates.

## REST API
- Handlers go in `api/v1/resources/`.
- Router lives in `api/v1/index.php`.
- Always call `api_authenticate()` and use `require_api_perm()`.
- Return via `api_response(true, $data, $msg, 200, $meta)`.

## Documentation for Agents
- Keep `_ai_ctxt/` files up to date.
- After schema changes → run the generator.
- When adding a new major flow or module → add/update the corresponding file in `_ai_ctxt/`.

## Testing & Deployment
- Test on actual XAMPP with the live DB.
- Use `migrations/migrate.php` for schema changes.
- Backup before major refactors (see `backups/`).

Follow these and the codebase stays maintainable for both humans and AI agents.
