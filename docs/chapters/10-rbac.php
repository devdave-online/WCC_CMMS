<p>
    WCC has <strong>24 permissions</strong> across <strong>6 roles</strong>, and both are
    editable. Because seats cost nothing, everyone who touches the work can have an account —
    which only works if permissions are fine-grained enough that giving someone access does
    not give them everything.
</p>

<h3 id="perm-model">The permission model</h3>

<p>A user's rights are resolved in two steps:</p>

<ol>
    <li>Start from the permission set for their <code>role_level</code>, read from
        <code>role_definitions</code>.</li>
    <li>Merge any per-user overrides from <code>users.permissions_json</code> on top.</li>
</ol>

<p>
    So a role is a starting point, not a cage. You can grant one technician
    <code>approve_purchase_orders</code> without inventing a new role, and without promoting
    them past everything else that a higher role would carry.
</p>

<p>The 24 permissions, by group:</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Group</th><th>Permissions</th></tr></thead>
    <tbody>
        <tr><td>Tickets</td><td><code>view_tickets</code>, <code>create_tickets</code>, <code>takeover_tickets</code>, <code>closeout_tickets</code>, <code>view_history</code></td></tr>
        <tr><td>Maintenance</td><td><code>view_work_orders</code>, <code>manage_work_orders</code></td></tr>
        <tr><td>Assets</td><td><code>view_equipment</code>, <code>manage_equipment</code>, <code>view_toolings</code>, <code>manage_toolings</code>, <code>view_inventory</code>, <code>manage_inventory</code></td></tr>
        <tr><td>Procurement</td><td><code>view_vendors</code>, <code>manage_vendors</code>, <code>view_purchase_requests</code>, <code>create_purchase_requests</code>, <code>approve_purchase_orders</code>, <code>fulfill_purchase_orders</code></td></tr>
        <tr><td>Reports</td><td><code>view_statistics</code></td></tr>
        <tr><td>Admin</td><td><code>manage_users</code>, <code>manage_settings</code>, <code>reset_passwords</code>, <code>delete_users</code></td></tr>
    </tbody>
</table>
</div>

<div class="docs-note">
    <span class="t">Equipment and tooling are separate</span>
    Tooling used to ride on equipment permissions. It now has its own pair:
    <code>view_toolings</code> (registry ledger, BOM/docs APIs) and
    <code>manage_toolings</code> (tooling vault). Uncheck either box on a user or role-save
    role presets to flush access without touching equipment.
</div>

<h3 id="perm-matrix">The full matrix</h3>

<div class="table-scroll">
<table>
    <thead><tr><th>Level</th><th>Role</th><th>Permissions</th><th>Shape of the job</th></tr></thead>
    <tbody>
        <tr><td>1</td><td>Operator</td><td>4</td><td>Reports faults and reads history. Cannot take work on or see costs.</td></tr>
        <tr><td>2</td><td>Technician</td><td>10</td><td>Takes over and performs work, consumes stock, raises requisitions.</td></tr>
        <tr><td>3</td><td>Supervisor</td><td>14</td><td>Closes out work, approves purchase cost, reads the KPI dashboard.</td></tr>
        <tr><td>4</td><td>Admin</td><td>24</td><td>Everything, including user management and data administration.</td></tr>
        <tr><td>5</td><td>Custom Viewer</td><td>0</td><td>A deliberately empty base — grant exactly what an auditor or contractor needs, nothing more.</td></tr>
        <tr><td>6</td><td>Storekeeper</td><td>7</td><td>Fulfils purchase orders and receives goods. <strong>Cannot approve the spend.</strong></td></tr>
    </tbody>
</table>
</div>

<div class="docs-note">
    <span class="t">Why Storekeeper exists as its own role</span>
    Separating <code>fulfill_purchase_orders</code> from <code>approve_purchase_orders</code>
    means the person who authorises money is never the person who receives the goods. That
    separation of duties is the first thing an auditor looks for in a procurement process,
    and it is enforced server-side rather than by convention.
</div>

<h3 id="custom-roles">Custom roles and overrides</h3>

<p>
    <em>Admin Panel → User Management → Role Presets</em> edits the permission set of any
    role, applying to everyone at that level. Per-user overrides are set on the individual
    user's row, and take precedence.
</p>

<p>
    <strong>Custom Viewer</strong> (level 5) ships with zero permissions on purpose. It is
    the correct starting point for anyone whose access should be an explicit list — an
    external auditor, a contractor, a manager who needs statistics but nothing else.
</p>

<div class="docs-note warn">
    <span class="t">Role names are data, not constants</span>
    <code>role_definitions</code> is the authority, and it is editable. Read a role's name
    through <code>get_role_name()</code> rather than mapping level numbers to names in code —
    that way, when an administrator renames or re-scopes a role, every screen and API response
    reflects it immediately.
</div>

<h3 id="enforcement">Where it is enforced</h3>

<p>The same permission set is applied at four independent layers:</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Layer</th><th>Mechanism</th></tr></thead>
    <tbody>
        <tr><td>Navigation</td><td><code>nav.php</code> renders only what you can reach. <strong>Convenience, not security.</strong></td></tr>
        <tr><td>Page</td><td><code>require_perm('…')</code> at the top of the file. Typing the URL directly hits this.</td></tr>
        <tr><td>Action</td><td>Handlers re-check before mutating. A form that was rendered while you had rights is re-validated when it is submitted.</td></tr>
        <tr><td>API</td><td><code>require_api_perm('…')</code> in the REST layer, against the same permission set.</td></tr>
    </tbody>
</table>
</div>

<div class="docs-note danger">
    <span class="t">Hiding a control is not a control</span>
    Every layer above the page gate is presentation. If you add a page or an endpoint and
    forget <code>require_perm()</code> / <code>require_api_perm()</code>, it is reachable by
    every authenticated user regardless of what the sidebar shows. When reviewing a change,
    check the gate, not the menu.
</div>

<p>
    A denied page renders an Access Denied panel <em>with the sidebar intact</em>, at HTTP
    200 rather than 403. That is a deliberate UX choice — the user can navigate away instead
    of hitting a dead end — but it means automated checks must assert on the page content,
    not the status code.
</p>
