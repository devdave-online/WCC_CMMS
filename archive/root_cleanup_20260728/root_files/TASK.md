# WCC Hardening Tasks

**Status legend:** `pending` | `in_progress` | `done` | `deferred`  
**Project:** WCC CMMS (`C:\xampp\htdocs`)  
**Rule:** After every **4–5 tasks marked done**, the agent MUST surface a reminder about **T-SCHEMA**.

---

## Active queue

### Phase A — Tracking + identity

| ID | Status | Task |
|----|--------|------|
| T1 | done | TASK.md |
| T2 | done | Inactive users blocked (login + API + companion Basic) |
| T3 | done | Temp password on create/reset |

### Phase B — Authorization

| ID | Status | Task |
|----|--------|------|
| T4 | done | Inventory audit RBAC |
| T5 | done | Legacy read API perms |
| T6 | done | Write APIs + no PDO leakage |
| T7 | done | UI page audit |
| T8 | done | Migration CLI guards |

### Phase C — CSRF

| ID | Status | Task |
|----|--------|------|
| T9 | done | csrf.php + head WCC_CSRF |
| T10 | done | PO/PR/users form CSRF |
| T11 | done | JSON mutation CSRF + clients |
| T12 | pending | Browser smoke (manual) |

### Phase D — RBAC

| ID | Status | Task |
|----|--------|------|
| T13 | done | Full registry merge |
| T14 | done | Viewer override verified |

### Phase E — REST

| ID | Status | Task |
|----|--------|------|
| T15 | done | PR API → purchase_orders |
| T16 | done | PO approve vs fulfill |
| T17 | done | Docs updated |

### Phase F — DB credentials UI

| ID | Status | Task |
|----|--------|------|
| T18 | cancelled | Removed — fixed root/empty for LAN only |
| T19 | cancelled | DB Connection UI stripped from App Settings |
| T20 | cancelled | N/A — no runtime credential config |

### Phase G — XSS

| ID | Status | Task |
|----|--------|------|
| T21 | done | escapeHtml in wcc-ui.js (v2.5.2) |
| T22 | done | Patch high-risk innerHTML sinks |

### Phase H — Tests

| ID | Status | Task |
|----|--------|------|
| T23 | done | kpi_accuracy.php (run) |
| T24 | done | tests/security_gates.php |
| T25 | done | tests/rbac_merge.php |
| T26 | done | tests/api_procurement_smoke.php |
| T27 | done | tests/README.md |

---

## Deferred

| ID | Status | Task |
|----|--------|------|
| T-SCHEMA | deferred | Regenerate schema.sql from live + DATA_MODEL refresh |
| T-CSP | deferred | Global CSP after main wave |

---

## Completion log

| When | IDs | Notes |
|------|-----|-------|
| 2026-07-26 | T1–T17 (exc T12) | Security wave 1 |
| 2026-07-26 | T18–T27 | DB UI, XSS, full CLI tests (all green) |

## Done count

- **Completed:** 26 (T12 manual browser smoke still open)  
- **Schema reminder:** YES — still deferred (T-SCHEMA)
