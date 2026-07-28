<?php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('view_tickets');

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    // Query includes the 48-hour Repeat Offender sub-query and Equipment Join
    $stmt = $pdo->query("
        SELECT a.*, e.equip_name,
        (SELECT COUNT(*) FROM active_tickets t2 WHERE t2.equip_id = a.equip_id AND t2.created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR) -- AND t2.deleted_at IS NULL
        ) as recent_count
        FROM active_tickets a 
        LEFT JOIN equipment e ON a.equip_id = e.equip_id 
        WHERE a.status IN ('OPEN', 'ESCALATED', 'PENDING', 'HOLD') 
          -- AND a.deleted_at IS NULL   (uncomment after running migration 0005)
        ORDER BY a.created_at DESC
    ");
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch timeline actions for these tickets
    $ticket_ids = array_column($tickets, 'ticket_id');
    $actions_by_ticket = [];
    if (!empty($ticket_ids)) {
        $inQuery = implode(',', array_fill(0, count($ticket_ids), '?'));
        $stmtAct = $pdo->prepare("SELECT * FROM ticket_actions WHERE ticket_id IN ($inQuery) ORDER BY action_start ASC");
        $stmtAct->execute($ticket_ids);
        $actions = $stmtAct->fetchAll(PDO::FETCH_ASSOC);
        foreach ($actions as $act) {
            $actions_by_ticket[$act['ticket_id']][] = $act;
        }
    }

    // Fetch comments for these tickets
    $comments_by_ticket = [];
    if (!empty($ticket_ids)) {
        $stmtCmt = $pdo->prepare("SELECT * FROM ticket_comments WHERE ticket_id IN ($inQuery) ORDER BY created_at ASC");
        $stmtCmt->execute($ticket_ids);
        $comments = $stmtCmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($comments as $cmt) {
            $comments_by_ticket[$cmt['ticket_id']][] = $cmt;
        }
    }
    // ================= FACTORY HEALTH CALCULATION =================
    // 1. Get total number of machines in the factory
    $stmtEq = $pdo->query("SELECT COUNT(*) FROM equipment");  // TODO: add WHERE deleted_at IS NULL after migration 0005
    $total_machines = (int)$stmtEq->fetchColumn();
    
    // 2. Count how many unique machines are currently broken (on the dashboard)
    $down_machines = count(array_unique(array_column($tickets, 'equip_id')));
    
    // 3. Calculate percentage and assign dynamic color
    $health_pct = $total_machines > 0 ? round((($total_machines - $down_machines) / $total_machines) * 100, 1) : 100;
    $health_color = $health_pct >= 90 ? '#10b981' : ($health_pct >= 75 ? '#f59e0b' : '#ef4444');

    require_once __DIR__ . '/../inc/kpi_engine.php';
    $op_kpis = get_current_month_operations_kpi($pdo);
    // ==============================================================

    // ================= WORK ORDERS (SCHEDULED) ====================
    $stmtWO = $pdo->query("
        SELECT w.*, e.equip_name, u.badge_number as assigned_user
        FROM work_orders w
        LEFT JOIN equipment e ON w.equipment_id = e.equip_id
        LEFT JOIN users u ON w.assigned_to = u.user_id
        WHERE w.status = 'Scheduled'
        ORDER BY w.scheduled_date ASC
        LIMIT 15
    ");
    $active_wos = $stmtWO->fetchAll(PDO::FETCH_ASSOC);

    $db_today = $pdo->query("SELECT CURDATE()")->fetchColumn();

} catch (PDOException $e) { wcc_user_error("Unable to load active tickets right now.", $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Active Tickets</title>
    <style>
        /* Force table headers and main rows to NEVER wrap, pushing overflow to horizontal scrollbar */
        .data-table th, .parent-row td {
            white-space: nowrap !important;
        }
    </style>

    <script>
        async function submitComment(ticketId) {
            const input = document.getElementById('commentInput_' + ticketId);
            const text = input.value.trim();
            if (!text) return;

            try {
                const res = await fetch('/api/add_comment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ticket_id: ticketId, comment_text: text })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    input.value = '';
                    refreshComments(ticketId);
                } else {
                    alert(data.message || 'Failed to add comment');
                }
            } catch (err) {
                console.error(err);
                alert('Error submitting comment');
            }
        }

        async function refreshComments(ticketId) {
            try {
                const res = await fetch('/api/get_comments.php?ticket_id=' + encodeURIComponent(ticketId));
                const data = await res.json();
                if (data.status === 'success') {
                    const container = document.getElementById('commentsList_' + ticketId);
                    if (container) {
                        container.innerHTML = data.html;
                    }
                }
            } catch (e) {
                console.error('Error fetching comments:', e);
            }
        }
    </script>
</head>
<body><?php include __DIR__ . '/../nav.php'; ?>

<div class="dashboard-container">
    <div class="header-flex">
        <h2>Active Tickets</h2>
        <div style="display:flex; gap:10px;"><a href="../index.php" class="nav-btn">🏠 Hub</a><a href="../register.php" class="nav-btn primary">+ New Ticket</a></div>
    </div>

    <div style="background: var(--panel-bg); border-radius: 16px; padding: 20px; margin-bottom: 25px; border: 1px solid var(--panel-border); border-top: 1px solid var(--panel-border-top); box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; align-items: flex-end;">
            <span style="font-weight: 800; color: var(--text-accent); font-size: 1.1em; letter-spacing: 1px;">🏭 FACTORY HEALTH</span>
            <span style="font-weight: 800; color: <?= $health_color ?>; font-size: 1.4em;"><?= $health_pct ?>% UPTIME</span>
        </div>
        <div style="width: 100%; background: rgba(0,0,0,0.2); border-radius: 10px; height: 12px; overflow: hidden; box-shadow: inset 0 1px 3px rgba(0,0,0,0.5);">
            <div style="width: <?= $health_pct ?>%; background: <?= $health_color ?>; height: 100%; transition: width 1.5s ease-in-out;"></div>
        </div>
        <div style="font-size: 0.85em; color: var(--text-secondary); margin-top: 12px; font-weight: 600; display: flex; justify-content: space-between; align-items: center;">
            <span><?= $total_machines - $down_machines ?> of <?= $total_machines ?> machines are currently operational</span>
            <span style="display: flex; gap: 15px;">
                <span style="background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); padding: 4px 10px; border-radius: 6px; color: #10b981; font-size: 1.05em;">
                    🛡️ MTBF MTD: <strong><?= $op_kpis['mtbf'] ?>h</strong>
                </span>
                <span style="background: rgba(234,179,8,0.1); border: 1px solid rgba(234,179,8,0.3); padding: 4px 10px; border-radius: 6px; color: #eab308; font-size: 1.05em;">
                    ⏱️ Wrench Time: <strong><?= $op_kpis['mttr'] ?>m</strong>
                </span>
            </span>
        </div>
    </div>

    <div class="table-container" style="overflow-x: auto; width: 100%;">
    <table class="data-table" style="width: 100%;">
        <thead>
            <tr>
                <th>Ticket ID</th>
                <th>Equipment Details</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Ongoing Time</th>
                <th>Announced By</th>
                <th>Invoked PIC</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($tickets) > 0): ?>
                <?php foreach ($tickets as $ticket): 
                    $safe_timestamp = str_replace(' ', 'T', $ticket['created_at']);
                    $stat = $ticket['status'];
                    $prio = !empty($ticket['priority']) ? strtolower($ticket['priority']) : 'normal';
                    
                    // The Idle Ticket Logic (Over 45 mins and nobody has taken it)
                    $minutes_open = round((time() - strtotime($ticket['created_at'])) / 60);
                    $isIdle = ($stat == 'OPEN' && $minutes_open > 45);
                    $isRepeat = ($ticket['recent_count'] >= 2);
                    
                    // Row Styling Hierarchy
                    if ($isIdle) {
                        $rowClass = "priority-idle";
                    } elseif ($prio == 'critical' && $isRepeat) {
                        $rowClass = "priority-critical-repeat";
                    } elseif ($isRepeat) {
                        $rowClass = "priority-repeat";
                    } else {
                        $rowClass = "priority-" . $prio;
                    }
                    
                    $badgeClass = "badge-" . $prio;
                    $dot = ($prio=='critical')?'🔴':(($prio=='high')?'🟠':(($prio=='low')?'🟢':'🔵'));
                ?>
                    <tr class="<?= $rowClass ?> parent-row" data-id="<?= htmlspecialchars($ticket['ticket_id']) ?>">
                        <td style="font-weight: 600; color: var(--text-accent); white-space: nowrap;">
                            <span class="row-arrow">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </span>
                            <?= htmlspecialchars($ticket['ticket_id']) ?>
                        </td>
                        
                        <td>
                            <div style="font-weight: 700; color: var(--text-accent); font-size: 1.1em;">
                                <?= htmlspecialchars($ticket['equip_id']) ?>
                                <?php if($isRepeat): ?>
                                    <span style="font-size: 0.7em; background: #ffedd5; color: #ea580c; padding: 2px 6px; border-radius: 6px; margin-left: 5px; vertical-align: top; border: 1px solid #fdba74;">⚠️ Repeat Offender</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 0.85em; color: var(--text-secondary); margin-top: 2px; font-weight: 500;"><?= htmlspecialchars($ticket['equip_name'] ?? 'Unknown Machine') ?></div>
                        </td>

                        <td><span class="prio-badge <?= $badgeClass ?>"><?= $dot ?> <?= $prio ?></span></td>
                        
                        <td>
                            <?php 
                            $class = ($stat=='OPEN') ? 'status-open' : (($stat=='ESCALATED') ? 'status-escalated' : (($stat=='HOLD') ? 'status-escalated' : 'status-pending'));
                            if ($stat == 'HOLD') {
                                echo "<span class='$class' style='background:#fef08a; color:#854d0e; border:1px solid #fde047;'>ON HOLD</span>"; 
                            } else {
                                echo "<span class='$class'>$stat</span>"; 
                            }
                            
                            // The IDLE Warning Badge
                            if ($isIdle) {
                                echo "<br><span style='display:inline-block; margin-top:6px; font-size:0.8em; background:#f3e8ff; color:#7e22ce; padding:3px 8px; border-radius:6px; border:1px solid #d8b4fe; font-weight:bold; box-shadow: 0 2px 4px rgba(168, 85, 247, 0.2);'>⏳ IDLE > 45m</span>";
                            }
                            ?>
                        </td>

                        <td>
                            <div style="color: var(--text-secondary); font-size: 0.85em;">Form Date: <?= htmlspecialchars($ticket['report_time']) ?></div>
                            <span class="live-timer" data-start="<?= $safe_timestamp ?>">Calculating...</span>
                        </td>
                        <td><?= htmlspecialchars($ticket['announced_by']) ?></td>
                        
                        <td>
                            <div style="font-weight: 700; color: var(--text-accent); font-size: 1.05em;">👨‍🔧 <?= htmlspecialchars($ticket['pic'] ?? 'Unassigned') ?></div>
                        </td>
                        
                        <td>
                            <?php if ($stat == 'OPEN' || $stat == 'ESCALATED'): ?>
                                <a href="/_maint/takeover.php?id=<?= urlencode($ticket['ticket_id']) ?>" class="action-btn btn-take">Takeover</a>
                            <?php elseif ($stat == 'PENDING' || $stat == 'IN_PROGRESS'): ?>
                                <a href="/_maint/closeout.php?id=<?= urlencode($ticket['ticket_id']) ?>" class="action-btn btn-close">Review/Close</a>
                                <button onclick="openHoldModal('<?= htmlspecialchars(addslashes($ticket['ticket_id'])) ?>'); event.stopPropagation();" class="action-btn btn-take" style="background:#eab308; color:black; margin-top:5px; border:none; display:block; width:100%; box-sizing:border-box;">Put on Hold</button>
                            <?php elseif ($stat == 'HOLD'): ?>
                                <a href="/_maint/takeover.php?id=<?= urlencode($ticket['ticket_id']) ?>" class="action-btn btn-take">Resume Job</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <tr class="child-row">
                        <td colspan="12">
                            <div class="child-content">
                                <span style="font-weight: 800; color: var(--text-accent); font-size: 0.9em; text-transform: uppercase;">Original Fault Description:</span><br> 
                                <span style="font-size: 0.95em; line-height: 1.4; display: inline-block; margin-top: 5px; margin-bottom: 10px;"><?= nl2br(htmlspecialchars($ticket['fault_desc'])) ?></span>
                                
                                <?php if (!empty($actions_by_ticket[$ticket['ticket_id']])): ?>
                                    <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px; margin-top: 5px;">
                                        <span style="font-weight: 800; color: var(--text-accent); font-size: 0.9em; text-transform: uppercase; margin-bottom: 10px; display: block;">Intervention Timeline:</span>
                                        <div style="display: flex; flex-direction: column; gap: 10px;">
                                            <?php foreach($actions_by_ticket[$ticket['ticket_id']] as $idx => $act): ?>
                                                <div style="background: var(--panel-bg); border-left: 3px solid <?= ($act['escalated_to'] !== 'None' ? '#ea580c' : '#10b981') ?>; padding: 12px; border-radius: 6px; border-top: 1px solid rgba(255,255,255,0.05); border-right: 1px solid rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.05); box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                                                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 5px;">
                                                        <span style="font-weight: bold; color: var(--text-primary); font-size: 1.05em;">👨‍🔧 <?= htmlspecialchars($act['tech_name']) ?></span>
                                                        <span style="color: var(--text-secondary); font-size: 0.85em; background: rgba(0,0,0,0.2); padding: 3px 8px; border-radius: 4px;">⏱️ <?= htmlspecialchars(date('M d, H:i', strtotime($act['action_start']))) ?> - <?= htmlspecialchars(date('H:i', strtotime($act['action_end']))) ?></span>
                                                    </div>
                                                    
                                                    <div style="font-size: 0.95em; color: #cbd5e1; margin-bottom: 5px;">
                                                        <strong style="color: var(--text-secondary);">Action Taken:</strong> <?= nl2br(htmlspecialchars($act['action_taken'])) ?>
                                                    </div>
                                                    
                                                    <?php if(!empty($act['parts_used']) && $act['parts_used'] !== 'None'): ?>
                                                        <div style="font-size: 0.85em; color: #94a3b8; margin-top: 5px; padding: 5px; background: rgba(0,0,0,0.15); border-radius: 4px; display: inline-block;">
                                                            <strong style="color: var(--text-secondary);">📦 Parts Used:</strong> <?= htmlspecialchars($act['parts_used']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if($act['escalated_to'] !== 'None'): ?>
                                                        <div style="font-size: 0.9em; color: #ea580c; margin-top: 8px; font-weight: bold; padding: 5px; background: rgba(234, 88, 12, 0.1); border-radius: 4px; display: inline-block; border: 1px solid rgba(234, 88, 12, 0.2);">
                                                            ⚠️ Escalated to: <?= htmlspecialchars($act['escalated_to']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Relational Comments Feed -->
                                <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px; margin-top: 15px;">
                                    <span style="font-weight: 800; color: var(--text-accent); font-size: 0.9em; text-transform: uppercase; margin-bottom: 10px; display: block;">💬 Live Comments Feed:</span>
                                    
                                    <div class="comments-container" id="commentsList_<?= htmlspecialchars($ticket['ticket_id']) ?>" style="max-height: 300px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; margin-bottom: 10px; padding-right: 5px;">
                                        <?php if (!empty($comments_by_ticket[$ticket['ticket_id']])): ?>
                                            <?php foreach($comments_by_ticket[$ticket['ticket_id']] as $cmt): ?>
                                                <div style="background: rgba(255,255,255,0.05); padding: 8px 12px; border-radius: 8px; border-left: 3px solid #38bdf8;">
                                                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 0.85em;">
                                                        <strong style="color: var(--text-primary);"><?= htmlspecialchars($cmt['user_name']) ?></strong>
                                                        <span style="color: var(--text-secondary);"><?= htmlspecialchars(date('M d, H:i', strtotime($cmt['created_at']))) ?></span>
                                                    </div>
                                                    <div style="font-size: 0.95em; color: #e2e8f0;">
                                                        <?= nl2br(htmlspecialchars($cmt['comment_text'])) ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div style="font-size: 0.9em; color: var(--text-secondary); font-style: italic;">No comments yet.</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Add Comment Form -->
                                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                                        <input type="text" id="commentInput_<?= htmlspecialchars($ticket['ticket_id']) ?>" placeholder="Type a comment..." style="flex-grow: 1; padding: 10px; border-radius: 6px; border: 1px solid var(--text-accent); background: var(--input-bg); color: var(--text-primary); box-sizing: border-box;" onkeypress="if(event.key === 'Enter') submitComment('<?= htmlspecialchars(addslashes($ticket['ticket_id'])) ?>')">
                                        <button onclick="submitComment('<?= htmlspecialchars(addslashes($ticket['ticket_id'])) ?>')" class="action-btn btn-take" style="margin: 0; padding: 0 20px;">Send</button>
                                    </div>
                                </div>
                                
                            </div>
                        </td>
                    </tr>

                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="12" style="text-align: center; padding: 40px; color: var(--text-secondary);">No active tickets right now! 🎉</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>

    <div class="header-flex" style="margin-top: 50px; margin-bottom: 15px;">
        <h2 style="color: var(--text-accent);">🛠️ Scheduled Work Orders</h2>
        <div>
            <a href="/_maint/pm_calendar.php" class="nav-btn primary">🗓️ Open PM Calendar</a>
        </div>
    </div>

    <div class="table-container" style="overflow-x: auto; width: 100%;">
    <table class="data-table" style="min-width: 1200px;">
        <thead>
            <tr>
                <th>WO #</th>
                <th>Title / Instructions</th>
                <th>Target Equipment</th>
                <th>Scheduled Date</th>
                <th>Assigned Tech</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($active_wos) > 0): ?>
                <?php foreach ($active_wos as $wo): 
                    $diff = (strtotime($db_today) - strtotime($wo['scheduled_date'])) / (60 * 60 * 24);
                    $rowStyle = "";
                    if ($diff > 0) $rowStyle = "background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444;";
                    elseif ($diff == 0) $rowStyle = "background: rgba(234, 179, 8, 0.1); border-left: 4px solid #eab308;";
                ?>
                    <tr style="<?= $rowStyle ?>">
                        <td style="font-weight: 600; color: var(--text-accent); white-space: nowrap;">
                            WO-<?= htmlspecialchars($wo['wo_id']) ?>
                            <?php if ($diff > 0): ?>
                                <br><span style="display:inline-block; margin-top:6px; font-size:0.75em; background: rgba(239, 68, 68, 0.15); color: #ef4444; padding: 3px 10px; border-radius: 12px; border: 1px solid #ef4444; font-weight: 800; letter-spacing: 0.5px; animation: pulseRed 1.5s infinite;">OVERDUE</span>
                            <?php elseif ($diff == 0): ?>
                                <br><span style="display:inline-block; margin-top:6px; font-size:0.75em; background: rgba(234, 179, 8, 0.15); color: #eab308; padding: 3px 10px; border-radius: 12px; border: 1px solid #eab308; font-weight: 800; letter-spacing: 0.5px;">TODAY</span>
                            <?php endif; ?>
                        </td>
                        <td><strong style="color: var(--text-primary);"><?= htmlspecialchars($wo['title']) ?></strong></td>
                        <td><?= htmlspecialchars($wo['equip_name'] ?? 'Unknown') ?></td>
                        <td style="font-weight: bold;">
                            <?php if ($diff > 0): ?>
                                <span style="color: #ef4444; margin-right: 5px;">⚠️</span>
                            <?php elseif ($diff == 0): ?>
                                <span style="color: #eab308; margin-right: 5px;">⚠️</span>
                            <?php endif; ?>
                            <span style="<?= $diff > 0 ? 'color: #ef4444;' : ($diff == 0 ? 'color: #eab308;' : '') ?>"><?= htmlspecialchars($wo['scheduled_date']) ?></span>
                        </td>
                        <td>👨‍🔧 <?= htmlspecialchars($wo['assigned_user'] ?? 'Unassigned') ?></td>
                        <td>
                            <a href="/_maint/wo_takeover.php?wo_id=<?= urlencode($wo['wo_id']) ?>" class="action-btn btn-take">Takeover</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align: center; padding: 20px; color: var(--text-secondary);">No active work orders pending.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- HOLD MODAL -->
<div id="holdModal" class="modal" onclick="if(event.target.id === 'holdModal') closeHoldModal();" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.8);">
    <div class="modal-content" onclick="event.stopImmediatePropagation();" style="background-color: #0f172a; margin: 15% auto; padding: 20px; border: 1px solid #334155; width: 400px; border-radius: 8px; position:relative; box-shadow: 0 4px 25px rgba(0,0,0,1);">
        <span class="close-btn" onclick="closeHoldModal()" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        <h2 style="color:var(--text-accent); margin-top:0; margin-bottom:15px;">Put Ticket on Hold</h2>
        <input type="hidden" id="hold_ticket_id">
        <label style="display:block; margin-bottom:5px; color:var(--text-secondary); font-weight:600;">Reason for Hold:</label>
        <select id="hold_reason" style="width:100%; padding:10px; margin-bottom:15px; border-radius:6px; background:#1e293b; color:white; border:1px solid #475569;">
            <option value="Waiting for Parts">Waiting for Parts</option>
            <option value="Waiting for External Vendor">Waiting for External Vendor</option>
            <option value="Waiting for Production Clearance">Waiting for Production Clearance</option>
            <option value="End of Shift / Handover">End of Shift / Handover</option>
            <option value="Other">Other</option>
        </select>
        <label style="display:block; margin-bottom:5px; color:var(--text-secondary); font-weight:600;">Explanation / Comments:</label>
        <textarea id="hold_explanation" rows="3" style="width:100%; padding:10px; border-radius:6px; background:#1e293b; color:white; border:1px solid #475569; margin-bottom:15px; box-sizing:border-box;" placeholder="Provide details..."></textarea>
        <button class="nav-btn primary" onclick="submitHold()" style="width:100%; font-weight:bold; padding:12px; font-size:1.05em; background:var(--primary-color); border:none; border-radius:6px; color:white; cursor:pointer;">Confirm HOLD Status</button>
    </div>
</div>


<script>
    function openHoldModal(ticketId) {
        document.getElementById('hold_ticket_id').value = ticketId;
        document.getElementById('holdModal').style.display = 'block';
    }
    
    function closeHoldModal() {
        document.getElementById('holdModal').style.display = 'none';
    }
    
    async function submitHold() {
        const ticketId = document.getElementById('hold_ticket_id').value;
        const reason = document.getElementById('hold_reason').value;
        const explanation = document.getElementById('hold_explanation').value;
        
        if(!explanation) { alert("Please provide an explanation."); return; }
        
        const payload = {
            ticket_id: ticketId,
            reason: reason,
            explanation: explanation
        };
        
        try {
            const res = await fetch('/api/submit_hold.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status === 'success') {
                window.location.reload();
            } else {
                alert("Error: " + data.message);
            }
        } catch (e) {
            alert("Network error.");
        }
    }

    function updateTimers() {
        const timers = document.querySelectorAll('.live-timer');
        const now = new Date();
        timers.forEach(timer => {
            const startTime = new Date(timer.getAttribute('data-start'));
            if (isNaN(startTime)) { timer.innerText = "Time Error"; return; }
            const diff = now - startTime;
            if (diff < 0) { timer.innerText = "Just now..."; return; }
            const d = Math.floor(diff / (1000 * 60 * 60 * 24));
            const h = Math.floor((diff / (1000 * 60 * 60)) % 24);
            const m = Math.floor((diff / 1000 / 60) % 60);
            const s = Math.floor((diff / 1000) % 60);
            let timeString = "";
            if (d > 0) timeString += d + "d ";
            if (h > 0 || d > 0) timeString += String(h).padStart(2, '0') + "h ";
            timeString += String(m).padStart(2, '0') + "m " + String(s).padStart(2, '0') + "s";
            timer.innerText = "⏱️ " + timeString;
        });
    }

    window.onload = function() {
        updateTimers();
        setInterval(updateTimers, 1000);
        
        // Poll for new comments in expanded accordions
        setInterval(function() {
            const expandedRows = document.querySelectorAll('.parent-row.is-expanded');
            expandedRows.forEach(row => {
                const ticketId = row.getAttribute('data-id');
                if (ticketId) {
                    refreshComments(ticketId);
                }
            });
        }, 3000);
    };

</body>
</html>
