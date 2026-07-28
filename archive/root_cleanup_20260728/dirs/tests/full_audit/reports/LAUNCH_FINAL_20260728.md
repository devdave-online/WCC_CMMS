# WCC CMMS OB1.0.0 — Final pre-launch gate report

**Date:** 2026-07-28  
**Release:** OB1.0.0 Open Beta  
**Licensor:** David Zoltan Csiki  
**Master runner:** `php tests/full_audit/run_all_gates.php`  
**Result:** **ALL GATES GREEN**

---

## Automated gates (this run)

| Gate | Result | Report |
|------|--------|--------|
| security_gates | **PASS** (20/0) | console / legacy in audit |
| cqa_static (+ L0 poison) | **PASS** (69/0) | `cqa_20260728_190401.md` |
| full_audit --mutate | **PASS** (0 fail) | `audit_20260728_190407.md` |
| fqa_manual_path | **PASS** (27/0) | `fqa_manual_20260728_190409.md` |
| rest_v1_full | **PASS** (67/0, skip 1) | `rest_v1_20260728_190411.md` |
| pre_ship_deep | **PASS** (109/0) | `preship_deep_20260728_190416.md` |
| **run_all_gates** | **PASS** | `PRESHIP_ALL_20260728_190357.md` |

---

## What pre_ship_deep exercised

### Auth / roles
- Login: admin, tech, supervisor, operator

### Page matrix
- Full `registry.php` page loads as admin (no PHP fatals / 500s)
- Public docs + LICENSE

### Ticket actionable events
- Create OPEN ticket  
- Tech **finish** → PENDING  
- **Escalate** → ESCALATED  
- **Hold** → HOLD  
- Supervisor **closeout / sign-off** → CLOSED + `closed_at`  
- History shows closed ticket  
- **Instant resolve** → CLOSED + `closed_at`

### Self-service
- Locale save/restore  
- Session timeout save  
- Password change + re-login + restore  

### Assets
- Equipment BOM, tooling BOM/docs  
- Operator vault deny  

### REST
- `/me`, `/toolings`  

### Documentation
- docs.php content markers (RBAC, tooling, API, tickets)  
- Chapter files present  
- Assets tooling section, API toolings, selfservice language  

---

## L0 static protections added

- Detect **raw** `json_encode` inside double-quoted `onclick` (the class of bug that killed password change)  
- Confirm modal present + defined  
- Profile password form is real `submit` path  
- About Gmail/LinkedIn/privacy/beta markers  
- Version OB1.0.0 + full legal name in LICENSE  

---

## How to re-run before package

```text
C:\xampp\php\php.exe tests\full_audit\run_all_gates.php
```

Human L3 (JS-only, ~20 min): `tests/full_audit/reports/UI_CHECKLIST.md`

---

## Docs ship checklist

| Item | Status |
|------|--------|
| Open beta + local privacy (About + OPEN_BETA.md) | Yes |
| License Apache 2.0 + Commons Clause, licensor full name | Yes |
| Tooling RBAC + REST toolings in docs | Yes |
| Self-service language | Yes |
| Ticket lifecycle docs | Yes |
| UI checklist for manual JS | Yes |

---

## Residual (not automated)

- Real browser confirm dialogs (covered by UI checklist)  
- Print/label hardware  
- All 34 locales quality  
- MariaDB process must be running on host  

---

## Verdict

**SOFT LAUNCH / OPEN BETA PACKAGE: GO** on automated evidence as of this report.

Ship package only after:
1. `run_all_gates.php` green (done)  
2. Optional: tick UI_CHECKLIST.md once on a hard-refreshed browser  
3. Confirm `$wcc_contact_bug_email` is your real inbox  
