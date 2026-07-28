/* ==========================================================================
   WCC CMMS — Shared UI Behavior  v2.1
   --------------------------------------------------------------------------
   Loaded once (deferred) by inc/head.php, or by nav.php's fallback shell for
   pages not yet migrated. All public functions live on window because pages
   call them from inline on* attributes.

   Sections:
     1. Theme (light/dark toggle — the ONLY theming layer)
     2. Sidebar (collapse/expand, mobile drawer, active-link pointer)
     3. Accordion rows (delegated)
     4. Scroll-to-top
     5. Modals (wcc-modal system + About modal helpers)
     6. Toasts
     7. Guarded legacy shims (filterTable etc. — page-local copies win
        until each page migrates to the shared versions)
   ========================================================================== */
'use strict';

/* ==========================================================================
   0. CSRF (session-cookie mutations)
   window.WCC_CSRF / <meta name="csrf-token"> is set by inc/head.php.
   ========================================================================== */
function wccCsrfToken() {
    if (typeof window.WCC_CSRF === 'string' && window.WCC_CSRF) return window.WCC_CSRF;
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? (m.getAttribute('content') || '') : '';
}

/** Headers for JSON fetch mutations (includes X-CSRF-Token). */
function wccJsonHeaders(extra) {
    var h = {
        'Content-Type': 'application/json',
        'X-CSRF-Token': wccCsrfToken()
    };
    if (extra) {
        for (var k in extra) {
            if (Object.prototype.hasOwnProperty.call(extra, k)) h[k] = extra[k];
        }
    }
    return h;
}

/** Shallow-clone a payload object and attach csrf for JSON body consumers. */
function wccWithCsrf(payload) {
    var o = payload && typeof payload === 'object' ? Object.assign({}, payload) : {};
    o.csrf = wccCsrfToken();
    return o;
}

window.wccCsrfToken = wccCsrfToken;
window.wccJsonHeaders = wccJsonHeaders;
window.wccWithCsrf = wccWithCsrf;

/* ==========================================================================
   0b. HTML escape (DOM XSS defence for innerHTML sinks)
   ========================================================================== */
