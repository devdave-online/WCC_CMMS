<p>
    Forty tables, all InnoDB, all <code>utf8mb4</code>. Every query in the application is a
    prepared statement — there is no ORM and no query builder, so what you read in a page is
    the SQL that runs.
</p>

<h3 id="schema-overview">The shape of the data</h3>

<p>The tables fall into four groups, and the distinction matters because it decides what
    is safe to clear (see <a href="#dataadmin">Data Administration</a>):</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Group</th><th>Contains</th><th>Clearing it means</th></tr></thead>
    <tbody>
        <tr><td><strong>Transactional</strong></td><td>Tickets, actions, work orders, purchase orders, ledger, notifications, audit</td><td>Losing history. The plant still exists.</td></tr>
        <tr><td><strong>Reference</strong></td><td>Equipment, parts, vendors, lines, workshops, checklists</td><td>Re-setting up the plant from scratch.</td></tr>
        <tr><td><strong>Config</strong></td><td>Users, roles, settings, registration and skill configuration</td><td>Breaking login and application behaviour.</td></tr>
        <tr><td><strong>System</strong></td><td><code>schema_migrations</code></td><td>Losing track of which migrations ran.</td></tr>
    </tbody>
</table>
</div>

<h3 id="schema-tickets">Tickets and actions</h3>

<p>
    The core of the system is two tables. <code>active_tickets</code> is the fault — what
    broke, on what, when, reported by whom, at what priority. <code>ticket_actions</code> is
    the work — who took it, when they started, when they finished, what they found and what
    they replaced.
</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Table</th><th>Key columns</th><th>Notes</th></tr></thead>
    <tbody>
        <tr>
            <td><code>active_tickets</code></td>
            <td><code>ticket_id</code> (PK, varchar), <code>equip_id</code>, <code>report_date</code>, <code>report_time</code>, <code>announced_by</code>, <code>pic</code>, <code>fault_desc</code>, <code>priority</code>, <code>status</code>, <code>closed_by</code></td>
            <td>The primary key is a human-readable string, not an integer — see <a href="#tickets">Ticket Lifecycle</a>. Status is <code>OPEN</code> / <code>PENDING</code> / <code>ESCALATED</code> / <code>CLOSED</code>. <code>closed_at</code> is set when the ticket leaves the active board so History can sort by close time.</td>
        </tr>
        <tr>
            <td><code>ticket_actions</code></td>
            <td><code>action_id</code>, <code>ticket_id</code>, <code>tech_name</code>, <code>action_start</code>, <code>action_end</code>, <code>fault_type</code>, <code>root_cause</code>, <code>action_taken</code>, <code>parts_used</code></td>
            <td><code>action_start</code>/<code>action_end</code> are the source of every repair-time metric in the system.</td>
        </tr>
        <tr>
            <td><code>ticket_comments</code></td>
            <td><code>ticket_id</code>, <code>user_name</code>, <code>comment_text</code></td>
            <td>Free discussion attached to a ticket.</td>
        </tr>
    </tbody>
</table>
</div>

<div class="docs-note">
    <span class="t">People are stored by name</span>
    <code>tech_name</code>, <code>pic</code>, <code>announced_by</code> and
    <code>closed_by</code> hold a person's name rather than a <code>user_id</code>, so records
    read naturally as people. When filtering by person, use <code>wcc_tech_aliases()</code>
    from <code>inc/techident.php</code>, which resolves every spelling a person's work may be
    filed under so results are always complete.
</div>

<h3 id="schema-assets">Assets and plant</h3>

<p>
    Physical structure is a three-level hierarchy: <code>workshops</code> contain
    <code>production_lines</code> contain <code>equipment</code>. Equipment can also sit
    outside a line entirely — compressors, chillers and cranes serve the whole site.
</p>

<p>
    <code>equipment</code> is the widest table in the schema (38 columns) because it carries
    the full asset record: OEM brand, model and serial; purchase date, PO value and vendor;
    warranty expiry and end-of-life; criticality A/B/C; PM intervals; LOTO protocol; and a
    JSON blob of technical details. <code>asset_uuid</code> is the identifier printed on the
    physical label and matched by the scanner.
</p>

<p>
    Supporting tables: <code>equipment_bom</code> (which parts fit this machine),
    <code>equipment_documents</code> (manuals and drawings), and <code>uuid_rules</code>
    (per-category asset ID generation patterns). Soft-delete uses <code>deleted_at</code> on
    equipment (and tooling); factory-health style counts exclude soft-deleted rows.
