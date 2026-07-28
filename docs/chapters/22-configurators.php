<p>
    WCC has no single settings tree. Configuration lives next to the feature it configures,
    gated by that feature's permission — so the people who own a process own its rules,
    rather than everything funnelling through a general administrator. This chapter is the
    index of where each configurator actually lives.
</p>

<div class="docs-note">
    <span class="t">The placement rule</span>
    Procurement approval policy is edited on the Purchase Orders page and gated by
    <code>approve_purchase_orders</code> — <strong>not</strong> by
    <code>manage_settings</code>. Putting it in a generic settings page would mean anyone who
    can change settings can change spending limits, which is not the same group of people.
</div>

<h3 id="cfg-registration">Registration configurator</h3>

<p>
    <em>User Management → Registration Configurator.</em> Controls which fields appear when
    creating a user, and which are mandatory. Each field has three switches: enabled,
    required, and a display label you choose.
</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Field</th><th>Typical use</th></tr></thead>
    <tbody>
        <tr><td>Full Name · Email · Status</td><td>Usually enabled and required.</td></tr>
        <tr><td>Phone · Department</td><td>Enable where the plant tracks them.</td></tr>
        <tr><td>Location / Workshop</td><td>Useful on multi-workshop sites.</td></tr>
        <tr><td>Certifications / Skills</td><td>Free-text capture at creation time.</td></tr>
        <tr><td>Notes</td><td>Anything else.</td></tr>
    </tbody>
</table>
</div>

<p>
    Turning a field off removes it from the creation form entirely, and it stops appearing as
    a column where the directory renders it. A shorter form gets filled in properly; a form
    with eight optional fields gets three of them completed.
</p>

<h3 id="cfg-roles">Role presets</h3>

<p>
    <em>User Management → Role Presets.</em> Edits the permission set attached to each of the
    six role levels, applying to everyone at that level.
</p>

<p>
    Permissions are grouped (Tickets, Maintenance, Assets, Procurement, Reports, Admin) with
    a checkbox each. Per-user overrides are set separately on the individual user's row and
    take precedence — so you can grant one technician an extra permission without changing
    the role for everyone.
</p>

<div class="docs-note warn">
    <span class="t">Level 5 is deliberately empty</span>
    "Custom Viewer" ships with zero permissions. It is the correct starting point for anyone
    whose access should be an explicit list — an external auditor, a contractor, a manager who
    needs statistics and nothing else. Start from nothing and add, rather than starting from a
    role and trying to subtract.
</div>

<h3 id="cfg-kpi">KPI targets</h3>

<p>
    <em>Admin Panel → KPI Targets.</em> Sets the dashed target lines on the trend charts, in
    one of two modes:
</p>

<ul>
    <li><strong>Static Baseline</strong> — fixed numbers you type, shown in every month. Use
        when held to a contractual or management target.</li>
    <li><strong>Dynamic (3-month rolling)</strong> — each month's target is computed from the
        three months immediately before it. The typed values are ignored except as a fallback
        when that window has no data.</li>
    </ul>

<p>
    Selecting Dynamic disables the number fields and reveals a <strong>?</strong> that opens
    the exact formulas. Targets are MTTA and MTTR in minutes (lower is better) and MTBF in
    hours (higher is better).
</p>

<h3 id="cfg-calendar">Operational calendar</h3>

<p>
    <em>Admin Panel → System Settings.</em> Two settings that change how time is measured:
</p>

<ul>
    <li><strong>Session Lockout Timer</strong> — global idle timeout in minutes. Users may
        set a shorter personal value in their profile; they cannot set a longer one.</li>
    <li><strong>Plant Holidays</strong> — a JSON array of <code>YYYY-MM-DD</code> dates,
        excluded entirely from downtime calculations.</li>
</ul>

<div class="docs-note">
    <span class="t">Why holidays matter to your numbers</span>
    A fault reported at 16:00 on the day before a shutdown and fixed on the first morning
    back did not cost a week of production. Without the holiday list, MDT and MTTA count
    every one of those closed days as downtime, and your reliability figures are worse than
    reality by however long the plant was shut.
</div>

<h3 id="cfg-procurement">Procurement workflow</h3>

<p>
    <em>Purchase Orders → ⚙ Workflow.</em> Gated by <code>approve_purchase_orders</code>.
    Two settings decide how requisitions are routed:
</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Setting</th><th>Effect</th></tr></thead>
    <tbody>
        <tr><td><strong>Workflow enabled</strong> = off</td><td>Every requisition auto-approves on submit and goes straight to Issued.</td></tr>
        <tr><td><strong>Auto-approve limit</strong> &gt; 0</td><td>Requisitions at or under the limit auto-approve; larger ones wait for a human.</td></tr>
        <tr><td>Enabled, no limit</td><td>Everything waits for approval.</td></tr>
    </tbody>
</table>
</div>

<p>
    Auto-approvals are logged with the reason, so the audit trail never has an unexplained
    gap where a decision should be.
</p>

<h3 id="cfg-other">Other configurators</h3>

<div class="table-scroll">
<table>
    <thead><tr><th>Configurator</th><th>Where</th><th>Chapter</th></tr></thead>
    <tbody>
        <tr><td>Skill Configurator</td><td>User Management</td><td><a href="#skill-config">Skills &amp; Proficiencies</a></td></tr>
        <tr><td>UUID rules</td><td>Equipment Vault</td><td><a href="#eq-uuid">Equipment in Depth</a></td></tr>
        <tr><td>Label &amp; Printer Setup</td><td>Equipment Vault</td><td><a href="#eq-labelsetup">Equipment in Depth</a></td></tr>
        <tr><td>PM Configurator · PM Checklists</td><td>Admin Panel</td><td><a href="#workorders">Work Orders &amp; PM</a></td></tr>
        <tr><td>Production Lines</td><td>Admin Panel</td><td><a href="#hierarchy">Assets &amp; Labels</a></td></tr>
        <tr><td>Department budgets</td><td>Department Management</td><td><a href="#receipt">Procurement</a></td></tr>
    </tbody>
</table>
</div>
