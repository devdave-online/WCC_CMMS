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
    $content = Get-Content $file -Raw
    
    # Check if the icon span is already added to avoid duplicates
    if ($content -notmatch '<span style="position:absolute; left:12px; top:50%; transform:translateY\(-50%\); color:var\(--text-secondary\); pointer-events:none; font-size:1.1em;">') {
        
        # We find the search container div that wraps the input
        # We need to prepend the icon span right before the <input type="text" id="ledgerSearch"
        
        $iconHtml = '<span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-secondary); pointer-events:none; font-size:1.1em;">??</span>'
        
        # Pattern 1: match the input start
        $content = $content -replace '(<input type="text" id="ledgerSearch")', ("$iconHtml`n                        `$1")
        
        # Fix padding: replace padding:8px 35px 8px 12px; with padding:8px 35px 8px 35px;
        $content = $content -replace 'padding:8px 35px 8px 12px;', 'padding:8px 35px 8px 35px;'
        
        # For equipment.php which might have different padding (it used a class .search-input) 
        # Actually equipment.php has style="..." ? Let's just forcefully inject the style for all ledgerSearch inputs to ensure consistency.
        $content = $content -replace 'style="width:360px; padding:8px 35px 8px 12px; border-radius:20px; border: 1px solid var\(--text-accent\); background:var\(--input-bg\); color:var\(--text-primary\); transition: all 0.3s; box-sizing: border-box;"', 'style="width:360px; padding:8px 35px 8px 35px; border-radius:20px; border: 1px solid var(--text-accent); background:var(--input-bg); color:var(--text-primary); transition: all 0.3s; box-sizing: border-box;"'
        
        # For equipment.php, setup_vault_vendors, vendors which use style classes:
        # replace `class="search-input"` with the inline style above to match others.
        if ($file -match 'equipment.php' -or $file -match 'vendors.php') {
            $content = $content -replace 'class="search-input"', 'style="width:360px; padding:8px 35px 8px 35px; border-radius:20px; border: 1px solid var(--text-accent); background:var(--input-bg); color:var(--text-primary); transition: all 0.3s; box-sizing: border-box;"'
        }
        
        # Remove any leading magnifying glasses from the placeholder text
        $content = $content -replace 'placeholder="?? ', 'placeholder="'
        $content = $content -replace 'placeholder="?? ', 'placeholder="'
        
        Set-Content -Path $file -Value $content
        Write-Output "Updated $file"
    } else {
        Write-Output "Already updated $file"
    }
}
