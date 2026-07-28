# Workshop Control Center (WCC)

**Free. Unlimited seats. Source available.**

A modern CMMS built for real workshops and factories.

Most maintenance software charges per seat.  
That forces teams to share logins — and destroys data quality.

WCC removes the seat meter completely.

---

## Why WCC?

- **Free** for your own use
- **Unlimited seats** — every technician can have their own account
- **34 languages**
- **Fully offline Android companion**
- **AI Agent-ready** (`ai_agent.ini` + complete `ai_ctxt` documentation)
- Modern, clean interface
- Self-hosted — you own your data
- Full REST API

Built for the people who actually keep the lines running.

---

## Key Features

- Full ticket / work order lifecycle with **measured times** (not estimates)
- Preventive Maintenance with recurring schedules and checklists
- Asset register with offline QR / DataMatrix label printing
- Real inventory ledger linked to the exact job
- End-to-end procurement with separation of duties
- Live reliability KPIs (MTTR, MTBF, Ghost Time, etc.)
- Granular RBAC (22 permissions, editable roles)
- Skills & certification tracking
- Fully offline-capable Android companion app
- Complete AI agent documentation so you can run your own agents against the system

---

## Screenshots

<!-- Replace these with your real screenshots -->

### Dashboard
![Dashboard](docs/screenshots/dashboard.png)

### Work Orders
![Work Orders](docs/screenshots/tickets.png)

### Inventory
![Inventory](docs/screenshots/inventory.png)

### Asset Register
![Assets](docs/screenshots/assets.png)

### Android Companion
![Companion](docs/screenshots/companion.png)

---

## Quick Start

1. Clone the repository
2. Point your web server to the project
3. Import the database
4. Open the app and log in
5. Change the default password

**Requirements:**
- PHP 8.0+
- MySQL / MariaDB
- Apache with `mod_rewrite`

Detailed installation instructions are inside the project.

---

## Android Companion

The companion app is built for the shop floor:

- Works fully offline
- Fast QR / barcode scanning
- Quick actions after scanning an asset
- Syncs when connection is available

---

## AI Agent Support

WCC is designed so AI agents can understand and work with it.

- `ai_agent.ini` — agent initialization
- `_ai_ctxt/` — full structured documentation of the system

You can run your own agents using your own API keys.

---

## License

**Apache License 2.0 + Commons Clause**

You may:
- Use it freely
- Modify it
- Run it commercially in your own plant

You may **not**:
- Sell the software
- Offer it as a paid hosted service

See the [LICENSE](LICENSE) file for full details.

---

## Support the Project

WCC is free and will stay free.  
If it helps your plant and you want to support continued development, you can buy me a coffee.

---

**Status:** Open Beta (OB1.0.0)  
Made by David Zoltan Csiki
