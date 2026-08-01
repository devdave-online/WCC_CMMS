# AGENT INSTRUCTIONS — READ THIS FIRST

**For every AI AGENT working on WCC CMMS.**

If you skip this file, you will ship the wrong product shape (SaaS locks, companion breakage, wrong version stamps, or "enterprise" rewrites). **Do not skip.**

---

## 0. 60-second orientation

| Fact | Value |
|------|--------|
| Product | **WCC CMMS** — Workshop Control Center |
| Release | **OB1.0.0** Open Beta (codename Unified Horizon) |
| Runtime | Raw **PHP 8** + **MariaDB/MySQL** + vanilla JS/CSS — **no framework** |
| Deploy | **Offline / on-prem**, **one install per site** |
| Hardlocks | **None** — never add license/geo/kill-switch gates |
| Companion | **Separate package** under `/api/companion/*` — leave unless asked |
| Version stamps | `version.json` **and** `inc/version.php` → `OB1.0.0` |
| Full PO brief | `_ai_ctxt/PRODUCT_STATUS.md` |
| Bootstrap ini | `/ai_agent.ini` (project root) |

---

## 1. Mandatory reading order

1. **This file** (`AGENT_INSTRUCTIONS.md`)  
2. **`PRODUCT_STATUS.md`** — PM/PO truth: ship model, gates, debt  
3. **`OVERVIEW.md`** — domain & users  
4. **`ARCHITECTURE.md`** — folders, shared infra  
5. **`DATA_MODEL.md`** — tables & invariants  
6. **`KEY_FLOWS.md`** — ticket / PO / tooling flows  
7. **`CONVENTIONS.md`** — code & UI standards  
8. **`REST_API.md`** — integration surface (incl. toolings)  
9. **`context.json`** — machine-readable index  

Also load root **`ai_agent.ini`**. Optional quick print:

```bash
php _ai_ctxt/print-init-summary.php
```

Human product docs: `docs/OPEN_BETA.md`, in-app `docs.php`.

---

## 2. Absolute rules

1. **No framework migration** unless the human explicitly orders it.  
2. **Root-absolute paths** for cross-module links: `/_maint/...`, `/css/...`.  
3. **RBAC server-side:** `can()` / `require_perm()` / `require_api_perm()` / `api_guard_perm()`.  
4. **CSRF** on browser mutating POSTs (`inc/csrf.php`, `WCC_CSRF`).  
5. **DB:** only via `get_wcc_db_connection()` / `$pdo` prepared statements.  
6. **Do not break `/api/companion/*`** when adding REST or web features.  
7. **Do not add hardlocks**, forced cloud auth, or demo-only feature death switches.  
8. **i18n:** no "high impact" language ranking; English fallback is correct.  
9. **Tooling ≠ equipment** — separate perms and tables.  
10. **Ticket IDs** are `TK-…` strings in REST.  
11. **Live DB > schema.sql** when they drift; add migrations for intentional changes.  
12. **After structural change:** update `_ai_ctxt` + tests that cover the path.  

---

## 3. Where code lives

| Area | Path |
|------|------|
| Tickets / WO / closeout | `_maint/` |
| Equipment + tooling UI | `_eam/` (`toolings.php`, `setup_vault_toolings.php`, equipment*) |
| Inventory / PO / vendors | `_logi/` |
| Admin / users / backup | `_mgmt/` |
| Reports / history | `_rpt/` |
| REST v1 | `api/v1/` (+ `resources/toolings.php`) |
| Companion (hands off) | `api/companion/` |
| Shared | `inc/`, `rbac.php`, `auth.php`, `nav.php`, `lang/` |
| AI context | `_ai_ctxt/`, `ai_agent.ini` |
| QA | `tests/` |

---

## 4. REST vs companion vs web JSON

| Client | Typical base | Auth |
|--------|--------------|------|
| Integrations / new clients | `/api/v1/*` | X-API-Key / Basic |
| Companion app | `/api/companion/*` | Session + api_guard |
| Browser UI AJAX | `/api/*.php` | Session + CSRF + api_guard |

**Toolings REST (full):**  
`/api/v1/toolings`, `/toolings/{id}/bom`, `/toolings/{id}/documents`  
**Toolings companion (leave):**  
`/api/companion/toolings.php`

---

## 5. Permissions you must know

**24 keys.** Tooling pair:

- `view_toolings` — ledger, BOM/docs read APIs, REST GET  
- `manage_toolings` — vault, REST write, BOM/doc mutations  

Independent of `view_equipment` / `manage_equipment`.

---

## 6. Version & about modal

- About modal reads **`version.json`**.  
- Assets use **`WCC_UI_VERSION`** from `inc/version.php`.  
- Current release string: **OB1.0.0**.

---

## 7. QA commands (local)

```bash
php tests/security_gates.php
php tests/full_audit/run.php --mutate
php tests/full_audit/rest_v1_full.php
php tests/full_audit/fqa_manual_path.php
php tests/full_audit/cqa_static.php
```

MySQL must be running or HTTP suites fail.

---

## 8. Keep context fresh

```bash
php _ai_ctxt/generate-context.php
php _ai_ctxt/generate-context.php --live
```

If product strategy changes (release type, deploy model, major feature), update **`PRODUCT_STATUS.md`** and **`ai_agent.ini`** in the same change set.

---

## 9. Tone of the product

Built **by** people who work plants **for** people who work plants. Prefer:

- Explicit over clever  
- Working shop-floor flow over abstract purity  
- Documented honesty (open beta l10n) over fake "enterprise certified" claims  

**Start with PRODUCT_STATUS.md next.**
