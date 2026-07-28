# WCC Data Model

This document describes the core database schema and business semantics.

**Source of truth**: the **live DB** first, then `schema.sql` + `migrations/` (latest numbered files include **0021** locale, **0020** closed_at, **0017–0019** toolings). Known drift: live may have more tables than schema.sql lists; `role_definitions` historically lacked a `description` column live. When column lists matter, check the live DB.

**How to refresh**: Run `php _ai_ctxt/generate-context.php` (parses schema.sql and can introspect live DB).

## Core Entities & Relationships

### Users & Access Control
- **users**: Central identity table.
  - `role_level`: 1=Operator, 2=Technician, 3=Supervisor, 4=Admin, 5=Custom Viewer, 6=Storekeeper (fulfils POs, no cost approval).
  - `permissions_json`: Per-user overrides on top of role defaults.
  - `theme_prefs_json`: dormant (Theme Lab removed 2026-07-17).
  - `admin_layout_json`: per-user admin panel tile order (migration 0014); NULL = default.
  - `api_key`: For REST API authentication (X-API-Key).
  - `locale`: UI language pack code (i18n).
  - `status`, `last_login`, `must_change_password` (authoritative), workshop scoping.

- **role_definitions**: Preset permissions per role_level (JSON). **Editable source of truth** via Role Presets UI; **24 permission keys** incl. tooling pair `view_toolings` / `manage_toolings`, procurement split `approve_purchase_orders` / `fulfill_purchase_orders`, and `delete_users`.
- **team_directory**: Lookup for "announced_by", technicians, etc.

### Assets (Equipment)
- **equipment**: The heart of the asset registry.
  - `parent_asset_id`: Self-referential hierarchy.
  - `workshop_id` / `line_id`: Link to production structure.
  - `asset_purchase_id`: Link to procurement POs.
  - Rich metadata: purchase, warranty, PM intervals, technical_details (JSON), loto_protocol, etc.
  - `is_active` / soft-delete `deleted_at` where used.
  - Factory health queries must exclude soft-deleted equipment.

- **equipment_bom**: Bill of materials linking equipment to inventory_parts.
- **equipment_documents**: Manuals / drawings for equipment.

- **production_lines**: Logical grouping under workshops.
  - Linked to equipment via line_id.
  - `products_built`, status.

- **workshops** (referenced): Plants / areas.

### Assets (Tooling) — parallel register, not a subtype of equipment
- **toolings**: Dies, molds, fixtures, jigs, gauges, hand tools, cutting tools, …
  - Keys: `tooling_id`, unique `tooling_code`, `tooling_name`, `barcode`, `asset_tag`
  - `status`: Available | In Use | Maintenance | Calibration Due | Retired
  - `condition_rating`: New | Good | Fair | Poor
  - Optional `linked_equip_id` (home machine), `workshop_id`, `line_id`
  - Soft-delete: `deleted_at` (REST DELETE sets this + Retired)
- **tooling_bom**: `tooling_id` → `inventory_parts` (qty, notes); unique (tooling_id, part_id)
- **tooling_documents**: docs metadata (`file_path`, type, uploaded_by)
- **RBAC:** `view_toolings` / `manage_toolings` only — independent of equipment perms
- **APIs:** REST `/api/v1/toolings` (+ bom/documents); companion `/api/companion/toolings.php`; web `get_tooling_*` + `upload_document.php`

### Maintenance & Tickets
- **active_tickets**: Live faults and events.
  - `ticket_id` (e.g. `TK-YYMMDD-###` / human-readable string PK)
  - Status: OPEN, PENDING, ESCALATED, HOLD, CLOSED, …
  - `closed_at` set on close (history sort)
  - Links to equipment, announced_by, pic, priority, fault_desc, event_class

- **ticket_actions**: Every technician intervention.
  - Time tracking (`action_start` / `action_end`)
  - `parts_used`, **`action_taken`** (not a free-form `notes` column on REST writes), tech_name
  - Drives MTTR and consumption analytics

- **ticket_comments**: Discussion thread for active tickets.
  - `ticket_id`, `user_name`, `comment_text`, `created_at`.

- **work_orders**: Scheduled maintenance (PM or corrective).
  - Status: Scheduled, In Progress, Completed.
  - `scheduled_date`, `started_at`, assigned_to, priority.
  - `checklist_data`: JSON snapshot of PM checklist tasks (with up to 3 image uploads per task).
  - Can be generated from PM rules or manually.

- **pm_checklists** / **pm_checklist_items**: Master templates for Work Order tasks.

- **history** (logical): Closed tickets move or are viewed via history.php (same table or filtered).

