<?php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('view_purchase_requests');
require_once __DIR__ . '/../_trck/tracking_stepper.php';

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/csrf.php';
$pdo = get_wcc_db_connection();

try {
    // Handle PR Creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['po_vendor_id'])) {
        wcc_csrf_require();
        $vendor_id = (int)$_POST['po_vendor_id'];
        $dept_id = !empty($_POST['dept_id']) ? (int)$_POST['dept_id'] : null;
        $created_by = $_SESSION['user_id'];
        
        $part_ids = $_POST['po_part_id'] ?? [];
        $qtys = $_POST['po_qty'] ?? [];
        
        $total_amount = 0;
        $final_items = [];
        
        foreach($part_ids as $idx => $pid) {
            $pid = (int)$pid;
            $qty = (int)$qtys[$idx];
            if ($qty <= 0) continue;
            
            $stmt = $pdo->prepare("SELECT part_id, cost_per_unit, supersession_sku, lifecycle_status, part_name FROM inventory_parts WHERE part_id = ?");
            $stmt->execute([$pid]);
            $part = $stmt->fetch();
            if ($part) {
                $unit_price = (float)$part['cost_per_unit'];
                $total_amount += ($unit_price * $qty);
                $final_items[] = [
                    'part_id' => $part['part_id'],
                    'qty' => $qty,
                    'unit_price' => $unit_price
                ];
            }
        }
        
        if (count($final_items) > 0) {
            $po_number = "PR-" . date("Ymd") . "-" . rand(1000,9999);

            // Decide approval routing (shared helper — same logic as auto-reorder).
            require_once __DIR__ . '/../inc/procurement.php';
            require_once __DIR__ . '/../inc/notifications.php';
            $route          = wcc_procurement_route($pdo, (float)$total_amount);
            $status         = $route['status'];
            $approval_level = $route['approval_level'];
            $auto_approved  = $route['auto_approved'];
            $auto_reason    = $route['reason'];

            $stmt = $pdo->prepare("INSERT INTO purchase_orders (po_number, vendor_id, dept_id, created_by, total_amount, status, approval_level) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$po_number, $vendor_id, $dept_id, $created_by, $total_amount, $status, $approval_level]);
            $po_id = $pdo->lastInsertId();

            $stmt_item = $pdo->prepare("INSERT INTO po_items (po_id, part_id, ordered_qty, unit_price) VALUES (?, ?, ?, ?)");
            foreach($final_items as $item) {
                $stmt_item->execute([$po_id, $item['part_id'], $item['qty'], $item['unit_price']]);
            }

            // Log creation in audit trail
            $stmt_log = $pdo->prepare("INSERT INTO po_status_logs (po_id, action_type, status_from, status_to, note, changed_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_log->execute([$po_id, 'PR Submitted', 'Draft', ($auto_approved ? 'Pending Approval' : $status), null, $created_by]);
            if ($auto_approved) {
                $stmt_log->execute([$po_id, 'Auto-Approved', 'Pending Approval', 'Issued', $auto_reason, $created_by]);
            }

            // Notify the right people about the next step.
            if ($auto_approved) {
                wcc_notify_perm('fulfill_purchase_orders', 'po_awaiting', 'PO ' . $po_number . ' is approved and awaiting fulfilment ($' . number_format($total_amount, 2) . ').', '/_logi/purchase_orders.php', 'info', (int)$created_by);
            } else {
                wcc_notify_perm('approve_purchase_orders', 'pr_pending', 'PR ' . $po_number . ' needs cost approval ($' . number_format($total_amount, 2) . ').', '/_logi/purchase_orders.php', 'warning', (int)$created_by);
            }

            $outcome = $auto_approved ? 'auto_approved' : 'pending';
            header("Location: /_logi/purchase_requests.php?msg=pr_submitted&po=" . urlencode($po_number) . "&status=" . urlencode($status) . "&outcome=" . $outcome);
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
    
    $po_details = [];
    try {
        $item_stmt = $pdo->query("
            SELECT i.*, p.part_name, p.internal_code 
            FROM po_items i
            LEFT JOIN inventory_parts p ON i.part_id = p.part_id
        ");
        $all_items = $item_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($all_items as $itm) {
            $po_details[$itm['po_id']][] = $itm;
        }
    } catch (Exception $e) {
        // non critical, details will be empty
    }

    // Fetch attached documents (invoice) keyed by po_id, for verification links
    $po_docs = [];
    try {
        $doc_stmt = $pdo->query("SELECT po_id, doc_type, file_path, original_name FROM po_documents");
        foreach($doc_stmt->fetchAll(PDO::FETCH_ASSOC) as $d) {
            $po_docs[$d['po_id']][$d['doc_type']] = $d;
        }
    } catch (Exception $e) { /* table may not exist pre-migration */ }

} catch (PDOException $e) { wcc_user_error("Could not load purchase requests.", $e->getMessage()); }
?>
<?php
$page_title = __('pr.title');
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
        
        .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .items-table th, .items-table td { padding: 8px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); font-size: 0.9em; }
        .items-table th { color: var(--text-secondary); }

        /* Modal CSS */
        .enterprise-modal { width: 900px !important; max-width: 95% !important; }
        .form-section { margin-top: 20px; padding-top: 10px; border-top: 1px solid var(--panel-border); }
        .form-section h3 { color: var(--text-accent); margin-bottom: 15px; font-size: 1.1em; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
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
            input.placeholder = (typeof t === 'function' ? t('search.placeholder_pr') : 'Search purchase requests... (Drag to column)');
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
            <h1><?= __e('pr.title') ?></h1>
            <div style="display:flex; gap:10px; align-items:center;">
                <div id="searchWrapper">
                    <div id="searchContainerOrig" style="display:inline-block; position:relative; width:100%;" draggable="true" ondragstart="dragSearch(event)">
                        <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); pointer-events:none; font-size:1.1em;">🔍</span>
                        <input type="text" id="ledgerSearch" onkeyup="handleSearchInput(event)" ondblclick="resetSearchPosition()" placeholder="<?= __e('search.placeholder_pr') ?>" style="padding:8px 35px 8px 35px; border-radius:20px; border: 1px solid var(--text-accent); background:var(--input-bg); color:var(--text-primary); transition: all 0.3s; box-sizing: border-box;">
                        <span id="lockTokenBtn" onclick="lockToken()" style="display:none; position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:1.1em; transition: transform 0.2s;" title="Lock Token" onmouseover="this.style.transform='translateY(-50%) scale(1.2)'" onmouseout="this.style.transform='translateY(-50%) scale(1)'">🔒</span>
                    </div>
                </div>
                <button class="btn" onclick="document.getElementById('poModal').style.display='block'">+ Submit PR</button>
            </div>
        </div>
        <div id="activeFiltersArea" style="display:flex; gap:8px; margin-bottom:15px; flex-wrap:wrap; min-height:30px; align-items:center;">
            <span id="searchMatchCount" class="search-match-count" aria-live="polite"></span>
        </div>
        
        <div class="table-container" style="overflow-x:auto;">
            <table class="data-table" id="poTable">
                <thead>
                    <tr>
                        <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">PR / PO #</th>
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
                    <td colspan="6" style="padding:0;">
                        <div class="child-content">
                            <div class="grid-4">
                                <div style="grid-column: span 4;">
                                    <?php render_tracking_stepper($i['status'], false, $i['po_id'], $i['username'] ?? 'System'); ?>
                                </div>
                                <div class="detail-card" style="grid-column: span 3;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                                        <h4 style="margin:0;">Line Items</h4>
                                        <div style="display:flex; gap:8px;">
                                            <button type="button" class="btn btn-sm" onclick="openDoc('/_logi/pr_document.php?po_id=<?= $i['po_id'] ?>&type=pr')">📄 View PR Document</button>
                                            <?php if(isset($po_docs[$i['po_id']]['invoice'])): ?>
                                                <button type="button" class="btn btn-sm" onclick="openDoc('<?= htmlspecialchars($po_docs[$i['po_id']]['invoice']['file_path']) ?>')">🧾 View Invoice</button>
                                            <?php else: ?>
                                                <span style="color:var(--text-muted); font-size:0.8em; align-self:center;">No invoice yet</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <table class="items-table" style="margin-top:10px;">
                                        <thead>
                                            <tr>
                                                <th>Part SKU</th>
                                                <th>Description</th>
                                                <th>Qty Ordered</th>
                                                <th>Qty Rcvd</th>
                                                <th>Unit Price</th>
                                                <th>Status</th>
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
                                                    <td><?= htmlspecialchars($itm['status'] ?? 'N/A') ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="6">No line items attached.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
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
    
<!-- PO Modal -->
<div id="poModal" class="modal">
  <div class="modal-content enterprise-modal">
    <span class="close" onclick="document.getElementById('poModal').style.display='none'">&times;</span>
    <h2>Create Purchase Request (PR)</h2>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(wcc_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-section" style="border-top:none; margin-top:0; padding-top:0;">
            <h3>Vendor & Allocation</h3>
            <div class="grid-2">
                <div>
                    <label>Select Vendor *</label>
                    <select name="po_vendor_id" required>
                        <?php
                        try {
                            $v_stmt = $pdo->query("SELECT vendor_id, vendor_name FROM vendors_suppliers");
                            while($v = $v_stmt->fetch()) {
                                echo "<option value='".$v['vendor_id']."'>".htmlspecialchars($v['vendor_name'])."</option>";
                            }
                        } catch (Exception $e) {
                            echo "<option value=''>Error loading vendors</option>";
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label>Budget Allocation (Department) *</label>
                    <select name="dept_id" required>
                        <option value="">-- Select Department --</option>
                        <?php
                        try {
                            $d_stmt = $pdo->query("SELECT dept_id, dept_name, budget_allocated, budget_consumed FROM departments");
                            while($d = $d_stmt->fetch()) {
                                $rem = $d['budget_allocated'] - $d['budget_consumed'];
                                echo "<option value='".$d['dept_id']."'>".htmlspecialchars($d['dept_name'])." (Remaining: $".number_format($rem, 2).")</option>";
                            }
                        } catch (Exception $e) {
                            echo "<option value=''>Error loading departments</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin-bottom:0;">Line Items (Pricing auto-locks to Ledger)</h3>
                <button type="button" class="btn btn-sm" onclick="addPrLineRow()">➕ Add Item</button>
            </div>
            <?php
            // Build the part <option> list once; each row reuses it.
            $pr_part_options = '';
            $p_stmt = $pdo->query("SELECT part_id, part_name, internal_code, cost_per_unit FROM inventory_parts WHERE lifecycle_status = 'Active' ORDER BY part_name ASC");
            while($p = $p_stmt->fetch()) {
                $pr_part_options .= "<option value='".$p['part_id']."' data-price='".(float)$p['cost_per_unit']."'>".htmlspecialchars($p['part_name'])." (".htmlspecialchars($p['internal_code']).") - $".number_format((float)$p['cost_per_unit'], 2)."</option>";
            }
            ?>
            <div id="prLineItems" style="margin-top:12px; display:flex; flex-direction:column; gap:10px;">
                <div class="pr-line-row" style="display:grid; grid-template-columns: 1fr 110px 36px; gap:10px; align-items:end;">
                    <div>
                        <label>Select Part</label>
                        <select name="po_part_id[]" class="pr-part" onchange="updatePrTotal()" required><?= $pr_part_options ?></select>
                    </div>
                    <div>
                        <label>Quantity</label>
                        <input type="number" name="po_qty[]" class="pr-qty" value="1" min="1" oninput="updatePrTotal()" required>
                    </div>
                    <button type="button" class="pr-remove" title="Remove line" onclick="removePrLineRow(this)" style="height:44px; border:1px solid var(--danger-border); background:var(--danger-bg); color:var(--danger); border-radius:var(--radius-sm); cursor:pointer; font-size:1.1em;">✖</button>
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end; margin-top:12px; font-weight:600; color:var(--text-accent);">
                Estimated Total:&nbsp;<span id="prEstTotal" style="font-family:monospace;">$0.00</span>
            </div>
        </div>

        <p style="color:var(--text-secondary); font-size:0.85em; margin-top:20px;">
            <em>All Purchase Requests require explicit admin approval before issuing.</em>
        </p>
        <button type="submit" class="btn btn-primary" style="margin-top:10px;">Submit Purchase Request</button>
    </form>
  </div>
</div>

<script>
    // --- Multi-line PR item rows ---
    function addPrLineRow() {
        const container = document.getElementById('prLineItems');
        const first = container.querySelector('.pr-line-row');
        const clone = first.cloneNode(true);
        clone.querySelector('.pr-part').selectedIndex = 0;
        clone.querySelector('.pr-qty').value = 1;
        container.appendChild(clone);
        updatePrTotal();
    }
    function removePrLineRow(btn) {
        const container = document.getElementById('prLineItems');
        if (container.querySelectorAll('.pr-line-row').length <= 1) return; // keep at least one
        btn.closest('.pr-line-row').remove();
        updatePrTotal();
    }
    function updatePrTotal() {
        let total = 0;
        document.querySelectorAll('#prLineItems .pr-line-row').forEach(row => {
            const opt = row.querySelector('.pr-part').selectedOptions[0];
            const price = opt ? parseFloat(opt.dataset.price || '0') : 0;
            const qty = parseInt(row.querySelector('.pr-qty').value || '0', 10);
            if (price > 0 && qty > 0) total += price * qty;
        });
        const el = document.getElementById('prEstTotal');
        if (el) el.textContent = '$' + total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    document.addEventListener('DOMContentLoaded', updatePrTotal);

    function openDoc(url) {
        window.open(url, 'WccDoc', 'width=920,height=840,menubar=no,toolbar=no');
    }
    function openPRDocument(poId) {
        openDoc('/_logi/pr_document.php?po_id=' + poId + '&type=pr');
    }

    <?php if(isset($_GET['msg']) && $_GET['msg'] === 'pr_submitted'): ?>
    window.addEventListener('DOMContentLoaded', () => {
        if (typeof openWccAlert === 'function') {
            <?php if(($_GET['outcome'] ?? '') === 'auto_approved'): ?>
            openWccAlert('Auto-Approved', 'Purchase Request <?= htmlspecialchars($_GET['po'] ?? '') ?> was auto-approved and is now awaiting fulfilment by a Storekeeper.');
            <?php else: ?>
            openWccAlert('Submitted', 'Purchase Request <?= htmlspecialchars($_GET['po'] ?? '') ?> submitted and is now Pending Approval.');
            <?php endif; ?>
        }
    });
    <?php endif; ?>
</script>
</body>
</html>


