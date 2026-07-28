<?php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('view_history');

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    // LEFT JOIN added here for the history query
    $stmt = $pdo->query("
        SELECT a.*, e.equip_name 
        FROM active_tickets a 
        LEFT JOIN equipment e ON a.equip_id = e.equip_id 
        WHERE a.status = 'CLOSED' 
        -- AND a.deleted_at IS NULL   (uncomment after ensuring migration 0005_add_soft_delete_columns.sql has been applied)
        ORDER BY a.created_at DESC
    ");
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmtActions = $pdo->query("SELECT * FROM ticket_actions ORDER BY action_start ASC");
    $all_actions = $stmtActions->fetchAll(PDO::FETCH_ASSOC);
    
    $actions_by_ticket = [];
    foreach ($all_actions as $action) { $actions_by_ticket[$action['ticket_id']][] = $action; }

    $stmtCmt = $pdo->query("SELECT * FROM ticket_comments ORDER BY created_at ASC");
    $all_comments = $stmtCmt->fetchAll(PDO::FETCH_ASSOC);
    $comments_by_ticket = [];
    foreach ($all_comments as $cmt) { $comments_by_ticket[$cmt['ticket_id']][] = $cmt; }
} catch (PDOException $e) { wcc_user_error("Could not load event history.", $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Event History</title>
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

        /* Prevent header wrap for multi-word columns */
        th:nth-child(5), th:nth-child(6) {
            white-space: nowrap !important;
        }

        /* Base for ticket ID cells */
        #historyTable .parent-row td:first-child {
            white-space: nowrap;
        }

        .prio-text {
            /* text part only - orb/emoji keeps badge font size */
        }
    </style>
    <script>
        let activeFilters = [];
        let filterIdCounter = 0;
        let activeColumnIndex = -1;

        function getColumnName(index) {
            let th = document.querySelector("#historyTable thead tr").children[index];
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
            var table = document.getElementById("historyTable");
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
            input.style.width = '360px';
            input.placeholder = 'Search Event History... (Drag to column)';
            lockBtn.style.display = 'none';
            activeColumnIndex = -1;
            filterTable();
        }
    </script>
</head>
<body>
<?php include __DIR__ . '/../nav.php'; ?>

<div class="dashboard-container">
    <div class="header-flex" style="margin-bottom:10px;">
        <h2>Event History Archive</h2>
        <div id="searchWrapper" style="display: flex; gap: 15px; align-items: center;">
            <div id="searchContainerOrig" style="display:inline-block; position:relative; width:100%;" draggable="true" ondragstart="dragSearch(event)">
                <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); pointer-events:none; font-size:1.1em;">🔍</span>
                <input type="text" id="ledgerSearch" onkeyup="handleSearchInput(event)" ondblclick="resetSearchPosition()" placeholder="Search Event History... (Drag to column)" style="width:360px; padding:8px 35px 8px 35px; border-radius:20px; border: 1px solid var(--text-accent); background:var(--input-bg); color:var(--text-primary); transition: all 0.3s; box-sizing: border-box;">
                <span id="lockTokenBtn" onclick="lockToken()" style="display:none; position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:1.1em; transition: transform 0.2s;" title="Lock Token" onmouseover="this.style.transform='translateY(-50%) scale(1.2)'" onmouseout="this.style.transform='translateY(-50%) scale(1)'">🔒</span>
            </div>
            <button class="btn" onclick="window.location.href='/index.php'" style="white-space: nowrap;">🏠 Hub</button>
        </div>
    </div>
    <div id="activeFiltersArea" style="display:flex; gap:8px; margin-bottom:15px; flex-wrap:wrap; min-height:30px;"></div>
    
    <div class="table-container" style="overflow-x:auto;">
        <table class="data-table" id="historyTable">
            <thead>
                <tr>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Ticket ID</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Equipment Details</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Priority</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Status</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Reported At</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Announced By</th>
                    <th ondrop="dropSearch(event, this)" ondragover="allowDrop(event)">Fault Description</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($tickets) > 0): ?>
                <?php foreach ($tickets as $ticket): 
                    $prio = !empty($ticket['priority']) ? strtolower($ticket['priority']) : 'normal';
                    $badgeClass = "badge-" . $prio;
                    $dot = ($prio=='critical')?'🔴':(($prio=='high')?'🟠':(($prio=='low')?'🟢':'🔵'));
                ?>
                    <tr class="parent-row" >
                        <td style="font-weight: 600; color: var(--text-accent);"><span class="row-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></span><?= htmlspecialchars($ticket['ticket_id']) ?></td>
                        
                        <td>
                            <div style="font-weight: 700; color: var(--text-accent); font-size: 1.1em;"><?= htmlspecialchars($ticket['equip_id']) ?></div>
                            <div style="font-size: 0.85em; color: var(--text-secondary); margin-top: 2px; font-weight: 500;"><?= htmlspecialchars($ticket['equip_name'] ?? 'Unknown Machine') ?></div>
                        </td>
                        <td style="white-space:nowrap;"><span class="prio-badge <?= $badgeClass ?>"><?= $dot ?> <span class="prio-text"><?= $prio ?></span></span></td>
                        
                        <td><span class="status-closed"><?= htmlspecialchars($ticket['status']) ?></span></td>
                        <td><?= htmlspecialchars($ticket['report_time']) ?></td>
                        <td><?= htmlspecialchars($ticket['announced_by']) ?></td>
                        <td><?= htmlspecialchars($ticket['fault_desc']) ?></td>
                    </tr>
                    
                    <tr class="child-row" id="child-<?= $ticket['ticket_id'] ?>" style="display:none;">
                        <td colspan="7" style="padding: 0;">
                            <div class="child-content">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 2px solid rgba(30, 58, 138, 0.2); padding-bottom: 6px;">
                                    <span style="font-weight: 800; color: var(--text-accent); font-size: 1.1em;">Interrogation Timeline:</span>
                                    
                                    <div style="display: flex; gap: 10px;">
                                        <span style="font-weight: 700; color: #b91c1c; background: rgba(239, 68, 68, 0.1); padding: 5px 14px; border-radius: 8px; font-size: 0.9em; border: 1px solid rgba(239, 68, 68, 0.3); box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                            Initial PIC: <?= htmlspecialchars($ticket['pic'] ?? 'Unknown') ?>
                                        </span>

                                        <span style="font-weight: 700; color: #047857; background: rgba(16, 185, 129, 0.1); padding: 5px 14px; border-radius: 8px; font-size: 0.9em; border: 1px solid rgba(16, 185, 129, 0.3); box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                            Verified By: <?= htmlspecialchars($ticket['closed_by'] ?? 'Auto / Unknown') ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <?php if(isset($actions_by_ticket[$ticket['ticket_id']])): ?>
                                    <?php foreach($actions_by_ticket[$ticket['ticket_id']] as $act): ?>
                                        <div class="timeline-item">
                                            <strong>Tech: <?= htmlspecialchars($act['tech_name'] ?? 'Unknown') ?></strong> 
                                            <span style="color:#64748b; font-size:0.9em;">(<?= htmlspecialchars($act['action_start'] ?? '') ?> to <?= htmlspecialchars($act['action_end'] ?? '') ?>)</span><br>
                                            
                                            <div style="margin-top: 8px; font-size: 0.95em; color: var(--text-primary);">
                                                <strong>Fault Category:</strong> <?= htmlspecialchars($act['fault_type'] ?? 'N/A') ?> | 
                                                <strong>Root Cause:</strong> <?= htmlspecialchars($act['root_cause'] ?? 'N/A') ?><br>
                                                <strong>Action Taken:</strong> <?= htmlspecialchars($act['action_taken'] ?? 'N/A') ?> <br>
                                                <strong>Parts Used:</strong> <?= htmlspecialchars($act['parts_used'] ?? 'N/A') ?>
                                                
                                                <?php $escalated = $act['escalated_to'] ?? 'None'; if($escalated !== 'None' && $escalated !== ''): ?>
                                                    <br><span style="color:#ea580c; font-weight: bold;">Escalated to: <?= htmlspecialchars($escalated) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <em style="color:#64748b;">No intervention details recorded.</em>
                                <?php endif; ?>
                            </div>

                            <!-- Relational Comments Feed Archive -->
                            <div style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
                                <span style="font-weight: 800; color: var(--text-accent); font-size: 0.9em; text-transform: uppercase; margin-bottom: 10px; display: block;">💬 Comments Archive:</span>
                                
                                <div class="comments-container" style="max-height: 300px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; margin-bottom: 10px; padding-right: 5px;">
                                    <?php if (!empty($comments_by_ticket[$ticket['ticket_id']])): ?>
                                        <?php foreach($comments_by_ticket[$ticket['ticket_id']] as $cmt): ?>
                                            <div style="background: rgba(255,255,255,0.05); padding: 8px 12px; border-radius: 8px; border-left: 3px solid #38bdf8;">
                                                <div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 0.85em;">
                                                    <strong style="color: var(--text-primary);"><?= htmlspecialchars($cmt['user_name']) ?></strong>
                                                    <span style="color: var(--text-secondary);"><?= htmlspecialchars(date('M d, H:i', strtotime($cmt['created_at']))) ?></span>
                                                </div>
                                                <div style="font-size: 0.95em; color: #e2e8f0;">
                                                    <?= nl2br(htmlspecialchars($cmt['comment_text'])) ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div style="font-size: 0.9em; color: var(--text-secondary); font-style: italic;">No comments were made during this intervention.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align: center; padding: 40px; color: var(--text-secondary);">No closed tickets in the archive yet!</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="/timer.js"></script>