### Inventory & Logistics
- **inventory_parts**: Spare parts master.
  - Rich fields: internal_code, oem_part_number, stock_level, minimum_threshold, lead times, moq, currency, etc.
  - Linked to vendors via manufacturer_id.

- **inventory_ledger**: Transaction log for stock movements.
  - `change_qty` (positive = receipt, negative = consume)
  - `reason` (wo_consume, ticket_action, po_receipt, adjustment...)

- **vendors_suppliers**: Supplier master.

### Procurement
- **purchase_orders**: A PR and its PO are the SAME row (there is no separate purchase_requests table — `po_number` is "PR-YYYYMMDD-####").
  - `status` ENUM: Draft → Pending Approval → Issued → Shipped → In Transit → Partially/Fully Received → Closed / Cancelled.
  - `approval_level`: "Requires Admin" | "Auto-Approved".
  - Approval routing driven by `app_settings` keys `procurement_workflow_enabled` + `po_auto_approve_limit` (see KEY_FLOWS §5).
  - `po_items` linking to inventory_parts (multi-line PRs supported).
- **po_status_logs**: Full lifecycle audit; `note` column (migration 0012) carries step comments + auto-approve reasons.
- **po_documents** (migration 0012): Attached docs per PO (e.g. supplier invoice uploads); generated PR document via `pr_document.php`.
- Budget consumed on Fully Received (departments.budget_consumed + department_budget_logs).
- Strong audit trail for compliance (IATF hints).

### Other Important Tables
- **audit_log**: Centralized change tracking (actor, before/after JSON, entity).
- **notifications** (migration 0015): per-user notification center. Cols `id, user_id, type, message, link, severity, is_read, created_at`. Written by `inc/notifications.php`, read by nav.php bell + `api/notifications.php`.
- **app_settings**: Runtime config — session_lockout_time, KPI targets, plant_holidays, procurement_workflow_enabled, po_auto_approve_limit, optional `default_reorder_dept_id` (auto-reorder department override; when unset, auto-reorders fall back to the single/first department — the "Budget" — so their spend consumes a budget on receipt), plus category `EquipmentLabels` (equip_label_* keys: symbology, field toggles, method, label/page/margins/gaps mm, Zebra ip/port/dpi/darkness/speed — self-healed by `_eam/label_lib.php`).
- **analytics_logs**: Cached KPIs.
- **equipment_bom**: Bill of materials (equipment ↔ parts).
- **po_status_logs**: Detailed PO lifecycle events.

## Important Business Rules & Invariants
- A ticket is "active" until explicitly closed via closeout flow.
- Work orders can exist independently or be linked to tickets.
- Stock movements must go through the ledger (never direct UPDATE on stock_level alone in normal flows).
- Production lines group equipment for shop-floor visibility.
- Permissions are additive (role defaults + user overrides).
- Soft deletes (`deleted_at`) are used on some entities for history preservation.

## Code-Level Relations & Dependencies
- **Include Chain** (typical page): auth.php → rbac.php → inc/db.php → nav.php (+ _about_modal.php)
- **Cross-Module**:
  - Tickets and Work Orders heavily reference Equipment (equip_id) and Production Lines.
  - Inventory and Purchase Orders are tightly coupled (po_items → inventory_parts).
  - Audit log is written from many places (users, equipment, POs, etc.).
- **Shared Singletons**: Only one DB connection function, one RBAC engine.
- **UI Dependencies**: nav.php drives most navigation; changes there affect permission-gated links everywhere.
- **API Dependencies**: All v1 resources depend on bootstrap.php (auth + helpers).

## Query Patterns Commonly Used
- Join equipment + production_lines + workshops for asset views.
- ticket_actions + work_orders for time/parts consumption.
- inventory_ledger for movement history.
- audit_log for compliance / "who changed what".

For the complete raw schema, see `schema.sql` in the project root.



## Auto-Generated from schema.sql

**Generated:** 2026-07-23 04:00:06

### Core Tables

- **`active_tickets`**
- **`analytics_logs`**
- **`app_settings`**
- **`audit_log`**
- **`departments`**
- **`equipment`**
- **`equipment_bom`**
- **`inventory_ledger`**
- **`inventory_parts`**
- **`notifications`**
- **`po_documents`**
- **`po_items`**
- **`po_status_logs`**
- **`purchase_orders`**
- **`role_definitions`**
- **`schema_migrations`**
- **`skill_automation_config`**
- **`team_directory`**
- **`ticket_actions`**
- **`user_registration_config`**
- **`user_skills`**
- **`users`**
- **`vendors_suppliers`**
- **`work_orders`**

> Full column definitions and foreign keys are in `schema.sql`.