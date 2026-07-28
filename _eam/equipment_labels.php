<?php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('manage_equipment');

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/label_lib.php';
$pdo = get_wcc_db_connection();

$fetch_rows = function (array $ids) use ($pdo): array {
    $in = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare("SELECT e.*, w.name AS plant_name, l.name AS line_name
                           FROM equipment e
                           LEFT JOIN workshops w ON e.workshop_id = w.workshop_id
                           LEFT JOIN production_lines l ON e.line_id = l.line_id
                          WHERE e.equip_id IN ($in)
                          ORDER BY e.equip_id ASC");
    $st->execute($ids);
    return $st->fetchAll(PDO::FETCH_ASSOC);
};

// ------------------------------------------------------------------
// JSON API: save settings / direct Zebra printing (raw ZPL over TCP)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
    header('Content-Type: application/json');
    $body = json_decode(file_get_contents('php://input'), true);
    $action = $body['action'] ?? '';
    if (!in_array($action, ['print_labels', 'test_print', 'save_label_settings'], true)) {
        echo json_encode(['status' => 'error', 'message' => __('equip.unknown_action')]);
        exit;
    }
    if (!wcc_csrf_valid($body['csrf'] ?? null)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => __('common.security_check')]);
        exit;
    }
    try {
        if ($action === 'save_label_settings') {
            $clean = wcc_label_validate_settings((array)($body['settings'] ?? []));
            if (!$clean) {
                echo json_encode(['status' => 'error', 'message' => __('equip.no_valid_settings')]);
                exit;
            }
            wcc_label_settings($pdo); // self-heal first so every key exists
            $up = $pdo->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_key = ?");
            foreach ($clean as $k => $v) {
                $up->execute([$v, $k]);
            }
            echo json_encode(['status' => 'success', 'message' => __('equip.label_settings_saved')]);
            exit;
        }

        // --- print_labels / test_print → Zebra network method only ---
        $cfg = wcc_label_settings($pdo);
        if ($cfg['equip_label_method'] !== 'network_zpl') {
            echo json_encode(['status' => 'error', 'message' => __('equip.use_browser_print')]);
            exit;
        }
        if ($action === 'test_print') {
            $rows = [[
                'equip_id' => 0, 'asset_uuid' => 'TEST-0000', 'equip_name' => 'WCC Test Label',
                'oem_serial' => 'SN-TEST', 'oem_brand' => 'WCC', 'oem_model' => 'Configurator',
                'plant_name' => 'Test Plant', 'line_name' => 'Line 0', 'category' => 'Test', 'criticality' => 'B',
            ]];
        } else {
            $ids = array_values(array_filter(array_unique(array_map('intval', (array)($body['ids'] ?? []))), fn($v) => $v > 0));
            if (!$ids) {
                echo json_encode(['status' => 'error', 'message' => __('equip.no_equipment_selected')]);
                exit;
            }
            $rows = $fetch_rows($ids);
            if (!$rows) {
                echo json_encode(['status' => 'error', 'message' => __('equip.selected_not_found')]);
                exit;
            }
        }
        $ip = trim($cfg['equip_label_printer_ip']);
        $port = (int)$cfg['equip_label_printer_port'] ?: 9100;
        if ($ip === '') {
            echo json_encode(['status' => 'error', 'message' => __('equip.no_printer_ip')]);
            exit;
        }
        $zpl = '';
        foreach ($rows as $r) {
            $zpl .= wcc_label_zpl($r, $cfg);
        }
        $sock = @fsockopen($ip, $port, $errno, $errstr, 3);
        if (!$sock) {
            echo json_encode(['status' => 'error', 'message' => __('equip.printer_unreachable', ['ip' => $ip, 'port' => (string)$port, 'err' => $errstr])]);
            exit;
        }
        fwrite($sock, $zpl);
        fclose($sock);
        echo json_encode(['status' => 'success', 'message' => __('equip.sent_labels', ['n' => (string)count($rows), 'ip' => $ip]), 'printed' => count($rows)]);
    } catch (InvalidArgumentException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => __('equip.label_error', ['msg' => $e->getMessage()])]);
    }
    exit;
}

// ------------------------------------------------------------------
// GET ?ids=1,2,3 → standalone print/preview page (any printer via the
// browser's print dialog — that dialog is the device selector).
// ------------------------------------------------------------------
$cfg = wcc_label_settings($pdo);
$ids = array_values(array_filter(array_unique(array_map('intval', explode(',', (string)($_GET['ids'] ?? '')))), fn($v) => $v > 0));
if (!$ids) {
    http_response_code(400);
    die(__('equip.no_ids'));
}
$rows = $fetch_rows($ids);
if (!$rows) {
    http_response_code(404);
    die(__('equip.selected_not_found'));
}

