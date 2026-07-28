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

    // 3. Calculate percentage and assign dynamic health token
    $health_pct = $total_machines > 0 ? round((($total_machines - $down_machines) / $total_machines) * 100, 1) : 100;
    $health_var = $health_pct >= 90 ? 'var(--success)' : ($health_pct >= 75 ? 'var(--warning)' : 'var(--danger)');

    // Month-to-date operations snapshot, from the single KPI engine (same maths as
    // the dashboard) so the chips never disagree with _rpt/statistics.php.
    require_once __DIR__ . '/../inc/kpi.php';
    require_once __DIR__ . '/../inc/shift_calendar.php';
    $holJson = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key='plant_holidays'")->fetchColumn() ?: '[]';
    $mtdCal  = new ShiftCalendar('06:00:00', '22:00:00', [1,2,3,4,5], json_decode($holJson, true) ?? []);
    $op      = wcc_kpi_window_summary($pdo, date('Y-m-01'), date('Y-m-d'), $mtdCal, 16, [1,2,3,4,5]);
    $op_kpis = [
        'mtbf'   => $op['mtbf'] === null ? null : round($op['mtbf'] / 60, 1), // hours
        'labour' => $op['labour'],                                            // minutes (hands-on effort)
    ];
    // ==============================================================

    // ================= WORK ORDERS (SCHEDULED) ====================
    $stmtWO = $pdo->query("
        SELECT w.*, e.equip_name, u.badge_number as assigned_user
        FROM work_orders w
        LEFT JOIN equipment e ON w.equipment_id = e.equip_id
        LEFT JOIN users u ON w.assigned_to = u.user_id
        WHERE w.status IN ('Scheduled', 'In Progress', 'Missed')
        ORDER BY (w.scheduled_date < CURDATE()) DESC, w.scheduled_date ASC
        LIMIT 30
    ");
    $active_wos = $stmtWO->fetchAll(PDO::FETCH_ASSOC);

    $db_today = $pdo->query("SELECT CURDATE()")->fetchColumn();

} catch (PDOException $e) { wcc_user_error("Unable to load active tickets right now.", $e->getMessage()); }

