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

$page_title = 'Register Intervention';
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
    <div class="page-header"><h1>Log Intervention</h1><a href="index.php" class="nav-btn">🏠 Hub</a></div>

    <div class="grid-2" style="margin-bottom: 15px;">
        <div>
            <label for="filter_workshop">Workshop / Plant Filter:</label>
            <select id="filter_workshop" onchange="updateLineFilter()">
                <option value="">-- All Workshops --</option>
                <?php foreach($workshops as $w): ?>
                    <option value="<?= $w['workshop_id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="filter_line">Production Line Filter:</label>
            <select id="filter_line" onchange="filterEquipment()">
                <option value="">-- All Lines --</option>
                <!-- Filled via JS -->
            </select>
        </div>
    </div>

    <label for="equip_search">Search Equipment (By Name or UUID):</label>
    <div class="searchbox-container">
        <input type="text" id="equip_search" placeholder="Start typing machine name or UUID..." autocomplete="off">
        <input type="hidden" id="equip_id" required>
        <div id="searchbox_dropdown" class="searchbox-dropdown"></div>
    </div>

    <div id="repeat_warning" role="alert"></div>

    <div class="grid-2" style="margin-top: 15px;">
        <div><label for="equip_name">Equipment Name:</label><input type="text" id="equip_name" readonly></div>
        <div><label for="equip_line">Workshop / Line:</label><input type="text" id="equip_line" readonly></div>
    </div>

    <div class="grid-2">
        <div><label for="report_date">Date:</label><input type="date" id="report_date" required></div>
        <div><label for="report_time">Report Time:</label><input type="time" id="report_time" required></div>
    </div>

    <div class="grid-2">
        <div>
            <label for="announced_by">Announced By (Production):</label>
            <input type="text" id="announced_by" value="<?= htmlspecialchars($_SESSION['username'] ?? 'Unknown User') ?>" readonly>
        </div>
        <div>
            <label for="pic">Person In Charge (Technical):</label>
            <select id="pic" required><option value="">Loading...</option></select>
        </div>
    </div>

    <div class="grid-2">
        <div>
            <label for="priority">Priority Level:</label>
            <select id="priority" required>
                <option value="critical">🔴 Critical (Line Down)</option>
                <option value="high">🟠 High (Major Defect)</option>
                <option value="normal" selected>🔵 Normal (Standard)</option>
                <option value="low">🟢 Low (Cosmetic/Minor)</option>
            </select>
        </div>
        <div>
            <label for="event_class">Event Type:</label>
            <?php require_once __DIR__ . '/inc/kpi.php'; ?>
            <select id="event_class" required title="Only 'failure'-type events count toward MTBF; the rest are still logged and timed.">
                <?php foreach (WCC_EVENT_CLASSES as $ck => $clabel): ?>
                <option value="<?= htmlspecialchars($ck) ?>" <?= $ck === 'failure' ? 'selected' : '' ?>><?= htmlspecialchars($clabel) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <label for="fault_desc">Fault Description:</label>
    <textarea id="fault_desc" rows="3" required></textarea>

    <button id="submitBtn" onclick="submitTicket()" class="btn btn-primary btn-block" style="margin-top: 10px;">Submit Ticket</button>
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
        lineSelect.innerHTML = '<option value="">-- All Lines --</option>';

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
            dropdown.innerHTML = '<div class="searchbox-empty">No equipment found.</div>';
            dropdown.style.display = 'block'; return;
        }

        matches.forEach(item => {
            const div = document.createElement('div');
            div.className = 'searchbox-item';
            const eName = (typeof escapeHtml === 'function' ? escapeHtml : (s) => String(s ?? ''))(item.equip_name);
            const eUuid = (typeof escapeHtml === 'function' ? escapeHtml : (s) => String(s ?? ''))(item.asset_uuid || 'N/A');
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
            dropdown.innerHTML = '<option value="">-- Select --</option>';
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
            document.getElementById('equip_line').value = (machine.plant_name || 'N/A') + " / " + (machine.line_name || 'N/A');

            // Check for repeat offenders (2 or more tickets in 48 hours)
            if (parseInt(machine.recent_count) >= 2) {
                warningDiv.style.display = 'block';
                warningDiv.innerText = `⚠️ WARNING: This machine has had ${machine.recent_count} faults in the last 48 hours. Look for a root cause!`;
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
            openWccAlert('Validation Error', "Please search and select an Equipment!"); return;
        }
        if(!payload.fault_desc || !payload.pic) {
            openWccAlert('Validation Error', "Please fill all mandatory fields (Fault Description, PIC)!"); return;
        }

        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerText = "Processing... ⏳";

        try {
            const response = await fetch('/api/submit_ticket.php', { method: 'POST', headers: wccJsonHeaders(), body: JSON.stringify(wccWithCsrf(payload)) });
            const result = await response.json();
            if(result.status === 'success') {
                openWccAlert('Success', "Ticket Submitted Successfully!", 'index.php');
            } else { openWccAlert('Error', result.message); btn.disabled = false; btn.innerText = "Submit Ticket"; }
        } catch (e) { openWccAlert('Error', "Error: " + e.message); btn.disabled = false; btn.innerText = "Submit Ticket"; }
    }
</script>
</body>
</html>
