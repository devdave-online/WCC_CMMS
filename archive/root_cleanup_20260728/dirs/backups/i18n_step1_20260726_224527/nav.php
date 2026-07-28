<?php
// nav.php — Side Navigation Bar (WCC shell)
// Markup + permission gating only. Styles: css/global.css (sidebar section).
// Behavior: js/wcc-ui.js. Shared <head>: inc/head.php (WCC_HEAD guard below).

// 1. Bulletproof Session Starter
if (session_status() === PHP_SESSION_NONE) {
require_once __DIR__ . '/inc/session.php'; // hardened session bootstrap
}

// 2. Fail-safe include to ensure the can() function is always available
require_once 'rbac.php';

require_once __DIR__ . '/inc/version.php';
require_once __DIR__ . '/inc/notifications.php';
require_once __DIR__ . '/inc/csrf.php';
$__nav_uid      = (int)($_SESSION['user_id'] ?? 0);
$__notif_unread = wcc_unread_count($__nav_uid);
$__notif_list   = wcc_recent_notifications($__nav_uid, 30);

// 3. Fallback shell for pages that have not adopted inc/head.php yet.
//    Migrated pages define WCC_HEAD and get all of this from the head partial.
if (!defined('WCC_HEAD')): ?>
<script>
(function () {
    try {
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.classList.add('light-theme');
            if (document.body) document.body.classList.add('light-theme');
        }
        if (window.innerWidth > 768) {
            var ml = localStorage.getItem('sidebarState') === 'open' ? '240px' : '60px';
            var s = document.createElement('style');
            s.innerHTML = 'body { margin-left: ' + ml + ' !important; transition: none !important; }';
            document.head.appendChild(s);
            window.__sidebarSnapStyle = s;
        }
    } catch (e) {}
})();
</script>
<link rel="stylesheet" href="/css/global.css?v=<?= WCC_UI_VERSION ?>">
<script src="/js/wcc-ui.js?v=<?= WCC_UI_VERSION ?>" defer></script>
<script src="/js/xmb-wave.js?v=<?= WCC_UI_VERSION ?>" defer></script>
<script>
/* nav.php sits inside <body>: body exists — mirror theme class for legacy selectors */
if (document.documentElement.classList.contains('light-theme') && document.body) {
    document.body.classList.add('light-theme');
}
</script>
<?php endif; ?>

<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()"></div>
<button type="button" id="mobileNavToggle" onclick="toggleSidebar()" aria-label="Open navigation menu">☰</button>

