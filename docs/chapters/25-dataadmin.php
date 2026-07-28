<p>
    <em>Admin Panel → Data Administration</em> holds the three operations that can destroy
    an installation: backup, restore and flush. It is gated by <code>manage_settings</code>,
    every action requires a CSRF token, and every action is written to the audit log.
</p>

<h3 id="backup">Backup</h3>

<figure class="docs-figure">
    <img src="/img/docs/data_admin.png" alt="Data Administration">
    <figcaption>The Data Administration center for backups, restores, and flushes.</figcaption>
</figure>

<p>
    A full <code>mysqldump</code> of the entire database — schema, data, routines and events
    — written to <code>backups/</code> with a timestamped filename. You can download it or
    keep it on the server.
</p>

<p>
    It backs up <strong>everything</strong>, discovered at run time. This matters: the tool
    it replaced dumped a hardcoded list of 15 tables, against a live schema of 40. Anyone
    restoring from one of those backups would have silently lost notifications, the audit
    log and role definitions — a disaster recovery plan that quietly does not work is worse
    than none, because you are relying on it.
</p>

<div class="docs-note">
    <span class="t">Backups are protected by default</span>
    Because a full backup contains everything — including password hashes —
    <code>backups/</code> ships denied to the web by <code>.htaccess</code>. Treat a
    downloaded dump with the same care as the database itself — it is the database.
</div>

<h3 id="restore">Restore</h3>

<p>
    Restore streams a <code>.sql</code> file into the MySQL client, either from a file you
    upload or one already in <code>backups/</code>. The on-disk route avoids PHP upload
    limits entirely, so large dumps are not a problem.
</p>

<p>
    <strong>A fresh backup is taken automatically before the restore begins.</strong> If you
    restore the wrong file, the state you just replaced is still on disk.
</p>

<h3 id="flush">Flush</h3>

<p>
    Flush empties selected tables. They are presented in the four groups described in
    <a href="#schema">Database Schema</a>, with live row counts, so you can see what you are
    about to lose.
</p>

<ul>
    <li><strong>Factory Reset</strong> pre-selects the transactional group — clears history,
        keeps the plant.</li>
    <li>Reference, Config and System tables are individually selectable and marked as
        dangerous.</li>
    <li>Foreign keys are disabled for the truncate and re-enabled after, so tables with
        dependents clear cleanly instead of failing halfway.</li>
</ul>

<div class="docs-note warn">
    <span class="t">What certain tables cost you</span>
    Flushing <code>users</code> logs you out immediately — the login page will then seed a
    fresh default administrator. Flushing <code>app_settings</code> or
    <code>role_definitions</code> discards configuration and permissions.
    <code>schema_migrations</code> loses the record of which migrations have run, and the
    next <code>--apply</code> will try to run all of them again.
</div>

<h3 id="safety">Safety rails</h3>

<div class="table-scroll">
<table>
    <thead><tr><th>Rail</th><th>What it stops</th></tr></thead>
    <tbody>
        <tr><td>Automatic pre-action backup</td><td>An irreversible mistake. Restore and flush both snapshot first.</td></tr>
        <tr><td>Type-to-confirm (<code>RESTORE</code> / <code>FLUSH</code>)</td><td>A misplaced click. The button stays disabled until the word is typed exactly.</td></tr>
        <tr><td>Table-name allow-list</td><td>Injection. Every name is checked against the live schema before it can appear in a statement.</td></tr>
        <tr><td>CSRF + permission on every POST</td><td>Cross-site triggering and unauthorised use.</td></tr>
        <tr><td>Audit entry per action</td><td>Silent destruction. <code>data.backup</code>, <code>data.restore</code>, <code>data.flush</code>.</td></tr>
    </tbody>
</table>
</div>

<div class="docs-note">
    <span class="t">Reserved for trusted operators on public installs</span>
    Backup, restore and flush are powerful by nature and are gated on
    <code>manage_settings</code>. For a public-facing installation, WCC's configuration flag
    can switch these operations off entirely, so a public demo can offer the full application
    while keeping the destructive tools out of reach. Restrict them to accounts you fully
    control, or disable them — your choice per deployment.
</div>
