<?php
include 'auth.php';
// RBAC EXCEPTION: No require_perm() here. 
// Per requirements, my_profile.php (and change_password.php) must be accessible to EVERY logged-in user 
// because everyone owns their own profile and basic self-service settings (timeout, theme prefs).
// It only uses $_SESSION['user_id'] for the current user. Do not add RBAC gates.

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/inc/db.php';
$pdo = get_wcc_db_connection();

$uid  = (int)$_SESSION['user_id'];
$msg  = '';
$msg_type = 'success';

try {

    // --- Handle: Save personal session timeout ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_timeout') {
        $mins = isset($_POST['personal_timeout']) && $_POST['personal_timeout'] !== ''
            ? max(1, min(1440, (int)$_POST['personal_timeout']))
            : null;
        $pdo->prepare("UPDATE users SET session_timeout_mins = ? WHERE user_id = ?")->execute([$mins, $uid]);
        $_SESSION['personal_timeout'] = $mins;
        $msg = $mins ? "Session timeout set to {$mins} minutes." : "Using global default session timeout.";
    }

    // --- Handle: Update own basic profile (self-service, like industry leaders) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $department = trim($_POST['department'] ?? '');

        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msg = "Invalid email address.";
            $msg_type = 'error';
        } else {
            $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, department = ? WHERE user_id = ?")
                ->execute([$full_name ?: null, $email ?: null, $phone ?: null, $department ?: null, $uid]);
            $msg = "Profile details updated.";
            $msg_type = 'success';
            // refresh me
            $meStmt = $pdo->prepare("SELECT username, role_level, created_at, session_timeout_mins, email, full_name, phone, department, status FROM users WHERE user_id = ?");
            $meStmt->execute([$uid]);
            $me = $meStmt->fetch(PDO::FETCH_ASSOC);
        }
    }

    // --- Handle: Change own password ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $current  = $_POST['current_password'] ?? '';
        $new_pw   = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        $row = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $row->execute([$uid]);
        $hash = $row->fetchColumn();

        if (!password_verify($current, $hash)) {
            $msg = 'Current password is incorrect.'; $msg_type = 'error';
        } elseif (strlen($new_pw) < 6) {
            $msg = 'New password must be at least 6 characters.'; $msg_type = 'error';
        } elseif ($new_pw !== $confirm) {
            $msg = 'New passwords do not match.'; $msg_type = 'error';
        } else {
            $pdo->prepare("UPDATE users SET password_hash = ?, must_change_password = 0 WHERE user_id = ?")->execute([password_hash($new_pw, PASSWORD_DEFAULT), $uid]);
            $_SESSION['must_change_password'] = false;
            $msg = 'Password updated successfully.';
        }
    }

    // Fetch current user data
    $me = $pdo->prepare("SELECT username, role_level, created_at, session_timeout_mins, email, full_name, phone, department, status, badge_number FROM users WHERE user_id = ?");
    $me->execute([$uid]);
    $me = $me->fetch(PDO::FETCH_ASSOC);

    // Fetch global default timeout for display
    $global_timeout = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key = 'session_lockout_time'")->fetchColumn() ?: 360;

    // --- NEW: Personal Stats ---
    $username = $me['username'];
    
    // Total Interventions & Avg Wrench Time
    $statStmt = $pdo->prepare("
        SELECT COUNT(*) as total_interventions, 
               AVG(TIMESTAMPDIFF(MINUTE, action_start, action_end)) as avg_wrench_time 
        FROM ticket_actions 
        WHERE tech_name = ? AND action_start IS NOT NULL AND action_end IS NOT NULL
    ");
    $statStmt->execute([$username]);
    $myStats = $statStmt->fetch(PDO::FETCH_ASSOC);
    $total_interventions = $myStats['total_interventions'] ?? 0;
    $avg_wrench_time = $myStats['avg_wrench_time'] ? round($myStats['avg_wrench_time']) : 0;
    
    // Tickets Reported
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM active_tickets WHERE announced_by = ?");
    $stmt->execute([$username]);
    $tickets_reported = $stmt->fetchColumn();

    // --- NEW: Active Work Orders ---
    $stmt = $pdo->prepare("
        SELECT w.*, e.equip_name 
        FROM work_orders w 
        LEFT JOIN equipment e ON w.equipment_id = e.equip_id 
        WHERE w.assigned_to = ? AND w.status IN ('Scheduled', 'In Progress')
        ORDER BY w.scheduled_date ASC
    ");
    $stmt->execute([$uid]);
    $myWorkOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- NEW: Recent Activity ---
    $stmt = $pdo->prepare("
        SELECT ticket_id, action_taken, timestamp_logged 
        FROM ticket_actions 
        WHERE tech_name = ? 
        ORDER BY timestamp_logged DESC 
        LIMIT 5
    ");
    $stmt->execute([$username]);
    $myRecentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate Gamified Proficiencies (for me only) based on Equipment Category
    $gamifiedStats = [];
    $gStmt = $pdo->prepare("
        SELECT e.category, SUM(TIMESTAMPDIFF(MINUTE, ta.action_start, ta.action_end))/60 as total_hours
        FROM ticket_actions ta
        JOIN active_tickets at ON at.ticket_id = ta.ticket_id
        JOIN equipment e ON e.equip_id = at.equip_id
        WHERE ta.tech_name = ? AND ta.action_start IS NOT NULL AND ta.action_end IS NOT NULL AND e.category IS NOT NULL AND e.category != ''
        GROUP BY e.category
    ");
    $gStmt->execute([$username]);
    foreach($gStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $gamifiedStats[$r['category']] = (float)$r['total_hours'];
    }

    // Manual Skills (for me only)
    $mStmt = $pdo->prepare("SELECT skill_name FROM user_skills WHERE user_id = ?");
    $mStmt->execute([$uid]);
    $user_man_skills = $mStmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) { wcc_user_error("Profile load failed.", $e->getMessage()); }

require_once __DIR__ . '/rbac.php';
$role_name = get_role_name((int)$me['role_level']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>My Profile — WCC</title>
    <style>
        .profile-grid {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 24px;
            margin-top: 28px;
            align-items: start;
        }
        @media (max-width: 900px) { .profile-grid { grid-template-columns: 1fr; } }

        .profile-card {
            background: var(--panel-bg);
            backdrop-filter: blur(15px);
            border: 1px solid var(--panel-border);
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
        }
        .profile-card h3 {
            color: var(--text-accent);
            margin: 0 0 18px 0;
            font-size: 1.05em;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid var(--panel-border);
            padding-bottom: 10px;
        }
        .avatar-ring {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--text-accent), #7c3aed);
            display: flex; align-items: center; justify-content: center;
            font-size: 2.2em;
            margin: 0 auto 16px;
            box-shadow: 0 0 0 4px var(--panel-border), 0 0 20px rgba(56,189,248,0.3);
        }
        .stat-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 0.9em;
        }
        .stat-row:last-child { border-bottom: none; }
        .stat-row .lbl { color: var(--text-secondary); }
        .stat-row .val { color: var(--text-primary); font-weight: 600; }
        .role-badge {
            display: inline-block;
            padding: 4px 12px; border-radius: 20px;
            background: rgba(56,189,248,0.15);
            border: 1px solid var(--text-accent);
            color: var(--text-accent);
            font-size: 0.85em; font-weight: 700;
        }

        /* Timeout slider */
        .timeout-display {
            font-size: 2em; font-weight: 800; color: var(--text-accent);
            text-align: center; margin: 10px 0 4px;
        }
        .timeout-label { text-align:center; color: var(--text-secondary); font-size: 0.85em; margin-bottom: 16px; }
        input[type="range"] {
            width: 100%; -webkit-appearance: none; appearance: none;
            height: 6px; border-radius: 3px;
            background: linear-gradient(to right, var(--text-accent) 0%, var(--panel-border) 0%);
            outline: none; cursor: pointer;
        }
        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none; appearance: none;
            width: 20px; height: 20px; border-radius: 50%;
            background: var(--text-accent);
            box-shadow: 0 0 8px rgba(56,189,248,0.5);
            cursor: pointer;
        }
        .preset-btns { display: flex; gap: 8px; margin-top: 14px; flex-wrap: wrap; }
        .preset-btn {
            flex: 1; min-width: 60px;
            padding: 6px 10px; border-radius: 8px; font-size: 0.8em; font-weight: 600;
            background: rgba(255,255,255,0.05); border: 1px solid var(--panel-border);
            color: var(--text-secondary); cursor: pointer; transition: all 0.2s;
        }
        .preset-btn:hover, .preset-btn.active {
            background: var(--text-accent); color: #0f172a; border-color: var(--text-accent);
        }

        /* Color blocks */
        .color-block {
            display: flex; flex-direction: column; gap: 8px;
            background: rgba(0,0,0,0.1); padding: 12px;
            border-radius: 12px; border: 1px solid var(--panel-border); transition: border 0.2s;
        }
        .color-block:hover { border-color: var(--text-accent); }
        .color-block label { color: var(--text-secondary); margin: 0; font-weight: bold; font-size: 0.85em; text-align: center; }
        .color-block input[type="color"] { background: transparent; border: none; width: 100%; height: 40px; cursor: pointer; padding: 0; }
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        @media (max-width: 600px) { .grid-4 { grid-template-columns: repeat(2, 1fr); } }

        .msg {
            padding: 12px 18px; border-radius: 10px; margin-bottom: 20px;
            font-weight: 600; font-size: 0.95em;
        }
        .msg.success { background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); }
        .msg.error   { background: rgba(239,68,68,0.15);  color: #ef4444; border: 1px solid rgba(239,68,68,0.3); }

        .section-sep { margin: 20px 0; border: none; border-top: 1px solid var(--panel-border); }

        /* --- NEW PROFILE WIDGET STYLES --- */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .stat-box { 
            background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); 
            border-radius: 16px; padding: 20px; text-align: center;
            box-shadow: inset 0 0 20px rgba(56,189,248,0.02);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-box:hover { transform: translateY(-3px); box-shadow: inset 0 0 20px rgba(56,189,248,0.1), 0 5px 15px rgba(0,0,0,0.3); }
        .stat-box .val { font-size: 2.2em; font-weight: 800; color: var(--text-accent); text-shadow: 0 0 15px rgba(56,189,248,0.4); margin-bottom: 5px; }
        .stat-box .lbl { font-size: 0.8em; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px; font-weight: 700; }
        .stat-box .stat-value { font-size: 2.2em; font-weight: 800; color: var(--text-accent); text-shadow: 0 0 15px rgba(56,189,248,0.4); margin-bottom: 5px; }
        .stat-box .stat-label { font-size: 0.8em; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px; font-weight: 700; }

        .timeline-scroll { max-height: 250px; overflow-y: auto; padding-right: 8px; }
        .timeline-scroll::-webkit-scrollbar { width: 4px; }
        .timeline-scroll::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); border-radius: 2px; }
        .timeline-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 2px; }
        
        .timeline-item { border-left: 2px solid var(--panel-border); padding-left: 15px; position: relative; margin-bottom: 18px; }
        .timeline-item:last-child { margin-bottom: 0; }
        .timeline-item::before { 
            content: ''; position: absolute; left: -6px; top: 0; width: 10px; height: 10px; 
            border-radius: 50%; background: var(--text-accent); box-shadow: 0 0 8px var(--text-accent);
        }
        .timeline-time { font-size: 0.75em; color: var(--text-secondary); font-weight: bold; margin-bottom: 4px; display: block; }
        .timeline-desc { font-size: 0.9em; color: var(--text-primary); }
        .timeline-desc strong { color: var(--text-accent); }

        .wo-card { 
            background: rgba(0,0,0,0.15); border-left: 4px solid #f59e0b; 
            padding: 12px 15px; border-radius: 8px; margin-bottom: 10px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .wo-card:hover { background: rgba(255,255,255,0.05); }
    </style>
</head>
<body><?php include 'nav.php'; ?>

<div class="dashboard-container dash-box">
    <div class="header-flex" style="border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px;">
        <h2>👤 My Profile &amp; Preferences</h2>
    </div>

    <?php if ($msg): ?>
        <div class="msg <?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="profile-grid">

        <!-- LEFT COLUMN: Identity card -->
        <div style="display:flex; flex-direction:column; gap:20px;">

            <div class="profile-card" style="text-align:center;">
                <div class="avatar-ring">👤</div>
                <div style="font-size:1.4em; font-weight:800; color:var(--text-primary); margin-bottom:6px;">
                    <?= htmlspecialchars($me['full_name'] ?: strtoupper($me['username'])) ?>
                    <span style="font-size:0.6em; color:var(--text-secondary); display:block;">Badge: <?= htmlspecialchars($me['badge_number'] ?? 'N/A') ?></span>
                </div>
                <div class="role-badge">L<?= $me['role_level'] ?> — <?= $role_name ?></div>
                <hr class="section-sep">
                <div class="stat-row">
                    <span class="lbl">User ID</span>
                    <span class="val">#<?= $uid ?></span>
                </div>
                <div class="stat-row">
                    <span class="lbl">Member Since</span>
                    <span class="val"><?= date('d M Y', strtotime($me['created_at'])) ?></span>
                </div>
                <div class="stat-row">
                    <span class="lbl">Session Timeout</span>
                    <span class="val">
                        <?= $me['session_timeout_mins']
                            ? $me['session_timeout_mins'] . ' min (personal)'
                            : $global_timeout . ' min (global)' ?>
                    </span>
                </div>
            </div>

            <!-- Self-service profile edit -->
            <div class="profile-card">
                <h3 style="font-size:1.05em;">✏️ Edit Profile</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="save_profile">
                    <label style="font-size:0.85em;">Full Name</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($me['full_name'] ?? '') ?>" style="width:100%; padding:6px; margin-bottom:8px; border-radius:6px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary);">
                    <label style="font-size:0.85em;">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($me['email'] ?? '') ?>" style="width:100%; padding:6px; margin-bottom:8px; border-radius:6px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary);">
                    <label style="font-size:0.85em;">Phone</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($me['phone'] ?? '') ?>" style="width:100%; padding:6px; margin-bottom:8px; border-radius:6px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary);">
                    <label style="font-size:0.85em;">Department</label>
                    <input type="text" name="department" value="<?= htmlspecialchars($me['department'] ?? '') ?>" style="width:100%; padding:6px; margin-bottom:12px; border-radius:6px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary);">
                    <button type="submit" class="nav-btn primary" style="width:100%; font-size:0.9em;">Save Profile</button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="profile-card">
                <h3>🔑 Change Password</h3>
                <form id="changePasswordForm" method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required autocomplete="current-password"
                        style="width:100%;box-sizing:border-box;margin-bottom:12px;">
                    <label>New Password</label>
                    <input type="password" name="new_password" required minlength="6" autocomplete="new-password"
                        style="width:100%;box-sizing:border-box;margin-bottom:12px;">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required autocomplete="new-password"
                        style="width:100%;box-sizing:border-box;margin-bottom:16px;">
                    <button type="button" class="nav-btn primary" style="width:100%;" onclick="openWccConfirm('Are you sure you want to change your password?', function() { document.getElementById('changePasswordForm').submit(); }, 'Update Password');">Update Password</button>
                </form>
            </div>
        </div>

        <!-- RIGHT COLUMN: Preferences & Dashboards -->
        <div style="display:flex; flex-direction:column; gap:20px;">

            <!-- Performance Dashboard -->
            <div class="profile-card">
                <h3>📈 My Performance Dashboard</h3>
                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="val"><?= $total_interventions ?></div>
                        <div class="lbl">Interventions</div>
                    </div>
                    <div class="stat-box">
                        <div class="val"><?= $avg_wrench_time ?><span style="font-size:0.5em;">m</span></div>
                        <div class="lbl">Avg Wrench Time</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value"><?= $tickets_closed ?></div>
                        <div class="stat-label">Tickets Closed Out</div>
                    </div>
                </div>

                <!-- Gamified Proficiencies Box -->
                <div style="margin-top:20px; padding:15px; background:var(--panel-bg); border-radius:12px; border:1px solid var(--panel-border);">
                    <div style="font-weight:600; margin-bottom:15px; font-size:1.1em; color:var(--text-accent);">Gamified Proficiencies</div>
                    <div style="display:flex; flex-wrap:wrap; gap:10px;">
                        <?php if (!empty($gamifiedStats)): ?>
                            <?php foreach ($gamifiedStats as $cat => $hours): 
                                $badgeIcon = '🥉'; $badgeTier = 'Novice'; $badgeColor = '#cd7f32';
                                if ($hours >= 200) { $badgeIcon = '👑'; $badgeTier = 'Master'; $badgeColor = '#ef4444'; }
                                elseif ($hours >= 100) { $badgeIcon = '💎'; $badgeTier = 'Expert'; $badgeColor = '#a855f7'; }
                                elseif ($hours >= 40) { $badgeIcon = '🥇'; $badgeTier = 'Proficient'; $badgeColor = '#eab308'; }
                                elseif ($hours >= 20) { $badgeIcon = '🥈'; $badgeTier = 'Competent'; $badgeColor = '#94a3b8'; }
                                elseif ($hours >= 10) { $badgeIcon = '🥉'; $badgeTier = 'Advanced'; $badgeColor = '#d97706'; }
                            ?>
                                <div style="background:rgba(255,255,255,0.05); border:1px solid <?= $badgeColor ?>44; padding:8px 15px; border-radius:10px; font-size:0.9em; display:flex; align-items:center; gap:10px;">
                                    <span style="font-size:1.8em; filter: drop-shadow(0 0 2px <?= $badgeColor ?>);"><?= $badgeIcon ?></span>
                                    <div>
                                        <div style="color:var(--text-primary); font-weight:bold; font-size:1.1em;"><?= htmlspecialchars($cat) ?></div>
                                        <div style="color:<?= $badgeColor ?>;"><?= $badgeTier ?> (<?= round($hours, 1) ?>h)</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="color:var(--text-secondary); font-size:0.9em; font-style:italic;">No machine proficiencies logged yet. Wrench time unlocks automatic badges!</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Manual Skills Box -->
                <div style="margin-top:20px; padding:15px; background:var(--panel-bg); border-radius:12px; border:1px solid var(--panel-border);">
                    <div style="font-weight:600; margin-bottom:15px; font-size:1.1em; color:var(--text-accent);">Manual Skills</div>
                    <div style="display:flex; flex-wrap:wrap; gap:10px;">
                        <?php if (!empty($user_man_skills)): ?>
                            <?php foreach ($user_man_skills as $ms): ?>
                                <div style="background:rgba(255,255,255,0.05); border:1px solid var(--panel-border); padding:8px 15px; border-radius:10px; font-size:0.9em; display:flex; align-items:center; gap:8px;">
                                    <span style="font-size:1.4em;">🛠️</span>
                                    <strong style="color:var(--text-primary); font-size:1.1em;"><?= htmlspecialchars($ms) ?></strong>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="color:var(--text-secondary); font-size:0.9em; font-style:italic;">No manual skills logged.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Active Work Orders -->
            <div class="profile-card">
                <h3><span class="icon">📋</span> Active Work Orders</h3>
                <?php if(empty($myWorkOrders)): ?>
                    <p style="color:var(--text-secondary); text-align:center; padding: 20px 0;">No active work orders assigned to you! 🎉</p>
                <?php else: ?>
                    <div class="timeline-scroll">
                        <?php foreach($myWorkOrders as $wo): ?>
                            <div class="wo-card">
                                <div>
                                    <strong style="color:white;">WO #<?= $wo['wo_id'] ?></strong> — <span style="color:var(--text-accent); font-size:0.9em;"><?= htmlspecialchars($wo['status']) ?></span><br>
                                    <span style="color:var(--text-secondary); font-size:0.85em;"><?= htmlspecialchars($wo['equip_name'] ?? 'Unknown Machine') ?></span>
                                </div>
                                <div>
                                    <button class="nav-btn" style="padding:4px 10px; font-size:0.8em;" onclick="window.location.href='/_maint/work_orders.php'">View</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Activity -->
            <div class="profile-card">
                <h3>📜 Recent Activity Log</h3>
                <?php if(empty($myRecentActivity)): ?>
                    <p style="color:var(--text-secondary); text-align:center; padding: 20px 0;">No recent activity found.</p>
                <?php else: ?>
                    <div class="timeline-scroll">
                        <?php foreach($myRecentActivity as $act): ?>
                            <div class="timeline-item">
                                <span class="timeline-time"><?= date('d M Y - H:i', strtotime($act['timestamp_logged'])) ?></span>
                                <div class="timeline-desc">
                                    Logged action on <strong><?= htmlspecialchars($act['ticket_id']) ?></strong>:<br>
                                    <span style="color:var(--text-secondary); font-size:0.9em;">"<?= htmlspecialchars($act['action_taken']) ?>"</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Skills & Certifications -->
            <div class="profile-card">
                <h3>🎓 Skills & Certifications</h3>
                <div class="skill-form">
                    <input type="text" id="newSkillName" placeholder="e.g. Level 3 Electrician">
                    <button type="button" class="nav-btn primary" onclick="addSkill()">Add</button>
                </div>
                <div id="skillsContainer">
                    <?php if(empty($mySkills)): ?>
                        <p id="noSkillsMsg" style="color:var(--text-secondary); text-align:center;">No skills added yet.</p>
                    <?php else: ?>
                        <?php foreach($mySkills as $skill): ?>
                            <span class="skill-badge" id="skill-<?= $skill['id'] ?>">
                                🛡️ <?= htmlspecialchars($skill['skill_name']) ?> 
                                <span class="del-btn" onclick="deleteSkill(<?= $skill['id'] ?>)">✖</span>
                            </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Session Timeout -->
            <div class="profile-card">
                <h3>⏱️ Personal Session Timeout</h3>
                <p style="color:var(--text-secondary);font-size:0.9em;margin-top:0;">
                    Override the global default (<?= $global_timeout ?> min) with your own preference.
                    Set to longer if you're at a dedicated workstation, shorter on shared equipment.
                </p>
                <form method="POST" id="timeoutForm">
                    <input type="hidden" name="action" value="save_timeout">
                    <input type="hidden" name="personal_timeout" id="timeoutHidden"
                        value="<?= htmlspecialchars($me['session_timeout_mins'] ?? '') ?>">

                    <div class="timeout-display" id="timeoutDisplay">
                        <?= $me['session_timeout_mins'] ? $me['session_timeout_mins'] . ' min' : 'Global (' . $global_timeout . ' min)' ?>
                    </div>
                    <div class="timeout-label" id="timeoutSublabel">
                        <?= $me['session_timeout_mins'] ? 'Personal preference active' : 'No personal override set' ?>
                    </div>

                    <input type="range" id="timeoutSlider" min="5" max="480" step="5"
                        value="<?= $me['session_timeout_mins'] ?: $global_timeout ?>"
                        oninput="updateSlider(this.value)">

                    <div class="preset-btns">
                        <button type="button" class="preset-btn" onclick="setPreset(15)">15 min</button>
                        <button type="button" class="preset-btn" onclick="setPreset(30)">30 min</button>
                        <button type="button" class="preset-btn" onclick="setPreset(60)">1 hour</button>
                        <button type="button" class="preset-btn" onclick="setPreset(120)">2 hours</button>
                        <button type="button" class="preset-btn" onclick="setPreset(240)">4 hours</button>
                        <button type="button" class="preset-btn" onclick="setPreset(480)">8 hours</button>
                    </div>

                    <div style="display:flex;gap:10px;margin-top:18px;">
                        <button type="submit" class="nav-btn primary" style="flex:1;">Save My Timeout</button>
                        <button type="button" class="nav-btn" onclick="clearPersonalTimeout()"
                            style="flex:1;border:1px solid #ef4444;color:#ef4444;background:transparent;">
                            Use Global Default
                        </button>
                    </div>
                </form>
            </div>

            <div style="margin-top: 8px; text-align: right;">
                <a href="/_mgmt/app_settings.php" class="nav-btn" style="font-size: 0.85em; padding: 6px 12px;">🎨 Open Theme Lab in Settings</a>
            </div>

        </div>
    </div>
