# PRODUCT STATUS — WCC CMMS Open Beta OB1.0.0

**Last updated:** 2026-08-01  
**Audience:** Any AI AGENT + human maintainers  
**Role of this file:** Product Owner / PM single page — *where we are, what we ship, what we refuse*

---

## 1. One-paragraph brief

WCC (Workshop Control Center) is a **raw-PHP, framework-free CMMS** for manufacturing workshops. **Open Beta OB1.0.0** ("Unified Horizon") was **published on 2026-08-01** as a public GitHub release (`.zip` + `.rar` + companion `.apk`). Deployment is **offline / on-prem only**: each plant runs **its own** install and database. There is **no** multi-tenant cloud control plane, **no** license hardlocks, and **no** requirement for the app to phone home. One public **showcase** instance is hosted so evaluators can try the product without installing — it is an ordinary install running with `WCC_DEMO_MODE=1`, not a tenanted service; see `[public_demo]` in `ai_agent.ini`. Languages ship broadly; **English is the reference** because native QA of all 34 packs is not claimed.

---

## 2. Version stamps (must stay aligned when you release)

| Surface | Location | Value |
|---------|----------|--------|
| About modal + docs version line | `version.json` → `version` | **OB1.0.0** |
| Codename | `version.json` → `codename` | Unified Horizon |
| CSS/JS cache-bust | `inc/version.php` → `WCC_UI_VERSION` | **OB1.0.0** |

Agents changing "the version" should update **both** `version.json` and `WCC_UI_VERSION` unless the human asks for a cache-bust-only bump.

---

## 3. Product principles (non-negotiable)

1. **Technician-first** — speed and clarity on the floor beat enterprise ceremony.  
2. **Raw PHP + PDO** — explicit SQL, no ORM, no build pipeline required.  
3. **Offline-capable** — works on plant LAN / single PC with XAMPP-class stack.  
4. **One site = one install** — data stays local.  
5. **No hardlocks** — do not add license servers, trial timers that disable modules, or geo feature gates.  
6. **RBAC is real** — hide UI *and* enforce server-side.  
7. **Companion is a separate hive** — `/api/companion/*` is owned by the companion package contract; REST v1 is the open integration surface. Extend REST without breaking companion.  
8. **Honest localization** — ship packs; do not claim native validation of every language.

---

## 4. What is in the product (feature inventory)

### Maintenance
- Ticket register → active board → takeover (finish / escalate / hold) → supervisor closeout → history  
- Instant / quick resolve path with `closed_at` for history sort  
- Work orders + PM calendar + checklists (images)  
- Notifications bell (new ticket, close, PR/PO, low stock, WO assign, cert expiry, …)

### Assets
- Equipment vault + ledger, BOM, documents, labels (QR/DataMatrix, Zebra/sheet)  
- **Tooling** vault + ledger, independent perms `view_toolings` / `manage_toolings`  
- Tables: `toolings`, `tooling_bom`, `tooling_documents`  
- Soft-delete on tooling (and equipment patterns)

### Logistics
- Inventory master + ledger + audit  
- Event-driven auto-reorder → same PR routing as manual  
- Vendors, PR/PO lifecycle, Storekeeper fulfil vs cost approve split  

### Admin / people
- 6 roles, **24 permissions**, Role Presets + per-user overrides  
- Users, skills/certs, admin tile board (drag layout), data admin backup/restore/flush  
- My Profile: language (`users.locale`), password, timeout, wave background  

### Platform
- Unified Design System v2 (dark/light)  
- i18n: **34** locales, ~**747** keys, English fallback  
- In-app documentation (`docs.php`, 30 chapters)  
- REST API v1 (incl. **full toolings** resource)  
- Companion endpoints under `/api/companion/` (leave alone unless asked)  
- Security: sessions hardened, login rate limit, CSRF on critical POSTs, `.htaccess` webroot hardening  

---

## 5. REST API status (agents integrating)

**Base:** `/api/v1/` — auth: `X-API-Key` or Basic.

