<?php
// NOTE: Hardcoded path replaced with relative for safety. Dev-only script.
$content = file_get_contents('css/global.css');

// Replace body
$content = preg_replace('/body \{.*?\}/s', "body {\n    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;\n    background: linear-gradient(135deg, #0f172a, #1e293b, #0f172a, #020617);\n    background-size: 200% 200%;\n    animation: gradientShift 12s ease infinite;\n    padding: 20px; \n    margin: 0;\n    margin-left: 80px;\n    min-height: 100vh;\n    color: #f8fafc;\n    transition: margin-left 0.3s;\n}", $content);

// Replace dashboard-container
$content = preg_replace('/\.dashboard-container \{.*?\}/s', ".dashboard-container {\n    background: linear-gradient(135deg, rgba(30, 41, 59, 0.7), rgba(15, 23, 42, 0.9));\n    backdrop-filter: blur(20px);\n    -webkit-backdrop-filter: blur(20px);\n    border: 1px solid rgba(255, 255, 255, 0.1);\n    border-top: 1px solid rgba(255, 255, 255, 0.2);\n    padding: 30px 40px;\n    border-radius: 24px;\n    box-shadow: 0 10px 30px 0 rgba(0, 0, 0, 0.5);\n    max-width: 1400px;\n    margin: auto;\n    overflow-x: auto;\n}", $content);

// Replace header-flex h2
$content = preg_replace('/\.header-flex h2 \{.*?\}/s', ".header-flex h2 {\n    color: var(--text-accent);\n    margin: 0;\n    font-size: 2em;\n}", $content);

// Replace nav-btn
$content = preg_replace('/\.nav-btn, \.btn \{.*?\}/s', ".nav-btn, .btn {\n    background: rgba(15, 23, 42, 0.6);\n    padding: 10px 18px;\n    text-decoration: none;\n    border-radius: 12px;\n    font-weight: 600;\n    border: 1px solid rgba(255, 255, 255, 0.2);\n    transition: 0.2s;\n    color: var(--text-accent);\n    cursor: pointer;\n}", $content);

$content = preg_replace('/\.nav-btn:hover, \.btn:hover \{.*?\}/s', ".nav-btn:hover, .btn:hover {\n    background: rgba(30, 41, 59, 0.8);\n    border: 1px solid rgba(56, 189, 248, 0.4);\n    box-shadow: 0 0 15px rgba(56, 189, 248, 0.2);\n}", $content);

// Replace nav-btn primary
$content = preg_replace('/\.nav-btn\.primary, \.btn\.primary \{.*?\}/s', ".nav-btn.primary, .btn.primary {\n    background: linear-gradient(135deg, #0284c7, #0369a1);\n    color: white;\n    border: none;\n    box-shadow: 0 4px 10px rgba(0,0,0,0.3);\n}", $content);

$content = preg_replace('/\.nav-btn\.primary:hover, \.btn\.primary:hover \{.*?\}/s', ".nav-btn.primary:hover, .btn.primary:hover {\n    background: linear-gradient(135deg, #0369a1, #075985);\n    transform: translateY(-2px);\n    box-shadow: 0 6px 15px rgba(2, 132, 199, 0.4);\n}", $content);

// Replace th
$content = preg_replace('/th \{.*?\}/s', "th {\n    background: rgba(15, 23, 42, 0.8);\n    color: #94a3b8;\n    font-size: 0.85em;\n    text-transform: uppercase;\n    border-bottom: 2px solid rgba(56, 189, 248, 0.2);\n}", $content);

// Replace parent-row:hover
$content = preg_replace('/\.parent-row:hover, tbody tr:hover \{.*?\}/s', ".parent-row:hover, tbody tr:hover {\n    background: rgba(30, 41, 59, 0.5);\n}", $content);

file_put_contents('css/global.css', $content);
echo "global.css rewritten to dark mode.";
?>

