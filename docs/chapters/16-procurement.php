<p>
    Procurement is where a maintenance system touches money, which is why it is the part
    most likely to be audited. WCC models the whole path — request, approval, fulfilment,
    receipt, budget — with the authorising and receiving roles deliberately separated.
</p>

<h3 id="po-states">The nine stages</h3>

<figure class="docs-figure">
    <img src="/img/docs/po_list.png" alt="Purchase Orders List">
    <figcaption>The main Purchase Orders ledger overview.</figcaption>
</figure>

<pre><code>Draft → Pending Approval → Issued → Shipped → In Transit
      → Partially Received → Fully Received → Closed
                                    ↘ Cancelled (from any stage)</code></pre>

<div class="table-scroll">
<table>
    <thead><tr><th>Stage</th><th>Means</th><th>Requires</th></tr></thead>
    <tbody>
        <tr><td>Draft</td><td>Being written, not submitted.</td><td><code>create_purchase_requests</code></td></tr>
        <tr><td>Pending Approval</td><td>Submitted, waiting on a cost decision.</td><td>—</td></tr>
        <tr><td>Issued</td><td>Approved and sent to the vendor.</td><td><code>approve_purchase_orders</code></td></tr>
        <tr><td>Shipped</td><td>Vendor has despatched.</td><td rowspan="5"><code>fulfill_purchase_orders</code></td></tr>
        <tr><td>In Transit</td><td>On its way.</td></tr>
        <tr><td>Partially Received</td><td>Some lines received; the rest outstanding.</td></tr>
        <tr><td>Fully Received</td><td>Everything arrived. Budget is consumed here.</td></tr>
        <tr><td>Closed</td><td>Invoice matched, order finished.</td></tr>
        <tr><td>Cancelled</td><td>Called off.</td><td>—</td></tr>
    </tbody>
</table>
</div>

<p>
    <code>_trck/tracking_stepper.php</code> renders this as a visual progress bar, and every
    transition is written to <code>po_status_logs</code> with the actor and an optional note.
</p>

<h3 id="approval">Approval routing</h3>

<p>
    Routing is configured from the ⚙ Workflow modal on the Purchase Orders page — gated by
    <code>approve_purchase_orders</code>, deliberately <strong>not</strong> by
    <code>manage_settings</code>. The people who own the approval policy are the people who
    approve, not whoever happens to be a general administrator.
</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Configuration</th><th>Result</th></tr></thead>
    <tbody>
        <tr><td>Workflow disabled</td><td>Every requisition auto-approves on submit, going straight to <code>Issued</code> with approval level <em>Auto-Approved</em>.</td></tr>
        <tr><td>Enabled, auto-approve limit &gt; 0</td><td>Requisitions at or under the limit auto-approve; anything larger goes to <code>Pending Approval</code>.</td></tr>
        <tr><td>Enabled, no limit</td><td>Everything waits for a holder of <code>approve_purchase_orders</code>.</td></tr>
    </tbody>
</table>
</div>

<p>
    Auto-approvals write a log entry explaining <em>why</em> they were approved without a
    human. An audit trail that silently skips a step is worse than one that says "approved
    automatically: below the £250 threshold".
</p>

<div class="docs-note">
    <span class="t">Emergency bypass</span>
    Orders can be flagged as emergency, recorded in <code>is_emergency_bypass</code>. The
    flag does not remove the approval requirement — it marks the order so that expedited
    purchases are visible afterwards rather than invisible.
</div>

<h3 id="fulfilment">Storekeeper fulfilment</h3>

<figure class="docs-figure">
    <img src="/img/docs/po_details.png" alt="PO Tracking Details">
    <figcaption>Tracking a partially received Purchase Order and its line items.</figcaption>
</figure>

<p>
    Once an order is <code>Issued</code>, it belongs to whoever holds
    <code>fulfill_purchase_orders</code> — the Storekeeper role. They move it through
    shipping, transit and receipt.
</p>

<div class="docs-note warn">
    <span class="t">This separation is the point</span>
    A Storekeeper can receive £50,000 of goods and cannot approve a single pound of it. An
    Approver can authorise spend and cannot mark it received. One person cannot both
    authorise a purchase and confirm its arrival — which is the control that makes invoice
    fraud hard. Both permissions are checked <strong>server-side on every transition</strong>,
    not merely reflected in which buttons are drawn.
</div>

<h3 id="receipt">Goods receipt and budgets</h3>

<p>Receiving a line does three things at once:</p>

<ol>
    <li>Increments <code>received_qty</code> on the line. Partial receipt is simply
        <code>received_qty &lt; ordered_qty</code> — no separate state to maintain.</li>
    <li>Raises <code>stock_level</code> on the part.</li>
    <li>Writes an <code>inventory_ledger</code> row with reason <code>po_receipt</code>,
        referencing the order.</li>
</ol>

<p>
    Budget is consumed when the order reaches <strong>Fully Received</strong>, not when it is
    approved or issued. Money is counted against a department when the goods actually
    arrive, so budget reflects what the plant has, not what it has asked for.
    <code>department_budget_logs</code> records each movement, and the Departments screen
    shows allocated against consumed.
</p>

<p>
    Two document types attach to an order: a generated requisition PDF, and an uploaded
    supplier invoice — both stored in <code>po_documents</code>, so the paperwork sits with
    the order rather than in somebody's mailbox.
</p>
