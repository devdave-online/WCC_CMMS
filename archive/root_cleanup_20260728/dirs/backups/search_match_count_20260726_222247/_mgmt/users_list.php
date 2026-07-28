<?php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('manage_users');

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/techident.php';
require_once __DIR__ . '/../inc/gamification.php';
$pdo = get_wcc_db_connection();

try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY user_id ASC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch user manual skills
    $skillsStmt = $pdo->query("SELECT user_id, skill_name FROM user_skills");
    $allSkills = $skillsStmt->fetchAll(PDO::FETCH_ASSOC);
    $userSkillsMap = [];
    foreach ($allSkills as $s) {
        $userSkillsMap[$s['user_id']][] = $s['skill_name'];
    }

    // Calculate Gamified Proficiencies based on Equipment Category
    $gamifiedStats = [];
    $gStmt = $pdo->query("
        SELECT ta.tech_name, e.category, SUM(TIMESTAMPDIFF(MINUTE, ta.action_start, ta.action_end))/60 as total_hours
        FROM ticket_actions ta
        JOIN active_tickets at ON at.ticket_id = ta.ticket_id
        JOIN equipment e ON e.equip_id = at.equip_id
        WHERE ta.action_start IS NOT NULL AND ta.action_end IS NOT NULL AND e.category IS NOT NULL AND e.category != ''
        GROUP BY ta.tech_name, e.category
    ");
    foreach($gStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $gamifiedStats[$r['tech_name']][$r['category']] = (float)$r['total_hours'];
    }
    // Fetch skill automation config
    $skillAutoConfigs = [];
    try {
        $sacStmt = $pdo->query("SELECT * FROM skill_automation_config ORDER BY equipment_category ASC");
        $skillAutoConfigs = $sacStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    // Helper for gamification level
    // Ladder lives in inc/gamification.php so every screen renders a tier identically.
    function getGamifiedLevel($hours) { return wcc_gamified_level((float)$hours); }
} catch (PDOException $e) { wcc_user_error("Could not load user list.", $e->getMessage()); }
?>
<?php
$page_title = 'Users Directory';
require_once __DIR__ . '/../inc/head.php';
?>
    <style>
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
            let th = document.querySelector("#usersListTable thead tr").children[index];
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
            var table = document.getElementById("usersListTable");
            var tr = table.getElementsByClassName("parent-row");

            for (let i = 0; i < tr.length; i++) {
                let matchFound = true;
                let tds = tr[i].getElementsByTagName("td");

                for (let f of activeFilters) {
                    let cell = tds[f.colIndex];
                    if (cell) {
                        let txt = cell.textContent || cell.innerText;
                        if (txt.toUpperCase().indexOf(f.query) === -1) {
                            matchFound = false;
                            break;
                        }
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
                                if (txt.toUpperCase().indexOf(globalFilter) > -1) {
                                    globalMatch = true;
                                    break;
                                }
                            }
                        }
                        if (!globalMatch) matchFound = false;
                    }
                }

                tr[i].style.display = matchFound ? "" : "none";
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
            input.placeholder = 'Search users... (Drag to column)';
            lockBtn.style.display = 'none';
            activeColumnIndex = -1;
            filterTable();
        }
    </script>
    <style>
        /* Tighten table to fit exactly inside the 1400px container */
        #usersListTable th, #usersListTable td {
            padding: 10px 8px;
            font-size: 0.9em;
        }
    </style>
