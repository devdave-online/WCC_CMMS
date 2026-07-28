<?php
return function (WccAuditReport $report, array $ctx): void {
    $section = 'Inventory';
    /** @var WccAuditHttpClient $http */
    $http = $ctx['http'];
    if (isset($ctx['ensure_login']) && is_callable($ctx['ensure_login'])) {
        ($ctx['ensure_login'])();
    }

    $r = $http->get('/_logi/inventory.php');
    if ($r['status'] === 403) {
        $report->skip($section, 'inventory_page', '403');
        return;
    }
    if ($r['status'] >= 500 || $r['status'] === 0) {
        $report->fail($section, 'inventory_page', 'HTTP ' . $r['status']);
        return;
    }
    $report->ok($section, 'inventory_page', 'HTTP ' . $r['status']);
    if (stripos($r['body'], 'search') !== false || stripos($r['body'], 'filter') !== false || str_contains($r['body'], 'ledgerSearch')) {
        $report->ok($section, 'inventory_search_ui');
    } else {
        $report->fail($section, 'inventory_search_ui', 'no search markers len=' . strlen($r['body']));
    }

    $a = $http->get('/_logi/inventory_audit.php');
    if ($a['status'] === 200 || $a['status'] === 403) {
        $report->ok($section, 'inventory_audit', 'HTTP ' . $a['status']);
    } else {
        $report->fail($section, 'inventory_audit', 'HTTP ' . $a['status']);
    }
};
