<?php
/**
 * FQA critical-path driver (plan A4) — simulates manuals via HTTP.
 * CLI only. Uses demo accounts from config.
 */
if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

require __DIR__ . '/lib/HttpClient.php';
$cfg = require __DIR__ . '/config.php';
$base = $cfg['base_url'];
$pass = $cfg['admin_pass'];

$okN = 0;
$failN = 0;
$lines = [];

function fqa(string $id, bool $pass, string $detail = ''): void
{
    global $okN, $failN, $lines;
    if ($pass) {
        $okN++;
        $msg = "  OK  [$id]" . ($detail !== '' ? " — $detail" : '');
    } else {
        $failN++;
        $msg = " FAIL [$id]" . ($detail !== '' ? " — $detail" : '');
    }
    $lines[] = $msg;
    echo $msg . "\n";
}

function denied(string $body): bool
{
    return (bool)preg_match('/Access Denied<\/h2>/', $body)
        || (bool)preg_match('/You do not have the/', $body);
}

function extractCsrf(WccAuditHttpClient $c): ?string
{
    return $c->extractCsrf($c->lastBody());
}

// 1 Admin login hub
$admin = new WccAuditHttpClient($base, 25);
fqa('1_admin_login', $admin->login($cfg['admin_user'], $pass), $cfg['admin_user']);
$admin->get('/index.php');
fqa('1_hub', $admin->lastStatus() === 200 && strpos($admin->lastBody(), 'wcc-sidebar') !== false, 'HTTP ' . $admin->lastStatus());

// 2 Create ticket (operator-capable: admin also has create)
$admin->get('/register.php');
$csrf = extractCsrf($admin) ?? '';
// need equip id
require_once __DIR__ . '/../../inc/db.php';
$pdo = get_wcc_db_connection();
$equipId = (int)$pdo->query("SELECT equip_id FROM equipment WHERE deleted_at IS NULL LIMIT 1")->fetchColumn();
$tag = '[FQA-' . date('ymdHis') . ']';
$payload = [
    'equip_id' => $equipId,
    'report_date' => date('Y-m-d'),
    'report_time' => date('H:i:s'),
    'pic' => 'FQA Bot',
    'fault_desc' => $tag . ' manual path ticket',
    'priority' => 'normal',
    'event_class' => 'failure',
    'csrf' => $csrf,
];
// get fresh csrf from page with token in WCC_CSRF
if (preg_match('/WCC_CSRF\s*=\s*"([^"]+)"/', $admin->lastBody(), $m)) {
    $payload['csrf'] = $m[1];
}
$r = $admin->postJson('/api/submit_ticket.php', $payload, $payload['csrf']);
$body = $admin->lastBody();
$j = json_decode($body, true);
$ticketId = $j['ticket_id'] ?? null;
if (!$ticketId && !empty($j['message']) && preg_match('/TK-[A-Z0-9\-]+/', $j['message'], $tm)) {
    $ticketId = $tm[0];
}
// submit_ticket returns message with id
if (!$ticketId && preg_match('/TK-\d{6}-\d+/', $body, $tm)) {
    $ticketId = $tm[0];
}
fqa('2_create_ticket', ($j['status'] ?? '') === 'success' && $ticketId, 'id=' . ($ticketId ?: 'none') . ' body=' . substr($body, 0, 120));

// 3 Takeover finish as tech
$tech = new WccAuditHttpClient($base, 25);
fqa('3_tech_login', $tech->login('j.okafor', $pass));
$tech->get('/_maint/takeover.php?id=' . urlencode((string)$ticketId));
if (preg_match('/WCC_CSRF\s*=\s*"([^"]+)"/', $tech->lastBody(), $m)) {
    $tcsrf = $m[1];
} else {
    $tcsrf = extractCsrf($tech) ?? $payload['csrf'];
}
$now = date('Y-m-d H:i:s');
$take = [
    'ticket_id' => $ticketId,
    'action_type' => 'finish',
    'action_start' => $now,
    'action_end' => date('Y-m-d H:i:s', time() + 120),
    'fault_type' => 'Mechanical',
    'root_cause' => 'Wear',
    'action_taken' => $tag . ' finish action',
    'parts_used' => 'None',
    'escalated_to' => 'None',
    'csrf' => $tcsrf,
];
$tech->postJson('/api/submit_takeover.php', $take, $tcsrf);
$tj = json_decode($tech->lastBody(), true);
fqa('3_takeover_finish', ($tj['status'] ?? '') === 'success', substr($tech->lastBody(), 0, 100));

