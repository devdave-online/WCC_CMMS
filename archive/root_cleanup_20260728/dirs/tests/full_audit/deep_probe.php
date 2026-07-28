<?php
/**
 * Deep functional probes — real POSTs that the smoke suite only half covers.
 * CLI only. Creates QA-tagged rows and cleans them up.
 */
if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

require_once __DIR__ . '/lib/HttpClient.php';
require_once __DIR__ . '/lib/DbProbe.php';

$config = require __DIR__ . '/config.php';
$http = new WccAuditHttpClient($config['base_url'], 25);
$db = new WccAuditDbProbe();
$fail = 0;
$pass = 0;
$tag = '[QA-AUDIT]';

function p(string $label, bool $ok, string $detail = ''): void
{
    global $fail, $pass;
    if ($ok) {
        $pass++;
        echo "  OK  $label" . ($detail !== '' ? " — $detail" : '') . "\n";
    } else {
        $fail++;
        echo " FAIL $label" . ($detail !== '' ? " — $detail" : '') . "\n";
    }
}

echo "=== Deep probe ===\n";
$okLogin = $http->login($config['admin_user'], $config['admin_pass'])
    || $http->login('admin', 'password')
    || $http->login('admin', 'Demo2026!');
p('login', $okLogin);
if (!$okLogin) {
    exit(1);
}

$vault = $http->get('/_eam/setup_vault_toolings.php');
$csrf = $http->extractCsrf($vault['body']);
p('vault_csrf', (bool)$csrf, $csrf ? 'got token' : 'missing');

// 1) Label symbology save (tooling)
$r = $http->postJson('/_eam/equipment_labels.php', [
    'action' => 'save_label_settings',
    'settings' => ['tooling_label_symbology' => 'datamatrix'],
], $csrf);
$j = json_decode($r['body'], true);
p('tooling_symbology_save', ($j['status'] ?? '') === 'success', substr($r['body'], 0, 120));

// verify DB
$sym = $db->one("SELECT setting_value FROM app_settings WHERE setting_key='tooling_label_symbology'");
p('tooling_symbology_db', $sym === 'datamatrix', 'value=' . var_export($sym, true));

// restore code128
$http->postJson('/_eam/equipment_labels.php', [
    'action' => 'save_label_settings',
    'settings' => ['tooling_label_symbology' => 'code128'],
], $csrf);

// 2) Tooling create + BOM link via form
$tidBefore = (int)$db->one('SELECT COUNT(*) FROM toolings');
$create = $http->postForm('/_eam/setup_vault_toolings.php', [
    'csrf' => $csrf,
    'tooling_name' => $tag . ' deep probe ' . date('His'),
    'category' => 'Gauge',
    'status' => 'Available',
    'condition_rating' => 'Good',
    'is_active' => '1',
    'tooling_code' => '',
    'barcode' => '',
], true);
$newId = $db->one(
    "SELECT tooling_id FROM toolings WHERE tooling_name LIKE ? ORDER BY tooling_id DESC LIMIT 1",
    [$tag . '%']
);
// POST returns 302 redirect; curl-follow may land on 200/403 depending on session race — row is authoritative
p('create_tooling', (bool)$newId, 'id=' . $newId . ' http=' . $create['status'] . ' (302 expected on save)');

// 3) Add BOM part to new tooling (or existing tooling 1)
$partId = $db->one('SELECT part_id FROM inventory_parts ORDER BY part_id ASC LIMIT 1');
$targetTid = $newId ?: $db->firstToolingId();
if ($partId && $targetTid) {
    $vault = $http->get('/_eam/setup_vault_toolings.php');
    $csrf = $http->extractCsrf($vault['body']);
    $bomPost = $http->postForm('/_eam/setup_vault_toolings.php', [
        'csrf' => $csrf,
        'action' => 'add_bom',
        'bom_tooling_id' => (string)$targetTid,
        'part_id' => (string)$partId,
        'quantity' => '2',
    ], true);
    $bomCount = $db->one(
        'SELECT COUNT(*) FROM tooling_bom WHERE tooling_id=? AND part_id=?',
        [(int)$targetTid, (int)$partId]
    );
    p('add_tooling_bom', (int)$bomCount > 0, 'count=' . $bomCount . ' http=' . $bomPost['status']);

    $api = $http->get('/api/get_tooling_bom.php?tooling_id=' . (int)$targetTid);
    $aj = json_decode($api['body'], true);
    p(
        'get_tooling_bom_api',
        is_array($aj) && ($aj['status'] ?? '') === 'success' && count($aj['data'] ?? []) > 0,
        substr($api['body'], 0, 180)
    );
} else {
    p('add_tooling_bom', false, 'no part/tooling');
}

