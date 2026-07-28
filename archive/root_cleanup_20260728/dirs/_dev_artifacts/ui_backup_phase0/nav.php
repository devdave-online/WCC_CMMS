<?php

// nav.php - Side Navigation Bar

// 1. Bulletproof Session Starter
if (session_status() === PHP_SESSION_NONE) {
require_once __DIR__ . '/../../inc/session.php'; // hardened session bootstrap
}

// 2. Fail-safe include to ensure the can() function is always available
require_once 'rbac.php'; 
?>
<script>
    // 1. Determine active theme instantly (prefer server session for Phase 4 persist)
    let currentTheme = localStorage.getItem('theme') === 'light' ? 'light' : 'dark';
    // Server prefs used only to seed local if no local custom yet (lab changes in local take precedence for this browser)
    const serverPrefs = <?php echo json_encode($_SESSION['theme_prefs'] ?? null); ?>;
    const hasLocalPrefs = !!localStorage.getItem('wcc_theme_prefs');
    if (serverPrefs && (serverPrefs.dark || serverPrefs.light) && !hasLocalPrefs) {
        localStorage.setItem('wcc_theme_prefs', JSON.stringify(serverPrefs));
    }
    if (currentTheme === 'light') {
        document.documentElement.classList.add('light-theme');
        if (document.body) document.body.classList.add('light-theme');
    }

    // 2. Load the correct set of custom colors for the active theme
    let savedPrefs = localStorage.getItem('wcc_theme_prefs');
    if (savedPrefs) {
        try {
            const prefs = JSON.parse(savedPrefs);
            const activePrefs = prefs[currentTheme] || {}; // Pull only dark OR light settings
            for (const [key, value] of Object.entries(activePrefs)) {
                document.documentElement.style.setProperty(key, value);
                if (document.body) document.body.style.setProperty(key, value);
            }
        } catch (e) {
            console.error("Theme prefs parsing failed", e);
        }
    }

    // 3. Global toggle function (Now swaps custom variables too)
    function toggleTheme() {
        const isLight = document.documentElement.classList.toggle('light-theme');
        if (document.body) {
            if (isLight) {
                document.body.classList.add('light-theme');
            } else {
                document.body.classList.remove('light-theme');
            }
        }
        const newTheme = isLight ? 'light' : 'dark';
        localStorage.setItem('theme', newTheme);
        
        // Wipe ALL custom inline variables so they don't bleed into the other theme
        const keysToClear = ['--text-accent', '--custom-sidebar-accent', '--sidebar-bg', '--panel-bg', '--modal-bg', '--child-content-bg', '--bg-gradient', '--sidebar-text', '--text-primary'];
        keysToClear.forEach(k => {
          document.documentElement.style.removeProperty(k);
          if (document.body) document.body.style.removeProperty(k);
        });
        
        // Inject the custom variables for the newly selected theme (if they exist)
        const sp = localStorage.getItem('wcc_theme_prefs');
        if (sp) {
            try {
                const p = JSON.parse(sp);
                const ap = p[newTheme] || {};
                for (const [key, value] of Object.entries(ap)) {
                    document.documentElement.style.setProperty(key, value);
                    if (document.body) document.body.style.setProperty(key, value);
                }
            } catch (e) {}
        }
    }
</script>
<script>
  // Set sidebar margin as early as possible to prevent slide on page load
  (function() {
    try {
      var isOpen = localStorage.getItem('sidebarState') === 'open';
      var ml = isOpen ? '240px' : '60px';
      // Inject style to set margin immediately without transition
      var s = document.createElement('style');
      s.innerHTML = 'body { margin-left: ' + ml + ' !important; transition: none !important; }';
      document.head.appendChild(s);
      // Store for later restore
      window.__sidebarSnapStyle = s;
    } catch(e){}
  })();
