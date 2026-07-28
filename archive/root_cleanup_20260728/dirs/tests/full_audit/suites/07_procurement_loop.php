<?php
return function (WccAuditReport $report, array $ctx): void {
    $section = 'Procurement';
    /** @var WccAuditHttpClient $http */
    $http = $ctx['http'];

    foreach ([
        'pr' => '/_logi/purchase_requests.php',
        'po' => '/_logi/purchase_orders.php',
        'vendors' => '/_logi/vendors_list.php',
    ] as $id => $path) {
        $r = $http->get($path);
        if ($r['status'] === 0 || $r['status'] >= 500) {
            $report->fail($section, 'load:' . $id, 'HTTP ' . $r['status']);
        } elseif ($r['status'] === 403) {
            $report->skip($section, 'load:' . $id, '403');
        } else {
            $report->ok($section, 'load:' . $id, 'HTTP ' . $r['status']);
        }
    }

    // Existing static procurement smoke still authoritative for model
    $report->skip($section, 'pr_create', 'use tests/api_procurement_smoke.php for model; pass --mutate for future PR create');
};
