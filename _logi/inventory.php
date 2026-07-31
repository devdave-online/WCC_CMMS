<?php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('view_inventory');

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/demo_mode.php';
wcc_demo_guard_destructive_get();   // public demo: block ?delete_*=… style handlers
require_once __DIR__ . '/../inc/icons.php';
require_once __DIR__ . '/../inc/stock_status.php';
$pdo = get_wcc_db_connection();

try {
    $items = $pdo->query("SELECT * FROM inventory_parts ORDER BY part_id ASC")->fetchAll(PDO::FETCH_ASSOC);

    // Two lookup SETS, each computed once so the row loop stays O(1) per part
    // instead of firing a query per row.

    // Critical spare = fitted to the BOM of any Class-A (line-stopping) machine.
    $criticalSpare = [];
    foreach ($pdo->query("SELECT DISTINCT eb.part_id
                            FROM equipment_bom eb
                            JOIN equipment e ON e.equip_id = eb.equip_id
                           WHERE e.criticality = 'A'")->fetchAll(PDO::FETCH_COLUMN) as $pid) {
        $criticalSpare[(int)$pid] = true;
    }

    // On order = covered by a purchase order in any in-flight state. This is the
    // exact set inc/reorder.php dedupes against, so the badge and auto-reorder can
    // never disagree about whether a part is already handled.
    $onOrder = [];
    foreach ($pdo->query("SELECT DISTINCT pi.part_id
                            FROM po_items pi
                            JOIN purchase_orders po ON po.po_id = pi.po_id
                           WHERE po.status IN ('Draft','Pending Approval','Issued','Shipped','In Transit','Partially Received')")
                  ->fetchAll(PDO::FETCH_COLUMN) as $pid) {
        $onOrder[(int)$pid] = true;
    }

    $stockCfg = wcc_stock_status_config();
} catch (PDOException $e) { wcc_user_error("Could not load inventory data.", $e->getMessage()); }
?>
<?php
$page_title = __('inv.title');
require_once __DIR__ . '/../inc/head.php';
?>
    <style>
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
        .details-item { margin-bottom: 8px; font-size: 0.9em; }
        .details-item strong { color: var(--text-secondary); display: inline-block; width: 140px; }
        .details-item span { color: var(--text-primary); font-family: monospace; }
        
        /* Draggable Search Bar styling */
        .search-container { margin-bottom: 20px; display: flex; justify-content: flex-end; align-items: center; gap: 15px; }
        .search-input { padding: 10px 15px; border-radius: 20px; border: 1px solid var(--panel-border); background: var(--panel-bg); color: var(--text-primary); width: 300px; transition: border 0.3s; }
        .search-input:focus { outline: none; border-color: var(--text-accent); }
        
        /* Token Filter Styles */
        .filter-token { background: var(--panel-bg); border: 1px solid var(--text-accent); border-radius: 16px; padding: 4px 12px; font-size: 0.85em; color: var(--text-primary); display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); backdrop-filter: blur(10px); animation: fadeIn 0.2s ease-out; }
        .filter-token span { font-weight: bold; color: var(--text-accent); }
        .filter-token-close { cursor: pointer; color: var(--danger); font-weight: bold; transition: transform 0.2s; }
        .filter-token-close:hover { transform: scale(1.2); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

        /* Stock-status key — teaches the two unlabelled icon columns, the way the
           PM calendar explains its dot colours. Theme-aware via design tokens. */
        .stock-legend { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 20px; padding: 12px 20px; margin-bottom: 15px; background: var(--panel-bg); border: 1px solid var(--panel-border); border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .stock-legend-title { font-size: 0.72em; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-secondary); margin-right: 2px; }
        .stock-legend-sep { width: 1px; align-self: stretch; background: var(--panel-border); margin: 2px 4px; }
        .stock-legend-item { display: flex; align-items: center; gap: 7px; font-size: 0.85em; font-weight: 600; color: var(--text-primary); white-space: nowrap; }
        .stock-legend-item[title] { cursor: help; }
    </style>
    <script>
        let activeFilters = [];
        let filterIdCounter = 0;
        let activeColumnIndex = -1;

        function getColumnName(index) {
            let th = document.querySelector("#inventoryTable thead tr").children[index];
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
            var table = document.getElementById("inventoryTable");
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
            input.placeholder = (typeof t === 'function' ? t('search.placeholder_inventory') : 'Search inventory... (Drag to column)');
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
            <h1><?= __e('inv.title') ?></h1>
            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; justify-content:flex-end;">
                <?php if (can('manage_inventory') || can('approve_purchase_orders')): ?>
                <button type="button" class="pill-btn pill-warning" style="white-space:nowrap;" onclick="wccRunReorderCheck(this)">🔄 Run reorder check</button>
                <?php endif; ?>
                <div id="searchWrapper">
                    <div id="searchContainerOrig" style="display:inline-block; position:relative; width:100%;" draggable="true" ondragstart="dragSearch(event)">
                        <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); pointer-events:none; font-size:1.1em;">🔍</span>
                        <input type="text" id="ledgerSearch" onkeyup="handleSearchInput(event)" ondblclick="resetSearchPosition()" placeholder="<?= __e('search.placeholder_inventory') ?>" style="padding:8px 35px 8px 35px; border-radius:20px; border: 1px solid var(--text-accent); background:var(--input-bg); color:var(--text-primary); transition: all 0.3s; box-sizing: border-box;">
                        <span id="lockTokenBtn" onclick="lockToken()" style="display:none; position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:1.1em; transition: transform 0.2s;" title="Lock Token" onmouseover="this.style.transform='translateY(-50%) scale(1.2)'" onmouseout="this.style.transform='translateY(-50%) scale(1)'">🔒</span>
                    </div>
                </div>
            </div>
        </div>
        <div id="activeFiltersArea" style="display:flex; gap:8px; margin-bottom:15px; flex-wrap:wrap; min-height:30px; align-items:center;">
            <span id="searchMatchCount" class="search-match-count" aria-live="polite"></span>
        </div>

        <?php
        // Status key for the two icon columns. Icon + colour are read from
        // WCC_STOCK_STATES, so the legend can never drift from what the rows draw.
        // reorder/no_vendor share the red octagon, so one entry stands for both
        // (its tooltip names the nuance); the six visuals below are the full set.
        $legendItems = [
            ['state' => 'healthy',     'label' => 'Healthy'],
            ['state' => 'approaching', 'label' => 'Approaching min'],
            ['state' => 'reorder',     'label' => 'At / below min — reorder', 'note' => 'The same red octagon also flags a part below minimum with no supplier set — order it, nobody is yet.'],
            ['state' => 'out',         'label' => 'Out of stock'],
            ['state' => 'on_order',    'label' => 'On order'],
            ['state' => 'obsolete',    'label' => 'Obsolete / phasing out'],
        ];
        ?>
        <div class="stock-legend" aria-label="Stock status key">
            <span class="stock-legend-title">Status key</span>
            <?php foreach ($legendItems as $li):
                [$icon, $color, ] = WCC_STOCK_STATES[$li['state']]; ?>
            <div class="stock-legend-item"<?= isset($li['note']) ? ' title="' . htmlspecialchars($li['note']) . '"' : '' ?>>
                <span style="color: <?= $color ?>; display:inline-flex;"><?= wcc_icon($icon, ['size' => 18]) ?></span>
                <span><?= htmlspecialchars($li['label']) ?></span>
            </div>
            <?php endforeach; ?>
            <span class="stock-legend-sep"></span>
            <div class="stock-legend-item" title="Fitted to the bill of materials of a Class-A (line-stopping) machine — keep it in stock.">
                <span style="color:#f5b301; display:inline-flex;"><?= wcc_icon('star-filled', ['size' => 16]) ?></span>
                <span>Critical spare</span>
            </div>
        </div>

        <div class="table-container" style="overflow-x:auto;">
            <table class="data-table" id="inventoryTable">
                <thead>
                    <tr>
                        <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Name</th>
                        <th style="width:36px;" aria-label="Stock status"></th>
                        <th style="width:28px;" aria-label="Critical spare"></th>
                        <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Internal Code</th>
                        <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Stock Level</th>
                        <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Min Threshold</th>
                    </tr>
                </thead>
                <tbody>
            <?php
            foreach($items as $i):
                $pid = (int)$i['part_id'];
                $isCritical = isset($criticalSpare[$pid]);
                $ss = wcc_stock_status($i, isset($onOrder[$pid]), $stockCfg);
            ?>
            <tr class="parent-row" data-id="<?= htmlspecialchars($i['part_id']) ?>">
                <td style="font-weight:bold; color: var(--text-accent);">
                    <span class="row-arrow">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </span>
                    <?= htmlspecialchars($i['part_name']) ?>
                </td>
                <td style="text-align:center;">
                    <span style="color: <?= $ss['color'] ?>; display:inline-flex;"><?= wcc_icon($ss['icon'], ['title' => $ss['label'], 'size' => 20]) ?></span>
                </td>
                <td style="text-align:center;">
                    <?php if ($isCritical): ?>
                        <span style="color:#f5b301; display:inline-flex;"><?= wcc_icon('star-filled', ['title' => 'Critical spare — fitted to a Class-A machine', 'size' => 18]) ?></span>
                    <?php endif; ?>
                </td>
                <td style="font-family: monospace; color: var(--text-secondary);"><?= htmlspecialchars($i['internal_code']) ?></td>
                <td style="<?= $i['stock_level'] <= $i['minimum_threshold'] ? 'color: var(--danger); font-weight: bold;' : '' ?>">
                    <?= htmlspecialchars($i['stock_level']) ?>
                </td>
                <td><?= htmlspecialchars($i['minimum_threshold']) ?></td>
            </tr>
            <tr class="child-row" id="details-<?= htmlspecialchars($i['part_id']) ?>">
                <td colspan="12">
                    <div class="child-content" style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 20px;">
                        <div class="layer-panel">
                            <h4>Part DNA</h4>
                            <div class="details-item"><strong>Vendor SKU:</strong> <span><?= htmlspecialchars($i['vendor_sku'] ?? 'N/A') ?></span></div>
                            <div class="details-item"><strong>Description:</strong> <span><?= htmlspecialchars($i['standardized_desc'] ?? 'N/A') ?></span></div>
                            <div class="details-item"><strong>OEM Name:</strong> <span><?= htmlspecialchars($i['oem_name'] ?? 'N/A') ?></span></div>
                            <div class="details-item"><strong>OEM Part #:</strong> <span><?= htmlspecialchars($i['oem_part_number'] ?? 'N/A') ?></span></div>
                        </div>
                        <div class="layer-panel">
                            <h4>Logistics</h4>
                            <div class="details-item"><strong>Max Stock:</strong> <span><?= htmlspecialchars($i['maximum_stock'] ?? '0') ?></span></div>
                            <div class="details-item"><strong>Unit Price:</strong> <span><?= htmlspecialchars($i['cost_per_unit'] ?? '0.00') ?> <?= htmlspecialchars($i['currency'] ?? 'USD') ?></span></div>
                            <div class="details-item"><strong>Std Lead Time:</strong> <span><?= htmlspecialchars($i['standard_lead_time'] ?? '0') ?> days</span></div>
                            <div class="details-item"><strong>Exp Lead Time:</strong> <span><?= htmlspecialchars($i['expedited_lead_time'] ?? '0') ?> days</span></div>
                            <div class="details-item"><strong>MOQ:</strong> <span><?= htmlspecialchars($i['moq'] ?? '1') ?> <?= htmlspecialchars($i['uom'] ?? 'Each') ?></span></div>
                        </div>
                        <div class="layer-panel">
                            <h4>Storage & Tracking</h4>
                            <div class="details-item"><strong>Warehouse ID:</strong> <span><?= htmlspecialchars($i['warehouse_id'] ?? 'N/A') ?></span></div>
                            <div class="details-item"><strong>Location:</strong> <span>Aisle <?= htmlspecialchars($i['aisle'] ?? '-') ?>, Rack <?= htmlspecialchars($i['rack'] ?? '-') ?>, Shelf <?= htmlspecialchars($i['shelf'] ?? '-') ?>, Bin <?= htmlspecialchars($i['bin_code'] ?? '-') ?></span></div>
                            <div class="details-item"><strong>Auto Reorder:</strong> <span><?= !empty($i['auto_reorder']) ? 'Yes' : 'No' ?></span></div>
                            <div class="details-item"><strong>Serial #:</strong> <span><?= htmlspecialchars($i['serial_number'] ?? 'N/A') ?></span></div>
                            <div class="details-item"><strong>Batch Lot:</strong> <span><?= htmlspecialchars($i['batch_lot'] ?? 'N/A') ?></span></div>
                        </div>
                        <div class="layer-panel">
                            <h4>Compliance & Status</h4>
                            <div class="details-item"><strong>Condition:</strong> <span><?= htmlspecialchars($i['part_condition'] ?? 'N/A') ?></span></div>
                            <div class="details-item"><strong>Lifecycle:</strong> <span><?= htmlspecialchars($i['lifecycle_status'] ?? 'N/A') ?></span></div>
                            <div class="details-item"><strong>EOL Date:</strong> <span><?= htmlspecialchars($i['eol_date'] ?? 'N/A') ?></span></div>
                            <div class="details-item"><strong>Material Spec:</strong> <span><?= htmlspecialchars($i['material_spec'] ?? 'N/A') ?></span></div>
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
        async function wccRunReorderCheck(btn) {
            const original = btn.textContent;
            btn.disabled = true; btn.textContent = '⏳ Checking…';
            try {
                const r = await fetch('/cron_requisition.php?json=1&csrf=<?= wcc_csrf_token() ?>', { credentials: 'same-origin' });
                const res = await r.json();
                if (res.status === 'success') {
                    showToast(res.message, (res.created && res.created.length) ? 'success' : 'info');
                } else {
                    showToast(res.message || 'Reorder check failed.', 'error');
                }
            } catch (e) {
                showToast('Could not run the reorder check.', 'error');
            } finally {
                btn.disabled = false; btn.textContent = original;
            }
        }
    </script>
</body>
</html>

