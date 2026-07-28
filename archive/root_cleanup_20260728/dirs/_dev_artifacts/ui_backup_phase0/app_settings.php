<?php
include __DIR__ . '/../auth.php';
require_perm('manage_settings');

// Enterprise centralized DB connection (highest quality, single source of truth)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    // Create lockout setting if it doesn't exist
    $stmt = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key = 'session_lockout_time'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("INSERT INTO app_settings (category, setting_key, setting_value) VALUES ('Security', 'session_lockout_time', '15')");
        $lockout_time = 15;
    } else {
        $lockout_time = $stmt->fetchColumn();
    }
    
    // Handle form submissions for Lockout
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['session_lockout_time'])) {
        $new_time = (int)$_POST['session_lockout_time'];
        if ($new_time > 0) {
            $stmt = $pdo->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_key = 'session_lockout_time'");
            $stmt->execute([$new_time]);
            $lockout_time = $new_time;
            header("Location: /_mgmt/app_settings.php?msg=lockout_updated&time=" . $new_time);
            exit;
        }
    }

    // Load KPI Targets
    $kpi_defaults = ['target_mttd' => '60', 'target_mttr' => '120', 'target_mtbf' => '48', 'plant_holidays' => '[]'];
    $kpi_settings = [];
    foreach ($kpi_defaults as $key => $default) {
        $stmt = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        if ($stmt->rowCount() == 0) {
            $pdo->prepare("INSERT INTO app_settings (category, setting_key, setting_value) VALUES ('KPI', ?, ?)")->execute([$key, $default]);
            $kpi_settings[$key] = $default;
        } else {
            $kpi_settings[$key] = $stmt->fetchColumn();
        }
    }

    // Handle KPI Targets Form Submission (Removed from here, now in admin_panel.php)
    
    // Handle Plant Holidays Form Submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_plant_holidays') {
        $holidays = $_POST['plant_holidays'] ?? '[]';
        if (json_decode($holidays) === null) {
            $holidays = '[]';
        }
        $pdo->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_key = 'plant_holidays'")->execute([$holidays]);
        header("Location: /_mgmt/app_settings.php?msg=holidays_updated");
        exit;
    }

    // Phase 4: Handle server save of theme prefs (from JS customization)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_theme_prefs' && isset($_SESSION['user_id'])) {
        $prefs = $_POST['theme_prefs'] ?? '{}';
        // Basic validate JSON
        if (json_decode($prefs) !== null) {
            $stmt = $pdo->prepare("UPDATE users SET theme_prefs_json = ? WHERE user_id = ?");
            $stmt->execute([$prefs, $_SESSION['user_id']]);
        }
        // No output, fire-and-forget
        exit;
    }
    
    // Handle Inventory Part Creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['part_name'])) {
        $name = $_POST['part_name'] ?? '';
        $code = $_POST['internal_code'] ?? '';
        $stock = (int)($_POST['stock_level'] ?? 0);
        $min = (int)($_POST['minimum_threshold'] ?? 5);
        $cost = (float)($_POST['cost_per_unit'] ?? 0);
        
        $vendor_sku = $_POST['vendor_sku'] ?? '';
        $standardized_desc = $_POST['standardized_desc'] ?? '';
        $oem_name = $_POST['oem_name'] ?? '';
        $oem_part_number = $_POST['oem_part_number'] ?? '';
        $supersession_sku = $_POST['supersession_sku'] ?? '';
        
        $maximum_stock = (int)($_POST['maximum_stock'] ?? 0);
        $standard_lead_time = (int)($_POST['standard_lead_time'] ?? 0);
        $expedited_lead_time = (int)($_POST['expedited_lead_time'] ?? 0);
        $moq = (int)($_POST['moq'] ?? 1);
        $uom = $_POST['uom'] ?? 'Each';
        $currency = $_POST['currency'] ?? 'USD';
        $price_expiration = !empty($_POST['price_expiration']) ? $_POST['price_expiration'] : null;
        
        $eol_date = !empty($_POST['eol_date']) ? $_POST['eol_date'] : null;
        $shelf_life_months = (int)($_POST['shelf_life_months'] ?? 0);
        $material_spec = $_POST['material_spec'] ?? '';
        $compliance_docs = $_POST['compliance_docs'] ?? '';
        
        $warehouse_id = !empty($_POST['warehouse_id']) ? (int)$_POST['warehouse_id'] : null;
        $aisle = $_POST['aisle'] ?? '';
        $rack = $_POST['rack'] ?? '';
        $shelf = $_POST['shelf'] ?? '';
        $bin_code = $_POST['bin_code'] ?? '';
        $auto_reorder = isset($_POST['auto_reorder']) ? 1 : 0;
        $primary_vendor_id = !empty($_POST['primary_vendor_id']) ? (int)$_POST['primary_vendor_id'] : null;
        $serial_number = $_POST['serial_number'] ?? '';
        $batch_lot = $_POST['batch_lot'] ?? '';
        $part_condition = $_POST['part_condition'] ?? 'New';
        $lifecycle_status = $_POST['lifecycle_status'] ?? 'Active';
        
        if ($name && $code) {
            $stmt = $pdo->prepare("INSERT INTO inventory_parts (
                part_name, internal_code, stock_level, minimum_threshold, cost_per_unit,
                vendor_sku, standardized_desc, oem_name, oem_part_number, supersession_sku,
                maximum_stock, standard_lead_time, expedited_lead_time, moq, uom, currency, price_expiration,
                eol_date, shelf_life_months, material_spec, compliance_docs,
                warehouse_id, aisle, rack, shelf, bin_code, auto_reorder, primary_vendor_id,
                serial_number, batch_lot, part_condition, lifecycle_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $name, $code, $stock, $min, $cost,
                $vendor_sku, $standardized_desc, $oem_name, $oem_part_number, $supersession_sku,
                $maximum_stock, $standard_lead_time, $expedited_lead_time, $moq, $uom, $currency, $price_expiration,
                $eol_date, $shelf_life_months, $material_spec, $compliance_docs,
                $warehouse_id, $aisle, $rack, $shelf, $bin_code, $auto_reorder, $primary_vendor_id,
                $serial_number, $batch_lot, $part_condition, $lifecycle_status
            ]);
            header("Location: /_mgmt/app_settings.php?msg=part_registered");
            exit;
        }
    }

    // Handle Workshop Creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_workshop') {
        $w_name = $_POST['workshop_name'] ?? '';
        $w_loc = $_POST['workshop_location'] ?? '';
        if ($w_name) {
            $pdo->prepare("INSERT INTO workshops (name, location) VALUES (?, ?)")->execute([$w_name, $w_loc]);
            header("Location: /_mgmt/app_settings.php");
            exit;
        }
    }

    // Handle Line Creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_line') {
        $l_name = $_POST['line_name'] ?? '';
        $w_id = (int)($_POST['workshop_id'] ?? 0);
        $l_prods = $_POST['products_built'] ?? '';
        if ($l_name && $w_id) {
            $pdo->prepare("INSERT INTO production_lines (workshop_id, name, products_built) VALUES (?, ?, ?)")->execute([$w_id, $l_name, $l_prods]);
            header("Location: /_mgmt/app_settings.php");
            exit;
        }
    }

    // Handle Delete Workshop
    if (isset($_GET['delete_workshop'])) {
        $pdo->prepare("DELETE FROM workshops WHERE workshop_id = ?")->execute([$_GET['delete_workshop']]);
        header("Location: /_mgmt/app_settings.php");
        exit;
    }

    // Handle Delete Line
    if (isset($_GET['delete_line'])) {
        $pdo->prepare("DELETE FROM production_lines WHERE line_id = ?")->execute([$_GET['delete_line']]);
        header("Location: /_mgmt/app_settings.php");
        exit;
    }

    // Fetch Workshops for Dropdowns and Management
    $workshops = $pdo->query("SELECT * FROM workshops ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $lines = $pdo->query("SELECT l.*, w.name as workshop_name FROM production_lines l JOIN workshops w ON l.workshop_id = w.workshop_id ORDER BY w.name ASC, l.name ASC")->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Data for PM Configurator
    $all_equipment = $pdo->query("SELECT equip_id as equipment_id, equip_name as name FROM equipment ORDER BY equip_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $all_parts = $pdo->query("SELECT part_id, part_name, internal_code FROM inventory_parts ORDER BY part_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $all_techs = $pdo->query("SELECT user_id, username, badge_number FROM users WHERE role_level >= 2 ORDER BY badge_number ASC")->fetchAll(PDO::FETCH_ASSOC);
    $pm_schedules = $pdo->query("
        SELECT p.*, e.equip_name as equipment_name, u.username as assigned_user
        FROM pm_schedules p
        LEFT JOIN equipment e ON p.equipment_id = e.equip_id
        LEFT JOIN users u ON p.assigned_to = u.user_id
        ORDER BY p.title ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Handle PM Schedule Creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_pm_schedule') {
        $title = $_POST['pm_title'] ?? '';
        $desc = $_POST['pm_desc'] ?? '';
        $eq_id = !empty($_POST['pm_equipment_id']) ? (int)$_POST['pm_equipment_id'] : null;
        $freq = !empty($_POST['pm_frequency']) ? (int)$_POST['pm_frequency'] : 30;
        $parts = isset($_POST['pm_parts']) ? json_encode($_POST['pm_parts']) : json_encode([]);
        $next_run = date('Y-m-d', strtotime("+$freq days"));

        if ($title) {
            $stmt = $pdo->prepare("INSERT INTO pm_schedules (title, description, equipment_id, assigned_to, parts_list, frequency_days, next_run_date) VALUES (?, ?, ?, NULL, ?, ?, ?)");
            $stmt->execute([$title, $desc, $eq_id, $parts, $freq, $next_run]);
            
            // Auto-generate the first Work Order
            $wo_stmt = $pdo->prepare("INSERT INTO work_orders (title, description, equipment_id, assigned_to, parts_list, scheduled_date, status) VALUES (?, ?, ?, NULL, ?, ?, 'Scheduled')");
            $wo_stmt->execute([$title, "Auto-generated from PM Schedule: $desc", $eq_id, $parts, $next_run]);

            header("Location: /_mgmt/app_settings.php?msg=pm_scheduled");
            exit;
        }
    }

    // Handle Ad-Hoc Work Order Creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_ad_hoc_wo') {
        $title = $_POST['title'] ?? '';
        $desc = $_POST['description'] ?? '';
        $assigned = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
        $date = $_POST['scheduled_date'] ?? '';
        $eq_id = !empty($_POST['equipment_id']) ? (int)$_POST['equipment_id'] : null;
        $parts = json_encode([]); // Parts are not configured here as per user request
        
        if ($title && $date) {
            $stmt = $pdo->prepare("INSERT INTO work_orders (title, description, equipment_id, assigned_to, parts_list, scheduled_date, status) VALUES (?, ?, ?, ?, ?, ?, 'Scheduled')");
            $stmt->execute([$title, $desc, $eq_id, $assigned, $parts, $date]);
            header("Location: /_mgmt/app_settings.php?msg=adhoc_scheduled");
            exit;
        }
    }

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>System Settings — WCC</title>
    <style>
        .setting-card {
            background: var(--panel-bg); 
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid var(--panel-border);
            padding: 30px;
            border-radius: 24px;
            box-shadow: 0 10px 30px 0 rgba(0, 0, 0, 0.5);
            text-align: center;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), background 0.3s ease, box-shadow 0.3s ease, border 0.3s ease;
            text-decoration: none;
            color: var(--text-primary);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 200px;
        }
        .setting-card:hover {
            transform: translateY(-8px);
            border: 1px solid var(--panel-border-top);
            box-shadow: 0 20px 40px 0 rgba(0,0,0,0.2);
        }
        .setting-card h3 { color: var(--text-accent); margin-top: 15px; margin-bottom: 5px; font-size: 1.4em; }
        .setting-card p { color: var(--text-secondary); font-size: 0.9em; margin: 0; }
        .grid-2x3 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 30px; }
        
        /* Modal CSS */
        .enterprise-modal { width: 900px !important; max-width: 95% !important; }
        .form-section { margin-top: 20px; padding-top: 10px; border-top: 1px solid var(--panel-border); }
        .form-section h3 { color: var(--text-accent); margin-bottom: 15px; font-size: 1.1em; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
        
        .color-block {
            display: flex; flex-direction: column; gap: 8px; 
            background: rgba(0,0,0,0.1); padding: 12px; 
            border-radius: 12px; border: 1px solid var(--panel-border);
            transition: border 0.2s;
        }
        .color-block:hover { border-color: var(--text-accent); }
        .color-block label { color: var(--text-secondary); margin: 0; font-weight: bold; font-size: 0.85em; text-align: center; }
        .color-block input[type="color"] { background: transparent; border: none; width: 100%; height: 40px; cursor: pointer; padding: 0; }
    </style>
</head>
<body><?php include __DIR__ . '/../nav.php'; ?>
<?php require_once __DIR__ . '/../rbac.php'; ?>

<div class="dashboard-container dash-box">
    <div class="header-flex" style="border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px;">
        <h2>⚙️ System Administration Console</h2>
        <div style="display:flex; align-items:center; gap:12px;">
            <a href="admin_panel.php" class="nav-btn">← Return to Admin Panel</a>
        </div>
    </div>

    <div style="margin-top: 40px; padding: 20px; background: var(--panel-bg); border-radius: 16px; border: 1px solid var(--panel-border); box-shadow: 0 10px 30px 0 rgba(0,0,0,0.2);">
        <h3 style="color: var(--text-accent); margin-top: 0;">Security Settings</h3>
        <form method="POST" style="display: flex; align-items: center; gap: 15px;">
            <label style="color:var(--text-secondary);">Session Lockout Timer (Minutes):</label>
            <input type="number" name="session_lockout_time" value="<?= htmlspecialchars($lockout_time) ?>" required min="1" max="1440" style="padding:10px; border-radius:6px; background:rgba(0,0,0,0.1); border:1px solid var(--panel-border); color:var(--text-primary); font-size:1.1em; width: 100px;">
            <button type="submit" class="nav-btn primary">Save Changes</button>
        </form>
        <p style="color:var(--text-secondary); font-size:0.85em; margin-top: 10px;">Determines how much idle time is allowed before the system forces a re-login.</p>

        <!-- Operational Calendar (Plant Holidays) -->
        <h3 style="color: var(--text-accent); margin-top: 40px; margin-bottom: 15px;">📅 Operational Calendar</h3>
        <form method="POST" style="background: rgba(0,0,0,0.1); padding: 20px; border-radius: 12px; border: 1px solid var(--panel-border);">
            <input type="hidden" name="action" value="save_plant_holidays">
            <div style="margin-bottom: 15px;">
                <label style="color:var(--text-secondary); display:block; margin-bottom: 5px;">Plant Holidays (JSON Array of YYYY-MM-DD strings)</label>
                <input type="text" name="plant_holidays" value="<?= htmlspecialchars($kpi_settings['plant_holidays'] ?? '[]') ?>" placeholder='e.g. ["2026-12-25", "2026-01-01"]' style="width: 100%; padding:10px; border-radius:6px; background:rgba(0,0,0,0.2); border:1px solid var(--panel-border); color:white; box-sizing: border-box; font-family: monospace;">
                <p style="color:var(--text-secondary); font-size: 0.85em; margin-top: 5px;">These dates are skipped entirely when calculating operational downtime (MDT) and MTTD.</p>
            </div>
            <button type="submit" class="nav-btn primary">Save Holidays</button>
        </form>

        <!-- THEME LAB LAUNCHER - Human, spacious, modal based -->
        <div style="background: var(--panel-bg); padding: 18px 20px; border-radius: 14px; border: 1px solid var(--panel-border); margin-top: 22px; display: flex; align-items: center; justify-content: space-between; gap: 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            <div>
                <div style="font-size: 1.2em; font-weight: 700; color: var(--text-accent); display: flex; align-items: center; gap: 8px;">
                    🎨 Theme Lab
                </div>
                <div style="font-size: 0.92em; color: var(--text-secondary); margin-top: 3px;">
                    Large live preview • Accessibility scoring • Beautiful animated presets • Full control per mode
                </div>
            </div>
            <button onclick="openThemeLabModal()" class="nav-btn primary" style="padding: 11px 24px; font-size: 0.97em; font-weight: 600;">
                Open Theme Lab →
            </button>
        </div>

        <!-- Old Theme Lab script removed - replaced with spacious modal version below -->
    </div>
</div>

<!-- THEME LAB MODAL - Human-centered redesign: spacious, readable, animated, great presets + preserved scoring tracer -->
<div id="themeLabModal" class="modal" onclick="if (event.target.id === 'themeLabModal') closeThemeLabModal();" style="display:none; z-index: 12000;">
  <div class="modal-content" onclick="event.stopImmediatePropagation();" style="width: 920px; max-width: 96%; padding: 22px 26px; border-radius: 18px; background: var(--modal-bg); color: var(--modal-text); box-shadow: 0 20px 50px rgba(0,0,0,0.45);">
    <span onclick="closeThemeLabModal()" style="float:right; font-size:28px; font-weight:300; cursor:pointer; color:#64748b; line-height:1;">&times;</span>
    
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
      <div>
        <div style="font-size:1.35em; font-weight:700; color:var(--text-accent);">🎨 Theme Lab</div>
        <div style="font-size:0.9em; color:var(--text-secondary);">Design for humans • Live preview • Real accessibility scores • 6 tokens</div>
      </div>
      <button onclick="themeLabToggleMode()" class="nav-btn primary" id="themeLabToggleBtn" style="padding:9px 18px; font-size:0.95em;">Toggle Light / Dark</button>
    </div>

    <!-- Large Human Preview -->
    <div style="margin: 10px 0 14px;">
      <div style="font-size:0.85em; font-weight:600; color:var(--text-secondary); margin-bottom:5px; letter-spacing:0.3px;">LIVE PREVIEW — see your choices in context</div>
      <div id="tlPreview" style="height:210px; border-radius:14px; border:1px solid var(--panel-border); overflow:hidden; display:flex; box-shadow: 0 6px 20px rgba(0,0,0,0.2); position:relative; transition: box-shadow .3s;">
        <!-- Sidebar -->
        <div id="tlSidebar" style="width:68px; padding:8px 6px; display:flex; flex-direction:column; gap:5px; font-size:9.5px;">
          <div style="font-weight:700; margin-bottom:2px; opacity:0.85;">MENU</div>
          <div style="padding:3px 4px; border-radius:4px;">📋 Tickets</div>
          <div style="padding:3px 4px; border-radius:4px;">⚙️ Equipment</div>
          <div style="padding:3px 4px; border-radius:4px;">📦 Inventory</div>
          <div style="margin-top:auto; height:22px; border-radius:5px; display:flex; align-items:center; justify-content:center; font-size:9px; font-weight:600; color:white;" id="tlAccentBar">Accent</div>
        </div>

        <!-- Canvas / Main -->
        <div id="tlCanvas" style="flex:1; padding:10px 12px; display:flex; flex-direction:column; gap:8px; font-size:10.5px; line-height:1.35; position:relative;">
          <div style="font-weight:600; opacity:0.9;">Dashboard Overview</div>
          <div id="tlBodyText" style="opacity:0.85; font-size:10px; line-height:1.4;">
            The quick brown fox jumps over the lazy dog.<br>
            Your factory data looks healthy today.
          </div>

          <!-- Sample Panel -->
          <div id="tlPanel" style="flex:1; border-radius:8px; padding:8px 10px; display:flex; flex-direction:column; gap:4px; box-shadow: inset 0 1px 0 rgba(255,255,255,0.06);">
            <div style="font-size:9.5px; font-weight:600;">Panel Sample</div>
            <div style="display:flex; gap:6px; align-items:center;">
              <div id="tlSampleBtn" style="background: var(--text-accent); color:#fff; padding:3px 9px; border-radius:5px; font-size:9px; font-weight:600; cursor:default; display:inline-block;">Take Action</div>
              <div style="font-size:9px; opacity:0.7;">Secondary text</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Accessibility Scoring Tracer (kept & promoted) -->
    <div style="background: var(--input-bg); border:1px solid var(--panel-border); border-radius:10px; padding:11px 13px; margin-bottom:14px; font-size:0.92em;">
      <div style="font-weight:700; color:var(--text-secondary); margin-bottom:5px; font-size:0.95em;">ACCESSIBILITY SCORES (current mode)</div>
      <div id="tlScores" style="font-size:0.9em; line-height:1.45;"></div>
      <div style="margin-top:5px; font-size:0.78em; color:var(--text-secondary);">
        Accent contrast on the surfaces you control. Body text uses fixed high-contrast simulation per mode (no direct text-color picker).
      </div>
    </div>

    <!-- Beautiful Pre-established Themes -->
    <div style="margin-bottom:12px;">
      <div style="font-weight:600; font-size:0.9em; margin-bottom:6px; color:var(--text-secondary);">✨ Good-looking Quick Starts (click to apply)</div>
      <div style="display:flex; gap:18px;">
        <!-- Dark presets -->
        <div>
          <div style="font-size:0.82em; font-weight:600; margin-bottom:4px;">🌙 Dark</div>
          <div style="display:flex; gap:8px; align-items:center;">
            <div onclick="applyPreset('dark','ocean')" class="tl-swatch" style="width:34px;height:34px;background:#0f172a;border:2px solid #334155;border-radius:8px;cursor:pointer;" title="Ocean — deep &amp; professional"></div>
            <div onclick="applyPreset('dark','slate')" class="tl-swatch" style="width:34px;height:34px;background:#1e293b;border:2px solid #475569;border-radius:8px;cursor:pointer;" title="Slate"></div>
            <div onclick="applyPreset('dark','midnight')" class="tl-swatch" style="width:34px;height:34px;background:#020617;border:2px solid #1e293b;border-radius:8px;cursor:pointer;" title="Midnight"></div>
          </div>
          <div style="font-size:0.7em; margin-top:2px; color:#64748b;">Ocean • Slate • Midnight</div>
        </div>

        <!-- Light presets -->
        <div>
          <div style="font-size:0.82em; font-weight:600; margin-bottom:4px;">☀️ Light</div>
          <div style="display:flex; gap:8px; align-items:center;">
            <div onclick="applyPreset('light','paper')" class="tl-swatch" style="width:34px;height:34px;background:#f8fafc;border:2px solid #cbd5e1;border-radius:8px;cursor:pointer;" title="Paper — clean &amp; bright"></div>
            <div onclick="applyPreset('light','ice')" class="tl-swatch" style="width:34px;height:34px;background:#e0e7ff;border:2px solid #a5b4fc;border-radius:8px;cursor:pointer;" title="Ice"></div>
            <div onclick="applyPreset('light','warm')" class="tl-swatch" style="width:34px;height:34px;background:#fef3c7;border:2px solid #fcd34d;border-radius:8px;cursor:pointer;" title="Warm"></div>
          </div>
          <div style="font-size:0.7em; margin-top:2px; color:#64748b;">Paper • Ice • Warm</div>
        </div>
      </div>
    </div>

    <!-- Spacious 2x2 Controls per mode -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:18px;">
      <!-- Dark -->
      <div>
        <div style="font-weight:600; margin-bottom:6px; font-size:0.95em;">🌙 Dark Mode</div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
          <div style="background:rgba(255,255,255,0.04); padding:8px 10px; border-radius:8px; border:1px solid var(--panel-border);">
            <div style="font-size:0.82em; margin-bottom:3px;">Accent</div>
            <input type="color" id="tlDarkAccent" style="width:100%; height:32px; padding:0; border:none; background:transparent;">
            <input type="text" id="tlDarkAccentHex" style="width:100%; margin-top:3px; font-size:0.82em; padding:3px 5px; border:1px solid var(--panel-border); border-radius:4px; background:var(--input-bg);">
          </div>
          <div style="background:rgba(255,255,255,0.04); padding:8px 10px; border-radius:8px; border:1px solid var(--panel-border);">
            <div style="font-size:0.82em; margin-bottom:3px;">Sidebar</div>
            <input type="color" id="tlDarkSidebar" style="width:100%; height:32px; padding:0; border:none; background:transparent;">
            <input type="text" id="tlDarkSidebarHex" style="width:100%; margin-top:3px; font-size:0.82em; padding:3px 5px; border:1px solid var(--panel-border); border-radius:4px; background:var(--input-bg);">
          </div>
          <div style="background:rgba(255,255,255,0.04); padding:8px 10px; border-radius:8px; border:1px solid var(--panel-border);">
            <div style="font-size:0.82em; margin-bottom:3px;">Panels</div>
            <input type="color" id="tlDarkSurface" style="width:100%; height:32px; padding:0; border:none; background:transparent;">
            <input type="text" id="tlDarkSurfaceHex" style="width:100%; margin-top:3px; font-size:0.82em; padding:3px 5px; border:1px solid var(--panel-border); border-radius:4px; background:var(--input-bg);">
          </div>
          <div style="background:rgba(255,255,255,0.04); padding:8px 10px; border-radius:8px; border:1px solid var(--panel-border);">
            <div style="font-size:0.82em; margin-bottom:3px;">Canvas</div>
            <input type="color" id="tlDarkCanvas" style="width:100%; height:32px; padding:0; border:none; background:transparent;">
            <input type="text" id="tlDarkCanvasHex" style="width:100%; margin-top:3px; font-size:0.82em; padding:3px 5px; border:1px solid var(--panel-border); border-radius:4px; background:var(--input-bg);">
          </div>
          <div style="background:rgba(255,255,255,0.04); padding:8px 10px; border-radius:8px; border:1px solid var(--panel-border);">
            <div style="font-size:0.82em; margin-bottom:3px;">Sidebar Text</div>
            <input type="color" id="tlDarkSidebarText" style="width:100%; height:32px; padding:0; border:none; background:transparent;">
            <input type="text" id="tlDarkSidebarTextHex" style="width:100%; margin-top:3px; font-size:0.82em; padding:3px 5px; border:1px solid var(--panel-border); border-radius:4px; background:var(--input-bg);">
          </div>
          <div style="background:rgba(255,255,255,0.04); padding:8px 10px; border-radius:8px; border:1px solid var(--panel-border);">
            <div style="font-size:0.82em; margin-bottom:3px;">Content Text</div>
            <input type="color" id="tlDarkTextPrimary" style="width:100%; height:32px; padding:0; border:none; background:transparent;">
            <input type="text" id="tlDarkTextPrimaryHex" style="width:100%; margin-top:3px; font-size:0.82em; padding:3px 5px; border:1px solid var(--panel-border); border-radius:4px; background:var(--input-bg);">
          </div>
        </div>
      </div>

      <!-- Light -->
      <div>
        <div style="font-weight:600; margin-bottom:6px; font-size:0.95em;">☀️ Light Mode</div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
          <div style="background:rgba(255,255,255,0.04); padding:8px 10px; border-radius:8px; border:1px solid var(--panel-border);">
            <div style="font-size:0.82em; margin-bottom:3px;">Accent</div>
            <input type="color" id="tlLightAccent" style="width:100%; height:32px; padding:0; border:none; background:transparent;">
            <input type="text" id="tlLightAccentHex" style="width:100%; margin-top:3px; font-size:0.82em; padding:3px 5px; border:1px solid var(--panel-border); border-radius:4px; background:var(--input-bg);">
          </div>
          <div style="background:rgba(255,255,255,0.04); padding:8px 10px; border-radius:8px; border:1px solid var(--panel-border);">
            <div style="font-size:0.82em; margin-bottom:3px;">Sidebar</div>
            <input type="color" id="tlLightSidebar" style="width:100%; height:32px; padding:0; border:none; background:transparent;">
            <input type="text" id="tlLightSidebarHex" style="width:100%; margin-top:3px; font-size:0.82em; padding:3px 5px; border:1px solid var(--panel-border); border-radius:4px; background:var(--input-bg);">
          </div>
          <div style="background:rgba(255,255,255,0.04); padding:8px 10px; border-radius:8px; border:1px solid var(--panel-border);">
            <div style="font-size:0.82em; margin-bottom:3px;">Panels</div>
            <input type="color" id="tlLightSurface" style="width:100%; height:32px; padding:0; border:none; background:transparent;">
            <input type="text" id="tlLightSurfaceHex" style="width:100%; margin-top:3px; font-size:0.82em; padding:3px 5px; border:1px solid var(--panel-border); border-radius:4px; background:var(--input-bg);">
          </div>
          <div style="background:rgba(255,255,255,0.04); padding:8px 10px; border-radius:8px; border:1px solid var(--panel-border);">
            <div style="font-size:0.82em; margin-bottom:3px;">Canvas</div>
            <input type="color" id="tlLightCanvas" style="width:100%; height:32px; padding:0; border:none; background:transparent;">
            <input type="text" id="tlLightCanvasHex" style="width:100%; margin-top:3px; font-size:0.82em; padding:3px 5px; border:1px solid var(--panel-border); border-radius:4px; background:var(--input-bg);">
          </div>
          <div style="background:rgba(255,255,255,0.04); padding:8px 10px; border-radius:8px; border:1px solid var(--panel-border);">
            <div style="font-size:0.82em; margin-bottom:3px;">Sidebar Text</div>
            <input type="color" id="tlLightSidebarText" style="width:100%; height:32px; padding:0; border:none; background:transparent;">
            <input type="text" id="tlLightSidebarTextHex" style="width:100%; margin-top:3px; font-size:0.82em; padding:3px 5px; border:1px solid var(--panel-border); border-radius:4px; background:var(--input-bg);">
          </div>
          <div style="background:rgba(255,255,255,0.04); padding:8px 10px; border-radius:8px; border:1px solid var(--panel-border);">
            <div style="font-size:0.82em; margin-bottom:3px;">Content Text</div>
            <input type="color" id="tlLightTextPrimary" style="width:100%; height:32px; padding:0; border:none; background:transparent;">
            <input type="text" id="tlLightTextPrimaryHex" style="width:100%; margin-top:3px; font-size:0.82em; padding:3px 5px; border:1px solid var(--panel-border); border-radius:4px; background:var(--input-bg);">
          </div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div style="margin-top:16px; display:flex; gap:10px; justify-content:flex-end; align-items:center;">
      <button onclick="themeLabApplyCurrent()" class="nav-btn primary" style="padding:8px 16px; font-size:0.9em;">Apply to Current Page</button>
      <button onclick="themeLabSaveAll()" class="nav-btn" style="padding:8px 16px; font-size:0.9em; border:1px solid var(--text-accent); color:var(--text-accent);">Save to My Account</button>
      <button onclick="themeLabReset()" style="padding:8px 14px; background:#ef4444; color:white; border:none; border-radius:6px; font-size:0.9em; font-weight:500;">Reset to Original</button>
      <button onclick="closeThemeLabModal()" class="nav-btn" style="padding:8px 14px; font-size:0.9em;">Close</button>
    </div>
  </div>
</div>

<style>
  .tl-swatch { transition: transform 0.15s cubic-bezier(0.2,0.8,0.2,1), box-shadow 0.15s; }
  .tl-swatch:hover { transform: scale(1.12); box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.35); }
  #tlPreview, #tlSidebar, #tlCanvas, #tlPanel, #tlSampleBtn, #tlAccentBar { transition: background 0.28s ease, color 0.28s ease, background-color 0.28s ease, box-shadow 0.2s; }
  #themeLabModal .modal-content { animation: tlModalPop 0.22s cubic-bezier(0.2, 0.9, 0.3, 1); }
  @keyframes tlModalPop { from { opacity: 0; transform: scale(0.96) translateY(12px); } to { opacity: 1; transform: scale(1) translateY(0); } }
