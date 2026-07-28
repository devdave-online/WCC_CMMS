<?php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('closeout_tickets');

if (!isset($_GET['id'])) { header("Location: ../index.php"); exit; }
$ticket_id = $_GET['id'];

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    $stmt = $pdo->prepare("SELECT * FROM active_tickets WHERE ticket_id = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$ticket) { die(__('ticket.not_found_die')); }
    
    $stmtAction = $pdo->prepare("SELECT * FROM ticket_actions WHERE ticket_id = ? ORDER BY action_start ASC");
    $stmtAction->execute([$ticket_id]);
    $actions = $stmtAction->fetchAll(PDO::FETCH_ASSOC);
    
    $stmtCmt = $pdo->prepare("SELECT * FROM ticket_comments WHERE ticket_id = ? ORDER BY created_at ASC");
    $stmtCmt->execute([$ticket_id]);
    $comments = $stmtCmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { wcc_user_error("Could not load the ticket for closeout.", $e->getMessage()); }
?>
<?php
$page_title = __('ticket.closeout_title');
require_once __DIR__ . '/../inc/head.php';
include __DIR__ . '/../nav.php';
?>

<div class="form-container">
    <div class="page-header"><h1><?= __e('ticket.closeout_review') ?></h1><a href="../index.php" class="nav-btn">🔙 <?= __e('btn.cancel') ?></a></div>
    <div class="ticket-info">
        <strong><?= __e('ticket.id_label') ?></strong> <?= htmlspecialchars($ticket['ticket_id']) ?> | <strong><?= __e('ticket.equip_short') ?></strong> <?= htmlspecialchars($ticket['equip_id']) ?><br>
        <span style="color:var(--danger);"><?= __e('ticket.original_issue') ?> <?= htmlspecialchars($ticket['fault_desc']) ?></span>
    </div>

    <h2 style="margin-top: 25px; margin-bottom: 15px; color: var(--text-accent); font-size: 1.1em;"><?= __e('ticket.intervention_timeline') ?></h2>
    <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 25px;">
        <?php foreach($actions as $idx => $act): ?>
            <div style="background: var(--surface-1); border: 1px solid var(--panel-border); border-left: 3px solid <?= ($act['escalated_to'] !== 'None' ? 'var(--status-escalated-text)' : 'var(--success)') ?>; padding: 12px; border-radius: var(--radius-sm); box-shadow: var(--shadow-1);">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; border-bottom: 1px solid var(--panel-border); padding-bottom: 5px; gap: var(--space-2); flex-wrap: wrap;">
                    <span style="font-weight: bold; color: var(--text-primary);">👨‍🔧 <?= htmlspecialchars($act['tech_name']) ?></span>
                    <span style="color: var(--text-secondary); font-size: var(--fs-sm); background: rgba(0,0,0,0.2); padding: 3px 8px; border-radius: 4px;">⏱️ <?= htmlspecialchars(date('M d, H:i', strtotime($act['action_start']))) ?> - <?= htmlspecialchars(date('H:i', strtotime($act['action_end']))) ?></span>
                </div>

                <div style="font-size: 0.95em; color: var(--text-secondary); margin-bottom: 5px;">
                    <strong style="color: var(--text-primary);"><?= __e('ticket.action_taken') ?>:</strong> <?= nl2br(htmlspecialchars($act['action_taken'])) ?>
                </div>

                <?php if(!empty($act['parts_used']) && $act['parts_used'] !== 'None'): ?>
                    <div style="font-size: var(--fs-sm); color: var(--text-muted); margin-top: 5px; padding: 5px; background: rgba(0,0,0,0.15); border-radius: 4px; display: inline-block;">
                        <strong style="color: var(--text-secondary);">📦 <?= __e('ticket.parts_used') ?>:</strong> <?= htmlspecialchars($act['parts_used']) ?>
                    </div>
                <?php endif; ?>

                <?php if($act['escalated_to'] !== 'None'): ?>
                    <div style="font-size: var(--fs-sm); color: var(--status-escalated-text); margin-top: 8px; font-weight: bold; padding: 5px; background: var(--status-escalated-bg); border-radius: 4px; display: inline-block; border: 1px solid var(--status-escalated-border);">
                        ⚠️ <?= __e('ticket.escalated_to_label', ['name' => $act['escalated_to']]) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <h2 style="margin-top: 5px; margin-bottom: 15px; color: var(--text-accent); font-size: 1.1em;">💬 <?= __e('ticket.comments_archive') ?></h2>
    <div style="max-height: 300px; overflow-y: auto; padding-right: 5px; display: flex; flex-direction: column; gap: 8px; margin-bottom: 25px;">
        <?php if (!empty($comments)): ?>
            <?php foreach($comments as $cmt): ?>
                <div style="background: var(--surface-1); padding: 8px 12px; border-radius: var(--radius-sm); border-left: 3px solid var(--accent);">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: var(--fs-sm);">
                        <strong style="color: var(--text-primary);"><?= htmlspecialchars($cmt['user_name']) ?></strong>
                        <span style="color: var(--text-secondary);"><?= htmlspecialchars(date('M d, H:i', strtotime($cmt['created_at']))) ?></span>
                    </div>
                    <div style="font-size: 0.95em; color: var(--text-primary);">
                        <?= nl2br(htmlspecialchars($cmt['comment_text'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="font-size: var(--fs-sm); color: var(--text-muted); font-style: italic;"><?= __e('ticket.no_comments') ?></div>
        <?php endif; ?>
    </div>

    <input type="hidden" id="ticket_id" value="<?= htmlspecialchars($ticket['ticket_id']) ?>">
    <label for="supervisor_name"><?= __e('ticket.supervisor') ?>:</label>
    <input type="text" id="supervisor_name" value="<?= htmlspecialchars($_SESSION['username'] ?? __('common.unknown')) ?>" readonly>
    <button id="closeBtn" class="btn btn-primary btn-block" style="margin-top:20px; font-size:1.1em; padding:15px;" onclick="closeTicket()"><?= __e('ticket.confirm_archive') ?></button>
</div>
<script>
    async function closeTicket() {
        if(!document.getElementById('supervisor_name').value) { openWccAlert(typeof t === 'function' ? t('common.validation_error') : 'Validation Error', typeof t === 'function' ? t('ticket.sign_off') : 'Please sign off!'); return; }
        
        // QoL UPDATE: Lock button
        const btn = document.getElementById('closeBtn');
        btn.disabled = true;
        btn.innerText = (typeof t === 'function' ? t('ticket.archiving') : 'Archiving… ⏳');

        const payload = { ticket_id: document.getElementById('ticket_id').value, supervisor: document.getElementById('supervisor_name').value };
        try {
            const response = await fetch('/api/submit_closeout.php', { method: 'POST', headers: wccJsonHeaders(), body: JSON.stringify(wccWithCsrf(payload)) });
            const result = await response.json();
            if (result.status === 'success') {
                const msg = result.message || (result.already_closed
                    ? (typeof t === 'function' ? t('ticket.already_closed') : 'Ticket is already closed.')
                    : (typeof t === 'function' ? t('ticket.closed_success') : 'Ticket Closed and Sent to History!'));
                if (typeof showToast === 'function') {
                    showToast(msg, result.already_closed ? 'warning' : 'success', result.already_closed ? 5000 : 4000);
                }
                if (result.already_closed) {
                    openWccAlert(typeof t === 'function' ? t('ticket.already_closed') : 'Already closed', msg, '/_rpt/history.php');
                } else {
                    openWccAlert(typeof t === 'function' ? t('common.success') : 'Success', msg, '/_rpt/history.php');
                }
            } else {
                const errMsg = result.message || (typeof t === 'function' ? t('ticket.could_not_close') : 'Could not close out the ticket.');
                if (typeof showToast === 'function') showToast(errMsg, 'error');
                openWccAlert(typeof t === 'function' ? t('common.error') : 'Error', errMsg);
                btn.disabled = false; btn.innerText = (typeof t === 'function' ? t('ticket.confirm_archive') : 'Confirm & Archive Ticket');
            }
        } catch (error) {
            const errMsg = (typeof t === 'function' ? t('common.error') : 'Error') + ': ' + error.message;
            if (typeof showToast === 'function') showToast(errMsg, 'error');
            openWccAlert(typeof t === 'function' ? t('common.error') : 'Error', errMsg);
            btn.disabled = false; btn.innerText = (typeof t === 'function' ? t('ticket.confirm_archive') : 'Confirm & Archive Ticket');
        }
    }
    
    // JS window.onload and dropdown logic removed as it is no longer needed
</script>
</body>
</html>
