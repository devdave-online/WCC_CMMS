<?php
include __DIR__ . '/../auth.php';
require_perm('manage_settings');
require_once __DIR__ . '/../inc/demo_mode.php';
wcc_demo_guard_destructive_get();   // public demo: block ?delete_*=… handlers before they run

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/kpi.php';   // WCC_EVENT_CLASSES for the failure-class config
$pdo = get_wcc_db_connection();

// ------------------------------------------------------------------
// Admin board tile registry — single source of truth for the panel grid.
// Render order, the edit-layout whitelist, and defaults all come from here.
// Adding a tile here is enough: it appears at the end of everyone's board.
// ------------------------------------------------------------------
$ADMIN_TILES = [
    'users'            => ['type'=>'link','icon'=>'👥','title_key'=>'admin.tile.users','desc_key'=>'admin.tile.users_desc','href'=>'users.php'],
    'equipment_vault'  => ['type'=>'link','icon'=>'🔒','title_key'=>'admin.tile.equipment_vault','desc_key'=>'admin.tile.equipment_vault_desc','href'=>'/_eam/setup_vault_equipment.php'],
    'tooling_vault'    => ['type'=>'link','icon'=>'🔧','title_key'=>'admin.tile.tooling_vault','desc_key'=>'admin.tile.tooling_vault_desc','href'=>'/_eam/setup_vault_toolings.php'],
    'vendors'          => ['type'=>'link','icon'=>'🏢','title_key'=>'admin.tile.vendors','desc_key'=>'admin.tile.vendors_desc','href'=>'/_logi/setup_vault_vendors.php'],
    'departments'      => ['type'=>'link','icon'=>'🏬','title_key'=>'admin.tile.departments','desc_key'=>'admin.tile.departments_desc','href'=>'setup_vault_departments.php'],
    'add_part'         => ['type'=>'modal','icon'=>'📦','title_key'=>'admin.tile.add_part','desc_key'=>'admin.tile.add_part_desc','modal'=>'addModal'],
    'inventory_audit'  => ['type'=>'link','icon'=>'📋','title_key'=>'admin.tile.inventory_audit','desc_key'=>'admin.tile.inventory_audit_desc','href'=>'/_logi/inventory_audit.php'],
    'inventory_health' => ['type'=>'modal','icon'=>'🩺','title_key'=>'admin.tile.inventory_health','desc_key'=>'admin.tile.inventory_health_desc','modal'=>'invHealthModal'],
    'purchase_orders'  => ['type'=>'link','icon'=>'📝','title_key'=>'admin.tile.purchase_orders','desc_key'=>'admin.tile.purchase_orders_desc','href'=>'/_logi/purchase_orders.php'],
    'production_lines' => ['type'=>'modal','icon'=>'🏭','title_key'=>'admin.tile.production_lines','desc_key'=>'admin.tile.production_lines_desc','modal'=>'linesModal'],
    'pm_configurator'  => ['type'=>'modal','icon'=>'🗓️','title_key'=>'admin.tile.pm_configurator','desc_key'=>'admin.tile.pm_configurator_desc','modal'=>'pmModal'],
    'adhoc_wo'         => ['type'=>'modal','icon'=>'📝','title_key'=>'admin.tile.adhoc_wo','desc_key'=>'admin.tile.adhoc_wo_desc','modal'=>'addWOModal'],
    'documents'        => ['type'=>'modal','icon'=>'📁','title_key'=>'admin.tile.documents','desc_key'=>'admin.tile.documents_desc','modal'=>'docsModal'],
    'kpi_targets'      => ['type'=>'modal','icon'=>'📈','title_key'=>'admin.tile.kpi_targets','desc_key'=>'admin.tile.kpi_targets_desc','modal'=>'kpiModal'],
    'pm_checklists'    => ['type'=>'modal','icon'=>'✅','title_key'=>'admin.tile.pm_checklists','desc_key'=>'admin.tile.pm_checklists_desc','modal'=>'checklistModal'],
    'placeholder_1'    => ['type'=>'placeholder','icon'=>'🚧','title_key'=>'admin.tile.coming_soon','desc_key'=>'admin.tile.coming_soon_desc'],
    'placeholder_2'    => ['type'=>'placeholder','icon'=>'🚧','title_key'=>'admin.tile.coming_soon','desc_key'=>'admin.tile.coming_soon_desc'],
    'placeholder_3'    => ['type'=>'placeholder','icon'=>'🚧','title_key'=>'admin.tile.coming_soon','desc_key'=>'admin.tile.coming_soon_desc'],
];

