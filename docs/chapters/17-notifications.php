<p>
    Notifications exist so that work waiting on somebody actually reaches them. The design
    is deliberately unambitious — no websockets, no push service, no live polling — because
    a maintenance system's alerts need to be reliable, not instant.
</p>

<h3 id="notif-model">How notifications work</h3>

<p>
    Each notification is a row in <code>notifications</code>: recipient, type, message, link,
    severity and a read flag. The bell in the sidebar footer shows the unread count and opens
    a list; entries link straight to whatever needs attention.
</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Severity</th><th>Icon</th><th>Meaning</th></tr></thead>
    <tbody>
        <tr><td><code>info</code></td><td>ℹ️</td><td>Something happened you may want to know.</td></tr>
        <tr><td><code>warning</code></td><td>⚠️</td><td>Something needs attention before it becomes a problem.</td></tr>
        <tr><td><code>danger</code></td><td>⛔</td><td>Something is already a problem.</td></tr>
        <tr><td><code>success</code></td><td>✅</td><td>Something completed.</td></tr>
    </tbody>
</table>
</div>

<p>Two ways to send:</p>

<ul>
    <li><code>wcc_notify($user_id, …)</code> — one named person.</li>
    <li><code>wcc_notify_perm($permission, …)</code> — <strong>everyone holding a
        permission</strong>, optionally excluding the actor.</li>
    <li><code>wcc_notify_perms([$permA, $permB], …)</code> — everyone holding
        <em>any</em> of those permissions, <strong>one row per user</strong> (union / dedupe).</li>
</ul>

<p>
    The second is the one that matters. Alerts are addressed to a <em>capability</em>, not to
    a person: "whoever can approve purchases" rather than a name in a config file. Staff
    changes, holidays and role edits are handled automatically, and nothing is ever routed to
    someone who left last year.
</p>

<div class="docs-note">
    <span class="t">Counts refresh on page load, not by polling</span>
    No background timer, no open connection. On an intranet where people navigate constantly
    this is indistinguishable from live, and it costs nothing when a browser sits open on a
    workshop terminal all weekend. Notifications are a work queue, not a chat client.
</div>

<h3 id="notif-triggers">Every trigger</h3>

<div class="table-scroll">
<table>
    <thead><tr><th>Event</th><th>Goes to</th><th>Severity</th></tr></thead>
    <tbody>
        <tr><td>New ticket registered</td><td><code>takeover_tickets</code> holders (not the reporter). Web + REST create.</td><td>info · warning if high priority</td></tr>
        <tr><td>Ticket escalated</td><td>Union of <code>takeover_tickets</code> + <code>closeout_tickets</code> (one row per user; actor excluded)</td><td>warning</td></tr>
        <tr><td>Ticket put on hold</td><td>Same union as escalate</td><td>warning</td></tr>
        <tr><td>Ticket closed / quick resolve</td><td>Union of <code>view_history</code> + <code>view_statistics</code> (one row per user; actor excluded). Re-close does not re-notify.</td><td>success</td></tr>
        <tr><td>Work order assigned</td><td>The assignee</td><td>info</td></tr>
        <tr><td>Work order completed</td><td>Assignee (if not actor) + union of <code>view_work_orders</code> + <code>view_statistics</code></td><td>success</td></tr>
        <tr><td>Requisition needs approval</td><td><code>approve_purchase_orders</code> holders</td><td>warning</td></tr>
        <tr><td>Order awaiting fulfilment</td><td><code>fulfill_purchase_orders</code> holders</td><td>info</td></tr>
        <tr><td>Stock at or below minimum</td><td><code>manage_inventory</code> holders, plus approvers</td><td>warning · danger at zero</td></tr>
        <tr><td>Auto-reorder raised</td><td><code>manage_inventory</code> holders</td><td>info</td></tr>
        <tr><td>Certification expiring</td><td>The holder, at 30 / 20 / 10 / 5 / 3 days</td><td>warning · danger inside 5 days</td></tr>
        <tr><td>Certification expired</td><td>The holder <strong>and</strong> <code>manage_users</code> holders</td><td>danger</td></tr>
    </tbody>
</table>
</div>

<h3 id="notif-expiry">Certification expiry, in detail</h3>

<p>
    Most triggers fire from the action that caused them. Certification expiry is different —
    nothing "happens" when a date passes — so it runs as a scheduled job:
</p>

<pre><code>php cron_skill_expiry.php            # run daily
php cron_skill_expiry.php --dry-run  # show what would send, send nothing</code></pre>

<p>
    Each certification falls into exactly <strong>one</strong> bucket per run: the tightest
    horizon its remaining days fall inside. A certification added four days before expiry
    lands in the "5 day" bucket only — it does not fire 30, 20, 10 and 5 simultaneously.
</p>

<div class="docs-note">
    <span class="t">Safe to run twice</span>
    Sent buckets are recorded in the notification's own <code>type</code> field
    (<code>skill_exp:&lt;id&gt;:&lt;bucket&gt;</code>), so re-running the job — after
    downtime, or twice by accident — sends nothing extra. A reminder system that spams on
    retry gets muted, and a muted alert is worse than none.
</div>

<p>
    Once a certification has actually lapsed, the holder's managers are told as well. Someone
    working without a valid LOTO authorisation is not only their own problem.
</p>
