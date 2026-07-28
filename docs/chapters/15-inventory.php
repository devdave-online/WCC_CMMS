<p>
    Inventory in a CMMS exists to answer one question at 3am: <em>do we have the part?</em>
    Everything else — valuation, reorder points, consumption analysis — is downstream of
    keeping that answer true.
</p>

<h3 id="stock-status">The status column — read the whole store at a glance</h3>

<p>
    Right after each part's name the Inventory page carries two icon columns (both deliberately
    unlabelled — the icons carry the meaning). The first is a live <strong>stock-status</strong>
    badge, the second a gold ★ for <strong>critical spares</strong>. Sitting beside the name they
    describe, they let a storekeeper triage the whole store in one downward glance.
</p>

<p>
    So nothing has to be memorised, a compact <strong>status key</strong> is printed directly
    above the table — the same idea as the colour legend beneath the PM calendar. It lists
    every badge with its meaning and is generated from the same status engine the rows use, so
    it can never fall out of step with what the icons actually show. The table below is the
    full reference; the on-screen key is its short form.
</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Badge</th><th>Means</th><th>Action</th></tr></thead>
    <tbody>
        <tr><td>✔ green</td><td>Healthy — comfortably above minimum</td><td>Nothing.</td></tr>
        <tr><td>▲ amber</td><td>Approaching minimum (within the warning band)</td><td>Keep an eye on it.</td></tr>
        <tr><td>⬣ red</td><td>At or below minimum</td><td>Order it — nobody is yet.</td></tr>
        <tr><td>✕ red</td><td>Out of stock (zero)</td><td>A job may be blocked right now.</td></tr>
        <tr><td>🚚 blue</td><td>On order — an open PO already covers it</td><td>Handled; wait for delivery.</td></tr>
        <tr><td>⊘ grey</td><td>Obsolete / phasing out</td><td>Find a replacement — do not reorder.</td></tr>
    </tbody>
</table>
</div>

<div class="docs-note">
    <span class="t">The distinction that matters most</span>
    A red badge means low <strong>and nobody's on it</strong> — your actual to-do list. The
    moment an order exists (auto-raised or manual) the part turns blue "on order," so red is
    only ever the parts that genuinely need someone to act. Pair that with the ★ and the
    highest-priority row in the whole store — a critical spare, below minimum, not on order —
    reads itself off the screen.
</div>

<p>
    The badges are <strong>live</strong>: they read current stock and purchase-order data on
    every load, so a part flips from red to blue the instant a PO appears, and back to green
    once goods are received above the band. Nothing needs to be "run." The obsolete state
    also suppresses the red alarm — the system never tells you to reorder something it knows
    can't be ordered.
</p>

<div class="docs-note">
    <span class="t">Tuning it</span>
    The amber "approaching" band (how far above minimum still warns) and each part's lifecycle
    (Active / Phasing Out / Obsolete) are set together in <em>Admin Panel → Inventory
    Health</em>. The band is a single percentage; lifecycle is per-part.
</div>

<h3 id="parts-master">The parts master</h3>

<figure class="docs-figure">
    <img src="/img/docs/inventory_ledger.png" alt="Parts Master">
    <figcaption>The inventory catalogue and stock levels.</figcaption>
</figure>

<p><code>_logi/inventory.php</code> holds the catalogue. The fields that do real work:</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Field</th><th>Purpose</th></tr></thead>
    <tbody>
        <tr><td><code>stock_level</code></td><td>What is on the shelf now.</td></tr>
        <tr><td><code>minimum_threshold</code></td><td>The reorder point. At or below this, the part needs replacing.</td></tr>
        <tr><td><code>maximum_stock</code></td><td>Target level — reorder quantity is calculated up to this.</td></tr>
        <tr><td><code>moq</code></td><td>Minimum order quantity the vendor will accept.</td></tr>
        <tr><td><code>auto_reorder</code></td><td>Whether crossing the threshold raises a requisition automatically.</td></tr>
        <tr><td><code>primary_vendor_id</code></td><td>Who to buy it from. Without one, WCC can warn but cannot order.</td></tr>
        <tr><td><code>standard_lead_time</code> / <code>expedited_lead_time</code></td><td>Days to delivery — the difference between "reorder now" and "reorder yesterday".</td></tr>
        <tr><td>aisle / rack / shelf / <code>bin_code</code></td><td>Where it physically is. A part nobody can find is out of stock.</td></tr>
        <tr><td><code>lifecycle_status</code></td><td>Active, Phasing Out or Obsolete — so you learn about a discontinued part before you need it.</td></tr>
    </tbody>
