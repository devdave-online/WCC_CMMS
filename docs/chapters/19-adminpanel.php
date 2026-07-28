<p>
    <code>_mgmt/admin_panel.php</code> is the hub for everything administrative. Rather than
    burying configuration in a settings tree, it presents thirteen tiles — some open a
    configurator in place, some navigate to a full management page.
</p>

<h3 id="tile-board">The tile board</h3>

<figure class="docs-figure">
    <img src="/img/docs/admin_panel.png" alt="Admin Panel">
    <figcaption>The central administrative tile board.</figcaption>
</figure>

<p>
    Tiles are filtered by <strong>your</strong> permissions, so two administrators can see
    different boards. Each tile is one of two kinds:
</p>

<ul>
    <li><strong>Modal tiles</strong> open a configurator over the panel. You stay on the
        page — useful for quick configuration that does not deserve a full navigation.</li>
    <li><strong>Link tiles</strong> navigate to a dedicated management page, each of which
        has a "Return to Admin Panel" control.</li>
</ul>

<h3 id="tile-reorder">Rearranging your panel</h3>

<p>
    The tile order is <strong>personal</strong>. An administrator who spends their week in
    procurement should not have to scroll past PM checklists every time.
</p>

<ol>
    <li>Click <strong>Edit Layout</strong>. Tiles become draggable.</li>
    <li>Drag them into the order you want.</li>
    <li>Click <strong>Save</strong>. The order is written to your user record
        (<code>users.admin_layout_json</code>).</li>
    <li><strong>Reset</strong> discards your arrangement and returns to the default.</li>
</ol>

<div class="docs-note">
    <span class="t">Per user, not per installation</span>
    Your arrangement affects nobody else. It survives logout and follows you to another
    browser, because it lives on the account rather than in local storage. A tile you gain
    access to later appears in the default position rather than being lost.
</div>

<h3 id="tile-inventory">What every tile does</h3>

<div class="table-scroll">
<table>
    <thead><tr><th>Tile</th><th>Kind</th><th>What it is for</th></tr></thead>
    <tbody>
        <tr><td>👥 <strong>User Management</strong></td><td>page</td><td>Accounts, roles, per-user permission overrides, certifications, password resets, CSV export.</td></tr>
        <tr><td>⚙️ <strong>Enclosed Setup Vault</strong></td><td>page</td><td>The equipment master: full asset records, BOM, documents, UUID rules, label and printer setup.</td></tr>
        <tr><td>🏢 <strong>Vendor Management</strong></td><td>page</td><td>Suppliers, contacts, payment terms, lead times, ratings.</td></tr>
        <tr><td>🏦 <strong>Department Management</strong></td><td>page</td><td>Departments, budget allocation, consumption history.</td></tr>
        <tr><td>📦 <strong>Add Inventory Part</strong></td><td>modal</td><td>Register a spare part without leaving the panel — code, thresholds, cost, vendor, location.</td></tr>
        <tr><td>📜 <strong>Inventory Audit Log</strong></td><td>page</td><td>Every stock movement with its source document.</td></tr>
        <tr><td>🩺 <strong>Inventory Health</strong></td><td>modal</td><td>The stock-status warning band and per-part lifecycle (Active / Phasing Out / Obsolete) — drives the badges on the Inventory page. See <a href="#stock-status">Inventory</a>.</td></tr>
        <tr><td>🛒 <strong>PR / PO Management</strong></td><td>page</td><td>The procurement engine: requisitions, approval, fulfilment, receipt.</td></tr>
        <tr><td>🏭 <strong>Production Lines</strong></td><td>modal</td><td>Create workshops and production lines — the plant hierarchy.</td></tr>
        <tr><td>🗓️ <strong>PM Configurator</strong></td><td>modal</td><td>Create a preventive schedule: equipment, interval, checklist, required parts, first run date.</td></tr>
        <tr><td>🛠️ <strong>Ad-Hoc Work Order</strong></td><td>modal</td><td>One-off planned job that is not part of a recurring schedule.</td></tr>
        <tr><td>📄 <strong>Documents Management</strong></td><td>modal</td><td>Upload safety SOPs and manuals against equipment.</td></tr>
        <tr><td>📈 <strong>KPI Targets</strong></td><td>modal</td><td>Static or rolling targets for MTTA, MTTR and MTBF. See <a href="#cfg-kpi">Configurators</a>.</td></tr>
        <tr><td>✅ <strong>PM Checklists</strong></td><td>modal</td><td>Reusable task lists with expected durations, attachable to schedules.</td></tr>
    </tbody>
</table>
</div>

<p>
    Two further administrative pages are reached from the sidebar rather than a tile:
    <strong>System Settings</strong> (session timeout, plant holidays) and
    <strong>Data Administration</strong> (backup, restore, flush — see
    <a href="#dataadmin">Data Administration</a>).
</p>

<div class="docs-note warn">
    <span class="t">A tile is not a permission</span>
    Tiles are filtered by permission, but the protection is the <code>require_perm()</code>
    call on the destination page — not the absence of a tile. Someone who knows the URL
    reaches the same gate. Never treat "they cannot see the tile" as access control.
</div>
