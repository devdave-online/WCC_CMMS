# WCC CMMS — Project Overview

## What is WCC?

**WCC (Workshop Control Center)** is a custom-built, **framework-free** Computerized Maintenance Management System for manufacturing and industrial workshops.

It manages the maintenance lifecycle on the factory floor:

- Logging equipment (and tooling-related) events  
- Assigning and tracking work (tickets + work orders / PM)  
- Spare parts inventory and procurement  
- Asset registers: **equipment** and **tooling** (separate)  
- Analytics (MTTR, consumption, KPI targets)  
- Users, RBAC, notifications, REST + companion integrations  

**Core philosophy:** Built *by* technicians and engineers *for* technicians and engineers. Speed, clarity, and practicality over bloat.

---

## Release posture (OB1.0.0 Open Beta)

| | |
|--|--|
| Version | **OB1.0.0** (codename Unified Horizon) |
| Mode | **Open beta** |
| Deploy | **Offline / on-prem**, one database and file tree **per site** |
| Hardlocks | **None** |
| Languages | 34 packs ship; **English is the reference** (native QA of all languages not claimed) |
| Companion | Separate mobile package; uses `/api/companion/*` |

See `_ai_ctxt/PRODUCT_STATUS.md` and `docs/OPEN_BETA.md`.

---

## Primary users

| Role (level) | Job |
|--------------|-----|
| Operator (1) | Register faults, limited read |
| Technician (2) | Takeover, actions, consume stock, tooling/equipment view as granted |
| Supervisor (3) | Closeout, approvals, broader visibility |
| Admin (4) | Full configuration, users, data admin |
| Custom Viewer (5) | Empty base — grant explicitly |
| Storekeeper (6) | Fulfil POs / stock — **not** cost approve |

---

## Capability map

| Domain | Capabilities |
|--------|----------------|
| **Tickets** | Register, board, takeover, escalate, hold, closeout, history, comments, instant resolve, `closed_at` |
| **Work orders / PM** | Schedules, calendar, checklists, takeover, inventory consume |
| **Equipment** | Vault, ledger, BOM, docs, labels (QR/DM, Zebra/sheet), soft-delete aware health |
| **Tooling** | Parallel register; vault/ledger; BOM/docs; RBAC pair; REST v1 full; companion list/scan |
| **Inventory** | Master, ledger, audit, auto-reorder |
| **Procurement** | PR/PO single table model; workflow toggle; approve vs fulfil; documents |
| **People** | Users, roles (24 perms), skills/certs, profile language |
| **Analytics** | KPIs, history, exports |
| **Platform** | i18n, design system, notifications, docs, backup/restore/flush, REST, companion APIs |

---

## Technology stack

- PHP 8.x (raw, no framework)  
- MySQL / MariaDB (InnoDB, utf8mb4)  
- PDO only (`inc/db.php`)  
- Vanilla JS + CSS custom properties (Unified Design System v2)  
- Session auth + hardened cookies; REST X-API-Key / Basic  
- File-based deploys (copy + import SQL)  

---

## Domain vocabulary

| Term | Meaning |
|------|---------|
| **Ticket** | Fault/event in `active_tickets` until closed |
| **Action** | Technician intervention row in `ticket_actions` |
| **Work order** | Planned / PM work in `work_orders` |
| **Tooling** | Dies, molds, fixtures, gauges, etc. — **not** the same table as equipment |
| **Closeout** | Supervisor completion of a ticket |
| **Evil Maid** | Sensitive pages bound to authenticated session user |
| **Fulfil vs approve** | Storekeeper logistics vs cost authority on POs |

---

## Current status snapshot (2026-07-28)

- Modular domains (`_maint`, `_eam`, `_logi`, …)  
- Design system v2 + dark/light  
- Tooling module + independent RBAC  
- i18n 34 locales equal groups  
- REST v1 includes **toolings** (+ bom/documents)  
- Companion tooling endpoints remain for the mobile package  
- Soft-launch automated gates green on restored DB; open beta packaging docs present  
- Residual: ops care for MySQL process stability on XAMPP; l10n quality via community  

This is a **production-oriented plant tool**, not a toy demo — demo seed data is only for exploration.
