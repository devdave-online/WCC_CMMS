<p>
    WCC is raw PHP and MySQL with no build step, no package manager and no framework.
    Deployment is copying files and importing a database — there is nothing to compile and
    nothing to keep running except your web server.
</p>

<h3 id="requirements">Requirements</h3>

<div class="table-scroll">
<table>
    <thead><tr><th>Component</th><th>Minimum</th><th>Notes</th></tr></thead>
    <tbody>
        <tr><td>PHP</td><td>8.0</td><td>Developed against 8.2. Needs <code>pdo_mysql</code> and <code>json</code> — both standard.</td></tr>
        <tr><td>MySQL / MariaDB</td><td>MySQL 5.7 · MariaDB 10.3</td><td>Developed against MariaDB 10.4. Uses <code>utf8mb4</code> throughout.</td></tr>
        <tr><td>Web server</td><td>Apache with <code>mod_rewrite</code></td><td><code>AllowOverride All</code> is required — security rules live in <code>.htaccess</code>.</td></tr>
        <tr><td>Disk</td><td>~50 MB + uploads</td><td>The application is small; attachments and database backups dominate.</td></tr>
        <tr><td>Browser</td><td>Any current browser</td><td>WebGL only affects the animated background, which can be switched off.</td></tr>
    </tbody>
</table>
</div>

<p>
    XAMPP on Windows, or a standard LAMP stack on Linux, both satisfy this without extra
    configuration. No Composer, Node or build tooling is involved at any point.
</p>

<h3 id="quick-install">Quick install</h3>

<ol>
    <li>Copy the application into your web root (for XAMPP, <code>C:\xampp\htdocs</code>).</li>
    <li>Create an empty database and a user that owns it.</li>
    <li>Import the supplied SQL dump into that database.</li>
    <li>Point <code>inc/db.php</code> at your database name, user and password.</li>
    <li>Open the site and sign in.</li>
</ol>

<div class="docs-note">
    <span class="t">Zero-config on a laptop, one step for a server</span>
    WCC runs immediately against a default XAMPP setup — no database configuration needed to
    start exploring. When you move it to a shared server, take the standard step of creating a
    dedicated database user scoped to its own schema and pointing <code>inc/db.php</code> at
    it. That is the only database change go-live requires.
</div>

<h3 id="manual-install">Manual install</h3>

<p>
    If you are building the schema rather than importing a dump, apply the migrations in
    order. They are plain numbered SQL files, applied by a small runner:
</p>

<pre><code>php migrations/migrate.php --status    # what has and has not been applied
php migrations/migrate.php --apply     # apply everything outstanding</code></pre>

<p>
    The runner records each applied file in <code>schema_migrations</code>, so re-running it
    is safe and only pending files execute. See <a href="#migrations">Migrations</a> for how
    to write one, and for how to regenerate a clean schema baseline whenever you want one.
</p>

<h3 id="first-login">First login</h3>

<p>
    If the <code>users</code> table is completely empty, <code>login.php</code> seeds a single
    administrator on first visit — username <code>admin</code>, password <code>password</code>
    — and immediately forces a password change, because that credential is public knowledge.
</p>

<p>
    If you imported the demo database instead, the accounts described in
    <a href="#demodata">Demo Data</a> already exist and no account is seeded.
</p>

<div class="docs-note">
    <span class="t">A 30-second go-live check</span>
    Before a server goes public, three quick confirmations: your admin password is set, your
    database user is scoped, and <code>.htaccess</code> is active — request
    <code>/schema.sql</code> and confirm you get a <strong>403</strong>. A 403 means Apache is
    honouring the project's security rules (see <a href="#hardening">Hardening</a>); if it
    downloads instead, enable <code>AllowOverride All</code> for the directory. That is the
    whole checklist.
</div>
