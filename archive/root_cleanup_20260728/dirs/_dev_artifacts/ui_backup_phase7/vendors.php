<?php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('view_vendors');

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    $stmt = $pdo->query("SELECT * FROM vendors_suppliers ORDER BY vendor_id ASC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { die("DB Error: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Vendors Directory</title>
    <style>
        .layer-panel {
            background: transparent !important;
            border: 1px solid var(--panel-border) !important;
            padding: 15px;
            border-radius: 8px;
            box-shadow: none !important;
        }
        .data-pair {
            margin-bottom: 8px;
            display: flex;
            font-size: 0.9em;
        }
        .data-pair strong {
            color: var(--text-secondary);
            display: inline-block;
            width: 200px;
        }
        .data-pair span {
            color: var(--text-primary);
            font-family: monospace;
        }
        
        .filter-token { background: var(--panel-bg); border: 1px solid var(--text-accent); border-radius: 16px; padding: 4px 12px; font-size: 0.85em; color: var(--text-primary); display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); backdrop-filter: blur(10px); animation: fadeIn 0.2s ease-out; }
        .filter-token span { font-weight: bold; color: var(--text-accent); }
        .filter-token-close { cursor: pointer; color: #ef4444; font-weight: bold; transition: transform 0.2s; }
        .filter-token-close:hover { transform: scale(1.2); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
    </style>
    <script>
        let activeFilters = [];
        let filterIdCounter = 0;
        let activeColumnIndex = -1;

        function getColumnName(index) {
            let th = document.querySelector("#vendorsTable thead tr").children[index];
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
            } else { filterTable(); }
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
            var table = document.getElementById("vendorsTable");
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

                if (matchFound) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                    tr[i].classList.remove('is-expanded');
                    let nextRow = tr[i].nextElementSibling;
                    if (nextRow && nextRow.classList.contains('child-row')) {
                        nextRow.style.display = "none";
                    }
                }
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
            input.placeholder = 'Search vendors... (Drag to column. Dbl-Click Reset)';
            lockBtn.style.display = 'none';
            activeColumnIndex = -1;
            filterTable();
        }
    </script>
</head>
<body><?php include __DIR__ . '/../nav.php'; ?>

<div class="dashboard-container">
    <div class="header-flex" style="margin-bottom:10px;">
        <h2>?? Vendors Directory</h2>
        <div style="display:flex; gap:10px; align-items:center;">
            <div id="searchWrapper">
                <div id="searchContainerOrig" style="display:inline-block; position:relative; width:100%;" draggable="true" ondragstart="dragSearch(event)">
                    <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); pointer-events:none; font-size:1.1em;">🔍</span>
                        <input type="text" id="ledgerSearch" onkeyup="handleSearchInput(event)" placeholder="Search vendors... (Drag to column. Dbl-Click Reset)" 
                        ondblclick="resetSearchPosition()"
                        style="width:360px; padding:8px 35px 8px 35px; border-radius:20px; border: 1px solid var(--text-accent); background:var(--input-bg); color:var(--text-primary); transition: all 0.3s; box-sizing: border-box;">
                    <span id="lockTokenBtn" onclick="lockToken()" style="display:none; position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:1.1em; transition: transform 0.2s;" title="Lock Token" onmouseover="this.style.transform='translateY(-50%) scale(1.2)'" onmouseout="this.style.transform='translateY(-50%) scale(1)'">🔒</span>
                </div>
            </div>
        </div>
    </div>
    
    <div id="activeFiltersArea" style="display:flex; gap:8px; margin-bottom:15px; flex-wrap:wrap; min-height:30px;"></div>
    
    <div class="table-container" style="overflow-x: auto;">
        <table class="data-table" id="vendorsTable">
            <thead>
                <tr>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Vendor ID</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Vendor Name</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Type</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Primary Contact</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">24/7 Hotline</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Rating</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $i): ?>
                <tr class="parent-row" data-id="<?= $i['vendor_id'] ?>">
                    <td style="font-family: monospace; color: var(--text-secondary);">
                        <span class="row-arrow">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </span>
                        <?= htmlspecialchars($i['vendor_id']) ?>
                    </td>
                    <td style="font-weight:bold; color:var(--text-accent);"><?= htmlspecialchars($i['vendor_name']) ?></td>
                    <td><span class="prio-badge badge-normal" style="background:var(--child-bg); color:var(--text-secondary); border: 1px solid var(--panel-border);"><?= htmlspecialchars($i['vendor_type'] ?? 'Uncategorized') ?></span></td>
                    <td><?= htmlspecialchars($i['primary_contact_name'] ?? 'N/A') ?></td>
                    <td style="color:#ef4444; font-weight:bold;"><?= htmlspecialchars($i['emergency_contact'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($i['rating']) ?> / 5.00</td>
                </tr>
                <tr class="child-row" id="acc-<?= $i['vendor_id'] ?>">
                    <td colspan="12">
                        <div class="child-content" style="display: grid; grid-template-columns: 1fr; gap: 20px;">
                            <div class="layer-panel">
                                <h4 style="margin-top:0; border-bottom:1px solid var(--panel-border); padding-bottom:5px; color:var(--text-accent);">Vendor Master Profile</h4>
                                <div class="data-pair"><strong>Vendor Name:</strong> <span><?= htmlspecialchars($i['vendor_name']) ?></span></div>
                                <div class="data-pair"><strong>Vendor Type:</strong> <span><?= htmlspecialchars($i['vendor_type'] ?? 'N/A') ?></span></div>
                                <div class="data-pair"><strong>Primary Contact:</strong> <span><?= htmlspecialchars($i['primary_contact_name'] ?? 'N/A') ?></span></div>
                                <div class="data-pair"><strong>Email:</strong> <span><?= htmlspecialchars($i['contact_email'] ?? 'N/A') ?></span></div>
                                <div class="data-pair"><strong>Direct Phone:</strong> <span><?= htmlspecialchars($i['contact_phone'] ?? 'N/A') ?></span></div>
                                <div class="data-pair"><strong>Emergency/24-7 Contact:</strong> <span style="color:#f87171; font-weight:bold;"><?= htmlspecialchars($i['emergency_contact'] ?? 'N/A') ?></span></div>
                                <div class="data-pair"><strong>Payment Terms:</strong> <span><?= htmlspecialchars($i['payment_terms'] ?? 'N/A') ?></span></div>
                                <div class="data-pair"><strong>Address:</strong> <span><?= htmlspecialchars($i['vendor_address'] ?? 'N/A') ?></span></div>
                                <div class="data-pair"><strong>Shipping Time:</strong> <span><?= htmlspecialchars($i['shipping_time'] ?? 'N/A') ?></span></div>
                                <div class="data-pair"><strong>Remarks:</strong> <span><?= nl2br(htmlspecialchars($i['vendor_remarks'] ?? 'N/A')) ?></span></div>
                                <div class="data-pair"><strong>Added On:</strong> <span><?= htmlspecialchars($i['created_at']) ?></span></div>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>

