WCC CMMS — Open Beta
====================
Offline / on-prem. One install per site. No cloud license lock. No feature hardlocks.
Licensor: David Zoltan Csiki  |  License: LICENSE.txt (Apache 2.0 + Commons Clause)
Open beta, as-is: keep backups; you secure your own install.

1. Start MySQL, then the web server (e.g. XAMPP: MySQL first, then Apache).
2. Copy the application into the web root.
3. Create database workshop_db and import the supplied SQL seed dump.
4. If needed, edit inc/db.php for your DB user/password.
5. Open the site in a browser and sign in.
6. Change every demo password you keep. Take a backup off the machine.

Read:
  docs/OPEN_BETA.md              — what open beta means, languages, support
  docs/GETTING_STARTED.md        — install detail
  docs/DISTRIBUTION_CHECKLIST.md — for packagers building the zip
  docs/BACKUP.md                 — backup / restore

Languages: many UI packs ship with English fallback. English is the
reference language; other locales are best-effort in open beta.
Report bad translations with locale code + context.

Support: About modal → Found a bug? (email) / Wanna chat? (LinkedIn)
Version: OB1.0.0
