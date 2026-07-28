<?php
/**
 * WCC CMMS — brute-force throttle.
 *
 * Uses the `rate_limit` table that already shipped in the schema but was never
 * wired to anything. Fixed-window counter keyed on (ip_address, endpoint).
 *
 * Fail-open by design: a DB hiccup must never lock a technician out of the
 * shop-floor terminal. It stops password-guessing, it is not an access gate.
 */

require_once __DIR__ . '/db.php';

/** Best-effort client IP. Proxy headers are deliberately ignored (trivially spoofed). */
function wcc_client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

/**
 * Current strike count for this IP+endpoint, or 0 once the window has rolled over.
 * Returns [count, seconds_until_window_resets].
 */
function wcc_rate_status(string $endpoint, int $windowSeconds = 900): array
{
    try {
        $pdo = get_wcc_db_connection();
        $st = $pdo->prepare("SELECT request_count, window_start FROM rate_limit WHERE ip_address = ? AND endpoint = ?");
        $st->execute([wcc_client_ip(), $endpoint]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) return [0, 0];

        $age = time() - (int)$row['window_start'];
        if ($age >= $windowSeconds) return [0, 0];           // stale window = clean slate
        return [(int)$row['request_count'], $windowSeconds - $age];
    } catch (Throwable $e) {
        return [0, 0];                                        // fail open
    }
}

/** True when this IP has burned through its allowance. */
function wcc_rate_blocked(string $endpoint, int $max = 10, int $windowSeconds = 900): bool
{
    [$count] = wcc_rate_status($endpoint, $windowSeconds);
    return $count >= $max;
}

/** Record one failure; starts a fresh window if the previous one expired. */
function wcc_rate_hit(string $endpoint, int $windowSeconds = 900): void
{
    try {
        $pdo = get_wcc_db_connection();
        $now = time();
        // ON DUPLICATE KEY rides the existing unique(ip_address, endpoint) index, so
        // this is a single atomic statement — no read-then-write race between attempts.
        $pdo->prepare(
            "INSERT INTO rate_limit (ip_address, endpoint, window_start, request_count)
             VALUES (?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE
                request_count = IF(? - window_start >= ?, 1, request_count + 1),
                window_start  = IF(? - window_start >= ?, ?, window_start)"
        )->execute([wcc_client_ip(), $endpoint, $now, $now, $windowSeconds, $now, $windowSeconds, $now]);
    } catch (Throwable $e) {
        // fail open
    }
}

/** Wipe the counter — called after a successful login so honest users never accumulate. */
function wcc_rate_clear(string $endpoint): void
{
    try {
        get_wcc_db_connection()
            ->prepare("DELETE FROM rate_limit WHERE ip_address = ? AND endpoint = ?")
            ->execute([wcc_client_ip(), $endpoint]);
    } catch (Throwable $e) {
        // fail open
    }
}
