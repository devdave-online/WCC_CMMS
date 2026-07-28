<?php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('takeover_tickets');

if (!isset($_GET['id'])) { header("Location: ../index.php"); exit; }
$ticket_id = $_GET['id'];

// Enterprise centralized DB (Phase 1 complete)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    $stmt = $pdo->prepare("SELECT * FROM active_tickets WHERE ticket_id = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$ticket) { die(__('ticket.not_found_die')); }
    
    // Fetch parts for silent searchbox
    $parts_stmt = $pdo->query("SELECT part_id, part_name, internal_code, stock_level FROM inventory_parts WHERE lifecycle_status = 'Active' ORDER BY part_name ASC");
    $all_parts = $parts_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { wcc_user_error("Could not load the ticket for takeover.", $e->getMessage()); }
?>

<?php
$page_title = __('ticket.takeover_title');
require_once __DIR__ . '/../inc/head.php';
include __DIR__ . '/../nav.php';
?>

<div class="form-container">
    <div class="page-header"><h1><?= __e('ticket.takeover_title') ?></h1><a href="../index.php" class="nav-btn">🔙 <?= __e('btn.cancel') ?></a></div>
    <div class="ticket-info">
        <div><span><?= __e('ticket.id_label') ?></span> <?= htmlspecialchars($ticket['ticket_id']) ?></div>
        <div><span><?= __e('ticket.equipment_label') ?></span> <?= htmlspecialchars($ticket['equip_id']) ?></div>
        <div style="margin-top:5px; color:var(--danger); font-weight:600;"><?= __e('ticket.issue_label') ?> <?= htmlspecialchars($ticket['fault_desc']) ?></div>
    </div>
    <input type="hidden" id="ticket_id" value="<?= htmlspecialchars($ticket['ticket_id']) ?>">
    
    <label for="tech_name"><?= __e('ticket.overtook_by') ?></label>
    <input type="text" id="tech_name" value="<?= htmlspecialchars($_SESSION['username'] ?? __('common.unknown')) ?>" readonly>

    <div class="grid-2">
        <div><label for="action_start"><?= __e('ticket.start_time') ?></label><input type="datetime-local" id="action_start" required></div>
        <div><label for="action_end"><?= __e('ticket.end_time') ?></label><input type="datetime-local" id="action_end" required></div>
    </div>

    <div class="grid-2">
        <div>
            <label for="fault_type"><?= __e('ticket.fault_type') ?></label>
            <select id="fault_type" required>
                <option value="">-- <?= __e('common.select') ?> --</option>
                <option value="Mechanical"><?= __e('ticket.fault.mechanical') ?></option>
                <option value="Electrical"><?= __e('ticket.fault.electrical') ?></option>
                <option value="Pneumatic/Hydraulic"><?= __e('ticket.fault.pneumatic') ?></option>
                <option value="Software/Controls"><?= __e('ticket.fault.software') ?></option>
                <option value="Tooling/Fixture"><?= __e('ticket.fault.tooling') ?></option>
                <option value="Operator Error"><?= __e('ticket.fault.operator') ?></option>
                <option value="Other"><?= __e('ticket.fault.other') ?></option>
            </select>
        </div>
        <div>
            <label for="escalated_to"><?= __e('ticket.escalate_to') ?></label>
            <select id="escalated_to">
                <option value="None"><?= __e('common.loading') ?></option>
            </select>
        </div>
    </div>

    <label for="root_cause"><?= __e('ticket.root_cause') ?>:</label>
    <input type="text" id="root_cause" required placeholder="<?= __e('ticket.why_broke') ?>">

    <label for="action_taken"><?= __e('ticket.action_taken') ?>:</label>
    <textarea id="action_taken" rows="2" required placeholder="<?= __e('ticket.what_done') ?>"></textarea>

    <label for="parts_used"><?= __e('ticket.parts_used') ?> (<?= __e('common.optional') ?>):</label>
    <input type="text" id="parts_used" list="parts_list" placeholder="<?= __e('ticket.parts_search') ?>">
    <datalist id="parts_list">
        <?php foreach($all_parts as $p): ?>
            <option value="ID: <?= $p['part_id'] ?> | <?= htmlspecialchars($p['part_name']) ?> (<?= htmlspecialchars($p['internal_code']) ?>)"></option>
        <?php endforeach; ?>
    </datalist>

    <div class="btn-group" style="margin-top:20px;">
        <button id="btnEscalate" class="btn-escalate" onclick="submitTakeover('escalate')"><?= __e('ticket.escalate') ?></button>
        <button id="btnFinish" class="btn-finish" onclick="submitTakeover('finish')"><?= __e('ticket.finish_job') ?></button>
    </div>
</div>

