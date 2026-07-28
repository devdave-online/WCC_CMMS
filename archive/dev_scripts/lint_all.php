<?php
$dir = new RecursiveDirectoryIterator('c:/xampp/htdocs');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

$errors = [];
foreach($files as $file) {
    $path = $file[0];
    if (strpos($path, 'archive') !== false || strpos($path, '_dev_artifacts') !== false) continue; // skip archive
    $output = [];
    $returnVar = 0;
    exec("C:\\xampp\\php\\php.exe -l \"" . $path . "\"", $output, $returnVar);
    if ($returnVar !== 0) {
        $errors[] = implode("\n", $output);
    }
}

if (empty($errors)) {
    echo "All active PHP files passed syntax check.\n";
} else {
    echo "Found syntax errors:\n";
    echo implode("\n\n", $errors);
}
