<?php
/**
 * Code Quality Assurance — static gates for launch.
 */
if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

$root = realpath(__DIR__ . '/../..');
$ok = 0;
$fail = 0;
$lines = [];

function cqa(string $name, bool $pass, string $detail = ''): void
{
    global $ok, $fail, $lines;
    if ($pass) {
        $ok++;
        $msg = "  OK  $name" . ($detail ? " — $detail" : '');
    } else {
        $fail++;
        $msg = " FAIL $name" . ($detail ? " — $detail" : '');
    }
    $lines[] = $msg;
    echo $msg . "\n";
}

// 1. PHP lint critical paths
$lintTargets = [
    'rbac.php', 'nav.php', 'login.php', 'index.php', 'register.php', 'my_profile.php',
    'inc/i18n.php', 'inc/notifications.php', 'inc/db.php', 'inc/head.php', 'auth.php',
    'api/submit_ticket.php', 'api/submit_closeout.php', 'api/submit_takeover.php',
    'api/submit_hold.php', 'api/submit_instant_resolve.php',
    'api/get_tooling_bom.php', 'api/get_tooling_docs.php', 'api/upload_document.php',
    'api/v1/index.php', 'api/v1/bootstrap.php',
    'api/v1/resources/tickets.php', 'api/v1/resources/ticket_actions.php',
    'api/v1/resources/vendors.php', 'api/v1/resources/work_orders.php',
    '_eam/toolings.php', '_eam/setup_vault_toolings.php', '_eam/equipment.php',
    '_maint/active_tickets.php', '_maint/takeover.php', '_maint/closeout.php',
    '_rpt/history.php',
];
$php = PHP_BINARY;
foreach ($lintTargets as $rel) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($path)) {
        cqa("lint:$rel", false, 'missing file');
        continue;
    }
    $out = [];
    $code = 0;
    exec(escapeshellarg($php) . ' -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
    cqa("lint:$rel", $code === 0, $code === 0 ? 'syntax ok' : implode(' ', $out));
}

// 2. Required symbols / patterns
require $root . '/rbac.php';
cqa('rbac_view_toolings', isset(PERMISSION_LABELS['view_toolings']));
cqa('rbac_manage_toolings', isset(PERMISSION_LABELS['manage_toolings']));
cqa('rbac_backfill_fn', function_exists('wcc_backfill_tooling_perms'));

require $root . '/inc/i18n.php';
cqa('i18n_catalog_34', count(wcc_locale_catalog()) === 34, 'count=' . count(wcc_locale_catalog()));
$groups = array_unique(array_column(wcc_locale_catalog(), 'group'));
$badGroup = false;
foreach ($groups as $g) {
    if (stripos($g, 'high impact') !== false || stripos($g, 'regional') !== false) {
        $badGroup = true;
    }
}
cqa('i18n_equal_groups', !$badGroup, implode(' | ', $groups));

// 3. i18n pack parity
$en = json_decode(file_get_contents($root . '/lang/en.json'), true);
$enKeys = array_keys($en);
$packBad = 0;
foreach (glob($root . '/lang/*.json') as $f) {
    $b = basename($f, '.json');
    if (in_array($b, ['en', 'en.context', 'glossary', '_meta'], true) || str_starts_with($b, '_')) {
        continue;
    }
    $d = json_decode(file_get_contents($f), true) ?: [];
    if (count(array_diff($enKeys, array_keys($d))) > 0) {
        $packBad++;
    }
}
cqa('i18n_pack_parity', $packBad === 0, "en_keys=" . count($enKeys) . " bad_packs=$packBad");

// 4. Tooling gates on pages
cqa('toolings_require_view', str_contains(file_get_contents($root . '/_eam/toolings.php'), "require_perm('view_toolings')"));
cqa('vault_require_manage', str_contains(file_get_contents($root . '/_eam/setup_vault_toolings.php'), "require_perm('manage_toolings')"));
cqa('api_bom_toolings', str_contains(file_get_contents($root . '/api/get_tooling_bom.php'), "view_toolings"));
cqa('api_docs_toolings', str_contains(file_get_contents($root . '/api/get_tooling_docs.php'), "view_toolings"));

// 5. CSRF on critical APIs
foreach (['submit_ticket.php', 'submit_closeout.php', 'submit_takeover.php', 'submit_hold.php'] as $api) {
    $src = file_get_contents($root . '/api/' . $api);
    cqa("csrf:$api", str_contains($src, 'wcc_csrf_require_json'));
}

// 6. Health soft-delete
cqa('health_soft_delete', str_contains(file_get_contents($root . '/_maint/active_tickets.php'), 'deleted_at IS NULL'));

// 7. No force recovery in my.ini
$ini = 'C:/xampp/mysql/bin/my.ini';
if (is_file($ini)) {
    $iniSrc = file_get_contents($ini);
    cqa('no_innodb_force_recovery', !preg_match('/^\s*innodb_force_recovery\s*=\s*[1-9]/m', $iniSrc));
} else {
    cqa('no_innodb_force_recovery', true, 'my.ini not found — skip');
}

