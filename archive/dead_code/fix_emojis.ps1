$files = Get-ChildItem -Path "c:\xampp\htdocs\*.php"
foreach ($f in $files) {
    $c = [System.IO.File]::ReadAllText($f.FullName, [System.Text.Encoding]::UTF8)
    $changed = $false
    
    if ($c -match 'dY"\?</span>') {
        $c = $c -replace 'dY"\?</span>', '🔍</span>'
        $changed = $true
    }
    if ($c -match 'font-size:1.1em;">\?\?</span>') {
        $c = $c -replace 'font-size:1.1em;">\?\?</span>', 'font-size:1.1em;">🔍</span>'
        $changed = $true
    }
    if ($c -match 'dY"O</span>') {
        $c = $c -replace 'dY"O</span>', '🔒</span>'
        $changed = $true
    }
    if ($c -match 'scale\(1\)''">\?\?</span>') {
        $c = $c -replace 'scale\(1\)''">\?\?</span>', 'scale(1)''>🔒</span>'
        $changed = $true
    }
    if ($c -match 'placeholder="\?\? ') {
        $c = $c -replace 'placeholder="\?\? ', 'placeholder="'
        $changed = $true
    }
    
    if ($changed) {
        [System.IO.File]::WriteAllText($f.FullName, $c, (New-Object System.Text.UTF8Encoding $false))
        Write-Output "Fixed $($f.Name)"
    }
}
