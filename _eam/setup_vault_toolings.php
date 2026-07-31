<?php
/**
 * Tooling Setup Vault
 * Structural clone of setup_vault_equipment.php — same CSS, table, accordion, search, modals.
 * Domain: tooling codes, machine allocation, tooling BOM (parts on tools), barcode gen.
 */
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('manage_toolings');

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/demo_mode.php';
wcc_demo_guard_destructive_get();   // public demo: block ?delete_*=… style handlers
require_once __DIR__ . '/label_lib.php';
$pdo = get_wcc_db_connection();
$label_cfg = wcc_label_settings($pdo);
$tooling_symbology = $label_cfg['tooling_label_symbology'] ?? 'code128';
if (!in_array($tooling_symbology, ['code128', 'qrcode', 'datamatrix'], true)) {
    $tooling_symbology = 'code128';
}

$CATEGORIES = ['Die', 'Mold', 'Fixture', 'Jig', 'Gauge', 'Hand Tool', 'Cutting Tool', 'Other'];
$STATUSES = ['Available', 'In Use', 'Maintenance', 'Calibration Due', 'Retired'];
$CONDITIONS = ['New', 'Good', 'Fair', 'Poor'];

/**
 * Auto tooling code from uuid_rules (target_entity = Tooling) — same engine as equipment UUIDs.
 * Falls back to TL-{CAT}-### if no rule is configured.
 */
