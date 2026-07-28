<p>
    Most of WCC is tables — tickets, work orders, parts, orders, users, ledger entries. They
    all share the same interaction model, so learning it once applies everywhere. This
    chapter covers the parts that are not obvious from looking at them, particularly the
    filter tokens, which are the single most useful feature most users never discover.
</p>

<h3 id="drag-filter">Drag-to-filter tokens</h3>

<figure class="docs-figure">
    <img src="/img/docs/ticket_history.png" alt="Drag-to-filter">
    <figcaption>A filtered table view showing active search tokens and expanded rows.</figcaption>
</figure>

<p>
    The search box above a table is <strong>draggable</strong>. Drag it onto a column header
    and it becomes a filter scoped to that column.
</p>

<ol>
    <li>Type what you are looking for into the search box.</li>
    <li>Drag the box onto a column header — the header highlights as a drop target.</li>
    <li>The search becomes a <strong>token</strong>: a removable chip reading
        <code>Status: CLOSED</code>.</li>
    <li>The box returns to its place, empty and ready for the next one.</li>
</ol>

<p>
    Tokens are <strong>cumulative and AND-ed together</strong>. Three tokens means rows must
    match all three. This is how you answer a real question in a few seconds:
</p>

<div class="docs-note">
    <span class="t">Worked example</span>
    "Which high-priority faults on the Okuma did Sara close?"
    <br><br>
    Drag <code>Okuma</code> onto <strong>Equipment</strong> → drag <code>high</code> onto
    <strong>Priority</strong> → drag <code>Lindqvist</code> onto <strong>PIC</strong>.
    Three drags, no query language, and each token is removable independently with its ✖ so
    you can widen the question one step at a time.
</div>

<p>
    A 🔒 icon appears in the search box once a column is targeted — clicking it locks the
    current text into a token without dragging, which is quicker once you know the feature is
    there. Typing without dragging performs an ordinary global search across all columns.
</p>

<p>This works identically on twelve screens:</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Module</th><th>Pages</th></tr></thead>
    <tbody>
        <tr><td>Maintenance</td><td>Active Tickets, Work Orders</td></tr>
        <tr><td>Assets</td><td>Equipment, Equipment Vault, Toolings, Tooling Vault</td></tr>
        <tr><td>Logistics</td><td>Inventory, Inventory Ledger, Purchase Orders, Purchase Requests, Vendors, Vendor Vault</td></tr>
        <tr><td>Management</td><td>User Management, Users Directory</td></tr>
        <tr><td>Reports</td><td>Event History</td></tr>
    </tbody>
</table>
</div>

<h3 id="accordions">Expandable rows</h3>

<p>
    A row with a ❯ arrow expands in place. The detail panel varies by table, and is where
    most of the real content lives:
</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Table</th><th>Expanding a row shows</th></tr></thead>
    <tbody>
        <tr><td>Work Orders</td><td>Instructions, required parts with quantities, and the Takeover button</td></tr>
        <tr><td>Equipment / Toolings</td><td>BOM linked parts, attached manuals/documents, label payload summary</td></tr>
        <tr><td>User Management</td><td>Full profile, gamified proficiencies, certifications, and the complete permission matrix</td></tr>
        <tr><td>Inventory Ledger</td><td>The source document — the work order or purchase order behind the movement</td></tr>
        <tr><td>Purchase Orders</td><td>Line items, status history, attached documents</td></tr>
        <tr><td>Event History</td><td>The full intervention record: root cause, action taken, parts used</td></tr>
    </tbody>
</table>
</div>

<p>
    In the ledger, rows referencing a work order or purchase order are clickable through to
    that document — so "where did these twelve bearings go" resolves to the actual job in two
    clicks.
</p>

<h3 id="sorting-search">Sorting and search</h3>

<p>
    Sortable tables expose sort options through the column headers or a sort control, and the
    choice is carried in the URL (<code>?sort=…</code>) — so a sorted view can be bookmarked
    or sent to a colleague.
</p>

<p>
    Default ordering is chosen per table to put the important thing first rather than to be
    alphabetical. Work Orders leads with overdue, then upcoming, then completed. Purchase
    Orders leads with what needs action. Event History prefers recently closed tickets
    (<code>closed_at</code>) so a job you just signed off appears at the top.
</p>

<p>
    Global search and column filters update a live <strong>match count</strong>
    (<em>N of M</em> / <em>N records</em>) via the shared table UI helpers — so operators
    can tell immediately whether a criticality search (CLASS A/B/C) or a free-text filter
    actually narrowed the list.
</p>

<div class="docs-note">
    <span class="t">Tables adapt to the screen</span>
    Below roughly 640px, tables collapse into stacked cards — each row becomes a labelled
    block instead of scrolling sideways. Wide tables that stay tabular scroll inside their own
    container, never widening the page itself.
</div>

<h3 id="table-exports">Exports</h3>

<ul>
    <li><strong>Tickets CSV</strong> and <strong>Parts CSV</strong> from the KPI dashboard,
        honouring the selected date range.</li>
    <li><strong>Export CSV</strong> from User Management, for the current directory.</li>
    <li><strong>Print / PDF</strong> on the KPI dashboard, using a print stylesheet that
        drops the sidebar and page chrome and keeps the figures.</li>
</ul>

<p>
    Exports contain what the query returned, not what is on screen — so a filtered view and
    an export can differ. Set the date range you want before exporting.
</p>