<script>
    const now = new Date(); now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('action_start').value = now.toISOString().slice(0,16); document.getElementById('action_end').value = now.toISOString().slice(0,16);

    async function submitTakeover(actionType) {
        const escalatedValue = document.getElementById('escalated_to').value.trim();
        const partsUsedRaw = document.getElementById('parts_used').value;
        
        let consumedParts = [];
        let partsText = partsUsedRaw || 'None';
        
        // Silent Consumption Parsing
        // If they selected from the datalist, it looks like "ID: 5 | Motor (MTR-01)"
        const partMatch = partsUsedRaw.match(/^ID:\s*(\d+)\s*\|/);
        if (partMatch) {
            consumedParts.push({ part_id: partMatch[1], qty: 1 });
        }
        
        const payload = {
            ticket_id: document.getElementById('ticket_id').value,
            tech_name: document.getElementById('tech_name').value,
            action_start: document.getElementById('action_start').value.replace('T', ' ') + ':00',
            action_end: document.getElementById('action_end').value.replace('T', ' ') + ':00',
            fault_type: document.getElementById('fault_type').value,
            root_cause: document.getElementById('root_cause').value,
            action_taken: document.getElementById('action_taken').value,
            parts_used: partsText,
            escalated_to: escalatedValue || 'None',
            action_type: actionType,
            parts_consumed_data: consumedParts
        };

        if(!payload.tech_name || !payload.fault_type || !payload.root_cause || !payload.action_taken) { 
            openWccAlert(typeof t === 'function' ? t('common.validation_error') : 'Validation Error', typeof t === 'function' ? t('ticket.missing_fields') : 'Fill all required fields!'); return; 
        }
        
        if(actionType === 'escalate' && (!escalatedValue || escalatedValue === 'None')) { 
            openWccAlert(typeof t === 'function' ? t('common.validation_error') : 'Validation Error', typeof t === 'function' ? t('ticket.escalate_need_name') : 'Please enter the name of the person you are escalating this to!'); return; 
        }

        // QoL UPDATE: Lock BOTH buttons to prevent double submission
        const btnEscalate = document.getElementById('btnEscalate');
        const btnFinish = document.getElementById('btnFinish');
        btnEscalate.disabled = true;
        btnFinish.disabled = true;
        
        if (actionType === 'escalate') { btnEscalate.innerText = (typeof t === 'function' ? t('ticket.escalating') : 'Escalating… ⏳'); } 
        else { btnFinish.innerText = (typeof t === 'function' ? t('ticket.saving') : 'Saving… ⏳'); }

        try {
            const response = await fetch('/api/submit_takeover.php', { method: 'POST', headers: wccJsonHeaders(), body: JSON.stringify(wccWithCsrf(payload)) });
            const result = await response.json();
            if(result.status === 'success') {
                const msg = result.message || (typeof t === 'function' ? t('ticket.action_logged') : 'Action logged successfully!');
                if (typeof showToast === 'function') showToast(msg, 'success');
                openWccAlert(typeof t === 'function' ? t('common.success') : 'Success', msg, '../index.php');
            } else {
                const errMsg = result.message || (typeof t === 'function' ? t('ticket.could_not_takeover') : 'Could not take over the ticket.');
                if (typeof showToast === 'function') showToast(errMsg, 'error');
                openWccAlert(typeof t === 'function' ? t('common.error') : 'Error', errMsg);
                btnEscalate.disabled = false; btnFinish.disabled = false;
                btnEscalate.innerText = (typeof t === 'function' ? t('ticket.escalate') : '⚠️ Save & Escalate');
                btnFinish.innerText = (typeof t === 'function' ? t('ticket.finish_job') : '✅ Finish Job');
            }
        } catch (error) {
            const errMsg = (typeof t === 'function' ? t('common.error') : 'Error') + ': ' + error.message;
            if (typeof showToast === 'function') showToast(errMsg, 'error');
            openWccAlert(typeof t === 'function' ? t('common.error') : 'Error', errMsg);
            btnEscalate.disabled = false; btnFinish.disabled = false;
            btnEscalate.innerText = (typeof t === 'function' ? t('ticket.escalate') : '⚠️ Save & Escalate');
            btnFinish.innerText = (typeof t === 'function' ? t('ticket.finish_job') : '✅ Finish Job');
        }
    }

    window.onload = async function() {
        await loadTeamMembers('technical', 'escalated_to');
    };

    async function loadTeamMembers(role, elementId) {
        try {
            const response = await fetch('/api/get_team.php?role=' + role);
            const result = await response.json();
            const dropdown = document.getElementById(elementId);
            
            // Set the default top option based on the field
            if (elementId === 'escalated_to') {
                dropdown.innerHTML = '<option value="None">' + (typeof t === 'function' ? t('ticket.no_escalation') : '-- No Escalation --') + '</option>';
            } else {
                dropdown.innerHTML = '<option value="">-- ' + (typeof t === 'function' ? t('common.select') : 'Select') + ' --</option>';
            }
            
            result.data.forEach(m => {
                let opt = document.createElement('option');
                opt.value = opt.textContent = m.full_name;
                dropdown.appendChild(opt);
            });
        } catch (e) { console.error("Team load error", e); }
    }
</script>
</body>
</html>
