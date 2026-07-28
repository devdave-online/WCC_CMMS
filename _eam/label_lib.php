<?php
/**
 * Equipment label shared library — settings, payload, and ZPL builders.
 * Pure functions, no side effects. Used by:
 *   _eam/equipment_labels.php     (print/preview page + JSON API)
 *   _eam/setup_vault_equipment.php (configurator modal + preview defaults)
 *
 * Payload format (compact "thin QR", intranet-offline):
 *   WCC|<equip_id>|<asset_uuid>|<name≤40>|SN:<oem_serial>
 * Empty segments are omitted; '|' inside data is replaced with '/'.
 */

const WCC_LABEL_DEFAULTS = [
    'equip_label_symbology'      => 'qrcode',       // qrcode | datamatrix
    'tooling_label_symbology'    => 'code128',      // code128 | qrcode | datamatrix (tooling vault)
    'equip_label_fields'         => '{"uuid":true,"serial":true,"brand_model":false,"location":true,"category_crit":false}',
    'equip_label_method'         => 'browser_sheet', // browser_sheet | browser_single | network_zpl
    'equip_label_width_mm'       => '50.8',
    'equip_label_height_mm'      => '25.4',
    'equip_label_page_preset'    => 'a4',            // a4 | letter | custom
    'equip_label_page_width_mm'  => '210',
    'equip_label_page_height_mm' => '297',
    'equip_label_margin_mm'      => '10',
    'equip_label_gap_x_mm'       => '3',
    'equip_label_gap_y_mm'       => '3',
    'equip_label_printer_ip'     => '',
    'equip_label_printer_port'   => '9100',
    'equip_label_dpi'            => '203',           // 203 | 300
    'equip_label_darkness'       => '10',            // ^MD 0-30
    'equip_label_speed'          => '4',             // ^PR ips
];

/** Load settings with self-healing defaults (missing keys are seeded). */
function wcc_label_settings(PDO $pdo): array {
    $cfg = WCC_LABEL_DEFAULTS;
    try {
        $in = implode(',', array_fill(0, count($cfg), '?'));
        $st = $pdo->prepare("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ($in)");
        $st->execute(array_keys($cfg));
        $have = $st->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ($cfg as $k => $def) {
            if (array_key_exists($k, $have)) {
                $cfg[$k] = $have[$k];
            } else {
                $pdo->prepare("INSERT IGNORE INTO app_settings (category, setting_key, setting_value) VALUES ('EquipmentLabels', ?, ?)")
                    ->execute([$k, $def]);
            }
        }
    } catch (Exception $e) { /* table missing → run on defaults */ }
    return $cfg;
}

/**
 * Validate + normalize a settings map from the configurator.
 * Returns only known keys with safe values; throws InvalidArgumentException on bad input.
 */
function wcc_label_validate_settings(array $s): array {
    $out = [];
    $enums = [
        'equip_label_symbology'   => ['qrcode', 'datamatrix'],
        'tooling_label_symbology' => ['code128', 'qrcode', 'datamatrix'],
        'equip_label_method'      => ['browser_sheet', 'browser_single', 'network_zpl'],
        'equip_label_page_preset' => ['a4', 'letter', 'custom'],
        'equip_label_dpi'         => ['203', '300'],
    ];
    $ranges = [ // key => [min, max]
        'equip_label_width_mm'       => [10, 300],
        'equip_label_height_mm'      => [10, 300],
        'equip_label_page_width_mm'  => [50, 500],
        'equip_label_page_height_mm' => [50, 500],
        'equip_label_margin_mm'      => [0, 50],
        'equip_label_gap_x_mm'       => [0, 50],
        'equip_label_gap_y_mm'       => [0, 50],
        'equip_label_printer_port'   => [1, 65535],
        'equip_label_darkness'       => [0, 30],
        'equip_label_speed'          => [1, 14],
    ];
    foreach ($s as $k => $v) {
        if (!array_key_exists($k, WCC_LABEL_DEFAULTS)) continue; // ignore unknown keys
        if (isset($enums[$k])) {
            if (!in_array((string)$v, $enums[$k], true)) throw new InvalidArgumentException("Invalid value for $k");
            $out[$k] = (string)$v;
        } elseif (isset($ranges[$k])) {
            $n = (float)$v;
            [$min, $max] = $ranges[$k];
            if ($n < $min || $n > $max) throw new InvalidArgumentException("$k must be between $min and $max");
            $out[$k] = (string)$n;
        } elseif ($k === 'equip_label_fields') {
            $f = is_array($v) ? $v : json_decode((string)$v, true);
            if (!is_array($f)) throw new InvalidArgumentException('Invalid field toggles');
            $clean = [];
            foreach (['uuid', 'serial', 'brand_model', 'location', 'category_crit'] as $fk) {
                $clean[$fk] = !empty($f[$fk]);
            }
            $out[$k] = json_encode($clean);
        } elseif ($k === 'equip_label_printer_ip') {
            $ip = trim((string)$v);
            if ($ip !== '' && !filter_var($ip, FILTER_VALIDATE_IP) && !preg_match('/^[a-zA-Z0-9.\-]+$/', $ip)) {
                throw new InvalidArgumentException('Invalid printer IP / hostname');
            }
            $out[$k] = $ip;
        }
    }
    return $out;
}

