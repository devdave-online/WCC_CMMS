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
    require_once __DIR__ . '/inc/i18n.php';
    wcc_i18n_boot('en');
    $message = __('login.session_expired');
}

// Use the centralized connection (inc/db.php already loaded above)
try {
    // Auto-seed a default admin if the users table is completely empty.
    // Default password is the public string "password" — login always forces a change
    // (session flag) and we also set must_change_password=1 when the column exists.
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('password', PASSWORD_DEFAULT);
        try {
            $pdo->prepare("INSERT INTO users (username, password_hash, role_level, badge_number, full_name, status, must_change_password)
                           VALUES (?, ?, ?, ?, ?, ?, 1)")
                ->execute(['admin', $hash, 4, 'IB-00001', 'Administrator', 'active']);
        } catch (Exception $seedEx) {
            try {
                $pdo->prepare("INSERT INTO users (username, password_hash, role_level, badge_number, full_name, status)
                               VALUES (?, ?, ?, ?, ?, ?)")
                    ->execute(['admin', $hash, 4, 'IB-00001', 'Administrator', 'active']);
            } catch (Exception $seedEx2) {
                // Fallback for older schema without the new columns
                $pdo->prepare("INSERT INTO users (username, password_hash, role_level) VALUES (?, ?, ?)")
                    ->execute(['admin', $hash, 4]);
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        // Throttle check happens BEFORE the password is verified, so a locked-out
        // IP can't keep testing candidates.
        [$tries, $resetIn] = wcc_rate_status('login', WCC_LOGIN_WINDOW);
        if ($tries >= WCC_LOGIN_MAX_TRIES) {
            require_once __DIR__ . '/inc/i18n.php';
            wcc_i18n_boot(isset($_SESSION['locale']) ? (string)$_SESSION['locale'] : 'en');
            $error = __('login.throttled', ['mins' => max(1, (int)ceil($resetIn / 60))]);
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
                require_once __DIR__ . '/inc/i18n.php';
                wcc_i18n_boot('en');
                $error = __('login.inactive');
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

            require_once __DIR__ . '/inc/i18n.php';
            wcc_i18n_sync_from_user($user_row);

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
            require_once __DIR__ . '/inc/i18n.php';
            wcc_i18n_boot(isset($_SESSION['locale']) ? (string)$_SESSION['locale'] : 'en');
            $error = __('login.invalid');
        }
        } // end throttle gate
    }
} catch (PDOException $e) {
    // Never echo the driver message: it leaks table names, SQL and file paths.
    error_log('[WCC Login] ' . $e->getMessage());
    require_once __DIR__ . '/inc/i18n.php';
    wcc_i18n_boot('en');
    $error = __('login.unavailable');
}

require_once __DIR__ . '/inc/i18n.php';
if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['locale'])) {
    wcc_i18n_boot((string)$_SESSION['locale']);
} else {
    wcc_i18n_boot('en');
}
$page_title = __('login.title');
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
    /* Orb mark (Companion ic_launcher) — also in global.css for shell pages */
    .wcc-login-mark {
        width: 80px;
        height: 80px;
        border-radius: 18px;
        object-fit: cover;
        display: block;
        margin: 0 auto 14px;
        box-shadow: 0 8px 28px rgba(0,0,0,0.35), 0 0 0 1px rgba(255,255,255,0.1);
    }
    .login-docs-link {
        text-align: center;
        margin: 18px 0 0;
        font-size: 0.95em;
    }
    .login-docs-link a {
        color: var(--text-accent);
        font-weight: 700;
        text-decoration: none;
    }
    .login-docs-link a:hover { text-decoration: underline; }
</style>

<button type="button" id="loginThemeToggle" onclick="toggleTheme()" aria-label="<?= __e('login.switch_theme') ?>" title="<?= __e('nav.switch_theme') ?>">
    <span id="wccThemeIcon" aria-hidden="true">🌙</span>
</button>

<div class="auth-container">
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="/img/wcc-orb.png" alt="" class="wcc-login-mark" width="80" height="80" decoding="async">
        <h1 style="color: var(--text-accent); margin:0; font-size: 2.5em;"><?= __e('app.short') ?></h1>
        <p class="text-muted" style="margin-top: 6px;"><?= __e('login.subtitle') ?></p>
    </div>

    <?php if ($message): ?>
        <div class="login-alert is-warning" role="status">⚠️ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="login-alert is-error" role="alert">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="login-field">
            <label for="loginUsername"><?= __e('login.username') ?></label>
            <input type="text" id="loginUsername" name="username" required autofocus autocomplete="username">
        </div>
        <div class="login-field">
            <label for="loginPassword"><?= __e('login.password') ?></label>
            <input type="password" id="loginPassword" name="password" required autocomplete="current-password">
            <button type="button" id="togglePassword" aria-label="<?= __e('login.show_password') ?>" title="<?= __e('login.show_password') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            </button>
        </div>
        <button type="submit" class="btn btn-primary btn-block" style="font-size:1.05em; padding:12px;"><?= __e('btn.login') ?></button>
    </form>
    <p class="login-docs-link">
        <a href="/docs.php"><?= __e('login.open_docs') ?></a>
    </p>
</div>

<script>
    const toggle = document.getElementById('togglePassword');
    if (toggle) {
        toggle.addEventListener('click', function() {
            const pwd = document.getElementById('loginPassword');
            if (!pwd) return;
            const type = pwd.getAttribute('type') === 'password' ? 'text' : 'password';
            pwd.setAttribute('type', type);
            const showL = (typeof t === 'function') ? t('login.show_password') : 'Show password';
            const hideL = (typeof t === 'function') ? t('login.hide_password') : 'Hide password';
            this.setAttribute('aria-label', type === 'password' ? showL : hideL);
            this.setAttribute('title', type === 'password' ? showL : hideL);
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

<div class="login-footer"><?= __e('app.short') ?> CMMS • XAMPP</div>
</body>
</html>
