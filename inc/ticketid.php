<?php
/**
 * WCC CMMS — ticket identifier generation.
 *
 * `active_tickets.ticket_id` is the primary key, and it used to be built as
 * "TK-WEB-" . date('ymd-His') — second-resolution. Two operators registering a
 * fault in the same second produced the same key and the second INSERT failed
 * outright. On a busy line, with several terminals and a shift change, that is
 * not a theoretical race: it is a hard error on the most-used path in the app.
 *
 * The ID was also accepted from the request body when supplied, so a caller could
 * pick any identifier it liked, including one already in use.
 *
 * Format: TK-YYMMDD-NNN — a per-day sequence, zero padded to three digits and
 * growing beyond that if a day ever needs it. Compact, sorts chronologically,
 * readable over a radio ("tee-kay two-six-oh-seven-two-two dash fourteen"), and
 * identical to the format used for seeded history so the two are indistinguishable.
 */

/** Next free sequential id for today. Not a reservation — see wcc_insert_ticket(). */
function wcc_next_ticket_id(PDO $pdo, ?string $day = null): string
{
    $day    = $day ?: date('ymd');
    $prefix = 'TK-' . $day . '-';

    // Highest sequence already used today. CAST so '10' sorts above '9'.
    $st = $pdo->prepare(
        "SELECT MAX(CAST(SUBSTRING(ticket_id, ?) AS UNSIGNED))
           FROM active_tickets
          WHERE ticket_id LIKE ?"
    );
    $st->execute([strlen($prefix) + 1, $prefix . '%']);
    $next = (int)$st->fetchColumn() + 1;

    return $prefix . str_pad((string)$next, 3, '0', STR_PAD_LEFT);
}

/**
 * Insert a ticket under a freshly generated id, retrying if another request wins
 * the race for the same sequence number.
 *
 * The MAX()+1 read and the INSERT are not atomic together, so two concurrent
 * registrations can compute the same next value. Rather than lock the table on
 * the hottest write path, we let the primary key arbitrate: the loser catches
 * the duplicate-key error and takes the next number. Converges in one extra
 * attempt under realistic contention.
 *
 * @param callable $insert function(string $ticketId): void — performs the INSERT
 * @return string the id actually committed
 * @throws RuntimeException if it cannot settle within the attempt budget
 */
function wcc_insert_ticket(PDO $pdo, callable $insert, int $attempts = 6): string
{
    for ($i = 0; $i < $attempts; $i++) {
        $id = wcc_next_ticket_id($pdo);
        try {
            $insert($id);
            return $id;
        } catch (PDOException $e) {
            // 23000 = integrity constraint violation (duplicate key here).
            if ($e->getCode() !== '23000') throw $e;
            // Someone else took this number; brief jittered pause, then recompute.
            usleep(mt_rand(1000, 8000));
        }
    }
    throw new RuntimeException('Could not allocate a unique ticket ID after ' . $attempts . ' attempts.');
}
