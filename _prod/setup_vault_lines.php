<?php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('manage_equipment');

// Enterprise centralized DB connection (Phase 1 fix)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    
    // Group Lines by Workshop
    $stmt = $pdo->query("SELECT w.workshop_id, w.name as workshop_name, w.location, w.status as w_status, 
                        l.line_id, l.name as line_name, l.products_built, l.status as l_status,
                        (SELECT COUNT(*) FROM equipment e WHERE e.line_id = l.line_id) as machine_count
                        FROM workshops w
                        LEFT JOIN production_lines l ON w.workshop_id = l.workshop_id
                        ORDER BY w.name ASC, l.name ASC");
    $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $workshops = [];
    foreach($raw_data as $row) {
        $wid = $row['workshop_id'];
        if(!isset($workshops[$wid])) {
            $workshops[$wid] = [
                'name' => $row['workshop_name'],
                'location' => $row['location'],
                'status' => $row['w_status'],
                'lines' => []
            ];
        }
        if($row['line_id']) {
            $workshops[$wid]['lines'][] = [
                'id' => $row['line_id'],
                'name' => $row['line_name'],
                'products' => $row['products_built'],
                'status' => $row['l_status'],
                'machines' => $row['machine_count']
            ];
        }
    }

    // Fetch detailed equipment list per line (for expandable line accordions)
    $equipmentByLine = [];
    try {
        $eqStmt = $pdo->query("SELECT equip_id, equip_name, category, is_active, oem_brand, line_id 
                               FROM equipment 
                               WHERE line_id IS NOT NULL 
                               ORDER BY equip_name");
        foreach ($eqStmt->fetchAll(PDO::FETCH_ASSOC) as $eq) {
            $lid = $eq['line_id'];
            if (!isset($equipmentByLine[$lid])) $equipmentByLine[$lid] = [];
            $equipmentByLine[$lid][] = $eq;
        }
    } catch (Exception $e) { /* optional */ }

    // Attach equipment lists to lines
    foreach ($workshops as $wid => &$w) {
        foreach ($w['lines'] as &$line) {
            $line['equipment'] = $equipmentByLine[$line['id']] ?? [];
        }
    }

} catch (PDOException $e) { die("DB Error: " . $e->getMessage()); }
?>
<?php
$page_title = __('lines.title');
require_once __DIR__ . '/../inc/head.php';
?>
    <style>
        /* Workshop accordion styling */
        .workshop-accordion {
            border: none;
        }
        .workshop-accordion summary {
            outline: none;
            user-select: none;
        }
        .workshop-accordion summary::-webkit-details-marker,
        .workshop-accordion summary::marker {
            display: none;
        }
        .workshop-accordion[open] summary .row-arrow {
            transform: rotate(90deg);
            color: var(--text-accent);
        }
        .workshop-accordion summary:hover {
            background: var(--input-bg) !important;
        }
        .workshop-accordion summary .row-arrow {
            transition: transform 0.2s ease;
        }

        /* Make sure inner tables and child content look good */
        .workshop-accordion .data-table {
            margin: 0;
            border-top: 1px solid var(--panel-border);
        }
    </style>
<?php include __DIR__ . '/../nav.php'; ?>
    <?php require_once __DIR__ . '/../rbac.php'; ?>
    <div class="dashboard-container">
        <div class="page-header">
            <h2>🏭 <?= __e('lines.directory') ?></h2>
            
        </div>
        
        <div style="margin-bottom: 20px; color: var(--text-secondary); font-size: 0.9em;">
            <p><?= __e('lines.intro') ?></p>
        </div>

        <!-- Workshop + Line Accordions -->
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php foreach($workshops as $wid => $w): ?>
                <!-- Workshop header as collapsible (solution for #2) -->
                <details class="workshop-accordion" open>
                    <summary style="list-style: none; cursor: pointer; padding: 16px 20px; background: var(--panel-bg); border: 1px solid var(--panel-border); border-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span class="row-arrow" style="margin-right: 6px;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </span>
                            <h3 style="margin: 0; color: var(--text-accent); font-size: 1.35em;">🏢 <?= htmlspecialchars($w['name']) ?></h3>
                        </div>
                        <div style="text-align: right; font-size: 0.9em; color: var(--text-secondary);">
                            <?= __e('lines.location') ?> <strong style="color: var(--text-primary);"><?= htmlspecialchars($w['location'] ?: __('lines.not_specified')) ?></strong>
                            <span style="margin-left: 12px; background: rgba(255,255,255,0.08); padding: 2px 8px; border-radius: 10px; font-size: 0.75em;">
                                <?= count($w['lines']) == 1 ? __e('lines.count_one', ['n' => (string)count($w['lines'])]) : __e('lines.count_many', ['n' => (string)count($w['lines'])]) ?>
                            </span>
                        </div>
                    </summary>

                    <div style="margin-top: 8px; padding: 0 4px;">
                        <?php if(empty($w['lines'])): ?>
                            <div style="padding: 12px 16px; color: #94a3b8; font-style: italic; background: rgba(255,255,255,0.02); border-radius: 8px;">
                                <?= __e('lines.no_lines') ?>
                            </div>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th><?= __e('th.line_name') ?></th>
                                        <th><?= __e('th.products_built') ?></th>
                                        <th><?= __e('th.status') ?></th>
                                        <th><?= __e('th.machine_count') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($w['lines'] as $l): ?>
                                    <!-- Line row as parent (solution for #3) -->
                                    <tr class="parent-row" data-id="line-<?= $l['id'] ?>">
                                        <td style="font-weight: bold; color: var(--text-accent); white-space: nowrap;">
                                            <span class="row-arrow">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                            </span>
                                            <?= htmlspecialchars($l['name']) ?>
                                        </td>
                                        <td><?= htmlspecialchars($l['products'] ?: __('common.na')) ?></td>
                                        <td>
                                            <span style="background: rgba(16, 185, 129, 0.2); color: #10b981; padding: 3px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold;">
                                                <?= htmlspecialchars($l['status']) ?>
                                            </span>
                                        </td>
                                        <td style="font-family: monospace; font-size: 1.1em; color: var(--text-accent);">
                                            <?= (int)$l['machines'] ?> <?= __e('lines.units') ?>
                                        </td>
                                    </tr>

                                    <!-- Line details / machines (child) -->
                                    <tr class="child-row" id="line-<?= $l['id'] ?>">
                                        <td colspan="4">
                                            <div class="child-content" style="margin: 4px 12px 16px; padding: 16px; border-radius: 10px;">
                                                <div style="font-weight: 600; margin-bottom: 8px; color: var(--text-accent);">
                                                    <?= __e('lines.machines_on_line', ['n' => (string)count($l['equipment'])]) ?>
                                                </div>
                                                <?php if (!empty($l['equipment'])): ?>
                                                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 6px 12px; font-size: 0.9em;">
                                                        <?php foreach ($l['equipment'] as $eq): ?>
                                                            <div style="display: flex; align-items: center; gap: 6px; padding: 4px 8px; background: rgba(255,255,255,0.03); border-radius: 6px;">
                                                                <span style="color: var(--text-accent);">•</span>
                                                                <strong><?= htmlspecialchars($eq['equip_name']) ?></strong>
                                                                <span style="font-size: 0.8em; color: var(--text-secondary);">
                                                                    (<?= htmlspecialchars($eq['category'] ?? __('common.na')) ?>)
                                                                </span>
                                                                <span style="margin-left: auto; font-size: 0.75em; padding: 1px 6px; border-radius: 8px; background: <?= $eq['is_active'] ? 'rgba(16,185,129,0.15)' : 'rgba(239,68,68,0.15)' ?>; color: <?= $eq['is_active'] ? '#10b981' : '#ef4444' ?>;">
                                                                    <?= $eq['is_active'] ? __e('common.online') : __e('common.offline') ?>
                                                                </span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div style="color: #94a3b8; font-style: italic; font-size: 0.9em;"><?= __e('lines.no_equipment') ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>


