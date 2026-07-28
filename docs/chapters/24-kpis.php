<p>
    The KPI dashboard (<code>_rpt/statistics.php</code>) is where recorded work becomes an
    argument for budget. Nothing on it is entered by hand — every figure is derived from
    tickets and their action records.
</p>

<h3 id="kpi-definitions">What each KPI means</h3>

<figure class="docs-figure">
    <img src="/img/docs/kpi_dashboard.png" alt="KPI Dashboard">
    <figcaption>The main KPI tracking dashboard and trend charts.</figcaption>
</figure>

<div class="table-scroll">
<table>
    <thead><tr><th>Metric</th><th>Question it answers</th><th>Direction</th></tr></thead>
    <tbody>
        <tr><td><strong>MTTA</strong><br>Mean Time To Acknowledge</td><td>How long between a fault being reported and somebody starting on it? (Response time.)</td><td>Lower is better</td></tr>
        <tr><td><strong>MTTR</strong><br>Mean Time To Repair</td><td>Once work starts, how long until the machine is back — the elapsed repair window.</td><td>Lower is better</td></tr>
        <tr><td><strong>Repair Labour</strong></td><td>Hands-on technician effort per repair. Parallel work counts fully — this is workload, not a clock.</td><td>Context</td></tr>
        <tr><td><strong>MDT</strong><br>Mean Down Time</td><td>Total time from report to resolution — what production actually lost. <strong>MDT = MTTA + MTTR.</strong></td><td>Lower is better</td></tr>
        <tr><td><strong>MTBF</strong><br>Mean Time Between Failures</td><td>Running time between breakdowns — reported two ways: whole-plant and per machine.</td><td>Higher is better</td></tr>
        <tr><td><strong>Ghost Time</strong></td><td>MTTR minus hands-on repair — idle <em>inside</em> the repair: waiting for parts (incl. explicit <strong>On Hold</strong>), travel, handover.</td><td>Lower is better</td></tr>
    </tbody>
</table>
</div>

<div class="docs-note">
    <span class="t">Ghost Time is the one to look at</span>
    MTTR is a statement about your technicians. Ghost Time is a statement about your
    <em>organisation</em> — stores, logistics, handover. A plant with excellent MTTR and
    terrible Ghost Time does not have a maintenance problem; it has a supply and scheduling
    problem, and hiring more technicians will not fix it.
</div>

<h3 id="kpi-formulas">The actual formulas</h3>

<pre><code>MTTA   = Σ shift-adjusted(report → first action start)  ÷ interventions
MTTR   = Σ shift-adjusted(first action start → last end) ÷ interventions   (= MDT − MTTA)
MDT    = Σ shift-adjusted(report → last action end)      ÷ interventions   (= MTTA + MTTR)
Active = Σ shift-adjusted(union of action intervals)     ÷ interventions   (parallel-safe)
Ghost  = MTTR − Active                                                     (idle within the repair)
Labour = Σ every action's own duration                   ÷ interventions   (effort / workload)

Plant MTBF     = total fleet uptime ÷ total failures
Per-asset MTBF = that asset's uptime ÷ its failures
Availability   = (scheduled − downtime) ÷ scheduled       (fleet-wide; failed-only on toggle)</code></pre>

<p>
    Only <strong>closed</strong> tickets with both a start and an end time contribute. An
    open job is not a data point, and including it would flatter the numbers. Two clean
    identities fall out of the definitions and hold on the dashboard:
    <code>MDT = MTTA + MTTR</code> and <code>MTTR = Active&nbsp;repair + Ghost</code>.
</p>