/** Compact scannable payload — minimal but complete identity. */
function wcc_label_payload(array $row): string {
    $seg = function ($s) { return str_replace('|', '/', trim((string)$s)); };
    $parts = ['WCC', (string)(int)$row['equip_id']];
    if (!empty($row['asset_uuid'])) $parts[] = $seg($row['asset_uuid']);
    $name = $seg($row['equip_name'] ?? '');
    if ($name !== '') $parts[] = mb_substr($name, 0, 40);
    if (!empty($row['oem_serial'])) $parts[] = 'SN:' . $seg($row['oem_serial']);
    return implode('|', $parts);
}

/** Human-readable text lines per the field toggles. Name (bold) always first. */
function wcc_label_text_lines(array $row, array $cfg): array {
    $f = json_decode($cfg['equip_label_fields'] ?? '{}', true) ?: [];
    $lines = [['b', (string)($row['equip_name'] ?? '')]];
    if (!empty($f['uuid']) && !empty($row['asset_uuid'])) $lines[] = ['n', (string)$row['asset_uuid']];
    if (!empty($f['serial']) && !empty($row['oem_serial'])) $lines[] = ['n', 'SN: ' . $row['oem_serial']];
    if (!empty($f['brand_model'])) {
        $bm = trim(($row['oem_brand'] ?? '') . ' ' . ($row['oem_model'] ?? ''));
        if ($bm !== '') $lines[] = ['n', $bm];
    }
    if (!empty($f['location'])) {
        $loc = trim(($row['plant_name'] ?? '') . (empty($row['line_name']) ? '' : ' / ' . $row['line_name']));
        if ($loc !== '') $lines[] = ['n', $loc];
    }
    if (!empty($f['category_crit'])) {
        $cc = trim(($row['category'] ?? '') . (empty($row['criticality']) ? '' : ' · Class ' . $row['criticality']));
        if ($cc !== '') $lines[] = ['n', $cc];
    }
    return $lines;
}

/** One complete ZPL label (^XA…^XZ). Code left, text block right. */
function wcc_label_zpl(array $row, array $cfg): string {
    $dpi = (int)$cfg['equip_label_dpi'] ?: 203;
    $dots = function (float $mm) use ($dpi) { return (int)round($mm * $dpi / 25.4); };
    $w = $dots((float)$cfg['equip_label_width_mm']);
    $h = $dots((float)$cfg['equip_label_height_mm']);
    $pad = $dots(2.0);
    // ZPL control chars stripped from data
    $zseg = function ($s) { return str_replace(['^', '~'], ['/', '/'], (string)$s); };
    $payload = $zseg(wcc_label_payload($row));

    // Code square on the left; ~33 modules covers this payload size in both symbologies.
    $codeSize = min($h - 2 * $pad, (int)($w * 0.40));
    $mag = max(2, min(10, intdiv($codeSize, 33)));

    $zpl  = "^XA";
    $zpl .= "^PW{$w}^LL{$h}^LH0,0";
    $zpl .= "^MD" . (int)$cfg['equip_label_darkness'];
    $zpl .= "^PR" . (int)$cfg['equip_label_speed'];
    if (($cfg['equip_label_symbology'] ?? 'qrcode') === 'datamatrix') {
        $zpl .= "^FO{$pad},{$pad}^BXN,{$mag},200^FD{$payload}^FS";
    } else {
        $zpl .= "^FO{$pad},{$pad}^BQN,2,{$mag}^FDQA,{$payload}^FS";
    }
    $tx = $pad + $codeSize + $pad;
    $maxTextW = max(10, $w - $tx - $pad);
    $y = $pad;
    foreach (wcc_label_text_lines($row, $cfg) as $line) {
        [$style, $text] = $line;
        $fh = $style === 'b' ? $dots(3.5) : $dots(2.6);
        if ($y + $fh > $h - $pad) break; // clip: never print past the label edge
        $zpl .= "^FO{$tx},{$y}^A0N,{$fh},{$fh}^FB{$maxTextW},1,0,L^FD" . $zseg($text) . "^FS";
        $y += $fh + $dots(0.8);
    }
    $zpl .= "^XZ";
    return $zpl;
}

/** Sheet-grid geometry for the browser_sheet mode. */
function wcc_label_grid(array $cfg): array {
    $pw = (float)$cfg['equip_label_page_width_mm'];
    $ph = (float)$cfg['equip_label_page_height_mm'];
    $m  = (float)$cfg['equip_label_margin_mm'];
    $lw = (float)$cfg['equip_label_width_mm'];
    $lh = (float)$cfg['equip_label_height_mm'];
    $gx = (float)$cfg['equip_label_gap_x_mm'];
    $gy = (float)$cfg['equip_label_gap_y_mm'];
    $cols = max(1, (int)floor(($pw - 2 * $m + $gx) / ($lw + $gx)));
    $rows = max(1, (int)floor(($ph - 2 * $m + $gy) / ($lh + $gy)));
    return ['cols' => $cols, 'rows' => $rows, 'per_page' => $cols * $rows];
}
