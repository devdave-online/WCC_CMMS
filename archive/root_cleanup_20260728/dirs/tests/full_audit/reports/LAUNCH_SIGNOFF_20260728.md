# WCC CMMS — Soft Launch Sign-off

**Date:** 2026-07-28  
**Host:** local XAMPP (`http://127.0.0.1` / `localhost`)  
**Database:** `workshop_db` (MariaDB 10.4.32, datadir rebuilt 2026-07-27)  
**Scope:** Soft launch (plant LAN / internal), not public internet production  
**Sign-off drivers:** Automated CQA + FQA (A4 manuals) + prior A5 gate re-run  

---

## Verdict

| Gate | Result | Evidence |
|------|--------|----------|
| A1 Infrastructure (MySQL + Apache + login) | **PASS** at sign-off | mysqld held through full suites; `login.php` HTTP 200 |
| A2 Data integrity post-restore | **PASS** | users present; tooling tables; `locale` / `closed_at`; no `innodb_force_recovery` in my.ini |
| A3 Security gates | **PASS** | `security_gates.php` **20 / 0** |
| A4 Critical path (manuals / FQA HTTP) | **PASS** | `fqa_manual_path.php` **27 / 0** |
| A5 Full audit + REST | **PASS** | `full_audit --mutate` **0 fail**; REST v1 **52 pass / 0 fail / 1 skip** |
| CQA static | **PASS** | `cqa_static.php` **54 / 0** |
| Documentation feature coverage | **PASS** | Tooling RBAC, language, tables match-count, API contract, deploy MySQL note updated |

### Soft launch decision

**APPROVED for soft launch** on this host, with residual ops risk noted below (MySQL process management after unclean shutdowns).

> On 2026-07-28, against restored `workshop_db` on localhost: security_gates 20/20, full_audit 0 fail, rest_v1 52/0 (1 skip), FQA manual path 27/0, CQA static 54/0, tooling RBAC allow/deny verified, backup dump documented. Soft launch approved for internal/LAN use.

---

## A5 automated results (current datadir)

| Suite | Command | Result | Report |
|-------|---------|--------|--------|
| Security | `php tests/security_gates.php` | 20 passed, 0 failed | console |
| Full audit | `php tests/full_audit/run.php --mutate` | Failures: **0** | `audit_20260728_164849.md` |
| REST v1 | `php tests/full_audit/rest_v1_full.php` | pass=**52** fail=**0** skip=**1** | `rest_v1_20260728_164850.md` |

REST skip: `POST /api-keys` intentionally skipped (would rotate live admin key).

Earlier same-day run `audit_20260728_164609.md` (6 fails) is **invalidated** — all failures were runner/login while MySQL was down mid-suite, not product defects.

---

## A4 / FQA critical path (HTTP manuals)

**Command:** `php tests/full_audit/fqa_manual_path.php`  
**Result:** pass=**27** fail=**0**  
**Report:** `fqa_manual_20260728_164933.md`

| Step | Case IDs | Outcome |
|------|----------|---------|
| 1 Admin login → hub | `1_admin_login`, `1_hub` | OK (`a.rivera`) |
| 2 Register ticket | `2_create_ticket` | OK `TK-260728-004` |
| 3 Takeover finish | `3_tech_login`, `3_takeover_finish`, `4_status_pending` | OK → PENDING |
| 4 Escalate | `4_escalate`, `4_status_escalated` | OK `TK-260728-005` → ESCALATED |
| 5 Closeout + history | `5_sup_login`, `5_closeout`, `5_history_shows_ticket` | OK history shows ticket |
| 6 Instant resolve | `6_instant_resolve`, `6_closed_at` | OK CLOSED + `closed_at` |
| 7 Equipment BOM/docs | `7_equip_bom`, `7_equip_docs` | OK HTTP 200 |
| 8 Tooling BOM/docs | `8_tooling_bom`, `8_tooling_docs` | OK HTTP 200 |
| 9 Admin tooling vault | `9_admin_vault` | OK |
| 10 Operator vault deny | `10_op_vault_denied` | OK Access Denied |
| 11 Flush tooling perms | `11_flush_tooling_deny`, `11_flush_restore` | OK deny then restore |
| 12 Language | `12_profile_lang_picker`, `12_language_vi` | OK `vi` marker |
| 13 REST me + tickets | `13_rest_me`, `13_rest_tickets` | OK 200 |
| 14 Backup dump | `14_backup_dump` | OK ~438 KB dump |
| Health soft-delete | `health_soft_delete` | OK source gate |

