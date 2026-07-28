<p>
    A ticket is reactive — something broke. A work order is planned: a job scheduled in
    advance, usually preventive maintenance, sometimes an improvement or an inspection.
    Both consume time and parts, and both feed the same metrics.
</p>

<h3 id="wo-states">Work order states</h3>

<figure class="docs-figure">
    <img src="/img/docs/pm_modal.png" alt="Work order details">
    <figcaption>Viewing a work order in progress.</figcaption>
</figure>

<div class="table-scroll">
<table>
    <thead><tr><th>Status</th><th>Means</th></tr></thead>
    <tbody>
        <tr><td><code>Scheduled</code></td><td>Planned for a date. Nobody has started.</td></tr>
        <tr><td><code>In Progress</code></td><td>A technician has started; <code>started_at</code> is set.</td></tr>
        <tr><td><code>Completed</code></td><td>Finished, with completion time, who did it, and parts used.</td></tr>
        <tr><td><code>Missed</code></td><td>The date passed and it was never done. Deliberately distinct from Cancelled.</td></tr>
        <tr><td><code>Cancelled</code></td><td>Deliberately called off — asset off-line, job superseded.</td></tr>
    </tbody>
</table>
</div>

<div class="docs-note">
    <span class="t">"Overdue" means one thing everywhere</span>
    A work order is overdue when its scheduled date has passed <strong>and nobody has picked
    it up</strong>, or when it is explicitly <code>Missed</code>. Work that is
    <code>In Progress</code> is not overdue — somebody is on it right now. That single
    definition drives the dashboard badge, the red row highlighting and the sort order, so
    every screen agrees on what needs attention.
</div>

<h3 id="pm-schedules">Preventive schedules</h3>

<p>
    A PM schedule is a recurring template: a title, the equipment, an assigned technician, a
    checklist, an interval in days, and the next run date. When it comes due it produces a
    work order.
</p>

<p>
    Intervals are calendar-based, not meter-based — WCC does not ingest runtime hours from
    machines. Equipment carries both <code>pm_days_interval</code> and
    <code>pm_hours_interval</code> for reference, but scheduling is driven by days, which is
    what the overwhelming majority of plants actually run on.
</p>

<h3 id="checklists">Checklists</h3>

<p>
    A checklist is a reusable list of tasks, each with an <strong>expected duration in
    minutes</strong>. Attached to a work order, it becomes the technician's step list, and
    completion is recorded per task.
</p>

<div class="docs-note warn">
    <span class="t">The completion-time guard</span>
    If a checklist's tasks total 120 minutes and someone marks the whole thing complete 8
    minutes after starting, WCC refuses the closeout and says so. It is not accusing anyone
    of anything — it is refusing to record a physically impossible number, because a PM
    record nobody believes is worse than no PM record at all.
</div>

<p>
    Where enabled, technicians can attach photos to individual checklist tasks — useful for
    "show me the state of the belt before you changed it" evidence.
</p>

<h3 id="pm-calendar">The calendar</h3>

<figure class="docs-figure">
    <img src="/img/docs/pm_calendar.png" alt="PM Calendar">
    <figcaption>The Preventive Maintenance calendar overview.</figcaption>
</figure>

<p>
    <code>_maint/pm_calendar.php</code> shows the month with each work order colour-coded by
    urgency. On handheld screens it becomes an agenda list rather than a grid.
</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Marker</th><th>Meaning</th></tr></thead>
    <tbody>
        <tr><td>🔵 Blue</td><td>Upcoming, more than 7 days out</td></tr>
        <tr><td>🟢 Green</td><td>Upcoming, within 7 days</td></tr>
        <tr><td>🟡 Yellow</td><td>Scheduled today</td></tr>
        <tr><td>🟠 Orange</td><td>Overdue by 1–2 days</td></tr>
        <tr><td>🔴 Red (pulsing)</td><td>Overdue by 3 or more days</td></tr>
        <tr><td>✅</td><td>Completed</td></tr>
        <tr><td>❌</td><td>Cancelled or Missed</td></tr>
    </tbody>
</table>
</div>

<p>
    Two completion rates are shown beneath the grid: annual and current-month, each the
    proportion of scheduled work actually completed. The month figure is the one to watch —
    the annual number is slow to move and slow to warn you.
</p>

