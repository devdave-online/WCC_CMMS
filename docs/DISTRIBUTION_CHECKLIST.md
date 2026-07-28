# Distribution checklist — offline open beta package

**Goal:** One zip (or folder) a site can install **without internet** and run as **their only instance**.  
**Rules:** No live shared demo. No hardlocks. No forced online license. Each site owns ops.

---

## 1. Package name

Suggested pattern:

```text
WCC_CMMS_OpenBeta_OB1.0.0_YYYYMMDD.zip
```

Include a short `README.txt` at the root of the zip that points to:

- `docs/OPEN_BETA.md` — what open beta means  
- `docs/GETTING_STARTED.md` — install  
- `docs/BACKUP.md` — backup  

---

## 2. Include (application)

| Item | Notes |
|------|--------|
| All PHP application code (`*.php`, modules `_eam/`, `_maint/`, `_rpt/`, `api/`, `inc/`, …) | Root = web root content |
| `css/`, `js/`, `img/`, `lang/` | Full UI + all language packs |
| `docs/` + `docs.php` + `docs/registry.php` | In-app manual |
| `.htaccess` | Webroot hardening |
| `migrations/` | For upgrades / clean schema path |
| `schema.sql` if present and current | Optional baseline |
| `demo/demo_seed.php` (optional) | Only if you want sites to re-seed demo |
| `rbac.php`, `auth.php`, `nav.php`, entry pages | Core |

**Database dump (required for “works out of the box”):**

| Item | Notes |
|------|--------|
| A known-good SQL dump | e.g. copy of `workshop_db_*.sql` into `package/sql/` or `backups/seed/` |
| Optional: empty-schema-only dump | For sites that refuse demo data |

Document which dump is **demo** vs **empty**.

---

## 3. Exclude (do not ship)

| Exclude | Why |
|---------|-----|
| `.git/`, `.gitignore` local secrets | Source control / noise |
| `mysql/data*`, XAMPP tree | Not part of the app |
| `C:\xampp\mysql\data_corrupt_*` | Corrupt archives |
| Live `uploads/*` with plant files | Privacy; ship empty dirs with `.gitkeep` if needed |
| Local `backups/*` recovery clutter | Keep **one** intentional seed dump only |
| `tests/full_audit/.qa_api_key` | Live secrets |
| PHP session / temp files | Clutter |
| IDE / local AI AGENT session folders | Not product |
| `node_modules` / vendor if any unused | N/A if pure PHP |

Optional for **smaller** beta zips: omit `tests/` (gates are for maintainers). Keep them if partners will re-run QA on site.

---

## 4. Pre-zip maintainer checklist

- [ ] UI version bump if this is a new drop (`inc/version.php`)  
- [ ] Fresh dump from a clean, known state (or re-verify existing seed dump opens)  
- [ ] No `innodb_force_recovery` in any bundled MySQL config (you should not bundle MySQL config at all)  
- [ ] Demo passwords documented as **demo only** (`docs/OPEN_BETA.md` + seed docs)  
- [ ] `php tests/security_gates.php` → 0 fail (if tests included / available)  
- [ ] Spot-check: copy package to a **second** empty folder, import dump, login once  
- [ ] README lists: offline model, one site = one install, English = reference language  

---

## 5. Site install (hand this to the customer)

### Windows / XAMPP (typical offline plant PC)

1. Install XAMPP (or use existing). Start **MySQL**, then **Apache**.  
2. Copy package contents into the web root (e.g. `C:\xampp\htdocs\` or a vhost folder).  
3. Create database `workshop_db` (phpMyAdmin or CLI).  
4. Import the supplied SQL dump.  
5. If credentials differ from default local root/empty password, edit `inc/db.php`.  
6. Open `http://localhost/` (or their vhost) → login.  
7. Change passwords for any account they keep.  
8. Take a backup and store a copy **off the PC**.  

### Linux LAMP (optional)

Same files + MariaDB import + point `inc/db.php` at a scoped DB user. TLS is **their** choice for their domain; the product does not require a cloud control plane.

---

## 6. Post-install smoke (site, 10 minutes)

| # | Check |
|---|--------|
| 1 | Login works |
| 2 | Register one test ticket |
| 3 | Takeover or instant resolve once |
| 4 | Equipment or tooling list loads |
| 5 | My Profile → language switch (optional) → can return to English |
| 6 | Backup still exists / new backup created |

If MySQL is stopped, the app will error — **start MySQL first**. That is operational, not a product hardlock.

---

## 7. Multi-site distribution

| Rule | Detail |
|------|--------|
| One package build | Same zip for everyone |
| One database per site | Never share one DB across plants |
| Credentials | Each site sets its own passwords |
| Updates | Ship a new zip + migration notes; site applies offline |
| Feedback | Aggregate translation/bug reports centrally; fix in next zip |

No remote “enable site” step. No geo hardlock.

---

## 8. Suggested zip layout

```text
WCC_CMMS_OpenBeta_OB1.0.0/
  README.txt                 ← short: offline, open beta, start MySQL, open docs
  docs/
    OPEN_BETA.md
    GETTING_STARTED.md
    DISTRIBUTION_CHECKLIST.md
    BACKUP.md
    ...
  sql/
    workshop_db_seed.sql     ← intentional dump only
  (application root files and folders)
  lang/
  api/
  inc/
  ...
```

---

## 9. README.txt template (paste into zip root)

```text
WCC CMMS — Open Beta
====================
Offline / on-prem. One install per site. No cloud license lock.

1. Start MySQL, then the web server (e.g. XAMPP).
2. Copy these files into the web root.
3. Create database workshop_db and import sql/workshop_db_seed.sql
4. Open the site in a browser and sign in.
5. Change demo passwords. Read docs/OPEN_BETA.md and docs/GETTING_STARTED.md

Languages: many UI packs ship with English fallback. English is the
reference language; other locales are best-effort in open beta.

Support: [YOUR CHANNEL HERE]
Version: OB1.0.0
```

Replace `[YOUR CHANNEL HERE]` before shipping.

---

## 10. Done criteria for “we can send this globally”

- [ ] Zip builds clean on a machine that never saw the original XAMPP tree  
- [ ] Import + login succeeds offline  
- [ ] OPEN_BETA wording is in the package  
- [ ] One intentional SQL seed (demo and/or empty)  
- [ ] No secrets, no corrupt datadirs, no hardlock modules  
- [ ] You accept that l10n quality will improve via **site feedback**, not pre-launch native QA for all 34  

When those boxes are checked, you can distribute the open beta without pretending it is a hosted global SaaS.
