# WCC CMMS — Project Structure

**Status:** Reorganized (2026-07-13)  
**Confirmed by user:** Yes

This document captures the **final target folder structure** for the WCC CMMS application. The goal is a clean, domain-segmented layout using `_` prefixed module folders for easy navigation, future expansion, and separation of concerns.

## Root (Minimal Bootstrap + Shared)

Only essential entry points, shared libraries, and infrastructure stay at the top level.

```
C:\xampp\htdocs\
├── index.php                  # Main dashboard / Tickets Hub
├── login.php
├── register.php
├── my_profile.php
├── change_password.php
├── auth.php                   # Shared authentication
├── rbac.php                   # Shared role/permission engine
├── nav.php                    # Shared sidebar navigation (updated links)
├── _about_modal.php
├── theme_css.php
├── add_css.php
│   (thin support files)
│
├── inc/
│   ├── db.php                 # Centralized DB connection (get_wcc_db_connection)
│   └── audit.php
├── css/
│   └── global.css
├── img/
│
├── api/                       # REST API (top-level is conventional)
│   └── v1/
│       ├── index.php          # Router
│       ├── bootstrap.php      # Auth, helpers, error handling
│       └── resources/
│           ├── users.php
│           ├── equipment.php
│           ├── production_lines.php   # (planned)
│           ├── tickets.php
│           ├── work_orders.php
│           ├── inventory.php
│           ├── vendors.php            # (planned)
│           └── ...
│
├── migrations/
│   └── *.sql + migrate.php
├── backups/
├── archive/
│   └── dead_code/
│
├── schema.sql
├── version.json
├── LICENSE.txt
├── CMMS_QA_AND_FUTURE_PLAN.md
├── rest_api_core.md
└── (misc one-off scripts)
```

## Module Folders (Domain Segmented)

All new feature pages and existing module-specific pages belong here.

```
├── _mgmt/                     # Management & Administration
│   ├── admin_panel.php
│   ├── app_settings.php
│   ├── users.php
│   ├── users_list.php
│   ├── branding.php
│   ├── setup_vault_departments.php
│   └── README.md
│
├── _prod/                     # Production (MES / Shop Floor)
│   ├── setup_vault_lines.php          ← Production Lines view + management (MOVED)
│   └── README.md
│
├── _qual/                     # Quality
│   └── README.md
│
├── _eam/                      # Enterprise Asset Management
│   ├── equipment.php
│   ├── equipment_list.php
│   ├── setup_vault_equipment.php
│   └── README.md
│
├── _maint/                    # Maintenance Operations (Core CMMS)
│   ├── active_tickets.php
│   ├── work_orders.php
│   ├── pm_calendar.php
│   ├── takeover.php
│   ├── wo_takeover.php
│   ├── quick_resolve.php
│   ├── closeout.php
│   ├── repair_closeout.php
│   └── README.md
│
├── _logi/                     # Logistics, Inventory & Procurement
│   ├── inventory.php
│   ├── purchase_requests.php
│   ├── purchase_orders.php
│   ├── vendors.php
│   ├── vendors_list.php
│   ├── pr_document.php
│   ├── setup_vault_vendors.php
│   └── README.md
│
├── _trck/                     # Tracking, Historian & Stepper
│   ├── tracking_stepper.php
│   └── README.md
│
├── _rpt/                      # Reports, Analytics & Insights
│   ├── statistics.php
│   ├── history.php
│   ├── setup_vault_analytics.php
│   └── README.md
│
├── _cmms/                     # CMMS Core / Dedicated Features
│   └── README.md
│
├── _erp/                      # ERP
│   └── README.md
│
├── _mes/                      # Manufacturing Execution System
│   └── README.md
│
├── _docs/                     # Project Documentation
│   ├── PROJECT_STRUCTURE.md   ← This file lives in _dev_artifacts (copy or symlink concept)
│   └── ...
│
└── _dev_artifacts/            # Development artifacts, plans, trees, exports
    └── PROJECT_STRUCTURE.md   ← Current location of this document
```

## Current State After Initial Reorg (2026-07-13)

- Created all module folders listed above.
- Moved `setup_vault_lines.php` → `_prod/setup_vault_lines.php` (Production Lines view + management page).
- Updated includes in the moved file to use `__DIR__ . '/../...'` for shared files (`auth.php`, `rbac.php`, `nav.php`, `inc/db.php`).
- Updated navigation link in `nav.php`:
  - `href="_prod/setup_vault_lines.php"`
  - `basename()` check continues to work.
- All other pages remain at root for now (minimal blast radius). Future passes will migrate files into their logical modules.
- New folders contain (or will contain) `README.md` describing purpose and planned contents.
- No other live code references to the old `setup_vault_lines.php` path remain.

