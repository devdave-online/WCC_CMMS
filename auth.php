<?php
// auth.php - Global Authentication Gateway
require_once __DIR__ . '/inc/session.php'; // hardened session bootstrap

// Load centralized error handling (friendly messages + logging)
require_once __DIR__ . '/inc/error.php';

// Prevent browser caching of PHP pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Load RBAC engine (always — safe to include multiple times)
require_once __DIR__ . '/rbac.php';

// Exclude login.php from the check to prevent redirect loops
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page !== 'login.php') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /login.php");
        exit;
    }

    if ($current_page !== 'change_password.php' && isset($_SESSION['must_change_password']) && $_SESSION['must_change_password'] === true) {
        header("Location: /change_password.php");
        exit;
    }

    // Rebuild permissions cache if missing (e.g. after a code deploy mid-session)
    if (!isset($_SESSION['permissions']) && isset($_SESSION['role_level'])) {
        try {
            // Use the central connection — a second hardcoded root/empty-password
            // copy here would silently fall back to role-only permissions the moment
            // real DB credentials are set for a deployment.
            require_once __DIR__ . '/inc/db.php';
            $__pdo = get_wcc_db_connection();
            $__stmt = $__pdo->prepare("SELECT permissions_json, badge_number, full_name, locale FROM users WHERE user_id = ?");
            $__stmt->execute([$_SESSION['user_id']]);
            $__row = $__stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['permissions'] = wcc_get_permissions(
                (int)$_SESSION['role_level'],
                $__row['permissions_json'] ?? null
            );
            $_SESSION['badge_number'] = $__row['badge_number'] ?? null;
            // Sessions started before full_name was stored still get it here.
            $_SESSION['full_name'] = $__row['full_name'] ?? null;
            if (!empty($__row['locale'])) {
                require_once __DIR__ . '/inc/i18n.php';
                wcc_set_locale((string)$__row['locale'], false);
            }
        } catch (Exception $e) {
            // Fallback: derive from role_level only if DB is unavailable
            $_SESSION['permissions'] = wcc_get_permissions((int)($_SESSION['role_level'] ?? 1), null);
        }
    }

    // If registry grew (e.g. new tooling perms), rebuild session permissions once
    if (isset($_SESSION['permissions'], $_SESSION['role_level'], $_SESSION['user_id'])) {
        $__needRebuild = false;
        foreach (array_keys(PERMISSION_LABELS) as $__pk) {
            if (!array_key_exists($__pk, $_SESSION['permissions'])) {
                $__needRebuild = true;
                break;
            }
        }
        if ($__needRebuild) {
            try {
                require_once __DIR__ . '/inc/db.php';
                $__pdoR = get_wcc_db_connection();
                $__stR = $__pdoR->prepare('SELECT permissions_json FROM users WHERE user_id = ?');
                $__stR->execute([(int)$_SESSION['user_id']]);
                $__pj = $__stR->fetchColumn();
                $_SESSION['permissions'] = wcc_get_permissions(
                    (int)$_SESSION['role_level'],
                    $__pj !== false ? (string)$__pj : null
                );
            } catch (Exception $e) {
                $_SESSION['permissions'] = wcc_get_permissions((int)$_SESSION['role_level'], null);
            }
        }
    }

    // Locale for sessions that already have permissions but no language yet
    if (empty($_SESSION['locale']) && !empty($_SESSION['user_id'])) {
        try {
            require_once __DIR__ . '/inc/db.php';
            require_once __DIR__ . '/inc/i18n.php';
            $__pdoLoc = get_wcc_db_connection();
            $__stLoc = $__pdoLoc->prepare('SELECT locale FROM users WHERE user_id = ?');
            $__stLoc->execute([(int)$_SESSION['user_id']]);
            $__loc = $__stLoc->fetchColumn();
            wcc_set_locale($__loc ? (string)$__loc : 'en', false);
        } catch (Exception $e) {
            require_once __DIR__ . '/inc/i18n.php';
            wcc_set_locale('en', false);
        }
    } elseif (!empty($_SESSION['locale'])) {
        require_once __DIR__ . '/inc/i18n.php';
        wcc_i18n_boot((string)$_SESSION['locale']);
    }
}
?>
