# WCC CMMS — Open Beta (global, offline)

**Product version:** OB1.0.0 (`version.json` + `inc/version.php`)  
**Release type:** Open beta  
**Deployment model:** Offline / on-prem only — **one install per site**  
**Licensor:** David Zoltan Csiki  
**License:** Apache 2.0 + Commons Clause (see `LICENSE.txt`)  
**Not included:** Shared multi-tenant cloud demo, license hardlocks, remote kill switches  

### Open beta, in plain words

This is beta software for evaluation and real-world feedback. It is provided **as-is**. The author is not responsible for lost data or unexpected downtime — keep backups, use sensible passwords, and secure your own host. Full legal text is in `LICENSE.txt`.

---

## One-sentence pitch

> **WCC CMMS Open Beta** is an on-prem workshop CMMS you run on your own machine or plant network. Full features for each site. Languages ship as best-effort; **English is the reference** until local speakers validate packs.

---

## What this beta is

| | |
|--|--|
| **Where it runs** | Site PC, plant LAN server, or the site’s own host/domain — **their** instance |
| **Network** | Offline / intranet capable; no requirement to phone home |
| **Data** | Stays on that site’s database |
| **Features** | Full product (tickets, WO/PM, equipment & tooling, inventory, procurement, RBAC, REST, docs) |
| **Languages** | 34 UI packs with English fallback; **not** natively QA’d for every locale |

## What this beta is not

- Not a single public “try WCC online” multi-tenant service  
- Not a locked trial that expires or disables modules by region  
- Not a claim that every translation was reviewed by a native speaker  
- Not a requirement for internet access to use day-to-day features  

---

## Localization honesty (say this out loud)

You cannot confirm all locales without local help. That is expected.

**Public wording (copy-paste):**

> Open beta. The interface is available in many languages. **English is authoritative.** Other languages may contain machine-assisted or imperfect phrasing. Switch back to English anytime under My Profile. Please report bad strings with locale code + screenshot/key if possible.

**Technical reality (already in the app):**

- Missing keys fall back to English  
- Packs are key-complete vs `en` at soft-launch QA; **quality ≠ completeness**  
- RTL locales (e.g. Arabic, Urdu) may need layout polish from real users  

**Later (optional, docs only — not hardlocks):**  
Publish a short “community validated” list as natives confirm packs. Unlisted languages stay “shipped / unreviewed.”

---

## Who should install

| Audience | Fit |
|----------|-----|
| Plant / workshop evaluating CMMS offline | Yes |
| Integrator deploying one instance per customer | Yes |
| Site with no reliable internet | Yes |
| Someone expecting a hosted SaaS login page | No — give them install package instead |

---

## Support expectations (open beta)

Define your channel once (email, form, chat, issue tracker) and stick to it.

**In scope for beta feedback:**

- Crash / 500 / data loss  
- Workflow blockers (cannot register, close, or print)  
- Permission / RBAC surprises  
- Translation errors that change meaning  

**Out of scope / later:**

- Pixel-perfect RTL and every modal string in every language  
- Load testing for thousands of concurrent users  
- Custom plant process redesign  

---

## Quality baseline already proven (developer reference)

On restored `workshop_db` (2026-07-28 sign-off pack):

| Gate | Result |
|------|--------|
| Security gates | 20 / 0 |
| Full audit (mutate) | 0 FAIL |
| REST v1 | 52 pass / 0 fail / 1 skip |
| FQA critical path | 27 / 0 |
| CQA static | 54 / 0 |

Reports: `tests/full_audit/reports/LAUNCH_SIGNOFF_20260728.md`  
Re-run the same gates on **your** copy after packaging if you rebuild the dump.

---

## Site admin: first hour after install

1. Start MySQL, then Apache (or equivalent).  
2. Log in; **change** every demo/admin password you keep.  
3. Decide: keep demo data to learn, or flush / load real plant master data.  
4. Create real users and roles; set tooling vs equipment perms deliberately.  
5. Take a backup and copy it **off the machine** (see `BACKUP.md`).  
6. Optional: pick interface language (My Profile) — English remains safe default.  

---

## Related docs

| Doc | Purpose |
|-----|---------|
| [DISTRIBUTION_CHECKLIST.md](DISTRIBUTION_CHECKLIST.md) | What to put in the zip; what to omit |
| [GETTING_STARTED.md](GETTING_STARTED.md) | Install path for a new site |
| [BACKUP.md](BACKUP.md) | Backup / restore |
| In-app manual (`docs.php`) | Full product documentation |
| `tests/full_audit/reports/LAUNCH_SIGNOFF_20260728.md` | Gate evidence (maintainers) |
