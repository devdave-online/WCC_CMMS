<?php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('approve_purchase_orders');

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/audit.php';
$pdo = get_wcc_db_connection();

try {
    // Handle Status Updates (Only Admins)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_po_id'])) {
        if (can('approve_purchase_orders')) {
            $action_po_id = (int)$_POST['action_po_id'];
            $new_status = $_POST['new_status'] ?? '';
            $user_id = $_SESSION['user_id'];
            
            // Handle State Change (Approve/Reject)
            if ($new_status) {
                // Get old status
                $old_st = $pdo->prepare("SELECT status FROM purchase_orders WHERE po_id = ?");
                $old_st->execute([$action_po_id]);
                $old_status = $old_st->fetchColumn();
                
                $pdo->prepare("UPDATE purchase_orders SET status = ? WHERE po_id = ?")->execute([$new_status, $action_po_id]);
                
                // Log
                $pdo->prepare("INSERT INTO po_status_logs (po_id, action_type, status_from, status_to, changed_by) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$action_po_id, 'Status Update', $old_status, $new_status, $user_id]);
            }
            
            // Handle Receiving
            if (isset($_POST['process_receipt'])) {
                $receive_qty = $_POST['receive_qty'] ?? [];
                
                foreach($receive_qty as $po_item_id => $qty_to_receive) {
                    $po_item_id = (int)$po_item_id;
                    $qty_to_receive = (int)$qty_to_receive;
                    
                    if ($qty_to_receive > 0) {
                        $itm_st = $pdo->prepare("SELECT pi.part_id, pi.ordered_qty, pi.received_qty, pi.status, ip.part_name FROM po_items pi LEFT JOIN inventory_parts ip ON pi.part_id = ip.part_id WHERE pi.po_item_id = ?");
                        $itm_st->execute([$po_item_id]);
                        $itm = $itm_st->fetch();
                        
                        if ($itm) {
                            // Prevent over-receiving
                            $remaining = $itm['ordered_qty'] - $itm['received_qty'];
                            if ($qty_to_receive > $remaining) {
                                $qty_to_receive = $remaining;
                            }
                            if ($qty_to_receive <= 0) continue;
                            
                            $old_item_status = $itm['status'];
                            $new_rcvd = $itm['received_qty'] + $qty_to_receive;
                            $itm_status = ($new_rcvd >= $itm['ordered_qty']) ? 'Received' : 'Pending';
                            
                            $pdo->prepare("UPDATE po_items SET received_qty = ?, status = ? WHERE po_item_id = ?")
                                ->execute([$new_rcvd, $itm_status, $po_item_id]);
                                
                            // Update Inventory + Phase 5 ledger + audit
                            $pdo->prepare("UPDATE inventory_parts SET stock_level = stock_level + ? WHERE part_id = ?")
                                ->execute([$qty_to_receive, $itm['part_id']]);
                            $pdo->prepare("INSERT INTO inventory_ledger (part_id, change_qty, reason, reference_type, reference_id, actor_user_id) VALUES (?, ?, 'po_receipt', 'purchase_orders', ?, ?)")
                                ->execute([$itm['part_id'], $qty_to_receive, $action_po_id, $user_id]);
                            wcc_audit_log('inventory.receipt', 'inventory_parts', $itm['part_id'], null, ['qty' => $qty_to_receive], 'PO ' . $action_po_id . ' receipt');
                                
                            // Log Line item receipt with part name
                            $part_label = $itm['part_name'] ?? ('Part #' . $itm['part_id']);
                            $pdo->prepare("INSERT INTO po_status_logs (po_id, action_type, status_from, status_to, changed_by) VALUES (?, ?, ?, ?, ?)")
                                ->execute([$action_po_id, "Received $qty_to_receive × $part_label ($new_rcvd/{$itm['ordered_qty']})", $old_item_status, $itm_status, $user_id]);
                        }
                    }
                }
                
                // Re-evaluate PO Status
                $chk = $pdo->prepare("SELECT SUM(ordered_qty) as t_ord, SUM(received_qty) as t_rcv FROM po_items WHERE po_id = ?");
                $chk->execute([$action_po_id]);
                $totals = $chk->fetch();
                
                $old_po_st = $pdo->prepare("SELECT status FROM purchase_orders WHERE po_id = ?");
                $old_po_st->execute([$action_po_id]);
                $old_po_status = $old_po_st->fetchColumn();
                
                $final_status = ($totals['t_rcv'] >= $totals['t_ord']) ? 'Fully Received' : 'Partially Received';
                $pdo->prepare("UPDATE purchase_orders SET status = ? WHERE po_id = ?")->execute([$final_status, $action_po_id]);
                
                if ($final_status === 'Fully Received' && $old_po_status !== 'Fully Received') {
                    $po_info_stmt = $pdo->prepare("SELECT dept_id, total_amount, po_number FROM purchase_orders WHERE po_id = ?");
                    $po_info_stmt->execute([$action_po_id]);
                    $po_info = $po_info_stmt->fetch();
                    if ($po_info && $po_info['dept_id']) {
                        $pdo->prepare("UPDATE departments SET budget_consumed = budget_consumed + ? WHERE dept_id = ?")->execute([$po_info['total_amount'], $po_info['dept_id']]);
                        $pdo->prepare("INSERT INTO department_budget_logs (dept_id, action_type, amount, notes, changed_by) VALUES (?, 'Consume', ?, ?, ?)")->execute([$po_info['dept_id'], $po_info['total_amount'], "PO Received: " . $po_info['po_number'], $user_id]);
                    }
                }
                
                $pdo->prepare("INSERT INTO po_status_logs (po_id, action_type, status_from, status_to, changed_by) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$action_po_id, 'Receipt Processed', $old_po_status, $final_status, $user_id]);
            }
            header("Location: /_logi/purchase_orders.php");
            exit;
        }
    }

    $stmt = $pdo->query("
        SELECT p.*, v.vendor_name, u.username
        FROM purchase_orders p 
        LEFT JOIN vendors_suppliers v ON p.vendor_id = v.vendor_id 
        LEFT JOIN users u ON p.created_by = u.user_id 
        ORDER BY p.po_id DESC
    ");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $item_stmt = $pdo->query("
        SELECT i.*, p.part_name, p.internal_code 
        FROM po_items i
        LEFT JOIN inventory_parts p ON i.part_id = p.part_id
    ");
    $all_items = $item_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $po_details = [];
    foreach($all_items as $itm) {
        $po_details[$itm['po_id']][] = $itm;
    }
    
    // Fetch Audit Logs
    $log_stmt = $pdo->query("
        SELECT l.*, u.username 
        FROM po_status_logs l
        LEFT JOIN users u ON l.changed_by = u.user_id
        ORDER BY l.created_at DESC
    ");
    $all_logs = $log_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $po_logs = [];
    foreach($all_logs as $lg) {
        $po_logs[$lg['po_id']][] = $lg;
    }

} catch (PDOException $e) { die("DB Error: " . $e->getMessage()); }
require_once __DIR__ . '/../rbac.php';
require_once __DIR__ . '/../_trck/tracking_stepper.php';
?>
<?php
$page_title = 'Enterprise PO Ledger';
require_once __DIR__ . '/../inc/head.php';
?>
    <style>
        .filter-token { background: var(--panel-bg); border: 1px solid var(--text-accent); border-radius: 16px; padding: 4px 12px; font-size: 0.85em; color: var(--text-primary); display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); backdrop-filter: blur(10px); animation: fadeIn 0.2s ease-out; }
        .filter-token span { font-weight: bold; color: var(--text-accent); }
        .filter-token-close { cursor: pointer; color: var(--danger); font-weight: bold; transition: transform 0.2s; }
        .filter-token-close:hover { transform: scale(1.2); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
        
        .parent-row { cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border-bottom: 1px solid rgba(255,255,255,0.05); }
        .parent-row:hover { background: rgba(255, 255, 255, 0.08); box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 10; position: relative; }
        .child-row { display: none; background: rgba(0,0,0,0.4); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(255,255,255,0.05); transition: all 0.3s; }
        .child-row.open { display: table-row; animation: slideDown 0.3s ease-out; }
        .child-content { padding: 25px; border-left: 4px solid var(--text-accent); background: linear-gradient(90deg, rgba(56, 189, 248, 0.05), transparent); }
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
        .detail-card { background: rgba(255,255,255,0.03); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.08); box-shadow: inset 0 0 20px rgba(255,255,255,0.01); backdrop-filter: blur(5px); }
        .detail-card h4 { margin: 0 0 10px 0; color: var(--text-accent); font-size: 0.9em; text-transform: uppercase; letter-spacing: 1px; }
        .status-badge { padding: 6px 14px; border-radius: 20px; font-size: 0.85em; font-weight: 600; text-shadow: 0 1px 2px rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.2); box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: inline-block; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .row-arrow svg { transition: transform 0.3s; width: 18px; height: 18px; vertical-align: middle; margin-right: 8px; color: var(--text-secondary); }
        .parent-row.open .row-arrow svg { transform: rotate(90deg); color: var(--text-accent); }
        
        /* Table inside details for line items */
        .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .items-table th, .items-table td { padding: 8px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); font-size: 0.9em; }
        .items-table th { color: var(--text-secondary); }
        
        .action-panel { background: rgba(0,0,0,0.1); padding: 10px 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); margin-top: 15px; display:flex; justify-content: space-between; align-items:center; }
        .action-btn { background: var(--panel-bg); color: var(--text-primary); border: 1px solid var(--text-accent); padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.85em; transition: 0.2s; }
        .action-btn:hover { background: var(--text-accent); color: #fff; }
        .action-btn.reject { border-color: #ef4444; color: #ef4444; }
        .action-btn.reject:hover { background: #ef4444; color: #fff; }
        .action-btn.receive { border-color: #10b981; color: #10b981; }
        .action-btn.receive:hover { background: #10b981; color: #fff; }
    </style>
    <script>
        // --- Accordion Logic ---
        function toggleAccordion(poId, rowElement) {
            const detailsRow = document.getElementById('details-' + poId);
            if (!detailsRow) return;
            const isOpen = detailsRow.classList.contains('open');
            // Close all
            document.querySelectorAll('.child-row').forEach(row => row.classList.remove('open'));
            document.querySelectorAll('.parent-row').forEach(row => row.classList.remove('open'));
            if (!isOpen) {
                detailsRow.classList.add('open');
                rowElement.classList.add('open');
            }
        }

        // --- Restore Search Logic ---
        let activeFilters = [];
        let filterIdCounter = 0;
        let activeColumnIndex = -1;

        function getColumnName(index) {
            let th = document.querySelector("#poTable thead tr").children[index];
            return th ? (th.innerText || th.textContent).trim() : "Column";
        }

        function createFilterToken(colIndex, query) {
            let colName = getColumnName(colIndex);
            let id = 'filter-' + filterIdCounter++;
            activeFilters.push({ id: id, colIndex: colIndex, query: query.toUpperCase() });
            let area = document.getElementById('activeFiltersArea');
            let token = document.createElement('div');
            token.id = id;
            token.className = 'filter-token';
            token.innerHTML = '<span>' + colName + ':</span> ' + query + ' <div class="filter-token-close" onclick="removeFilterToken(\'' + id + '\')">✖</div>';
            area.appendChild(token);
        }

        function removeFilterToken(id) {
            activeFilters = activeFilters.filter(f => f.id !== id);
            let token = document.getElementById(id);
            if (token) token.remove();
            filterTable();
        }

        function handleSearchInput(ev) {
            if (ev.key === 'Enter' && activeColumnIndex > -1 && ev.target.value.trim() !== '') {
                lockToken();
            } else {
                filterTable();
            }
        }

        function lockToken() {
            var input = document.getElementById("ledgerSearch");
            var query = input.value.trim();
            if (query !== '' && activeColumnIndex > -1) {
                createFilterToken(activeColumnIndex, query);
                input.value = '';
                resetSearchPosition();
            }
        }

        function filterTable() {
            var input = document.getElementById("ledgerSearch");
            var globalFilter = input.value.toUpperCase();
            var table = document.getElementById("poTable");
            var tr = table.getElementsByClassName("parent-row");

            for (let i = 0; i < tr.length; i++) {
                let matchFound = true;
                let tds = tr[i].getElementsByTagName("td");

                for (let f of activeFilters) {
                    let cell = tds[f.colIndex];
                    if (cell) {
                        let txt = cell.textContent || cell.innerText;
                        if (txt.toUpperCase().indexOf(f.query) === -1) { matchFound = false; break; }
                    }
                }

                if (matchFound && globalFilter !== "") {
                    if (activeColumnIndex > -1) {
                        let cell = tds[activeColumnIndex];
                        if (cell) {
                            let txt = cell.textContent || cell.innerText;
                            if (txt.toUpperCase().indexOf(globalFilter) === -1) matchFound = false;
                        }
                    } else {
                        let globalMatch = false;
                        for (let j = 0; j < tds.length; j++) { 
                            if (tds[j]) {
                                let txt = tds[j].textContent || tds[j].innerText;
                                if (txt.toUpperCase().indexOf(globalFilter) > -1) { globalMatch = true; break; }
                            }
                        }
                        if (!globalMatch) matchFound = false;
                    }
                }

                tr[i].style.display = matchFound ? "" : "none";
            }
        }

        function allowDrop(ev) { ev.preventDefault(); }
        function dragSearch(ev) { ev.dataTransfer.setData("text", ev.target.id); }
        function dropSearch(ev, thElement) {
            ev.preventDefault();
            var container = document.getElementById("searchContainerOrig");
            var input = document.getElementById("ledgerSearch");
            var lockBtn = document.getElementById("lockTokenBtn");
            var colIndex = Array.from(thElement.parentNode.children).indexOf(thElement);
            var query = input.value.trim();

            if (query !== '') {
                createFilterToken(colIndex, query);
                input.value = '';
                resetSearchPosition();
            } else {
                thElement.appendChild(container);
                container.style.marginTop = '10px';
                input.style.width = '100%';
                input.placeholder = 'Type & click 📌 to Lock';
                activeColumnIndex = colIndex;
                lockBtn.style.display = 'block';
                input.focus();
                filterTable();
            }
        }

        function resetSearchPosition() {
            let wrapper = document.getElementById('searchWrapper');
            let container = document.getElementById('searchContainerOrig');
            let input = document.getElementById('ledgerSearch');
            let lockBtn = document.getElementById('lockTokenBtn');
            
            wrapper.appendChild(container);
            container.style.marginTop = '0';
            input.style.width = '';
            input.placeholder = 'Search purchase orders... (Drag to column)';
            lockBtn.style.display = 'none';
            activeColumnIndex = -1;
            filterTable();
        }

    </script>
<?php include __DIR__ . '/../nav.php'; ?>
    <div class="dashboard-container">
        <div class="page-header" style="margin-bottom:10px;">
            <h1>Enterprise Purchase Order Management</h1>
            <div style="display:flex; gap:10px; align-items:center;">
                <div id="searchWrapper">
                    <div id="searchContainerOrig" style="display:inline-block; position:relative; width:100%;" draggable="true" ondragstart="dragSearch(event)">
                        <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); pointer-events:none; font-size:1.1em;">🔍</span>
                        <input type="text" id="ledgerSearch" onkeyup="handleSearchInput(event)" ondblclick="resetSearchPosition()" placeholder="Search purchase orders... (Drag to column)" style="padding:8px 35px 8px 35px; border-radius:20px; border: 1px solid var(--text-accent); background:var(--input-bg); color:var(--text-primary); transition: all 0.3s; box-sizing: border-box;">
                        <span id="lockTokenBtn" onclick="lockToken()" style="display:none; position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:1.1em; transition: transform 0.2s;" title="Lock Token" onmouseover="this.style.transform='translateY(-50%) scale(1.2)'" onmouseout="this.style.transform='translateY(-50%) scale(1)'">🔒</span>
                    </div>
                </div>
            </div>
        </div>
        <div id="activeFiltersArea" style="display:flex; gap:8px; margin-bottom:15px; flex-wrap:wrap; min-height:30px;"></div>
        
        <div class="table-container" style="overflow-x:auto;">
            <table class="data-table" id="poTable">
                <thead>
                    <tr>
                        <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">PO #</th>
                        <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Vendor</th>
                        <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Total Amount</th>
                        <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Status</th>
                        <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Approval Level</th>
                        <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Date</th>
                    </tr>
                </thead>
                <tbody>
            <?php foreach($items as $i): 
                $badgeColors = [
                    'Draft' => 'background: linear-gradient(135deg, #475569, #334155); color: #cbd5e1;',
                    'Pending Approval' => 'background: linear-gradient(135deg, #ca8a04, #a16207); color: #fef08a;',
                    'Approved' => 'background: linear-gradient(135deg, #16a34a, #15803d); color: #bbf7d0;',
                    'Issued' => 'background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #bfdbfe;',
                    'In Transit' => 'background: linear-gradient(135deg, #9333ea, #7e22ce); color: #e9d5ff;',
                    'Partially Received' => 'background: linear-gradient(135deg, #0d9488, #0f766e); color: #ccfbf1;',
                    'Fully Received' => 'background: linear-gradient(135deg, #16a34a, #15803d); color: #bbf7d0;',
                    'Closed' => 'background: linear-gradient(135deg, #3f3f46, #27272a); color: #a1a1aa;',
                    'Cancelled' => 'background: linear-gradient(135deg, #dc2626, #b91c1c); color: #fecaca;'
                ];
                $badgeStyle = $badgeColors[$i['status']] ?? 'background: rgba(255,255,255,0.1); color: #fff;';
            ?>
                <tr class="parent-row" onclick="toggleAccordion(<?= $i['po_id'] ?>, this)">
                    <td><span class="row-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></span><strong><?= htmlspecialchars($i['po_number']) ?></strong></td>
                    <td><?= htmlspecialchars($i['vendor_name'] ?? 'Unknown') ?></td>
                    <td style="font-family: monospace; font-size: 1.1em; color: var(--text-accent);">$<?= number_format($i['total_amount'], 2) ?></td>
                    <td><span class="status-badge" style="<?= $badgeStyle ?>"><?= htmlspecialchars($i['status']) ?></span></td>
                    <td><span style="color:var(--text-secondary);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" style="vertical-align:middle; margin-right:4px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg><?= htmlspecialchars($i['approval_level']) ?></span></td>
                    <td style="color:var(--text-secondary);"><?= date('M j, Y', strtotime($i['created_at'])) ?></td>
                </tr>
                <tr class="child-row" id="details-<?= $i['po_id'] ?>">
                    <td colspan="12" style="padding:0;">
                        <div class="child-content">
                            <div class="grid-4" style="margin-bottom: 20px;">
                                <div style="grid-column: span 4; margin-bottom: 5px;">
                                    <?php 
                                    $is_admin = can('approve_purchase_orders');
                                    render_tracking_stepper($i['status'], $is_admin, $i['po_id'], $i['username'] ?? 'System'); 
                                    ?>
                                </div>
                                <div class="detail-card" style="grid-column: span 3;">
                                    <h4>Line Items</h4>
                                    <form method="POST">
                                        <input type="hidden" name="action_po_id" value="<?= $i['po_id'] ?>">
                                        <table class="items-table">
                                            <thead>
                                                <tr>
                                                    <th>Part SKU</th>
                                                    <th>Description</th>
                                                    <th>Qty Ordered</th>
                                                    <th>Qty Rcvd</th>
                                                    <th>Unit Price</th>
                                                    <th>Status</th>
                                                    <?php if(in_array($i['status'], ['In Transit', 'Partially Received'])): ?>
                                                        <th>Receive Qty</th>
                                                    <?php endif; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(isset($po_details[$i['po_id']])): ?>
                                                    <?php foreach($po_details[$i['po_id']] as $itm): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($itm['internal_code'] ?? 'N/A') ?></td>
                                                        <td><?= htmlspecialchars($itm['part_name'] ?? 'Unknown Part') ?></td>
                                                        <td><?= htmlspecialchars($itm['ordered_qty']) ?></td>
                                                        <td><?= htmlspecialchars($itm['received_qty']) ?></td>
                                                        <td>$<?= number_format($itm['unit_price'], 2) ?></td>
                                                        <td><?= htmlspecialchars($itm['status']) ?></td>
                                                        <?php if(in_array($i['status'], ['In Transit', 'Partially Received'])): ?>
                                                            <td>
                                                                <?php if($itm['status'] !== 'Received'): ?>
                                                                    <input type="number" name="receive_qty[<?= $itm['po_item_id'] ?>]" min="0" max="<?= $itm['ordered_qty'] - $itm['received_qty'] ?>" value="0" style="width:60px; padding: 4px;">
                                                                <?php else: ?>
                                                                    -
                                                                <?php endif; ?>
                                                            </td>
                                                        <?php endif; ?>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr><td colspan="7">No line items attached.</td></tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                        <?php if(in_array($i['status'], ['In Transit', 'Partially Received'])): ?>
                                            <button type="submit" name="process_receipt" value="1" class="action-btn receive" style="margin-top: 10px; float:right;">📦 Process Receipt</button>
                                            <div style="clear:both;"></div>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                                <div class="detail-card">
                                    <h4>Lifecycle Audit Trail (IATF Compliant)</h4>
                                    <ul style="list-style: none; padding: 0; font-size: 0.9em; margin: 0; max-height: 150px; overflow-y: auto;">
                                        <?php if(isset($po_logs[$i['po_id']])): ?>
                                            <?php foreach($po_logs[$i['po_id']] as $log): ?>
                                                <li style="border-bottom: 1px solid rgba(255,255,255,0.1); padding: 5px 0;">
                                                    <span style="color:var(--text-secondary);">[<?= $log['created_at'] ?>]</span> 
                                                    <strong><?= htmlspecialchars($log['username'] ?? 'System') ?></strong>: 
                                                    <?= htmlspecialchars($log['action_type']) ?> 
                                                    <?php if($log['status_to']): ?>
                                                        <span style="color:var(--text-accent);">(&rarr; <?= htmlspecialchars($log['status_to']) ?>)</span>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li>No audit logs found.</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                

                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
    function openPRDocument(poId) {
        window.open('/_logi/pr_document.php?po_id=' + poId, 'PRDocument', 'width=900,height=800,menubar=no,toolbar=no');
    }
    </script>
</body>
</html>



