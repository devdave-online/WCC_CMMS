<p>
    Schema changes are numbered SQL files in <code>migrations/</code>, applied in order by a
    small runner and recorded so they never run twice. There is no rollback mechanism —
    forward-only, which for a system whose database you are expected to back up before
    touching is the honest trade.
</p>

<h3 id="how-migrations">How migrations run</h3>

<pre><code>php migrations/migrate.php --status    # list applied and pending
php migrations/migrate.php --apply     # apply everything pending, in order</code></pre>

<p>
    The runner reads <code>migrations/*.sql</code>, sorts by filename, and skips anything
    already recorded in <code>schema_migrations</code>. Each successful file is inserted into
    that table, so re-running is safe and idempotent.
</p>

<p>Naming is <code>NNNN_short_description.sql</code>, zero-padded so lexical order is
    execution order:</p>

<pre><code>0012_po_status_logs_note.sql
0013_role_definitions_storekeeper.sql
0014_add_admin_layout_json_to_users.sql
0015_create_notifications.sql</code></pre>

<h3 id="writing-one">Writing a migration</h3>

<ul>
    <li>
        <strong>Make it re-runnable where you can.</strong> <code>CREATE TABLE IF NOT
        EXISTS</code> and guarded <code>ALTER</code>s turn a half-applied migration from a
        crisis into an inconvenience.
    </li>
    <li>
        <strong>Mind the MySQL/MariaDB gap.</strong> The two dialects differ in places — for
        example MariaDB does not accept <code>CAST(… AS JSON)</code>. If you develop on one
        and deploy on the other, test your migration on both.
    </li>
    <li>
        <strong>Never edit an applied migration.</strong> It has already run somewhere and
        will not run again. Write a new one.
    </li>
    <li>
        <strong>Back up first.</strong> <em>Admin Panel → Data Administration → Backup</em>
        takes a full dump in one click. Do it before <code>--apply</code>, every time.
    </li>
</ul>

<h3 id="schema-drift">The database is the source of truth</h3>

<p>
    The recommended way to stand up a fresh installation is to import the supplied database
    dump — it is a complete snapshot of the live schema and is what the application is built
    against. The migration files then carry it forward from there.
</p>

<div class="docs-note">
    <span class="t">When you need a table's exact shape, ask the database</span>
    <code>DESCRIBE table_name</code> or a query against <code>information_schema.columns</code>
    is always authoritative. The schema summary in <a href="#schema">Database Schema</a> was
    read directly from a running installation for exactly this reason — the database is the
    definitive record of its own structure.
</div>

<p>
    To regenerate a clean schema baseline at any time, the built-in backup tooling produces a
    full <code>mysqldump</code>; a <code>--no-data</code> dump gives you a structure-only
    reference whenever you want one.
</p>