---

## CQA static

**Command:** `php tests/full_audit/cqa_static.php`  
**Result:** pass=**54** fail=**0**  
**Report:** `cqa_20260728_164936.md`

Coverage includes:

- PHP lint on critical pages/APIs (tickets, tooling, REST resources, vaults)
- RBAC symbols: `view_toolings`, `manage_toolings`, backfill helper
- i18n: **34** locales, equal groups (no “high impact”), **747** keys, **0** incomplete packs
- Tooling page/API permission gates
- CSRF on submit ticket/closeout/takeover/hold
- Soft-delete filter in factory health
- No `innodb_force_recovery` in my.ini
- JS `escapeHtml`, search match count, i18n `t()`
- REST schema markers: `action_taken`, `vendor_address`

---

## Documentation updates (feature completeness)

| Area | Chapter | Notes |
|------|---------|-------|
| Tooling RBAC + flush | `10-rbac.php`, `14-assets.php` | Separate view/manage; vault vs ledger |
| Tooling schema | `07-schema.php` | `toolings` / `tooling_bom` / `tooling_documents`; `closed_at`; `locale` |
| Tables UX | `18-tables.php` | Tooling ledgers, match count, history close sort |
| Language | `23-selfservice.php` + registry section `my-language` | 34 packs, equal groups |
| API contract | `27-api.php` | `TK-…` ids, `action_taken`, `vendor_address`, `equip_id` |
| Deploy ops | `28-deployment.php` | Start MySQL first; dump path; no force recovery |
| Registry TOC | `docs/registry.php` | `tooling`, `my-language` sections |

All 30 manual chapters remain the product feature inventory (Orientation through AI handoff).

---

## Backup / restore

| Item | Location |
|------|----------|
| Pre-launch dump | `backups/pre_launch_20260727_220101/workshop_db_20260727_220101.sql` (~438 KB) |
| Role snapshot | `backups/pre_launch_20260727_220101/role_definitions.json` |
| User snapshot | `backups/pre_launch_20260727_220101/users_snapshot.json` |
| Corrupt datadir archive | `C:\xampp\mysql\data_corrupt_20260727_221856` (do not use as live datadir) |

---

## Residual risks (accepted for soft launch)

1. **MariaDB process stability** — process can be absent after host sleep / unclean stop; start MySQL before use; prefer clean shutdown.  
2. **Demo credentials** — rotate all shared demo passwords before real plant accounts.  
3. **Root DB with empty password** — acceptable only for isolated LAN; not for internet exposure.  
4. **Deep admin modal i18n / notification body localization / RTL polish** — deferred debt.  
5. **Public internet hardening** (TLS, scoped DB user, CORS lock) — required only if exposed beyond LAN.  
6. **GET /tickets/{numeric}** returns 404 by design — clients must use `TK-…` identifiers.

---

## Explicitly out of scope (logged debt)

- Load / performance testing  
- External pen test  
- Companion fine-grained tooling RBAC beyond login gates  
- 24h continuous soak (recommended next ops step)  

---

## Morning re-check (if box rebooted)

```text
1. Start MySQL + Apache (XAMPP)
2. mysql -u root -e "SELECT 1"
3. php tests/security_gates.php
4. php tests/full_audit/run.php --mutate
5. php tests/full_audit/rest_v1_full.php
6. php tests/full_audit/fqa_manual_path.php
7. php tests/full_audit/cqa_static.php
```

Pass bar: 0 FAIL on each (REST skip=1 OK).

---

## Sign-off

| Role | Name | Decision |
|------|------|----------|
| Engineering / QA automation | Autonomous gate suite | **GO** soft launch |
| Human owner | ________________ | ☐ GO  ☐ HOLD |

**Status recorded:** Soft launch **GO** on automated + FQA/CQA evidence as of 2026-07-28 16:49 local.
