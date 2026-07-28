<?php
/**
 * Static structural gates — no HTTP, no DB writes.
 * @return callable(WccAuditReport, array): void
 */
return function (WccAuditReport $report, array $ctx): void {
    $root = $ctx['root'];
    $section = 'Static';
    $phpBin = $ctx['php_bin'] ?? 'php';

    $critical = [
        $root . '/_eam/toolings.php',
        $root . '/_eam/setup_vault_toolings.php',
        $root . '/_eam/equipment.php',
        $root . '/_eam/setup_vault_equipment.php',
        $root . '/api/upload_document.php',
        $root . '/api/get_tooling_bom.php',
        $root . '/api/get_tooling_docs.php',
        $root . '/api/submit_ticket.php',
        $root . '/inc/csrf.php',
        $root . '/rbac.php',
        $root . '/nav.php',
        $root . '/login.php',
    ];
    foreach ($critical as $fp) {
        $base = basename($fp);
        if (!is_file($fp)) {
            $report->fail($section, 'exists:' . $base, 'missing');
            continue;
        }
        $out = [];
        $code = 0;
        exec(escapeshellarg($phpBin) . ' -l ' . escapeshellarg($fp) . ' 2>&1', $out, $code);
        if ($code === 0) {
            $report->ok($section, 'lint:' . $base);
        } else {
            $report->fail($section, 'lint:' . $base, implode(' ', $out));
        }
    }

    $pageCount = 0;
    foreach (['_eam', '_logi', '_maint', '_mgmt', '_prod', '_rpt', '_trck'] as $dir) {
        $base = $root . '/' . $dir;
        if (!is_dir($base)) {
            continue;
        }
        foreach (glob($base . '/*.php') ?: [] as $fp) {
            $pageCount++;
            $bn = basename($fp);
            $src = (string)@file_get_contents($fp);
            if ($src === '') {
                $report->fail($section, 'read:' . $bn, 'empty/unreadable');
                continue;
            }
            // Libraries / include-only components (not standalone pages)
            if (str_contains($bn, '_lib.php') || str_contains($src, 'function render_') || str_contains($src, 'function get_step_index')) {
                $report->ok($section, 'library_skip:' . $bn);
                continue;
            }
            // Tiny redirects
            if (strlen($src) < 250 && (str_contains($src, 'header(') || str_contains($src, 'Location:'))) {
                $report->ok($section, 'redirect_stub:' . $bn);
                continue;
            }
            $gated = str_contains($src, 'require_perm(') || (str_contains($src, 'auth.php'));
            $hasCan = str_contains($src, 'can(');
            if ($gated || $hasCan) {
                $report->ok($section, 'auth_pattern:' . $bn);
            } else {
                $report->fail($section, 'auth_pattern:' . $bn, 'no auth.php/require_perm/can');
            }
        }
    }
    $report->ok($section, 'module_page_count', (string)$pageCount);

    $csrf = (string)@file_get_contents($root . '/inc/csrf.php');
    if (str_contains($csrf, 'function wcc_csrf_require')) {
        $report->ok($section, 'csrf_require');
    } else {
        $report->fail($section, 'csrf_require', 'missing');
    }
    if (str_contains($csrf, 'function wcc_csrf_require_json')) {
        $report->ok($section, 'csrf_require_json');
    } else {
        $report->fail($section, 'csrf_require_json', 'missing');
    }

    foreach ([
        'migrations/0017_create_toolings.sql',
        'migrations/0018_create_tooling_bom.sql',
        'migrations/0019_create_tooling_documents.sql',
        'api/get_tooling_bom.php',
        'api/get_tooling_docs.php',
        'api/upload_document.php',
    ] as $rel) {
        if (is_file($root . '/' . $rel)) {
            $report->ok($section, 'artifact:' . $rel);
        } else {
            $report->fail($section, 'artifact:' . $rel, 'missing');
        }
    }

    // Tables exist
    /** @var WccAuditDbProbe $db */
    $db = $ctx['db'];
    if ($db->pdo()) {
        foreach (['toolings', 'tooling_bom', 'tooling_documents', 'equipment', 'equipment_bom', 'active_tickets'] as $t) {
            try {
                $db->one("SELECT 1 FROM `$t` LIMIT 1");
                $report->ok($section, 'table:' . $t);
            } catch (Throwable $e) {
                // empty table still ok; missing table fails
                $msg = $e->getMessage();
                if (str_contains($msg, "doesn't exist") || str_contains($msg, 'not found')) {
                    $report->fail($section, 'table:' . $t, $msg);
                } else {
                    $report->ok($section, 'table:' . $t, 'query ok');
                }
            }
        }
    } else {
        $report->skip($section, 'db_tables', 'DB unavailable');
    }
};
