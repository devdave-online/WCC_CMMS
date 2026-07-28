<?php
/**
 * WCC CMMS — CSRF token helper.
 *
 * State-changing actions that arrive over GET links (?delete_…, ?run_cron) or
 * session-cookie POSTs are vulnerable to cross-site request forgery: a malicious
 * page can make an authenticated browser fire the request. Gating each such
 * action on a per-session token that only our own pages know defeats that.
 *
 * Usage:
 *   require_once __DIR__ . '/csrf.php';
 *   // in a link:   ...&csrf=<?= wcc_csrf_token() ?>
 *   // in a form:   <input type="hidden" name="csrf" value="<?= wcc_csrf_token() ?>">
 *   // in a handler: wcc_csrf_require();
 *   // JSON APIs:    wcc_csrf_require_json($body['csrf'] ?? null);  // also checks X-CSRF-Token
 */

if (!function_exists('wcc_csrf_token')) {

    function wcc_csrf_token(): string {
        if (session_status() === PHP_SESSION_NONE) {
            require_once __DIR__ . '/session.php'; // hardened session bootstrap
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /** True if the supplied token matches the session token. */
    function wcc_csrf_valid(?string $token): bool {
        if (session_status() === PHP_SESSION_NONE) {
            require_once __DIR__ . '/session.php';
        }
        return !empty($_SESSION['csrf_token'])
            && is_string($token)
            && $token !== ''
            && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Resolve token from explicit arg, query/body field, or X-CSRF-Token header.
     */
    function wcc_csrf_from_request(?string $token = null): string {
        if (is_string($token) && $token !== '') {
            return $token;
        }
        if (!empty($_GET['csrf']) && is_string($_GET['csrf'])) {
            return $_GET['csrf'];
        }
        if (!empty($_POST['csrf']) && is_string($_POST['csrf'])) {
            return $_POST['csrf'];
        }
        $hdr = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return is_string($hdr) ? $hdr : '';
    }

    /**
     * True when the caller is an API client (companion app / integrators), not a
     * browser form/fetch that relies only on the session cookie.
     *
     * Companion always sends Authorization: Basic (see NetworkModule). Cross-site
     * form CSRF cannot forge that header, so these clients are not subject to the
     * classic cookie-only CSRF vector. Browser pages keep using CSRF tokens.
     */
    function wcc_csrf_is_api_client_request(): bool {
        if (!empty($_SERVER['PHP_AUTH_USER'])) {
            return true;
        }
        $auth = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';
        if (is_string($auth) && preg_match('/^\s*(Basic|Bearer)\s+\S+/i', $auth)) {
            return true;
        }
        // Real API keys only (32+ hex-ish). Companion stores username as "api key"
        // for the header — do NOT treat short X-API-Key values as proof of API auth.
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        if (is_string($apiKey) && strlen($apiKey) >= 32) {
            return true;
        }
        return false;
    }

    /**
     * Enforce a valid token or stop (plain text — HTML pages / form POSTs).
     */
    function wcc_csrf_require(?string $token = null): void {
        if (wcc_csrf_is_api_client_request()) {
            return;
        }
        if (!wcc_csrf_valid(wcc_csrf_from_request($token))) {
            http_response_code(403);
            die('Security check failed (invalid or missing CSRF token). Go back and retry from the app.');
        }
    }

    /**
     * Enforce a valid token or stop with JSON (legacy /api/* endpoints).
     * Pass $body['csrf'] when the client embeds the token in the JSON body;
     * X-CSRF-Token header is always accepted as well.
     *
     * Skipped for Basic/Bearer API clients (WCC Companion app).
     */
    function wcc_csrf_require_json(?string $token = null): void {
        if (wcc_csrf_is_api_client_request()) {
            return;
        }
        if (!wcc_csrf_valid(wcc_csrf_from_request($token))) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'status'  => 'error',
                'success' => false,
                'message' => 'Security check failed — reload the page and retry.',
            ]);
            exit;
        }
    }
}
