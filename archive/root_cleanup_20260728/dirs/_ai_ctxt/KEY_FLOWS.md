# Key Business Flows

## 1. Ticket Lifecycle (Core CMMS Flow)
1. **Register** (`register.php` or API)
   - Operator selects equipment/line.
   - Enters fault description, priority, announced_by.
   - Creates record in `active_tickets`.

2. **View & Assign** (`active_tickets.php`)
   - Technicians see open/pending tickets.
   - Can filter, search.

3. **Takeover** (`takeover.php`)
   - Technician claims the ticket.
   - Starts tracking time.

4. **Perform Work**
   - Log actions in `ticket_actions` (time, parts, notes).
   - Can escalate, add more work.

5. **Closeout** (`closeout.php`)
   - Final review.
   - Record total time, parts used, root cause hints.
   - Change status to CLOSED.
   - Ticket moves to history view.

6. **History** (`history.php`)
   - Closed interventions for analysis and repeat detection.

**Important**: "Evil Maid" protection — closeout and takeover are hard-locked to the authenticated user.

## 2. Work Order / PM Flow
- Preventive Maintenance generated in `app_settings.php` or `admin_panel.php` or via seed.
- Appear in `work_orders` with `scheduled_date`.
- `pm_calendar.php` shows calendar view.
- Technician takes over via `wo_takeover.php`.
- Status transitions: Scheduled → In Progress → Completed.
- Can consume inventory via ledger.

## 3. Asset Management (Equipment)
- Equipment created/edited in `_eam/`.
- Linked to `production_lines` (via `setup_vault_lines.php` in _prod).
- Lines belong to Workshops.
- Used heavily in ticket registration and work orders.
- **Physical labels**: `setup_vault_equipment.php` has per-row + batch (select-all) label printing via `_eam/equipment_labels.php`; shared builders in `_eam/label_lib.php`. Payload is compact offline data `WCC|<id>|<uuid>|<name≤40>|SN:<serial>` (intranet, no URLs). Symbology QR/DataMatrix selectable (edit-modal "Label Generator"); rendering is fully local (vendored `js/bwip-js-min.js` for browser, native `^BQ`/`^BX` for Zebra). Print methods: sheet-grid on any printer (configurable page/label/margins/gaps), one-label-per-page, or direct ZPL to a Zebra over TCP 9100. Settings in `app_settings` category `EquipmentLabels`, edited via the vault page's "Label & Printer Setup" modal (gated `manage_equipment`).

## 3b. Tooling Management (parallel to equipment)
- **Ledger** `_eam/toolings.php` — requires `view_toolings`; accordion BOM + documents.
- **Vault** `_eam/setup_vault_toolings.php` — requires `manage_toolings`; master data CRUD.
- **Web JSON:** `api/get_tooling_bom.php`, `api/get_tooling_docs.php`, `api/upload_document.php` (`entity=tooling`).
- **REST v1:** full `/api/v1/toolings` (+ `/bom`, `/documents`) for integrations — soft-delete on DELETE.
- **Companion hive (leave alone):** `api/companion/toolings.php` list/search; `scan_lookup.php` can return `kind=tooling`.
- Flush access: uncheck `view_toolings` / `manage_toolings` on user or role presets — does not touch equipment.

## 4. Inventory & Reorder
- Parts master in `_logi/inventory.php`.
- **Event-driven auto-reorder**: when consumption (wo_takeover / api/submit_takeover) drops an `auto_reorder=1` Active part to/below `minimum_threshold`, `inc/reorder.php` `wcc_check_and_reorder()` places a `PR-AUTO-…` reorder through the SAME procurement routing as a manual PR (`inc/procurement.php` `wcc_procurement_route()`), dedupes against open orders, and notifies. No vendor → notify-only. Batch fallback + manual "Run reorder check" button (inventory.php) via `cron_requisition.php`.
- Consumption is logged in `inventory_ledger` — trailable in the parts-consumption history (`inventory_audit.php`) — for BOTH paths: work-order takeover (`wo_consume`, ref work_orders) and ticket takeover (`ticket_consume`, ref active_tickets). Both consume only the actual on-hand amount (stock floored at 0) and the ledger records the real quantity. Closeout does not touch stock.
- Receipts via Purchase Orders.

