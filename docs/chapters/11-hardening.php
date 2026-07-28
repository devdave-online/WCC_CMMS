<p>
    This chapter covers everything protecting the application below the permission layer:
    request forgery, query safety, what the web server will hand out, and what happens to
    uploaded files.
</p>

<h3 id="csrf">CSRF protection</h3>

<p>
    <code>inc/csrf.php</code> issues a per-session token and validates it on state-changing
    requests. <code>wcc_csrf_require()</code> rejects a missing or wrong token outright.
</p>

<p>
    This applies to state-changing <strong>GET</strong> links too, not just POST forms. A
    plain link that deletes something is otherwise triggerable by an <code>&lt;img&gt;</code>
    tag in an email — which is why those links carry a token.
</p>

<p>
    <code>SameSite=Lax</code> on the session cookie (see <a href="#auth">Authentication</a>)
    is a second, independent layer: even without a token check, a cross-site POST arrives
    with no session at all.
</p>

<h3 id="sql-injection">Query safety</h3>

<p>
    Every query is a prepared statement with bound parameters. There is no string
    concatenation of user input into SQL anywhere in the application.
</p>

<p>Two places need care because identifiers cannot be bound as parameters:</p>

<ul>
    <li>
        <strong>Table names in Data Administration.</strong> Flush validates every requested
        name against the live list from <code>information_schema</code> before it appears in
        a <code>TRUNCATE</code>. A name that is not on that list is rejected, so the input is
        an allow-list lookup rather than a value.
    </li>
    <li>
        <strong>Dynamic <code>IN (…)</code> lists.</strong> Built as
        <code>?,?,?</code> placeholders with the values bound —
        <code>wcc_tech_alias_placeholders()</code> exists precisely so that pattern is
        written once instead of hand-rolled at each call site.
    </li>
</ul>

<h3 id="webroot">Webroot exposure</h3>

<p>
    The application lives in the document root, so anything not explicitly denied is
    downloadable. Protection is layered in <code>.htaccess</code> files:
</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Path</th><th>Rule</th><th>Reason</th></tr></thead>
    <tbody>
        <tr><td>root</td><td><code>Options -Indexes</code></td><td>Without it, directory listings hand over a full file inventory before anyone tries a login.</td></tr>
        <tr><td>root</td><td>Deny <code>.sql .md .ini .log .bak .yml</code>, dotfiles, <code>composer/package.json</code></td><td>Schema dumps, config and dependency versions were all fetchable.</td></tr>
        <tr><td>root</td><td><code>X-Content-Type-Options</code>, <code>X-Frame-Options</code>, <code>Referrer-Policy</code>, <code>Permissions-Policy</code></td><td>Baseline response headers.</td></tr>
        <tr><td><code>inc/ migrations/ _ai_ctxt/ _dev_artifacts/ docs/</code></td><td>Deny all</td><td>Server-side only; nothing fetches them over HTTP.</td></tr>
        <tr><td><code>backups/ archive/</code></td><td>Deny all</td><td>Database dumps contain every password hash. Non-negotiable.</td></tr>
        <tr><td><code>uploads/</code></td><td>Listing off, <strong>PHP execution disabled</strong></td><td>Cannot be denied outright — invoices and checklist photos are linked directly. See below.</td></tr>
    </tbody>
</table>
</div>

<div class="docs-note danger">
    <span class="t">Verify the rules are actually in force</span>
    All of this depends on <code>AllowOverride All</code>. Request <code>/schema.sql</code>
    in a browser: <strong>403</strong> means the rules are live, a <strong>download</strong>
    means Apache is ignoring every <code>.htaccess</code> in the project and none of the
    protection above exists. Check this once, on every new deployment.
</div>

<p>
    PHP itself is configured with <code>expose_php=Off</code> and
    <code>display_errors=Off</code> with <code>log_errors=On</code>, so error detail reaches
    the log rather than the visitor. Application code never echoes a driver message — a PDO
    exception leaks table names, SQL and file paths, so those are logged and replaced with a
    generic message.
</p>

<h3 id="uploads">Upload handling</h3>

<p>
    <code>uploads/</code> holds attacker-supplied bytes: invoice PDFs and checklist photos.
    It cannot be blocked, because the application links to those files directly. So it is
    hardened instead:
</p>

<ul>
    <li><strong>PHP execution is disabled</strong> in that directory. A <code>.php</code>
        file smuggled past the upload filter is served as inert text, not executed — this is
        the classic upload-to-remote-code-execution chain, and disabling the engine breaks
        it regardless of how the file got there.</li>
    <li>Directory listing is off, so filenames cannot be enumerated.</li>
    <li>Uploads are validated on extension, checked with <code>is_uploaded_file()</code>,
        size-capped, and renamed to a generated filename — the original name never becomes a
        path on disk.</li>
    <li>Responses carry <code>nosniff</code> and a restrictive
        <code>Content-Security-Policy</code>, so a file that lies about its type is not
        reinterpreted as script.</li>
</ul>

<div class="docs-note">
    <span class="t">Serving files, and gating them if you need to</span>
    Attachments are served directly by the web server with generated, non-guessable
    filenames — fast and simple, and the right default for manuals and photos. If a
    particular deployment stores commercially sensitive documents and wants every download to
    require a login, route <code>uploads/</code> through a small PHP gatekeeper that checks
    the session before streaming the file. Both approaches are supported; choose per
    deployment based on how sensitive your attachments are.
</div>
