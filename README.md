# WCC_CMMS
Free unlimited-seat CMMS with 34 languages, online+offline Android companion, and full AI agent support (ai_agent.ini + ai_ctxt). Source available.

1What WCC Is
WCC is a complete, enterprise-grade maintenance management system — and it is free. Not a trial. Not a crippled community edition with the useful features behind a paywall. The whole thing: full ticket lifecycle, preventive maintenance, asset management with printed QR labels, a real inventory ledger, end-to-end procurement with separation of duties, live KPIs, granular role-based access, and a REST API — yours to run on your own hardware, for as many users as you like, forever.

Commercial CMMS platforms charge per seat per month. That pricing model quietly decides who is allowed to record work — and the answer is always "as few people as possible," because every login costs money. So the operator who spotted the fault, the storekeeper who has the part, and the supervisor who signs the purchase all end up sharing one account or working around the system. The record stops reflecting reality, and a maintenance system whose data you cannot trust is worse than a whiteboard.

WCC removes the meter. Give everyone who touches the work an account, because seats cost nothing. That single decision is what makes the rest possible: genuinely fine-grained permissions so access does not mean access-to-everything, an interface fast enough to use with gloves on at 3am, and data that stays trustworthy with the whole shift entering it.

What you get
Capability	What it means on the floor
Full ticket lifecycle	Register → take over → work → close out → searchable history, with time measured at every step so your KPIs are real, not estimated.
Preventive maintenance	Recurring schedules, reusable checklists with expected times, a colour-coded calendar, and automatic overdue detection.
Asset management + labels	Deep equipment records with OEM, warranty and lifecycle data — and print QR or DataMatrix labels, fully offline, on a Zebra or any office printer.
Inventory with a real ledger	Every stock movement is recorded and traceable back to the exact job that caused it. Parts reorder themselves when they hit their minimum.
End-to-end procurement	Requisition → approval → storekeeper fulfilment → receipt → budget, with the person who authorises spend kept separate from the person who receives goods.
Live KPI dashboard	MTTA, MTTR, MTBF, downtime and Ghost Time — computed from the work as it is recorded, with static or rolling targets.
Granular RBAC	22 permissions across 6 editable roles, enforced on the server for every page and every API call.
Skills & competence tracking	Proficiencies earned automatically from logged work, plus certifications with expiry warnings.
REST API	Key-authenticated, same permission model, ready for the mobile companion app or your own integrations.
Built on four principles
Everything in WCC follows the same four rules, and it is worth knowing them because they are why the numbers can be trusted:

The record reflects what happened. Time is measured, not estimated — the clock starts when a technician takes a job and stops when they close it. Stock movements are written to a ledger as they occur. Every figure traces to an event.
Permission is enforced on the server. Every action re-checks the caller's rights at the point it executes, through the web interface and the API alike.
Speed on the floor beats completeness in the form. Required input is kept to what is genuinely required. A technician with a broken line will fill in a short form and skip a long one — so the forms are short.
Nothing is guessed. When the system cannot determine something — no vendor for a part, no mapping for a category — it tells you, rather than substituting a plausible-looking value you would later have to unpick.
Scope, stated plainly
A focused tool does a few things excellently rather than everything adequately.

WCC is deliberately:

A maintenance system, not an ERP. It handles maintenance procurement end to end; it is not your accounting ledger of record.
Calendar-and-checklist driven, not IoT or predictive-AI. No sensor ingestion, no failure-prediction model — because that is what the overwhelming majority of plants actually run on, reliably, today.
Single-organisation. One installation serves one company, with full support for multiple plants and workshops inside it.
Web-first, with a companion app for scanning. The interface adapts to handheld screens; camera-based QR scanning lives in a separate Android app, because a shop-floor intranet usually has no HTTPS and browsers require it for camera access.
The licence, in plain terms
Apache 2.0 with the Commons Clause. Use it, modify it, and run it for your own operations indefinitely — including commercially. The single restriction: you may not sell the software itself or offer it as a paid hosted service. Run it for your plant, for free, for good.
Orientation
2Installation
WCC is raw PHP and MySQL with no build step, no package manager and no framework. Deployment is copying files and importing a database — there is nothing to compile and nothing to keep running except your web server.

Requirements
Component	Minimum	Notes
PHP	8.0	Developed against 8.2. Needs pdo_mysql and json — both standard.
MySQL / MariaDB	MySQL 5.7 · MariaDB 10.3	Developed against MariaDB 10.4. Uses utf8mb4 throughout.
Web server	Apache with mod_rewrite	AllowOverride All is required — security rules live in .htaccess.
Disk	~50 MB + uploads	The application is small; attachments and database backups dominate.
Browser	Any current browser	WebGL only affects the animated background, which can be switched off.
XAMPP on Windows, or a standard LAMP stack on Linux, both satisfy this without extra configuration. No Composer, Node or build tooling is involved at any point.

Quick install
Copy the application into your web root (for XAMPP, C:\xampp\htdocs).
Create an empty database and a user that owns it.
Import the supplied SQL dump into that database.
Point inc/db.php at your database name, user and password.
Open the site and sign in.
Zero-config on a laptop, one step for a server
WCC runs immediately against a default XAMPP setup — no database configuration needed to start exploring. When you move it to a shared server, take the standard step of creating a dedicated database user scoped to its own schema and pointing inc/db.php at it. That is the only database change go-live requires.
Manual install
If you are building the schema rather than importing a dump, apply the migrations in order. They are plain numbered SQL files, applied by a small runner:

php migrations/migrate.php --status    # what has and has not been applied
php migrations/migrate.php --apply     # apply everything outstanding
The runner records each applied file in schema_migrations, so re-running it is safe and only pending files execute. See Migrations for how to write one, and for how to regenerate a clean schema baseline whenever you want one.

First login
If the users table is completely empty, login.php seeds a single administrator on first visit — username admin, password password — and immediately forces a password change, because that credential is public knowledge.

If you imported the demo database instead, the accounts described in Demo Data already exist and no account is seeded.

A 30-second go-live check
Before a server goes public, three quick confirmations: your admin password is set, your database user is scoped, and .htaccess is active — request /schema.sql and confirm you get a 403. A 403 means Apache is honouring the project's security rules (see Hardening); if it downloads instead, enable AllowOverride All for the directory. That is the whole checklist.
Orientation
3A Guided Tour
This chapter walks the shortest path that touches every major part of the system: a machine breaks, somebody reports it, a technician fixes it consuming a part, and the numbers move. If you read one chapter before demonstrating WCC, read this one.

The application shell
Every signed-in page shares the same frame:

The sidebar is built from your permissions, not from a fixed menu. Two people can be looking at the same installation and see genuinely different navigation — a Storekeeper has no Tickets section at all. It collapses to icons and remembers that choice.
The notification bell sits in the sidebar footer and shows a count of unread items. It refreshes on page load rather than polling; see Notifications.
The theme toggle switches light and dark. The choice is stored per browser and applied before first paint, so there is no flash of the wrong theme.
The animated background is a WebGL effect that can be turned off per user under My Profile → Visual Preferences. On machines without WebGL it simply never appears — nothing breaks.
Your first ticket, end to end
1 · Register the event. From the hub, choose Register Event. Pick the machine — the search matches on name or asset UUID, so a scanned label finds it directly. Describe the fault, set a priority, and name the person in charge. The reporter is taken from your session and cannot be overridden.

2 · A technician takes it over. The ticket appears on the Active Tickets board and in the bell of everyone who can take jobs on. Opening Takeover starts the clock. From this point the ticket is bound to that technician — another user cannot close out someone else's job.

3 · The work is recorded. On closeout the technician records what was actually done, the fault type and root cause, and any parts consumed. Consuming a part writes an inventory_ledger row tied to this ticket, so months later the question "where did those twelve bearings go?" has an exact answer.

4 · Stock may reorder itself. If that consumption dropped the part to its minimum and the part is marked for auto-reorder, a purchase requisition is raised automatically, through the same approval rules a human requisition would follow. See Inventory.

5 · The numbers move. The closed ticket now contributes to MTTA, MTTR and downtime on the KPI dashboard, to that machine's failure history, and to the technician's proficiency hours. Nothing here is entered twice — the metrics are a consequence of the work being recorded, not a separate reporting exercise.

The point of the walkthrough
One person recording one repair, once, produced: an auditable intervention record, a stock movement, possibly a purchase requisition, three updated KPIs and a skills increment. That compounding is the entire argument for the system.
Demo accounts
A demo database ships with a fictional plant — two workshops, six production lines, twenty-four machines and roughly nine months of history. It has one account per role, so you can see exactly how the system changes shape for different people. All share the password Demo2026!.

Sign in as	Role	What they can do
a.rivera	Admin	Everything, including user management and data administration.
p.nair	Supervisor	Close out work, approve purchases, read the KPI dashboard.
j.okafor	Technician	Take over tickets and work orders, consume stock.
r.silva	Operator	Report faults. Cannot take them on or see costs.
h.bakker	Storekeeper	Fulfil purchase orders and receive goods — but never approve their cost.
c.whitfield	Viewer	Read-only.
Worth demonstrating deliberately
Sign in as h.bakker and try to approve a purchase order. The Storekeeper can ship, receive and close orders but cannot approve the spend — the separation between "who authorises money" and "who moves goods" is enforced server-side, not merely hidden. That distinction is what auditors ask about.
Architecture
4Code Structure
WCC has no framework, no router and no autoloader. A URL maps to a file on disk, that file includes what it needs, and it prints HTML. If you can read PHP you can read this codebase without learning anything else first — which is the point, because the people most likely to maintain a plant's CMMS are plant engineers, not full-time web developers.

Folder map
htdocs/
├── index.php  login.php  register.php  my_profile.php   entry points
├── auth.php   rbac.php   nav.php                        shared shell
├── docs.php                                             this manual
│
├── inc/          shared logic — no HTML, no output
├── api/          JSON endpoints used by the pages
│   └── v1/       versioned REST API for machines and the companion app
│
├── _maint/       maintenance operations   (7 pages)  tickets, work orders, PM
├── _logi/        logistics & procurement  (9 pages)  inventory, POs, vendors
├── _mgmt/        management & admin       (6 pages)  users, settings, data admin
├── _eam/         asset management         (5 pages)  equipment, labels
├── _rpt/         reporting                (3 pages)  KPIs, history
├── _prod/        production topology      (1 page)   lines
├── _trck/        tracking                 (1 page)   PO stepper
│
├── css/  js/  img/  uploads/  backups/  migrations/  demo/
└── archive/      retired scripts, kept out of the served surface
Why underscore modules
The leading underscore is not decoration. It groups the domain folders together at the top of any directory listing, and — more usefully — it makes the domain visible in every URL and every include path. /_logi/purchase_orders.php tells you which part of the business you are in before you open anything.

Modules are organised by business domain, not by technical layer. There is no controllers/ or models/, because a maintenance engineer debugging the purchase-order screen wants every part of that screen in one place, not scattered across three layers by a convention they did not choose.

_qual/, _cmms/, _erp/ and _mes/ exist but are empty — reserved for future domains so the naming does not have to be retrofitted later.

Dependency direction
Modules depend on inc/ and on each other's data, never on each other's pages. _maint reads equipment (_eam) and parts (_logi); _rpt reads from nearly everything. No module includes a page from another module, so there are no circular includes to untangle.
Anatomy of a page
Every protected page follows the same five-step shape. Once you have read one, you have read all of them:

<?php
include __DIR__ . '/../auth.php';            // 1. session + login gate
require_once __DIR__ . '/../rbac.php';
require_perm('view_work_orders');            // 2. permission gate — server-side

require_once __DIR__ . '/../inc/db.php';     // 3. dependencies
$pdo = get_wcc_db_connection();

