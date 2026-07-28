# WCC UI string context

Keys: 747 — use with en.json + glossary.json for agent translation/review.

| Key | English | Type | Screen |
|-----|---------|------|--------|
| `about.assisted_by` | Assisted by | label | about |
| `about.btn.admin_panel` | Admin Panel | label | about |
| `about.btn.browse_assets` | Browse Assets | label | about |
| `about.btn.diagnostics` | System Diagnostics | label | about |
| `about.btn.log_intervention` | Log Intervention | label | about |
| `about.btn.manage_lines` | Manage Prod. Lines | label | about |
| `about.btn.pm_calendar` | PM Calendar | label | about |
| `about.btn.search_parts` | Search Parts | label | about |
| `about.btn.user_guide` | User Guide | label | about |
| `about.btn.vault_config` | Vault Config | label | about |
| `about.btn.view_board` | View Board | label | about |
| `about.btn.view_pos` | View POs | label | about |
| `about.btn.view_prs` | View PRs | label | about |
| `about.btn.work_orders` | Work Orders | label | about |
| `about.changelog.api` | REST API v1: Key-authenticated endpoints under the same permission model, for integration with whatever else runs on your floor. | label | about |
| `about.changelog.assets` | Asset Register with Physical Labels: Full OEM/warranty/lifecycle records, plus QR & DataMatrix label printing — offline payloads for Zebra or any sheet printer, no internet required. | label | about |
| `about.changelog.design` | Unified Design System: One token-driven look across every module — AA-contrast text, a single modal/button/badge family, true Light/Dark, and a WebGL background that can be switched off on older machines. | label | about |
| `about.changelog.inventory` | Inventory with a Real Ledger: Every movement — ticket consumption, work-order consumption, PO receipt — is recorded and traceable back to the job that caused it. | label | about |
| `about.changelog.notif` | Notifications & Audit Trail: Per-user alerts for approvals, low stock and overdue work, with an immutable audit log behind them. | label | about |
| `about.changelog.procurement` | Procurement, End to End: Requisition → cost approval → storekeeper fulfilment → goods receipt → budget reconciliation, with a nine-stage tracking stepper and full status history. | label | about |
| `about.changelog.rbac` | Granular RBAC: 22 permissions across 6 editable roles, enforced server-side on every page and API endpoint — including a Storekeeper who can fulfil orders but never approve their cost. | label | about |
| `about.changelog.reorder` | Event-Driven Auto-Reorder: Consumption that drops a part to its minimum raises a requisition automatically, through the same approval rules as a manual one. | label | about |
| `about.changelog.security` | Hardened by Default: CSRF on state changes, prepared statements throughout, session-fixation and brute-force defences, and Evil Maid locks binding takeover and closeout to the authenticated user. | label | about |
| `about.changelog.tickets` | Complete Ticket Lifecycle: Register → takeover → logged actions → closeout → searchable history, with time captured at every step so MTTA and MTTR are measured, not estimated. | label | about |
| `about.changelog.ux` | Device-Aware UX: Handheld mode activates automatically — tables collapse into cards, the calendar becomes an agenda, and touch targets grow to shop-glove size. | label | about |
| `about.changelog.wo` | Work Orders & Preventive Maintenance: Recurring schedules, reusable checklists, a calendar view, and overdue detection that surfaces what slipped. | label | about |
| `about.constructed_via` | Constructed via | label | about |
| `about.core_arch` | // CORE_SYSTEM_ARCHITECTURE // | label | about |
| `about.core_engine_by` | Core Engine & Architecture by | label | about |
| `about.docs` | Webapp Documentation | label | about |
| `about.feat.analytics` | Real-Time KPI & MTTR Analytics | label | about |
| `about.feat.analytics_body` | Aggregates raw intervention telemetry to calculate Mean Time To Repair (MTTR), track downtime, monitor technician workload, and visualize parts consumption over time. | label | about |
| `about.feat.assets` | Comprehensive Asset & BOM Register | label | about |
| `about.feat.assets_body` | Maintains the core CMMS entity structure via a self-referential hierarchy. Machines and sub-assemblies are linked directly to their spare parts through the Bill of Materials (BOM) routing. | label | about |
| `about.feat.docs` | Safety Document Management | label | about |
| `about.feat.docs_body` | Secure, dedicated storage for Safety SOPs, Technical Manuals, and Blueprints. Strictly enforces MIME-type validation and isolated document pathing to guarantee safety-critical documents are always accessible directly from the asset page. | label | about |
| `about.feat.inventory` | Spare Parts Inventory & Auto-Reorder | label | about |
| `about.feat.inventory_body` | Tracks full logistics data, stock levels, and compliance fields. Features a zero-touch auto-reorder system that triggers when stock falls below defined thresholds. | label | about |
| `about.feat.lines` | Production Lines & Shop Floor Hierarchies | label | about |
| `about.feat.lines_body` | Full workshop-to-line-to-equipment hierarchy management. Production lines are first-class citizens with machine counts, status tracking, and direct integration into work orders and equipment. | label | about |
| `about.feat.modular` | Segmented Modular Architecture | label | about |
| `about.feat.modular_body` | Complete reorganization into logical domain folders (_mgmt, _maint, _eam, _prod, _logi, _rpt etc.) for maintainability and future expansion. All references, includes, and navigation updated. | label | about |
| `about.feat.pm` | Scheduled Maintenance & PM Calendar | label | about |
| `about.feat.pm_body` | Proactive scheduling engine for Preventive Maintenance (PM). Automatically tracks overdue routines, dynamically adjusts to plant holidays, and visualizes workloads on an interactive calendar. | label | about |
| `about.feat.procurement` | End-to-End Procurement (PRs & POs) | label | about |
| `about.feat.procurement_body` | Coordinates the full acquisition chain. Internal Purchase Requests (PRs) escalate into formal Purchase Orders (POs) sent to vendors, maintaining an IATF-compliant audit log across 9 distinct PO statuses. | label | about |
| `about.feat.rbac` | Role-Based Access Control (RBAC) | label | about |
| `about.feat.rbac_body` | Enterprise-grade permission system driving absolute security. From granular module access to Evil Maid Protection preventing unauthorized session hijacking during ticket takeovers and closeouts. | label | about |
| `about.feat.tickets` | Full Ticket Lifecycle (Open to Closeout) | label | about |
| `about.feat.tickets_body` | Manages industrial faults from initial registry to technician assignment and final resolution. Supports direct entry for fast-tracked repairs. | label | about |
| `about.genesis` | The WCC Genesis Protocol | label | about |
| `about.hosted_by` | Hosted & Championed by the | label | about |
| `about.indykb_blurb` | Forged for the technicians sharing knowledge within our ranks, and freely available to anyone worldwide who needs to keep their production lines moving. | label | about |
| `about.license_body` | This software is licensed under the Apache License 2.0 combined with the Commons Clause v1.0. We built this to help technicians globally keep their lines running. You are free to use, modify, and distribute this software for your factory operations forever. However, you may not sell this software or offer it as a commercial hosted service. | label | about |
| `about.license_title` | Free & Open to the World (But Not for Profit) | title | about |
| `about.modules` | System Modules & Capabilities | label | about |
| `about.my_take` | my take | label | about |
| `about.powered_by` | Powered by | label | about |
| `about.release_intro` | The first complete release — every module below is built, wired end to end and tested against a live plant dataset. | label | about |
| `about.unified_by` | Unified by | label | about |
| `about.view_license` | View License | label | about |
| `about.welcome` | Welcome to the Workshop Control Center (WCC). This is not just another bloated piece of enterprise software designed in a boardroom—it is a high-performance, framework-free CMMS forged directly on the factory floor. Built by technicians and engineers, FOR technicians and engineers, WCC strips away corporate dead weight to deliver what actually matters: absolute execution speed. Designed to operate at the edge, it breaks down information gatekeeping, putting the tools to manage the complete ticket lifecycle, track vital inventory, and analyze real-time KPIs directly into your hands. | label | about |
| `about.whats_in` | What's in :version (:codename) | label | about |
| `admin.control_title` | Admin Control Panel | title | admin |
| `admin.data_admin` | Data Administration (Backup / Restore / Flush) | label | admin |
| `admin.drag_hint` | Drag tiles to rearrange, then save. | hint | admin |
| `admin.edit_layout` | Edit Layout | label | admin |
| `admin.enterprise` | Enterprise Manufacturing | label | admin |
| `admin.filter_parts` | Filter parts… | label | admin |
| `admin.panel_title` | Admin Panel | title | admin |
| `admin.reset_layout` | Reset to default | label | admin |
| `admin.save_layout` | Save Layout | button | admin |
| `admin.section.adhoc_wo` | Schedule Ad-Hoc Work Order | label | admin |
| `admin.section.documents` | Documents Management | label | admin |
| `admin.section.inv_health` | Inventory Health | label | admin |
| `admin.section.kpi_targets` | KPI & Performance Targets | label | admin |
| `admin.section.pm_checklists` | Manage PM Checklists | label | admin |
| `admin.section.pm_config` | Preventative Maintenance Configuration | label | admin |
| `admin.section.spare_part` | Register Spare Part | label | admin |
| `admin.section.workshops` | Production Workshops & Lines Configuration | label | admin |
| `admin.settings_hint` | Security, session timeouts & theme customization are managed separately. | hint | admin |
| `admin.settings_title` | App Settings | title | admin |
| `admin.system_settings_link` | System Settings → | label | admin |
| `admin.tile.add_part` | Add Inventory Part | label | admin |
| `admin.tile.add_part_desc` | Register New Spare Parts | label | admin |
| `admin.tile.adhoc_wo` | Ad-Hoc Work Order | label | admin |
| `admin.tile.adhoc_wo_desc` | Create a single Work Order | label | admin |
| `admin.tile.coming_soon` | Coming Soon | label | admin |
| `admin.tile.coming_soon_desc` | Future Expansion Tile | label | admin |
| `admin.tile.departments` | Department Management | label | admin |
| `admin.tile.departments_desc` | Budget Allocation & Tracking | label | admin |
| `admin.tile.documents` | Documents Management | label | admin |
| `admin.tile.documents_desc` | Safety SOPs & Manuals | label | admin |
| `admin.tile.equipment_vault` | Enclosed Setup Vault | label | admin |
| `admin.tile.equipment_vault_desc` | Admin Equipment Config | label | admin |
| `admin.tile.inventory_audit` | Inventory Audit Log | label | admin |
| `admin.tile.inventory_audit_desc` | Full Transaction History | label | admin |
| `admin.tile.inventory_health` | Inventory Health | label | admin |
| `admin.tile.inventory_health_desc` | Stock Warning Band & Lifecycle | label | admin |
| `admin.tile.kpi_targets` | KPI Targets | label | admin |
| `admin.tile.kpi_targets_desc` | Set MTBF, MTTA & MTTR | label | admin |
| `admin.tile.pm_checklists` | PM Checklists | label | admin |
| `admin.tile.pm_checklists_desc` | Task Checklists & Times | label | admin |
| `admin.tile.pm_configurator` | PM Configurator | label | admin |
| `admin.tile.pm_configurator_desc` | Preventative Maintenance Cycles | label | admin |
| `admin.tile.production_lines` | Production Lines | label | admin |
| `admin.tile.production_lines_desc` | Workshops & Line Config | label | admin |
| `admin.tile.purchase_orders` | PR / PO Management | label | admin |
| `admin.tile.purchase_orders_desc` | Enterprise Procurement Engine | label | admin |
| `admin.tile.tooling_vault` | Tooling Vault | label | admin |
| `admin.tile.tooling_vault_desc` | Dies, fixtures, gauges & allocation | label | admin |
| `admin.tile.users` | User Management | label | admin |
| `admin.tile.users_desc` | Role-Based Access Control & Accounts | label | admin |
| `admin.tile.vendors` | Vendor Management | label | admin |
| `admin.tile.vendors_desc` | Supplier Database & Contacts | label | admin |
| `analytics.diag_heading` | Advanced Analytics & KPI Diagnostics | title | analytics |
| `analytics.diag_title` | System Diagnostics & KPIs | title | analytics |
| `api.database_error` | Database error. | error | api |
| `api.invalid_json` | Invalid data | error | api |
| `app.name` | Workshop Control Center | title | login.php |
| `app.short` | WCC | nav_label | nav.php, login.php |
| `auth.access_denied` | Access denied | label | auth |
| `btn.cancel` | Cancel | button | shared |
| `btn.close` | Close | button | shared |
| `btn.login` | Login | button | login.php |
| `btn.save` | Save | button | shared |
| `cal.fri` | Fri | label | cal |
| `cal.mon` | Mon | label | cal |
| `cal.sat` | Sat | label | cal |
| `cal.sun` | Sun | label | cal |
| `cal.thu` | Thu | label | cal |
| `cal.tue` | Tue | label | cal |
| `cal.wed` | Wed | label | cal |
| `common.action_failed` | Action failed. | error | common |
| `common.actions` | Actions | label | common |
| `common.active` | Active | label | common |
| `common.add` | Add | label | common |
| `common.all` | All | label | common |
| `common.apply` | Apply | label | common |
| `common.back` | Back | label | common |
| `common.calculating` | Calculating… | label | common |
| `common.cancel_action` | Cancel | label | common |
| `common.cancelled` | Cancelled | label | common |
| `common.category` | Category | label | common |
| `common.clear` | Clear | label | common |
| `common.close_photo` | Close photo | label | common |
| `common.closed` | Closed | label | common |
| `common.column` | Column | label | common |
| `common.completed` | Completed | label | common |
| `common.confirm` | Confirm | label | common |
| `common.confirm_delete` | Delete this item? | label | common |
| `common.could_not_load` | Could not load. | label | common |
| `common.could_not_save` | Could not save. | label | common |
| `common.date` | Date | label | common |
| `common.delete` | Delete | label | common |
| `common.description` | Description | label | common |
| `common.details` | Details | label | common |
| `common.download` | Download | label | common |
| `common.edit` | Edit | label | common |
| `common.equipment` | Equipment | label | common |
| `common.error` | Error | error | common |
| `common.escalated` | Escalated | label | common |
| `common.high` | High | label | common |
| `common.hold` | Hold | label | common |
| `common.hub` | Hub | label | common |
| `common.inactive` | Inactive | label | common |
| `common.info` | Info | label | common |
| `common.just_now` | Just now… | label | common |
| `common.loading` | Loading… | label | common |
| `common.loading_ellipsis` | Loading… | label | common |
| `common.location` | Location | label | common |
| `common.low` | Low | label | common |
| `common.mins` | mins | label | common |
| `common.missed` | Missed | label | common |
| `common.na` | N/A | label | common |
| `common.name` | Name | label | common |
| `common.network_error` | Network error. | error | common |
| `common.next` | Next | label | common |
| `common.no` | No | label | common |
| `common.no_data` | No data available. | label | common |
| `common.no_results` | No results found. | label | common |
| `common.none` | None | label | common |
| `common.normal` | Normal | label | common |
| `common.notes` | Notes | label | common |
| `common.offline` | OFFLINE | label | common |
| `common.ok` | OK | label | common |
| `common.online` | ONLINE | label | common |
| `common.open` | Open | label | common |
| `common.optional` | Optional | label | common |
| `common.overdue` | OVERDUE | label | common |
| `common.pending` | Pending | label | common |
| `common.please_wait` | Please wait… | label | common |
| `common.prev` | Prev | label | common |
| `common.print` | Print | label | common |
| `common.priority` | Priority | label | common |
| `common.proceed` | Proceed | label | common |
| `common.quantity` | Quantity | label | common |
| `common.refresh` | Refresh | label | common |
| `common.required` | Required | label | common |
| `common.reset` | Reset | label | common |
| `common.saved` | Saved | success | common |
| `common.saving` | Saving… | label | common |
| `common.search` | Search | label | common |
| `common.security_check` | Security check failed — reload the page and retry. | label | common |
| `common.select` | Select | label | common |
| `common.send` | Send | label | common |
| `common.session_expired` | Session expired. Please log in again. | label | common |
| `common.status` | Status | label | common |
| `common.success` | Success | success | common |
| `common.tbd` | TBD | label | common |
| `common.time` | Time | label | common |
| `common.time_error` | Time Error | error | common |
| `common.today` | TODAY | label | common |
| `common.total` | Total | label | common |
| `common.type` | Type | label | common |
| `common.unassigned` | Unassigned | label | common |
| `common.unauthorized` | Unauthorized | label | common |
| `common.unknown` | Unknown | label | common |
| `common.upload` | Upload | label | common |
| `common.validation_error` | Validation Error | error | common |
| `common.view` | View | label | common |
| `common.warning` | Warning | label | common |
| `common.yes` | Yes | label | common |
| `dept.create_vault` | Create Budget Vault | label | dept |
| `dept.history` | Transaction History | label | dept |
| `dept.transaction` | Budget Transaction | label | dept |
| `dept.vault_title` | Budget Vault Management | title | dept |
| `docs.contents` | Documentation contents | label | docs |
| `docs.filter` | Filter chapters | label | docs |
| `docs.title` | Documentation | title | docs |
| `equip.code_error` | code error | label | equip |
| `equip.datamatrix` | DataMatrix | label | equip |
| `equip.label_error` | Label error: :msg | label | equip |
| `equip.label_save_failed` | Could not save label settings. | error | equip |
| `equip.label_settings_saved` | Label settings saved. | label | equip |
| `equip.labels_print` | Equipment Labels — Print | label | equip |
| `equip.labels_title` | Label and Printer Setup | title | equip |
| `equip.ledger_title` | Equipment Ledger | title | equip |
| `equip.no_equipment_selected` | No equipment selected. | label | equip |
| `equip.no_ids` | No equipment ids provided. Open this page from the Equipment Vault via Print Labels. | label | equip |
| `equip.no_printer_ip` | No printer IP configured — open Label & Printer Setup first. | label | equip |
| `equip.no_test_label` | No equipment available for a test label. | label | equip |
| `equip.no_valid_settings` | No valid settings supplied. | label | equip |
| `equip.one_label_page` | One label per page (:w×:h mm). | label | equip |
| `equip.print_failed` | Print failed — could not reach the server. | error | equip |
| `equip.print_hint` | Pick your printer in the dialog that opens. | button | equip |
| `equip.print_n_labels` | Print :n label | button | equip |
| `equip.print_n_labels_plural` | Print :n labels | button | equip |
| `equip.printer_unreachable` | Printer unreachable at :ip::port — check Label & Printer Setup. (:err) | label | equip |
| `equip.qr_code` | QR Code | label | equip |
| `equip.selected_not_found` | Selected equipment not found. | label | equip |
| `equip.sent_labels` | Sent :n label(s) to the Zebra at :ip. | label | equip |
| `equip.sheet_layout` | Sheet layout: :cols × :rows = :per_page labels/page. | label | equip |
| `equip.symbology` | Symbology: | label | equip |
| `equip.test_print_failed` | Test print failed — could not reach the server. | error | equip |
| `equip.unknown_action` | Unknown action. | label | equip |
| `equip.upload_failed` | Upload failed | error | equip |
| `equip.upload_failed_detail` | Upload failed: :msg | error | equip |
| `equip.use_browser_print` | Current method prints via the browser dialog — use the print window, or switch to "Zebra network" in Label & Printer Setup. | label | equip |
| `equip.vault_title` | Equipment Vault | title | equip |
| `health.available_now` | :pct% AVAILABLE NOW | label | health |
| `health.factory_title` | FACTORY HEALTH | title | health |
| `health.machines_operational` | :up of :total machines are currently operational | label | health |
| `health.mtbf_mtd` | MTBF MTD: | label | health |
| `health.repair_labour` | Repair Labour: | label | health |
| `health.uptime_aria` | Factory uptime :pct percent | label | health |
| `inv.audit_title` | Enterprise Inventory Audit Log | title | inv |
| `inv.critical_spare` | Critical spare | label | inv |
| `inv.low_stock` | Low stock: :name | label | inv |
| `inv.reorder_failed` | Could not run the reorder check. | error | inv |
| `inv.stock_status` | Stock status | label | inv |
| `inv.stock_status_key` | Stock status key | label | inv |
| `inv.title` | Inventory Parts | title | inv |
| `lang.fallback_hint` | English until translated | help | my_profile.php |
| `lang.help` | UI language for navigation and screens that support translation. Other pages may still show English until they are migrated. Change is saved to your profile. | help | my_profile.php |
| `lang.label` | Language | label | my_profile.php |
| `lang.save` | Save language | button | my_profile.php |
| `lang.saved` | Language updated. | success | my_profile.php |
| `lang.section` | Language / भाषा | title | my_profile.php |
| `lines.count_many` | :n lines | label | lines |
| `lines.count_one` | :n line | label | lines |
| `lines.directory` | Production Lines Directory | title | lines |
| `lines.intro` | This is a read-only directory of all configured Workshops and Production Lines. Click workshop headers to collapse/expand lines. Click any line row to see its assigned machines. | hint | lines |
| `lines.location` | Location: | label | lines |
| `lines.machines_on_line` | Machines on this line (:n) | label | lines |
| `lines.no_equipment` | No equipment assigned to this line yet. | label | lines |
| `lines.no_lines` | No production lines currently allocated to this workshop. | label | lines |
| `lines.not_specified` | Not Specified | label | lines |
| `lines.title` | Production Lines | title | lines |
| `lines.units` | Units | label | lines |
| `login.hide_password` | Hide password | label | login.php |
| `login.inactive` | This account is inactive or pending. Contact an administrator. | error | login.php |
| `login.invalid` | Invalid username or password. | error | login.php |
| `login.password` | Password | label | login.php |
| `login.password_change` | Change Password | error | login |
| `login.session_expired` | Your session has expired. Please log in again. | warning | login.php?expired |
| `login.show_password` | Show password | label | login.php |
| `login.subtitle` | Workshop Control Center | title | login.php |
| `login.switch_theme` | Switch light/dark theme | label | login.php |
| `login.throttled` | Too many failed sign-in attempts. Please try again in :mins minute(s). | error | login.php |
| `login.title` | Login | title | login.php |
| `login.unavailable` | The system is temporarily unavailable. Please try again. | error | login.php |
| `login.username` | Username or Badge Number | label | login.php |
| `modal.are_you_sure` | Are you sure? | label | modal |
| `modal.ok_countdown` | OK (:n s) | label | modal |
| `nav.about` | About WCC | label | nav.php |
| `nav.admin_panel` | Admin Panel | nav_label | nav.php |
| `nav.analytics` | Analytics | nav_label | nav.php |
| `nav.equipment` | Equipment | nav_label | nav.php |
| `nav.event_history` | Event History | nav_label | nav.php |
| `nav.inventory` | Inventory | nav_label | nav.php |
| `nav.logout` | Logout | nav_label | nav.php |
| `nav.main` | Main navigation | label | nav.php |
| `nav.my_profile` | My Profile | nav_label | nav.php |
| `nav.notifications` | Notifications | nav_label | nav.php |
| `nav.open_menu` | Open navigation menu | label | nav.php |
| `nav.pm_calendar` | PM Calendar | nav_label | nav.php |
| `nav.prod_lines` | Prod. Lines | nav_label | nav.php |
| `nav.purchase_requests` | Purchase Requests | nav_label | nav.php |
| `nav.role` | ROLE: | label | nav.php |
| `nav.section.admin` | Administration | nav_label | nav.php |
| `nav.section.assets` | Assets | nav_label | nav.php |
| `nav.section.insights` | Insights | nav_label | nav.php |
| `nav.section.operations` | Operations | nav_label | nav.php |
| `nav.section.people` | People | nav_label | nav.php |
| `nav.section.records` | Records | nav_label | nav.php |
| `nav.settings` | Settings | nav_label | nav.php |
| `nav.switch_theme` | Switch theme | label | nav.php |
| `nav.theme_dark` | Theme: Dark | nav_label | nav.php |
| `nav.theme_light` | Theme: Light | nav_label | nav.php |
| `nav.tickets` | Tickets | nav_label | nav.php |
| `nav.toggle_sidebar` | Toggle sidebar | label | nav.php |
| `nav.tooling` | Tooling | nav_label | nav.php |
| `nav.user` | USER: | label | nav.php |
| `nav.users` | Users | nav_label | nav.php |
| `nav.vendors` | Vendors | nav_label | nav.php |
| `nav.work_orders` | Work Orders | nav_label | nav.php |
| `notif.action_failed` | Action failed. | error | nav.php JS |
| `notif.all_cleared` | All notifications cleared. | label | notif |
| `notif.all_marked_read` | All marked read. | label | notif |
| `notif.close` | Close | button | nav.php notif modal |
| `notif.could_not_update` | Could not update notifications. | label | notif |
| `notif.delete_all` | Delete all | button | nav.php notif modal |
| `notif.empty` | You're all caught up — no notifications. | help | nav.php notif modal |
| `notif.mark_all_read` | Mark all read | button | nav.php notif modal |
| `notif.title` | Notifications | title | nav.php notif modal |
| `notif.unknown_action` | Unknown action. | label | notif |
| `notif.unread_suffix` | (:n unread) | status | nav.php |
| `notif.update_failed` | Could not update notifications. | error | nav.php JS |
| `pm.annual_rate` | Annual Completion Rate | label | pm |
| `pm.annual_rate_title` | Percentage of all scheduled events completed this year | title | pm |
| `pm.day_title` | Scheduled Maintenance for :date | title | pm |
| `pm.list_view` | List View | label | pm |
| `pm.month_rate` | :month Completion Rate | label | pm |
| `pm.month_rate_title` | Percentage of scheduled events completed this month | title | pm |
| `pm.mtbf_mtd` | MTBF MTD: | label | pm |
| `pm.no_wo_day` | No Work Orders scheduled for this day. | label | pm |
| `pm.open_takeover` | Open / Takeover | label | pm |
| `pm.overdue_1_2` | Overdue (1-2 days) | label | pm |
| `pm.overdue_3plus` | Overdue (3+ days) | label | pm |
| `pm.repair_labour` | Repair Labour: | label | pm |
| `pm.scheduled_today` | Scheduled Today | label | pm |
| `pm.title` | PM Calendar | title | pm |
| `pm.upcoming_gt7` | Upcoming (>7 days) | label | pm |
| `pm.upcoming_lt7` | Upcoming (<7 days) | label | pm |
| `po.title` | Enterprise Purchase Order Management | title | po |
| `po.workflow_saved` | Approval workflow settings saved. | success | po |
| `pr.auto_approved` | Auto-Approved | label | pr |
| `pr.submitted` | Submitted | button | pr |
| `pr.title` | Enterprise Purchase Requests | title | pr |
| `profile.active_wos` | Active Work Orders | label | profile |
| `profile.avg_wrench` | Avg Wrench Time | label | profile |
| `profile.badge` | Badge: :n | label | profile |
| `profile.change_password` | Change Password | label | profile |
| `profile.confirm_change_pw` | Are you sure you want to change your password? | label | profile |
| `profile.confirm_password` | Confirm New Password | label | profile |
| `profile.could_not_add_skill` | Could not add skill. | label | profile |
| `profile.could_not_remove_skill` | Could not remove skill. | label | profile |
| `profile.current_password` | Current Password | label | profile |
| `profile.department` | Department | label | profile |
| `profile.details_updated` | Profile details updated. | label | profile |
| `profile.drag_timeout` | Drag to set your personal timeout | label | profile |
| `profile.edit` | Edit Profile | label | profile |
| `profile.email` | Email | label | profile |
| `profile.expires` | Expires | label | profile |
| `profile.full_name` | Full Name | label | profile |
| `profile.gamified` | Gamified Proficiencies | label | profile |
| `profile.global_display` | Global (:mins min) | label | profile |
| `profile.h_to_next` | :h to :tier | label | profile |
| `profile.how_earned` | How are these earned? | label | profile |
| `profile.interventions` | Interventions | label | profile |
| `profile.invalid_email` | Invalid email address. | error | profile |
| `profile.logged_action` | Logged action on :id: | label | profile |
| `profile.member_since` | Member Since | label | profile |
| `profile.min_global` | :mins min (global) | label | profile |
| `profile.min_n` | :n min | label | profile |
| `profile.min_personal` | :mins min (personal) | label | profile |
| `profile.new_password` | New Password | label | profile |
| `profile.new_skill` | New skill name | label | profile |
| `profile.no_active_wos` | No active work orders assigned to you! | label | profile |
| `profile.no_mapped_prof` | Your logged hours are on equipment categories that are not mapped to a proficiency yet. An administrator can map them in Users → Skill Configurator. | label | profile |
| `profile.no_override` | No personal override set | label | profile |
| `profile.no_prof` | No machine proficiencies logged yet. Wrench time unlocks automatic badges! | label | profile |
| `profile.no_recent` | No recent activity found. | label | profile |
| `profile.no_skills` | No skills added yet. | label | profile |
| `profile.password_incorrect` | Current password is incorrect. | label | profile |
| `profile.password_mismatch` | New passwords do not match. | label | profile |
| `profile.password_short` | New password must be at least 6 characters. | label | profile |
| `profile.password_updated` | Password updated successfully. | label | profile |
| `profile.performance` | My Performance Dashboard | label | profile |
| `profile.personal_active` | Personal preference active | label | profile |
| `profile.phone` | Phone | label | profile |
| `profile.preset_15` | 15 min | label | profile |
| `profile.preset_1h` | 1 hour | label | profile |
| `profile.preset_2h` | 2 hours | label | profile |
| `profile.preset_30` | 30 min | label | profile |
| `profile.preset_4h` | 4 hours | label | profile |
| `profile.preset_8h` | 8 hours | label | profile |
| `profile.recent_activity` | Recent Activity Log | label | profile |
| `profile.remove_skill` | Remove skill | label | profile |
| `profile.remove_skill_btn` | Remove Skill | label | profile |
| `profile.remove_skill_confirm` | Remove this skill? | label | profile |
| `profile.ribbon_off` | Ribbon background off. | label | profile |
| `profile.ribbon_on` | Ribbon background on. | label | profile |
| `profile.save` | Save Profile | label | profile |
| `profile.save_timeout` | Save My Timeout | button | profile |
| `profile.session_timeout` | Personal Session Timeout | label | profile |
| `profile.session_timeout_row` | Session Timeout | label | profile |
| `profile.silk_ribbon` | Silk ribbon background | label | profile |
| `profile.skill_add_error` | Error adding skill. | error | profile |
| `profile.skill_added` | Skill added. | success | profile |
| `profile.skill_expiry` | Certification expiry date (optional) | label | profile |
| `profile.skill_expiry_help` | Leave the date blank if the certification does not expire. Expiring within :days days is flagged amber; past the date is flagged red. | label | profile |
| `profile.skill_remove_error` | Error removing skill. | error | profile |
| `profile.skill_removed` | Skill removed. | label | profile |
| `profile.skills` | Skills & Certifications | label | profile |
| `profile.tickets_closed` | Tickets Closed Out | label | profile |
| `profile.tickets_reported` | Tickets Reported | label | profile |
| `profile.timeout_global` | Using global default session timeout. | label | profile |
| `profile.timeout_help` | Override the global default (:mins min) with your own preference. Set to longer if you are at a dedicated workstation, shorter on shared equipment. | label | profile |
| `profile.timeout_label` | Personal session timeout in minutes | label | profile |
| `profile.timeout_set` | Session timeout set to :mins minutes. | label | profile |
| `profile.title` | My Profile & Preferences | title | profile |
| `profile.top_tier` | Top tier reached | label | profile |
| `profile.update_password` | Update Password | label | profile |
| `profile.use_global` | Use Global Default | label | profile |
| `profile.user_id` | User ID | label | profile |
| `profile.using_global_short` | Using global default | label | profile |
| `profile.visual_help` | The animated silk-ribbon background looks great but uses the GPU. Turn it off on older or low-power PCs for a snappier feel. Saved on this device only. | label | profile |
| `profile.visual_prefs` | Visual Preferences | label | profile |
| `pw.action_required` | Action Required | label | pw |
| `pw.cannot_default` | You cannot use the default password. | label | pw |
| `pw.db_error` | Database error. Please try again. | error | pw |
| `pw.mismatch` | Passwords do not match. | label | pw |
| `pw.must_change` | You must change your password before continuing. | label | pw |
| `pw.too_short` | Password must be at least 6 characters long. | label | pw |
| `search.aria_equipment` | Search equipment | label | search |
| `search.aria_tooling` | Search tooling | label | search |
| `search.aria_wo` | Search work orders | label | search |
| `search.drag_to_column` | Drag to column | label | search |
| `search.lock_token` | Lock Token | label | search |
| `search.n_of_total` | :visible of :total | status | shared #searchMatchCount |
| `search.n_records` | :total records | status | shared #searchMatchCount |
| `search.placeholder_audit` | Search transactions... (Drag to column) | label | search |
| `search.placeholder_equipment` | Search by name, uuid, brand... (Drag to column) | label | search |
| `search.placeholder_generic` | Search… (Drag to column) | label | search |
| `search.placeholder_history` | Search Event History... (Drag to column) | label | search |
| `search.placeholder_inventory` | Search inventory... (Drag to column) | label | search |
| `search.placeholder_linked_parts` | Search linked parts... | label | search |
| `search.placeholder_parts` | Search parts... | label | search |
| `search.placeholder_po` | Search purchase orders... (Drag to column) | label | search |
| `search.placeholder_pr` | Search purchase requests... (Drag to column) | label | search |
| `search.placeholder_tooling` | Search by name, code, tag... (Drag to column) | label | search |
| `search.placeholder_users` | Search users... (Drag to column) | label | search |
| `search.placeholder_vault` | Search vault... (Drag to column) | label | search |
| `search.placeholder_vendors` | Search vendors... (Drag to column. Dbl-Click Reset) | label | search |
| `search.placeholder_wo` | Search work orders... (Drag to column) | label | search |
| `search.type_lock` | Type & click 📌 to Lock | label | search |
| `settings.console_title` | System Administration Console | title | settings |
| `settings.msg.holidays_updated` | Plant holidays saved. | label | settings |
| `settings.msg.lockout_updated` | Session lockout timer updated. | label | settings |
| `settings.ops_calendar` | Operational Calendar | label | settings |
| `settings.plant_holidays` | Plant Holidays (JSON Array of YYYY-MM-DD strings) | label | settings |
| `settings.plant_holidays_help` | These dates are skipped entirely when calculating operational downtime (MDT) and MTTA. | hint | settings |
| `settings.return_admin` | Return to Admin Panel | label | settings |
| `settings.save_changes` | Save Changes | button | settings |
| `settings.save_holidays` | Save Holidays | button | settings |
| `settings.security` | Security Settings | label | settings |
| `settings.session_lockout` | Session Lockout Timer (Minutes): | label | settings |
| `settings.session_lockout_help` | Determines how much idle time is allowed before the system forces a re-login. | hint | settings |
| `stats.apply_filter` | Apply Filter | label | stats |
| `stats.dashboard_title` | KPI & Performance Dashboard | title | stats |
| `stats.from` | From: | label | stats |
| `stats.no_data_month` | No data for :month | label | stats |
| `stats.parts_csv` | Parts CSV | label | stats |
| `stats.print_pdf` | Print / PDF | button | stats |
| `stats.tickets_csv` | Tickets CSV | label | stats |
| `stats.till` | Till: | label | stats |
| `stats.title` | Analytics | title | stats |
| `th.actions` | Actions | label | th |
| `th.announced_by` | Announced By | label | th |
| `th.asset_name` | Asset Name | label | th |
| `th.assigned_tech` | Assigned Tech | label | th |
| `th.assigned_to` | Assigned to | label | th |
| `th.assigned_to_cap` | Assigned To | label | th |
| `th.cal_due` | Cal. due | label | th |
| `th.category` | Category | label | th |
| `th.closed` | Closed | label | th |
| `th.code` | Code | label | th |
| `th.created` | Created | label | th |
| `th.criticality` | Criticality | label | th |
| `th.department` | Department | label | th |
| `th.due_date` | Due date | label | th |
| `th.email` | Email | label | th |
| `th.equipment` | Equipment | label | th |
| `th.equipment_details` | Equipment Details | label | th |
| `th.equipment_location` | Equipment / Location | label | th |
| `th.invoked_pic` | Invoked PIC | label | th |
| `th.line_name` | Line Name | label | th |
| `th.machine_count` | Machine Count | label | th |
| `th.ongoing_time` | Ongoing Time | label | th |
| `th.part_name` | Part Name | label | th |
| `th.phone` | Phone | label | th |
| `th.plant_line` | Plant / Line | label | th |
| `th.priority` | Priority | label | th |
| `th.products_built` | Products Built | label | th |
| `th.role` | Role | label | th |
| `th.scheduled_date` | Scheduled Date | label | th |
| `th.status` | Status | label | th |
| `th.stock` | Stock | label | th |
| `th.target_equipment` | Target Equipment | label | th |
| `th.ticket_id` | Ticket ID | label | th |
| `th.title` | Title | title | th |
| `th.title_instructions` | Title / Instructions | title | th |
| `th.tooling_name` | Tooling Name | label | th |
| `th.username` | Username | label | th |
| `th.uuid` | UUID | label | th |
| `th.vendor` | Vendor | label | th |
| `th.wo_id` | WO ID | label | th |
| `th.wo_num` | WO # | label | th |
| `ticket.action_logged` | Action logged successfully! | label | ticket |
| `ticket.action_placeholder` | e.g. Reset tripped breaker, cleared jam | label | ticket |
| `ticket.action_taken` | Action taken | label | ticket |
| `ticket.active_title` | Active Tickets | title | ticket |
| `ticket.all_lines` | -- All Lines -- | label | ticket |
| `ticket.all_workshops` | -- All Workshops -- | label | ticket |
| `ticket.already_closed` | Ticket is already closed. | label | ticket |
| `ticket.announced_by` | Announced by | label | ticket |
| `ticket.archiving` | Archiving… ⏳ | label | ticket |
| `ticket.cannot_comment_closed` | Cannot add comments to a closed ticket. | label | ticket |
| `ticket.closed_ok` | Closed successfully! | toast | api/submit_closeout.php |
| `ticket.closed_success` | Ticket Closed and Sent to History! | success | ticket |
| `ticket.closeout_review` | Final Review & Close | title | closeout.php |
| `ticket.closeout_title` | Close Out Ticket | title | ticket |
| `ticket.comment_added` | Comment added successfully! | success | ticket |
| `ticket.comment_error` | Error submitting comment | error | ticket |
| `ticket.comment_failed` | Failed to add comment | error | ticket |
| `ticket.comment_placeholder` | Type a comment... | placeholder | active_tickets.php |
| `ticket.comment_required` | Ticket ID and Comment Text are required. | label | ticket |
| `ticket.comments_archive` | Live Comments Archive: | label | ticket |
| `ticket.confirm_archive` | Confirm & Archive Ticket | label | ticket |
| `ticket.could_not_close` | Could not close out the ticket. | label | ticket |
| `ticket.could_not_create` | Could not create ticket. Please try again. | label | ticket |
| `ticket.could_not_resolve` | Could not record the quick resolution. | label | ticket |
| `ticket.could_not_takeover` | Could not take over the ticket. Please try again. | label | ticket |
| `ticket.end_time` | End Time: | label | ticket |
| `ticket.equip_name_label` | Equipment Name: | label | ticket |
| `ticket.equip_search` | Start typing machine name or UUID... | label | ticket |
| `ticket.equip_short` | Equip: | label | ticket |
| `ticket.equipment_label` | Equipment: | label | ticket |
| `ticket.escalate` | ⚠️ Save & Escalate | label | ticket |
| `ticket.escalate_need_name` | Please enter the name of the person you are escalating this to! | label | ticket |
| `ticket.escalate_to` | Escalate To (Name): | label | ticket |
| `ticket.escalated_to` | Escalated to | label | ticket |
| `ticket.escalated_to_label` | Escalated to: :name | label | ticket |
| `ticket.escalating` | Escalating… ⏳ | label | ticket |
| `ticket.event_class.failure` | Failure / Breakdown | error | ticket |
| `ticket.event_class.induced` | Induced / Secondary damage | label | ticket |
| `ticket.event_class.inspection` | Inspection / PM check | label | ticket |
| `ticket.event_class.no_fault` | No Fault Found | label | ticket |
| `ticket.event_class.request` | Request / Facilities | label | ticket |
| `ticket.event_class.setup` | Setup / Changeover | label | ticket |
| `ticket.event_type` | Event Type: | label | ticket |
| `ticket.event_type_hint` | Only 'failure'-type events count toward MTBF; the rest are still logged and timed. | label | ticket |
| `ticket.fault.electrical` | Electrical | label | ticket |
| `ticket.fault.mechanical` | Mechanical | label | ticket |
| `ticket.fault.operator` | Operator Error | label | ticket |
| `ticket.fault.other` | Other | label | ticket |
| `ticket.fault.pneumatic` | Pneumatic/Hydraulic | label | ticket |
| `ticket.fault.software` | Software/Controls | label | ticket |
| `ticket.fault.tooling` | Tooling/Fixture | label | ticket |
| `ticket.fault_desc` | Fault description | label | ticket |
| `ticket.fault_type` | Fault Type: | label | ticket |
| `ticket.fill_mandatory` | Please fill all mandatory fields (Fault Description, PIC)! | label | ticket |
| `ticket.filter_line` | Production Line Filter: | label | ticket |
| `ticket.filter_workshop` | Workshop / Plant Filter: | label | ticket |
| `ticket.finish_job` | ✅ Finish Job | label | ticket |
| `ticket.fix_desc_label` | Fix Description (Short): | label | ticket |
| `ticket.form_date` | Form Date: :date | label | ticket |
| `ticket.history_title` | Event History Archive | title | ticket |
| `ticket.hold.end_of_shift` | End of Shift / Handover | label | ticket |
| `ticket.hold.other` | Other | label | ticket |
| `ticket.hold.waiting_parts` | Waiting for Parts | label | ticket |
| `ticket.hold.waiting_production` | Waiting for Production Clearance | label | ticket |
| `ticket.hold.waiting_vendor` | Waiting for External Vendor | label | ticket |
| `ticket.hold_confirm` | Confirm HOLD Status | label | ticket |
| `ticket.hold_details` | Provide details... | placeholder | active_tickets.php |
| `ticket.hold_explain` | Please provide an explanation. | label | ticket |
| `ticket.hold_explanation` | Explanation / Comments: | label | ticket |
| `ticket.hold_modal_title` | Put Ticket on Hold | title | ticket |
| `ticket.hold_reason` | Reason | label | ticket |
| `ticket.hub_active_desc` | View and manage OPEN and PENDING tasks | title | ticket |
| `ticket.hub_history_desc` | Browse the archive of CLOSED interventions | title | ticket |
| `ticket.hub_instant_desc` | Fast-track and close minor fixes immediately without a formal ticket | title | ticket |
| `ticket.hub_instant_title` | Instant Resolve | title | ticket |
| `ticket.hub_overdue_many` | :count work orders past due and not yet started. | title | ticket |
| `ticket.hub_overdue_one` | :count work order past due and not yet started. | title | ticket |
| `ticket.hub_overdue_title` | Overdue Work Alert | title | ticket |
| `ticket.hub_register_desc` | Log a new machine breakdown or intervention | title | ticket |
| `ticket.hub_register_title` | Register New Event | title | ticket |
| `ticket.hub_subtitle` | Select an Action | title | ticket |
| `ticket.hub_title` | Tickets Hub | title | ticket |
| `ticket.id_label` | Ticket ID: | label | ticket |
| `ticket.idle_badge` | IDLE > 45m | label | ticket |
| `ticket.instant_logged` | Instant Fix logged successfully! | label | ticket |
| `ticket.intervention_timeline` | Intervention Timeline: | label | ticket |
| `ticket.invalid_data` | Invalid data | error | ticket |
| `ticket.invalid_or_missing_id` | Invalid data or missing ticket ID | error | ticket |
| `ticket.issue_label` | Issue: | label | ticket |
| `ticket.live_comments` | Live Comments Feed: | label | ticket |
| `ticket.log_close_instantly` | Log & Close Instantly | label | ticket |
| `ticket.missing_fields` | Missing required fields. | label | ticket |
| `ticket.new_ticket` | New Ticket | button | active_tickets.php |
| `ticket.no_active` | No active tickets right now! | label | ticket |
| `ticket.no_comments` | No comments recorded. | label | ticket |
| `ticket.no_comments_yet` | No comments yet. | label | ticket |
| `ticket.no_equipment` | No equipment found. | label | ticket |
| `ticket.no_escalation` | -- No Escalation -- | label | ticket |
| `ticket.not_found` | Ticket not found. | label | ticket |
| `ticket.not_found_die` | Ticket not found! | label | ticket |
| `ticket.on_hold` | ON HOLD | label | ticket |
| `ticket.open_pm_calendar` | Open PM Calendar | label | ticket |
| `ticket.original_fault` | Original Fault Description: | label | ticket |
| `ticket.original_issue` | Original Issue: | label | ticket |
| `ticket.overtook_by` | Overtook by: | label | ticket |
| `ticket.parts_search` | Search by part name or code... (Leave blank if none) | label | ticket |
| `ticket.parts_used` | Parts used | label | ticket |
| `ticket.pending_wos` | Pending Work Orders | label | ticket |
| `ticket.pic` | PIC | label | ticket |
| `ticket.plant_line_label` | Plant / Line: | label | ticket |
| `ticket.priority_critical` | Critical (Line Down) | label | ticket |
| `ticket.priority_high` | High (Major Defect) | label | ticket |
| `ticket.priority_low` | Low (Cosmetic/Minor) | label | ticket |
| `ticket.priority_normal` | Normal (Standard) | label | ticket |
| `ticket.processing` | Processing… ⏳ | label | ticket |
| `ticket.put_on_hold` | Ticket put on hold. | label | ticket |
| `ticket.put_on_hold_btn` | Put on Hold | label | ticket |
| `ticket.quick_resolve_title` | Quick Resolve | title | ticket |
| `ticket.register_title` | Log Intervention | title | ticket |
| `ticket.registered` | Event Registered! Ticket ID: :id | label | ticket |
| `ticket.repeat_offender` | Repeat Offender | label | ticket |
| `ticket.repeat_warning` | WARNING: This machine has had :count faults in the last 48 hours. Look for a root cause! | label | ticket |
| `ticket.report_date` | Report date | label | ticket |
| `ticket.report_time` | Report time | label | ticket |
| `ticket.resume_job` | Resume Job | label | ticket |
| `ticket.review_close` | Review/Close | label | ticket |
| `ticket.root_cause` | Root cause | label | ticket |
| `ticket.saving` | Saving… ⏳ | label | ticket |
| `ticket.search_equip_label` | Search Equipment (By Name or UUID): | label | ticket |
| `ticket.select_equipment` | Please search and select an Equipment! | label | ticket |
| `ticket.sign_off` | Please sign off! | label | ticket |
| `ticket.start_time` | Start Time: | label | ticket |
| `ticket.submit` | Submit Ticket | button | ticket |
| `ticket.submit_success` | Ticket Submitted Successfully! | success | ticket |
| `ticket.submitting` | Submitting… | button | ticket |
| `ticket.supervisor` | Supervisor | label | ticket |
| `ticket.takeover_btn` | Takeover | label | ticket |
| `ticket.takeover_title` | Take Over Ticket | title | ticket |
| `ticket.technician_name` | Technician Name: | label | ticket |
| `ticket.unauthorized_expired` | Unauthorized. Session expired. | label | ticket |
| `ticket.unauthorized_session` | Unauthorized: Invalid Session | label | ticket |
| `ticket.unknown_machine` | Unknown Machine | label | ticket |
| `ticket.what_done` | What exactly did you do to fix it? | label | ticket |
| `ticket.why_broke` | Why did it break? | label | ticket |
| `ticket.workshop_line_label` | Workshop / Line: | label | ticket |
| `tooling.registry_title` | Tooling Registry | title | tooling |
| `tooling.vault_title` | Tooling Vault | title | tooling |
| `users.list_title` | User Directory | title | users |
| `users.manage_title` | User Management (RBAC) | title | users.php |
| `users.title` | Users | title | users |
| `vendors.config_title` | Vendor Vault Configuration | title | vendors |
| `vendors.mgmt_title` | Vendors Management | title | vendors |
| `vendors.register` | Register Vendor | label | vendors |
| `vendors.title` | Vendors | title | vendors |
| `vendors.vault_title` | Vendor Vault | title | vendors |
| `wo.action_notes` | Describe what was done... | label | wo |
| `wo.action_taken` | Action Taken / Technician Notes: | label | wo |
| `wo.already_closed` | This Work Order is already closed and locked. | label | wo |
| `wo.assigned` | Work order assigned to you: :title (due :date) | label | wo |
| `wo.below_minimum` | below minimum | label | wo |
| `wo.calendar` | Calendar | label | wo |
| `wo.checklist_audit` | PM Checklist Audit: | label | wo |
| `wo.complete_checklist` | Complete all checklist items by pressing and holding them before marking the work order as Completed. | label | wo |
| `wo.completed` | Work order completed: :title (#:id) | label | wo |
| `wo.decrease` | Decrease | label | wo |
| `wo.deduct_1x` | (Deduct 1x) | label | wo |
| `wo.final_status` | Final Status: | label | wo |
| `wo.in_stock_line` | In stock: :stock → :after after | label | wo |
| `wo.increase` | Increase | label | wo |
| `wo.instructions` | Work Order Instructions: | label | wo |
| `wo.instructions_label` | Instructions: | label | wo |
| `wo.locked_status` | Locked: :status | label | wo |
| `wo.log_close` | Log & Close Work Order | label | wo |
| `wo.mins` | Mins | label | wo |
| `wo.no_instructions` | No instructions provided. | label | wo |
| `wo.no_pending` | No active work orders pending. | label | wo |
| `wo.not_found` | Work Order not found. | label | wo |
| `wo.part_search` | Start typing part name or code... | label | wo |
| `wo.parts_consumed` | Parts Consumed | label | wo |
| `wo.parts_consumed_hint` | (search a part, then set the quantity used) | hint | wo |
| `wo.photo_alt` | Checklist task photo | label | wo |
| `wo.pm_calendar` | PM Calendar | label | wo |
| `wo.pm_checklist_expected` | PM Checklist (Expected: :mins mins) | label | wo |
| `wo.qc_alert` | Quality Control Alert | label | wo |
| `wo.qc_too_fast` | It is physically impossible to complete this checklist so fast. You finished in :actual minutes, but standard operating procedure requires at least :expected minutes. | label | wo |
| `wo.qty_used` | Quantity used | label | wo |
| `wo.remove_part` | Remove part | label | wo |
| `wo.required_parts` | Required Parts: | label | wo |
| `wo.required_parts_consume` | Required Parts (Check to Consume from Inventory): | label | wo |
| `wo.start_work` | START WORK NOW | label | wo |
| `wo.start_work_hint` | Starts the time tracker for the PM Checklist. | hint | wo |
| `wo.started_at` | Started At: | label | wo |
| `wo.status_cancelled` | Cancelled | label | wo |
| `wo.status_completed` | Completed | label | wo |
| `wo.status_in_progress` | In Progress | label | wo |
| `wo.status_missed` | Missed | label | wo |
| `wo.takeover_btn` | Takeover WO | button | wo |
| `wo.takeover_title` | Work Order Takeover | title | wo |
| `wo.task_description` | Task Description | label | wo |
| `wo.tech_taking_over` | Technician Taking Over: | label | wo |
| `wo.title` | Work Orders | title | wo |
| `wo.under_min` | Item under minimum threshold! Place a PR/PO! Proceed anyway? | label | wo |
| `wo.unknown_part` | Unknown Part | label | wo |
| `wo.updated_ok` | Work Order Updated Successfully! | label | wo |
| `wo.upload_photo_title` | Upload Photo Evidence (Max 3) | title | wo |
| `wo.view_photo` | View Photo | label | wo |
| `wo.view_photo_n` | View Photo :n | label | wo |
