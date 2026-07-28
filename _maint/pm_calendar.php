<?php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('view_work_orders');

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    // Setup Calendar Grid (Current Month)
    $month = isset($_GET['month']) ? $_GET['month'] : date('m');
    $year = isset($_GET['year']) ? $_GET['year'] : date('Y');

    $first_day = mktime(0,0,0,$month,1,$year);
    $days_in_month = date('t', $first_day);
    $day_of_week = date('w', $first_day); // 0=Sun, 6=Sat

    $prev_month = $month - 1;
    $prev_year = $year;
    if($prev_month == 0) { $prev_month = 12; $prev_year--; }

    $next_month = $month + 1;
    $next_year = $year;
    if($next_month == 13) { $next_month = 1; $next_year++; }

    // Fetch all WOs to plot on the calendar
    $stmt = $pdo->query("SELECT * FROM work_orders ORDER BY scheduled_date ASC");
    $wos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $events_by_date = [];
    $annual_scheduled = 0;
    $annual_completed = 0;
    $month_scheduled = 0;
    $month_completed = 0;

    $today = new DateTime('today');

    foreach ($wos as $wo) {
        if (!$wo['scheduled_date']) continue;
        
        $date = date('Y-m-d', strtotime($wo['scheduled_date']));
        $wo_month = date('m', strtotime($wo['scheduled_date']));
        $wo_year = date('Y', strtotime($wo['scheduled_date']));
        $is_current_month = ($wo_month == $month && $wo_year == $year);

        if (!isset($events_by_date[$date])) {
            $events_by_date[$date] = [];
        }
        
        // Calculate State
        $state = 'empty';
        $status = $wo['status'];
        
        if ($status === 'Completed') {
            $state = 'completed';
            $annual_completed++;
            $annual_scheduled++;
            if ($is_current_month) {
                $month_completed++;
                $month_scheduled++;
            }
        } elseif ($status === 'Cancelled' || $status === 'Missed') {
            $state = 'cancelled';
            $annual_scheduled++;
            if ($is_current_month) {
                $month_scheduled++;
            }
        } else {
            $annual_scheduled++;
            if ($is_current_month) {
                $month_scheduled++;
            }
            
            $scheduled_dt = new DateTime($date);
            $diff_days = (int)$today->diff($scheduled_dt)->format('%R%a'); // + is future, - is past
            
            if ($diff_days > 2 && $diff_days <= 7) {
                $state = 'upcoming_7';
            } elseif ($diff_days > 0 && $diff_days <= 2) {
                $state = 'upcoming_2';
            } elseif ($diff_days === 0) {
                $state = 'today';
            } elseif ($diff_days === -1 || $diff_days === -2) {
                $state = 'overdue_1';
            } elseif ($diff_days <= -3) {
                $state = 'overdue_3';
            } else {
                $state = 'upcoming_far';
            }
        }
        
        $wo['glow_state'] = $state;
        $events_by_date[$date][] = $wo;
    }
    
    $annual_pct = $annual_scheduled > 0 ? round(($annual_completed / $annual_scheduled) * 100) : 100;
    $month_pct = $month_scheduled > 0 ? round(($month_completed / $month_scheduled) * 100) : 100;

    // Month-to-date operations snapshot from the single KPI engine (matches the dashboard).
    require_once __DIR__ . '/../inc/kpi.php';
    require_once __DIR__ . '/../inc/shift_calendar.php';
    $holJson = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key='plant_holidays'")->fetchColumn() ?: '[]';
    $mtdCal  = new ShiftCalendar('06:00:00', '22:00:00', [1,2,3,4,5], json_decode($holJson, true) ?? []);
    $op      = wcc_kpi_window_summary($pdo, date('Y-m-01'), date('Y-m-d'), $mtdCal, 16, [1,2,3,4,5]);
    $op_kpis = [
        'mtbf'   => $op['mtbf'] === null ? null : round($op['mtbf'] / 60, 1),
        'labour' => $op['labour'],
    ];

} catch (PDOException $e) { die("DB Error: " . $e->getMessage()); }
?>
<?php
$page_title = __('pm.title');
require_once __DIR__ . '/../inc/head.php';
?>
    <style>
        .calendar-container {
            background: var(--panel-bg);
            border: 1px solid var(--panel-border);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            margin-bottom: 20px;
        }
        .cal-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 20px;
        }
        .cal-header h2 { margin: 0; color: var(--text-accent); }
        .cal-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
        }
        .cal-day-header {
            text-align: center; font-weight: bold; color: var(--text-secondary);
            padding: 10px 0;
        }
        .cal-box {
            background: rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 8px;
            height: 120px;
            padding: 10px;
            position: relative;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            display: flex;
            flex-direction: column;
        }
        .cal-box:hover {
            transform: scale(1.02);
            z-index: 10;
        }
        .cal-date {
            position: absolute; top: 8px; right: 8px;
            font-size: 0.85em; color: var(--text-secondary); font-weight: bold;
            z-index: 2;
        }
        
        .cal-events {
            margin-top: 15px;
            flex-grow: 1;
            overflow-y: auto;
            padding-right: 4px;
        }
        
        .cal-events::-webkit-scrollbar { width: 4px; }
        .cal-events::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); border-radius: 2px; }
        .cal-events::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 2px; }
        .cal-events::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.4); }
        
        .wo-event {
            margin-bottom: 6px;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 0.75em;
            font-weight: bold;
            color: white;
            text-shadow: 0 1px 3px rgba(0,0,0,0.8);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }

        /* --- LED GLOW STATES --- */
        .cal-box.empty-day {
            box-shadow: inset 0 0 10px rgba(56, 189, 248, 0.1);
            border: 1px solid rgba(56, 189, 248, 0.2);
        }

        .cal-box.muted-day {
            background: rgba(0,0,0,0.1);
            border: 1px dashed rgba(255,255,255,0.05);
            cursor: default;
        }
        .cal-box.muted-day:hover { transform: none; z-index: 1; }
        .cal-box.muted-day .cal-date { color: rgba(255,255,255,0.15); }
        
        .state-upcoming_far { background: var(--indigo-800); box-shadow: 0 0 10px rgba(30, 58, 138, 0.5); }
        .state-upcoming_7 { background: var(--blue-500); box-shadow: 0 0 12px rgba(59, 130, 246, 0.8); border: 1px solid #60a5fa; } /* Blue Glow */
        .state-upcoming_2 { background: var(--emerald-500); box-shadow: 0 0 15px rgba(16, 185, 129, 0.9); border: 1px solid #34d399; } /* Green Glow */
        .state-today      { background: var(--amber-500); box-shadow: 0 0 20px rgba(234, 179, 8, 1); border: 1px solid #fde047; color: #000; text-shadow:none; } /* Yellow Glow */
        .state-today::before { content: '⏰ '; }
        .state-overdue_1  { background: #f97316; box-shadow: 0 0 18px rgba(249, 115, 22, 0.9); border: 1px solid #fdba74; } /* Orange Glow */
        .state-overdue_1::before, .state-overdue_3::before { content: '⚠️ '; }
        
        @keyframes pulseRed {
            0% { box-shadow: 0 0 15px rgba(220, 38, 38, 0.8); border-color: #f87171; }
            50% { box-shadow: 0 0 30px rgba(220, 38, 38, 1); border-color: #fca5a5; }
            100% { box-shadow: 0 0 15px rgba(220, 38, 38, 0.8); border-color: #f87171; }
        }
        .state-overdue_3  { background: var(--red-700); animation: pulseRed 1.5s infinite; } /* Deep Red Pulsing */

        .state-completed  { background: transparent; border: 1px solid var(--success-border); color: var(--success); box-shadow: inset 0 0 10px rgba(16,185,129,0.2); }
        .state-completed::before { content: '✅ '; }

        .state-cancelled  { background: transparent; border: 1px solid var(--danger-border); color: var(--danger); box-shadow: inset 0 0 10px rgba(239,68,68,0.2); text-decoration: line-through; }
        .state-cancelled::before { content: '❌ '; }

        /* Handheld: calendar grid becomes a vertical agenda — only days with work show */
        @media (max-width: 768px) {
            .cal-grid { grid-template-columns: 1fr; gap: 8px; }
            .cal-day-header { display: none; }
            .cal-box.muted-day { display: none; }
            .cal-box.empty-day { display: none; }
            .cal-box { height: auto; min-height: 64px; }
            .cal-header > div { flex-wrap: wrap; gap: 10px; justify-content: center !important; }
        }


        /* Progress Bar */
        .progress-wrapper {
            background: rgba(0,0,0,0.3);
            border-radius: 20px;
            height: 24px;
            width: 100%;
            margin-top: 20px;
            border: 1px solid var(--panel-border);
            overflow: hidden;
            position: relative;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #10b981);
            transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex; align-items: center; justify-content: flex-end;
            padding-right: 10px;
            color: white; font-weight: bold; font-size: 0.8em; text-shadow: 0 1px 2px rgba(0,0,0,0.5);
            box-shadow: 0 0 15px rgba(16,185,129,0.5);
        }

        /* Day Details Modal */
        #dayDataArea {
            margin-top: 20px;
            padding: 20px;
            background: rgba(0,0,0,0.2);
            border-radius: 12px;
            border: 1px solid var(--panel-border);
            display: none;
        }
        .wo-card {
            background: var(--panel-bg);
            border-left: 4px solid var(--text-accent);
            padding: 15px; margin-bottom: 10px; border-radius: 8px;
            display: flex; justify-content: space-between; align-items: center;
        }
    </style>
<?php include __DIR__ . '/../nav.php'; ?>
    <div class="dashboard-container">
        <div class="page-header">
            <h1>🗓️ <?= __e('pm.title') ?></h1>
            <a class="btn" href="/_maint/work_orders.php"><?= __e('pm.list_view') ?></a>
        </div>

        <div class="calendar-container">
            <div class="cal-header" style="flex-direction: column; align-items: stretch; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <a href="?month=<?= $prev_month ?>&year=<?= $prev_year ?>" class="nav-btn">◀ <?= __e('common.prev') ?></a>
                    <div style="text-align: center;">
                        <h2 style="margin-bottom: 8px;"><?= date('F Y', mktime(0,0,0,$month,1,$year)) ?></h2>
                        <span style="display: inline-flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                            <span style="background: var(--success-bg); border: 1px solid var(--success-border); padding: 4px 10px; border-radius: var(--radius-sm); color: var(--success); font-size: var(--fs-sm); font-weight: bold;">
                                🛡️ <?= __e('pm.mtbf_mtd') ?> <strong><?= $op_kpis['mtbf'] === null ? '—' : $op_kpis['mtbf'] . 'h' ?></strong>
                            </span>
                            <span style="background: var(--warning-bg); border: 1px solid var(--warning-border); padding: 4px 10px; border-radius: var(--radius-sm); color: var(--warning); font-size: var(--fs-sm); font-weight: bold;">
                                🔧 <?= __e('pm.repair_labour') ?> <strong><?= $op_kpis['labour'] ?>m</strong>
                            </span>
                        </span>
                    </div>
                    <a href="?month=<?= $next_month ?>&year=<?= $next_year ?>" class="nav-btn"><?= __e('common.next') ?> ▶</a>
                </div>
            </div>

            <div class="cal-grid">
                <div class="cal-day-header"><?= __e('cal.sun') ?></div><div class="cal-day-header"><?= __e('cal.mon') ?></div><div class="cal-day-header"><?= __e('cal.tue') ?></div>
                <div class="cal-day-header"><?= __e('cal.wed') ?></div><div class="cal-day-header"><?= __e('cal.thu') ?></div><div class="cal-day-header"><?= __e('cal.fri') ?></div><div class="cal-day-header"><?= __e('cal.sat') ?></div>
                
                <?php
                // Pad empty cells before the 1st
                $days_in_prev_month = date('t', mktime(0,0,0,$month-1, 1, $year));
                for ($i = 0; $i < $day_of_week; $i++) { 
                    $prev_day = $days_in_prev_month - ($day_of_week - 1 - $i);
                    echo "<div class='cal-box muted-day'><div class='cal-date'>$prev_day</div></div>"; 
                }

                for ($day = 1; $day <= $days_in_month; $day++) {
                    $current_date = sprintf("%04d-%02d-%02d", $year, $month, $day);
                    $events = $events_by_date[$current_date] ?? [];
                    $is_empty = empty($events);
                    
                    $box_class = $is_empty ? 'cal-box empty-day' : 'cal-box';
                    if ($current_date === date('Y-m-d')) $box_class .= ' current-day'; // could style today's box specifically
                    
                    echo "<div class='$box_class' onclick='showDayData(\"$current_date\")'>";
                    echo "<div class='cal-date'>$day</div>";
                    
                    echo "<div class='cal-events'>";
                    foreach ($events as $ev) {
                        $st = $ev['glow_state'];
                        echo "<div class='wo-event state-$st' title='{$ev['title']}'>WO #{$ev['wo_id']}</div>";
                    }
                    echo "</div>";
                    
                    echo "</div>";
                }

                // Pad empty cells at the end to finish the grid
                $total_cells = $day_of_week + $days_in_month;
                $remaining_cells = (7 - ($total_cells % 7)) % 7;
                // If it ends exactly on Saturday, we might want to ensure at least 5 rows (35 days) or 6 rows (42 days) so height is consistent?
                // Actually, standard is to fill up to 35 or 42 based on total. Let's just fill the last row.
                for ($i = 1; $i <= $remaining_cells; $i++) {
                    echo "<div class='cal-box muted-day'><div class='cal-date'>$i</div></div>";
                }
                ?>
            </div>

            <div style="display: flex; gap: 20px; margin-top: 20px;">
                <div style="flex: 1;">
                    <div style="color: var(--text-accent); font-weight: bold; font-size: 0.85em; margin-bottom: 5px; text-transform: uppercase;"><?= __e('pm.annual_rate') ?></div>
                    <div class="progress-wrapper" style="margin-top: 0;" title="<?= __e('pm.annual_rate_title') ?>">
                        <div class="progress-fill" style="width: <?= $annual_pct ?>%;"><?= $annual_pct ?>%</div>
                    </div>
                </div>
                <div style="flex: 1;">
                    <div style="color: var(--text-accent); font-weight: bold; font-size: 0.85em; margin-bottom: 5px; text-transform: uppercase;"><?= __e('pm.month_rate', ['month' => date('F', mktime(0,0,0,$month,1))]) ?></div>
                    <div class="progress-wrapper" style="margin-top: 0;" title="<?= __e('pm.month_rate_title') ?>">
                        <div class="progress-fill" style="width: <?= $month_pct ?>%;"><?= $month_pct ?>%</div>
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-top: 25px; padding: 15px 25px; background: rgba(0,0,0,0.2); border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); justify-content: center; box-shadow: inset 0 2px 10px rgba(0,0,0,0.2);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 16px; height: 16px; border-radius: 50%; background: #3b82f6; box-shadow: 0 0 12px rgba(59, 130, 246, 0.8);"></div>
                    <span style="font-size: 0.9em; font-weight: 600; color: var(--text-primary);"><?= __e('pm.upcoming_gt7') ?></span>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 16px; height: 16px; border-radius: 50%; background: #10b981; box-shadow: 0 0 15px rgba(16, 185, 129, 0.9);"></div>
                    <span style="font-size: 0.9em; font-weight: 600; color: var(--text-primary);"><?= __e('pm.upcoming_lt7') ?></span>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 16px; height: 16px; border-radius: 50%; background: #eab308; box-shadow: 0 0 20px rgba(234, 179, 8, 1);"></div>
                    <span style="font-size: 0.9em; font-weight: 600; color: var(--text-primary);"><?= __e('pm.scheduled_today') ?></span>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 16px; height: 16px; border-radius: 50%; background: #f97316; box-shadow: 0 0 18px rgba(249, 115, 22, 0.9);"></div>
                    <span style="font-size: 0.9em; font-weight: 600; color: var(--text-primary);"><?= __e('pm.overdue_1_2') ?></span>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 16px; height: 16px; border-radius: 50%; background: #b91c1c; animation: pulseRed 1.5s infinite;"></div>
                    <span style="font-size: 0.9em; font-weight: 600; color: var(--text-primary);"><?= __e('pm.overdue_3plus') ?></span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 1.2em; line-height: 16px; color: #10b981; text-shadow: 0 0 10px rgba(16,185,129,0.5);">✅</span>
                    <span style="font-size: 0.9em; font-weight: 600; color: var(--text-primary);"><?= __e('common.completed') ?></span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 1.2em; line-height: 16px; color: #ef4444; text-shadow: 0 0 10px rgba(239,68,68,0.5);">❌</span>
                    <span style="font-size: 0.9em; font-weight: 600; color: var(--text-primary);"><?= __e('common.cancelled') ?></span>
                </div>
                <!-- Missed shares the cancelled styling (see the $state mapping above), so
                     it was drawn on the calendar but never explained in the legend. -->
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 1.2em; line-height: 16px; color: #ef4444; text-shadow: 0 0 10px rgba(239,68,68,0.5);">❌</span>
                    <span style="font-size: 0.9em; font-weight: 600; color: var(--text-primary);"><?= __e('common.missed') ?></span>
                </div>
            </div>
        </div>

        <div id="dayDataArea">
            <h3 id="dayTitle" style="color: var(--text-accent); margin-top: 0;"></h3>
            <div id="dayContent"></div>
        </div>
    </div>

    <!-- WO JSON DATA FOR JS -->
    <script>
        const eventsData = <?= json_encode($events_by_date) ?>;

        function showDayData(dateStr) {
            const area = document.getElementById('dayDataArea');
            const title = document.getElementById('dayTitle');
            const content = document.getElementById('dayContent');
            
            area.style.display = 'block';
            title.innerText = (typeof t === 'function' ? t('pm.day_title', {date: dateStr}) : ('Scheduled Maintenance for ' + dateStr));
            content.innerHTML = '';

            const dayEvents = eventsData[dateStr] || [];
            if (dayEvents.length === 0) {
                content.innerHTML = '<p style="color:var(--text-secondary);">' + (typeof t === 'function' ? t('pm.no_wo_day') : 'No Work Orders scheduled for this day.') + '</p>';
                return;
            }

            dayEvents.forEach(ev => {
                let statusColor = ev.status === 'Completed' ? 'var(--success)' : (ev.status === 'Cancelled' ? 'var(--danger)' : 'var(--warning)');
                let card = document.createElement('div');
                card.className = 'wo-card';
                const esc = (typeof escapeHtml === 'function') ? escapeHtml : (s) => String(s ?? '');
                const woId = parseInt(ev.wo_id, 10) || 0;
                const statusLbl = (typeof t === 'function' ? t('common.status') : 'Status');
                const openLbl = (typeof t === 'function' ? t('pm.open_takeover') : 'Open / Takeover');
                card.innerHTML = `
                    <div>
                        <h4 style="margin:0 0 5px 0; color:var(--text-primary);">WO #${woId} - ${esc(ev.title)}</h4>
                        <span style="font-size:0.85em; color:var(--text-secondary);">${esc(statusLbl)}: <strong style="color:${statusColor}">${esc(ev.status)}</strong></span>
                    </div>
                    <div>
                        <button class="nav-btn primary" onclick="window.location.href='/_maint/wo_takeover.php?wo_id=${woId}'">${esc(openLbl)}</button>
                    </div>
                `;
                content.appendChild(card);
            });
            
            // Scroll to the data area smoothly
            area.scrollIntoView({behavior: "smooth", block: "start"});
        }

        <?php if(isset($_GET['msg']) && $_GET['msg'] === 'wo_updated'): ?>
        window.addEventListener('DOMContentLoaded', () => {
            if (typeof openWccAlert === 'function') {
                openWccAlert(typeof t === 'function' ? t('common.success') : 'Success', typeof t === 'function' ? t('wo.updated_ok') : 'Work Order Updated Successfully!');
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>


