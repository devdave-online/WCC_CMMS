<?php
include 'auth.php';
require_perm('manage_settings');

$host = 'localhost'; $db = 'workshop_db'; $user = 'root'; $pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    
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
            echo "<script>localStorage.setItem('sessionExpiry', new Date().getTime() + ($new_time * 60 * 1000)); alert('Lockout time updated to $new_time minutes'); window.location.href='app_settings.php';</script>";
            exit;
        }
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
            echo "<script>alert('Enterprise Part successfully registered!'); window.location.href='app_settings.php';</script>";
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
    <title>App Settings - WCC</title>
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
    </style>
</head>
<body><?php include 'nav.php'; ?>
<?php require_once 'rbac.php'; ?>

<div class="dashboard-container dash-box">
    <div class="header-flex" style="border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px;">
        <h2>⚙️ Admin Control Panel</h2>
    </div>

    <div class="grid-2x3">
        <a href="users.php" class="setting-card">
            <div style="font-size: 3em;">👥</div>
            <h3>User Management</h3>
            <p>Role-Based Access Control & Accounts</p>
        </a>

        <a href="setup_vault_equipment.php" class="setting-card">
            <div style="font-size: 3em;">🔒</div>
            <h3>Enclosed Setup Vault</h3>
            <p>Admin Equipment Config</p>
        </a>

        <a href="setup_vault_vendors.php" class="setting-card">
            <div style="font-size: 3em;">🏢</div>
            <h3>Vendor Management</h3>
            <p>Supplier Database & Contacts</p>
        </a>
        
        <div class="setting-card" onclick="document.getElementById('addModal').style.display='block'" style="cursor:pointer;">
            <div style="font-size: 3em;">📦</div>
            <h3>Add Inventory Part</h3>
            <p>Register Enterprise Components</p>
        </div>
        
        <a href="purchase_orders.php" class="setting-card">
            <div style="font-size: 3em;">📝</div>
            <h3>PR / PO Management</h3>
            <p>Enterprise Procurement Engine</p>
        </a>

        <div class="setting-card" onclick="alert('Work Order Modal coming soon!')" style="cursor:pointer;">
            <div style="font-size: 3em;">🔧</div>
            <h3>Create Work Order</h3>
            <p>Schedule Maintenance Tasks</p>
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


        <div style="background: var(--panel-bg); padding: 20px; border-radius: 12px; border: 1px solid var(--panel-border); text-align: left; margin-top: 20px; box-shadow: inset 0 2px 10px rgba(0,0,0,0.1);">
            <h4 style="color: var(--text-accent); margin-top: 0;">Theme Settings</h4>
            <p style="color: var(--text-secondary); font-size: 0.9em;">Toggle between the Premium Dark Mode (Default) and the Original Light Mode aesthetics.</p>
            <button onclick="toggleTheme()" class="nav-btn primary" id="themeToggleButton">Toggle Light/Dark Mode</button>
            <script>
                // Update button text if needed based on current theme
                function updateToggleButtonText() {
                    const btn = document.getElementById('themeToggleButton');
                    if (document.body.classList.contains('light-theme')) {
                        btn.innerText = 'Switch to Dark Mode';
                    } else {
                        btn.innerText = 'Switch to Light Mode';
                    }
                }
                // Call it once on load
                updateToggleButtonText();
                
                // Override the toggleTheme from nav.php to also update the button text
                const originalToggle = toggleTheme;
                toggleTheme = function() {
                    originalToggle();
                    updateToggleButtonText();
                }
            </script>
        </div>
    </div>
</div>

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



</body>
</html>



