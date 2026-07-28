<?php
include __DIR__ . '/../auth.php';
require_perm('manage_users');
require_once __DIR__ . '/../rbac.php';

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/techident.php';
require_once __DIR__ . '/../inc/gamification.php';
$pdo = get_wcc_db_connection();

/**
 * Cryptographically random temporary password for new accounts / admin resets.
 * Shown once to the admin (session flash); user is forced to change on login.
 */
if (!function_exists('wcc_generate_temp_password')) {
    function wcc_generate_temp_password(int $length = 12): string {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $max = strlen($alphabet) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }
        return $out;
    }
}

try {
    // All state-changing POSTs require CSRF (forms + JS-built form posts).
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        wcc_csrf_require($_POST['csrf'] ?? null);
    }

    // Load registration config
    $configStmt = $pdo->query("SELECT * FROM user_registration_config ORDER BY display_order, id");
    $regConfig = [];
    foreach ($configStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $regConfig[$row['field_name']] = $row;
    }

    // Load current role definitions (editable presets)
    $roleDefs = [];
    try {
        $roleRows = $pdo->query("SELECT * FROM role_definitions ORDER BY role_level")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($roleRows as $r) {
            $roleDefs[(int)$r['role_level']] = [
                'name' => $r['name'],
                'permissions' => json_decode($r['permissions_json'] ?? '{}', true) ?: []
            ];
        }
    } catch (Exception $e) {
        // table may not exist yet — will fall back in UI
    }

    // Handle config save from Registration Configurator
    if (isset($_POST['action']) && $_POST['action'] === 'save_registration_config') {
        foreach ($_POST['config'] ?? [] as $field => $data) {
            $is_enabled = isset($data['enabled']) ? 1 : 0;
            $is_required = isset($data['required']) ? 1 : 0;
            $pdo->prepare("UPDATE user_registration_config SET is_enabled = ?, is_required = ? WHERE field_name = ?")
                ->execute([$is_enabled, $is_required, $field]);
        }
        header("Location: users.php?config_saved=1");
        exit;
    }

    // Handle saving Role Presets (the editable defaults for each role level)
    if (isset($_POST['action']) && $_POST['action'] === 'save_role_presets') {
        // Ensure table exists (in case migration not run yet)
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `role_definitions` (
                `role_level` INT NOT NULL PRIMARY KEY,
                `name` VARCHAR(50) NOT NULL,
                `permissions_json` TEXT NOT NULL,
                `description` VARCHAR(255) DEFAULT NULL,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Exception $e) {}

        // Save / update existing roles (supports custom levels too)
        foreach ($_POST['role'] ?? [] as $lvl => $data) {
            $lvl = (int)$lvl;
            if ($lvl < 1) continue;

            $name = trim($data['name'] ?? '');
            if (empty($name)) $name = get_role_name($lvl) ?: "Level $lvl";

            $perms = [];
            foreach (array_keys(PERMISSION_LABELS) as $pkey) {
                $perms[$pkey] = isset($data['perm'][$pkey]);
            }

            $json = json_encode($perms);

            $stmt = $pdo->prepare("
                INSERT INTO role_definitions (role_level, name, permissions_json) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE name = VALUES(name), permissions_json = VALUES(permissions_json)
            ");
            $stmt->execute([$lvl, $name, $json]);
        }

        // Handle NEW custom preset
        if (isset($_POST['new_role']['level']) && isset($_POST['new_role']['name'])) {
            $newLvl = (int)($_POST['new_role']['level'] ?? 0);
            $newName = trim($_POST['new_role']['name'] ?? '');
            if ($newLvl >= 5 && !empty($newName)) {
                $newPerms = [];
                foreach (array_keys(PERMISSION_LABELS) as $pkey) {
                    $newPerms[$pkey] = isset($_POST['new_role']['perm'][$pkey]);
                }
                $json = json_encode($newPerms);

                $stmt = $pdo->prepare("
                    INSERT INTO role_definitions (role_level, name, permissions_json) 
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE name = VALUES(name), permissions_json = VALUES(permissions_json)
                ");
                $stmt->execute([$newLvl, $newName, $json]);
            }
        }
        header("Location: users.php?roles_saved=1");
        exit;
    }    // Handle new user creation - respect config, use defaults for missing
    if (isset($_POST['action']) && $_POST['action'] === 'create_user') {
        $username = trim($_POST['username'] ?? '');
        $badge = trim($_POST['badge_number'] ?? '');
        if (empty($badge)) {
            // Generate default if not provided
            $badge = 'IB-' . str_pad(rand(10000,99999), 5, '0', STR_PAD_LEFT);
        }

        $data = [];
        $fields = ['email', 'full_name', 'phone', 'department', 'workshop_id', 'certifications', 'notes', 'status'];
        foreach ($fields as $f) {
            $val = trim($_POST[$f] ?? '');
            if (empty($val)) {
                // Default anonym based on config
                $cfg = $regConfig[$f] ?? ['is_enabled' => 0];
                if (empty($cfg['is_enabled'])) {
                    $val = 'N/A';
                } else {
                    $val = 'TBD';
                }
            }
            $data[$f] = $val;
        }

        $role     = (int)($_POST['role_level'] ?? 1);
        // One-time temporary password (never the fixed string "password").
        // Shown once via session flash so the admin can hand it to the user.
        $temp_plain = wcc_generate_temp_password();
        $pw         = password_hash($temp_plain, PASSWORD_DEFAULT);

        // Collect permission overrides from checkboxes
        $overrides = [];
        foreach (array_keys(PERMISSION_LABELS) as $pkey) {
            $roleDefaults = get_role_permissions($role);
            $default = $roleDefaults[$pkey] ?? false;
            $posted  = isset($_POST['perm_' . $pkey]);
            if ($posted !== $default) {
                $overrides[$pkey] = $posted;
            }
        }
        $perms_json = !empty($overrides) ? json_encode($overrides) : null;

        $stmt = $pdo->prepare("INSERT INTO users (username, badge_number, email, full_name, phone, department, password_hash, role_level, permissions_json, status, workshop_id, notes, must_change_password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([
            $username, $badge,
            $data['email'], $data['full_name'], $data['phone'], $data['department'],
            $pw, $role, $perms_json, $data['status'], $data['workshop_id'] ?: null, $data['notes'], 
        ]);
        $_SESSION['wcc_temp_pw_flash'] = [
            'username' => $username,
            'password' => $temp_plain,
            'reason'   => 'created',
        ];
        header("Location: /_mgmt/users.php?temp_pw=1");
        exit;
    }

    // Handle password reset
    if (isset($_POST['trigger_reset_password'])) {
        if (!can('reset_passwords')) {
            die("Access Denied: You do not have permission to reset passwords.");
        }
        $edit_uid = (int)($_POST['edit_user_id'] ?? 0);
        if ($edit_uid > 0) {
            $temp_plain = wcc_generate_temp_password();
            $pw = password_hash($temp_plain, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password_hash = ?, must_change_password = 1 WHERE user_id = ?")
                ->execute([$pw, $edit_uid]);
            $uname = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
            $uname->execute([$edit_uid]);
            $_SESSION['wcc_temp_pw_flash'] = [
                'username' => (string)($uname->fetchColumn() ?: $edit_uid),
                'password' => $temp_plain,
                'reason'   => 'reset',
            ];
        }
        header("Location: /_mgmt/users.php?temp_pw=1");
        exit;
    }

    // Handle full user save (profile + permissions) - industry standard inline editor
    if (isset($_POST['action']) && $_POST['action'] === 'save_user') {
        $edit_uid   = (int)($_POST['edit_user_id'] ?? 0);
        
        // Get current role before update (for override comparison)
        $edit_role  = (int)$pdo->query("SELECT role_level FROM users WHERE user_id = $edit_uid")->fetchColumn();

        // Profile fields + optional role change
        $email = trim($_POST['email'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $status = $_POST['status'] ?? 'active';
        $notes = trim($_POST['notes'] ?? '');
        $new_role_level = (int)($_POST['role_level'] ?? $edit_role);

        $pdo->prepare("UPDATE users SET email = ?, full_name = ?, phone = ?, status = ?, notes = ?, role_level = ? WHERE user_id = ?")
            ->execute([$email ?: null, $full_name ?: null, $phone ?: null, $status, $notes ?: null, $new_role_level, $edit_uid]);

        // Permissions - use (possibly new) role for comparison
        $effective_role = $new_role_level ?: $edit_role;
        $overrides  = [];
        foreach (array_keys(PERMISSION_LABELS) as $pkey) {
            $roleDefaults = get_role_permissions($effective_role);
            $default = $roleDefaults[$pkey] ?? false;
            $posted  = isset($_POST['perm_' . $pkey]);
            if ($posted !== $default) {
                $overrides[$pkey] = $posted;
            }
        }
        $perms_json = !empty($overrides) ? json_encode($overrides) : null;
        $pdo->prepare("UPDATE users SET permissions_json = ? WHERE user_id = ?")->execute([$perms_json, $edit_uid]);

        // Save new manual skills assigned by admin.
        // The parallel new_manual_skill_expiry[] array is index-matched to the names, so
        // an admin can set a renewal date at the moment the certification is granted —
        // previously the expiry column could only ever be null from this screen.
        if (isset($_POST['new_manual_skills']) && is_array($_POST['new_manual_skills'])) {
            $expiries = $_POST['new_manual_skill_expiry'] ?? [];
            $ins = $pdo->prepare("INSERT INTO user_skills (user_id, skill_name, expiry_date) VALUES (?, ?, ?)");
            foreach ($_POST['new_manual_skills'] as $i => $sk) {
                if (trim($sk) === '') continue;
                $exp = trim((string)($expiries[$i] ?? ''));
                // Accept only a real Y-m-d; anything else stores as "never expires".
                $valid = ($exp !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $exp) && strtotime($exp)) ? $exp : null;
                $ins->execute([$edit_uid, trim($sk), $valid]);
            }
        }
        
        // Handle deletion of existing manual skills
        if (isset($_POST['delete_manual_skills']) && is_array($_POST['delete_manual_skills'])) {
            $del = $pdo->prepare("DELETE FROM user_skills WHERE user_id = ? AND skill_name = ?");
            foreach ($_POST['delete_manual_skills'] as $sk) {
                $del->execute([$edit_uid, $sk]);
            }
        }

        // Simple audit log (if table exists)
        try {
            $pdo->prepare("INSERT INTO audit_log (actor_user_id, action, entity_type, entity_id, notes) VALUES (?, 'user.update', 'user', ?, 'Profile/permissions updated by admin')")
                ->execute([$_SESSION['user_id'] ?? null, $edit_uid]);
        } catch (Exception $e) {}

        header("Location: /_mgmt/users.php?profile_saved=1");
        exit;
    }

    // Reset permissions to pure role defaults (useful "copy from role" UX)
    if (isset($_POST['action']) && $_POST['action'] === 'reset_permissions') {
        $edit_uid = (int)($_POST['edit_user_id'] ?? 0);
        $pdo->prepare("UPDATE users SET permissions_json = NULL WHERE user_id = ?")->execute([$edit_uid]);
        header("Location: /_mgmt/users.php");
        exit;
    }

    // Delete a user — gated by the dedicated delete_users permission, with guards.
    if (isset($_POST['action']) && $_POST['action'] === 'delete_user') {
        $del_uid = (int)($_POST['edit_user_id'] ?? 0);

        if (!can('delete_users')) {
            header("Location: /_mgmt/users.php?delete_err=" . urlencode('You do not have permission to delete users.'));
            exit;
        }
        if ($del_uid <= 0) {
            header("Location: /_mgmt/users.php?delete_err=" . urlencode('Invalid user.'));
            exit;
        }
        if ($del_uid === (int)($_SESSION['user_id'] ?? 0)) {
            header("Location: /_mgmt/users.php?delete_err=" . urlencode('You cannot delete your own account.'));
            exit;
        }

        // Load target
        $tstmt = $pdo->prepare("SELECT username, role_level FROM users WHERE user_id = ?");
        $tstmt->execute([$del_uid]);
        $target = $tstmt->fetch(PDO::FETCH_ASSOC);
        if (!$target) {
            header("Location: /_mgmt/users.php?delete_err=" . urlencode('User not found.'));
            exit;
        }

        // Never delete the last remaining active Admin (role_level 4).
        if ((int)$target['role_level'] === 4) {
            $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role_level = 4 AND status = 'active'")->fetchColumn();
            if ($adminCount <= 1) {
                header("Location: /_mgmt/users.php?delete_err=" . urlencode('Cannot delete the last active administrator.'));
                exit;
            }
        }

        // Refuse if the user is referenced by records that would break history
        // (POs they created, work orders assigned to them). Suggest deactivation instead.
        $refs = 0;
        try { $s = $pdo->prepare("SELECT COUNT(*) FROM purchase_orders WHERE created_by = ?"); $s->execute([$del_uid]); $refs += (int)$s->fetchColumn(); } catch (Exception $e) {}
        try { $s = $pdo->prepare("SELECT COUNT(*) FROM work_orders WHERE assigned_to = ?"); $s->execute([$del_uid]); $refs += (int)$s->fetchColumn(); } catch (Exception $e) {}
        if ($refs > 0) {
            header("Location: /_mgmt/users.php?delete_err=" . urlencode('This user has ' . $refs . ' linked record(s) (purchase orders / work orders). Set their status to "inactive" instead of deleting, to preserve history.'));
            exit;
        }

        try {
            $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$del_uid]);
            try {
                $pdo->prepare("INSERT INTO audit_log (actor_user_id, action, entity_type, entity_id, notes) VALUES (?, 'user.delete', 'user', ?, ?)")
                    ->execute([$_SESSION['user_id'] ?? null, $del_uid, 'Deleted user ' . ($target['username'] ?? $del_uid)]);
            } catch (Exception $e) {}
            header("Location: /_mgmt/users.php?user_deleted=" . urlencode($target['username'] ?? ('#' . $del_uid)));
            exit;
        } catch (PDOException $e) {
            header("Location: /_mgmt/users.php?delete_err=" . urlencode('Could not delete: the user is still referenced by other records.'));
            exit;
        }
    }

    // Handle saving automated skill config
    if (isset($_POST['action']) && $_POST['action'] === 'save_skill_config') {
        $cat = trim($_POST['equipment_category'] ?? '');
        $skill = trim($_POST['skill_name'] ?? '');
        $icon = trim($_POST['icon'] ?? '⭐');
        if ($cat && $skill) {
            $stmt = $pdo->prepare("INSERT INTO skill_automation_config (equipment_category, skill_name, icon) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE skill_name = VALUES(skill_name), icon = VALUES(icon)");
            $stmt->execute([$cat, $skill, $icon]);
        }
        header("Location: /_mgmt/users.php?skill_config_saved=1");
        exit;
    }

    // Handle deleting automated skill config
    if (isset($_POST['action']) && $_POST['action'] === 'delete_skill_config') {
        $id = (int)($_POST['config_id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM skill_automation_config WHERE id = ?")->execute([$id]);
        }
        header("Location: /_mgmt/users.php?skill_config_deleted=1");
        exit;
    }

    // Fetch skill automation config
    $skillAutoConfigs = [];
    $skillCfgByCat   = [];
    try {
        $sacStmt = $pdo->query("SELECT * FROM skill_automation_config ORDER BY equipment_category ASC");
        $skillAutoConfigs = $sacStmt->fetchAll(PDO::FETCH_ASSOC);
        // Category-keyed view of the same rows, for direct lookup when rendering a
        // user's earned proficiencies.
        foreach ($skillAutoConfigs as $__sc) { $skillCfgByCat[$__sc['equipment_category']] = $__sc; }
    } catch (Exception $e) {}

    $stmt  = $pdo->query("SELECT * FROM users ORDER BY user_id ASC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // For location select in add form
    $workshops = [];
    try {
        $workshops = $pdo->query("SELECT workshop_id, name FROM workshops ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { /* workshops table may not exist yet */ }

    // Calculate Gamified Proficiencies based on Equipment Category.
    //
    // ticket_actions.tech_name holds a display name for records written by the app
    // today, but older/imported rows can hold the username instead. Keying the map
    // on ONE of those makes the whole board read 0 for anyone filed under the other
    // spelling, so the map is keyed on whatever is stored and the per-user lookup
    // below tries every alias (see inc/techident.php).
    $gamifiedStats = [];
    try {
        $gStmt = $pdo->query("
            SELECT ta.tech_name, e.category, SUM(TIMESTAMPDIFF(MINUTE, ta.action_start, ta.action_end))/60 as total_hours
            FROM ticket_actions ta
            JOIN active_tickets at ON at.ticket_id = ta.ticket_id
            JOIN equipment e ON e.equip_id = at.equip_id
            WHERE ta.action_start IS NOT NULL AND ta.action_end IS NOT NULL AND e.category IS NOT NULL AND e.category != ''
            GROUP BY ta.tech_name, e.category
        ");
        foreach($gStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $gamifiedStats[$r['tech_name']][$r['category']] = (float)$r['total_hours'];
        }
    } catch (Exception $e) {}

    /** Merge a user's hours across every name their interventions may be filed under. */
    function wcc_user_gamified(array $stats, array $user): array
    {
        $out = [];
        foreach (wcc_tech_aliases($user) as $alias) {
            foreach (($stats[$alias] ?? []) as $cat => $hrs) {
                $out[$cat] = ($out[$cat] ?? 0) + (float)$hrs;
            }
        }
        return $out;
    }

    // Fetch Manual Skills
    // Fetch Manual Skills assigned to users
    $manualSkills = [];
    try {
        // expiry_date is fetched too: an administrator reviewing the directory needs
        // to see a lapsed certification, which is the whole point of storing it.
        $msStmt = $pdo->query("SELECT u.username, us.skill_name, us.expiry_date
                                 FROM user_skills us JOIN users u ON u.user_id = us.user_id
                             ORDER BY us.expiry_date IS NULL, us.expiry_date ASC");
        foreach($msStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $manualSkills[$row['username']][] = [
                'name'   => $row['skill_name'],
                'expiry' => $row['expiry_date'],
            ];
        }
    } catch (Exception $e) {}

    // Helper for gamification level
    // Ladder lives in inc/gamification.php so every screen renders a tier identically.
    function getGamifiedLevel($hours) { return wcc_gamified_level((float)$hours); }
} catch (PDOException $e) { die("DB Error: " . $e->getMessage()); }
?>
<?php
$page_title = 'User Management';
require_once __DIR__ . '/../inc/head.php';
?>
    <style>
        .filter-token {
            background: var(--panel-bg);
            border: 1px solid var(--text-accent);
            border-radius: 16px;
            padding: 4px 12px;
            font-size: 0.85em;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            animation: fadeIn 0.2s ease-out;
        }
        .filter-token span { font-weight: bold; color: var(--text-accent); }
        .filter-token-close { cursor: pointer; color: #ef4444; font-weight: bold; transition: transform 0.2s; }
        .filter-token-close:hover { transform: scale(1.2); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

        /* Registration Configurator - clean, professional rows (overrides broad .modal-content label rules) */
        .config-list {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin: 12px 0;
        }
        .config-header {
            display: flex;
            align-items: center;
            font-size: 0.72em;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 2px 8px 4px 8px;
            margin-bottom: 2px;
        }
        .config-header .field-col { flex: 1; padding-left: 30px; }
        .config-header .req-col { min-width: 100px; text-align: center; }

        .config-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 10px;
            border: 1px solid var(--panel-border);
            border-radius: 8px;
            background: rgba(255,255,255,0.025);
            transition: background 0.1s ease;
        }
        .config-row:hover {
            background: rgba(255,255,255,0.06);
        }
        .config-row input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--text-accent);
            margin: 0;
            flex-shrink: 0;
            cursor: pointer;
        }

        /* Critical: prevent .modal-content label {display:block; margin-top:15px} from breaking layout */
        .config-row label,
        .config-row .field-name,
        .config-row .req-label {
            display: inline-flex !important;
            align-items: center;
            margin: 0 !important;
            padding: 0 !important;
            font-weight: 400 !important;
            color: inherit;
            line-height: 1.1;
        }

        .config-row .field-toggle {
            display: flex;
            align-items: center;
            gap: 9px;
            flex: 1;
            cursor: pointer;
            min-width: 0;
        }
        .config-row .field-name {
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.95em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .config-row .req-toggle {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.78em;
            color: var(--text-secondary);
            min-width: 100px;
            cursor: pointer;
            white-space: nowrap;
        }
        .config-row .req-toggle input {
            width: 15px;
            height: 15px;
            accent-color: var(--text-accent);
            margin: 0;
        }
        .config-row .req-label {
            font-size: 0.95em;
        }

        .config-actions button {
            width: auto !important;
            margin: 0 !important;
            min-width: 110px;
        }

        /* Role Presets modal polish - improved layout */
        #rolePresetsModal .modal-content label {
            margin-top: 0 !important;
            font-weight: normal !important;
            display: flex !important;
            font-size: 0.82em;
        }

        .role-card {
            border: 1px solid var(--panel-border);
            border-radius: 10px;
            padding: 12px 14px;
            background: rgba(255,255,255,0.02);
            margin-bottom: 12px;
        }
        .role-card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .role-level-badge {
            font-size: 0.7em;
            background: var(--text-accent);
            color: #0f172a;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 700;
            flex-shrink: 0;
        }
        .role-name-input {
            flex: 1;
            font-weight: 600;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            color: var(--text-primary);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.95em;
        }

        /* Accordion for permission categories */
        .perm-category details {
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 6px;
            margin-bottom: 4px;
            background: rgba(0,0,0,0.1);
        }
        .perm-category summary {
            cursor: pointer;
            padding: 6px 10px;
            font-size: 0.8em;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            list-style: none;
            user-select: none;
        }
        .perm-category summary:hover {
            background: rgba(255,255,255,0.05);
        }
        .perm-category summary::marker,
        .perm-category summary::-webkit-details-marker {
            display: none;
        }
        .perm-category details[open] summary {
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 4px;
        }
        .perm-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3px 10px;
            padding: 4px 10px 8px;
            font-size: 0.82em;
        }
        .perm-grid label {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }

        .new-preset-section {
            margin-top: 20px;
            padding-top: 12px;
            border-top: 2px dashed var(--panel-border);
        }
    </style>
    <script>
        let activeFilters = [];
        let filterIdCounter = 0;
        let activeColumnIndex = -1;

        function getColumnName(index) {
            let th = document.querySelector("#usersTable thead tr").children[index];
            return th ? (th.innerText || th.textContent).trim() : "Column";
        }

        function createFilterToken(colIndex, query) {
            let colName = getColumnName(colIndex);
            let id = 'filter-' + filterIdCounter++;
            activeFilters.push({ id: id, colIndex: colIndex, query: query.toUpperCase() });
            
            let area = document.getElementById('activeFiltersArea');
            let token = document.createElement('div');
            token.id = id;
            token.className = 'filter-token';
            token.innerHTML = '<span>' + colName + ':</span> ' + query + ' <div class="filter-token-close" onclick="removeFilterToken(\'' + id + '\')">✖</div>';
            area.appendChild(token);
        }

        function removeFilterToken(id) {
            activeFilters = activeFilters.filter(f => f.id !== id);
            let token = document.getElementById(id);
            if (token) token.remove();
            filterTable();
        }

        function handleSearchInput(ev) {
            if (ev.key === 'Enter' && activeColumnIndex > -1 && ev.target.value.trim() !== '') {
                lockToken();
            } else {
                filterTable();
            }
        }

        function lockToken() {
            var input = document.getElementById("ledgerSearch");
            var query = input.value.trim();
            if (query !== '' && activeColumnIndex > -1) {
                createFilterToken(activeColumnIndex, query);
                input.value = '';
                resetSearchPosition();
            }
        }

        function filterTable() {
            var input = document.getElementById("ledgerSearch");
            var globalFilter = input.value.toUpperCase();
            var table = document.getElementById("usersTable");
            var tr = table.getElementsByClassName("parent-row");

            for (let i = 0; i < tr.length; i++) {
                let matchFound = true;
                let tds = tr[i].getElementsByTagName("td");

                for (let f of activeFilters) {
                    let cell = tds[f.colIndex];
                    if (cell) {
                        let txt = cell.textContent || cell.innerText;
                        if (txt.toUpperCase().indexOf(f.query) === -1) {
                            matchFound = false;
                            break;
                        }
                    }
                }

                if (matchFound && globalFilter !== "") {
                    if (activeColumnIndex > -1) {
                        let cell = tds[activeColumnIndex];
                        if (cell) {
                            let txt = cell.textContent || cell.innerText;
                            if (txt.toUpperCase().indexOf(globalFilter) === -1) matchFound = false;
                        }
                    } else {
                        let globalMatch = false;
                        for (let j = 0; j < tds.length; j++) { 
                            if (tds[j]) {
                                let txt = tds[j].textContent || tds[j].innerText;
                                if (txt.toUpperCase().indexOf(globalFilter) > -1) {
                                    globalMatch = true;
                                    break;
                                }
                            }
                        }
                        if (!globalMatch) matchFound = false;
                    }
                }

                if (matchFound) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                    tr[i].classList.remove('is-expanded');
                    let nextRow = tr[i].nextElementSibling;
                    if (nextRow && nextRow.classList.contains('child-row')) {
                        nextRow.style.display = "none";
                    }
                }
            }
        }

        function allowDrop(ev) { ev.preventDefault(); }
        function dragSearch(ev) { ev.dataTransfer.setData("text", ev.target.id); }
        function dropSearch(ev, thElement) {
            ev.preventDefault();
            var container = document.getElementById("searchContainerOrig");
            var input = document.getElementById("ledgerSearch");
            var lockBtn = document.getElementById("lockTokenBtn");
            var colIndex = Array.from(thElement.parentNode.children).indexOf(thElement);
            var query = input.value.trim();

            if (query !== '') {
                createFilterToken(colIndex, query);
                input.value = '';
                resetSearchPosition();
            } else {
                thElement.appendChild(container);
                container.style.marginTop = '10px';
                input.style.width = '100%';
                input.placeholder = 'Type & click 📌 to Lock';
                activeColumnIndex = colIndex;
                lockBtn.style.display = 'block';
                input.focus();
                filterTable();
            }
        }

        function resetSearchPosition() {
            let wrapper = document.getElementById('searchWrapper');
            let container = document.getElementById('searchContainerOrig');
            let input = document.getElementById('ledgerSearch');
            let lockBtn = document.getElementById('lockTokenBtn');
            
            wrapper.appendChild(container);
            container.style.marginTop = '0';
            input.style.width = '';
            input.placeholder = 'Search users... (Drag to column)';
            lockBtn.style.display = 'none';
            activeColumnIndex = -1;
            filterTable();
        }

        function exportUsers() {
            const table = document.getElementById('usersTable');
            let csv = [];
            const rows = table.querySelectorAll('tr');
            rows.forEach(row => {
                const cols = row.querySelectorAll('th, td');
                let rowData = [];
                cols.forEach(col => {
                    let text = col.innerText.trim().replace(/"/g, '""');
                    rowData.push('"' + text + '"');
                });
                csv.push(rowData.join(','));
            });
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.href = url;
            link.download = 'users_export.csv';
            link.click();
            URL.revokeObjectURL(url);
        }

        function wccUsersCsrfField() {
            const t = (typeof wccCsrfToken === 'function' ? wccCsrfToken() : (window.WCC_CSRF || ''));
            return `<input type="hidden" name="csrf" value="${t.replace(/"/g, '&quot;')}">`;
        }

        function resetPerms(userId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = wccUsersCsrfField() + `
                <input type="hidden" name="action" value="reset_permissions">
                <input type="hidden" name="edit_user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function resetPassword(userId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = wccUsersCsrfField() + `
                <input type="hidden" name="trigger_reset_password" value="1">
                <input type="hidden" name="edit_user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function deleteUser(userId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = wccUsersCsrfField() + `
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="edit_user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function filterPerms(input, userId) {
            const grid = document.getElementById('permGrid-' + userId);
            const term = input.value.toLowerCase();
            grid.querySelectorAll('label').forEach(label => {
                const text = label.textContent.toLowerCase();
                label.style.display = text.includes(term) ? '' : 'none';
            });
        }
    </script>
    <style>
        /* Tighten table to fit exactly inside the 1400px container */
        #usersTable th, #usersTable td {
            padding: 10px 8px;
            font-size: 0.9em;
        }
    </style>
<?php include __DIR__ . '/../nav.php'; ?>

<div class="dashboard-container">
    <?php
    $temp_flash = $_SESSION['wcc_temp_pw_flash'] ?? null;
    if ($temp_flash && isset($_GET['temp_pw'])):
        unset($_SESSION['wcc_temp_pw_flash']);
        $tf_user = htmlspecialchars((string)($temp_flash['username'] ?? ''), ENT_QUOTES, 'UTF-8');
        $tf_pw   = htmlspecialchars((string)($temp_flash['password'] ?? ''), ENT_QUOTES, 'UTF-8');
        $tf_why  = ($temp_flash['reason'] ?? '') === 'created' ? 'created' : 'reset';
    ?>
        <div style="background: rgba(16, 185, 129, 0.15); color: var(--text-primary); padding: 14px 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(16,185,129,0.35); text-align: left; max-width: 640px; margin-left: auto; margin-right: auto;">
            <strong style="color:#10b981;">✅ Temporary password <?= $tf_why === 'created' ? 'for new user' : 'after reset' ?></strong>
            <div style="margin-top:8px; font-size:0.95em;">
                User <code><?= $tf_user ?></code> must change this on first login.
            </div>
            <div style="margin-top:10px; font-size:1.15em; letter-spacing:0.04em;">
                <code style="background:var(--surface-1); padding:6px 12px; border-radius:6px; user-select:all;"><?= $tf_pw ?></code>
            </div>
            <div style="margin-top:8px; font-size:0.85em; color:var(--text-muted);">Copy it now — it will not be shown again.</div>
        </div>
    <?php elseif (isset($_GET['reset_success'])): ?>
        <div style="background: rgba(16, 185, 129, 0.2); color: #10b981; padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
            ✅ Password reset completed.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['user_deleted'])): ?>
        <div style="background: var(--success-bg); color: var(--success); padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; font-size:0.9em; border:1px solid var(--success-border);">
            🗑 User <strong><?= htmlspecialchars($_GET['user_deleted']) ?></strong> was deleted.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['delete_err'])): ?>
        <div style="background: var(--danger-bg); color: var(--danger); padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; font-size:0.9em; border:1px solid var(--danger-border);">
            ⛔ <?= htmlspecialchars($_GET['delete_err']) ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['config_saved'])): ?>
        <div style="background: rgba(16, 185, 129, 0.15); color: #10b981; padding: 9px 14px; border-radius: 8px; margin-bottom: 16px; font-size:0.9em; border:1px solid rgba(16,185,129,0.3);">
            ✅ Registration configuration saved. New user forms will now respect the updated field settings.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['roles_saved'])): ?>
        <div style="background: rgba(59, 130, 246, 0.15); color: #60a5fa; padding: 9px 14px; border-radius: 8px; margin-bottom: 16px; font-size:0.9em; border:1px solid rgba(59,130,246,0.3);">
            ✅ Role presets saved. These defaults will be used for new users and when resetting permissions on existing users.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['profile_saved'])): ?>
        <div style="background: rgba(16, 185, 129, 0.15); color: #10b981; padding: 9px 14px; border-radius: 8px; margin-bottom: 16px; font-size:0.9em; border:1px solid rgba(16, 185, 129, 0.3);">
            ✅ User profile and skills updated successfully.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['skill_config_saved'])): ?>
        <div style="background: rgba(16, 185, 129, 0.15); color: #10b981; padding: 9px 14px; border-radius: 8px; margin-bottom: 16px; font-size:0.9em; border:1px solid rgba(16, 185, 129, 0.3);">
            ✅ Skill automation configuration saved successfully.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['skill_config_deleted'])): ?>
        <div style="background: rgba(239, 68, 68, 0.15); color: #ef4444; padding: 9px 14px; border-radius: 8px; margin-bottom: 16px; font-size:0.9em; border:1px solid rgba(239, 68, 68, 0.3);">
            ✅ Skill automation configuration deleted.
        </div>
    <?php endif; ?>
    <div class="page-header" style="margin-bottom:10px;">
        <h1>User Management (RBAC)</h1>
        <div style="display:flex; gap:10px; align-items:center;">
            <?php if (can('manage_settings')): ?>
            <a href="/_mgmt/admin_panel.php" class="nav-btn" style="white-space:nowrap;">← Return to Admin Panel</a>
            <?php endif; ?>
            <div id="searchWrapper">
                <div id="searchContainerOrig" style="display:inline-block; position:relative; width:100%;" draggable="true" ondragstart="dragSearch(event)">
                    <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); pointer-events:none; font-size:1.1em;">🔍</span>
                        <input type="text" id="ledgerSearch" onkeyup="handleSearchInput(event)" ondblclick="resetSearchPosition()" placeholder="Search users... (Drag to column)" style="padding:8px 35px 8px 35px; border-radius:20px; border: 1px solid var(--text-accent); background:var(--input-bg); color:var(--text-primary); transition: all 0.3s; box-sizing: border-box;">
                    <span id="lockTokenBtn" onclick="lockToken()" style="display:none; position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:1.1em; transition: transform 0.2s;" title="Lock Token" onmouseover="this.style.transform='translateY(-50%) scale(1.2)'" onmouseout="this.style.transform='translateY(-50%) scale(1)'">🔒</span>
                </div>
            </div>
        </div>
    </div>
    <div style="display:flex; gap:10px; margin-bottom:15px; flex-wrap:wrap;">
        <button class="pill-btn pill-success" onclick="document.getElementById('addModal').style.display='block'">+ Add User</button>
        <button class="pill-btn pill-info" onclick="showRegistrationConfigurator()">Registration Configurator</button>
        <button class="pill-btn pill-info" onclick="showRolePresets()">Role Presets</button>
        <button class="pill-btn pill-info" onclick="exportUsers()">Export CSV</button>
        <button class="pill-btn pill-info" onclick="document.getElementById('skillConfigModal').style.display='block'">Skill Configurator</button>
    </div>
    <div id="activeFiltersArea" style="display:flex; gap:8px; margin-bottom:15px; flex-wrap:wrap; min-height:30px;"></div>
    
    <div class="table-container" style="overflow-x:auto;">
        <table class="data-table" id="usersTable">
            <thead>
                <tr>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Badge #</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Email</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Full Name</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Username</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Role</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Status</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Last Login</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Location</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Skills<?= wcc_gamified_help_button() ?></th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Created</th>
                </tr>
            </thead>
            <tbody>
        <?php foreach($items as $u): 
            $role_name = get_role_name((int)$u['role_level']);
            $perms = wcc_get_permissions((int)$u['role_level'], $u['permissions_json']);
        ?>
            <tr class="parent-row" data-id="<?= $u['user_id'] ?>">
                <td style="white-space:nowrap;">
                    <span class="row-arrow">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </span>
                    <?= htmlspecialchars($u['badge_number'] ?? 'N/A') ?>
                </td>
                <td><?= !empty($regConfig['email']['is_enabled']) ? htmlspecialchars($u['email'] ?? 'N/A') : 'N/A' ?></td>
                <td><?= !empty($regConfig['full_name']['is_enabled']) ? htmlspecialchars($u['full_name'] ?? 'N/A') : 'N/A' ?></td>
                <td style="font-weight:bold; color:var(--text-accent);"><?= htmlspecialchars($u['username']) ?></td>
                <td style="white-space:nowrap;"><span style="background:rgba(255,255,255,0.1); padding:4px 8px; border-radius:12px; font-size:0.85em; font-weight:600; color:var(--text-accent);">L<?= $u['role_level'] ?> — <?= $role_name ?></span></td>
                <td>
                    <?php $st = $u['status'] ?? 'active'; $stColor = $st === 'active' ? '#10b981' : ($st === 'inactive' ? '#ef4444' : '#f59e0b'); ?>
                    <span style="background:<?= $stColor ?>22; color:<?= $stColor ?>; padding:2px 8px; border-radius:10px; font-size:0.8em; font-weight:600; text-transform:uppercase;"><?= htmlspecialchars($st) ?></span>
                </td>
                <td style="white-space:nowrap;"><?= !empty($u['last_login']) ? date('Y-m-d H:i', strtotime($u['last_login'])) : '—' ?></td>
                <td><?= !empty($regConfig['workshop_id']['is_enabled']) ? htmlspecialchars($u['workshop_id'] ?? 'N/A') : 'N/A' ?></td>
                <td style="white-space:nowrap;">
                    <?php
                        $userManualSkills = $manualSkills[$u['username']] ?? [];
                        $mCount = count($userManualSkills);
                        
                        $userGamified = wcc_user_gamified($gamifiedStats, $u);
                        $gTokens = [];
                        foreach ($skillAutoConfigs as $sconf) {
                            $cat = $sconf['equipment_category'];
                            if (isset($userGamified[$cat]) && $userGamified[$cat] > 0) {
                                $hrs = $userGamified[$cat];
                                $lvl = getGamifiedLevel($hrs);
                                // Tier icon first, then the category icon, matching the chip
                                // order on My Profile — the same proficiency must not read
                                // differently depending on which screen you are looking at.
                                $gTokens[] = "<span style='background:var(--panel-bg); color:var(--text-accent); border-radius:8px; padding:4px 8px; font-size:0.9em; display:inline-block; border: 1px solid var(--text-accent);'>{$lvl['icon']} {$sconf['icon']} " . htmlspecialchars($sconf['skill_name']) . " <span style='color:var(--text-secondary);'>(" . round($hrs, 1) . "h)</span></span>";
                            }
                        }
                        $gCount = count($gTokens);
                    ?>
                    <div style="display:flex; gap:4px; align-items:center;">
                        <button type="button" class="pill-btn pill-info pill-sm" onclick="showUserSkills('Manual Skills (<?= htmlspecialchars(addslashes($u['username'])) ?>)', 'manual-skills-<?= $u['user_id'] ?>'); event.stopPropagation();" title="Manual Skills">🛠️ <?= $mCount ?></button>
                        <button type="button" class="pill-btn pill-warning pill-sm" onclick="showUserSkills('Gamified Skills (<?= htmlspecialchars(addslashes($u['username'])) ?>)', 'gamified-skills-<?= $u['user_id'] ?>'); event.stopPropagation();" title="Gamified Skills">🏆 <?= $gCount ?></button>
                    </div>
                    
                    <div id="manual-skills-<?= $u['user_id'] ?>" style="display:none;">
                        <?php 
                        if ($mCount > 0) {
                            // Shared chip renderer — shows lapsed / expiring-soon state.
                            foreach ($userManualSkills as $sk) {
                                echo "<div style='margin-bottom:8px;'>" . wcc_skill_chip($sk['name'], $sk['expiry']) . "</div>";
                            }
                        } else {
                            echo "<div style='color:var(--text-secondary); font-style:italic;'>No manual skills.</div>";
                        }
                        ?>
                    </div>
                    <div id="gamified-skills-<?= $u['user_id'] ?>" style="display:none;">
                        <?php
                        if ($gCount > 0) {
                            foreach ($gTokens as $gt) echo "<div style='margin-bottom:8px;'>" . $gt . "</div>";
                        } else {
                            echo "<div style='color:var(--text-secondary); font-style:italic;'>No gamified skills.</div>";
                        }
                        ?>
                    </div>
                </td>
                <td style="white-space:nowrap;"><?= htmlspecialchars($u['created_at']) ?></td>
            </tr>
            <tr class="child-row" id="details-<?= $u['user_id'] ?>">
                <td colspan="9">
                    <div class="child-content" style="display: grid; grid-template-columns: 1fr; gap: 20px;">
                        <span style="font-size: 0.9em; color:var(--text-secondary);">Badge: <strong style="color:var(--text-primary);"><?= htmlspecialchars($u['badge_number'] ?? 'N/A') ?></strong> &nbsp;|&nbsp; Internal ID: <strong><?= htmlspecialchars($u['user_id']) ?></strong> &nbsp;|&nbsp; Role: <strong style="color:var(--text-accent);"><?= $role_name ?></strong></span>
                        <form method="POST" style="margin:0;">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(wcc_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="save_user">
                            <input type="hidden" name="edit_user_id" value="<?= $u['user_id'] ?>">

                            <!-- Quick Profile Edit (industry leading: editable core fields inline) -->
                            <div style="margin-bottom:15px; padding:10px; background:var(--panel-bg); border-radius:8px; border:1px solid var(--panel-border);">
                                <div style="font-weight:600; margin-bottom:6px; color:var(--text-accent);">Profile</div>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:0.85em;">
                                    <div>
                                        <label>Role Level</label>
                                        <select name="role_level" style="width:100%; padding:4px; border:1px solid var(--input-border); border-radius:4px; background:var(--input-bg); color:var(--text-primary);">
                                            <?php foreach (get_all_roles() as $rl => $rn): ?>
                                                <option value="<?= $rl ?>" <?= (int)$u['role_level'] === $rl ? 'selected' : '' ?>><?= $rl ?> — <?= htmlspecialchars($rn) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label>Email</label>
                                        <input type="email" name="email" value="<?= htmlspecialchars($u['email'] ?? '') ?>" style="width:100%; padding:4px; border:1px solid var(--input-border); border-radius:4px; background:var(--input-bg); color:var(--text-primary);">
                                    </div>
                                    <div>
                                        <label>Full Name</label>
                                        <input type="text" name="full_name" value="<?= htmlspecialchars($u['full_name'] ?? '') ?>" style="width:100%; padding:4px; border:1px solid var(--input-border); border-radius:4px; background:var(--input-bg); color:var(--text-primary);">
                                    </div>
                                    <div>
                                        <label>Phone</label>
                                        <input type="text" name="phone" value="<?= htmlspecialchars($u['phone'] ?? '') ?>" style="width:100%; padding:4px; border:1px solid var(--input-border); border-radius:4px; background:var(--input-bg); color:var(--text-primary);">
                                    </div>
                                    <div>
                                        <label>Status</label>
                                        <select name="status" style="width:100%; padding:4px; border:1px solid var(--input-border); border-radius:4px; background:var(--input-bg); color:var(--text-primary);">
                                            <option value="active" <?= ($u['status'] ?? 'active') == 'active' ? 'selected' : '' ?>>Active</option>
                                            <option value="inactive" <?= ($u['status'] ?? '') == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                            <option value="pending" <?= ($u['status'] ?? '') == 'pending' ? 'selected' : '' ?>>Pending</option>
                                        </select>
                                    </div>
                                </div>
                                <div style="margin-top:6px;">
                                    <label style="font-size:0.8em;">Notes</label>
                                    <textarea name="notes" rows="1" style="width:100%; padding:4px; border:1px solid var(--input-border); border-radius:4px; background:var(--input-bg); color:var(--text-primary); font-size:0.8em;"><?= htmlspecialchars($u['notes'] ?? '') ?></textarea>
                                </div>
                            </div>
                            
                            <!-- Skills & Gamification achievements -->
                            <?php 
                            // Same alias-aware lookup as the badge count above — keying
                            // these two differently is what made the count and the
                            // detail panel disagree.
                            $user_stats = wcc_user_gamified($gamifiedStats, $u);
                            $user_man_skills = $manualSkills[$u['username']] ?? [];
                            ?>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                                <!-- Gamified Proficiencies Box -->
                                <div style="padding:10px; background:var(--panel-bg); border-radius:8px; border:1px solid var(--panel-border);">
                                    <div style="font-weight:600; margin-bottom:10px; color:var(--text-accent);">Gamified Proficiencies</div>
                                    <div style="display:flex; flex-wrap:wrap; gap:10px;">
                                        <?php if (!empty($user_stats)): ?>
                                            <?php
                                            $__shownProf = 0;
                                            foreach ($user_stats as $eq_type => $hrs):
                                                // Only mapped categories score, and the chip must show the
                                                // configured icon + proficiency NAME — this panel was still
                                                // rendering the bare category with just a tier medal, so the
                                                // same proficiency looked different here than on My Profile
                                                // and in the badge popover.
                                                if ($hrs > 0 && isset($skillCfgByCat[$eq_type])) {
                                                    $__cfg = $skillCfgByCat[$eq_type];
                                                    $__shownProf++;
                                                    $__t = wcc_gamified_level((float)$hrs);
                                                    $level = $__t['tier']; $badge_color = $__t['color']; $icon = $__t['icon'];
                                                    $__catIcon = trim((string)($__cfg['icon'] ?? ''));

                                                    echo '<div style="background:rgba(255,255,255,0.05); border:1px solid '.$badge_color.'; padding:6px 12px; border-radius:12px; font-size:0.85em; display:flex; align-items:center; gap:10px; box-shadow: 0 2px 10px '.str_replace(')', ', 0.1)', str_replace('rgb', 'rgba', $badge_color)).';">';
                                                    // Tier icon first, then the category icon — same order as My Profile.
                                                    echo '<span style="display:inline-flex; align-items:center; gap:3px;">';
                                                    echo '<span style="font-size:1.6em;">'.$icon.'</span>';
                                                    if ($__catIcon !== '') echo '<span style="font-size:1.05em; opacity:.85;">'.htmlspecialchars($__catIcon).'</span>';
                                                    echo '</span> ';
                                                    echo '<div><strong style="color:var(--text-primary); font-size:1.1em;">'.htmlspecialchars($__cfg['skill_name'] ?: $eq_type).'</strong><br>';
                                                    echo '<span style="color:var(--text-secondary); font-size:0.75em;">'.htmlspecialchars($eq_type).'</span><br>';
                                                    echo '<span style="color:'.$badge_color.'; font-weight:bold; text-transform:uppercase; font-size:0.9em;">'.$level.'</span> <span style="color:var(--text-secondary); font-size:0.8em;">('.round($hrs,1).'h)</span></div>';
                                                    echo '</div>';
                                                }
                                            endforeach; ?>
                                            <?php if ($__shownProf === 0): ?>
                                                <div style="color:var(--text-secondary); font-size:0.85em; font-style:italic; padding:6px;">
                                                    Logged hours are on categories not mapped in the Skill Configurator, so nothing has been earned yet.
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div style="color:var(--text-secondary); font-size:0.85em; font-style:italic; padding:6px;">No machine proficiencies logged yet.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Manual Skills Box -->
                                <div style="padding:10px; background:var(--panel-bg); border-radius:8px; border:1px solid var(--panel-border); position:relative;">
                                    <div style="font-weight:600; margin-bottom:10px; color:var(--text-accent);">Manual Skills</div>
                                    
                                    <div id="ms_container_<?= $u['user_id'] ?>">
                                        <div style="display:flex; flex-direction:column; gap:8px;">
                                            <?php if (!empty($user_man_skills)): ?>
                                                <?php foreach ($user_man_skills as $idx => $ms):
    // $ms is ['name'=>..., 'expiry'=>...] since the fetch now carries expiry_date.
    $msName = is_array($ms) ? ($ms['name'] ?? '') : (string)$ms;
    $msExp  = wcc_skill_expiry(is_array($ms) ? ($ms['expiry'] ?? null) : null);
?>
                                                    <div style="background:rgba(255,255,255,0.05); border:1px solid var(--panel-border); padding:6px 12px; border-radius:8px; font-size:0.85em; display:flex; align-items:center; justify-content:space-between;">
                                                        <div style="display:flex; align-items:center; gap:8px;">
                                                            <span style="font-size:1.3em;">🛠️</span>
                                                            <strong style="color:var(--text-primary); font-size:1.1em;<?= $msExp['state'] === 'expired' ? ' text-decoration:line-through; opacity:.8;' : '' ?>"><?= htmlspecialchars($msName) ?></strong>
                                                            <?php if ($msExp['state'] !== 'none'): ?>
                                                                <small style="color:<?= $msExp['color'] ?>; font-weight:700; white-space:nowrap;"><?= $msExp['icon'] ?> <?= htmlspecialchars($msExp['label']) ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                        <button type="button" style="background:rgba(239, 68, 68, 0.1); border:1px solid rgba(239, 68, 68, 0.3); color:#ef4444; border-radius:4px; padding:4px 8px; cursor:pointer; font-size:0.9em; transition:all 0.2s;" onmouseover="this.style.background='rgba(239,68,68,0.2)'" onmouseout="this.style.background='rgba(239,68,68,0.1)'" onclick="if(confirm('Mark this skill for removal? It will be permanently deleted when you click Save Profile & Permissions.')) { this.parentElement.innerHTML = '<input type=\'hidden\' name=\'delete_manual_skills[]\' value=\'<?= htmlspecialchars($msName, ENT_QUOTES) ?>\'>'; }">
                                                            Remove
                                                        </button>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div style="color:var(--text-secondary); font-size:0.85em; font-style:italic; padding:6px;">No manual skills logged.</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div style="margin-top:10px;">
                                        <button type="button" class="pill-btn pill-warning pill-sm" onclick="addNewSkillInput(<?= $u['user_id'] ?>)">+ Add New Skill</button>
                                    </div>
                                </div>
                            </div>
                            

                            <?php 
                            $grouped = [];
                            foreach(PERMISSION_LABELS as $key => $meta) {
                                $grouped[$meta['group']][$key] = $meta['label'];
                            }
                            ?>
                            <!-- 
                            These checkboxes control access to specific pages/zones in the app.
                            - 'manage_settings' unlocks admin_panel.php + app_settings.php + some setup vaults.
                            - 'manage_users' unlocks the RBAC user editor.
                            - 'view_xxx' / 'manage_xxx' control visibility + hard gates on the corresponding pages (see require_perm calls).
                            - my_profile.php is deliberately NOT gated here (self-service exception for all roles).
                            -->
                            <input type="text" id="permSearch-<?= $u['user_id'] ?>" placeholder="Search permissions..." onkeyup="filterPerms(this, <?= $u['user_id'] ?>)" style="margin-bottom:8px; width:100%; padding:5px; border-radius:4px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary); font-size:0.85em;">
                            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;" id="permGrid-<?= $u['user_id'] ?>">
                                <?php foreach($grouped as $group_name => $group_perms): ?>
                                    <div style="margin-bottom: 10px;">
                                        <div style="font-size:0.8em; text-transform:uppercase; color:var(--text-secondary); margin-bottom:5px; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:3px; letter-spacing:1px;"><?= htmlspecialchars($group_name) ?></div>
                                        <?php foreach($group_perms as $pkey => $plabel): 
                                            $has_perm = $perms[$pkey] ?? false;
                                        ?>
                                            <label style="display:flex; align-items:center; gap:8px; font-size:0.9em; margin-bottom:6px; cursor:pointer;">
                                                <input type="checkbox" name="perm_<?= $pkey ?>" <?= $has_perm ? 'checked' : '' ?> style="width:auto; margin:0;">
                                                <span style="<?= $has_perm ? 'color: #10b981; font-weight:bold;' : 'color: var(--text-primary);' ?>">
                                                    <?= $has_perm ? '✓ ' : '' ?><?= htmlspecialchars($plabel) ?>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div> <!-- end permGrid -->
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; gap: 8px; flex-wrap: wrap;">
                                <button type="submit" class="pill-btn pill-success">💾 Save Profile & Permissions</button>
                                <button type="button" class="pill-btn pill-warning" onclick="openWccConfirm('Reset permissions to role defaults?', function() { resetPerms(<?= $u['user_id'] ?>); }, 'Reset Perms');">↺ Reset Perms to Role</button>
                                <?php if(can('reset_passwords')): ?>
                                <button type="button" class="pill-btn pill-danger" onclick="openWccConfirm('Reset this user\\'s password? A one-time temporary password will be shown once; they must change it on next login.', function() { resetPassword(<?= $u['user_id'] ?>); }, 'Reset Password');">Reset Password</button>
                                <?php endif; ?>
                                <?php if(can('delete_users') && (int)$u['user_id'] !== (int)($_SESSION['user_id'] ?? 0)): ?>
                                <button type="button" class="pill-btn pill-danger" onclick="openWccConfirm('Permanently delete user &quot;<?= htmlspecialchars(addslashes($u['username'])) ?>&quot;? This cannot be undone. (Users with linked purchase/work orders are blocked — deactivate those instead.)', function() { deleteUser(<?= $u['user_id'] ?>); }, 'Delete User');">🗑 Delete User</button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function addNewSkillInput(uid) {
    const container = document.getElementById('ms_container_' + uid);
    const div = document.createElement('div');
    div.style.cssText = 'display:flex; gap:5px; margin-top:5px; align-items:center;';
    // The expiry input is index-matched to the name input by the POST handler, so both
    // must be added and removed together — hence one row per certification.
    div.innerHTML = `
        <span style="font-size:1.3em;">🛠️</span>
        <input type="text" name="new_manual_skills[]" placeholder="e.g. Master Electrician" style="flex:1; padding:6px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary); border-radius:4px; font-size:0.85em;" required>
        <input type="date" name="new_manual_skill_expiry[]" title="Expiry date (optional — leave blank if it never expires)" aria-label="Expiry date (optional)" style="padding:6px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary); border-radius:4px; font-size:0.85em;">
        <button type="button" style="background:transparent; border:none; color:#ef4444; font-size:1.2em; cursor:pointer;" onclick="this.parentElement.remove()">&times;</button>
    `;
    container.appendChild(div);
}
</script>

<!-- Add User Modal -->
<div id="addModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="document.getElementById('addModal').style.display='none'">&times;</span>
    <h2 style="color:var(--text-primary); margin-top:0;">Register New User</h2>
    <form method="POST">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(wcc_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="action" value="create_user">
        
        <!-- Always collect badge and username + role -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <div>
                <label style="color:var(--text-secondary); display:block; margin-top:10px;">Badge Number (I-Badge) *</label>
                <input type="text" name="badge_number" placeholder="e.g. IB-01234" required style="width:100%; box-sizing:border-box; padding:8px; border-radius:4px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary);">
            </div>
            <div>
                <label style="color:var(--text-secondary); display:block; margin-top:10px;">Username (login) *</label>
                <input type="text" name="username" required style="width:100%; box-sizing:border-box; padding:8px; border-radius:4px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary);">
            </div>
        </div>

        <label style="color:var(--text-secondary); display:block; margin-top:10px;">Role Level *</label>
        <select name="role_level" required style="width:100%; box-sizing:border-box; padding:8px; margin-bottom: 10px; border-radius:4px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary);">
            <?php 
            $roleList = get_all_roles();
            foreach ($roleList as $lvl => $nm): 
            ?>
                <option value="<?= $lvl ?>"> <?= $lvl ?> — <?= htmlspecialchars($nm) ?></option>
            <?php endforeach; ?>
        </select>

        <?php 
        // Dynamic fields based on config
        $dynamicFields = [
            'full_name' => ['label' => 'Full Name', 'type' => 'text'],
            'email' => ['label' => 'Email', 'type' => 'email'],
            'phone' => ['label' => 'Phone', 'type' => 'text'],
            'department' => ['label' => 'Department', 'type' => 'text'],
            'workshop_id' => ['label' => 'Location / Workshop', 'type' => 'select'],
            'certifications' => ['label' => 'Certifications / Skills', 'type' => 'text'],
            'notes' => ['label' => 'Notes', 'type' => 'textarea'],
            'status' => ['label' => 'Status', 'type' => 'select'],
        ];
        foreach ($dynamicFields as $fname => $fmeta):
          $cfg = $regConfig[$fname] ?? ['is_enabled' => 0, 'is_required' => 0];
          if (empty($cfg['is_enabled'])) continue;
          $req = !empty($cfg['is_required']) ? 'required' : '';
        ?>
        <div style="margin-bottom:10px;">
          <label style="color:var(--text-secondary); display:block;"><?= htmlspecialchars($fmeta['label']) ?> <?= $req ? '*' : '' ?></label>
          <?php if ($fmeta['type'] === 'select' && $fname === 'workshop_id'): ?>
            <select name="<?= $fname ?>" <?= $req ?> style="width:100%; padding:8px; border-radius:4px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary);">
              <option value="">N/A</option>
              <?php foreach ($workshops as $w): ?>
                <option value="<?= $w['workshop_id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
              <?php endforeach; ?>
            </select>
          <?php elseif ($fmeta['type'] === 'select' && $fname === 'status'): ?>
            <select name="<?= $fname ?>" <?= $req ?> style="width:100%; padding:8px; border-radius:4px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary);">
              <option value="active">Active</option>
              <option value="pending">Pending</option>
              <option value="inactive">Inactive</option>
            </select>
          <?php elseif ($fmeta['type'] === 'textarea'): ?>
            <textarea name="<?= $fname ?>" rows="2" <?= $req ?> style="width:100%; padding:8px; border-radius:4px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary);"></textarea>
          <?php else: ?>
            <input type="<?= $fmeta['type'] ?>" name="<?= $fname ?>" <?= $req ?> style="width:100%; padding:8px; border-radius:4px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary);">
          <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); padding: 10px; border-radius: 8px; margin-bottom: 15px;">
            <span style="color:#ef4444; font-size:0.9em;"><strong>Note:</strong> New users are created with the default password <code>password</code> and <strong>must change it on first login</strong>. They inherit base permissions for their role. Missing fields use "N/A" / "TBD" per current configurator.</span>
        </div>

        <button type="submit" class="pill-btn pill-success pill-block">Create & Invite User</button>
    </form>
  </div>
</div>

<!-- Registration Configurator Modal -->
<div id="configModal" class="modal">
  <div class="modal-content" style="max-width: 560px; width: 100%; padding: 22px 24px;">
    <span class="close" onclick="document.getElementById('configModal').style.display='none'">&times;</span>
    <h2 style="margin:0 0 4px 0; font-size:1.15em; color: var(--text-primary);">Registration Configurator</h2>
    <p style="color:var(--text-secondary); font-size:0.88em; margin:4px 0 10px; line-height:1.35;">
      Select fields to include in the new user registration form. Disabled fields use safe defaults ("N/A", "TBD", "Not used").
    </p>
    <form method="POST">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(wcc_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="action" value="save_registration_config">

      <div class="config-list">
        <div class="config-header">
          <div class="field-col">Field to collect</div>
          <div class="req-col">Required</div>
        </div>
        <?php 
        $allFields = [
          'full_name' => 'Full Name',
          'email' => 'Email',
          'phone' => 'Phone',
          'department' => 'Department',
          'workshop_id' => 'Location / Workshop',
          'certifications' => 'Certifications / Skills',
          'notes' => 'Notes',
          'status' => 'Status'
        ];
        foreach ($allFields as $fname => $flabel):
          $cfg = $regConfig[$fname] ?? ['is_enabled'=>1, 'is_required'=>0];
        ?>
        <div class="config-row">
          <label class="field-toggle" for="cfg_<?= $fname ?>">
            <input type="checkbox" name="config[<?= $fname ?>][enabled]" id="cfg_<?= $fname ?>" <?= !empty($cfg['is_enabled']) ? 'checked' : '' ?>>
            <span class="field-name"><?= htmlspecialchars($flabel) ?></span>
          </label>

          <label class="req-toggle" for="req_<?= $fname ?>">
            <input type="checkbox" name="config[<?= $fname ?>][required]" id="req_<?= $fname ?>" <?= !empty($cfg['is_required']) ? 'checked' : '' ?>>
            <span class="req-label">Required</span>
          </label>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="config-actions" style="margin-top:16px; display:flex; gap:10px; justify-content:flex-end;">
        <button type="submit" class="pill-btn pill-success">💾 Save Configuration</button>
        <button type="button" class="pill-btn" onclick="document.getElementById('configModal').style.display='none'">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
  function showRegistrationConfigurator() {
    const modal = document.getElementById('configModal');
    modal.style.display = 'block';
    wireConfiguratorSync(modal);
  }

  function wireConfiguratorSync(modal) {
    // Prevent "required" when the field is not enabled. Clean + professional UX.
    modal.querySelectorAll('.config-row').forEach(row => {
      const enabledInput = row.querySelector('input[name$="[enabled]"]');
      const reqInput = row.querySelector('input[name$="[required]"]');
      if (!enabledInput || !reqInput) return;

      const updateReqState = () => {
        if (!enabledInput.checked) {
          reqInput.checked = false;
          reqInput.disabled = true;
          row.style.opacity = '0.65';
        } else {
          reqInput.disabled = false;
          row.style.opacity = '1';
        }
      };

      // initial
      updateReqState();

      enabledInput.addEventListener('change', updateReqState);
      // also allow clicking the req label to auto-enable if trying to require
      reqInput.addEventListener('change', () => {
        if (reqInput.checked && !enabledInput.checked) {
          enabledInput.checked = true;
          updateReqState();
        }
      });
    });
  }

  function showRolePresets() {
    document.getElementById('rolePresetsModal').style.display = 'block';
  }

  // If modal already visible on load (rare), wire it
  document.addEventListener('DOMContentLoaded', () => {
    const existing = document.getElementById('configModal');
    if (existing && existing.style.display === 'block') {
      wireConfiguratorSync(existing);
    }
  });
</script>

<!-- Role Presets Modal (defines the default permissions for each role level) -->
<div id="rolePresetsModal" class="modal">
  <div class="modal-content" style="max-width: 860px; width: 96%; padding: 18px 22px; max-height: 92vh; overflow-y: auto;">
    <span class="close" onclick="document.getElementById('rolePresetsModal').style.display='none'">&times;</span>
    <h2 style="margin:0 0 2px 0; font-size:1.1em; color: var(--text-primary);">Role Presets</h2>
    <p style="color:var(--text-secondary); font-size:0.82em; margin:2px 0 12px; line-height:1.3;">
      Edit the <strong>base permission presets</strong> for roles. These are used for new users and "Reset Perms to Role".<br>
      Per-user overrides (in the user rows) take precedence on top of these.
    </p>

    <form method="POST">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(wcc_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="action" value="save_role_presets">

      <?php 
      $groupedPerms = [];
      foreach (PERMISSION_LABELS as $key => $meta) {
          $groupedPerms[$meta['group']][$key] = $meta['label'];
      }

      // Render existing roles (from DB + fallback)
      $existingRoles = get_all_roles();
      foreach ($existingRoles as $lvl => $defaultName):
          $current = $roleDefs[$lvl] ?? ['name' => $defaultName, 'permissions' => get_role_permissions($lvl)];
          $rolePerms = $current['permissions'] ?: get_role_permissions($lvl);
          $isCustom = $lvl > 4;
      ?>
      <div class="role-card">
        <div class="role-card-header">
          <div class="role-level-badge">L<?= $lvl ?><?= $isCustom ? ' (custom)' : '' ?></div>
          <input type="text" name="role[<?= $lvl ?>][name]" value="<?= htmlspecialchars($current['name']) ?>" class="role-name-input">
        </div>

        <div class="perm-category">
          <?php foreach ($groupedPerms as $groupName => $permsInGroup): 
              $anyChecked = false;
              foreach ($permsInGroup as $pkey => $plabel) {
                  if (!empty($rolePerms[$pkey])) { $anyChecked = true; break; }
              }
          ?>
          <details <?= $anyChecked ? 'open' : '' ?>>
            <summary><?= htmlspecialchars($groupName) ?> (<?= count($permsInGroup) ?>)</summary>
            <div class="perm-grid">
              <?php foreach ($permsInGroup as $pkey => $plabel): 
                  $checked = !empty($rolePerms[$pkey]);
              ?>
              <label>
                <input type="checkbox" name="role[<?= $lvl ?>][perm][<?= $pkey ?>]" <?= $checked ? 'checked' : '' ?> style="width:14px; height:14px; accent-color:var(--text-accent); flex-shrink:0;">
                <span style="color:var(--text-primary);"><?= htmlspecialchars($plabel) ?></span>
              </label>
              <?php endforeach; ?>
            </div>
          </details>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>

      <!-- Add New Custom Preset -->
      <div class="new-preset-section">
        <div style="font-weight:600; font-size:0.95em; margin-bottom:6px; color:var(--text-accent);">+ Add New Custom Role Preset</div>
        <div style="display:flex; gap:10px; align-items:center; margin-bottom:8px; flex-wrap:wrap;">
          <div>
            <label style="font-size:0.75em; display:block; color:var(--text-secondary);">Level</label>
            <input type="number" name="new_role[level]" value="5" min="5" style="width:70px; padding:4px 6px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary); border-radius:4px;">
          </div>
          <div style="flex:1; min-width:180px;">
            <label style="font-size:0.75em; display:block; color:var(--text-secondary);">Name</label>
            <input type="text" name="new_role[name]" value="Custom Role" style="width:100%; padding:4px 6px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary); border-radius:4px;">
          </div>
        </div>

        <div class="perm-category">
          <?php foreach ($groupedPerms as $groupName => $permsInGroup): ?>
          <details>
            <summary><?= htmlspecialchars($groupName) ?> (<?= count($permsInGroup) ?>)</summary>
            <div class="perm-grid">
              <?php foreach ($permsInGroup as $pkey => $plabel): ?>
              <label>
                <input type="checkbox" name="new_role[perm][<?= $pkey ?>]" style="width:14px; height:14px; accent-color:var(--text-accent); flex-shrink:0;">
                <span style="color:var(--text-primary);"><?= htmlspecialchars($plabel) ?></span>
              </label>
              <?php endforeach; ?>
            </div>
          </details>
          <?php endforeach; ?>
        </div>
        <div style="font-size:0.75em; color:var(--text-secondary); margin-top:4px;">New custom levels start at 5+. Assign them to users like any other role.</div>
      </div>

      <div style="margin-top:14px; display:flex; gap:10px; justify-content:flex-end; align-items:center; flex-wrap:wrap;">
        <span style="font-size:0.78em; color:var(--text-secondary); margin-right:auto;">Saved presets apply immediately to new users &amp; resets. Existing user overrides are preserved.</span>
        <button type="submit" class="pill-btn pill-success">💾 Save All Presets</button>
        <button type="button" class="pill-btn" onclick="document.getElementById('rolePresetsModal').style.display='none'">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Skill Configurator Modal -->
<div id="skillConfigModal" class="modal">
  <div class="modal-content" style="max-width: 600px; width: 96%; padding: 18px 22px;">
    <span class="close" onclick="document.getElementById('skillConfigModal').style.display='none'">&times;</span>
    <h2 style="margin:0 0 10px 0; font-size:1.1em; color: var(--text-primary);">Automated Skill Configurator<?= wcc_gamified_help_button('How do proficiencies work?') ?></h2>
    <p style="color:var(--text-secondary); font-size:0.85em; margin-bottom:15px;">
      Map equipment categories to gamified skills. When technicians log repair hours against a mapped
      category they level up automatically — <strong>a category that is not mapped here earns nothing</strong>,
      no matter how much work is done on it. Category names must match the equipment record exactly.
      <a href="#" onclick="wccShowGamifiedHelp(); return false;" style="color:var(--text-accent);">See the tier thresholds</a>.
    </p>

    <!-- Existing Configs -->
    <table class="data-table" style="width: 100%; margin-bottom: 20px; font-size: 0.9em;">
      <thead>
        <tr>
          <th>Category</th>
          <th>Skill Name</th>
          <th>Icon</th>
          <th style="width: 50px;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($skillAutoConfigs as $cfg): ?>
        <tr>
          <td><?= htmlspecialchars($cfg['equipment_category']) ?></td>
          <td style="color:var(--text-accent); font-weight:bold;"><?= htmlspecialchars($cfg['skill_name']) ?></td>
          <td><?= htmlspecialchars($cfg['icon']) ?></td>
          <td>
            <form method="POST" style="margin:0;" onsubmit="return confirm('Remove this skill mapping?');">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(wcc_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="action" value="delete_skill_config">
              <input type="hidden" name="config_id" value="<?= $cfg['id'] ?>">
              <button type="submit" style="background:none; border:none; color:#ef4444; cursor:pointer;" title="Remove">🗑️</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($skillAutoConfigs)): ?>
        <tr><td colspan="4" style="text-align:center; color:var(--text-secondary);">No mappings defined yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- Add New Config -->
    <form method="POST">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(wcc_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="action" value="save_skill_config">
      <div style="background:var(--panel-bg); border:1px solid var(--panel-border); padding:10px; border-radius:8px;">
        <h4 style="margin:0 0 10px 0; color:var(--text-primary);">+ Add New Mapping</h4>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
          <div style="flex:1; min-width:140px;">
            <label style="display:block; font-size:0.8em; color:var(--text-secondary); margin-bottom:4px;">Equipment Category</label>
            <input type="text" name="equipment_category" required placeholder="e.g. Robotics" style="width:100%; padding:6px; border-radius:4px; border:1px solid var(--input-border); background:var(--input-bg); color:var(--text-primary);">
          </div>
          <div style="flex:1; min-width:140px;">
            <label style="display:block; font-size:0.8em; color:var(--text-secondary); margin-bottom:4px;">Skill Name</label>
            <input type="text" name="skill_name" required placeholder="e.g. Robotics Tech" style="width:100%; padding:6px; border-radius:4px; border:1px solid var(--input-border); background:var(--input-bg); color:var(--text-primary);">
          </div>
          <div style="width:60px;">
            <label style="display:block; font-size:0.8em; color:var(--text-secondary); margin-bottom:4px;">Icon</label>
            <input type="text" name="icon" value="⭐" style="width:100%; padding:6px; text-align:center; border-radius:4px; border:1px solid var(--input-border); background:var(--input-bg); color:var(--text-primary);">
          </div>
        </div>
        <button type="submit" class="pill-btn pill-success" style="margin-top:10px;">💾 Save Mapping</button>
      </div>
    </form>
  </div>
</div>

<!-- View User Skills Modal -->
<div id="viewUserSkillsModal" class="modal">
  <div class="modal-content" style="max-width: 400px; width: 96%; padding: 18px 22px;">
    <span class="close" onclick="document.getElementById('viewUserSkillsModal').style.display='none'">&times;</span>
    <h2 id="viewUserSkillsTitle" style="margin:0 0 15px 0; font-size:1.1em; color: var(--text-primary);">Skills</h2>
    <div id="viewUserSkillsBody" style="max-height: 400px; overflow-y:auto; padding-top:4px;"></div>
  </div>
</div>

<script>
function showUserSkills(title, contentId) {
    document.getElementById('viewUserSkillsTitle').innerText = title;
    document.getElementById('viewUserSkillsBody').innerHTML = document.getElementById(contentId).innerHTML;
    document.getElementById('viewUserSkillsModal').style.display = 'block';
}
</script>

<?= wcc_gamified_help_modal() ?>
</body>
</html>