<?php include __DIR__ . '/../nav.php'; ?>
<div class="dashboard-container">
    <div class="page-header" style="margin-bottom:10px;">
        <h1>Users Directory</h1>
        <div style="display:flex; gap:10px; align-items:center;">
            <div id="searchWrapper">
                <div id="searchContainerOrig" style="display:inline-block; position:relative; width:100%;" draggable="true" ondragstart="dragSearch(event)">
                    <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); pointer-events:none; font-size:1.1em;">🔍</span>
                    <input type="text" id="ledgerSearch" onkeyup="handleSearchInput(event)" ondblclick="resetSearchPosition()" placeholder="Search users... (Drag to column)" style="padding:8px 35px 8px 35px; border-radius:20px; border: 1px solid var(--text-accent); background:var(--input-bg); color:var(--text-primary); transition: all 0.3s; box-sizing: border-box;">
                    <span id="lockTokenBtn" onclick="lockToken()" style="display:none; position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:1.1em; transition: transform 0.2s;" title="Lock Token" onmouseover="this.style.transform='translateY(-50%) scale(1.2)'" onmouseout="this.style.transform='translateY(-50%) scale(1)'">🔒</span>
                </div>
            </div>
        </div>
    </div>
    <div style="display:flex; gap:10px; margin-bottom:15px; flex-wrap:wrap;">
        <button class="btn" onclick="document.getElementById('skillConfigModal').style.display='block'">View Gamified Skills</button>
    </div>
    <div id="activeFiltersArea" style="display:flex; gap:8px; margin-bottom:15px; flex-wrap:wrap; min-height:30px;"></div>
    <table class="data-table" id="usersListTable">
        <thead>
            <tr>
                <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Badge</th>
                <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Username</th>
                <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Full Name</th>
                <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Role Level</th>
                <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Status</th>
                <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $i):
                $role_labels = [1=>'Operator', 2=>'Technician', 3=>'Supervisor', 4=>'Admin'];
                $role_label = $role_labels[$i['role_level']] ?? 'User';
                $has_overrides = !empty($i['permissions_json']);
            ?>
            <tr class="parent-row" >
                <td style="white-space:nowrap;"><span class="row-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></span><?= htmlspecialchars($i['badge_number'] ?? 'N/A') ?></td>
                <td style="font-weight:bold; color: var(--text-accent); white-space:nowrap;"><?= htmlspecialchars($i['username']) ?></td>
                <td><?= htmlspecialchars($i['full_name'] ?? 'N/A') ?></td>
                <td style="white-space:nowrap;">
                    Level <?= htmlspecialchars($i['role_level']) ?> — <?= $role_label ?>
                    <?php if($has_overrides): ?>
                        <span style="background: rgba(167,139,250,0.15); color: #a78bfa; padding: 2px 6px; border-radius: 4px; font-size: 0.75em; margin-left: 6px;">Custom</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php $st = $i['status'] ?? 'active'; $stColor = $st === 'active' ? '#10b981' : ($st === 'inactive' ? '#ef4444' : '#f59e0b'); ?>
                    <span style="background:<?= $stColor ?>22; color:<?= $stColor ?>; padding:2px 8px; border-radius:10px; font-size:0.8em; font-weight:600; text-transform:uppercase;"><?= htmlspecialchars($st) ?></span>
                </td>
                <td style="white-space:nowrap;"><?= htmlspecialchars($i['created_at']) ?></td>
            </tr>
            <tr class="child-row" id="child-user-<?= $i['user_id'] ?>" style="display:none;">
                <td colspan="6" style="padding: 0;">
                    <div class="child-content">
                        <div style="margin-bottom: 10px;">
                            <span style="color: #94a3b8;">User ID:</span> <span style="color:white;"><?= htmlspecialchars($i['user_id']) ?></span>
                            &nbsp;&nbsp;
                            <span style="color: #94a3b8;">Badge:</span> <span style="color:#a78bfa; font-weight: bold;"><?= htmlspecialchars($i['badge_number'] ?? 'N/A') ?></span>
                            &nbsp;&nbsp;
                            <span style="color: #94a3b8;">Role:</span> <span style="color:#a78bfa; font-weight: bold;"><?= $role_label ?></span>
                        </div>

                        <!-- User Data (view only) -->
                        <div style="margin-bottom:15px; padding:10px; background:var(--panel-bg); border-radius:8px; border:1px solid var(--panel-border);">
                            <div style="font-weight:600; margin-bottom:6px; color:var(--text-accent);">User Data</div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:0.85em;">
                                <div>
                                    <span style="color: #94a3b8;">Email:</span><br>
                                    <span style="color:var(--text-primary);"><?= htmlspecialchars($i['email'] ?? 'N/A') ?></span>
                                </div>
                                <div>
                                    <span style="color: #94a3b8;">Full Name:</span><br>
                                    <span style="color:var(--text-primary);"><?= htmlspecialchars($i['full_name'] ?? 'N/A') ?></span>
                                </div>
                                <div>
                                    <span style="color: #94a3b8;">Phone:</span><br>
                                    <span style="color:var(--text-primary);"><?= htmlspecialchars($i['phone'] ?? 'N/A') ?></span>
                                </div>
                                <div>
                                    <span style="color: #94a3b8;">Department:</span><br>
                                    <span style="color:var(--text-primary);"><?= htmlspecialchars($i['department'] ?? 'N/A') ?></span>
                                </div>
                                <div>
                                    <span style="color: #94a3b8;">Status:</span><br>
                                    <?php $st = $i['status'] ?? 'active'; $stColor = $st === 'active' ? '#10b981' : ($st === 'inactive' ? '#ef4444' : '#f59e0b'); ?>
                                    <span style="background:<?= $stColor ?>22; color:<?= $stColor ?>; padding:2px 8px; border-radius:10px; font-size:0.8em; font-weight:600; text-transform:uppercase;"><?= htmlspecialchars($st) ?></span>
                                </div>
                                <div>
                                    <span style="color: #94a3b8;">Last Login:</span><br>
                                    <span style="color:var(--text-primary);"><?= !empty($i['last_login']) ? date('Y-m-d H:i', strtotime($i['last_login'])) : '—' ?></span>
                                </div>
                            </div>
                            <div style="margin-top:6px;">
                                <span style="color: #94a3b8;">Notes:</span><br>
                                <span style="color:#cbd5e1; font-size:0.85em;"><?= htmlspecialchars($i['notes'] ?? '—') ?></span>
                            </div>
                        </div>
                        
                        <!-- Gamified Proficiencies Box -->
                        <div style="margin-bottom:15px; padding:10px; background:var(--panel-bg); border-radius:8px; border:1px solid var(--panel-border);">
                            <div style="font-weight:600; margin-bottom:10px; color:var(--text-accent);">Gamified Proficiencies</div>
                            <div style="display:flex; flex-wrap:wrap; gap:10px;">
                                <?php 
                                // Merge across aliases rather than picking the first that
                                // exists — a technician can legitimately have hours filed
                                // under both spellings.
                                $user_stats = [];
                                foreach (wcc_tech_aliases($i) as $__a) {
                                    foreach (($gamifiedStats[$__a] ?? []) as $__c => $__h) {
                                        $user_stats[$__c] = ($user_stats[$__c] ?? 0) + (float)$__h;
                                    }
                                } 
                                $hasGamified = false;
                                foreach ($skillAutoConfigs as $sconf) {
                                    $cat = $sconf['equipment_category'];
                                    if (isset($user_stats[$cat]) && $user_stats[$cat] > 0) {
                                        $hasGamified = true;
                                        $hrs = $user_stats[$cat];
                                        $lvl = getGamifiedLevel($hrs);
                                        $badgeColor = $lvl['color'];
                                        ?>
                                        <div style="background:rgba(255,255,255,0.05); border:1px solid <?= $badgeColor ?>44; padding:6px 12px; border-radius:8px; font-size:0.85em; display:flex; align-items:center; gap:8px;">
                                            <!-- Tier icon then the category's configured icon — the
                                                 same pairing used on My Profile and in the Users
                                                 Directory. This chip was showing the tier alone. -->
                                            <span style="display:inline-flex; align-items:center; gap:3px;" aria-hidden="true">
                                                <span style="font-size:1.5em; filter: drop-shadow(0 0 2px <?= $badgeColor ?>);"><?= $lvl['icon'] ?></span>
                                                <?php if (!empty($sconf['icon'])): ?>
                                                    <span style="font-size:1.05em; opacity:.85;"><?= htmlspecialchars($sconf['icon']) ?></span>
                                                <?php endif; ?>
                                            </span>
                                            <div>
                                                <div style="color:var(--text-primary); font-weight:bold;"><?= htmlspecialchars($sconf['skill_name']) ?></div>
                                                <div style="color:<?= $badgeColor ?>; font-size:0.9em;"><?= $lvl['tier'] ?> (<?= round($hrs, 1) ?>h on <?= htmlspecialchars($cat) ?>)</div>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                }
                                if (!$hasGamified):
                                ?>
                                    <div style="color:var(--text-secondary); font-size:0.85em; font-style:italic; padding:6px;">No machine proficiencies logged yet.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Manual Skills Box -->
                        <div style="margin-bottom:15px; padding:10px; background:var(--panel-bg); border-radius:8px; border:1px solid var(--panel-border);">
                            <div style="font-weight:600; margin-bottom:10px; color:var(--text-accent);">Manual Skills</div>
                            <div style="display:flex; flex-wrap:wrap; gap:10px;">
                                <?php 
                                $user_man_skills = $userSkillsMap[$i['user_id']] ?? [];
                                if (!empty($user_man_skills)): 
                                ?>
                                    <?php foreach ($user_man_skills as $ms): ?>
                                        <div style="background:rgba(255,255,255,0.05); border:1px solid var(--panel-border); padding:6px 12px; border-radius:8px; font-size:0.85em; display:flex; align-items:center; gap:8px;">
                                            <span style="font-size:1.3em;">🛠️</span>
                                            <strong style="color:var(--text-primary); font-size:1.1em;"><?= htmlspecialchars($ms) ?></strong>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div style="color:var(--text-secondary); font-size:0.85em; font-style:italic; padding:6px;">No manual skills logged.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script>
    function toggleDetails(id) {
        const row = document.getElementById('child-' + id);
        if (row.style.display === 'table-row') {
            row.style.display = 'none';
        } else {
            row.style.display = 'table-row';
        }
    }
</script>

<!-- Skill Configurator Modal (Read-Only) -->
<div id="skillConfigModal" class="modal">
  <div class="modal-content" style="max-width: 600px; width: 96%; padding: 18px 22px;">
    <span class="close" onclick="document.getElementById('skillConfigModal').style.display='none'">&times;</span>
    <h2 style="margin:0 0 10px 0; font-size:1.1em; color: var(--text-primary);">Automated Skill Configurator</h2>
    <p style="color:var(--text-secondary); font-size:0.85em; margin-bottom:15px;">
      Map Equipment Categories to specific gamified skills. When technicians log hours on these categories, they automatically level up! (Read-only view)
    </p>

    <table class="data-table" style="width: 100%; margin-bottom: 20px; font-size: 0.9em;">
      <thead>
        <tr>
          <th>Category</th>
          <th>Skill Name</th>
          <th>Icon</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($skillAutoConfigs as $cfg): ?>
        <tr>
          <td><?= htmlspecialchars($cfg['equipment_category']) ?></td>
          <td style="color:var(--text-accent); font-weight:bold;"><?= htmlspecialchars($cfg['skill_name']) ?></td>
          <td><?= htmlspecialchars($cfg['icon']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($skillAutoConfigs)): ?>
        <tr><td colspan="3" style="text-align:center; color:var(--text-secondary);">No mappings defined yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>