function escapeHtml(value) {
    if (value === null || value === undefined) return '';
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
window.escapeHtml = escapeHtml;

/* ==========================================================================
   0c. Table column filter text (drag-to-column search)
   --------------------------------------------------------------------------
   Badge cells like "CLASS A" break substring search: query "A" or "C" matches
   every row because those letters appear inside "CLASS". Pages should set
   data-search="|A|CRITICAL|CLASS A|" on the <td>; this helper:
     - exact token match for short queries (e.g. A, B, C)
     - substring match on the data-search / text for longer queries
   Falls back to visible textContent when data-search is absent.
   ========================================================================== */
function wccTableCellMatches(cell, query) {
    if (!cell) return false;
    var q = String(query == null ? '' : query).toUpperCase().trim();
    if (!q) return true;
    var ds = cell.getAttribute ? cell.getAttribute('data-search') : null;
    if (ds) {
        var hay = String(ds).toUpperCase();
        // Token form: |A|CRITICAL|CLASS A|
        if (hay.indexOf('|' + q + '|') !== -1) return true;
        // Multi-char / phrase (e.g. "CLASS A", "CRITICAL")
        if (q.length >= 2 && hay.indexOf(q) !== -1) return true;
        return false;
    }
    var txt = (cell.textContent || cell.innerText || '').toUpperCase();
    return txt.indexOf(q) !== -1;
}
window.wccTableCellMatches = wccTableCellMatches;

/* ==========================================================================
   1. THEME
   ========================================================================== */

/* One-time cleanup: the removed Theme Lab persisted custom colors here.
   Clearing it makes the stylesheet tokens authoritative again. */
try { localStorage.removeItem('wcc_theme_prefs'); } catch (e) { /* private mode */ }

function wccCurrentTheme() {
    return document.documentElement.classList.contains('light-theme') ? 'light' : 'dark';
}

function wccUpdateThemeToggleUI() {
    var isLight = wccCurrentTheme() === 'light';
    var btn = document.getElementById('wccThemeToggle');
    if (!btn) return;
    var icon = document.getElementById('wccThemeIcon');
    var label = document.getElementById('wccThemeLabel');
    if (icon) icon.textContent = isLight ? '☀️' : '🌙'; /* ☀️ / 🌙 */
    if (label) label.textContent = isLight ? 'Theme: Light' : 'Theme: Dark';
    btn.setAttribute('aria-pressed', isLight ? 'true' : 'false');
    btn.setAttribute('title', isLight ? 'Switch to dark theme' : 'Switch to light theme');
}

function toggleTheme() {
    var isLight = document.documentElement.classList.toggle('light-theme');
    if (document.body) document.body.classList.toggle('light-theme', isLight);
    try { localStorage.setItem('theme', isLight ? 'light' : 'dark'); } catch (e) {}
    wccUpdateThemeToggleUI();
    /* Charts and other listeners can re-skin on this event */
    window.dispatchEvent(new CustomEvent('wcc:themechange', { detail: { theme: isLight ? 'light' : 'dark' } }));
}

document.addEventListener('DOMContentLoaded', wccUpdateThemeToggleUI);

/* ==========================================================================
   2. SIDEBAR
   ========================================================================== */
function applySidebarState(isInitialLoad) {
    var sidebar = document.getElementById('wccSidebar');
    if (!sidebar) return;
    var backdrop = document.getElementById('sidebarBackdrop');

    if (window.innerWidth <= 768) {
        sidebar.classList.remove('open');
        if (backdrop) backdrop.classList.remove('show');
        document.body.style.marginLeft = '0px';
        return;
    }

    var shouldBeOpen = localStorage.getItem('sidebarState') === 'open';

    if (isInitialLoad) {
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
    } else {
        sidebar.classList.remove('open');
        document.body.style.marginLeft = '60px';
    }
    if (backdrop) backdrop.classList.remove('show');

    if (isInitialLoad) {
        void document.body.offsetHeight; /* reflow: snap without animating */
        document.body.style.transition = '';
        sidebar.style.transition = '';
    }
}

function toggleSidebar() {
    var sidebar = document.getElementById('wccSidebar');
    if (!sidebar) return;
    var backdrop = document.getElementById('sidebarBackdrop');
    var isMobile = window.innerWidth <= 768;

    if (sidebar.classList.contains('open')) {
        sidebar.classList.remove('open');
        document.body.style.marginLeft = isMobile ? '0px' : '60px';
        if (backdrop) backdrop.classList.remove('show');
        if (!isMobile) localStorage.setItem('sidebarState', 'closed');
    } else {
        sidebar.classList.add('open');
        document.body.style.marginLeft = isMobile ? '0px' : '240px';
        if (backdrop && isMobile) backdrop.classList.add('show');
        if (!isMobile) localStorage.setItem('sidebarState', 'open');
    }
    window.dispatchEvent(new Event('resize')); /* charts re-measure */
}

document.addEventListener('DOMContentLoaded', function () {
    applySidebarState(true);

    /* Re-evaluate when crossing the mobile breakpoint */
    var lastMobile = window.innerWidth <= 768;
    window.addEventListener('resize', function () {
        var nowMobile = window.innerWidth <= 768;
        if (nowMobile !== lastMobile) {
            lastMobile = nowMobile;
            applySidebarState(false);
        }
    });

    /* Active-link anchored pointer (hover preview, settles on active) */
    var navLinks = document.querySelectorAll('.wcc-nav-link');
    var selectedLink = document.querySelector('.wcc-nav-link.active');
    var setAnchorOnSelected = function () {
        if (selectedLink) selectedLink.style.anchorName = '--selected';
    };
    setAnchorOnSelected();
    navLinks.forEach(function (link) {
        var start = function () {
            if (link !== selectedLink) {
                if (selectedLink) selectedLink.style.anchorName = '';
                link.style.anchorName = '--selected';
            }
        };
        var end = function () {
            if (link !== selectedLink) {
                link.style.anchorName = '';
                setAnchorOnSelected();
            }
        };
        link.addEventListener('mouseenter', start);
        link.addEventListener('focus', start);
        link.addEventListener('mouseleave', end);
        link.addEventListener('blur', end);
    });
});

/* ==========================================================================
   3. ACCORDION ROWS (delegated — one implementation for every table)
   ========================================================================== */

/**
 * Freeze column proportions after a natural single-line layout pass.
 * - Parent cells stay one line (CSS nowrap).
 * - If content needs more room than the default 1400px box, expand
 *   .dashboard-container max-width by N px (no sidescroll inside the box).
 * - Prevents accordion open from reflowing columns.
 */
function wccLockAccordionTableColumns(table) {
    if (!table || !table.tHead) return;
    var ths = table.tHead.querySelectorAll('tr th');
    if (!ths.length) return;

    var dash = table.closest('.dashboard-container');
    var BASE_DASH_MAX = 1400;
    var SLACK_PX = 24; // padding buffer so longest cell fits cleanly

    // Measure with auto layout at content size (clear any previous lock)
    table.style.tableLayout = 'auto';
    table.style.width = 'max-content';
    table.style.minWidth = '';
    if (dash) {
        dash.style.maxWidth = '';
        dash.style.overflowX = '';
    }
    for (var i = 0; i < ths.length; i++) {
        ths[i].style.width = '';
        ths[i].style.minWidth = '';
    }

    var colCount = ths.length;
    var widths = [];
    var c;
    for (c = 0; c < colCount; c++) {
        widths[c] = ths[c].getBoundingClientRect().width;
    }

    // Also size to parent-row content (e.g. long codes in col 2)
    var rows = table.querySelectorAll('tbody tr.parent-row');
    var maxRows = Math.min(rows.length, 40);
    for (var r = 0; r < maxRows; r++) {
        var cells = rows[r].children;
        for (c = 0; c < colCount && c < cells.length; c++) {
            var cw = cells[c].scrollWidth;
            if (cw > widths[c]) widths[c] = cw;
        }
    }

    var total = 0;
    for (c = 0; c < colCount; c++) total += widths[c];
    if (total < 80) return; // not laid out yet

    table.style.minWidth = Math.ceil(total) + 'px';
    table.style.width = '100%';
    table.style.tableLayout = 'fixed';
    for (c = 0; c < colCount; c++) {
        ths[c].style.width = ((widths[c] / total) * 100).toFixed(3) + '%';
        ths[c].style.minWidth = Math.ceil(widths[c]) + 'px';
    }

    // Grow the dashboard box to fit content — never sidescroll inside it
    if (dash) {
        var cs = window.getComputedStyle(dash);
        var padX = (parseFloat(cs.paddingLeft) || 0) + (parseFloat(cs.paddingRight) || 0);
        var borderX = (parseFloat(cs.borderLeftWidth) || 0) + (parseFloat(cs.borderRightWidth) || 0);
        var needed = Math.ceil(total + padX + borderX + SLACK_PX);
        if (needed > BASE_DASH_MAX) {
            dash.style.maxWidth = needed + 'px';
        } else {
            dash.style.maxWidth = BASE_DASH_MAX + 'px';
        }
        dash.style.overflowX = 'hidden';
    }

    table.setAttribute('data-wcc-cols-locked', '1');
}

function wccLockAllAccordionTables() {
    document.querySelectorAll('table.data-table').forEach(function (table) {
        if (table.querySelector('tr.parent-row')) {
            wccLockAccordionTableColumns(table);
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    // Lock after first paint so natural column sizes are correct
    requestAnimationFrame(function () {
        wccLockAllAccordionTables();
        // Second pass after fonts/layout settle
        setTimeout(wccLockAllAccordionTables, 100);
    });

    var resizeTimer = null;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(wccLockAllAccordionTables, 150);
    }, { passive: true });

    document.addEventListener('click', function (e) {
        var parentRow = e.target.closest('.parent-row');
        if (!parentRow) return;
        if (e.target.closest('.nav-btn') || e.target.closest('.action-btn') ||
            e.target.closest('a') || e.target.closest('input') ||
            e.target.closest('select') || e.target.closest('button:not(.expander-btn)')) {
            return;
        }
        var nextRow = parentRow.nextElementSibling;
        while (nextRow && nextRow.nodeType !== 1) nextRow = nextRow.nextElementSibling;
        if (nextRow && nextRow.classList.contains('child-row')) {
            var isExpanded = nextRow.style.display === 'table-row';
            nextRow.style.display = isExpanded ? 'none' : 'table-row';
            parentRow.classList.toggle('is-expanded', !isExpanded);
        }
    });
});

/* ==========================================================================
   4. SCROLL-TO-TOP (styles live in global.css → #globalScrollTopBtn)
   ========================================================================== */
document.addEventListener('DOMContentLoaded', function () {
    var scrollBtn = document.createElement('button');
    scrollBtn.type = 'button';
    scrollBtn.textContent = '↑';
    scrollBtn.id = 'globalScrollTopBtn';
    scrollBtn.setAttribute('aria-label', 'Scroll to top');
    document.body.appendChild(scrollBtn);

    window.addEventListener('scroll', function () {
        scrollBtn.classList.toggle('visible', window.pageYOffset > 300);
    }, { passive: true });

    scrollBtn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});

/* ==========================================================================
   5. MODALS
   ========================================================================== */
function openWccModal(id) {
    var m = document.getElementById(id);
    if (!m) return;
    m.classList.add('open');
    /* Move focus to the first focusable control for keyboard users */
    var focusable = m.querySelector('[autofocus], button, [href], input, select, textarea');
    if (focusable) focusable.focus();
}
function closeWccModal(id) {
    var m = document.getElementById(id);
    if (m) m.classList.remove('open');
}

/* About modal — mechanics preserved exactly (display block/none) */
function openAboutModal() {
    var m = document.getElementById('wccAboutModal');
    if (m) m.style.display = 'block';
}
function closeAboutModal() {
    var m = document.getElementById('wccAboutModal');
    if (m) m.style.display = 'none';
}

/* Esc closes any open wcc-modal; backdrop click closes too */
document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('.wcc-modal.open').forEach(function (m) { m.classList.remove('open'); });
    var about = document.getElementById('wccAboutModal');
    if (about && about.style.display === 'block') about.style.display = 'none';
});
document.addEventListener('click', function (e) {
    if (e.target.classList && e.target.classList.contains('wcc-modal') && e.target.classList.contains('open')) {
        e.target.classList.remove('open');
    }
});

