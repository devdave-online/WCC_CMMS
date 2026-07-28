<?php
// nav.php - Side Navigation Bar
?>
<script>
    // Initialize Theme immediately to prevent FOUC
    if (localStorage.getItem('theme') === 'light') {
        document.documentElement.classList.add('light-theme'); // Use documentElement or wait for body
        // Wait for body to be available to apply class
        window.addEventListener('DOMContentLoaded', () => {
            document.body.classList.add('light-theme');
        });
    }

    // Global toggle function
    function toggleTheme() {
        document.body.classList.toggle('light-theme');
        if (document.body.classList.contains('light-theme')) {
            localStorage.setItem('theme', 'light');
        } else {
            localStorage.setItem('theme', 'dark');
        }
    }
</script>
<link rel="stylesheet" href="css/global.css?v=<?= time() ?>">
<style>
    .wcc-sidebar {
        --sidebar-accent: #38bdf8; /* High contrast on dark */
        position: fixed;
        top: 0; left: 0; bottom: 0;
        width: 60px; /* Collapsed width */
        background: rgba(30, 41, 59, 0.85);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border-right: 1px solid rgba(255, 255, 255, 0.1);
        z-index: 10000;
        transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow-x: hidden;
        white-space: nowrap;
        display: flex;
        flex-direction: column;
        box-shadow: 4px 0 15px rgba(0,0,0,0.2);
    }
    
    .wcc-sidebar.open {
        width: 240px; /* Expanded width */
    }

    .wcc-brand {
        display: flex;
        align-items: center;
        padding: 20px 15px;
        color: var(--sidebar-accent);
        font-weight: 800;
        font-size: 1.2em;
        letter-spacing: 1px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        margin-bottom: 10px;
        justify-content: space-between;
    }
    
    .menu-toggle {
        cursor: pointer;
        font-size: 1.5em;
        color: #f8fafc;
        margin-right: 15px;
        transition: color 0.2s;
    }
    .menu-toggle:hover {
        color: var(--sidebar-accent);
    }
    
    .wcc-brand .brand-text {
        opacity: 0;
        transition: opacity 0.2s;
        flex-grow: 1;
    }
    .wcc-sidebar.open .wcc-brand .brand-text {
        opacity: 1;
        transition-delay: 0.1s;
    }

    .wcc-nav-link {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        color: #e2e8f0;
        text-decoration: none;
        font-weight: 600;
        transition: background 0.2s, color 0.2s, border-left 0.2s;
        border-left: 3px solid transparent;
        margin: 4px 0;
    }
    .wcc-nav-link .icon {
        font-size: 1.3em;
        margin-right: 15px;
        min-width: 30px;
        text-align: center;
        transition: transform 0.2s;
    }
    .wcc-nav-link .text {
        opacity: 0;
        transition: opacity 0.2s;
        font-size: 0.95em;
    }
    
    .wcc-sidebar.open .wcc-nav-link .text {
        opacity: 1;
        transition-delay: 0.1s;
    }

    :root {
        --spring-easing: linear(0, 0.0018, 0.0069 1.15%, 0.026 2.3%, 0.0637, 0.1135 5.18%, 0.2229 7.78%, 0.5977 15.84%, 0.7014, 0.7904, 0.8641, 0.9228, 0.9676 28.8%, 1.0032 31.68%, 1.0225, 1.0352 36.29%, 1.0431 38.88%, 1.046 42.05%, 1.0448 44.35%, 1.0407 47.23%, 1.0118 61.63%, 1.0025 69.41%, 0.9981 80.35%, 0.9992 99.94%);
    }

    .anchored-pointer {
        position: absolute;
        position-anchor: --selected;
        top: anchor(top);
        bottom: anchor(bottom);
        left: anchor(left);
        right: anchor(right);
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        transition: all 0.6s var(--spring-easing);
        pointer-events: none;
        overflow: hidden;
        backdrop-filter: blur(8px) url(#displacementFilter);
        -webkit-backdrop-filter: blur(8px) url(#displacementFilter);
        z-index: -1;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    
    .anchored-pointer::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 10px 50%, rgba(139, 92, 246, 0.5) 0%, transparent 40%),
                    radial-gradient(ellipse at 50% 50%, rgba(255,255,255,0.1) 0%, transparent 80%);
        border-left: 4px solid var(--sidebar-accent);
    }

    body.light-theme .anchored-pointer {
        background: rgba(0, 0, 0, 0.03);
        border: 1px solid rgba(0, 0, 0, 0.05);
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    
    body.light-theme .anchored-pointer::before {
        background: radial-gradient(circle at 10px 50%, rgba(139, 92, 246, 0.3) 0%, transparent 40%),
                    radial-gradient(ellipse at 50% 50%, rgba(0,0,0,0.03) 0%, transparent 80%);
        border-left: 4px solid var(--sidebar-accent);
    }

    .wcc-nav-link {
        position: relative; /* ensure text is above pointer */
        z-index: 1;
        transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), color 0.2s, background 0.2s, border-left 0.2s;
    }

    .wcc-nav-link:hover, .wcc-nav-link.active {
        color: var(--sidebar-accent);
        transform: scale(1.03);
        z-index: 2;
    }
    .wcc-nav-link:hover .icon, .wcc-nav-link.active .icon {
        transform: scale(1.15);
    }

    /* Red Zone - Timer */
    .sidebar-red-zone {
        margin-top: auto;
        padding: 15px;
        opacity: 0;
        transition: opacity 0.2s;
        pointer-events: none;
    }
    .wcc-sidebar.open .sidebar-red-zone {
        opacity: 1;
        pointer-events: auto;
    }

    #timerLabel { 
        color: rgba(255, 255, 255, 0.9); 
        font-size: 0.7em; 
        font-weight: 800; 
        letter-spacing: 2px; 
        text-transform: uppercase; 
        margin-bottom: 8px; 
    }
    #blockContainer { 
        display: flex; 
        gap: 3px; 
        width: 100%; 
        flex-wrap: wrap; 
    }
    .time-block { 
        width: 8px; 
        height: 12px; 
        border-radius: 2px; 
        background: transparent; 
        transition: background 0.3s ease, box-shadow 0.3s ease; 
    }

    /* Violet Zone - Footer */
    .sidebar-violet-zone {
        padding-top: 10px;
        padding-bottom: 20px;
    }
