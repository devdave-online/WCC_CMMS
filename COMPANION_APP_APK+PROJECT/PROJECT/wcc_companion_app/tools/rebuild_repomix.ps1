# Rebuild ai_ctxt/REPOMIX_SOURCE_WALL.txt without deleting originals.
# Sole author: Project owner

$ErrorActionPreference = "Stop"
# Resolve project root from this script location (no personal absolute paths).
$proj = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
$out = Join-Path $proj "ai_ctxt\REPOMIX_SOURCE_WALL.txt"
$sb = New-Object System.Text.StringBuilder
[void]$sb.AppendLine("================================================================================")
[void]$sb.AppendLine("WCC COMPANION - REPOMIX SOURCE WALL")
[void]$sb.AppendLine("Sole author: Project owner")
[void]$sb.AppendLine("Generated: $(Get-Date -Format o)")
[void]$sb.AppendLine("This file is a CONCATENATION for analysis. Originals remain untouched.")
[void]$sb.AppendLine("================================================================================")

$files = @()
$files += Get-Item "$proj\ai_agent.ini"
$files += Get-Item "$proj\AUTHOR.md"
$files += Get-ChildItem "$proj\ai_ctxt\*.md" -File | Where-Object { $_.Name -ne "REPOMIX_SOURCE_WALL.txt" } | Sort-Object Name
$files += Get-Item "$proj\app\build.gradle.kts"
$files += Get-Item "$proj\app\src\main\AndroidManifest.xml"
$files += Get-ChildItem "$proj\app\src\main\java" -Recurse -Filter "*.kt" | Sort-Object FullName
$files += Get-Item "$proj\app\src\main\res\values\strings.xml"
$files += Get-ChildItem "$proj\app\src\main\res\xml\*.xml"
$files += Get-ChildItem "$proj\tools\*.py" -ErrorAction SilentlyContinue
$files += Get-Item "$proj\tools\rebuild_repomix.ps1" -ErrorAction SilentlyContinue

$n = 0
foreach ($f in $files) {
  if ($null -eq $f -or -not $f.Exists) { continue }
  if ($f.Name -eq "REPOMIX_SOURCE_WALL.txt") { continue }
  $rel = $f.FullName.Substring($proj.Length + 1)
  [void]$sb.AppendLine("")
  [void]$sb.AppendLine("########## BEGIN FILE: $rel ##########")
  [void]$sb.AppendLine([System.IO.File]::ReadAllText($f.FullName))
  [void]$sb.AppendLine("########## END FILE: $rel ##########")
  $n++
}
[void]$sb.AppendLine("")
[void]$sb.AppendLine("END REPOMIX WALL - files=$n - sole author: Project owner")
[System.IO.File]::WriteAllText($out, $sb.ToString(), [System.Text.UTF8Encoding]::new($false))
Write-Host "Wrote $out ($n files, $((Get-Item $out).Length) bytes)"
