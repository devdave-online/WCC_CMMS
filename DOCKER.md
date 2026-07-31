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

## Publishing it with HTTPS (optional)

To expose the demo on the internet with a real certificate, start the extra
`caddy` service. It obtains and renews a free Let's Encrypt certificate on its
own — no manual certificate handling.

```bash
# hostname must resolve to this machine; with no domain, use a wildcard-DNS host
export WCC_PUBLIC_HOST=demo.example.com          # or 203.0.113.10.nip.io
docker compose --profile public up -d --build
```

Requirements:

- **Ports 80 and 443 reachable from the internet.** Port 80 is used for the
  ACME HTTP-01 challenge; without it no certificate can be issued.
- **`WCC_PUBLIC_HOST` must resolve to this server.** Any real hostname works.
  With no domain, `<your-ip>.nip.io` resolves to that IP and is perfectly valid
  for a certificate.

Caddy redirects `http://` to `https://` automatically. Certificates are stored in
the `caddy_data` volume — keep it, or every restart re-requests one and burns
Let's Encrypt quota.

`docker compose up -d` **without** `--profile public` runs the plain local stack
with no certificate machinery, which is what you want on a laptop or a LAN.

### Behind a proxy

`WCC_TRUSTED_PROXIES` (already set in `docker-compose.yml`) lists the addresses
allowed to set `X-Forwarded-For`/`X-Forwarded-Proto`. Only requests coming from
those addresses have the headers honoured, so the login throttle sees each real
visitor instead of lumping everyone behind the proxy's single address. Leave it
unset on a direct LAN install and the headers are ignored entirely.

## Notes

- The app reads its DB settings from environment variables (`WCC_DB_*`), set in
  `docker-compose.yml`. Running under plain XAMPP instead, those are unset and it
  falls back to `localhost / root / (empty) / workshop_db` — the classic setup.
- The demo database ships in `docker/initdb/`. To refresh it after schema changes,
  re-dump your working DB over that file.
- Server-side backup/restore (Data Administration) shells out to XAMPP's Windows
  `mysqldump` path and is inert inside the container — everything else works.
