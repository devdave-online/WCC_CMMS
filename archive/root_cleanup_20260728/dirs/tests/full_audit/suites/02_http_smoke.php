<?php
/**
 * HTTP smoke: unauthenticated gate + authenticated load for every registry page.
 * @return callable(WccAuditReport, array): void
 */
return function (WccAuditReport $report, array $ctx): void {
    $section = 'HTTP-Smoke';
    /** @var WccAuditHttpClient $http */
    $http = $ctx['http'];
    /** @var WccAuditDbProbe $db */
    $db = $ctx['db'];
    $registry = $ctx['registry'];

    $equipId = $db->firstEquipId() ?? 1;
    $toolingId = $db->firstToolingId() ?? 1;

    // Unauthenticated: protected path should not 500
    $httpUnauth = new WccAuditHttpClient($ctx['config']['base_url'], (int)$ctx['config']['timeout']);
    $r = $httpUnauth->get('/_eam/toolings.php', false);
    if ($r['status'] === 0) {
        $report->fail($section, 'unauth_toolings_reachable', 'server unreachable: ' . $r['body']);
    } elseif ($r['status'] >= 500) {
        $report->fail($section, 'unauth_toolings_no_500', 'HTTP ' . $r['status']);
    } else {
        $report->ok($section, 'unauth_toolings_no_500', 'HTTP ' . $r['status']);
    }

    // Login (suite runner also forces login; re-confirm here)
    $user = $ctx['config']['admin_user'];
    $pass = $ctx['config']['admin_pass'];
    if (isset($ctx['ensure_login']) && is_callable($ctx['ensure_login'])) {
        ($ctx['ensure_login'])(true);
    }
    if (!$http->isAuthenticated()) {
        if (!$http->login($user, $pass) && !$http->login('admin', 'password') && !$http->login('admin', 'Demo2026!')) {
            $report->fail($section, 'admin_login', "could not login as $user (or admin fallback)");
            $report->skip($section, 'all_authenticated_loads', 'no session');
            return;
        }
    }
    $report->ok($section, 'admin_login', $user);

    $home = $http->get('/index.php');
    $csrf = $http->extractCsrf($home['body']);
    if ($csrf) {
        $report->ok($section, 'csrf_token_present');
    } else {
        $report->fail($section, 'csrf_token_present', 'WCC_CSRF not found after login');
    }

    foreach ($registry as $entry) {
        $id = $entry['id'];
        $path = $entry['path'];
        $path = str_replace(
            ['{equip_id}', '{tooling_id}'],
            [(string)$equipId, (string)$toolingId],
            $path
        );
        $caps = $entry['capabilities'] ?? [];
        $needsLogin = !empty($entry['require_login']);

        // Do not hit /login.php while authenticated mid-suite — it can confuse session UX.
        // Still load it once unauth above; when authed, skip re-load or accept 200.
        $res = $http->get($path, true);
        $status = $res['status'];
        $body = $res['body'];

        if ($status === 0) {
            $report->fail($section, 'load:' . $id, 'unreachable');
            continue;
        }
        if ($status >= 500) {
            $report->fail($section, 'load:' . $id, 'HTTP ' . $status . ' ' . substr(strip_tags($body), 0, 120));
            continue;
        }
        // 403/302 acceptable for missing perms depending on role
        if ($status === 403) {
            $report->skip($section, 'load:' . $id, 'HTTP 403 (perm)');
            continue;
        }
        $report->ok($section, 'load:' . $id, 'HTTP ' . $status);

        if (!empty($caps['marker'])) {
            $m = $caps['marker'];
            if (stripos($body, $m) !== false) {
                $report->ok($section, 'marker:' . $id, $m);
            } else {
                // soft: some pages redirect
                if ($status >= 300 && $status < 400) {
                    $report->skip($section, 'marker:' . $id, 'redirect');
                } else {
                    $report->fail($section, 'marker:' . $id, "missing '$m'");
                }
            }
        }

        if (!empty($caps['search'])) {
            if (str_contains($body, 'ledgerSearch') || str_contains($body, 'Search') || str_contains($body, 'search')) {
                $report->ok($section, 'search_ui:' . $id);
            } else {
                $report->fail($section, 'search_ui:' . $id, 'no search UI markers');
            }
        }

        if (!empty($caps['search_per_column'])) {
            if (str_contains($body, 'ondrop="dropSearch') || str_contains($body, 'dropSearch')) {
                $report->ok($section, 'column_search:' . $id);
            } else {
                $report->fail($section, 'column_search:' . $id, 'no dropSearch handlers on columns');
            }
        }

        if (!empty($caps['accordion'])) {
            if (str_contains($body, 'parent-row') && str_contains($body, 'child-row')) {
                $report->ok($section, 'accordion:' . $id);
            } else {
                $report->fail($section, 'accordion:' . $id, 'missing parent/child-row');
            }
        }

        foreach (['marker_docs_modal', 'marker_bom_modal'] as $mk) {
            if (!empty($caps[$mk])) {
                $needle = $caps[$mk];
                if (str_contains($body, $needle)) {
                    $report->ok($section, $mk . ':' . $id);
                } else {
                    $report->fail($section, $mk . ':' . $id, "missing $needle");
                }
            }
        }

        if (!empty($caps['api_json']) || !empty($caps['api_bom']) || !empty($caps['api_docs'])) {
            // handled in assets suite / here for dedicated API entries
        }

        if (!empty($caps['api_json'])) {
            $json = json_decode($body, true);
            if (is_array($json)) {
                $report->ok($section, 'json:' . $id);
                if (!empty($caps['expect_success']) && (($json['status'] ?? '') !== 'success')) {
                    $report->fail($section, 'json_success:' . $id, json_encode($json));
                } elseif (!empty($caps['expect_success'])) {
                    $report->ok($section, 'json_success:' . $id);
                }
            } else {
                // might be HTML error
                $report->fail($section, 'json:' . $id, 'not JSON HTTP ' . $status);
            }
        }

        // Page-level BOM/docs API probes
        foreach (['api_bom', 'api_docs'] as $ak) {
            if (empty($caps[$ak])) {
                continue;
            }
            $apiPath = str_replace(
                ['{equip_id}', '{tooling_id}'],
                [(string)$equipId, (string)$toolingId],
                $caps[$ak]
            );
            $ar = $http->get($apiPath);
            $aj = json_decode($ar['body'], true);
            if ($ar['status'] === 200 && is_array($aj) && ($aj['status'] ?? '') === 'success') {
                $report->ok($section, $ak . ':' . $id, 'rows=' . (isset($aj['data']) ? count((array)$aj['data']) : '?'));
            } else {
                $report->fail($section, $ak . ':' . $id, 'HTTP ' . $ar['status'] . ' ' . substr($ar['body'], 0, 100));
            }
        }
    }

    // Store csrf for later suites via shared file? use ctx by reference — PHP return won't work.
    // Write csrf to temp for mutate suites
    if (!empty($csrf)) {
        file_put_contents(sys_get_temp_dir() . '/wcc_qa_csrf_' . getmypid() . '.txt', $csrf);
    }
};