</style>

<script>
  // ======================================================
  // NEW HUMAN THEME LAB (modal, spacious, animated, scoring preserved)
  // ======================================================
  (function() {
    const originalDefaults = {
      dark:  { 
        '--text-accent': '#38bdf8', 
        '--sidebar-bg': '#1e293b', 
        '--panel-bg': 'linear-gradient(135deg, rgba(30, 41, 59, 0.75), rgba(15, 23, 42, 0.95))', 
        '--bg-gradient': 'linear-gradient(135deg, #0f172a, #1e293b, #0f172a, #020617)',
        '--sidebar-text': '#e2e8f0',
        '--text-primary': '#f8fafc'
      },
      light: { 
        '--text-accent': '#1e3a8a', 
        '--sidebar-bg': '#f1f5f9', 
        '--panel-bg': 'linear-gradient(135deg, rgba(255, 255, 255, 0.65), rgba(189, 194, 255, 0.35))', 
        '--bg-gradient': 'linear-gradient(135deg, #e0e7ff, #BDC2FF, #f1f5f9, #e0e7ff)',
        '--sidebar-text': '#1e293b',
        '--text-primary': '#0f172a'
      }
    };

    let prefs = JSON.parse(localStorage.getItem('wcc_theme_prefs') || '{}');
    if (!prefs.dark) prefs = { dark: {}, light: {} };

    // Migrate legacy short keys to full CSS var names so nav.php can apply them globally
    const migrateMap = {
      'accent': '--text-accent',
      'sidebar': '--sidebar-bg',
      'surface': '--panel-bg',
      'canvas': '--bg-gradient',
      'sidebarText': '--sidebar-text',
      'textPrimary': '--text-primary'
    };
    ['dark', 'light'].forEach(mode => {
      if (prefs[mode]) {
        Object.keys(migrateMap).forEach(oldKey => {
          const newKey = migrateMap[oldKey];
          if (prefs[mode][oldKey] !== undefined && prefs[mode][newKey] === undefined) {
            prefs[mode][newKey] = prefs[mode][oldKey];
            delete prefs[mode][oldKey];
          }
        });
      }
    });
    if (Object.keys(prefs.dark || {}).length || Object.keys(prefs.light || {}).length) {
      localStorage.setItem('wcc_theme_prefs', JSON.stringify(prefs));
    }

    let currentMode = document.documentElement.classList.contains('light-theme') ? 'light' : 'dark';

    // Helpers
    function extractColor(v) {
      if (!v) return '#000000';
      const m = String(v).match(/#[0-9a-fA-F]{6}/);
      return m ? m[0] : (v.startsWith('#') ? v.substring(0,7) : '#000000');
    }

    function getLuminance(hex) {
      hex = extractColor(hex);
      let r = parseInt(hex.substr(1,2),16) / 255;
      let g = parseInt(hex.substr(3,2),16) / 255;
      let b = parseInt(hex.substr(5,2),16) / 255;
      r = r <= 0.03928 ? r / 12.92 : Math.pow((r + 0.055) / 1.055, 2.4);
      g = g <= 0.03928 ? g / 12.92 : Math.pow((g + 0.055) / 1.055, 2.4);
      b = b <= 0.03928 ? b / 12.92 : Math.pow((b + 0.055) / 1.055, 2.4);
      return 0.2126 * r + 0.7152 * g + 0.0722 * b;
    }

    function getContrast(c1, c2) {
      const l1 = getLuminance(c1);
      const l2 = getLuminance(c2);
      const lighter = Math.max(l1, l2);
      const darker = Math.min(l1, l2);
      return (lighter + 0.05) / (darker + 0.05);
    }

    function updateScores() {
      const d = prefs[currentMode] || originalDefaults[currentMode];
      const scoresEl = document.getElementById('tlScores');
      if (!scoresEl) return;

      const accent = d['--text-accent'];
      const panel = d['--panel-bg'];
      const sidebar = d['--sidebar-bg'];
      const canvas = d['--bg-gradient'];
      const bodyText = d['--text-primary'] || (currentMode === 'dark' ? '#f8fafc' : '#0f172a');

      const pairs = [
        { label: 'Accent on Canvas', ratio: getContrast(accent, canvas) },
        { label: 'Accent on Panels', ratio: getContrast(accent, panel) },
        { label: 'Accent on Sidebar', ratio: getContrast(accent, sidebar) },
        { label: 'Body (sim) on Canvas', ratio: getContrast(bodyText, canvas) },
        { label: 'Body (sim) on Panels', ratio: getContrast(bodyText, panel) }
      ];

      let html = '';
      pairs.forEach(p => {
        const r = p.ratio.toFixed(2);
        let badge = (r >= 7) ? '<span style="color:#10b981;font-weight:600;">AAA</span>' :
                    (r >= 4.5) ? '<span style="color:#3b82f6;font-weight:600;">AA</span>' :
                    (r >= 3) ? '<span style="color:#f59e0b;font-weight:600;">AA*</span>' :
                               '<span style="color:#ef4444;font-weight:600;">Fail</span>';
        html += `<div style="margin:2px 0; display:flex; gap:6px; align-items:center;">
          <strong style="min-width:138px;">${p.label}</strong> 
          <span style="font-variant-numeric:tabular-nums;">${r}:1</span> ${badge}
        </div>`;
      });
      scoresEl.innerHTML = html;
    }

    function updatePreview() {
      const d = prefs[currentMode] || originalDefaults[currentMode];

      const sidebarEl = document.getElementById('tlSidebar');
      const canvasEl = document.getElementById('tlCanvas');
      const panelEl = document.getElementById('tlPanel');
      const accentBar = document.getElementById('tlAccentBar');
      const sampleBtn = document.getElementById('tlSampleBtn');
      const bodyTextEl = document.getElementById('tlBodyText');

      if (sidebarEl) {
        sidebarEl.style.background = d['--sidebar-bg'];
        sidebarEl.style.color = d['--sidebar-text'] || (currentMode === 'dark' ? '#e2e8f0' : '#1e293b');
      }
      if (canvasEl) canvasEl.style.background = d['--bg-gradient'];
      if (panelEl) panelEl.style.background = d['--panel-bg'];

      const bodySim = d['--text-primary'] || (currentMode === 'dark' ? '#f8fafc' : '#0f172a');
      if (bodyTextEl) bodyTextEl.style.color = bodySim;

      if (accentBar) accentBar.style.background = d['--text-accent'];
      if (sampleBtn) sampleBtn.style.background = d['--text-accent'];

      updateScores();
    }

    function applyToPage(mode) {
      const d = prefs[mode] || originalDefaults[mode];
      const root = document.documentElement;
      const body = document.body;
      for (const [cssVar, val] of Object.entries(d)) {
        root.style.setProperty(cssVar, val);
        if (body) body.style.setProperty(cssVar, val);
      }
    }

    function loadPickers() {
      const fieldToVar = {
        'Accent': '--text-accent',
        'Sidebar': '--sidebar-bg',
        'Surface': '--panel-bg',
        'Canvas': '--bg-gradient',
        'SidebarText': '--sidebar-text',
        'TextPrimary': '--text-primary'
      };
      ['dark','light'].forEach(m => {
        const pre = 'tl' + (m === 'dark' ? 'Dark' : 'Light');
        const vals = prefs[m] || originalDefaults[m];

        Object.keys(fieldToVar).forEach(field => {
          const varKey = fieldToVar[field];
          const col = document.getElementById(pre + field);
          const hex = document.getElementById(pre + field + 'Hex');
          const val = vals[varKey] || '';

          if (col) col.value = extractColor(val);
          if (hex) hex.value = extractColor(val);
        });
      });
    }

    function bindPickers() {
      const fieldToVar = {
        'Accent': '--text-accent',
        'Sidebar': '--sidebar-bg',
        'Surface': '--panel-bg',
        'Canvas': '--bg-gradient',
        'SidebarText': '--sidebar-text',
        'TextPrimary': '--text-primary'
      };
      ['dark','light'].forEach(m => {
        const pre = 'tl' + (m === 'dark' ? 'Dark' : 'Light');

        Object.keys(fieldToVar).forEach(field => {
          const varKey = fieldToVar[field];
          const col = document.getElementById(pre + field);
          const hex = document.getElementById(pre + field + 'Hex');

          if (!col || !hex) return;

          const update = (val) => {
            if (!prefs[m]) prefs[m] = {};
            let storeVal = val;
            if (varKey === '--sidebar-bg') storeVal = val + 'D9';   // subtle translucency like before
            prefs[m][varKey] = storeVal;
            localStorage.setItem('wcc_theme_prefs', JSON.stringify(prefs));
            if (m === currentMode) {
              applyToPage(m);
              updatePreview();
            }
          };

          col.addEventListener('input', e => {
            hex.value = e.target.value;
            update(e.target.value);
          });

          hex.addEventListener('input', e => {
            const v = e.target.value;
            col.value = v;
            update(v);
          });
        });
      });
    }

    window.applyPreset = function(mode, name) {
      const nicePresets = {
        dark: {
          ocean: { '--text-accent': '#38bdf8', '--sidebar-bg': '#1e293b', '--panel-bg': 'linear-gradient(135deg, rgba(30, 41, 59, 0.75), rgba(15, 23, 42, 0.95))', '--bg-gradient': 'linear-gradient(135deg, #0f172a, #1e293b, #0f172a, #020617)', '--sidebar-text': '#e2e8f0', '--text-primary': '#f8fafc' },
          slate: { '--text-accent': '#64748b', '--sidebar-bg': '#1e293b', '--panel-bg': 'linear-gradient(135deg, rgba(51, 65, 85, 0.75), rgba(30, 41, 59, 0.9))', '--bg-gradient': 'linear-gradient(135deg, #0f172a, #1e293b, #020617)', '--sidebar-text': '#e2e8f0', '--text-primary': '#f8fafc' },
          midnight: { '--text-accent': '#a5b4fc', '--sidebar-bg': '#020617', '--panel-bg': 'linear-gradient(135deg, rgba(30, 41, 59, 0.85), rgba(15, 23, 42, 0.95))', '--bg-gradient': 'linear-gradient(135deg, #020617, #0f172a, #020617)', '--sidebar-text': '#cbd5e1', '--text-primary': '#e2e8f0' }
        },
        light: {
          paper: { '--text-accent': '#1e3a8a', '--sidebar-bg': '#f1f5f9', '--panel-bg': 'linear-gradient(135deg, rgba(255,255,255,0.7), rgba(241,245,249,0.5))', '--bg-gradient': 'linear-gradient(135deg, #e0e7ff, #BDC2FF, #f1f5f9, #e0e7ff)', '--sidebar-text': '#1e293b', '--text-primary': '#0f172a' },
          ice: { '--text-accent': '#3b82f6', '--sidebar-bg': '#e0e7ff', '--panel-bg': 'linear-gradient(135deg, rgba(248,250,252,0.75), rgba(224,231,255,0.4))', '--bg-gradient': 'linear-gradient(135deg, #e0e7ff, #c7d2fe, #e0e7ff)', '--sidebar-text': '#1e293b', '--text-primary': '#0f172a' },
          warm: { '--text-accent': '#b45309', '--sidebar-bg': '#fef3c7', '--panel-bg': 'linear-gradient(135deg, rgba(255,251,235,0.75), rgba(254,243,199,0.45))', '--bg-gradient': 'linear-gradient(135deg, #fefce8, #fef3c7, #fefce8)', '--sidebar-text': '#334155', '--text-primary': '#1e293b' }
        }
      };

      prefs[mode] = nicePresets[mode][name] || originalDefaults[mode];
      localStorage.setItem('wcc_theme_prefs', JSON.stringify(prefs));
      loadPickers();
      if (mode === currentMode) {
        applyToPage(mode);
        updatePreview();
      }
    };

    window.themeLabToggleMode = function() {
      toggleTheme();
      currentMode = document.documentElement.classList.contains('light-theme') ? 'light' : 'dark';
      updatePreview();
      const btn = document.getElementById('themeLabToggleBtn');
      if (btn) btn.textContent = currentMode === 'light' ? 'Switch to Dark' : 'Toggle Light / Dark';
    };

    window.themeLabApplyCurrent = function() {
      localStorage.setItem('wcc_theme_prefs', JSON.stringify(prefs));
      applyToPage(currentMode);
      // nice little visual confirmation on main page
      const orig = document.body.style.boxShadow || '';
      document.body.style.boxShadow = '0 0 0 3px rgba(56,189,248,0.25)';
      setTimeout(() => { document.body.style.boxShadow = orig; }, 650);
    };

    window.themeLabSaveAll = function() {
      localStorage.setItem('wcc_theme_prefs', JSON.stringify(prefs));
      fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=save_theme_prefs&theme_prefs=' + encodeURIComponent(JSON.stringify(prefs))
      }).then(() => {
        const msg = document.createElement('span');
        msg.textContent = ' ✓ Saved to account';
        msg.style.cssText = 'margin-left:10px;color:#10b981;font-size:0.85em;';
        const actions = document.querySelector('#themeLabModal .modal-content');
        if (actions) actions.appendChild(msg);
        setTimeout(() => msg.remove(), 1600);
      }).catch(() => {});
    };
    window.themeLabReset = function() {
      openWccConfirm('Reset all custom colors? This will restore the original beautiful gradients.', function() {
        prefs = { dark: {}, light: {} };
        localStorage.removeItem('wcc_theme_prefs');

        // Clear inline customs so the gorgeous CSS gradients from global.css return
        const keys = ['--text-accent', '--sidebar-bg', '--panel-bg', '--modal-bg', '--bg-gradient', '--sidebar-text', '--text-primary'];
        keys.forEach(k => {
          document.documentElement.style.removeProperty(k);
          if (document.body) document.body.style.removeProperty(k);
        });

        loadPickers();
        updatePreview();

        // Also re-apply the base original look for the current mode preview only
        const orig = originalDefaults[currentMode];
        const root = document.documentElement;
        root.style.setProperty('--text-accent', orig.accent);
        // leave the rest to CSS
      }, 'Reset Colors');
    };

    window.openThemeLabModal = function() {
      const modal = document.getElementById('themeLabModal');
      if (!modal) return;
      modal.style.display = 'block';

      // sync current real mode
      currentMode = document.documentElement.classList.contains('light-theme') ? 'light' : 'dark';
      loadPickers();
      updatePreview();

      const btn = document.getElementById('themeLabToggleBtn');
      if (btn) btn.textContent = currentMode === 'light' ? 'Switch to Dark' : 'Toggle Light / Dark';
    };

    window.closeThemeLabModal = function() {
      const modal = document.getElementById('themeLabModal');
      if (modal) modal.style.display = 'none';
    };

    // Init pickers + preview for when modal is open
    // (bind happens on first open via the functions)
    document.addEventListener('DOMContentLoaded', () => {
      // Pre-bind once elements exist (they live inside modal)
      setTimeout(() => {
        try { bindPickers(); } catch(e){}
      }, 120);
    });

    // Make sure preview updates if someone toggles outside
    const origToggle = window.toggleTheme;
    if (typeof origToggle === 'function') {
      window.toggleTheme = function() {
        origToggle();
        if (document.getElementById('themeLabModal') && document.getElementById('themeLabModal').style.display === 'block') {
          currentMode = document.documentElement.classList.contains('light-theme') ? 'light' : 'dark';
          updatePreview();
        }
      };
    }
  })();
