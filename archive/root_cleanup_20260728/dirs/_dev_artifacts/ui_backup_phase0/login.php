<?php
// login.php - Global Authentication Gate
require_once __DIR__ . '/../../inc/session.php'; // hardened session bootstrap

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/inc/db.php';
$pdo = get_wcc_db_connection();

$error = "";
$message = "";

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: /login.php");
    exit;
}

if (isset($_GET['expired'])) {
    session_destroy(); // Actually terminate the session on the server
    $_SESSION = [];
    $message = "Your session has expired. Please log in again.";
}

// Use the centralized connection (inc/db.php already loaded above)
try {
    // Auto-seed a default admin if the users table is completely empty.
    // Uses "password" to be consistent with the rest of the app (forces change on first login).
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('password', PASSWORD_DEFAULT);
        try {
            $pdo->prepare("INSERT INTO users (username, password_hash, role_level, badge_number, full_name, status) 
                           VALUES (?, ?, ?, ?, ?, ?)")
                ->execute(['admin', $hash, 4, 'IB-00001', 'Administrator', 'active']);
        } catch (Exception $seedEx) {
            // Fallback for older schema without the new columns
            $pdo->prepare("INSERT INTO users (username, password_hash, role_level) VALUES (?, ?, ?)")
                ->execute(['admin', $hash, 4]);
        }
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        // Login by username or badge_number (safe I-badge for TISAX/privacy)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR badge_number = ?");
        $stmt->execute([$username, $username]);
        $user_row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user_row && password_verify($password, $user_row['password_hash'])) {
            $_SESSION['user_id']    = $user_row['user_id'];
            $_SESSION['username']   = $user_row['username'];
            $_SESSION['role_level'] = $user_row['role_level'];
            $_SESSION['badge_number'] = $user_row['badge_number'] ?? null;

            // Record last login (industry standard for activity)
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?")->execute([$user_row['user_id']]);

            if ($password === 'password') {
                $_SESSION['must_change_password'] = true;
            } else {
                unset($_SESSION['must_change_password']);
            }

            // Resolve and cache RBAC permissions for this session
            require_once 'rbac.php';
            $_SESSION['permissions'] = wcc_get_permissions(
                (int)$user_row['role_level'],
                $user_row['permissions_json'] ?? null
            );

            // Load server-persisted theme prefs (Phase 4)
            if (!empty($user_row['theme_prefs_json'])) {
                $_SESSION['theme_prefs'] = json_decode($user_row['theme_prefs_json'], true);
            }

            // Get dynamic session timeout
            $session_minutes = 15; // default
            try {
                $stmt_cfg = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key = 'session_lockout_time'");
                if ($stmt_cfg->rowCount() > 0) {
                    $session_minutes = (int)$stmt_cfg->fetchColumn();
                }
            } catch (Exception $e) { /* table may not exist yet */ }

            $expiry = (time() + ($session_minutes * 60)) * 1000;
            echo "<script>localStorage.setItem('sessionExpiry', '$expiry'); window.location.href='index.php';</script>";
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>WCC Login</title>
    <link rel="stylesheet" href="/css/global.css">
    <style>
        /* Force perfect centering on login page and neutralize global shifts */
        html, body {
            height: 100%;
            margin: 0 !important;
            padding: 0 !important;
        }
        body {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            min-height: 100vh;
            background: var(--bg-gradient);
        }
        .auth-container {
            margin: 0 auto !important;
            transform: none !important;
            max-width: 420px;
            width: 100%;
        }
        @media (min-width: 1000px) {
            .auth-container {
                transform: none !important;
                margin: 0 auto !important;
            }
        }
    </style>
</head>
<body>

<div class="auth-container">
    <div style="text-align: center; margin-bottom: 20px;">
        <h1 style="color: var(--text-accent); margin:0; font-size: 2.5em;">🚀 WCC</h1>
        <p style="color: #94a3b8;">Workshop Control Center</p>
    </div>
    
    <?php if ($message): ?>
        <div style="background: rgba(234, 179, 8, 0.15); color: #facc15; padding: 10px 14px; border-radius: 8px; margin-bottom: 18px; text-align: center; border: 1px solid rgba(234,179,8,0.3); font-size:0.95em;">
            ⚠️ <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background: rgba(239, 68, 68, 0.12); color: #fca5a5; padding: 10px 14px; border-radius: 8px; margin-bottom: 18px; text-align: center; border: 1px solid rgba(239,68,68,0.25); font-size:0.95em;">
            ❌ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div style="margin-bottom: 15px; text-align: left;">
            <input type="text" name="username" required autofocus 
                   style="width:100%; padding:11px; border-radius:8px; 
                          background:var(--input-bg); border:1px solid var(--input-border); 
                          color:var(--input-text); font-size:1.05em; box-sizing:border-box;">
        </div>
        <div style="margin-bottom: 25px; position: relative; text-align: left;">
            <label style="display:block; color:var(--text-secondary); margin-bottom:5px; font-size:0.95em;">Password</label>
            <input type="password" id="loginPassword" name="password" required 
                   style="width:100%; padding:11px; padding-right: 42px; border-radius:8px; 
                          background:var(--input-bg); border:1px solid var(--input-border); 
                          color:var(--input-text); font-size:1.05em; box-sizing:border-box;">
            <span id="togglePassword" style="position: absolute; right: 12px; top: 36px; cursor: pointer; color: var(--text-secondary); display: flex; align-items: center; justify-content: center; width: 24px; height: 24px;" title="Show Password">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            </span>
        </div>
        <button type="submit" class="nav-btn primary" style="width:100%; justify-content:center; font-size:1.05em; padding:12px;">Login</button>
    </form>
</div>

<script>
    const toggle = document.getElementById('togglePassword');
    if (toggle) {
        toggle.addEventListener('click', function() {
            const pwd = document.getElementById('loginPassword');
            if (!pwd) return;
            const type = pwd.getAttribute('type') === 'password' ? 'text' : 'password';
            pwd.setAttribute('type', type);
            this.innerHTML = type === 'password' 
                ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>'
                : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';
        });
    }
</script>

<div style="position: fixed; bottom: 12px; left: 50%; transform: translateX(-50%); font-size: 11px; opacity: 0.6; color: var(--text-secondary);">
    WCC CMMS • XAMPP
</div>
</body>
</html>