## 4b. Notifications (per-user, backend/static)
- Table `notifications` (migration 0015); helper `inc/notifications.php` (`wcc_notify`, `wcc_notify_perm`, `wcc_unread_count`, `wcc_recent_notifications`) mirrors `inc/audit.php`.
- Nav bell (nav.php sidebar footer) shows a red pulse + count when unread; opens the `wccNotifModal` overlay (server-rendered list, Mark all read / Delete all). Actions via `api/notifications.php` (JSON, session + CSRF, user-scoped). Counts refresh on page load (no live polling).
- Triggers wired: new ticket (→takeover_tickets), event closed (→view_statistics), PR needs approval (→approve_purchase_orders), PO awaiting fulfilment (→fulfill_purchase_orders), low-stock/auto-reorder (→manage_inventory + approvers), WO assigned (→assignee).

## 5. Procurement Flow
1. Create Purchase Request (`purchase_requests.php`).
2. Approval routing (configured via the ⚙ Workflow modal in `_logi/purchase_orders.php` — gated by `approve_purchase_orders`, NOT manage_settings; keys in `app_settings`):
   - `procurement_workflow_enabled` = 0 → every PR auto-approves on submit (status `Issued`, approval_level `Auto-Approved`).
   - `= 1` with `po_auto_approve_limit` > 0 → PRs with total ≤ limit auto-approve; larger ones go `Pending Approval`.
   - Otherwise → `Pending Approval` until a holder of `approve_purchase_orders` signs off (cost approval).
3. After approval the **Storekeeper** takes over: `fulfill_purchase_orders` gates Ship / In Transit / Receive / Close in `purchase_orders.php` + `_trck/tracking_stepper.php`. Role level 6 "Storekeeper" holds fulfill but not approve; Admin holds both. Transitions are permission-checked server-side, not just hidden in the UI.
4. Multiple status steps tracked in `po_status_logs` (auto-approvals log a note explaining why).
5. Upon receipt: update inventory + ledger; budget consumed on Fully Received.
6. Full audit trail.

## 5b. Demo Seeding (`demo/demo_seed.php`)
- **CLI only** (`php demo/demo_seed.php`) — it is destructive, so it must never be an HTTP endpoint. Returns 403 over the web.
- Flushes transactional + master tables, **preserves** `role_definitions`, `app_settings`, `schema_migrations`, then rebuilds a mid-size plant: 2 workshops / 6 lines / 24 assets, 35 parts, 8 vendors, 5 departments, 11 users (one per role), ~126 tickets over 9 months, 52 work orders, 11 PM schedules + 5 checklists, 33 POs parked at **all 9 stepper stages**, ledger rows for all three movement types, unread notifications, 40 audit entries.
- **Every date is relative to `NOW()`**, so the demo never looks stale — re-run before any pitch (or nightly) and "yesterday" is genuinely yesterday. `mt_srand` is seeded fixed by default so runs are reproducible; `--seed=N` draws a different variant of the same shape.
- Deliberate imperfections make it credible: 2 overdue PM schedules, 3 missed work orders, 7 parts at/below reorder point (1 stocked out), expired warranties.
- Demo logins: `a.rivera` (Admin), `p.nair` (Supervisor), `j.okafor` (Technician), `r.silva` (Operator), `h.bakker` (Storekeeper), `c.whitfield` (Viewer) — password `Demo2026!`.

## 6. User & Permission Flow
- Login → `auth.php`
- Login hardening: session ID regenerated on success (fixation defence), failed attempts throttled per IP via `inc/ratelimit.php`, identical error text for unknown-user vs wrong-password (no username enumeration), PDO messages logged not echoed.
- RBAC loaded from `role_definitions` + user `permissions_json`.
- `can('permission_name')` and `require_perm()` used everywhere.
- Admin can override per user in `users.php`.
- API uses same RBAC via `require_api_perm()`.

## 7. Analytics & KPIs
- Raw data from tickets + actions + ledger + work_orders.
- `statistics.php` and `setup_vault_analytics.php` compute:
  - MTTR, MTTD, MTBF
  - Downtime
  - Parts consumption
  - Technician utilization
- Some cached in `analytics_logs`.

## State Machines (Simplified)
**Tickets**: OPEN → PENDING → (IN_PROGRESS via action) → CLOSED (via closeout)

**Work Orders**: Scheduled → In Progress → Completed

**Purchase Orders**: Draft → Pending Approval → Issued → Shipped → In Transit → Partially/Fully Received → Closed (or Cancelled). Pending→Issued needs `approve_purchase_orders` (or auto-approve per settings); Issued onward needs `fulfill_purchase_orders`.

These flows are the heart of the system. Any new feature should align with or explicitly extend these.