</style>

<div class="wcc-sidebar" id="wccSidebar">
    <!-- Anchored Pointer for Glassmorphism Hover/Active state -->
    <div class="anchored-pointer"></div>
    <div class="wcc-brand">
        <div class="menu-toggle" onclick="toggleSidebar()">☰</div>
        <span class="brand-text">🚀 WCC</span>
    </div>
    
    <?php if(can('view_tickets')): ?>
    <a href="index.php" class="wcc-nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' || basename($_SERVER['PHP_SELF']) == 'active_tickets.php' ? 'active' : '' ?>">
        <span class="icon">🎫</span><span class="text">Tickets</span>
    </a>
    <?php endif; ?>

    <?php if(can('view_equipment')): ?>
    <a href="equipment_list.php" class="wcc-nav-link <?= basename($_SERVER['PHP_SELF']) == 'equipment_list.php' || basename($_SERVER['PHP_SELF']) == 'equipment.php' ? 'active' : '' ?>">
        <span class="icon">⚙️</span><span class="text">Equipment</span>
    </a>
    <?php endif; ?>

    <?php if(can('view_inventory')): ?>
    <a href="inventory.php" class="wcc-nav-link <?= basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : '' ?>">
        <span class="icon">📦</span><span class="text">Inventory</span>
    </a>
    <?php endif; ?>

    <?php if(can('view_history')): ?>
    <a href="history.php" class="wcc-nav-link <?= basename($_SERVER['PHP_SELF']) == 'history.php' ? 'active' : '' ?>">
        <span class="icon">🗄️</span><span class="text">The Vault</span>
    </a>
    <?php endif; ?>

    <?php if(can('manage_users')): ?>
    <a href="users_list.php" class="wcc-nav-link <?= basename($_SERVER['PHP_SELF']) == 'users_list.php' || basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">
        <span class="icon">👥</span><span class="text">Users</span>
    </a>
    <?php endif; ?>

    <?php if(can('view_vendors')): ?>
    <a href="vendors_list.php" class="wcc-nav-link <?= basename($_SERVER['PHP_SELF']) == 'vendors_list.php' || basename($_SERVER['PHP_SELF']) == 'vendors.php' ? 'active' : '' ?>">
        <span class="icon">🏢</span><span class="text">Vendors</span>
    </a>
    <?php endif; ?>

    <?php if(can('view_purchase_requests')): ?>
    <a href="purchase_requests.php" class="wcc-nav-link <?= basename($_SERVER['PHP_SELF']) == 'purchase_requests.php' ? 'active' : '' ?>">
        <span class="icon">🛒</span><span class="text">Purchase Requests</span>
    </a>
    <?php endif; ?>

    <?php if(can('view_work_orders')): ?>
    <a href="work_orders.php" class="wcc-nav-link <?= basename($_SERVER['PHP_SELF']) == 'work_orders.php' ? 'active' : '' ?>">
        <span class="icon">🛠️</span><span class="text">Work Orders</span>
    </a>
    <?php endif; ?>

    <?php if(can('view_statistics')): ?>
    <a href="statistics.php" class="wcc-nav-link <?= basename($_SERVER['PHP_SELF']) == 'statistics.php' ? 'active' : '' ?>">
        <span class="icon">📊</span><span class="text">Analytics</span>
    </a>
    <?php endif; ?>

    <!-- Red Zone: Timer -->
    <div class="sidebar-red-zone" id="industrialTimer">
        <div id="timerLabel">//SYS.LIFESPAN//</div>
        <div id="blockContainer"></div>
    </div>

    <!-- Violet Zone: Settings & Logout -->
    <div class="sidebar-violet-zone">
        <div style="padding: 0 15px 10px 15px; color: #94a3b8; font-size: 0.7em; font-weight: 800; letter-spacing: 1px; line-height: 1.6;">
            <?php
            $role_names = [1=>'OPERATOR', 2=>'TECHNICIAN', 3=>'SUPERVISOR', 4=>'ADMIN'];
            $role_name = $role_names[$_SESSION['role_level'] ?? 1] ?? 'USER';
            ?>
            <span style="color: #64748b;">ROLE:</span> <span style="color: #a78bfa;"><?= $role_name ?></span><br>
            <span style="color: #64748b;">USER:</span> <span style="color: var(--sidebar-accent);"><?= isset($_SESSION['username']) ? htmlspecialchars(strtoupper($_SESSION['username'])) : 'GUEST' ?></span>
        </div>
        <?php if(can('manage_settings')): ?>
        <a href="app_settings.php" class="wcc-nav-link <?= basename($_SERVER['PHP_SELF']) == 'app_settings.php' ? 'active' : '' ?>">
            <span class="icon">⚙️</span><span class="text">Settings</span>
        </a>
        <?php endif; ?>
        <a href="login.php?logout=true" class="wcc-nav-link" style="color: #fca5a5;">
            <span class="icon">🚪</span><span class="text">Logout</span>
        </a>
    </div>
