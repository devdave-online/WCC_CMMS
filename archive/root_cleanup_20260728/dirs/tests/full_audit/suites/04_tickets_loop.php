<?php
/**
 * Ticket create simulation (only with --mutate).
 * @return callable(WccAuditReport, array): void
 */
return function (WccAuditReport $report, array $ctx): void {
    $section = 'Tickets';
    /** @var WccAuditHttpClient $http */
    $http = $ctx['http'];
    /** @var WccAuditDbProbe $db */
    $db = $ctx['db'];
    $mutate = !empty($ctx['mutate']);
    $tag = $ctx['config']['tag'] ?? '[QA-AUDIT]';

    $home = $http->get('/index.php');
    $csrf = $http->extractCsrf($home['body']);
    if (!$csrf) {
        $csrfFile = sys_get_temp_dir() . '/wcc_qa_csrf_' . getmypid() . '.txt';
        if (is_file($csrfFile)) {
            $csrf = trim((string)file_get_contents($csrfFile));
        }
    }
    if (!$csrf) {
        $report->fail($section, 'csrf', 'no token');
        return;
    }
    $report->ok($section, 'csrf_ready');

    $equipId = $db->firstEquipId();
    if (!$equipId) {
        $report->skip($section, 'create_ticket', 'no equipment rows');
        return;
    }

    // Active tickets page loads
    $at = $http->get('/_maint/active_tickets.php');
    if ($at['status'] === 200 && str_contains($at['body'], 'parent-row')) {
        $report->ok($section, 'active_tickets_has_rows_markup');
    } elseif ($at['status'] === 200) {
        $report->ok($section, 'active_tickets_load', 'no parent-row (empty ok)');
    } else {
        $report->fail($section, 'active_tickets_load', 'HTTP ' . $at['status']);
    }

    if (!$mutate) {
        $report->skip($section, 'create_ticket', 'pass --mutate to create a QA ticket');
        return;
    }

    $payload = [
        'equip_id' => $equipId,
        'report_date' => date('Y-m-d'),
        'report_time' => date('H:i:s'),
        'pic' => 'QA Audit',
        'fault_desc' => $tag . ' automated functional audit ticket ' . date('c'),
        'priority' => 'normal',
        'event_class' => 'failure',
        'csrf' => $csrf,
    ];
    $res = $http->postJson('/api/submit_ticket.php', $payload, $csrf);
    $json = json_decode($res['body'], true);
    if (($json['status'] ?? '') === 'success') {
        $report->ok($section, 'create_ticket', $json['message'] ?? 'ok');
        // Track for cleanup note
        if (preg_match('/Ticket ID:\s*(\S+)/', $json['message'] ?? '', $m)) {
            file_put_contents(
                sys_get_temp_dir() . '/wcc_qa_tickets_' . getmypid() . '.txt',
                $m[1] . "\n",
                FILE_APPEND
            );
        }
    } else {
        $report->fail($section, 'create_ticket', 'HTTP ' . $res['status'] . ' ' . substr($res['body'], 0, 200));
    }
};
