<?php
/**
 * Close a ticket and prove it appears first on history + notification fired.
 */
if (PHP_SAPI !== 'cli') exit("CLI only\n");
require_once __DIR__ . '/lib/HttpClient.php';
require_once __DIR__ . '/lib/DbProbe.php';
$c = require __DIR__ . '/config.php';
$h = new WccAuditHttpClient($c['base_url'], 25);
$db = new WccAuditDbProbe();
$fail = 0;
function chk(string $l, bool $ok, string $d = ''): void {
    global $fail;
    echo ($ok ? '  OK  ' : ' FAIL ') . $l . ($d ? " — $d" : '') . "\n";
    if (!$ok) $fail++;
}

if (!$h->login($c['admin_user'], $c['admin_pass'])) {
    chk('login', false);
    exit(1);
}
chk('login', true);

// Ensure an open ticket exists
$pdo = $db->pdo();
$openId = $db->one("SELECT ticket_id FROM active_tickets WHERE status IN ('OPEN','PENDING','ESCALATED','HOLD') ORDER BY created_at DESC LIMIT 1");
if (!$openId) {
    // create via API
    $page = $h->get('/index.php');
    $csrf = $h->extractCsrf($page['body']);
    $eid = $db->firstEquipId();
    $r = $h->postJson('/api/submit_ticket.php', [
        'equip_id' => $eid,
        'report_date' => date('Y-m-d'),
        'report_time' => date('H:i:s'),
        'pic' => 'QA',
        'fault_desc' => '[QA-AUDIT] history close probe',
        'priority' => 'normal',
    ], $csrf);
    $j = json_decode($r['body'], true);
    chk('create_open_ticket', ($j['status'] ?? '') === 'success', substr($r['body'], 0, 120));
    if (preg_match('/Ticket ID:\s*(\S+)/', $j['message'] ?? '', $m)) {
        $openId = $m[1];
    } else {
        $openId = $db->one("SELECT ticket_id FROM active_tickets WHERE fault_desc LIKE '%history close probe%' ORDER BY created_at DESC LIMIT 1");
    }
}
chk('have_open_ticket', (bool)$openId, (string)$openId);

$page = $h->get('/_maint/closeout.php?ticket_id=' . urlencode((string)$openId));
// closeout page may need ticket in body; still get csrf from any authed page
$page = $h->get('/_maint/active_tickets.php');
$csrf = $h->extractCsrf($page['body']);
$close = $h->postJson('/api/submit_closeout.php', ['ticket_id' => $openId], $csrf);
$cj = json_decode($close['body'], true);
chk('closeout_api', ($cj['status'] ?? '') === 'success', substr($close['body'], 0, 150));

$row = $db->all("SELECT ticket_id, status, closed_by, closed_at FROM active_tickets WHERE ticket_id = ?", [$openId]);
$st = $row[0] ?? [];
chk('db_status_closed', strtoupper($st['status'] ?? '') === 'CLOSED', json_encode($st));
chk('db_closed_at_set', !empty($st['closed_at']), 'closed_at=' . ($st['closed_at'] ?? 'null'));
chk('db_closed_by_set', !empty($st['closed_by']) && $st['closed_by'] !== 'Unknown', 'closed_by=' . ($st['closed_by'] ?? ''));

// Top of history order
$top = $db->one("SELECT ticket_id FROM active_tickets WHERE UPPER(status)='CLOSED' ORDER BY COALESCE(closed_at, created_at) DESC, ticket_id DESC LIMIT 1");
chk('history_sort_top_is_new', $top === $openId, "top=$top expected=$openId");

$hist = $h->get('/_rpt/history.php');
chk('history_page_has_ticket', str_contains($hist['body'], (string)$openId), 'page HTTP ' . $hist['status']);
chk('history_has_closed_at_col', str_contains($hist['body'], 'Closed At'));

// Notifications for view_history users (admin)
$uid = (int)$db->one("SELECT user_id FROM users WHERE username = ?", [$c['admin_user']]);
// actor is excluded from notify_perm — check another admin or same user if not excluded... exclude is actor so admin won't get own close notice
// Check any notification with this ticket id
$n = $db->one("SELECT COUNT(*) FROM notifications WHERE message LIKE ?", ['%' . $openId . '%']);
chk('notification_created', (int)$n > 0, 'count=' . $n);

// WO: ensure completed_date path - soft check completed sort column exists
$woDone = $db->one("SELECT wo_id FROM work_orders WHERE status='Completed' AND completed_date IS NOT NULL ORDER BY completed_date DESC LIMIT 1");
chk('wo_completed_have_dates', (bool)$woDone, 'wo_id=' . $woDone);

echo $fail ? "DONE fail=$fail\n" : "DONE all ok\n";
exit($fail ? 1 : 0);
