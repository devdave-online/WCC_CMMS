<p>
    <a href="#assets">Assets &amp; Labels</a> covers what an equipment record holds. This
    chapter covers the four things attached to it that make the register operationally useful
    rather than merely a list.
</p>

<h3 id="eq-bom">Bill of materials</h3>

<p>
    A BOM links a machine to the parts that fit it, with quantities. Built from the equipment
    record — expand a machine, open <strong>BOM</strong>, and add parts from the inventory
    master.
</p>

<p>The value is at 3am, not at commissioning time. With a BOM in place:</p>

<ul>
    <li>A technician sees which parts fit <em>this</em> machine, instead of searching a
        catalogue of thousands by guesswork.</li>
    <li>Ordering the wrong-but-similar bearing becomes much less likely.</li>
    <li>The parts most worth stocking are visible — anything on the BOM of a criticality-A
        machine.</li>
</ul>

<div class="docs-note">
    <span class="t">Build it as you go</span>
    You do not need a complete BOM on day one. A practical approach: whenever a technician
    consumes a part on a machine, add it to that machine's BOM. Within a few months each BOM
    covers exactly the parts that actually fail — the useful subset — and it built itself from
    real work rather than a data-entry project.
</div>

<h3 id="eq-docs">Documents and manuals</h3>

<p>
    Files attach to a machine: OEM manuals, wiring diagrams, safety SOPs, calibration
    certificates. Upload from the equipment record or the Documents Management tile on the
    Admin Panel; each carries a title and a document type.
</p>

<p>
    The point is that the manual is on the machine's record rather than on a shared drive
    nobody can find at 3am. Equipment also has a <code>sop_link</code> field for a procedure
    hosted elsewhere, and a <code>loto_protocol</code> free-text field for the
    lock-out/tag-out steps — worth filling in even when nothing else is.
</p>

<div class="docs-note">
    <span class="t">How attachments are served</span>
    Documents are served directly by the web server with generated, non-guessable filenames —
    fast and simple, ideal for manuals and drawings. If a deployment needs every download to
    require a login, <a href="#uploads">Hardening</a> shows how to route uploads through a
    session check.
</div>

<h3 id="eq-uuid">UUID rules</h3>

<p>
    Every asset carries an <code>asset_uuid</code> — the identifier printed on its label and
    matched by the scanner. The UUID Configurator, on the equipment vault page, defines how
    those identifiers are generated <strong>per equipment category</strong>.
</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Setting</th><th>Does</th><th>Example</th></tr></thead>
    <tbody>
        <tr><td><strong>Category</strong></td><td>Which equipment category the rule applies to</td><td><code>Mechanical</code></td></tr>
        <tr><td><strong>Prefix</strong></td><td>Leading text</td><td><code>MCH-</code></td></tr>
        <tr><td><strong>Serial length</strong></td><td>Zero-padded width of the counter</td><td><code>4</code> → <code>0007</code></td></tr>
        <tr><td><strong>Current serial</strong></td><td>Next number to issue — increments automatically</td><td><code>3</code></td></tr>
        <tr><td><strong>Random chars</strong></td><td>Extra random characters appended</td><td><code>0</code></td></tr>
        <tr><td><strong>Char type</strong></td><td>Numeric or alphanumeric for the random part</td><td><code>ALPHANUMERIC</code></td></tr>
    </tbody>
</table>
</div>

<p>
    A rule of <code>MCH-</code> with a 4-digit serial yields <code>MCH-0001</code>,
    <code>MCH-0002</code>. Categorised identifiers mean a scanned or spoken code tells you
    what kind of machine it is before you look it up — which matters over a radio.
</p>

<h3 id="eq-labelsetup">Label &amp; printer setup</h3>

<p>
    The <strong>Label &amp; Printer Setup</strong> modal on the equipment vault page
    configures physical printing. Settings are stored in <code>app_settings</code> under
    <code>EquipmentLabels</code> and gated by <code>manage_equipment</code> — the people who
    own the asset register own its labels.
</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Group</th><th>Settings</th></tr></thead>
    <tbody>
        <tr><td><strong>Method</strong></td><td>Browser sheet (any printer), one label per page, or direct to a Zebra.</td></tr>
        <tr><td><strong>Symbology</strong></td><td>QR code or DataMatrix. DataMatrix is denser and survives small, oily or curved surfaces better.</td></tr>
        <tr><td><strong>Label</strong></td><td>Width and height in mm — default 50.8 × 25.4 (2″ × 1″).</td></tr>
        <tr><td><strong>Page</strong></td><td>Preset (A4/Letter) or custom width and height, plus margin.</td></tr>
        <tr><td><strong>Grid</strong></td><td>Horizontal and vertical gaps, so labels line up with off-the-shelf sheet stock.</td></tr>
        <tr><td><strong>Zebra</strong></td><td>Printer IP and port (9100), DPI (203/300), darkness, print speed.</td></tr>
        <tr><td><strong>Fields</strong></td><td>Which of UUID, serial and brand/model appear as human-readable text beside the code.</td></tr>
    </tbody>
</table>
</div>

<p>
    Select machines individually or with select-all, then print as a batch — commissioning a
    line does not mean printing labels one at a time. A preview renders before anything is
    sent to a printer.
</p>

<div class="docs-note">
    <span class="t">Codes are generated locally</span>
    Both symbologies render on your own hardware — a vendored barcode library in the browser,
    or native ZPL commands on the Zebra. Nothing is sent to an external service to produce a
    label, which is what makes this work on an isolated plant network.
</div>
