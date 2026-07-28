<?php
include __DIR__ . '/../auth.php';
require_perm('manage_settings');

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    // Handle Workshop Creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_workshop') {
        $w_name = $_POST['workshop_name'] ?? '';
        $w_loc  = $_POST['workshop_location'] ?? '';
        if ($w_name) {
            $pdo->prepare("INSERT INTO workshops (name, location) VALUES (?, ?)")->execute([$w_name, $w_loc]);
            header("Location: /_mgmt/admin_panel.php"); exit;
        }
    }
    // Handle Line Creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_line') {
        $l_name = $_POST['line_name'] ?? '';
        $w_id   = (int)($_POST['workshop_id'] ?? 0);
        $l_prods= $_POST['products_built'] ?? '';
        if ($l_name && $w_id) {
            $pdo->prepare("INSERT INTO production_lines (workshop_id, name, products_built) VALUES (?, ?, ?)")->execute([$w_id, $l_name, $l_prods]);
            header("Location: /_mgmt/admin_panel.php"); exit;
        }
    }
    if (isset($_GET['delete_workshop'])) {
        $pdo->prepare("DELETE FROM workshops WHERE workshop_id = ?")->execute([$_GET['delete_workshop']]);
        header("Location: /_mgmt/admin_panel.php"); exit;
    }
    if (isset($_GET['delete_line'])) {
        $pdo->prepare("DELETE FROM production_lines WHERE line_id = ?")->execute([$_GET['delete_line']]);
        header("Location: /_mgmt/admin_panel.php"); exit;
    }
    // Handle Inventory Part
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['part_name'])) {
        $name=$_POST['part_name']??''; $code=$_POST['internal_code']??'';
        $stock=(int)($_POST['stock_level']??0); $min=(int)($_POST['minimum_threshold']??5); $cost=(float)($_POST['cost_per_unit']??0);
        $vendor_sku=$_POST['vendor_sku']??''; $standardized_desc=$_POST['standardized_desc']??'';
        $oem_name=$_POST['oem_name']??''; $oem_part_number=$_POST['oem_part_number']??''; $supersession_sku=$_POST['supersession_sku']??'';
        $maximum_stock=(int)($_POST['maximum_stock']??0); $standard_lead_time=(int)($_POST['standard_lead_time']??0);
        $expedited_lead_time=(int)($_POST['expedited_lead_time']??0); $moq=(int)($_POST['moq']??1);
        $uom=$_POST['uom']??'Each'; $currency=$_POST['currency']??'USD';
        $price_expiration=!empty($_POST['price_expiration'])?$_POST['price_expiration']:null;
        $eol_date=!empty($_POST['eol_date'])?$_POST['eol_date']:null;
        $shelf_life_months=(int)($_POST['shelf_life_months']??0); $material_spec=$_POST['material_spec']??''; $compliance_docs=$_POST['compliance_docs']??'';
        $warehouse_id=!empty($_POST['warehouse_id'])?(int)$_POST['warehouse_id']:null;
        $aisle=$_POST['aisle']??''; $rack=$_POST['rack']??''; $shelf=$_POST['shelf']??''; $bin_code=$_POST['bin_code']??'';
        $auto_reorder=isset($_POST['auto_reorder'])?1:0;
        $primary_vendor_id=!empty($_POST['primary_vendor_id'])?(int)$_POST['primary_vendor_id']:null;
        $serial_number=$_POST['serial_number']??''; $batch_lot=$_POST['batch_lot']??'';
        $part_condition=$_POST['part_condition']??'New'; $lifecycle_status=$_POST['lifecycle_status']??'Active';
        if ($name && $code) {
            $stmt = $pdo->prepare("INSERT INTO inventory_parts (part_name,internal_code,stock_level,minimum_threshold,cost_per_unit,vendor_sku,standardized_desc,oem_name,oem_part_number,supersession_sku,maximum_stock,standard_lead_time,expedited_lead_time,moq,uom,currency,price_expiration,eol_date,shelf_life_months,material_spec,compliance_docs,warehouse_id,aisle,rack,shelf,bin_code,auto_reorder,primary_vendor_id,serial_number,batch_lot,part_condition,lifecycle_status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$name,$code,$stock,$min,$cost,$vendor_sku,$standardized_desc,$oem_name,$oem_part_number,$supersession_sku,$maximum_stock,$standard_lead_time,$expedited_lead_time,$moq,$uom,$currency,$price_expiration,$eol_date,$shelf_life_months,$material_spec,$compliance_docs,$warehouse_id,$aisle,$rack,$shelf,$bin_code,$auto_reorder,$primary_vendor_id,$serial_number,$batch_lot,$part_condition,$lifecycle_status]);
            header("Location: /_mgmt/admin_panel.php?msg=part_registered"); exit;
        }
    }
    // Handle PM Checklist Deletion
    if (isset($_GET['delete_checklist'])) {
        $pdo->prepare("DELETE FROM pm_checklists WHERE checklist_id = ?")->execute([$_GET['delete_checklist']]);
        header("Location: /_mgmt/admin_panel.php?msg=checklist_deleted"); exit;
    }
    // Handle PM Checklist Edit
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_pm_checklist') {
        $cid = intval($_POST['edit_checklist_id'] ?? 0);
        $title = $_POST['checklist_title'] ?? '';
        $desc = $_POST['checklist_desc'] ?? '';
        $tasks = $_POST['task_desc'] ?? [];
        $times = $_POST['task_time'] ?? [];
        if ($title && !empty($tasks) && $cid > 0) {
            $pdo->prepare("UPDATE pm_checklists SET title = ?, description = ? WHERE checklist_id = ?")->execute([$title, $desc, $cid]);
            $pdo->prepare("DELETE FROM pm_checklist_items WHERE checklist_id = ?")->execute([$cid]);
            $stmt = $pdo->prepare("INSERT INTO pm_checklist_items (checklist_id, task_desc, expected_time_mins) VALUES (?,?,?)");
            foreach ($tasks as $i => $task) {
                $stmt->execute([$cid, $task, intval($times[$i] ?? 1)]);
            }
            header("Location: /_mgmt/admin_panel.php?msg=checklist_updated"); exit;
        }
    }
    // Handle PM Checklist Creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_pm_checklist') {
        $title = $_POST['checklist_title'] ?? '';
        $desc = $_POST['checklist_desc'] ?? '';
        $tasks = $_POST['task_desc'] ?? [];
        $times = $_POST['task_time'] ?? [];
        if ($title) {
            $pdo->prepare("INSERT INTO pm_checklists (title, description) VALUES (?, ?)")->execute([$title, $desc]);
            $cl_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("INSERT INTO pm_checklist_items (checklist_id, task_desc, expected_time_mins) VALUES (?, ?, ?)");
            for($i=0; $i<count($tasks); $i++) {
                $t = trim($tasks[$i]);
                $m = (int)($times[$i] ?? 1);
                if ($t !== '') {
                    $stmt->execute([$cl_id, $t, $m]);
                }
            }
            header("Location: /_mgmt/admin_panel.php?msg=checklist_created"); exit;
        }
    }
    // Handle PM Schedule
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_pm_schedule') {
        $title=$_POST['pm_title']??''; $desc=$_POST['pm_desc']??'';
        $eq_id=!empty($_POST['pm_equipment_id'])?(int)$_POST['pm_equipment_id']:null;
        $freq=!empty($_POST['pm_frequency'])?(int)$_POST['pm_frequency']:30;
        $checklist_id=!empty($_POST['checklist_id'])?(int)$_POST['checklist_id']:null;
        $parts=isset($_POST['pm_parts'])?json_encode($_POST['pm_parts']):json_encode([]);
        $next_run=date('Y-m-d',strtotime("+$freq days"));
        
        $checklist_data = null;
        if ($checklist_id) {
            $c_items = $pdo->prepare("SELECT task_desc, expected_time_mins FROM pm_checklist_items WHERE checklist_id = ?");
            $c_items->execute([$checklist_id]);
            $res = $c_items->fetchAll(PDO::FETCH_ASSOC);
            foreach($res as &$r) $r['completed'] = false;
            $checklist_data = json_encode($res);
        }

        if ($title) {
            $pdo->prepare("INSERT INTO pm_schedules (title,description,equipment_id,assigned_to,parts_list,checklist_id,frequency_days,next_run_date) VALUES (?,?,?,NULL,?,?,?,?)")->execute([$title,$desc,$eq_id,$parts,$checklist_id,$freq,$next_run]);
            $pdo->prepare("INSERT INTO work_orders (title,description,equipment_id,assigned_to,parts_list,checklist_data,scheduled_date,status) VALUES (?,?,?,NULL,?,?,?,'Scheduled')")->execute([$title,"Auto-generated from PM Schedule: $desc",$eq_id,$parts,$checklist_data,$next_run]);
            header("Location: /_mgmt/admin_panel.php?msg=pm_scheduled"); exit;
        }
    }
    // Handle Ad-Hoc WO
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_ad_hoc_wo') {
        $title=$_POST['title']??''; $desc=$_POST['description']??'';
        $assigned=!empty($_POST['assigned_to'])?(int)$_POST['assigned_to']:null;
        $checklist_id=!empty($_POST['checklist_id'])?(int)$_POST['checklist_id']:null;
        $date=$_POST['scheduled_date']??'';
        $eq_id=!empty($_POST['equipment_id'])?(int)$_POST['equipment_id']:null;
        
        $checklist_data = null;
        if ($checklist_id) {
            $c_items = $pdo->prepare("SELECT task_desc, expected_time_mins FROM pm_checklist_items WHERE checklist_id = ?");
            $c_items->execute([$checklist_id]);
            $res = $c_items->fetchAll(PDO::FETCH_ASSOC);
            foreach($res as &$r) $r['completed'] = false;
            $checklist_data = json_encode($res);
        }

        if ($title && $date) {
            $pdo->prepare("INSERT INTO work_orders (title,description,equipment_id,assigned_to,parts_list,checklist_data,scheduled_date,status) VALUES (?,?,?,?,?,?,?,'Scheduled')")->execute([$title,$desc,$eq_id,$assigned,json_encode([]),$checklist_data,$date]);
            header("Location: /_mgmt/admin_panel.php?msg=adhoc_scheduled"); exit;
        }
    }
    
    // Handle KPI Targets Form Submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_kpi_targets') {
        $mttd = (int)($_POST['target_mttd'] ?? 60);
        $mttr = (int)($_POST['target_mttr'] ?? 120);
        $mtbf = (int)($_POST['target_mtbf'] ?? 48);
        $calc_mode = $_POST['target_calc_mode'] ?? 'static';
        
        $pdo->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_key = 'target_mttd'")->execute([$mttd]);
        $pdo->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_key = 'target_mttr'")->execute([$mttr]);
        $pdo->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_key = 'target_mtbf'")->execute([$mtbf]);
        $pdo->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_key = 'target_calc_mode'")->execute([$calc_mode]);
        header("Location: /_mgmt/admin_panel.php?msg=kpi_updated"); exit;
    }

    // Load KPI Targets for Modal
    $kpi_defaults = ['target_calc_mode' => 'static', 'target_mttd' => '60', 'target_mttr' => '120', 'target_mtbf' => '48'];
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

    // Fetch data for modals
    $workshops    = $pdo->query("SELECT * FROM workshops ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $lines        = $pdo->query("SELECT l.*, w.name as workshop_name FROM production_lines l JOIN workshops w ON l.workshop_id = w.workshop_id ORDER BY w.name ASC, l.name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $all_equipment= $pdo->query("SELECT equip_id as equipment_id, equip_name as name FROM equipment ORDER BY equip_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $all_parts    = $pdo->query("SELECT part_id, part_name, internal_code FROM inventory_parts ORDER BY part_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $all_techs    = $pdo->query("SELECT user_id, username, badge_number FROM users WHERE role_level >= 2 ORDER BY badge_number ASC")->fetchAll(PDO::FETCH_ASSOC);
    $pm_schedules = $pdo->query("SELECT p.*, e.equip_name as equipment_name, u.badge_number as assigned_user FROM pm_schedules p LEFT JOIN equipment e ON p.equipment_id = e.equip_id LEFT JOIN users u ON p.assigned_to = u.user_id ORDER BY p.title ASC")->fetchAll(PDO::FETCH_ASSOC);

    // Fetch existing PM checklists
    $all_checklists_raw = $pdo->query("SELECT * FROM pm_checklists ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
    $all_checklists = [];
    foreach($all_checklists_raw as $cl) {
        $cl['items'] = $pdo->query("SELECT task_desc, expected_time_mins FROM pm_checklist_items WHERE checklist_id = " . intval($cl['checklist_id']) . " ORDER BY item_id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $all_checklists[] = $cl;
    }

} catch (PDOException $e) { wcc_user_error("Admin Panel Error", $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Admin Control Panel — WCC</title>
    <style>
        .setting-card {
            background: var(--panel-bg);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid var(--panel-border);
            padding: 30px;
            border-radius: 24px;
            box-shadow: 0 10px 30px 0 rgba(0,0,0,0.5);
            text-align: center;
            transition: transform 0.4s cubic-bezier(0.175,0.885,0.32,1.275), box-shadow 0.3s ease, border 0.3s ease;
            text-decoration: none;
            color: var(--text-primary);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 200px;
            cursor: pointer;
        }
        .setting-card:hover {
            transform: translateY(-8px);
            border: 1px solid var(--panel-border-top);
            box-shadow: 0 20px 40px 0 rgba(0,0,0,0.2);
        }
        .setting-card h3 { color: var(--text-accent); margin-top: 15px; margin-bottom: 5px; font-size: 1.4em; }
        .setting-card p  { color: var(--text-secondary); font-size: 0.9em; margin: 0; }
        .panel-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 30px; }
        .enterprise-modal { width: 900px !important; max-width: 95% !important; }
        .form-section { margin-top: 20px; padding-top: 10px; border-top: 1px solid var(--panel-border); }
        .form-section h3 { color: var(--text-accent); margin-bottom: 15px; font-size: 1.1em; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
        .settings-link-bar {
            margin-top: 30px; padding: 16px 24px;
            background: var(--panel-bg); border: 1px solid var(--panel-border);
            border-radius: 14px; display: flex; align-items: center; justify-content: space-between;
        }
        .settings-link-bar span { color: var(--text-secondary); font-size: 0.9em; }
        .settings-link-bar a {
            display: inline-flex; align-items: center; gap: 8px;
            color: var(--text-accent); text-decoration: none; font-weight: 600; font-size: 0.9em;
            padding: 8px 16px; border: 1px solid var(--text-accent); border-radius: 8px; transition: background 0.2s, color 0.2s;
        }
        .settings-link-bar a:hover { background: var(--text-accent); color: #0f172a; }
    </style>
</head>
<body><?php include __DIR__ . '/../nav.php'; ?>
<?php require_once __DIR__ . '/../rbac.php'; ?>

<div class="dashboard-container dash-box">
    <div class="header-flex" style="border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px;">
        <h2>🛡️ Admin Control Panel</h2>
    </div>

    <div class="panel-grid">
        <a href="users.php" class="setting-card">
            <div style="font-size:3em;">👥</div>
            <h3>User Management</h3>
            <p>Role-Based Access Control &amp; Accounts</p>
        </a>
        <a href="/_eam/setup_vault_equipment.php" class="setting-card">
            <div style="font-size:3em;">🔒</div>
            <h3>Enclosed Setup Vault</h3>
            <p>Admin Equipment Config</p>
        </a>
        <a href="/_logi/setup_vault_vendors.php" class="setting-card">
            <div style="font-size:3em;">🏢</div>
            <h3>Vendor Management</h3>
            <p>Supplier Database &amp; Contacts</p>
        </a>
        <a href="setup_vault_departments.php" class="setting-card">
            <div style="font-size:3em;">🏬</div>
            <h3>Department Management</h3>
            <p>Budget Allocation &amp; Tracking</p>
        </a>
        <div class="setting-card" onclick="document.getElementById('addModal').style.display='block'">
            <div style="font-size:3em;">📦</div>
            <h3>Add Inventory Part</h3>
            <p>Register New Spare Parts</p>
        </div>
        <a href="/_logi/inventory_audit.php" class="setting-card">
            <div style="font-size:3em;">📋</div>
            <h3>Inventory Audit Log</h3>
            <p>Full Transaction History</p>
        </a>
        <a href="/_logi/purchase_orders.php" class="setting-card">
            <div style="font-size:3em;">📝</div>
            <h3>PR / PO Management</h3>
            <p>Enterprise Procurement Engine</p>
        </a>
        <div class="setting-card" onclick="document.getElementById('linesModal').style.display='block'">
            <div style="font-size:3em;">🏭</div>
            <h3>Production Lines</h3>
            <p>Workshops &amp; Line Config</p>
        </div>
        <div class="setting-card" onclick="document.getElementById('pmModal').style.display='block'">
            <div style="font-size:3em;">🗓️</div>
            <h3>PM Configurator</h3>
            <p>Preventative Maintenance Cycles</p>
        </div>
        <div class="setting-card" onclick="document.getElementById('addWOModal').style.display='block'">
            <div style="font-size:3em;">📝</div>
            <h3>Ad-Hoc Work Order</h3>
            <p>Create a single Work Order</p>
        </div>
        <div class="setting-card" onclick="document.getElementById('docsModal').style.display='block'">
            <div style="font-size:3em;">📁</div>
            <h3>Documents Management</h3>
            <p>Safety SOPs &amp; Manuals</p>
        </div>
        <div class="setting-card" onclick="document.getElementById('kpiModal').style.display='block'">
            <div style="font-size:3em;">📈</div>
            <h3>KPI Targets</h3>
            <p>Set MTBF, MTTD &amp; MTTR</p>
        </div>
        <div class="setting-card" onclick="document.getElementById('checklistModal').style.display='block'">
            <div style="font-size:3em;">✅</div>
            <h3>PM Checklists</h3>
            <p>Task Checklists &amp; Times</p>
        </div>
        <!-- 3x5 Grid Placeholders -->
        <div class="setting-card" style="opacity: 0.4; cursor: default;">
            <div style="font-size:3em;">🚧</div>
            <h3>Coming Soon</h3>
            <p>Future Expansion Tile</p>
        </div>
        <div class="setting-card" style="opacity: 0.4; cursor: default;">
            <div style="font-size:3em;">🚧</div>
            <h3>Coming Soon</h3>
            <p>Future Expansion Tile</p>
        </div>
        <div class="setting-card" style="opacity: 0.4; cursor: default;">
            <div style="font-size:3em;">🚧</div>
            <h3>Coming Soon</h3>
            <p>Future Expansion Tile</p>
        </div>
    </div>

    <div class="settings-link-bar">
        <span>⚙️ Security, session timeouts &amp; theme customization are managed separately.</span>
        <a href="/_mgmt/app_settings.php">System Settings →</a>
<br><br>
<a href="/_mgmt/admin_backup.php" style="color:#38bdf8;">🗄️ Database Backup Tool</a>
    </div>
</div>

<!-- Add Inventory Part Modal -->
<div id="addModal" class="modal">
  <div class="modal-content enterprise-modal">
    <span class="close" onclick="document.getElementById('addModal').style.display='none'">&times;</span>
    <h2>Register Spare Part</h2>
    <form method="POST">
        <div class="form-section" style="border-top:none;margin-top:0;padding-top:0;">
            <h3>Part DNA (The Base)</h3>
            <div class="grid-2">
                <div><label>Part Name *</label><input type="text" name="part_name" required></div>
                <div><label>Internal Code (SKU) *</label><input type="text" name="internal_code" required></div>
                <div><label>Vendor SKU</label><input type="text" name="vendor_sku"></div>
                <div><label>Standardized Description</label><input type="text" name="standardized_desc"></div>
                <div><label>OEM Name</label><input type="text" name="oem_name"></div>
                <div><label>OEM Part Number</label><input type="text" name="oem_part_number"></div>
                <div><label>Supersession SKU</label><input type="text" name="supersession_sku"></div>
            </div>
        </div>
        <div class="form-section">
            <h3>Logistics &amp; Procurement</h3>
            <div class="grid-3">
                <div><label>Current Stock</label><input type="number" name="stock_level" value="0" required></div>
                <div><label>Min Threshold</label><input type="number" name="minimum_threshold" value="5" required></div>
                <div><label>Max Stock</label><input type="number" name="maximum_stock" value="0"></div>
                <div><label>Unit Price</label><input type="number" step="0.01" name="cost_per_unit" value="0.00"></div>
                <div><label>Currency</label><input type="text" name="currency" value="USD"></div>
                <div><label>Price Exp. Date</label><input type="date" name="price_expiration"></div>
                <div><label>Std. Lead Time (days)</label><input type="number" name="standard_lead_time" value="0"></div>
                <div><label>Exp. Lead Time (days)</label><input type="number" name="expedited_lead_time" value="0"></div>
                <div><label>MOQ</label><input type="number" name="moq" value="1"></div>
                <div><label>Unit of Measure</label><input type="text" name="uom" value="Each"></div>
                <div><label>Primary Vendor ID</label><input type="number" name="primary_vendor_id"></div>
                <div style="display:flex;align-items:flex-end;padding-bottom:10px;"><label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:0;"><input type="checkbox" name="auto_reorder" style="width:auto;margin-top:0;"> Auto Reorder</label></div>
            </div>
        </div>
        <div class="form-section">
            <h3>Storage &amp; Tracking</h3>
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
            <h3>Compliance &amp; Lifecycle</h3>
            <div class="grid-3">
                <div><label>Condition</label><select name="part_condition"><option>New</option><option>Refurbished</option><option>Defective</option><option>Awaiting QA</option></select></div>
                <div><label>Lifecycle Status</label><select name="lifecycle_status"><option>Active</option><option>Phasing Out</option><option>Obsolete</option></select></div>
                <div><label>EOL Date</label><input type="date" name="eol_date"></div>
                <div><label>Shelf-Life (Months)</label><input type="number" name="shelf_life_months" value="0"></div>
                <div><label>Material Spec</label><input type="text" name="material_spec"></div>
                <div><label>Compliance Docs</label><input type="text" name="compliance_docs"></div>
            </div>
        </div>
        <button type="submit" class="btn" style="margin-top:30px;">Save Enterprise Part</button>
    </form>
  </div>
</div>

<!-- Documents Management Modal -->
<div id="docsModal" class="modal">
  <div class="modal-content enterprise-modal" style="max-width: 600px !important;">
    <span class="close" onclick="document.getElementById('docsModal').style.display='none'">&times;</span>
    <h2>📁 Documents Management</h2>
    <p style="color: var(--text-secondary); margin-bottom: 20px;">Upload Safety SOPs, manuals, or technical diagrams. Documents are securely linked to specific equipment assets.</p>
    
    <form id="docUploadForm" method="POST" enctype="multipart/form-data" style="background:rgba(0,0,0,0.1);padding:20px;border-radius:12px;border:1px solid var(--panel-border);">
        <div style="margin-bottom: 15px;">
            <label>Target Equipment *</label>
            <select name="equip_id" required style="width:100%; padding: 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: white;">
                <option value="">-- Select Equipment --</option>
                <?php foreach($all_equipment as $eq): ?>
                    <option value="<?= $eq['equipment_id'] ?>"><?= htmlspecialchars($eq['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div style="margin-bottom: 15px;">
            <label>Document Title *</label>
            <input type="text" name="doc_title" placeholder="e.g. Safety Protocol v2" required style="width:100%; padding: 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: white; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 15px;">
            <label>Document Type *</label>
            <select name="doc_type" required style="width:100%; padding: 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: white;">
                <option value="SOP">Safety SOP</option>
                <option value="Manual">User Manual</option>
                <option value="Diagram">Technical Diagram</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <div style="margin-bottom: 25px;">
            <label>Select File (PDF, DOCX, TXT, PNG, JPG) *</label>
            <input type="file" name="doc_file" required accept=".pdf,.docx,.txt,.png,.jpg,.jpeg" style="width:100%; padding: 10px; border-radius: 6px; border: 1px dashed rgba(255,255,255,0.3); background: rgba(0,0,0,0.2); color: white; box-sizing: border-box;">
        </div>

        <button type="button" class="btn" onclick="submitDocUpload()" style="width:100%; background: #a855f7; border-color: #9333ea;">Upload &amp; Link Document</button>
    </form>
  </div>
</div>

<script>
    async function submitDocUpload() {
        const form = document.getElementById('docUploadForm');
        if(!form.reportValidity()) return;
        const formData = new FormData(form);
        const btn = form.querySelector('button');
        btn.disabled = true; btn.innerText = 'Uploading...';
        
        try {
            const resp = await fetch('/api/upload_document.php', { method: 'POST', body: formData });
            const res = await resp.json();
            if(res.status === 'success') {
                if(typeof openWccAlert === 'function') {
                    openWccAlert('Success', res.message, '/_mgmt/admin_panel.php');
                } else {
                    alert(res.message); window.location.reload();
                }
            } else {
                if(typeof openWccAlert === 'function') openWccAlert('Error', res.message); else alert(res.message);
                btn.disabled = false; btn.innerText = 'Upload & Link Document';
            }
        } catch(e) {
            if(typeof openWccAlert === 'function') openWccAlert('Error', 'Upload failed: ' + e.message); else alert('Upload failed: ' + e.message);
            btn.disabled = false; btn.innerText = 'Upload & Link Document';
        }
    }
</script>

<!-- Production Lines Modal -->
<div id="linesModal" class="modal">
  <div class="modal-content enterprise-modal">
    <span class="close" onclick="document.getElementById('linesModal').style.display='none'">&times;</span>
    <h2>🏭 Production Workshops &amp; Lines Configuration</h2>
    <div class="grid-2" style="margin-top:20px;">
        <div class="form-section" style="border-top:none;margin-top:0;padding-top:0;">
            <h3>Create New Workshop</h3>
            <form method="POST" style="background:rgba(0,0,0,0.1);padding:15px;border-radius:8px;border:1px solid var(--panel-border);">
                <input type="hidden" name="action" value="add_workshop">
                <label>Workshop Name *</label>
                <input type="text" name="workshop_name" placeholder="e.g. Assembly Plant Alpha" required style="width:100%;box-sizing:border-box;margin-bottom:10px;">
                <label>Location Details</label>
                <input type="text" name="workshop_location" placeholder="e.g. Building 2, Floor 1" style="width:100%;box-sizing:border-box;margin-bottom:15px;">
                <button type="submit" class="btn" style="width:100%;">+ Add Workshop</button>
            </form>
            <h4 style="margin-top:20px;color:var(--text-accent);">Existing Workshops</h4>
            <table class="data-table" style="font-size:0.9em;">
                <thead><tr><th>Name</th><th>Location</th><th>Act</th></tr></thead>
                <tbody>
                    <?php foreach($workshops as $w): ?>
                    <tr>
                        <td><?= htmlspecialchars($w['name']) ?></td>
                        <td><?= htmlspecialchars($w['location']) ?></td>
                        <td><a href="#" onclick="openWccConfirm('Delete this workshop and ALL its lines?', '?delete_workshop=<?= $w['workshop_id'] ?>', 'Delete Workshop'); return false;" class="nav-btn" style="background:#ef4444;color:white;padding:2px 5px;text-decoration:none;border:none;font-size:0.8em;">X</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="form-section" style="border-top:none;margin-top:0;padding-top:0;">
            <h3>Allocate New Line to Workshop</h3>
            <form method="POST" style="background:rgba(0,0,0,0.1);padding:15px;border-radius:8px;border:1px solid var(--panel-border);">
                <input type="hidden" name="action" value="add_line">
                <label>Parent Workshop *</label>
                <select name="workshop_id" required style="width:100%;box-sizing:border-box;margin-bottom:10px;">
                    <option value="">-- Choose Workshop --</option>
                    <?php foreach($workshops as $w): ?>
                        <option value="<?= $w['workshop_id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Line Name *</label>
                <input type="text" name="line_name" placeholder="e.g. Conveyor System B" required style="width:100%;box-sizing:border-box;margin-bottom:10px;">
                <label>Products Built Here</label>
                <input type="text" name="products_built" placeholder="e.g. Engine Blocks" style="width:100%;box-sizing:border-box;margin-bottom:15px;">
                <button type="submit" class="btn" style="width:100%;background:#ca8a04;">+ Allocate Line</button>
            </form>
            <h4 style="margin-top:20px;color:var(--text-accent);">Existing Lines</h4>
            <div style="max-height:200px;overflow-y:auto;">
                <table class="data-table" style="font-size:0.9em;">
                    <thead><tr><th>Workshop</th><th>Line Name</th><th>Act</th></tr></thead>
                    <tbody>
                        <?php foreach($lines as $l): ?>
                        <tr>
                            <td><?= htmlspecialchars($l['workshop_name']) ?></td>
                            <td><?= htmlspecialchars($l['name']) ?></td>
                            <td><a href="#" onclick="openWccConfirm('Delete this line?', '?delete_line=<?= $l['line_id'] ?>', 'Delete Line'); return false;" class="nav-btn" style="background:#ef4444;color:white;padding:2px 5px;text-decoration:none;border:none;font-size:0.8em;">X</a></td>
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
    <div class="grid-2" style="margin-top:20px;">
        <div class="form-section" style="border-top:none;margin-top:0;padding-top:0;">
            <h3>Create PM Schedule</h3>
            <form method="POST" style="background:rgba(0,0,0,0.1);padding:15px;border-radius:8px;border:1px solid var(--panel-border);">
                <input type="hidden" name="action" value="add_pm_schedule">
                <label>Schedule Title *</label>
                <input type="text" name="pm_title" placeholder="e.g. Monthly Lubrication" required style="width:100%;box-sizing:border-box;margin-bottom:10px;">
                <label>Description / Instructions</label>
                <textarea name="pm_desc" rows="2" style="width:100%;box-sizing:border-box;margin-bottom:10px;"></textarea>
                <label>Target Equipment *</label>
                <select name="pm_equipment_id" required style="width:100%;box-sizing:border-box;margin-bottom:10px;">
                    <option value="">-- Select Equipment --</option>
                    <?php foreach($all_equipment as $eq): ?>
                        <option value="<?= $eq['equipment_id'] ?>"><?= htmlspecialchars($eq['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Frequency (Days)</label>
                <input type="number" name="pm_frequency" min="1" required style="width:100%;box-sizing:border-box;margin-bottom:10px;">
                <label>Required Parts</label>
                <input type="text" id="partSearch" placeholder="Search parts..." onkeyup="filterParts()" style="width:100%;box-sizing:border-box;margin-bottom:5px;padding:8px;border-radius:4px;background:rgba(0,0,0,0.1);color:white;border:1px solid rgba(255,255,255,0.2);">
                <div id="partsList" style="max-height:150px;overflow-y:auto;background:rgba(0,0,0,0.2);border:1px solid var(--panel-border);padding:5px;border-radius:4px;margin-bottom:10px;">
                    <?php foreach($all_parts as $p): ?>
                        <label style="display:block;cursor:pointer;padding:4px;border-bottom:1px solid rgba(255,255,255,0.05);">
                            <input type="checkbox" name="pm_parts[]" value="<?= $p['part_id'] ?>" style="margin-right:8px;">
                            <span class="part-name"><?= htmlspecialchars($p['part_name'].' ('.$p['internal_code'].')') ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <label>Attach Checklist (Optional)</label>
                <select name="checklist_id" style="width:100%;box-sizing:border-box;margin-bottom:10px;">
                    <option value="">-- No Checklist --</option>
                    <?php foreach($all_checklists as $cl): ?>
                        <option value="<?= $cl['checklist_id'] ?>"><?= htmlspecialchars($cl['title']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn" style="width:100%;margin-top:15px;background:#10b981;color:black;font-weight:800;font-size:1.1em;">+ Save PM Schedule</button>
            </form>
        </div>
        <div class="form-section" style="border-top:none;margin-top:0;padding-top:0;">
            <h3>Active PM Schedules</h3>
            <div style="max-height:450px;overflow-y:auto;">
                <table class="data-table" style="font-size:0.85em;">
                    <thead><tr><th>Title</th><th>Equipment</th><th>Freq</th><th>Tech</th></tr></thead>
                    <tbody>
                        <?php foreach($pm_schedules as $pm): ?>
                        <tr>
                            <td><?= htmlspecialchars($pm['title']) ?></td>
                            <td><?= htmlspecialchars($pm['equipment_name']??'Unknown') ?></td>
                            <td><?= $pm['frequency_days'] ?>d</td>
                            <td><?= htmlspecialchars($pm['assigned_user'] ?? 'N/A') ?></td>
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
  <div class="modal-content enterprise-modal" style="width:700px;max-width:95%;">
    <span class="close" onclick="document.getElementById('addWOModal').style.display='none'">&times;</span>
    <h2>📝 Schedule Ad-Hoc Work Order</h2>
    <form method="POST">
        <input type="hidden" name="action" value="add_ad_hoc_wo">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-top:20px;">
            <div class="form-section" style="border-top:none;margin:0;padding:0;">
                <label>Title *</label>
                <input type="text" name="title" required style="width:100%;box-sizing:border-box;margin-bottom:10px;">
                <label>Description</label>
                <textarea name="description" rows="3" style="width:100%;box-sizing:border-box;margin-bottom:10px;"></textarea>
                <label>Workshop</label>
                <select id="wo_workshop" onchange="updateWOLine()" style="width:100%;box-sizing:border-box;margin-bottom:10px;">
                    <option value="">-- All Workshops --</option>
                    <?php foreach($workshops as $w): ?>
                        <option value="<?= $w['workshop_id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Production Line</label>
                <select id="wo_line" onchange="updateWOEquipment()" style="width:100%;box-sizing:border-box;margin-bottom:10px;">
                    <option value="">-- All Lines --</option>
                </select>
            </div>
            <div class="form-section" style="border-top:none;margin:0;padding:0;">
                <label>Target Equipment *</label>
                <select name="equipment_id" id="wo_equipment" required style="width:100%;box-sizing:border-box;margin-bottom:10px;">
                    <option value="">-- Select Equipment --</option>
                    <?php foreach($all_equipment as $eq): ?>
                        <option value="<?= $eq['equipment_id'] ?>"><?= htmlspecialchars($eq['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Assigned To</label>
                <select name="assigned_to" style="width:100%;box-sizing:border-box;margin-bottom:10px;">
                    <option value="">-- Unassigned --</option>
                    <?php foreach($all_techs as $t): ?>
                        <option value="<?= $t['user_id'] ?>"><?= htmlspecialchars($t['badge_number'] ?? 'IB-?????') ?> (<?= htmlspecialchars($t['username']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <label>Attach Checklist (Optional)</label>
                <select name="checklist_id" style="width:100%;box-sizing:border-box;margin-bottom:10px;">
                    <option value="">-- No Checklist --</option>
                    <?php foreach($all_checklists as $cl): ?>
                        <option value="<?= $cl['checklist_id'] ?>"><?= htmlspecialchars($cl['title']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Scheduled Date *</label>
                <input type="date" name="scheduled_date" required style="width:100%;box-sizing:border-box;margin-bottom:10px;">
            </div>
        </div>
        <button type="submit" class="btn" style="width:100%;margin-top:20px;background:#38bdf8;color:black;font-size:1.1em;font-weight:bold;">+ Create Work Order</button>
    </form>
  </div>
</div>

<!-- Checklist Config Modal -->
<div id="checklistModal" class="modal">
  <div class="modal-content enterprise-modal">
    <span class="close" onclick="document.getElementById('checklistModal').style.display='none'">&times;</span>
    <h2>✅ Manage PM Checklists</h2>
    <div class="grid-2" style="margin-top:20px;">
        <div class="form-section" style="border-top:none;margin-top:0;padding-top:0;">
            <h3>Create New Checklist</h3>
            <form method="POST" style="background:rgba(0,0,0,0.1);padding:15px;border-radius:8px;border:1px solid var(--panel-border);">
                <input type="hidden" name="action" value="add_pm_checklist">
                <label>Checklist Title *</label>
                <input type="text" name="checklist_title" required style="width:100%;box-sizing:border-box;margin-bottom:10px;">
                <label>Description</label>
                <textarea name="checklist_desc" rows="2" style="width:100%;box-sizing:border-box;margin-bottom:10px;"></textarea>
                
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">
                    <label style="margin:0;">Checklist Items (Task & Expected Time)</label>
                    <button type="button" class="btn" style="padding:4px 8px; font-size:0.8em;" onclick="addChecklistItem()">+ Add Row</button>
                </div>
                <div id="checklist_items_container" style="margin-top:10px; border-left: 2px solid var(--text-accent); padding-left: 10px;">
                    <div style="display:grid; grid-template-columns: 1fr 80px 30px; gap:10px; margin-bottom:5px;">
                        <input type="text" name="task_desc[]" placeholder="Task Description" required>
                        <input type="number" name="task_time[]" placeholder="Mins" min="1" required title="Expected Minutes">
                        <div></div>
                    </div>
                </div>

                <button type="submit" class="btn" style="width:100%;margin-top:15px;background:#3b82f6;color:white;font-weight:bold;">+ Save Checklist</button>
            </form>
        </div>
        <div class="form-section" style="border-top:none;margin-top:0;padding-top:0;">
            <h3>Available Checklists</h3>
            <div style="max-height:450px;overflow-y:auto;">
                <table class="data-table" style="font-size:0.85em;">
                    <thead><tr><th>Title</th><th>Description</th><th style="width:40px;"></th></tr></thead>
                    <tbody>
                        <?php foreach($all_checklists as $cl): ?>
                        <tr>
                            <td><?= htmlspecialchars($cl['title']) ?></td>
                            <td><?= htmlspecialchars($cl['description'] ?? '') ?></td>
                            <td style="text-align:center; min-width: 50px;">
                                <a href="#" onclick="editChecklist(<?= $cl['checklist_id'] ?>); return false;" style="color:var(--text-primary); text-decoration:none; font-weight:bold; font-size:1.1em; margin-right: 5px;" title="Edit">✏️</a>
                                <a href="?delete_checklist=<?= $cl['checklist_id'] ?>" onclick="return confirm('Permanently delete this checklist?');" style="color:var(--text-accent); text-decoration:none; font-weight:bold; font-size:1.2em;" title="Delete">×</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
  </div>
</div>

<script>
const allChecklists = <?= json_encode($all_checklists) ?>;
function editChecklist(id) {
    const cl = allChecklists.find(c => c.checklist_id == id);
    if(!cl) return;
    document.querySelector('input[name="action"][value="add_pm_checklist"], input[name="action"][value="edit_pm_checklist"]').value = 'edit_pm_checklist';
    let form = document.querySelector('form:has(input[name="checklist_title"])');
    if (!document.getElementById('edit_checklist_id')) {
        let hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'edit_checklist_id';
        hidden.id = 'edit_checklist_id';
        form.appendChild(hidden);
    }
    document.getElementById('edit_checklist_id').value = id;
    form.querySelector('input[name="checklist_title"]').value = cl.title;
    form.querySelector('textarea[name="checklist_desc"]').value = cl.description || '';
    
    const container = document.getElementById('checklist_items_container');
    container.innerHTML = '';
    cl.items.forEach(item => {
        const row = document.createElement('div');
        row.style.cssText = 'display:grid; grid-template-columns: 1fr 80px 30px; gap:10px; margin-bottom:5px;';
        const safeDesc = item.task_desc.replace(/"/g, '&quot;');
        row.innerHTML = `
            <input type="text" name="task_desc[]" placeholder="Task Description" value="${safeDesc}" required>
            <input type="number" name="task_time[]" placeholder="Mins" min="1" value="${item.expected_time_mins}" required title="Expected Minutes">
            <button type="button" style="background:transparent;border:none;color:#ef4444;cursor:pointer;font-weight:bold;font-size:1.1em;" onclick="this.parentElement.remove()">×</button>
        `;
        container.appendChild(row);
    });
    
    form.querySelector('button[type="submit"]').textContent = '✓ Update Checklist';
    form.parentElement.querySelector('h3').textContent = 'Edit Checklist';
}
function addChecklistItem() {
    const container = document.getElementById('checklist_items_container');
    const row = document.createElement('div');
    row.style.cssText = 'display:grid; grid-template-columns: 1fr 80px 30px; gap:10px; margin-bottom:5px;';
    row.innerHTML = `
        <input type="text" name="task_desc[]" placeholder="Task Description" required>
        <input type="number" name="task_time[]" placeholder="Mins" min="1" required title="Expected Minutes">
        <button type="button" style="background:transparent;border:none;color:#ef4444;cursor:pointer;font-weight:bold;font-size:1.1em;" onclick="this.parentElement.remove()">×</button>
    `;
    container.appendChild(row);
}
</script>

<!-- KPI Targets Modal -->
<div id="kpiModal" class="modal">
  <div class="modal-content" style="max-width: 500px;">
    <span class="close" onclick="document.getElementById('kpiModal').style.display='none'">&times;</span>
    <h2>📈 KPI &amp; Performance Targets</h2>
    <form method="POST">
        <input type="hidden" name="action" value="save_kpi_targets">
        <div class="form-section" style="border-top:none;margin-top:0;padding-top:0;">
            <p style="color:var(--text-secondary); font-size: 0.9em; margin-bottom: 20px;">
                These baseline targets govern the dashboard performance analysis.
            </p>
            <div style="margin-bottom: 20px;">
                <label style="color:var(--text-secondary); display:block; margin-bottom: 5px; font-weight:bold;">Calculation Mode</label>
                <select name="target_calc_mode" id="targetCalcMode" onchange="toggleKpiInputs()" style="width: 100%; padding:10px; border-radius:6px; background:rgba(0,0,0,0.2); border:1px solid var(--panel-border); color:white; box-sizing: border-box;">
                    <option value="static" <?= ($kpi_settings['target_calc_mode'] ?? 'static') === 'static' ? 'selected' : '' ?>>Static Baseline Targets</option>
                    <option value="dynamic" <?= ($kpi_settings['target_calc_mode'] ?? 'static') === 'dynamic' ? 'selected' : '' ?>>Dynamic (3-Month Rolling Average)</option>
                </select>
                <div style="font-size: 0.8em; color:var(--text-secondary); margin-top:5px;">Dynamic mode automatically computes targets based on the previous 3 months.</div>
            </div>
            <div id="kpiStaticInputs">
                <div style="margin-bottom: 15px;">
                    <label style="color:var(--text-secondary); display:block; margin-bottom: 5px;">Target MTTD (Minutes)</label>
                    <input type="number" name="target_mttd" id="target_mttd_input" value="<?= htmlspecialchars($kpi_settings['target_mttd'] ?? 60) ?>" required min="1" style="width: 100%; padding:10px; border-radius:6px; background:rgba(0,0,0,0.2); border:1px solid var(--panel-border); color:white; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="color:var(--text-secondary); display:block; margin-bottom: 5px;">Target MTTR (Minutes)</label>
                    <input type="number" name="target_mttr" id="target_mttr_input" value="<?= htmlspecialchars($kpi_settings['target_mttr'] ?? 120) ?>" required min="1" style="width: 100%; padding:10px; border-radius:6px; background:rgba(0,0,0,0.2); border:1px solid var(--panel-border); color:white; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="color:var(--text-secondary); display:block; margin-bottom: 5px;">Target MTBF (Hours)</label>
                    <input type="number" name="target_mtbf" id="target_mtbf_input" value="<?= htmlspecialchars($kpi_settings['target_mtbf'] ?? 48) ?>" required min="1" style="width: 100%; padding:10px; border-radius:6px; background:rgba(0,0,0,0.2); border:1px solid var(--panel-border); color:white; box-sizing: border-box;">
                </div>
            </div>
        </div>
        <button type="submit" class="btn" style="width:100%;margin-top:10px;background:#10b981;color:black;font-size:1.1em;font-weight:bold;">Save KPI Targets</button>
    </form>
  </div>
</div>

<script>
    function toggleKpiInputs() {
        const mode = document.getElementById('targetCalcMode').value;
        const inputs = [
            document.getElementById('target_mttd_input'),
            document.getElementById('target_mttr_input'),
            document.getElementById('target_mtbf_input')
        ];
        
        inputs.forEach(el => {
            if (mode === 'dynamic') {
                el.setAttribute('disabled', 'disabled');
                el.style.opacity = '0.4';
                el.style.cursor = 'not-allowed';
            } else {
                el.removeAttribute('disabled');
                el.style.opacity = '1';
                el.style.cursor = 'text';
            }
        });
    }
    
    // Call once on load
    window.addEventListener('DOMContentLoaded', toggleKpiInputs);

    function filterParts() {
        let filter = document.getElementById('partSearch').value.toLowerCase();
        let labels = document.getElementById('partsList').getElementsByTagName('label');
        for (let i = 0; i < labels.length; i++) {
            let text = labels[i].querySelector('.part-name').innerText.toLowerCase();
            labels[i].style.display = text.includes(filter) ? 'block' : 'none';
        }
    }
    let woProductionLines = <?= json_encode($lines) ?>;
    let woEquipmentData   = <?= json_encode($all_equipment) ?>;
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
            <?php if($_GET['msg'] === 'part_registered'): ?>
            openWccAlert('Success', 'Enterprise Part successfully registered!');
            <?php elseif($_GET['msg'] === 'pm_scheduled'): ?>
            openWccAlert('Success', 'PM Schedule Created & Initial Work Order Scheduled!');
            <?php elseif($_GET['msg'] === 'adhoc_scheduled'): ?>
            openWccAlert('Success', 'Ad-Hoc Work Order Scheduled!');
            <?php elseif($_GET['msg'] === 'kpi_updated'): ?>
            openWccAlert('Success', 'KPI & Performance Targets Updated successfully!');
            <?php endif; ?>
        }
    });
    <?php endif; ?>
</script>
</body>
</html>
