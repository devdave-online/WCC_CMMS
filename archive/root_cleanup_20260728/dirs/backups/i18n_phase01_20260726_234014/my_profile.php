<?php
include 'auth.php';
// RBAC EXCEPTION: No require_perm() here.
// Per requirements, my_profile.php (and change_password.php) must be accessible to EVERY logged-in user
// because everyone owns their own profile and basic self-service settings (timeout, theme prefs).
// It only uses $_SESSION['user_id'] for the current user. Do not add RBAC gates.

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/techident.php';
require_once __DIR__ . '/inc/gamification.php';
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

    // --- Personal Stats ---
    $username = $me['username'];

    // Intervention records are filed under a display name today and under a username
    // in older rows, and announced_by / closed_by have the same split. Matching on a
    // single spelling silently zeroes a technician's whole profile, so every personal
    // stat below matches any alias this user's work could be recorded under.
    $myAliases = wcc_tech_aliases($me);
    $aliasPh   = wcc_tech_alias_placeholders($myAliases);

    // Total Interventions & Avg Wrench Time
    $statStmt = $pdo->prepare("
        SELECT COUNT(*) as total_interventions,
               AVG(TIMESTAMPDIFF(MINUTE, action_start, action_end)) as avg_wrench_time
        FROM ticket_actions
        WHERE tech_name IN ($aliasPh) AND action_start IS NOT NULL AND action_end IS NOT NULL
    ");
    $statStmt->execute($myAliases);
    $myStats = $statStmt->fetch(PDO::FETCH_ASSOC);
    $total_interventions = $myStats['total_interventions'] ?? 0;
    $avg_wrench_time = $myStats['avg_wrench_time'] ? round($myStats['avg_wrench_time']) : 0;

    // Tickets Reported
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM active_tickets WHERE announced_by IN ($aliasPh)");
    $stmt->execute($myAliases);
    $tickets_reported = $stmt->fetchColumn();

    // Tickets Closed Out (previously referenced but never computed)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM active_tickets WHERE closed_by IN ($aliasPh) AND status = 'CLOSED'");
    $stmt->execute($myAliases);
    $tickets_closed = $stmt->fetchColumn();

    // --- Active Work Orders ---
    $stmt = $pdo->prepare("
        SELECT w.*, e.equip_name
        FROM work_orders w
        LEFT JOIN equipment e ON w.equipment_id = e.equip_id
        WHERE w.assigned_to = ? AND w.status IN ('Scheduled', 'In Progress')
        ORDER BY w.scheduled_date ASC
    ");
    $stmt->execute([$uid]);
    $myWorkOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Recent Activity ---
    $stmt = $pdo->prepare("
        SELECT ticket_id, action_taken, timestamp_logged
        FROM ticket_actions
        WHERE tech_name IN ($aliasPh)
        ORDER BY timestamp_logged DESC
        LIMIT 5
    ");
    $stmt->execute($myAliases);
    $myRecentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate Gamified Proficiencies (for me only) based on Equipment Category
    $gamifiedStats = [];
    $gStmt = $pdo->prepare("
        SELECT e.category, SUM(TIMESTAMPDIFF(MINUTE, ta.action_start, ta.action_end))/60 as total_hours
        FROM ticket_actions ta
        JOIN active_tickets at ON at.ticket_id = ta.ticket_id
        JOIN equipment e ON e.equip_id = at.equip_id
        WHERE ta.tech_name IN ($aliasPh) AND ta.action_start IS NOT NULL AND ta.action_end IS NOT NULL AND e.category IS NOT NULL AND e.category != ''
        GROUP BY e.category
    ");
    $gStmt->execute($myAliases);
    foreach($gStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $gamifiedStats[$r['category']] = (float)$r['total_hours'];
    }

    // The Skill Configurator gives every equipment category a proficiency NAME and an
    // ICON, and the Users Directory renders both. This page was showing the bare
    // category ("Assembly") with no icon, so the same proficiency looked like two
    // different things depending which screen you were on.
    //
    // The configurator is also the allow-list: an unmapped category earns nothing, so
    // showing unmapped categories here would contradict the rule stated in the
    // configurator itself.
    $skillAutoConfigs = [];
    try {
        foreach ($pdo->query("SELECT equipment_category, skill_name, icon FROM skill_automation_config
                              ORDER BY equipment_category ASC")->fetchAll(PDO::FETCH_ASSOC) as $sc) {
            $skillAutoConfigs[$sc['equipment_category']] = $sc;
        }
    } catch (Exception $e) { /* table may not exist on an old install */ }

    // Manual Skills (id + name — feeds both the badge chips and the editor card)
    $mStmt = $pdo->prepare("SELECT id, skill_name FROM user_skills WHERE user_id = ? ORDER BY skill_name ASC");
    $mStmt->execute([$uid]);
    $mySkills = $mStmt->fetchAll(PDO::FETCH_ASSOC);
    $user_man_skills = array_column($mySkills, 'skill_name');

} catch (PDOException $e) { wcc_user_error("Profile load failed.", $e->getMessage()); }

require_once __DIR__ . '/rbac.php';
$role_name = get_role_name((int)$me['role_level']);

$page_title = 'My Profile';
require_once __DIR__ . '/inc/head.php';
?>
<style>
    .profile-grid {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: var(--space-6);
        margin-top: var(--space-6);
        align-items: start;
    }
    @media (max-width: 900px) { .profile-grid { grid-template-columns: 1fr; } }

    .profile-card {
        background: var(--panel-bg);
        backdrop-filter: blur(15px);
        border: 1px solid var(--panel-border);
        border-radius: var(--radius-lg);
        padding: var(--space-6);
        box-shadow: var(--shadow-1);
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
        box-shadow: 0 0 0 4px var(--panel-border), 0 0 20px var(--focus-ring);
    }
    .stat-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid var(--panel-border);
        font-size: var(--fs-sm);
    }
    .stat-row:last-child { border-bottom: none; }
    .stat-row .lbl { color: var(--text-secondary); }
    .stat-row .val { color: var(--text-primary); font-weight: 600; }
    .role-badge {
        display: inline-block;
        padding: 4px 12px; border-radius: var(--radius-full);
        background: var(--info-bg);
        border: 1px solid var(--text-accent);
        color: var(--text-accent);
        font-size: var(--fs-sm); font-weight: 700;
    }

    /* Timeout slider */
    .timeout-display {
        font-size: 2em; font-weight: 800; color: var(--text-accent);
        text-align: center; margin: 10px 0 4px;
    }
    .timeout-label { text-align:center; color: var(--text-muted); font-size: var(--fs-sm); margin-bottom: 16px; }
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
        box-shadow: 0 0 8px var(--focus-ring);
        cursor: pointer;
    }
    .preset-btns { display: flex; gap: 8px; margin-top: 14px; flex-wrap: wrap; }
    .preset-btn {
        flex: 1; min-width: 60px;
        padding: 6px 10px; border-radius: var(--radius-sm); font-size: var(--fs-xs); font-weight: 600;
        background: var(--surface-1); border: 1px solid var(--panel-border);
        color: var(--text-secondary); cursor: pointer; transition: all var(--transition-fast);
        font-family: inherit;
    }
    .preset-btn:hover, .preset-btn.active {
        background: var(--text-accent); color: var(--slate-900); border-color: var(--text-accent);
    }

    .msg {
        padding: 12px 18px; border-radius: var(--radius-md); margin-bottom: 20px;
        font-weight: 600; font-size: var(--fs-sm);
        border: 1px solid transparent;
    }
    .msg.success { background: var(--success-bg); color: var(--success); border-color: var(--success-border); }
    .msg.error   { background: var(--danger-bg);  color: var(--danger);  border-color: var(--danger-border); }

    .section-sep { margin: 20px 0; border: none; border-top: 1px solid var(--panel-border); }

    /* Performance widgets */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 15px; margin-bottom: 25px; }
    .stat-box {
        background: var(--surface-1); border: 1px solid var(--panel-border);
        border-radius: var(--radius-lg); padding: var(--space-5); text-align: center;
        transition: transform var(--transition-fast), box-shadow var(--transition-fast);
    }
    .stat-box:hover { transform: translateY(-3px); box-shadow: var(--shadow-1); }
    .stat-box .val, .stat-box .stat-value { font-size: 2.2em; font-weight: 800; color: var(--text-accent); margin-bottom: 5px; }
    .stat-box .lbl, .stat-box .stat-label { font-size: var(--fs-xs); color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; font-weight: 700; }

    .timeline-scroll { max-height: 250px; overflow-y: auto; padding-right: 8px; }
    .timeline-item { border-left: 2px solid var(--panel-border); padding-left: 15px; position: relative; margin-bottom: 18px; }
    .timeline-item:last-child { margin-bottom: 0; }
    .timeline-item::before {
        content: ''; position: absolute; left: -6px; top: 0; width: 10px; height: 10px;
        border-radius: 50%; background: var(--text-accent); box-shadow: 0 0 8px var(--text-accent);
    }
    .timeline-time { font-size: var(--fs-xs); color: var(--text-muted); font-weight: bold; margin-bottom: 4px; display: block; }
    .timeline-desc { font-size: var(--fs-sm); color: var(--text-primary); }
    .timeline-desc strong { color: var(--text-accent); }

    .wo-card {
        background: var(--surface-1); border: 1px solid var(--panel-border); border-left: 4px solid var(--amber-500);
        padding: 12px 15px; border-radius: var(--radius-sm); margin-bottom: 10px;
        display: flex; justify-content: space-between; align-items: center; gap: var(--space-3);
    }
    .wo-card:hover { background: var(--surface-2); }

    /* Proficiency + skill chips */
    .prof-chip {
        background: var(--surface-1); padding: 8px 15px; border-radius: var(--radius-md);
        font-size: var(--fs-sm); display: flex; align-items: center; gap: 10px;
        border: 1px solid var(--panel-border);
    }
    .skill-form { display: flex; gap: 10px; margin-bottom: 16px; }
    .skill-form input { flex: 1; padding: 10px; border-radius: var(--radius-sm); background: var(--input-bg); border: 1px solid var(--input-border); color: var(--input-text); font-family: inherit; }
    #skillsContainer { display: flex; flex-wrap: wrap; gap: 10px; }
    .skill-badge {
        display: inline-flex; align-items: center; gap: 8px;
        background: var(--surface-1); border: 1px solid var(--panel-border);
        padding: 8px 14px; border-radius: var(--radius-full);
        font-size: var(--fs-sm); font-weight: 600; color: var(--text-primary);
    }
    .skill-badge .del-btn { cursor: pointer; color: var(--danger); font-weight: 700; padding: 0 2px; }
    .skill-badge .del-btn:hover { transform: scale(1.2); }
</style>
<?php include 'nav.php'; ?>

<div class="dashboard-container">
    <div class="page-header">
        <h1>👤 My Profile &amp; Preferences</h1>
    </div>

    <?php if ($msg): ?>
        <div class="msg <?= $msg_type ?>" role="<?= $msg_type === 'error' ? 'alert' : 'status' ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="profile-grid">

        <!-- LEFT COLUMN: Identity card -->
        <div style="display:flex; flex-direction:column; gap:20px;">

            <div class="profile-card" style="text-align:center;">
                <div class="avatar-ring" aria-hidden="true">👤</div>
                <div style="font-size:1.4em; font-weight:800; color:var(--text-primary); margin-bottom:6px;">
                    <?= htmlspecialchars($me['full_name'] ?: strtoupper($me['username'])) ?>
                    <span class="fs-xs text-muted" style="display:block; font-weight:600;">Badge: <?= htmlspecialchars($me['badge_number'] ?? 'N/A') ?></span>
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
                <h3>✏️ Edit Profile</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="save_profile">
                    <div class="field">
                        <label for="pf_full_name">Full Name</label>
                        <input type="text" id="pf_full_name" name="full_name" value="<?= htmlspecialchars($me['full_name'] ?? '') ?>" autocomplete="name">
                    </div>
                    <div class="field">
                        <label for="pf_email">Email</label>
                        <input type="email" id="pf_email" name="email" value="<?= htmlspecialchars($me['email'] ?? '') ?>" autocomplete="email">
                    </div>
                    <div class="field">
                        <label for="pf_phone">Phone</label>
                        <input type="text" id="pf_phone" name="phone" value="<?= htmlspecialchars($me['phone'] ?? '') ?>" autocomplete="tel">
                    </div>
                    <div class="field">
                        <label for="pf_department">Department</label>
                        <input type="text" id="pf_department" name="department" value="<?= htmlspecialchars($me['department'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Save Profile</button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="profile-card">
                <h3>🔑 Change Password</h3>
                <form id="changePasswordForm" method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="field">
                        <label for="pw_current">Current Password</label>
                        <input type="password" id="pw_current" name="current_password" required autocomplete="current-password">
                    </div>
                    <div class="field">
                        <label for="pw_new">New Password</label>
                        <input type="password" id="pw_new" name="new_password" required minlength="6" autocomplete="new-password">
                    </div>
                    <div class="field">
                        <label for="pw_confirm">Confirm New Password</label>
                        <input type="password" id="pw_confirm" name="confirm_password" required autocomplete="new-password">
                    </div>
                    <button type="button" class="btn btn-primary btn-block" onclick="openWccConfirm('Are you sure you want to change your password?', function() { document.getElementById('changePasswordForm').submit(); }, 'Update Password');">Update Password</button>
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
                        <div class="val"><?= $tickets_reported ?></div>
                        <div class="lbl">Tickets Reported</div>
                    </div>
                    <div class="stat-box">
                        <div class="val"><?= $tickets_closed ?></div>
                        <div class="lbl">Tickets Closed Out</div>
                    </div>
                </div>

                <!-- Gamified Proficiencies Box -->
                <div style="margin-top:20px; padding:15px; background:var(--surface-1); border-radius:var(--radius-md); border:1px solid var(--panel-border);">
                    <div style="font-weight:600; margin-bottom:15px; font-size:1.1em; color:var(--text-accent);">Gamified Proficiencies<?= wcc_gamified_help_button("How are these earned?") ?></div>
                    <div style="display:flex; flex-wrap:wrap; gap:10px;">
                        <?php if (!empty($gamifiedStats)): ?>
                            <?php
                            $__shown = 0;
                            foreach ($gamifiedStats as $cat => $hours):
                                // Only mapped categories score — same allow-list the Skill
                                // Configurator describes and the Users Directory applies.
                                if (!isset($skillAutoConfigs[$cat])) continue;
                                $__cfg = $skillAutoConfigs[$cat];
                                $__shown++;

                                // Shared ladder (inc/gamification.php) — this used to be a local copy
                                // with its own colours, so the same tier looked different here than
                                // it did on the Users Directory.
                                $__t = wcc_gamified_level((float)$hours);
                                $badgeIcon = $__t['icon']; $badgeTier = $__t['tier']; $badgeColor = $__t['color'];
                                $__next = wcc_gamified_next((float)$hours);
                            ?>
                                <div class="prof-chip" style="border-color:<?= $badgeColor ?>44;">
                                    <!-- Icon pair: earned tier first, then the category's own icon
                                         from the Skill Configurator. Keeping them together means the
                                         eye reads "what level, on what" in one place, instead of the
                                         category mark being buried in the label text. -->
                                    <span style="display:inline-flex; align-items:center; gap:4px;" aria-hidden="true">
                                        <span style="font-size:1.8em; filter: drop-shadow(0 0 2px <?= $badgeColor ?>);"><?= $badgeIcon ?></span>
                                        <?php if (!empty($__cfg['icon'])): ?>
                                            <span style="font-size:1.15em; opacity:.85;"><?= htmlspecialchars($__cfg['icon']) ?></span>
                                        <?php endif; ?>
                                    </span>
                                    <div>
                                        <div style="color:var(--text-primary); font-weight:bold;">
                                            <?= htmlspecialchars($__cfg['skill_name'] ?: $cat) ?>
                                        </div>
                                        <div class="text-muted" style="font-size:0.78em;"><?= htmlspecialchars($cat) ?></div>
                                        <div style="color:<?= $badgeColor ?>;"><?= $badgeTier ?> (<?= round($hours, 1) ?>h)</div>
                                        <?php if ($__next): ?>
                                            <!-- A ladder is only motivating if the next rung is visible. -->
                                            <div class="text-muted" style="font-size:0.78em; margin-top:2px;">
                                                <?= $__next['remaining'] ?>h to <?= htmlspecialchars($__next['tier']['tier']) ?> <?= $__next['tier']['icon'] ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-muted" style="font-size:0.78em; margin-top:2px;">Top tier reached</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if ($__shown === 0): ?>
                                <div class="text-muted fs-sm" style="font-style:italic;">
                                    Your logged hours are on equipment categories that are not mapped to a proficiency yet.
                                    An administrator can map them in Users → Skill Configurator.
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-muted fs-sm" style="font-style:italic;">No machine proficiencies logged yet. Wrench time unlocks automatic badges!</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Active Work Orders -->
            <div class="profile-card">
                <h3>📋 Active Work Orders</h3>
                <?php if(empty($myWorkOrders)): ?>
                    <div class="wcc-empty"><span class="empty-icon" aria-hidden="true">🎉</span><p>No active work orders assigned to you!</p></div>
                <?php else: ?>
                    <div class="timeline-scroll">
                        <?php foreach($myWorkOrders as $wo): ?>
                            <div class="wo-card">
                                <div>
                                    <strong style="color:var(--text-primary);">WO #<?= $wo['wo_id'] ?></strong> — <span style="color:var(--text-accent); font-size:var(--fs-sm);"><?= htmlspecialchars($wo['status']) ?></span><br>
                                    <span class="text-muted fs-sm"><?= htmlspecialchars($wo['equip_name'] ?? 'Unknown Machine') ?></span>
                                </div>
                                <a class="btn btn-sm" href="/_maint/work_orders.php">View</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Activity -->
            <div class="profile-card">
                <h3>📜 Recent Activity Log</h3>
                <?php if(empty($myRecentActivity)): ?>
                    <div class="wcc-empty"><span class="empty-icon" aria-hidden="true">📭</span><p>No recent activity found.</p></div>
                <?php else: ?>
                    <div class="timeline-scroll">
                        <?php foreach($myRecentActivity as $act): ?>
                            <div class="timeline-item">
                                <span class="timeline-time"><?= date('d M Y - H:i', strtotime($act['timestamp_logged'])) ?></span>
                                <div class="timeline-desc">
                                    Logged action on <strong><?= htmlspecialchars($act['ticket_id']) ?></strong>:<br>
                                    <span class="text-muted fs-sm">"<?= htmlspecialchars($act['action_taken']) ?>"</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Skills & Certifications -->
            <div class="profile-card">
                <h3>🎓 Skills &amp; Certifications</h3>
                <!-- The expiry column and the API have always supported a renewal date;
                     only this input was missing, so certifications could never be given
                     one through the UI. Optional — many skills genuinely never expire. -->
                <div class="skill-form" style="flex-wrap:wrap; gap:8px;">
                    <input type="text" id="newSkillName" placeholder="e.g. Level 3 Electrician" aria-label="New skill name">
                    <label for="newSkillExpiry" style="display:flex; align-items:center; gap:6px; font-size:0.85em; color:var(--text-secondary); white-space:nowrap;">
                        Expires
                        <input type="date" id="newSkillExpiry" aria-label="Certification expiry date (optional)"
                               title="Leave blank for a certification that does not expire"
                               style="padding:6px 8px;">
                    </label>
                    <button type="button" class="btn btn-primary" onclick="addSkill()">Add</button>
                </div>
                <p class="text-muted fs-sm" style="margin:-4px 0 12px 0;">
                    Leave the date blank if the certification does not expire. Expiring within
                    <?= WCC_SKILL_EXPIRY_WARN_DAYS ?> days is flagged amber; past the date is flagged red.
                </p>
                <div id="skillsContainer">
                    <?php if(empty($mySkills)): ?>
                        <p id="noSkillsMsg" class="text-muted" style="text-align:center; width:100%;">No skills added yet.</p>
                    <?php else: ?>
                        <?php foreach($mySkills as $skill):
                            // Expiry was stored but never shown, so a lapsed LOTO
                            // authorisation looked identical to a current one.
                            $exp = wcc_skill_expiry($skill['expiry_date'] ?? null);
                        ?>
                            <span class="skill-badge" id="skill-<?= $skill['id'] ?>"
                                  style="border-color: <?= $exp['color'] ?>66;"
                                  title="<?= htmlspecialchars($exp['label']) ?>">
                                🛡️ <span<?= $exp['state'] === 'expired' ? ' style="text-decoration:line-through; opacity:.8;"' : '' ?>><?= htmlspecialchars($skill['skill_name']) ?></span>
                                <?php if ($exp['state'] !== 'none'): ?>
                                    <small style="color:<?= $exp['color'] ?>; font-weight:700; margin-left:4px; white-space:nowrap;">
                                        <?= $exp['icon'] ?> <?= htmlspecialchars($exp['label']) ?>
                                    </small>
                                <?php endif; ?>
                                <span class="del-btn" role="button" tabindex="0" aria-label="Remove skill <?= htmlspecialchars($skill['skill_name']) ?>" onclick="deleteSkill(<?= $skill['id'] ?>)">✖</span>
                            </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Session Timeout -->
            <div class="profile-card">
                <h3>⏱️ Personal Session Timeout</h3>
                <p class="text-muted fs-sm" style="margin-top:0;">
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
                        aria-label="Personal session timeout in minutes"
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
                        <button type="submit" class="btn btn-primary" style="flex:1;">Save My Timeout</button>
                        <button type="button" class="btn" onclick="clearPersonalTimeout()"
                            style="flex:1;border:1px solid var(--danger-border);color:var(--danger);background:transparent;">
                            Use Global Default
                        </button>
                    </div>
                </form>
            </div>

            <!-- Visual Preferences (per-device, localStorage) -->
            <div class="profile-card">
                <h3>🎨 Visual Preferences</h3>
                <p class="text-muted fs-sm" style="margin-top:0;">
                    The animated silk-ribbon background looks great but uses the GPU. Turn it off on
                    older or low-power PCs for a snappier feel. Saved on <strong>this device</strong> only.
                </p>
                <label style="display:flex; align-items:center; justify-content:space-between; gap:14px; cursor:pointer; padding:6px 0;">
                    <span><strong>Silk ribbon background</strong></span>
                    <input type="checkbox" id="waveBgToggle"
                        onchange="if(typeof wccSetWaveBg==='function'){wccSetWaveBg(this.checked);} showToast(this.checked ? 'Ribbon background on.' : 'Ribbon background off.', 'info');"
                        style="width:20px; height:20px; accent-color: var(--text-accent); cursor:pointer;">
                </label>
            </div>
            <script>
                // Reflect this device's saved preference into the toggle (reads localStorage directly,
                // so it works even before the deferred wave script has run).
                (function () {
                    var cb = document.getElementById('waveBgToggle');
                    if (!cb) return;
                    var on = true;
                    try { on = localStorage.getItem('wccWaveBg') !== 'off'; } catch (e) {}
                    cb.checked = on;
                })();
            </script>

        </div>
    </div>
</div>

<script>
    // ---- Skills Management ----
    // Mirrors wcc_skill_expiry() in inc/gamification.php so a freshly added chip looks
    // identical to one rendered by PHP on reload. Thresholds come from the server.
    const WCC_SKILL_WARN_DAYS = <?= WCC_SKILL_EXPIRY_WARN_DAYS ?>;
    function wccSkillExpiryJs(dateStr) {
        if (!dateStr) return { state: 'none', label: '', color: '#94a3b8', icon: '' };
        const today = new Date(); today.setHours(0, 0, 0, 0);
        const exp = new Date(dateStr + 'T00:00:00');
        if (isNaN(exp)) return { state: 'none', label: '', color: '#94a3b8', icon: '' };
        const days = Math.floor((exp - today) / 86400000);
        if (days < 0)  return { state: 'expired',  label: `Expired ${Math.abs(days)}d ago`, color: '#ef4444', icon: '⛔' };
        if (days <= WCC_SKILL_WARN_DAYS)
            return { state: 'expiring', label: days === 0 ? 'Expires today' : `Expires in ${days}d`, color: '#f97316', icon: '⚠️' };
        return { state: 'valid', label: 'Valid until ' + exp.toLocaleDateString(undefined, {day:'numeric', month:'short', year:'numeric'}), color: '#10b981', icon: '' };
    }

    async function addSkill() {
        const nameInput   = document.getElementById('newSkillName');
        const expiryInput = document.getElementById('newSkillExpiry');
        const name   = nameInput.value.trim();
        const expiry = expiryInput ? expiryInput.value : '';
        if(!name) return;

        try {
            const res = await fetch('/api/manage_skills.php', {
                method: 'POST', headers: wccJsonHeaders(),
                body: JSON.stringify(wccWithCsrf({ action: 'add', skill_name: name, expiry_date: expiry || null }))
            });
            const data = await res.json();
            if(data.status === 'success') {
                const container = document.getElementById('skillsContainer');
                const noMsg = document.getElementById('noSkillsMsg');
                if(noMsg) noMsg.style.display = 'none';

                const e = wccSkillExpiryJs(expiry);
                const span = document.createElement('span');
                span.className = 'skill-badge';
                span.id = `skill-${data.id}`;
                span.title = e.label;
                if (e.state !== 'none') span.style.borderColor = e.color + '66';
                const nameHtml = e.state === 'expired'
                    ? `<span style="text-decoration:line-through; opacity:.8;">${name}</span>` : name;
                const expHtml = e.state === 'none' ? ''
                    : ` <small style="color:${e.color}; font-weight:700; white-space:nowrap;">${e.icon} ${e.label}</small>`;
                span.innerHTML = `🛡️ ${nameHtml}${expHtml} <span class="del-btn" role="button" tabindex="0" aria-label="Remove skill ${name}" onclick="deleteSkill(${data.id})">✖</span>`;
                container.appendChild(span);
                nameInput.value = '';
                if (expiryInput) expiryInput.value = '';
                showToast('Skill added.', 'success');
            } else {
                showToast(data.message || 'Could not add skill.', 'error');
            }
        } catch(e) { showToast('Error adding skill.', 'error'); }
    }

    async function deleteSkill(id) {
        openWccConfirm('Remove this skill?', async function() {
            try {
                const res = await fetch('/api/manage_skills.php', {
                    method: 'POST', headers: wccJsonHeaders(),
                    body: JSON.stringify(wccWithCsrf({ action: 'delete', id: id }))
                });
                const data = await res.json();
                if(data.status === 'success') {
                    document.getElementById(`skill-${id}`).remove();
                    if(document.querySelectorAll('.skill-badge').length === 0) {
                        const noMsg = document.getElementById('noSkillsMsg');
                        if(noMsg) noMsg.style.display = 'block';
                    }
                    showToast('Skill removed.', 'success');
                } else {
                    showToast(data.message || 'Could not remove skill.', 'error');
                }
            } catch(e) { showToast('Error removing skill.', 'error'); }
        }, 'Remove Skill');
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
<?= wcc_gamified_help_modal() ?>
</body>
</html>
