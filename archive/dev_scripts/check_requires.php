<?php
$dir = new RecursiveDirectoryIterator('c:/xampp/htdocs');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

$broken_requires = [];

foreach($files as $file) {
    $path = $file[0];
    if (strpos($path, 'archive') !== false || strpos($path, '_dev_artifacts') !== false) continue; // skip archive
    
    $content = file_get_contents($path);
    $lines = explode("\n", $content);
    
    foreach ($lines as $line_num => $line) {
        if (preg_match('/(require_once|require|include_once|include)\s+__DIR__\s*\.\s*[\'"]([^\'"]+)[\'"]/i', $line, $matches) || 
            preg_match('/(require_once|require|include_once|include)\s*\(?\s*[\'"]([^\'"]+)[\'"]\s*\)?/i', $line, $matches)) {
            
            $req_path = $matches[2];
            
            // Resolve the path based on how it's required
            if (strpos($line, '__DIR__') !== false) {
                $abs_path = dirname($path) . $req_path;
            } else {
                // simple require 'file.php' is relative to the current file's directory
                $abs_path = dirname($path) . '/' . $req_path;
            }
            
            $real_path = realpath($abs_path);
            
            if ($real_path === false && !file_exists($abs_path)) {
                $broken_requires[] = "File: " . str_replace("c:\\xampp\\htdocs\\", "", $path) . "\nLine " . ($line_num + 1) . ": " . trim($line) . "\nResolved to: " . $abs_path . " (DOES NOT EXIST)\n";
            }
        }
    }
}

if (empty($broken_requires)) {
    echo "No broken require/include paths found.\n";
} else {
    echo "Found Broken Requires:\n";
    echo implode("\n", $broken_requires);
}
