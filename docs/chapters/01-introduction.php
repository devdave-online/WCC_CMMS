<p style="font-size:1.15em; line-height:1.7;">
    <strong>WCC is a complete, enterprise-grade maintenance management system — and it is
    free.</strong> Not a trial. Not a crippled community edition with the useful features
    behind a paywall. The whole thing: full ticket lifecycle, preventive maintenance,
    asset management with printed QR labels, a real inventory ledger, end-to-end procurement
    with separation of duties, live KPIs, granular role-based access, and a REST API — yours
    to run on your own hardware, for as many users as you like, forever.
</p>

<p>
    Commercial CMMS platforms charge per seat per month. That pricing model quietly decides
    who is <em>allowed</em> to record work — and the answer is always "as few people as
    possible," because every login costs money. So the operator who spotted the fault, the
    storekeeper who has the part, and the supervisor who signs the purchase all end up
    sharing one account or working around the system. The record stops reflecting reality,
    and a maintenance system whose data you cannot trust is worse than a whiteboard.
</p>

<p>
    WCC removes the meter. Give everyone who touches the work an account, because seats cost
    nothing. That single decision is what makes the rest possible: genuinely fine-grained
    permissions so access does not mean access-to-everything, an interface fast enough to use
    with gloves on at 3am, and data that stays trustworthy with the whole shift entering it.
</p>

<h3 id="why-wcc">What you get</h3>

<div class="table-scroll">
<table>
    <thead><tr><th>Capability</th><th>What it means on the floor</th></tr></thead>
    <tbody>
        <tr><td><strong>Full ticket lifecycle</strong></td><td>Register → take over → work → close out → searchable history, with time measured at every step so your KPIs are real, not estimated.</td></tr>
        <tr><td><strong>Preventive maintenance</strong></td><td>Recurring schedules, reusable checklists with expected times, a colour-coded calendar, and automatic overdue detection.</td></tr>
        <tr><td><strong>Asset management + labels</strong></td><td>Deep equipment records with OEM, warranty and lifecycle data — and print QR or DataMatrix labels, fully offline, on a Zebra or any office printer.</td></tr>
        <tr><td><strong>Inventory with a real ledger</strong></td><td>Every stock movement is recorded and traceable back to the exact job that caused it. Parts reorder themselves when they hit their minimum.</td></tr>
        <tr><td><strong>End-to-end procurement</strong></td><td>Requisition → approval → storekeeper fulfilment → receipt → budget, with the person who authorises spend kept separate from the person who receives goods.</td></tr>
        <tr><td><strong>Live KPI dashboard</strong></td><td>MTTA, MTTR, MTBF, downtime and Ghost Time — computed from the work as it is recorded, with static or rolling targets.</td></tr>
        <tr><td><strong>Granular RBAC</strong></td><td>22 permissions across 6 editable roles, enforced on the server for every page and every API call.</td></tr>
        <tr><td><strong>Skills &amp; competence tracking</strong></td><td>Proficiencies earned automatically from logged work, plus certifications with expiry warnings.</td></tr>
        <tr><td><strong>REST API</strong></td><td>Key-authenticated, same permission model, ready for the mobile companion app or your own integrations.</td></tr>
    </tbody>
</table>
</div>

<h3 id="design-rules">Built on four principles</h3>

<p>Everything in WCC follows the same four rules, and it is worth knowing them because
    they are why the numbers can be trusted:</p>

<ol>
    <li>
        <strong>The record reflects what happened.</strong> Time is measured, not estimated —
        the clock starts when a technician takes a job and stops when they close it. Stock
        movements are written to a ledger as they occur. Every figure traces to an event.
    </li>
    <li>
        <strong>Permission is enforced on the server.</strong> Every action re-checks the
        caller's rights at the point it executes, through the web interface and the API alike.
    </li>
    <li>
        <strong>Speed on the floor beats completeness in the form.</strong> Required input is
        kept to what is genuinely required. A technician with a broken line will fill in a
        short form and skip a long one — so the forms are short.
    </li>
    <li>
        <strong>Nothing is guessed.</strong> When the system cannot determine something — no
        vendor for a part, no mapping for a category — it tells you, rather than substituting
        a plausible-looking value you would later have to unpick.
    </li>
</ol>

<h3 id="what-it-is-not">Scope, stated plainly</h3>

<p>A focused tool does a few things excellently rather than everything adequately. WCC is
    deliberately:</p>

<ul>
    <li><strong>A maintenance system, not an ERP.</strong> It handles maintenance procurement
        end to end; it is not your accounting ledger of record.</li>
    <li><strong>Calendar-and-checklist driven, not IoT or predictive-AI.</strong> No sensor
        ingestion, no failure-prediction model — because that is what the overwhelming
        majority of plants actually run on, reliably, today.</li>
    <li><strong>Single-organisation.</strong> One installation serves one company, with full
        support for multiple plants and workshops inside it.</li>
    <li><strong>Web-first, with a companion app for scanning.</strong> The interface adapts to
        handheld screens; camera-based QR scanning lives in a separate Android app, because a
        shop-floor intranet usually has no HTTPS and browsers require it for camera access.</li>
</ul>

<div class="docs-note">
    <span class="t">The licence, in plain terms</span>
    Apache 2.0 with the Commons Clause. Use it, modify it, and run it for your own operations
    indefinitely — including commercially. The single restriction: you may not sell the
    software itself or offer it as a paid hosted service. Run it for your plant, for free,
    for good.
</div>
