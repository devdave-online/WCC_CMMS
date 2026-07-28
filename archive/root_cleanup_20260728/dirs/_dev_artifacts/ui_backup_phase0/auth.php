<?php
// auth.php - Global Authentication Gateway
require_once __DIR__ . '/../../inc/session.php'; // hardened session bootstrap

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
            $__host = 'localhost'; $__db = 'workshop_db'; $__u = 'root'; $__p = '';
            $__pdo = new PDO("mysql:host=$__host;dbname=$__db;charset=utf8mb4", $__u, $__p);
            $__stmt = $__pdo->prepare("SELECT permissions_json, badge_number FROM users WHERE user_id = ?");
            $__stmt->execute([$_SESSION['user_id']]);
            $__row = $__stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['permissions'] = wcc_get_permissions(
                (int)$_SESSION['role_level'],
                $__row['permissions_json'] ?? null
            );
            $_SESSION['badge_number'] = $__row['badge_number'] ?? null;

            // Load server theme prefs for persistence (Phase 4)
            if (!empty($__row['theme_prefs_json'])) {
                $_SESSION['theme_prefs'] = json_decode($__row['theme_prefs_json'], true);
            }
        } catch (Exception $e) {
            // Fallback: derive from role_level only if DB is unavailable
            $_SESSION['permissions'] = wcc_get_permissions((int)($_SESSION['role_level'] ?? 1), null);
        }
    }
}
?>
