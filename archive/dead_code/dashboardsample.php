<?php
$host = 'localhost'; $db = 'workshop_db'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    
    // Query includes the 48-hour Repeat Offender sub-query and Equipment Join
    $stmt = $pdo->query("
        SELECT a.*, e.equip_name,
        (SELECT COUNT(*) FROM active_tickets t2 WHERE t2.equip_id = a.equip_id AND t2.created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)) as recent_count
        FROM active_tickets a 
        LEFT JOIN equipment e ON a.equip_id = e.equip_id 
        WHERE a.status IN ('OPEN', 'ESCALATED', 'PENDING') 
        ORDER BY a.created_at DESC
    ");
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ================= FACTORY HEALTH CALCULATION =================
    // 1. Get total number of machines in the factory
    $stmtEq = $pdo->query("SELECT COUNT(*) FROM equipment");
    $total_machines = (int)$stmtEq->fetchColumn();
    
    // 2. Count how many unique machines are currently broken (on the dashboard)
    $down_machines = count(array_unique(array_column($tickets, 'equip_id')));
    
    // 3. Calculate percentage and assign dynamic color
    $health_pct = $total_machines > 0 ? round((($total_machines - $down_machines) / $total_machines) * 100, 1) : 100;
    $health_color = $health_pct >= 90 ? '#10b981' : ($health_pct >= 75 ? '#f59e0b' : '#ef4444');
    // ==============================================================

} catch (PDOException $e) { die("DB Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Active Tickets</title>
    <style>
        /* Body padding increased to 130px at the top to clear the industrial timer */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #e0e7ff, #BDC2FF, #f1f5f9, #e0e7ff); background-size: 200% 200%; animation: gradientShift 12s ease infinite; padding: 130px 20px 40px 20px; margin: 0; min-height: 100vh; color: #0f172a; }
        @keyframes gradientShift { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        
        /* ==========================================
           THE INDUSTRIAL BLOCK TRACKER CSS (From Hub)
           ========================================== */
        #industrialTimer {
            position: fixed; 
            top: 20px; 
            left: 50%; 
            transform: translateX(-50%);
            background: rgba(30, 58, 138, 0.25); 
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.25); 
            border-top: 1px solid rgba(255, 255, 255, 0.4); 
            box-shadow: 0 10px 30px rgba(30, 58, 138, 0.15); 
            width: 90%; 
            max-width: 800px; 
            box-sizing: border-box;
            padding: 15px 25px; 
            border-radius: 18px; 
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        #timerLabel { color: rgba(255, 255, 255, 0.9); font-size: 0.75em; font-weight: 800; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 12px; width: 100%; text-align: left; text-shadow: 0 2px 4px rgba(0,0,0,0.3); }
        #blockContainer { display: flex; gap: 6px; width: 100%; }
        .time-block { flex: 1; height: 24px; border-radius: 4px; background: transparent; box-shadow: none; transition: background 0.3s ease, box-shadow 0.3s ease; }

        /* ==========================================
           DASHBOARD & TABLE CSS
           ========================================== */
        .dashboard-container { background: linear-gradient(135deg, rgba(255, 255, 255, 0.6), rgba(189, 194, 255, 0.3)); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.6); padding: 30px 40px; border-radius: 24px; box-shadow: 0 10px 30px 0 rgba(148, 163, 184, 0.2); max-width: 1400px; margin: auto; overflow-x: auto; }
        .header-flex { display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.5); padding-bottom: 15px; }
        h2 { color: #1e3a8a; margin: 0; font-size: 2em; }
        .nav-btn { background: rgba(255,255,255,0.6); padding: 10px 18px; text-decoration: none; border-radius: 12px; font-weight: 600; border: 1px solid rgba(255,255,255,0.8); transition: 0.2s; color: #1e3a8a; }
        .nav-btn.primary { background: #1e3a8a; color: white; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 15px; text-align: left; vertical-align: middle; }
        th { background: rgba(255, 255, 255, 0.4); color: #334155; font-size: 0.85em; text-transform: uppercase; border-bottom: 1px solid rgba(255,255,255,0.4); }
        tr { border-left: 4px solid transparent; }
        
        .parent-row { cursor: pointer; transition: background 0.2s; border-bottom: 1px solid rgba(255,255,255,0.2); }
        .parent-row:hover { background: rgba(255, 255, 255, 0.2); }
        .child-row { display: none; background: rgba(255, 255, 255, 0.1); }
        .child-content { padding: 15px 25px; margin: 10px 15px; background: rgba(255, 255, 255, 0.7); border-radius: 12px; border-left: 4px solid #1e3a8a; box-shadow: inset 0 2px 5px rgba(0,0,0,0.05); color: #475569; }

        /* Status & Priority Colors */
        @keyframes criticalPulse { 0% { background: rgba(239, 68, 68, 0.05); box-shadow: inset 0 0 0 rgba(239, 68, 68, 0); } 50% { background: rgba(239, 68, 68, 0.2); box-shadow: inset 0 0 15px rgba(239, 68, 68, 0.3); } 100% { background: rgba(239, 68, 68, 0.05); box-shadow: inset 0 0 0 rgba(239, 68, 68, 0); } }
        @keyframes orangePulse { 0% { background: rgba(234, 88, 12, 0.04); box-shadow: inset 0 0 0 rgba(234, 88, 12, 0); } 50% { background: rgba(234, 88, 12, 0.15); box-shadow: inset 0 0 15px rgba(234, 88, 12, 0.3); border-left-color: #ea580c; } 100% { background: rgba(234, 88, 12, 0.04); box-shadow: inset 0 0 0 rgba(234, 88, 12, 0); } }
        @keyframes criticalRepeatPulse { 0% { background: rgba(239, 68, 68, 0.1); border-left-color: #ef4444; box-shadow: inset 0 0 15px rgba(239, 68, 68, 0.2); } 50% { background: rgba(234, 88, 12, 0.2); border-left-color: #ea580c; box-shadow: inset 0 0 25px rgba(234, 88, 12, 0.5); } 100% { background: rgba(239, 68, 68, 0.1); border-left-color: #ef4444; box-shadow: inset 0 0 15px rgba(239, 68, 68, 0.2); } }
        @keyframes purplePulse { 0% { background: rgba(168, 85, 247, 0.05); box-shadow: inset 0 0 0 rgba(168, 85, 247, 0); } 50% { background: rgba(147, 51, 234, 0.15); border-left-color: #7e22ce; box-shadow: inset 0 0 20px rgba(147, 51, 234, 0.3); } 100% { background: rgba(168, 85, 247, 0.05); box-shadow: inset 0 0 0 rgba(168, 85, 247, 0); } }
        
        tr.priority-critical { border-left-color: #ef4444; animation: criticalPulse 1.5s infinite ease-in-out; }
        tr.priority-high { border-left-color: #ea580c; background: rgba(234, 88, 12, 0.04); }
        tr.priority-normal { border-left-color: #3b82f6; background: transparent; }
        tr.priority-low { border-left-color: #22c55e; background: rgba(34, 197, 94, 0.04); }
        tr.priority-repeat { animation: orangePulse 1.5s infinite ease-in-out; border-left-color: #ea580c; }
        tr.priority-critical-repeat { animation: criticalRepeatPulse 1s infinite ease-in-out; }
        tr.priority-idle { animation: purplePulse 1.5s infinite ease-in-out !important; border-left-color: #a855f7 !important; }
        
        .badge-critical { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
        .badge-high { background: #ffedd5; color: #c2410c; border: 1px solid #fdba74; }
        .badge-normal { background: #dbeafe; color: #1d4ed8; border: 1px solid #93c5fd; }
        .badge-low { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .prio-badge { display: inline-block; padding: 4px 10px; border-radius: 8px; font-weight: 700; font-size: 0.75em; text-transform: uppercase; }

        .status-open { display: inline-block; padding: 4px 10px; background: rgba(239, 68, 68, 0.15); color: #b91c1c; border-radius: 20px; font-weight: bold; font-size: 0.85em; border: 1px solid rgba(239, 68, 68, 0.3); }
        .status-escalated { display: inline-block; padding: 4px 10px; background: rgba(234, 88, 12, 0.15); color: #c2410c; border-radius: 20px; font-weight: bold; font-size: 0.85em; border: 1px solid rgba(234, 88, 12, 0.3); }
        .status-pending { display: inline-block; padding: 4px 10px; background: rgba(245, 158, 11, 0.15); color: #b45309; border-radius: 20px; font-weight: bold; font-size: 0.85em; border: 1px solid rgba(245, 158, 11, 0.3); }
        
        .live-timer { display: block; margin-top: 5px; font-family: monospace; font-size: 1.1em; color: #b91c1c; font-weight: bold; background: rgba(255,255,255,0.5); padding: 4px 8px; border-radius: 6px; width: fit-content; }
        
        .action-btn { border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 0.85em; text-decoration: none; transition: 0.2s; cursor: pointer; display: inline-block; }
        .btn-take { background: #1e3a8a; color: white; }
        .btn-take:hover { background: #1e40af; transform: translateY(-2px); }
        .btn-close { background: #047857; color: white; }
        
    </style>
</head>
<body>

    <div id="industrialTimer">
        <div id="timerLabel">//SYS.LIFESPAN//</div>
        <div id="blockContainer"></div>
    </div>

<div class="dashboard-container">
    <div class="header-flex">
        <h2>Active Tickets</h2>
        <div style="display:flex; gap:10px;"><a href="index.html" class="nav-btn">🏠 Menu</a><a href="register.php" class="nav-btn primary">+ New Ticket</a></div>
    </div>

    <div style="background: rgba(255,255,255,0.6); border-radius: 16px; padding: 20px; margin-bottom: 25px; border: 1px solid rgba(255,255,255,0.8); box-shadow: inset 0 2px 5px rgba(0,0,0,0.05);">
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; align-items: flex-end;">
            <span style="font-weight: 800; color: #1e3a8a; font-size: 1.1em; letter-spacing: 1px;">🏭 FACTORY HEALTH</span>
            <span style="font-weight: 800; color: <?= $health_color ?>; font-size: 1.4em;"><?= $health_pct ?>% UPTIME</span>
        </div>
        <div style="width: 100%; background: #cbd5e1; border-radius: 10px; height: 12px; overflow: hidden; box-shadow: inset 0 1px 3px rgba(0,0,0,0.2);">
            <div style="width: <?= $health_pct ?>%; background: <?= $health_color ?>; height: 100%; transition: width 1.5s ease-in-out;"></div>
        </div>
        <div style="font-size: 0.85em; color: #64748b; margin-top: 8px; font-weight: 600;">
            <?= $total_machines - $down_machines ?> of <?= $total_machines ?> machines are currently operational
        </div>
    </div>

    <table>
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
                    <tr class="<?= $rowClass ?> parent-row" onclick="toggleFault('<?= htmlspecialchars($ticket['ticket_id']) ?>')">
                        <td style="font-weight: 600; color: #1e3a8a;">➕ <?= htmlspecialchars($ticket['ticket_id']) ?></td>
                        
                        <td>
                            <div style="font-weight: 700; color: #1e3a8a; font-size: 1.1em;">
                                <?= htmlspecialchars($ticket['equip_id']) ?>
                                <?php if($isRepeat): ?>
                                    <span style="font-size: 0.7em; background: #ffedd5; color: #ea580c; padding: 2px 6px; border-radius: 6px; margin-left: 5px; vertical-align: top; border: 1px solid #fdba74;">⚠️ Repeat Offender</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 0.85em; color: #64748b; margin-top: 2px; font-weight: 500;"><?= htmlspecialchars($ticket['equip_name'] ?? 'Unknown Machine') ?></div>
                        </td>

                        <td><span class="prio-badge <?= $badgeClass ?>"><?= $dot ?> <?= $prio ?></span></td>
                        
                        <td>
                            <?php 
                            $class = ($stat=='OPEN') ? 'status-open' : (($stat=='ESCALATED') ? 'status-escalated' : 'status-pending');
                            echo "<span class='$class'>$stat</span>"; 
                            
                            // The IDLE Warning Badge
                            if ($isIdle) {
                                echo "<br><span style='display:inline-block; margin-top:6px; font-size:0.8em; background:#f3e8ff; color:#7e22ce; padding:3px 8px; border-radius:6px; border:1px solid #d8b4fe; font-weight:bold; box-shadow: 0 2px 4px rgba(168, 85, 247, 0.2);'>⏳ IDLE > 45m</span>";
                            }
                            ?>
                        </td>

                        <td>
                            <div style="color: #64748b; font-size: 0.85em;">Form Date: <?= htmlspecialchars($ticket['report_time']) ?></div>
                            <span class="live-timer" data-start="<?= $safe_timestamp ?>">Calculating...</span>
                        </td>
                        <td><?= htmlspecialchars($ticket['announced_by']) ?></td>
                        
                        <td>
                            <div style="font-weight: 700; color: #1e3a8a; font-size: 1.05em;">👨‍🔧 <?= htmlspecialchars($ticket['pic'] ?? 'Unassigned') ?></div>
                        </td>
                        
                        <td>
                            <?php if ($stat == 'OPEN' || $stat == 'ESCALATED'): ?>
                                <a href="takeover.php?id=<?= urlencode($ticket['ticket_id']) ?>" class="action-btn btn-take" onclick="event.stopPropagation();">Takeover</a>
                            <?php else: ?>
                                <a href="closeout.php?id=<?= urlencode($ticket['ticket_id']) ?>" class="action-btn btn-close" onclick="event.stopPropagation();">Review/Close</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <tr class="child-row" id="fault-<?= htmlspecialchars($ticket['ticket_id']) ?>">
                        <td colspan="8" style="padding: 0;">
                            <div class="child-content">
                                <span style="font-weight: 800; color: #1e3a8a; font-size: 0.9em; text-transform: uppercase;">Original Fault Description:</span><br> 
                                <span style="font-size: 0.95em; line-height: 1.4; display: inline-block; margin-top: 5px;"><?= nl2br(htmlspecialchars($ticket['fault_desc'])) ?></span>
                            </div>
                        </td>
                    </tr>

                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" style="text-align: center; padding: 40px; color: #64748b;">No active tickets right now! 🎉</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="timer.js"></script>
<script>
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

    function toggleFault(ticketId) {
        const childRow = document.getElementById('fault-' + ticketId);
        if (childRow.style.display === 'table-row') {
            childRow.style.display = 'none';
        } else {
            childRow.style.display = 'table-row';
        }
    }

    window.onload = function() {
        updateTimers();
        setInterval(updateTimers, 1000);
    };
</script>

</body>
</html>
