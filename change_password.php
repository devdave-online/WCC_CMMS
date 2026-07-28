<?php
// change_password.php
require 'auth.php';

// RBAC EXCEPTION: No require_perm() — self-service / forced password change path.
// Every user (any role) must be able to reach this when must_change_password is set,
// or to change their own password. Gated only by auth.php session.

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/i18n.php';
$pdo = get_wcc_db_connection();

$error = "";
$message = "";

// If they don't need to change password, redirect them to index
if (!isset($_SESSION['must_change_password']) || $_SESSION['must_change_password'] !== true) {
    header("Location: /index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_password']) && isset($_POST['confirm_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password === 'password') {
        $error = __('pw.cannot_default');
    } elseif ($new_password !== $confirm_password) {
        $error = __('pw.mismatch');
    } elseif (strlen($new_password) < 6) {
        $error = __('pw.too_short');
    } else {
        try {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, must_change_password = 0 WHERE user_id = ?");
            $stmt->execute([$hash, $_SESSION['user_id']]);

            unset($_SESSION['must_change_password']);

            header("Location: /index.php");
            exit;
        } catch (PDOException $e) {
            error_log('[WCC change_password] ' . $e->getMessage());
            $error = __('pw.db_error');
        }
    }
}

$page_title = __('login.password_change');
require_once __DIR__ . '/inc/head.php';
?>
<style>
    html, body { height: 100%; margin: 0 !important; padding: 0 !important; }
    body {
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        min-height: 100vh;
    }
    .auth-container { margin: 0 auto !important; max-width: 420px; width: calc(100% - 32px); }
    .login-alert {
        padding: 10px 14px;
        border-radius: var(--radius-sm);
        margin-bottom: 18px;
        text-align: center;
        font-size: var(--fs-sm);
        background: var(--danger-bg);
        color: var(--danger);
        border: 1px solid var(--danger-border);
    }
    .login-field { margin-bottom: 15px; text-align: left; }
    .login-field label { display: block; color: var(--text-secondary); margin-bottom: 5px; font-size: var(--fs-sm); font-weight: 600; }
</style>

<div class="auth-container">
    <div style="text-align: center; margin-bottom: 20px;">
        <h1 style="color: var(--text-accent); margin:0; font-size: 2.5em;">🔒 <?= __e('pw.action_required') ?></h1>
        <p class="text-muted" style="margin-top: 6px;"><?= __e('pw.must_change') ?></p>
    </div>

    <?php if ($error): ?>
        <div class="login-alert" role="alert">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="change_password.php">
        <div class="login-field">
            <label for="new_password"><?= __e('profile.new_password') ?></label>
            <input type="password" id="new_password" name="new_password" required autofocus autocomplete="new-password">
        </div>
        <div class="login-field">
            <label for="confirm_password"><?= __e('profile.confirm_password') ?></label>
            <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary btn-block" style="font-size:1.05em; padding:12px;"><?= __e('profile.update_password') ?></button>
    </form>
</div>

</body>
</html>