<p>
    <strong>Two MTBFs, on purpose.</strong> Per asset, MTBF is
    <code>(scheduled minutes − that asset's downtime) ÷ its failures</code>, with overlapping
    tickets on the same machine merged first so concurrent faults are not double-counted.
    <strong>Plant MTBF</strong> then rolls every machine up — including the ones that never
    failed — into one fleet-wide figure. The plant number answers "how reliable is the
    factory?"; the per-asset table answers "which machine is dragging it down?" They are
    deliberately different statistics.
</p>

<h3 id="shift-model">Shift-adjusted downtime</h3>

<p>
    A fault reported at 22:00 and fixed at 06:00 did not cost eight hours of production if
    the plant runs two shifts and was closed overnight. MTTA and MDT are therefore
    <strong>shift-adjusted</strong>: only scheduled operating time is counted, via
    <code>inc/shift_calendar.php</code>.
</p>

<p>
    Plant holidays are configured in <em>Admin Panel → System Settings → Operational
    Calendar</em> as a JSON array of dates, and are excluded entirely.
</p>

<p>
    MTTA, MTTR and MDT are all shift-adjusted — a repair that spans a night the plant does not
    run is not charged for hours the machine was never expected to work. <strong>Repair
    Labour</strong> is the one figure that is not a clock: it totals hands-on effort, so two
    technicians working the same hour count as two labour-hours. That separation is what lets
    Ghost Time isolate <em>waiting</em> from <em>working</em>.
</p>

<h3 id="event-class">What counts as a failure</h3>

<p>
    Not every closed ticket is a breakdown. An inspection, a "no fault found", a changeover
    or a facilities request is <em>downtime</em>, but it is not a <em>failure</em> — and
    counting it as one quietly deflates MTBF. Every ticket therefore carries an
    <strong>event class</strong>, chosen at registration (it defaults to <em>Failure</em>, so
    existing data is unchanged until reclassified):
</p>

<ul>
    <li><strong>Failure / Breakdown</strong> and <strong>Induced / Secondary damage</strong>
        count toward MTBF by default.</li>
    <li><strong>Inspection</strong>, <strong>No Fault Found</strong>, <strong>Setup /
        Changeover</strong> and <strong>Request / Facilities</strong> do not.</li>
</ul>

<p>
    Which classes count is set in <em>Admin Panel → KPI Targets</em>. The distinction applies
    only to the <strong>MTBF failure count</strong>: a non-failure ticket still contributes its
    downtime to MDT and Availability, because the machine really was stopped — it simply was
    not <em>failing</em>.
</p>

<div class="docs-note">
    <span class="t">The population toggle</span>
    Response and repair times can be read two ways — across every repaired ticket, or across
    only genuine failures — so the dashboard has a <strong>"Response &amp; repair times over:
    All repaired / Failures only"</strong> switch. It moves MTTA, MTTR, MDT, Repair Labour and
    Ghost between the two populations. Reliability (MTBF, Availability) is unaffected; it is
    already defined in terms of failures and downtime.
</div>

<h3 id="kpi-targets">Targets: static and rolling</h3>

<p>The dashed target lines on the trend chart come from one of two modes:</p>

<ul>
    <li><strong>Static Baseline</strong> — fixed numbers you type in, shown in every month.
        Use when you are held to a contractual or management target.</li>
    <li><strong>Dynamic (3-month rolling)</strong> — each month's target is computed from the
        three months immediately before it, so the question becomes "are we better than we
        recently were?" It is a <em>weighted</em> average (summed minutes ÷ summed
        interventions), so a busy month counts more than a quiet one.</li>
</ul>

<h3 id="exports">Exports and printing</h3>

<p>
    Tickets and parts consumption export to CSV over the selected date range, and the
    dashboard has a print stylesheet that produces a clean report without the application
    chrome. Clicking a point on the trend chart opens a weekly breakdown for that month.
</p>

<div class="docs-note">
    <span class="t">How to read the reliability figures</span>
    A few methodology notes, so the numbers mean what you expect:
    <ul style="margin:8px 0 0 0;">
        <li><strong>MTTR is the elapsed repair window</strong> — response-to-resolution once
            work starts. The hands-on effort behind it is reported separately as <strong>Repair
            Labour</strong>, and the difference between the two is Ghost Time.</li>
        <li><strong>Ghost Time includes On&nbsp;Hold.</strong> When a ticket is explicitly
            paused (usually awaiting a part), that wait is a named slice of Ghost — so "we were
            slow" and "we were blocked waiting for stores" are never confused.</li>
        <li><strong>MTBF is measured against scheduled operating time</strong>, not the
            calendar — a machine is not "failing" while the plant is closed. A period with no
            failures shows a gap rather than a value, since there is no interval between
            failures to average.</li>
        <li><strong>Availability is fleet-wide by default</strong> — every machine, including
            the ones that never failed — with a one-click toggle to the focused "failed assets
            only" view. Plant MTBF is likewise rolled up across the whole fleet.</li>
    </ul>
</div>