// Create second ticket for escalate path
$admin->get('/register.php');
if (preg_match('/WCC_CSRF\s*=\s*"([^"]+)"/', $admin->lastBody(), $m)) {
    $csrf2 = $m[1];
} else {
    $csrf2 = $payload['csrf'];
}
$payload2 = $payload;
$payload2['csrf'] = $csrf2;
$payload2['fault_desc'] = $tag . ' escalate path';
$admin->postJson('/api/submit_ticket.php', $payload2, $csrf2);
$j2 = json_decode($admin->lastBody(), true);
$ticketEsc = $j2['ticket_id'] ?? null;
if (!$ticketEsc && preg_match('/TK-\d{6}-\d+/', $admin->lastBody(), $tm)) {
    $ticketEsc = $tm[0];
}
// 4 Escalate
$tech->get('/_maint/takeover.php?id=' . urlencode((string)$ticketEsc));
if (preg_match('/WCC_CSRF\s*=\s*"([^"]+)"/', $tech->lastBody(), $m)) {
    $tcsrf = $m[1];
}
$esc = $take;
$esc['ticket_id'] = $ticketEsc;
$esc['action_type'] = 'escalate';
$esc['escalated_to'] = 'Supervisor';
$esc['csrf'] = $tcsrf;
$tech->postJson('/api/submit_takeover.php', $esc, $tcsrf);
$ej = json_decode($tech->lastBody(), true);
fqa('4_escalate', ($ej['status'] ?? '') === 'success', (string)$ticketEsc);

// verify status in DB
$st = $pdo->prepare("SELECT status FROM active_tickets WHERE ticket_id = ?");
$st->execute([$ticketId]);
$stFinish = $st->fetchColumn();
$st->execute([$ticketEsc]);
$stEsc = $st->fetchColumn();
fqa('4_status_pending', strtoupper((string)$stFinish) === 'PENDING', (string)$stFinish);
fqa('4_status_escalated', strtoupper((string)$stEsc) === 'ESCALATED', (string)$stEsc);

// 5 Closeout as supervisor
$sup = new WccAuditHttpClient($base, 25);
fqa('5_sup_login', $sup->login('p.nair', $pass));
$sup->get('/_maint/closeout.php?id=' . urlencode((string)$ticketId));
if (preg_match('/WCC_CSRF\s*=\s*"([^"]+)"/', $sup->lastBody(), $m)) {
    $scsrf = $m[1];
} else {
    $scsrf = $tcsrf;
}
$sup->postJson('/api/submit_closeout.php', [
    'ticket_id' => $ticketId,
    'supervisor' => 'p.nair',
    'csrf' => $scsrf,
], $scsrf);
$cj = json_decode($sup->lastBody(), true);
fqa('5_closeout', ($cj['status'] ?? '') === 'success', substr($sup->lastBody(), 0, 100));

// History shows closed
$sup->get('/_rpt/history.php');
$hist = $sup->lastBody();
fqa('5_history_shows_ticket', strpos($hist, (string)$ticketId) !== false, (string)$ticketId);

