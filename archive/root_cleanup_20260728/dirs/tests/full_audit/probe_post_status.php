<?php
require_once __DIR__ . '/lib/HttpClient.php';
$c = require __DIR__ . '/config.php';
$h = new WccAuditHttpClient($c['base_url'], 20);
$h->login($c['admin_user'], $c['admin_pass']);
$v = $h->get('/_eam/setup_vault_toolings.php');
$csrf = $h->extractCsrf($v['body']);
echo "csrf=" . ($csrf ? 'yes' : 'no') . "\n";

$ch = curl_init($c['base_url'] . '/_eam/setup_vault_toolings.php');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HEADER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_COOKIEFILE => $h->cookieFile(),
    CURLOPT_COOKIEJAR => $h->cookieFile(),
    CURLOPT_POSTFIELDS => http_build_query([
        'csrf' => $csrf,
        'tooling_name' => '[QA-AUDIT] statuscheck',
        'category' => 'Other',
        'status' => 'Available',
        'condition_rating' => 'Good',
        'is_active' => '1',
    ]),
]);
$raw = curl_exec($ch);
$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "POST status=$code\n";
echo substr((string)$raw, 0, 500) . "\n";
