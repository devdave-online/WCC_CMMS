<?php
include 'auth.php';
require_once 'rbac.php';
require_perm('create_tickets');

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/inc/db.php';
$pdo = get_wcc_db_connection();

try {
    $workshops = $pdo->query("SELECT * FROM workshops ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $lines = $pdo->query("SELECT * FROM production_lines ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { wcc_user_error("Could not load equipment data for registration.", $e->getMessage()); }

$page_title = __('ticket.register_title');
require_once __DIR__ . '/inc/head.php';
?>
<style>
    .searchbox-container { position: relative; width: 100%; }
    .searchbox-dropdown {
        position: absolute; top: 100%; left: 0; right: 0;
        background: var(--modal-bg, var(--panel-bg)); border: 1px solid var(--panel-border);
        border-radius: var(--radius-sm); max-height: 250px; overflow-y: auto;
        z-index: 1000; display: none; box-shadow: var(--shadow-2);
    }
    .searchbox-item {
        padding: 10px; cursor: pointer; border-bottom: 1px solid var(--panel-border);
        display: flex; flex-direction: column; gap: 4px;
    }
    .searchbox-item:hover { background: var(--table-row-hover); }
    .item-name { font-weight: bold; color: var(--text-accent); }
    .item-uuid { font-family: monospace; font-size: var(--fs-xs); color: var(--text-secondary); }
    .searchbox-empty { padding: 10px; color: var(--text-muted); }
    #repeat_warning { display: none; color: var(--warning); font-weight: bold; margin-top: 8px; font-size: var(--fs-sm); }
</style>
<?php include 'nav.php'; ?>

<div class="form-container">
    <div class="page-header"><h1><?= __e('ticket.register_title') ?></h1><a href="index.php" class="nav-btn">🏠 <?= __e('common.hub') ?></a></div>

    <div class="grid-2" style="margin-bottom: 15px;">
        <div>
            <label for="filter_workshop"><?= __e('ticket.filter_workshop') ?></label>
            <select id="filter_workshop" onchange="updateLineFilter()">
                <option value=""><?= __e('ticket.all_workshops') ?></option>
                <?php foreach($workshops as $w): ?>
                    <option value="<?= $w['workshop_id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="filter_line"><?= __e('ticket.filter_line') ?></label>
            <select id="filter_line" onchange="filterEquipment()">
                <option value=""><?= __e('ticket.all_lines') ?></option>
                <!-- Filled via JS -->
            </select>
        </div>
    </div>

    <label for="equip_search"><?= __e('ticket.search_equip_label') ?></label>
    <div class="searchbox-container">
        <input type="text" id="equip_search" placeholder="<?= __e('ticket.equip_search') ?>" autocomplete="off">
        <input type="hidden" id="equip_id" required>
        <div id="searchbox_dropdown" class="searchbox-dropdown"></div>
    </div>

    <div id="repeat_warning" role="alert"></div>

    <div class="grid-2" style="margin-top: 15px;">
        <div><label for="equip_name"><?= __e('ticket.equip_name_label') ?></label><input type="text" id="equip_name" readonly></div>
        <div><label for="equip_line"><?= __e('ticket.workshop_line_label') ?></label><input type="text" id="equip_line" readonly></div>
    </div>

    <div class="grid-2">
        <div><label for="report_date"><?= __e('ticket.report_date') ?>:</label><input type="date" id="report_date" required></div>
        <div><label for="report_time"><?= __e('ticket.report_time') ?>:</label><input type="time" id="report_time" required></div>
    </div>

    <div class="grid-2">
        <div>
            <label for="announced_by"><?= __e('ticket.announced_by') ?>:</label>
            <input type="text" id="announced_by" value="<?= htmlspecialchars($_SESSION['username'] ?? __('common.unknown')) ?>" readonly>
        </div>
        <div>
            <label for="pic"><?= __e('ticket.pic') ?>:</label>
            <select id="pic" required><option value=""><?= __e('common.loading') ?></option></select>
        </div>
    </div>

    <div class="grid-2">
        <div>
            <label for="priority"><?= __e('common.priority') ?>:</label>
            <select id="priority" required>
                <option value="critical">🔴 <?= __e('ticket.priority_critical') ?></option>
                <option value="high">🟠 <?= __e('ticket.priority_high') ?></option>
                <option value="normal" selected>🔵 <?= __e('ticket.priority_normal') ?></option>
                <option value="low">🟢 <?= __e('ticket.priority_low') ?></option>
            </select>
        </div>
        <div>
            <label for="event_class"><?= __e('ticket.event_type') ?></label>
            <?php require_once __DIR__ . '/inc/kpi.php'; ?>
            <select id="event_class" required title="<?= __e('ticket.event_type_hint') ?>">
                <?php foreach (WCC_EVENT_CLASSES as $ck => $clabel): ?>
                <option value="<?= htmlspecialchars($ck) ?>" <?= $ck === 'failure' ? 'selected' : '' ?>><?= __e('ticket.event_class.' . $ck) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <label for="fault_desc"><?= __e('ticket.fault_desc') ?>:</label>
    <textarea id="fault_desc" rows="3" required></textarea>

    <button id="submitBtn" onclick="submitTicket()" class="btn btn-primary btn-block" style="margin-top: 10px;"><?= __e('ticket.submit') ?></button>
</div>

<script>
    let equipmentData = [];
    let filteredEquipment = [];
    let productionLines = <?= json_encode($lines) ?>;

    // Consolidated Load logic
    window.onload = async function() {
        document.getElementById('report_date').valueAsDate = new Date();
        document.getElementById('report_time').value = new Date().toLocaleTimeString('en-US', { hour12: false, hour: "numeric", minute: "numeric" });

        try {
            const resp = await fetch('/api/get_equipment.php');
            const result = await resp.json();
            if(result.status === 'success') {
                equipmentData = result.data;
                filteredEquipment = [...equipmentData];
            }
        } catch (e) { console.error("Equip load error"); }

        await loadTeamMembers('technical', 'pic');

        // Setup Searchbox Listener
        const searchInput = document.getElementById('equip_search');
        searchInput.addEventListener('input', function() {
            renderSearchDropdown(this.value.trim().toLowerCase());
        });

        // Hide dropdown on click outside
        document.addEventListener('click', function(e) {
            if(!e.target.closest('.searchbox-container')) {
                document.getElementById('searchbox_dropdown').style.display = 'none';
            }
        });

        // Focus opens dropdown
        searchInput.addEventListener('focus', function() {
            renderSearchDropdown(this.value.trim().toLowerCase());
        });
    };

    function updateLineFilter() {
        const w_id = document.getElementById('filter_workshop').value;
        const lineSelect = document.getElementById('filter_line');
        const allLines = (typeof t === 'function' ? t('ticket.all_lines') : '-- All Lines --');
        lineSelect.innerHTML = '<option value="">' + allLines + '</option>';

        if (w_id) {
            productionLines.filter(l => String(l.workshop_id) === String(w_id)).forEach(l => {
                lineSelect.innerHTML += `<option value="${l.line_id}">${l.name}</option>`;
            });
        }
        filterEquipment();
    }

    function filterEquipment() {
        const w_id = document.getElementById('filter_workshop').value;
        const l_id = document.getElementById('filter_line').value;

        filteredEquipment = equipmentData.filter(item => {
            if (w_id && String(item.workshop_id) !== String(w_id)) return false;
            if (l_id && String(item.line_id) !== String(l_id)) return false;
            return true;
        });

        // Reset selection if the current item is filtered out
        const currentSelectedId = document.getElementById('equip_id').value;
        if (currentSelectedId) {
            if (!filteredEquipment.find(e => String(e.equip_id) === String(currentSelectedId))) {
                document.getElementById('equip_id').value = '';
                document.getElementById('equip_search').value = '';
                autoFillEquipment();
            }
        }
        renderSearchDropdown(document.getElementById('equip_search').value.trim().toLowerCase());
    }

    function renderSearchDropdown(query) {
        const dropdown = document.getElementById('searchbox_dropdown');
        dropdown.innerHTML = '';

        if (filteredEquipment.length === 0) {
            dropdown.style.display = 'none'; return;
        }

        const matches = filteredEquipment.filter(item => {
            if(!query) return true;
            return (item.equip_name && item.equip_name.toLowerCase().includes(query)) ||
                   (item.asset_uuid && item.asset_uuid.toLowerCase().includes(query));
        });

        if (matches.length === 0) {
            dropdown.innerHTML = '<div class="searchbox-empty">' + (typeof t === 'function' ? t('ticket.no_equipment') : 'No equipment found.') + '</div>';
            dropdown.style.display = 'block'; return;
        }

        matches.forEach(item => {
            const div = document.createElement('div');
            div.className = 'searchbox-item';
            const eName = (typeof escapeHtml === 'function' ? escapeHtml : (s) => String(s ?? ''))(item.equip_name);
            const eUuid = (typeof escapeHtml === 'function' ? escapeHtml : (s) => String(s ?? ''))(item.asset_uuid || (typeof t === 'function' ? t('common.na') : 'N/A'));
            div.innerHTML = `
                <span class="item-name">${eName}</span>
                <span class="item-uuid">UUID: ${eUuid}</span>
            `;
            div.onclick = function() {
                document.getElementById('equip_id').value = item.equip_id;
                document.getElementById('equip_search').value = item.equip_name;
                dropdown.style.display = 'none';
                autoFillEquipment();
            };
            dropdown.appendChild(div);
        });

        dropdown.style.display = 'block';
    }

    async function loadTeamMembers(role, elementId) {
        try {
            const response = await fetch('/api/get_team.php?role=' + role);
            const result = await response.json();
            const dropdown = document.getElementById(elementId);
            dropdown.innerHTML = '<option value="">-- ' + (typeof t === 'function' ? t('common.select') : 'Select') + ' --</option>';
            result.data.forEach(m => {
                let opt = document.createElement('option');
                opt.value = opt.textContent = m.full_name;
                dropdown.appendChild(opt);
            });
        } catch (e) { console.error("Team load error", e); }
    }

    function autoFillEquipment() {
        const selectedId = document.getElementById('equip_id').value;
        const machine = equipmentData.find(item => String(item.equip_id) === String(selectedId));
        const warningDiv = document.getElementById('repeat_warning');

        if (machine) {
            document.getElementById('equip_name').value = machine.equip_name || '';
            const na = (typeof t === 'function' ? t('common.na') : 'N/A');
            document.getElementById('equip_line').value = (machine.plant_name || na) + " / " + (machine.line_name || na);

            // Check for repeat offenders (2 or more tickets in 48 hours)
            if (parseInt(machine.recent_count) >= 2) {
                warningDiv.style.display = 'block';
                const warn = (typeof t === 'function' ? t('ticket.repeat_warning', { count: machine.recent_count }) : `WARNING: This machine has had ${machine.recent_count} faults in the last 48 hours. Look for a root cause!`);
                warningDiv.innerText = '⚠️ ' + warn;
            } else {
                warningDiv.style.display = 'none';
            }
        } else {
            document.getElementById('equip_name').value = "";
            document.getElementById('equip_line').value = "";
            warningDiv.style.display = 'none';
        }
    }

    async function submitTicket() {
        const payload = {
            equip_id: document.getElementById('equip_id').value,
            report_date: document.getElementById('report_date').value,
            report_time: document.getElementById('report_time').value,
            priority: document.getElementById('priority').value,
            event_class: document.getElementById('event_class').value,
            announced_by: document.getElementById('announced_by').value,
            pic: document.getElementById('pic').value,
            fault_desc: document.getElementById('fault_desc').value
        };

        if(!payload.equip_id) {
            openWccAlert(typeof t === 'function' ? t('common.validation_error') : 'Validation Error', typeof t === 'function' ? t('ticket.select_equipment') : 'Please search and select an Equipment!'); return;
        }
        if(!payload.fault_desc || !payload.pic) {
            openWccAlert(typeof t === 'function' ? t('common.validation_error') : 'Validation Error', typeof t === 'function' ? t('ticket.fill_mandatory') : 'Please fill all mandatory fields (Fault Description, PIC)!'); return;
        }

        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerText = (typeof t === 'function' ? t('ticket.processing') : 'Processing… ⏳');

        try {
            const response = await fetch('/api/submit_ticket.php', { method: 'POST', headers: wccJsonHeaders(), body: JSON.stringify(wccWithCsrf(payload)) });
            const result = await response.json();
            if(result.status === 'success') {
                const msg = result.message || (typeof t === 'function' ? t('ticket.submit_success') : 'Ticket Submitted Successfully!');
                if (typeof showToast === 'function') showToast(msg, 'success');
                openWccAlert(typeof t === 'function' ? t('common.success') : 'Success', msg, 'index.php');
            } else {
                const errMsg = result.message || (typeof t === 'function' ? t('ticket.could_not_create') : 'Could not submit ticket.');
                if (typeof showToast === 'function') showToast(errMsg, 'error');
                openWccAlert(typeof t === 'function' ? t('common.error') : 'Error', errMsg);
                btn.disabled = false; btn.innerText = (typeof t === 'function' ? t('ticket.submit') : 'Submit Ticket');
            }
        } catch (e) {
            const errMsg = (typeof t === 'function' ? t('common.error') : 'Error') + ': ' + e.message;
            if (typeof showToast === 'function') showToast(errMsg, 'error');
            openWccAlert(typeof t === 'function' ? t('common.error') : 'Error', errMsg);
            btn.disabled = false; btn.innerText = (typeof t === 'function' ? t('ticket.submit') : 'Submit Ticket');
        }
    }
</script>
</body>
</html>
