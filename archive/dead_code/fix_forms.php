<?php
$pages = ['register.php', 'takeover.php', 'closeout.php', 'quick_resolve.php'];
foreach ($pages as $p) {
    if (!file_exists($p)) continue;
    $content = file_get_contents($p);
    
    // Add auth.php to register.php if missing
    if ($p == 'register.php' && strpos($content, 'auth.php') === false) {
        $content = "<?php include 'auth.php'; ?>\n" . $content;
    }
    
    // Remove inline industrialTimer if it exists
    $content = preg_replace('/<div id="industrialTimer">.*?<\/div>\s*<\/div>/s', '', $content);
    $content = preg_replace('/<div id="industrialTimer">.*?<\/div>\s*<\/div>\s*/s', '', $content);
    // Just in case it's a single div level
    $content = preg_replace('/<div id="industrialTimer">.*?<\/div>/s', '', $content);
    
    // Add nav.php right after <body>
    if (strpos($content, 'nav.php') === false) {
        $content = preg_replace('/<body>/', "<body>\n<?php include 'nav.php'; ?>\n", $content);
    }
    
    // Add dashboard-container dash-box around form-container if not there?
    // Wait, global.css styles .form-container:
    // .form-container { background: linear-gradient(135deg, rgba(30, 41, 59, 0.7), rgba(15, 23, 42, 0.9)); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.1); padding: 30px; border-radius: 16px; max-width: 700px; margin: 40px auto; color: white; ... }
    // But .form-container doesn't have margin-left: 80px for the sidebar!
    // So I should wrap .form-container in a .dashboard-container!
    
    // Let's wrap everything between nav.php and scripts in dashboard-container?
    // It's easier to just add margin-left: 80px to .form-container directly in global.css!
    // Let's just output the fixed content for now.
    
    file_put_contents($p, $content);
    echo "Fixed $p\n";
}
?>
