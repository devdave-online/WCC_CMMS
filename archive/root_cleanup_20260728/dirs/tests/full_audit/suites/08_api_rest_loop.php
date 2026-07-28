<?php
return function (WccAuditReport $report, array $ctx): void {
    $section = 'REST-Companion';
    /** @var WccAuditHttpClient $http */
    $http = $ctx['http'];
    /** @var WccAuditDbProbe $db */
    $db = $ctx['db'];
    if (isset($ctx['ensure_login']) && is_callable($ctx['ensure_login'])) {
        ($ctx['ensure_login'])();
    }

    // Session JSON APIs — 200, 401/403 all acceptable closed forms; 500 is fail
    $n = $http->get('/api/notifications.php');
    if ($n['status'] >= 500 || $n['status'] === 0) {
        $report->fail($section, 'notifications', 'HTTP ' . $n['status'] . ' ' . substr($n['body'], 0, 80));
    } else {
        $report->ok($section, 'notifications', 'HTTP ' . $n['status']);
    }

    // REST v1 without API key should fail closed (401/403/json error), not 500
    $rest = $http->get('/api/v1/index.php');
    if ($rest['status'] >= 500) {
        $report->fail($section, 'rest_v1_no_key', 'HTTP 500');
    } else {
        $report->ok($section, 'rest_v1_no_key', 'HTTP ' . $rest['status']);
    }

    // Companion toolings — Basic auth optional; unauthenticated should not 500
    $ct = $http->get('/api/companion/toolings.php');
    if ($ct['status'] >= 500) {
        $report->fail($section, 'companion_toolings', 'HTTP 500 ' . substr($ct['body'], 0, 80));
    } else {
        $report->ok($section, 'companion_toolings', 'HTTP ' . $ct['status']);
    }

    // If user has api_key, try REST equipment list
    $row = $db->all('SELECT api_key FROM users WHERE api_key IS NOT NULL AND api_key != "" LIMIT 1');
    if ($row && !empty($row[0]['api_key'])) {
        $key = $row[0]['api_key'];
        $ch = curl_init($ctx['config']['base_url'] . '/api/v1/equipment');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $key, 'Accept: application/json'],
        ]);
        // path may be /api/v1/index.php?resource= — try common patterns
        // Prefer index routing
        curl_close($ch);
        $r2 = $http->request('GET', '/api/v1/index.php', null, [
            'Authorization' => 'Bearer ' . $key,
            'Accept' => 'application/json',
        ]);
        if ($r2['status'] >= 500) {
            $report->fail($section, 'rest_with_key', 'HTTP 500');
        } else {
            $report->ok($section, 'rest_with_key', 'HTTP ' . $r2['status']);
        }
    } else {
        $report->skip($section, 'rest_with_key', 'no user api_key in DB');
    }
};