// 6 Instant resolve
$admin->get('/_maint/quick_resolve.php');
if (preg_match('/WCC_CSRF\s*=\s*"([^"]+)"/', $admin->lastBody(), $m)) {
    $qcsrf = $m[1];
} else {
    $qcsrf = $scsrf;
}
$admin->postJson('/api/submit_instant_resolve.php', [
    'equip_id' => $equipId,
    'action_taken' => $tag . ' quick fix',
    'csrf' => $qcsrf,
], $qcsrf);
$qj = json_decode($admin->lastBody(), true);
$qTid = $qj['ticket_id'] ?? null;
fqa('6_instant_resolve', ($qj['status'] ?? '') === 'success' && $qTid, (string)$qTid);
if ($qTid) {
    $st = $pdo->prepare("SELECT status, closed_at FROM active_tickets WHERE ticket_id = ?");
    $st->execute([$qTid]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    fqa('6_closed_at', $row && strtoupper($row['status']) === 'CLOSED' && !empty($row['closed_at']), json_encode($row));
}

// 7-8 Equipment / tooling BOM docs APIs
$eqId = $equipId;
$tlId = (int)$pdo->query("SELECT tooling_id FROM toolings WHERE deleted_at IS NULL LIMIT 1")->fetchColumn();
$admin->get('/api/get_equipment_bom.php?equip_id=' . $eqId);
$bj = json_decode($admin->lastBody(), true);
fqa('7_equip_bom', ($bj['status'] ?? '') === 'success', 'HTTP ' . $admin->lastStatus());
$admin->get('/api/get_equipment_docs.php?equip_id=' . $eqId);
$dj = json_decode($admin->lastBody(), true);
fqa('7_equip_docs', ($dj['status'] ?? '') === 'success', 'HTTP ' . $admin->lastStatus());
$admin->get('/api/get_tooling_bom.php?tooling_id=' . $tlId);
$tb = json_decode($admin->lastBody(), true);
fqa('8_tooling_bom', ($tb['status'] ?? '') === 'success', 'HTTP ' . $admin->lastStatus());
$admin->get('/api/get_tooling_docs.php?tooling_id=' . $tlId);
$td = json_decode($admin->lastBody(), true);
fqa('8_tooling_docs', ($td['status'] ?? '') === 'success', 'HTTP ' . $admin->lastStatus());

// 9 Admin tooling vault
$admin->get('/_eam/setup_vault_toolings.php');
fqa('9_admin_vault', $admin->lastStatus() === 200 && strpos($admin->lastBody(), 'vaultTable') !== false && !denied($admin->lastBody()));

// 10 Operator vault denied
$op = new WccAuditHttpClient($base, 25);
$op->login('r.silva', $pass);
$op->get('/_eam/setup_vault_toolings.php');
fqa('10_op_vault_denied', denied($op->lastBody()), 'HTTP ' . $op->lastStatus());

// 11 Flush tooling on tech: set view_toolings false in permissions_json then verify deny
$techRow = $pdo->prepare("SELECT user_id, role_level, permissions_json FROM users WHERE username='j.okafor'");
$techRow->execute();
$tr = $techRow->fetch(PDO::FETCH_ASSOC);
$origJson = $tr['permissions_json'];
require_once __DIR__ . '/../../rbac.php';
$eff = wcc_get_permissions((int)$tr['role_level'], $tr['permissions_json']);
$eff['view_toolings'] = false;
$eff['manage_toolings'] = false;
$pdo->prepare("UPDATE users SET permissions_json=? WHERE user_id=?")->execute([json_encode($eff), $tr['user_id']]);
$tech2 = new WccAuditHttpClient($base, 25);
$tech2->login('j.okafor', $pass);
$tech2->get('/_eam/toolings.php');
fqa('11_flush_tooling_deny', denied($tech2->lastBody()), 'after override');
// restore
$pdo->prepare("UPDATE users SET permissions_json=? WHERE user_id=?")->execute([$origJson, $tr['user_id']]);
$tech3 = new WccAuditHttpClient($base, 25);
$tech3->login('j.okafor', $pass);
$tech3->get('/_eam/toolings.php');
fqa('11_flush_restore', !denied($tech3->lastBody()) && strpos($tech3->lastBody(), 'ledgerTable') !== false);

// 12 Language
$admin->get('/my_profile.php');
fqa('12_profile_lang_picker', strpos($admin->lastBody(), 'profile_locale') !== false || strpos($admin->lastBody(), 'name="locale"') !== false);
// set vi
$uid = (int)$pdo->query("SELECT user_id FROM users WHERE username='a.rivera'")->fetchColumn();
$pdo->prepare("UPDATE users SET locale='vi' WHERE user_id=?")->execute([$uid]);
$adminVi = new WccAuditHttpClient($base, 25);
$adminVi->login($cfg['admin_user'], $pass);
$adminVi->get('/index.php');
// session may load locale from DB on rebuild
$bodyVi = $adminVi->lastBody();
// Vietnamese nav might say "Phiếu" or WCC_LOCALE
$hasVi = strpos($bodyVi, 'WCC_LOCALE = "vi"') !== false || strpos($bodyVi, "WCC_LOCALE = 'vi'") !== false
    || strpos($bodyVi, 'Phiếu') !== false || strpos($bodyVi, 'việc') !== false;
fqa('12_language_vi', $hasVi, 'locale marker in HTML');
$pdo->prepare("UPDATE users SET locale='en' WHERE user_id=?")->execute([$uid]);

// 13 REST
$keyFile = __DIR__ . '/.qa_api_key';
$key = is_file($keyFile) ? trim(file_get_contents($keyFile)) : '';
if ($key === '') {
    $key = bin2hex(random_bytes(24));
    $pdo->prepare("UPDATE users SET api_key=? WHERE username=?")->execute([$key, $cfg['admin_user']]);
    file_put_contents($keyFile, $key);
}
$ch = curl_init($base . '/api/v1/me');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['X-API-Key: ' . $key, 'Accept: application/json'],
    CURLOPT_TIMEOUT => 20,
]);
$meBody = curl_exec($ch);
$meCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$meJ = json_decode((string)$meBody, true);
fqa('13_rest_me', $meCode === 200 && !empty($meJ['success']), 'HTTP ' . $meCode);
$ch = curl_init($base . '/api/v1/tickets?per_page=3');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['X-API-Key: ' . $key, 'Accept: application/json'],
    CURLOPT_TIMEOUT => 20,
]);
$tkBody = curl_exec($ch);
$tkCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$tkJ = json_decode((string)$tkBody, true);
fqa('13_rest_tickets', $tkCode === 200 && !empty($tkJ['success']), 'HTTP ' . $tkCode);

// 14 Backup dump exists
$dumps = glob(__DIR__ . '/../../backups/pre_launch_*/workshop_db_*.sql');
$best = $dumps ? max(array_map('filesize', $dumps)) : 0;
fqa('14_backup_dump', $best > 100000, 'largest_dump_bytes=' . $best);

// Soft-delete health query present
$src = file_get_contents(__DIR__ . '/../../_maint/active_tickets.php');
fqa('health_soft_delete', strpos($src, 'deleted_at IS NULL') !== false);

echo "\n=== FQA manual path: pass=$okN fail=$failN ===\n";
$report = __DIR__ . '/reports/fqa_manual_' . date('Ymd_His') . '.md';
file_put_contents($report, "# FQA manual path (A4)\n\npass=$okN fail=$failN\n\n```\n" . implode("\n", $lines) . "\n```\n");
echo "Report: $report\n";
exit($failN > 0 ? 1 : 0);
