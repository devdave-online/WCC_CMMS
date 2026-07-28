<?php
echo "<h2>Deploying Ultimate Standardization Protocol...</h2>";

// 1. NUKE & PAVE GLOBAL.CSS
$css_file = 'c:/xampp/htdocs/css/global.css';
$css_c = file_get_contents($css_file);

// Strip out any previous accordion attempts
$css_c = preg_replace('/\/\* =========================================\s*Unified Accordion Standard.*$/is', '', $css_c);
$css_c = preg_replace('/\/\* =========================================\s*ULTIMATE UNIFIED ACCORDION.*$/is', '', $css_c);

$css = <<<EOD
/* =========================================
   ULTIMATE UNIFIED ACCORDION
   ========================================= */
.parent-row { cursor: pointer; transition: all 0.2s ease-in-out; border-left: 4px solid transparent; }
.parent-row:hover { background: rgba(255, 255, 255, 0.05); }

/* The Seamless Active Card State */
.parent-row.is-expanded { 
    background: var(--input-bg) !important; 
    border-left: 4px solid var(--text-accent) !important;
}
.parent-row.is-expanded td { border-bottom: none !important; }

/* The Rotating Chevron */
.row-arrow {
    display: inline-flex; align-items: center; justify-content: center;
    width: 20px; height: 20px; margin-right: 12px;
    color: var(--text-secondary); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    vertical-align: middle;
}
.parent-row.is-expanded .row-arrow { transform: rotate(90deg); color: var(--text-accent); }

/* The Child Container */
.child-row { display: none; background: transparent !important; }
.child-row.active { display: table-row; }
.child-row > td { padding: 0 !important; border-bottom: 1px solid var(--table-row-border) !important; }

.child-content { 
    margin: 0 15px 20px 15px !important; padding: 25px !important; 
    background: var(--input-bg) !important; 
    border: 1px solid var(--panel-border) !important; border-top: none !important;
    border-left: 4px solid var(--text-accent) !important; 
    border-radius: 0 0 12px 12px !important; 
    box-shadow: inset 0 5px 15px rgba(0,0,0,0.1) !important; 
    color: var(--text-primary) !important; 
}

/* BRUTE-FORCE OVERRIDE: Flatten all legacy inner boxes */
.layer-panel, .details-group {
    background: transparent !important;
    box-shadow: none !important;
    border: 1px solid var(--panel-border) !important;
    padding: 15px !important;
    border-radius: 8px !important;
}

/* Force-hide any surviving legacy expander buttons */
button.expander-btn { display: none !important; }
EOD;

file_put_contents($css_file, $css_c . "\n" . $css);
echo "✅ Global CSS forcefully overridden with !important tags.<br>";


// 2. CLEAN HTML & RESTORE GRIDS
$files = [
    'c:/xampp/htdocs/active_tickets.php', 'c:/xampp/htdocs/history.php', 'c:/xampp/htdocs/inventory.php', 
    'c:/xampp/htdocs/purchase_orders.php', 'c:/xampp/htdocs/purchase_requests.php', 
    'c:/xampp/htdocs/setup_vault_equipment.php', 'c:/xampp/htdocs/setup_vault_vendors.php', 
    'c:/xampp/htdocs/users.php', 'c:/xampp/htdocs/users_list.php', 'c:/xampp/htdocs/vendors.php',
    'c:/xampp/htdocs/equipment.php', 'c:/xampp/htdocs/equipment_list.php'
];

$grids = [
    'equipment.php' => '1fr 1fr 1fr',
    'setup_vault_equipment.php' => '1fr 1fr 1fr',
    'inventory.php' => '1fr 1fr 1fr 1fr',
    'vendors.php' => '1fr',
    'setup_vault_vendors.php' => '1fr',
    'users.php' => '1fr'
];

$arrow = '<span class="row-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></span>';

foreach($files as $file) {
    if (!file_exists($file)) continue;
    $c = file_get_contents($file);

    // 1. Aggressive Class Standardization
    $c = preg_replace('/class="([^"]*)main-row([^"]*)"/i', 'class="$1parent-row$2"', $c);
    $c = preg_replace('/class="([^"]*)details-row([^"]*)"/i', 'class="$1child-row$2"', $c);
    $c = preg_replace('/class="([^"]*)accordion-row([^"]*)"/i', 'class="$1child-row$2"', $c);
    $c = str_replace('class="details-content"', 'class="child-content"', $c);
    $c = str_replace('class="accordion-content"', 'class="child-content"', $c);

    // 2. Destructive HTML Cleanup (Nukes empty headers and expander columns)
    $c = preg_replace('/<th[^>]*>[\s]*<\/th>/is', '', $c);
    $c = preg_replace('/<td[^>]*>[\s\S]*?expander-btn[\s\S]*?<\/td>/is', '', $c);
    $c = preg_replace('/onclick=["\']toggle[A-Za-z]+\(.*?\)[;"\']/is', '', $c);

    // 3. Inject Chevron safely into the first column of the parent row
    $c = preg_replace('/<span class="row-arrow">[\s\S]*?<\/span>/is', '', $c);
    $c = preg_replace('/(<tr[^>]*parent-row[^>]*>\s*<td[^>]*>)/is', '$1' . $arrow, $c);

    // 4. Force Colspan to stretch across all columns dynamically
    $c = preg_replace('/(<tr[^>]*child-row[^>]*>[\s\S]*?<td[^>]*colspan=")\d+(")/is', '${1}12${2}', $c);

    // 5. Inject specific CSS Grid layouts directly into the container
    $base = basename($file);
    if (isset($grids[$base])) {
        $c = preg_replace('/class="child-content"\s+style="[^"]*"/is', 'class="child-content"', $c);
        $c = str_replace('class="child-content"', 'class="child-content" style="display: grid; grid-template-columns: ' . $grids[$base] . '; gap: 20px;"', $c);
    }

    file_put_contents($file, $c);
    echo "✅ Purged and unified layout in <strong>" . $base . "</strong><br>";
}

echo "<h3>💥 Ultimate Nuke Complete! Everything is flat, unified, and driven by global.css.</h3>";
?>