<?php
/**
 * WCC CMMS — public demo guard.
 *
 * When this instance is a PUBLIC SHOWCASE, visitors must be free to click
 * everything and see real data, but must NOT be able to destroy the demo, take
 * it over, or reach the host. This file is the single switch for that.
 *
 * Enabled by setting the environment variable WCC_DEMO_MODE=1 (docker-compose
 * sets it for the public demo). Unset — the normal case for a real installation
 * — every guard here is inert and the product behaves exactly as shipped.
 *
 *   wcc_demo_mode()             → is this a public demo?
 *   wcc_demo_block($what)       → hard-stop a destructive action (JSON or page)
 *   wcc_demo_guard_post($map)   → block a list of POST 'action' values
 *   wcc_demo_notice()           → the banner shown on gated pages
 *
 * The guards run SERVER-SIDE on purpose. Hiding a button is not security: a
 * visitor can re-post the form by hand. Every block below rejects the request
 * itself, so the UI hint and the enforcement can never drift apart.
 */

/** Is this instance running as a public demo? */
function wcc_demo_mode(): bool
{
    static $on = null;
    if ($on === null) {
        $v = getenv('WCC_DEMO_MODE');
        $on = ($v !== false && $v !== '' && $v !== '0' && strtolower((string)$v) !== 'false');
    }
    return $on;
}

/**
 * Reject a destructive action. Answers JSON to API/XHR callers and a friendly
 * page to browsers, then exits — the caller never proceeds.
 */
function wcc_demo_block(string $what = 'This action'): void
{
    $msg = $what . ' is disabled in the public demo. '
         . 'Download WCC and run it yourself to use the full feature set.';

    $wantsJson = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        || str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/');

    http_response_code(403);
    if ($wantsJson) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'demo_mode' => true, 'message' => $msg]);
        exit;
    }

    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Disabled in demo — WCC</title>'
       . '<style>body{font-family:system-ui,sans-serif;background:#0f172a;color:#e2e8f0;'
       . 'display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0}'
       . '.c{max-width:520px;padding:32px;background:#1e293b;border:1px solid #334155;border-radius:14px;text-align:center}'
       . 'h1{margin:0 0 10px;font-size:1.25rem;color:#38bdf8}p{line-height:1.6;color:#cbd5e1}'
       . 'a{display:inline-block;margin-top:16px;padding:9px 18px;background:#0284c7;color:#fff;'
       . 'text-decoration:none;border-radius:8px;font-weight:600}</style>'
       . '<div class="c"><h1>🛡️ Disabled in the public demo</h1><p>' . htmlspecialchars($msg) . '</p>'
       . '<a href="/index.php">← Back to the demo</a></div>';
    exit;
}

/**
 * Guard a page's POST handlers.
 *
 * @param array $blocked  ['action_value' => 'Human label', ...] — matched against $_POST['action'].
 * @param array $blockedKeys ['post_key' => 'Human label', ...] — matched on the mere PRESENCE of a
 *        POST key, for handlers that are triggered by a flag rather than an 'action' value
 *        (e.g. users.php fires the password reset on isset($_POST['trigger_reset_password'])).
 *        Missing these is the classic hole: the button is hidden, the endpoint still works.
 */
function wcc_demo_guard_post(array $blocked, array $blockedKeys = []): void
{
    if (!wcc_demo_mode()) return;
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') return;

    $action = $_POST['action'] ?? '';
    if ($action !== '' && isset($blocked[$action])) {
        wcc_demo_block($blocked[$action]);
    }
    foreach ($blockedKeys as $key => $label) {
        if (isset($_POST[$key])) wcc_demo_block($label);
    }
}

/**
 * Guard delete-style handlers triggered by a GET parameter
 * (e.g. ?delete_workshop=3). Pass ['get_key' => 'Human label', ...].
 *
 * These matter most on a public demo: a GET delete needs no form at all — the
 * visitor only has to visit a URL, so hiding the link protects nothing.
 */
function wcc_demo_guard_get(array $blockedKeys): void
{
    if (!wcc_demo_mode()) return;
    foreach ($blockedKeys as $key => $label) {
        if (isset($_GET[$key])) wcc_demo_block($label);
    }
}

/**
 * Catch-all: block ANY GET parameter whose name looks like a delete/remove/flush
 * trigger. Used as a safety net so a handler added later is covered by default
 * rather than silently exposed.
 */
function wcc_demo_guard_destructive_get(): void
{
    if (!wcc_demo_mode()) return;
    foreach (array_keys($_GET) as $k) {
        if (preg_match('/^(delete|remove|drop|flush|purge|wipe|reset)_/i', (string)$k)) {
            wcc_demo_block('Deleting records');
        }
    }
}

/** Block the whole page (for pages with no safe subset, e.g. Data Administration). */
function wcc_demo_block_page(string $what): void
{
    if (wcc_demo_mode()) wcc_demo_block($what);
}

/** Inline banner markup for pages that stay visible but have gated actions. */
function wcc_demo_notice(string $text = 'You are viewing the public demo — changes that would damage the showcase are disabled.'): string
{
    if (!wcc_demo_mode()) return '';
    return '<div style="display:flex;align-items:center;gap:10px;margin:0 0 18px;padding:11px 16px;'
         . 'background:rgba(56,189,248,.08);border:1px solid rgba(56,189,248,.35);border-radius:10px;'
         . 'color:var(--text-primary);font-size:.9em;">'
         . '<span style="font-size:1.1em;">🛡️</span><span>' . htmlspecialchars($text) . '</span></div>';
}