/* ==========================================================================
   6. TOASTS
   ========================================================================== */
function showToast(message, type, timeoutMs) {
    type = type || 'info';
    timeoutMs = timeoutMs || 4000;
    var container = document.getElementById('wccToasts');
    if (!container) {
        container = document.createElement('div');
        container.id = 'wccToasts';
        container.setAttribute('aria-live', 'polite');
        document.body.appendChild(container);
    }
    var icons = { success: '✅', error: '⛔', warning: '⚠️', info: 'ℹ️' };
    var toast = document.createElement('div');
    toast.className = 'wcc-toast wcc-toast-' + type;
    toast.setAttribute('role', 'status');
    var icon = document.createElement('span');
    icon.textContent = icons[type] || icons.info;
    icon.setAttribute('aria-hidden', 'true');
    var text = document.createElement('span');
    text.textContent = message;
    toast.appendChild(icon);
    toast.appendChild(text);
    container.appendChild(toast);
    setTimeout(function () {
        toast.classList.add('closing');
        setTimeout(function () { toast.remove(); }, 250);
    }, timeoutMs);
    return toast;
}

/* ==========================================================================
   7. GUARDED LEGACY SHIMS
   --------------------------------------------------------------------------
   Pages that still carry their own copies keep them (their inline scripts run
   before this deferred file, so the guards below skip). Migrated pages fall
   through to these shared versions.
   ========================================================================== */

