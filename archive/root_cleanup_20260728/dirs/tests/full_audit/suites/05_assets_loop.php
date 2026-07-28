<?php
/**
 * Assets deep checks: equipment + tooling BOM/docs APIs and vault markers.
 * @return callable(WccAuditReport, array): void
 */
return function (WccAuditReport $report, array $ctx): void {
    $section = 'Assets';
    /** @var WccAuditHttpClient $http */
    $http = $ctx['http'];
    /** @var WccAuditDbProbe $db */
    $db = $ctx['db'];
    $mutate = !empty($ctx['mutate']);
    $tag = $ctx['config']['tag'] ?? '[QA-AUDIT]';

    if (isset($ctx['ensure_login']) && is_callable($ctx['ensure_login'])) {
        ($ctx['ensure_login'])();
    }

    $equipId = $db->firstEquipId();
    $toolingId = $db->firstToolingId();

    // Equipment BOM/docs
    if ($equipId) {
        foreach ([
            'equip_bom' => '/api/get_equipment_bom.php?equip_id=' . $equipId,
            'equip_docs' => '/api/get_equipment_docs.php?equip_id=' . $equipId,
        ] as $id => $path) {
            $r = $http->get($path);
            $j = json_decode($r['body'], true);
            if (is_array($j) && ($j['status'] ?? '') === 'success') {
                $report->ok($section, $id, 'count=' . count($j['data'] ?? []));
            } else {
                $report->fail($section, $id, substr($r['body'], 0, 150));
            }
        }
        $eq = $http->get('/_eam/equipment.php');
        if (str_contains($eq['body'], 'openBOMModal') && str_contains($eq['body'], 'View Linked Parts')) {
            $report->ok($section, 'equipment_bom_link');
        } else {
            $report->fail($section, 'equipment_bom_link', 'View Linked Parts / openBOMModal missing');
        }
    } else {
        $report->skip($section, 'equipment_apis', 'no equipment');
    }

    // Tooling BOM/docs
    if ($toolingId) {
        foreach ([
            'tooling_bom' => '/api/get_tooling_bom.php?tooling_id=' . $toolingId,
            'tooling_docs' => '/api/get_tooling_docs.php?tooling_id=' . $toolingId,
        ] as $id => $path) {
            $r = $http->get($path);
            $j = json_decode($r['body'], true);
            if (is_array($j) && ($j['status'] ?? '') === 'success') {
                $report->ok($section, $id, 'count=' . count($j['data'] ?? []));
            } else {
                $report->fail($section, $id, substr($r['body'], 0, 150));
            }
        }
        $tl = $http->get('/_eam/toolings.php');
        if (str_contains($tl['body'], 'openBOMModal') && str_contains($tl['body'], 'View Linked Parts')) {
            $report->ok($section, 'tooling_bom_link');
        } else {
            $report->fail($section, 'tooling_bom_link', 'View Linked Parts missing on ledger');
        }
        if (str_contains($tl['body'], 'get_tooling_bom.php')) {
            $report->ok($section, 'tooling_bom_api_wired');
        } else {
            $report->fail($section, 'tooling_bom_api_wired', 'ledger JS not calling get_tooling_bom');
        }
        if (str_contains($tl['body'], 'openToolingDocsModal') || str_contains($tl['body'], 'View docs')) {
            $report->ok($section, 'tooling_docs_link');
        } else {
            $report->fail($section, 'tooling_docs_link', 'docs link missing');
        }

        $vault = $http->get('/_eam/setup_vault_toolings.php');
        if ($vault['status'] === 403) {
            $report->skip($section, 'tooling_vault', '403 manage_equipment');
        } else {
            foreach (['Manage BOM', 'Docs', 'Code Configurator', 'vaultTable', 'toolingDocsModal'] as $needle) {
                if (str_contains($vault['body'], $needle)) {
                    $report->ok($section, 'vault_has:' . preg_replace('/\W+/', '_', $needle));
                } else {
                    $report->fail($section, 'vault_has:' . preg_replace('/\W+/', '_', $needle), 'missing');
                }
            }
            // Symbology save setting present
            if (str_contains($vault['body'], 'saveToolingLabelSymbology') || str_contains($vault['body'], 'labelSymbology')) {
                $report->ok($section, 'vault_label_symbology_ui');
            } else {
                $report->fail($section, 'vault_label_symbology_ui', 'missing');
            }
        }
    } else {
        $report->skip($section, 'tooling_apis', 'no toolings table/rows');
    }

    // Optional mutate: create tooling via POST form
    if ($mutate && $toolingId) {
        $vault = $http->get('/_eam/setup_vault_toolings.php');
        $csrf = $http->extractCsrf($vault['body']);
        if (!$csrf) {
            $report->skip($section, 'create_tooling', 'no csrf on vault');
            return;
        }
        // Soft create
        $fields = [
            'csrf' => $csrf,
            'tooling_name' => $tag . ' probe tool ' . date('His'),
            'category' => 'Other',
            'status' => 'Available',
            'condition_rating' => 'Good',
            'is_active' => '1',
            // blank code/barcode → auto
        ];
        $res = $http->postForm('/_eam/setup_vault_toolings.php', $fields, true);
        if ($res['status'] < 500 && $res['status'] !== 0) {
            // verify row
            $found = $db->one(
                "SELECT tooling_id FROM toolings WHERE tooling_name LIKE ? ORDER BY tooling_id DESC LIMIT 1",
                [$tag . '%']
            );
            if ($found) {
                $report->ok($section, 'create_tooling', 'id=' . $found);
                // soft-delete cleanup
                try {
                    $db->pdo()->prepare("UPDATE toolings SET deleted_at = NOW(), status='Retired' WHERE tooling_id = ?")
                        ->execute([(int)$found]);
                    $report->ok($section, 'cleanup_tooling', 'soft-deleted ' . $found);
                } catch (Throwable $e) {
                    $report->fail($section, 'cleanup_tooling', $e->getMessage());
                }
            } else {
                $report->fail($section, 'create_tooling', 'POST ok but row not found; HTTP ' . $res['status']);
            }
        } else {
            $report->fail($section, 'create_tooling', 'HTTP ' . $res['status']);
        }
    } else {
        $report->skip($section, 'create_tooling', 'pass --mutate to create/soft-delete tooling');
    }
};
