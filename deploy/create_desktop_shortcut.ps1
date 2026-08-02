# Creates a desktop shortcut labeled "نظام الفندق". No technical knowledge
# needed by the user: the web server already runs 24/7 as a Windows Service,
# so this shortcut is the ONLY thing hotel staff ever need to click.
#
# Prefers the bundled Electron desktop app (a proper app window, no browser
# chrome) if it was cloned down with the project; falls back to a plain
# browser shortcut otherwise.

$ErrorActionPreference = "Stop"

$desktop = [Environment]::GetFolderPath("Desktop")
$desktopExe = Join-Path $PSScriptRoot "HotelOsamma-Desktop.exe"

if (Test-Path $desktopExe) {
    $shortcutPath = Join-Path $desktop "نظام الفندق.lnk"
    $shell = New-Object -ComObject WScript.Shell
    $shortcut = $shell.CreateShortcut($shortcutPath)
    $shortcut.TargetPath = $desktopExe
    $shortcut.WorkingDirectory = $PSScriptRoot
    $shortcut.Description = "نظام الفندق"
    $shortcut.Save()
    Write-Output "Desktop shortcut created (desktop app): $shortcutPath"
} else {
    $shortcutPath = Join-Path $desktop "نظام الفندق.url"
    $content = @"
[InternetShortcut]
URL=http://localhost/
IconIndex=0
"@
    Set-Content -Path $shortcutPath -Value $content -Encoding UTF8 -Force
    Write-Output "Desktop shortcut created (browser link): $shortcutPath"
}
