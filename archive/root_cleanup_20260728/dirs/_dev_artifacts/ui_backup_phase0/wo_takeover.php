<?php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('manage_work_orders');

// Enterprise centralized DB (Phase 1 complete)
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/audit.php';
$pdo = get_wcc_db_connection();

$wo_id = isset($_GET['wo_id']) ? (int)$_GET['wo_id'] : 0;

try {
    
    // Fetch WO Data
    $stmt = $pdo->prepare("
        SELECT w.*, e.equip_name as equipment_name, u.username as assigned_user 
        FROM work_orders w
        LEFT JOIN equipment e ON w.equipment_id = e.equip_id
        LEFT JOIN users u ON w.assigned_to = u.user_id
        WHERE w.wo_id = ?
    ");
    $stmt->execute([$wo_id]);
    $wo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wo) {
        die("Work Order not found.");
    }

    // Decode Required Parts
    $required_parts_ids = json_decode($wo['parts_list'] ?? '[]', true);
    $required_parts = [];
    if (!empty($required_parts_ids)) {
        $in_clause = implode(',', array_map('intval', $required_parts_ids));
        $parts_stmt = $pdo->query("SELECT part_id, part_name, internal_code FROM inventory_parts WHERE part_id IN ($in_clause)");
        $required_parts = $parts_stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // All inventory parts for dynamic search (allow adding used parts at takeover time)
    // Note: slider min set to 1 (practical for real breakdowns), warning shown if going under min_threshold
    $all_parts = $pdo->query("SELECT part_id, part_name, internal_code, stock_level, minimum_threshold FROM inventory_parts ORDER BY part_name ASC")->fetchAll(PDO::FETCH_ASSOC);

    $all_parts = $pdo->query("SELECT part_id, part_name, internal_code, stock_level, minimum_threshold FROM inventory_parts ORDER BY part_name ASC")->fetchAll(PDO::FETCH_ASSOC);

    // PM Checklists Logic
    $checklist_data = json_decode($wo['checklist_data'] ?? '[]', true);
    $total_expected_mins = 0;
    foreach($checklist_data as $item) {
        $total_expected_mins += (int)($item['expected_time_mins'] ?? 0);
    }

    // Handle Form Submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle Start Work Action
        if (isset($_POST['action']) && $_POST['action'] === 'start_work') {
            $pdo->prepare("UPDATE work_orders SET started_at = NOW(), status = 'In Progress' WHERE wo_id = ?")->execute([$wo_id]);
            header("Location: /_maint/wo_takeover.php?wo_id=" . $wo_id); exit;
        }

        if (in_array($wo['status'], ['Completed', 'Cancelled', 'Missed'])) {
            die("This Work Order is already closed and locked.");
        }
        
        $new_status = $_POST['status'] ?? 'Completed';

        // Checklist Time Validation
        if ($new_status === 'Completed' && !empty($checklist_data) && !empty($wo['started_at'])) {
            $elapsed_stmt = $pdo->prepare("SELECT TIMESTAMPDIFF(SECOND, started_at, NOW()) FROM work_orders WHERE wo_id = ?");
            $elapsed_stmt->execute([$wo_id]);
            $elapsed_secs = (int)$elapsed_stmt->fetchColumn();
            
            $expected_secs = $total_expected_mins * 60;
            if ($elapsed_secs < $expected_secs) {
                // Return them back to the form with a glassmorphic error
                echo "<!DOCTYPE html><html><head><meta name='viewport' content='width=device-width, initial-scale=1.0'><link rel='stylesheet' href='/css/style.css'></head><body style='background: #0f172a; padding: 40px;'>";
                echo "<div style='background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.4); border-radius: 16px; padding: 30px; margin: 40px auto; max-width: 500px; text-align: center; backdrop-filter: blur(16px); box-shadow: 0 20px 40px rgba(0,0,0,0.3);'>";
                echo "<div style='font-size: 3.5em; margin-bottom: 10px;'>⏱️</div>";
                echo "<h2 style='color: #f87171; margin-top: 0; font-family: sans-serif;'>Quality Control Alert</h2>";
                echo "<p style='color: #e2e8f0; font-size: 1.1em; line-height: 1.5; font-family: sans-serif;'>It is physically impossible to complete this checklist so fast. You finished in <strong>".round($elapsed_secs/60)." minutes</strong>, but standard operating procedure requires at least <strong>$total_expected_mins minutes</strong>.</p>";
                echo "<a href='/_maint/wo_takeover.php?wo_id=$wo_id' style='background: #ef4444; color: white; padding: 12px 24px; border-radius: 8px; margin-top: 20px; display: inline-block; text-decoration: none; font-weight: bold; font-family: sans-serif; transition: background 0.2s;' onmouseover='this.style.background=\"#dc2626\"' onmouseout='this.style.background=\"#ef4444\"'>← Return to Work Order</a>";
                echo "</div></body></html>";
                exit;
            }
        }
        
        $notes = $_POST['action_taken'] ?? '';
        $new_status = $_POST['status'] ?? 'Completed';
        $tech_id = $_SESSION['user_id'];
        
        // Handle checklist photos if any (up to 3 per task)
        if (isset($_FILES['checklist_photos']) && $new_status === 'Completed') {
            foreach ($_FILES['checklist_photos']['tmp_name'] as $idx => $tmp_names) {
                if (empty($tmp_names)) continue;
                if (!is_array($tmp_names)) $tmp_names = [$tmp_names];
                $name_array = (array)$_FILES['checklist_photos']['name'][$idx];
                
                $paths = [];
                foreach ($tmp_names as $i => $tmp_name) {
                    if (!empty($tmp_name) && is_uploaded_file($tmp_name)) {
                        $ext = strtolower(pathinfo($name_array[$i], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                            $filename = 'wo_' . $wo_id . '_task_' . $idx . '_' . time() . '_' . $i . '.' . $ext;
                            $dest = __DIR__ . '/../uploads/checklists/' . $filename;
                            if (move_uploaded_file($tmp_name, $dest)) {
                                $paths[] = '/uploads/checklists/' . $filename;
                            }
                        }
                    }
                    if (count($paths) >= 3) break;
                }
                
                if (!empty($paths) && isset($checklist_data[$idx])) {
                    if (!isset($checklist_data[$idx]['photo_paths'])) $checklist_data[$idx]['photo_paths'] = [];
                    $checklist_data[$idx]['photo_paths'] = array_merge($checklist_data[$idx]['photo_paths'], $paths);
                }
            }
        }
        $updated_checklist_json = json_encode($checklist_data);
        
        // Collect used parts: pre-planned checked + newly searched/added with qty
        $used_part_ids = isset($_POST['consume_parts']) ? array_map('intval', $_POST['consume_parts']) : [];
        $used_parts_data = [];
        if (!empty($_POST['used_parts_json'])) {
            $used_parts_data = json_decode($_POST['used_parts_json'], true) ?: [];
            foreach ($used_parts_data as $item) {
                $pid = intval($item['id'] ?? $item);
                if (!in_array($pid, $used_part_ids)) {
                    $used_part_ids[] = $pid;
                }
            }
        }
        
        // Fetch names and codes for nice display in notes (fix for showing only ID like 905)
        $part_display = [];
        if (!empty($used_part_ids)) {
            $in = implode(',', array_map('intval', $used_part_ids));
            $pinfo = $pdo->query("SELECT part_id, part_name, internal_code FROM inventory_parts WHERE part_id IN ($in)");
            foreach ($pinfo as $p) {
                $part_display[$p['part_id']] = $p['part_name'] . ' (' . $p['internal_code'] . ')';
            }
        }
        
        // Build nice note with names + qtys
        if (!empty($used_parts_data)) {
            $used_strs = [];
            foreach ($used_parts_data as $item) {
                $pid = intval($item['id'] ?? 0);
                $qty = intval($item['qty'] ?? 1);
                $name = $part_display[$pid] ?? ('Part#' . $pid);
                $used_strs[] = $name . ' x' . $qty;
            }
            $notes .= "\nParts actually consumed: " . implode(', ', $used_strs);
        } else if (!empty($used_part_ids)) {
            $used_strs = [];
            foreach ($used_part_ids as $pid) {
                $used_strs[] = ($part_display[$pid] ?? ('Part#' . $pid)) . ' x1';
            }
            $notes .= "\nParts actually consumed: " . implode(', ', $used_strs);
        }
        
        if ($new_status === 'Completed') {
            $stmt = $pdo->prepare("UPDATE work_orders SET status = 'Completed', description = CONCAT(IFNULL(description,''), '\n\nTechnician Notes: ', ?), parts_list = ?, checklist_data = ?, completed_date = NOW(), completed_by = ? WHERE wo_id = ?");
            $stmt->execute([$notes, json_encode($used_parts_data), $updated_checklist_json, $tech_id, $wo_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE work_orders SET status = ?, description = CONCAT(IFNULL(description,''), '\n\nTechnician Notes: ', ?), parts_list = ?, checklist_data = ? WHERE wo_id = ?");
            $stmt->execute([$new_status, $notes, json_encode($used_parts_data), $updated_checklist_json, $wo_id]);
        }  // Phase 5 audit logging
        wcc_audit_log(
            'work_order.' . strtolower($new_status),
            'work_orders',
            (string)$wo_id,
            ['status' => $wo['status']],
            ['status' => $new_status, 'completed_by' => $tech_id],
            'WO takeover/close with notes'
        );
        
        // Parts Deduction with proper qty (for searched) and 1 for pre-planned checkboxes
        if ($new_status === 'Completed') {
            // from checkboxes (1 each)
            if (isset($_POST['consume_parts'])) {
                foreach ($_POST['consume_parts'] as $pid) {
                    $pdo->prepare("UPDATE inventory_parts SET stock_level = GREATEST(stock_level - 1, 0) WHERE part_id = ?")->execute([intval($pid)]);
                    wcc_audit_log('inventory.deduct', 'inventory_parts', $pid, null, ['qty' => 1], 'WO ' . $wo_id . ' consumption');
                    $pdo->prepare("INSERT INTO inventory_ledger (part_id, change_qty, reason, reference_type, reference_id, actor_user_id) VALUES (?, ?, 'wo_consume', 'work_orders', ?, ?)")->execute([$pid, -1, $wo_id, $tech_id]);
                }
            }
            // from searched with qty
            if (!empty($used_parts_data)) {
                foreach ($used_parts_data as $item) {
                    $pid = intval($item['id'] ?? 0);
                    $qty = intval($item['qty'] ?? 1);
                    if ($pid > 0 && $qty > 0) {
                        $pdo->prepare("UPDATE inventory_parts SET stock_level = GREATEST(stock_level - ?, 0) WHERE part_id = ?")->execute([$qty, $pid]);
                        wcc_audit_log('inventory.deduct', 'inventory_parts', $pid, null, ['qty' => $qty], 'WO ' . $wo_id . ' consumption');
                        $pdo->prepare("INSERT INTO inventory_ledger (part_id, change_qty, reason, reference_type, reference_id, actor_user_id) VALUES (?, ?, 'wo_consume', 'work_orders', ?, ?)")->execute([$pid, -$qty, $wo_id, $tech_id]);
                    }
                }
            }
        }
        
        header("Location: /_maint/pm_calendar.php?msg=wo_updated");
        exit;
    }

} catch (PDOException $e) { die("DB Error: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Takeover Work Order #<?= $wo_id ?></title>
</head>
<body>
<?php include __DIR__ . '/../nav.php'; ?>

<div class="form-container">
    <div class="header-flex">
        <h2>🛠️ WO Takeover: #<?= $wo_id ?></h2>
        <a href="/_maint/pm_calendar.php" class="nav-btn">📅 Calendar</a>
    </div>
    
    <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; border: 1px solid var(--panel-border); margin-bottom: 20px;">
        <h3 style="color: var(--text-accent); margin-top: 0;"><?= htmlspecialchars($wo['title']) ?></h3>
        <p style="color: var(--text-secondary); margin-bottom: 5px;"><strong>Scheduled Date:</strong> <?= htmlspecialchars($wo['scheduled_date']) ?></p>
        <p style="color: var(--text-secondary); margin-bottom: 5px;"><strong>Equipment:</strong> <?= htmlspecialchars($wo['equipment_name'] ?? 'N/A') ?></p>
        <p style="color: var(--text-secondary); margin-bottom: 5px;"><strong>Assigned To:</strong> <?= htmlspecialchars($wo['assigned_user'] ?? 'N/A') ?></p>
        <p style="color: var(--text-primary); margin-top: 15px;"><strong>Instructions:</strong><br><?= nl2br(htmlspecialchars($wo['description'])) ?></p>
    </div>

    <?php $isLocked = in_array($wo['status'], ['Completed', 'Cancelled', 'Missed']); ?>
    <form method="POST" id="takeoverForm" enctype="multipart/form-data" onsubmit="return validateTakeoverForm(this)">
        <?php if ($wo['status'] === 'Scheduled' && empty($wo['started_at'])): ?>
            <input type="hidden" name="action" value="start_work">
            <div style="text-align: center; margin: 40px 0;">
                <button type="submit" class="btn" style="background: #3b82f6; color: white; padding: 20px 40px; font-size: 1.5em; font-weight: bold; border-radius: 12px; box-shadow: 0 10px 20px rgba(0,0,0,0.3);">
                    🚀 START WORK NOW
                </button>
                <p style="color: var(--text-secondary); margin-top: 15px;">Starts the time tracker for the PM Checklist.</p>
            </div>
        <?php else: ?>
        
        <?php if (!empty($checklist_data)): ?>
            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <h3 style="color: #10b981; margin-top: 0;">✅ PM Checklist (Expected: <?= $total_expected_mins ?> mins)</h3>
                <?php if (!empty($wo['started_at'])): ?>
                    <p style="color:var(--text-secondary); font-size:0.9em; margin-bottom:15px;">Started At: <strong><?= $wo['started_at'] ?></strong></p>
                <?php endif; ?>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php foreach($checklist_data as $idx => $item): ?>
                        <div style="position: relative; background: rgba(0,0,0,0.25); padding: 14px 18px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.05); margin-bottom: 5px; display: flex; flex-direction: column; gap: 8px;">
                            <!-- Expected Time at Top Left -->
                            <div style="display: flex; justify-content: flex-start;">
                                <span style="font-size: 0.8em; color: #94a3b8; background: rgba(0,0,0,0.4); padding: 3px 8px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.05);">⏱️ <?= $item['expected_time_mins'] ?> mins</span>
                            </div>
                            
                            <!-- Main content row -->
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <!-- Details to the Left (Hold-to-complete container) -->
                                <div id="task_label_<?= $idx ?>" class="task-hold-container <?= $isLocked ? 'completed' : '' ?>" 
                                     <?php if (!$isLocked): ?>
                                     onmousedown="startHold(<?= $idx ?>)" onmouseup="endHold(<?= $idx ?>)" onmouseleave="endHold(<?= $idx ?>)" 
                                     ontouchstart="startHold(<?= $idx ?>, event)" ontouchend="endHold(<?= $idx ?>)" ontouchcancel="endHold(<?= $idx ?>)"
                                     <?php endif; ?>
                                     style="flex: 1; cursor: pointer; margin: 0; position: relative; user-select: none; -webkit-user-select: none; transition: all 0.3s; <?= $isLocked ? '' : 'opacity: 0.6; filter: grayscale(1);' ?>">
                                    
                                    <span style="font-size: 1.1em; font-weight: 500; line-height: 1.4; color: <?= $isLocked ? '#10b981' : '#f8fafc' ?>;" id="task_text_<?= $idx ?>">
                                        <?= htmlspecialchars($item['task_desc']) ?>
                                    </span>
                                    
                                    <?php if (!$isLocked): ?>
                                    <!-- Visual countdown overlay container -->
                                    <div id="hold_overlay_<?= $idx ?>" style="position: absolute; top: 50%; left: 0%; transform: translate(-20px, -50%); opacity: 0; pointer-events: none; transition: opacity 0.1s; background: rgba(0,0,0,0.8); border-radius: 50%; padding: 2px;">
                                        <svg width="30" height="30" viewBox="0 0 40 40">
                                            <circle cx="20" cy="20" r="16" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="4"></circle>
                                            <circle id="hold_progress_<?= $idx ?>" cx="20" cy="20" r="16" fill="none" stroke="#10b981" stroke-width="4" stroke-dasharray="100" stroke-dashoffset="100" style="transform: rotate(-90deg); transform-origin: 50% 50%;"></circle>
                                        </svg>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Controls to the Right -->
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 20px; margin-left: 20px;">
                                    <input type="checkbox" id="chk_<?= $idx ?>" required <?= $isLocked ? 'checked disabled' : '' ?> style="opacity: 0; position: absolute; pointer-events: none;">
                                    
                                    <?php if (!$isLocked): ?>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div id="preview_container_<?= $idx ?>" style="display: flex; gap: 5px; flex-wrap: wrap;"></div>
                                            <label style="cursor: pointer; font-size: 1.8em; line-height: 1; margin: 0; opacity: 0.7; transition: opacity 0.2s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.7'" title="Upload Photo Evidence (Max 3)">
                                                📸
                                                <input type="file" name="checklist_photos[<?= $idx ?>][]" id="file_<?= $idx ?>" accept="image/*" multiple style="visibility: hidden; position: absolute; width: 0; height: 0;" onchange="handlePhotoSelect(this, <?= $idx ?>)">
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <label>Technician Taking Over:</label>
        <input type="text" value="<?= htmlspecialchars($_SESSION['username'] ?? 'Unknown User') ?>" readonly style="background: rgba(0,0,0,0.2); cursor: not-allowed; color: #94a3b8; font-weight: bold;">

        <?php if (!empty($required_parts)): ?>
            <label>Required Parts (Check to Consume from Inventory):</label>
            <div style="background: rgba(0,0,0,0.1); padding: 15px; border-radius: 8px; border: 1px solid var(--panel-border); margin-bottom: 15px;">
                <?php foreach($required_parts as $p): ?>
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: var(--text-primary); margin: 5px 0;">
                        <input type="checkbox" name="consume_parts[]" value="<?= $p['part_id'] ?>" style="width: auto;" <?= $isLocked ? 'disabled' : '' ?>>
                        <?= htmlspecialchars($p['part_name'] . ' [' . $p['internal_code'] . ']') ?> (Deduct 1x)
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Revised thoroughly: Usage list based management with qty slider (min_threshold to stock - min_threshold) -->
        <label>Search & Add Actual Parts Used (dropdown + qty slider per part):</label>
        <div class="searchbox-container" style="position:relative;">
            <input type="text" id="part_search" placeholder="Start typing part name or code..." autocomplete="off" 
                   style="width:100%; padding:10px; border-radius:6px; border:1px solid var(--input-border); background:var(--input-bg); color:var(--input-text);">
            <div id="part_dropdown" style="position:absolute; top:100%; left:0; right:0; background:var(--modal-bg); border:1px solid var(--panel-border); border-radius:6px; max-height:220px; overflow-y:auto; z-index:1000; display:none; box-shadow:0 4px 12px rgba(0,0,0,0.3);"></div>
        </div>
        <div id="selected_used_parts" style="margin-top:8px; display:flex; flex-direction:column; gap:8px; min-height:20px;"></div>
        <input type="hidden" name="used_parts_json" id="used_parts_json" value="[]">

        <label>Action Taken / Technician Notes:</label>
        <textarea name="action_taken" rows="3" required placeholder="Describe what was done..." style="width: 100%; box-sizing: border-box;" <?= $isLocked ? 'disabled' : '' ?>></textarea>

        <label>Final Status:</label>
        <select name="status" style="width: 100%; box-sizing: border-box;" <?= $isLocked ? 'disabled' : '' ?>>
            <option value="Completed" <?= $wo['status'] === 'Completed' ? 'selected' : '' ?>>✅ Completed</option>
            <option value="In Progress" <?= $wo['status'] === 'In Progress' ? 'selected' : '' ?>>⏳ In Progress</option>
            <option value="Missed" <?= $wo['status'] === 'Missed' ? 'selected' : '' ?>>⚠️ Missed</option>
            <option value="Cancelled" <?= $wo['status'] === 'Cancelled' ? 'selected' : '' ?>>❌ Cancelled</option>
        </select>
        
        <?php if ($isLocked): ?>
            <button type="button" class="btn" style="width: 100%; margin-top: 15px; background: #64748b; cursor: not-allowed;" disabled>Locked: <?= $wo['status'] ?></button>
        <?php else: ?>
            <button type="submit" class="btn" style="width: 100%; margin-top: 15px; background: #10b981; color: white;">Log & Close Work Order</button>
        <?php endif; ?>
        <?php endif; ?>
    </form>
</div>

<script src="/timer.js"></script>
<script>
// Revised thoroughly: Parts search with qty slider for usage list management
let selectedUsedParts = [];
const allPartsData = <?= json_encode($all_parts) ?>;

function updatePartQty(idx, newVal) {
    if (selectedUsedParts[idx]) {
        const part = selectedUsedParts[idx];
        let v = parseInt(newVal) || 1;
        const minQ = 1;
        const maxQ = part.stock_level || 1;
        v = Math.max(minQ, Math.min(maxQ, v));
        
        const resultingStock = (part.stock_level || 0) - v;
        const minThreshold = part.minimum_threshold || 0;

        const applyQty = function() {
            part.qty = v;
            const num = document.getElementById('num-' + idx);
            const slider = document.querySelector(`#selected_used_parts > div:nth-child(${idx+1}) input[type=range]`);
            if (num) num.value = v;
            if (slider) slider.value = v;
        };

        const revertQty = function() {
            const num = document.getElementById('num-' + idx);
            const slider = document.querySelector(`#selected_used_parts > div:nth-child(${idx+1}) input[type=range]`);
            if (num) num.value = part.qty;
            if (slider) slider.value = part.qty;
        };

        if (resultingStock < minThreshold) {
            revertQty();
            openWccConfirm(`Using ${v} would bring this item under minimum threshold (${minThreshold}). Place a PR/PO! Proceed?`, applyQty, 'Proceed');
            return;
        }
        
        applyQty();
        
        // update displayed value
        const valSpan = document.getElementById('qty-val-' + idx);
        if (valSpan) valSpan.textContent = v;
        
        // sync slider and number
        const rows = document.getElementById('selected_used_parts').children;
        if (rows[idx]) {
            const slider = rows[idx].querySelector('input[type="range"]');
            if (slider) slider.value = v;
            const num = document.getElementById('num-' + idx);
            if (num) num.value = v;
        }
        
        // update hidden
        document.getElementById('used_parts_json').value = JSON.stringify(
            selectedUsedParts.map(p => ({id: p.part_id, qty: p.qty || 1}))
        );
    }
}

function removeUsedPart(index) {
    selectedUsedParts.splice(index, 1);
    renderSelectedUsedParts();
}

function renderSelectedUsedParts() {
    const container = document.getElementById('selected_used_parts');
    container.innerHTML = '';
    selectedUsedParts.forEach((part, idx) => {
        const row = document.createElement('div');
        row.style.cssText = 'display:flex; align-items:center; gap:8px; background:rgba(56,189,248,0.1); padding:6px 10px; border-radius:8px; font-size:0.85em; flex-wrap:wrap;';
        
        const info = document.createElement('span');
        info.style.cssText = 'flex: 0 0 auto; min-width:120px;';
        info.innerHTML = `<strong>${part.part_name}</strong> <small>(${part.internal_code})</small>`;
        
        const minQ = 1;
        const maxQ = part.stock_level || 1;
        const curQ = Math.max(minQ, Math.min(maxQ, part.qty || minQ));
        part.qty = curQ; // clamp
        part.min_qty = minQ;
        part.max_qty = maxQ;
        
        // Slider
        const slider = document.createElement('input');
        slider.type = 'range';
        slider.min = minQ;
        slider.max = maxQ;
        slider.value = curQ;
        slider.style.cssText = 'flex:1; min-width:80px;';
        slider.oninput = function() { 
            updatePartQty(idx, this.value); 
            const num = document.getElementById('num-' + idx);
            if (num) num.value = this.value;
        };
        
        // Number box for exact input (for older users)
        const numInput = document.createElement('input');
        numInput.type = 'number';
        numInput.id = 'num-' + idx;
        numInput.min = minQ;
        numInput.max = maxQ;
        numInput.value = curQ;
        numInput.style.cssText = 'width:60px; padding:2px 4px; border-radius:4px; border:1px solid var(--input-border); background:var(--input-bg); color:var(--input-text); font-size:0.9em;';
        numInput.oninput = function() {
            let v = parseInt(this.value) || minQ;
            v = Math.max(minQ, Math.min(maxQ, v));
            this.value = v;
            updatePartQty(idx, v);
            slider.value = v;
        };
        
        const valSpan = document.createElement('span');
        valSpan.id = 'qty-val-' + idx;
        valSpan.style.cssText = 'min-width:30px; text-align:center; font-weight:bold; font-size:0.9em;';
        valSpan.textContent = curQ;
        
        const remove = document.createElement('span');
        remove.textContent = '×';
        remove.style.cssText = 'cursor:pointer; color:#f87171; font-weight:bold; padding:0 4px; margin-left:4px;';
        remove.onclick = () => removeUsedPart(idx);
        
        row.appendChild(info);
        row.appendChild(slider);
        row.appendChild(numInput);
        row.appendChild(valSpan);
        row.appendChild(remove);
        
        container.appendChild(row);
    });
    
    document.getElementById('used_parts_json').value = JSON.stringify(
        selectedUsedParts.map(p => ({id: p.part_id, qty: p.qty || 1}))
    );
}

// Hold to Complete mechanic
let holdTimers = {};
let holdIntervals = {};

function startHold(idx, event) {
    if (event && event.type.startsWith('touch')) { 
        // Allow default to let scroll happen, but prevent text selection if needed
    }
    
    let chk = document.getElementById('chk_' + idx);
    let container = document.getElementById('task_label_' + idx);
    let overlay = document.getElementById('hold_overlay_' + idx);
    let progress = document.getElementById('hold_progress_' + idx);
    let text = document.getElementById('task_text_' + idx);
    
    if (chk.checked) {
        // Uncheck instantly on click if already completed
        chk.checked = false;
        container.style.opacity = '0.6';
        container.style.filter = 'grayscale(1)';
        text.style.color = '#f8fafc';
        text.innerHTML = text.innerHTML.replace('✔️ ', '');
        return;
    }
    
    overlay.style.opacity = '1';
    
    let startTime = Date.now();
    let duration = 1000; // 1 second hold
    
    holdIntervals[idx] = setInterval(() => {
        let elapsed = Date.now() - startTime;
        let pct = Math.min(elapsed / duration, 1);
        progress.style.strokeDashoffset = 100 - (pct * 100);
        
        if (elapsed >= duration) {
            clearInterval(holdIntervals[idx]);
            chk.checked = true;
            overlay.style.opacity = '0';
            progress.style.strokeDashoffset = '100';
            container.style.opacity = '1';
            container.style.filter = 'none';
            text.style.color = '#10b981';
            if (!text.innerHTML.includes('✔️')) {
                text.innerHTML = '✔️ ' + text.innerHTML;
            }
        }
    }, 20);
}

function endHold(idx) {
    if (holdIntervals[idx]) {
        clearInterval(holdIntervals[idx]);
    }
    let chk = document.getElementById('chk_' + idx);
    let overlay = document.getElementById('hold_overlay_' + idx);
    let progress = document.getElementById('hold_progress_' + idx);
    
    if (!chk.checked) {
        overlay.style.opacity = '0';
        progress.style.strokeDashoffset = '100';
    }
}

function validateTakeoverForm(form) {
    let status = document.querySelector('select[name="status"]');
    if (status && status.value === 'Completed') {
        let chks = document.querySelectorAll('input[id^="chk_"]');
        for (let i = 0; i < chks.length; i++) {
            if (!chks[i].checked) {
                alert("Please complete all checklist items by pressing and holding them before marking the work order as Completed.");
                return false;
            }
        }
    }
    return true;
}

function filterUsedParts(query) {
    const dropdown = document.getElementById('part_dropdown');
    dropdown.innerHTML = '';
    if (!query || query.length < 1) {
        dropdown.style.display = 'none';
        return;
    }
    const q = query.toLowerCase();
    const matches = allPartsData.filter(p => 
        (p.part_name && p.part_name.toLowerCase().includes(q)) || 
        (p.internal_code && p.internal_code.toLowerCase().includes(q))
    ).slice(0, 7);

    if (matches.length === 0) {
        dropdown.style.display = 'block';
        const no = document.createElement('div');
        no.style.cssText = 'padding:8px 12px; color:#94a3b8;';
        no.textContent = 'No matching parts';
        dropdown.appendChild(no);
        return;
    }

    matches.forEach(part => {
        if (selectedUsedParts.find(sp => sp.part_id == part.part_id)) return;
        
        // Rational min=1 for "how many used". Warning if current stock <= min_threshold or adding would go under.
        const minQ = 1;
        const maxQ = part.stock_level || 1;
        const isUnderMin = (part.stock_level || 0) <= (part.minimum_threshold || 0);
        
        const div = document.createElement('div');
        div.style.cssText = 'padding:6px 10px; cursor:pointer; border-bottom:1px solid rgba(255,255,255,0.08);';
        div.innerHTML = `<strong>${part.part_name}</strong> <small>(${part.internal_code})</small> <span style="float:right; color:#94a3b8;">stock ${part.stock_level}</span>`;
        
        div.onclick = () => {
            const addPart = () => {
                const newPart = {...part, qty: minQ, min_qty: minQ, max_qty: maxQ};
                selectedUsedParts.push(newPart);
                renderSelectedUsedParts();
                dropdown.style.display = 'none';
                document.getElementById('part_search').value = '';
            };

            if (isUnderMin) {
                openWccConfirm("Item under minimum threshold! Place a PR/PO! Proceed anyway?", addPart, 'Proceed');
                dropdown.style.display = 'none';
                document.getElementById('part_search').value = '';
                return;
            }
            addPart();
        };
        dropdown.appendChild(div);
    });
    dropdown.style.display = 'block';
}

// Setup
document.addEventListener('DOMContentLoaded', function() {
    const search = document.getElementById('part_search');
    const dropdown = document.getElementById('part_dropdown');
    
    if (search) {
        search.addEventListener('input', () => filterUsedParts(search.value.trim()));
        search.addEventListener('focus', () => { if (search.value.trim()) filterUsedParts(search.value.trim()); });
    }
    
    document.addEventListener('click', function(e) {
        if (dropdown && !e.target.closest('.searchbox-container')) {
            dropdown.style.display = 'none';
        }
    });
});

function handlePhotoSelect(input, idx) {
    let container = document.getElementById('preview_container_' + idx);
    container.innerHTML = '';
    if (input.files && input.files.length > 0) {
        let count = Math.min(input.files.length, 3);
        for(let i = 0; i < count; i++) {
            let reader = new FileReader();
            reader.onload = function(e) {
                let div = document.createElement('div');
                div.style.position = 'relative';
                div.innerHTML = `<img src="${e.target.result}" style="max-height: 40px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.2);">`;
                container.appendChild(div);
            }
            reader.readAsDataURL(input.files[i]);
        }
        let clearBtn = document.createElement('div');
        clearBtn.onclick = () => removePhoto(idx);
        clearBtn.innerHTML = '×';
        clearBtn.style.cssText = 'background: red; color: white; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-size: 12px; cursor: pointer; font-weight: bold; line-height: 1; margin-top: -5px; margin-left: -5px; z-index: 2; box-shadow: 0 2px 4px rgba(0,0,0,0.5);';
        container.appendChild(clearBtn);
    }
}

function removePhoto(idx) {
    let input = document.getElementById('file_' + idx);
    let container = document.getElementById('preview_container_' + idx);
    input.value = ''; // clear the file
    container.innerHTML = '';
}
</script>
</body>
</html>

