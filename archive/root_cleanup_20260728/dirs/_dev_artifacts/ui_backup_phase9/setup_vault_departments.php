<?php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('manage_equipment');

// Enterprise centralized DB connection (Phase 1 fix)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    // Handle Delete Department
    if (isset($_GET['delete_id'])) {
        $stmt = $pdo->prepare("DELETE FROM departments WHERE dept_id = ?");
        $stmt->execute([$_GET['delete_id']]);
        header("Location: setup_vault_departments.php");
        exit;
    }

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add_dept') {
            $name = $_POST['dept_name'] ?? '';
            $initial = (float)($_POST['initial_budget'] ?? 0);
            
            $stmt = $pdo->prepare("INSERT INTO departments (dept_name, budget_allocated, budget_consumed) VALUES (?, ?, 0)");
            $stmt->execute([$name, $initial]);
            $dept_id = $pdo->lastInsertId();
            
            if ($initial > 0) {
                $log = $pdo->prepare("INSERT INTO department_budget_logs (dept_id, action_type, amount, notes, changed_by) VALUES (?, 'Allocate', ?, 'Initial Allocation', ?)");
                $log->execute([$dept_id, $initial, $_SESSION['user_id']]);
            }
        } 
        elseif ($action === 'modify_budget') {
            $dept_id = (int)$_POST['dept_id'];
            $mod_action = $_POST['mod_action']; // Allocate, Revoke, Relocate
            $amount = (float)$_POST['amount'];
            $notes = $_POST['notes'] ?? '';
            
            if ($mod_action === 'Allocate') {
                $pdo->prepare("UPDATE departments SET budget_allocated = budget_allocated + ? WHERE dept_id = ?")->execute([$amount, $dept_id]);
                $pdo->prepare("INSERT INTO department_budget_logs (dept_id, action_type, amount, notes, changed_by) VALUES (?, 'Allocate', ?, ?, ?)")->execute([$dept_id, $amount, $notes, $_SESSION['user_id']]);
            } 
            elseif ($mod_action === 'Revoke') {
                $pdo->prepare("UPDATE departments SET budget_allocated = budget_allocated - ? WHERE dept_id = ?")->execute([$amount, $dept_id]);
                $pdo->prepare("INSERT INTO department_budget_logs (dept_id, action_type, amount, notes, changed_by) VALUES (?, 'Revoke', ?, ?, ?)")->execute([$dept_id, $amount, $notes, $_SESSION['user_id']]);
            }
            elseif ($mod_action === 'Relocate') {
                $target_dept_id = (int)$_POST['target_dept_id'];
                if ($target_dept_id && $target_dept_id !== $dept_id) {
                    // Deduct from source
                    $pdo->prepare("UPDATE departments SET budget_allocated = budget_allocated - ? WHERE dept_id = ?")->execute([$amount, $dept_id]);
                    $pdo->prepare("INSERT INTO department_budget_logs (dept_id, action_type, amount, notes, changed_by) VALUES (?, 'Relocate (Out)', ?, ?, ?)")->execute([$dept_id, $amount, "Transferred to Dept ID: $target_dept_id. $notes", $_SESSION['user_id']]);
                    
                    // Add to target
                    $pdo->prepare("UPDATE departments SET budget_allocated = budget_allocated + ? WHERE dept_id = ?")->execute([$amount, $target_dept_id]);
                    $pdo->prepare("INSERT INTO department_budget_logs (dept_id, action_type, amount, notes, changed_by) VALUES (?, 'Relocate (In)', ?, ?, ?)")->execute([$target_dept_id, $amount, "Received from Dept ID: $dept_id. $notes", $_SESSION['user_id']]);
                }
            }
        }
        
        header("Location: /_mgmt/setup_vault_departments.php");
        exit;
    }

    $stmt = $pdo->query("SELECT * FROM departments ORDER BY dept_name ASC");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all logs for the history modal (to inject via JS)
    $logs_stmt = $pdo->query("
        SELECT l.log_id, l.dept_id, l.action_type, l.amount, l.notes, l.created_at, d.dept_name, u.username 
        FROM department_budget_logs l 
        LEFT JOIN departments d ON l.dept_id = d.dept_id 
        LEFT JOIN users u ON l.changed_by = u.user_id 
    ");
    $all_logs = $logs_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch POs linked to departments to act as expenditures
    $po_stmt = $pdo->query("
        SELECT p.po_id as log_id, p.dept_id, 'PO Expenditure' as action_type, p.total_amount as amount, 
               CONCAT('PO #', p.po_number, ' - ', v.vendor_name, ' (Status: ', p.status, ')') as notes, 
               p.created_at, u.username, d.dept_name
        FROM purchase_orders p
        LEFT JOIN departments d ON p.dept_id = d.dept_id
        LEFT JOIN users u ON p.created_by = u.user_id
        LEFT JOIN vendors_suppliers v ON p.vendor_id = v.vendor_id
        WHERE p.dept_id IS NOT NULL AND p.status != 'Cancelled'
    ");
    $all_pos = $po_stmt->fetchAll(PDO::FETCH_ASSOC);

    $all_transactions = array_merge($all_logs, $all_pos);
    usort($all_transactions, function($a, $b) {
        return strtotime($b['created_at']) <=> strtotime($a['created_at']);
    });

    $logs_by_dept = [];
    foreach($all_transactions as $log) {
        $logs_by_dept[$log['dept_id']][] = $log;
    }

} catch (PDOException $e) { die("DB Error: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Budget Vault Management</title>
    <style>
        .vault-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .dept-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--panel-border);
            padding: 20px; border-radius: 12px;
            display: flex; flex-direction: column; gap: 10px;
        }
        .dept-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }
        .dept-title { color: var(--text-accent); font-size: 1.2em; font-weight: bold; margin: 0; }
        .stat-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-top: 10px; }
        .stat-box { background: rgba(0,0,0,0.2); padding: 10px; border-radius: 8px; text-align: center; }
        .stat-label { font-size: 0.8em; color: var(--text-secondary); text-transform: uppercase; }
        .stat-val { font-size: 1.1em; color: var(--text-primary); font-family: monospace; margin-top: 5px; font-weight: bold; }
        .actions-flex { display: flex; gap: 10px; margin-top: 15px; }
    </style>
    <script>
        const logsData = <?= json_encode($logs_by_dept) ?>;

        function toggleLedger(deptId, deptName) {
            const container = document.getElementById('ledgerContainer');
            const tbody = document.getElementById('ledgerTableBody');
            const title = document.getElementById('ledgerTitle');
            
            // If already open for this dept, close it
            if (container.style.display === 'block' && container.dataset.deptId == deptId) {
                container.style.display = 'none';
                return;
            }
            
            container.style.display = 'block';
            container.dataset.deptId = deptId;
            title.innerText = 'Budget Ledger: ' + deptName;
            
            tbody.innerHTML = '';
            
            const logs = logsData[deptId] || [];
            if(logs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No transactions found.</td></tr>';
                container.scrollIntoView({ behavior: 'smooth', block: 'start' });
                return;
            }
            
            logs.forEach(log => {
                let color = log.action_type.includes('Allocate') || log.action_type.includes('In') ? '#22c55e' : (log.action_type.includes('Revoke') || log.action_type.includes('Out') || log.action_type.includes('PO Expenditure') ? '#ef4444' : '#eab308');
                let sign = (log.action_type.includes('Allocate') || log.action_type === 'Relocate (In)') ? '+' : '-';

                let row = `<tr>
                    <td>${log.created_at}</td>
                    <td><span style="color:${color}; font-weight:bold;">${log.action_type}</span></td>
                    <td style="font-family:monospace; color:${color};">${sign}$${parseFloat(log.amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td>${log.username || 'System'}</td>
                    <td style="font-size:0.9em; color:var(--text-secondary);">${log.notes || ''}</td>
                </tr>`;
                tbody.innerHTML += row;
            });

            container.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        function openTransactionModal(deptId, deptName) {
            document.getElementById('mod_dept_id').value = deptId;
            document.getElementById('modTitle').innerText = 'Budget Transaction: ' + deptName;
            document.getElementById('modModal').style.display = 'block';
        }
        
        function openHistoryModal(deptId, deptName) {
            document.getElementById('histTitle').innerText = 'Transaction History: ' + deptName;
            let tbody = document.getElementById('histTableBody');
            tbody.innerHTML = '';
            
            if (logsData[deptId] && logsData[deptId].length > 0) {
                logsData[deptId].forEach(log => {
                    let color = log.action_type.includes('Allocate') || log.action_type.includes('In') ? '#22c55e' : (log.action_type.includes('Revoke') || log.action_type.includes('Out') ? '#ef4444' : '#eab308');
                    let row = `<tr>
                        <td>${log.created_at}</td>
                        <td><span style="color:${color}; font-weight:bold;">${log.action_type}</span></td>
                        <td style="font-family:monospace;">$${parseFloat(log.amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                        <td>${log.username || 'System'}</td>
                        <td style="font-size:0.9em; color:var(--text-secondary);">${log.notes || ''}</td>
                    </tr>`;
                    tbody.innerHTML += row;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No transaction history found for this vault.</td></tr>';
            }
            
            document.getElementById('histModal').style.display = 'block';
        }
        
        function toggleTargetDept() {
            let action = document.getElementById('mod_action').value;
            document.getElementById('target_dept_container').style.display = (action === 'Relocate') ? 'block' : 'none';
        }
    </script>
</head>
<body><?php include __DIR__ . '/../nav.php'; ?>
    <?php require_once __DIR__ . '/../rbac.php'; ?>
    <div class="dashboard-container">
        <div class="header-flex">
            <h2>🏬 Budget Vault Management</h2>
            <div style="display:flex; gap:15px;">
                <a href="app_settings.php" class="nav-btn">⬅️ BACK</a>
                <button class="btn" onclick="document.getElementById('addModal').style.display='block'">+ Add Budget Vault</button>
            </div>
        </div>

        <div class="vault-grid">
            <?php foreach($departments as $d): 
                $alloc = (float)$d['budget_allocated'];
                $cons = (float)$d['budget_consumed'];
                $rem = $alloc - $cons;
                $rem_color = $rem < 0 ? '#ef4444' : ($rem < ($alloc * 0.1) ? '#ca8a04' : '#22c55e');
            ?>
            <div class="dept-card">
                <div class="dept-header">
                    <h3 class="dept-title" style="cursor:pointer;" title="Click to view Ledger" onclick="toggleLedger(<?= $d['dept_id'] ?>, '<?= addslashes(htmlspecialchars($d['dept_name'])) ?>')">
                        <?= htmlspecialchars($d['dept_name']) ?> <span style="font-size:0.7em; color:var(--text-secondary); font-weight:normal;">(View Ledger)</span>
                    </h3>
                    <a href="#" onclick="openWccConfirm('Delete this Budget Vault? (Historical Purchase Orders will be preserved but unlinked from this vault)', '?delete_id=<?= $d['dept_id'] ?>'); return false;" style="color:#ef4444; font-size:0.9em; text-decoration:none;">✖ Delete</a>
                </div>
                <div class="stat-grid">
                    <div class="stat-box">
                        <div class="stat-label">Allocated Budget</div>
                        <div class="stat-val">$<?= number_format($alloc, 2) ?></div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">Consumed Budget</div>
                        <div class="stat-val" style="color:#ef4444;">$<?= number_format($cons, 2) ?></div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">Remaining</div>
                        <div class="stat-val" style="color:<?= $rem_color ?>;">$<?= number_format($rem, 2) ?></div>
                    </div>
                </div>
                <div class="actions-flex">
                    <button class="nav-btn primary" style="flex:1;" onclick="openTransactionModal(<?= $d['dept_id'] ?>, '<?= addslashes(htmlspecialchars($d['dept_name'])) ?>')">Modify Budget</button>
                    <button class="nav-btn" style="flex:1;" onclick="openHistoryModal(<?= $d['dept_id'] ?>, '<?= addslashes(htmlspecialchars($d['dept_name'])) ?>')">Transaction History</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Inline Ledger Container -->
        <div id="ledgerContainer" class="dept-card" style="display:none; margin-top:30px; border: 1px solid var(--panel-border);">
            <div class="dept-header" style="border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; display:flex; justify-content:space-between; align-items:center;">
                <h3 class="dept-title" id="ledgerTitle" style="color:var(--text-primary);">Budget Ledger</h3>
                <span style="cursor:pointer; color:var(--text-secondary); font-size:1.5em; line-height:1;" onclick="document.getElementById('ledgerContainer').style.display='none'">&times;</span>
            </div>
            <div style="max-height: 500px; overflow-y:auto; margin-top:20px;">
                <table class="data-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th style="text-align:left; padding:8px;">Date & Time</th>
                            <th style="text-align:left; padding:8px;">Transaction Type</th>
                            <th style="text-align:left; padding:8px;">Amount</th>
                            <th style="text-align:left; padding:8px;">User</th>
                            <th style="text-align:left; padding:8px;">Notes</th>
                        </tr>
                    </thead>
                    <tbody id="ledgerTableBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Dept Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content" style="max-width:500px;">
            <span class="close" onclick="document.getElementById('addModal').style.display='none'">&times;</span>
            <h2>Create Budget Vault</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_dept">
                <label>Vault Name *</label>
                <input type="text" name="dept_name" required>
                <label>Initial Budget Allocation ($)</label>
                <input type="number" step="0.01" name="initial_budget" value="0.00">
                <button type="submit" class="btn" style="width:100%; margin-top:20px;">Save Budget Vault</button>
            </form>
        </div>
    </div>

    <!-- Modify Budget Modal -->
    <div id="modModal" class="modal">
        <div class="modal-content" style="max-width:500px;">
            <span class="close" onclick="document.getElementById('modModal').style.display='none'">&times;</span>
            <h2 id="modTitle">Budget Transaction</h2>
            <form method="POST">
                <input type="hidden" name="action" value="modify_budget">
                <input type="hidden" name="dept_id" id="mod_dept_id">
                
                <label>Transaction Type *</label>
                <select name="mod_action" id="mod_action" required onchange="toggleTargetDept()">
                    <option value="Allocate">Allocate (Add Funds)</option>
                    <option value="Revoke">Revoke (Remove Funds)</option>
                    <option value="Relocate">Relocate (Transfer to another Dept)</option>
                </select>

                <div id="target_dept_container" style="display:none;">
                    <label>Transfer To *</label>
                    <select name="target_dept_id">
                        <option value="">-- Select Target Department --</option>
                        <?php foreach($departments as $d): ?>
                            <option value="<?= $d['dept_id'] ?>"><?= htmlspecialchars($d['dept_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <label>Amount ($) *</label>
                <input type="number" step="0.01" name="amount" required min="0.01">

                <label>Transaction Notes</label>
                <textarea name="notes" rows="3" placeholder="Reason for modification..."></textarea>

                <button type="submit" class="btn" style="width:100%; margin-top:20px;">Execute Transaction</button>
            </form>
        </div>
    </div>

    <!-- History Modal -->
    <div id="histModal" class="modal">
        <div class="modal-content" style="max-width:800px;">
            <span class="close" onclick="document.getElementById('histModal').style.display='none'">&times;</span>
            <h2 id="histTitle">Transaction History</h2>
            <div style="max-height: 400px; overflow-y:auto; margin-top:20px;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Action</th>
                            <th>Amount</th>
                            <th>User</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody id="histTableBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>


