<?php
/**
 * Search capability probes: count filterable columns, assert JS hooks.
 * @return callable(WccAuditReport, array): void
 */
return function (WccAuditReport $report, array $ctx): void {
    $section = 'Search';
    /** @var WccAuditHttpClient $http */
    $http = $ctx['http'];

    $pages = [
        'equipment' => '/_eam/equipment.php',
        'toolings' => '/_eam/toolings.php',
        'equipment_vault' => '/_eam/setup_vault_equipment.php',
        'toolings_vault' => '/_eam/setup_vault_toolings.php',
        'active_tickets' => '/_maint/active_tickets.php',
        'history' => '/_rpt/history.php',
        'work_orders' => '/_maint/work_orders.php',
        'inventory' => '/_logi/inventory.php',
    ];

    if (isset($ctx['ensure_login']) && is_callable($ctx['ensure_login'])) {
        ($ctx['ensure_login'])();
    }

    foreach ($pages as $id => $path) {
        $r = $http->get($path);
        if ($r['status'] >= 500 || $r['status'] === 0) {
            $report->fail($section, 'fetch:' . $id, 'HTTP ' . $r['status'] . ' len=' . strlen($r['body']));
            continue;
        }
        if ($r['status'] === 403) {
            $report->skip($section, 'fetch:' . $id, '403');
            continue;
        }
        $body = $r['body'];
        // Detect login bounce
        if (str_contains($body, 'name="username"') && str_contains($body, 'name="password"') && !str_contains($body, 'ledgerTable')) {
            $report->fail($section, 'fetch:' . $id, 'got login page (session lost)');
            if (isset($ctx['ensure_login'])) {
                ($ctx['ensure_login'])();
            }
            continue;
        }
        $hasSearch = str_contains($body, 'ledgerSearch')
            || str_contains($body, 'id="ledgerSearch"')
            || preg_match('/type=["\']search["\']/i', $body)
            || str_contains($body, 'placeholder="Search')
            || str_contains($body, 'Drag to column');
        if ($hasSearch) {
            $report->ok($section, 'ledgerSearch:' . $id);
        } else {
            if (stripos($body, 'search') !== false || stripos($body, 'filter') !== false) {
                $report->ok($section, 'search_generic:' . $id);
            } else {
                $report->fail($section, 'search_input:' . $id, 'no search UI (body len ' . strlen($body) . ')');
                continue;
            }
        }

        // Per-column: count th with dropSearch
        preg_match_all('/ondrop\s*=\s*["\']dropSearch/i', $body, $m);
        $cols = count($m[0] ?? []);
        if ($cols > 0) {
            $report->ok($section, 'columns_dropSearch:' . $id, $cols . ' columns');
            // filterTable / handleSearchInput present
            if (str_contains($body, 'function filterTable') || str_contains($body, 'filterTable(')) {
                $report->ok($section, 'filterTable_js:' . $id);
            } else {
                $report->fail($section, 'filterTable_js:' . $id, 'filterTable missing');
            }
            if (str_contains($body, 'activeFilters') || str_contains($body, 'createFilterToken')) {
                $report->ok($section, 'token_filters:' . $id);
            } else {
                $report->skip($section, 'token_filters:' . $id, 'no token filter JS');
            }
        } else {
            $report->skip($section, 'columns_dropSearch:' . $id, 'page uses different filter model');
        }
    }
};
