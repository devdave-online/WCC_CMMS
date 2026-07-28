<?php
/**
 * WCC CMMS — JSON-safe auth guards for AJAX endpoints under /api/*.
 *
 * The legacy endpoints return JSON, so they can't use rbac.php's require_perm()
 * (which emits an HTML "Access Denied" block). These helpers emit a JSON error
 * and exit instead. Permissions come from $_SESSION['permissions'], populated at
 * login and persisted in the session, so can() works without auth.php.
 *
 * Usage (after the endpoint has started its session):
 *   require_once __DIR__ . '/../inc/api_guard.php';
 *   api_guard_perm('closeout_tickets');
 */

if (!function_exists('api_guard_login')) {

    function _api_guard_boot(): void {
        if (session_status() === PHP_SESSION_NONE) {
require_once __DIR__ . '/../inc/session.php'; // hardened session bootstrap
        }
        require_once __DIR__ . '/../rbac.php';
    }

    function _api_guard_fail(int $code, string $msg): void {
        http_response_code($code);
        // Keep the shape both response styles in this app understand.
        echo json_encode(['status' => 'error', 'success' => false, 'message' => $msg]);
        exit;
    }

    /** Require an authenticated session (any role). */
    function api_guard_login(): void {
        _api_guard_boot();
        if (!isset($_SESSION['user_id']) && !isset($_SESSION['username'])) {
            _api_guard_fail(401, 'Authentication required.');
        }
    }

    /** Require an authenticated session AND a specific permission. */
    function api_guard_perm(string $perm): void {
        api_guard_login();
        if (!can($perm)) {
            _api_guard_fail(403, 'Forbidden: insufficient permissions.');
        }
    }
}
