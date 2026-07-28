<?php
/**
 * WCC CMMS — hardened session bootstrap.
 *
 * Every entry point starts its session through here instead of calling
 * session_start() raw. Doing it in code rather than php.ini means the hardening
 * ships with the app: a deploy to another host (or a XAMPP reinstall) can't
 * silently lose it.
 *
 * Idempotent — safe to include from a page that already started a session.
 */

if (session_status() === PHP_SESSION_NONE) {

    // Reject a session ID the server never issued. Without this an attacker can
    // plant a known ID in the victim's browser and inherit the session once they
    // log in (session fixation).
    @ini_set('session.use_strict_mode', '1');
    @ini_set('session.use_only_cookies', '1');

    // HTTPS is optional here on purpose: the shop-floor intranet has no TLS, and
    // a hard Secure flag would break login there. On a TLS host it turns itself on.
    $https = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
          || (($_SERVER['SERVER_PORT'] ?? null) == 443)
          || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $https,   // only when the request actually is TLS
        'httponly' => true,     // no JS can read the cookie -> XSS can't steal the session
        'samesite' => 'Lax',    // blocks cross-site POST CSRF while keeping normal links working
    ]);

    session_start();
}