// JSON API: save / reset the current user's board layout (fetch from the Edit Layout UI).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
    header('Content-Type: application/json');
    $body = json_decode(file_get_contents('php://input'), true);
    $action = $body['action'] ?? '';
    if (in_array($action, ['save_admin_layout', 'reset_admin_layout'], true)) {
        if (!wcc_csrf_valid($body['csrf'] ?? null)) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Security check failed — reload the page and retry.']);
            exit;
        }
        try {
            if ($action === 'save_admin_layout') {
                $order = $body['order'] ?? null;
                if (!is_array($order)) {
                    echo json_encode(['status' => 'error', 'message' => 'Invalid layout payload.']);
                    exit;
                }
                // Whitelist against the registry, no duplicates, keep the given order.
                $clean = [];
                foreach ($order as $id) {
                    if (is_string($id) && isset($ADMIN_TILES[$id]) && !in_array($id, $clean, true)) {
                        $clean[] = $id;
                    }
                }
                if (count($clean) !== count($order)) {
                    echo json_encode(['status' => 'error', 'message' => 'Layout contained unknown tiles — not saved.']);
                    exit;
                }
                $pdo->prepare("UPDATE users SET admin_layout_json = ? WHERE user_id = ?")
                    ->execute([json_encode($clean), $_SESSION['user_id']]);
                echo json_encode(['status' => 'success', 'message' => 'Layout saved.']);
            } else {
                $pdo->prepare("UPDATE users SET admin_layout_json = NULL WHERE user_id = ?")
                    ->execute([$_SESSION['user_id']]);
                echo json_encode(['status' => 'success', 'message' => 'Layout reset to default.']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Save failed: ' . $e->getMessage()]);
        }
        exit;
    }
    echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
    exit;
}