</script>
<link rel="stylesheet" href="/css/global.css?v=<?= time() ?>">
<style>
    .wcc-sidebar {
        --sidebar-accent: var(--custom-sidebar-accent, #38bdf8);
        position: fixed;
        top: 0; left: 0; bottom: 0;
        width: 60px;
        background: var(--sidebar-bg, rgba(30, 41, 59, 0.85));
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
        width: 240px;
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
        padding: 0 10px;
        height: 42px;
        color: var(--sidebar-text, #e2e8f0);
        text-decoration: none;
        font-weight: 600;
        transition: background 0.2s, color 0.2s, border-left 0.2s;
        border-left: 3px solid transparent;
        margin: 2px 0;
        box-sizing: border-box;
    }
    .wcc-nav-link .icon {
        font-size: 1.3em;
        width: 30px;
        margin-right: 8px;
        flex-shrink: 0;
        text-align: center;
        transition: transform 0.2s;
    }
    .wcc-nav-link .text {
        opacity: 0;
        transition: opacity 0.2s ease;
        font-size: 0.95em;
        white-space: nowrap;
    }
    
    .wcc-sidebar.open .wcc-nav-link .text {
        opacity: 1;
        transition-delay: 0.1s;
    }

    /* Sidebar width animates smoothly only when user toggles it */
    .wcc-sidebar {
        transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
        transition: all 0.15s ease-out;   /* much faster + simpler to reduce twitch */
        pointer-events: none;
        overflow: hidden;
        /* Removed heavy displacementFilter + long spring to prevent jitter during sidebar/content shifts */
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
        position: relative;
        z-index: 1;
        transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), color 0.2s, background 0.2s, border-left 0.2s;
        box-sizing: border-box;
    }

    .wcc-nav-link:hover, .wcc-nav-link.active {
        color: var(--sidebar-accent);
        /* Reduced scale to minimize visual twitch/jitter on active items */
        transform: scale(1.01);
        z-index: 2;
    }
    .wcc-nav-link:hover .icon, .wcc-nav-link.active .icon {
        transform: scale(1.05);
    }

    /* Enterprise navigation sections - FIXED height so icons NEVER shift */
    .nav-section {
        font-size: 0.65em;
        font-weight: 700;
        letter-spacing: 1.2px;
        text-transform: uppercase;
        color: var(--text-secondary);
        opacity: 0.55;
        padding: 8px 0 4px 12px;
        margin-top: 6px;
        border-top: 1px solid rgba(255,255,255,0.06);
        box-sizing: border-box;
        height: 30px;   /* FIXED - this exact height is used in BOTH states */
    }

    /* Collapsed: same FIXED 30px height as when the header text is visible.
       Icons stay on the exact same vertical lines.
       Full-width thin grey line (spans the nav panel width like a box separator). */
    .wcc-sidebar:not(.open) .nav-section {
        height: 30px;
        padding: 0;
        margin-top: 6px;           /* identical margin */
        border: none;
        display: block;
        overflow: hidden;
        color: transparent;        /* completely hide "Operations" etc. text */
        position: relative;
    }

    .wcc-sidebar:not(.open) .nav-section::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 12px;                /* align with icon left padding */
        right: 12px;               /* full width inside the sidebar */
        height: 1px;
        background: rgba(255,255,255,0.1);
        transform: translateY(-50%);
    }

    /* Widget Containers for seamless collapse/expand transitions */
    .sidebar-widget-container {
        position: relative;
        width: 100%;
        display: flex;
        align-items: center;
        min-height: 48px;
        margin: 4px 0;
    }
    .collapsed-widget {
        position: absolute;
        left: 15px; /* Matches wcc-nav-link padding-left */
        width: 30px; /* Matches wcc-nav-link icon min-width */
        opacity: 1;
        transition: opacity 0.2s, transform 0.2s;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .wcc-sidebar.open .collapsed-widget {
        opacity: 0;
        transform: scale(0.5);
        pointer-events: none;
    }
    .expanded-widget {
        opacity: 0;
        transition: opacity 0.2s, visibility 0.2s;
        pointer-events: none;
        width: 240px; /* Lock to expanded width to prevent vertical layout stretch from flex-wrap */
        box-sizing: border-box;
        padding: 0 15px;
    }
    .wcc-sidebar:not(.open) .expanded-widget {
        position: absolute;
        visibility: hidden;
    }
    .wcc-sidebar.open .expanded-widget {
        opacity: 1;
        visibility: visible;
        transition-delay: 0.1s, 0s;
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

    /* SVG Mini Gauge for Collapsed State */
    .mini-gauge-svg {
        width: 28px; height: 28px;
        filter: drop-shadow(0 0 4px rgba(16, 185, 129, 0.4));
    }
    .mini-gauge-svg path {
        stroke-linecap: round; stroke-linejoin: round;
    }

    /* Mobile Overlay Sidebar */
    #mobileNavToggle {
        display: none;
        position: fixed;
        top: 15px;
        left: 15px;
        background: var(--sidebar-bg);
        color: var(--sidebar-accent);
        width: 48px;
        height: 48px;
        border-radius: 8px;
        z-index: 9998;
        align-items: center;
        justify-content: center;
        font-size: 1.5em;
        cursor: pointer;
        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }
    .sidebar-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(4px);
        z-index: 9999;
    }
    .sidebar-backdrop.show {
        display: block;
    }

    @media (max-width: 768px) {
        #mobileNavToggle {
            display: flex;
        }
        .wcc-sidebar {
            position: fixed !important;
            left: -260px !important;
            top: 0;
            height: 100vh;
            z-index: 10000;
            width: 260px !important;
            transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            box-shadow: none !important;
        }
        .wcc-sidebar.open {
            left: 0 !important;
            box-shadow: 4px 0 25px rgba(0,0,0,0.5) !important;
        }
        .wcc-sidebar .menu-toggle {
            display: none !important;
        }
        .wcc-sidebar .wcc-nav-link .text, .wcc-sidebar .expanded-widget {
            opacity: 1 !important;
            visibility: visible !important;
            pointer-events: auto !important;
        }
        .wcc-sidebar .collapsed-widget {
            display: none !important;
        }
    }
