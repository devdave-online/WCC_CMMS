<?php
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../_eam/label_lib.php';
$p = get_wcc_db_connection();
$c = wcc_label_settings($p);
echo 'tooling_symb=' . ($c['tooling_label_symbology'] ?? 'missing') . PHP_EOL;
$v = wcc_label_validate_settings(['tooling_label_symbology' => 'qrcode']);
echo 'validated=';
print_r($v);
// upsert like the API
$up = $p->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_key = ?");
foreach ($v as $k => $val) {
    $up->execute([$val, $k]);
    echo "updated $k rows=" . $up->rowCount() . PHP_EOL;
}
$c2 = wcc_label_settings($p);
echo 'after=' . $c2['tooling_label_symbology'] . PHP_EOL;
