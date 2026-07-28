<?php
$c = file_get_contents('statistics.php');

$search = <<<EOT
                        <div class="stats">
                            Labor: <strong><?= formatTime(\$stats['labor_minutes']) ?></strong> | Interventions: <strong><?= \$stats['interventions'] ?></strong>
            <summary style="font-weight: bold; color: #1e3a8a; font-size: 1.1em;">📂 View Raw Data Ledgers (Tickets & Parts)</summary>
EOT;

$replace = <<<EOT
                        <div class="stats">
                            Labor: <strong><?= formatTime(\$stats['labor_minutes']) ?></strong> | Interventions: <strong><?= \$stats['interventions'] ?></strong>
                        </div>
                        <div class="progress-bg">
                            <div class="progress-bar" style="width: <?= \$visual_width ?>%; background: <?= \$bar_color ?>;"></div>
                        </div>
                        <div class="util-text" style="color: <?= \$text_color ?>;"><?= \$utilization ?>% of Capacity</div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div style="font-size:0.85em; color:#64748b; margin-top:-10px; margin-bottom:30px;">
            *Capacity based on <?= \$shift_hours ?> hour shift lengths. Note: Calculation assumes tech was present all <?= \$interval_days ?> days of the interval.
        </div>

        <details style="margin-top: 20px; background: rgba(255,255,255,0.4); padding: 15px; border-radius: 12px; cursor: pointer;">
            <summary style="font-weight: bold; color: #1e3a8a; font-size: 1.1em;">📂 View Raw Data Ledgers (Tickets & Parts)</summary>
EOT;

$c = str_replace(trim($search), trim($replace), $c);
file_put_contents('statistics.php', $c);
echo "Done.";
?>
