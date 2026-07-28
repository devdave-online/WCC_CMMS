<?php
/**
 * Donation / support prompt preferences (per-user).
 * JSON POST, session + CSRF gated.
 *
 * Actions:
 *   snooze   — hide About coffee accordion for N days (default 30)
 *   dismiss  — hide forever
 *   status   — return current visibility (optional helper)
 */
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/i18n.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => __('common.unauthorized')]);
    exit;
}

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/csrf.php';

$pdo  = get_wcc_db_connection();
$uid  = (int)$_SESSION['user_id'];
$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    $body = [];
}

$action = (string)($body['action'] ?? '');
$csrf   = $body['csrf'] ?? null;

if ($action !== 'status' && !wcc_csrf_valid($csrf)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => __('common.security_check')]);
    exit;
}

/** @return array{visible:bool,status:string,snooze_until:?string} */
function wcc_donation_prompt_state(PDO $pdo, int $uid): array
{
    try {
        $st = $pdo->prepare('SELECT status, snooze_until FROM donation_prompt_prefs WHERE user_id = ? LIMIT 1');
        $st->execute([$uid]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        // Table missing / not migrated yet → always show
        return ['visible' => true, 'status' => 'shown', 'snooze_until' => null];
    }
    if (!$row) {
        return ['visible' => true, 'status' => 'shown', 'snooze_until' => null];
    }
    $status = (string)$row['status'];
    $until  = $row['snooze_until'] ?? null;
    if ($status === 'dismissed') {
        return ['visible' => false, 'status' => 'dismissed', 'snooze_until' => null];
    }
    if ($status === 'snoozed' && $until) {
        $ts = strtotime((string)$until);
        if ($ts !== false && $ts > time()) {
            return ['visible' => false, 'status' => 'snoozed', 'snooze_until' => (string)$until];
        }
        // Expired snooze → treat as shown again
        return ['visible' => true, 'status' => 'shown', 'snooze_until' => null];
    }
    return ['visible' => true, 'status' => 'shown', 'snooze_until' => null];
}

try {
    switch ($action) {
        case 'status':
            echo json_encode(['status' => 'success', 'data' => wcc_donation_prompt_state($pdo, $uid)]);
            break;

        case 'snooze': {
            $days = (int)($body['days'] ?? 30);
            if ($days < 1) {
                $days = 30;
            }
            if ($days > 3650) {
                $days = 3650;
            }
            $until = (new DateTimeImmutable('now'))->modify('+' . $days . ' days')->format('Y-m-d H:i:s');
            $sql = 'INSERT INTO donation_prompt_prefs (user_id, status, snooze_until, last_action)
                    VALUES (?, \'snoozed\', ?, \'coffee_snooze\')
                    ON DUPLICATE KEY UPDATE status = VALUES(status),
                      snooze_until = VALUES(snooze_until),
                      last_action = VALUES(last_action)';
            $pdo->prepare($sql)->execute([$uid, $until]);
            echo json_encode([
                'status'  => 'success',
                'message' => __('about.support.toast_snoozed', ['days' => $days]),
                'days'    => $days,
                'data'    => wcc_donation_prompt_state($pdo, $uid),
            ]);
            break;
        }

        case 'dismiss': {
            $sql = 'INSERT INTO donation_prompt_prefs (user_id, status, snooze_until, last_action)
                    VALUES (?, \'dismissed\', NULL, \'no_coffee\')
                    ON DUPLICATE KEY UPDATE status = VALUES(status),
                      snooze_until = NULL,
                      last_action = VALUES(last_action)';
            $pdo->prepare($sql)->execute([$uid]);
            echo json_encode([
                'status'  => 'success',
                'message' => __('about.support.toast_dismissed'),
                'data'    => wcc_donation_prompt_state($pdo, $uid),
            ]);
            break;
        }

        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => __('common.error')]);
    }
} catch (Throwable $e) {
    error_log('[WCC donation_prompt] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => __('common.error')]);
}
