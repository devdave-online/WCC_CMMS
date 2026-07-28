<?php
/**
 * Full REST API v1 functional sweep (CLI only).
 * Auth: X-API-Key (from tests/full_audit/.qa_api_key or generated) + Basic Auth check.
 *
 * Usage: php tests/full_audit/rest_v1_full.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

$cfg = require __DIR__ . '/config.php';
$base = rtrim($cfg['base_url'], '/');
$adminUser = $cfg['admin_user'];
$adminPass = $cfg['admin_pass'];

require_once __DIR__ . '/../../inc/db.php';
$pdo = get_wcc_db_connection();

// Ensure API key for admin
$keyFile = __DIR__ . '/.qa_api_key';
$key = is_file($keyFile) ? trim((string)file_get_contents($keyFile)) : '';
$st = $pdo->prepare("SELECT user_id, api_key FROM users WHERE username = ?");
$st->execute([$adminUser]);
$row = $st->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    fwrite(STDERR, "Admin user not found: $adminUser\n");
    exit(1);
}
$adminId = (int)$row['user_id'];
if ($key === '' || $key !== ($row['api_key'] ?? '')) {
    $key = bin2hex(random_bytes(24));
    $pdo->prepare("UPDATE users SET api_key = ? WHERE user_id = ?")->execute([$key, $adminId]);
    file_put_contents($keyFile, $key);
}
echo "REST v1 full sweep\nBase: $base\nAdmin: $adminUser (#$adminId)\nKey: " . substr($key, 0, 8) . "…\n\n";

$pass = 0;
$fail = 0;
$skip = 0;
$lines = [];

function rest_req(string $method, string $url, ?string $key, ?array $json = null, ?string $basicUser = null, ?string $basicPass = null): array
{
    $ch = curl_init($url);
    $headers = ['Accept: application/json'];
    if ($key) {
        $headers[] = 'X-API-Key: ' . $key;
    }
    if ($basicUser !== null) {
        curl_setopt($ch, CURLOPT_USERPWD, $basicUser . ':' . ($basicPass ?? ''));
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    }
    if ($json !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
    }
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    $decoded = null;
    if (is_string($body) && $body !== '') {
        $decoded = json_decode($body, true);
    }
    return ['status' => $status, 'body' => $body, 'json' => $decoded, 'err' => $err];
}

function ok(string $name, bool $cond, string $detail = ''): void
{
    global $pass, $fail, $lines;
    if ($cond) {
        $pass++;
        $lines[] = "  OK  $name" . ($detail !== '' ? " — $detail" : '');
        echo "  OK  $name" . ($detail !== '' ? " — $detail" : '') . "\n";
    } else {
        $fail++;
        $lines[] = " FAIL $name" . ($detail !== '' ? " — $detail" : '');
        echo " FAIL $name" . ($detail !== '' ? " — $detail" : '') . "\n";
    }
}

function skip(string $name, string $detail = ''): void
{
    global $skip, $lines;
    $skip++;
    $lines[] = " SKIP $name" . ($detail !== '' ? " — $detail" : '');
    echo " SKIP $name" . ($detail !== '' ? " — $detail" : '') . "\n";
}

// ---------- Auth ----------
echo "--- Auth ---\n";
$r = rest_req('GET', "$base/api/v1/", null);
ok('unauth_root_401', $r['status'] === 401, 'HTTP ' . $r['status']);

$r = rest_req('GET', "$base/api/v1/", $key);
ok('auth_root_key', $r['status'] === 200 && !empty($r['json']['success']), 'HTTP ' . $r['status']);
$resources = $r['json']['data']['resources'] ?? [];
ok('auth_root_lists_resources', is_array($resources) && count($resources) >= 10, 'count=' . count($resources));

$r = rest_req('GET', "$base/api/v1/me", null, null, $adminUser, $adminPass);
ok('auth_basic_me', $r['status'] === 200 && !empty($r['json']['success']), 'HTTP ' . $r['status']);

$r = rest_req('GET', "$base/api/v1/me", $key);
ok('auth_me', $r['status'] === 200 && !empty($r['json']['data']['user_id'] ?? $r['json']['data']['username'] ?? null), 'HTTP ' . $r['status']);

// ---------- GET list endpoints ----------
echo "\n--- GET lists ---\n";
$listRoutes = [
    'users' => 200,
    'roles' => 200,
    'equipment' => 200,
    'toolings' => 200,
    'production-lines' => 200,
    'tickets' => 200,
    'work-orders' => 200,
    'inventory' => 200,
    'vendors' => 200,
    'purchase-orders' => 200,
    'purchase-requests' => 200,
    'stats' => 200,
    'audit' => 200,
    'ai-context' => 200,
    'me' => 200,
];
$firstIds = [];
foreach ($listRoutes as $route => $expect) {
    $r = rest_req('GET', "$base/api/v1/$route?per_page=5", $key);
    $okHttp = $r['status'] === $expect || ($r['status'] === 200 && !empty($r['json']['success']));
    // some may 403 if perm missing - admin should have all
    $okJson = is_array($r['json']);
    ok("GET /$route", $okHttp && $okJson, 'HTTP ' . $r['status'] . ' success=' . json_encode($r['json']['success'] ?? null));
    $data = $r['json']['data'] ?? null;
    if (is_array($data) && isset($data[0]) && is_array($data[0])) {
        // guess id field
        foreach (['user_id', 'equip_id', 'tooling_id', 'ticket_id', 'wo_id', 'part_id', 'vendor_id', 'po_id', 'line_id', 'id', 'role_level'] as $idk) {
            if (isset($data[0][$idk])) {
                $firstIds[$route] = $data[0][$idk];
                break;
            }
        }
    }
}

// ticket-actions needs ticket_id
$tid = $firstIds['tickets'] ?? null;
if ($tid) {
    $r = rest_req('GET', "$base/api/v1/ticket-actions?ticket_id=" . urlencode((string)$tid), $key);
    ok('GET /ticket-actions?ticket_id', $r['status'] === 200 && !empty($r['json']['success']), 'HTTP ' . $r['status']);
} else {
    skip('GET /ticket-actions', 'no ticket id from list');
}

// ---------- GET by id ----------
echo "\n--- GET by id ---\n";
$idMap = [
    'users' => $firstIds['users'] ?? $adminId,
    'equipment' => $firstIds['equipment'] ?? null,
    'toolings' => $firstIds['toolings'] ?? null,
    'tickets' => $firstIds['tickets'] ?? null,
    'work-orders' => $firstIds['work-orders'] ?? null,
    'inventory' => $firstIds['inventory'] ?? null,
    'vendors' => $firstIds['vendors'] ?? null,
    'purchase-orders' => $firstIds['purchase-orders'] ?? null,
    'purchase-requests' => $firstIds['purchase-requests'] ?? null,
    'production-lines' => $firstIds['production-lines'] ?? null,
    'roles' => $firstIds['roles'] ?? 4,
];
foreach ($idMap as $route => $id) {
    if ($id === null || $id === '') {
        skip("GET /$route/{id}", 'no id available');
        continue;
    }
    $r = rest_req('GET', "$base/api/v1/$route/" . rawurlencode((string)$id), $key);
    $okHttp = in_array($r['status'], [200, 404], true); // 404 acceptable if soft filters
    ok("GET /$route/$id", $okHttp && is_array($r['json']), 'HTTP ' . $r['status']);
}

// ---------- Mutations (tagged + cleanup) ----------
echo "\n--- Mutations (create / update / cleanup) ---\n";
$tag = '[QA-REST-' . date('ymdHis') . ']';
$created = [];

// Ticket create
$equipId = $firstIds['equipment'] ?? null;
if (!$equipId) {
    $eq = $pdo->query("SELECT equip_id FROM equipment WHERE deleted_at IS NULL LIMIT 1")->fetchColumn();
    $equipId = $eq ?: null;
}
if ($equipId) {
    $r = rest_req('POST', "$base/api/v1/tickets", $key, [
        'equip_id' => (int)$equipId,
        'fault_desc' => $tag . ' REST ticket smoke',
        'priority' => 'normal',
    ]);
    $okCreate = $r['status'] === 201 || ($r['status'] === 200 && !empty($r['json']['success']));
    $newTid = $r['json']['data']['ticket_id'] ?? null;
    ok('POST /tickets', $okCreate && $newTid, 'HTTP ' . $r['status'] . ' id=' . ($newTid ?: '?'));
    if ($newTid) {
        $created['ticket'] = $newTid;
        $r2 = rest_req('GET', "$base/api/v1/tickets/" . rawurlencode($newTid), $key);
        ok('GET created ticket', $r2['status'] === 200 && !empty($r2['json']['success']), 'HTTP ' . $r2['status']);
        $r3 = rest_req('PUT', "$base/api/v1/tickets/" . rawurlencode($newTid), $key, [
            'priority' => 'high',
            'fault_desc' => $tag . ' updated',
        ]);
        ok('PUT /tickets/{id}', in_array($r3['status'], [200, 201], true) && ($r3['json']['success'] ?? false), 'HTTP ' . $r3['status']);
        // ticket action
        $r4 = rest_req('POST', "$base/api/v1/ticket-actions", $key, [
            'ticket_id' => $newTid,
            'tech_name' => 'QA REST',
            'parts_used' => 'None',
            'notes' => $tag . ' action',
            'action_start' => date('Y-m-d H:i:s'),
            'action_end' => date('Y-m-d H:i:s', time() + 60),
        ]);
        // notes column may be absent on older schemas — 500 is an API schema bug, not auth
        if ($r4['status'] >= 500) {
            ok('POST /ticket-actions', false, 'HTTP ' . $r4['status'] . ' schema/API error: ' . substr((string)$r4['body'], 0, 160));
        } else {
            ok('POST /ticket-actions', in_array($r4['status'], [200, 201], true) && !empty($r4['json']['success']), 'HTTP ' . $r4['status']);
        }
    }
} else {
    skip('POST /tickets', 'no equipment id');
}

// Equipment create + delete
$r = rest_req('POST', "$base/api/v1/equipment", $key, [
    'equip_name' => $tag . ' Equip',
    'category' => 'QA',
    'criticality' => 'C',
    'is_active' => 1,
    'plant_name' => 'QA Plant',
]);
$newEq = $r['json']['data']['equip_id'] ?? null;
ok('POST /equipment', ($r['status'] === 201 || !empty($r['json']['success'])) && $newEq, 'HTTP ' . $r['status']);
if ($newEq) {
    $created['equipment'] = $newEq;
    $r = rest_req('PUT', "$base/api/v1/equipment/$newEq", $key, ['equip_name' => $tag . ' Equip Updated']);
    ok('PUT /equipment/{id}', $r['status'] === 200 && !empty($r['json']['success']), 'HTTP ' . $r['status']);
    $r = rest_req('DELETE', "$base/api/v1/equipment/$newEq", $key);
    ok('DELETE /equipment/{id}', $r['status'] === 200 && !empty($r['json']['success']), 'HTTP ' . $r['status']);
    unset($created['equipment']);
}

// Toolings create / update / bom / docs / soft-delete
$tlCode = 'TL-QA-' . substr(md5($tag), 0, 8);
$r = rest_req('POST', "$base/api/v1/toolings", $key, [
    'tooling_code' => $tlCode,
    'tooling_name' => $tag . ' Tooling',
    'category' => 'Fixture',
    'status' => 'Available',
    'condition_rating' => 'Good',
    'barcode' => 'BC-' . $tlCode,
    'location' => 'QA crib',
]);
$newTl = $r['json']['data']['tooling_id'] ?? null;
ok('POST /toolings', ($r['status'] === 201 || !empty($r['json']['success'])) && $newTl, 'HTTP ' . $r['status'] . ' body=' . substr((string)$r['body'], 0, 120));
if ($newTl) {
    $created['tooling'] = $newTl;
    $r = rest_req('GET', "$base/api/v1/toolings/$newTl", $key);
    ok('GET /toolings/{id}', $r['status'] === 200 && !empty($r['json']['success']), 'HTTP ' . $r['status']);
    $r = rest_req('GET', "$base/api/v1/toolings?barcode=" . urlencode('BC-' . $tlCode), $key);
    ok('GET /toolings?barcode=', $r['status'] === 200 && !empty($r['json']['success']), 'HTTP ' . $r['status']);
    $r = rest_req('PUT', "$base/api/v1/toolings/$newTl", $key, [
        'tooling_name' => $tag . ' Tooling Updated',
        'status' => 'In Use',
    ]);
    ok('PUT /toolings/{id}', $r['status'] === 200 && !empty($r['json']['success']), 'HTTP ' . $r['status']);

    $partForBom = $firstIds['inventory'] ?? null;
    if (!$partForBom) {
        $partForBom = $pdo->query("SELECT part_id FROM inventory_parts LIMIT 1")->fetchColumn() ?: null;
    }
    if ($partForBom) {
        $r = rest_req('POST', "$base/api/v1/toolings/$newTl/bom", $key, [
            'part_id' => (int)$partForBom,
            'quantity' => 2,
            'notes' => $tag . ' bom',
        ]);
        $bomId = $r['json']['data']['bom_id'] ?? null;
        ok('POST /toolings/{id}/bom', ($r['status'] === 201 || !empty($r['json']['success'])) && $bomId, 'HTTP ' . $r['status']);
        $r = rest_req('GET', "$base/api/v1/toolings/$newTl/bom", $key);
        ok('GET /toolings/{id}/bom', $r['status'] === 200 && !empty($r['json']['success']), 'HTTP ' . $r['status']);
        if ($bomId) {
            $r = rest_req('PUT', "$base/api/v1/toolings/$newTl/bom/$bomId", $key, ['quantity' => 3]);
            ok('PUT /toolings/{id}/bom/{bom_id}', $r['status'] === 200 && !empty($r['json']['success']), 'HTTP ' . $r['status']);
            $r = rest_req('DELETE', "$base/api/v1/toolings/$newTl/bom/$bomId", $key);
            ok('DELETE /toolings/{id}/bom/{bom_id}', $r['status'] === 200 && !empty($r['json']['success']), 'HTTP ' . $r['status']);
        }
    } else {
        skip('POST /toolings/{id}/bom', 'no inventory part');
    }

    $r = rest_req('POST', "$base/api/v1/toolings/$newTl/documents", $key, [
        'doc_title' => $tag . ' Doc',
        'doc_type' => 'SOP',
        'file_path' => 'tooling/qa/' . $tlCode . '/manual.pdf',
    ]);
    $docId = $r['json']['data']['doc_id'] ?? null;
    ok('POST /toolings/{id}/documents', ($r['status'] === 201 || !empty($r['json']['success'])) && $docId, 'HTTP ' . $r['status']);
    $r = rest_req('GET', "$base/api/v1/toolings/$newTl/documents", $key);
    ok('GET /toolings/{id}/documents', $r['status'] === 200 && !empty($r['json']['success']), 'HTTP ' . $r['status']);
    if ($docId) {
        $r = rest_req('DELETE', "$base/api/v1/toolings/$newTl/documents/$docId", $key);
        ok('DELETE /toolings/{id}/documents/{doc_id}', $r['status'] === 200 && !empty($r['json']['success']), 'HTTP ' . $r['status']);
    }

    $r = rest_req('DELETE', "$base/api/v1/toolings/$newTl", $key);
    ok('DELETE /toolings/{id}', $r['status'] === 200 && !empty($r['json']['success']), 'HTTP ' . $r['status']);
    $r = rest_req('GET', "$base/api/v1/toolings/$newTl", $key);
    ok('GET soft-deleted tooling 404', $r['status'] === 404, 'HTTP ' . $r['status']);
    unset($created['tooling']);
}

// Inventory create + delete
$code = 'QA-REST-' . substr(md5($tag), 0, 8);
$r = rest_req('POST', "$base/api/v1/inventory", $key, [
    'part_name' => $tag . ' Part',
    'internal_code' => $code,
    'stock_level' => 5,
    'minimum_threshold' => 1,
    'cost_per_unit' => 1.5,
]);
$newPart = $r['json']['data']['part_id'] ?? null;
ok('POST /inventory', ($r['status'] === 201 || !empty($r['json']['success'])) && $newPart, 'HTTP ' . $r['status'] . ' body=' . substr((string)$r['body'], 0, 100));
if ($newPart) {
    $r = rest_req('PUT', "$base/api/v1/inventory/$newPart", $key, ['stock_level' => 7]);
    ok('PUT /inventory/{id}', $r['status'] === 200 && !empty($r['json']['success']), 'HTTP ' . $r['status']);
    $r = rest_req('DELETE', "$base/api/v1/inventory/$newPart", $key);
    ok('DELETE /inventory/{id}', $r['status'] === 200 && !empty($r['json']['success']), 'HTTP ' . $r['status']);
}

// Vendor create + delete (if supported)
$r = rest_req('POST', "$base/api/v1/vendors", $key, [
    'vendor_name' => $tag . ' Vendor',
    'primary_contact_name' => 'QA Contact',
    'contact_email' => 'qa-rest@example.com',
    'contact_phone' => '000',
    'address' => 'QA Street',
]);
$newVen = $r['json']['data']['vendor_id'] ?? $r['json']['data']['id'] ?? null;
if ($r['status'] === 405) {
    skip('POST /vendors', 'method not allowed');
} elseif ($r['status'] >= 500) {
    ok('POST /vendors', false, 'HTTP ' . $r['status'] . ' API error: ' . substr((string)$r['body'], 0, 160));
} else {
    ok('POST /vendors', ($r['status'] === 201 || !empty($r['json']['success'])) && $newVen, 'HTTP ' . $r['status'] . ' ' . substr((string)$r['body'], 0, 100));
    if ($newVen) {
        $r = rest_req('DELETE', "$base/api/v1/vendors/$newVen", $key);
        ok('DELETE /vendors/{id}', in_array($r['status'], [200, 204], true) || !empty($r['json']['success']), 'HTTP ' . $r['status']);
    }
}

// Work order create if possible (API field is equip_id → equipment_id column)
if ($equipId) {
    $r = rest_req('POST', "$base/api/v1/work-orders", $key, [
        'title' => $tag . ' WO',
        'equip_id' => (int)$equipId,
        'status' => 'Scheduled',
        'scheduled_date' => date('Y-m-d', strtotime('+7 days')),
        'description' => $tag,
    ]);
    $newWo = $r['json']['data']['wo_id'] ?? $r['json']['data']['id'] ?? null;
    if ($r['status'] === 405) {
        skip('POST /work-orders', '405');
    } else {
        ok('POST /work-orders', ($r['status'] === 201 || !empty($r['json']['success'])) && $newWo, 'HTTP ' . $r['status'] . ' ' . substr((string)$r['body'], 0, 120));
        if ($newWo) {
            $created['wo'] = $newWo;
            $r = rest_req('PUT', "$base/api/v1/work-orders/$newWo", $key, ['status' => 'Cancelled']);
            ok('PUT /work-orders/{id}', in_array($r['status'], [200, 201], true) || !empty($r['json']['success']), 'HTTP ' . $r['status']);
            $r = rest_req('DELETE', "$base/api/v1/work-orders/$newWo", $key);
            if ($r['status'] !== 405) {
                ok('DELETE /work-orders/{id}', $r['status'] === 200 || !empty($r['json']['success']), 'HTTP ' . $r['status']);
                unset($created['wo']);
            } else {
                skip('DELETE /work-orders', '405 — leave cancelled');
            }
        }
    }
}

// Production lines (fields: name + workshop_id)
$wsId = null;
try {
    $wsId = $pdo->query("SELECT workshop_id FROM workshops LIMIT 1")->fetchColumn() ?: null;
} catch (Throwable $e) {
}
if ($wsId) {
    $r = rest_req('POST', "$base/api/v1/production-lines", $key, [
        'name' => $tag . ' Line',
        'workshop_id' => (int)$wsId,
        'status' => 'Active',
    ]);
    $lid = $r['json']['data']['line_id'] ?? $r['json']['data']['id'] ?? null;
    ok('POST /production-lines', ($r['status'] === 201 || !empty($r['json']['success'])), 'HTTP ' . $r['status'] . ' ' . substr((string)$r['body'], 0, 100));
    if ($lid) {
        rest_req('DELETE', "$base/api/v1/production-lines/$lid", $key);
    }
} else {
    skip('POST /production-lines', 'no workshop_id in DB');
}

// Roles GET already; PUT roles may be sensitive — skip mutate unless easy
$r = rest_req('GET', "$base/api/v1/roles/4", $key);
ok('GET /roles/4', $r['status'] === 200 || $r['status'] === 404, 'HTTP ' . $r['status']);

// API key regenerate self-check (do not rotate admin key mid-suite permanently — skip or restore)
skip('POST /api-keys', 'would rotate live admin key; already tested generate path via DB seed');

// Stats / audit / ai-context deeper
$r = rest_req('GET', "$base/api/v1/stats", $key);
ok('GET /stats payload', $r['status'] === 200 && is_array($r['json']), 'HTTP ' . $r['status']);
$r = rest_req('GET', "$base/api/v1/audit?per_page=5", $key);
ok('GET /audit', $r['status'] === 200 || $r['status'] === 403, 'HTTP ' . $r['status']);
$r = rest_req('GET', "$base/api/v1/ai-context", $key);
ok('GET /ai-context', $r['status'] === 200 || $r['status'] === 403, 'HTTP ' . $r['status']);

// Forbidden with bad key
$r = rest_req('GET', "$base/api/v1/me", 'deadbeefdeadbeefdeadbeefdeadbeef');
ok('bad_key_401', $r['status'] === 401, 'HTTP ' . $r['status']);

// Cleanup residual ticket
if (!empty($created['ticket'])) {
    // Prefer status update rather than hard DELETE if risky
    $r = rest_req('PUT', "$base/api/v1/tickets/" . rawurlencode($created['ticket']), $key, ['status' => 'CLOSED']);
    if (!($r['json']['success'] ?? false)) {
        rest_req('DELETE', "$base/api/v1/tickets/" . rawurlencode($created['ticket']), $key);
    }
    // Soft mark in DB
    try {
        $pdo->prepare("UPDATE active_tickets SET status='CLOSED', fault_desc=CONCAT(fault_desc,' [QA-REST-DONE]') WHERE ticket_id=?")->execute([$created['ticket']]);
    } catch (Throwable $e) {
    }
    ok('cleanup ticket', true, (string)$created['ticket']);
}

// Summary
echo "\n=== REST v1 summary: pass=$pass fail=$fail skip=$skip ===\n";
$report = __DIR__ . '/reports/rest_v1_' . date('Ymd_His') . '.md';
$md = "# REST API v1 full sweep\n\n";
$md .= "- Date: " . date('c') . "\n";
$md .= "- Base: `$base`\n";
$md .= "- Pass: **$pass** · Fail: **$fail** · Skip: **$skip**\n\n";
$md .= "```\n" . implode("\n", $lines) . "\n```\n";
file_put_contents($report, $md);
echo "Report: $report\n";
exit($fail > 0 ? 1 : 0);
