<p>
    Everything else in WCC points at a machine. If the asset register is wrong, the fault
    history, the PM schedule and the reliability numbers are all wrong with it — so this is
    the part worth getting right before you load anything else.
</p>

<h3 id="asset-register">The asset register</h3>

<figure class="docs-figure">
    <img src="/img/docs/asset_list.png" alt="Asset Register">
    <figcaption>The master equipment list.</figcaption>
</figure>

<p>
    <code>_eam/setup_vault_equipment.php</code> is the master list. Each record carries far
    more than a name, because the questions asked of a CMMS six months in are rarely "what is
    it called":
</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Group</th><th>Fields</th><th>Answers</th></tr></thead>
    <tbody>
        <tr><td>Identity</td><td><code>asset_uuid</code>, name, category, type</td><td>Which machine is this, and what class of thing is it?</td></tr>
        <tr><td>OEM</td><td>brand, model, serial</td><td>Who do we call, and what exactly do we quote?</td></tr>
        <tr><td>Commercial</td><td>purchase date, PO value, vendor, warranty expiry, EOL</td><td>Is this still under warranty? Should we be replacing it?</td></tr>
        <tr><td>Operational</td><td>criticality A/B/C, base speed / pressure / temp / voltage</td><td>How badly does it hurt when this stops, and what is "normal"?</td></tr>
        <tr><td>Maintenance</td><td>PM interval, last PM date, LOTO protocol, SOP link</td><td>When is it due, and how is it made safe?</td></tr>
        <tr><td>Placement</td><td>workshop, line, station</td><td>Where is it, and what stops when it does?</td></tr>
    </tbody>
</table>
</div>

<p>
    <strong>Criticality</strong> is the field that earns its keep. A is "the line stops", B is
    "we work around it", C is "we notice eventually". It drives prioritisation everywhere and
    is worth setting honestly — if everything is A, nothing is.
</p>

<h3 id="hierarchy">Workshops, lines and stations</h3>

<figure class="docs-figure">
    <img src="/img/docs/asset_detail.png" alt="Asset Details">
    <figcaption>Detailed view of an asset showing its position in the hierarchy.</figcaption>
</figure>

<pre><code>Workshop  ──►  Production Line  ──►  Equipment
"Plant A"      "CNC Cell 1"          "Mazak VTC-800"</code></pre>

<p>
    The hierarchy is what lets a fault on one machine be understood as a line stoppage. It
    also drives the workshop breakdown on the KPI dashboard.
</p>

<p>
    Equipment may sit outside any line — compressors, chillers, cranes and dust extraction
    serve the whole site. They are still fully tracked; they simply have no
    <code>line_id</code>.
</p>

<h3 id="tooling">Tooling registry and vault</h3>

<p>
    Tools, fixtures and dies live beside equipment, not inside it. The shop floor sees them
    on <code>_eam/toolings.php</code> (ledger with accordion detail, linked parts BOM and
    documents). Master data and code/label rules live on
    <code>_eam/setup_vault_toolings.php</code>.
</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Surface</th><th>Permission</th><th>What it unlocks</th></tr></thead>
    <tbody>
        <tr><td>Tooling registry</td><td><code>view_toolings</code></td><td>Ledger, search, BOM list API, docs list API</td></tr>
        <tr><td>Tooling vault</td><td><code>manage_toolings</code></td><td>Create/edit/retire, BOM edit, label symbology, doc upload</td></tr>
        <tr><td>Equipment ledger/vault</td><td><code>view_equipment</code> / <code>manage_equipment</code></td><td>Independent of tooling — can be granted or flushed separately</td></tr>
    </tbody>
</table>
</div>

<p>
    Linked parts are stored in <code>tooling_bom</code>; files in
    <code>tooling_documents</code> (under <code>uploads/tooling/…</code>). APIs:
    <code>/api/get_tooling_bom.php</code>, <code>/api/get_tooling_docs.php</code>,
    <code>/api/upload_document.php</code> with <code>entity=tooling</code>.
</p>

<h3 id="labels">QR and DataMatrix labels</h3>

<p>
    Every asset can carry a printed label so a technician scans instead of typing. The
    payload is deliberately minimal and <strong>completely offline</strong>:
</p>

<pre><code>WCC|&lt;equip_id&gt;|&lt;asset_uuid&gt;|&lt;name, max 40 chars&gt;|SN:&lt;serial&gt;</code></pre>

<div class="docs-note">
    <span class="t">Why a payload and not a URL</span>
    A shop-floor network usually has no route to the internet, and often no DNS worth relying
    on. A label encoding <code>https://…</code> is useless the moment either is true. This
    payload identifies the machine on its own — enough to look it up, or to read from the
    scanner if the system is down. Codes are generated locally (a vendored barcode library in
    the browser, native ZPL on a Zebra), so nothing is ever sent anywhere to make a label.
</div>

<p>Both QR and DataMatrix are supported; DataMatrix is denser and survives better on
    small, oily or curved surfaces.</p>

<h3 id="printing">Printing: Zebra and paper</h3>

<p>Two paths, because most plants have exactly one of the two:</p>

<ul>
    <li>
        <strong>Zebra label printer</strong> — ZPL is generated and sent to the printer over
        TCP port 9100. Darkness, speed and DPI are configurable. This is the durable option:
        thermal transfer onto industrial label stock.
    </li>
    <li>
        <strong>Any ordinary printer</strong> — labels are laid out on a configurable sheet
        grid. Page size, label size, margins and gaps are all settable, so it works with
        off-the-shelf A4 label sheets, or plain paper into a laminator.
    </li>
</ul>

<p>
    Selection is per-asset or in batch with select-all, so commissioning a new line does not
    mean printing labels one at a time. Settings live in <code>app_settings</code> under
    <code>EquipmentLabels</code> and are edited from the vault page's setup modal, gated by
    <code>manage_equipment</code> — the people who own the asset register own its labels.
</p>

<div class="docs-note warn">
    <span class="t">Scanning is the companion app's job</span>
    WCC's web interface can print labels but cannot read them. Browsers refuse camera access
    without HTTPS, and shop-floor intranets typically have none — so scanning lives in the
    separate Android companion app, which talks to the same REST API. This is a constraint of
    the environment, not a gap in the product.
</div>
