<?php
// login.php - Global Authentication Gate
require_once __DIR__ . '/inc/session.php'; // hardened session bootstrap

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/ratelimit.php';
$pdo = get_wcc_db_connection();

$error = "";
$message = "";

// Brute-force throttle: 10 failed attempts per IP per 15 minutes.
// Generous enough that a technician fat-fingering their badge never notices,
// tight enough that a password-guessing script gets nowhere.
const WCC_LOGIN_MAX_TRIES = 10;
const WCC_LOGIN_WINDOW    = 900;

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

        // Throttle check happens BEFORE the password is verified, so a locked-out
        // IP can't keep testing candidates.
        [$tries, $resetIn] = wcc_rate_status('login', WCC_LOGIN_WINDOW);
        if ($tries >= WCC_LOGIN_MAX_TRIES) {
            $error = "Too many failed sign-in attempts. Please try again in "
                   . max(1, (int)ceil($resetIn / 60)) . " minute(s).";
            $user_row = null;
            $password = '';
        } else {

        // Login by username or badge_number (safe I-badge for TISAX/privacy)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR badge_number = ?");
        $stmt->execute([$username, $username]);
        $user_row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user_row && password_verify($password, $user_row['password_hash'])) {
            // Status gate: only active accounts may sign in. Missing status (legacy rows)
            // is treated as active so older installs keep working.
            $acct_status = strtolower(trim((string)($user_row['status'] ?? 'active')));
            if ($acct_status !== '' && $acct_status !== 'active') {
                // Correct password but account disabled/pending — do not open a session.
                wcc_rate_hit('login', WCC_LOGIN_WINDOW);
                $error = "This account is inactive or pending. Contact an administrator.";
            } else {
            // Session fixation defence: a session ID that existed before login must
            // never become an authenticated one.
            session_regenerate_id(true);
            wcc_rate_clear('login');

            $_SESSION['user_id']    = $user_row['user_id'];
            $_SESSION['username']   = $user_row['username'];
            $_SESSION['role_level'] = $user_row['role_level'];
            $_SESSION['badge_number'] = $user_row['badge_number'] ?? null;
            // Display name is what intervention records are stamped with, so the
            // history and the proficiency board read as people rather than logins.
            $_SESSION['full_name']  = $user_row['full_name'] ?? null;

            // Record last login (industry standard for activity)
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?")->execute([$user_row['user_id']]);

            // Force a password change when the DB flag is set (authoritative) OR
            // the user is still on the seeded default password.
            if (!empty($user_row['must_change_password']) || $password === 'password') {
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
            } // end active-status gate
        } else {
            wcc_rate_hit('login', WCC_LOGIN_WINDOW);
            // Deliberately identical for "no such user" and "wrong password" —
            // a different message would confirm which usernames exist.
            $error = "Invalid username or password.";
        }
        } // end throttle gate
    }
} catch (PDOException $e) {
    // Never echo the driver message: it leaks table names, SQL and file paths.
    error_log('[WCC Login] ' . $e->getMessage());
    $error = "The system is temporarily unavailable. Please try again.";
}

$page_title = 'Login';
require_once __DIR__ . '/inc/head.php';
?>
<style>
    /* Standalone page (no sidebar): perfect centering, neutralize shell margins */
    html, body { height: 100%; margin: 0 !important; padding: 0 !important; }
    body {
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        min-height: 100vh;
        background: var(--bg-gradient);
    }
    .auth-container { margin: 0 auto !important; max-width: 420px; width: calc(100% - 32px); }

    .login-alert {
        padding: 10px 14px;
        border-radius: var(--radius-sm);
        margin-bottom: 18px;
        text-align: center;
        font-size: var(--fs-sm);
        border: 1px solid transparent;
    }
    .login-alert.is-warning { background: var(--warning-bg); color: var(--warning); border-color: var(--warning-border); }
    .login-alert.is-error   { background: var(--danger-bg);  color: var(--danger);  border-color: var(--danger-border); }

    .login-field { margin-bottom: 15px; text-align: left; position: relative; }
    .login-field label { display: block; color: var(--text-secondary); margin-bottom: 5px; font-size: var(--fs-sm); font-weight: 600; }
    .login-field input { font-size: 1.05em !important; }
    .login-field input#loginPassword { padding-right: 42px !important; }
    #togglePassword {
        position: absolute; right: 8px; top: 31px;
        background: none; border: none; cursor: pointer;
        color: var(--text-secondary);
        display: flex; align-items: center; justify-content: center;
        width: 32px; height: 32px; border-radius: var(--radius-sm);
    }
    #togglePassword:hover { color: var(--text-primary); }

    #loginThemeToggle {
        position: fixed; top: 16px; right: 16px;
        background: var(--btn-bg); border: 1px solid var(--btn-border);
        color: var(--text-primary);
        width: 44px; height: 44px; border-radius: var(--radius-md);
        font-size: 1.2em; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: border-color var(--transition-fast);
    }
    #loginThemeToggle:hover { border-color: var(--btn-hover-border); }

    .login-footer {
        position: fixed; bottom: 12px; left: 50%; transform: translateX(-50%);
        font-size: var(--fs-xs); color: var(--text-muted);
    }
</style>

<button type="button" id="loginThemeToggle" onclick="toggleTheme()" aria-label="Switch light/dark theme" title="Switch theme">
    <span id="wccThemeIcon" aria-hidden="true">🌙</span>
</button>

<div class="auth-container">
    <div style="text-align: center; margin-bottom: 20px;">
        <h1 style="color: var(--text-accent); margin:0; font-size: 2.5em;">🚀 WCC</h1>
        <p class="text-muted" style="margin-top: 6px;">Workshop Control Center</p>
    </div>

    <?php if ($message): ?>
        <div class="login-alert is-warning" role="status">⚠️ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="login-alert is-error" role="alert">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="login-field">
            <label for="loginUsername">Username or Badge Number</label>
            <input type="text" id="loginUsername" name="username" required autofocus autocomplete="username">
        </div>
        <div class="login-field">
            <label for="loginPassword">Password</label>
            <input type="password" id="loginPassword" name="password" required autocomplete="current-password">
            <button type="button" id="togglePassword" aria-label="Show password" title="Show password">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            </button>
        </div>
        <button type="submit" class="btn btn-primary btn-block" style="font-size:1.05em; padding:12px;">Login</button>
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
            this.setAttribute('aria-label', type === 'password' ? 'Show password' : 'Hide password');
            this.innerHTML = type === 'password'
                ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>'
                : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';
        });
    }

    // Keep the standalone toggle icon in sync with the active theme
    (function () {
        var icon = document.getElementById('wccThemeIcon');
        function sync() {
            if (icon) icon.textContent = document.documentElement.classList.contains('light-theme') ? '☀️' : '🌙';
        }
        window.addEventListener('wcc:themechange', sync);
        document.addEventListener('DOMContentLoaded', sync);
    })();
</script>

<div class="login-footer">WCC CMMS • XAMPP</div>
</body>
</html>
