<?php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('manage_equipment');
require_once __DIR__ . '/../inc/demo_mode.php';
wcc_demo_guard_destructive_get();   // public demo: block ?delete_*=… handlers before they run

// Enterprise centralized DB connection (Phase 1 fix)
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/label_lib.php';
$pdo = get_wcc_db_connection();
$label_cfg = wcc_label_settings($pdo);

try {
    // Handle Delete Equipment
    if (isset($_GET['delete_id'])) {
        wcc_csrf_require();
        $stmt = $pdo->prepare("DELETE FROM equipment WHERE equip_id = ?");
        $stmt->execute([$_GET['delete_id']]);
        header("Location: /_eam/setup_vault_equipment.php");
        exit;
    }

    // Handle Delete BOM Item
    if (isset($_GET['delete_bom_id'])) {
        wcc_csrf_require();
        $stmt = $pdo->prepare("DELETE FROM equipment_bom WHERE bom_id = ?");
        $stmt->execute([$_GET['delete_bom_id']]);
        header("Location: /_eam/setup_vault_equipment.php");
        exit;
    }

    // Handle form submission (Add / Edit)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action']) && $_POST['action'] === 'add_bom') {
            $equip_id = (int)$_POST['bom_equip_id'];
            $part_id = (int)$_POST['part_id'];
            $quantity = (int)$_POST['quantity'];
            if ($equip_id && $part_id && $quantity > 0) {
                $pdo->prepare("INSERT INTO equipment_bom (equip_id, part_id, quantity) VALUES (?, ?, ?)")->execute([$equip_id, $part_id, $quantity]);
            }
            header("Location: /_eam/setup_vault_equipment.php");
            exit;
        }

        $id = $_POST['equip_id'] ?? '';
        $uuid = $_POST['asset_uuid'] ?? '';

        $name = $_POST['equip_name'] ?? '';
        $oem_brand = $_POST['oem_brand'] ?? '';
        $oem_model = $_POST['oem_model'] ?? '';
        $oem_serial = $_POST['oem_serial'] ?? '';
        $cat = $_POST['category'] ?? '';
        $criticality = $_POST['criticality'] ?? 'B';
        $workshop_id = !empty($_POST['workshop_id']) ? (int)$_POST['workshop_id'] : null;
        $line_id = !empty($_POST['line_id']) ? (int)$_POST['line_id'] : null;
        
        // Layer 2
        $po_value = !empty($_POST['po_value']) ? $_POST['po_value'] : 0.00;
        $asset_purchase_id = $_POST['asset_purchase_id'] ?? '';
        $lifecycle = !empty($_POST['lifecycle_years']) ? $_POST['lifecycle_years'] : 10;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $fat_date = !empty($_POST['fat_date']) ? $_POST['fat_date'] : null;
        $sat_date = !empty($_POST['sat_date']) ? $_POST['sat_date'] : null;
        
        // Layer 3
        $b_speed = $_POST['base_speed'] ?? '';
        $b_press = $_POST['base_pressure'] ?? '';
        $pm_days = !empty($_POST['pm_days_interval']) ? $_POST['pm_days_interval'] : null;
        
        $custom_fields = [];
        if (isset($_POST['custom_field_keys']) && is_array($_POST['custom_field_keys'])) {
            foreach ($_POST['custom_field_keys'] as $index => $key) {
                $val = $_POST['custom_field_values'][$index] ?? '';
                if (trim($key) !== '') {
                    $custom_fields[] = ['key' => trim($key), 'value' => trim($val)];
                }
            }
        }
        $custom_name = $_POST['custom_field_name'] ?? '';
        $custom_val = $_POST['custom_field_value'] ?? '';
        if ($custom_name !== '') {
            $custom_fields[] = ['key' => $custom_name, 'value' => $custom_val];
        }
        $tech_details_str = json_encode(['custom_fields' => $custom_fields]);
        
        if (empty($uuid)) {
            // Apply UUID Rule Logic
            $stmtRule = $pdo->prepare("SELECT * FROM uuid_rules WHERE category = ? LIMIT 1");
            $stmtRule->execute([$cat]);
            $rule = $stmtRule->fetch(PDO::FETCH_ASSOC);

            if (!$rule) {
                // Check for GLOBAL_DEFAULT
                $stmtRule = $pdo->prepare("SELECT * FROM uuid_rules WHERE category = 'GLOBAL_DEFAULT' LIMIT 1");
                $stmtRule->execute();
                $rule = $stmtRule->fetch(PDO::FETCH_ASSOC);
            }

            if ($rule) {
                // Generate based on rule
                $prefix = $rule['prefix'];
                $serial = str_pad($rule['current_serial'], $rule['serial_length'], '0', STR_PAD_LEFT);
                $random_part = '';
                
                if ($rule['random_chars'] > 0) {
                    $chars = '0123456789';
                    if ($rule['char_type'] === 'ALPHANUMERIC') $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    if ($rule['char_type'] === 'SPECIAL') $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*';
                    
                    for ($i = 0; $i < $rule['random_chars']; $i++) {
                        $random_part .= $chars[mt_rand(0, strlen($chars) - 1)];
                    }
                }
                
                $uuid = $prefix . $serial . ($random_part ? '-' . $random_part : '');
                
                // Increment serial
                $pdo->prepare("UPDATE uuid_rules SET current_serial = current_serial + 1 WHERE rule_id = ?")->execute([$rule['rule_id']]);
            } else {
                // Fallback to random
                $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
            }
        }
        
        if ($name) {
            if ($id) {
                $stmtUpdate = $pdo->prepare("UPDATE equipment SET asset_uuid=?, oem_brand=?, oem_model=?, oem_serial=?, equip_name=?, category=?, criticality=?, workshop_id=?, line_id=?, po_value=?, asset_purchase_id=?, lifecycle_years=?, is_active=?, fat_date=?, sat_date=?, base_speed=?, base_pressure=?, pm_days_interval=?, technical_details=? WHERE equip_id=?");
                $stmtUpdate->execute([$uuid, $oem_brand, $oem_model, $oem_serial, $name, $cat, $criticality, $workshop_id, $line_id, $po_value, $asset_purchase_id, $lifecycle, $is_active, $fat_date, $sat_date, $b_speed, $b_press, $pm_days, $tech_details_str, $id]);
            } else {
                $stmtInsert = $pdo->prepare("INSERT INTO equipment (asset_uuid, oem_brand, oem_model, oem_serial, equip_name, category, criticality, workshop_id, line_id, po_value, asset_purchase_id, lifecycle_years, is_active, fat_date, sat_date, base_speed, base_pressure, pm_days_interval, technical_details) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtInsert->execute([$uuid, $oem_brand, $oem_model, $oem_serial, $name, $cat, $criticality, $workshop_id, $line_id, $po_value, $asset_purchase_id, $lifecycle, $is_active, $fat_date, $sat_date, $b_speed, $b_press, $pm_days, $tech_details_str]);
            }
            header("Location: /_eam/setup_vault_equipment.php");
            exit;
        }
    }

    // Handle UUID Rule addition
    if (isset($_POST['action']) && $_POST['action'] === 'add_uuid_rule') {
        $cat = $_POST['rule_category'] ?? '';
        $prefix = $_POST['rule_prefix'] ?? '';
        $s_len = (int)$_POST['serial_length'];
        $r_chars = (int)$_POST['random_chars'];
        $c_type = $_POST['char_type'];
        
        if ($cat) {
            $pdo->prepare("INSERT INTO uuid_rules (target_entity, category, prefix, serial_length, random_chars, char_type) VALUES ('Equipment', ?, ?, ?, ?, ?)")->execute([$cat, $prefix, $s_len, $r_chars, $c_type]);
            header("Location: /_eam/setup_vault_equipment.php");
            exit;
        }
    }
    
    // Handle Delete UUID Rule
    if (isset($_GET['delete_rule_id'])) {
        wcc_csrf_require();
        $pdo->prepare("DELETE FROM uuid_rules WHERE rule_id = ?")->execute([$_GET['delete_rule_id']]);
        header("Location: /_eam/setup_vault_equipment.php");
        exit;
    }
    
    // Fetch data for lists
    $stmt = $pdo->query("SELECT e.*, w.name as plant_name, l.name as line_name FROM equipment e LEFT JOIN workshops w ON e.workshop_id = w.workshop_id LEFT JOIN production_lines l ON e.line_id = l.line_id ORDER BY e.equip_id ASC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $uuid_rules = $pdo->query("SELECT * FROM uuid_rules WHERE target_entity = 'Equipment' ORDER BY category ASC")->fetchAll(PDO::FETCH_ASSOC);
    $workshops = $pdo->query("SELECT * FROM workshops ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $production_lines = $pdo->query("SELECT * FROM production_lines ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Inventory Parts for BOM
    $parts_stmt = $pdo->query("SELECT part_id, part_name, internal_code FROM inventory_parts WHERE lifecycle_status = 'Active' ORDER BY part_name ASC");
    $all_parts = $parts_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch existing BOMs
    $bom_stmt = $pdo->query("
        SELECT b.bom_id, b.equip_id, b.quantity, p.part_name, p.internal_code 
        FROM equipment_bom b 
        JOIN inventory_parts p ON b.part_id = p.part_id
    ");
    $all_boms = $bom_stmt->fetchAll(PDO::FETCH_ASSOC);
    $boms_by_equip = [];
    foreach($all_boms as $b) {
        $boms_by_equip[$b['equip_id']][] = $b;
    }

} catch (PDOException $e) { die("DB Error: " . $e->getMessage()); }
?>
<?php
$page_title = __('equip.vault_title');
require_once __DIR__ . '/../inc/head.php';
?>
    <style>
        .vault-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .layer-panel {
            background: transparent !important;
            border: 1px solid var(--panel-border) !important;
            border-radius: 8px;
            padding: 15px;
            box-shadow: none !important;
        }
        .layer-box h4, .layer-panel h4 {
            margin-top: 0;
            color: var(--text-accent);
            border-bottom: 1px solid var(--panel-border);
            padding-bottom: 8px;
            margin-bottom: 12px;
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
    </style>
    <script src="/js/chart.js"></script>
    <script src="/js/bwip-js-min.js"></script>
    <script>
        let activeFilters = [];
        let filterIdCounter = 0;
        let activeColumnIndex = -1;

        function getColumnName(index) {
            let th = document.querySelector("#vaultTable thead tr").children[index];
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
            var table = document.getElementById("vaultTable");
            var tr = table.getElementsByClassName("parent-row");

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
                    if (cell && !cellMatch(cell, f.query)) { matchFound = false; break; }
                }

                if (matchFound && globalFilter !== "") {
                    if (activeColumnIndex > -1) {
                        let cell = tds[activeColumnIndex];
                        if (cell && !cellMatch(cell, globalFilter)) matchFound = false;
                    } else {
                        let globalMatch = false;
                        for (let j = 0; j < tds.length; j++) {
                            if (tds[j] && cellMatch(tds[j], globalFilter)) { globalMatch = true; break; }
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
            input.placeholder = (typeof t === 'function' ? t('search.placeholder_vault') : 'Search vault... (Drag to column)');
            lockBtn.style.display = 'none';
            activeColumnIndex = -1;
            filterTable();
        }

        function openEditModal(data) {
            document.getElementById('edit_equip_id').value = data.equip_id || '';
            document.getElementById('edit_asset_uuid').value = data.asset_uuid || '';
            document.getElementById('edit_equip_name').value = data.equip_name || '';
            document.getElementById('edit_oem_brand').value = data.oem_brand || '';
            document.getElementById('edit_oem_model').value = data.oem_model || '';
            document.getElementById('edit_oem_serial').value = data.oem_serial || '';
            document.getElementById('edit_category').value = data.category || '';
            document.getElementById('edit_criticality').value = data.criticality || 'B';
            document.getElementById('edit_workshop_id').value = data.workshop_id || '';
            document.getElementById('edit_line_id').value = data.line_id || '';
            
            document.getElementById('edit_asset_purchase_id').value = data.asset_purchase_id || '';
            document.getElementById('edit_po_value').value = data.po_value || '0.00';
            document.getElementById('edit_lifecycle').value = data.lifecycle_years || '10';
            document.getElementById('edit_is_active').checked = data.is_active == 1;
            document.getElementById('edit_fat').value = data.fat_date || '';
            document.getElementById('edit_sat').value = data.sat_date || '';
            
            document.getElementById('edit_base_speed').value = data.base_speed || '';
            document.getElementById('edit_base_pressure').value = data.base_pressure || '';
            document.getElementById('edit_pm_days').value = data.pm_days_interval || '';
            
            let customFieldsContainer = document.getElementById('custom_fields_container');
            customFieldsContainer.innerHTML = '';
            
            if(data.technical_details) {
                try {
                    let p = JSON.parse(data.technical_details);
                    if (Array.isArray(p)) {
                        p.forEach(cf => addCustomField(cf.key, cf.value));
                    } else if (p.custom_fields && Array.isArray(p.custom_fields)) {
                        p.custom_fields.forEach(cf => addCustomField(cf.key, cf.value));
                    } else if (p.custom_name) {
                        addCustomField(p.custom_name, p.custom_val);
                    }
                } catch(e) {}
            }

            document.getElementById('modalTitle').innerText = 'Modify Equipment Setup';
            document.getElementById('addModal').style.display = 'block';
        }
        
        function addCustomField(key = '', val = '') {
            let container = document.getElementById('custom_fields_container');
            let div = document.createElement('div');
            div.style.marginBottom = '15px';
            div.style.padding = '10px';
            div.style.background = 'rgba(0,0,0,0.2)';
            div.style.borderRadius = '5px';
            
            let kStr = String(key || '').replace(/"/g, '&quot;');
            let vStr = String(val || '').replace(/"/g, '&quot;');

            div.innerHTML = `
                <div style="display:flex; justify-content:flex-end; margin-bottom: 5px;">
                    <button type="button" class="pill-btn pill-danger pill-sm" onclick="this.parentElement.parentElement.remove()">✕ Remove</button>
                </div>
                <label style="color:#94a3b8; font-size:0.85em; margin-bottom:4px; display:block;">Field Name (String)</label>
                <input type="text" name="custom_field_keys[]" placeholder="e.g. Voltage" value="${kStr}" required style="margin-bottom:10px; width:100%; box-sizing:border-box;">
                <label style="color:#94a3b8; font-size:0.85em; margin-bottom:4px; display:block;">Field Value (Numerical)</label>
                <input type="number" step="any" name="custom_field_values[]" placeholder="e.g. 240" value="${vStr}" required style="width:100%; box-sizing:border-box;">
            `;
            container.appendChild(div);
        }

        function openAddModal() {
            document.getElementById('equipForm').reset();
            document.getElementById('edit_equip_id').value = '';
            document.getElementById('custom_fields_container').innerHTML = '';
            document.getElementById('modalTitle').innerText = 'Register New Equipment';
            document.getElementById('addModal').style.display = 'block';
        }

        const bomData = <?= json_encode($boms_by_equip) ?>;
        
        function openBOMModal(equipId, equipName) {
            document.getElementById('bom_equip_id').value = equipId;
            document.getElementById('bomModalTitle').innerText = 'Manage BOM: ' + equipName;
            
            let tbody = document.getElementById('bomTableBody');
            tbody.innerHTML = '';
            
            if (bomData[equipId] && bomData[equipId].length > 0) {
                bomData[equipId].forEach(bom => {
                    let row = `<tr>
                        <td>${bom.internal_code}</td>
                        <td>${bom.part_name}</td>
                        <td>${bom.quantity}</td>
                        <td><a href="?delete_bom_id=${bom.bom_id}&csrf=<?= wcc_csrf_token() ?>" class="pill-btn pill-danger pill-sm">Remove</a></td>
                    </tr>`;
                    tbody.innerHTML += row;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">No parts linked to this equipment.</td></tr>';
            }
            
            document.getElementById('bomModal').style.display = 'block';
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
    <?php require_once __DIR__ . '/../rbac.php'; ?>
    <div class="dashboard-container">
        <div class="page-header">
            <h2>🔒 <?= __e('equip.vault_title') ?></h2>
            <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap; justify-content:flex-end;">
                <?php if (can('manage_settings')): ?>
                <a href="/_mgmt/admin_panel.php" class="nav-btn" style="white-space:nowrap;">← Return to Admin Panel</a>
                <?php endif; ?>
                <div id="searchWrapper">
                    <div class="search-container" id="searchContainerOrig" style="display:inline-block; position:relative; width:100%;" draggable="true" ondragstart="dragSearch(event)">
                        <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); pointer-events:none; font-size:1.1em;">🔍</span>
                        <input type="text" id="ledgerSearch" style="padding:8px 35px 8px 35px; border-radius:20px; border: 1px solid var(--text-accent); background:var(--input-bg); color:var(--text-primary); transition: all 0.3s; box-sizing: border-box;" onkeyup="handleSearchInput(event)" ondblclick="resetSearchPosition()" placeholder="<?= __e('search.placeholder_vault') ?>">
                        <span id="lockTokenBtn" onclick="lockToken()" style="display:none; position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:1.1em; transition: transform 0.2s;" title="Lock Token" onmouseover="this.style.transform='translateY(-50%) scale(1.2)'" onmouseout="this.style.transform='translateY(-50%) scale(1)'">🔒</span>
                    </div>
                </div>
                <button class="pill-btn pill-warning" style="white-space:nowrap;" onclick="document.getElementById('uuidConfigModal').style.display='block'">⚙️ UUID Configurator</button>
                <button class="pill-btn pill-info" id="printLabelsBtn" disabled style="opacity:0.5; white-space:nowrap;" onclick="printSelectedLabels()">🏷️ Print Labels (<span id="labelSelCount">0</span>)</button>
                <button class="pill-btn" title="Label &amp; Printer Setup" aria-label="Label and Printer Setup" style="white-space:nowrap;" onclick="openLabelSetup()">🖨️</button>
                <button class="pill-btn pill-success" style="white-space:nowrap;" onclick="openAddModal()">+ Add Equipment</button>
            </div>
        </div>
        <div id="activeFiltersArea" style="display:flex; gap:8px; margin-bottom:15px; flex-wrap:wrap; min-height:30px; align-items:center;">
            <span id="searchMatchCount" class="search-match-count" aria-live="polite"></span>
        </div>
        <table class="data-table" id="vaultTable">
            <thead>
                <tr>
                    <th style="width:34px; text-align:center;"><input type="checkbox" id="labelSelectAll" title="Select all visible" onclick="toggleSelectAllLabels(this)" style="width:16px; height:16px; cursor:pointer;"></th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">UUID</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Name</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Category</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Criticality</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($items as $i): 
                $cid = $i['equip_id'];
                $tech_details = json_decode($i['technical_details'] ?? '{}', true);
                
                // Robust parsing for PHP array vs object
                $custom_fields = [];
                if (isset($tech_details['custom_fields']) && is_array($tech_details['custom_fields'])) {
                    $custom_fields = $tech_details['custom_fields'];
                } elseif (is_array($tech_details) && isset($tech_details[0])) {
                    $custom_fields = $tech_details; // Legacy array format
                } elseif (isset($tech_details['custom_name'])) {
                    $custom_fields[] = ['key' => $tech_details['custom_name'], 'value' => $tech_details['custom_val']];
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
                <td style="text-align:center;" onclick="event.stopPropagation();">
                    <input type="checkbox" class="label-select" value="<?= $cid ?>" onchange="updateLabelSelection()" style="width:16px; height:16px; cursor:pointer;">
                </td>
                <td style="font-family: monospace; font-size: 0.8em; color: var(--text-secondary);">
                    <span class="row-arrow">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </span>
                    <?= htmlspecialchars($i['asset_uuid'] ?? '') ?>
                </td>
                <td style="font-weight:bold; color: var(--text-accent);"><?= htmlspecialchars($i['equip_name']) ?></td>
                <td><?= htmlspecialchars(!empty($i['category']) ? $i['category'] : 'N/A') ?></td>
                <td data-search="|<?= htmlspecialchars($crit) ?>|<?= htmlspecialchars(strtoupper($critLabel)) ?>|CLASS <?= htmlspecialchars($crit) ?>|CLASS-<?= htmlspecialchars($crit) ?>|CRITICALITY <?= htmlspecialchars($crit) ?>|">
                    <span class="<?= $critClass ?> prio-badge" title="Criticality: <?= htmlspecialchars($critLabel) ?>">CLASS <?= htmlspecialchars($crit) ?></span>
                </td>
                <td data-search="<?= $i['is_active'] ? '|YES|Y|ACTIVE|ONLINE|1|TRUE|' : '|NO|N|INACTIVE|OFFLINE|0|FALSE|' ?>">
                    <?= $i['is_active'] ? '🟢 Yes' : '🔴 No' ?>
                </td>
                <td>
                    <?php $jsonData = htmlspecialchars(json_encode($i), ENT_QUOTES, 'UTF-8'); ?>
                    <button type="button" onclick="openBOMModal(<?= $cid ?>, '<?= addslashes(htmlspecialchars($i['equip_name'])) ?>')" class="pill-btn pill-info pill-sm" style="margin-right: 5px;">Manage BOM</button>
                    <button type="button" onclick="openEditModal(<?= $jsonData ?>)" class="pill-btn pill-warning pill-sm" style="margin-right: 5px;">Configure</button>
                    <button type="button" onclick="event.stopPropagation(); printLabels([<?= $cid ?>]);" class="pill-btn pill-info pill-sm" title="Print this equipment's label" style="margin-right: 5px;">🏷️</button>
                    <a href="#" onclick="openWccConfirm('WARNING: Deleting equipment destroys linked Work Orders and BOMs! Proceed?', 'setup_vault_equipment.php?delete_id=<?= urlencode($i['equip_id']) ?>&csrf=<?= wcc_csrf_token() ?>', 'Delete Equipment'); return false;" class="pill-btn pill-danger pill-sm">Delete</a>
                </td>
            </tr>
            <tr class="child-row" id="acc-<?= $cid ?>">
                <td colspan="12">
                    <div class="child-content" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                        <div class="layer-panel">
                            <h4>Identity & Context</h4>
                            <div class="data-pair"><strong>Criticality:</strong> <span><span class="<?= $critClass ?> prio-badge">CLASS <?= htmlspecialchars($crit) ?></span> <?= $critLabel ?></span></div>
                            <div class="data-pair"><strong>OEM Brand:</strong> <span><?= htmlspecialchars(!empty($i['oem_brand']) ? $i['oem_brand'] : 'N/A') ?></span></div>
                            <div class="data-pair"><strong>OEM Model:</strong> <span><?= htmlspecialchars(!empty($i['oem_model']) ? $i['oem_model'] : 'N/A') ?></span></div>
                            <div class="data-pair"><strong>OEM Serial:</strong> <span><?= htmlspecialchars(!empty($i['oem_serial']) ? $i['oem_serial'] : 'N/A') ?></span></div>
                            <div class="data-pair"><strong>Plant Location:</strong> <span><?= htmlspecialchars(!empty($i['plant_name']) ? $i['plant_name'] : 'N/A') ?></span></div>
                            <div class="data-pair"><strong>Line Name:</strong> <span><?= htmlspecialchars(!empty($i['line_name']) ? $i['line_name'] : 'N/A') ?></span></div>
                        </div>
                        <div class="layer-panel">
                            <h4>Lifecycle & Finance</h4>
                            <div class="data-pair"><strong>Asset Purchase ID / PO:</strong> <span><?= htmlspecialchars(!empty($i['asset_purchase_id']) ? $i['asset_purchase_id'] : 'N/A') ?></span></div>
                            <div class="data-pair"><strong>Purchase Order Value:</strong> <span>$<?= htmlspecialchars(!empty($i['po_value']) ? number_format($i['po_value'], 2) : '0.00') ?></span></div>
                            <div class="data-pair"><strong>Lifecycle Horizon:</strong> <span><?= htmlspecialchars(!empty($i['lifecycle_years']) ? $i['lifecycle_years'] : '10') ?> Years</span></div>
                            <div class="data-pair"><strong>FAT Sign-off:</strong> <span><?= htmlspecialchars(!empty($i['fat_date']) ? $i['fat_date'] : 'Pending') ?></span></div>
                            <div class="data-pair"><strong>SAT Sign-off:</strong> <span><?= htmlspecialchars(!empty($i['sat_date']) ? $i['sat_date'] : 'Pending') ?></span></div>
                        </div>
                        <div class="layer-panel">
                            <h4>Ops & Safety</h4>
                            <div class="data-pair"><strong>Baseline Speed:</strong> <span><?= htmlspecialchars(!empty($i['base_speed']) ? $i['base_speed'] : 'N/A') ?></span></div>
                            <div class="data-pair"><strong>Baseline Press:</strong> <span><?= htmlspecialchars(!empty($i['base_pressure']) ? $i['base_pressure'] : 'N/A') ?></span></div>
                            <div class="data-pair"><strong>PM Interval:</strong> <span><?= htmlspecialchars(!empty($i['pm_days_interval']) ? $i['pm_days_interval'] : 'N/A') ?> Days</span></div>
                            <?php foreach($custom_fields as $cf): if(!empty($cf['key'])): ?>
                            <div class="data-pair"><strong><?= htmlspecialchars($cf['key']) ?>:</strong> <span><?= htmlspecialchars($cf['value']) ?></span></div>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Configuration Modal -->
    <div id="addModal" class="modal">
      <div class="modal-content vault-modal-content" style="padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.5);">
        <span class="close" onclick="document.getElementById('addModal').style.display='none'">&times;</span>
        <h2 id="modalTitle">Register Equipment</h2>
        <form method="POST" id="equipForm">
            <input type="hidden" name="equip_id" id="edit_equip_id">
            
            <div class="vault-grid">
                <div class="layer-box">
                    <h4>Layer 1: Identity & Context</h4>
                    <label>Asset UUID (Leave blank to auto-generate)</label>
                    <input type="text" name="asset_uuid" id="edit_asset_uuid">
                    <label>Equipment Name *</label>
                    <input type="text" name="equip_name" id="edit_equip_name" required>
                    <label>OEM Brand</label>
                    <input type="text" name="oem_brand" id="edit_oem_brand">
                    <label>OEM Model</label>
                    <input type="text" name="oem_model" id="edit_oem_model">
                    <label>OEM Serial Number</label>
                    <input type="text" name="oem_serial" id="edit_oem_serial">
                    <label>Category</label>
                    <input type="text" name="category" id="edit_category">
                    <label>Criticality Ranking</label>
                    <select name="criticality" id="edit_criticality">
                        <option value="A">Class A (High)</option>
                        <option value="B" selected>Class B (Normal)</option>
                        <option value="C">Class C (Low)</option>
                    </select>
                    <label>Workshop</label>
                    <select name="workshop_id" id="edit_workshop_id">
                        <option value="">-- Unassigned --</option>
                        <?php foreach($workshops as $w): ?>
                            <option value="<?= $w['workshop_id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Production Line</label>
                    <select name="line_id" id="edit_line_id">
                        <option value="">-- Unassigned --</option>
                        <?php foreach($production_lines as $l): ?>
                            <option value="<?= $l['line_id'] ?>"><?= htmlspecialchars($l['name']) ?> (<?= htmlspecialchars($l['workshop_id']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="layer-box">
                    <h4>Layer 2: Lifecycle & Finance</h4>
                    <label>Asset Purchase ID / PO Number</label>
                    <input type="text" name="asset_purchase_id" id="edit_asset_purchase_id" placeholder="e.g. PO-2026-X80">
                    <label>Purchase Order Value ($)</label>
                    <input type="number" step="0.01" name="po_value" id="edit_po_value">
                    <label>Lifecycle Horizon (Years)</label>
                    <input type="number" name="lifecycle_years" id="edit_lifecycle">
                    <label>FAT Sign-off Date</label>
                    <input type="date" name="fat_date" id="edit_fat">
                    <label>SAT Sign-off Date</label>
                    <input type="date" name="sat_date" id="edit_sat">
                    <div style="margin-top: 15px;">
                        <label style="display:inline-flex; align-items:center; cursor:pointer;">
                            <input type="checkbox" name="is_active" id="edit_is_active" value="1" style="width:20px !important; margin:0 10px 0 0 !important; display:inline-block !important;">
                            <span>Active / Production Ready</span>
                        </label>
                    </div>
                </div>

                <div class="layer-box">
                    <h4>Layer 3: Ops & Safety</h4>
                    <label>Baseline Speed</label>
                    <input type="text" name="base_speed" id="edit_base_speed" placeholder="e.g. 1500 RPM">
                    <label>Baseline Pressure</label>
                    <input type="text" name="base_pressure" id="edit_base_pressure" placeholder="e.g. 120 PSI">
                    <label>PM Interval (Days)</label>
                    <input type="number" name="pm_days_interval" id="edit_pm_days" placeholder="e.g. 180">
                    <label style="display:flex; align-items:center; gap: 8px;">
                        Label Generator
                        <select id="labelSymbology" onchange="saveLabelSymbology(this.value)" title="Code type printed on labels (global preference)" style="margin-left:auto; width:auto; padding:4px 8px;">
                            <option value="qrcode" <?= ($label_cfg['equip_label_symbology'] ?? 'qrcode') === 'qrcode' ? 'selected' : '' ?>>QR Code</option>
                            <option value="datamatrix" <?= ($label_cfg['equip_label_symbology'] ?? '') === 'datamatrix' ? 'selected' : '' ?>>DataMatrix</option>
                        </select>
                        <span style="cursor: pointer; font-size: 1.2em;" title="Preview the label code" onclick="openBarcodePreview()">🔲</span>
                    </label>
                    <div style="margin-top:20px; border-top:1px solid var(--panel-border); padding-top:10px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                            <label style="color: var(--text-accent); margin:0;">Dynamic Custom Fields</label>
                            <button type="button" class="pill-btn pill-warning pill-sm" onclick="addCustomField()">+ Add Field</button>
                        </div>
                        <div id="custom_fields_container"></div>
                    </div>
                </div>
            </div>
            <button type="submit" class="pill-btn pill-success pill-block">💾 Save Equipment Configuration</button>
        </form>
      </div>
    </div>

    <!-- BOM Modal -->
    <div id="bomModal" class="modal">
      <div class="modal-content vault-modal-content" style="padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); max-width: 700px;">
        <span class="close" onclick="document.getElementById('bomModal').style.display='none'">&times;</span>
        <h2 id="bomModalTitle">Manage BOM</h2>
        
        <table class="data-table" style="margin-bottom:20px;">
            <thead>
                <tr>
                    <th>Part SKU</th>
                    <th>Part Name</th>
                    <th>Quantity</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="bomTableBody">
            </tbody>
        </table>

        <h4 style="color:var(--text-accent); margin-top:30px; border-top:1px solid var(--panel-border); padding-top:15px;">Add Part to BOM</h4>
        <form method="POST">
            <input type="hidden" name="action" value="add_bom">
            <input type="hidden" name="bom_equip_id" id="bom_equip_id">
            
            <div style="display:grid; grid-template-columns: 2fr 1fr; gap:15px; align-items:end;">
                <div>
                    <label>Select Part</label>
                    <select name="part_id" required style="width:100%;">
                        <?php foreach($all_parts as $p): ?>
                            <option value="<?= $p['part_id'] ?>"><?= htmlspecialchars($p['part_name']) ?> (<?= htmlspecialchars($p['internal_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Quantity per Machine</label>
                    <input type="number" name="quantity" value="1" min="1" required style="width:100%;">
                </div>
            </div>
            <button type="submit" class="pill-btn pill-success pill-block" style="margin-top:20px;">+ Add Part to BOM</button>
        </form>
      </div>
    </div>

    <!-- UUID Configurator Modal -->
    <div id="uuidConfigModal" class="modal">
      <div class="modal-content vault-modal-content" style="padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); max-width: 800px;">
        <span class="close" onclick="document.getElementById('uuidConfigModal').style.display='none'">&times;</span>
        <h2>⚙️ UUID Generation Rules</h2>
        
        <div class="form-section" style="border-top:none; margin-top:0; padding-top:0;">
            <p style="color:var(--text-secondary); margin-bottom: 20px;">Define structural templates for automatically generating Asset UUIDs based on the Equipment Category.</p>
            
            <form method="POST" style="background: rgba(0,0,0,0.1); padding: 15px; border-radius: 8px; border: 1px solid var(--panel-border);">
                <input type="hidden" name="action" value="add_uuid_rule">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; align-items:end;">
                    <div>
                        <label>Category (Target)</label>
                        <input type="text" name="rule_category" placeholder="e.g. Mechanical, Electrical, or GLOBAL_DEFAULT" required style="width:100%;">
                    </div>
                    <div>
                        <label>Static Prefix</label>
                        <input type="text" name="rule_prefix" placeholder="e.g. 212- or MCH-" style="width:100%;">
                    </div>
                    <div>
                        <label>Auto-Increment Serial Length</label>
                        <input type="number" name="serial_length" value="4" min="1" max="10" required style="width:100%;">
                    </div>
                    <div>
                        <label>Appended Random Chars Length</label>
                        <input type="number" name="random_chars" value="0" min="0" max="10" required style="width:100%;">
                    </div>
                    <div style="grid-column: span 2;">
                        <label>Random Character Set Type</label>
                        <select name="char_type" style="width:100%;">
                            <option value="NUMERIC">Numeric Only (0-9)</option>
                            <option value="ALPHANUMERIC" selected>Alphanumeric (A-Z, 0-9)</option>
                            <option value="SPECIAL">Alphanumeric + Special Characters</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="pill-btn pill-success pill-block" style="margin-top:15px;">+ Create Rule</button>
            </form>

            <h4 style="margin-top: 25px; color: var(--text-accent);">Active Configuration Rules</h4>
            <div style="overflow-x: auto;">
                <table class="data-table" style="font-size: 0.85em;">
                    <thead><tr><th>Category Target</th><th>Generation Template</th><th>Current Serial</th><th>Act</th></tr></thead>
                    <tbody>
                        <?php foreach($uuid_rules as $r): ?>
                        <tr>
                            <td style="font-weight:bold; color:var(--text-accent);"><?= htmlspecialchars($r['category']) ?></td>
                            <td style="font-family:monospace;"><?= htmlspecialchars($r['prefix']) ?>[{SERIAL:<?= $r['serial_length'] ?>}]-<?= $r['random_chars'] > 0 ? '{RAND:' . $r['random_chars'] . ':' . $r['char_type'] . '}' : '' ?></td>
                            <td><?= $r['current_serial'] ?></td>
                            <td><a href="#" onclick="openWccConfirm('Delete this UUID Rule?', '?delete_rule_id=<?= $r['rule_id'] ?>&csrf=<?= wcc_csrf_token() ?>', 'Delete Rule'); return false;" class="pill-btn pill-danger pill-sm">✕</a></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($uuid_rules)): ?>
                        <tr><td colspan="4" style="text-align:center;">No custom UUID rules configured.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
      </div>
    </div>

    <!-- Barcode Preview Modal -->
    <div id="barcodeModal" class="modal">
      <div class="modal-content vault-modal-content" style="padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); max-width: 400px; text-align: center;">
        <span class="close" onclick="document.getElementById('barcodeModal').style.display='none'">&times;</span>
        <h3 id="barcodeModalTitle" style="color:var(--text-accent); margin-bottom: 20px;">Label Code Preview</h3>
        <div style="background: white; padding: 20px; border-radius: 8px; display: inline-block;">
            <canvas id="barcodeCanvas"></canvas>
        </div>
        <div id="barcodePayload" style="font-family:monospace; font-size:0.78em; color:var(--text-secondary); margin-top:12px; word-break:break-all;"></div>
        <div id="barcodeError" style="color: #ef4444; margin-top: 15px; font-size: 0.9em; display: none;"></div>
        <button type="button" class="pill-btn pill-info" id="barcodePrintBtn" style="margin-top:15px;" onclick="printLabelFromPreview()">🖨️ Print This Label</button>
      </div>
    </div>

    <!-- Label & Printer Setup Modal -->
    <div id="labelSetupModal" class="modal">
      <div class="modal-content vault-modal-content" style="padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); max-width: 760px;">
        <span class="close" onclick="document.getElementById('labelSetupModal').style.display='none'">&times;</span>
        <h2>🖨️ Label &amp; Printer Setup</h2>
        <p style="color:var(--text-secondary); margin-bottom:18px;">Configure the print device and label layout. For "Any printer" methods the browser's print dialog is where you pick the physical device.</p>

        <label>Print Method / Device</label>
        <select id="ls_method" onchange="labelSetupSections()" style="width:100%;">
            <option value="browser_sheet">Any printer — sheet of labels per page (A4/Letter sticker sheets)</option>
            <option value="browser_single">Any printer — one label per page (driver-installed label printer)</option>
            <option value="network_zpl">Zebra network printer — direct ZPL (no dialog, prints instantly)</option>
        </select>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-top:15px;">
            <div>
                <label>Label Width (mm)</label>
                <input type="number" id="ls_label_w" step="0.1" min="10" max="300" style="width:100%;" oninput="labelSetupReadout()">
            </div>
            <div>
                <label>Label Height (mm)</label>
                <input type="number" id="ls_label_h" step="0.1" min="10" max="300" style="width:100%;" oninput="labelSetupReadout()">
            </div>
        </div>

        <div id="ls_sheet_section" style="border-top:1px solid var(--panel-border); margin-top:15px; padding-top:12px;">
            <h4 style="color:var(--text-accent); margin-bottom:10px;">Page Setup (sheet mode)</h4>
            <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:15px;">
                <div>
                    <label>Page Size</label>
                    <select id="ls_preset" onchange="labelPresetChanged()" style="width:100%;">
                        <option value="a4">A4 (210 × 297)</option>
                        <option value="letter">Letter (215.9 × 279.4)</option>
                        <option value="custom">Custom…</option>
                    </select>
                </div>
                <div>
                    <label>Page Width (mm)</label>
                    <input type="number" id="ls_page_w" step="0.1" min="50" max="500" style="width:100%;" oninput="labelSetupReadout()">
                </div>
                <div>
                    <label>Page Height (mm)</label>
                    <input type="number" id="ls_page_h" step="0.1" min="50" max="500" style="width:100%;" oninput="labelSetupReadout()">
                </div>
                <div>
                    <label>Page Margin (mm)</label>
                    <input type="number" id="ls_margin" step="0.5" min="0" max="50" style="width:100%;" oninput="labelSetupReadout()">
                </div>
                <div>
                    <label>Column Gap (mm)</label>
                    <input type="number" id="ls_gap_x" step="0.5" min="0" max="50" style="width:100%;" oninput="labelSetupReadout()">
                </div>
                <div>
                    <label>Row Gap (mm)</label>
                    <input type="number" id="ls_gap_y" step="0.5" min="0" max="50" style="width:100%;" oninput="labelSetupReadout()">
                </div>
            </div>
            <div id="ls_readout" style="margin-top:10px; padding:8px 12px; background:rgba(13,148,136,0.12); border:1px solid #0d9488; border-radius:8px; color:var(--text-primary); font-size:0.9em;"></div>
        </div>

        <div id="ls_zebra_section" style="border-top:1px solid var(--panel-border); margin-top:15px; padding-top:12px;">
            <h4 style="color:var(--text-accent); margin-bottom:10px;">Zebra Device (network)</h4>
            <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:15px;">
                <div style="grid-column: span 2;">
                    <label>Printer IP / Hostname</label>
                    <input type="text" id="ls_ip" placeholder="e.g. 192.168.1.60" style="width:100%;">
                </div>
                <div>
                    <label>Port</label>
                    <input type="number" id="ls_port" min="1" max="65535" style="width:100%;">
                </div>
                <div>
                    <label>DPI</label>
                    <select id="ls_dpi" style="width:100%;">
                        <option value="203">203 dpi</option>
                        <option value="300">300 dpi</option>
                    </select>
                </div>
                <div>
                    <label>Darkness (0-30)</label>
                    <input type="number" id="ls_darkness" min="0" max="30" style="width:100%;">
                </div>
                <div>
                    <label>Speed (ips)</label>
                    <input type="number" id="ls_speed" min="1" max="14" style="width:100%;">
                </div>
            </div>
        </div>

        <div style="border-top:1px solid var(--panel-border); margin-top:15px; padding-top:12px;">
            <h4 style="color:var(--text-accent); margin-bottom:10px;">Fields Printed on the Label <span style="color:var(--text-secondary); font-weight:normal; font-size:0.85em;">(name is always shown)</span></h4>
            <div style="display:flex; gap:18px; flex-wrap:wrap;">
                <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer;"><input type="checkbox" id="lsf_uuid" style="width:16px;height:16px;"> Asset UUID</label>
                <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer;"><input type="checkbox" id="lsf_serial" style="width:16px;height:16px;"> Serial No.</label>
                <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer;"><input type="checkbox" id="lsf_brand_model" style="width:16px;height:16px;"> Make / Model</label>
                <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer;"><input type="checkbox" id="lsf_location" style="width:16px;height:16px;"> Location</label>
                <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer;"><input type="checkbox" id="lsf_category_crit" style="width:16px;height:16px;"> Category / Class</label>
            </div>
        </div>

        <div style="display:flex; gap:10px; margin-top:20px; justify-content:flex-end;">
            <button type="button" class="pill-btn pill-warning" onclick="labelTestPrint()">🧪 Test Print</button>
            <button type="button" class="pill-btn pill-success" onclick="saveLabelSettings()">💾 Save Settings</button>
        </div>
      </div>
    </div>

    <script>
        // ---- Equipment label system (config, selection, preview, print) ----
        const LABEL_CSRF = '<?= wcc_csrf_token() ?>';
        let labelCfg = <?= json_encode($label_cfg) ?>;
        let previewEquipId = null;

        function labelFieldsObj() {
            try { return JSON.parse(labelCfg.equip_label_fields || '{}') || {}; } catch (e) { return {}; }
        }

        // --- Selection ---
        function visibleParentRows() {
            return [...document.querySelectorAll('#vaultTable tr.parent-row')].filter(r => r.style.display !== 'none');
        }
        function updateLabelSelection() {
            const n = document.querySelectorAll('#vaultTable .label-select:checked').length;
            document.getElementById('labelSelCount').innerText = n;
            const btn = document.getElementById('printLabelsBtn');
            btn.disabled = n === 0;
            btn.style.opacity = n === 0 ? '0.5' : '1';
        }
        function toggleSelectAllLabels(master) {
            visibleParentRows().forEach(r => {
                const cb = r.querySelector('.label-select');
                if (cb) cb.checked = master.checked;
            });
            updateLabelSelection();
        }
        function printSelectedLabels() {
            const ids = [...document.querySelectorAll('#vaultTable .label-select:checked')].map(cb => parseInt(cb.value, 10));
            if (ids.length) printLabels(ids);
        }

        // --- Printing (method-aware) ---
        async function printLabels(ids) {
            if (labelCfg.equip_label_method === 'network_zpl') {
                try {
                    const resp = await fetch('/_eam/equipment_labels.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'print_labels', csrf: LABEL_CSRF, ids: ids })
                    });
                    const res = await resp.json();
                    showToast(res.message, res.status === 'success' ? 'success' : 'error');
                } catch (e) {
                    showToast('Print failed — could not reach the server.', 'error');
                }
            } else {
                window.open('/_eam/equipment_labels.php?ids=' + ids.join(','), 'WccLabels', 'width=980,height=800,menubar=no,toolbar=no');
            }
        }
        function printLabelFromPreview() {
            if (previewEquipId) printLabels([parseInt(previewEquipId, 10)]);
        }

        // --- Code preview in the edit modal (compact payload, chosen symbology) ---
        function buildPreviewPayload() {
            const seg = s => (s || '').trim().replace(/\|/g, '/');
            const parts = ['WCC', document.getElementById('edit_equip_id').value || '0'];
            const uuid = seg(document.getElementById('edit_asset_uuid').value);
            if (uuid) parts.push(uuid);
            const name = seg(document.getElementById('edit_equip_name').value);
            if (name) parts.push(name.substring(0, 40));
            const sn = seg(document.getElementById('edit_oem_serial').value);
            if (sn) parts.push('SN:' + sn);
            return parts.join('|');
        }
        function openBarcodePreview() {
            const equipId = document.getElementById('edit_equip_id').value;
            if (!equipId) {
                openWccAlert('Error', 'Please save the equipment first to preview its label code.');
                return;
            }
            previewEquipId = equipId;
            const symb = document.getElementById('labelSymbology').value;
            const payload = buildPreviewPayload();

            document.getElementById('barcodeModalTitle').innerText = (symb === 'datamatrix' ? 'DataMatrix' : 'QR Code') + ' Preview';
            document.getElementById('barcodePayload').innerText = payload;
            document.getElementById('barcodeError').style.display = 'none';
            document.getElementById('barcodeModal').style.display = 'block';

            try {
                bwipjs.toCanvas('barcodeCanvas', {
                    bcid: symb,
                    text: payload,
                    scale: 3,
                    includetext: false
                });
            } catch (e) {
                document.getElementById('barcodeError').innerText = 'Error generating code: ' + e;
                document.getElementById('barcodeError').style.display = 'block';
            }
        }
        async function saveLabelSymbology(v) {
            labelCfg.equip_label_symbology = v;
            await postLabelSettings({ equip_label_symbology: v }, 'Code type saved — labels will use ' + (v === 'datamatrix' ? 'DataMatrix' : 'QR Code') + '.');
        }

        // --- Setup modal ---
        function openLabelSetup() {
            document.getElementById('ls_method').value = labelCfg.equip_label_method;
            document.getElementById('ls_label_w').value = labelCfg.equip_label_width_mm;
            document.getElementById('ls_label_h').value = labelCfg.equip_label_height_mm;
            document.getElementById('ls_preset').value = labelCfg.equip_label_page_preset;
            document.getElementById('ls_page_w').value = labelCfg.equip_label_page_width_mm;
            document.getElementById('ls_page_h').value = labelCfg.equip_label_page_height_mm;
            document.getElementById('ls_margin').value = labelCfg.equip_label_margin_mm;
            document.getElementById('ls_gap_x').value = labelCfg.equip_label_gap_x_mm;
            document.getElementById('ls_gap_y').value = labelCfg.equip_label_gap_y_mm;
            document.getElementById('ls_ip').value = labelCfg.equip_label_printer_ip;
            document.getElementById('ls_port').value = labelCfg.equip_label_printer_port;
            document.getElementById('ls_dpi').value = labelCfg.equip_label_dpi;
            document.getElementById('ls_darkness').value = labelCfg.equip_label_darkness;
            document.getElementById('ls_speed').value = labelCfg.equip_label_speed;
            const f = labelFieldsObj();
            ['uuid', 'serial', 'brand_model', 'location', 'category_crit'].forEach(k => {
                document.getElementById('lsf_' + k).checked = !!f[k];
            });
            labelSetupSections();
            labelSetupReadout();
            document.getElementById('labelSetupModal').style.display = 'block';
        }
        function labelSetupSections() {
            const m = document.getElementById('ls_method').value;
            document.getElementById('ls_sheet_section').style.display = (m === 'browser_sheet') ? '' : 'none';
            document.getElementById('ls_zebra_section').style.display = (m === 'network_zpl') ? '' : 'none';
        }
        function labelPresetChanged() {
            const p = document.getElementById('ls_preset').value;
            if (p === 'a4') { document.getElementById('ls_page_w').value = 210; document.getElementById('ls_page_h').value = 297; }
            if (p === 'letter') { document.getElementById('ls_page_w').value = 215.9; document.getElementById('ls_page_h').value = 279.4; }
            labelSetupReadout();
        }
        function labelSetupReadout() {
            const num = id => parseFloat(document.getElementById(id).value) || 0;
            const pw = num('ls_page_w'), ph = num('ls_page_h'), m = num('ls_margin');
            const lw = num('ls_label_w'), lh = num('ls_label_h'), gx = num('ls_gap_x'), gy = num('ls_gap_y');
            let text = '—';
            if (lw > 0 && lh > 0 && pw > 0 && ph > 0) {
                const cols = Math.max(1, Math.floor((pw - 2 * m + gx) / (lw + gx)));
                const rows = Math.max(1, Math.floor((ph - 2 * m + gy) / (lh + gy)));
                text = '📐 ' + cols + ' × ' + rows + ' = <strong>' + (cols * rows) + ' labels per page</strong> (' + lw + '×' + lh + 'mm labels on ' + pw + '×' + ph + 'mm paper)';
            }
            document.getElementById('ls_readout').innerHTML = text;
        }
        function gatherLabelSettings() {
            return {
                equip_label_method: document.getElementById('ls_method').value,
                equip_label_width_mm: document.getElementById('ls_label_w').value,
                equip_label_height_mm: document.getElementById('ls_label_h').value,
                equip_label_page_preset: document.getElementById('ls_preset').value,
                equip_label_page_width_mm: document.getElementById('ls_page_w').value,
                equip_label_page_height_mm: document.getElementById('ls_page_h').value,
                equip_label_margin_mm: document.getElementById('ls_margin').value,
                equip_label_gap_x_mm: document.getElementById('ls_gap_x').value,
                equip_label_gap_y_mm: document.getElementById('ls_gap_y').value,
                equip_label_printer_ip: document.getElementById('ls_ip').value,
                equip_label_printer_port: document.getElementById('ls_port').value,
                equip_label_dpi: document.getElementById('ls_dpi').value,
                equip_label_darkness: document.getElementById('ls_darkness').value,
                equip_label_speed: document.getElementById('ls_speed').value,
                equip_label_fields: {
                    uuid: document.getElementById('lsf_uuid').checked,
                    serial: document.getElementById('lsf_serial').checked,
                    brand_model: document.getElementById('lsf_brand_model').checked,
                    location: document.getElementById('lsf_location').checked,
                    category_crit: document.getElementById('lsf_category_crit').checked
                }
            };
        }
        async function postLabelSettings(settings, okMsg) {
            try {
                const resp = await fetch('/_eam/equipment_labels.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'save_label_settings', csrf: LABEL_CSRF, settings: settings })
                });
                const res = await resp.json();
                if (res.status === 'success') {
                    showToast(okMsg || res.message, 'success');
                    return true;
                }
                showToast(res.message, 'error');
                return false;
            } catch (e) {
                showToast('Could not save label settings.', 'error');
                return false;
            }
        }
        async function saveLabelSettings() {
            const s = gatherLabelSettings();
            if (await postLabelSettings(s, 'Label settings saved.')) {
                // keep the local copy in sync so printing uses the new method immediately
                Object.keys(s).forEach(k => {
                    labelCfg[k] = (k === 'equip_label_fields') ? JSON.stringify(s[k]) : String(s[k]);
                });
                document.getElementById('labelSetupModal').style.display = 'none';
            }
        }
        async function labelTestPrint() {
            const m = document.getElementById('ls_method').value;
            if (m !== 'network_zpl') {
                // Browser methods: a test print is just the print window with the first equipment
                const first = document.querySelector('#vaultTable .label-select');
                if (first) { printLabels([parseInt(first.value, 10)]); }
                else { showToast('No equipment available for a test label.', 'warning'); }
                return;
            }
            // Save current form first so the test uses what's on screen, then fire the test label
            if (!(await postLabelSettings(gatherLabelSettings(), 'Settings saved — sending test label…'))) return;
            Object.assign(labelCfg, { equip_label_method: 'network_zpl' });
            try {
                const resp = await fetch('/_eam/equipment_labels.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'test_print', csrf: LABEL_CSRF })
                });
                const res = await resp.json();
                showToast(res.message, res.status === 'success' ? 'success' : 'error');
            } catch (e) {
                showToast('Test print failed — could not reach the server.', 'error');
            }
        }
    </script>
</body>
</html>