</script>

<!-- The Modal -->
<div id="addModal" class="modal">
  <div class="modal-content enterprise-modal">
    <span class="close" onclick="document.getElementById('addModal').style.display='none'">&times;</span>
    <h2>Register Spare Part</h2>
    <form method="POST">
        <div class="form-section" style="border-top:none; margin-top:0; padding-top:0;">
            <h3>Part DNA (The Base)</h3>
            <div class="grid-2">
                <div><label>Part Name *</label><input type="text" name="part_name" required></div>
                <div><label>Internal Code (SKU) *</label><input type="text" name="internal_code" required></div>
                <div><label>Vendor SKU</label><input type="text" name="vendor_sku" placeholder="Exact part number"></div>
                <div><label>Standardized Description</label><input type="text" name="standardized_desc" placeholder="e.g. Bearing, Roller, 50mm"></div>
                <div><label>OEM Name</label><input type="text" name="oem_name"></div>
                <div><label>OEM Part Number</label><input type="text" name="oem_part_number"></div>
                <div><label>Supersession SKU</label><input type="text" name="supersession_sku"></div>
            </div>
        </div>

        <div class="form-section">
            <h3>Logistics & Procurement (The Supply Chain)</h3>
            <div class="grid-3">
                <div><label>Current Stock</label><input type="number" name="stock_level" value="0" required></div>
                <div><label>Min Threshold</label><input type="number" name="minimum_threshold" value="5" required></div>
                <div><label>Max Stock</label><input type="number" name="maximum_stock" value="0"></div>
                <div><label>Unit Price</label><input type="number" step="0.01" name="cost_per_unit" value="0.00"></div>
                <div><label>Currency</label><input type="text" name="currency" value="USD"></div>
                <div><label>Price Exp. Date</label><input type="date" name="price_expiration"></div>
                <div><label>Std. Lead Time (days)</label><input type="number" name="standard_lead_time" value="0"></div>
                <div><label>Exp. Lead Time (days)</label><input type="number" name="expedited_lead_time" value="0"></div>
                <div><label>Min Order Qty (MOQ)</label><input type="number" name="moq" value="1"></div>
                <div><label>Unit of Measure</label><input type="text" name="uom" value="Each"></div>
                <div><label>Primary Vendor ID</label><input type="number" name="primary_vendor_id"></div>
                <div style="display:flex; align-items:flex-end; padding-bottom:10px;"><label style="display:flex; align-items:center; gap:8px; cursor:pointer; margin-top:0;"><input type="checkbox" name="auto_reorder" style="width:auto; margin-top:0;"> Auto Reorder Enabled</label></div>
            </div>
        </div>

        <div class="form-section">
            <h3>Storage Coordinates & Tracking</h3>
            <div class="grid-3">
                <div><label>Warehouse ID</label><input type="number" name="warehouse_id"></div>
                <div><label>Aisle</label><input type="text" name="aisle"></div>
                <div><label>Rack</label><input type="text" name="rack"></div>
                <div><label>Shelf</label><input type="text" name="shelf"></div>
                <div><label>Bin Code</label><input type="text" name="bin_code"></div>
                <div><label>Serial Number</label><input type="text" name="serial_number"></div>
                <div><label>Batch / Lot</label><input type="text" name="batch_lot"></div>
            </div>
        </div>

        <div class="form-section">
            <h3>Compliance, Condition & Lifecycle</h3>
            <div class="grid-3">
                <div>
                    <label>Condition</label>
                    <select name="part_condition">
                        <option value="New">New</option>
                        <option value="Refurbished">Refurbished</option>
                        <option value="Defective">Defective</option>
                        <option value="Awaiting QA">Awaiting QA</option>
                    </select>
                </div>
                <div>
                    <label>Lifecycle Status</label>
                    <select name="lifecycle_status">
                        <option value="Active">Active</option>
                        <option value="Phasing Out">Phasing Out</option>
                        <option value="Obsolete">Obsolete</option>
                    </select>
                </div>
                <div><label>End-of-Life (EOL) Date</label><input type="date" name="eol_date"></div>
                <div><label>Shelf-Life Max (Months)</label><input type="number" name="shelf_life_months" value="0"></div>
                <div><label>Material Specification</label><input type="text" name="material_spec" placeholder="e.g. 316 Stainless Steel"></div>
                <div><label>Compliance Doc Links</label><input type="text" name="compliance_docs" placeholder="Links to MSDS, CoC, etc."></div>
            </div>
        </div>
        
        <button type="submit" class="btn" style="margin-top:30px;">Save Enterprise Part</button>
    </form>
  </div>