</div>

<script src="timer.js"></script>
<script>
    function applySidebarState() {
        const sidebar = document.getElementById('wccSidebar');
        if (localStorage.getItem('sidebarState') === 'open') {
            sidebar.classList.add('open');
            document.body.style.marginLeft = '240px';
        } else {
            sidebar.classList.remove('open');
            document.body.style.marginLeft = '60px';
        }
    }

    function toggleSidebar() {
        const sidebar = document.getElementById('wccSidebar');
        if (sidebar.classList.contains('open')) {
            localStorage.setItem('sidebarState', 'closed');
        } else {
            localStorage.setItem('sidebarState', 'open');
        }
        applySidebarState();
    }

    // Apply on load
    document.addEventListener('DOMContentLoaded', applySidebarState);
</script>


<!-- SVG Filter for Glassmorphism Displacement -->
<svg width="0" height="0" style="position: absolute; pointer-events: none;">
  <filter id="displacementFilter" color-interpolation-filters="linearRGB" filterUnits="objectBoundingBox" primitiveUnits="userSpaceOnUse">
    <feDisplacementMap in="SourceGraphic" in2="SourceGraphic" scale="5" xChannelSelector="A" yChannelSelector="A" x="5" y="-5" width="100%" height="100%" result="displacementMap"/>
  </filter>
</svg>

<script>
    // Glassmorphism Hover Effect logic
    document.addEventListener('DOMContentLoaded', () => {
        const navLinks = document.querySelectorAll('.wcc-nav-link');
        let selectedLink = document.querySelector('.wcc-nav-link.active');
        
        const setAnchorOnSelected = () => {
            if (selectedLink) {
                selectedLink.style.anchorName = '--selected';
            }
        };
        
        setAnchorOnSelected();
        
        navLinks.forEach(link => {
            // Hover and focus
            const handleInteractionStart = () => {
                if (link !== selectedLink) {
                    if (selectedLink) {
                        selectedLink.style.anchorName = '';
                    }
                    link.style.anchorName = '--selected';
                }
            };
        
            link.addEventListener('mouseenter', handleInteractionStart);
            link.addEventListener('focus', handleInteractionStart);
        
            // Blur action
            const handleInteractionEnd = () => {
                if (link !== selectedLink) {
                    link.style.anchorName = '';
                    setAnchorOnSelected();
                }
            };
        
            link.addEventListener('mouseleave', handleInteractionEnd);
            link.addEventListener('blur', handleInteractionEnd);
        });
    });
</script>

<!-- Unified Accordion Logic -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', function(e) {
        const parentRow = e.target.closest('.parent-row');
        if (!parentRow) return;

        // Ignore clicks on actionable items inside the row
        if (e.target.closest('.nav-btn') || e.target.closest('.action-btn') || 
            e.target.closest('a') || e.target.closest('input') || 
            e.target.closest('select') || e.target.closest('button:not(.expander-btn)')) {
            return;
        }

        // Find the adjacent child row (skips hidden text/comment nodes)
        let nextRow = parentRow.nextElementSibling;
        while(nextRow && nextRow.nodeType !== 1) {
            nextRow = nextRow.nextElementSibling;
        }

        if (nextRow && nextRow.classList.contains('child-row')) {
            const isExpanded = nextRow.style.display === 'table-row';
            nextRow.style.display = isExpanded ? 'none' : 'table-row';
            if (isExpanded) {
                parentRow.classList.remove('is-expanded');
            } else {
                parentRow.classList.add('is-expanded');
            }
        }
    });
});
</script>