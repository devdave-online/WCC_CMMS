<?php
$files = [
    'c:/xampp/htdocs/_logi/inventory.php',
    'c:/xampp/htdocs/_logi/purchase_orders.php',
    'c:/xampp/htdocs/_eam/setup_vault_equipment.php',
    'c:/xampp/htdocs/_mgmt/users.php',
    'c:/xampp/htdocs/_maint/work_orders.php',
    'c:/xampp/htdocs/_eam/equipment.php',
    'c:/xampp/htdocs/_logi/setup_vault_vendors.php',
    'c:/xampp/htdocs/_logi/vendors.php'
];
foreach($files as $file) {
    $c = file_get_contents($file);
    
    // Fix search icon span (matches any characters inside the span because it might be broken)
    $c = preg_replace('/<span style="position:absolute; left:12px; top:50%; transform:translateY\(-50%\); color:var\(--text-secondary\); pointer-events:none; font-size:1.1em;">.*?<\/span>/s', '<span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); pointer-events:none; font-size:1.1em;">🔍</span>', $c);
    
    // Fix lock token button
    $c = preg_replace('/(<span id="lockTokenBtn"[^>]*>).*?(<\/span>)/s', '$1📌$2', $c);
    
    // Fix placeholder encoding issues (removes leading question marks, unicode emojis, and spaces)
    $c = preg_replace('/placeholder="\?\?\s*🔍\s*/u', 'placeholder="', $c);
    $c = preg_replace('/placeholder="\?\?\s*/', 'placeholder="', $c);
    $c = preg_replace('/placeholder="🔍\s*/u', 'placeholder="', $c);
    
    file_put_contents($file, $c);
}
echo 'Fixed via PHP!';
?>
