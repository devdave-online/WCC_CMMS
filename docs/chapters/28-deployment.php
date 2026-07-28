<p>
    WCC deploys as files plus a database. This chapter covers the two situations that
    actually occur — a machine inside the plant, and a server anyone on the internet can
    reach — because they need very different decisions.
</p>

<h3 id="local-deploy">Local deployment</h3>

<p>The normal case: a PC or small server on the plant network.</p>

<ol>
    <li>Copy the application into the web root.</li>
    <li>Create the database, import the dump, point <code>inc/db.php</code> at it.</li>
    <li>Sign in and change the seeded administrator password.</li>
    <li>Confirm <code>.htaccess</code> is honoured — request <code>/schema.sql</code> and
        expect <strong>403</strong>.</li>
    <li>Decide about the demo data: keep it to explore, or clear it via
        <em>Data Administration → Flush → Factory Reset</em> before entering real work.</li>
</ol>

<div class="docs-note">
    <span class="t">No TLS on the shop floor is normal</span>
    Plant intranets frequently have no certificate authority and no HTTPS. WCC works over
    plain HTTP: the session cookie's <code>Secure</code> flag switches itself on only when
    the request actually is TLS, so nothing breaks. The one real consequence is that browsers
    refuse camera access without HTTPS, which is why QR scanning lives in the companion app.
</div>

<div class="docs-note warn">
    <span class="t">Start MariaDB before you use the app</span>
    Apache alone is not enough. If MySQL is down, login and every page that hits the database
    return errors (often HTTP 500). On XAMPP: start MySQL, wait until
    <code>mysql -u root -e "SELECT 1"</code> succeeds, then open the app. After any unclean
    shutdown or datadir rebuild, re-run the automated gates and keep a known-good dump under
    <code>backups/pre_launch_*/workshop_db_*.sql</code>. Never leave
    <code>innodb_force_recovery</code> set for day-to-day use.
</div>

<h3 id="public-deploy">Public hosting</h3>

<p>
    Reachable from the internet is a different risk profile. Everything below is in addition
    to the local checklist.
</p>

<p>
    A short go-live checklist takes an installation from "works on the network" to "safe on
    the open internet." Every item is standard practice for production web software:
</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Step</th><th>What it does for you</th></tr></thead>
    <tbody>
        <tr><td><strong>Create a scoped database user</strong> with rights to its own schema only</td><td>Contains the application to its own database, the standard principle of least privilege.</td></tr>
        <tr><td><strong>Enable HTTPS</strong> and redirect HTTP</td><td>Encrypts credentials and sessions in transit. WCC's session cookie automatically switches to Secure once TLS is present — no config needed.</td></tr>
        <tr><td><strong>Restrict Data Administration</strong> to trusted operators</td><td>Backup, restore and flush are powerful; on a public host, reserve them for accounts you fully control.</td></tr>
        <tr><td><strong>Set strong administrator passwords</strong></td><td>The single most effective control on any admin-gated feature.</td></tr>
        <tr><td><strong>Tidy the demo accounts</strong></td><td>Remove or re-password the shared-credential demo logins once real work begins.</td></tr>
        <tr><td><strong>Confirm <code>display_errors</code> is off</strong> (the default hardened setting)</td><td>Keeps technical detail in the log and out of the browser — already the shipped configuration.</td></tr>
    </tbody>
</table>
</div>

<div class="docs-note">
    <span class="t">One codebase for every environment</span>
    Where a public demo needs certain features held back, a single configuration flag
    disables them across the board, enforced server-side. There is no separate "public build"
    to maintain — the same code runs everywhere, so every installation benefits from every
    improvement.
</div>

<h3 id="backups-ops">Backups and cron</h3>

<p>
    Backups live in <code>backups/</code>, which is denied over HTTP. That protects them from
    the web, not from disk failure — <strong>copy them off the machine</strong>. A backup
    stored only on the server it backs up is not a backup.
</p>

<p>Scheduled jobs worth running:</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Job</th><th>Cadence</th><th>Does</th></tr></thead>
    <tbody>
        <tr><td><code>php cron_skill_expiry.php</code></td><td>daily</td><td>Warns holders at 30/20/10/5/3 days and on expiry. Safe to run repeatedly.</td></tr>
        <tr><td><code>php cron_requisition.php</code></td><td>daily</td><td>Sweeps every part for low stock, catching anything event-driven reorder missed.</td></tr>
        <tr><td><code>mysqldump</code> or the backup tool</td><td>daily</td><td>Full database dump, copied off-machine.</td></tr>
        <tr><td><code>php demo/demo_seed.php</code></td><td>nightly, <em>demo instances only</em></td><td>Resets a public demo to a clean state.</td></tr>
    </tbody>
</table>
</div>

<p>
    On Windows use Task Scheduler with the full PHP path
    (<code>C:\xampp\php\php.exe</code>) and the script's absolute path. On Linux, standard
    crontab entries.
</p>

<div class="docs-note danger">
    <span class="t">Test a restore before you need one</span>
    An untested backup is a hope. At least once, restore a dump into a scratch database and
    sign in. That is the only thing that proves the file is complete — and it is how the
    15-table-versus-40-table gap described in <a href="#dataadmin">Data Administration</a>
    would have been caught years earlier.
</div>