### Actual Current Snapshot (after full migration)

```
htdocs/
├── (root - shared/entry only)
│   ├── index.php, login.php, register.php, my_profile.php, change_password.php
│   ├── auth.php, rbac.php, nav.php, _about_modal.php
│   ├── theme_css.php, add_css.php, timer.js, ...
│   ├── api/, inc/, css/, img/, migrations/, archive/, backups/
│
├── _mgmt/ (7 files)
│   ├── admin_panel.php, app_settings.php, users.php, users_list.php
│   ├── branding.php, setup_vault_departments.php
│
├── _prod/ (2 files)
│   └── setup_vault_lines.php + README
│
├── _eam/ (4 files)
│   ├── equipment.php, equipment_list.php, setup_vault_equipment.php
│
├── _maint/ (9 files)
│   ├── active_tickets.php, work_orders.php, pm_calendar.php
│   ├── takeover.php, wo_takeover.php, quick_resolve.php
│   ├── closeout.php, repair_closeout.php
│
├── _logi/ (8 files)
│   ├── inventory.php, purchase_*.php, vendors*.php, pr_document.php
│   └── setup_vault_vendors.php
│
├── _rpt/ (4 files)
│   ├── statistics.php, history.php, setup_vault_analytics.php
│
├── _trck/ (2 files)
│   └── tracking_stepper.php (component)
│
├── _qual/, _cmms/, _erp/, _mes/   (placeholders + READMEs)
│
└── _dev_artifacts/, _docs/
```

All includes (using __DIR__ . '/../...'), nav links, cross-page hrefs (using module paths or ../ for root), self-redirects, JS window.location, and form actions updated for the new locations.

**Full migration of content pages completed** (2026-07-13). 
- All major UI/content pages moved to logical modules (_mgmt, _prod, _eam, _maint, _logi, _rpt, _trck).
- Remaining backup/BAK/page files cleaned to archive/dead_code.
- **Dependencies (pointers) fixed**:
  - Server includes: `__DIR__ . '/../...'` for auth/rbac/nav/db in all moved pages.
  - HTML pointers: all `href`, `action`, `window.location`, `Location:` updated to root-absolute `/path` (e.g. `/_maint/work_orders.php`, `/index.php`, `/register.php`).
  - This fixes links when shared nav/about_modal are included from subfolder pages.
  - Asset links (css/global.css, timer.js) use `/css/...` and `/timer.js`.
  - Cross and self updated for robustness.
  - Verified: no remaining bare references to old paths in live code.

Only core shared/bootstrap files, helpers, scripts, and infrastructure remain at root.

**AI Agents**: 
- Root `AGENTS.md` is the entry point.
- Full context lives in `_ai_ctxt/`.
- Use `GET /api/v1/ai-context` (and `?live=1`) for dynamic context.
- Run `php _ai_ctxt/generate-context.php [--live]` after schema changes.
```


## How to Move Additional Pages (Guidelines)

When moving a page `foo.php` into a module (e.g. `_maint/`):

1. Move the file.
2. Fix top-of-file includes:
   ```php
   include __DIR__ . '/../auth.php';
   require_once __DIR__ . '/../rbac.php';
   require_once __DIR__ . '/../inc/db.php';
   ```
3. Fix any `include 'nav.php';` or similar inside the file.
4. Update **all** links that point to it:
   - In `nav.php`
   - In `admin_panel.php`, `_about_modal.php`, `index.php`, other pages
   - Internal `header("Location: ...")` and form actions (use module path or relative filename if same dir)
5. Update `basename($_SERVER['PHP_SELF'])` checks if they exist (usually still work).
6. Test RBAC permission, the page load, and any links to it.
7. Update this document + `rest_api_core.md` / `CMMS_QA_AND_FUTURE_PLAN.md` if needed.

## REST API Note

The API lives under `/api/v1/`. Future resources will be added for:
- `production_lines`
- `vendors`
- `purchase_requests` / `purchase_orders`
- Statistics, audit logs, API key management, etc.

See `rest_api_core.md` and `api/v1/` for current implementation.

## Philosophy

- **Lean root** — only what is required to bootstrap every page.
- **Domain folders** — `_prod`, `_maint`, `_logi`, etc. map to real business areas.
- **Future-proof** — empty folders are intentional placeholders.
- **Documentation lives close** — `_dev_artifacts/` for working trees and plans.
- **Update everything** — never leave broken links after a move.

---

**Last updated:** 2026-07-13 (post-confirmation reorg)

For questions or next steps (more migrations, full REST expansion), refer to the active task list or CMMS_QA_AND_FUTURE_PLAN.md.