function wcc_next_tooling_code(PDO $pdo, string $category = ''): string
{
    $rule = null;
    if ($category !== '') {
        $st = $pdo->prepare("SELECT * FROM uuid_rules WHERE target_entity = 'Tooling' AND category = ? LIMIT 1");
        $st->execute([$category]);
        $rule = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    if (!$rule) {
        $st = $pdo->prepare("SELECT * FROM uuid_rules WHERE target_entity = 'Tooling' AND category = 'GLOBAL_DEFAULT' LIMIT 1");
        $st->execute();
        $rule = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    if ($rule) {
        $prefix = (string)($rule['prefix'] ?? '');
        $serial = str_pad((string)(int)$rule['current_serial'], max(1, (int)$rule['serial_length']), '0', STR_PAD_LEFT);
        $random_part = '';
        $rChars = (int)($rule['random_chars'] ?? 0);
        if ($rChars > 0) {
            $chars = '0123456789';
            if (($rule['char_type'] ?? '') === 'ALPHANUMERIC') $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            if (($rule['char_type'] ?? '') === 'SPECIAL') $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*';
            for ($i = 0; $i < $rChars; $i++) {
                $random_part .= $chars[mt_rand(0, strlen($chars) - 1)];
            }
        }
        $code = $prefix . $serial . ($random_part !== '' ? '-' . $random_part : '');
        $pdo->prepare("UPDATE uuid_rules SET current_serial = current_serial + 1 WHERE rule_id = ?")
            ->execute([(int)$rule['rule_id']]);
        return $code;
    }

    // Legacy fallback when no Tooling rules exist yet
    $map = [
        'Die' => 'DIE', 'Mold' => 'MLD', 'Fixture' => 'FIX', 'Jig' => 'JIG',
        'Gauge' => 'GAU', 'Hand Tool' => 'HND', 'Cutting Tool' => 'CUT', 'Other' => 'GEN',
    ];
    $prefix = $map[$category] ?? 'GEN';
    $like = 'TL-' . $prefix . '-%';
    $st = $pdo->prepare("SELECT tooling_code FROM toolings WHERE tooling_code LIKE ? ORDER BY tooling_id DESC LIMIT 1");
    $st->execute([$like]);
    $last = $st->fetchColumn();
    $n = 1;
    if ($last && preg_match('/-(\d+)$/', (string)$last, $m)) {
        $n = (int)$m[1] + 1;
    }
    return sprintf('TL-%s-%03d', $prefix, $n);
}

function wcc_tooling_barcode_from_code(string $code): string
{
    $safe = preg_replace('/[^A-Za-z0-9\-]/', '', $code);
    return 'BC-' . ($safe !== '' ? $safe : ('TL-' . strtoupper(bin2hex(random_bytes(3)))));
}

try {
    if (isset($_GET['retire_id'])) {
        wcc_csrf_require();
        $pdo->prepare("UPDATE toolings SET deleted_at = NOW(), status = 'Retired', is_active = 0, updated_at = NOW() WHERE tooling_id = ? AND deleted_at IS NULL")
            ->execute([(int)$_GET['retire_id']]);
        header('Location: /_eam/setup_vault_toolings.php');
        exit;
    }
    if (isset($_GET['restore_id'])) {
        wcc_csrf_require();
        $pdo->prepare("UPDATE toolings SET deleted_at = NULL, is_active = 1, status = 'Available', updated_at = NOW() WHERE tooling_id = ?")
            ->execute([(int)$_GET['restore_id']]);
        header('Location: /_eam/setup_vault_toolings.php?show_retired=1');
        exit;
    }
    if (isset($_GET['delete_bom_id'])) {
        wcc_csrf_require();
        $pdo->prepare("DELETE FROM tooling_bom WHERE bom_id = ?")->execute([(int)$_GET['delete_bom_id']]);
        header('Location: /_eam/setup_vault_toolings.php');
        exit;
    }
    if (isset($_GET['delete_rule_id'])) {
        wcc_csrf_require();
        $pdo->prepare("DELETE FROM uuid_rules WHERE rule_id = ? AND target_entity = 'Tooling'")
            ->execute([(int)$_GET['delete_rule_id']]);
        header('Location: /_eam/setup_vault_toolings.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        wcc_csrf_require();

        // Code generation rule (mirrors equipment UUID configurator)
        if (isset($_POST['action']) && $_POST['action'] === 'add_code_rule') {
            $cat = trim((string)($_POST['rule_category'] ?? ''));
            $prefix = trim((string)($_POST['rule_prefix'] ?? ''));
            $s_len = max(1, min(10, (int)($_POST['serial_length'] ?? 3)));
            $r_chars = max(0, min(10, (int)($_POST['random_chars'] ?? 0)));
            $c_type = $_POST['char_type'] ?? 'NUMERIC';
            if (!in_array($c_type, ['NUMERIC', 'ALPHANUMERIC', 'SPECIAL'], true)) $c_type = 'NUMERIC';
            if ($cat !== '') {
                $pdo->prepare("INSERT INTO uuid_rules (target_entity, category, prefix, serial_length, random_chars, char_type) VALUES ('Tooling', ?, ?, ?, ?, ?)")
                    ->execute([$cat, $prefix, $s_len, $r_chars, $c_type]);
            }
            header('Location: /_eam/setup_vault_toolings.php');
            exit;
        }

        if (isset($_POST['action']) && $_POST['action'] === 'add_bom') {
            $tid = (int)($_POST['bom_tooling_id'] ?? 0);
            $pid = (int)($_POST['part_id'] ?? 0);
            $qty = max(1, (int)($_POST['quantity'] ?? 1));
            if ($tid && $pid) {
                $pdo->prepare("INSERT INTO tooling_bom (tooling_id, part_id, quantity) VALUES (?, ?, ?)
                               ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)")
                    ->execute([$tid, $pid, $qty]);
            }
            header('Location: /_eam/setup_vault_toolings.php');
            exit;
        }

        $id = !empty($_POST['tooling_id']) ? (int)$_POST['tooling_id'] : null;
        $code = trim((string)($_POST['tooling_code'] ?? ''));
        $name = trim((string)($_POST['tooling_name'] ?? ''));
        $category = trim((string)($_POST['category'] ?? ''));
        $tooling_type = trim((string)($_POST['tooling_type'] ?? ''));
        $barcode = trim((string)($_POST['barcode'] ?? ''));
        $asset_tag = trim((string)($_POST['asset_tag'] ?? ''));
        $oem_brand = trim((string)($_POST['oem_brand'] ?? ''));
        $oem_model = trim((string)($_POST['oem_model'] ?? ''));
        $serial_number = trim((string)($_POST['serial_number'] ?? ''));
        $status = $_POST['status'] ?? 'Available';
        if (!in_array($status, $STATUSES, true)) $status = 'Available';
        $condition_rating = $_POST['condition_rating'] ?? 'Good';
        if (!in_array($condition_rating, $CONDITIONS, true)) $condition_rating = 'Good';
        $location = trim((string)($_POST['location'] ?? ''));
        $workshop_id = !empty($_POST['workshop_id']) ? (int)$_POST['workshop_id'] : null;
        $line_id = !empty($_POST['line_id']) ? (int)$_POST['line_id'] : null;
        $linked_equip_id = !empty($_POST['linked_equip_id']) ? (int)$_POST['linked_equip_id'] : null;
        $owner_dept = trim((string)($_POST['owner_dept'] ?? ''));
        $calibration_due = !empty($_POST['calibration_due']) ? $_POST['calibration_due'] : null;
        $last_calibration = !empty($_POST['last_calibration']) ? $_POST['last_calibration'] : null;
        $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
        $cost = ($_POST['cost'] ?? '') !== '' ? (float)$_POST['cost'] : null;
        $notes = trim((string)($_POST['notes'] ?? ''));
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($name !== '') {
            if ($code === '') {
                $code = wcc_next_tooling_code($pdo, $category);
            }
            if ($barcode === '') {
                $barcode = wcc_tooling_barcode_from_code($code);
            }
            if ($asset_tag === '') {
                $asset_tag = 'AT-' . preg_replace('/^TL-/', '', $code);
            }

            if ($id) {
                $pdo->prepare("UPDATE toolings SET
                    tooling_code=?, tooling_name=?, category=?, tooling_type=?, barcode=?, asset_tag=?,
                    oem_brand=?, oem_model=?, serial_number=?, status=?, condition_rating=?, location=?,
                    workshop_id=?, line_id=?, linked_equip_id=?, owner_dept=?,
                    calibration_due=?, last_calibration=?, purchase_date=?, cost=?, notes=?, is_active=?,
                    updated_at=NOW()
                    WHERE tooling_id=? AND deleted_at IS NULL")->execute([
                    $code, $name, $category ?: null, $tooling_type ?: null, $barcode ?: null, $asset_tag ?: null,
                    $oem_brand ?: null, $oem_model ?: null, $serial_number ?: null, $status, $condition_rating, $location ?: null,
                    $workshop_id, $line_id, $linked_equip_id, $owner_dept ?: null,
                    $calibration_due, $last_calibration, $purchase_date, $cost, $notes ?: null, $is_active,
                    $id,
                ]);
            } else {
                $pdo->prepare("INSERT INTO toolings
                    (tooling_code, tooling_name, category, tooling_type, barcode, asset_tag,
                     oem_brand, oem_model, serial_number, status, condition_rating, location,
                     workshop_id, line_id, linked_equip_id, owner_dept,
                     calibration_due, last_calibration, purchase_date, cost, notes, is_active)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute([
                    $code, $name, $category ?: null, $tooling_type ?: null, $barcode ?: null, $asset_tag ?: null,
                    $oem_brand ?: null, $oem_model ?: null, $serial_number ?: null, $status, $condition_rating, $location ?: null,
                    $workshop_id, $line_id, $linked_equip_id, $owner_dept ?: null,
                    $calibration_due, $last_calibration, $purchase_date, $cost, $notes ?: null, $is_active,
                ]);
            }
            header('Location: /_eam/setup_vault_toolings.php');
            exit;
        }
    }

    $show_retired = isset($_GET['show_retired']);
    $sqlItems = "
        SELECT t.*, e.equip_name AS linked_equip_name
        FROM toolings t
        LEFT JOIN equipment e ON e.equip_id = t.linked_equip_id
        " . ($show_retired ? "" : "WHERE t.deleted_at IS NULL") . "
        ORDER BY t.deleted_at IS NULL DESC, t.tooling_id ASC
    ";
    $items = $pdo->query($sqlItems)->fetchAll(PDO::FETCH_ASSOC);

    $boms_by_tool = [];
    try {
        $bomRows = $pdo->query("
            SELECT b.bom_id, b.tooling_id, b.quantity, p.part_name, p.internal_code
            FROM tooling_bom b
            JOIN inventory_parts p ON p.part_id = b.part_id
            ORDER BY p.part_name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($bomRows as $b) {
            $boms_by_tool[$b['tooling_id']][] = $b;
        }
    } catch (Throwable $e) {
        $boms_by_tool = [];
    }

    try {
        $equipment = $pdo->query("SELECT equip_id, equip_name FROM equipment WHERE deleted_at IS NULL ORDER BY equip_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $equipment = $pdo->query("SELECT equip_id, equip_name FROM equipment ORDER BY equip_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    try {
        $all_parts = $pdo->query("SELECT part_id, part_name, internal_code FROM inventory_parts WHERE lifecycle_status = 'Active' ORDER BY part_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $all_parts = $pdo->query("SELECT part_id, part_name, internal_code FROM inventory_parts ORDER BY part_name ASC LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);
    }

    $workshops = [];
    try { $workshops = $pdo->query("SELECT workshop_id, name FROM workshops ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) {}
    $production_lines = [];
    try { $production_lines = $pdo->query("SELECT line_id, name FROM production_lines ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) {}

    $code_rules = $pdo->query("SELECT * FROM uuid_rules WHERE target_entity = 'Tooling' ORDER BY category ASC")->fetchAll(PDO::FETCH_ASSOC);

    // Seed sensible defaults once (same idea as equipment UUID rules)
    if (empty($code_rules)) {
        $defaults = [
            ['Die', 'TL-DIE-', 3],
            ['Mold', 'TL-MLD-', 3],
            ['Fixture', 'TL-FIX-', 3],
            ['Jig', 'TL-JIG-', 3],
            ['Gauge', 'TL-GAU-', 3],
            ['Hand Tool', 'TL-HND-', 3],
            ['Cutting Tool', 'TL-CUT-', 3],
            ['Other', 'TL-GEN-', 3],
            ['GLOBAL_DEFAULT', 'TL-GEN-', 3],
        ];
        foreach ($defaults as $d) {
            // Advance serial past any existing codes with this prefix
            $like = $d[1] . '%';
            $st = $pdo->prepare("SELECT tooling_code FROM toolings WHERE tooling_code LIKE ? ORDER BY tooling_id DESC LIMIT 1");
            $st->execute([$like]);
            $last = $st->fetchColumn();
            $serial = 1;
            if ($last && preg_match('/(\d+)$/', (string)$last, $m)) {
                $serial = (int)$m[1] + 1;
            }
            $pdo->prepare("INSERT INTO uuid_rules (target_entity, category, prefix, serial_length, current_serial, random_chars, char_type) VALUES ('Tooling', ?, ?, ?, ?, 0, 'NUMERIC')")
                ->execute([$d[0], $d[1], $d[2], $serial]);
        }
        $code_rules = $pdo->query("SELECT * FROM uuid_rules WHERE target_entity = 'Tooling' ORDER BY category ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    die('DB Error: ' . $e->getMessage());
}

$csrf = wcc_csrf_token();
$statusClass = [
    'Available' => 'badge-low', 'In Use' => 'badge-high', 'Maintenance' => 'badge-normal',
    'Calibration Due' => 'badge-critical', 'Retired' => 'badge-normal',
];

$page_title = __('tooling.vault_title');
require_once __DIR__ . '/../inc/head.php';
?>
    <style>
        /* EXACT copy of setup_vault_equipment.php page styles — no width clamps */
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
        .retired-row { opacity: 0.65; }
    </style>
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
            document.getElementById('f_tooling_id').value = data.tooling_id || '';
            document.getElementById('f_tooling_code').value = data.tooling_code || '';
            document.getElementById('f_tooling_name').value = data.tooling_name || '';
            document.getElementById('f_category').value = data.category || '';
            document.getElementById('f_tooling_type').value = data.tooling_type || '';
            document.getElementById('f_barcode').value = data.barcode || '';
            document.getElementById('f_asset_tag').value = data.asset_tag || '';
            document.getElementById('f_oem_brand').value = data.oem_brand || '';
            document.getElementById('f_oem_model').value = data.oem_model || '';
            document.getElementById('f_serial').value = data.serial_number || '';
            document.getElementById('f_status').value = data.status || 'Available';
            document.getElementById('f_condition').value = data.condition_rating || 'Good';
            document.getElementById('f_location').value = data.location || '';
            document.getElementById('f_workshop').value = data.workshop_id || '';
            document.getElementById('f_line').value = data.line_id || '';
            document.getElementById('f_linked_equip').value = data.linked_equip_id || '';
            document.getElementById('f_owner').value = data.owner_dept || '';
            document.getElementById('f_last_cal').value = data.last_calibration || '';
            document.getElementById('f_cal_due').value = data.calibration_due || '';
            document.getElementById('f_purchase').value = data.purchase_date || '';
            document.getElementById('f_cost').value = data.cost != null ? data.cost : '';
            document.getElementById('f_notes').value = data.notes || '';
            document.getElementById('f_is_active').checked = data.is_active == 1 || data.is_active === true || data.is_active === '1';
            document.getElementById('modalTitle').innerText = 'Modify Tooling Setup';
            document.getElementById('addModal').style.display = 'block';
        }

        function openAddModal() {
            document.getElementById('toolingForm').reset();
            document.getElementById('f_tooling_id').value = '';
            document.getElementById('f_is_active').checked = true;
            document.getElementById('f_status').value = 'Available';
            document.getElementById('f_condition').value = 'Good';
            document.getElementById('modalTitle').innerText = 'Register New Tooling';
            document.getElementById('addModal').style.display = 'block';
        }

        const bomData = <?= json_encode($boms_by_tool, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        function bomRowsForTool(toolingId) {
            if (!bomData) return [];
            if (bomData[toolingId] && bomData[toolingId].length) return bomData[toolingId];
            if (bomData[String(toolingId)] && bomData[String(toolingId)].length) return bomData[String(toolingId)];
            return [];
        }

        async function openBOMModal(toolingId, toolingName) {
            document.getElementById('bom_tooling_id').value = toolingId;
            document.getElementById('bomModalTitle').innerText = 'Manage Tool BOM: ' + toolingName;

            let tbody = document.getElementById('bomTableBody');
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:16px;">Loading...</td></tr>';
            document.getElementById('bomModal').style.display = 'block';

            const esc = (typeof escapeHtml === 'function') ? escapeHtml : function (s) { return String(s ?? ''); };
            let rows = bomRowsForTool(toolingId);

            // Live refresh from API (same source as ledger) if page-cache is empty/stale
            try {
                const resp = await fetch('/api/get_tooling_bom.php?tooling_id=' + toolingId);
                const result = await resp.json();
                if (result.status === 'success' && Array.isArray(result.data)) {
                    rows = result.data.map(function (p) {
                        return {
                            bom_id: p.bom_id || 0,
                            internal_code: p.sku,
                            part_name: p.part_name,
                            quantity: p.quantity
                        };
                    });
                }
            } catch (e) { /* keep cached rows */ }

            tbody.innerHTML = '';
            if (rows.length > 0) {
                rows.forEach(function (bom) {
                    const removeCell = bom.bom_id
                        ? '<a href="?delete_bom_id=' + bom.bom_id + '&csrf=<?= wcc_csrf_token() ?>" class="pill-btn pill-danger pill-sm">Remove</a>'
                        : '';
                    tbody.innerHTML += '<tr>'
                        + '<td>' + esc(bom.internal_code) + '</td>'
                        + '<td>' + esc(bom.part_name) + '</td>'
                        + '<td>' + esc(bom.quantity) + '</td>'
                        + '<td>' + removeCell + '</td></tr>';
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">No parts linked to this tooling.</td></tr>';
            }
        }

        const LABEL_CSRF = <?= json_encode($csrf) ?>;
        let toolingLabelSymbology = <?= json_encode($tooling_symbology) ?>;

        function toolingSymbologyLabel(v) {
            if (v === 'datamatrix') return 'DataMatrix';
            if (v === 'qrcode') return 'QR Code';
            return 'Code 128';
        }

        function generateBarcodeNow() {
            var code = document.getElementById('f_tooling_code').value.trim();
            if (!code) {
                var cat = document.getElementById('f_category').value || 'Other';
                var map = {Die:'DIE',Mold:'MLD',Fixture:'FIX',Jig:'JIG',Gauge:'GAU','Hand Tool':'HND','Cutting Tool':'CUT',Other:'GEN'};
                code = 'TL-' + (map[cat] || 'GEN') + '-TMP';
            }
            var safe = code.replace(/[^A-Za-z0-9\-]/g, '');
            document.getElementById('f_barcode').value = 'BC-' + safe;
        }

        /** Persist tooling label code type (same API as equipment label settings). */
        async function saveToolingLabelSymbology(v) {
            v = (v || 'code128').toString();
            if (['code128', 'qrcode', 'datamatrix'].indexOf(v) === -1) v = 'code128';
            toolingLabelSymbology = v;
            var sel = document.getElementById('labelSymbology');
            if (sel) sel.value = v;
            try {
                var resp = await fetch('/_eam/equipment_labels.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'save_label_settings',
                        csrf: LABEL_CSRF,
                        settings: { tooling_label_symbology: v }
                    })
                });
                var res = await resp.json();
                if (res.status === 'success') {
                    var msg = 'Code type saved — labels will use ' + toolingSymbologyLabel(v) + '.';
                    if (typeof showToast === 'function') showToast(msg, 'success');
                    else if (typeof openWccAlert === 'function') openWccAlert('Saved', msg);
                    else alert(msg);
                    return true;
                }
                var errMsg = res.message || 'Could not save code type.';
                if (typeof showToast === 'function') showToast(errMsg, 'error');
                else alert(errMsg);
                return false;
            } catch (e) {
                if (typeof showToast === 'function') showToast('Could not save label settings.', 'error');
                else alert('Could not save label settings.');
                return false;
            }
        }

        function openBarcodePreview() {
            var val = (document.getElementById('f_barcode').value || document.getElementById('f_tooling_code').value || '').trim();
            var canvas = document.getElementById('barcodeCanvas');
            var err = document.getElementById('barcodeError');
            var payload = document.getElementById('barcodePayload');
            var title = document.getElementById('barcodeModalTitle');
            var sel = document.getElementById('labelSymbology');
            var symb = (sel && sel.value) ? sel.value : toolingLabelSymbology;
            if (['code128', 'qrcode', 'datamatrix'].indexOf(symb) === -1) symb = 'code128';

            if (!val) {
                err.style.display = 'block';
                err.innerText = 'Enter a barcode (or leave blank to auto-generate on save).';
                document.getElementById('barcodeModal').style.display = 'block';
                return;
            }
            // Clear previous drawing so a failed render does not leave Code 128 on screen
            try {
                var ctx = canvas.getContext('2d');
                if (ctx) { ctx.clearRect(0, 0, canvas.width || 1, canvas.height || 1); }
            } catch (e0) {}

            payload.innerText = val;
            err.style.display = 'none';
            title.innerText = toolingSymbologyLabel(symb) + ' Preview';
            document.getElementById('barcodeModal').style.display = 'block';

            if (typeof bwipjs === 'undefined') {
                err.style.display = 'block';
                err.innerText = 'Barcode library (bwip-js) not loaded.';
                return;
            }
            try {
                var opts = { bcid: symb, text: val, scale: 3, includetext: symb === 'code128' };
                if (symb === 'code128') { opts.height = 12; opts.textxalign = 'center'; opts.scale = 2; }
                else { opts.includetext = false; }
                bwipjs.toCanvas(canvas, opts);
            } catch (e) {
                err.innerText = 'Error generating code: ' + e;
                err.style.display = 'block';
            }
        }

        function previewRowBarcode(payload) {
            // Preview uses saved/global symbology — do not clobber the edit form barcode
            // unless the modal form field is empty / matching.
            var f = document.getElementById('f_barcode');
            if (f) f.value = payload || f.value || '';
            var sel = document.getElementById('labelSymbology');
            if (sel) sel.value = toolingLabelSymbology;
            openBarcodePreview();
        }

        // ---- Tooling documents (list + upload) ----
        let docsToolingId = 0;

        function toolingDocIcon(type) {
            if (type === 'SOP') return '🛡️';
            if (type === 'Manual') return '📘';
            if (type === 'Diagram' || type === 'Drawing') return '📐';
            if (type === 'Calibration') return '📏';
            return '📄';
        }

        async function loadToolingDocsList() {
            var box = document.getElementById('toolingDocsList');
            if (!box || !docsToolingId) return;
            box.innerHTML = '<div style="text-align:center; padding:16px; color:var(--text-secondary);">Loading...</div>';
            try {
                var resp = await fetch('/api/get_tooling_docs.php?tooling_id=' + docsToolingId);
                var result = await resp.json();
                var esc = (typeof escapeHtml === 'function') ? escapeHtml : function (s) { return String(s ?? ''); };
                if (result.status !== 'success') {
                    box.innerHTML = '<div style="text-align:center; color:#ef4444; padding:16px;">' + esc(result.message || 'Error') + '</div>';
                    return;
                }
                if (!result.data || !result.data.length) {
                    box.innerHTML = '<div style="text-align:center; padding:16px; color:var(--text-secondary);">No documents linked to this tooling yet.</div>';
                    return;
                }
                var html = '';
                result.data.forEach(function (d) {
                    var safePath = String(d.file_path || '').replace(/[^a-zA-Z0-9_\-.\/]/g, '');
                    html += '<a href="/_doc/' + esc(safePath) + '" target="_blank" rel="noopener" style="display:flex; align-items:center; padding:12px; margin-bottom:8px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:8px; text-decoration:none; color:inherit;">'
                        + '<div style="font-size:1.6em; margin-right:12px;">' + toolingDocIcon(d.doc_type) + '</div>'
                        + '<div style="flex:1; min-width:0;">'
                        + '<div style="font-weight:bold; margin-bottom:4px;">' + esc(d.doc_title) + '</div>'
                        + '<div style="font-size:0.85em; color:var(--text-secondary);">Type: ' + esc(d.doc_type) + ' · by ' + esc(d.uploaded_by) + '</div>'
                        + '</div><div style="color:var(--text-accent);">Open ↗</div></a>';
                });
                box.innerHTML = html;
            } catch (e) {
                box.innerHTML = '<div style="text-align:center; color:#ef4444; padding:16px;">Error loading documents.</div>';
            }
        }

        function openToolingDocsModal(toolingId, toolingName) {
            docsToolingId = toolingId;
            document.getElementById('toolingDocsTitle').innerText = 'Documents: ' + toolingName;
            document.getElementById('doc_tooling_id').value = toolingId;
            document.getElementById('toolingDocUploadForm').reset();
            document.getElementById('doc_tooling_id').value = toolingId;
            document.getElementById('toolingDocsModal').style.display = 'block';
            loadToolingDocsList();
        }

        async function submitToolingDocUpload() {
            var form = document.getElementById('toolingDocUploadForm');
            if (!form.reportValidity()) return;
            var formData = new FormData(form);
            formData.set('entity', 'tooling');
            formData.set('tooling_id', String(docsToolingId));
            formData.set('csrf', LABEL_CSRF);
            var btn = document.getElementById('toolingDocUploadBtn');
            btn.disabled = true;
            btn.innerText = 'Uploading...';
            try {
                var resp = await fetch('/api/upload_document.php', {
                    method: 'POST',
                    headers: LABEL_CSRF ? { 'X-CSRF-Token': LABEL_CSRF } : {},
                    body: formData
                });
                var res = await resp.json();
                if (res.status === 'success') {
                    if (typeof showToast === 'function') showToast(res.message, 'success');
                    else alert(res.message);
                    form.reset();
                    document.getElementById('doc_tooling_id').value = docsToolingId;
                    await loadToolingDocsList();
                } else {
                    if (typeof showToast === 'function') showToast(res.message || 'Upload failed', 'error');
                    else alert(res.message || 'Upload failed');
                }
            } catch (e) {
                if (typeof showToast === 'function') showToast('Upload failed: ' + e.message, 'error');
                else alert('Upload failed: ' + e.message);
            }
            btn.disabled = false;
            btn.innerText = '⬆️ Upload & Link Document';
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
        <div class="page-header">
            <h2>🔧 <?= __e('tooling.vault_title') ?></h2>
            <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap; justify-content:flex-end;">
                <?php if (can('manage_settings')): ?>
                <a href="/_mgmt/admin_panel.php" class="nav-btn" style="white-space:nowrap;">← Return to Admin Panel</a>
                <?php endif; ?>
                <a href="/_eam/toolings.php" class="nav-btn" style="white-space:nowrap;">📋 Ledger</a>
                <div id="searchWrapper">
                    <div class="search-container" id="searchContainerOrig" style="display:inline-block; position:relative; width:100%;" draggable="true" ondragstart="dragSearch(event)">
                        <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); pointer-events:none; font-size:1.1em;">🔍</span>
                        <input type="text" id="ledgerSearch" style="padding:8px 35px 8px 35px; border-radius:20px; border: 1px solid var(--text-accent); background:var(--input-bg); color:var(--text-primary); transition: all 0.3s; box-sizing: border-box;" onkeyup="handleSearchInput(event)" ondblclick="resetSearchPosition()" placeholder="<?= __e('search.placeholder_vault') ?>">
                        <span id="lockTokenBtn" onclick="lockToken()" style="display:none; position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:1.1em; transition: transform 0.2s;" title="Lock Token" onmouseover="this.style.transform='translateY(-50%) scale(1.2)'" onmouseout="this.style.transform='translateY(-50%) scale(1)'">🔒</span>
                    </div>
                </div>
                <button type="button" class="pill-btn pill-warning" style="white-space:nowrap;" onclick="document.getElementById('codeConfigModal').style.display='block'">⚙️ Code Configurator</button>
                <?php if ($show_retired): ?>
                <a href="/_eam/setup_vault_toolings.php" class="pill-btn" style="white-space:nowrap; text-decoration:none;">Hide retired</a>
                <?php else: ?>
                <a href="/_eam/setup_vault_toolings.php?show_retired=1" class="pill-btn" style="white-space:nowrap; text-decoration:none;">Show retired</a>
                <?php endif; ?>
                <button type="button" class="pill-btn pill-success" style="white-space:nowrap;" onclick="openAddModal()">+ Add Tooling</button>
            </div>
        </div>
        <div id="activeFiltersArea" style="display:flex; gap:8px; margin-bottom:15px; flex-wrap:wrap; min-height:30px; align-items:center;">
            <span id="searchMatchCount" class="search-match-count" aria-live="polite"></span>
        </div>
        <table class="data-table" id="vaultTable">
            <thead>
                <tr>
                    <!-- Same column pattern as setup_vault_equipment.php (Code≈UUID, Name, Category, Status≈Criticality, Active, Actions) -->
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Code</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Name</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Category</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Status</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $i):
                $cid = (int)$i['tooling_id'];
                $retired = !empty($i['deleted_at']);
                $st = $i['status'] ?? 'Available';
                $badge = $statusClass[$st] ?? 'badge-normal';
                $bomCount = isset($boms_by_tool[$cid]) ? count($boms_by_tool[$cid]) : 0;
                $jsonData = htmlspecialchars(json_encode($i), ENT_QUOTES, 'UTF-8');

                $cal = $i['calibration_due'] ?? null;
                $calClass = '';
                $calPill = 'status-closed';
                $calLabel = !empty($cal) ? $cal : 'N/A';
                if ($cal) {
                    $ts = strtotime($cal . ' 23:59:59');
                    if ($ts !== false && $ts < time()) {
                        $calClass = 'cal-overdue';
                        $calPill = 'status-open'; // red candy
                        $calLabel = $cal . ' ⚠';
                        // Surface overdue on status badge when still "Available"
                        if ($st === 'Available') {
                            $st = 'Calibration Due';
                            $badge = $statusClass[$st] ?? 'badge-critical';
                        }
                    } elseif ($ts !== false && $ts < strtotime('+60 days')) {
                        $calClass = 'cal-soon';
                        $calPill = 'status-pending';
                    }
                }
            ?>
            <tr class="parent-row<?= $retired ? ' retired-row' : '' ?>" data-id="<?= $cid ?>">
                <td style="font-family: monospace; font-size: 0.8em; color: var(--text-secondary);">
                    <span class="row-arrow">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </span>
                    <?= htmlspecialchars($i['tooling_code'] ?? '') ?>
                </td>
                <td style="font-weight:bold; color: var(--text-accent);"><?= htmlspecialchars($i['tooling_name'] ?? '') ?></td>
                <td><?= htmlspecialchars(!empty($i['category']) ? $i['category'] : 'N/A') ?></td>
                <td data-search="|<?= htmlspecialchars(strtoupper($st)) ?>|<?= $retired ? 'RETIRED|' : '' ?>">
                    <span class="<?= $badge ?> prio-badge"><?= htmlspecialchars($st) ?><?= $retired ? ' · retired' : '' ?></span>
                </td>
                <td data-search="<?= !empty($i['is_active']) ? '|YES|Y|ACTIVE|ONLINE|1|' : '|NO|N|INACTIVE|OFFLINE|0|' ?>">
                    <?= !empty($i['is_active']) ? '🟢 Yes' : '🔴 No' ?>
                </td>
                <td>
                    <?php if (!$retired): ?>
                    <button type="button" onclick="openBOMModal(<?= $cid ?>, '<?= addslashes(htmlspecialchars($i['tooling_name'] ?? '')) ?>')" class="pill-btn pill-info pill-sm" style="margin-right: 5px;">Manage BOM</button>
                    <button type="button" onclick="openToolingDocsModal(<?= $cid ?>, '<?= addslashes(htmlspecialchars($i['tooling_name'] ?? '')) ?>')" class="pill-btn pill-info pill-sm" style="margin-right: 5px;">Docs</button>
                    <button type="button" onclick="openEditModal(<?= $jsonData ?>)" class="pill-btn pill-warning pill-sm" style="margin-right: 5px;">Configure</button>
                    <button type="button" onclick="event.stopPropagation(); previewRowBarcode(<?= htmlspecialchars(json_encode($i['barcode'] ?: $i['tooling_code']), ENT_QUOTES) ?>);" class="pill-btn pill-info pill-sm" title="Preview barcode" style="margin-right: 5px;">🏷️</button>
                    <a href="#" onclick="openWccConfirm('Retire this tooling? It will be soft-deleted from the ledger.', 'setup_vault_toolings.php?retire_id=<?= $cid ?>&csrf=<?= wcc_csrf_token() ?>', 'Retire Tooling'); return false;" class="pill-btn pill-danger pill-sm">Retire</a>
                    <?php else: ?>
                    <a href="setup_vault_toolings.php?restore_id=<?= $cid ?>&csrf=<?= urlencode($csrf) ?>" class="pill-btn pill-success pill-sm">Restore</a>
                    <?php endif; ?>
                </td>
            </tr>
            <tr class="child-row" id="acc-<?= $cid ?>">
                <td colspan="12">
                    <div class="child-content" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                        <div class="layer-panel">
                            <h4>Identity & Context</h4>
                            <div class="data-pair"><strong>Type:</strong> <span><?= htmlspecialchars(!empty($i['tooling_type']) ? $i['tooling_type'] : 'N/A') ?></span></div>
                            <div class="data-pair"><strong>Barcode:</strong> <span><?= htmlspecialchars(!empty($i['barcode']) ? $i['barcode'] : 'N/A') ?></span></div>
                            <div class="data-pair"><strong>Asset Tag:</strong> <span><?= htmlspecialchars(!empty($i['asset_tag']) ? $i['asset_tag'] : 'N/A') ?></span></div>
                            <div class="data-pair"><strong>OEM Brand:</strong> <span><?= htmlspecialchars(!empty($i['oem_brand']) ? $i['oem_brand'] : 'N/A') ?></span></div>
                            <div class="data-pair"><strong>OEM Model:</strong> <span><?= htmlspecialchars(!empty($i['oem_model']) ? $i['oem_model'] : 'N/A') ?></span></div>
                            <div class="data-pair"><strong>Serial:</strong> <span><?= htmlspecialchars(!empty($i['serial_number']) ? $i['serial_number'] : 'N/A') ?></span></div>
                        </div>
                        <div class="layer-panel">
                            <h4>Allocation</h4>
                            <div class="data-pair"><strong>Machine:</strong> <span><?= htmlspecialchars(!empty($i['linked_equip_name']) ? $i['linked_equip_name'] : 'Unallocated') ?></span></div>
                            <div class="data-pair"><strong>Location:</strong> <span><?= htmlspecialchars(!empty($i['location']) ? $i['location'] : 'N/A') ?></span></div>
                            <div class="data-pair"><strong>Owner Dept:</strong> <span><?= htmlspecialchars(!empty($i['owner_dept']) ? $i['owner_dept'] : 'N/A') ?></span></div>
                            <div class="data-pair"><strong>Condition:</strong> <span><?= htmlspecialchars(!empty($i['condition_rating']) ? $i['condition_rating'] : 'N/A') ?></span></div>
                            <div class="data-pair"><strong>Parts on Tool:</strong> <span><?= $bomCount ?> SKU(s)</span></div>
                            <div class="data-pair"><strong>Documents:</strong> <span><a href="#" style="color:var(--text-accent);" onclick="event.stopPropagation(); openToolingDocsModal(<?= $cid ?>, '<?= addslashes(htmlspecialchars($i['tooling_name'] ?? '')) ?>'); return false;">Manage docs</a></span></div>
                        </div>
                        <div class="layer-panel">
                            <h4>Metrology</h4>
                            <div class="data-pair"><strong>Last Cal:</strong> <span><?= htmlspecialchars(!empty($i['last_calibration']) ? $i['last_calibration'] : 'N/A') ?></span></div>
                            <div class="data-pair"><strong>Cal Due:</strong> <span class="cal-pill <?= $calPill ?> <?= $calClass ?>" style="padding:2px 8px; border-radius:10px; font-size:0.85em;"><?= htmlspecialchars($calLabel) ?></span></div>
                            <div class="data-pair"><strong>Purchase:</strong> <span><?= htmlspecialchars(!empty($i['purchase_date']) ? $i['purchase_date'] : 'N/A') ?></span></div>
                            <div class="data-pair"><strong>Notes:</strong> <span style="font-family:inherit;"><?= htmlspecialchars(!empty($i['notes']) ? $i['notes'] : 'N/A') ?></span></div>
                        </div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($items)): ?>
            <tr><td colspan="6" style="text-align:center; padding:24px; color:var(--text-secondary);">No tooling yet — click + Add Tooling.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Configuration Modal (same pattern as equipment vault) -->
    <div id="addModal" class="modal">
      <div class="modal-content vault-modal-content" style="padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.5);">
        <span class="close" onclick="document.getElementById('addModal').style.display='none'">&times;</span>
        <h2 id="modalTitle">Register Tooling</h2>
        <form method="POST" id="toolingForm">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="tooling_id" id="f_tooling_id">

            <div class="vault-grid">
                <div class="layer-box">
                    <h4>Layer 1: Identity & Context</h4>
                    <label>Tooling Code (Leave blank to auto-generate from Code Rules)</label>
                    <input type="text" name="tooling_code" id="f_tooling_code" placeholder="e.g. TL-DIE-001 — blank uses category rule">
                    <label>Tooling Name *</label>
                    <input type="text" name="tooling_name" id="f_tooling_name" required>
                    <label>Category</label>
                    <select name="category" id="f_category">
                        <option value="">—</option>
                        <?php foreach ($CATEGORIES as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Type / Subtype</label>
                    <input type="text" name="tooling_type" id="f_tooling_type">
                    <label>OEM Brand</label>
                    <input type="text" name="oem_brand" id="f_oem_brand">
                    <label>OEM Model</label>
                    <input type="text" name="oem_model" id="f_oem_model">
                    <label>Serial Number</label>
                    <input type="text" name="serial_number" id="f_serial">
                </div>

                <div class="layer-box">
                    <h4>Layer 2: Allocation to Machine</h4>
                    <label>Allocated Equipment</label>
                    <select name="linked_equip_id" id="f_linked_equip">
                        <option value="">— Unallocated —</option>
                        <?php foreach ($equipment as $eq): ?>
                        <option value="<?= (int)$eq['equip_id'] ?>"><?= htmlspecialchars($eq['equip_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Location / Crib</label>
                    <input type="text" name="location" id="f_location">
                    <label>Workshop</label>
                    <select name="workshop_id" id="f_workshop">
                        <option value="">— Unassigned —</option>
                        <?php foreach ($workshops as $w): ?>
                        <option value="<?= (int)$w['workshop_id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Production Line</label>
                    <select name="line_id" id="f_line">
                        <option value="">— Unassigned —</option>
                        <?php foreach ($production_lines as $l): ?>
                        <option value="<?= (int)$l['line_id'] ?>"><?= htmlspecialchars($l['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Owner Department</label>
                    <input type="text" name="owner_dept" id="f_owner">
                    <label>Status</label>
                    <select name="status" id="f_status">
                        <?php foreach ($STATUSES as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Condition</label>
                    <select name="condition_rating" id="f_condition">
                        <?php foreach ($CONDITIONS as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div style="margin-top: 15px;">
                        <label style="display:inline-flex; align-items:center; cursor:pointer;">
                            <input type="checkbox" name="is_active" id="f_is_active" value="1" style="width:20px !important; margin:0 10px 0 0 !important; display:inline-block !important;" checked>
                            <span>Active / Issueable</span>
                        </label>
                    </div>
                </div>

                <div class="layer-box">
                    <h4>Layer 3: Barcode & Metrology</h4>
                    <label>Barcode (Leave blank to auto-generate)</label>
                    <input type="text" name="barcode" id="f_barcode" placeholder="e.g. BC-TL-DIE-001">
                    <div style="display:flex; gap:8px; align-items:center; margin: 8px 0 12px; flex-wrap:wrap;">
                        <button type="button" class="pill-btn pill-info pill-sm" style="width:auto; margin:0;" onclick="generateBarcodeNow()">Generate Barcode</button>
                        <label style="display:flex; align-items:center; gap: 8px; margin:0; font-weight:normal;">
                            Label Generator
                            <select id="labelSymbology" title="Code type for tooling labels (saved preference)" style="margin-left:auto; width:auto; padding:4px 8px;"
                                    onchange="saveToolingLabelSymbology(this.value)">
                                <option value="code128" <?= $tooling_symbology === 'code128' ? 'selected' : '' ?>>Code 128</option>
                                <option value="qrcode" <?= $tooling_symbology === 'qrcode' ? 'selected' : '' ?>>QR Code</option>
                                <option value="datamatrix" <?= $tooling_symbology === 'datamatrix' ? 'selected' : '' ?>>DataMatrix</option>
                            </select>
                            <span style="cursor: pointer; font-size: 1.2em;" title="Preview the label code" onclick="openBarcodePreview()">🔲</span>
                        </label>
                    </div>
                    <label>Asset Tag</label>
                    <input type="text" name="asset_tag" id="f_asset_tag" placeholder="Auto from code if blank">
                    <label>Last Calibration</label>
                    <input type="date" name="last_calibration" id="f_last_cal">
                    <label>Calibration Due</label>
                    <input type="date" name="calibration_due" id="f_cal_due">
                    <label>Purchase Date</label>
                    <input type="date" name="purchase_date" id="f_purchase">
                    <label>Cost</label>
                    <input type="number" step="0.01" name="cost" id="f_cost">
                    <label>Notes</label>
                    <textarea name="notes" id="f_notes" rows="3"></textarea>
                    <p style="color:var(--text-secondary); font-size:0.85em; margin-top:12px;">
                        After save: use <strong>Manage BOM</strong> on the row to allocate inventory parts to this tool (tool BOM — separate from machine BOM).
                    </p>
                </div>
            </div>
            <button type="submit" class="pill-btn pill-success pill-block">💾 Save Tooling Configuration</button>
        </form>
      </div>
    </div>

    <!-- BOM Modal — parts on the TOOL itself -->
    <div id="bomModal" class="modal">
      <div class="modal-content vault-modal-content" style="padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); max-width: 700px;">
        <span class="close" onclick="document.getElementById('bomModal').style.display='none'">&times;</span>
        <h2 id="bomModalTitle">Manage Tool BOM</h2>
        <p style="color:var(--text-secondary); font-size:0.9em;">Parts / inserts belonging to this <strong>tooling</strong> (not the machine BOM).</p>

        <table class="data-table" style="margin-bottom:20px;">
            <thead>
                <tr>
                    <th>Part SKU</th>
                    <th>Part Name</th>
                    <th>Quantity</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="bomTableBody"></tbody>
        </table>

        <h4 style="color:var(--text-accent); margin-top:30px; border-top:1px solid var(--panel-border); padding-top:15px;">Add Part to Tool BOM</h4>
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="add_bom">
            <input type="hidden" name="bom_tooling_id" id="bom_tooling_id">

            <div style="display:grid; grid-template-columns: 2fr 1fr; gap:15px; align-items:end;">
                <div>
                    <label>Select Part</label>
                    <select name="part_id" required style="width:100%;">
                        <?php foreach ($all_parts as $p): ?>
                            <option value="<?= (int)$p['part_id'] ?>"><?= htmlspecialchars($p['part_name']) ?> (<?= htmlspecialchars($p['internal_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Quantity per Tool</label>
                    <input type="number" name="quantity" value="1" min="1" required style="width:100%;">
                </div>
            </div>
            <button type="submit" class="pill-btn pill-success pill-block" style="margin-top:20px;">+ Add Part to Tool BOM</button>
        </form>
      </div>
    </div>

    <!-- Tooling Documents Modal (list + upload) -->
    <div id="toolingDocsModal" class="modal">
      <div class="modal-content vault-modal-content" style="padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); max-width: 720px;">
        <span class="close" onclick="document.getElementById('toolingDocsModal').style.display='none'">&times;</span>
        <h2 id="toolingDocsTitle">Documents</h2>
        <p style="color:var(--text-secondary); font-size:0.9em;">SOPs, manuals, drawings, and calibration certs for this <strong>tool</strong> (stored under <code>/_doc/tooling/</code>).</p>

        <h4 style="color:var(--text-accent); margin:16px 0 10px;">Linked documents</h4>
        <div id="toolingDocsList" style="max-height:280px; overflow-y:auto; margin-bottom:20px;">
            <div style="text-align:center; padding:16px; color:var(--text-secondary);">Loading...</div>
        </div>

        <h4 style="color:var(--text-accent); margin:20px 0 12px; border-top:1px solid var(--panel-border); padding-top:16px;">Upload document</h4>
        <form id="toolingDocUploadForm" enctype="multipart/form-data" onsubmit="event.preventDefault(); submitToolingDocUpload();">
            <input type="hidden" name="tooling_id" id="doc_tooling_id" value="">
            <input type="hidden" name="entity" value="tooling">
            <div style="margin-bottom:12px;">
                <label>Document Title *</label>
                <input type="text" name="doc_title" required placeholder="e.g. Die setup SOP v2" style="width:100%;">
            </div>
            <div style="margin-bottom:12px;">
                <label>Document Type *</label>
                <select name="doc_type" required style="width:100%;">
                    <option value="SOP">Safety SOP</option>
                    <option value="Manual">User Manual</option>
                    <option value="Drawing">Drawing</option>
                    <option value="Calibration">Calibration cert</option>
                    <option value="Diagram">Technical Diagram</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div style="margin-bottom:16px;">
                <label>Select File (PDF, DOCX, TXT, PNG, JPG) *</label>
                <input type="file" name="doc_file" required accept=".pdf,.docx,.txt,.png,.jpg,.jpeg" style="width:100%;">
            </div>
            <button type="submit" id="toolingDocUploadBtn" class="pill-btn pill-info pill-block">⬆️ Upload &amp; Link Document</button>
        </form>
      </div>
    </div>

    <!-- Code Configurator Modal (mirrors equipment UUID Configurator) -->
    <div id="codeConfigModal" class="modal">
      <div class="modal-content vault-modal-content" style="padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); max-width: 800px;">
        <span class="close" onclick="document.getElementById('codeConfigModal').style.display='none'">&times;</span>
        <h2>⚙️ Tooling Code Generation Rules</h2>

        <div class="form-section" style="border-top:none; margin-top:0; padding-top:0;">
            <p style="color:var(--text-secondary); margin-bottom: 20px;">Define structural templates for automatically generating tooling codes based on Category (same engine as Equipment UUID rules). Use category <code>GLOBAL_DEFAULT</code> as the catch-all when no category-specific rule matches.</p>

            <form method="POST" style="background: rgba(0,0,0,0.1); padding: 15px; border-radius: 8px; border: 1px solid var(--panel-border);">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="add_code_rule">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; align-items:end;">
                    <div>
                        <label>Category (Target)</label>
                        <input type="text" name="rule_category" list="toolingCatList" placeholder="e.g. Die, Fixture, or GLOBAL_DEFAULT" required style="width:100%;">
                        <datalist id="toolingCatList">
                            <?php foreach ($CATEGORIES as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>">
                            <?php endforeach; ?>
                            <option value="GLOBAL_DEFAULT">
                        </datalist>
                    </div>
                    <div>
                        <label>Static Prefix</label>
                        <input type="text" name="rule_prefix" placeholder="e.g. TL-DIE-" style="width:100%;">
                    </div>
                    <div>
                        <label>Auto-Increment Serial Length</label>
                        <input type="number" name="serial_length" value="3" min="1" max="10" required style="width:100%;">
                    </div>
                    <div>
                        <label>Appended Random Chars Length</label>
                        <input type="number" name="random_chars" value="0" min="0" max="10" required style="width:100%;">
                    </div>
                    <div style="grid-column: span 2;">
                        <label>Random Character Set Type</label>
                        <select name="char_type" style="width:100%;">
                            <option value="NUMERIC" selected>Numeric Only (0-9)</option>
                            <option value="ALPHANUMERIC">Alphanumeric (A-Z, 0-9)</option>
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
                        <?php foreach ($code_rules as $r): ?>
                        <tr>
                            <td style="font-weight:bold; color:var(--text-accent);"><?= htmlspecialchars($r['category']) ?></td>
                            <td style="font-family:monospace;"><?= htmlspecialchars($r['prefix']) ?>[{SERIAL:<?= (int)$r['serial_length'] ?>}]<?= ((int)$r['random_chars'] > 0) ? '-{RAND:' . (int)$r['random_chars'] . ':' . htmlspecialchars($r['char_type']) . '}' : '' ?></td>
                            <td><?= (int)$r['current_serial'] ?></td>
                            <td><a href="#" onclick="openWccConfirm('Delete this code rule?', '?delete_rule_id=<?= (int)$r['rule_id'] ?>&csrf=<?= wcc_csrf_token() ?>', 'Delete Rule'); return false;" class="pill-btn pill-danger pill-sm">✕</a></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($code_rules)): ?>
                        <tr><td colspan="4" style="text-align:center;">No custom code rules configured.</td></tr>
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
      </div>
    </div>

<script>
window.addEventListener('click', function(e) {
    if (e.target.classList && e.target.classList.contains('modal')) e.target.style.display = 'none';
});
</script>
</body>
</html>
