$files = @(
    "c:\xampp\htdocs\inventory.php",
    "c:\xampp\htdocs\purchase_orders.php",
    "c:\xampp\htdocs\setup_vault_equipment.php",
    "c:\xampp\htdocs\users.php",
    "c:\xampp\htdocs\work_orders.php",
    "c:\xampp\htdocs\equipment.php",
    "c:\xampp\htdocs\setup_vault_vendors.php",
    "c:\xampp\htdocs\vendors.php"
)

foreach ($file in $files) {
    $content = [System.IO.File]::ReadAllText($file)
    
    # Fix the search icon: replace ?? with 🔍 inside the injected span
    $content = $content -replace '<span style="position:absolute; left:12px; top:50%; transform:translateY\(-50%\); color:var\(--text-secondary\); pointer-events:none; font-size:1.1em;">.*?</span>', '<span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); pointer-events:none; font-size:1.1em;">🔍</span>'
    
    # Fix the lock token button icon: replace whatever character is between > and </span>
    $content = $content -replace '(<span id="lockTokenBtn"[^>]*>)[^<]*(</span>)', '$1📌$2'
    
    # Fix vendors.php and others that might have "?? " in the placeholder
    $content = $content -replace 'placeholder="\?\?\s*', 'placeholder="'
    
    # Just in case there are other question marks inside placeholder
    $content = $content -replace 'placeholder="\?\? ', 'placeholder="'
    
    # Specifically for vendors.php that had ?? 🔍 Search vendors
    $content = $content -replace 'placeholder="\?\? 🔍 ', 'placeholder="'
    
    [System.IO.File]::WriteAllText($file, $content, [System.Text.Encoding]::UTF8)
    Write-Output "Fixed $file"
}