<?php
/**
 * WCC CMMS — technician identity for intervention records.
 *
 * `ticket_actions.tech_name` is a free-text name, and historically two different
 * things ended up in it:
 *
 *   - the API write paths (submit_takeover / submit_hold / submit_instant_resolve /
 *     add_comment) stored $_SESSION['username']   e.g. "j.okafor"
 *   - imported and seeded history stored the person's display name  e.g. "Jide Okafor"
 *
 * That split silently broke the gamified proficiencies: my_profile.php looked rows
 * up by username (so a technician saw their own stats), while _mgmt/users.php looked
 * them up by full name (so the Users Directory showed 0 for everyone). Whichever
 * single key you pick, half the existing rows stop matching.
 *
 * Rather than migrate the column and hope nothing else wrote to it, identity is
 * resolved here and every read matches on BOTH forms.
 */

/**
 * The name new intervention records should be stamped with: display name, else username.
 *
 * Session-first, then a one-off DB lookup. The lookup matters for the REST API and
 * the companion app: those authenticate with X-API-Key and never run the login flow,
 * so $_SESSION['full_name'] is never populated and every record they wrote would be
 * stamped with the raw username while the web app wrote display names.
 */
function wcc_tech_name(): string
{
    $full = trim((string)($_SESSION['full_name'] ?? ''));
    if ($full !== '') return $full;

    $uid = (int)($_SESSION['user_id'] ?? 0);
    if ($uid > 0) {
        static $resolved = [];               // one query per request, per user
        if (!array_key_exists($uid, $resolved)) {
            $resolved[$uid] = null;
            try {
                $st = get_wcc_db_connection()->prepare("SELECT full_name FROM users WHERE user_id = ?");
                $st->execute([$uid]);
                $name = trim((string)$st->fetchColumn());
                if ($name !== '') $resolved[$uid] = $name;
            } catch (Throwable $e) { /* fall through to username */ }
        }
        if ($resolved[$uid] !== null) {
            $_SESSION['full_name'] = $resolved[$uid];   // reuse for the rest of the request
            return $resolved[$uid];
        }
    }

    return (string)($_SESSION['username'] ?? 'Unknown User');
}

/**
 * Every spelling a given user's interventions might be filed under.
 * Pass to an IN (...) clause so old and new rows both count.
 *
 * @param array $user row with at least username, ideally full_name
 * @return string[] de-duplicated, non-empty candidate names
 */
function wcc_tech_aliases(array $user): array
{
    $names = [];
    foreach (['full_name', 'username'] as $k) {
        $v = trim((string)($user[$k] ?? ''));
        if ($v !== '') $names[$v] = true;
    }
    return array_keys($names) ?: ['\x00none'];
}

/** Ready-made "(?,?)" placeholder list for wcc_tech_aliases(). */
function wcc_tech_alias_placeholders(array $aliases): string
{
    return implode(',', array_fill(0, count($aliases), '?'));
}
