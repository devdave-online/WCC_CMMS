<?php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('view_work_orders');

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    // Fetch Users for Dropdown
    $userStmt = $pdo->query("SELECT user_id, username FROM users WHERE role_level >= 2"); // techs/supervisors
    $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Data for View Pane
    $workshops = $pdo->query("SELECT * FROM workshops ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $lines = $pdo->query("SELECT * FROM production_lines ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

    // Sort logic
    $sort = $_GET['sort'] ?? 'default';
    $order_by = 'w.scheduled_date ASC';
    switch ($sort) {
        case 'id_asc': $order_by = 'w.wo_id ASC'; break;
        case 'id_desc': $order_by = 'w.wo_id DESC'; break;
        case 'title_asc': $order_by = 'w.title ASC'; break;
        case 'title_desc': $order_by = 'w.title DESC'; break;
        case 'date_desc': $order_by = 'w.scheduled_date DESC'; break;
        case 'date_asc': $order_by = 'w.scheduled_date ASC'; break;
        case 'default':
        default:
            $order_by = "
                CASE WHEN w.status IN ('Scheduled', 'In Progress') AND w.scheduled_date < CURDATE() THEN 1 
                     WHEN w.status IN ('Scheduled', 'In Progress') THEN 2
                     WHEN w.status = 'Completed' THEN 3
                     ELSE 4 END ASC,
                w.completed_date DESC, w.wo_id DESC
            "; 
            break;
    }

    $stmt = $pdo->query("
        SELECT w.*, u.username, e.equip_name
        FROM work_orders w 
        LEFT JOIN users u ON w.assigned_to = u.user_id
        LEFT JOIN equipment e ON w.equipment_id = e.equip_id
        ORDER BY $order_by
    ");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $db_today = $pdo->query("SELECT CURDATE()")->fetchColumn();
    
    $all_parts = $pdo->query("SELECT part_id, part_name, internal_code FROM inventory_parts")->fetchAll(PDO::FETCH_ASSOC);
    $parts_map = [];
    foreach($all_parts as $p) $parts_map[$p['part_id']] = $p['part_name'] . ' (' . $p['internal_code'] . ')';
} catch (PDOException $e) { wcc_user_error("Unable to load work orders.", $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Work Orders</title>
    <style>
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
        .filter-token span { font-weight: bold; color: var(--text-accent); }
        .filter-token-close { cursor: pointer; color: #ef4444; font-weight: bold; transition: transform 0.2s; }
        .filter-token-close:hover { transform: scale(1.2); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
        th.active-filter-col { background: rgba(56, 189, 248, 0.2) !important; border-bottom: 2px solid var(--text-accent); }
    </style>
    <script>
        let activeFilters = [];
        let filterIdCounter = 0;
        let activeColumnIndex = -1;

        function getColumnName(index) {
            try {
                let thead = document.querySelector("#woTable thead tr");
                if (!thead || !thead.children || index < 0 || index >= thead.children.length) return "Column";
                let th = thead.children[index];
                return th ? (th.innerText || th.textContent || '').trim() : "Column";
            } catch(e) { return "Column"; }
        }

        function createFilterToken(colIndex, query) {
            try {
                let colName = getColumnName(colIndex);
                let id = 'filter-' + filterIdCounter++;
                activeFilters.push({ id: id, colIndex: colIndex, query: (query || '').toUpperCase() });
                
                let area = document.getElementById('activeFiltersArea');
                if (!area) return;
                let token = document.createElement('div');
                token.id = id;
                token.className = 'filter-token';
                token.innerHTML = '<span>' + colName + ':</span> ' + (query || '') + ' <div class="filter-token-close" onclick="removeFilterToken(\'' + id + '\')">✖</div>';
                area.appendChild(token);
            } catch(e) { console.error('createFilterToken', e); }
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
            try {
                var input = document.getElementById("ledgerSearch");
                if (!input) return;
                var globalFilter = (input.value || '').toUpperCase();
                var table = document.getElementById("woTable");
                if (!table) return;
                var tr = table.getElementsByClassName("parent-row");

                for (let i = 0; i < tr.length; i++) {
                    let matchFound = true;
                    let tds = tr[i].getElementsByTagName("td");
                    if (!tds || tds.length === 0) continue;

                    for (let f of activeFilters) {
                        let cell = (f.colIndex >= 0 && f.colIndex < tds.length) ? tds[f.colIndex] : null;
                        if (cell) {
                            let txt = cell.textContent || cell.innerText || '';
                            if (txt.toUpperCase().indexOf(f.query) === -1) {
                                matchFound = false;
                                break;
                            }
                        }
                    }

                    if (matchFound && globalFilter !== "") {
                        if (activeColumnIndex > -1) {
                            let cell = (activeColumnIndex < tds.length) ? tds[activeColumnIndex] : null;
                            if (cell) {
                                let txt = cell.textContent || cell.innerText || '';
                                if (txt.toUpperCase().indexOf(globalFilter) === -1) matchFound = false;
                            }
                        } else {
                            let globalMatch = false;
                            for (let j = 0; j < tds.length; j++) { 
                                if (tds[j]) {
                                    let txt = tds[j].textContent || tds[j].innerText || '';
                                    if (txt.toUpperCase().indexOf(globalFilter) > -1) {
                                        globalMatch = true;
                                        break;
                                    }
                                }
                            }
                            if (!globalMatch) matchFound = false;
                        }
                    }

                    tr[i].style.display = matchFound ? "" : "none";
                    // hide child
                    let nextRow = tr[i].nextElementSibling;
                    if (nextRow && nextRow.classList.contains('child-row')) {
                        nextRow.style.display = 'none';
                        tr[i].classList.remove('is-expanded');
                    }
                }
            } catch(e) { console.error('filterTable error', e); }
        }

        function allowDrop(ev) { ev.preventDefault(); }
        function dragSearch(ev) { ev.dataTransfer.setData("text", ev.target.id); }
        function dropSearch(ev, thElement) {
            ev.preventDefault();
            ev.stopImmediatePropagation();
            try {
                if (!thElement || !thElement.parentNode) return;
                var input = document.getElementById("ledgerSearch");
                var lockBtn = document.getElementById("lockTokenBtn");
                if (!input) return;
                var colIndex = Array.from(thElement.parentNode.children).indexOf(thElement);
                if (colIndex < 0) return;
                var query = (input.value || '').trim();

                // Clear previous active col highlight
                document.querySelectorAll('#woTable thead th').forEach(th => th.classList.remove('active-filter-col'));

                if (query !== '') {
                    createFilterToken(colIndex, query);
                    input.value = '';
                    resetSearchPosition();
                } else {
                    var container = document.getElementById("searchContainerOrig");
                    thElement.appendChild(container);
                    container.style.marginTop = '10px';
                    input.style.width = '100%';
                    input.placeholder = 'Type & click 📌 to Lock';
                    activeColumnIndex = colIndex;
                    if (lockBtn) lockBtn.style.display = 'block';
                    input.focus();
                    filterTable();
                }
            } catch(e) { console.error('dropSearch error', e); }
        }

        function resetSearchPosition() {
            try {
                let wrapper = document.getElementById('searchWrapper');
                let container = document.getElementById('searchContainerOrig');
                let input = document.getElementById('ledgerSearch');
                let lockBtn = document.getElementById('lockTokenBtn');
                if (!wrapper || !container || !input) return;
                
                wrapper.appendChild(container);
                container.style.marginTop = '0';
                input.style.width = '360px';
                input.placeholder = 'Search work orders... (Drag to column)';
                if (lockBtn) lockBtn.style.display = 'none';
                activeColumnIndex = -1;
                
                // remove highlights
                document.querySelectorAll('#woTable thead th').forEach(th => th.classList.remove('active-filter-col'));
                
                filterTable();
            } catch(e) { console.error('resetSearchPosition', e); }
        }
    </script>
</head>
<body><?php include __DIR__ . '/../nav.php'; ?>
    <div class="dashboard-container">
        <div class="header-flex" style="margin-bottom:10px;">
            <h2>Work Orders (Scheduled Maintenance)</h2>
            <div id="searchWrapper">
                <div id="searchContainerOrig" style="display:inline-block; position:relative; width:100%;" draggable="true" ondragstart="dragSearch(event)">
                    <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); pointer-events:none; font-size:1.1em;">🔍</span>
                    <input type="text" id="ledgerSearch" onkeyup="handleSearchInput(event)" ondblclick="resetSearchPosition()" placeholder="Search work orders... (Drag to column)" style="width:360px; padding:8px 35px 8px 35px; border-radius:20px; border: 1px solid var(--text-accent); background:var(--input-bg); color:var(--text-primary); transition: all 0.3s; box-sizing: border-box;">
                    <span id="lockTokenBtn" onclick="lockToken()" style="display:none; position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:1.1em; transition: transform 0.2s;" title="Lock Token" onmouseover="this.style.transform='translateY(-50%) scale(1.2)'" onmouseout="this.style.transform='translateY(-50%) scale(1)'">🔒</span>
                </div>
            </div>
        </div>
        <div id="activeFiltersArea" style="display:flex; gap:8px; margin-bottom:15px; flex-wrap:wrap; min-height:30px;"></div>
        
        <div class="table-container" style="overflow-x:auto;">
            <table class="data-table" id="woTable">
                <thead>
                    <tr>
                        <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">
                            <a href="?sort=<?= $sort === 'id_asc' ? 'id_desc' : 'id_asc' ?>" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                                WO #
                                <span style="font-size: 0.8em; opacity: <?= strpos($sort, 'id') !== false ? '1' : '0.3' ?>;">
                                    <?= $sort === 'id_desc' ? '▼' : '▲' ?>
                                </span>
                            </a>
                        </th>
                        <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">
                            <a href="?sort=<?= $sort === 'title_asc' ? 'title_desc' : 'title_asc' ?>" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                                Title
                                <span style="font-size: 0.8em; opacity: <?= strpos($sort, 'title') !== false ? '1' : '0.3' ?>;">
                                    <?= $sort === 'title_desc' ? '▼' : '▲' ?>
                                </span>
                            </a>
                        </th>
                        <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Equipment</th>
                        <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Assigned To</th>
                        <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Status</th>
                        <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">
                            <a href="?sort=<?= $sort === 'date_asc' ? 'date_desc' : 'date_asc' ?>" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                                Scheduled Date
                                <span style="font-size: 0.8em; opacity: <?= strpos($sort, 'date') !== false ? '1' : '0.3' ?>;">
                                    <?= $sort === 'date_desc' ? '▼' : '▲' ?>
                                </span>
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody>
            <?php foreach($items as $i): ?>
            <?php 
                $is_overdue = false;
                if (($i['status'] === 'Scheduled' || $i['status'] === 'In Progress') && !empty($i['scheduled_date'])) {
                    if (strtotime($i['scheduled_date']) < strtotime($db_today)) {
                        $is_overdue = true;
                    }
                }
                $rowStyle = $is_overdue ? "background: rgba(239, 68, 68, 0.05); border-left: 3px solid #ef4444;" : "";
            ?>
            <tr class="parent-row" data-id="<?= htmlspecialchars($i['wo_id']) ?>" style="<?= $rowStyle ?>">
                <td style="font-weight: 600; color: var(--text-accent);">
                    <span class="row-arrow">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </span>
                    WO-<?= htmlspecialchars($i['wo_id']) ?>
                </td>
                <td><strong style="color: var(--text-primary);"><?= htmlspecialchars($i['title']) ?></strong></td>
                <td><?= htmlspecialchars($i['equip_name'] ?? 'Unknown') ?></td>
                <td>👨‍🔧 <?= htmlspecialchars($i['username'] ?? 'Unassigned') ?></td>
                <td>
                    <?php 
                        $stat = $i['status'];
                        if ($is_overdue) {
                            echo "<span class='status-escalated' style='animation: pulseRed 1.5s infinite;'>OVERDUE</span>";
                        } elseif ($stat === 'Completed') {
                            echo "<span class='status-open' style='background: rgba(16,185,129,0.2); color: #10b981; border: 1px solid #10b981;'>$stat</span>";
                        } elseif ($stat === 'Cancelled' || $stat === 'Missed') {
                            echo "<span class='status-escalated' style='background: rgba(239,68,68,0.2); color: #ef4444; border: 1px solid #ef4444;'>$stat</span>";
                        } else {
                            $class = ($stat == 'Scheduled') ? 'status-pending' : 'status-open';
                            echo "<span class='$class'>$stat</span>";
                        }
                    ?>
                </td>
                <td style="font-weight:bold;">
                    <?php if ($is_overdue): ?>
                        <span style="color: #ef4444; font-size: 0.9em; margin-right: 5px;">⚠️</span>
                    <?php endif; ?>
                    <?= htmlspecialchars($i['scheduled_date'] ?? 'TBD') ?>
                </td>
            </tr>
            <tr class="child-row">
                <td colspan="6">
                    <div class="child-content">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                            <div style="padding-right: 20px;">
                                <span style="font-weight: 800; color: var(--text-accent); font-size: 0.9em; text-transform: uppercase;">Work Order Instructions:</span><br>
                                <span style="font-size: 0.95em; line-height: 1.4; display: inline-block; margin-top: 5px;"><?= nl2br(htmlspecialchars($i['description'] ?? 'No instructions provided.')) ?></span>
                            </div>
                            <?php if ($i['status'] === 'Scheduled' || $i['status'] === 'In Progress' || $i['status'] === 'Overdue'): ?>
                                <a href="/_maint/wo_takeover.php?wo_id=<?= urlencode($i['wo_id']) ?>" style="background: #3b82f6; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 0.9em; white-space: nowrap; box-shadow: 0 4px 6px rgba(0,0,0,0.3); border: 1px solid #60a5fa;" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">
                                    🚀 Takeover WO
                                </a>
                            <?php endif; ?>
                        </div>
                        <?php 
                        $parts = json_decode($i['parts_list'] ?? '[]', true); 
                        if (!empty($parts)): 
                        ?>
                            <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px; margin-top: 5px;">
                                <span style="font-weight: 800; color: var(--text-accent); font-size: 0.9em; text-transform: uppercase; margin-bottom: 10px; display: block;">Required Parts:</span>
                                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                    <?php foreach($parts as $pid): ?>
                                        <div style="background: rgba(0,0,0,0.2); border: 1px solid var(--panel-border); padding: 5px 10px; border-radius: 6px; font-size: 0.85em; color: var(--text-secondary);">
                                            📦 <?= htmlspecialchars($parts_map[$pid] ?? 'Unknown Part') ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php 
                        $chk_data = json_decode($i['checklist_data'] ?? '[]', true);
                        if (!empty($chk_data)): 
                        ?>
                            <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px; margin-top: 15px;">
                                <span style="font-weight: 800; color: var(--text-accent); font-size: 0.9em; text-transform: uppercase; margin-bottom: 10px; display: block;">PM Checklist Audit:</span>
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <?php foreach($chk_data as $idx => $chk): ?>
                                        <div style="background: rgba(0,0,0,0.2); border: 1px solid var(--panel-border); padding: 8px 12px; border-radius: 6px; font-size: 0.9em; color: var(--text-primary); display: flex; justify-content: space-between; align-items: center;">
                                            <span>✔️ <?= htmlspecialchars($chk['task_desc'] ?? '') ?> <small style="color: #94a3b8;">(<?= $chk['expected_time_mins'] ?? 0 ?> mins)</small></span>
                                            <?php 
                                            $paths = [];
                                            if (!empty($chk['photo_paths']) && is_array($chk['photo_paths'])) {
                                                $paths = $chk['photo_paths'];
                                            } elseif (!empty($chk['photo_path'])) {
                                                $paths = [$chk['photo_path']];
                                            }
                                            $paths = array_filter($paths, function($p) { return !empty(trim($p)); });
                                            
                                            if (!empty($paths)): 
                                            ?>
                                                <div style="display: flex; gap: 5px;">
                                                <?php foreach($paths as $pNum => $path): ?>
                                                    <button type="button" onclick="openImageOverlay('<?= htmlspecialchars($path) ?>')" style="background: rgba(59,130,246,0.2); border: 1px solid #3b82f6; color: #60a5fa; padding: 4px 10px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 5px;">📸 View Photo <?= count($paths) > 1 ? ($pNum+1) : '' ?></button>
                                                <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
                </tbody>
            </table>
            </table>
</div>
<div id="imageOverlay" onclick="closeImageOverlay(event)" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center; backdrop-filter: blur(5px); cursor: pointer;">
    <div style="position: relative; max-width: 90%; max-height: 90%; cursor: default;" onclick="event.stopPropagation()">
        <span onclick="document.getElementById('imageOverlay').style.display='none'" style="position: absolute; top: -15px; right: -15px; background: red; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; justify-content: center; align-items: center; cursor: pointer; font-weight: bold; font-size: 18px; box-shadow: 0 4px 10px rgba(0,0,0,0.5);">×</span>
        <img id="overlayImg" src="" style="max-width: 100%; max-height: 90vh; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.8);">
    </div>
</div>
<script>
function openImageOverlay(src) {
    document.getElementById('overlayImg').src = src;
    document.getElementById('imageOverlay').style.display = 'flex';
}
function closeImageOverlay(e) {
    document.getElementById('imageOverlay').style.display = 'none';
}
</script>
</body>
</html>
