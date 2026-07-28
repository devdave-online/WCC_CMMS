<p>
    Symptoms that have actually occurred, with the cause and the fix. Most were diagnosed
    the slow way at least once, which is why they are written down.
</p>

<h3 id="common-issues">Common issues</h3>

<div class="table-scroll">
<table>
    <thead><tr><th>Symptom</th><th>Likely cause</th><th>Fix</th></tr></thead>
    <tbody>
        <tr>
            <td>A list shows only the first row or two, then the page ends oddly</td>
            <td>A row threw mid-loop. Because notices become exceptions, everything after it never rendered — and the friendly error page sits below the fold.</td>
            <td>Check the PHP error log for the real exception. Usually a column holding an unexpected shape, e.g. JSON that is a list of objects where the code expected scalars.</td>
        </tr>
        <tr>
            <td>"Something went wrong. Unexpected error occurred."</td>
            <td>Any uncaught throw.</td>
            <td>The detail is in the error log, never on screen. If the log is empty, the log <em>directory</em> may not exist — <code>error_log</code> silently discards when it cannot write.</td>
        </tr>
        <tr>
            <td>A dropdown is empty and blocks a form</td>
            <td>The lookup table is empty, or its values do not match what the query filters on.</td>
            <td>Check the exact values. <code>team_directory.role_type</code> must be the literal <code>technical</code> or <code>production</code> — job titles there silently empty the Person In Charge list.</td>
        </tr>
        <tr>
            <td>A technician's stats read zero although their work is clearly recorded</td>
            <td>Filtering on one spelling of their name.</td>
            <td>Match both via <code>wcc_tech_aliases()</code>. Records carry the display name or the username depending on when they were written.</td>
        </tr>
        <tr>
            <td>Two screens disagree about the same count</td>
            <td>The rule is implemented twice and the copies have drifted.</td>
            <td>Move it into <code>inc/</code> and have both screens call the shared version, so they cannot disagree.</td>
        </tr>
        <tr>
            <td><code>/schema.sql</code> downloads instead of returning 403</td>
            <td>Apache is ignoring <code>.htaccess</code>.</td>
            <td>Set <code>AllowOverride All</code> for the directory and reload. Until then <strong>no</strong> file-level protection in this project is active.</td>
        </tr>
        <tr>
            <td>A modal renders narrow and clips its contents</td>
            <td>Its content needs more than the default 460px. (<code>.modal-content</code> is now <code>width: 94vw</code> capped by <code>max-width: 460px</code>, so raising max-width widens it — the old fixed-400px trap is gone.)</td>
            <td>Raise <code>max-width</code> (e.g. <code>max-width: 760px</code>), or set <code>width: min(760px, 94vw)</code>.</td>
        </tr>
        <tr>
            <td>A CSS or JS change has no effect</td>
            <td>Cached asset.</td>
            <td>Bump <code>WCC_UI_VERSION</code> in <code>inc/version.php</code>; assets are requested with it as a cache-buster.</td>
        </tr>
        <tr>
            <td>Locked out after repeated failed logins</td>
            <td>The brute-force throttle: 10 failures per IP per 15 minutes.</td>
            <td>Wait for the window, or clear the row for that IP from <code>rate_limit</code>.</td>
        </tr>
        <tr>
            <td>A migration never applies</td>
            <td>It references something that does not exist in the live schema.</td>
            <td>Check the real table with <code>DESCRIBE</code>, not <code>schema.sql</code> — see <a href="#migrations">Migrations</a> on schema drift.</td>
        </tr>
        <tr>
            <td>Auto-reorder is not raising requisitions</td>
            <td>One of its guards is unmet.</td>
            <td>Confirm <code>auto_reorder</code> is on, lifecycle is <code>Active</code>, stock is at or below minimum, a <code>primary_vendor_id</code> is set, and no open order already covers it.</td>
        </tr>
    </tbody>
</table>
</div>

<h3 id="where-logs">Where to look</h3>

<div class="table-scroll">
<table>
    <thead><tr><th>Source</th><th>Holds</th></tr></thead>
    <tbody>
        <tr><td>PHP error log<br><code>C:\xampp\php\logs\php_error_log</code></td><td>Every uncaught exception with a stack trace. The first place to look, always. Create the directory if missing.</td></tr>
        <tr><td>Apache error log</td><td>Server-level failures — the application never started.</td></tr>
        <tr><td><code>audit_log</code> table</td><td>Who changed what, with before/after values.</td></tr>
        <tr><td><code>po_status_logs</code></td><td>Every purchase order transition and why.</td></tr>
        <tr><td><code>inventory_ledger</code></td><td>Every stock movement and the job behind it.</td></tr>
        <tr><td>Browser console</td><td>Front-end errors — a failed <code>fetch</code> to an <code>api/</code> endpoint usually shows the JSON error.</td></tr>
    </tbody>
</table>
</div>

<div class="docs-note danger">
    <span class="t">How to verify a page really works</span>
    <code>php -l</code> checks syntax only — it does not resolve includes or execute
    anything, and it cannot tell you a page is broken. Load the page and assert on: the text
    <strong>"Something went wrong"</strong>, an HTTP <strong>5xx</strong>, output that
    <strong>ends before <code>&lt;/html&gt;</code></strong>, and <strong>new lines in the PHP
    error log</strong> during the request. Grepping for "Fatal error" proves nothing here —
    that string never appears.
</div>

<h3 id="faq">FAQ</h3>

<p><strong>Can I run this on shared hosting?</strong><br>
    Yes, if you get PHP 8, MySQL, and <code>AllowOverride All</code>. Without the last one
    the security rules are inert — verify before trusting it.</p>

<p><strong>How do I add a permission?</strong><br>
    Add it to <code>PERMISSION_LABELS</code> in <code>rbac.php</code>, grant it to the
    appropriate roles in the Role Presets editor, then gate pages with
    <code>require_perm()</code> and endpoints with <code>require_api_perm()</code>. Adding
    the label alone protects nothing.</p>

<p><strong>Why is my new page visible to everyone?</strong><br>
    It has no <code>require_perm()</code> call. The sidebar hides links, but a typed URL
    reaches the file directly.</p>

<p><strong>Can I change the roles?</strong><br>
    Yes — <code>role_definitions</code> is editable through the UI. Do not hardcode level
    numbers or names anywhere; use <code>get_role_name()</code> and permission checks.</p>

<p><strong>How do I turn off the animated background?</strong><br>
    Per user, in <em>My Profile → Visual Preferences</em>. The setting is stored per browser,
    so an old shop-floor PC can disable it without affecting anyone else.</p>

<p><strong>Can I delete old tickets?</strong><br>
    You can, via Data Administration, but consider not to. Ticket history is what makes MTBF,
    repeat-offender analysis and machine lifetime cost possible. Archive the database instead.</p>
