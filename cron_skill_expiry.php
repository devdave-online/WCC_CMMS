<?php
/**
 * WCC CMMS — certification expiry warning job.
 *
 * Run once a day (Task Scheduler / cron):
 *     php cron_skill_expiry.php
 *     php cron_skill_expiry.php --dry-run     # show what would be sent, send nothing
 *
 * Safe to run repeatedly: each certification is warned at most once per horizon
 * (see inc/skill_expiry.php), so a double-run or a catch-up after downtime sends
 * nothing extra.
 *
 * CLI only. Warnings are not sensitive, but this is a write path that anyone could
 * otherwise hammer to flood the notification centre.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Forbidden: cron_skill_expiry.php runs from the command line only.\n");
}

require_once __DIR__ . '/inc/skill_expiry.php';

$dry = in_array('--dry-run', $argv ?? [], true);

echo "\n=== WCC certification expiry check" . ($dry ? ' (DRY RUN)' : '') . " ===\n";
echo 'Horizons: ' . implode(', ', WCC_SKILL_EXPIRY_TIERS) . " days, plus on expiry\n\n";

$r = wcc_skill_expiry_run($dry);

if ($r['details']) {
    printf("  %-22s %-32s %5s  %s\n", 'HOLDER', 'CERTIFICATION', 'DAYS', 'BUCKET');
    foreach ($r['details'] as $d) echo '  ' . $d . "\n";
} else {
    echo "  Nothing due.\n";
}

printf("\n  scanned %d · %s %d · already-warned/not-due %d\n\n",
    $r['scanned'], $dry ? 'would send' : 'sent', $dry ? count($r['details']) : $r['sent'], $r['skipped']);