</p>

<p>
    Tooling is a parallel register: <code>toolings</code>, <code>tooling_bom</code>, and
    <code>tooling_documents</code>. It does not sit under equipment; permissions
    (<code>view_toolings</code> / <code>manage_toolings</code>) are independent. See
    <a href="#assets">Assets &amp; Labels</a> for vault vs ledger surfaces.
</p>

<h3 id="schema-stock">Inventory and ledger</h3>

<p>
    <code>inventory_parts</code> holds the parts master — stock level, minimum threshold,
    maximum, MOQ, unit cost, lead times, vendor, and physical location down to the bin.
</p>

<p>
    <code>inventory_ledger</code> is the important one. Every stock movement is an immutable
    row: <code>part_id</code>, <code>change_qty</code> (signed), <code>reason</code>,
    <code>reference_type</code> and <code>reference_id</code> pointing back at whatever
    caused it, plus the actor and timestamp. Three reasons occur in practice —
    <code>ticket_consume</code>, <code>wo_consume</code> and <code>po_receipt</code> — so any
    quantity can be traced to the specific job or delivery behind it.
</p>

<h3 id="schema-procure">Procurement</h3>

<div class="table-scroll">
<table>
    <thead><tr><th>Table</th><th>Holds</th></tr></thead>
    <tbody>
        <tr><td><code>purchase_orders</code></td><td>The order: number, vendor, department, total, status, approval level, emergency-bypass flag.</td></tr>
        <tr><td><code>po_items</code></td><td>Lines: part, ordered qty, received qty, unit price. Partial receipt is the difference between the two.</td></tr>
        <tr><td><code>po_status_logs</code></td><td>Every transition, with who and a note — the audit trail behind the stepper.</td></tr>
        <tr><td><code>po_documents</code></td><td>Generated requisitions and uploaded invoices.</td></tr>
        <tr><td><code>departments</code> · <code>department_budget_logs</code></td><td>Budget allocated and consumed, and the movements behind it.</td></tr>
        <tr><td><code>vendors_suppliers</code></td><td>Suppliers, contacts, payment terms, lead time, rating.</td></tr>
    </tbody>
</table>
</div>

<h3 id="schema-system">Users, RBAC and system</h3>

<div class="table-scroll">
<table>
    <thead><tr><th>Table</th><th>Holds</th></tr></thead>
    <tbody>
        <tr><td><code>users</code></td><td>Accounts. <code>role_level</code> plus optional <code>permissions_json</code> overrides; <code>api_key</code> for REST access; <code>badge_number</code> as the shop-floor identifier; <code>locale</code> for the UI language pack.</td></tr>
        <tr><td><code>role_definitions</code></td><td>The editable role → permission map. <strong>Authoritative</strong> — the hardcoded fallback in <code>rbac.php</code> only applies if this table is unreadable.</td></tr>
        <tr><td><code>user_skills</code></td><td>Manually granted certifications with an optional <code>expiry_date</code>.</td></tr>
        <tr><td><code>skill_automation_config</code></td><td>Maps an equipment category to a proficiency name and icon. Acts as the allow-list: an unmapped category earns nothing.</td></tr>
        <tr><td><code>app_settings</code></td><td>Key/value configuration grouped by category (SLA, KPI, Procurement, Security, EquipmentLabels).</td></tr>
        <tr><td><code>audit_log</code></td><td>Actor, action, entity, before/after JSON, notes.</td></tr>
        <tr><td><code>notifications</code></td><td>Per-user alerts with type, severity, link and read flag.</td></tr>
        <tr><td><code>rate_limit</code></td><td>Failed-login counters keyed on IP and endpoint.</td></tr>
    </tbody>
</table>
</div>

<div class="docs-note">
    <span class="t">Tables that are intentionally empty</span>
    Several tables exist and are never written by the current code:
    <code>eam_directory</code>, <code>ticket_parts_consumed</code>,
    <code>system_audit_logs</code>, <code>scheduled_reports</code> and
    <code>notification_broadcast</code>. They are schema left over from earlier designs,
    superseded by <code>inventory_ledger</code>, <code>audit_log</code> and
    <code>notifications</code>. They are harmless, but do not build against them expecting
    data to appear.
</div>
