<?php
/**
 * WCC CMMS — inventory stock-status model.
 *
 * One place decides what state a part is in, so the inventory list, any dashboard
 * and the API all agree. The rendering (icon + colour) lives in inc/icons.php and
 * css; this file is pure classification.
 *
 * The decision is a first-match tree, because a part can be several things at once
 * and precedence matters:
 *
 *   1. Obsolete / phasing out   -> do-not-reorder. Never show the "order now" alarm
 *                                  for something that cannot or should not be ordered.
 *   2. Out of stock (0)         -> the work-stopper. Beats "on order" for the glance
 *                                  signal (you have zero right now); the tooltip still
 *                                  notes if replenishment is on the way.
 *   3. Low (<= min + warn band):
 *        a. an open PO covers it -> on order (low, but handled)
 *        b. no vendor set        -> needs order, but cannot auto-reorder (say why)
 *        c. stock <= min         -> at/below reorder point, act
 *        d. otherwise            -> approaching minimum, watch
 *   4. otherwise                -> healthy
 */

require_once __DIR__ . '/db.php';

const WCC_STOCK_STATES = [
    // state       => [icon (inc/icons.php),  css colour var,          short label]
    'healthy'      => ['circle-check',   'var(--success)', 'Healthy'],
    'approaching'  => ['alert-triangle', 'var(--warning)', 'Low — approaching minimum'],
    'reorder'      => ['alert-octagon',  'var(--danger)',  'At/below minimum — reorder'],
    'no_vendor'    => ['alert-octagon',  'var(--danger)',  'Below minimum — no supplier set'],
    'out'          => ['circle-x',       'var(--danger)',  'Out of stock'],
    'on_order'     => ['truck-delivery', 'var(--info)',    'On order — replenishment in flight'],
    'obsolete'     => ['ban',            'var(--text-muted)', 'Obsolete — do not reorder'],
];

/**
 * Configurable thresholds, from app_settings (category 'Inventory').
 * warn_pct: how far above the minimum still counts as "approaching" (percent).
 */
function wcc_stock_status_config(): array
{
    static $cfg = null;
    if ($cfg !== null) return $cfg;

    $cfg = ['warn_pct' => 25];   // sensible default
    try {
        $rows = get_wcc_db_connection()
            ->query("SELECT setting_key, setting_value FROM app_settings WHERE category = 'Inventory'")
            ->fetchAll(PDO::FETCH_KEY_PAIR);
        if (isset($rows['stock_warn_pct']) && is_numeric($rows['stock_warn_pct'])) {
            $cfg['warn_pct'] = max(0, min(200, (int)$rows['stock_warn_pct']));
        }
    } catch (Throwable $e) { /* keep defaults */ }

    return $cfg;
}

/**
 * Classify a part.
 *
 * @param array $part    row with stock_level, minimum_threshold, lifecycle_status, primary_vendor_id
 * @param bool  $onOrder is there an open PO covering this part (see the caller's precomputed set)
 * @param array|null $cfg override for wcc_stock_status_config()
 * @return array{state:string, icon:string, color:string, label:string}
 */
function wcc_stock_status(array $part, bool $onOrder = false, ?array $cfg = null): array
{
    $cfg   = $cfg ?? wcc_stock_status_config();
    $stock = (int)($part['stock_level'] ?? 0);
    $min   = max(0, (int)($part['minimum_threshold'] ?? 0));
    $life  = $part['lifecycle_status'] ?? 'Active';
    $hasVendor = !empty($part['primary_vendor_id']);

    // Warning band: within warn_pct above the minimum, at least 1 unit.
    $buffer = max(1, (int)ceil($min * ($cfg['warn_pct'] / 100)));
    $band   = $min + $buffer;

    $state = 'healthy';

    if ($life === 'Obsolete' || $life === 'Phasing Out') {
        $state = 'obsolete';
    } elseif ($stock === 0) {
        $state = 'out';
    } elseif ($stock <= $band) {                 // low in some degree
        if ($onOrder)            $state = 'on_order';
        elseif (!$hasVendor)     $state = 'no_vendor';
        elseif ($stock <= $min)  $state = 'reorder';
        else                     $state = 'approaching';
    }

    [$icon, $color, $label] = WCC_STOCK_STATES[$state];

    // Enrich the out-of-stock tooltip when relief is genuinely on the way.
    if ($state === 'out' && $onOrder) $label = 'Out of stock — replenishment on the way';

    return ['state' => $state, 'icon' => $icon, 'color' => $color, 'label' => $label];
}
