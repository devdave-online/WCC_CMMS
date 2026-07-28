<?php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('manage_vendors');

// Enterprise centralized DB connection (Phase 1 fix)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    // Handle form submission (Add, Update, Delete)
    $error_message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_vendor_id'])) {
            $del_id = $_POST['delete_vendor_id'];
            $stmt = $pdo->prepare("DELETE FROM vendors_suppliers WHERE vendor_id = ?");
            $stmt->execute([$del_id]);
            header("Location: /_logi/setup_vault_vendors.php");
            exit;
        }

        $id = !empty($_POST['vendor_id']) ? $_POST['vendor_id'] : null;
        $name = $_POST['vendor_name'] ?? '';
        $primary_contact = $_POST['primary_contact_name'] ?? '';
        $email = $_POST['contact_email'] ?? '';
        $phone = $_POST['contact_phone'] ?? '';
        $emergency = $_POST['emergency_contact'] ?? '';
        $payment_terms = $_POST['payment_terms'] ?? '';
        $address = $_POST['vendor_address'] ?? '';
        $shipping = $_POST['shipping_time'] ?? '';
        $vendor_type = $_POST['vendor_type'] ?? '';
        $vendor_remarks = $_POST['vendor_remarks'] ?? '';
        $rating = !empty($_POST['rating']) ? $_POST['rating'] : 5.00;
        
        if ($name !== '') {
            try {
                if ($id) {
                // Update
                $sql = "UPDATE vendors_suppliers SET 
                        vendor_name=?, primary_contact_name=?, contact_email=?, contact_phone=?, 
                        emergency_contact=?, payment_terms=?, vendor_address=?, shipping_time=?,
                        vendor_type=?, vendor_remarks=?, rating=? WHERE vendor_id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $primary_contact, $email, $phone, $emergency, $payment_terms, $address, $shipping, $vendor_type, $vendor_remarks, $rating, $id]);
            } else {
                // Insert
                $sql = "INSERT INTO vendors_suppliers 
                        (vendor_name, primary_contact_name, contact_email, contact_phone, emergency_contact, payment_terms, vendor_address, shipping_time, vendor_type, vendor_remarks, rating) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $primary_contact, $email, $phone, $emergency, $payment_terms, $address, $shipping, $vendor_type, $vendor_remarks, $rating]);
            }
            header("Location: /_logi/setup_vault_vendors.php");
            exit;
            } catch (PDOException $e) {
                if ($e->getCode() == 23000 || $e->getCode() == 1062) {
                    $error_message = "A vendor with the name '$name' already exists.";
                } else {
                    $error_message = "Database error: " . $e->getMessage();
                }
            }
        }
    }

    require_once __DIR__ . '/../rbac.php';
    $stmt = $pdo->query("SELECT * FROM vendors_suppliers ORDER BY vendor_id ASC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { die("DB Error: " . $e->getMessage()); }
?>
<?php
$page_title = 'Vendors Management';
require_once __DIR__ . '/../inc/head.php';
?>
    <style>
        .layer-panel {
            background: transparent !important;
            border: 1px solid var(--panel-border) !important;
            padding: 15px;
            border-radius: 8px;
            box-shadow: none !important;
        }
        .data-pair {
            margin-bottom: 8px;
            display: flex;
            font-size: 0.9em;
        }
        .data-pair strong {
            color: var(--text-secondary);
            display: inline-block;
            width: 200px;
        }
        .data-pair span {
            color: var(--text-primary);
            font-family: monospace;
        }
        .filter-token {
            background: var(--panel-bg);
            border: 1px solid var(--text-accent);
            border-radius: 16px;
            padding: 4px 12px;
            font-size: 0.85em;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            animation: fadeIn 0.2s ease-out;
        }
        .filter-token span { font-weight: bold; color: var(--text-accent); }
        .filter-token-close { cursor: pointer; color: #ef4444; font-weight: bold; transition: transform 0.2s; }
        .filter-token-close:hover { transform: scale(1.2); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
    </style>
    <script>
        let activeFilters = [];
        let filterIdCounter = 0;
        let activeColumnIndex = -1;

        function getColumnName(index) {
            let th = document.querySelector("#vendorsTable thead tr").children[index];
            return th ? (th.innerText || th.textContent).trim() : "Column";
        }

        function createFilterToken(colIndex, query) {
            let colName = getColumnName(colIndex);
            let id = 'filter-' + filterIdCounter++;
            activeFilters.push({ id: id, colIndex: colIndex, query: query.toUpperCase() });
            
            let area = document.getElementById('activeFiltersArea');
            let token = document.createElement('div');
            token.id = id;
            token.className = 'filter-token';
            token.innerHTML = '<span>' + colName + ':</span> ' + query + ' <div class="filter-token-close" onclick="removeFilterToken(\'' + id + '\')">✖</div>';
            area.appendChild(token);
        }

        function removeFilterToken(id) {
            activeFilters = activeFilters.filter(f => f.id !== id);
            let token = document.getElementById(id);
            if (token) token.remove();
            filterTable();
        }

        function handleSearchInput(ev) {
            if (ev.key === 'Enter' && activeColumnIndex > -1 && ev.target.value.trim() !== '') {
                lockToken();
            } else {
                filterTable();
            }
        }

        function lockToken() {
            var input = document.getElementById("ledgerSearch");
            var query = input.value.trim();
            if (query !== '' && activeColumnIndex > -1) {
                createFilterToken(activeColumnIndex, query);
                input.value = '';
                resetSearchPosition();
            }
        }

        function filterTable() {
            var input = document.getElementById("ledgerSearch");
            var globalFilter = input.value.toUpperCase();
            var table = document.getElementById("vendorsTable");
            var tr = table.getElementsByClassName("parent-row");

            for (let i = 0; i < tr.length; i++) {
                let matchFound = true;
                let tds = tr[i].getElementsByTagName("td");

                for (let f of activeFilters) {
                    let cell = tds[f.colIndex];
                    if (cell) {
                        let txt = cell.textContent || cell.innerText;
                        if (txt.toUpperCase().indexOf(f.query) === -1) { matchFound = false; break; }
                    }
                }

                if (matchFound && globalFilter !== "") {
                    if (activeColumnIndex > -1) {
                        let cell = tds[activeColumnIndex];
                        if (cell) {
                            let txt = cell.textContent || cell.innerText;
                            if (txt.toUpperCase().indexOf(globalFilter) === -1) matchFound = false;
                        }
                    } else {
                        let globalMatch = false;
                        for (let j = 0; j < tds.length; j++) { 
                            if (tds[j]) {
                                let txt = tds[j].textContent || tds[j].innerText;
                                if (txt.toUpperCase().indexOf(globalFilter) > -1) { globalMatch = true; break; }
                            }
                        }
                        if (!globalMatch) matchFound = false;
                    }
                }

                if (matchFound) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                    tr[i].classList.remove('is-expanded');
                    let nextRow = tr[i].nextElementSibling;
                    if (nextRow && nextRow.classList.contains('child-row')) {
                        nextRow.style.display = "none";
                    }
                }
            }
        }

        function allowDrop(ev) { ev.preventDefault(); }
        function dragSearch(ev) { ev.dataTransfer.setData("text", ev.target.id); }
        function dropSearch(ev, thElement) {
            ev.preventDefault();
            var container = document.getElementById("searchContainerOrig");
            var input = document.getElementById("ledgerSearch");
            var lockBtn = document.getElementById("lockTokenBtn");
            var colIndex = Array.from(thElement.parentNode.children).indexOf(thElement);
            var query = input.value.trim();

            if (query !== '') {
                createFilterToken(colIndex, query);
                input.value = '';
                resetSearchPosition();
            } else {
                thElement.appendChild(container);
                container.style.marginTop = '10px';
                input.style.width = '100%';
                input.placeholder = 'Type & click 📌 to Lock';
                activeColumnIndex = colIndex;
                lockBtn.style.display = 'block';
                input.focus();
                filterTable();
            }
        }

        function resetSearchPosition() {
            let wrapper = document.getElementById('searchWrapper');
            let container = document.getElementById('searchContainerOrig');
            let input = document.getElementById('ledgerSearch');
            let lockBtn = document.getElementById('lockTokenBtn');
            
            wrapper.appendChild(container);
            container.style.marginTop = '0';
            input.style.width = '';
            input.placeholder = 'Search vendors... (Drag to column. Dbl-Click Reset)';
            lockBtn.style.display = 'none';
            activeColumnIndex = -1;
            filterTable();
        }

        function openModal(data = null) {
            if (data) {
                document.getElementById('modalTitle').innerText = 'Configure Vendor Profile';
                document.getElementById('edit_vendor_id').value = data.vendor_id;
                document.getElementById('edit_vendor_name').value = data.vendor_name;
                document.getElementById('edit_primary_contact').value = data.primary_contact_name || '';
                document.getElementById('edit_email').value = data.contact_email || '';
                document.getElementById('edit_phone').value = data.contact_phone || '';
                document.getElementById('edit_emergency').value = data.emergency_contact || '';
                document.getElementById('edit_payment').value = data.payment_terms || '';
                document.getElementById('edit_address').value = data.vendor_address || '';
                document.getElementById('edit_shipping').value = data.shipping_time || '';
                document.getElementById('edit_vendor_type').value = data.vendor_type || '';
                document.getElementById('edit_remarks').value = data.vendor_remarks || '';
                document.getElementById('edit_rating').value = data.rating || '5.00';
            } else {
                document.getElementById('modalTitle').innerText = 'Register New Vendor';
                document.getElementById('edit_vendor_id').value = '';
                document.getElementById('edit_vendor_name').value = '';
                document.getElementById('edit_primary_contact').value = '';
                document.getElementById('edit_email').value = '';
                document.getElementById('edit_phone').value = '';
                document.getElementById('edit_emergency').value = '';
                document.getElementById('edit_payment').value = '';
                document.getElementById('edit_address').value = '';
                document.getElementById('edit_shipping').value = '';
                document.getElementById('edit_vendor_type').value = '';
                document.getElementById('edit_remarks').value = '';
                document.getElementById('edit_rating').value = '5.00';
            }
            document.getElementById('setupModal').style.display = 'block';
        }
    </script>
<?php include __DIR__ . '/../nav.php'; ?>

<div class="dashboard-container">
    <div class="page-header" style="margin-bottom:10px;">
        <h1>🏢 Vendor Vault Configuration</h1>
        <div style="display:flex; gap:10px; align-items:center;">
            <?php if (can('manage_settings')): ?>
            <a href="/_mgmt/admin_panel.php" class="nav-btn" style="white-space:nowrap;">← Return to Admin Panel</a>
            <?php endif; ?>
            <div id="searchWrapper">
                <div id="searchContainerOrig" style="display:inline-block; position:relative; width:100%;" draggable="true" ondragstart="dragSearch(event)">
                    <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); pointer-events:none; font-size:1.1em;">🔍</span>
                        <input type="text" id="ledgerSearch" onkeyup="handleSearchInput(event)" placeholder="Search vendors... (Drag to column. Dbl-Click Reset)" 
                        ondblclick="resetSearchPosition()"
                        style="padding:8px 35px 8px 35px; border-radius:20px; border: 1px solid var(--text-accent); background:var(--input-bg); color:var(--text-primary); transition: all 0.3s; box-sizing: border-box;">
                    <span id="lockTokenBtn" onclick="lockToken()" style="display:none; position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:1.1em; transition: transform 0.2s;" title="Lock Token" onmouseover="this.style.transform='translateY(-50%) scale(1.2)'" onmouseout="this.style.transform='translateY(-50%) scale(1)'">🔒</span>
                </div>
            </div>
            <button class="pill-btn pill-success" style="white-space:nowrap;" onclick="openModal()">+ Add Vendor</button>
        </div>
    </div>
    
    <div id="activeFiltersArea" style="display:flex; gap:8px; margin-bottom:15px; flex-wrap:wrap; min-height:30px;"></div>
    
    <div class="table-container" style="overflow-x: auto;">
        <table class="data-table" id="vendorsTable">
            <thead>
                <tr>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Vendor ID</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Vendor Name</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Type</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Primary Contact</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">24/7 Hotline</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Rating</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $i): ?>
                <tr class="parent-row" data-id="<?= $i['vendor_id'] ?>">
                    <td style="font-family: monospace; color: var(--text-secondary);">
                        <span class="row-arrow">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </span>
                        <?= htmlspecialchars($i['vendor_id']) ?>
                    </td>
                    <td style="font-weight:bold; color:var(--text-accent);"><?= htmlspecialchars($i['vendor_name']) ?></td>
                    <td><span class="prio-badge badge-normal" style="background:var(--child-bg); color:var(--text-secondary); border: 1px solid var(--panel-border);"><?= htmlspecialchars($i['vendor_type'] ?? 'Uncategorized') ?></span></td>
                    <td><?= htmlspecialchars($i['primary_contact_name'] ?? 'N/A') ?></td>
                    <td style="color:#ef4444; font-weight:bold;"><?= htmlspecialchars($i['emergency_contact'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($i['rating']) ?> / 5.00</td>
                    <td style="display: flex; gap: 5px;">
                        <button type="button" class="pill-btn pill-warning pill-sm"
                            onclick='openModal(<?= htmlspecialchars(json_encode($i), ENT_QUOTES, "UTF-8") ?>)'>Configure</button>
                        <form id="vendorForm_<?= htmlspecialchars($i['vendor_id']) ?>" method="POST" style="margin:0;">
                            <input type="hidden" name="delete_vendor_id" value="<?= htmlspecialchars($i['vendor_id']) ?>">
                            <button type="button" onclick="openWccConfirm('Are you sure you want to delete this vendor?', function() { document.getElementById('vendorForm_<?= htmlspecialchars($i['vendor_id']) ?>').submit(); }, 'Delete Vendor');" class="pill-btn pill-danger pill-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                <tr class="child-row" id="acc-<?= $i['vendor_id'] ?>">
                    <td colspan="12">
                        <div class="child-content" style="display: grid; grid-template-columns: 1fr; gap: 20px;">
                            <div class="layer-panel">
                                <h4 style="margin-top:0; border-bottom:1px solid var(--panel-border); padding-bottom:5px; color:var(--text-accent);">Vendor Master Profile</h4>
                                <div class="data-pair"><strong>Vendor Name:</strong> <span><?= htmlspecialchars($i['vendor_name']) ?></span></div>
                                <div class="data-pair"><strong>Vendor Type:</strong> <span><?= htmlspecialchars($i['vendor_type'] ?? 'N/A') ?></span></div>
                                <div class="data-pair"><strong>Primary Contact:</strong> <span><?= htmlspecialchars($i['primary_contact_name'] ?? 'N/A') ?></span></div>
                                <div class="data-pair"><strong>Email:</strong> <span><?= htmlspecialchars($i['contact_email'] ?? 'N/A') ?></span></div>
                                <div class="data-pair"><strong>Direct Phone:</strong> <span><?= htmlspecialchars($i['contact_phone'] ?? 'N/A') ?></span></div>
                                <div class="data-pair"><strong>Emergency/24-7 Contact:</strong> <span style="color:#f87171; font-weight:bold;"><?= htmlspecialchars($i['emergency_contact'] ?? 'N/A') ?></span></div>
                                <div class="data-pair"><strong>Payment Terms:</strong> <span><?= htmlspecialchars($i['payment_terms'] ?? 'N/A') ?></span></div>
                                <div class="data-pair"><strong>Address:</strong> <span><?= htmlspecialchars($i['vendor_address'] ?? 'N/A') ?></span></div>
                                <div class="data-pair"><strong>Shipping Time:</strong> <span><?= htmlspecialchars($i['shipping_time'] ?? 'N/A') ?></span></div>
                                <div class="data-pair"><strong>Remarks:</strong> <span><?= nl2br(htmlspecialchars($i['vendor_remarks'] ?? 'N/A')) ?></span></div>
                                <div class="data-pair"><strong>Added On:</strong> <span><?= htmlspecialchars($i['created_at']) ?></span></div>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Configuration Modal -->
<div id="setupModal" class="modal">
  <div class="modal-content" style="padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.5);">
    <span class="close" onclick="document.getElementById('setupModal').style.display='none'">&times;</span>
    <h2 id="modalTitle" style="color: var(--text-primary);">Register Vendor</h2>
    <?php if(!empty($error_message)): ?>
        <div style="background: rgba(239,68,68,0.2); border: 1px solid #ef4444; color: #ef4444; padding: 10px; border-radius: 8px; margin-bottom: 15px;">
            <?= htmlspecialchars($error_message) ?>
        </div>
        <script>
            window.onload = function() {
                openModal();
            }
        </script>
    <?php endif; ?>
    <form method="POST">
        <input type="hidden" name="vendor_id" id="edit_vendor_id">
        
        <div class="layer-box" style="padding: 20px; background: var(--card-bg); border-radius: 8px; border: 1px solid var(--panel-border); margin-bottom: 20px;">
            <h4 style="margin-top:0; border-bottom: 1px solid var(--panel-border); padding-bottom: 10px; color: var(--text-accent);">Vendor Master Profile</h4>
            
            <label style="color:var(--text-secondary); display:block; margin-top:10px;">Vendor Name * (Official legal entity)</label>
            <input type="text" name="vendor_name" id="edit_vendor_name" required style="width:100%; box-sizing:border-box; padding:8px; margin-bottom: 10px; border-radius:4px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary);">
            
            <label style="color:var(--text-secondary); display:block;">Vendor Category / Type</label>
            <select name="vendor_type" id="edit_vendor_type" style="width:100%; box-sizing:border-box; padding:8px; margin-bottom: 10px; border-radius:4px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary);">
                <option value="">Select Category</option>
                <option value="Equipment Vendor (OEM)">Equipment Vendor (OEM)</option>
                <option value="Spare Parts Vendor">Spare Parts Vendor</option>
                <option value="Auxiliary/Consumables Vendor">Auxiliary/Consumables Vendor</option>
                <option value="Service & Maintenance Contractor">Service & Maintenance Contractor</option>
                <option value="Software & IT Solutions">Software & IT Solutions</option>
                <option value="General Services & Logistics">General Services & Logistics</option>
            </select>
            
            <label style="color:var(--text-secondary); display:block;">Primary Contact Name</label>
            <input type="text" name="primary_contact_name" id="edit_primary_contact" style="width:100%; box-sizing:border-box; padding:8px; margin-bottom: 10px; border-radius:4px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary);">
            
            <label style="color:var(--text-secondary); display:block;">Primary Contact Email</label>
            <input type="email" name="contact_email" id="edit_email" style="width:100%; box-sizing:border-box; padding:8px; margin-bottom: 10px; border-radius:4px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary);">
            
            <label style="color:var(--text-secondary); display:block;">Primary Contact Phone</label>
            <input type="text" name="contact_phone" id="edit_phone" style="width:100%; box-sizing:border-box; padding:8px; margin-bottom: 10px; border-radius:4px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary);">
            
            <label style="color:var(--text-secondary); display:block;">Emergency/24-7 Contact (For line-down situations)</label>
            <input type="text" name="emergency_contact" id="edit_emergency" placeholder="Name & Phone" style="width:100%; box-sizing:border-box; padding:8px; margin-bottom: 10px; border-radius:4px; background:var(--input-bg); border:1px solid #f87171; color:var(--text-primary);">
            
            <label style="color:var(--text-secondary); display:block;">Payment Terms (e.g., Net 30, Net 60)</label>
            <input type="text" name="payment_terms" id="edit_payment" placeholder="e.g. Net 30" style="width:100%; box-sizing:border-box; padding:8px; margin-bottom: 10px; border-radius:4px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary);">
            
            <label style="color:var(--text-secondary); display:block;">Address</label>
            <textarea name="vendor_address" id="edit_address" placeholder="Physical or Mailing Address" style="width:100%; box-sizing:border-box; padding:8px; margin-bottom: 10px; border-radius:4px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary); resize:vertical;"></textarea>
            
            <label style="color:var(--text-secondary); display:block;">Shipping Time</label>
            <input type="text" name="shipping_time" id="edit_shipping" placeholder="e.g. 3-5 Business Days" style="width:100%; box-sizing:border-box; padding:8px; margin-bottom: 10px; border-radius:4px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary);">

            <label style="color:var(--text-secondary); display:block;">Vendor Remarks (Specialties, Items Provided, etc.)</label>
            <textarea name="vendor_remarks" id="edit_remarks" placeholder="e.g. Winding machines, sprays, pneumatic parts..." style="width:100%; box-sizing:border-box; padding:8px; margin-bottom: 10px; border-radius:4px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary); resize:vertical;"></textarea>
            
            <label style="color:var(--text-secondary); display:block;">Performance Rating (1.00 - 5.00)</label>
            <input type="number" step="0.01" min="1.00" max="5.00" name="rating" id="edit_rating" placeholder="5.00" style="width:100%; box-sizing:border-box; padding:8px; margin-bottom: 15px; border-radius:4px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary);">
        </div>
        
        <button type="submit" class="pill-btn pill-success pill-block">💾 Save Vendor Configuration</button>
    </form>
  </div>
</div>
</body>
</html>

