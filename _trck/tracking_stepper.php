<?php
/**
 * Tracking Stepper Component
 * Usage: include this file, then call
 *   render_tracking_stepper($status, $can_approve, $po_id, $username, $can_fulfill)
 *
 * $status      = current PO status string
 * $can_approve = user holds approve_purchase_orders (cost sign-off: step 0 → Issued)
 * $po_id       = the PO ID (needed for forms)
 * $username    = who owns the PO (shown in the header)
 * $can_fulfill = user holds fulfill_purchase_orders (logistics: ship / transit / close)
 *
 * The cost-approval button and the fulfilment buttons are gated separately so an
 * approver signs off spend and a Storekeeper takes over shipping/receiving/closing.
 */

function get_step_index($status) {
    $map = [
        'Draft' => -1,
        'Pending Approval' => 0,
        'Issued' => 1,
        'Shipped' => 2,
        'In Transit' => 3,
        'Partially Received' => 4,
        'Fully Received' => 4,
        'Closed' => 5,
        'Cancelled' => -2,
    ];
    return $map[$status] ?? -1;
}

function render_tracking_stepper($status, $can_approve = false, $po_id = 0, $username = '', $can_fulfill = false) {
    $steps = [
        ['label' => 'Submitted',        'icon' => '📋', 'desc' => 'PR/PO to buyer'],
        ['label' => 'Cost Approved',     'icon' => '💰', 'desc' => 'Budget cleared'],
        ['label' => 'Shipment Sent',     'icon' => '🚚', 'desc' => 'Vendor dispatched'],
        ['label' => 'In Transit',        'icon' => '✈️', 'desc' => 'On the way'],
        ['label' => 'Goods Received',    'icon' => '✅', 'desc' => 'Arrived at dock'],
        ['label' => 'Items Registered',  'icon' => '📦', 'desc' => 'In inventory'],
    ];
    
    $current = get_step_index($status);
    $is_cancelled = ($status === 'Cancelled');
    $is_partial = ($status === 'Partially Received');
    
    // Next action mapping for interactive mode
    $next_actions = [
        0 => ['next_status' => 'Issued',    'btn_label' => '💰 Approve Cost',       'btn_class' => ''],
        1 => ['next_status' => 'Shipped',   'btn_label' => '🚚 Mark Shipped',       'btn_class' => ''],
        2 => ['next_status' => 'In Transit','btn_label' => '✈️ Mark In Transit',    'btn_class' => ''],
        // Step 3 (In Transit) → receiving is handled separately via the line items form
        4 => ['next_status' => 'Closed',    'btn_label' => '📦 Register & Close',   'btn_class' => 'receive'],
    ];
    
    ?>
    <div class="tracking-stepper <?= $is_cancelled ? 'cancelled' : '' ?>">
        <div class="stepper-header">
            <span class="stepper-title">Order Tracking</span>
            <span class="stepper-meta">by <?= htmlspecialchars($username) ?></span>
        </div>
        <div class="stepper-track">
            <?php foreach($steps as $idx => $step): 
                $state = 'pending';
                if ($is_cancelled) {
                    $state = 'cancelled';
                } elseif ($idx < $current) {
                    $state = 'complete';
                } elseif ($idx === $current) {
                    $state = ($is_partial && $idx === 4) ? 'partial' : 'active';
                }
            ?>
            <div class="stepper-step <?= $state ?>">
                <div class="step-connector <?= $idx === 0 ? 'first' : '' ?>"></div>
                <div class="step-node">
                    <span class="step-icon"><?= $step['icon'] ?></span>
                    <?php if($state === 'complete'): ?>
                        <span class="step-check">✓</span>
                    <?php endif; ?>
                </div>
                <div class="step-label"><?= $step['label'] ?></div>
                <div class="step-desc"><?= $step['desc'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if($is_cancelled): ?>
            <div class="stepper-cancelled-badge">❌ CANCELLED</div>
        <?php endif; ?>
        
        <?php
            // Which buttons this user may see, by permission.
            $show_approve = ($current === 0 && $can_approve && isset($next_actions[0]));                       // cost sign-off
            $show_move    = ($current >= 1 && $current <= 2 && $can_fulfill && isset($next_actions[$current])); // ship / transit
            $show_close   = ($current === 4 && $status === 'Fully Received' && $can_fulfill && isset($next_actions[4]));
            $show_cancel  = (($can_approve || $can_fulfill) && !in_array($status, ['Fully Received', 'Closed', 'Cancelled']));
            $any_action   = ($show_approve || $show_move || $show_close || $show_cancel);
            // Cues shown when this user is waiting on someone else.
            $await_approval = ($status === 'Pending Approval' && !$can_approve);
            $await_fulfill  = (in_array($status, ['Issued', 'Shipped', 'In Transit', 'Partially Received', 'Fully Received']) && !$can_fulfill);
        ?>
        <?php if(!$is_cancelled && $status !== 'Closed' && $any_action): ?>
        <form method="POST" class="stepper-action-form">
            <?php if (function_exists('wcc_csrf_token')): ?>
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(wcc_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <?php endif; ?>
            <input type="hidden" name="action_po_id" value="<?= $po_id ?>">
            <input type="text" name="step_note" class="stepper-note" placeholder="Optional note for this step (added to the audit trail)…" autocomplete="off">
            <div class="stepper-actions">
                <?php if($show_approve): ?>
                    <button type="submit" name="new_status" value="<?= $next_actions[0]['next_status'] ?>" class="action-btn <?= $next_actions[0]['btn_class'] ?>"><?= $next_actions[0]['btn_label'] ?></button>
                <?php endif; ?>
                <?php if($show_move): ?>
                    <button type="submit" name="new_status" value="<?= $next_actions[$current]['next_status'] ?>" class="action-btn <?= $next_actions[$current]['btn_class'] ?>"><?= $next_actions[$current]['btn_label'] ?></button>
                <?php endif; ?>
                <?php if($show_close): ?>
                    <button type="submit" name="new_status" value="<?= $next_actions[4]['next_status'] ?>" class="action-btn receive"><?= $next_actions[4]['btn_label'] ?></button>
                <?php endif; ?>
                <?php if($show_cancel): ?>
                    <button type="submit" name="new_status" value="Cancelled" class="action-btn reject">❌ Cancel</button>
                <?php endif; ?>
            </div>
        </form>
        <?php endif; ?>
        <?php if($await_approval): ?>
            <div class="stepper-locked">⏳ Awaiting cost approval by an approver.</div>
        <?php elseif($await_fulfill): ?>
            <div class="stepper-locked">🧑‍🏭 Cost approved — awaiting fulfilment by a Storekeeper.</div>
        <?php endif; ?>
        <?php if($status === 'Closed'): ?>
            <div class="stepper-locked">🔒 Closed — All items registered in inventory.</div>
        <?php endif; ?>
    </div>
    <?php
}
