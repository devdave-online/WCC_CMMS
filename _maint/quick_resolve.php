<?php 
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('create_tickets');

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    $workshops = $pdo->query("SELECT * FROM workshops ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $lines = $pdo->query("SELECT * FROM production_lines ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { die("DB Error"); }
?>
<?php
$page_title = __('ticket.quick_resolve_title');
require_once __DIR__ . '/../inc/head.php';
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
</style>
<?php include __DIR__ . '/../nav.php'; ?>

<div class="form-container">
    <div class="page-header">
        <h1>⚡ <?= __e('ticket.quick_resolve_title') ?></h1>
        <a href="../index.php" class="nav-btn">🏠 <?= __e('common.hub') ?></a>
    </div>

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

    <div class="grid-2" style="margin-top: 15px;">
        <div><label for="equip_name"><?= __e('ticket.equip_name_label') ?></label><input type="text" id="equip_name" readonly></div>
        <div><label for="equip_line"><?= __e('ticket.plant_line_label') ?></label><input type="text" id="equip_line" readonly></div>
    </div>

    <label for="tech_name"><?= __e('ticket.technician_name') ?></label>
    <input type="text" id="tech_name" value="<?= htmlspecialchars($_SESSION['username'] ?? __('common.unknown')) ?>" readonly>
    <input type="hidden" id="logged_in_user" value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>">

    <label for="action_taken"><?= __e('ticket.fix_desc_label') ?></label>
    <input type="text" id="action_taken" placeholder="<?= __e('ticket.action_placeholder') ?>" required>

    <button id="submitBtn" onclick="submitInstantResolve()" class="btn btn-primary btn-block" style="margin-top: 10px;"><?= __e('ticket.log_close_instantly') ?></button>
</div>

<script>
    let equipmentData = [];
    let filteredEquipment = [];
    let productionLines = <?= json_encode($lines) ?>;

    window.onload = async function() {
        // Load Equipment
        try {
            const resp = await fetch('/api/get_equipment.php');
            const result = await resp.json();
            if(result.status === 'success') {
                equipmentData = result.data;
                filteredEquipment = [...equipmentData];
            }
        } catch (e) { console.error("Equip load error"); }
        
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
        lineSelect.innerHTML = '<option value="">' + (typeof t === 'function' ? t('ticket.all_lines') : '-- All Lines --') + '</option>';
        
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

    function autoFillEquipment() {
        const selectedId = document.getElementById('equip_id').value;
        const machine = equipmentData.find(item => String(item.equip_id) === String(selectedId));
        if (machine) {
            document.getElementById('equip_name').value = machine.equip_name;
            const na = (typeof t === 'function' ? t('common.na') : 'N/A');
            let pName = machine.plant_name || na;
            let lName = machine.line_name || na;
            document.getElementById('equip_line').value = pName + " / " + lName;
        } else {
            document.getElementById('equip_name').value = "";
            document.getElementById('equip_line').value = "";
        }
    }

    async function submitInstantResolve() {
        const payload = {
            equip_id: document.getElementById('equip_id').value,
            tech_name: document.getElementById('tech_name').value,
            action_taken: document.getElementById('action_taken').value
        };

        if(!payload.equip_id || !payload.tech_name || !payload.action_taken) { 
            openWccAlert(typeof t === 'function' ? t('common.validation_error') : 'Validation Error', typeof t === 'function' ? t('ticket.missing_fields') : 'Please fill all fields!'); return; 
        }

        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerText = (typeof t === 'function' ? t('ticket.processing') : 'Processing… ⏳');

        try {
            const response = await fetch('/api/submit_instant_resolve.php', { method: 'POST', headers: wccJsonHeaders(), body: JSON.stringify(wccWithCsrf(payload)) });
            const result = await response.json();
            
            if(result.status === 'success') {
                const msg = result.message || (typeof t === 'function' ? t('ticket.instant_logged') : 'Instant Fix logged successfully!');
                if (typeof showToast === 'function') showToast(msg, 'success');
                openWccAlert(typeof t === 'function' ? t('common.success') : 'Success', msg, '../index.php');
            } else {
                const errMsg = result.message || (typeof t === 'function' ? t('ticket.could_not_resolve') : 'Could not record the quick resolution.');
                if (typeof showToast === 'function') showToast(errMsg, 'error');
                openWccAlert(typeof t === 'function' ? t('common.error') : 'Error', errMsg);
                btn.disabled = false; btn.innerText = (typeof t === 'function' ? t('ticket.log_close_instantly') : 'Log & Close Instantly');
            }
        } catch (e) {
            const errMsg = (typeof t === 'function' ? t('common.error') : 'Error') + ': ' + e.message;
            if (typeof showToast === 'function') showToast(errMsg, 'error');
            openWccAlert(typeof t === 'function' ? t('common.error') : 'Error', errMsg);
            btn.disabled = false; btn.innerText = (typeof t === 'function' ? t('ticket.log_close_instantly') : 'Log & Close Instantly');
        }
    }
</script>

</body>
</html>