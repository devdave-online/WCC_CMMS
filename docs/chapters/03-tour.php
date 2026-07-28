<p>
    This chapter walks the shortest path that touches every major part of the system: a
    machine breaks, somebody reports it, a technician fixes it consuming a part, and the
    numbers move. If you read one chapter before demonstrating WCC, read this one.
</p>

<h3 id="the-shell">The application shell</h3>

<p>Every signed-in page shares the same frame:</p>

<ul>
    <li>
        <strong>The sidebar</strong> is built from your permissions, not from a fixed menu.
        Two people can be looking at the same installation and see genuinely different
        navigation — a Storekeeper has no Tickets section at all. It collapses to icons and
        remembers that choice.
    </li>
    <li>
        <strong>The notification bell</strong> sits in the sidebar footer and shows a count
        of unread items. It refreshes on page load rather than polling; see
        <a href="#notifications">Notifications</a>.
    </li>
    <li>
        <strong>The theme toggle</strong> switches light and dark. The choice is stored per
        browser and applied before first paint, so there is no flash of the wrong theme.
    </li>
    <li>
        <strong>The animated background</strong> is a WebGL effect that can be turned off per
        user under <em>My Profile → Visual Preferences</em>. On machines without WebGL it
        simply never appears — nothing breaks.
    </li>
</ul>

<h3 id="first-ticket">Your first ticket, end to end</h3>

<p>
    <strong>1 · Register the event.</strong> From the hub, choose <em>Register Event</em>.
    Pick the machine — the search matches on name or asset UUID, so a scanned label finds it
    directly. Describe the fault, set a priority, and name the person in charge. The reporter
    is taken from your session and cannot be overridden.
</p>

<p>
    <strong>2 · A technician takes it over.</strong> The ticket appears on the Active
    Tickets board and in the bell of everyone who can take jobs on. Opening
    <em>Takeover</em> starts the clock. From this point the ticket is bound to that
    technician — another user cannot close out someone else's job.
</p>

<p>
    <strong>3 · The work is recorded.</strong> On closeout the technician records what was
    actually done, the fault type and root cause, and any parts consumed. Consuming a part
    writes an <code>inventory_ledger</code> row tied to this ticket, so months later the
    question "where did those twelve bearings go?" has an exact answer.
</p>

<p>
    <strong>4 · Stock may reorder itself.</strong> If that consumption dropped the part to
    its minimum and the part is marked for auto-reorder, a purchase requisition is raised
    automatically, through the same approval rules a human requisition would follow. See
    <a href="#inventory">Inventory</a>.
</p>

<p>
    <strong>5 · The numbers move.</strong> The closed ticket now contributes to MTTA, MTTR
    and downtime on the KPI dashboard, to that machine's failure history, and to the
    technician's proficiency hours. Nothing here is entered twice — the metrics are a
    consequence of the work being recorded, not a separate reporting exercise.
</p>

<div class="docs-note">
    <span class="t">The point of the walkthrough</span>
    One person recording one repair, once, produced: an auditable intervention record, a
    stock movement, possibly a purchase requisition, three updated KPIs and a skills
    increment. That compounding is the entire argument for the system.
</div>

<h3 id="demo-accounts">Demo accounts</h3>

<p>
    A demo database ships with a fictional plant — two workshops, six production lines,
    twenty-four machines and roughly nine months of history. It has one account per role, so
    you can see exactly how the system changes shape for different people. All share the
    password <code>Demo2026!</code>.
</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Sign in as</th><th>Role</th><th>What they can do</th></tr></thead>
    <tbody>
        <tr><td><code>a.rivera</code></td><td>Admin</td><td>Everything, including user management and data administration.</td></tr>
        <tr><td><code>p.nair</code></td><td>Supervisor</td><td>Close out work, approve purchases, read the KPI dashboard.</td></tr>
        <tr><td><code>j.okafor</code></td><td>Technician</td><td>Take over tickets and work orders, consume stock.</td></tr>
        <tr><td><code>r.silva</code></td><td>Operator</td><td>Report faults. Cannot take them on or see costs.</td></tr>
        <tr><td><code>h.bakker</code></td><td>Storekeeper</td><td>Fulfil purchase orders and receive goods — but never approve their cost.</td></tr>
        <tr><td><code>c.whitfield</code></td><td>Viewer</td><td>Read-only.</td></tr>
    </tbody>
</table>
</div>

<div class="docs-note">
    <span class="t">Worth demonstrating deliberately</span>
    Sign in as <code>h.bakker</code> and try to approve a purchase order. The Storekeeper can
    ship, receive and close orders but cannot approve the spend — the separation between
    "who authorises money" and "who moves goods" is enforced server-side, not merely hidden.
    That distinction is what auditors ask about.
</div>
