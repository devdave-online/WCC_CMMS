<?php
/**
 * WCC CMMS — certification expiry notifications.
 *
 * A lapsed LOTO authorisation or an out-of-date working-at-height ticket is a safety
 * problem, not a cosmetic one, so the badges added to the Users Directory and My
 * Profile are not enough on their own: somebody has to be TOLD, in advance, while
 * there is still time to book the renewal.
 *
 * Warns the holder at five decreasing horizons — 30, 20, 10, 5 and 3 days — then once
 * more on the day it lapses, at which point the holder's managers are told too.
 *
 * ── Why this cannot double-notify ────────────────────────────────────────────────
 * Each certification is placed in exactly ONE bucket per run: the tightest threshold
 * its remaining days fall inside. A skill 4 days from expiry is in the "5" bucket, not
 * in 30+20+10+5 at once — otherwise adding a nearly-expired certification would fire
 * five notifications immediately.
 *
 * Already-sent buckets are recorded in the notification's own `type` column as
 * "skill_exp:<skill_id>:<bucket>", so re-running the job is harmless and no extra
 * table is needed. Running it twice a day, or catching up after the server was off
 * for a week, produces the same result as running it once daily.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/notifications.php';
require_once __DIR__ . '/gamification.php';

/** Warning horizons in days, widest first. */
const WCC_SKILL_EXPIRY_TIERS = [30, 20, 10, 5, 3];

/**
 * Which bucket a certification currently falls into, or null when it is too far out
 * to warn about yet.
 *
 * @return string|null one of "30","20","10","5","3","expired"
 */
function wcc_skill_expiry_bucket(int $daysLeft): ?string
{
    if ($daysLeft < 0) return 'expired';

    $bucket = null;
    foreach (WCC_SKILL_EXPIRY_TIERS as $tier) {
        if ($daysLeft <= $tier) $bucket = (string)$tier;   // keeps narrowing → tightest wins
    }
    return $bucket;                                        // null when further out than 30 days
}

/**
 * Scan certifications and send any warning that is due.
 *
 * @param bool $dryRun report what would be sent without writing anything
 * @return array{scanned:int,sent:int,skipped:int,details:array<int,string>}
 */
function wcc_skill_expiry_run(bool $dryRun = false): array
{
    $out = ['scanned' => 0, 'sent' => 0, 'skipped' => 0, 'details' => []];

    try {
        $pdo = get_wcc_db_connection();

        $rows = $pdo->query("
            SELECT us.id, us.user_id, us.skill_name, us.expiry_date,
                   DATEDIFF(us.expiry_date, CURDATE()) AS days_left,
                   u.full_name, u.username
              FROM user_skills us
              JOIN users u ON u.user_id = us.user_id
             WHERE us.expiry_date IS NOT NULL
               AND u.status = 'active'
             ORDER BY us.expiry_date ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $seen = $pdo->prepare("SELECT 1 FROM notifications WHERE user_id = ? AND type = ? LIMIT 1");

        foreach ($rows as $r) {
            $out['scanned']++;

            $days   = (int)$r['days_left'];
            $bucket = wcc_skill_expiry_bucket($days);
            if ($bucket === null) { $out['skipped']++; continue; }   // still far out

            // varchar(50) — "skill_exp:12345:expired" fits comfortably.
            $key = 'skill_exp:' . (int)$r['id'] . ':' . $bucket;

            $seen->execute([(int)$r['user_id'], $key]);
            if ($seen->fetchColumn()) { $out['skipped']++; continue; } // already warned

            $who   = $r['full_name'] ?: $r['username'];
            $skill = $r['skill_name'];
            $when  = date('j M Y', strtotime($r['expiry_date']));

            if ($bucket === 'expired') {
                $msg      = "Certification EXPIRED: {$skill} lapsed on {$when}. Renew before further work requiring it.";
                $severity = 'danger';
            } else {
                $msg      = "Certification expiring: {$skill} expires in {$days} day" . ($days === 1 ? '' : 's')
                          . " ({$when}). Book the renewal.";
                $severity = ($days <= 5) ? 'danger' : 'warning';
            }

            $out['details'][] = sprintf('%-22s %-32s %4dd  → %s', $who, $skill, $days, $bucket);

            if ($dryRun) continue;

            wcc_notify((int)$r['user_id'], $key, $msg, '/my_profile.php', $severity);
            $out['sent']++;

            // Once it has actually lapsed the holder alone is not enough — whoever
            // manages people needs to know somebody is working without a valid ticket.
            if ($bucket === 'expired') {
                wcc_notify_perm(
                    'manage_users',
                    'skill_exp_mgr:' . (int)$r['id'],
                    "{$who}'s certification \"{$skill}\" expired on {$when}.",
                    '/_mgmt/users.php',
                    'danger',
                    (int)$r['user_id']
                );
            }
        }
    } catch (Throwable $e) {
        error_log('[WCC skill expiry] ' . $e->getMessage());
    }

    return $out;
}