</style>

<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()"></div>
<div id="mobileNavToggle" onclick="toggleSidebar()">☰</div>

<div class="wcc-sidebar" id="wccSidebar">
    <div class="anchored-pointer"></div>
    <div class="wcc-brand">
        <div class="menu-toggle" onclick="toggleSidebar()">☰</div>
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
    <?php if(can('view_history') || can('view_vendors') || can('view_purchase_requests')): ?>
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

    <div class="wcc-sidebar-footer" style="margin-top: auto; display: flex; flex-direction: column; width: 100%; padding-bottom: 10px;">
        <div class="sidebar-widget-container">
            <div class="collapsed-widget" title="System Lifespan">
                <svg class="mini-gauge-svg" id="miniGaugeSvg" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2">
                    <!-- Background Arc -->
                    <path d="M4 15a9 9 0 1 1 16 0" stroke="rgba(255,255,255,0.1)"/>
                    <!-- Value Arc -->
                    <path id="miniGaugeValue" d="M4 15a9 9 0 0 1 9-9" stroke="#10b981" style="transition: stroke 0.3s;"/>
                    <!-- Needle -->
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
            <a href="/my_profile.php" class="collapsed-widget <?= basename($_SERVER['PHP_SELF']) == 'my_profile.php' ? 'active' : '' ?>" title="My Profile" style="text-decoration:none;display:flex;align-items:center;justify-content:center;">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#a78bfa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </a>
            <a href="/my_profile.php" class="expanded-widget <?= basename($_SERVER['PHP_SELF']) == 'my_profile.php' ? 'active' : '' ?>" style="text-decoration:none;display:block;padding:6px 8px;border-radius:8px;transition:background 0.2s;" onmouseover="this.style.background='rgba(167,139,250,0.12)'" onmouseout="this.style.background='transparent'">
                <div style="color: #94a3b8; font-size: 0.7em; font-weight: 800; letter-spacing: 1px; line-height: 1.6;">
                    <?php
                    $role_name = strtoupper(get_role_name((int)($_SESSION['role_level'] ?? 1)));
                    ?>
                    <span style="color: #64748b;">ROLE:</span> <span style="color: #a78bfa;"><?= $role_name ?></span><br>
                    <span style="color: #64748b;">USER:</span> <span style="color: var(--sidebar-accent);"><?= isset($_SESSION['badge_number']) ? htmlspecialchars($_SESSION['badge_number']) : (isset($_SESSION['username']) ? htmlspecialchars(strtoupper($_SESSION['username'])) : 'GUEST') ?></span>
                </div>
            </a>
        </div>

        <?php if(can('manage_settings')): ?>
        <div class="nav-section" style="margin-top:4px;">Administration</div>
        <a href="/_mgmt/admin_panel.php" class="wcc-nav-link <?= basename($_SERVER['PHP_SELF']) == 'admin_panel.php' ? 'active' : '' ?>">
            <span class="icon">🛡️</span><span class="text">Admin Panel</span>
        </a>
        <a href="/_mgmt/app_settings.php" class="wcc-nav-link <?= basename($_SERVER['PHP_SELF']) == 'app_settings.php' ? 'active' : '' ?>">
            <span class="icon">⚙️</span><span class="text">Settings</span>
        </a>
        <?php endif; ?>
        <a href="/login.php?logout=true" class="wcc-nav-link" style="color: #fca5a5;">
            <span class="icon">🚪</span><span class="text">Logout</span>
        </a>
    </div>
</div>

<!-- About WCC Welcome Modal Overlay (Included) -->
<?php include __DIR__ . '/_about_modal.php'; ?>

<!-- Global Confirmation Modal Overlay (Included) -->
<?php include __DIR__ . '/_confirm_modal.php'; ?>

<script src="/timer.js"></script>

