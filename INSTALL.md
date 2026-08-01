# Installing WCC CMMS

Two ways to run it. Pick one.

---

## Option A — XAMPP (Windows, no Docker)

**You need:** [XAMPP](https://www.apachefriends.org/) with **PHP 8.0+** and MySQL/MariaDB.

1. **Download** `WCC-OB1.0.0.zip` from the
   [latest release](../../releases/latest) and extract it.

2. **Copy the files** into your web root, so `index.php` sits at
   `C:\xampp\htdocs\index.php`.

3. **Create the database** — open <http://localhost/phpmyadmin>, create a database
   named `workshop_db` with collation `utf8mb4_unicode_ci`.

4. **Import the schema** — select `workshop_db` → **Import** → choose `schema.sql`
   → **Go**. That creates all 79 tables, empty.

5. **Start Apache + MySQL** in the XAMPP control panel, then open
   <http://localhost>.

6. **First login** — the app creates a default administrator on first run and
   forces a password change immediately. Change it before letting anyone else in.

### If the connection fails

Credentials live in `inc/db.php` and default to the standard XAMPP setup
(`localhost` / `root` / empty password / `workshop_db`). Either match that, or set
the environment variables `WCC_DB_HOST`, `WCC_DB_NAME`, `WCC_DB_USER`,
`WCC_DB_PASS` — the code reads those first.

---

## Option B — Docker (any OS, one command)

**You need:** [Docker Desktop](https://www.docker.com/products/docker-desktop/).

```bash
git clone https://github.com/devdave-online/WCC_CMMS.git
cd WCC_CMMS
docker compose up -d
```

Open <http://localhost:8080>. See [DOCKER.md](DOCKER.md) for HTTPS, the demo
dataset and public hosting.

---

## Android companion (optional)

`WCC-Companion-OB1.0.0.apk` on the [release page](../../releases/latest) adds
camera QR/DataMatrix scanning and works offline. Android 8+, sideload it
(Settings → allow installs from this source).

It is a separate app on purpose: shop-floor intranets rarely have HTTPS, and
browsers refuse camera access without it.

---

## Requirements

| | |
|---|---|
| PHP | 8.0 or newer (8.2 recommended) |
| Database | MySQL 5.7+ / MariaDB 10.4+ |
| Web server | Apache with `mod_rewrite`, or nginx |
| Extensions | `pdo_mysql`, `mysqli` |
| Browser | Any current browser |

## Try before installing

Live demo: **<https://141.147.117.183.nip.io>** — sign in as `a.rivera` with
password `Demo2026!` (destructive actions are disabled there; they work normally
in your own installation).
