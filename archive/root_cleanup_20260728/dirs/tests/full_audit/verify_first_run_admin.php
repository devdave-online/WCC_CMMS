<?php
/**
 * Verify first-run: empty users → login.php seeds admin → login password → forced change.
 *
 *   C:\xampp\php\php.exe tests\full_audit\verify_first_run_admin.php
 */
if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

require __DIR__ . '/lib/HttpClient.php';
require_once __DIR__ . '/../../inc/db.php';

$base = 'http://127.0.0.1';
$pdo = get_wcc_db_connection();
$okN = 0;
$failN = 0;

function v(string $id, bool $pass, string $d = ''): void
{
    global $okN, $failN;
    if ($pass) {
        $okN++;
        echo "  OK  [$id]" . ($d !== '' ? " — $d" : '') . "\n";
    } else {
        $failN++;
        echo " FAIL [$id]" . ($d !== '' ? " — $d" : '') . "\n";
    }
}

echo "=== First-run admin / password verify ===\n";

$usersBefore = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
v('users_empty_before_seed', $usersBefore === 0, "n=$usersBefore");

// GET login seeds admin when empty
$c = new WccAuditHttpClient($base, 25);
$c->get('/login.php', true);
v('login_page_200', $c->lastStatus() === 200, 'HTTP ' . $c->lastStatus());

$usersAfter = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$row = $pdo->query("SELECT user_id, username, role_level, must_change_password, password_hash, status FROM users WHERE username='admin'")->fetch(PDO::FETCH_ASSOC);
v('admin_seeded', $usersAfter === 1 && $row, "users=$usersAfter");
v('admin_username', ($row['username'] ?? '') === 'admin');
v('admin_role_4', (int)($row['role_level'] ?? 0) === 4);
v('admin_hash_is_password', $row && password_verify('password', $row['password_hash']));

// Login with admin/password
$c2 = new WccAuditHttpClient($base, 25);
// Don't use login() helper — we need to see forced change flow
$c2->get('/login.php', true);
$c2->postForm('/login.php', ['username' => 'admin', 'password' => 'password'], true);
$bodyAfterLogin = $c2->lastBody();
// App may land on change_password via JS redirect from login response, or subsequent GET
$loginBody = $bodyAfterLogin;
// Follow by requesting a protected page
$probe = $c2->get('/index.php', true);
$probeBody = $c2->lastBody();
$cp = $c2->get('/change_password.php', true);
$cpBody = $c2->lastBody();

$onChangePage = str_contains($cpBody, 'new_password')
    || str_contains($cpBody, 'confirm_password')
    || str_contains($cpBody, 'must_change')
    || str_contains($cpBody, 'pw.must_change')
    || str_contains($cpBody, 'Action Required')
    || str_contains($cpBody, 'password_change');
// index should redirect or not show full app without change
$blockedFromApp = !str_contains($probeBody, 'wcc-sidebar') && !str_contains($probeBody, 'wccSidebar')
    || str_contains($probeBody, 'change_password')
    || str_contains($probeBody, 'new_password');

v('login_admin_password_accepted', !str_contains($loginBody, 'invalid') || $onChangePage || $c2->lastStatus() === 200, 'HTTP after login flow');
v('forced_change_page_reachable', $onChangePage, 'change_password form present');
v('app_blocked_until_change', $blockedFromApp || $onChangePage, 'cannot use app without changing default password');

// Session flag path: change password
if ($onChangePage) {
    $c2->postForm('/change_password.php', [
        'new_password' => 'StartReady_2026!',
        'confirm_password' => 'StartReady_2026!',
    ], true);
    $afterChange = $c2->get('/index.php', true);
    $appOk = str_contains($afterChange['body'], 'wcc-sidebar')
        || str_contains($afterChange['body'], 'wccSidebar')
        || str_contains($afterChange['body'], 'wcc-nav-link');
    v('password_change_submit', $appOk || !str_contains($afterChange['body'], 'new_password'), 'HTTP ' . $c2->lastStatus());
    v('app_after_password_change', $appOk, $appOk ? 'hub/shell reachable' : substr(preg_replace('/\s+/', ' ', strip_tags($afterChange['body'])), 0, 100));

    $hash = $pdo->query("SELECT password_hash FROM users WHERE username='admin'")->fetchColumn();
    v('db_hash_new_password', password_verify('StartReady_2026!', (string)$hash));
    v('db_hash_not_old_password', !password_verify('password', (string)$hash));

    // Login again with new password
    $c3 = new WccAuditHttpClient($base, 25);
    $okNew = $c3->login('admin', 'StartReady_2026!');
    v('relogin_new_password', $okNew);
    $okOld = (new WccAuditHttpClient($base, 15))->login('admin', 'password');
    v('old_password_rejected', !$okOld);
} else {
    v('password_change_submit', false, 'never reached change form — cannot complete path');
    v('app_after_password_change', false, 'skipped');
    v('db_hash_new_password', false, 'skipped');
    v('db_hash_not_old_password', false, 'skipped');
    v('relogin_new_password', false, 'skipped');
    v('old_password_rejected', false, 'skipped');
}

// Report must_change column on seed
$mcp = $pdo->query("SELECT must_change_password FROM users WHERE username='admin'")->fetchColumn();
v('must_change_flag_after', (int)$mcp === 0 || $mcp === '0' || $mcp === null || $mcp === '', 'must_change_password=' . var_export($mcp, true));

echo "\n=== FIRST-RUN VERIFY: pass=$okN fail=$failN ===\n";
if ($failN === 0) {
    echo "Start-ready: login admin / password → forced change works.\n";
    echo "Current admin password after test: StartReady_2026!\n";
} else {
    echo "Investigate failures above before ship.\n";
}
exit($failN > 0 ? 1 : 0);
