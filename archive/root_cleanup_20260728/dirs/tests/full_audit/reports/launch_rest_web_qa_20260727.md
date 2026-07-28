# Combined QA: REST v1 + website workflows

**Date:** 2026-07-27  
**Host:** `http://127.0.0.1` · DB `workshop_db`  

---

## Executive result

| Area | Result |
|------|--------|
| **REST API v1 full sweep** | **PASS 52 / FAIL 0 / SKIP 1** |
| **Website full audit (`--mutate`)** | **PASS (0 failures)** |
| **Security gates** | **20/20** |
| **Tooling RBAC role smoke** | **PASS** |
| **Launch readiness** | **GO** (after MySQL recovery — see ops note) |

Reports:
- REST: `tests/full_audit/reports/rest_v1_20260727_212331.md`
- Web: `tests/full_audit/reports/audit_20260727_212219.md`
- Suite: `php tests/full_audit/rest_v1_full.php`

---

## Ops note: MySQL crash mid-session

During this run MariaDB failed with InnoDB log corruption (`Missing MLOG_CHECKPOINT`). Recovery performed:

1. Moved broken datadir → `C:\xampp\mysql\data_corrupt_*`  
2. Restored stock `mysql\backup` → `mysql\data`  
3. Removed `innodb_force_recovery`  
4. Re-imported `backups\pre_launch_20260727_220101\workshop_db_*.sql`  
5. Re-applied role tooling perms + admin API key  

**Action for you:** Keep MySQL running via XAMPP Control Panel. Avoid killing mysqld uncleanly. Prefer the `pre_launch_*` dump as restore point.

---

## REST API v1 coverage

### Auth
| Check | Result |
|-------|--------|
| No key → 401 | OK |
| X-API-Key → root + resources list (16) | OK |
| Basic Auth → `/me` | OK |
| `/me` with key | OK |
| Bad key → 401 | OK |

### GET list (all resources)
users, roles, equipment, production-lines, tickets, ticket-actions, work-orders, inventory, vendors, purchase-orders, purchase-requests, stats, audit, ai-context, me — **all OK**

### GET by id
users, equipment, work-orders, inventory, vendors, POs/PRs, production-lines, roles — **OK**  
tickets numeric id 404 is OK (IDs are `TK-…` strings; created ticket GET by real id **OK**)

### Mutations (create / update / delete + cleanup)
| Endpoint | Result |
|----------|--------|
| POST/PUT tickets | OK |
| POST ticket-actions | OK (fixed API: was inserting non-existent `notes` column) |
| POST/PUT/DELETE equipment | OK |
| POST/PUT/DELETE inventory | OK |
| POST/DELETE vendors | OK (fixed API: `address` → `vendor_address`) |
| POST/PUT/DELETE work-orders | OK (payload uses `equip_id`) |
| POST production-lines | OK (`name` + `workshop_id`) |
| stats / audit / ai-context | OK |

**SKIP:** rotating `/api-keys` for live admin mid-suite (key seeded in DB for tests).

### API bugs fixed during this QA
1. `api/v1/resources/ticket_actions.php` — INSERT aligned to real schema (`action_taken`, etc.)  
2. `api/v1/resources/vendors.php` — INSERT/UPDATE use `vendor_address` / `vendor_remarks` (accept legacy aliases)

---

## Website workflow re-sweep

Full `run.php --mutate` including deep probe:

- Static gates, HTTP smoke (equipment, tooling, vaults, tickets, WO, inventory, procurement, admin, history, stats)  
- Search UI markers  
- Tooling BOM/docs upload  
- Symbology save  
- Security gates  
- **0 FAIL**

RBAC checks (HTTP):
- Admin: tooling ledger + vault **allow**  
- Operator/Tech: ledger **allow**, vault **deny**  
- Custom Viewer: tooling + equipment **deny**  
- Supervisor vault **allow**  
- Profile language picker present  

---

## Totals

| Suite | Pass | Fail | Skip |
|-------|------|------|------|
| REST v1 full | 52 | **0** | 1 |
| Web mutate audit | all | **0** | expected mutates only |
| Security gates | 20 | **0** | — |

---

## Recommendation

**Ship soft launch** with:

1. MySQL kept running via XAMPP (data dir already rebuilt + dump restored)  
2. Browser hard-refresh `v=2.7.0`  
3. Re-run anytime:  
   - `php tests/full_audit/rest_v1_full.php`  
   - `php tests/full_audit/run.php --mutate`  

No remaining functional blockers found in REST v1 core CRUD paths or website workflows covered by the harness.