// 4) Upload document to tooling — same session as HttpClient
$vault = $http->get('/_eam/setup_vault_toolings.php');
$csrf2 = $http->extractCsrf($vault['body']) ?: $csrf;
p('upload_csrf', (bool)$csrf2);

$tmp = sys_get_temp_dir() . '/qa_audit_doc.txt';
file_put_contents($tmp, "QA audit document " . date('c'));
$tidUp = (int)($targetTid ?: 1);
$up = $http->postMultipart('/api/upload_document.php', [
    'entity' => 'tooling',
    'tooling_id' => (string)$tidUp,
    'doc_title' => $tag . ' Manual',
    'doc_type' => 'Manual',
    'doc_file' => new CURLFile($tmp, 'text/plain', 'qa_audit.txt'),
], $csrf2);
$upJ = json_decode($up['body'], true);
p('upload_tooling_doc', ($upJ['status'] ?? '') === 'success', 'HTTP ' . $up['status'] . ' ' . substr($up['body'], 0, 150));

$docs = $http->get('/api/get_tooling_docs.php?tooling_id=' . $tidUp);
$dj = json_decode($docs['body'], true);
$docCount = is_array($dj['data'] ?? null) ? count($dj['data']) : 0;
p('list_tooling_docs', ($dj['status'] ?? '') === 'success' && $docCount > 0, 'count=' . $docCount . ' body=' . substr($docs['body'], 0, 120));

// 5) Equipment upload still works (backward compat) — needs manage_settings
$eid = $db->firstEquipId();
if ($eid && $csrf2) {
    $tmp2 = sys_get_temp_dir() . '/qa_eq_doc.txt';
    file_put_contents($tmp2, 'eq doc');
    $eu = $http->postMultipart('/api/upload_document.php', [
        'equip_id' => (string)$eid,
        'doc_title' => $tag . ' Equip Doc',
        'doc_type' => 'Other',
        'doc_file' => new CURLFile($tmp2, 'text/plain', 'eq.txt'),
    ], $csrf2);
    $ej = json_decode($eu['body'], true);
    p('upload_equipment_doc_compat', ($ej['status'] ?? '') === 'success', 'HTTP ' . $eu['status'] . ' ' . substr($eu['body'], 0, 150));
}

// 6) Ledger still shows View Linked Parts
$led = $http->get('/_eam/toolings.php');
p('ledger_view_linked_parts', str_contains($led['body'], 'View Linked Parts') && str_contains($led['body'], 'get_tooling_bom.php'));
p('ledger_view_docs', str_contains($led['body'], 'View docs') || str_contains($led['body'], 'openToolingDocsModal'));

// Cleanup QA data
$pdo = $db->pdo();
if ($pdo) {
    try {
        $pdo->exec("DELETE FROM tooling_documents WHERE doc_title LIKE '[QA-AUDIT]%'");
        $pdo->exec("DELETE FROM equipment_documents WHERE doc_title LIKE '[QA-AUDIT]%'");
        // remove bom links we may have added only if part was only QA? leave seed BOM
        $pdo->prepare("UPDATE toolings SET deleted_at=NOW(), status='Retired' WHERE tooling_name LIKE ?")
            ->execute([$tag . '%']);
        // close QA tickets if any open with tag in fault
        try {
            $pdo->prepare("UPDATE active_tickets SET status='CLOSED' WHERE fault_desc LIKE ?")
                ->execute(['%' . $tag . '%']);
        } catch (Throwable $e) {
        }
        p('cleanup', true);
    } catch (Throwable $e) {
        p('cleanup', false, $e->getMessage());
    }
}

echo "\n=== Deep probe done: $pass ok, $fail fail ===\n";
exit($fail > 0 ? 1 : 0);
