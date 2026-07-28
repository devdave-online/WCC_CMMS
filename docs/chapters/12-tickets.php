<p>
    A ticket is one fault on one machine, from the moment somebody notices it to the moment
    the line runs again. It is the busiest object in the system and the source of nearly
    every metric, so this chapter is worth reading closely.
</p>

<h3 id="ticket-states">The state machine</h3>

<pre><code>  OPEN ──────────► PENDING ──────────► CLOSED
  reported         taken over,          work finished,
  nobody on it     work underway        record complete</code></pre>

<div class="table-scroll">
<table>
    <thead><tr><th>State</th><th>Means</th><th>Who moves it on</th></tr></thead>
    <tbody>
        <tr><td><code>OPEN</code></td><td>Reported, nobody has taken it. <code>pic</code> may be a suggestion; no clock is running.</td><td>Anyone with <code>takeover_tickets</code>.</td></tr>
        <tr><td><code>PENDING</code></td><td>A technician owns it. <code>action_start</code> is set — this is when repair time begins accruing.</td><td>The owning technician, or someone with <code>closeout_tickets</code>.</td></tr>
        <tr><td><code>CLOSED</code></td><td>Finished. <code>action_end</code>, root cause, work done and parts consumed are all recorded.</td><td>Nobody — closed tickets are history and are read, not edited.</td></tr>
    </tbody>
</table>
</div>

<h3 id="registering">Registering an event</h3>

<figure class="docs-figure">
    <img src="/img/docs/ticket_open.png" alt="Registering an event">
    <figcaption>Registering a new ticket (OPEN state) by searching for the equipment.</figcaption>
</figure>

<p>
    <code>register.php</code> is intentionally the shortest form in the application. Pick the
    machine, describe the fault, set a priority and an <strong>event type</strong>, name a
    person in charge.
</p>

<p>
    The <strong>event type</strong> defaults to <em>Failure</em> and rarely needs changing —
    but flagging an inspection, a no-fault-found or a facilities request keeps it out of the
    breakdown count so <a href="#event-class">MTBF stays honest</a>. It never affects downtime.
</p>

<ul>
    <li>The equipment search matches on name <em>and</em> asset UUID, so a scanned label
        resolves directly to the machine.</li>
    <li><strong>The reporter is taken from the session</strong> and cannot be set by the
        client — you cannot file a fault as somebody else.</li>
    <li>The person-in-charge list is drawn from <code>team_directory</code> where
        <code>role_type = 'technical'</code>.</li>
    <li>A repeat-fault warning appears if the same machine has recent similar events.</li>
</ul>

<div class="docs-note">
    <span class="t">Ticket IDs</span>
    Format is <code>TK-YYMMDD-NNN</code> — a per-day sequence, e.g. <code>TK-260722-014</code>.
    Compact, chronologically sortable, and readable over a radio. The ID is
    <strong>always allocated by the server</strong>; a client-supplied one is ignored, because
    <code>ticket_id</code> is the primary key and a chosen value could collide with an
    existing ticket. Allocation retries on contention, so simultaneous registrations from
    several terminals all succeed.
</div>

<h3 id="takeover">Takeover and Evil Maid locking</h3>

<figure class="docs-figure">
    <img src="/img/docs/ticket_pending.png" alt="Taking over a ticket">
    <figcaption>Taking over a ticket logs the intervention details and shifts it to PENDING.</figcaption>
</figure>

<p>
    Taking over a ticket stamps the technician's name and starts the clock. From that point
    the ticket is <strong>bound to that person</strong>: another user cannot close out work
    they did not do, even if they have the permission in general.
</p>

<p>
    This is called "Evil Maid" protection in the codebase, and it exists for a mundane
    reason: shop-floor terminals are shared and frequently left logged in. Without the lock,
    the intervention record would say whoever last touched the keyboard, and every
    per-technician metric would be fiction.
</p>

<div class="docs-note">
    <span class="t">People are identified by name</span>
    Intervention records carry the technician's display name, so history reads as people
    rather than login IDs — "Sara Lindqvist closed this", not "user 5". Reporting resolves
    every spelling a person's work may be filed under, so a technician's stats are always
    complete regardless of how any individual record was entered.
</div>

<h3 id="closeout">Closeout</h3>

<figure class="docs-figure">
    <img src="/img/docs/ticket_closed.png" alt="Closing out a ticket">
    <figcaption>Closing the ticket (CLOSED state) deducts consumed parts and archives the record.</figcaption>
</figure>

<p>Closeout is where the record becomes worth having. It captures:</p>

<ul>
    <li><strong>Fault type and root cause</strong> — what class of failure, and why.</li>
    <li><strong>Action taken</strong> — what was actually done.</li>
    <li><strong>Parts consumed</strong> — each one writes an <code>inventory_ledger</code>
        row referencing this ticket.</li>
    <li><strong>End time</strong> — closing the interval that defines repair duration.</li>
</ul>

<p>
    Two side effects fire automatically. Consuming a part that drops to its minimum may raise
    a purchase requisition (<a href="#inventory">Inventory</a>). The logged hours add to the
    technician's proficiency for that equipment category
    (<a href="#rbac">Roles &amp; Permissions</a> covers who; the profile page shows the tiers).
</p>

<div class="docs-note">
    <span class="t">Stock is never driven negative</span>
    If a technician records using more of a part than the system believes is on the shelf,
    the consumption is capped at the quantity on hand and the ledger records what was
    actually taken. A stock level that has gone negative is not information, it is a bug
    waiting to be discovered during a stock count.
</div>

<h3 id="ticket-history">History and repeat detection</h3>

<figure class="docs-figure">
    <img src="/img/docs/ticket_history.png" alt="Ticket History">
    <figcaption>The historical archive of all closed interventions.</figcaption>
</figure>

<p>
    Closed tickets move to <code>_rpt/history.php</code> — searchable and filterable, and the
    input to the "top repeat offenders" ranking on the KPI dashboard. A machine appearing
    there repeatedly is the system telling you that fixing the symptom is not working and the
    PM interval or the root cause needs attention.
</p>

<p>
    Nothing is deleted. Tickets are archived rather than removed, so a machine's full fault
    history remains available for as long as you keep the database.
</p>