// Resolve this user's tile order: saved order first (stale ids dropped),
// then any tiles missing from the saved list appended in registry order.
$tile_order = array_keys($ADMIN_TILES);
try {
    $st = $pdo->prepare("SELECT admin_layout_json FROM users WHERE user_id = ?");
    $st->execute([$_SESSION['user_id']]);
    $saved = json_decode($st->fetchColumn() ?: 'null', true);
    if (is_array($saved)) {
        $known = array_values(array_intersect($saved, array_keys($ADMIN_TILES)));
        $tile_order = array_merge($known, array_values(array_diff(array_keys($ADMIN_TILES), $known)));
    }
} catch (Exception $e) { /* column missing pre-migration → default order */ }

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
        wcc_csrf_require();
        $pdo->prepare("DELETE FROM workshops WHERE workshop_id = ?")->execute([$_GET['delete_workshop']]);
        header("Location: /_mgmt/admin_panel.php"); exit;
    }
    if (isset($_GET['delete_line'])) {
        wcc_csrf_require();
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
        wcc_csrf_require();
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
            if (!empty($assigned)) {
                require_once __DIR__ . '/../inc/notifications.php';
                wcc_notify((int)$assigned, 'wo_assigned', 'Work order assigned to you: ' . $title . ' (due ' . $date . ')', '/_maint/work_orders.php', 'info');
            }
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

        // Which event classes count as a failure (for MTBF). Validate against the
        // known taxonomy so nothing arbitrary lands in the setting.
        $picked = array_values(array_intersect((array)($_POST['failure_classes'] ?? []), array_keys(WCC_EVENT_CLASSES)));
        if (empty($picked)) $picked = WCC_EVENT_CLASS_DEFAULT_FAILURES;   // never zero classes → MTBF would vanish
        $pdo->prepare("INSERT INTO app_settings (category, setting_key, setting_value) VALUES ('KPI','kpi_failure_classes',?)
                       ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([json_encode($picked)]);

        header("Location: /_mgmt/admin_panel.php?msg=kpi_updated"); exit;
    }

    // ── Inventory Health config: warning band % + per-part lifecycle ──────────
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($_POST['action'] ?? '') === 'save_inventory_health') {
        wcc_csrf_require();
        $pct = max(0, min(200, (int)($_POST['stock_warn_pct'] ?? 25)));
        // upsert without needing a migration
        $pdo->prepare("INSERT INTO app_settings (category, setting_key, setting_value) VALUES ('Inventory','stock_warn_pct',?)
                       ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$pct]);
        header("Location: /_mgmt/admin_panel.php?msg=inv_health_saved#top"); exit;
    }
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($_POST['action'] ?? '') === 'set_part_lifecycle') {
        wcc_csrf_require();
        $pid    = (int)($_POST['part_id'] ?? 0);
        $status = $_POST['lifecycle_status'] ?? 'Active';
        if (in_array($status, ['Active', 'Phasing Out', 'Obsolete'], true) && $pid > 0) {
            $pdo->prepare("UPDATE inventory_parts SET lifecycle_status = ? WHERE part_id = ?")->execute([$status, $pid]);
        }
        header("Location: /_mgmt/admin_panel.php?msg=lifecycle_saved#top"); exit;
    }

    // stock_warn_pct default (upsert-on-read, same pattern as the KPI block below)
    $invHealth = ['stock_warn_pct' => '25'];
    $st = $pdo->prepare("SELECT setting_value FROM app_settings WHERE category='Inventory' AND setting_key='stock_warn_pct'");
    $st->execute();
    if ($st->rowCount() === 0) {
        $pdo->prepare("INSERT INTO app_settings (category, setting_key, setting_value) VALUES ('Inventory','stock_warn_pct','25')")->execute();
    } else {
        $invHealth['stock_warn_pct'] = $st->fetchColumn();
    }
    // Parts for the lifecycle manager (all, with current status).
    $invParts = $pdo->query("SELECT part_id, internal_code, part_name, stock_level, minimum_threshold, lifecycle_status
                             FROM inventory_parts ORDER BY part_name ASC")->fetchAll(PDO::FETCH_ASSOC);

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

    // Which event classes currently count as a failure (for the checkboxes).
    $kpi_failure_classes = wcc_kpi_failure_classes($pdo);
    // Seed the setting on first view so the row exists for the UI.
    $hasFC = $pdo->prepare("SELECT 1 FROM app_settings WHERE setting_key='kpi_failure_classes'");
    $hasFC->execute();
    if ($hasFC->rowCount() === 0) {
        $pdo->prepare("INSERT INTO app_settings (category, setting_key, setting_value) VALUES ('KPI','kpi_failure_classes',?)")
            ->execute([json_encode($kpi_failure_classes)]);
    }

    // Fetch data for modals
    $workshops    = $pdo->query("SELECT * FROM workshops ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $lines        = $pdo->query("SELECT l.*, w.name as workshop_name FROM production_lines l JOIN workshops w ON l.workshop_id = w.workshop_id ORDER BY w.name ASC, l.name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $all_equipment= $pdo->query("SELECT equip_id as equipment_id, equip_name as name, workshop_id, line_id FROM equipment ORDER BY equip_name ASC")->fetchAll(PDO::FETCH_ASSOC);
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
<?php
$page_title = __('admin.control_title');
require_once __DIR__ . '/../inc/head.php';
?>
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
        /* Edit-layout mode: tiles become drag handles, clicks are suppressed */
        .panel-grid.editing .setting-card { cursor: grab !important; outline: 2px dashed var(--text-accent); outline-offset: -2px; opacity: 1 !important; }
        .panel-grid.editing .setting-card:active { cursor: grabbing !important; }
        .panel-grid.editing .setting-card:hover { transform: none; }
        .setting-card.dragging { opacity: 0.45 !important; }
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
<?php include __DIR__ . '/../nav.php'; ?>
<?php require_once __DIR__ . '/../rbac.php'; ?>

<div class="dashboard-container">
    <div class="page-header">
        <h1>🛡️ <?= __e('admin.control_title') ?></h1>
        <div style="display:flex; gap:10px; align-items:center;">
            <button type="button" id="editLayoutBtn" class="btn" onclick="enterLayoutEdit()">✏️ <?= __e('admin.edit_layout') ?></button>
            <div id="layoutEditControls" style="display:none; gap:10px; align-items:center;">
                <span style="color:var(--text-secondary); font-size:0.85em;"><?= __e('admin.drag_hint') ?></span>
                <button type="button" class="btn btn-primary" onclick="saveLayout()">💾 <?= __e('admin.save_layout') ?></button>
                <button type="button" class="btn" onclick="resetLayout()">↺ <?= __e('admin.reset_layout') ?></button>
                <button type="button" class="btn" onclick="location.reload()"><?= __e('btn.cancel') ?></button>
            </div>
    <?= wcc_demo_notice("You are exploring the public WCC demo — everything is browsable; actions that would destroy the showcase (database tools, deleting records, account changes) are disabled.") ?>
        </div>
    </div>

    <div class="panel-grid" id="panelGrid">
        <?php foreach ($tile_order as $tid): $tile = $ADMIN_TILES[$tid]; ?>
        <?php if ($tile['type'] === 'link'): ?>
        <a href="<?= $tile['href'] ?>" class="setting-card" data-tile-id="<?= $tid ?>">
            <div style="font-size:3em;"><?= $tile['icon'] ?></div>
            <h3><?= __e($tile['title_key']) ?></h3>
            <p><?= __e($tile['desc_key']) ?></p>
        </a>
        <?php elseif ($tile['type'] === 'modal'): ?>
        <div class="setting-card" data-tile-id="<?= $tid ?>" onclick="document.getElementById('<?= $tile['modal'] ?>').style.display='block'">
            <div style="font-size:3em;"><?= $tile['icon'] ?></div>
            <h3><?= __e($tile['title_key']) ?></h3>
            <p><?= __e($tile['desc_key']) ?></p>
        </div>
        <?php else: ?>
        <div class="setting-card" data-tile-id="<?= $tid ?>" style="opacity: 0.4; cursor: default;">
            <div style="font-size:3em;"><?= $tile['icon'] ?></div>
            <h3><?= __e($tile['title_key']) ?></h3>
            <p><?= __e($tile['desc_key']) ?></p>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="settings-link-bar">
        <span>⚙️ <?= __e('admin.settings_hint') ?></span>
        <a href="/_mgmt/app_settings.php"><?= __e('admin.system_settings_link') ?></a>
<br><br>
<a href="/_mgmt/admin_backup.php" style="color:#38bdf8;">🗄️ <?= __e('admin.data_admin') ?></a>
    </div>
</div>

<!-- Add Inventory Part Modal -->
<div id="addModal" class="modal">
  <div class="modal-content enterprise-modal">
    <span class="close" onclick="document.getElementById('addModal').style.display='none'">&times;</span>
    <h2><?= __e('admin.section.spare_part') ?></h2>
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
        <button type="submit" class="pill-btn pill-success pill-block" style="margin-top:30px;">💾 Save Enterprise Part</button>
    </form>
  </div>
</div>

<!-- Inventory Health Modal — warning band + per-part lifecycle -->
<div id="invHealthModal" class="modal">
  <!-- explicit width: .modal-content is fixed at 400px, so max-width alone can't widen it -->
  <div class="modal-content" style="width:min(720px, 94vw); max-width:none;">
    <span class="close" onclick="document.getElementById('invHealthModal').style.display='none'">&times;</span>
    <h2 style="margin:0 0 6px 0; font-size:1.15em; color:var(--text-primary);">🩺 <?= __e('admin.section.inv_health') ?></h2>
    <p style="margin:0 0 18px 0; color:var(--text-secondary); font-size:0.9em;">
        Controls the stock-status badges on the Inventory page.
    </p>

    <!-- Warning band -->
    <form method="POST" action="/_mgmt/admin_panel.php" style="margin-bottom:22px; padding:14px; background:var(--surface-1); border:1px solid var(--panel-border); border-radius:var(--radius-md,8px);">
        <input type="hidden" name="csrf" value="<?= wcc_csrf_token() ?>">
        <input type="hidden" name="action" value="save_inventory_health">
        <label style="display:block; font-weight:700; color:var(--text-primary); margin-bottom:6px;">Warning band (approaching minimum)</label>
        <p style="margin:0 0 10px 0; color:var(--text-secondary); font-size:0.85em;">
            How far <em>above</em> the minimum still shows the amber "approaching" warning. A part flags amber
            once its stock falls within this percentage of its minimum, and red once it hits the minimum.
        </p>
        <div style="display:flex; align-items:center; gap:10px;">
            <input type="number" name="stock_warn_pct" min="0" max="200" value="<?= htmlspecialchars($invHealth['stock_warn_pct']) ?>"
                   style="width:90px; padding:8px; border-radius:6px; background:rgba(0,0,0,0.2); border:1px solid var(--panel-border); color:var(--text-primary);">
            <span style="color:var(--text-secondary);">% above minimum</span>
            <button type="submit" class="pill-btn pill-success pill-sm" style="margin-left:auto;">Save band</button>
        </div>
        <div style="margin-top:8px; font-size:0.8em; color:var(--text-muted);">
            Example at 25%: a part with a minimum of 12 shows amber at 13–15, red at 12 or below.
        </div>
    </form>

    <!-- Per-part lifecycle -->
    <label style="display:block; font-weight:700; color:var(--text-primary); margin-bottom:6px;">Part lifecycle</label>
    <p style="margin:0 0 10px 0; color:var(--text-secondary); font-size:0.85em;">
        Marking a part <strong>Phasing Out</strong> or <strong>Obsolete</strong> stops it showing the red "reorder"
        alarm (it can no longer be auto-reordered) and flags it grey — "find a replacement" rather than "order more".
    </p>
    <input type="search" id="invLifeSearch" placeholder="<?= __e('admin.filter_parts') ?>" onkeyup="wccFilterLifeRows(this.value)"
           style="width:100%; padding:8px 10px; margin-bottom:10px; border-radius:6px; background:rgba(0,0,0,0.2); border:1px solid var(--panel-border); color:var(--text-primary);">
    <div style="max-height:320px; overflow-y:auto; border:1px solid var(--panel-border); border-radius:8px;">
        <table class="data-table" style="width:100%; font-size:0.9em;">
            <thead><tr>
                <th style="position:sticky; top:0; background:var(--modal-bg);">Part</th>
                <th style="position:sticky; top:0; background:var(--modal-bg); width:90px;">Stock</th>
                <th style="position:sticky; top:0; background:var(--modal-bg); width:190px;">Lifecycle</th>
            </tr></thead>
            <tbody>
            <?php foreach ($invParts as $p): ?>
                <tr class="inv-life-row" data-search="<?= htmlspecialchars(strtolower($p['part_name'] . ' ' . $p['internal_code'])) ?>">
                    <td>
                        <strong style="color:var(--text-primary);"><?= htmlspecialchars($p['part_name']) ?></strong><br>
                        <span style="font-family:monospace; color:var(--text-muted); font-size:0.85em;"><?= htmlspecialchars($p['internal_code']) ?></span>
                    </td>
                    <td style="<?= $p['stock_level'] <= $p['minimum_threshold'] ? 'color:var(--danger); font-weight:bold;' : '' ?>">
                        <?= (int)$p['stock_level'] ?> / <?= (int)$p['minimum_threshold'] ?>
                    </td>
                    <td>
                        <form method="POST" action="/_mgmt/admin_panel.php" style="display:flex; gap:6px; align-items:center; margin:0;">
                            <input type="hidden" name="csrf" value="<?= wcc_csrf_token() ?>">
                            <input type="hidden" name="action" value="set_part_lifecycle">
                            <input type="hidden" name="part_id" value="<?= (int)$p['part_id'] ?>">
                            <select name="lifecycle_status" onchange="this.form.requestSubmit()"
                                    style="flex:1; padding:5px; border-radius:5px; background:rgba(0,0,0,0.2); border:1px solid var(--panel-border); color:var(--text-primary); font-size:0.85em;">
                                <?php foreach (['Active','Phasing Out','Obsolete'] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= ($p['lifecycle_status'] ?? 'Active') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div id="invLifeNoMatch" style="display:none; padding:10px; color:var(--text-muted); font-style:italic;">No part matches that.</div>
  </div>
</div>
<script>
function wccFilterLifeRows(q) {
    q = (q || '').toLowerCase().trim();
    var shown = 0;
    document.querySelectorAll('.inv-life-row').forEach(function (r) {
        var hit = !q || (r.dataset.search || '').indexOf(q) > -1;
        r.style.display = hit ? '' : 'none';
        if (hit) shown++;
    });
    var nm = document.getElementById('invLifeNoMatch');
    if (nm) nm.style.display = shown ? 'none' : 'block';
}
</script>

<!-- Documents Management Modal -->
<div id="docsModal" class="modal">
  <div class="modal-content enterprise-modal" style="max-width: 600px !important;">
    <span class="close" onclick="document.getElementById('docsModal').style.display='none'">&times;</span>
    <h2>📁 <?= __e('admin.section.documents') ?></h2>
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

        <button type="button" class="pill-btn pill-info pill-block" onclick="submitDocUpload()">⬆️ Upload &amp; Link Document</button>
    </form>
  </div>
</div>

<script>
    async function submitDocUpload() {
        const form = document.getElementById('docUploadForm');
        if(!form.reportValidity()) return;
        const formData = new FormData(form);
        if (typeof wccCsrfToken === 'function') {
            formData.set('csrf', wccCsrfToken());
        } else if (window.WCC_CSRF) {
            formData.set('csrf', window.WCC_CSRF);
        }
        const btn = form.querySelector('button');
        btn.disabled = true; btn.innerText = 'Uploading...';
        
        try {
            const resp = await fetch('/api/upload_document.php', {
                method: 'POST',
                headers: (typeof wccCsrfToken === 'function' && wccCsrfToken())
                    ? { 'X-CSRF-Token': wccCsrfToken() }
                    : {},
                body: formData
            });
            const res = await resp.json();
            if(res.status === 'success') {
                if(typeof openWccAlert === 'function') {
                    openWccAlert('Success', res.message, '/_mgmt/admin_panel.php');
                } else {
                    showToast(res.message, "success"); setTimeout(function(){ window.location.reload(); }, 800);
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
    <h2>🏭 <?= __e('admin.section.workshops') ?></h2>
    <div class="grid-2" style="margin-top:20px;">
        <div class="form-section" style="border-top:none;margin-top:0;padding-top:0;">
            <h3>Create New Workshop</h3>
            <form method="POST" style="background:rgba(0,0,0,0.1);padding:15px;border-radius:8px;border:1px solid var(--panel-border);">
                <input type="hidden" name="action" value="add_workshop">
                <label>Workshop Name *</label>
                <input type="text" name="workshop_name" placeholder="e.g. Assembly Plant Alpha" required style="width:100%;box-sizing:border-box;margin-bottom:10px;">
                <label>Location Details</label>
                <input type="text" name="workshop_location" placeholder="e.g. Building 2, Floor 1" style="width:100%;box-sizing:border-box;margin-bottom:15px;">
                <button type="submit" class="pill-btn pill-success pill-block">+ Add Workshop</button>
            </form>
            <h4 style="margin-top:20px;color:var(--text-accent);">Existing Workshops</h4>
            <table class="data-table" style="font-size:0.9em;">
                <thead><tr><th>Name</th><th>Location</th><th>Act</th></tr></thead>
                <tbody>
                    <?php foreach($workshops as $w): ?>
                    <tr>
                        <td><?= htmlspecialchars($w['name']) ?></td>
                        <td><?= htmlspecialchars($w['location']) ?></td>
                        <td><a href="#" onclick="openWccConfirm('Delete this workshop and ALL its lines?', '?delete_workshop=<?= $w['workshop_id'] ?>&csrf=<?= wcc_csrf_token() ?>', 'Delete Workshop'); return false;" class="pill-btn pill-danger pill-sm">✕</a></td>
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
                <button type="submit" class="pill-btn pill-warning pill-block">+ Allocate Line</button>
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
                            <td><a href="#" onclick="openWccConfirm('Delete this line?', '?delete_line=<?= $l['line_id'] ?>&csrf=<?= wcc_csrf_token() ?>', 'Delete Line'); return false;" class="pill-btn pill-danger pill-sm">✕</a></td>
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
    <h2>🗓️ <?= __e('admin.section.pm_config') ?></h2>
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
                <button type="submit" class="pill-btn pill-success pill-block" style="margin-top:15px;">+ Save PM Schedule</button>
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
    <h2>📝 <?= __e('admin.section.adhoc_wo') ?></h2>
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
        <button type="submit" class="pill-btn pill-info pill-block" style="margin-top:20px;">+ Create Work Order</button>
    </form>
  </div>
</div>

<!-- Checklist Config Modal -->
<div id="checklistModal" class="modal">
  <div class="modal-content enterprise-modal">
    <span class="close" onclick="document.getElementById('checklistModal').style.display='none'">&times;</span>
    <h2>✅ <?= __e('admin.section.pm_checklists') ?></h2>
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
                    <button type="button" class="pill-btn pill-warning pill-sm" onclick="addChecklistItem()">+ Add Row</button>
                </div>
                <div id="checklist_items_container" style="margin-top:10px; border-left: 2px solid var(--text-accent); padding-left: 10px;">
                    <div style="display:grid; grid-template-columns: 1fr 80px 30px; gap:10px; margin-bottom:5px;">
                        <input type="text" name="task_desc[]" placeholder="Task Description" required>
                        <input type="number" name="task_time[]" placeholder="Mins" min="1" required title="Expected Minutes">
                        <div></div>
                    </div>
                </div>

                <button type="submit" class="pill-btn pill-info pill-block" style="margin-top:15px;">+ Save Checklist</button>
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
                                <a href="?delete_checklist=<?= $cl['checklist_id'] ?>&csrf=<?= wcc_csrf_token() ?>" onclick="return confirm('Permanently delete this checklist?');" class="pill-btn pill-danger pill-sm" title="Delete">✕</a>
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
  <div class="modal-content" style="width:min(760px, 94vw); max-width:none;">
    <span class="close" onclick="document.getElementById('kpiModal').style.display='none'">&times;</span>
    <h2>📈 <?= __e('admin.section.kpi_targets') ?></h2>
    <form method="POST">
        <input type="hidden" name="action" value="save_kpi_targets">
        <div class="form-section" style="border-top:none;margin-top:0;padding-top:0;">
            <p style="color:var(--text-secondary); font-size: 0.9em; margin-bottom: 20px;">
                These baseline targets govern the dashboard performance analysis.
            </p>
            <div style="margin-bottom: 20px;">
                <label style="color:var(--text-secondary); display:block; margin-bottom: 5px; font-weight:bold;">
                    Calculation Mode
                    <button type="button" id="kpiModeHelpBtn" onclick="wccShowKpiModeHelp(true); event.stopPropagation();"
                            title="How are these targets calculated?" aria-label="How are these targets calculated?"
                            style="width:20px; height:20px; padding:0; border-radius:50%; cursor:pointer; line-height:1;
                                   border:1px solid var(--text-accent); background:transparent; color:var(--text-accent);
                                   font-size:0.78em; font-weight:700; display:none; align-items:center;
                                   justify-content:center; vertical-align:middle; margin-left:6px;">?</button>
                </label>
                <select name="target_calc_mode" id="targetCalcMode" onchange="toggleKpiInputs()" style="width: 100%; padding:10px; border-radius:6px; background:rgba(0,0,0,0.2); border:1px solid var(--panel-border); color:white; box-sizing: border-box;">
                    <option value="static" <?= ($kpi_settings['target_calc_mode'] ?? 'static') === 'static' ? 'selected' : '' ?>>Static Baseline Targets</option>
                    <option value="dynamic" <?= ($kpi_settings['target_calc_mode'] ?? 'static') === 'dynamic' ? 'selected' : '' ?>>Dynamic (3-Month Rolling Average)</option>
                </select>
                <div style="font-size: 0.8em; color:var(--text-secondary); margin-top:5px;">Dynamic mode automatically computes targets based on the previous 3 months.</div>
            </div>

            <div id="kpiStaticInputs" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(190px, 1fr)); gap:16px;">
                <div>
                    <label style="color:var(--text-secondary); display:block; margin:0 0 6px;">Target MTTA — Response (min)</label>
                    <input type="number" name="target_mttd" id="target_mttd_input" value="<?= htmlspecialchars($kpi_settings['target_mttd'] ?? 60) ?>" required min="1" style="width: 100%; padding:10px; border-radius:6px; background:rgba(0,0,0,0.2); border:1px solid var(--panel-border); color:var(--text-primary); box-sizing: border-box;">
                </div>
                <div>
                    <label style="color:var(--text-secondary); display:block; margin:0 0 6px;">Target MTTR (min)</label>
                    <input type="number" name="target_mttr" id="target_mttr_input" value="<?= htmlspecialchars($kpi_settings['target_mttr'] ?? 120) ?>" required min="1" style="width: 100%; padding:10px; border-radius:6px; background:rgba(0,0,0,0.2); border:1px solid var(--panel-border); color:var(--text-primary); box-sizing: border-box;">
                </div>
                <div>
                    <label style="color:var(--text-secondary); display:block; margin:0 0 6px;">Target MTBF (hours)</label>
                    <input type="number" name="target_mtbf" id="target_mtbf_input" value="<?= htmlspecialchars($kpi_settings['target_mtbf'] ?? 48) ?>" required min="1" style="width: 100%; padding:10px; border-radius:6px; background:rgba(0,0,0,0.2); border:1px solid var(--panel-border); color:var(--text-primary); box-sizing: border-box;">
                </div>
            </div>
        </div>

        <div style="margin-top:18px; padding-top:16px; border-top:1px solid var(--panel-border);">
            <div style="color:var(--text-primary); font-weight:700; margin-bottom:4px;">Which events count as a failure (for MTBF)</div>
            <div style="font-size: 0.8em; color:var(--text-secondary); margin-bottom:12px; line-height:1.5;">
                Unticked classes still count as downtime and availability loss — they just are not
                <em>breakdowns</em>. Every ticket defaults to <strong>Failure</strong>, so nothing changes until you retick.
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(230px, 1fr)); gap:8px 16px;">
                <?php foreach (WCC_EVENT_CLASSES as $ck => $clabel):
                    $checked = in_array($ck, $kpi_failure_classes, true); ?>
                <label style="display:flex !important; align-items:center; gap:10px; margin:0; padding:9px 12px; font-weight:600; text-align:left; color:var(--text-primary); font-size:0.9em; background:rgba(0,0,0,0.15); border:1px solid var(--panel-border); border-radius:8px; cursor:pointer;">
                    <input type="checkbox" name="failure_classes[]" value="<?= htmlspecialchars($ck) ?>" <?= $checked ? 'checked' : '' ?> style="width:16px; height:16px; flex:0 0 auto; margin:0; accent-color: var(--text-accent); cursor:pointer;">
                    <span><?= htmlspecialchars($clabel) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" class="pill-btn pill-success pill-block" style="margin-top:18px;">💾 Save KPI Targets</button>
    </form>
  </div>
</div>

<!-- Stacked ON TOP of #kpiModal, not nested inside it: .modal sits at z-index 10000,
     so this needs to outrank it. Sibling placement also keeps it out of the KPI
     <form>, where a stray input could otherwise be submitted with the targets. -->
<div id="kpiModeHelp" class="modal" style="z-index: 10001;" role="dialog" aria-modal="true" aria-labelledby="kpiModeHelpTitle">
  <!-- Explicit width: .modal-content is fixed at 400px, so max-width alone leaves it
       narrow and clips the formula block. -->
  <div class="modal-content" style="width:min(660px, 92vw); max-width:none;">
    <span class="close" onclick="wccShowKpiModeHelp(false)">&times;</span>
    <h2 id="kpiModeHelpTitle" style="margin:0 0 14px 0; font-size:1.15em; color:var(--text-primary);">
        📐 How the dashed target lines are calculated
    </h2>

    <p style="margin:0 0 10px 0; color:var(--text-secondary); line-height:1.6; font-size:0.9em;">
        <strong style="color:var(--text-primary);">Static Baseline</strong> — the dashed line sits at the fixed
        numbers you type in, in every month. Use it when you have a target you are held to
        (a contract, an SLA, a management commitment) and want to see how far from it you are.
    </p>

    <p style="margin:0 0 14px 0; color:var(--text-secondary); line-height:1.6; font-size:0.9em;">
        <strong style="color:var(--text-primary);">Dynamic (3-month rolling)</strong> — each month's target is
        computed from the <em>three months immediately before it</em>, so the line moves with you and the
        question becomes "are we better than we recently were?" rather than "did we hit a fixed number?".
        The typed values are ignored, except as a fallback when those three months contain no data.
    </p>

    <div style="background: var(--surface-1); border: 1px solid var(--panel-border); border-radius: 6px; padding: 12px; font-family: ui-monospace, monospace; font-size: 0.8em; color: var(--text-primary); line-height:1.9; overflow-x:auto;">
        target_MTTA(m) = Σ response_minutes(m-1 … m-3) ÷ Σ interventions(m-1 … m-3)<br>
        target_MTTR(m) = Σ repair_minutes(m-1 … m-3) ÷ Σ interventions(m-1 … m-3)<br>
        target_MTBF(m) = Σ uptime_hours(m-1 … m-3) ÷ Σ failures(m-1 … m-3)
    </div>

    <ul style="margin:14px 0 0 0; padding-left:20px; color:var(--text-secondary); line-height:1.7; font-size:0.87em;">
        <li>It is a <strong>weighted</strong> average, not an average of the three monthly averages — a busy month counts more, so one quiet month cannot skew the target.</li>
        <li>Only the <strong>previous</strong> three months are used. The current month never contributes to its own target.</li>
        <li>MTTA/MTTR targets are in minutes and <strong>lower is better</strong>; MTBF is in hours and <strong>higher is better</strong>.</li>
        <li>A month whose preceding window has no recorded failures falls back to the static value you typed.</li>
    </ul>
  </div>
</div>

<script>
    // Opens only on an explicit click of the "?" — never automatically. The button
    // itself is only offered in Dynamic mode (see toggleKpiInputs), so the help
    // appears when the condition is met AND the admin asks for it.
    function wccShowKpiModeHelp(show) {
        const box = document.getElementById('kpiModeHelp');
        if (!box) return;
        box.style.display = (show === false) ? 'none' : 'block';
    }

    function toggleKpiInputs() {
        const mode = document.getElementById('targetCalcMode').value;

        // The "?" is only offered in Dynamic mode — that is the mode whose numbers stop
        // matching what was typed, so it is where an explanation is worth asking for.
        // It never opens by itself; the admin has to click it.
        const helpBtn = document.getElementById('kpiModeHelpBtn');
        if (helpBtn) helpBtn.style.display = (mode === 'dynamic') ? 'inline-flex' : 'none';
        if (mode !== 'dynamic') wccShowKpiModeHelp(false);

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

    // ---- Editable board layout (drag to reorder, per-user save) ----
    const WCC_CSRF = '<?= wcc_csrf_token() ?>';
    const panelGrid = document.getElementById('panelGrid');
    let layoutEditing = false;
    let draggedCard = null;

    function enterLayoutEdit() {
        layoutEditing = true;
        panelGrid.classList.add('editing');
        document.getElementById('editLayoutBtn').style.display = 'none';
        document.getElementById('layoutEditControls').style.display = 'flex';
        panelGrid.querySelectorAll('.setting-card').forEach(c => c.setAttribute('draggable', 'true'));
    }

    function exitLayoutEdit() {
        layoutEditing = false;
        panelGrid.classList.remove('editing');
        document.getElementById('editLayoutBtn').style.display = '';
        document.getElementById('layoutEditControls').style.display = 'none';
        panelGrid.querySelectorAll('.setting-card').forEach(c => c.removeAttribute('draggable'));
    }

    // While editing, swallow tile clicks so links don't navigate and modals don't open.
    panelGrid.addEventListener('click', function (e) {
        if (layoutEditing) { e.preventDefault(); e.stopPropagation(); }
    }, true);

    panelGrid.addEventListener('dragstart', function (e) {
        if (!layoutEditing) { e.preventDefault(); return; }
        draggedCard = e.target.closest('.setting-card');
        if (draggedCard) draggedCard.classList.add('dragging');
    });
    panelGrid.addEventListener('dragend', function () {
        if (draggedCard) draggedCard.classList.remove('dragging');
        draggedCard = null;
    });
    panelGrid.addEventListener('dragover', function (e) {
        if (!layoutEditing || !draggedCard) return;
        e.preventDefault();
        const over = e.target.closest('.setting-card');
        if (!over || over === draggedCard) return;
        const cards = [...panelGrid.querySelectorAll('.setting-card')];
        const overIdx = cards.indexOf(over), dragIdx = cards.indexOf(draggedCard);
        panelGrid.insertBefore(draggedCard, overIdx < dragIdx ? over : over.nextSibling);
    });

    async function saveLayout() {
        const order = [...panelGrid.querySelectorAll('.setting-card')].map(c => c.dataset.tileId);
        try {
            const resp = await fetch('/_mgmt/admin_panel.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'save_admin_layout', csrf: WCC_CSRF, order })
            });
            const res = await resp.json();
            showToast(res.message, res.status === 'success' ? 'success' : 'error');
            if (res.status === 'success') exitLayoutEdit();
        } catch (err) {
            showToast('Save failed — check your connection.', 'error');
        }
    }

    async function resetLayout() {
        try {
            const resp = await fetch('/_mgmt/admin_panel.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'reset_admin_layout', csrf: WCC_CSRF })
            });
            const res = await resp.json();
            if (res.status === 'success') { location.reload(); return; }
            showToast(res.message, 'error');
        } catch (err) {
            showToast('Reset failed — check your connection.', 'error');
        }
    }
</script>
</body>
</html>
