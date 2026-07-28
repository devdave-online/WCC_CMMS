<?php
/**
 * Tracking Stepper Component
 * Usage: include this file, then call render_tracking_stepper($status, $interactive, $po_id)
 * 
 * $status = current PO status string
 * $interactive = true for MANAGE page (shows action buttons), false for VIEW page
 * $po_id = the PO ID (needed for forms)
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

function render_tracking_stepper($status, $interactive = false, $po_id = 0, $username = '') {
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
        
        <?php if($interactive && !$is_cancelled && !in_array($status, ['Closed'])): ?>
        <div class="stepper-actions">
            <?php if($current >= 0 && $current <= 2 && isset($next_actions[$current])): ?>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action_po_id" value="<?= $po_id ?>">
                    <button type="submit" name="new_status" value="<?= $next_actions[$current]['next_status'] ?>" class="action-btn <?= $next_actions[$current]['btn_class'] ?>"><?= $next_actions[$current]['btn_label'] ?></button>
                </form>
            <?php endif; ?>
            <?php if($current === 4 && $status === 'Fully Received' && isset($next_actions[4])): ?>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action_po_id" value="<?= $po_id ?>">
                    <button type="submit" name="new_status" value="<?= $next_actions[4]['next_status'] ?>" class="action-btn receive"><?= $next_actions[4]['btn_label'] ?></button>
                </form>
            <?php endif; ?>
            <?php if(!in_array($status, ['Fully Received', 'Closed', 'Cancelled'])): ?>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action_po_id" value="<?= $po_id ?>">
                    <button type="submit" name="new_status" value="Cancelled" class="action-btn reject">❌ Cancel</button>
                </form>
            <?php endif; ?>
        </div>
        <?php elseif(in_array($status, ['Closed'])): ?>
            <div class="stepper-locked">🔒 Closed — All items registered in inventory.</div>
        <?php endif; ?>
    </div>
    <?php
}
?>

<style>
.tracking-stepper {
    background: var(--surface-1);
    border: 1px solid var(--panel-border);
    border-radius: var(--radius-md);
    padding: 20px;
    margin-bottom: 15px;
    position: relative;
    overflow: hidden;
}
.tracking-stepper.cancelled {
    opacity: 0.6;
    border-color: var(--danger-border);
}
.stepper-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.stepper-title {
    color: var(--text-accent);
    font-weight: bold;
    font-size: var(--fs-sm);
    text-transform: uppercase;
    letter-spacing: 1px;
}
.stepper-meta {
    color: var(--text-muted);
    font-size: var(--fs-xs);
}
.stepper-track {
    display: flex;
    justify-content: space-between;
    position: relative;
}
.stepper-step {
    flex: 1;
    text-align: center;
    position: relative;
    z-index: 1;
}
.step-connector {
    position: absolute;
    top: 22px;
    left: -50%;
    right: 50%;
    height: 2px;
    background: var(--panel-border);
    z-index: 0;
}
.step-connector.first { display: none; }
.stepper-step.complete .step-connector,
.stepper-step.active .step-connector,
.stepper-step.partial .step-connector {
    background: var(--text-accent);
}
.step-node {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    margin: 0 auto 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3em;
    position: relative;
    z-index: 2;
    border: 2px solid var(--border-strong);
    background: var(--panel-bg);
    transition: all 0.3s ease;
}
.stepper-step.complete .step-node {
    border-color: var(--text-accent);
    background: rgba(99,102,241,0.15);
}
.stepper-step.active .step-node {
    border-color: var(--text-accent);
    background: rgba(99,102,241,0.25);
    box-shadow: 0 0 15px rgba(99,102,241,0.4);
    animation: pulse-glow 2s infinite;
}
.stepper-step.partial .step-node {
    border-color: var(--amber-500);
    background: var(--warning-bg);
    box-shadow: 0 0 12px rgba(245,158,11,0.3);
    animation: pulse-glow-amber 2s infinite;
}
.stepper-step.cancelled .step-node {
    border-color: rgba(255,255,255,0.05);
    background: rgba(0,0,0,0.2);
}
.stepper-step.pending .step-node {
    opacity: 0.35;
}
.step-check {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: var(--emerald-500);
    color: #fff;
    font-size: 9px; /* decorative glyph inside a 16px dot, not readable text */
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}
.step-label {
    font-size: var(--fs-xs);
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 2px;
}
.stepper-step.pending .step-label { color: var(--text-muted); }
.stepper-step.cancelled .step-label { color: var(--text-muted); }
.step-desc {
    font-size: var(--fs-xs);
    color: var(--text-muted);
}
.stepper-step.pending .step-desc { opacity: 0.5; }

.stepper-actions {
    display: flex;
    gap: 8px;
    margin-top: 18px;
    padding-top: 12px;
    border-top: 1px solid var(--panel-border);
    justify-content: center;
    flex-wrap: wrap;
}
.stepper-cancelled-badge {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-15deg);
    font-size: 2em;
    font-weight: 900;
    color: var(--danger);
    opacity: 0.3;
    letter-spacing: 5px;
    pointer-events: none;
}
.stepper-locked {
    text-align: center;
    margin-top: 15px;
    padding-top: 12px;
    border-top: 1px solid var(--panel-border);
    color: var(--text-muted);
    font-size: var(--fs-sm);
}

@keyframes pulse-glow {
    0%, 100% { box-shadow: 0 0 15px rgba(99,102,241,0.4); }
    50% { box-shadow: 0 0 25px rgba(99,102,241,0.6); }
}
@keyframes pulse-glow-amber {
    0%, 100% { box-shadow: 0 0 12px rgba(245,158,11,0.3); }
    50% { box-shadow: 0 0 20px rgba(245,158,11,0.5); }
}

/* Handheld: horizontal track becomes a vertical checklist */
@media (max-width: 768px) {
    .stepper-track { flex-direction: column; gap: 14px; }
    .stepper-step { display: flex; align-items: center; text-align: left; gap: 12px; }
    .step-connector { display: none; }
    .step-node { margin: 0; flex-shrink: 0; }
    .step-label { margin-bottom: 0; font-size: var(--fs-sm); }
}
</style>
