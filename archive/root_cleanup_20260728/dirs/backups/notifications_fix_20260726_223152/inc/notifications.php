<?php
/**
 * WCC CMMS — Notification helper.
 *
 * Per-user, backend/static (no live polling). Counts refresh on page load.
 * Mirrors inc/audit.php: self-includes db.php, reads $_SESSION for actor,
 * and NEVER lets a notification failure break the calling flow.
 *
 * Usage:
 *   require_once __DIR__ . '/notifications.php';
 *   wcc_notify($user_id, 'wo_assigned', 'WO #42 assigned to you', '/_maint/work_orders.php', 'info');
 *   wcc_notify_perm('approve_purchase_orders', 'pr_pending', 'PR-… needs approval', '/_logi/purchase_orders.php', 'warning', $exclude_uid);
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../rbac.php';

/** Insert one notification for one user. Silent on failure. */
function wcc_notify(int $user_id, string $type, string $message, ?string $link = null, string $severity = 'info'): void
{
    if ($user_id <= 0) return;
    try {
        $pdo = get_wcc_db_connection();
        $pdo->prepare("INSERT INTO notifications (user_id, type, message, link, severity) VALUES (?, ?, ?, ?, ?)")
            ->execute([$user_id, $type, $message, $link, $severity]);
    } catch (Throwable $e) {
        error_log('[WCC NOTIFY] ' . $type . ': ' . $e->getMessage());
    }
}

/**
 * Notify every active user who holds a given permission (role default + per-user
 * overrides), optionally excluding one user (e.g. the actor). Returns count sent.
 */
function wcc_notify_perm(string $permission, string $type, string $message, ?string $link = null, string $severity = 'info', ?int $exclude_uid = null): int
{
    $sent = 0;
    try {
        $pdo = get_wcc_db_connection();
        $rows = $pdo->query("SELECT user_id, role_level, permissions_json FROM users WHERE status = 'active'")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $u) {
            $uid = (int)$u['user_id'];
            if ($exclude_uid !== null && $uid === $exclude_uid) continue;
            $perms = wcc_get_permissions((int)$u['role_level'], $u['permissions_json'] ?? null);
            if (!empty($perms[$permission])) {
                wcc_notify($uid, $type, $message, $link, $severity);
                $sent++;
            }
        }
    } catch (Throwable $e) {
        error_log('[WCC NOTIFY_PERM] ' . $type . ': ' . $e->getMessage());
    }
    return $sent;
}

/** Unread count for the nav bell. */
function wcc_unread_count(int $user_id): int
{
    if ($user_id <= 0) return 0;
    try {
        $pdo = get_wcc_db_connection();
        $st = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $st->execute([$user_id]);
        return (int)$st->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

/** Most recent notifications for the overlay (newest first). */
function wcc_recent_notifications(int $user_id, int $limit = 30): array
{
    if ($user_id <= 0) return [];
    try {
        $pdo = get_wcc_db_connection();
        $limit = max(1, min(100, $limit));
        $st = $pdo->prepare("SELECT id, type, message, link, severity, is_read, created_at
                               FROM notifications WHERE user_id = ?
                              ORDER BY id DESC LIMIT $limit");
        $st->execute([$user_id]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

/** Map a severity to an emoji glyph for the overlay. */
function wcc_notif_icon(string $severity): string
{
    switch ($severity) {
        case 'success': return '✅';
        case 'warning': return '⚠️';
        case 'danger':  return '⛔';
        default:        return 'ℹ️';
    }
}

/** Compact relative time ("3m", "2h", "5d") for a timestamp string. */
function wcc_notif_ago(string $ts): string
{
    $t = strtotime($ts);
    if (!$t) return '';
    $d = time() - $t;
    if ($d < 60)     return 'just now';
    if ($d < 3600)   return floor($d / 60) . 'm';
    if ($d < 86400)  return floor($d / 3600) . 'h';
    if ($d < 604800) return floor($d / 86400) . 'd';
    return date('M j', $t);
}
