<?php
/**
 * WCC CMMS — work order / PM schedule `parts_list` handling.
 *
 * This column is JSON, and the app historically wrote TWO different shapes into it:
 *
 *   creating a WO or PM   (_mgmt/app_settings.php, _mgmt/admin_panel.php)
 *       ["3","7"]                        - a flat list of part ids, no quantities
 *   completing a WO       (_maint/wo_takeover.php)
 *       [{"part_id":3,"qty":2}, ...]     - objects carrying the quantity used
 *
 * Both readers assumed the flat form. So the moment a technician completed a work
 * order through takeover, the Work Orders list hit `$parts_map[$array]` ("Illegal
 * offset type") and `intval($array)`. Because inc/error.php promotes notices to
 * exceptions, that single bad row aborted the whole page — the list rendered one
 * work order and then died into the generic error screen.
 *
 * Everything now goes through here. The object form is canonical (it carries the
 * quantity, which the flat form throws away), and decoding accepts either so no
 * existing row needs migrating.
 */

/**
 * Normalise any stored parts_list into [['part_id'=>int, 'qty'=>int], ...].
 * Unparseable input yields an empty list rather than throwing — a malformed row
 * must never be able to take a page down again.
 *
 * @param string|null $json raw column value
 */
function wcc_parts_list_decode(?string $json): array
{
    if ($json === null || $json === '') return [];

    $raw = json_decode($json, true);
    if (!is_array($raw)) return [];

    $out = [];
    foreach ($raw as $key => $item) {
        // [{"part_id":3,"qty":2}] — the canonical form
        if (is_array($item)) {
            $id  = (int)($item['part_id'] ?? $item['id'] ?? 0);
            $qty = (int)($item['qty'] ?? $item['quantity'] ?? 1);
        }
        // {"3": 2} — id => qty map
        elseif (!is_int($key) && is_scalar($item)) {
            $id  = (int)$key;
            $qty = (int)$item;
        }
        // [3, "7"] — legacy flat id list, quantity unknown
        elseif (is_scalar($item)) {
            $id  = (int)$item;
            $qty = 1;
        } else {
            continue;
        }

        if ($id > 0) {
            $qty = max(1, $qty);
            // Same part listed twice: keep one line, add the quantities.
            if (isset($out[$id])) $out[$id]['qty'] += $qty;
            else                  $out[$id] = ['part_id' => $id, 'qty' => $qty];
        }
    }
    return array_values($out);
}

/** Just the part ids, for IN (...) lookups. */
function wcc_parts_list_ids(?string $json): array
{
    return array_column(wcc_parts_list_decode($json), 'part_id');
}

/** Encode to the canonical stored form. Accepts ids or id=>qty pairs. */
function wcc_parts_list_encode(array $parts): string
{
    $out = [];
    foreach ($parts as $k => $v) {
        if (is_array($v))            $out[] = ['part_id' => (int)($v['part_id'] ?? 0), 'qty' => max(1, (int)($v['qty'] ?? 1))];
        elseif (!is_int($k))         $out[] = ['part_id' => (int)$k, 'qty' => max(1, (int)$v)];
        else                         $out[] = ['part_id' => (int)$v, 'qty' => 1];
    }
    $out = array_values(array_filter($out, fn($p) => $p['part_id'] > 0));
    return json_encode($out);
}