</div>

<script>
    // ---- Skills Management ----
    async function addSkill() {
        const nameInput = document.getElementById('newSkillName');
        const name = nameInput.value.trim();
        if(!name) return;
        
        try {
            const res = await fetch('/api/manage_skills.php', {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action: 'add', skill_name: name })
            });
            const data = await res.json();
            if(data.status === 'success') {
                const container = document.getElementById('skillsContainer');
                const noMsg = document.getElementById('noSkillsMsg');
                if(noMsg) noMsg.style.display = 'none';
                
                const span = document.createElement('span');
                span.className = 'skill-badge';
                span.id = `skill-${data.id}`;
                span.innerHTML = `🛡️ ${name} <span class="del-btn" onclick="deleteSkill(${data.id})">✖</span>`;
                container.appendChild(span);
                nameInput.value = '';
            } else {
                alert(data.message);
            }
        } catch(e) { alert("Error adding skill"); }
    }

    async function deleteSkill(id) {
        if(!confirm("Remove this skill?")) return;
        try {
            const res = await fetch('/api/manage_skills.php', {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action: 'delete', id: id })
            });
            const data = await res.json();
            if(data.status === 'success') {
                document.getElementById(`skill-${id}`).remove();
                if(document.querySelectorAll('.skill-badge').length === 0) {
                    const noMsg = document.getElementById('noSkillsMsg');
                    if(noMsg) noMsg.style.display = 'block';
                }
            } else {
                alert(data.message);
            }
        } catch(e) { alert("Error removing skill"); }
    }

    // ---- Session Timeout Slider ----
    const slider  = document.getElementById('timeoutSlider');
    const display = document.getElementById('timeoutDisplay');
    const sublbl  = document.getElementById('timeoutSublabel');
    const hidden  = document.getElementById('timeoutHidden');

    function updateSlider(val) {
        val = parseInt(val);
        slider.style.background = `linear-gradient(to right, var(--text-accent) ${((val-5)/(480-5))*100}%, var(--panel-border) ${((val-5)/(480-5))*100}%)`;
        display.textContent = val >= 60
            ? (val % 60 === 0 ? (val/60) + ' hr' : Math.floor(val/60) + 'h ' + (val%60) + 'm')
            : val + ' min';
        sublbl.textContent = 'Drag to set your personal timeout';
        hidden.value = val;
        highlightPreset(val);
    }

    function setPreset(val) {
        slider.value = val;
        updateSlider(val);
    }

    function highlightPreset(val) {
        document.querySelectorAll('.preset-btn').forEach(btn => {
            btn.classList.toggle('active', parseInt(btn.getAttribute('onclick').match(/\d+/)[0]) === val);
        });
    }

    function clearPersonalTimeout() {
        hidden.value = '';
        display.textContent = 'Global (<?= $global_timeout ?> min)';
        sublbl.textContent  = 'Using global default';
        document.getElementById('timeoutForm').submit();
    }

    // Init slider gradient on load
    updateSlider(slider.value);
    highlightPreset(<?= $me['session_timeout_mins'] ?: $global_timeout ?>);
</script>
</body>
</html>
