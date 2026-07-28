<?php
include 'auth.php';

if (!isset($_GET['id'])) { header("Location: index.php"); exit; }
$ticket_id = $_GET['id'];
$host = 'localhost'; $db = 'workshop_db'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $stmt = $pdo->prepare("SELECT * FROM active_tickets WHERE ticket_id = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$ticket) { die("Ticket not found!"); }
} catch (PDOException $e) { die("DB Error"); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Technician Takeover</title>
    
</head>
<body>
<?php include 'nav.php'; ?>


<div class="form-container">
    <div class="header-flex"><h2>Log Intervention</h2><a href="index.php" class="nav-btn">🔙 Cancel</a></div>
    <div class="ticket-info">
        <div><span>Ticket ID:</span> <?= htmlspecialchars($ticket['ticket_id']) ?></div>
        <div><span>Equipment:</span> <?= htmlspecialchars($ticket['equip_id']) ?></div>
        <div style="margin-top:5px; color:#b91c1c; font-weight:600;">Issue: <?= htmlspecialchars($ticket['fault_desc']) ?></div>
    </div>
    <input type="hidden" id="ticket_id" value="<?= htmlspecialchars($ticket['ticket_id']) ?>">
    
    <label>Technician Name:</label>
    <select id="tech_name" required>
        <option value="">Loading...</option>
    </select>
    
    <div class="grid-2">
        <div><label>Start Time:</label><input type="datetime-local" id="action_start" required></div>
        <div><label>End Time:</label><input type="datetime-local" id="action_end" required></div>
    </div>

    <div class="grid-2">
        <div>
            <label>Fault Type:</label>
            <select id="fault_type" required>
                <option value="">-- Select --</option>
                <option value="Mechanical">Mechanical</option>
                <option value="Electrical">Electrical</option>
                <option value="Pneumatic/Hydraulic">Pneumatic/Hydraulic</option>
                <option value="Software/Controls">Software/Controls</option>
                <option value="Tooling/Fixture">Tooling/Fixture</option>
                <option value="Operator Error">Operator Error</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div>
            <label>Escalate To (Name):</label>
    <select id="escalated_to">
        <option value="None">Loading...</option>
    </select>
        </div>
    </div>

    <label>Root Cause of Breakdown:</label>
    <input type="text" id="root_cause" required placeholder="Why did it break?">
    
    <label>Action Taken:</label>
    <textarea id="action_taken" rows="2" required placeholder="What exactly did you do to fix it?"></textarea>
    
    <label>Parts Used (Optional):</label>
    <input type="text" id="parts_used" placeholder="Leave blank if no parts were used">

    <div class="btn-group">
        <button id="btnEscalate" class="btn-escalate" onclick="submitTakeover('escalate')">⚠️ Save & Escalate</button>
        <button id="btnFinish" class="btn-finish" onclick="submitTakeover('finish')">✅ Finish Job</button>
    </div>
    <div id="successMsg" class="success-msg"></div>
</div>

<script>
    const now = new Date(); now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('action_start').value = now.toISOString().slice(0,16); document.getElementById('action_end').value = now.toISOString().slice(0,16);

    async function submitTakeover(actionType) {
        const escalatedValue = document.getElementById('escalated_to').value.trim();
        
        const payload = {
            ticket_id: document.getElementById('ticket_id').value,
            tech_name: document.getElementById('tech_name').value,
            action_start: document.getElementById('action_start').value.replace('T', ' ') + ':00',
            action_end: document.getElementById('action_end').value.replace('T', ' ') + ':00',
            fault_type: document.getElementById('fault_type').value,
            root_cause: document.getElementById('root_cause').value,
            action_taken: document.getElementById('action_taken').value,
            parts_used: document.getElementById('parts_used').value || 'None',
            escalated_to: escalatedValue || 'None',
            action_type: actionType 
        };

        if(!payload.tech_name || !payload.fault_type || !payload.root_cause || !payload.action_taken) { 
            alert("Fill all required fields!"); return; 
        }
        
        if(actionType === 'escalate' && (!escalatedValue || escalatedValue === 'None')) { 
            alert("Please enter the name of the person you are escalating this to!"); return; 
        }

        // QoL UPDATE: Lock BOTH buttons to prevent double submission
        const btnEscalate = document.getElementById('btnEscalate');
        const btnFinish = document.getElementById('btnFinish');
        btnEscalate.disabled = true;
        btnFinish.disabled = true;
        
        if (actionType === 'escalate') { btnEscalate.innerText = "Escalating... ⏳"; } 
        else { btnFinish.innerText = "Saving... ⏳"; }

        try {
            const response = await fetch('/api/submit_takeover.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            const result = await response.json();
            if(result.status === 'success') {
                const msgBox = document.getElementById('successMsg'); msgBox.style.display = 'block'; msgBox.innerText = result.message;
                setTimeout(() => { window.location.href = 'index.php'; }, 1500);
            } else { 
                alert(result.message); 
                btnEscalate.disabled = false; btnFinish.disabled = false;
                btnEscalate.innerText = "⚠️ Save & Escalate"; btnFinish.innerText = "✅ Finish Job";
            }
        } catch (error) { 
            alert("Error: " + error.message); 
            btnEscalate.disabled = false; btnFinish.disabled = false;
            btnEscalate.innerText = "⚠️ Save & Escalate"; btnFinish.innerText = "✅ Finish Job";
        }
    }
window.onload = async function() {
        await loadTeamMembers('technical', 'tech_name');
        await loadTeamMembers('technical', 'escalated_to');
    };

    async function loadTeamMembers(role, elementId) {
        try {
            const response = await fetch('api/get_team.php?role=' + role);
            const result = await response.json();
            const dropdown = document.getElementById(elementId);
            
            // Set the default top option based on the field
            if (elementId === 'escalated_to') {
                dropdown.innerHTML = '<option value="None">-- No Escalation --</option>';
            } else {
                dropdown.innerHTML = '<option value="">-- Select --</option>';
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
