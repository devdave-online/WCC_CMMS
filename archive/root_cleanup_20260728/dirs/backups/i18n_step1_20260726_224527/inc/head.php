<?php
/**
 * WCC CMMS — Shared <head> partial
 *
 * Usage at the very top of a page's HTML output (after PHP logic):
 *   $page_title = 'Active Tickets';          // required (falls back to WCC CMMS)
 *   $body_class = 'some-page';               // optional
 *   require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/head.php';
 *   ... page content ...
 *
 * Emits: doctype, <html>, full <head> (charset → viewport → title → theme
 * anti-flash → stylesheet → deferred shared JS) and the opening <body> tag.
 * Defines WCC_HEAD so nav.php skips its fallback shell emission.
 */
require_once __DIR__ . '/version.php';
// CSRF token for session-cookie fetch/forms (safe no-op if session not started yet).
if (session_status() === PHP_SESSION_ACTIVE || session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/csrf.php';
}
if (!defined('WCC_HEAD')) {
    define('WCC_HEAD', 1);
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars(($page_title ?? '') !== '' ? $page_title . ' — WCC' : 'WCC CMMS') ?></title>
<?php if (function_exists('wcc_csrf_token') && session_status() === PHP_SESSION_ACTIVE): ?>
<meta name="csrf-token" content="<?= htmlspecialchars(wcc_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
<script>window.WCC_CSRF = <?= json_encode(wcc_csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<?php endif; ?>
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🚀</text></svg>">
<script>
/* Anti-flash: apply theme + sidebar margin before first paint */
(function () {
    try {
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.classList.add('light-theme');
        }
        if (window.innerWidth > 768) {
            var ml = localStorage.getItem('sidebarState') === 'open' ? '240px' : '60px';
            var s = document.createElement('style');
            s.innerHTML = 'body { margin-left: ' + ml + ' !important; transition: none !important; }';
            document.head.appendChild(s);
            window.__sidebarSnapStyle = s;
        }
    } catch (e) {}
})();
</script>
<link rel="stylesheet" href="/css/global.css?v=<?= WCC_UI_VERSION ?>">
<script src="/js/wcc-ui.js?v=<?= WCC_UI_VERSION ?>" defer></script>
<script src="/js/xmb-wave.js?v=<?= WCC_UI_VERSION ?>" defer></script>
</head>
<body<?= isset($body_class) && $body_class !== '' ? ' class="' . htmlspecialchars($body_class) . '"' : '' ?>>
<script>
/* Body exists now — mirror the theme class for legacy body.light-theme selectors */
if (document.documentElement.classList.contains('light-theme')) {
    document.body.classList.add('light-theme');
}
</script>