</div>

<!-- Production Lines Modal -->
<div id="linesModal" class="modal">
  <div class="modal-content enterprise-modal">
    <span class="close" onclick="document.getElementById('linesModal').style.display='none'">&times;</span>
    <h2>🏭 Production Workshops & Lines Configuration</h2>
    
    <div class="grid-2" style="margin-top: 20px;">
        <!-- Add Workshop Form -->
        <div class="form-section" style="border-top:none; margin-top:0; padding-top:0;">
            <h3>Create New Workshop</h3>
            <form method="POST" style="background: rgba(0,0,0,0.1); padding: 15px; border-radius: 8px; border: 1px solid var(--panel-border);">
                <input type="hidden" name="action" value="add_workshop">
                <label>Workshop Name *</label>
                <input type="text" name="workshop_name" placeholder="e.g. Assembly Plant Alpha" required style="width: 100%; box-sizing: border-box; margin-bottom: 10px;">
                <label>Location Details</label>
                <input type="text" name="workshop_location" placeholder="e.g. Building 2, Floor 1" style="width: 100%; box-sizing: border-box; margin-bottom: 15px;">
                <button type="submit" class="btn" style="width: 100%;">+ Add Workshop</button>
            </form>
            
            <h4 style="margin-top: 20px; color: var(--text-accent);">Existing Workshops</h4>
            <table class="data-table" style="font-size: 0.9em;">
                <thead><tr><th>Workshop Name</th><th>Location</th><th>Act</th></tr></thead>
                <tbody>
                    <?php foreach($workshops as $w): ?>
                    <tr>
                        <td><?= htmlspecialchars($w['name']) ?></td>
                        <td><?= htmlspecialchars($w['location']) ?></td>
                        <td><a href="#" onclick="openWccConfirm('Deleting a workshop will delete ALL its lines too! Proceed?', '?delete_workshop=<?= $w['workshop_id'] ?>', 'Delete Workshop'); return false;" class="nav-btn" style="background: #ef4444; color: white; padding: 2px 5px; text-decoration: none; border: none; font-size: 0.8em;">X</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Line Form -->
        <div class="form-section" style="border-top:none; margin-top:0; padding-top:0;">
            <h3>Allocate New Line to Workshop</h3>
            <form method="POST" style="background: rgba(0,0,0,0.1); padding: 15px; border-radius: 8px; border: 1px solid var(--panel-border);">
                <input type="hidden" name="action" value="add_line">
                <label>Select Parent Workshop *</label>
                <select name="workshop_id" required style="width: 100%; box-sizing: border-box; margin-bottom: 10px;">
                    <option value="">-- Choose Workshop --</option>
                    <?php foreach($workshops as $w): ?>
                        <option value="<?= $w['workshop_id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Line Name *</label>
                <input type="text" name="line_name" placeholder="e.g. Conveyor System B" required style="width: 100%; box-sizing: border-box; margin-bottom: 10px;">
                <label>Products Built Here</label>
                <input type="text" name="products_built" placeholder="e.g. Engine Blocks" style="width: 100%; box-sizing: border-box; margin-bottom: 15px;">
                <button type="submit" class="btn" style="width: 100%; background: #ca8a04;">+ Allocate Line</button>
            </form>

            <h4 style="margin-top: 20px; color: var(--text-accent);">Existing Lines</h4>
            <div style="max-height: 200px; overflow-y: auto;">
                <table class="data-table" style="font-size: 0.9em;">
                    <thead><tr><th>Workshop</th><th>Line Name</th><th>Act</th></tr></thead>
                    <tbody>
                        <?php foreach($lines as $l): ?>
                        <tr>
                            <td><?= htmlspecialchars($l['workshop_name']) ?></td>
                            <td><?= htmlspecialchars($l['name']) ?></td>
                            <td><a href="#" onclick="openWccConfirm('Delete this line?', '?delete_line=<?= $l['line_id'] ?>', 'Delete Line'); return false;" class="nav-btn" style="background: #ef4444; color: white; padding: 2px 5px; text-decoration: none; border: none; font-size: 0.8em;">X</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
  </div>