$page_title = 'Active Tickets';
require_once __DIR__ . '/../inc/head.php';
?>
<style>
    /* ------------------------------------------------------------------
       Table fit: no horizontal scrollbar.

       This table used to force white-space:nowrap on every header and cell,
       which pinned it at ~1423px inside a 1248px box and pushed the overflow
       into a horizontal scrollbar. Nothing can compress when every column
       refuses to wrap, so widening the container alone could never fix it.

       Now: nowrap is kept ONLY where wrapping would actually look broken
       (IDs, status/priority pills, action buttons), and the text-heavy
       columns may wrap and give back their slack. The table then fits at
       1440 wide with the sidebar expanded, and degrades by wrapping further
       on smaller windows instead of scrolling sideways.
       Scoped >=641px so the mobile card-collapse still wraps freely.
       ------------------------------------------------------------------ */
    @media (min-width: 641px) {
        /* Roomier box on this page - it carries the widest table in the app. */
        .dashboard-container {
            max-width: 1680px;
            padding-left: var(--space-6);
            padding-right: var(--space-6);
        }

        .data-table { width: 100%; }

        /* Columns that must stay on one line. */
        .data-table th:nth-child(1), .parent-row td:nth-child(1),   /* Ticket ID */
        .data-table th:nth-child(3), .parent-row td:nth-child(3),   /* Priority  */
        .data-table th:nth-child(4), .parent-row td:nth-child(4),   /* Status    */
        .data-table th:nth-child(8), .parent-row td:nth-child(8) {  /* Action    */
            white-space: nowrap;
        }

        /* Columns free to wrap, with sane ceilings so one long value cannot
           blow the layout back out again. */
        .parent-row td:nth-child(2) { white-space: normal; max-width: 240px; }  /* Equipment   */
        .parent-row td:nth-child(5) { white-space: normal; max-width: 190px; }  /* Ongoing     */
        .parent-row td:nth-child(6) { white-space: normal; max-width: 140px; }  /* Announced   */
        .parent-row td:nth-child(7) { white-space: normal; max-width: 150px; }  /* Invoked PIC */

        /* Long equipment names break rather than overflow. */
        .parent-row td { overflow-wrap: anywhere; }
    }

    /* Compact tier: between the mobile card-collapse and a roomy desktop there
       is a band where even the wrapping columns cannot give back enough, because
       the four nowrap columns alone claim ~594px. Here we also tighten cell
       padding, drop a type size, and let the ticket ID wrap - enough to stay
       scrollbar-free down to the tablet breakpoint. */
    @media (min-width: 641px) and (max-width: 1200px) {
        .data-table th, .parent-row td {
            padding-left: 8px;
            padding-right: 8px;
            font-size: var(--fs-sm);
        }
        .data-table th:nth-child(1), .parent-row td:nth-child(1) { white-space: normal; }
        .parent-row td:nth-child(2) { max-width: 170px; }
        .parent-row td:nth-child(5) { max-width: 130px; }
        .parent-row td:nth-child(6) { max-width: 110px; }
        .parent-row td:nth-child(7) { max-width: 110px; }
    }

    .health-panel {
        background: var(--panel-bg);
        border-radius: var(--radius-lg);
        padding: var(--space-5);
        margin-bottom: var(--space-6);
        border: 1px solid var(--panel-border);
        border-top: 1px solid var(--panel-border-top);
        box-shadow: var(--shadow-1);
    }
    .health-chip {
        padding: 4px 10px;
        border-radius: var(--radius-sm);
        font-size: var(--fs-sm);
        border: 1px solid transparent;
        white-space: nowrap;
    }
    .health-meta { font-size: var(--fs-sm); color: var(--text-secondary); margin-top: 12px; font-weight: 600; display: flex; justify-content: space-between; align-items: center; gap: var(--space-3); flex-wrap: wrap; }

    .repeat-chip {
        font-size: var(--fs-xs); background: var(--warning-bg); color: var(--status-escalated-text);
        padding: 2px 6px; border-radius: var(--radius-sm); margin-left: 5px;
        vertical-align: top; border: 1px solid var(--status-escalated-border);
    }
    .hold-chip { background: var(--warning-bg) !important; color: var(--warning) !important; border: 1px solid var(--warning-border) !important; }
    .idle-chip {
        display: inline-block; margin-top: 6px; font-size: var(--fs-xs);
        background: rgba(168, 85, 247, 0.15); color: #c084fc;
        padding: 3px 8px; border-radius: var(--radius-sm);
        border: 1px solid rgba(168, 85, 247, 0.4); font-weight: bold;
    }
    .light-theme .idle-chip { background: #f3e8ff; color: #7e22ce; border-color: #d8b4fe; }

    .timeline-entry {
        background: var(--surface-1);
        padding: 12px; border-radius: var(--radius-sm);
        border: 1px solid var(--panel-border);
        box-shadow: var(--shadow-1);
    }
    .comment-bubble {
        background: var(--surface-1);
        padding: 8px 12px; border-radius: var(--radius-sm);
        border-left: 3px solid var(--accent);
    }
</style>
<?php include __DIR__ . '/../nav.php'; ?>

<script>
    async function submitComment(ticketId) {
        const input = document.getElementById('commentInput_' + ticketId);
        const text = input.value.trim();
        if (!text) return;

        try {
            const res = await fetch('/api/add_comment.php', {
                method: 'POST',
                headers: wccJsonHeaders(),
                body: JSON.stringify(wccWithCsrf({ ticket_id: ticketId, comment_text: text }))
            });
            const data = await res.json();
            if (data.status === 'success') {
                input.value = '';
                refreshComments(ticketId);
            } else {
                showToast(data.message || 'Failed to add comment', 'error');
            }
        } catch (err) {
            console.error(err);
            showToast('Error submitting comment', 'error');
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

<div class="dashboard-container">
    <div class="page-header">
        <h1>Active Tickets</h1>
        <div style="display:flex; gap:10px;"><a href="../index.php" class="nav-btn">🏠 Hub</a><a href="../register.php" class="nav-btn primary">+ New Ticket</a></div>
    </div>

    <div class="health-panel">
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; align-items: flex-end; gap: var(--space-3); flex-wrap: wrap;">
            <span style="font-weight: 800; color: var(--text-accent); font-size: 1.1em; letter-spacing: 1px;">🏭 FACTORY HEALTH</span>
            <span style="font-weight: 800; color: <?= $health_var ?>; font-size: 1.4em;"><?= $health_pct ?>% AVAILABLE NOW</span>
        </div>
        <div style="width: 100%; background: rgba(0,0,0,0.2); border-radius: 10px; height: 12px; overflow: hidden; box-shadow: inset 0 1px 3px rgba(0,0,0,0.5);" role="img" aria-label="Factory uptime <?= $health_pct ?> percent">
            <div style="width: <?= $health_pct ?>%; background: <?= $health_var ?>; height: 100%; transition: width 1.5s ease-in-out;"></div>
        </div>
        <div class="health-meta">
            <span><?= $total_machines - $down_machines ?> of <?= $total_machines ?> machines are currently operational</span>
            <span style="display: flex; gap: 10px; flex-wrap: wrap;">
                <span class="health-chip" style="background: var(--success-bg); border-color: var(--success-border); color: var(--success);">
                    🛡️ MTBF MTD: <strong><?= $op_kpis['mtbf'] === null ? '—' : $op_kpis['mtbf'] . 'h' ?></strong>
                </span>
                <span class="health-chip" style="background: var(--warning-bg); border-color: var(--warning-border); color: var(--warning);">
                    🔧 Repair Labour: <strong><?= $op_kpis['labour'] ?>m</strong>
                </span>
            </span>
        </div>
    </div>

    <div class="table-wrap">
    <table class="data-table table-cards" style="width: 100%;">
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
                        <td data-label="Ticket" style="font-weight: 600; color: var(--text-accent);">
                            <span class="row-arrow">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </span>
                            <?= htmlspecialchars($ticket['ticket_id']) ?>
                        </td>

                        <td data-label="Equipment">
                            <div style="font-weight: 700; color: var(--text-accent); font-size: 1.1em;">
                                <?= htmlspecialchars($ticket['equip_id']) ?>
                                <?php if($isRepeat): ?>
                                    <span class="repeat-chip">⚠️ Repeat Offender</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: var(--fs-sm); color: var(--text-secondary); margin-top: 2px; font-weight: 500;"><?= htmlspecialchars($ticket['equip_name'] ?? 'Unknown Machine') ?></div>
                        </td>

                        <td data-label="Priority"><span class="prio-badge <?= $badgeClass ?>"><?= $dot ?> <?= $prio ?></span></td>

                        <td data-label="Status">
                            <?php
                            $class = ($stat=='OPEN') ? 'status-open' : (($stat=='ESCALATED') ? 'status-escalated' : (($stat=='HOLD') ? 'status-escalated' : 'status-pending'));
                            if ($stat == 'HOLD') {
                                echo "<span class='$class hold-chip'>ON HOLD</span>";
                            } else {
                                echo "<span class='$class'>$stat</span>";
                            }

                            // The IDLE Warning Badge
                            if ($isIdle) {
                                echo "<br><span class='idle-chip'>⏳ IDLE > 45m</span>";
                            }
                            ?>
                        </td>

                        <td data-label="Ongoing">
                            <div style="color: var(--text-secondary); font-size: var(--fs-sm);">Form Date: <?= htmlspecialchars($ticket['report_time']) ?></div>
                            <span class="live-timer" data-start="<?= $safe_timestamp ?>">Calculating...</span>
                        </td>
                        <td data-label="Announced By"><?= htmlspecialchars($ticket['announced_by']) ?></td>

                        <td data-label="PIC">
                            <div style="font-weight: 700; color: var(--text-accent);">👨‍🔧 <?= htmlspecialchars($ticket['pic'] ?? 'Unassigned') ?></div>
                        </td>

                        <td data-label="Action">
                            <?php if ($stat == 'OPEN' || $stat == 'ESCALATED'): ?>
                                <a href="/_maint/takeover.php?id=<?= urlencode($ticket['ticket_id']) ?>" class="action-btn btn-take">Takeover</a>
                            <?php elseif ($stat == 'PENDING' || $stat == 'IN_PROGRESS'): ?>
                                <a href="/_maint/closeout.php?id=<?= urlencode($ticket['ticket_id']) ?>" class="action-btn btn-close">Review/Close</a>
                                <button onclick="openHoldModal('<?= htmlspecialchars(addslashes($ticket['ticket_id'])) ?>'); event.stopPropagation();" class="action-btn" style="background: var(--warning-bg); color: var(--warning); border: 1px solid var(--warning-border); margin-top:5px; display:block; width:100%;">Put on Hold</button>
                            <?php elseif ($stat == 'HOLD'): ?>
                                <a href="/_maint/takeover.php?id=<?= urlencode($ticket['ticket_id']) ?>" class="action-btn btn-take">Resume Job</a>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr class="child-row">
                        <td colspan="12">
                            <div class="child-content">
                                <span style="font-weight: 800; color: var(--text-accent); font-size: var(--fs-sm); text-transform: uppercase;">Original Fault Description:</span><br>
                                <span style="font-size: 0.95em; line-height: 1.4; display: inline-block; margin-top: 5px; margin-bottom: 10px;"><?= nl2br(htmlspecialchars($ticket['fault_desc'])) ?></span>

                                <?php if (!empty($actions_by_ticket[$ticket['ticket_id']])): ?>
                                    <div style="border-top: 1px solid var(--panel-border); padding-top: 15px; margin-top: 5px;">
                                        <span style="font-weight: 800; color: var(--text-accent); font-size: var(--fs-sm); text-transform: uppercase; margin-bottom: 10px; display: block;">Intervention Timeline:</span>
                                        <div style="display: flex; flex-direction: column; gap: 10px;">
                                            <?php foreach($actions_by_ticket[$ticket['ticket_id']] as $idx => $act): ?>
                                                <div class="timeline-entry" style="border-left: 3px solid <?= ($act['escalated_to'] !== 'None' ? 'var(--status-escalated-text)' : 'var(--success)') ?>;">
                                                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; border-bottom: 1px solid var(--panel-border); padding-bottom: 5px; gap: var(--space-2); flex-wrap: wrap;">
                                                        <span style="font-weight: bold; color: var(--text-primary);">👨‍🔧 <?= htmlspecialchars($act['tech_name']) ?></span>
                                                        <span style="color: var(--text-secondary); font-size: var(--fs-sm); background: rgba(0,0,0,0.2); padding: 3px 8px; border-radius: 4px;">⏱️ <?= htmlspecialchars(date('M d, H:i', strtotime($act['action_start']))) ?> - <?= htmlspecialchars(date('H:i', strtotime($act['action_end']))) ?></span>
                                                    </div>

                                                    <div style="font-size: 0.95em; color: var(--text-secondary); margin-bottom: 5px;">
                                                        <strong style="color: var(--text-primary);">Action Taken:</strong> <?= nl2br(htmlspecialchars($act['action_taken'])) ?>
                                                    </div>

                                                    <?php if(!empty($act['parts_used']) && $act['parts_used'] !== 'None'): ?>
                                                        <div style="font-size: var(--fs-sm); color: var(--text-muted); margin-top: 5px; padding: 5px; background: rgba(0,0,0,0.15); border-radius: 4px; display: inline-block;">
                                                            <strong style="color: var(--text-secondary);">📦 Parts Used:</strong> <?= htmlspecialchars($act['parts_used']) ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if($act['escalated_to'] !== 'None'): ?>
                                                        <div style="font-size: var(--fs-sm); color: var(--status-escalated-text); margin-top: 8px; font-weight: bold; padding: 5px; background: var(--status-escalated-bg); border-radius: 4px; display: inline-block; border: 1px solid var(--status-escalated-border);">
                                                            ⚠️ Escalated to: <?= htmlspecialchars($act['escalated_to']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Relational Comments Feed -->
                                <div style="border-top: 1px solid var(--panel-border); padding-top: 15px; margin-top: 15px;">
                                    <span style="font-weight: 800; color: var(--text-accent); font-size: var(--fs-sm); text-transform: uppercase; margin-bottom: 10px; display: block;">💬 Live Comments Feed:</span>

                                    <div class="comments-container" id="commentsList_<?= htmlspecialchars($ticket['ticket_id']) ?>" style="max-height: 300px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; margin-bottom: 10px; padding-right: 5px;">
                                        <?php if (!empty($comments_by_ticket[$ticket['ticket_id']])): ?>
                                            <?php foreach($comments_by_ticket[$ticket['ticket_id']] as $cmt): ?>
                                                <div class="comment-bubble">
                                                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: var(--fs-sm);">
                                                        <strong style="color: var(--text-primary);"><?= htmlspecialchars($cmt['user_name']) ?></strong>
                                                        <span style="color: var(--text-secondary);"><?= htmlspecialchars(date('M d, H:i', strtotime($cmt['created_at']))) ?></span>
                                                    </div>
                                                    <div style="font-size: 0.95em; color: var(--text-primary);">
                                                        <?= nl2br(htmlspecialchars($cmt['comment_text'])) ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div style="font-size: var(--fs-sm); color: var(--text-muted); font-style: italic;">No comments yet.</div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Add Comment Form -->
                                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                                        <input type="text" id="commentInput_<?= htmlspecialchars($ticket['ticket_id']) ?>" placeholder="Type a comment..." aria-label="New comment for ticket <?= htmlspecialchars($ticket['ticket_id']) ?>" style="flex-grow: 1; padding: 10px; border-radius: var(--radius-sm); border: 1px solid var(--text-accent); background: var(--input-bg); color: var(--text-primary); box-sizing: border-box;" onkeypress="if(event.key === 'Enter') submitComment('<?= htmlspecialchars(addslashes($ticket['ticket_id'])) ?>')">
                                        <button onclick="submitComment('<?= htmlspecialchars(addslashes($ticket['ticket_id'])) ?>')" class="action-btn btn-take" style="margin: 0; padding: 0 20px;">Send</button>
                                    </div>
                                </div>

                            </div>
                        </td>
                    </tr>

                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="12"><div class="wcc-empty"><span class="empty-icon" aria-hidden="true">🎉</span><p>No active tickets right now!</p></div></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>

    <div class="page-header" style="margin-top: 50px; margin-bottom: 15px;">
        <h2 style="color: var(--text-accent); margin: 0; font-size: var(--fs-xl);">🛠️ Pending Work Orders</h2>
        <div>
            <a href="/_maint/pm_calendar.php" class="nav-btn primary">🗓️ Open PM Calendar</a>
        </div>
    </div>

    <div class="table-wrap">
    <table class="data-table table-cards">
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
                    if ($diff > 0) $rowStyle = "background: var(--danger-bg); border-left: 4px solid var(--red-500);";
                    elseif ($diff == 0) $rowStyle = "background: var(--warning-bg); border-left: 4px solid var(--amber-500);";
                ?>
                    <tr style="<?= $rowStyle ?>">
                        <td data-label="WO #" style="font-weight: 600; color: var(--text-accent);">
                            WO-<?= htmlspecialchars($wo['wo_id']) ?>
                            <?php if ($diff > 0): ?>
                                <br><span style="display:inline-block; margin-top:6px; font-size:var(--fs-xs); background: var(--danger-bg); color: var(--danger); padding: 3px 10px; border-radius: var(--radius-full); border: 1px solid var(--danger-border); font-weight: 800; letter-spacing: 0.5px;">OVERDUE</span>
                            <?php elseif ($diff == 0): ?>
                                <br><span style="display:inline-block; margin-top:6px; font-size:var(--fs-xs); background: var(--warning-bg); color: var(--warning); padding: 3px 10px; border-radius: var(--radius-full); border: 1px solid var(--warning-border); font-weight: 800; letter-spacing: 0.5px;">TODAY</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Title"><strong style="color: var(--text-primary);"><?= htmlspecialchars($wo['title']) ?></strong>
                            <?php
                                $wo_stat = $wo['status'];
                                $stat_col = $wo_stat === 'Missed' ? 'var(--danger)' : ($wo_stat === 'In Progress' ? 'var(--info, #38bdf8)' : 'var(--text-secondary)');
                            ?>
                            <br><span style="font-size:var(--fs-xs); color: <?= $stat_col ?>; font-weight:600;"><?= htmlspecialchars($wo_stat) ?></span>
                        </td>
                        <td data-label="Equipment"><?= htmlspecialchars($wo['equip_name'] ?? 'Unknown') ?></td>
                        <td data-label="Scheduled" style="font-weight: bold;">
                            <?php if ($diff > 0): ?>
                                <span style="color: var(--danger); margin-right: 5px;">⚠️</span>
                            <?php elseif ($diff == 0): ?>
                                <span style="color: var(--warning); margin-right: 5px;">⚠️</span>
                            <?php endif; ?>
                            <span style="<?= $diff > 0 ? 'color: var(--danger);' : ($diff == 0 ? 'color: var(--warning);' : '') ?>"><?= htmlspecialchars($wo['scheduled_date']) ?></span>
                        </td>
                        <td data-label="Assigned">👨‍🔧 <?= htmlspecialchars($wo['assigned_user'] ?? 'Unassigned') ?></td>
                        <td data-label="Action">
                            <a href="/_maint/wo_takeover.php?wo_id=<?= urlencode($wo['wo_id']) ?>" class="action-btn btn-take">Takeover</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6"><div class="wcc-empty"><span class="empty-icon" aria-hidden="true">📭</span><p>No active work orders pending.</p></div></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- HOLD MODAL -->
<div id="holdModal" class="wcc-modal" role="dialog" aria-modal="true" aria-labelledby="holdModalTitle">
    <div class="wcc-modal-content wcc-modal-sm">
        <div class="wcc-modal-header">
            <h3 id="holdModalTitle">Put Ticket on Hold</h3>
            <button type="button" class="wcc-close-btn" onclick="closeWccModal('holdModal')" aria-label="Close">&times;</button>
        </div>
        <input type="hidden" id="hold_ticket_id">
        <div class="field">
            <label for="hold_reason">Reason for Hold:</label>
            <select id="hold_reason">
                <option value="Waiting for Parts">Waiting for Parts</option>
                <option value="Waiting for External Vendor">Waiting for External Vendor</option>
                <option value="Waiting for Production Clearance">Waiting for Production Clearance</option>
                <option value="End of Shift / Handover">End of Shift / Handover</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div class="field">
            <label for="hold_explanation">Explanation / Comments:</label>
            <textarea id="hold_explanation" rows="3" placeholder="Provide details..."></textarea>
        </div>
        <div class="wcc-modal-footer">
            <button type="button" class="btn" onclick="closeWccModal('holdModal')">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitHold()">Confirm HOLD Status</button>
        </div>
    </div>
</div>

<script>
    function openHoldModal(ticketId) {
        document.getElementById('hold_ticket_id').value = ticketId;
        openWccModal('holdModal');
    }

    async function submitHold() {
        const ticketId = document.getElementById('hold_ticket_id').value;
        const reason = document.getElementById('hold_reason').value;
        const explanation = document.getElementById('hold_explanation').value;

        if(!explanation) { showToast('Please provide an explanation.', 'warning'); return; }

        const payload = {
            ticket_id: ticketId,
            reason: reason,
            explanation: explanation
        };

        try {
            const res = await fetch('/api/submit_hold.php', {
                method: 'POST',
                headers: wccJsonHeaders(),
                body: JSON.stringify(wccWithCsrf(payload))
            });
            const data = await res.json();
            if (data.status === 'success') {
                window.location.reload();
            } else {
                showToast('Error: ' + data.message, 'error');
            }
        } catch (e) {
            showToast('Network error.', 'error');
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
</script>
</body>
</html>
