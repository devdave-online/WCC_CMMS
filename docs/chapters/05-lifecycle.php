<p>
    This chapter follows a single request from the browser to the rendered page, naming
    what runs at each step. It is the fastest way to understand where to put something new
    — or where to look when something misbehaves.
</p>

<h3 id="request-path">From URL to HTML</h3>

<ol>
    <li>
        <strong>Apache resolves the file.</strong> No front controller: <code>/_maint/work_orders.php</code>
        is that file. The one exception is <code>/api/v1/…</code>, where an
        <code>.htaccess</code> rewrite routes everything to <code>api/v1/index.php</code>
        so the REST API can have clean resource URLs.
    </li>
    <li>
        <strong><code>auth.php</code> runs first.</strong> It starts the hardened session,
        loads the error handler, sends no-cache headers, and redirects to the login page if
        there is no session. It also rebuilds the cached permission set if it is missing —
        which is what keeps a session valid across a code deploy.
    </li>
    <li>
        <strong><code>rbac.php</code> resolves rights.</strong> Permissions come from
        <code>role_definitions</code> for the user's role level, then any per-user overrides
        in <code>users.permissions_json</code> are merged on top. The result is cached in the
        session so the page does not re-query on every check.
    </li>
    <li>
        <strong><code>require_perm()</code> decides.</strong> Either the page continues, or
        it renders an Access Denied panel — with the sidebar still present, so the user can
        navigate away rather than hitting a dead end.
    </li>
    <li>
        <strong>The page queries, then renders.</strong> <code>inc/head.php</code> emits the
        document head, applies the saved theme before first paint, and opens
        <code>&lt;body&gt;</code>. <code>nav.php</code> draws the sidebar from the same
        permission set.
    </li>
</ol>

<h3 id="shared-infra">Shared infrastructure</h3>

<p>
    Everything in <code>inc/</code> is logic with no output. Each file owns exactly one
    concept — one definition of a rule, called from everywhere it applies — so the whole
    application stays consistent by construction.
</p>

<div class="table-scroll">
<table>
    <thead><tr><th>File</th><th>Owns</th></tr></thead>
    <tbody>
        <tr><td><code>db.php</code></td><td>The single PDO connection. Every page uses <code>get_wcc_db_connection()</code>; nothing constructs its own.</td></tr>
        <tr><td><code>session.php</code></td><td>Hardened session start — HttpOnly, SameSite, strict mode. Every entry point uses it instead of raw <code>session_start()</code>.</td></tr>
        <tr><td><code>error.php</code></td><td>Friendly error pages plus logging. See the warning below — its behaviour is unusual.</td></tr>
        <tr><td><code>csrf.php</code></td><td>Token issue and validation for state-changing requests.</td></tr>
        <tr><td><code>ratelimit.php</code></td><td>Fixed-window throttle, used for failed logins.</td></tr>
        <tr><td><code>audit.php</code></td><td><code>wcc_audit_log()</code> — actor, action, entity, before/after.</td></tr>
        <tr><td><code>notifications.php</code></td><td>Per-user notification centre and permission-targeted broadcast.</td></tr>
        <tr><td><code>techident.php</code></td><td>Who performed an intervention, matching both name spellings. See <a href="#tickets">Ticket Lifecycle</a>.</td></tr>
        <tr><td><code>ticketid.php</code></td><td>Collision-safe ticket ID allocation.</td></tr>
        <tr><td><code>partslist.php</code></td><td>Normalises the two historical shapes of <code>parts_list</code> JSON.</td></tr>
        <tr><td><code>workorders.php</code></td><td>The single definition of "overdue".</td></tr>
        <tr><td><code>procurement.php</code> · <code>reorder.php</code></td><td>Approval routing, and event-driven automatic reordering.</td></tr>
        <tr><td><code>gamification.php</code> · <code>skill_expiry.php</code></td><td>Proficiency tiers and certification-expiry warnings.</td></tr>
        <tr><td><code>kpi.php</code> · <code>shift_calendar.php</code></td><td>The single KPI engine and shift-aware elapsed time.</td></tr>
        <tr><td><code>dbadmin.php</code></td><td>Backup, restore and flush.</td></tr>
        <tr><td><code>head.php</code> · <code>version.php</code></td><td>The document shell and the asset cache-busting token.</td></tr>
    </tbody>
</table>
</div>

<div class="docs-note">
    <span class="t">A recurring lesson worth stating plainly</span>
    Each of these owns a single rule, defined once and shared. A proficiency tier, the
    definition of "overdue", the ticket-ID format — each lives in one place, so every screen
    that uses it stays in agreement. If you extend the system and find a rule that already
    exists, call the shared version rather than writing a second copy.
</div>

<h3 id="error-handling">Errors and logging</h3>

<p>
    <code>inc/error.php</code> gives the application a consistent safety net. Two handlers
    work together:
</p>

<ul>
    <li>An <strong>error handler</strong> that promotes PHP diagnostics to exceptions, so a
        latent problem surfaces immediately rather than corrupting output quietly.</li>
    <li>An <strong>exception handler</strong> that catches anything uncaught, logs the full
        technical detail for the operator, and shows the user a clean "Something went wrong"
        page — never a raw stack trace, a file path or a database message.</li>
</ul>

<p>
    The effect for an end user is that a problem produces a tidy, branded message instead of
    an intimidating error dump, while the full diagnostic is waiting in the log for whoever
    maintains the system.
</p>

<div class="docs-note">
    <span class="t">A note for developers verifying changes</span>
    Because errors surface as the friendly page rather than raw PHP text, confirm a page by
    loading it and checking the rendered result and the PHP error log — not by scanning the
    output for the words "Fatal error", which the handler deliberately never emits.
    <code>php -l</code> checks syntax only; it does not execute the page.
</div>
