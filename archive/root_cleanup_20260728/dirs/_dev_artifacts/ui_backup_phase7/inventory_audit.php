<?php
require_once __DIR__ . '/../../inc/session.php'; // hardened session bootstrap
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    // Fetch transaction history
    $stmt = $pdo->query("
        SELECT l.*, p.part_name, p.internal_code, u.username 
        FROM inventory_ledger l 
        LEFT JOIN inventory_parts p ON l.part_id = p.part_id 
        LEFT JOIN users u ON l.actor_user_id = u.user_id 
        ORDER BY l.created_at DESC 
        LIMIT 500
    ");
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Audit Log</title>
    <style>
        .filter-token { background: var(--panel-bg); border: 1px solid var(--text-accent); border-radius: 16px; padding: 4px 12px; font-size: 0.85em; color: var(--text-primary); display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); backdrop-filter: blur(10px); animation: fadeIn 0.2s ease-out; }
        .filter-token span { font-weight: bold; color: var(--text-accent); }
        .filter-token-close { cursor: pointer; color: #ef4444; font-weight: bold; transition: transform 0.2s; }
        .filter-token-close:hover { transform: scale(1.2); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
        
        .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .items-table th, .items-table td { padding: 12px 10px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.9em; }
        .items-table th { color: var(--text-secondary); text-transform: uppercase; font-size: 0.8em; letter-spacing: 0.5px; border-bottom: 1px solid rgba(255,255,255,0.2); }
        .items-table tbody tr { transition: background 0.2s; }
        .items-table tbody tr:hover { background: rgba(255,255,255,0.05); }

        .qty-badge { padding: 4px 10px; border-radius: 12px; font-weight: bold; font-size: 0.9em; display: inline-block; min-width: 40px; text-align: center; }
        .qty-positive { background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.4); }
        .qty-negative { background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.4); }
        .qty-zero { background: rgba(148, 163, 184, 0.2); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.4); }
        
        /* Accordion CSS */
        .parent-row { cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .parent-row:hover { background: rgba(255, 255, 255, 0.08); box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 10; position: relative; }
        .child-row { display: none; background: rgba(0,0,0,0.4); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(255,255,255,0.05); transition: all 0.3s; }
        .child-row.open { display: table-row; animation: slideDown 0.3s ease-out; }
        .child-content { padding: 25px; border-left: 4px solid var(--text-accent); background: linear-gradient(90deg, rgba(56, 189, 248, 0.05), transparent); }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .row-arrow svg { transition: transform 0.3s; width: 18px; height: 18px; vertical-align: middle; margin-right: 8px; color: var(--text-secondary); }
        .parent-row.open .row-arrow svg { transform: rotate(90deg); color: var(--text-accent); }
    </style>
    <script>
        // --- Restore Search Logic ---
        let activeFilters = [];
        let filterIdCounter = 0;
        let activeColumnIndex = -1;

        function getColumnName(index) {
            let th = document.querySelector("#auditTable thead tr").children[index];
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
            var table = document.getElementById("auditTable");
            var tbody = table.querySelector("tbody");
            var tr = tbody.getElementsByTagName("tr");

            for (let i = 0; i < tr.length; i++) {
                // skip the 'No inventory transactions found' row
                if (tr[i].children.length === 1) continue; 
                
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
            input.style.width = '360px';
            input.placeholder = 'Search transactions... (Drag to column)';
            lockBtn.style.display = 'none';
            activeColumnIndex = -1;
            filterTable();
        }

        async function toggleAuditAccordion(ledgerId, refType, refId, rowElement) {
            if (refType !== 'work_orders' && refType !== 'purchase_orders') return;
            
            const detailsRow = document.getElementById('audit-details-' + ledgerId);
            if (!detailsRow) return;
            
            const isOpen = detailsRow.classList.contains('open');
            
            // Close all
            document.querySelectorAll('.child-row').forEach(row => row.classList.remove('open'));
            document.querySelectorAll('.parent-row').forEach(row => row.classList.remove('open'));
            
            if (!isOpen) {
                detailsRow.classList.add('open');
                rowElement.classList.add('open');
                
                const contentDiv = detailsRow.querySelector('.child-content');
                if (contentDiv.innerHTML.trim() === 'Loading details...') {
                    try {
                        const response = await fetch(`ajax_get_audit_details.php?type=${refType}&id=${refId}`);
                        if (!response.ok) throw new Error('Network response was not ok');
                        const html = await response.text();
                        contentDiv.innerHTML = html;
                    } catch (error) {
                        contentDiv.innerHTML = '<span style="color:#ef4444;">Failed to load details.</span>';
                    }
                }
            }
        }
    </script>
</head>
<body>
<?php include __DIR__ . '/../nav.php'; ?>

<div class="dashboard-container">
    <div class="header-flex" style="margin-bottom:10px;">
        <h2>Enterprise Inventory Audit Log</h2>
        <div style="display:flex; gap:10px; align-items:center;">
            <div id="searchWrapper">
                <div id="searchContainerOrig" style="display:inline-block; position:relative; width:100%;" draggable="true" ondragstart="dragSearch(event)">
                    <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); pointer-events:none; font-size:1.1em;">🔍</span>
                    <input type="text" id="ledgerSearch" onkeyup="handleSearchInput(event)" ondblclick="resetSearchPosition()" placeholder="Search transactions... (Drag to column)" style="width:360px; padding:8px 35px 8px 35px; border-radius:20px; border: 1px solid var(--text-accent); background:var(--input-bg); color:var(--text-primary); transition: all 0.3s; box-sizing: border-box;">
                    <span id="lockTokenBtn" onclick="lockToken()" style="display:none; position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:1.1em; transition: transform 0.2s;" title="Lock Token" onmouseover="this.style.transform='translateY(-50%) scale(1.2)'" onmouseout="this.style.transform='translateY(-50%) scale(1)'">🔒</span>
                </div>
            </div>
            <a href="/_mgmt/admin_panel.php" class="btn">← Back to Admin Panel</a>
        </div>
    </div>
    
    <div id="activeFiltersArea" style="display:flex; gap:8px; margin-bottom:15px; flex-wrap:wrap; min-height:30px;"></div>
    
    <div class="table-container" style="overflow-x:auto;">
        <table class="items-table" id="auditTable">
            <thead>
                <tr>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Date / Time</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Part Name</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">SKU / Internal Code</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Change</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Reason</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Reference</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Actor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $t): 
                    $qty = (int)$t['change_qty'];
                    if ($qty > 0) {
                        $qtyClass = 'qty-positive';
                        $qtyStr = '+' . $qty;
                    } elseif ($qty < 0) {
                        $qtyClass = 'qty-negative';
                        $qtyStr = $qty;
                    } else {
                        $qtyClass = 'qty-zero';
                        $qtyStr = '0';
                    }
                    
                    $hasRef = (!empty($t['reference_type']) && !empty($t['reference_id']));
                    $isClickable = $hasRef && in_array($t['reference_type'], ['work_orders', 'purchase_orders']);
                    $rowClass = $isClickable ? 'parent-row' : '';
                    $onClick = $isClickable ? "onclick=\"toggleAuditAccordion({$t['ledger_id']}, '{$t['reference_type']}', '{$t['reference_id']}', this)\"" : '';
                    
                    $reasonMap = [
                        'po_receipt' => 'PO Received',
                        'wo_consume' => 'WO Consumption',
                        'manual_adj' => 'Manual Adjustment',
                        'initial_stock' => 'Initial Stock'
                    ];
                    $reasonLabel = $reasonMap[$t['reason']] ?? htmlspecialchars($t['reason']);
                ?>
                <tr class="<?= $rowClass ?>" <?= $onClick ?>>
                    <td style="white-space:nowrap; color:var(--text-secondary);">
                        <?php if($isClickable): ?>
                        <span class="row-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></span>
                        <?php endif; ?>
                        <?= date('M j, Y H:i', strtotime($t['created_at'])) ?>
                    </td>
                    <td style="font-weight:bold; color:var(--text-primary);"><?= htmlspecialchars($t['part_name'] ?? 'Unknown Part') ?></td>
                    <td style="color:var(--text-accent);"><?= htmlspecialchars($t['internal_code'] ?? '—') ?></td>
                    <td><span class="qty-badge <?= $qtyClass ?>"><?= $qtyStr ?></span></td>
                    <td>
                        <span style="background:rgba(255,255,255,0.05); padding:4px 8px; border-radius:8px; font-size:0.85em; border:1px solid rgba(255,255,255,0.1);">
                            <?= $reasonLabel ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!empty($t['reference_type']) && !empty($t['reference_id'])): ?>
                            <span style="color:var(--text-secondary); font-size:0.85em; text-transform:uppercase;"><?= htmlspecialchars($t['reference_type']) ?>:</span> 
                            <strong style="color:var(--text-primary);"><?= htmlspecialchars($t['reference_id']) ?></strong>
                        <?php else: ?>
                            <span style="color:var(--text-secondary);">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="display:inline-flex; align-items:center; gap:6px; background:rgba(0,0,0,0.2); padding:2px 8px; border-radius:12px; font-size:0.85em; border:1px solid rgba(255,255,255,0.05);">
                            👤 <?= htmlspecialchars($t['username'] ?? 'System') ?>
                        </span>
                    </td>
                </tr>
                <?php if ($isClickable): ?>
                <tr class="child-row" id="audit-details-<?= $t['ledger_id'] ?>">
                    <td colspan="7" style="padding:0;">
                        <div class="child-content">Loading details...</div>
                    </td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
                <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding:30px; color:var(--text-secondary);">No inventory transactions found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