$single = ($cfg['equip_label_method'] === 'browser_single');
$lw = (float)$cfg['equip_label_width_mm'];
$lh = (float)$cfg['equip_label_height_mm'];
$symbology = $cfg['equip_label_symbology'] === 'datamatrix' ? 'datamatrix' : 'qrcode';
$grid = wcc_label_grid($cfg);
$pages = $single ? array_chunk($rows, 1) : array_chunk($rows, $grid['per_page']);
// Code square in mm (mirrors the ZPL proportions)
$code_mm = min($lh - 4, $lw * 0.40);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars(__('equip.labels_print'), ENT_QUOTES, 'UTF-8') ?></title>
<script src="/js/bwip-js-min.js"></script>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #555; font-family: 'Segoe UI', Arial, sans-serif; }
    .toolbar {
        position: sticky; top: 0; z-index: 10; background: #1e293b; color: #f8fafc;
        padding: 12px 20px; display: flex; align-items: center; gap: 16px;
    }
    .toolbar button {
        background: #0284c7; color: #fff; border: none; border-radius: 8px;
        padding: 10px 22px; font-size: 1em; font-weight: 600; cursor: pointer;
    }
    .toolbar button:hover { background: #0369a1; }
    .toolbar .hint { font-size: 0.85em; color: #94a3b8; }
    .page {
        background: #fff; margin: 14px auto; box-shadow: 0 4px 14px rgba(0,0,0,0.4);
        width: <?= $single ? $lw : (float)$cfg['equip_label_page_width_mm'] ?>mm;
        <?php if ($single): ?>
        height: <?= $lh ?>mm;
        <?php else: ?>
        min-height: <?= (float)$cfg['equip_label_page_height_mm'] ?>mm;
        padding: <?= (float)$cfg['equip_label_margin_mm'] ?>mm;
        display: flex; flex-wrap: wrap; align-content: flex-start;
        column-gap: <?= (float)$cfg['equip_label_gap_x_mm'] ?>mm;
        row-gap: <?= (float)$cfg['equip_label_gap_y_mm'] ?>mm;
        <?php endif; ?>
    }
    .label {
        width: <?= $lw ?>mm; height: <?= $lh ?>mm; overflow: hidden;
        display: flex; align-items: center; gap: 2mm; padding: 2mm;
        background: #fff; color: #000;
        <?php if (!$single): ?>outline: 1px dashed #ccc;<?php endif; ?>
    }
    .label .code canvas { width: <?= $code_mm ?>mm; height: <?= $code_mm ?>mm; display: block; }
    .label .txt { flex: 1; min-width: 0; overflow: hidden; line-height: 1.25; }
    .label .txt .b { font-weight: 700; font-size: 9pt; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .label .txt .n { font-size: 7pt; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    @media print {
        body { background: #fff; }
        .toolbar { display: none; }
        .page { margin: 0; box-shadow: none; page-break-after: always; }
        .label { outline: none; }
    }
    @page {
        <?php if ($single): ?>
        size: <?= $lw ?>mm <?= $lh ?>mm; margin: 0;
        <?php else: ?>
        size: <?= (float)$cfg['equip_label_page_width_mm'] ?>mm <?= (float)$cfg['equip_label_page_height_mm'] ?>mm; margin: 0;
        <?php endif; ?>
    }
</style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">🖨️ <?= count($rows) === 1 ? __e('equip.print_n_labels', ['n' => (string)count($rows)]) : __e('equip.print_n_labels_plural', ['n' => (string)count($rows)]) ?></button>
        <span class="hint">
            <?= __e('equip.print_hint') ?>
            <?= $single
                ? __e('equip.one_label_page', ['w' => (string)$lw, 'h' => (string)$lh])
                : __e('equip.sheet_layout', ['cols' => (string)$grid['cols'], 'rows' => (string)$grid['rows'], 'per_page' => (string)$grid['per_page']]) ?>
            <?= __e('equip.symbology') ?> <?= $symbology === 'datamatrix' ? __e('equip.datamatrix') : __e('equip.qr_code') ?>.
        </span>
    </div>

    <?php foreach ($pages as $page_rows): ?>
    <div class="page">
        <?php foreach ($page_rows as $r): ?>
        <div class="label">
            <div class="code"><canvas class="labelCode" data-payload="<?= htmlspecialchars(wcc_label_payload($r), ENT_QUOTES, 'UTF-8') ?>"></canvas></div>
            <div class="txt">
                <?php foreach (wcc_label_text_lines($r, $cfg) as $line): ?>
                <div class="<?= $line[0] === 'b' ? 'b' : 'n' ?>"><?= htmlspecialchars($line[1]) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <script>
        // Render every code locally (vendored bwip-js — intranet-safe, no network).
        document.querySelectorAll('canvas.labelCode').forEach(cv => {
            try {
                bwipjs.toCanvas(cv, {
                    bcid: '<?= $symbology ?>',
                    text: cv.dataset.payload,
                    scale: 3,
                    includetext: false
                });
            } catch (e) {
                cv.closest('.label').insertAdjacentHTML('beforeend', '<span style="color:red;font-size:6pt;"><?= htmlspecialchars(__('equip.code_error'), ENT_QUOTES, 'UTF-8') ?></span>');
            }
        });
    </script>
</body>
</html>
