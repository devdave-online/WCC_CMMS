<?php 
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('create_tickets');

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    $workshops = $pdo->query("SELECT * FROM workshops ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $lines = $pdo->query("SELECT * FROM production_lines ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { die("DB Error"); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instant Resolve</title>
    <style>
        .searchbox-container { position: relative; width: 100%; }
        .searchbox-dropdown {
            position: absolute; top: 100%; left: 0; right: 0;
            background: var(--modal-bg, var(--panel-bg)); border: 1px solid var(--panel-border);
            border-radius: 4px; max-height: 250px; overflow-y: auto;
            z-index: 1000; display: none; box-shadow: 0 10px 25px rgba(0,0,0,0.8);
        }
        .searchbox-item {
            padding: 10px; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.05);
            display: flex; flex-direction: column; gap: 4px;
        }
        .searchbox-item:hover { background: rgba(255,255,255,0.1); }
        .item-name { font-weight: bold; color: var(--text-accent); }
        .item-uuid { font-family: monospace; font-size: 0.85em; color: var(--text-secondary); }
    </style>
</head>
<body>
<?php include __DIR__ . '/../nav.php'; ?>

<div class="form-container">
    <div class="header-flex">
        <h2>⚡ Instant Resolve</h2>
        <a href="../index.php" class="nav-btn">🏠 Hub</a>
    </div>
    
    <div class="grid-2" style="margin-bottom: 15px;">
        <div>
            <label>Workshop / Plant Filter:</label>
            <select id="filter_workshop" onchange="updateLineFilter()">
                <option value="">-- All Workshops --</option>
                <?php foreach($workshops as $w): ?>
                    <option value="<?= $w['workshop_id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Production Line Filter:</label>
            <select id="filter_line" onchange="filterEquipment()">
                <option value="">-- All Lines --</option>
                <!-- Filled via JS -->
            </select>
        </div>
    </div>

    <label>Search Equipment (By Name or UUID):</label>
    <div class="searchbox-container">
        <input type="text" id="equip_search" placeholder="Start typing machine name or UUID..." autocomplete="off">
        <input type="hidden" id="equip_id" required>
        <div id="searchbox_dropdown" class="searchbox-dropdown"></div>
    </div>

    <div class="grid-2" style="margin-top: 15px;">
        <div><label>Equipment Name:</label><input type="text" id="equip_name" readonly style="background: rgba(0,0,0,0.2);"></div>
        <div><label>Plant / Line:</label><input type="text" id="equip_line" readonly style="background: rgba(0,0,0,0.2);"></div>
    </div>

    <label>Technician Name:</label>
    <input type="text" id="tech_name" value="<?= htmlspecialchars($_SESSION['username'] ?? 'Unknown User') ?>" readonly style="background: rgba(0,0,0,0.2); cursor: not-allowed; color: #94a3b8;">
    <input type="hidden" id="logged_in_user" value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>">

    <label>Fix Description (Short):</label>
    <input type="text" id="action_taken" placeholder="e.g. Reset tripped breaker, cleared jam" required>
    
    <button id="submitBtn" onclick="submitInstantResolve()" class="btn" style="width: 100%; font-size: 1.1em; padding: 12px; margin-top: 10px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; transition: background 0.3s;">Log & Close Instantly</button>
    <div id="successMsg" class="success-msg"></div>
</div>

<script src="/timer.js"></script>
<script>
    let equipmentData = [];
    let filteredEquipment = [];
    let productionLines = <?= json_encode($lines) ?>;

    window.onload = async function() {
        // Load Equipment
        try {
            const resp = await fetch('/api/get_equipment.php');
            const result = await resp.json();
            if(result.status === 'success') {
                equipmentData = result.data;
                filteredEquipment = [...equipmentData];
            }
        } catch (e) { console.error("Equip load error"); }
        
        // Setup Searchbox Listener
        const searchInput = document.getElementById('equip_search');
        searchInput.addEventListener('input', function() {
            renderSearchDropdown(this.value.trim().toLowerCase());
        });
        
        // Hide dropdown on click outside
        document.addEventListener('click', function(e) {
            if(!e.target.closest('.searchbox-container')) {
                document.getElementById('searchbox_dropdown').style.display = 'none';
            }
        });
        
        // Focus opens dropdown
        searchInput.addEventListener('focus', function() {
            renderSearchDropdown(this.value.trim().toLowerCase());
        });
    };

    function updateLineFilter() {
        const w_id = document.getElementById('filter_workshop').value;
        const lineSelect = document.getElementById('filter_line');
        lineSelect.innerHTML = '<option value="">-- All Lines --</option>';
        
        if (w_id) {
            productionLines.filter(l => String(l.workshop_id) === String(w_id)).forEach(l => {
                lineSelect.innerHTML += `<option value="${l.line_id}">${l.name}</option>`;
            });
        }
        filterEquipment();
    }

    function filterEquipment() {
        const w_id = document.getElementById('filter_workshop').value;
        const l_id = document.getElementById('filter_line').value;
        
        filteredEquipment = equipmentData.filter(item => {
            if (w_id && String(item.workshop_id) !== String(w_id)) return false;
            if (l_id && String(item.line_id) !== String(l_id)) return false;
            return true;
        });
        
        // Reset selection if the current item is filtered out
        const currentSelectedId = document.getElementById('equip_id').value;
        if (currentSelectedId) {
            if (!filteredEquipment.find(e => String(e.equip_id) === String(currentSelectedId))) {
                document.getElementById('equip_id').value = '';
                document.getElementById('equip_search').value = '';
                autoFillEquipment();
            }
        }
        renderSearchDropdown(document.getElementById('equip_search').value.trim().toLowerCase());
    }

    function renderSearchDropdown(query) {
        const dropdown = document.getElementById('searchbox_dropdown');
        dropdown.innerHTML = '';
        
        if (filteredEquipment.length === 0) {
            dropdown.style.display = 'none'; return;
        }

        const matches = filteredEquipment.filter(item => {
            if(!query) return true;
            return (item.equip_name && item.equip_name.toLowerCase().includes(query)) || 
                   (item.asset_uuid && item.asset_uuid.toLowerCase().includes(query));
        });

        if (matches.length === 0) {
            dropdown.innerHTML = '<div style="padding:10px; color:#94a3b8;">No equipment found.</div>';
            dropdown.style.display = 'block'; return;
        }

        matches.forEach(item => {
            const div = document.createElement('div');
            div.className = 'searchbox-item';
            div.innerHTML = `
                <span class="item-name">${item.equip_name}</span>
                <span class="item-uuid">UUID: ${item.asset_uuid || 'N/A'}</span>
            `;
            div.onclick = function() {
                document.getElementById('equip_id').value = item.equip_id;
                document.getElementById('equip_search').value = item.equip_name;
                dropdown.style.display = 'none';
                autoFillEquipment();
            };
            dropdown.appendChild(div);
        });

        dropdown.style.display = 'block';
    }

    function autoFillEquipment() {
        const selectedId = document.getElementById('equip_id').value;
        const machine = equipmentData.find(item => String(item.equip_id) === String(selectedId));
        if (machine) {
            document.getElementById('equip_name').value = machine.equip_name;
            let pName = machine.plant_name || 'N/A';
            let lName = machine.line_name || 'N/A';
            document.getElementById('equip_line').value = pName + " / " + lName;
        } else {
            document.getElementById('equip_name').value = "";
            document.getElementById('equip_line').value = "";
        }
    }

    async function submitInstantResolve() {
        const payload = {
            equip_id: document.getElementById('equip_id').value,
            tech_name: document.getElementById('tech_name').value,
            action_taken: document.getElementById('action_taken').value
        };

        if(!payload.equip_id || !payload.tech_name || !payload.action_taken) { 
            openWccAlert('Validation Error', "Please fill all fields!"); return; 
        }

        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerText = "Processing... ⏳";

        try {
            const response = await fetch('/api/submit_instant_resolve.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            const result = await response.json();
            
            if(result.status === 'success') {
                openWccAlert('Success', result.message, '../index.php');
            } else { 
                openWccAlert('Error', result.message); 
                btn.disabled = false; btn.innerText = "Log & Close Instantly"; 
            }
        } catch (e) { 
            openWccAlert('Error', "Error: " + e.message); 
            btn.disabled = false; btn.innerText = "Log & Close Instantly"; 
        }
    }
</script>

</body>
</html>