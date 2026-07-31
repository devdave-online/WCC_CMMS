# Run WCC CMMS with Docker (the 90-second demo)

The fastest way to try WCC — no PHP, MySQL, or XAMPP setup. One command boots the
app **and** a database pre-loaded with a full demo factory (equipment, tickets,
work orders, inventory, procurement, KPIs).

## Prerequisites

- **[Docker Desktop](https://www.docker.com/products/docker-desktop/)** (Windows / macOS),
  or Docker Engine + Compose plugin (Linux).

## Run it

```bash
git clone https://github.com/devdave-online/WCC_CMMS
cd WCC_CMMS
docker compose up
```

Wait ~30 seconds for the database to load, then open **http://localhost:8080**.

## Demo logins

All accounts share the password **`Demo2026!`** (the built-in admin is `admin` / `admin`).

| Username | Role | What they can do |
|---|---|---|
| `a.rivera` | Admin | Everything |
| `p.nair` | Supervisor | Approvals, closeout, statistics |
| `j.okafor` | Technician | Takeover, work orders |
| `r.silva` | Operator | Register events |
| `h.bakker` | Storekeeper | PO fulfilment, stores |
| `c.whitfield` | Custom Viewer | Read-only |

## Stop / reset

```bash
docker compose down        # stop (keeps the database)
docker compose down -v     # stop and wipe the DB — next 'up' reloads a fresh demo
```

## Notes

- The app reads its DB settings from environment variables (`WCC_DB_*`), set in
  `docker-compose.yml`. Running under plain XAMPP instead, those are unset and it
  falls back to `localhost / root / (empty) / workshop_db` — the classic setup.
- The demo database ships in `docker/initdb/`. To refresh it after schema changes,
  re-dump your working DB over that file.
- Server-side backup/restore (Data Administration) shells out to XAMPP's Windows
  `mysqldump` path and is inert inside the container — everything else works.
