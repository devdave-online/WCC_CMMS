<?php
$files = glob("c:\\xampp\\htdocs\\*.php");
foreach ($files as $file) {
    $content = file_get_contents($file);
    $changed = false;
    
    // The search icon got corrupted to ??
    if (strpos($content, 'font-size:1.1em;">🔍</span>') !== false) {
        $content = str_replace('font-size:1.1em;">🔍</span>', 'font-size:1.1em;">🔍</span>', $content);
        $changed = true;
    }
    // Handle the dY"? corruption (caused by ANSI read of UTF-8)
    if (strpos($content, '🔍</span>') !== false) {
        $content = str_replace('🔍</span>', '🔍</span>', $content);
        $changed = true;
    }
    
    // The lock token icon got corrupted to ??
    if (strpos($content, 'scale(1)\'">??</span>') !== false) {
        $content = str_replace('scale(1)\'">??</span>', 'scale(1)\'">🔒</span>', $content);
        $changed = true;
    }
    // Handle the dY"O corruption
    if (strpos($content, '🔒</span>') !== false) {
        $content = str_replace('🔒</span>', '🔒</span>', $content);
        $changed = true;
    }
    
    // Placeholder corruption
    if (strpos($content, 'placeholder="') !== false) {
        $content = str_replace('placeholder="', 'placeholder="', $content);
        $changed = true;
    }

    if ($changed) {
        file_put_contents($file, $content);
        echo "Fixed $file\n";
    }
}
?>
