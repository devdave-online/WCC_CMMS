<?php
// users.php and vendors.php RBAC cleanup script
foreach(['users.php', 'vendors.php'] as $file) {
    $c = file_get_contents($file);
    
    // Remove local auth variables and session_start
    $c = preg_replace('/session_start\(\);\s*\$STATIC_PASSWORD.*?\$is_authenticated = .*?;/s', '', $c);
    
    // Remove if ($is_authenticated) wrapping the logic
    $c = preg_replace('/if \(\$is_authenticated\) \{/', '', $c, 1);
    
    // Replace the closing brace of if ($is_authenticated) around line 39-40
    // Actually, just find `} catch (PDOException $e) { die("DB Error: " . $e->getMessage()); } \}` and remove the last `}`.
    $c = preg_replace('/\}\s*\n\?>/', "\n?>", $c, 1);
    
    // Remove the Restricted Area HTML
    $c = preg_replace('/<\?php if \(!\$is_authenticated\): \?>.*?<\?php else: \?>/s', '', $c);
    
    // Remove the final <?php endif; ?>
    $c = preg_replace('/<\?php endif; \?>/s', '', $c);
    
    // Add RBAC check at the top right after include 'auth.php'
    $rbac_code = "<?php\ninclude 'auth.php';\nif (!isset(\$_SESSION['role_level']) || \$_SESSION['role_level'] < 4) {\n    require_once 'rbac.php';\n    echo \"<div class='dashboard-container dash-box' style='margin: 50px auto; max-width: 600px; text-align: center;'><h2 style='color:#ef4444;'>🔒 Admin Clearance Required</h2><p style='color:#94a3b8;'>You do not have permission to access this module.</p></div></body></html>\";\n    exit;\n}\n";
    $c = preg_replace('/<\?php\s*include \'auth\.php\';/', $rbac_code, $c, 1);

    file_put_contents($file, $c);
    echo "Fixed $file\n";
}
?>

