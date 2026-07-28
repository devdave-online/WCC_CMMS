<?php
/**
 * Companion — signed-in user achievements (gamified proficiencies + manual skills).
 *
 * Read-only. Reuses inc/gamification.php ladder and inc/techident.php aliases.
 * Does not modify _mgmt pages. Category icons come from skill_automation_config.
 *
 * GET → {
 *   status, data: {
 *     ladder, proficiencies[], manual_skills[], summary
 *   }
 * }
 */
require_once __DIR__ . '/../../inc/session.php';
header('Content-Type: application/json');

require_once __DIR__ . '/../../inc/db.php';
require_once __DIR__ . '/../../inc/techident.php';
require_once __DIR__ . '/../../inc/gamification.php';

$pdo = get_wcc_db_connection();

// Companion may arrive with PHPSESSID (loginForm) OR Basic Auth only (/me path).
// Accept either so Profile achievements work after cold start without a web session.
if (!isset($_SESSION['user_id']) && isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
    $st = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $st->execute([$_SERVER['PHP_AUTH_USER']]);
    $u = $st->fetch(PDO::FETCH_ASSOC);
    if ($u && password_verify($_SERVER['PHP_AUTH_PW'], $u['password_hash'])) {
        $st_status = strtolower(trim((string)($u['status'] ?? 'active')));
        if ($st_status === '' || $st_status === 'active') {
            $_SESSION['user_id'] = (int)$u['user_id'];
            $_SESSION['username'] = $u['username'];
            $_SESSION['full_name'] = $u['full_name'] ?? '';
            $_SESSION['role_level'] = (int)$u['role_level'];
        }
    }
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$uid = (int)$_SESSION['user_id'];

try {
    $st = $pdo->prepare(
        "SELECT user_id, username, full_name, badge_number, role_level
         FROM users WHERE user_id = ?"
    );
    $st->execute([$uid]);
    $user = $st->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit;
    }

    $aliases = wcc_tech_aliases($user);
    $aliasSlots = wcc_tech_alias_placeholders($aliases);

    // Hours per equipment category (same join as users_list / my_profile)
    $hoursByCat = [];
    if ($aliases) {
        $sql = "
            SELECT e.category, SUM(TIMESTAMPDIFF(MINUTE, ta.action_start, ta.action_end))/60 AS total_hours
            FROM ticket_actions ta
            JOIN active_tickets at ON at.ticket_id = ta.ticket_id
            JOIN equipment e ON e.equip_id = at.equip_id
            WHERE ta.tech_name IN ($aliasSlots)
              AND ta.action_start IS NOT NULL
              AND ta.action_end IS NOT NULL
              AND e.category IS NOT NULL AND e.category != ''
            GROUP BY e.category
        ";
        $g = $pdo->prepare($sql);
        $g->execute($aliases);
        foreach ($g->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $hoursByCat[$row['category']] = (float)$row['total_hours'];
        }
    }

    // Skill configurator map (only mapped categories score)
    $cfgByCat = [];
    try {
        foreach ($pdo->query(
            "SELECT equipment_category, skill_name, icon FROM skill_automation_config ORDER BY equipment_category ASC"
        ) as $cfg) {
            $cfgByCat[$cfg['equipment_category']] = $cfg;
        }
    } catch (Throwable $e) {
        $cfgByCat = [];
    }

    $ladder = wcc_gamified_tiers();
    // Progress: fraction from current tier min → next tier min
    $proficiencies = [];
    foreach ($hoursByCat as $cat => $hours) {
        if ($hours <= 0 || !isset($cfgByCat[$cat])) {
            continue;
        }
        $cfg = $cfgByCat[$cat];
        $tier = wcc_gamified_level($hours);
        $next = wcc_gamified_next($hours);

        $curMin = (float)$tier['min'];
        if ($next) {
            $nextMin = (float)$next['tier']['min'];
            $span = max(0.001, $nextMin - $curMin);
            $progress = max(0.0, min(1.0, ($hours - $curMin) / $span));
            $nextPayload = [
                'tier' => $next['tier']['tier'],
                'tier_icon' => $next['tier']['icon'],
                'tier_color' => $next['tier']['color'],
                'min' => $next['tier']['min'],
                'remaining_hours' => $next['remaining'],
            ];
        } else {
            $progress = 1.0;
            $nextPayload = null;
        }

        $proficiencies[] = [
            'category' => $cat,
            'skill_name' => $cfg['skill_name'] ?: $cat,
            'category_icon' => $cfg['icon'] ?? '',
            'hours' => round($hours, 1),
            'tier' => $tier['tier'],
            'tier_icon' => $tier['icon'],
            'tier_color' => $tier['color'],
            'tier_blurb' => $tier['blurb'],
            'tier_min' => $tier['min'],
            'next' => $nextPayload,
            'progress_01' => round($progress, 3),
        ];
    }

    // Highest tier first, then hours
    $tierRank = [];
    foreach ($ladder as $i => $t) {
        $tierRank[$t['tier']] = 100 - $i;
    }
    usort($proficiencies, function ($a, $b) use ($tierRank) {
        $ra = $tierRank[$a['tier']] ?? 0;
        $rb = $tierRank[$b['tier']] ?? 0;
        if ($ra !== $rb) {
            return $rb <=> $ra;
        }
        return $b['hours'] <=> $a['hours'];
    });

    $manual = [];
    $ms = $pdo->prepare(
        "SELECT skill_name, expiry_date FROM user_skills WHERE user_id = ? ORDER BY skill_name ASC"
    );
    $ms->execute([$uid]);
    $certExpiring = 0;
    $certExpired = 0;
    foreach ($ms->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $exp = wcc_skill_expiry($row['expiry_date'] ?? null);
        if ($exp['state'] === 'expiring') {
            $certExpiring++;
        }
        if ($exp['state'] === 'expired') {
            $certExpired++;
        }
        $manual[] = [
            'name' => $row['skill_name'],
            'expiry_date' => $row['expiry_date'],
            'state' => $exp['state'],
            'label' => $exp['label'],
            'color' => $exp['color'],
            'icon' => $exp['icon'],
            'days' => $exp['days'],
        ];
    }

    $masterCount = 0;
    $expertCount = 0;
    foreach ($proficiencies as $p) {
        if ($p['tier'] === 'Master') {
            $masterCount++;
        }
        if ($p['tier'] === 'Expert') {
            $expertCount++;
        }
    }

    $ladderOut = array_map(static function ($t) {
        return [
            'min' => $t['min'],
            'tier' => $t['tier'],
            'icon' => $t['icon'],
            'color' => $t['color'],
            'blurb' => $t['blurb'],
        ];
    }, $ladder);

    // ── Lifetime career stats (all history, exclude demo seed) ──
    $lifeTicketsWorked = 0;
    $lifeWrenchMins = 0;
    if ($aliases) {
        $lifeAct = $pdo->prepare(
            "SELECT COUNT(DISTINCT ticket_id) AS tickets_worked,
                    COALESCE(SUM(
                        CASE WHEN action_end > action_start
                             THEN TIMESTAMPDIFF(MINUTE, action_start, action_end)
                             ELSE 0 END
                    ), 0) AS total_mins
             FROM ticket_actions
             WHERE tech_name IN ($aliasSlots)
               AND (fault_type IS NULL OR fault_type <> 'COMPANION_DEMO_SEED')"
        );
        $lifeAct->execute($aliases);
        $lifeRow = $lifeAct->fetch(PDO::FETCH_ASSOC) ?: [];
        $lifeTicketsWorked = (int)($lifeRow['tickets_worked'] ?? 0);
        $lifeWrenchMins = (int)($lifeRow['total_mins'] ?? 0);
    }

    $lifeClosed = 0;
    if ($aliases) {
        $lc = $pdo->prepare(
            "SELECT COUNT(*) FROM active_tickets
             WHERE closed_by IN ($aliasSlots) AND status = 'CLOSED'"
        );
        $lc->execute($aliases);
        $lifeClosed = (int)$lc->fetchColumn();
    }

    $lifeWoDone = 0;
    try {
        $lw = $pdo->prepare(
            "SELECT COUNT(*) FROM work_orders
             WHERE assigned_to = ? AND status = 'Completed'"
        );
        $lw->execute([$uid]);
        $lifeWoDone = (int)$lw->fetchColumn();
    } catch (Throwable $e) {
        $lifeWoDone = 0;
    }

    $h = intdiv($lifeWrenchMins, 60);
    $m = $lifeWrenchMins % 60;
    if ($lifeWrenchMins <= 0) {
        $lifeWrenchLabel = '0m';
    } elseif ($h > 0) {
        $lifeWrenchLabel = $m > 0 ? "{$h}h {$m}m" : "{$h}h";
    } else {
        $lifeWrenchLabel = "{$m}m";
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'ladder' => $ladderOut,
            'proficiencies' => $proficiencies,
            'manual_skills' => $manual,
            'summary' => [
                'proficiency_count' => count($proficiencies),
                'master_count' => $masterCount,
                'expert_count' => $expertCount,
                'cert_count' => count($manual),
                'certs_expiring' => $certExpiring,
                'certs_expired' => $certExpired,
            ],
            'lifetime' => [
                'tickets_worked' => $lifeTicketsWorked,
                'total_wrench_minutes' => $lifeWrenchMins,
                'total_wrench_label' => $lifeWrenchLabel,
                'tickets_closed' => $lifeClosed,
                'work_orders_completed' => $lifeWoDone,
            ],
        ],
    ]);
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
