<?php
$files = glob("c:\\xampp\\htdocs\\*.php");
foreach ($files as $file) {
    $content = file_get_contents($file);
    $changed = false;
    
    // Check for the search span completely using regex
    if (preg_match_all('/(<span style="position:absolute; left:12px; top:50%; transform:translateY\(-50%\); color:var\(--text-secondary\); pointer-events:none; font-size:1.1em;">)(.*?)(<\/span>)/i', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            if ($m[2] !== '🔍') {
                $content = str_replace($m[0], $m[1] . '🔍' . $m[3], $content);
                $changed = true;
            }
        }
    }
    
    // Check for lockTokenBtn span using regex
    if (preg_match_all('/(<span id="lockTokenBtn"[^>]*>)(.*?)(<\/span>)/i', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            if ($m[2] !== '🔒') {
                $content = str_replace($m[0], $m[1] . '🔒' . $m[3], $content);
                $changed = true;
            }
        }
    }
    
    // Fix placeholders
    if (strpos($content, 'placeholder="?? ') !== false || strpos($content, 'placeholder="dY"? ') !== false) {
        $content = preg_replace('/placeholder="(.*?)\s+(Search.*?)"/i', 'placeholder="$2"', $content);
        $changed = true;
    }

    if ($changed) {
        file_put_contents($file, $content);
        echo "Regex Fixed icons in $file\n";
    }
}
?>
