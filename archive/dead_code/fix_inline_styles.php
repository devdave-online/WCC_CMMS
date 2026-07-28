<?php
// Fix active_tickets.php
$file = 'c:\xampp\htdocs\active_tickets.php';
$content = file_get_contents($file);
$content = str_replace('color: #64748b;', 'color: var(--text-secondary);', $content);
$content = str_replace('color: #1e3a8a;', 'color: var(--text-accent);', $content);
file_put_contents($file, $content);

// Fix history.php
$file = 'c:\xampp\htdocs\history.php';
$content = file_get_contents($file);
$content = str_replace('color: #64748b;', 'color: var(--text-secondary);', $content);
$content = str_replace('color: #1e3a8a;', 'color: var(--text-accent);', $content);
// Remove the duplicate timer
$timer_html = <<<EOD
    <div id="industrialTimer">
        <div id="timerLabel">//SYS.LIFESPAN//</div>
        <div id="blockContainer"></div>
    </div>
EOD;
$content = str_replace($timer_html, '', $content);
file_put_contents($file, $content);

// Fix statistics.php (just in case)
$file = 'c:\xampp\htdocs\statistics.php';
$content = file_get_contents($file);
$content = str_replace('color:#64748b;', 'color:var(--text-secondary);', $content);
file_put_contents($file, $content);

echo "Color replacements done and duplicate timer removed!";
?>
