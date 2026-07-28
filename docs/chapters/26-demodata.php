<p>
    An empty CMMS demonstrates nothing. Every screen shows "no records", the KPI dashboard
    is blank, and there is no way to tell a working system from a broken one. WCC ships a
    seeder that builds a complete, believable plant with nine months of operating history.
</p>

<h3 id="seeder">The seeder</h3>

<pre><code>php demo/demo_seed.php               # flush and seed
php demo/demo_seed.php --seed=42     # a different draw, same overall shape</code></pre>

<div class="docs-note danger">
    <span class="t">Command line only, and destructive</span>
    <code>demo_seed.php</code> returns <strong>403</strong> over HTTP by design. It truncates
    tables; exposing it as a web endpoint would hand any visitor a database-wipe button. It
    also does not ask for confirmation — the CLI requirement <em>is</em> the confirmation.
</div>

<p>
    It preserves <code>role_definitions</code>, <code>app_settings</code> and
    <code>schema_migrations</code>, so your permission model, configuration and migration
    state survive a reseed.
</p>

<p>
    <strong>Every date is relative to now.</strong> Nothing is hardcoded, so "yesterday" is
    genuinely yesterday no matter when you run it. Re-run it before a demonstration and the
    data is fresh; leave it a month and it ages naturally rather than becoming obviously
    stale. Randomness is seeded deterministically, so the same command produces the same
    plant — useful for reproducible screenshots.
</p>

<h3 id="what-it-makes">What it creates</h3>

<div class="table-scroll">
<table>
    <thead><tr><th>Area</th><th>Contents</th></tr></thead>
    <tbody>
        <tr><td>Plant</td><td>2 workshops, 6 production lines, 24 machines with real OEM names, criticality mix, PM intervals, warranty and EOL dates</td></tr>
        <tr><td>People</td><td>11 users — one per role — with certifications, some expiring soon and some already lapsed</td></tr>
        <tr><td>Stock</td><td>35 parts with locations and lead times; several at or below reorder point, one stocked out</td></tr>
        <tr><td>Suppliers</td><td>8 vendors, 5 departments with allocated and consumed budget</td></tr>
        <tr><td>History</td><td>~420 tickets across 9 months with full action logs, root causes and parts</td></tr>
        <tr><td>Planned work</td><td>52 work orders in every state including overdue; 11 PM schedules, 2 overdue; 5 checklists</td></tr>
        <tr><td>Procurement</td><td>33 purchase orders parked at <strong>all nine stepper stages</strong>, with line items and status history</td></tr>
        <tr><td>Traceability</td><td>~340 ledger rows covering all three movement types; unread notifications; audit entries</td></tr>
    </tbody>
</table>
</div>

<div class="docs-note">
    <span class="t">The imperfections are deliberate</span>
    Overdue work orders, lapsed certifications, a stocked-out part, expired warranties — a
    plant where everything is green is not credible, and it hides exactly the features worth
    demonstrating. The alerts, the escalations and the red badges only exist because there
    is something wrong to point at.
</div>

<p>
    Faults are matched to the machines they could plausibly happen to — a spindle overheat
    lands on a machining centre, never a palletiser — and technicians are given specialities,
    so their logged hours concentrate into recognisable proficiency tiers instead of
    scattering evenly across every category.
</p>

<h3 id="reseeding">Re-seeding for a pitch</h3>

<ol>
    <li><strong>Back up first</strong> if the current data matters —
        <em>Data Administration → Backup</em>, or <code>mysqldump</code>.</li>
    <li>Run <code>php demo/demo_seed.php</code>.</li>
    <li>Sign in as <code>a.rivera</code> / <code>Demo2026!</code> and confirm the dashboard
        shows unread notifications and an overdue-work alert.</li>
    <li>Take a backup of the seeded state as your demo baseline, so you can return to a
        known-good starting point after clicking around.</li>
</ol>

<p>
    For a permanently public demo, run the seeder nightly from cron. Each run resets the
    instance to the same clean state, so whatever visitors did during the day is gone by
    morning.
</p>

<div class="docs-note warn">
    <span class="t">Demo accounts share one password</span>
    All eleven use <code>Demo2026!</code>. That is a demonstration convenience and nothing
    else — never leave those accounts on a system holding real plant data.
</div>