| Resource | Notes |
|----------|--------|
| equipment, tickets, ticket-actions, work-orders, inventory, vendors, POs/PRs, lines, users, roles, stats, audit, me, ai-context, api-keys | Established |
| **toolings** | Full: list/get/create/update/**soft-delete**; nested **bom** + **documents** metadata |
| Ticket IDs | Public `TK-…` strings; numeric path ids → 404 by design |
| ticket-actions | Field `action_taken` |
| vendors | Column `vendor_address` |

**Companion (not REST):** `GET /api/companion/toolings.php`, `scan_lookup.php`, etc. Session/`api_guard_*`. Do not delete these when improving REST.

**Web helpers (session):** `get_tooling_bom.php`, `get_tooling_docs.php`, `upload_document.php`.

QA: `php tests/full_audit/rest_v1_full.php` — includes tooling mutation suite (expect 0 fail).

---

## 6. Quality gates (latest known good)

Recorded under `tests/full_audit/reports/` around **2026-07-28**:

| Gate | Result |
|------|--------|
| security_gates.php | 20 passed / 0 failed |
| full_audit run.php --mutate | 0 FAIL (when MySQL stable) |
| rest_v1_full.php | **67 pass / 0 fail / 1 skip** (after toolings REST) |
| fqa_manual_path.php | 27 / 0 |
| cqa_static.php | 54 / 0 |

Sign-off narrative: `tests/full_audit/reports/LAUNCH_SIGNOFF_20260728.md` (update when you re-gate).

**Known ops risk:** On XAMPP, MariaDB may be down after reboot/crash → login HTTP 500. Start MySQL first. Not a code hardlock.

---

## 7. Open beta distribution model

- Package as zip per `docs/DISTRIBUTION_CHECKLIST.md`  
- Messaging: `docs/OPEN_BETA.md`  
- Each customer/site: own files + own `workshop_db`  
- Demo accounts (if seed dump used): rotate passwords on real plant data  
- Languages: best-effort; English always available  

**Out of scope for beta:** public internet multi-tenant hosting, pen test as gate, load test for thousands concurrent, native review of all 34 languages.

---

## 8. Explicit debt / residual (do not pretend done)

| Item | Notes |
|------|--------|
| Native l10n QA | Community / local help over time |
| RTL polish | ar/ur usable but not pixel-perfect guaranteed |
| Deep admin modal i18n | Partial |
| Notification bodies always localized at write time | Often English at write |
| REST multipart tooling upload | Metadata via REST; binary via upload_document |
| schema.sql vs live drift | Live DB wins; migrations 0017–0021 exist |
| Demo password hygiene | Site responsibility |
| 24h MySQL soak | Recommended ops, not product lock |

---

## 9. Agent decision rules (when unsure)

| Situation | Do this |
|-----------|---------|
| "Should we add a license server?" | **No** — violates open beta offline model |
| "Should we force companion to use only REST?" | **No** unless product owner asks; keep both surfaces |
| "Should we rank languages high-impact?" | **No** — equal groups only |
| "Should we hard-delete toolings in REST?" | Prefer soft-delete (implemented) |
| "DB vs schema.sql disagree" | **Live DB** + add migration if changing |
| "Where is version?" | `version.json` + `WCC_UI_VERSION` |
| "Is this ready for global SaaS?" | **No** — ready for **global offline open beta packages** |

---

## 10. Demo / seed (dev only)

- CLI: `php demo/demo_seed.php` — destructive, HTTP-blocked  
- Shared demo password historically `Demo2026!` — **not for production plants**  
- Users e.g. `a.rivera` admin, `j.okafor` tech, `p.nair` supervisor, `r.silva` operator  

---

## 11. Stack coordinates (default local)

- Web root often `C:\xampp\htdocs`  
- DB: `workshop_db`, user `root`, empty password in `inc/db.php` (LAN default; sites may change)  
- PHP CLI: XAMPP `php.exe` for tests  

---

## 12. When you finish a major change

Update at least:

1. This file (`PRODUCT_STATUS.md`) if status/intent shifted  
2. `ai_agent.ini` `[project]` / `[release_status]` timestamps  
3. `_ai_ctxt/context.json`  
4. Relevant ARCHITECTURE / DATA_MODEL / REST_API / KEY_FLOWS  
5. User-facing docs chapter if feature is user-visible  
6. Tests if API or critical path changed  

---

**End of product status.** Next: `OVERVIEW.md` for domain, `ARCHITECTURE.md` for code map.
