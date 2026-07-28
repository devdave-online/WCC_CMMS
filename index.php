<?php
include 'auth.php';

// Enterprise centralized DB connection (highest quality, single source of truth)
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/workorders.php';
$pdo = get_wcc_db_connection();

// Shared definition (inc/workorders.php) so this badge and the Work Orders list
// can never disagree — they previously used different rules and showed different
// numbers for the same thing.
$overdue_pm_count = wcc_wo_overdue_count($pdo);

$page_title = __('ticket.hub_title');
require_once __DIR__ . '/inc/head.php';
?>
<style>
    .menu-grid { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-7); max-width: 800px; width: 100%; margin: var(--space-8) auto; }

    .menu-card {
        background: var(--panel-bg);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border: 1px solid var(--panel-border);
        border-top: 1px solid var(--panel-border-top);
        padding: var(--space-8) var(--space-5);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-2);
        text-align: center;
        text-decoration: none;
        color: var(--text-primary);
        transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), background 0.3s ease, box-shadow 0.3s ease, border 0.3s ease;
    }
    .menu-card:hover {
        transform: translateY(-8px);
        background: var(--btn-hover-bg);
        box-shadow: 0 20px 40px 0 rgba(0, 0, 0, 0.2);
        border: 1px solid var(--btn-hover-border);
    }
    .menu-card .icon { font-size: 3.5em; margin-bottom: 15px; filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.5)); transition: transform 0.3s ease; }
    .menu-card:hover .icon { transform: scale(1.1) rotate(3deg); }
    .menu-card h2 { margin: 0 0 10px 0; font-size: 1.5em; font-weight: 600; color: var(--text-accent); }
    .menu-card p { margin: 0; color: var(--text-muted); font-size: 0.95em; line-height: 1.5; font-weight: 400; }

    .menu-card.card-alert { border-color: var(--danger-border); grid-column: 1 / -1; background: var(--danger-bg); }
    .menu-card.card-alert h2, .menu-card.card-alert p { color: var(--danger); }
    .menu-card.card-fast { border-color: var(--warning-border); }
    .menu-card.card-fast h2 { color: var(--warning); }

    .hub-wrap { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 80vh; }
    .hub-title { color: var(--text-accent); margin: 0; font-size: 2.8em; font-weight: 600; letter-spacing: -0.5px; }
    .hub-subtitle { color: var(--text-muted); font-size: 1.1em; font-weight: 500; letter-spacing: 2px; text-transform: uppercase; margin-top: 10px; }

    @media (max-width: 768px) {
        .menu-grid { grid-template-columns: 1fr; gap: var(--space-4); }
        .hub-wrap { min-height: unset; }
        .hub-title { font-size: 2em; }
        .menu-card { padding: var(--space-6) var(--space-4); }
    }
</style>
<?php include 'nav.php'; ?>

<div class="dashboard-container hub-wrap">
    <div style="text-align: center; margin-bottom: 20px;">
        <h1 class="hub-title"><?= __e('ticket.hub_title') ?></h1>
        <p class="hub-subtitle"><?= __e('ticket.hub_subtitle') ?></p>
    </div>

    <div class="menu-grid">
        <?php if ($overdue_pm_count > 0): ?>
        <a href="/_maint/work_orders.php" class="menu-card card-alert">
            <div class="icon" aria-hidden="true">⚠️</div>
            <h2><?= __e('ticket.hub_overdue_title') ?></h2>
            <p><?= __e($overdue_pm_count === 1 ? 'ticket.hub_overdue_one' : 'ticket.hub_overdue_many', ['count' => $overdue_pm_count]) ?></p>
        </a>
        <?php endif; ?>

        <a href="/register.php" class="menu-card">
            <div class="icon" aria-hidden="true">📝</div>
            <h2><?= __e('ticket.hub_register_title') ?></h2>
            <p><?= __e('ticket.hub_register_desc') ?></p>
        </a>

        <a href="/_maint/active_tickets.php" class="menu-card">
            <div class="icon" aria-hidden="true">📋</div>
            <h2><?= __e('ticket.active_title') ?></h2>
            <p><?= __e('ticket.hub_active_desc') ?></p>
        </a>

        <a href="/_maint/quick_resolve.php" class="menu-card card-fast">
            <div class="icon" aria-hidden="true">⚡</div>
            <h2><?= __e('ticket.hub_instant_title') ?></h2>
            <p><?= __e('ticket.hub_instant_desc') ?></p>
        </a>

        <a href="/_rpt/history.php" class="menu-card">
            <div class="icon" aria-hidden="true">🗄️</div>
            <h2><?= __e('nav.event_history') ?></h2>
            <p><?= __e('ticket.hub_history_desc') ?></p>
        </a>
    </div>
</div>

</body>
</html>
