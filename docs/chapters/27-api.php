<p>
    The REST API exists so machines and other applications can use WCC — the Android
    companion app is its main consumer. It enforces the same permission model as the web
    interface, against the same data.
</p>

<h3 id="api-auth">Authentication</h3>

<p>Two methods, both resolving to a real user account:</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Method</th><th>How</th><th>For</th></tr></thead>
    <tbody>
        <tr><td><strong>API key</strong></td><td><code>X-API-Key: &lt;key&gt;</code></td><td>Applications and integrations. The key is stored on the user row.</td></tr>
        <tr><td><strong>Basic auth</strong></td><td>Standard <code>Authorization: Basic</code></td><td>Quick testing with <code>curl</code>.</td></tr>
        <tr><td><strong>Session</strong></td><td>Existing cookie</td><td>Falls back to the browser session when called from a logged-in page.</td></tr>
    </tbody>
</table>
</div>

<p>
    There is no anonymous access. Every call without valid credentials returns
    <strong>401</strong> — including the discovery endpoint, which lists resources only to
    an authenticated caller.
</p>

<div class="docs-note warn">
    <span class="t">An API key is a password</span>
    It grants exactly the permissions of the user it belongs to, with no expiry and no second
    factor. Issue keys to purpose-built accounts holding only what that integration needs —
    never to an administrator account because it was convenient.
</div>

<h3 id="api-resources">Resources</h3>

<p>Clean URLs under <code>/api/v1/</code>:</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Resource</th><th>Covers</th></tr></thead>
    <tbody>
        <tr><td><code>/me</code></td><td>The calling user, with live stats — interventions, tickets closed and reported, average wrench time.</td></tr>
        <tr><td><code>/tickets</code></td><td>Faults. List, read, create, update, delete.</td></tr>
        <tr><td><code>/ticket-actions</code></td><td>Intervention records against tickets. Body field is <code>action_taken</code> (not a free-form <code>notes</code> column).</td></tr>
        <tr><td><code>/work-orders</code></td><td>Planned work. Create with <code>equip_id</code> (equipment foreign key).</td></tr>
        <tr><td><code>/equipment</code></td><td>The asset register. Supports <code>?asset_uuid=</code> for exact scanner lookups and <code>?search=</code> across name, UUID and model.</td></tr>
        <tr><td><code>/toolings</code></td><td>Tooling register. CRUD with soft-delete. Filters: <code>search</code>, <code>barcode</code>, <code>asset_tag</code>, <code>tooling_code</code>, <code>category</code>, <code>status</code>, <code>linked_equip_id</code>. Nested: <code>/{id}/bom</code>, <code>/{id}/documents</code>. Perms <code>view_toolings</code> / <code>manage_toolings</code>. (Companion app still uses <code>/api/companion/toolings.php</code> — separate package.)</td></tr>
        <tr><td><code>/production-lines</code></td><td>Plant topology.</td></tr>
        <tr><td><code>/inventory</code></td><td>Parts and stock levels.</td></tr>
        <tr><td><code>/vendors</code> · <code>/purchase-orders</code> · <code>/purchase-requests</code></td><td>Procurement. Vendor address column is <code>vendor_address</code> (write may accept <code>address</code> alias).</td></tr>
        <tr><td><code>/users</code> · <code>/roles</code> · <code>/api-keys</code></td><td>Administration. Requires the corresponding admin permissions.</td></tr>
        <tr><td><code>/stats</code> · <code>/audit</code></td><td>KPI figures and the audit trail.</td></tr>
        <tr><td><code>/ai-context</code></td><td>A machine-readable description of the installation, for agent tooling.</td></tr>
    </tbody>
</table>
</div>

<p>Every response uses the same envelope, so clients need one parser:</p>

<pre><code>{
  "success":   true,
  "data":      { ... },
  "message":   "",
  "timestamp": "2026-07-22T18:55:25+02:00"
}</code></pre>

<h3 id="api-examples">Worked examples</h3>

<pre><code># who am I, and what have I done?
curl -H "X-API-Key: $KEY" http://wcc.local/api/v1/me

# find a machine from a scanned label
curl -H "X-API-Key: $KEY" \
     "http://wcc.local/api/v1/equipment?asset_uuid=WCC-A1B2C3-0007"

# open work orders for one machine
curl -H "X-API-Key: $KEY" \
     "http://wcc.local/api/v1/work-orders?equip_id=7"

# register a fault
curl -X POST -H "X-API-Key: $KEY" -H "Content-Type: application/json" \
     -d '{"equip_id":7,"fault_desc":"Spindle overheat alarm","priority":"high"}' \
     http://wcc.local/api/v1/tickets</code></pre>

<div class="docs-note">
    <span class="t">Ticket IDs are always server-allocated</span>
    A <code>ticket_id</code> supplied in a create request is <strong>ignored</strong> — it is
    the primary key, and a client-chosen value could collide with an existing ticket. The
    allocated ID comes back in the response; read it from there. If you need offline capture
    with client-generated identifiers, that requires a designed scheme rather than writing
    the field directly.
</div>

<div class="docs-note warn">
    <span class="t">Look up tickets by <code>TK-…</code>, not by numeric row ids</span>
    Public identifiers look like <code>TK-260728-004</code>.
    <code>GET /api/v1/tickets/123</code> returns <strong>404</strong> by design.
    Use <code>GET /api/v1/tickets/TK-260728-004</code> (or list/filter endpoints).
</div>

<div class="docs-note">
    <span class="t">People fields hold names</span>
    <code>tech_name</code>, <code>pic</code> and <code>announced_by</code> carry a person's
    name rather than a numeric ID, so records read naturally. When filtering by person on the
    server, <code>wcc_tech_aliases()</code> from <code>inc/techident.php</code> resolves every
    spelling a person's work may be filed under, so results are always complete.
</div>

<p>
    The <code>/me</code> response includes both <code>role</code> (formatted, e.g.
    <code>"L4 — Admin"</code>) and <code>role_name</code> (bare, e.g. <code>"Admin"</code>),
    both derived live from <code>role_definitions</code>. Since roles are editable, read the
    role from these fields rather than mapping level numbers to names in your client.
</p>