</table>
</div>

<h3 id="ledger">The ledger is the truth</h3>

<figure class="docs-figure">
    <img src="/img/docs/inventory_modal.png" alt="Inventory Details">
    <figcaption>Detailed view of an inventory item and its logistics.</figcaption>
</figure>

<p>
    <code>stock_level</code> is a running total. <code>inventory_ledger</code> is the record
    of how it got there — an immutable row per movement, carrying the signed quantity, the
    reason, and a reference back to whatever caused it.
</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Reason</th><th>Direction</th><th>Raised by</th></tr></thead>
    <tbody>
        <tr><td><code>ticket_consume</code></td><td>out</td><td>Parts used repairing a fault</td></tr>
        <tr><td><code>wo_consume</code></td><td>out</td><td>Parts used completing a work order</td></tr>
        <tr><td><code>po_receipt</code></td><td>in</td><td>Goods received against a purchase order</td></tr>
    </tbody>
</table>
</div>

<p>
    <code>_logi/inventory_audit.php</code> presents this as a searchable trail, and rows link
    back to the job or delivery behind them. When a stock count disagrees with the system,
    this is where the discrepancy is found — not by guessing, but by reading what happened.
</p>

<div class="docs-note">
    <span class="t">Why a ledger rather than just a number</span>
    A bare <code>stock_level</code> can only tell you that it is wrong, never why. With a
    ledger, "we are twelve bearings short" becomes a list of the twelve jobs that consumed
    them, with dates and technicians. That is the difference between an inventory system and
    a number in a box.
</div>

<h3 id="consumption">How stock is consumed</h3>

<p>Both consumption paths behave identically, which is deliberate:</p>

<ol>
    <li>The part is validated as real.</li>
    <li>The quantity is capped at what is actually on hand —
        <code>min(requested, on_hand)</code>.</li>
    <li><code>stock_level</code> is decremented by the capped amount.</li>
    <li>A ledger row records the <strong>actual</strong> quantity, not the requested one.</li>
    <li>Auto-reorder is evaluated.</li>
</ol>

<p>
    Step 2 is the important one. Recording a consumption larger than the stock on hand would
    drive the level negative, and a negative stock level is not a fact about the world — it
    is a number that will confuse everyone who sees it until somebody works out it was a data
    entry problem months earlier.
</p>

<h3 id="auto-reorder">Event-driven auto-reorder</h3>

<p>
    Reordering is not a nightly batch that notices yesterday's problem. It runs at the moment
    of consumption: if that consumption took a part to or below its minimum,
    <code>wcc_check_and_reorder()</code> considers a requisition immediately.
</p>

<p>It raises one only if all of these hold:</p>

<ul>
    <li><code>auto_reorder</code> is enabled for the part.</li>
    <li>Lifecycle status is <code>Active</code> — no automatic reordering of a part being
        phased out.</li>
    <li>Stock is genuinely at or below the threshold.</li>
    <li><strong>No open order already covers it.</strong> The part is not reordered twice —
        subsequent consumption of a part already on its way does not raise a second
        requisition.</li>
</ul>

<p>
    Quantity is <code>maximum_stock − stock_level</code>, or the MOQ if that is larger. The
    requisition is created as <code>PR-AUTO-…</code> and routed through
    <strong>exactly the same approval rules as a human requisition</strong> — automation
    decides <em>when</em> to ask, never whether approval is required.
</p>

<div class="docs-note warn">
    <span class="t">A part with no vendor cannot be ordered</span>
    If <code>primary_vendor_id</code> is empty, WCC notifies the people holding
    <code>manage_inventory</code> that stock is low and stops there. It will not invent a
    supplier. This is the "nothing silently guesses" rule in practice — and the reason to
    check that critical spares actually have a vendor set.
</div>

<p>
    A manual <em>Run reorder check</em> button on the inventory page sweeps every part using
    the same helper, for use after a stock count or an import.
</p>
