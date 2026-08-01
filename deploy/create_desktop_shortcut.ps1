# Creates a desktop shortcut labeled "نظام الفندق" that opens the hotel
# system in the default browser. No technical knowledge needed by the user:
# the web server already runs 24/7 as a Windows Service, so this shortcut
# is the ONLY thing the hotel staff ever needs to click.

$ErrorActionPreference = "Stop"

$desktop = [Environment]::GetFolderPath("Desktop")
$shortcutPath = Join-Path $desktop "نظام الفندق.url"

$content = @"
[InternetShortcut]
URL=http://localhost/
IconIndex=0
"@

Set-Content -Path $shortcutPath -Value $content -Encoding UTF8 -Force

Write-Output "Desktop shortcut created: $shortcutPath"