try {                                        // 4. all queries up front
    $items = $pdo->query("SELECT ...")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { wcc_user_error("Unable to load work orders.", $e->getMessage()); }

$page_title = 'Work Orders';                 // 5. then render
require_once __DIR__ . '/../inc/head.php';
?>
<?php include __DIR__ . '/../nav.php'; ?>
... HTML ...
Data is fetched before any output. That ordering is deliberate: it means a database failure can still redirect or render an error page, because nothing has been sent to the browser yet.

The permission gate is the security boundary
require_perm() at the top of the file is what actually protects the page. The sidebar hides links the user cannot use, but that is a courtesy — typing the URL directly hits the same gate. Any new page without a require_perm() call is public to every logged-in user, whatever the navigation shows.
Architecture
5Request Lifecycle
This chapter follows a single request from the browser to the rendered page, naming what runs at each step. It is the fastest way to understand where to put something new — or where to look when something misbehaves.

From URL to HTML
Apache resolves the file. No front controller: /_maint/work_orders.php is that file. The one exception is /api/v1/…, where an .htaccess rewrite routes everything to api/v1/index.php so the REST API can have clean resource URLs.
auth.php runs first. It starts the hardened session, loads the error handler, sends no-cache headers, and redirects to the login page if there is no session. It also rebuilds the cached permission set if it is missing — which is what keeps a session valid across a code deploy.
rbac.php resolves rights. Permissions come from role_definitions for the user's role level, then any per-user overrides in users.permissions_json are merged on top. The result is cached in the session so the page does not re-query on every check.
require_perm() decides. Either the page continues, or it renders an Access Denied panel — with the sidebar still present, so the user can navigate away rather than hitting a dead end.
The page queries, then renders. inc/head.php emits the document head, applies the saved theme before first paint, and opens <body>. nav.php draws the sidebar from the same permission set.
Shared infrastructure
Everything in inc/ is logic with no output. Each file owns exactly one concept — one definition of a rule, called from everywhere it applies — so the whole application stays consistent by construction.

File	Owns
db.php	The single PDO connection. Every page uses get_wcc_db_connection(); nothing constructs its own.
session.php	Hardened session start — HttpOnly, SameSite, strict mode. Every entry point uses it instead of raw session_start().
error.php	Friendly error pages plus logging. See the warning below — its behaviour is unusual.
csrf.php	Token issue and validation for state-changing requests.
ratelimit.php	Fixed-window throttle, used for failed logins.
audit.php	wcc_audit_log() — actor, action, entity, before/after.
notifications.php	Per-user notification centre and permission-targeted broadcast.
techident.php	Who performed an intervention, matching both name spellings. See Ticket Lifecycle.
ticketid.php	Collision-safe ticket ID allocation.
partslist.php	Normalises the two historical shapes of parts_list JSON.
workorders.php	The single definition of "overdue".
procurement.php · reorder.php	Approval routing, and event-driven automatic reordering.
gamification.php · skill_expiry.php	Proficiency tiers and certification-expiry warnings.
kpi.php · shift_calendar.php	The single KPI engine and shift-aware elapsed time.
dbadmin.php	Backup, restore and flush.
head.php · version.php	The document shell and the asset cache-busting token.
A recurring lesson worth stating plainly
Each of these owns a single rule, defined once and shared. A proficiency tier, the definition of "overdue", the ticket-ID format — each lives in one place, so every screen that uses it stays in agreement. If you extend the system and find a rule that already exists, call the shared version rather than writing a second copy.
Errors and logging
inc/error.php gives the application a consistent safety net. Two handlers work together:

An error handler that promotes PHP diagnostics to exceptions, so a latent problem surfaces immediately rather than corrupting output quietly.
An exception handler that catches anything uncaught, logs the full technical detail for the operator, and shows the user a clean "Something went wrong" page — never a raw stack trace, a file path or a database message.
The effect for an end user is that a problem produces a tidy, branded message instead of an intimidating error dump, while the full diagnostic is waiting in the log for whoever maintains the system.

A note for developers verifying changes
Because errors surface as the friendly page rather than raw PHP text, confirm a page by loading it and checking the rendered result and the PHP error log — not by scanning the output for the words "Fatal error", which the handler deliberately never emits. php -l checks syntax only; it does not execute the page.
Architecture
6Design System
There is one stylesheet, css/global.css, and one behaviour script, js/wcc-ui.js. No CSS framework, no build step, no utility classes. Pages style themselves by using design tokens, so a theme change is a variable change rather than a search-and-replace across a hundred files.

Tokens
Every colour, spacing step, radius and shadow is a CSS custom property defined once on :root and redefined under .light-theme. Components never name a literal colour.

--text-primary  --text-secondary  --text-muted  --text-accent
--panel-bg      --panel-border    --surface-1   --modal-bg
--danger        --warning         --success     --info
--space-1 … --space-8     --radius-sm/md/lg/xl     --shadow-1/2/3
--fs-xs --fs-sm ...
Using a token instead of a literal is what makes light mode work at all. A panel written as background: var(--panel-bg) follows the theme; the same panel written as background: #1e293b becomes a dark slab on a white page. That exact bug has appeared more than once — most recently in a KPI drill-down popup that hardcoded a navy background and pale text.

Components
Class	Use
.dashboard-container	The main page panel. Centred, capped width, blurred backdrop.
.data-table + .table-wrap	Tables. The wrapper carries overflow-x so wide content scrolls inside its own box rather than widening the page.
.parent-row / .child-row	Expandable table rows — the master/detail pattern used across the app.
.pill-btn	The standard button: pill-shaped, tinted background, coloured text. Variants pill-success, pill-warning, pill-danger, pill-info, pill-sm, pill-block.
.modal + .modal-content	Overlays. See the warning below before setting a width.
.wcc-empty	Empty states — an explicit "nothing here" rather than a blank region.
Modal widths
.modal-content is now viewport-relative (width: 94vw) capped by max-width, which defaults to a comfortable 460px. To make a modal wider, just raise its max-width — e.g. style="max-width:760px" gives an 760px modal that still shrinks on small screens. (Historically the base carried a fixed width: 400px, so max-width was ignored and modals crammed at 400px; that trap is gone.) For a hard width you can still set width: min(760px, 94vw).
js/wcc-ui.js supplies the small amount of shared behaviour: openWccModal() / closeWccModal(), showToast(), openWccConfirm() for destructive confirmations, and toggleTheme().

Light and dark
The theme is a single class, light-theme, on <html>. The choice persists in localStorage and is applied by a small inline script in inc/head.php before the stylesheet loads — without that, every page would flash the default theme before correcting itself. Switching dispatches a wcc:themechange event so components holding their own canvas or colours can react.

Cache busting
CSS and JS are requested with ?v= and the constant WCC_UI_VERSION from inc/version.php. Bump it whenever you change a shared asset, otherwise returning users keep the cached copy. It is unrelated to the application version in version.json — that one is the product's, this one is the browser's.
The animated background
js/xmb-wave.js draws three slow "silk ribbon" waves in the accent colours on a WebGL canvas behind all content. It is presentation only — nothing depends on it.

Its constraints are worth knowing because they are the reason it is not a performance problem:

Renders at 60% resolution, capped at 24 fps.
Pauses entirely when the tab is hidden.
Honours prefers-reduced-motion by drawing one static frame.
If WebGL is unavailable it simply never appears.
Users can switch it off in My Profile → Visual Preferences; the preference is per-browser (localStorage) so someone on an old shop-floor PC can disable it without affecting anyone else.
One subtlety worth recording: because WCC is a multi-page application, every navigation is a fresh document, and the animation clock would restart from zero on each one — the ribbon visibly snapping back to its opening shape on every menu click. The elapsed time is therefore carried in sessionStorage and resumed, so motion is continuous across pages. It is per-tab by design, so two open tabs each keep their own unbroken ribbon rather than fighting over one shared clock.

Data
7Database Schema
Forty tables, all InnoDB, all utf8mb4. Every query in the application is a prepared statement — there is no ORM and no query builder, so what you read in a page is the SQL that runs.

The shape of the data
The tables fall into four groups, and the distinction matters because it decides what is safe to clear (see Data Administration):

Group	Contains	Clearing it means
Transactional	Tickets, actions, work orders, purchase orders, ledger, notifications, audit	Losing history. The plant still exists.
Reference	Equipment, parts, vendors, lines, workshops, checklists	Re-setting up the plant from scratch.
Config	Users, roles, settings, registration and skill configuration	Breaking login and application behaviour.
System	schema_migrations	Losing track of which migrations ran.
Tickets and actions
The core of the system is two tables. active_tickets is the fault — what broke, on what, when, reported by whom, at what priority. ticket_actions is the work — who took it, when they started, when they finished, what they found and what they replaced.

Table	Key columns	Notes
active_tickets	ticket_id (PK, varchar), equip_id, report_date, report_time, announced_by, pic, fault_desc, priority, status, closed_by	The primary key is a human-readable string, not an integer — see Ticket Lifecycle. Status is OPEN / PENDING / ESCALATED / CLOSED. closed_at is set when the ticket leaves the active board so History can sort by close time.
ticket_actions	action_id, ticket_id, tech_name, action_start, action_end, fault_type, root_cause, action_taken, parts_used	action_start/action_end are the source of every repair-time metric in the system.
ticket_comments	ticket_id, user_name, comment_text	Free discussion attached to a ticket.
People are stored by name
tech_name, pic, announced_by and closed_by hold a person's name rather than a user_id, so records read naturally as people. When filtering by person, use wcc_tech_aliases() from inc/techident.php, which resolves every spelling a person's work may be filed under so results are always complete.
Assets and plant
Physical structure is a three-level hierarchy: workshops contain production_lines contain equipment. Equipment can also sit outside a line entirely — compressors, chillers and cranes serve the whole site.

equipment is the widest table in the schema (38 columns) because it carries the full asset record: OEM brand, model and serial; purchase date, PO value and vendor; warranty expiry and end-of-life; criticality A/B/C; PM intervals; LOTO protocol; and a JSON blob of technical details. asset_uuid is the identifier printed on the physical label and matched by the scanner.

Supporting tables: equipment_bom (which parts fit this machine), equipment_documents (manuals and drawings), and uuid_rules (per-category asset ID generation patterns). Soft-delete uses deleted_at on equipment (and tooling); factory-health style counts exclude soft-deleted rows.

Tooling is a parallel register: toolings, tooling_bom, and tooling_documents. It does not sit under equipment; permissions (view_toolings / manage_toolings) are independent. See Assets & Labels for vault vs ledger surfaces.

Inventory and ledger
inventory_parts holds the parts master — stock level, minimum threshold, maximum, MOQ, unit cost, lead times, vendor, and physical location down to the bin.

inventory_ledger is the important one. Every stock movement is an immutable row: part_id, change_qty (signed), reason, reference_type and reference_id pointing back at whatever caused it, plus the actor and timestamp. Three reasons occur in practice — ticket_consume, wo_consume and po_receipt — so any quantity can be traced to the specific job or delivery behind it.

Procurement
Table	Holds
purchase_orders	The order: number, vendor, department, total, status, approval level, emergency-bypass flag.
po_items	Lines: part, ordered qty, received qty, unit price. Partial receipt is the difference between the two.
po_status_logs	Every transition, with who and a note — the audit trail behind the stepper.
po_documents	Generated requisitions and uploaded invoices.
departments · department_budget_logs	Budget allocated and consumed, and the movements behind it.
vendors_suppliers	Suppliers, contacts, payment terms, lead time, rating.
Users, RBAC and system
Table	Holds
users	Accounts. role_level plus optional permissions_json overrides; api_key for REST access; badge_number as the shop-floor identifier; locale for the UI language pack.
role_definitions	The editable role → permission map. Authoritative — the hardcoded fallback in rbac.php only applies if this table is unreadable.
user_skills	Manually granted certifications with an optional expiry_date.
skill_automation_config	Maps an equipment category to a proficiency name and icon. Acts as the allow-list: an unmapped category earns nothing.
app_settings	Key/value configuration grouped by category (SLA, KPI, Procurement, Security, EquipmentLabels).
audit_log	Actor, action, entity, before/after JSON, notes.
notifications	Per-user alerts with type, severity, link and read flag.
rate_limit	Failed-login counters keyed on IP and endpoint.
Tables that are intentionally empty
Several tables exist and are never written by the current code: eam_directory, ticket_parts_consumed, system_audit_logs, scheduled_reports and notification_broadcast. They are schema left over from earlier designs, superseded by inventory_ledger, audit_log and notifications. They are harmless, but do not build against them expecting data to appear.
Data
8Migrations
Schema changes are numbered SQL files in migrations/, applied in order by a small runner and recorded so they never run twice. There is no rollback mechanism — forward-only, which for a system whose database you are expected to back up before touching is the honest trade.

How migrations run
php migrations/migrate.php --status    # list applied and pending
php migrations/migrate.php --apply     # apply everything pending, in order
The runner reads migrations/*.sql, sorts by filename, and skips anything already recorded in schema_migrations. Each successful file is inserted into that table, so re-running is safe and idempotent.

Naming is NNNN_short_description.sql, zero-padded so lexical order is execution order:

0012_po_status_logs_note.sql
0013_role_definitions_storekeeper.sql
0014_add_admin_layout_json_to_users.sql
0015_create_notifications.sql
Writing a migration
Make it re-runnable where you can. CREATE TABLE IF NOT EXISTS and guarded ALTERs turn a half-applied migration from a crisis into an inconvenience.
Mind the MySQL/MariaDB gap. The two dialects differ in places — for example MariaDB does not accept CAST(… AS JSON). If you develop on one and deploy on the other, test your migration on both.
Never edit an applied migration. It has already run somewhere and will not run again. Write a new one.
Back up first. Admin Panel → Data Administration → Backup takes a full dump in one click. Do it before --apply, every time.
The database is the source of truth
The recommended way to stand up a fresh installation is to import the supplied database dump — it is a complete snapshot of the live schema and is what the application is built against. The migration files then carry it forward from there.

When you need a table's exact shape, ask the database
DESCRIBE table_name or a query against information_schema.columns is always authoritative. The schema summary in Database Schema was read directly from a running installation for exactly this reason — the database is the definitive record of its own structure.
To regenerate a clean schema baseline at any time, the built-in backup tooling produces a full mysqldump; a --no-data dump gives you a structure-only reference whenever you want one.

Security
9Authentication
Authentication answers "who is this?". Authorisation — covered in Roles & Permissions — answers "what may they do?". This chapter is the first question only.

The login flow
Identify. A user signs in with either their username or their badge number. The badge is the shop-floor identifier printed on an ID card, which matters on a plant where people know each other by badge, and keeps personal names off shared terminals.
Throttle check, before the password is examined. If this IP has already failed too many times, the attempt is rejected without testing the credential — so a locked-out attacker cannot keep trying candidates.
Verify. password_verify() against a bcrypt hash. Plain passwords are never stored, logged, or written to a backup in recoverable form.
Regenerate the session ID. A session that existed before login must never become an authenticated one — see below.
Cache identity and rights. User ID, username, display name, badge, role level and the resolved permission set go into the session, so subsequent pages do not re-query on every permission check.
Failure messages are deliberately identical
"Invalid username or password" is returned whether the account does not exist or the password was wrong. Distinguishing them would let an attacker enumerate valid usernames — turning one unknown into two, and halving the work of a targeted attack.
Session handling
Every entry point starts its session through inc/session.php, never a raw session_start(). That file sets the cookie parameters before the session begins, which is the only point at which they can be set:

Setting	Value	Why
HttpOnly	on	JavaScript cannot read the cookie, so an XSS flaw cannot steal the session.
SameSite	Lax	Blocks cross-site POST (CSRF) while leaving ordinary links working.
Secure	only under TLS	Set automatically when the request is HTTPS. Not forced, because a shop-floor intranet frequently has no TLS and a hard flag would break login there entirely.
use_strict_mode	on	Rejects a session ID the server never issued — the other half of fixation defence.
Configuring this in code rather than php.ini is deliberate: the hardening then ships with the application. A deploy to a different host, or a reinstalled PHP, cannot silently lose it.

Idle timeout is configurable globally (Admin Panel → System Settings) and each user may set a shorter personal timeout in their profile.

Brute-force defence
Failed logins are counted per IP in a fixed window: 10 failures in 15 minutes locks further attempts from that address until the window rolls over. A successful login clears the counter, so someone who mistypes twice and then succeeds never accumulates toward a lockout.

It fails open, on purpose
If the database is unreachable, the throttle allows the attempt rather than blocking it. This is a password-guessing deterrent, not an access control — and a technician locked out of a shop-floor terminal at 3am because a counter table was unavailable is a worse outcome than the attack it would have prevented.
Password policy
Hashed with PASSWORD_DEFAULT (bcrypt), never stored reversibly.
A change is forced when must_change_password is set on the account, or when the password is still the seeded default. The database flag is authoritative — it cannot be bypassed by manipulating the client.
Administrators with reset_passwords can reset another user's password; they cannot read the existing one, because nobody can.
Self-service change lives in My Profile and requires the current password.
First-run convenience
On a brand-new installation with no users, the login page seeds a starter admin account so you can get straight in — and immediately requires you to set your own password before anything else. A clean, guided first login with no manual account creation needed.
Security
10Roles & Permissions
WCC has 24 permissions across 6 roles, and both are editable. Because seats cost nothing, everyone who touches the work can have an account — which only works if permissions are fine-grained enough that giving someone access does not give them everything.

The permission model
A user's rights are resolved in two steps:

Start from the permission set for their role_level, read from role_definitions.
Merge any per-user overrides from users.permissions_json on top.
So a role is a starting point, not a cage. You can grant one technician approve_purchase_orders without inventing a new role, and without promoting them past everything else that a higher role would carry.

The 24 permissions, by group:

Group	Permissions
Tickets	view_tickets, create_tickets, takeover_tickets, closeout_tickets, view_history
Maintenance	view_work_orders, manage_work_orders
Assets	view_equipment, manage_equipment, view_toolings, manage_toolings, view_inventory, manage_inventory
Procurement	view_vendors, manage_vendors, view_purchase_requests, create_purchase_requests, approve_purchase_orders, fulfill_purchase_orders
Reports	view_statistics
Admin	manage_users, manage_settings, reset_passwords, delete_users
Equipment and tooling are separate
Tooling used to ride on equipment permissions. It now has its own pair: view_toolings (registry ledger, BOM/docs APIs) and manage_toolings (tooling vault). Uncheck either box on a user or role-save role presets to flush access without touching equipment.
The full matrix
Level	Role	Permissions	Shape of the job
1	Operator	4	Reports faults and reads history. Cannot take work on or see costs.
2	Technician	10	Takes over and performs work, consumes stock, raises requisitions.
3	Supervisor	14	Closes out work, approves purchase cost, reads the KPI dashboard.
4	Admin	24	Everything, including user management and data administration.
5	Custom Viewer	0	A deliberately empty base — grant exactly what an auditor or contractor needs, nothing more.
6	Storekeeper	7	Fulfils purchase orders and receives goods. Cannot approve the spend.
Why Storekeeper exists as its own role
Separating fulfill_purchase_orders from approve_purchase_orders means the person who authorises money is never the person who receives the goods. That separation of duties is the first thing an auditor looks for in a procurement process, and it is enforced server-side rather than by convention.
Custom roles and overrides
Admin Panel → User Management → Role Presets edits the permission set of any role, applying to everyone at that level. Per-user overrides are set on the individual user's row, and take precedence.

Custom Viewer (level 5) ships with zero permissions on purpose. It is the correct starting point for anyone whose access should be an explicit list — an external auditor, a contractor, a manager who needs statistics but nothing else.

Role names are data, not constants
role_definitions is the authority, and it is editable. Read a role's name through get_role_name() rather than mapping level numbers to names in code — that way, when an administrator renames or re-scopes a role, every screen and API response reflects it immediately.
Where it is enforced
The same permission set is applied at four independent layers:

Layer	Mechanism
Navigation	nav.php renders only what you can reach. Convenience, not security.
Page	require_perm('…') at the top of the file. Typing the URL directly hits this.
Action	Handlers re-check before mutating. A form that was rendered while you had rights is re-validated when it is submitted.
API	require_api_perm('…') in the REST layer, against the same permission set.
Hiding a control is not a control
Every layer above the page gate is presentation. If you add a page or an endpoint and forget require_perm() / require_api_perm(), it is reachable by every authenticated user regardless of what the sidebar shows. When reviewing a change, check the gate, not the menu.
A denied page renders an Access Denied panel with the sidebar intact, at HTTP 200 rather than 403. That is a deliberate UX choice — the user can navigate away instead of hitting a dead end — but it means automated checks must assert on the page content, not the status code.

Security
11Hardening
This chapter covers everything protecting the application below the permission layer: request forgery, query safety, what the web server will hand out, and what happens to uploaded files.

CSRF protection
inc/csrf.php issues a per-session token and validates it on state-changing requests. wcc_csrf_require() rejects a missing or wrong token outright.

This applies to state-changing GET links too, not just POST forms. A plain link that deletes something is otherwise triggerable by an <img> tag in an email — which is why those links carry a token.

SameSite=Lax on the session cookie (see Authentication) is a second, independent layer: even without a token check, a cross-site POST arrives with no session at all.

Query safety
Every query is a prepared statement with bound parameters. There is no string concatenation of user input into SQL anywhere in the application.

Two places need care because identifiers cannot be bound as parameters:

Table names in Data Administration. Flush validates every requested name against the live list from information_schema before it appears in a TRUNCATE. A name that is not on that list is rejected, so the input is an allow-list lookup rather than a value.
Dynamic IN (…) lists. Built as ?,?,? placeholders with the values bound — wcc_tech_alias_placeholders() exists precisely so that pattern is written once instead of hand-rolled at each call site.
Webroot exposure
The application lives in the document root, so anything not explicitly denied is downloadable. Protection is layered in .htaccess files:

Path	Rule	Reason
root	Options -Indexes	Without it, directory listings hand over a full file inventory before anyone tries a login.
root	Deny .sql .md .ini .log .bak .yml, dotfiles, composer/package.json	Schema dumps, config and dependency versions were all fetchable.
root	X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy	Baseline response headers.
inc/ migrations/ _ai_ctxt/ _dev_artifacts/ docs/	Deny all	Server-side only; nothing fetches them over HTTP.
backups/ archive/	Deny all	Database dumps contain every password hash. Non-negotiable.
uploads/	Listing off, PHP execution disabled	Cannot be denied outright — invoices and checklist photos are linked directly. See below.
Verify the rules are actually in force
All of this depends on AllowOverride All. Request /schema.sql in a browser: 403 means the rules are live, a download means Apache is ignoring every .htaccess in the project and none of the protection above exists. Check this once, on every new deployment.
PHP itself is configured with expose_php=Off and display_errors=Off with log_errors=On, so error detail reaches the log rather than the visitor. Application code never echoes a driver message — a PDO exception leaks table names, SQL and file paths, so those are logged and replaced with a generic message.

Upload handling
uploads/ holds attacker-supplied bytes: invoice PDFs and checklist photos. It cannot be blocked, because the application links to those files directly. So it is hardened instead:

PHP execution is disabled in that directory. A .php file smuggled past the upload filter is served as inert text, not executed — this is the classic upload-to-remote-code-execution chain, and disabling the engine breaks it regardless of how the file got there.
Directory listing is off, so filenames cannot be enumerated.
Uploads are validated on extension, checked with is_uploaded_file(), size-capped, and renamed to a generated filename — the original name never becomes a path on disk.
Responses carry nosniff and a restrictive Content-Security-Policy, so a file that lies about its type is not reinterpreted as script.
Serving files, and gating them if you need to
Attachments are served directly by the web server with generated, non-guessable filenames — fast and simple, and the right default for manuals and photos. If a particular deployment stores commercially sensitive documents and wants every download to require a login, route uploads/ through a small PHP gatekeeper that checks the session before streaming the file. Both approaches are supported; choose per deployment based on how sensitive your attachments are.
Workflows
12Ticket Lifecycle
A ticket is one fault on one machine, from the moment somebody notices it to the moment the line runs again. It is the busiest object in the system and the source of nearly every metric, so this chapter is worth reading closely.

The state machine
  OPEN ──────────► PENDING ──────────► CLOSED
  reported         taken over,          work finished,
  nobody on it     work underway        record complete
State	Means	Who moves it on
OPEN	Reported, nobody has taken it. pic may be a suggestion; no clock is running.	Anyone with takeover_tickets.
PENDING	A technician owns it. action_start is set — this is when repair time begins accruing.	The owning technician, or someone with closeout_tickets.
CLOSED	Finished. action_end, root cause, work done and parts consumed are all recorded.	Nobody — closed tickets are history and are read, not edited.
Registering an event
Registering an event
Registering a new ticket (OPEN state) by searching for the equipment.
register.php is intentionally the shortest form in the application. Pick the machine, describe the fault, set a priority and an event type, name a person in charge.

The event type defaults to Failure and rarely needs changing — but flagging an inspection, a no-fault-found or a facilities request keeps it out of the breakdown count so MTBF stays honest. It never affects downtime.

The equipment search matches on name and asset UUID, so a scanned label resolves directly to the machine.
The reporter is taken from the session and cannot be set by the client — you cannot file a fault as somebody else.
The person-in-charge list is drawn from team_directory where role_type = 'technical'.
A repeat-fault warning appears if the same machine has recent similar events.
Ticket IDs
Format is TK-YYMMDD-NNN — a per-day sequence, e.g. TK-260722-014. Compact, chronologically sortable, and readable over a radio. The ID is always allocated by the server; a client-supplied one is ignored, because ticket_id is the primary key and a chosen value could collide with an existing ticket. Allocation retries on contention, so simultaneous registrations from several terminals all succeed.
Takeover and Evil Maid locking
Taking over a ticket
Taking over a ticket logs the intervention details and shifts it to PENDING.
Taking over a ticket stamps the technician's name and starts the clock. From that point the ticket is bound to that person: another user cannot close out work they did not do, even if they have the permission in general.

This is called "Evil Maid" protection in the codebase, and it exists for a mundane reason: shop-floor terminals are shared and frequently left logged in. Without the lock, the intervention record would say whoever last touched the keyboard, and every per-technician metric would be fiction.

People are identified by name
Intervention records carry the technician's display name, so history reads as people rather than login IDs — "Sara Lindqvist closed this", not "user 5". Reporting resolves every spelling a person's work may be filed under, so a technician's stats are always complete regardless of how any individual record was entered.
Closeout
Closing out a ticket
Closing the ticket (CLOSED state) deducts consumed parts and archives the record.
Closeout is where the record becomes worth having. It captures:

Fault type and root cause — what class of failure, and why.
Action taken — what was actually done.
Parts consumed — each one writes an inventory_ledger row referencing this ticket.
End time — closing the interval that defines repair duration.
Two side effects fire automatically. Consuming a part that drops to its minimum may raise a purchase requisition (Inventory). The logged hours add to the technician's proficiency for that equipment category (Roles & Permissions covers who; the profile page shows the tiers).

Stock is never driven negative
If a technician records using more of a part than the system believes is on the shelf, the consumption is capped at the quantity on hand and the ledger records what was actually taken. A stock level that has gone negative is not information, it is a bug waiting to be discovered during a stock count.
History and repeat detection
Ticket History
The historical archive of all closed interventions.
Closed tickets move to _rpt/history.php — searchable and filterable, and the input to the "top repeat offenders" ranking on the KPI dashboard. A machine appearing there repeatedly is the system telling you that fixing the symptom is not working and the PM interval or the root cause needs attention.

Nothing is deleted. Tickets are archived rather than removed, so a machine's full fault history remains available for as long as you keep the database.

Workflows
13Work Orders & PM
A ticket is reactive — something broke. A work order is planned: a job scheduled in advance, usually preventive maintenance, sometimes an improvement or an inspection. Both consume time and parts, and both feed the same metrics.

Work order states
Work order details
Viewing a work order in progress.
Status	Means
Scheduled	Planned for a date. Nobody has started.
In Progress	A technician has started; started_at is set.
Completed	Finished, with completion time, who did it, and parts used.
Missed	The date passed and it was never done. Deliberately distinct from Cancelled.
Cancelled	Deliberately called off — asset off-line, job superseded.
"Overdue" means one thing everywhere
A work order is overdue when its scheduled date has passed and nobody has picked it up, or when it is explicitly Missed. Work that is In Progress is not overdue — somebody is on it right now. That single definition drives the dashboard badge, the red row highlighting and the sort order, so every screen agrees on what needs attention.
Preventive schedules
A PM schedule is a recurring template: a title, the equipment, an assigned technician, a checklist, an interval in days, and the next run date. When it comes due it produces a work order.

Intervals are calendar-based, not meter-based — WCC does not ingest runtime hours from machines. Equipment carries both pm_days_interval and pm_hours_interval for reference, but scheduling is driven by days, which is what the overwhelming majority of plants actually run on.

Checklists
A checklist is a reusable list of tasks, each with an expected duration in minutes. Attached to a work order, it becomes the technician's step list, and completion is recorded per task.

The completion-time guard
If a checklist's tasks total 120 minutes and someone marks the whole thing complete 8 minutes after starting, WCC refuses the closeout and says so. It is not accusing anyone of anything — it is refusing to record a physically impossible number, because a PM record nobody believes is worse than no PM record at all.
Where enabled, technicians can attach photos to individual checklist tasks — useful for "show me the state of the belt before you changed it" evidence.

The calendar
PM Calendar
The Preventive Maintenance calendar overview.
_maint/pm_calendar.php shows the month with each work order colour-coded by urgency. On handheld screens it becomes an agenda list rather than a grid.

Marker	Meaning
🔵 Blue	Upcoming, more than 7 days out
🟢 Green	Upcoming, within 7 days
🟡 Yellow	Scheduled today
🟠 Orange	Overdue by 1–2 days
🔴 Red (pulsing)	Overdue by 3 or more days
✅	Completed
❌	Cancelled or Missed
Two completion rates are shown beneath the grid: annual and current-month, each the proportion of scheduled work actually completed. The month figure is the one to watch — the annual number is slow to move and slow to warn you.

Workflows
14Assets & Labels
Everything else in WCC points at a machine. If the asset register is wrong, the fault history, the PM schedule and the reliability numbers are all wrong with it — so this is the part worth getting right before you load anything else.

The asset register
Asset Register
The master equipment list.
_eam/setup_vault_equipment.php is the master list. Each record carries far more than a name, because the questions asked of a CMMS six months in are rarely "what is it called":

Group	Fields	Answers
Identity	asset_uuid, name, category, type	Which machine is this, and what class of thing is it?
OEM	brand, model, serial	Who do we call, and what exactly do we quote?
Commercial	purchase date, PO value, vendor, warranty expiry, EOL	Is this still under warranty? Should we be replacing it?
Operational	criticality A/B/C, base speed / pressure / temp / voltage	How badly does it hurt when this stops, and what is "normal"?
Maintenance	PM interval, last PM date, LOTO protocol, SOP link	When is it due, and how is it made safe?
Placement	workshop, line, station	Where is it, and what stops when it does?
Criticality is the field that earns its keep. A is "the line stops", B is "we work around it", C is "we notice eventually". It drives prioritisation everywhere and is worth setting honestly — if everything is A, nothing is.

Workshops, lines and stations
Asset Details
Detailed view of an asset showing its position in the hierarchy.
Workshop  ──►  Production Line  ──►  Equipment
"Plant A"      "CNC Cell 1"          "Mazak VTC-800"
The hierarchy is what lets a fault on one machine be understood as a line stoppage. It also drives the workshop breakdown on the KPI dashboard.

Equipment may sit outside any line — compressors, chillers, cranes and dust extraction serve the whole site. They are still fully tracked; they simply have no line_id.

Tooling registry and vault
Tools, fixtures and dies live beside equipment, not inside it. The shop floor sees them on _eam/toolings.php (ledger with accordion detail, linked parts BOM and documents). Master data and code/label rules live on _eam/setup_vault_toolings.php.

Surface	Permission	What it unlocks
Tooling registry	view_toolings	Ledger, search, BOM list API, docs list API
Tooling vault	manage_toolings	Create/edit/retire, BOM edit, label symbology, doc upload
Equipment ledger/vault	view_equipment / manage_equipment	Independent of tooling — can be granted or flushed separately
Linked parts are stored in tooling_bom; files in tooling_documents (under uploads/tooling/…). APIs: /api/get_tooling_bom.php, /api/get_tooling_docs.php, /api/upload_document.php with entity=tooling.

QR and DataMatrix labels
Every asset can carry a printed label so a technician scans instead of typing. The payload is deliberately minimal and completely offline:

WCC|<equip_id>|<asset_uuid>|<name, max 40 chars>|SN:<serial>
Why a payload and not a URL
A shop-floor network usually has no route to the internet, and often no DNS worth relying on. A label encoding https://… is useless the moment either is true. This payload identifies the machine on its own — enough to look it up, or to read from the scanner if the system is down. Codes are generated locally (a vendored barcode library in the browser, native ZPL on a Zebra), so nothing is ever sent anywhere to make a label.
Both QR and DataMatrix are supported; DataMatrix is denser and survives better on small, oily or curved surfaces.

Printing: Zebra and paper
Two paths, because most plants have exactly one of the two:

Zebra label printer — ZPL is generated and sent to the printer over TCP port 9100. Darkness, speed and DPI are configurable. This is the durable option: thermal transfer onto industrial label stock.
Any ordinary printer — labels are laid out on a configurable sheet grid. Page size, label size, margins and gaps are all settable, so it works with off-the-shelf A4 label sheets, or plain paper into a laminator.
Selection is per-asset or in batch with select-all, so commissioning a new line does not mean printing labels one at a time. Settings live in app_settings under EquipmentLabels and are edited from the vault page's setup modal, gated by manage_equipment — the people who own the asset register own its labels.

Scanning is the companion app's job
WCC's web interface can print labels but cannot read them. Browsers refuse camera access without HTTPS, and shop-floor intranets typically have none — so scanning lives in the separate Android companion app, which talks to the same REST API. This is a constraint of the environment, not a gap in the product.
Workflows
15Inventory
Inventory in a CMMS exists to answer one question at 3am: do we have the part? Everything else — valuation, reorder points, consumption analysis — is downstream of keeping that answer true.

The status column — read the whole store at a glance
Right after each part's name the Inventory page carries two icon columns (both deliberately unlabelled — the icons carry the meaning). The first is a live stock-status badge, the second a gold ★ for critical spares. Sitting beside the name they describe, they let a storekeeper triage the whole store in one downward glance.

So nothing has to be memorised, a compact status key is printed directly above the table — the same idea as the colour legend beneath the PM calendar. It lists every badge with its meaning and is generated from the same status engine the rows use, so it can never fall out of step with what the icons actually show. The table below is the full reference; the on-screen key is its short form.

Badge	Means	Action
✔ green	Healthy — comfortably above minimum	Nothing.
▲ amber	Approaching minimum (within the warning band)	Keep an eye on it.
⬣ red	At or below minimum	Order it — nobody is yet.
✕ red	Out of stock (zero)	A job may be blocked right now.
🚚 blue	On order — an open PO already covers it	Handled; wait for delivery.
⊘ grey	Obsolete / phasing out	Find a replacement — do not reorder.
The distinction that matters most
A red badge means low and nobody's on it — your actual to-do list. The moment an order exists (auto-raised or manual) the part turns blue "on order," so red is only ever the parts that genuinely need someone to act. Pair that with the ★ and the highest-priority row in the whole store — a critical spare, below minimum, not on order — reads itself off the screen.
The badges are live: they read current stock and purchase-order data on every load, so a part flips from red to blue the instant a PO appears, and back to green once goods are received above the band. Nothing needs to be "run." The obsolete state also suppresses the red alarm — the system never tells you to reorder something it knows can't be ordered.

Tuning it
The amber "approaching" band (how far above minimum still warns) and each part's lifecycle (Active / Phasing Out / Obsolete) are set together in Admin Panel → Inventory Health. The band is a single percentage; lifecycle is per-part.
The parts master
Parts Master
The inventory catalogue and stock levels.
_logi/inventory.php holds the catalogue. The fields that do real work:

Field	Purpose
stock_level	What is on the shelf now.
minimum_threshold	The reorder point. At or below this, the part needs replacing.
maximum_stock	Target level — reorder quantity is calculated up to this.
moq	Minimum order quantity the vendor will accept.
auto_reorder	Whether crossing the threshold raises a requisition automatically.
primary_vendor_id	Who to buy it from. Without one, WCC can warn but cannot order.
standard_lead_time / expedited_lead_time	Days to delivery — the difference between "reorder now" and "reorder yesterday".
aisle / rack / shelf / bin_code	Where it physically is. A part nobody can find is out of stock.
lifecycle_status	Active, Phasing Out or Obsolete — so you learn about a discontinued part before you need it.
The ledger is the truth
Inventory Details
Detailed view of an inventory item and its logistics.
stock_level is a running total. inventory_ledger is the record of how it got there — an immutable row per movement, carrying the signed quantity, the reason, and a reference back to whatever caused it.

Reason	Direction	Raised by
ticket_consume	out	Parts used repairing a fault
wo_consume	out	Parts used completing a work order
po_receipt	in	Goods received against a purchase order
_logi/inventory_audit.php presents this as a searchable trail, and rows link back to the job or delivery behind them. When a stock count disagrees with the system, this is where the discrepancy is found — not by guessing, but by reading what happened.

Why a ledger rather than just a number
A bare stock_level can only tell you that it is wrong, never why. With a ledger, "we are twelve bearings short" becomes a list of the twelve jobs that consumed them, with dates and technicians. That is the difference between an inventory system and a number in a box.
How stock is consumed
Both consumption paths behave identically, which is deliberate:

The part is validated as real.
The quantity is capped at what is actually on hand — min(requested, on_hand).
stock_level is decremented by the capped amount.
A ledger row records the actual quantity, not the requested one.
Auto-reorder is evaluated.
Step 2 is the important one. Recording a consumption larger than the stock on hand would drive the level negative, and a negative stock level is not a fact about the world — it is a number that will confuse everyone who sees it until somebody works out it was a data entry problem months earlier.

Event-driven auto-reorder
Reordering is not a nightly batch that notices yesterday's problem. It runs at the moment of consumption: if that consumption took a part to or below its minimum, wcc_check_and_reorder() considers a requisition immediately.

It raises one only if all of these hold:

auto_reorder is enabled for the part.
Lifecycle status is Active — no automatic reordering of a part being phased out.
Stock is genuinely at or below the threshold.
No open order already covers it. The part is not reordered twice — subsequent consumption of a part already on its way does not raise a second requisition.
Quantity is maximum_stock − stock_level, or the MOQ if that is larger. The requisition is created as PR-AUTO-… and routed through exactly the same approval rules as a human requisition — automation decides when to ask, never whether approval is required.

A part with no vendor cannot be ordered
If primary_vendor_id is empty, WCC notifies the people holding manage_inventory that stock is low and stops there. It will not invent a supplier. This is the "nothing silently guesses" rule in practice — and the reason to check that critical spares actually have a vendor set.
A manual Run reorder check button on the inventory page sweeps every part using the same helper, for use after a stock count or an import.

Workflows
16Procurement
Procurement is where a maintenance system touches money, which is why it is the part most likely to be audited. WCC models the whole path — request, approval, fulfilment, receipt, budget — with the authorising and receiving roles deliberately separated.

The nine stages
Purchase Orders List
The main Purchase Orders ledger overview.
Draft → Pending Approval → Issued → Shipped → In Transit
      → Partially Received → Fully Received → Closed
                                    ↘ Cancelled (from any stage)
Stage	Means	Requires
Draft	Being written, not submitted.	create_purchase_requests
Pending Approval	Submitted, waiting on a cost decision.	—
Issued	Approved and sent to the vendor.	approve_purchase_orders
Shipped	Vendor has despatched.	fulfill_purchase_orders
In Transit	On its way.
Partially Received	Some lines received; the rest outstanding.
Fully Received	Everything arrived. Budget is consumed here.
Closed	Invoice matched, order finished.
Cancelled	Called off.	—
_trck/tracking_stepper.php renders this as a visual progress bar, and every transition is written to po_status_logs with the actor and an optional note.

Approval routing
Routing is configured from the ⚙ Workflow modal on the Purchase Orders page — gated by approve_purchase_orders, deliberately not by manage_settings. The people who own the approval policy are the people who approve, not whoever happens to be a general administrator.

Configuration	Result
Workflow disabled	Every requisition auto-approves on submit, going straight to Issued with approval level Auto-Approved.
Enabled, auto-approve limit > 0	Requisitions at or under the limit auto-approve; anything larger goes to Pending Approval.
Enabled, no limit	Everything waits for a holder of approve_purchase_orders.
Auto-approvals write a log entry explaining why they were approved without a human. An audit trail that silently skips a step is worse than one that says "approved automatically: below the £250 threshold".

Emergency bypass
Orders can be flagged as emergency, recorded in is_emergency_bypass. The flag does not remove the approval requirement — it marks the order so that expedited purchases are visible afterwards rather than invisible.
Storekeeper fulfilment
PO Tracking Details
Tracking a partially received Purchase Order and its line items.
Once an order is Issued, it belongs to whoever holds fulfill_purchase_orders — the Storekeeper role. They move it through shipping, transit and receipt.

This separation is the point
A Storekeeper can receive £50,000 of goods and cannot approve a single pound of it. An Approver can authorise spend and cannot mark it received. One person cannot both authorise a purchase and confirm its arrival — which is the control that makes invoice fraud hard. Both permissions are checked server-side on every transition, not merely reflected in which buttons are drawn.
Goods receipt and budgets
Receiving a line does three things at once:

Increments received_qty on the line. Partial receipt is simply received_qty < ordered_qty — no separate state to maintain.
Raises stock_level on the part.
Writes an inventory_ledger row with reason po_receipt, referencing the order.
Budget is consumed when the order reaches Fully Received, not when it is approved or issued. Money is counted against a department when the goods actually arrive, so budget reflects what the plant has, not what it has asked for. department_budget_logs records each movement, and the Departments screen shows allocated against consumed.

Two document types attach to an order: a generated requisition PDF, and an uploaded supplier invoice — both stored in po_documents, so the paperwork sits with the order rather than in somebody's mailbox.

Workflows
17Notifications
Notifications exist so that work waiting on somebody actually reaches them. The design is deliberately unambitious — no websockets, no push service, no live polling — because a maintenance system's alerts need to be reliable, not instant.

How notifications work
Each notification is a row in notifications: recipient, type, message, link, severity and a read flag. The bell in the sidebar footer shows the unread count and opens a list; entries link straight to whatever needs attention.

Severity	Icon	Meaning
info	ℹ️	Something happened you may want to know.
warning	⚠️	Something needs attention before it becomes a problem.
danger	⛔	Something is already a problem.
success	✅	Something completed.
Two ways to send:

wcc_notify($user_id, …) — one named person.
wcc_notify_perm($permission, …) — everyone holding a permission, optionally excluding the actor.
wcc_notify_perms([$permA, $permB], …) — everyone holding any of those permissions, one row per user (union / dedupe).
The second is the one that matters. Alerts are addressed to a capability, not to a person: "whoever can approve purchases" rather than a name in a config file. Staff changes, holidays and role edits are handled automatically, and nothing is ever routed to someone who left last year.

Counts refresh on page load, not by polling
No background timer, no open connection. On an intranet where people navigate constantly this is indistinguishable from live, and it costs nothing when a browser sits open on a workshop terminal all weekend. Notifications are a work queue, not a chat client.
Every trigger
Event	Goes to	Severity
New ticket registered	takeover_tickets holders (not the reporter). Web + REST create.	info · warning if high priority
Ticket escalated	Union of takeover_tickets + closeout_tickets (one row per user; actor excluded)	warning
Ticket put on hold	Same union as escalate	warning
Ticket closed / quick resolve	Union of view_history + view_statistics (one row per user; actor excluded). Re-close does not re-notify.	success
Work order assigned	The assignee	info
Work order completed	Assignee (if not actor) + union of view_work_orders + view_statistics	success
Requisition needs approval	approve_purchase_orders holders	warning
Order awaiting fulfilment	fulfill_purchase_orders holders	info
Stock at or below minimum	manage_inventory holders, plus approvers	warning · danger at zero
Auto-reorder raised	manage_inventory holders	info
Certification expiring	The holder, at 30 / 20 / 10 / 5 / 3 days	warning · danger inside 5 days
Certification expired	The holder and manage_users holders	danger
Certification expiry, in detail
Most triggers fire from the action that caused them. Certification expiry is different — nothing "happens" when a date passes — so it runs as a scheduled job:

php cron_skill_expiry.php            # run daily
php cron_skill_expiry.php --dry-run  # show what would send, send nothing
Each certification falls into exactly one bucket per run: the tightest horizon its remaining days fall inside. A certification added four days before expiry lands in the "5 day" bucket only — it does not fire 30, 20, 10 and 5 simultaneously.

Safe to run twice
Sent buckets are recorded in the notification's own type field (skill_exp:<id>:<bucket>), so re-running the job — after downtime, or twice by accident — sends nothing extra. A reminder system that spams on retry gets muted, and a muted alert is worse than none.
Once a certification has actually lapsed, the holder's managers are told as well. Someone working without a valid LOTO authorisation is not only their own problem.

Features in Depth
18Working with Tables
Most of WCC is tables — tickets, work orders, parts, orders, users, ledger entries. They all share the same interaction model, so learning it once applies everywhere. This chapter covers the parts that are not obvious from looking at them, particularly the filter tokens, which are the single most useful feature most users never discover.

Drag-to-filter tokens
Drag-to-filter
A filtered table view showing active search tokens and expanded rows.
The search box above a table is draggable. Drag it onto a column header and it becomes a filter scoped to that column.

Type what you are looking for into the search box.
Drag the box onto a column header — the header highlights as a drop target.
The search becomes a token: a removable chip reading Status: CLOSED.
The box returns to its place, empty and ready for the next one.
Tokens are cumulative and AND-ed together. Three tokens means rows must match all three. This is how you answer a real question in a few seconds:

Worked example
"Which high-priority faults on the Okuma did Sara close?"

Drag Okuma onto Equipment → drag high onto Priority → drag Lindqvist onto PIC. Three drags, no query language, and each token is removable independently with its ✖ so you can widen the question one step at a time.
A 🔒 icon appears in the search box once a column is targeted — clicking it locks the current text into a token without dragging, which is quicker once you know the feature is there. Typing without dragging performs an ordinary global search across all columns.

This works identically on twelve screens:

Module	Pages
Maintenance	Active Tickets, Work Orders
Assets	Equipment, Equipment Vault, Toolings, Tooling Vault
Logistics	Inventory, Inventory Ledger, Purchase Orders, Purchase Requests, Vendors, Vendor Vault
Management	User Management, Users Directory
Reports	Event History
Expandable rows
A row with a ❯ arrow expands in place. The detail panel varies by table, and is where most of the real content lives:

Table	Expanding a row shows
Work Orders	Instructions, required parts with quantities, and the Takeover button
Equipment / Toolings	BOM linked parts, attached manuals/documents, label payload summary
User Management	Full profile, gamified proficiencies, certifications, and the complete permission matrix
Inventory Ledger	The source document — the work order or purchase order behind the movement
Purchase Orders	Line items, status history, attached documents
Event History	The full intervention record: root cause, action taken, parts used
In the ledger, rows referencing a work order or purchase order are clickable through to that document — so "where did these twelve bearings go" resolves to the actual job in two clicks.

Sorting and search
Sortable tables expose sort options through the column headers or a sort control, and the choice is carried in the URL (?sort=…) — so a sorted view can be bookmarked or sent to a colleague.

Default ordering is chosen per table to put the important thing first rather than to be alphabetical. Work Orders leads with overdue, then upcoming, then completed. Purchase Orders leads with what needs action. Event History prefers recently closed tickets (closed_at) so a job you just signed off appears at the top.

Global search and column filters update a live match count (N of M / N records) via the shared table UI helpers — so operators can tell immediately whether a criticality search (CLASS A/B/C) or a free-text filter actually narrowed the list.

Tables adapt to the screen
Below roughly 640px, tables collapse into stacked cards — each row becomes a labelled block instead of scrolling sideways. Wide tables that stay tabular scroll inside their own container, never widening the page itself.
Exports
Tickets CSV and Parts CSV from the KPI dashboard, honouring the selected date range.
Export CSV from User Management, for the current directory.
Print / PDF on the KPI dashboard, using a print stylesheet that drops the sidebar and page chrome and keeps the figures.
Exports contain what the query returned, not what is on screen — so a filtered view and an export can differ. Set the date range you want before exporting.

Features in Depth
19The Admin Panel
_mgmt/admin_panel.php is the hub for everything administrative. Rather than burying configuration in a settings tree, it presents thirteen tiles — some open a configurator in place, some navigate to a full management page.

The tile board
Admin Panel
The central administrative tile board.
Tiles are filtered by your permissions, so two administrators can see different boards. Each tile is one of two kinds:

Modal tiles open a configurator over the panel. You stay on the page — useful for quick configuration that does not deserve a full navigation.
Link tiles navigate to a dedicated management page, each of which has a "Return to Admin Panel" control.
Rearranging your panel
The tile order is personal. An administrator who spends their week in procurement should not have to scroll past PM checklists every time.

Click Edit Layout. Tiles become draggable.
Drag them into the order you want.
Click Save. The order is written to your user record (users.admin_layout_json).
Reset discards your arrangement and returns to the default.
Per user, not per installation
Your arrangement affects nobody else. It survives logout and follows you to another browser, because it lives on the account rather than in local storage. A tile you gain access to later appears in the default position rather than being lost.
What every tile does
Tile	Kind	What it is for
👥 User Management	page	Accounts, roles, per-user permission overrides, certifications, password resets, CSV export.
⚙️ Enclosed Setup Vault	page	The equipment master: full asset records, BOM, documents, UUID rules, label and printer setup.
🏢 Vendor Management	page	Suppliers, contacts, payment terms, lead times, ratings.
🏦 Department Management	page	Departments, budget allocation, consumption history.
📦 Add Inventory Part	modal	Register a spare part without leaving the panel — code, thresholds, cost, vendor, location.
📜 Inventory Audit Log	page	Every stock movement with its source document.
🩺 Inventory Health	modal	The stock-status warning band and per-part lifecycle (Active / Phasing Out / Obsolete) — drives the badges on the Inventory page. See Inventory.
🛒 PR / PO Management	page	The procurement engine: requisitions, approval, fulfilment, receipt.
🏭 Production Lines	modal	Create workshops and production lines — the plant hierarchy.
🗓️ PM Configurator	modal	Create a preventive schedule: equipment, interval, checklist, required parts, first run date.
🛠️ Ad-Hoc Work Order	modal	One-off planned job that is not part of a recurring schedule.
📄 Documents Management	modal	Upload safety SOPs and manuals against equipment.
📈 KPI Targets	modal	Static or rolling targets for MTTA, MTTR and MTBF. See Configurators.
✅ PM Checklists	modal	Reusable task lists with expected durations, attachable to schedules.
Two further administrative pages are reached from the sidebar rather than a tile: System Settings (session timeout, plant holidays) and Data Administration (backup, restore, flush — see Data Administration).

A tile is not a permission
Tiles are filtered by permission, but the protection is the require_perm() call on the destination page — not the absence of a tile. Someone who knows the URL reaches the same gate. Never treat "they cannot see the tile" as access control.
Features in Depth
20Skills & Proficiencies
WCC tracks competence two ways, and they are frequently confused because they sit side by side in the same column. Understanding that they are separate systems is the whole chapter.

Two separate systems
🏆 Gamified Proficiencies	🛠️ Manual Skills
Origin	Earned automatically from logged work	Granted by an administrator
Basis	Hours on an equipment category	A certificate, licence or course
Has tiers	Yes — six levels	No — you hold it or you do not
Expires	Never	Optionally, with warnings
Answers	"Who has actually worked on this kind of machine?"	"Who is allowed to do this?"
Stored in	Computed live — nothing stored	user_skills
Why both exist
They answer different questions and neither substitutes for the other. A technician with 200 hours on machining is demonstrably experienced — but if their LOTO authorisation lapsed last week they must not isolate that machine. Conversely a valid certificate says nothing about whether someone has ever touched a thermoformer.
How proficiencies are earned
Nobody awards a proficiency. When a technician closes an intervention, the time between taking the job over and finishing it is added to the equipment category of the machine worked on. Cross an hour threshold and the tier rises by itself.

Tier	Logged hours	Means
👑 Master	200 h +	Deep specialist — the person you wake at 3am for this equipment.
💎 Expert	100 h +	Handles the hard faults on this category unaided.
🥇 Proficient	40 h +	Comfortable across routine and most non-routine work.
🥈 Competent	20 h +	Works unsupervised on standard faults.
🥉 Advanced	10 h +	Past the basics, still building depth.
🌱 Novice	under 10 h	Getting started on this category.
The rules that decide what counts:

Only closed interventions with both a start and an end time. Open jobs count for nothing until closed out.
Tiers are per equipment category, not overall — someone can be Master on Machining and Novice on Packaging simultaneously.
A category only scores if it is mapped in the Skill Configurator. Unmapped categories earn nothing however much work is done on them.
Nothing decays. Hours accumulate for as long as the history is kept.
A chip shows the tier medal, the category icon, the proficiency name, the category, the hours, and how far to the next tier — "27h to Expert 💎". The ❓ beside the heading opens the full threshold table, generated from the same values the code scores with, so the explanation cannot drift from the behaviour.

Proficiencies appear in four places, rendered identically: your own profile, the Users Directory detail panel, the User Management detail panel, and the 🏆 badge popover.

The Skill Configurator
Skills Configurator
Mapping equipment categories to gamified proficiencies.
Reached from User Management, the configurator maps an equipment category to a proficiency name and an icon — for example Machining → ⚙️ Machining Specialist.

The configurator is an allow-list
A category that is not mapped here earns nothing, no matter how many hours are logged against it. The category name must also match the equipment record exactly — a mapping for "Conveyors" scores zero if your assets are categorised as "Conveyance". After adding an equipment category, add its mapping too, or that work becomes invisible.
Certifications and expiry
Manual skills are free text — whatever your plant actually requires: LOTO Authorised Person, Working at Height, KUKA Robot Programming, Forklift Licence B. Each may carry an optional expiry date; leave it blank for something that does not expire.

Two ways to add one:

Self-service — My Profile → Skills & Certifications. Name plus optional expiry date.
Administrator — User Management → expand the user → Manual Skills → Add New Skill, with its own date field per certification.
Expiry state is shown wherever a certification appears:

State	Appearance
No expiry	Plain chip.
Valid	Green — "Valid until 7 Jun 2027".
Expiring within 30 days	Amber ⚠️ — "Expires in 15d".
Expired	Red ⛔, name struck through — "Expired 5d ago".
Warnings are also pushed as notifications at 30, 20, 10, 5 and 3 days, and again on the day it lapses — at which point the holder's managers are told too. See Notifications for the scheduled job that drives this.

Renewing a certification
To renew, add the certification again with the new expiry date and remove the old entry — a quick two-step that also leaves the lapsed one visible until you clear it.
Features in Depth
21Equipment in Depth
Assets & Labels covers what an equipment record holds. This chapter covers the four things attached to it that make the register operationally useful rather than merely a list.

Bill of materials
A BOM links a machine to the parts that fit it, with quantities. Built from the equipment record — expand a machine, open BOM, and add parts from the inventory master.

The value is at 3am, not at commissioning time. With a BOM in place:

A technician sees which parts fit this machine, instead of searching a catalogue of thousands by guesswork.
Ordering the wrong-but-similar bearing becomes much less likely.
The parts most worth stocking are visible — anything on the BOM of a criticality-A machine.
Build it as you go
You do not need a complete BOM on day one. A practical approach: whenever a technician consumes a part on a machine, add it to that machine's BOM. Within a few months each BOM covers exactly the parts that actually fail — the useful subset — and it built itself from real work rather than a data-entry project.
Documents and manuals
Files attach to a machine: OEM manuals, wiring diagrams, safety SOPs, calibration certificates. Upload from the equipment record or the Documents Management tile on the Admin Panel; each carries a title and a document type.

The point is that the manual is on the machine's record rather than on a shared drive nobody can find at 3am. Equipment also has a sop_link field for a procedure hosted elsewhere, and a loto_protocol free-text field for the lock-out/tag-out steps — worth filling in even when nothing else is.

How attachments are served
Documents are served directly by the web server with generated, non-guessable filenames — fast and simple, ideal for manuals and drawings. If a deployment needs every download to require a login, Hardening shows how to route uploads through a session check.
UUID rules
Every asset carries an asset_uuid — the identifier printed on its label and matched by the scanner. The UUID Configurator, on the equipment vault page, defines how those identifiers are generated per equipment category.

Setting	Does	Example
Category	Which equipment category the rule applies to	Mechanical
Prefix	Leading text	MCH-
Serial length	Zero-padded width of the counter	4 → 0007
Current serial	Next number to issue — increments automatically	3
Random chars	Extra random characters appended	0
Char type	Numeric or alphanumeric for the random part	ALPHANUMERIC
A rule of MCH- with a 4-digit serial yields MCH-0001, MCH-0002. Categorised identifiers mean a scanned or spoken code tells you what kind of machine it is before you look it up — which matters over a radio.

Label & printer setup
The Label & Printer Setup modal on the equipment vault page configures physical printing. Settings are stored in app_settings under EquipmentLabels and gated by manage_equipment — the people who own the asset register own its labels.

Group	Settings
Method	Browser sheet (any printer), one label per page, or direct to a Zebra.
Symbology	QR code or DataMatrix. DataMatrix is denser and survives small, oily or curved surfaces better.
Label	Width and height in mm — default 50.8 × 25.4 (2″ × 1″).
Page	Preset (A4/Letter) or custom width and height, plus margin.
Grid	Horizontal and vertical gaps, so labels line up with off-the-shelf sheet stock.
Zebra	Printer IP and port (9100), DPI (203/300), darkness, print speed.
Fields	Which of UUID, serial and brand/model appear as human-readable text beside the code.
Select machines individually or with select-all, then print as a batch — commissioning a line does not mean printing labels one at a time. A preview renders before anything is sent to a printer.

Codes are generated locally
Both symbologies render on your own hardware — a vendored barcode library in the browser, or native ZPL commands on the Zebra. Nothing is sent to an external service to produce a label, which is what makes this work on an isolated plant network.
Features in Depth
22Configurators
WCC has no single settings tree. Configuration lives next to the feature it configures, gated by that feature's permission — so the people who own a process own its rules, rather than everything funnelling through a general administrator. This chapter is the index of where each configurator actually lives.

The placement rule
Procurement approval policy is edited on the Purchase Orders page and gated by approve_purchase_orders — not by manage_settings. Putting it in a generic settings page would mean anyone who can change settings can change spending limits, which is not the same group of people.
Registration configurator
User Management → Registration Configurator. Controls which fields appear when creating a user, and which are mandatory. Each field has three switches: enabled, required, and a display label you choose.

Field	Typical use
Full Name · Email · Status	Usually enabled and required.
Phone · Department	Enable where the plant tracks them.
Location / Workshop	Useful on multi-workshop sites.
Certifications / Skills	Free-text capture at creation time.
Notes	Anything else.
Turning a field off removes it from the creation form entirely, and it stops appearing as a column where the directory renders it. A shorter form gets filled in properly; a form with eight optional fields gets three of them completed.

Role presets
User Management → Role Presets. Edits the permission set attached to each of the six role levels, applying to everyone at that level.

Permissions are grouped (Tickets, Maintenance, Assets, Procurement, Reports, Admin) with a checkbox each. Per-user overrides are set separately on the individual user's row and take precedence — so you can grant one technician an extra permission without changing the role for everyone.

Level 5 is deliberately empty
"Custom Viewer" ships with zero permissions. It is the correct starting point for anyone whose access should be an explicit list — an external auditor, a contractor, a manager who needs statistics and nothing else. Start from nothing and add, rather than starting from a role and trying to subtract.
KPI targets
Admin Panel → KPI Targets. Sets the dashed target lines on the trend charts, in one of two modes:

Static Baseline — fixed numbers you type, shown in every month. Use when held to a contractual or management target.
Dynamic (3-month rolling) — each month's target is computed from the three months immediately before it. The typed values are ignored except as a fallback when that window has no data.
Selecting Dynamic disables the number fields and reveals a ? that opens the exact formulas. Targets are MTTA and MTTR in minutes (lower is better) and MTBF in hours (higher is better).

Operational calendar
Admin Panel → System Settings. Two settings that change how time is measured:

Session Lockout Timer — global idle timeout in minutes. Users may set a shorter personal value in their profile; they cannot set a longer one.
Plant Holidays — a JSON array of YYYY-MM-DD dates, excluded entirely from downtime calculations.
Why holidays matter to your numbers
A fault reported at 16:00 on the day before a shutdown and fixed on the first morning back did not cost a week of production. Without the holiday list, MDT and MTTA count every one of those closed days as downtime, and your reliability figures are worse than reality by however long the plant was shut.
Procurement workflow
Purchase Orders → ⚙ Workflow. Gated by approve_purchase_orders. Two settings decide how requisitions are routed:

Setting	Effect
Workflow enabled = off	Every requisition auto-approves on submit and goes straight to Issued.
Auto-approve limit > 0	Requisitions at or under the limit auto-approve; larger ones wait for a human.
Enabled, no limit	Everything waits for approval.
Auto-approvals are logged with the reason, so the audit trail never has an unexplained gap where a decision should be.

Other configurators
Configurator	Where	Chapter
Skill Configurator	User Management	Skills & Proficiencies
UUID rules	Equipment Vault	Equipment in Depth
Label & Printer Setup	Equipment Vault	Equipment in Depth
PM Configurator · PM Checklists	Admin Panel	Work Orders & PM
Production Lines	Admin Panel	Assets & Labels
Department budgets	Department Management	Procurement
Features in Depth
23Self-Service
my_profile.php is reachable by every logged-in user regardless of role — it carries no permission gate, deliberately, because everyone owns their own account. It is also the one screen a technician is likely to open by choice rather than because a job sent them there.

Your performance dashboard
Self-Service Dashboard
Your personal performance stats and recent activity.
Figure	Counts
Interventions	Closed intervention records filed under your name.
Avg Wrench Time	Your mean hands-on repair duration, in minutes.
Tickets Reported	Faults you raised.
Tickets Closed Out	Faults you signed off.
Beneath these sit your active work orders — anything Scheduled or In Progress assigned to you, with its date — and a recent activity log of your last five interventions.

Why these numbers can look wrong, and why they no longer are
Intervention records store a person's name rather than their user ID, and older records hold the username where newer ones hold the display name. Every figure here therefore matches both spellings. A profile that previously showed zero interventions for a technician whose work was plainly in the database was this exact problem — one spelling was being matched, and half the history was invisible.
Your skills
Both systems appear here, side by side — see Skills & Proficiencies for how they differ.

Gamified Proficiencies shows a chip per mapped equipment category you have worked on: tier medal, category icon, proficiency name, hours, and the distance to the next tier ("27h to Expert 💎"). The ❓ opens the threshold table. If your hours are all on unmapped categories, it says so rather than showing an empty box — the fix is an administrator adding the mapping, and the message says that.

Skills & Certifications is self-service: add a certification with an optional expiry date, or remove one. Expiry state is colour-coded, and warnings arrive as notifications at 30, 20, 10, 5 and 3 days before the date.

Personal preferences
Preference	Scope	Notes
Profile details
name, email, phone, department	Account	Self-service. Role, status and badge are administrator-only.
Password	Account	Requires the current password. Nobody, including administrators, can read the existing one.
Session timeout	Account	Override the global default with a shorter personal value. Useful on a shared terminal.
Interface language	Account	Stored as users.locale. Thirty-four packs ship with the product; groups are equal (no “high impact” tier). Applies after re-login / session rebuild.
Theme	Browser	Light or dark, from the sidebar footer. Applied before first paint so there is no flash.
Animated background	Browser	Switch the WebGL ribbon off. See below.
Interface language
Every user can pick their language on My Profile. The value is stored on the account (users.locale), not only in the browser, so a shared terminal still shows the right pack after the next login. Packs live under lang/*.json and share the same key set as English (747 keys at soft launch). Incomplete packs are not used for day-one UI groups — all shipped locales are full parity with en.

Language groups in the picker are named by region only (for example South & Southeast Asia, Europe & Americas). There is no “high impact” ranking; every language is treated equally in the UI.

Turning off the animated background
My Profile → Visual Preferences. The setting is stored per browser (localStorage), not on the account — deliberately, because it is about the machine you are sitting at, not who you are. An ageing shop-floor PC can run without it while the same user gets the full effect on their office desktop. With it off, no canvas is created at all, so it costs nothing.

It also disables itself when the tab is hidden, honours prefers-reduced-motion, and never appears on hardware without WebGL.
Personal timeout only shortens
You can set a stricter idle timeout than the plant default, not a longer one. If your administrator sets 30 minutes, you cannot give yourself four hours — the shared-terminal risk is the reason the global setting exists.
Analysis
24KPIs & Reporting
The KPI dashboard (_rpt/statistics.php) is where recorded work becomes an argument for budget. Nothing on it is entered by hand — every figure is derived from tickets and their action records.

What each KPI means
KPI Dashboard
The main KPI tracking dashboard and trend charts.
Metric	Question it answers	Direction
MTTA
Mean Time To Acknowledge	How long between a fault being reported and somebody starting on it? (Response time.)	Lower is better
MTTR
Mean Time To Repair	Once work starts, how long until the machine is back — the elapsed repair window.	Lower is better
Repair Labour	Hands-on technician effort per repair. Parallel work counts fully — this is workload, not a clock.	Context
MDT
Mean Down Time	Total time from report to resolution — what production actually lost. MDT = MTTA + MTTR.	Lower is better
MTBF
Mean Time Between Failures	Running time between breakdowns — reported two ways: whole-plant and per machine.	Higher is better
Ghost Time	MTTR minus hands-on repair — idle inside the repair: waiting for parts (incl. explicit On Hold), travel, handover.	Lower is better
Ghost Time is the one to look at
MTTR is a statement about your technicians. Ghost Time is a statement about your organisation — stores, logistics, handover. A plant with excellent MTTR and terrible Ghost Time does not have a maintenance problem; it has a supply and scheduling problem, and hiring more technicians will not fix it.
The actual formulas
MTTA   = Σ shift-adjusted(report → first action start)  ÷ interventions
MTTR   = Σ shift-adjusted(first action start → last end) ÷ interventions   (= MDT − MTTA)
MDT    = Σ shift-adjusted(report → last action end)      ÷ interventions   (= MTTA + MTTR)
Active = Σ shift-adjusted(union of action intervals)     ÷ interventions   (parallel-safe)
Ghost  = MTTR − Active                                                     (idle within the repair)
Labour = Σ every action's own duration                   ÷ interventions   (effort / workload)

Plant MTBF     = total fleet uptime ÷ total failures
Per-asset MTBF = that asset's uptime ÷ its failures
Availability   = (scheduled − downtime) ÷ scheduled       (fleet-wide; failed-only on toggle)
Only closed tickets with both a start and an end time contribute. An open job is not a data point, and including it would flatter the numbers. Two clean identities fall out of the definitions and hold on the dashboard: MDT = MTTA + MTTR and MTTR = Active repair + Ghost.

Two MTBFs, on purpose. Per asset, MTBF is (scheduled minutes − that asset's downtime) ÷ its failures, with overlapping tickets on the same machine merged first so concurrent faults are not double-counted. Plant MTBF then rolls every machine up — including the ones that never failed — into one fleet-wide figure. The plant number answers "how reliable is the factory?"; the per-asset table answers "which machine is dragging it down?" They are deliberately different statistics.

Shift-adjusted downtime
A fault reported at 22:00 and fixed at 06:00 did not cost eight hours of production if the plant runs two shifts and was closed overnight. MTTA and MDT are therefore shift-adjusted: only scheduled operating time is counted, via inc/shift_calendar.php.

Plant holidays are configured in Admin Panel → System Settings → Operational Calendar as a JSON array of dates, and are excluded entirely.

MTTA, MTTR and MDT are all shift-adjusted — a repair that spans a night the plant does not run is not charged for hours the machine was never expected to work. Repair Labour is the one figure that is not a clock: it totals hands-on effort, so two technicians working the same hour count as two labour-hours. That separation is what lets Ghost Time isolate waiting from working.

What counts as a failure
Not every closed ticket is a breakdown. An inspection, a "no fault found", a changeover or a facilities request is downtime, but it is not a failure — and counting it as one quietly deflates MTBF. Every ticket therefore carries an event class, chosen at registration (it defaults to Failure, so existing data is unchanged until reclassified):

Failure / Breakdown and Induced / Secondary damage count toward MTBF by default.
Inspection, No Fault Found, Setup / Changeover and Request / Facilities do not.
Which classes count is set in Admin Panel → KPI Targets. The distinction applies only to the MTBF failure count: a non-failure ticket still contributes its downtime to MDT and Availability, because the machine really was stopped — it simply was not failing.

The population toggle
Response and repair times can be read two ways — across every repaired ticket, or across only genuine failures — so the dashboard has a "Response & repair times over: All repaired / Failures only" switch. It moves MTTA, MTTR, MDT, Repair Labour and Ghost between the two populations. Reliability (MTBF, Availability) is unaffected; it is already defined in terms of failures and downtime.
Targets: static and rolling
The dashed target lines on the trend chart come from one of two modes:

Static Baseline — fixed numbers you type in, shown in every month. Use when you are held to a contractual or management target.
Dynamic (3-month rolling) — each month's target is computed from the three months immediately before it, so the question becomes "are we better than we recently were?" It is a weighted average (summed minutes ÷ summed interventions), so a busy month counts more than a quiet one.
Exports and printing
Tickets and parts consumption export to CSV over the selected date range, and the dashboard has a print stylesheet that produces a clean report without the application chrome. Clicking a point on the trend chart opens a weekly breakdown for that month.

How to read the reliability figures
A few methodology notes, so the numbers mean what you expect:
MTTR is the elapsed repair window — response-to-resolution once work starts. The hands-on effort behind it is reported separately as Repair Labour, and the difference between the two is Ghost Time.
Ghost Time includes On Hold. When a ticket is explicitly paused (usually awaiting a part), that wait is a named slice of Ghost — so "we were slow" and "we were blocked waiting for stores" are never confused.
MTBF is measured against scheduled operating time, not the calendar — a machine is not "failing" while the plant is closed. A period with no failures shows a gap rather than a value, since there is no interval between failures to average.
Availability is fleet-wide by default — every machine, including the ones that never failed — with a one-click toggle to the focused "failed assets only" view. Plant MTBF is likewise rolled up across the whole fleet.
Operations
25Data Administration
Admin Panel → Data Administration holds the three operations that can destroy an installation: backup, restore and flush. It is gated by manage_settings, every action requires a CSRF token, and every action is written to the audit log.

Backup
Data Administration
The Data Administration center for backups, restores, and flushes.
A full mysqldump of the entire database — schema, data, routines and events — written to backups/ with a timestamped filename. You can download it or keep it on the server.

It backs up everything, discovered at run time. This matters: the tool it replaced dumped a hardcoded list of 15 tables, against a live schema of 40. Anyone restoring from one of those backups would have silently lost notifications, the audit log and role definitions — a disaster recovery plan that quietly does not work is worse than none, because you are relying on it.

Backups are protected by default
Because a full backup contains everything — including password hashes — backups/ ships denied to the web by .htaccess. Treat a downloaded dump with the same care as the database itself — it is the database.
Restore
Restore streams a .sql file into the MySQL client, either from a file you upload or one already in backups/. The on-disk route avoids PHP upload limits entirely, so large dumps are not a problem.

A fresh backup is taken automatically before the restore begins. If you restore the wrong file, the state you just replaced is still on disk.

Flush
Flush empties selected tables. They are presented in the four groups described in Database Schema, with live row counts, so you can see what you are about to lose.

Factory Reset pre-selects the transactional group — clears history, keeps the plant.
Reference, Config and System tables are individually selectable and marked as dangerous.
Foreign keys are disabled for the truncate and re-enabled after, so tables with dependents clear cleanly instead of failing halfway.
What certain tables cost you
Flushing users logs you out immediately — the login page will then seed a fresh default administrator. Flushing app_settings or role_definitions discards configuration and permissions. schema_migrations loses the record of which migrations have run, and the next --apply will try to run all of them again.
Safety rails
Rail	What it stops
Automatic pre-action backup	An irreversible mistake. Restore and flush both snapshot first.
Type-to-confirm (RESTORE / FLUSH)	A misplaced click. The button stays disabled until the word is typed exactly.
Table-name allow-list	Injection. Every name is checked against the live schema before it can appear in a statement.
CSRF + permission on every POST	Cross-site triggering and unauthorised use.
Audit entry per action	Silent destruction. data.backup, data.restore, data.flush.
Reserved for trusted operators on public installs
Backup, restore and flush are powerful by nature and are gated on manage_settings. For a public-facing installation, WCC's configuration flag can switch these operations off entirely, so a public demo can offer the full application while keeping the destructive tools out of reach. Restrict them to accounts you fully control, or disable them — your choice per deployment.
Operations
26Demo Data
An empty CMMS demonstrates nothing. Every screen shows "no records", the KPI dashboard is blank, and there is no way to tell a working system from a broken one. WCC ships a seeder that builds a complete, believable plant with nine months of operating history.

The seeder
php demo/demo_seed.php               # flush and seed
php demo/demo_seed.php --seed=42     # a different draw, same overall shape
Command line only, and destructive
demo_seed.php returns 403 over HTTP by design. It truncates tables; exposing it as a web endpoint would hand any visitor a database-wipe button. It also does not ask for confirmation — the CLI requirement is the confirmation.
It preserves role_definitions, app_settings and schema_migrations, so your permission model, configuration and migration state survive a reseed.

Every date is relative to now. Nothing is hardcoded, so "yesterday" is genuinely yesterday no matter when you run it. Re-run it before a demonstration and the data is fresh; leave it a month and it ages naturally rather than becoming obviously stale. Randomness is seeded deterministically, so the same command produces the same plant — useful for reproducible screenshots.

What it creates
Area	Contents
Plant	2 workshops, 6 production lines, 24 machines with real OEM names, criticality mix, PM intervals, warranty and EOL dates
People	11 users — one per role — with certifications, some expiring soon and some already lapsed
Stock	35 parts with locations and lead times; several at or below reorder point, one stocked out
Suppliers	8 vendors, 5 departments with allocated and consumed budget
History	~420 tickets across 9 months with full action logs, root causes and parts
Planned work	52 work orders in every state including overdue; 11 PM schedules, 2 overdue; 5 checklists
Procurement	33 purchase orders parked at all nine stepper stages, with line items and status history
Traceability	~340 ledger rows covering all three movement types; unread notifications; audit entries
The imperfections are deliberate
Overdue work orders, lapsed certifications, a stocked-out part, expired warranties — a plant where everything is green is not credible, and it hides exactly the features worth demonstrating. The alerts, the escalations and the red badges only exist because there is something wrong to point at.
Faults are matched to the machines they could plausibly happen to — a spindle overheat lands on a machining centre, never a palletiser — and technicians are given specialities, so their logged hours concentrate into recognisable proficiency tiers instead of scattering evenly across every category.

Re-seeding for a pitch
Back up first if the current data matters — Data Administration → Backup, or mysqldump.
Run php demo/demo_seed.php.
Sign in as a.rivera / Demo2026! and confirm the dashboard shows unread notifications and an overdue-work alert.
Take a backup of the seeded state as your demo baseline, so you can return to a known-good starting point after clicking around.
For a permanently public demo, run the seeder nightly from cron. Each run resets the instance to the same clean state, so whatever visitors did during the day is gone by morning.

Demo accounts share one password
All eleven use Demo2026!. That is a demonstration convenience and nothing else — never leave those accounts on a system holding real plant data.
Operations
27REST API v1
The REST API exists so machines and other applications can use WCC — the Android companion app is its main consumer. It enforces the same permission model as the web interface, against the same data.

Authentication
Two methods, both resolving to a real user account:

Method	How	For
API key	X-API-Key: <key>	Applications and integrations. The key is stored on the user row.
Basic auth	Standard Authorization: Basic	Quick testing with curl.
Session	Existing cookie	Falls back to the browser session when called from a logged-in page.
There is no anonymous access. Every call without valid credentials returns 401 — including the discovery endpoint, which lists resources only to an authenticated caller.

An API key is a password
It grants exactly the permissions of the user it belongs to, with no expiry and no second factor. Issue keys to purpose-built accounts holding only what that integration needs — never to an administrator account because it was convenient.
Resources
Clean URLs under /api/v1/:

Resource	Covers
/me	The calling user, with live stats — interventions, tickets closed and reported, average wrench time.
/tickets	Faults. List, read, create, update, delete.
/ticket-actions	Intervention records against tickets. Body field is action_taken (not a free-form notes column).
/work-orders	Planned work. Create with equip_id (equipment foreign key).
/equipment	The asset register. Supports ?asset_uuid= for exact scanner lookups and ?search= across name, UUID and model.
/toolings	Tooling register. CRUD with soft-delete. Filters: search, barcode, asset_tag, tooling_code, category, status, linked_equip_id. Nested: /{id}/bom, /{id}/documents. Perms view_toolings / manage_toolings. (Companion app still uses /api/companion/toolings.php — separate package.)
/production-lines	Plant topology.
/inventory	Parts and stock levels.
/vendors · /purchase-orders · /purchase-requests	Procurement. Vendor address column is vendor_address (write may accept address alias).
/users · /roles · /api-keys	Administration. Requires the corresponding admin permissions.
/stats · /audit	KPI figures and the audit trail.
/ai-context	A machine-readable description of the installation, for agent tooling.
Every response uses the same envelope, so clients need one parser:

{
  "success":   true,
  "data":      { ... },
  "message":   "",
  "timestamp": "2026-07-22T18:55:25+02:00"
}
Worked examples
# who am I, and what have I done?
curl -H "X-API-Key: $KEY" http://wcc.local/api/v1/me

# find a machine from a scanned label
curl -H "X-API-Key: $KEY" \
     "http://wcc.local/api/v1/equipment?asset_uuid=WCC-A1B2C3-0007"

# open work orders for one machine
curl -H "X-API-Key: $KEY" \
     "http://wcc.local/api/v1/work-orders?equip_id=7"

# register a fault
curl -X POST -H "X-API-Key: $KEY" -H "Content-Type: application/json" \
     -d '{"equip_id":7,"fault_desc":"Spindle overheat alarm","priority":"high"}' \
     http://wcc.local/api/v1/tickets
Ticket IDs are always server-allocated
A ticket_id supplied in a create request is ignored — it is the primary key, and a client-chosen value could collide with an existing ticket. The allocated ID comes back in the response; read it from there. If you need offline capture with client-generated identifiers, that requires a designed scheme rather than writing the field directly.
Look up tickets by TK-…, not by numeric row ids
Public identifiers look like TK-260728-004. GET /api/v1/tickets/123 returns 404 by design. Use GET /api/v1/tickets/TK-260728-004 (or list/filter endpoints).
People fields hold names
tech_name, pic and announced_by carry a person's name rather than a numeric ID, so records read naturally. When filtering by person on the server, wcc_tech_aliases() from inc/techident.php resolves every spelling a person's work may be filed under, so results are always complete.
The /me response includes both role (formatted, e.g. "L4 — Admin") and role_name (bare, e.g. "Admin"), both derived live from role_definitions. Since roles are editable, read the role from these fields rather than mapping level numbers to names in your client.

Operations
28Deployment
WCC deploys as files plus a database. This chapter covers the two situations that actually occur — a machine inside the plant, and a server anyone on the internet can reach — because they need very different decisions.

Local deployment
The normal case: a PC or small server on the plant network.

Copy the application into the web root.
Create the database, import the dump, point inc/db.php at it.
Sign in and change the seeded administrator password.
Confirm .htaccess is honoured — request /schema.sql and expect 403.
Decide about the demo data: keep it to explore, or clear it via Data Administration → Flush → Factory Reset before entering real work.
No TLS on the shop floor is normal
Plant intranets frequently have no certificate authority and no HTTPS. WCC works over plain HTTP: the session cookie's Secure flag switches itself on only when the request actually is TLS, so nothing breaks. The one real consequence is that browsers refuse camera access without HTTPS, which is why QR scanning lives in the companion app.
Start MariaDB before you use the app
Apache alone is not enough. If MySQL is down, login and every page that hits the database return errors (often HTTP 500). On XAMPP: start MySQL, wait until mysql -u root -e "SELECT 1" succeeds, then open the app. After any unclean shutdown or datadir rebuild, re-run the automated gates and keep a known-good dump under backups/pre_launch_*/workshop_db_*.sql. Never leave innodb_force_recovery set for day-to-day use.
Public hosting
Reachable from the internet is a different risk profile. Everything below is in addition to the local checklist.

A short go-live checklist takes an installation from "works on the network" to "safe on the open internet." Every item is standard practice for production web software:

Step	What it does for you
Create a scoped database user with rights to its own schema only	Contains the application to its own database, the standard principle of least privilege.
Enable HTTPS and redirect HTTP	Encrypts credentials and sessions in transit. WCC's session cookie automatically switches to Secure once TLS is present — no config needed.
Restrict Data Administration to trusted operators	Backup, restore and flush are powerful; on a public host, reserve them for accounts you fully control.
Set strong administrator passwords	The single most effective control on any admin-gated feature.
Tidy the demo accounts	Remove or re-password the shared-credential demo logins once real work begins.
Confirm display_errors is off (the default hardened setting)	Keeps technical detail in the log and out of the browser — already the shipped configuration.
One codebase for every environment
Where a public demo needs certain features held back, a single configuration flag disables them across the board, enforced server-side. There is no separate "public build" to maintain — the same code runs everywhere, so every installation benefits from every improvement.
Backups and cron
Backups live in backups/, which is denied over HTTP. That protects them from the web, not from disk failure — copy them off the machine. A backup stored only on the server it backs up is not a backup.

Scheduled jobs worth running:

Job	Cadence	Does
php cron_skill_expiry.php	daily	Warns holders at 30/20/10/5/3 days and on expiry. Safe to run repeatedly.
php cron_requisition.php	daily	Sweeps every part for low stock, catching anything event-driven reorder missed.
mysqldump or the backup tool	daily	Full database dump, copied off-machine.
php demo/demo_seed.php	nightly, demo instances only	Resets a public demo to a clean state.
On Windows use Task Scheduler with the full PHP path (C:\xampp\php\php.exe) and the script's absolute path. On Linux, standard crontab entries.

Test a restore before you need one
An untested backup is a hope. At least once, restore a dump into a scratch database and sign in. That is the only thing that proves the file is complete — and it is how the 15-table-versus-40-table gap described in Data Administration would have been caught years earlier.
Operations
29Troubleshooting
Symptoms that have actually occurred, with the cause and the fix. Most were diagnosed the slow way at least once, which is why they are written down.

Common issues
Symptom	Likely cause	Fix
A list shows only the first row or two, then the page ends oddly	A row threw mid-loop. Because notices become exceptions, everything after it never rendered — and the friendly error page sits below the fold.	Check the PHP error log for the real exception. Usually a column holding an unexpected shape, e.g. JSON that is a list of objects where the code expected scalars.
"Something went wrong. Unexpected error occurred."	Any uncaught throw.	The detail is in the error log, never on screen. If the log is empty, the log directory may not exist — error_log silently discards when it cannot write.
A dropdown is empty and blocks a form	The lookup table is empty, or its values do not match what the query filters on.	Check the exact values. team_directory.role_type must be the literal technical or production — job titles there silently empty the Person In Charge list.
A technician's stats read zero although their work is clearly recorded	Filtering on one spelling of their name.	Match both via wcc_tech_aliases(). Records carry the display name or the username depending on when they were written.
Two screens disagree about the same count	The rule is implemented twice and the copies have drifted.	Move it into inc/ and have both screens call the shared version, so they cannot disagree.
/schema.sql downloads instead of returning 403	Apache is ignoring .htaccess.	Set AllowOverride All for the directory and reload. Until then no file-level protection in this project is active.
A modal renders narrow and clips its contents	Its content needs more than the default 460px. (.modal-content is now width: 94vw capped by max-width: 460px, so raising max-width widens it — the old fixed-400px trap is gone.)	Raise max-width (e.g. max-width: 760px), or set width: min(760px, 94vw).
A CSS or JS change has no effect	Cached asset.	Bump WCC_UI_VERSION in inc/version.php; assets are requested with it as a cache-buster.
Locked out after repeated failed logins	The brute-force throttle: 10 failures per IP per 15 minutes.	Wait for the window, or clear the row for that IP from rate_limit.
A migration never applies	It references something that does not exist in the live schema.	Check the real table with DESCRIBE, not schema.sql — see Migrations on schema drift.
Auto-reorder is not raising requisitions	One of its guards is unmet.	Confirm auto_reorder is on, lifecycle is Active, stock is at or below minimum, a primary_vendor_id is set, and no open order already covers it.
Where to look
Source	Holds
PHP error log
C:\xampp\php\logs\php_error_log	Every uncaught exception with a stack trace. The first place to look, always. Create the directory if missing.
Apache error log	Server-level failures — the application never started.
audit_log table	Who changed what, with before/after values.
po_status_logs	Every purchase order transition and why.
inventory_ledger	Every stock movement and the job behind it.
Browser console	Front-end errors — a failed fetch to an api/ endpoint usually shows the JSON error.
How to verify a page really works
php -l checks syntax only — it does not resolve includes or execute anything, and it cannot tell you a page is broken. Load the page and assert on: the text "Something went wrong", an HTTP 5xx, output that ends before </html>, and new lines in the PHP error log during the request. Grepping for "Fatal error" proves nothing here — that string never appears.
FAQ
Can I run this on shared hosting?
Yes, if you get PHP 8, MySQL, and AllowOverride All. Without the last one the security rules are inert — verify before trusting it.

How do I add a permission?
Add it to PERMISSION_LABELS in rbac.php, grant it to the appropriate roles in the Role Presets editor, then gate pages with require_perm() and endpoints with require_api_perm(). Adding the label alone protects nothing.

Why is my new page visible to everyone?
It has no require_perm() call. The sidebar hides links, but a typed URL reaches the file directly.

Can I change the roles?
Yes — role_definitions is editable through the UI. Do not hardcode level numbers or names anywhere; use get_role_name() and permission checks.

How do I turn off the animated background?
Per user, in My Profile → Visual Preferences. The setting is stored per browser, so an old shop-floor PC can disable it without affecting anyone else.

Can I delete old tickets?
You can, via Data Administration, but consider not to. Ticket history is what makes MTBF, repeat-offender analysis and machine lifetime cost possible. Archive the database instead.

Operations
30AI Agent Handoff
WCC is designed to be handed to an AI AGENT. A context layer at the project root lets any AI AGENT acquire the same understanding of the codebase in one read, rather than rediscovering it file by file and guessing at the conventions in between.

This matters beyond novelty. Agents that infer conventions from whatever file they happen to open will invent a second way of doing everything. The context layer exists to make the project's actual decisions explicit and unmissable.

The bootstrap file
ai_agent.ini sits in the project root and is the entry point. It is plain INI — readable by a human, parseable by anything — and deliberately not specific to any one agent product.

Section	Tells the agent
[project]	Name, version, description, tech stack, creation date.
[initialization]	What to read first, and in what order. The single most important section.
[context_layer]	Where the deep context lives and how to regenerate it.
[architecture]	Folder conventions, module list, shared files.
[rbac]	Permission count, role meanings, where authority lives, the procurement duty split.
[data_model]	Schema sources, latest migration, and the known drift warnings.
[styling]	Token tiers, theming, cache-busting rule, what was removed and must not be resurrected.
[conventions]	Include style, path style, permission helpers, documentation duty.
[rules_for_agents]	Six numbered standing instructions.
[how_to_use_with_multiple_agents]	Running several agent sessions against one project without them diverging.
The most valuable entries are the warnings
[data_model] caution states that the live database is authoritative over schema.sql and names a migration that can never apply. [styling] removed records that Theme Lab was deleted and that a dormant column must not be revived without review. These stop an agent from confidently rebuilding something that was removed on purpose, or from trusting a file that has drifted — the two most expensive mistakes a fresh agent makes.
The context folder
_ai_ctxt/ holds the depth that will not fit in an INI file. Each document has one job:

File	Contains
AGENT_INSTRUCTIONS.md	Standing instructions — read before touching anything.
OVERVIEW.md	What the product is and who uses it.
ARCHITECTURE.md	Folder structure, shared infrastructure, dependency direction.
DATA_MODEL.md	Tables and relationships. Generated — see below.
KEY_FLOWS.md	Ticket lifecycle, work orders, procurement, inventory, notifications.
CONVENTIONS.md	Code style, naming, the design-system rules.
REST_API.md	API surface summary.
context.json · manifest.json	Machine-readable equivalents for agents that prefer structured input.
AGENTS.md in the project root is the short front door — the file several agent tools look for by convention. It points at everything above.

Not served over HTTP
_ai_ctxt/ carries a deny-all .htaccess. Its contents are architecture notes for whoever is working on the code, not application pages, and a full description of your system's internals is exactly what you do not hand to an anonymous visitor. Agents read these from disk, not over the network.
Keeping context fresh
Two scripts maintain the layer:

php _ai_ctxt/generate-context.php          # refresh DATA_MODEL.md from the schema
php _ai_ctxt/generate-context.php --live   # include live row counts and samples
php _ai_ctxt/print-init-summary.php        # copy-pasteable briefing for a new session
Run the generator after any schema change or significant refactor. Stale context is worse than none: an agent trusts it, acts on it, and produces work that matches a system you no longer have.

For live figures without reading files at all, the REST API exposes the same material: GET /api/v1/ai-context, optionally with ?section=DATA_MODEL or ?live=1.

Handing the project to a new agent
Point the agent at the project root and have it read ai_agent.ini.
Follow [initialization] read_order — AGENTS.md, then AGENT_INSTRUCTIONS.md, then the rest of _ai_ctxt/.
Run print-init-summary.php for a condensed briefing to paste into a session.
Re-run the generator first if the schema has moved since the files were last written.
For several agents working the same project simultaneously, each loads the same ai_agent.ini, so all start from identical context. Machine-specific settings go in an untracked ai_agent.local.ini loaded after the main file.

Working style for multiple agents
When more than one agent works the same project, keep implementation aligned as well as understanding: communicate changes, and prefer the shared helpers in inc/ over writing a new copy of a rule that already exists. The context layer keeps everyone's mental model in sync; these habits keep the code in sync.
Keep the facts current
ai_agent.ini holds a few hand-maintained facts — version number, latest migration, permission count — that no script regenerates. Update them in the same edit as the change they describe, along with the affected _ai_ctxt/ documents, so the next agent starts from an accurate picture
