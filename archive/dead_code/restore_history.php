<?php
$file = 'c:\xampp\htdocs\history.php';
$content = file_get_contents($file);

$target = <<<EOD
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <table>
EOD;

$target = str_replace("\r\n", "\n", $target);
$target = str_replace("\n", "\r\n", $target);

$replacement = <<<EOD
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Event History</title>
</head>
<body><?php include 'nav.php'; ?>

<div class="dashboard-container">
    <div class="header-flex">
        <h2>Event History Archive</h2>
        <a href="index.php" class="nav-btn">🏠 Menu</a>
    </div>
    
    <table>
EOD;

$replacement = str_replace("\r\n", "\n", $replacement);
$replacement = str_replace("\n", "\r\n", $replacement);

$new_content = str_replace($target, $replacement, $content);

if ($new_content === $content) {
    echo "REPLACE FAILED\n";
    // Fallback preg_replace
    $pattern = '/<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">\s*<table/s';
    $replacement = "<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">\n    <meta charset=\"UTF-8\">\n    <title>Event History</title>\n</head>\n<body><?php include 'nav.php'; ?>\n\n<div class=\"dashboard-container\">\n    <div class=\"header-flex\">\n        <h2>Event History Archive</h2>\n        <a href=\"index.php\" class=\"nav-btn\">🏠 Menu</a>\n    </div>\n    \n    <table";
    $new_content = preg_replace($pattern, $replacement, $content);
    
    if ($new_content !== $content) {
        file_put_contents($file, $new_content);
        echo "REPLACE SUCCESS (preg_replace)\n";
    } else {
        echo "REPLACE FAILED COMPLETELY\n";
    }
} else {
    file_put_contents($file, $new_content);
    echo "REPLACE SUCCESS\n";
}
?>

