<?php
$php = <<<'EOD'
<?php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('closeout_tickets');

// Enterprise centralized DB (Phase 1 complete)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

if (!isset($_GET['id'])) { header("Location: ../index.php"); exit; }
$ticket_id = $_GET['id'];
try {
    $stmt = $pdo->prepare("SELECT * FROM active_tickets WHERE ticket_id = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$ticket) { die("Ticket not found!"); }
    
    $stmtAction = $pdo->prepare("SELECT * FROM ticket_actions WHERE ticket_id = ? ORDER BY action_start ASC");
    $stmtAction->execute([$ticket_id]);
    $actions = $stmtAction->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { die("DB Error"); }
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
    
    <h4>Technician Timeline:</h4>
    <?php foreach($actions as $act): ?>
        <div class="action-card">
            <strong><?= htmlspecialchars($act['tech_name']) ?></strong> (<?= htmlspecialchars($act['action_start']) ?> to <?= htmlspecialchars($act['action_end']) ?>)<br>
            <em>Fix:</em> <?= htmlspecialchars($act['action_taken']) ?> <br>
            <em>Parts:</em> <?= htmlspecialchars($act['parts_used']) ?>
        </div>
    <?php endforeach; ?>

    <input type="hidden" id="ticket_id" value="<?= htmlspecialchars($ticket['ticket_id']) ?>">
    <label>Supervisor Signature (Name):</label>
    <select id="supervisor_name" required>
        <option value="">Loading...</option>
    </select>
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
    
    window.onload = async function() {
        await loadTeamMembers('production', 'supervisor_name');
    };

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
</script>
</body>
</html>
EOD;
file_put_contents('c:\xampp\htdocs\closeout.php', $php);
?>