<nav class="wcc-sidebar" id="wccSidebar" aria-label="Main navigation">
    <div class="anchored-pointer"></div>
    <div class="wcc-brand">
        <button type="button" class="menu-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">☰</button>
        <span class="brand-text" id="wccBrandText" style="cursor: pointer;" onclick="openAboutModal()" title="About WCC">🚀 WCC</span>
    </div>

    <!-- Operations -->
    <?php if(can('view_tickets') || can('view_work_orders')): ?>
    <div class="nav-section">Operations</div>
    <?php endif; ?>

    <?php if(can('view_tickets')): ?>
    <a href="/index.php" class="wcc-nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' || basename($_SERVER['PHP_SELF']) == 'active_tickets.php' ? 'active' : '' ?>">
        <span class="icon">🎫</span><span class="text">Tickets</span>
    </a>
    <?php endif; ?>

    <?php if(can('view_work_orders')): ?>
    <a href="/_maint/work_orders.php" class="wcc-nav-link <?= basename($_SERVER['PHP_SELF']) == 'work_orders.php' ? 'active' : '' ?>">
        <span class="icon">🛠️</span><span class="text">Work Orders</span>
    </a>
    <a href="/_maint/pm_calendar.php" class="wcc-nav-link <?= basename($_SERVER['PHP_SELF']) == 'pm_calendar.php' ? 'active' : '' ?>">
        <span class="icon">🗓️</span><span class="text">PM Calendar</span>
    </a>
    <?php endif; ?>

    <!-- Assets -->
    <?php if(can('view_equipment') || can('view_inventory')): ?>
    <div class="nav-section">Assets</div>
    <?php endif; ?>

    <?php if(can('view_equipment')): ?>
    <a href="/_eam/equipment_list.php" class="wcc-nav-link <?= basename($_SERVER['PHP_SELF']) == 'equipment_list.php' || basename($_SERVER['PHP_SELF']) == 'equipment.php' ? 'active' : '' ?>">
        <span class="icon">⚙️</span><span class="text">Equipment</span>
    </a>
    <a href="/_eam/toolings.php" class="wcc-nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['toolings.php','toolings_list.php','setup_vault_toolings.php'], true) ? 'active' : '' ?>">
        <span class="icon">🔧</span><span class="text">Tooling</span>
    </a>
    <?php if(can('manage_equipment')): ?>
    <a href="/_prod/setup_vault_lines.php" class="wcc-nav-link <?= basename($_SERVER['PHP_SELF']) == 'setup_vault_lines.php' ? 'active' : '' ?>">
        <span class="icon">🏭</span><span class="text">Prod. Lines</span>
    </a>
    <?php endif; ?>
    <?php endif; ?>

    <?php if(can('view_inventory')): ?>
    <a href="/_logi/inventory.php" class="wcc-nav-link <?= basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : '' ?>">
        <span class="icon">📦</span><span class="text">Inventory</span>
    </a>
    <?php endif; ?>

    <!-- Records -->
    <?php if(can('view_history') || can('view_vendors') || can('view_purchase_requests') || can('approve_purchase_orders') || can('fulfill_purchase_orders')): ?>
    <div class="nav-section">Records</div>
    <?php endif; ?>

    <?php if(can('view_history')): ?>
    <a href="/_rpt/history.php" class="wcc-nav-link <?= basename($_SERVER['PHP_SELF']) == 'history.php' ? 'active' : '' ?>">
        <span class="icon">🗄️</span><span class="text">Event History</span>
    </a>
    <?php endif; ?>

    <?php if(can('view_vendors')): ?>
    <a href="/_logi/vendors_list.php" class="wcc-nav-link <?= basename($_SERVER['PHP_SELF']) == 'vendors_list.php' || basename($_SERVER['PHP_SELF']) == 'vendors.php' ? 'active' : '' ?>">
        <span class="icon">🏢</span><span class="text">Vendors</span>
    </a>
    <?php endif; ?>

    <?php if(can('view_purchase_requests')): ?>
    <a href="/_logi/purchase_requests.php" class="wcc-nav-link <?= basename($_SERVER['PHP_SELF']) == 'purchase_requests.php' ? 'active' : '' ?>">
        <span class="icon">🛒</span><span class="text">Purchase Requests</span>
    </a>
    <?php endif; ?>

    <?php
    // Purchase Orders ledger is admin-panel / PR→PO workflow only — not a sidebar destination.
    // (Direct URL still works for users with approve/fulfill; nav entry intentionally omitted.)
    ?>

    <!-- People -->
    <?php if(can('manage_users')): ?>
    <div class="nav-section">People</div>
    <a href="/_mgmt/users_list.php" class="wcc-nav-link <?= basename($_SERVER['PHP_SELF']) == 'users_list.php' || basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">
        <span class="icon">👥</span><span class="text">Users</span>
    </a>
    <?php endif; ?>

    <!-- Insights -->
    <?php if(can('view_statistics')): ?>
    <div class="nav-section">Insights</div>
    <a href="/_rpt/statistics.php" class="wcc-nav-link <?= basename($_SERVER['PHP_SELF']) == 'statistics.php' ? 'active' : '' ?>">
        <span class="icon">📊</span><span class="text">Analytics</span>
    </a>
    <?php endif; ?>

    <div class="wcc-sidebar-footer">
        <div class="sidebar-widget-container">
            <div class="collapsed-widget" title="System Lifespan">
                <svg class="mini-gauge-svg" id="miniGaugeSvg" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" aria-hidden="true">
                    <path d="M4 15a9 9 0 1 1 16 0" stroke="rgba(255,255,255,0.1)"/>
                    <path id="miniGaugeValue" d="M4 15a9 9 0 0 1 9-9" stroke="#10b981" style="transition: stroke 0.3s;"/>
                    <circle id="miniGaugeDot" cx="13" cy="13" r="2" fill="#10b981" stroke="none" style="transition: fill 0.3s;"/>
                    <path id="miniGaugeNeedle" d="M13 13l4 -4" stroke="#10b981" style="transition: stroke 0.3s;"/>
                </svg>
            </div>
            <div class="expanded-widget" id="industrialTimer">
                <div id="timerLabel">//SYS.LIFESPAN//</div>
                <div id="blockContainer"></div>
            </div>
        </div>

        <div class="sidebar-widget-container">
            <a href="/my_profile.php" class="collapsed-widget sidebar-profile-icon <?= basename($_SERVER['PHP_SELF']) == 'my_profile.php' ? 'active' : '' ?>" title="My Profile" aria-label="My Profile">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#a78bfa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </a>
            <a href="/my_profile.php" class="expanded-widget sidebar-profile-card <?= basename($_SERVER['PHP_SELF']) == 'my_profile.php' ? 'active' : '' ?>">
                <div class="sidebar-profile-meta">
                    <?php $role_name = strtoupper(get_role_name((int)($_SESSION['role_level'] ?? 1))); ?>
                    <span class="meta-key">ROLE:</span> <span class="meta-role"><?= $role_name ?></span><br>
                    <span class="meta-key">USER:</span> <span class="meta-user"><?= isset($_SESSION['badge_number']) ? htmlspecialchars($_SESSION['badge_number']) : (isset($_SESSION['username']) ? htmlspecialchars(strtoupper($_SESSION['username'])) : 'GUEST') ?></span>
                </div>
            </a>
        </div>

        <?php if(can('manage_settings')): ?>
        <div class="nav-section">Administration</div>
        <a href="/_mgmt/admin_panel.php" class="wcc-nav-link <?= basename($_SERVER['PHP_SELF']) == 'admin_panel.php' ? 'active' : '' ?>">
            <span class="icon">🛡️</span><span class="text">Admin Panel</span>
        </a>
        <a href="/_mgmt/app_settings.php" class="wcc-nav-link <?= basename($_SERVER['PHP_SELF']) == 'app_settings.php' ? 'active' : '' ?>">
            <span class="icon">⚙️</span><span class="text">Settings</span>
        </a>
        <?php endif; ?>

        <button type="button" id="wccNotifBell" class="wcc-nav-link" onclick="openWccModal('wccNotifModal')" title="Notifications" aria-label="Notifications<?= $__notif_unread > 0 ? ' (' . $__notif_unread . ' unread)' : '' ?>">
            <span class="icon wcc-notif-icon" aria-hidden="true">🔔<?php if($__notif_unread > 0): ?><span class="wcc-notif-dot"></span><?php endif; ?></span>
            <span class="text">Notifications<?php if($__notif_unread > 0): ?> <span class="wcc-notif-count"><?= $__notif_unread ?></span><?php endif; ?></span>
        </button>

        <button type="button" id="wccThemeToggle" class="wcc-nav-link" onclick="toggleTheme()" aria-pressed="false" title="Switch theme">
            <span class="icon" id="wccThemeIcon" aria-hidden="true">🌙</span><span class="text" id="wccThemeLabel">Theme: Dark</span>
        </button>

        <a href="/login.php?logout=true" class="wcc-nav-link wcc-nav-logout">
            <span class="icon">🚪</span><span class="text">Logout</span>
        </a>
    </div>