/* Generic table text filter. Auto-detects the standard #ledgerSearch input
   and the page's data table; honors [data-wcc-table] override on the input. */
if (typeof window.filterTable === 'undefined') {
    window.filterTable = function () {
        var input = document.getElementById('ledgerSearch') || document.querySelector('[data-wcc-search]');
        if (!input) return;
        var table = null;
        var explicit = input.getAttribute('data-wcc-table');
        if (explicit) table = document.getElementById(explicit);
        if (!table) {
            var scope = input.closest('.dashboard-container') || document;
            table = scope.querySelector('table');
        }
        if (!table) return;
        var q = input.value.toUpperCase();
        var rows = table.querySelectorAll('tbody tr:not(.child-row)');
        rows.forEach(function (row) {
            var txt = (row.textContent || '').toUpperCase();
            var show = q === '' || txt.indexOf(q) > -1;
            row.style.display = show ? '' : 'none';
            var next = row.nextElementSibling;
            if (next && next.classList.contains('child-row') && !show) {
                next.style.display = 'none';
                row.classList.remove('is-expanded');
            }
        });
    };
}

if (typeof window.handleSearchInput === 'undefined') {
    window.handleSearchInput = function () { window.filterTable(); };
}

if (typeof window.toggleAccordion === 'undefined') {
    window.toggleAccordion = function (id, rowElement) {
        var detailsRow = document.getElementById('details-' + id);
        if (!detailsRow) return;
        var isOpen = detailsRow.classList.contains('open');
        document.querySelectorAll('.child-row').forEach(function (r) { r.classList.remove('open'); });
        document.querySelectorAll('.parent-row').forEach(function (r) { r.classList.remove('open'); });
        if (!isOpen) {
            detailsRow.classList.add('open');
            if (rowElement) rowElement.classList.add('open');
        }
    };
}