</div>

<!-- PM Configurator Modal -->
<div id="pmModal" class="modal">
  <div class="modal-content enterprise-modal">
    <span class="close" onclick="document.getElementById('pmModal').style.display='none'">&times;</span>
    <h2>🗓️ Preventative Maintenance Configuration</h2>
    
    <div class="grid-2" style="margin-top: 20px;">
        <div class="form-section" style="border-top:none; margin-top:0; padding-top:0;">
            <h3>Create PM Schedule</h3>
            <form method="POST" style="background: rgba(0,0,0,0.1); padding: 15px; border-radius: 8px; border: 1px solid var(--panel-border);">
                <input type="hidden" name="action" value="add_pm_schedule">
                
                <label>Schedule Title *</label>
                <input type="text" name="pm_title" placeholder="e.g. Monthly Lubrication" required style="width: 100%; box-sizing: border-box; margin-bottom: 10px;">
                
                <label>Description / Instructions</label>
                <textarea name="pm_desc" rows="2" style="width: 100%; box-sizing: border-box; margin-bottom: 10px;"></textarea>
                
                <label>Target Equipment *</label>
                <select name="pm_equipment_id" required style="width: 100%; box-sizing: border-box; margin-bottom: 10px;">
                    <option value="">-- Select Equipment --</option>
                    <?php foreach($all_equipment as $eq): ?>
                        <option value="<?= $eq['equipment_id'] ?>"><?= htmlspecialchars($eq['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label>Frequency (Days)</label>
                <input type="number" name="pm_frequency" min="1" required style="width: 100%; box-sizing: border-box; margin-bottom: 10px;">

                <label>Required Parts (Search & Select)</label>
                <input type="text" id="partSearch" placeholder="Search parts..." onkeyup="filterParts()" style="width: 100%; box-sizing: border-box; margin-bottom: 5px; padding: 8px; border-radius: 4px; background: rgba(0,0,0,0.1); color: white; border: 1px solid rgba(255,255,255,0.2);">
                <div id="partsList" style="max-height: 150px; overflow-y: auto; background: rgba(0,0,0,0.2); border: 1px solid var(--panel-border); padding: 5px; border-radius: 4px; margin-bottom: 10px;">
                    <?php foreach($all_parts as $p): ?>
                        <label style="display: block; cursor: pointer; padding: 4px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <input type="checkbox" name="pm_parts[]" value="<?= $p['part_id'] ?>" style="margin-right: 8px;">
                            <span class="part-name"><?= htmlspecialchars($p['part_name'] . ' (' . $p['internal_code'] . ')') ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="btn" style="width: 100%; margin-top: 15px; background: #10b981; color: black; font-weight: 800; font-size: 1.1em;">+ Save PM Schedule</button>
            </form>
        </div>

        <div class="form-section" style="border-top:none; margin-top:0; padding-top:0;">
            <h3>Active PM Schedules</h3>
            <div style="max-height: 450px; overflow-y: auto;">
                <table class="data-table" style="font-size: 0.85em;">
                    <thead><tr><th>Title</th><th>Equipment</th><th>Freq</th><th>Tech</th></tr></thead>
                    <tbody>
                        <?php foreach($pm_schedules as $pm): ?>
                        <tr>
                            <td><?= htmlspecialchars($pm['title']) ?></td>
                            <td><?= htmlspecialchars($pm['equipment_name'] ?? 'Unknown') ?></td>
                            <td><?= $pm['frequency_days'] ?>d</td>
                            <td><?= htmlspecialchars($pm['assigned_user'] ?? 'Unassigned') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
  </div>
</div>

<!-- Ad-Hoc WO Modal -->
<div id="addWOModal" class="modal">
  <div class="modal-content enterprise-modal" style="width: 700px; max-width: 95%;">
    <span class="close" onclick="document.getElementById('addWOModal').style.display='none'">&times;</span>
    <h2>📝 Schedule Ad-Hoc Work Order</h2>
    <form method="POST">
        <input type="hidden" name="action" value="add_ad_hoc_wo">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top:20px;">
            <div class="form-section" style="border-top:none; margin:0; padding:0;">
                <label>Title *</label>
                <input type="text" name="title" required style="width: 100%; box-sizing: border-box; margin-bottom: 10px;">
                
                <label>Description</label>
                <textarea name="description" rows="3" style="width: 100%; box-sizing: border-box; margin-bottom: 10px;"></textarea>

                <label>Workshop</label>
                <select id="wo_workshop" onchange="updateWOLine()" style="width: 100%; box-sizing: border-box; margin-bottom: 10px;">
                    <option value="">-- All Workshops --</option>
                    <?php foreach($workshops as $w): ?>
                        <option value="<?= $w['workshop_id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label>Production Line</label>
                <select id="wo_line" onchange="updateWOEquipment()" style="width: 100%; box-sizing: border-box; margin-bottom: 10px;">
                    <option value="">-- All Lines --</option>
                </select>
            </div>
            <div class="form-section" style="border-top:none; margin:0; padding:0;">
                <label>Target Equipment *</label>
                <select name="equipment_id" id="wo_equipment" required style="width: 100%; box-sizing: border-box; margin-bottom: 10px;">
                    <option value="">-- Select Equipment --</option>
                    <?php foreach($all_equipment as $eq): ?>
                        <option value="<?= $eq['equipment_id'] ?>"><?= htmlspecialchars($eq['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label>Assigned To</label>
                <select name="assigned_to" style="width: 100%; box-sizing: border-box; margin-bottom: 10px;">
                    <option value="">-- Unassigned --</option>
                    <?php foreach($all_techs as $t): ?>
                        <option value="<?= $t['user_id'] ?>"><?= htmlspecialchars($t['badge_number'] ?? 'IB-?????') ?> (<?= htmlspecialchars($t['username']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                
                <label>Scheduled Date *</label>
                <input type="date" name="scheduled_date" required style="width: 100%; box-sizing: border-box; margin-bottom: 10px;">
            </div>
        </div>
        
        <button type="submit" class="btn" style="width: 100%; margin-top: 20px; background: #38bdf8; color: black; font-size: 1.1em; font-weight: bold;">+ Create Work Order</button>
    </form>
  </div>
</div>

<script>
    function filterParts() {
        let filter = document.getElementById('partSearch').value.toLowerCase();
        let labels = document.getElementById('partsList').getElementsByTagName('label');
        for (let i = 0; i < labels.length; i++) {
            let text = labels[i].querySelector('.part-name').innerText.toLowerCase();
            labels[i].style.display = text.includes(filter) ? 'block' : 'none';
        }
    }

    // Cascading Dropdowns logic for Ad-Hoc WO Modal
    let woProductionLines = <?= json_encode($lines) ?>;
    let woEquipmentData = <?= json_encode($all_equipment) ?>;

    function updateWOLine() {
        const w_id = document.getElementById('wo_workshop').value;
        const lineSelect = document.getElementById('wo_line');
        lineSelect.innerHTML = '<option value="">-- All Lines --</option>';
        
        if (w_id) {
            woProductionLines.filter(l => String(l.workshop_id) === String(w_id)).forEach(l => {
                lineSelect.innerHTML += `<option value="${l.line_id}">${l.name}</option>`;
            });
        }
        updateWOEquipment();
    }

    function updateWOEquipment() {
        const w_id = document.getElementById('wo_workshop').value;
        const l_id = document.getElementById('wo_line');
        const l_val = l_id ? l_id.value : '';
        const equipSelect = document.getElementById('wo_equipment');
        
        equipSelect.innerHTML = '<option value="">-- Select Equipment --</option>';
        
        woEquipmentData.filter(item => {
            if (w_id && String(item.workshop_id) !== String(w_id)) return false;
            if (l_val && String(item.line_id) !== String(l_val)) return false;
            return true;
        }).forEach(eq => {
            equipSelect.innerHTML += `<option value="${eq.equipment_id}">${eq.name}</option>`;
        });
    }
    <?php if(isset($_GET['msg'])): ?>
    window.addEventListener('DOMContentLoaded', () => {
        if (typeof openWccAlert === 'function') {
            <?php if($_GET['msg'] === 'lockout_updated'): ?>
            let newTime = <?= isset($_GET['time']) ? (int)$_GET['time'] : 0 ?>;
            localStorage.setItem('sessionExpiry', new Date().getTime() + (newTime * 60 * 1000));
            openWccAlert('Success', 'Lockout time updated to ' + newTime + ' minutes');
            <?php elseif($_GET['msg'] === 'part_registered'): ?>
            openWccAlert('Success', 'Enterprise Part successfully registered!');
            <?php elseif($_GET['msg'] === 'pm_scheduled'): ?>
            openWccAlert('Success', 'PM Schedule Created & Initial Work Order Scheduled!');
            <?php elseif($_GET['msg'] === 'adhoc_scheduled'): ?>
            openWccAlert('Success', 'Ad-Hoc Work Order Scheduled!');
            <?php endif; ?>
        }
    });
    <?php endif; ?>
</script>

</body>
</html>