<script>
    function toggleDetails(ticketId) {
        const childRow = document.getElementById('child-' + ticketId);
        if (childRow.style.display === 'table-row') {
            childRow.style.display = 'none';
        } else {
            childRow.style.display = 'table-row';
        }
    }

    function adjustColumnFonts() {
        const table = document.getElementById('historyTable');
        if (!table) return;

        const headerRow = table.querySelector('thead tr');
        const parentRows = table.querySelectorAll('.parent-row');
        if (!headerRow || parentRows.length === 0) return;

        // Helper to get column width from header
        function getColWidth(colIndex) {
            const th = headerRow.children[colIndex];
            return th ? th.offsetWidth : 100;
        }

        // --- Ticket ID column (index 0) ---
        const ticketColW = getColWidth(0);
        let maxTicketLen = 0;
        parentRows.forEach(row => {
            const td = row.children[0];
            // text after the arrow span
            let txt = td.textContent || '';
            const arrowSpan = td.querySelector('.row-arrow');
            if (arrowSpan) {
                txt = txt.replace(arrowSpan.textContent || '', '').trim();
            }
            maxTicketLen = Math.max(maxTicketLen, txt.length);
        });

        // Estimate: ~7px per char at base 13-14px + extra for arrow/padding
        const ticketBase = 13;
        let ticketNeeded = maxTicketLen * 7.5 + 35;
        let ticketSize = ticketBase;
        if (ticketNeeded > ticketColW * 0.92) {
            ticketSize = Math.max(9, Math.floor(ticketBase * (ticketColW * 0.92 / ticketNeeded)));
        }
        parentRows.forEach(row => {
            row.children[0].style.fontSize = ticketSize + 'px';
            row.children[0].style.fontWeight = '600';
        });

        // --- Priority column (index 2) ---
        const prioColW = getColWidth(2);
        let maxPrioLen = 0;
        parentRows.forEach(row => {
            const textSpan = row.children[2].querySelector('.prio-text');
            if (textSpan) {
                maxPrioLen = Math.max(maxPrioLen, (textSpan.textContent || '').length);
            }
        });

        // Base for prio text ~11px (0.75em), emoji stays at badge size
        const prioBase = 11;
        let prioNeeded = maxPrioLen * 7 + 22; // orb takes space but we don't shrink it
        let prioSize = prioBase;
        if (prioNeeded > prioColW * 0.85) {
            prioSize = Math.max(8, Math.floor(prioBase * (prioColW * 0.85 / prioNeeded)));
        }
        parentRows.forEach(row => {
            const textSpan = row.children[2].querySelector('.prio-text');
            if (textSpan) {
                textSpan.style.fontSize = prioSize + 'px';
            }
        });
    }

    // Run on load and after any resize/filter that might change widths
    document.addEventListener('DOMContentLoaded', () => {
        adjustColumnFonts();
        // Re-run on resize in case column widths change
        window.addEventListener('resize', () => {
            // debounce lightly
            clearTimeout(window._adjustTimer);
            window._adjustTimer = setTimeout(adjustColumnFonts, 150);
        });
    });
</script>

</body>
</html>

