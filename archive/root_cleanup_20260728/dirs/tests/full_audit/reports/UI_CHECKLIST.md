# UI / JS checklist (L3) — human, ~20–30 min before ship

Automated HTTP cannot fully prove JS. Tick every item on a hard-refreshed browser.

**Host:** _________________  **Date:** _________________  **Tester:** _________________

## Shell
- [ ] Login works (admin)
- [ ] Sidebar loads; theme toggle works
- [ ] Notifications bell opens list
- [ ] About (🚀 WCC): privacy purple, license green, beta amber
- [ ] About: email address visible + Gmail button opens compose
- [ ] About: LinkedIn button opens profile
- [ ] Confirm modal appears on a destructive action (e.g. vault delete confirm) and Cancel works

## Self-service
- [ ] My Profile → change password (confirm modal → success message)
- [ ] Re-login with new password, then restore if needed
- [ ] Language change (e.g. vi) updates chrome after save/reload
- [ ] Personal session timeout saves

## Tickets
- [ ] Register ticket → appears on active board
- [ ] Takeover finish → PENDING
- [ ] Escalate → ESCALATED
- [ ] Hold (if UI present) → HOLD
- [ ] Supervisor closeout → history shows ticket
- [ ] Instant resolve → CLOSED with closed_at

## Assets
- [ ] Equipment ledger expand BOM/docs
- [ ] Tooling ledger expand BOM/docs
- [ ] Tooling vault loads for admin; Access Denied for operator

## Tables UX
- [ ] Search + match count updates
- [ ] Drag-to-filter token (one table)

## Docs
- [ ] `/docs.php` loads public without login
- [ ] RBAC / assets tooling / API chapters open from sidebar

## Sign-off
- [ ] No console red errors on critical paths (optional DevTools)
- [ ] Automated `run_all_gates.php` already green

**UI L3:** ☐ PASS  ☐ FAIL notes: _________________________________