// 8. JS helpers present
$ui = file_get_contents($root . '/js/wcc-ui.js');
cqa('js_escapeHtml', str_contains($ui, 'function escapeHtml'));
cqa('js_match_count', str_contains($ui, 'wccUpdateSearchMatchCount'));
cqa('js_i18n_t', is_file($root . '/js/wcc-i18n.js') && str_contains(file_get_contents($root . '/js/wcc-i18n.js'), 'function t('));

// 9. Notifications helpers
$n = file_get_contents($root . '/inc/notifications.php');
cqa('notify_perms_union', str_contains($n, 'function wcc_notify_perms'));

// 10. REST ticket/vendor fixes
cqa('rest_ticket_actions_schema', str_contains(file_get_contents($root . '/api/v1/resources/ticket_actions.php'), 'action_taken'));
cqa('rest_vendors_address', str_contains(file_get_contents($root . '/api/v1/resources/vendors.php'), 'vendor_address'));

// 11. L0 poison — raw json_encode in double-quoted onclick (breaks HTML).
// SAFE: htmlspecialchars(json_encode(...), ENT_QUOTES) or data-* attributes.
$excludeDirNames = ['backups', 'archive', '_dev_artifacts', 'tests', 'vendor', 'node_modules', 'lang'];
$poisonHits = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($rii as $file) {
    /** @var SplFileInfo $file */
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
        continue;
    }
    $full = $file->getPathname();
    $rel = str_replace('\\', '/', substr($full, strlen($root) + 1));
    $top = explode('/', $rel)[0] ?? '';
    if (in_array($top, $excludeDirNames, true)) {
        continue;
    }
    $src = @file_get_contents($full);
    if ($src === false || $src === '') {
        continue;
    }
    // Find onclick="..." chunks that contain <?= json_encode without htmlspecialchars(
    if (preg_match_all('/onclick\s*=\s*"((?:\\\\.|[^"\\\\])*)"/s', $src, $attrs)) {
        foreach ($attrs[1] as $attrBody) {
            if (preg_match('/<\?=\s*json_encode\s*\(/', $attrBody)
                && !preg_match('/<\?=\s*htmlspecialchars\s*\(\s*json_encode\s*\(/', $attrBody)) {
                $poisonHits[] = $rel . ': raw json_encode in onclick="..."';
                break;
            }
        }
    }
}
cqa('poison_onclick_json_encode', $poisonHits === [], $poisonHits === [] ? 'clean' : implode('; ', array_slice($poisonHits, 0, 12)));

// Confirm modal must exist and define openWccConfirm
$confirm = $root . '/_confirm_modal.php';
cqa('confirm_modal_exists', is_file($confirm));
cqa('confirm_modal_defines_openWccConfirm', is_file($confirm) && str_contains(file_get_contents($confirm), 'function openWccConfirm'));
$nav = file_get_contents($root . '/nav.php');
cqa('nav_includes_confirm_modal', str_contains($nav, '_confirm_modal.php'));

// Profile password form must be real submit path (not dead type=button only)
$prof = file_get_contents($root . '/my_profile.php');
cqa('profile_password_form_post', str_contains($prof, "action'] === 'change_password'") || str_contains($prof, "action'] === \"change_password\"") || str_contains($prof, "value=\"change_password\""));
cqa('profile_password_submit_type', str_contains($prof, 'changePasswordForm') && (str_contains($prof, 'type="submit"') || str_contains($prof, "type='submit'")));
cqa('profile_no_onclick_json_encode_pw', !preg_match('/changePasswordForm[\s\S]{0,800}onclick\s*=\s*["\'][^"\']*json_encode/i', $prof));

// About contact: Gmail compose + visible email + LinkedIn
$about = file_get_contents($root . '/_about_modal.php');
cqa('about_gmail_compose', str_contains($about, 'mail.google.com/mail'));
cqa('about_email_visible', str_contains($about, 'about-email-addr') || str_contains($about, 'wcc_contact_bug_email'));
cqa('about_linkedin', str_contains($about, 'linkedin.com'));
cqa('about_privacy_notice', str_contains($about, 'privacy-notice') || str_contains($about, 'about.privacy_'));
cqa('about_beta_notice', str_contains($about, 'beta-notice') || str_contains($about, 'about.beta_'));

// Version stamps aligned
$vj = json_decode((string)@file_get_contents($root . '/version.json'), true) ?: [];
$verPhp = file_get_contents($root . '/inc/version.php');
cqa('version_json_ob', ($vj['version'] ?? '') === 'OB1.0.0');
cqa('version_ui_ob', str_contains($verPhp, "OB1.0.0"));
cqa('license_licensor_full_name', str_contains((string)@file_get_contents($root . '/LICENSE.txt'), 'David Zoltan Csiki'));

echo "\n=== CQA: pass=$ok fail=$fail ===\n";
$report = __DIR__ . '/reports/cqa_' . date('Ymd_His') . '.md';
file_put_contents($report, "# CQA static\n\npass=$ok fail=$fail\n\n```\n" . implode("\n", $lines) . "\n```\n");
echo "Report: $report\n";
exit($fail > 0 ? 1 : 0);