<svg width="0" height="0" style="position: absolute; pointer-events: none;">
  <filter id="displacementFilter" color-interpolation-filters="linearRGB" filterUnits="objectBoundingBox" primitiveUnits="userSpaceOnUse">
    <feDisplacementMap in="SourceGraphic" in2="SourceGraphic" scale="5" xChannelSelector="A" yChannelSelector="A" x="5" y="-5" width="100%" height="100%" result="displacementMap"/>
  </filter>
</svg>

<script>
    // --- MISSING MODAL FUNCTIONS RESTORED ---
    function openAboutModal() {
        document.getElementById('wccAboutModal').style.display = 'block';
    }

    function closeAboutModal() {
        document.getElementById('wccAboutModal').style.display = 'none';
    }
    // ----------------------------------------

    function applySidebarState(isInitialLoad = false) {
        const sidebar = document.getElementById('wccSidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        
        if (window.innerWidth <= 768) {
            sidebar.classList.remove('open');
            if(backdrop) backdrop.classList.remove('show');
            document.body.style.marginLeft = '0px';
            return;
        }

        const shouldBeOpen = localStorage.getItem('sidebarState') === 'open';

        if (isInitialLoad) {
            // Snap instantly on page load (no animation)
            if (window.__sidebarSnapStyle) {
                window.__sidebarSnapStyle.remove();
                window.__sidebarSnapStyle = null;
            }
            document.body.style.transition = 'none';
            sidebar.style.transition = 'none';
        }

        if (shouldBeOpen) {
            sidebar.classList.add('open');
            document.body.style.marginLeft = '240px';
            if (backdrop && window.innerWidth <= 768) backdrop.classList.add('show');
        } else {
            sidebar.classList.remove('open');
            document.body.style.marginLeft = '60px';
            if (backdrop) backdrop.classList.remove('show');
        }

        if (isInitialLoad) {
            // Force reflow then restore transitions for manual toggles only
            void document.body.offsetHeight;
            document.body.style.transition = '';
            sidebar.style.transition = '';
        }
    }

    function toggleSidebar() {
        const sidebar = document.getElementById('wccSidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        if (sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            document.body.style.marginLeft = '60px';
            if(backdrop) backdrop.classList.remove('show');
            localStorage.setItem('sidebarState', 'closed');
        } else {
            sidebar.classList.add('open');
            document.body.style.marginLeft = '240px';
            if(backdrop && window.innerWidth <= 768) backdrop.classList.add('show');
            localStorage.setItem('sidebarState', 'open');
        }
        
        // Custom event for charts that need resizing
        window.dispatchEvent(new Event('resize'));
    }

    document.addEventListener('DOMContentLoaded', () => {
        applySidebarState(true);
    });

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

    document.addEventListener('DOMContentLoaded', () => {
        document.addEventListener('click', function(e) {
            const parentRow = e.target.closest('.parent-row');
            if (!parentRow) return;

            if (e.target.closest('.nav-btn') || e.target.closest('.action-btn') || 
                e.target.closest('a') || e.target.closest('input') || 
                e.target.closest('select') || e.target.closest('button:not(.expander-btn)')) {
                return;
            }

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
    // Scroll-to-top button logic
    document.addEventListener('DOMContentLoaded', () => {
        const scrollBtn = document.createElement('button');
        scrollBtn.innerHTML = '↑';
        scrollBtn.id = 'globalScrollTopBtn';
        scrollBtn.title = 'Scroll to top';
        scrollBtn.style.cssText = `
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--text-accent);
            color: var(--panel-bg);
            border: none;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.3s, transform 0.3s;
            opacity: 0;
            transform: translateY(20px);
        `;
        
        // Mobile responsiveness & Hover effect
        const style = document.createElement('style');
        style.innerHTML = `
            @media (max-width: 768px) {
                #globalScrollTopBtn {
                    bottom: 20px !important;
                    right: 20px !important;
                    width: 45px !important;
                    height: 45px !important;
                    font-size: 20px !important;
                }
            }
            #globalScrollTopBtn:hover {
                transform: translateY(-5px) !important;
                box-shadow: 0 6px 20px rgba(0,0,0,0.4) !important;
            }
        `;
        document.head.appendChild(style);
        document.body.appendChild(scrollBtn);

        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                if (scrollBtn.style.display === 'none') {
                    scrollBtn.style.display = 'flex';
                    // Trigger reflow
                    void scrollBtn.offsetWidth;
                }
                scrollBtn.style.opacity = '1';
                scrollBtn.style.transform = 'translateY(0)';
            } else {
                scrollBtn.style.opacity = '0';
                scrollBtn.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    if (window.pageYOffset <= 300) scrollBtn.style.display = 'none';
                }, 300);
            }
        });

        scrollBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    });
</script>