</nav>

<!-- About WCC Welcome Modal Overlay (Included) -->
<?php include __DIR__ . '/_about_modal.php'; ?>

<!-- Global Confirmation Modal Overlay (Included) -->
<?php include __DIR__ . '/_confirm_modal.php'; ?>

<!-- Notification Center Overlay -->
<div class="wcc-modal" id="wccNotifModal" role="dialog" aria-modal="true" aria-labelledby="wccNotifTitle">
    <div class="wcc-modal-content wcc-modal-md">
        <div class="wcc-modal-header">
            <h3 id="wccNotifTitle">🔔 Notifications</h3>
            <button type="button" class="wcc-close-btn" onclick="closeWccModal('wccNotifModal')" aria-label="Close">✕</button>
        </div>
        <div class="wcc-notif-list" id="wccNotifList">
            <?php if(empty($__notif_list)): ?>
                <div class="wcc-notif-empty">🌱 You're all caught up — no notifications.</div>
            <?php else: foreach($__notif_list as $n): ?>
                <div class="wcc-notif-item <?= $n['is_read'] ? '' : 'unread' ?>" data-id="<?= (int)$n['id'] ?>">
                    <span class="wcc-notif-ico"><?= wcc_notif_icon($n['severity']) ?></span>
                    <div class="wcc-notif-body">
                        <?php if(!empty($n['link'])): ?>
                            <a href="<?= htmlspecialchars($n['link']) ?>" class="wcc-notif-msg" onclick="wccMarkNotifRead(<?= (int)$n['id'] ?>)"><?= htmlspecialchars($n['message']) ?></a>
                        <?php else: ?>
                            <span class="wcc-notif-msg"><?= htmlspecialchars($n['message']) ?></span>
                        <?php endif; ?>
                        <span class="wcc-notif-time"><?= wcc_notif_ago($n['created_at']) ?></span>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
        <div class="wcc-modal-footer">
            <button type="button" class="pill-btn" onclick="wccNotifAction('mark_all_read')">Mark all read</button>
            <button type="button" class="pill-btn pill-danger" onclick="wccNotifAction('delete_all')">Delete all</button>
        </div>
    </div>
</div>
<script>
    const WCC_NOTIF_CSRF = '<?= wcc_csrf_token() ?>';
    async function wccNotifAction(action) {
        try {
            const r = await fetch('/api/notifications.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: action, csrf: WCC_NOTIF_CSRF })
            });
            const res = await r.json();
            if (res.status === 'success') { location.reload(); }
            else { showToast(res.message || 'Action failed.', 'error'); }
        } catch (e) { showToast('Could not update notifications.', 'error'); }
    }
    function wccMarkNotifRead(id) {
        // fire-and-forget; the link navigation proceeds regardless
        try {
            fetch('/api/notifications.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'mark_read', id: id, csrf: WCC_NOTIF_CSRF }),
                keepalive: true
            });
        } catch (e) {}
    }
</script>

<script src="/timer.js"></script>
