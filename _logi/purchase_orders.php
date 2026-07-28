<?php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
// Approvers (cost sign-off) and Storekeepers (fulfilment) both work this ledger.
if (!can('approve_purchase_orders') && !can('fulfill_purchase_orders')) {
    require_perm('fulfill_purchase_orders'); // neither held → styled Access Denied + exit
}

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/audit.php';
require_once __DIR__ . '/../inc/csrf.php';
$pdo = get_wcc_db_connection();

try {
    // Procurement workflow policy (toggle + auto-approve limit) lives HERE, with the
    // people who run procurement — and is gated by the cost-approval permission,
    // NOT the generic manage_settings admin permission.
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_procurement') {
        wcc_csrf_require();
        if (can('approve_purchase_orders')) {
            $enabled = isset($_POST['procurement_workflow_enabled']) ? '1' : '0';
            $limit = max(0, (float)($_POST['po_auto_approve_limit'] ?? 0));
            $pdo->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_key = 'procurement_workflow_enabled'")->execute([$enabled]);
            $pdo->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_key = 'po_auto_approve_limit'")->execute([(string)$limit]);
        }
        header("Location: /_logi/purchase_orders.php" . (can('approve_purchase_orders') ? "?msg=workflow_saved" : ""));
        exit;
    }

    // Load procurement workflow settings (self-heal defaults if missing)
    $proc_defaults = ['procurement_workflow_enabled' => '1', 'po_auto_approve_limit' => '0'];
    $proc_settings = [];
    foreach ($proc_defaults as $key => $default) {
        $stmt = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        if ($stmt->rowCount() == 0) {
            $pdo->prepare("INSERT INTO app_settings (category, setting_key, setting_value) VALUES ('Procurement', ?, ?)")->execute([$key, $default]);
            $proc_settings[$key] = $default;
        } else {
            $proc_settings[$key] = $stmt->fetchColumn();
        }
    }

    // Add a free-text comment to the audit trail (comment on any step).
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_po_comment') {
        wcc_csrf_require();
        if (can('approve_purchase_orders') || can('fulfill_purchase_orders')) {
            $c_po_id = (int)($_POST['comment_po_id'] ?? 0);
            $c_note  = trim($_POST['comment_note'] ?? '');
            if ($c_po_id > 0 && $c_note !== '') {
                $pdo->prepare("INSERT INTO po_status_logs (po_id, action_type, note, changed_by) VALUES (?, 'Comment', ?, ?)")
                    ->execute([$c_po_id, $c_note, $_SESSION['user_id']]);
            }
        }
        header("Location: /_logi/purchase_orders.php");
        exit;
    }

    // Upload a supplier invoice document (available once the PO is in motion).
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_invoice') {
        wcc_csrf_require();
        if ((can('approve_purchase_orders') || can('fulfill_purchase_orders')) && isset($_FILES['invoice_file']) && $_FILES['invoice_file']['error'] === UPLOAD_ERR_OK) {
            $inv_po_id = (int)($_POST['invoice_po_id'] ?? 0);
            $tmp  = $_FILES['invoice_file']['tmp_name'];
            $orig = $_FILES['invoice_file']['name'];
            $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            $allowed = ['pdf','png','jpg','jpeg','webp'];
            if ($inv_po_id > 0 && in_array($ext, $allowed) && is_uploaded_file($tmp)) {
                $fname = 'invoice_po' . $inv_po_id . '_' . time() . '.' . $ext;
                $dest  = __DIR__ . '/../uploads/invoices/' . $fname;
                if (move_uploaded_file($tmp, $dest)) {
                    // One invoice per PO: replace any existing.
                    $pdo->prepare("DELETE FROM po_documents WHERE po_id = ? AND doc_type = 'invoice'")->execute([$inv_po_id]);
                    $pdo->prepare("INSERT INTO po_documents (po_id, doc_type, file_path, original_name, uploaded_by) VALUES (?, 'invoice', ?, ?, ?)")
                        ->execute([$inv_po_id, '/uploads/invoices/' . $fname, $orig, $_SESSION['user_id']]);
                    $pdo->prepare("INSERT INTO po_status_logs (po_id, action_type, note, changed_by) VALUES (?, 'Invoice Attached', ?, ?)")
                        ->execute([$inv_po_id, htmlspecialchars($orig), $_SESSION['user_id']]);
                }
            }
        }
        header("Location: /_logi/purchase_orders.php");
        exit;
    }

    // Handle Status Updates — cost sign-off vs fulfilment are separately gated.
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_po_id'])) {
        wcc_csrf_require();
        if (can('approve_purchase_orders') || can('fulfill_purchase_orders')) {
            $action_po_id = (int)$_POST['action_po_id'];
            $new_status = $_POST['new_status'] ?? '';
            $user_id = $_SESSION['user_id'];
            $step_note = trim($_POST['step_note'] ?? '');

            // Handle State Change — authorize the specific transition by permission.
            if ($new_status) {
                $transition_ok = false;
                if ($new_status === 'Issued') {
                    $transition_ok = can('approve_purchase_orders');                 // cost sign-off
                } elseif (in_array($new_status, ['Shipped', 'In Transit', 'Closed'])) {
                    $transition_ok = can('fulfill_purchase_orders');                  // logistics
                } elseif ($new_status === 'Cancelled') {
                    $transition_ok = can('approve_purchase_orders') || can('fulfill_purchase_orders');
                }

                if ($transition_ok) {
                    // Get old status
                    $old_st = $pdo->prepare("SELECT status FROM purchase_orders WHERE po_id = ?");
                    $old_st->execute([$action_po_id]);
                    $old_status = $old_st->fetchColumn();

                    $pdo->prepare("UPDATE purchase_orders SET status = ? WHERE po_id = ?")->execute([$new_status, $action_po_id]);

                    // Log (with optional per-step comment)
                    $pdo->prepare("INSERT INTO po_status_logs (po_id, action_type, status_from, status_to, note, changed_by) VALUES (?, ?, ?, ?, ?, ?)")
                        ->execute([$action_po_id, 'Status Update', $old_status, $new_status, ($step_note !== '' ? $step_note : null), $user_id]);
                }
            }

            // Handle Receiving (fulfilment only)
            if (isset($_POST['process_receipt']) && can('fulfill_purchase_orders')) {
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

    // Fetch attached documents (invoices) keyed by po_id
    $po_docs = [];
    try {
        $doc_stmt = $pdo->query("SELECT po_id, doc_type, file_path, original_name FROM po_documents");
        foreach($doc_stmt->fetchAll(PDO::FETCH_ASSOC) as $d) {
            $po_docs[$d['po_id']][$d['doc_type']] = $d;
        }
    } catch (Exception $e) { /* table may not exist pre-migration */ }

} catch (PDOException $e) { die("DB Error: " . $e->getMessage()); }
require_once __DIR__ . '/../rbac.php';
require_once __DIR__ . '/../_trck/tracking_stepper.php';
?>
<?php
$page_title = __('po.title');
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
        /* Solid fills so text stays legible in BOTH light and dark themes */
        .action-btn { background: linear-gradient(135deg, var(--sky-600), var(--sky-700)); color: #fff; border: 1px solid transparent; padding: 7px 14px; border-radius: 8px; cursor: pointer; font-size: 0.85em; font-weight: 600; transition: filter 0.2s, transform 0.2s, box-shadow 0.2s; box-shadow: 0 2px 6px rgba(0,0,0,0.25); }
        .action-btn:hover { filter: brightness(1.12); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
        .action-btn.reject { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .action-btn.receive { background: linear-gradient(135deg, #10b981, #059669); }
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
            if (typeof wccAppendFilterToken === 'function') { wccAppendFilterToken(area, token); } else { area.appendChild(token); }
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
        
            if (typeof wccRefreshSearchMatchCount === 'function') {
                wccRefreshSearchMatchCount(tr, globalFilter !== '' || activeFilters.length > 0);
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
            input.placeholder = (typeof t === 'function' ? t('search.placeholder_po') : 'Search purchase orders... (Drag to column)');
            lockBtn.style.display = 'none';
            activeColumnIndex = -1;
            filterTable();
        }

    
        // search match count init — show total on load
        (function () {
            function wccInitSearchMatchCount() {
                if (typeof filterTable === 'function') filterTable();
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', wccInitSearchMatchCount);
            } else {
                wccInitSearchMatchCount();
            }
        })();
    </script>
<?php include __DIR__ . '/../nav.php'; ?>
    <div class="dashboard-container">
        <div class="page-header" style="margin-bottom:10px;">
            <h1><?= __e('po.title') ?></h1>
            <div style="display:flex; gap:10px; align-items:center;">
                <?php if (can('manage_settings')): ?>
                <a href="/_mgmt/admin_panel.php" class="nav-btn" style="white-space:nowrap;">← Return to Admin Panel</a>
                <?php endif; ?>
                <?php if (can('approve_purchase_orders')): ?>
                <button type="button" class="pill-btn pill-info" onclick="openWccModal('workflowModal')" title="Approval workflow settings" style="white-space:nowrap;">⚙️ Workflow</button>
                <?php endif; ?>
                <div id="searchWrapper">
                    <div id="searchContainerOrig" style="display:inline-block; position:relative; width:100%;" draggable="true" ondragstart="dragSearch(event)">
                        <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); pointer-events:none; font-size:1.1em;">🔍</span>
                        <input type="text" id="ledgerSearch" onkeyup="handleSearchInput(event)" ondblclick="resetSearchPosition()" placeholder="<?= __e('search.placeholder_po') ?>" style="padding:8px 35px 8px 35px; border-radius:20px; border: 1px solid var(--text-accent); background:var(--input-bg); color:var(--text-primary); transition: all 0.3s; box-sizing: border-box;">
                        <span id="lockTokenBtn" onclick="lockToken()" style="display:none; position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:1.1em; transition: transform 0.2s;" title="Lock Token" onmouseover="this.style.transform='translateY(-50%) scale(1.2)'" onmouseout="this.style.transform='translateY(-50%) scale(1)'">🔒</span>
                    </div>
                </div>
            </div>
        </div>
        <div id="activeFiltersArea" style="display:flex; gap:8px; margin-bottom:15px; flex-wrap:wrap; min-height:30px; align-items:center;">
            <span id="searchMatchCount" class="search-match-count" aria-live="polite"></span>
        </div>
        
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
            <?php
            $can_approve = can('approve_purchase_orders'); // cost sign-off
            $can_fulfill = can('fulfill_purchase_orders'); // ship / receive / close
            ?>
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
                                    render_tracking_stepper($i['status'], $can_approve, $i['po_id'], $i['username'] ?? 'System', $can_fulfill);
                                    ?>
                                </div>
                                <div class="detail-card" style="grid-column: span 3;">
                                    <h4>Line Items</h4>
                                    <form method="POST">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(wcc_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
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
                                                    <?php if(in_array($i['status'], ['In Transit', 'Partially Received']) && $can_fulfill): ?>
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
                                                        <?php if(in_array($i['status'], ['In Transit', 'Partially Received']) && $can_fulfill): ?>
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
                                        <?php if(in_array($i['status'], ['In Transit', 'Partially Received']) && $can_fulfill): ?>
                                            <button type="submit" name="process_receipt" value="1" class="action-btn receive" style="margin-top: 10px; float:right;">📦 Process Receipt</button>
                                            <div style="clear:both;"></div>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                                <div class="detail-card">
                                    <h4>Lifecycle Audit Trail (IATF Compliant)</h4>
                                    <ul style="list-style: none; padding: 0; font-size: 0.9em; margin: 0 0 12px; max-height: 180px; overflow-y: auto;">
                                        <?php if(isset($po_logs[$i['po_id']])): ?>
                                            <?php foreach($po_logs[$i['po_id']] as $log): ?>
                                                <li style="border-bottom: 1px solid rgba(255,255,255,0.1); padding: 6px 0;">
                                                    <div>
                                                        <span style="color:var(--text-secondary);">[<?= $log['created_at'] ?>]</span>
                                                        <strong><?= htmlspecialchars($log['username'] ?? 'System') ?></strong>:
                                                        <?php if(($log['action_type'] ?? '') === 'Comment'): ?>
                                                            <span style="color:var(--warning);">💬 Comment</span>
                                                        <?php else: ?>
                                                            <?= htmlspecialchars($log['action_type']) ?>
                                                        <?php endif; ?>
                                                        <?php if($log['status_to']): ?>
                                                            <span style="color:var(--text-accent);">(&rarr; <?= htmlspecialchars($log['status_to']) ?>)</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if(!empty($log['note'])): ?>
                                                        <div style="margin-top:3px; padding:6px 10px; background:rgba(255,255,255,0.04); border-left:3px solid var(--text-accent); border-radius:4px; color:var(--text-primary); font-size:0.95em; white-space:pre-wrap;"><?= htmlspecialchars($log['note']) ?></div>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li>No audit logs found.</li>
                                        <?php endif; ?>
                                    </ul>
                                    <?php if(!in_array($i['status'], ['Closed', 'Cancelled'])): ?>
                                    <form method="POST" style="display:flex; gap:8px; align-items:flex-end;">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(wcc_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="action" value="add_po_comment">
                                        <input type="hidden" name="comment_po_id" value="<?= $i['po_id'] ?>">
                                        <div style="flex:1;">
                                            <label style="font-size:0.8em; color:var(--text-secondary);">Add a note to this step</label>
                                            <input type="text" name="comment_note" required placeholder="e.g. Vendor confirmed dispatch, tracking #..." style="width:100%; padding:8px; border-radius:6px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--input-text); box-sizing:border-box;">
                                        </div>
                                        <button type="submit" class="pill-btn pill-info pill-sm" style="white-space:nowrap;">💬 Post</button>
                                    </form>
                                    <?php endif; ?>
                                </div>

                                <div class="detail-card">
                                    <h4>Documents</h4>
                                    <div style="display:flex; flex-direction:column; gap:10px;">
                                        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:8px 10px; background:rgba(255,255,255,0.03); border-radius:8px;">
                                            <span style="color:var(--text-primary);">📄 Purchase Requisition</span>
                                            <button type="button" class="pill-btn pill-info pill-sm" onclick="openDoc('/_logi/pr_document.php?po_id=<?= $i['po_id'] ?>&type=pr')">View / Print</button>
                                        </div>
                                        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:8px 10px; background:rgba(255,255,255,0.03); border-radius:8px;">
                                            <span style="color:var(--text-primary);">🧾 Supplier Invoice</span>
                                            <?php if(isset($po_docs[$i['po_id']]['invoice'])): ?>
                                                <button type="button" class="pill-btn pill-info pill-sm" onclick="openDoc('<?= htmlspecialchars($po_docs[$i['po_id']]['invoice']['file_path']) ?>')" title="<?= htmlspecialchars($po_docs[$i['po_id']]['invoice']['original_name'] ?? '') ?>">View Invoice</button>
                                            <?php else: ?>
                                                <span style="color:var(--text-muted); font-size:0.85em;">Not attached</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if(in_array($i['status'], ['Shipped','In Transit','Partially Received','Fully Received','Closed'])): ?>
                                        <form method="POST" enctype="multipart/form-data" style="display:flex; gap:8px; align-items:center; margin-top:4px;">
                                            <input type="hidden" name="csrf" value="<?= htmlspecialchars(wcc_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="action" value="upload_invoice">
                                            <input type="hidden" name="invoice_po_id" value="<?= $i['po_id'] ?>">
                                            <input type="file" name="invoice_file" accept=".pdf,.png,.jpg,.jpeg,.webp" required style="flex:1; font-size:0.82em; color:var(--text-secondary);">
                                            <button type="submit" class="pill-btn pill-success pill-sm">⬆ Upload</button>
                                        </form>
                                        <span style="color:var(--text-muted); font-size:0.78em;">Attach the supplier invoice (PDF or image) once the shipment is in motion.</span>
                                        <?php endif; ?>
                                    </div>
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
    
    <?php if (can('approve_purchase_orders')): ?>
    <!-- Approval workflow settings — approvers only (matching server-side gate) -->
    <div class="wcc-modal" id="workflowModal" role="dialog" aria-modal="true" aria-labelledby="workflowModalTitle">
        <div class="wcc-modal-content wcc-modal-md">
            <div class="wcc-modal-header">
                <h3 id="workflowModalTitle">🛒 Approval Workflow</h3>
                <button type="button" class="wcc-close-btn" onclick="closeWccModal('workflowModal')" aria-label="Close">✕</button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(wcc_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="save_procurement">

                <?php $proc_on = ($proc_settings['procurement_workflow_enabled'] ?? '1') === '1'; ?>
                <label style="display:flex; align-items:center; gap:14px; cursor:pointer;">
                    <input type="checkbox" name="procurement_workflow_enabled" value="1" <?= $proc_on ? 'checked' : '' ?> style="width:20px; height:20px; accent-color: var(--text-accent); cursor:pointer; flex-shrink:0;">
                    <span>
                        <strong style="color:var(--text-primary);">Require approval before fulfilment</strong><br>
                        <span style="color:var(--text-secondary); font-size:0.85em;">
                            <strong>ON</strong>: a request needs cost sign-off, then a Storekeeper ships / receives / closes it.<br>
                            <strong>OFF</strong>: every request is auto-approved on submit — straight to fulfilment, no bottleneck.
                        </span>
                    </span>
                </label>

                <div style="margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--panel-border);">
                    <label style="color:var(--text-secondary); display:block; margin-bottom: 6px;">Auto-approve limit (only while the workflow is ON)</label>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span style="color:var(--text-secondary); font-size:1.1em;">$</span>
                        <input type="number" name="po_auto_approve_limit" min="0" step="0.01" value="<?= htmlspecialchars($proc_settings['po_auto_approve_limit'] ?? '0') ?>" style="padding:10px; border-radius:6px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--input-text); width: 160px; font-family: monospace;">
                    </div>
                    <p style="color:var(--text-secondary); font-size: 0.85em; margin-top: 6px;">
                        Requests at or under this amount skip sign-off and go straight to fulfilment.
                        <strong>0</strong> = every request needs sign-off.
                    </p>
                </div>

                <div class="wcc-modal-footer">
                    <button type="button" class="pill-btn" onclick="closeWccModal('workflowModal')">Cancel</button>
                    <button type="submit" class="pill-btn pill-success">💾 Save Workflow</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
    function openDoc(url) {
        window.open(url, 'WccDoc', 'width=920,height=840,menubar=no,toolbar=no');
    }
    function openPRDocument(poId) {
        openDoc('/_logi/pr_document.php?po_id=' + poId + '&type=pr');
    }
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'workflow_saved'): ?>
    document.addEventListener('DOMContentLoaded', () => { if (typeof showToast === 'function') showToast('Approval workflow settings saved.', 'success'); });
    <?php endif; ?>
    </script>
</body>
</html>



