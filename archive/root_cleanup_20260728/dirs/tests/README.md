# WCC CMMS — Test Suite

Run from project root with XAMPP PHP:

```bat
C:\xampp\php\php.exe tests\kpi_accuracy.php
C:\xampp\php\php.exe tests\security_gates.php
C:\xampp\php\php.exe tests\rbac_merge.php
C:\xampp\php\php.exe tests\api_procurement_smoke.php
```

## Full functional audit (section simulation loop)

Apache/XAMPP must be running. Default login: demo admin `a.rivera` / `Demo2026!` (override with env).

```bat
C:\xampp\php\php.exe tests\full_audit\run.php
C:\xampp\php\php.exe tests\full_audit\run.php --mutate
C:\xampp\php\php.exe tests\full_audit\run.php --suite=05_assets_loop
```

Env overrides: `WCC_QA_BASE`, `WCC_QA_USER`, `WCC_QA_PASS`.  
Optional local config: `tests/full_audit/config.local.php` (array merge).

Reports: `tests/full_audit/reports/audit_*.md` (+ `.json`).

| Suite | What it does |
|-------|----------------|
| `01_static_gates` | Lint critical files, auth patterns, tables, tooling artifacts |
| `02_http_smoke` | Login, load every registry page, search/accordion markers, BOM/docs APIs |
| `03_search_loop` | Per-page search UI + column dropSearch counts |
| `04_tickets_loop` | Ticket create (`--mutate` only) |
| `05_assets_loop` | Equipment/tooling BOM+docs APIs, vault UI, optional tooling create+soft-delete |
| `06_inventory_loop` | Inventory load/search markers |
| `07_procurement_loop` | PR/PO/vendors load |
| `08_api_rest_loop` | Notifications, REST closed-without-key, companion |

**Safety:** no app source changes; mutations off unless `--mutate`; QA rows tagged `[QA-AUDIT]`.

Or all (Windows):

```bat
C:\xampp\php\php.exe tests\kpi_accuracy.php && C:\xampp\php\php.exe tests\security_gates.php && C:\xampp\php\php.exe tests\rbac_merge.php && C:\xampp\php\php.exe tests\api_procurement_smoke.php && C:\xampp\php\php.exe tests\full_audit\run.php
```

| Script | Purpose |
|--------|---------|
| `kpi_accuracy.php` | KPI engine / shift calendar (existing, heavy) |
| `security_gates.php` | Static authz / CSRF / identity / migration guards |
| `rbac_merge.php` | Permission registry merge + overrides |
| `api_procurement_smoke.php` | PR/PO model + route helper + resource source checks |
| `full_audit/run.php` | Section-by-section HTTP + capability simulation |

Exit code `0` = all checks passed; non-zero = failures.

**Note:** These are CLI-only. They never run over the public web as a browser script.
