<?php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('view_equipment');

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    $stmt = $pdo->query("SELECT * FROM equipment ORDER BY equip_id ASC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { wcc_user_error("Could not load equipment data.", $e->getMessage()); }
?>
<?php
$page_title = 'Equipment Ledger';
require_once __DIR__ . '/../inc/head.php';
?>
    <style>
        #ledgerSearch { width: min(360px, 100%); }
        .layer-panel {
            background: transparent !important;
            border: 1px solid var(--panel-border) !important;
            border-radius: 8px;
            padding: 15px;
            box-shadow: none !important;
        }
        .layer-panel h4 {
            margin-top: 0;
            color: var(--text-accent);
            border-bottom: 1px solid var(--panel-border);
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .data-pair {
            margin-bottom: 8px;
            font-size: 0.9em;
        }
        .data-pair strong {
            color: var(--text-secondary);
            display: inline-block;
            width: 140px;
        }
        .data-pair span {
            color: var(--text-primary);
            font-family: monospace;
        }
        
        /* Draggable Search Bar styling */
        .search-container {
            margin-bottom: 20px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 15px;
        }
        .search-input {
            padding: 10px 15px;
            border-radius: 20px;
            border: 1px solid var(--panel-border);
            background: var(--panel-bg);
            color: var(--text-primary);
            width: 300px;
            transition: border 0.3s;
        }
        .search-input:focus {
            outline: none;
            border-color: var(--text-accent);
        }
        /* Token Filter Styles */
        .filter-token {
            background: var(--panel-bg);
            border: 1px solid var(--text-accent);
            border-radius: 16px;
            padding: 4px 12px;
            font-size: 0.85em;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            animation: fadeIn 0.2s ease-out;
        }
        .filter-token span {
            font-weight: bold;
            color: var(--text-accent);
        }
        .filter-token-close {
            cursor: pointer;
            color: var(--danger);
            font-weight: bold;
            transition: transform 0.2s;
        }
        .filter-token-close:hover {
            transform: scale(1.2);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
    <script>
        // Draggable Search Filter Logic (Tokenized)
        let activeFilters = [];
        let filterIdCounter = 0;
        let activeColumnIndex = -1;

        function getColumnName(index) {
            let th = document.querySelector("#ledgerTable thead tr").children[index];
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
            var table = document.getElementById("ledgerTable");
            var tr = table.getElementsByClassName("parent-row"); // UPDATED CLASS NAME

            for (let i = 0; i < tr.length; i++) {
                let matchFound = true;
                let tds = tr[i].getElementsByTagName("td");

                const cellMatch = (typeof wccTableCellMatches === 'function')
                    ? wccTableCellMatches
                    : function (cell, q) {
                        const txt = (cell.textContent || cell.innerText || '').toUpperCase();
                        return txt.indexOf(String(q || '').toUpperCase()) !== -1;
                    };

                for (let f of activeFilters) {
                    let cell = tds[f.colIndex];
                    if (cell && !cellMatch(cell, f.query)) {
                        matchFound = false;
                        break;
                    }
                }

                if (matchFound && globalFilter !== "") {
                    if (activeColumnIndex > -1) {
                        let cell = tds[activeColumnIndex];
                        if (cell && !cellMatch(cell, globalFilter)) matchFound = false;
                    } else {
                        let globalMatch = false;
                        for (let j = 0; j < tds.length; j++) {
                            if (tds[j] && cellMatch(tds[j], globalFilter)) {
                                globalMatch = true;
                                break;
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

        function allowDrop(ev) {
            ev.preventDefault();
        }

        function dragSearch(ev) {
            ev.dataTransfer.setData("text", ev.target.id);
        }

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
            input.placeholder = 'Search by name, uuid, brand... (Drag to column)';
            lockBtn.style.display = 'none';
            activeColumnIndex = -1;
            filterTable();
        }
    </script>
<?php include __DIR__ . '/../nav.php'; ?>
    <div class="dashboard-container">
        <div class="page-header">
            <h1>Equipment Ledger</h1>
            <div id="searchWrapper">
                <div id="searchContainerOrig" style="display:inline-block; position:relative; width:100%;" draggable="true" ondragstart="dragSearch(event)">
                    <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); pointer-events:none; font-size:1.1em;" aria-hidden="true">🔍</span>
                        <input type="text" id="ledgerSearch" aria-label="Search equipment" style="padding:8px 35px 8px 35px; border-radius:20px; border: 1px solid var(--text-accent); background:var(--input-bg); color:var(--text-primary); transition: all 0.3s; box-sizing: border-box;" onkeyup="handleSearchInput(event)" placeholder="Search by name, uuid, brand... (Drag to column)"
                        ondblclick="resetSearchPosition()">
                    <span id="lockTokenBtn" onclick="lockToken()" style="display:none; position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:1.1em; transition: transform 0.2s;" title="Lock Token" onmouseover="this.style.transform='translateY(-50%) scale(1.2)'" onmouseout="this.style.transform='translateY(-50%) scale(1)'">🔒</span>
                </div>
            </div>
        </div>
        <div id="activeFiltersArea" style="display:flex; gap:8px; margin-bottom:15px; flex-wrap:wrap; min-height:30px;"></div>
        <table class="data-table" id="ledgerTable">
            <thead>
                <tr>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Asset Name</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">UUID</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Category</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Criticality</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Plant / Line</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $i):
                    $cid = $i['equip_id'];
                    $war_status = "Unknown";
                    $war_color = "var(--text-secondary)";
                    if ($i['warranty_expiry']) {
                        if (strtotime($i['warranty_expiry']) > time()) {
                            $war_status = "Active";
                            $war_color = "var(--success)";
                        } else {
                            $war_status = "Expired";
                            $war_color = "var(--danger)";
                        }
                    }
                    // Criticality A/B/C -> the design system's real badge classes, computed
                    // once and used for both the table cell and the detail panel below.
                    $crit = strtoupper($i['criticality'] ?? 'B');
                    $critMap = [
                        'A' => ['Critical', 'badge-critical'],
                        'B' => ['Important', 'badge-high'],
                        'C' => ['Standard', 'badge-low'],
                    ];
                    [$critLabel, $critClass] = $critMap[$crit] ?? ['Unrated', 'badge-normal'];
                ?>
                <tr class="parent-row" data-id="<?= $cid ?>">
                    <td style="font-weight:bold; color: var(--text-accent);">
                        <span class="row-arrow">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </span>
                        <?= htmlspecialchars($i['equip_name']) ?>
                        <span style="display:none;"><?= htmlspecialchars(!empty($i['asset_purchase_id']) ? $i['asset_purchase_id'] : '') ?></span>
                    </td>
                    <td style="font-family: monospace; font-size: 0.8em; color: var(--text-secondary);"><?= htmlspecialchars(!empty($i['asset_uuid']) ? $i['asset_uuid'] : 'N/A') ?></td>
                    <td><?= htmlspecialchars(!empty($i['category']) ? $i['category'] : 'N/A') ?></td>
                    <td data-search="|<?= htmlspecialchars($crit) ?>|<?= htmlspecialchars(strtoupper($critLabel)) ?>|CLASS <?= htmlspecialchars($crit) ?>|CLASS-<?= htmlspecialchars($crit) ?>|CRITICALITY <?= htmlspecialchars($crit) ?>|">
                        <span class="<?= $critClass ?> prio-badge" title="Criticality: <?= htmlspecialchars($critLabel) ?>">CLASS <?= htmlspecialchars($crit) ?></span>
                    </td>
                    <td><?= htmlspecialchars(!empty($i['plant_name']) ? $i['plant_name'] : 'N/A') ?> / <?= htmlspecialchars(!empty($i['line_name']) ? $i['line_name'] : 'N/A') ?></td>
                    <td data-search="<?= $i['is_active'] ? '|ONLINE|YES|ACTIVE|ON|1|' : '|OFFLINE|NO|INACTIVE|OFF|0|' ?>">
                        <?php if ($i['is_active']): ?>
                            <span class="status-closed" style="padding: 2px 8px; border-radius: 10px; font-size: 0.8em;">ONLINE</span>
                        <?php else: ?>
                            <span class="status-open" style="padding: 2px 8px; border-radius: 10px; font-size: 0.8em;">OFFLINE</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr id="acc-<?= $cid ?>" class="child-row">
                    <td colspan="12">
                        <div class="child-content" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                            
                            <div class="layer-panel">
                                <h4>Identity & OEM Context</h4>
                                <div class="data-pair"><strong>Criticality:</strong> <span><span class="<?= $critClass ?> prio-badge">CLASS <?= htmlspecialchars($crit) ?></span> <?= $critLabel ?></span></div>
                                <div class="data-pair"><strong>OEM Brand:</strong> <span><?= htmlspecialchars(!empty($i['oem_brand']) ? $i['oem_brand'] : 'N/A') ?></span></div>
                                <div class="data-pair"><strong>Model No:</strong> <span><?= htmlspecialchars(!empty($i['oem_model']) ? $i['oem_model'] : 'N/A') ?></span></div>
                                <div class="data-pair"><strong>Serial No:</strong> <span><?= htmlspecialchars(!empty($i['oem_serial']) ? $i['oem_serial'] : 'N/A') ?></span></div>
                                <div class="data-pair"><strong>Asset Purchase ID / PO:</strong> <span><?= htmlspecialchars(!empty($i['asset_purchase_id']) ? $i['asset_purchase_id'] : 'N/A') ?></span></div>
                                <div class="data-pair"><strong>Equipment Type:</strong> <span><?= htmlspecialchars(!empty($i['equipment_type']) ? $i['equipment_type'] : 'N/A') ?></span></div>
                                <div class="data-pair"><strong>Station / Geo:</strong> <span><?= htmlspecialchars(!empty($i['station_name']) ? $i['station_name'] : 'N/A') ?></span></div>
                            </div>
                            
                            <div class="layer-panel">
                                <h4>Lifecycle Indicators</h4>
                                <div class="data-pair"><strong>Warranty Status:</strong> <span style="color: <?= $war_color ?>; font-weight: bold;"><?= $war_status ?></span></div>
                                <div class="data-pair"><strong>Exp. Date:</strong> <span><?= htmlspecialchars(!empty($i['warranty_expiry']) ? $i['warranty_expiry'] : 'N/A') ?></span></div>
                                <div class="data-pair"><strong>Lifecycle Target:</strong> <span><?= htmlspecialchars(!empty($i['lifecycle_years']) ? $i['lifecycle_years'] : 'N/A') ?> Years</span></div>
                                <div class="data-pair"><strong>FAT Cleared:</strong> <span><?= htmlspecialchars(!empty($i['fat_date']) ? $i['fat_date'] : 'Pending') ?></span></div>
                                <div class="data-pair"><strong>SAT Cleared:</strong> <span><?= htmlspecialchars(!empty($i['sat_date']) ? $i['sat_date'] : 'Pending') ?></span></div>
                            </div>
                            
                            <div class="layer-panel">
                                <h4>Operating Telemetry</h4>
                                <div class="data-pair"><strong>Base Speed:</strong> <span><?= htmlspecialchars(!empty($i['base_speed']) ? $i['base_speed'] : 'N/A') ?></span></div>
                                <div class="data-pair"><strong>Base Pressure:</strong> <span><?= htmlspecialchars(!empty($i['base_pressure']) ? $i['base_pressure'] : 'N/A') ?></span></div>
                                <div class="data-pair"><strong>PM Interval:</strong> <span><?= htmlspecialchars(!empty($i['pm_days_interval']) ? $i['pm_days_interval'] : 'N/A') ?> Days</span></div>
                                <?php 
                                    $tech_details = json_decode($i['technical_details'] ?? '{}', true);
                                    $custom_fields = $tech_details['custom_fields'] ?? [];
                                    if (empty($custom_fields) && isset($tech_details['custom_name'])) {
                                        $custom_fields[] = ['key' => $tech_details['custom_name'], 'value' => $tech_details['custom_val']];
                                    }
                                    foreach($custom_fields as $cf): if(!empty($cf['key'])):
                                ?>
                                <div class="data-pair"><strong><?= htmlspecialchars($cf['key']) ?>:</strong> <span><?= htmlspecialchars($cf['value']) ?></span></div>
                                <?php endif; endforeach; ?>
                                <div class="data-pair"><strong>BOM Spares:</strong> <span><a href="#" style="color: var(--text-accent);" onclick="openBOMModal(<?= $i['equip_id'] ?>, '<?= htmlspecialchars(addslashes($i['equip_name'])) ?>'); return false;">View Linked Parts</a></span></div>
                                <div class="data-pair"><strong>Safety SOP:</strong> <span><a href="#" style="color:var(--danger);" onclick="openDocsModal(<?= $i['equip_id'] ?>, '<?= htmlspecialchars(addslashes($i['equip_name'])) ?>'); return false;">Read Protocol</a></span></div>
                            </div>
                            
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<!-- BOM Modal -->
<div id="wccBomModal" class="wcc-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 9999; align-items: center; justify-content: center;">
    <div class="wcc-modal-content" style="background: var(--panel-bg); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 30px; width: 90%; max-width: 600px; box-shadow: 0 15px 40px rgba(0,0,0,0.4);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 id="bomModalTitle" style="margin: 0; color: var(--text-accent);">Linked BOM Spares</h3>
            <button onclick="document.getElementById('wccBomModal').style.display='none'" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.5em; line-height: 1;">&times;</button>
        </div>
        <input type="text" id="bomSearch" placeholder="Search linked parts..." style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: white; margin-bottom: 15px; box-sizing: border-box;" onkeyup="filterBOM()">
        <div style="max-height: 400px; overflow-y: auto;">
            <table class="wcc-table" style="width: 100%; text-align: left;">
                <thead><tr><th style="padding: 10px; border-bottom: 1px solid var(--panel-border);">Part Name</th><th style="padding: 10px; border-bottom: 1px solid var(--panel-border);">SKU</th><th style="padding: 10px; border-bottom: 1px solid var(--panel-border);">Qty</th></tr></thead>
                <tbody id="bomTableBody">
                    <tr><td colspan="3" style="text-align: center; padding: 20px;">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Documents Modal -->
<div id="wccDocsModal" class="wcc-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 9999; align-items: center; justify-content: center;">
    <div class="wcc-modal-content" style="background: var(--panel-bg); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 30px; width: 90%; max-width: 600px; box-shadow: 0 15px 40px rgba(0,0,0,0.4);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 id="docsModalTitle" style="margin: 0; color: var(--text-accent);">Equipment Documents</h3>
            <button onclick="document.getElementById('wccDocsModal').style.display='none'" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.5em; line-height: 1;">&times;</button>
        </div>
        <div style="max-height: 400px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px;" id="docsListContainer">
            <div style="text-align: center; padding: 20px; color: var(--text-secondary);">Loading...</div>
        </div>
    </div>
</div>

<script>
    function toggleChild(id) {
        const row = document.getElementById('acc-' + id);
        if (row.style.display === 'table-row') {
            row.style.display = 'none';
        } else {
            row.style.display = 'table-row';
        }
    }

    async function openBOMModal(equipId, equipName) {
        document.getElementById('bomModalTitle').innerText = 'Linked BOM: ' + equipName;
        document.getElementById('wccBomModal').style.display = 'flex';
        document.getElementById('bomTableBody').innerHTML = '<tr><td colspan="3" style="text-align: center; padding: 20px;">Loading...</td></tr>';
        document.getElementById('bomSearch').value = '';

        try {
            const resp = await fetch('/api/get_equipment_bom.php?equip_id=' + equipId);
            const result = await resp.json();
            if(result.status === 'success') {
                if(result.data.length === 0) {
                    document.getElementById('bomTableBody').innerHTML = '<tr><td colspan="3" style="text-align: center; padding: 20px; color: var(--text-secondary);">No linked parts found.</td></tr>';
                    return;
                }
                let html = '';
                const esc = (typeof escapeHtml === 'function') ? escapeHtml : (s) => String(s ?? '');
                result.data.forEach(p => {
                    html += `<tr>
                        <td style="padding: 10px; border-bottom: 1px solid var(--panel-border); color: white;">${esc(p.part_name)}</td>
                        <td style="padding: 10px; border-bottom: 1px solid var(--panel-border); color: var(--text-secondary);">${esc(p.sku)}</td>
                        <td style="padding: 10px; border-bottom: 1px solid var(--panel-border); color: var(--text-secondary);">${esc(p.quantity)}</td>
                    </tr>`;
                });
                document.getElementById('bomTableBody').innerHTML = html;
            } else {
                const esc = (typeof escapeHtml === 'function') ? escapeHtml : (s) => String(s ?? '');
                document.getElementById('bomTableBody').innerHTML = `<tr><td colspan="3" style="text-align: center; color: #ef4444;">${esc(result.message)}</td></tr>`;
            }
        } catch(e) {
            document.getElementById('bomTableBody').innerHTML = `<tr><td colspan="3" style="text-align: center; color: #ef4444;">Error loading BOM</td></tr>`;
        }
    }

    function filterBOM() {
        const q = document.getElementById('bomSearch').value.toLowerCase();
        const rows = document.getElementById('bomTableBody').getElementsByTagName('tr');
        for(let r of rows) {
            if(r.cells.length < 3) continue;
            const text = r.innerText.toLowerCase();
            r.style.display = text.includes(q) ? '' : 'none';
        }
    }
    
    async function openDocsModal(equipId, equipName) {
        document.getElementById('docsModalTitle').innerText = 'Safety SOPs & Docs: ' + equipName;
        document.getElementById('wccDocsModal').style.display = 'flex';
        document.getElementById('docsListContainer').innerHTML = '<div style="text-align: center; padding: 20px; color: var(--text-secondary);">Loading...</div>';

        try {
            const resp = await fetch('/api/get_equipment_docs.php?equip_id=' + equipId);
            const result = await resp.json();
            if(result.status === 'success') {
                if(result.data.length === 0) {
                    document.getElementById('docsListContainer').innerHTML = '<div style="text-align: center; padding: 20px; color: var(--text-secondary);">No documents linked to this equipment.</div>';
                    return;
                }
                let html = '';
                const esc = (typeof escapeHtml === 'function') ? escapeHtml : (s) => String(s ?? '');
                result.data.forEach(d => {
                    let icon = '📄';
                    if(d.doc_type === 'SOP') icon = '🛡️';
                    if(d.doc_type === 'Manual') icon = '📘';
                    if(d.doc_type === 'Diagram') icon = '📐';
                    // file_path is path-only; strip anything that could break out of href
                    const safePath = String(d.file_path || '').replace(/[^a-zA-Z0-9_\-.\/]/g, '');
                    
                    html += `
                        <a href="/_doc/${esc(safePath)}" target="_blank" style="display: flex; align-items: center; padding: 15px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; text-decoration: none; color: white; transition: background 0.2s;">
                            <div style="font-size: 2em; margin-right: 15px;">${icon}</div>
                            <div style="flex-grow: 1;">
                                <div style="font-weight: bold; margin-bottom: 5px;">${esc(d.doc_title)}</div>
                                <div style="font-size: 0.85em; color: var(--text-secondary);">Type: ${esc(d.doc_type)} | Uploaded by: ${esc(d.uploaded_by)}</div>
                            </div>
                            <div style="color: var(--text-accent);">Open ↗</div>
                        </a>
                    `;
                });
                document.getElementById('docsListContainer').innerHTML = html;
            } else {
                const esc = (typeof escapeHtml === 'function') ? escapeHtml : (s) => String(s ?? '');
                document.getElementById('docsListContainer').innerHTML = `<div style="text-align: center; color: #ef4444;">${esc(result.message)}</div>`;
            }
        } catch(e) {
            document.getElementById('docsListContainer').innerHTML = `<div style="text-align: center; color: #ef4444;">Error loading documents.</div>`;
        }
    }
</script>

</body>
</html>

