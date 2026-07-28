<?php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('closeout_tickets');

if (!isset($_GET['id'])) { header("Location: ../index.php"); exit; }
$ticket_id = $_GET['id'];

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    $stmt = $pdo->prepare("SELECT * FROM active_tickets WHERE ticket_id = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$ticket) { die("Ticket not found!"); }
    
    $stmtAction = $pdo->prepare("SELECT * FROM ticket_actions WHERE ticket_id = ? ORDER BY action_start ASC");
    $stmtAction->execute([$ticket_id]);
    $actions = $stmtAction->fetchAll(PDO::FETCH_ASSOC);
    
    $stmtCmt = $pdo->prepare("SELECT * FROM ticket_comments WHERE ticket_id = ? ORDER BY created_at ASC");
    $stmtCmt->execute([$ticket_id]);
    $comments = $stmtCmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { wcc_user_error("Could not load the ticket for closeout.", $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Supervisor Closeout</title>
</head>
<body>
<?php include __DIR__ . '/../nav.php'; ?>

<div class="form-container">
    <div class="header-flex"><h2>Final Review & Close</h2><a href="../index.php" class="nav-btn">🔙 Cancel</a></div>
    <div class="ticket-info">
        <strong>Ticket ID:</strong> <?= htmlspecialchars($ticket['ticket_id']) ?> | <strong>Equip:</strong> <?= htmlspecialchars($ticket['equip_id']) ?><br>
        <span style="color:#b91c1c;">Original Issue: <?= htmlspecialchars($ticket['fault_desc']) ?></span>
    </div>
    
    <h4 style="margin-top: 25px; margin-bottom: 15px; color: var(--text-accent);">Intervention Timeline:</h4>
    <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 25px;">
        <?php foreach($actions as $idx => $act): ?>
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

    <h4 style="margin-top: 5px; margin-bottom: 15px; color: var(--text-accent);">💬 Live Comments Archive:</h4>
    <div style="max-height: 300px; overflow-y: auto; padding-right: 5px; display: flex; flex-direction: column; gap: 8px; margin-bottom: 25px;">
        <?php if (!empty($comments)): ?>
            <?php foreach($comments as $cmt): ?>
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
            <div style="font-size: 0.9em; color: var(--text-secondary); font-style: italic;">No comments recorded.</div>
        <?php endif; ?>
    </div>

    <input type="hidden" id="ticket_id" value="<?= htmlspecialchars($ticket['ticket_id']) ?>">
    <label>Supervisor Signature (Name):</label>
    <input type="text" id="supervisor_name" value="<?= htmlspecialchars($_SESSION['username'] ?? 'Unknown User') ?>" readonly style="background: rgba(0,0,0,0.2); cursor: not-allowed; color: #94a3b8; width: 100%; padding: 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); margin-bottom: 15px; box-sizing: border-box;">
    <button id="closeBtn" class="btn primary" style="width:100%; margin-top:20px; font-size:1.1em; padding:15px;" onclick="closeTicket()">Confirm & Archive Ticket</button>
    <div id="successMsg" class="success-msg"></div>
</div>
<script>
    async function closeTicket() {
        if(!document.getElementById('supervisor_name').value) { openWccAlert('Validation Error', "Please sign off!"); return; }
        
        // QoL UPDATE: Lock button
        const btn = document.getElementById('closeBtn');
        btn.disabled = true;
        btn.innerText = "Archiving... ⏳";

        const payload = { ticket_id: document.getElementById('ticket_id').value, supervisor: document.getElementById('supervisor_name').value };
        try {
            const response = await fetch('/api/submit_closeout.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            const result = await response.json();
            if(result.status === 'success') {
                openWccAlert('Success', "Ticket Closed and Sent to History!", '/_rpt/history.php');
            } else { 
                openWccAlert('Error', result.message); 
                btn.disabled = false; btn.innerText = "Confirm & Archive Ticket";
            }
        } catch (error) { 
            openWccAlert('Error', "Error: " + error.message); 
            btn.disabled = false; btn.innerText = "Confirm & Archive Ticket";
        }
    }
    
    // JS window.onload and dropdown logic removed as it is no longer needed
</script>
</body>
</html>
