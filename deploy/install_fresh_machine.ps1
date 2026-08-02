# =============================================================================
# Hotel Osamma - one-shot installer for a brand new Windows PC
# =============================================================================
# Run this on a completely empty machine (no PHP, no Git, no Node.js, nothing).
# It installs every prerequisite, deploys the app, sets it up as a permanent
# Windows service, and configures automatic cloud backup.
#
# HOW TO RUN:
#   1. Right-click PowerShell -> "Run as Administrator"
#   2. Paste this single line and press Enter:
#      powershell -ExecutionPolicy Bypass -File install_fresh_machine.ps1
#
# The script is safe to re-run if it stops partway through - it skips any
# step that already succeeded and continues from where it left off.
# =============================================================================

# ---- Self-elevate to Administrator -----------------------------------------
$currentPrincipal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $currentPrincipal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Write-Host "Restarting as Administrator..." -ForegroundColor Yellow
    Start-Process powershell -Verb RunAs -ArgumentList "-ExecutionPolicy Bypass -File `"$PSCommandPath`""
    exit
}

$ErrorActionPreference = "Stop"
$LogFile = "$env:USERPROFILE\Desktop\hotel_install_log.txt"
Start-Transcript -Path $LogFile -Append | Out-Null

function Step($msg) {
    Write-Host ""
    Write-Host "==> $msg" -ForegroundColor Cyan
}
function OK($msg) {
    Write-Host "    OK: $msg" -ForegroundColor Green
}
function Fail($msg) {
    Write-Host "    FAILED: $msg" -ForegroundColor Red
    Write-Host ""
    Write-Host "The full log was saved to: $LogFile" -ForegroundColor Yellow
    Write-Host "Send that file so the problem can be diagnosed." -ForegroundColor Yellow
    Stop-Transcript | Out-Null
    Read-Host "Press Enter to close this window"
    exit 1
}

$RepoUrl    = "https://github.com/L-U-P-I-N/hotel_osamma.git"
$ProjectDir = "C:\laragon\www\hotel_osamma"

# ---- 1. Prerequisites via winget --------------------------------------------
Step "Checking Windows Package Manager (winget)"
$winget = Get-Command winget -ErrorAction SilentlyContinue
if (-not $winget) {
    Fail "winget is not available on this PC. Windows must be updated to a recent version first (Microsoft Store > 'App Installer')."
}
OK "winget is available"

Step "Installing Git (skipped if already installed)"
try {
    winget install --id Git.Git -e --source winget --accept-source-agreements --accept-package-agreements --silent
    OK "Git ready"
} catch { Fail "Could not install Git: $_" }

Step "Installing Node.js LTS (skipped if already installed)"
try {
    winget install --id OpenJS.NodeJS.LTS -e --source winget --accept-source-agreements --accept-package-agreements --silent
    OK "Node.js ready"
} catch { Fail "Could not install Node.js: $_" }

Step "Installing Laragon - PHP + Apache (skipped if already installed)"
try {
    winget install --id LeNgocKhoa.Laragon -e --source winget --accept-source-agreements --accept-package-agreements --silent
    OK "Laragon ready"
} catch { Fail "Could not install Laragon: $_" }

# Refresh PATH in this session so newly-installed tools (git, node) are found
# without needing to close and reopen PowerShell.
$machinePath = [System.Environment]::GetEnvironmentVariable("Path", "Machine")
$userPath    = [System.Environment]::GetEnvironmentVariable("Path", "User")
$env:Path = "$machinePath;$userPath"

if (-not (Test-Path "C:\laragon")) {
    Fail "Laragon does not appear to be installed at C:\laragon after the winget install step."
}

Step "Locating PHP and Apache inside Laragon"
$PhpDir = Get-ChildItem "C:\laragon\bin\php\php-*" -Directory -ErrorAction SilentlyContinue |
    Sort-Object Name -Descending | Select-Object -First 1
$ApacheDir = Get-ChildItem "C:\laragon\bin\apache\httpd-*" -Directory -ErrorAction SilentlyContinue |
    Sort-Object Name -Descending | Select-Object -First 1
if (-not $PhpDir -or -not $ApacheDir) {
    Fail "Could not find PHP or Apache under C:\laragon\bin - Laragon installation looks incomplete."
}
$PhpExe = Join-Path $PhpDir.FullName "php.exe"
OK "PHP found: $($PhpDir.Name)"
OK "Apache found: $($ApacheDir.Name)"

$gitExe = "C:\Program Files\Git\bin\git.exe"
if (-not (Test-Path $gitExe)) {
    $gitCmd = Get-Command git -ErrorAction SilentlyContinue
    if ($gitCmd) { $gitExe = $gitCmd.Source } else { Fail "git.exe not found even after installing Git." }
}
$npmExe = (Get-Command npm -ErrorAction SilentlyContinue).Source
if (-not $npmExe) { Fail "npm not found even after installing Node.js." }

# ---- 2. Clone / update the project ------------------------------------------
Step "Downloading the hotel system (main branch)"
if (Test-Path "$ProjectDir\.git") {
    OK "Project already present, pulling latest main"
    & $gitExe -C $ProjectDir fetch origin main
    & $gitExe -C $ProjectDir checkout main
    & $gitExe -C $ProjectDir reset --hard origin/main
} else {
    New-Item -ItemType Directory -Path "C:\laragon\www" -Force -ErrorAction SilentlyContinue | Out-Null
    & $gitExe clone --branch main $RepoUrl $ProjectDir
}
if (-not (Test-Path "$ProjectDir\artisan")) { Fail "The project folder does not contain a Laravel app (artisan file missing) after git clone/pull." }
OK "Project code is up to date"

Set-Location $ProjectDir

# ---- 3. Composer dependencies ------------------------------------------------
Step "Installing PHP packages (composer)"
if (-not (Test-Path "$ProjectDir\composer.phar")) {
    Invoke-WebRequest -Uri "https://getcomposer.org/composer.phar" -OutFile "$ProjectDir\composer.phar" -UseBasicParsing
}
& $PhpExe "$ProjectDir\composer.phar" install --no-dev --optimize-autoloader
if (-not (Test-Path "$ProjectDir\vendor\autoload.php")) { Fail "composer install did not produce vendor/autoload.php." }
OK "PHP packages installed"

# ---- 4. Frontend assets -------------------------------------------------------
Step "Installing and building the web assets (npm)"
& $npmExe install
& $npmExe run build
if (-not (Test-Path "$ProjectDir\public\build\manifest.json")) { Fail "npm run build did not produce public/build/manifest.json." }
OK "Web assets built"

# ---- 5. Environment configuration --------------------------------------------
Step "Configuring .env"
if (-not (Test-Path "$ProjectDir\.env")) {
    Copy-Item "$ProjectDir\.env.example" "$ProjectDir\.env"
}
(Get-Content "$ProjectDir\.env") `
    -replace '^APP_ENV=.*', 'APP_ENV=production' `
    -replace '^APP_DEBUG=.*', 'APP_DEBUG=false' `
    -replace '^APP_URL=.*', 'APP_URL=http://localhost' `
    -replace '^DB_CONNECTION=.*', 'DB_CONNECTION=sqlite' |
    Set-Content "$ProjectDir\.env"

if (-not (Select-String -Path "$ProjectDir\.env" -Pattern "^DB_DATABASE=" -Quiet)) {
    Add-Content "$ProjectDir\.env" "`nDB_DATABASE=database/database.sqlite"
}

$hasKey = (Select-String -Path "$ProjectDir\.env" -Pattern "^APP_KEY=base64:").Count -gt 0
if (-not $hasKey) {
    & $PhpExe artisan key:generate --force
}
OK ".env ready"

# ---- 6. Database ---------------------------------------------------------------
Step "Setting up the database"
if (-not (Test-Path "$ProjectDir\database\database.sqlite")) {
    New-Item -ItemType File -Path "$ProjectDir\database\database.sqlite" -Force | Out-Null
}
& $PhpExe artisan migrate --force
if ($LASTEXITCODE -ne 0) { Fail "A migration failed - see the output above." }
OK "Database is ready"

& $PhpExe artisan storage:link

# Optional: import a backup zip if the operator dropped one on the Desktop
# or in Downloads (must contain a database-*.sql file, same format produced
# by the online system's "download full system backup" button). Safe to
# skip entirely - a fresh empty database is used otherwise.
Step "Looking for an existing data backup to import (optional)"
$backupZip = Get-ChildItem -Path "$env:USERPROFILE\Desktop", "$env:USERPROFILE\Downloads" `
    -Filter "*.zip" -ErrorAction SilentlyContinue |
    Where-Object { $_.Name -match "backup" } |
    Sort-Object LastWriteTime -Descending | Select-Object -First 1
if ($backupZip) {
    $ageMinutes = [math]::Round(((Get-Date) - $backupZip.LastWriteTime).TotalMinutes)
    Write-Host ""
    Write-Host "    Found backup file: $($backupZip.Name)" -ForegroundColor Yellow
    Write-Host "    Downloaded: $($backupZip.LastWriteTime)  ($ageMinutes minutes ago)" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "    If this is NOT a backup you just downloaded moments ago from the" -ForegroundColor Yellow
    Write-Host "    online system's 'download full system backup' button, STOP - importing" -ForegroundColor Yellow
    Write-Host "    an old backup will overwrite the database with outdated data." -ForegroundColor Yellow
    Write-Host ""
    $confirm = Read-Host "    Import this backup now? (y/n)"
    if ($confirm -match '^(y|yes)$') {
        $extractDir = "$env:TEMP\hotel_backup_import"
        Remove-Item $extractDir -Recurse -Force -ErrorAction SilentlyContinue
        Expand-Archive -Path $backupZip.FullName -DestinationPath $extractDir -Force
        $sqlFile = Get-ChildItem $extractDir -Filter "*.sql" -Recurse | Select-Object -First 1
        if ($sqlFile) {
            & $PhpExe "$ProjectDir\deploy\import_mysql_dump.php" $sqlFile.FullName "$ProjectDir\database\database.sqlite"
            OK "Old data imported - check the 'Data freshness check' dates printed above"
        }
        $filesDir = Get-ChildItem $extractDir -Filter "files" -Directory -Recurse | Select-Object -First 1
        if ($filesDir) {
            Copy-Item "$($filesDir.FullName)\*" "$ProjectDir\storage\app\private\" -Recurse -Force -ErrorAction SilentlyContinue
        }
    } else {
        OK "Skipped importing - continuing with an empty database"
    }
} else {
    OK "No backup zip found on Desktop/Downloads - starting with an empty database"
}

# ---- 7. Apache + PHP module ------------------------------------------------------
Step "Configuring Apache to run PHP"
$laragonPhpConf = "C:\laragon\etc\apache2\mod_php.conf"
New-Item -ItemType Directory -Path (Split-Path $laragonPhpConf) -Force -ErrorAction SilentlyContinue | Out-Null
@"
LoadFile "$($PhpDir.FullName)\libcrypto-3-x64.dll"
LoadFile "$($PhpDir.FullName)\libssl-3-x64.dll"
LoadModule php_module "$($PhpDir.FullName)\php8apache2_4.dll"
PHPIniDir "$($PhpDir.FullName)"
<IfModule mime_module>
    AddType application/x-httpd-php .php
</IfModule>
"@ | Set-Content $laragonPhpConf

$httpdConf = Join-Path $ApacheDir.FullName "conf\httpd.conf"
$conf = Get-Content $httpdConf -Raw
if ($conf -notmatch '(?m)^LoadModule rewrite_module') {
    $conf = $conf -replace '(?m)^#\s*LoadModule rewrite_module modules/mod_rewrite\.so', 'LoadModule rewrite_module modules/mod_rewrite.so'
}
# Match on the filename alone (not the full path with a specific slash
# direction) so a previously-added Include - however it was written - is
# recognised and never duplicated. A duplicate Include of this same file
# makes Apache load the PHP module twice and blocks the service from
# starting.
if ($conf -notmatch '(?i)Include\s+"[^"]*mod_php\.conf"') {
    $includeLine = "Include `"$($laragonPhpConf -replace '\\','/')`""
    $conf = $conf -replace '(Include conf/extra/httpd-vhosts\.conf)', "$includeLine`n`$1"
    if ($conf -notmatch '(?i)Include\s+"[^"]*mod_php\.conf"') {
        $conf += "`n$includeLine`nInclude conf/extra/httpd-vhosts.conf`n"
    }
}
$conf = $conf -replace 'DocumentRoot "\$\{SRVROOT\}/htdocs"', 'DocumentRoot "C:/laragon/www"'
$conf = $conf -replace '<Directory "\$\{SRVROOT\}/htdocs">', '<Directory "C:/laragon/www">'
Set-Content -Path $httpdConf -Value $conf
OK "Apache core config updated"

Step "Setting up the site (virtual host)"
$vhostConf = Join-Path $ApacheDir.FullName "conf\extra\httpd-vhosts.conf"
$siteBlock = @"

<VirtualHost *:80>
    ServerAdmin admin@hotel-osamma.local
    DocumentRoot "C:/laragon/www/hotel_osamma/public"
    ServerName localhost

    <Directory "C:/laragon/www/hotel_osamma/public">
        Options FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.php index.html index.htm

        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteBase /
            RewriteRule ^index\.php$ - [L]
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule . /index.php [L]
        </IfModule>
    </Directory>
</VirtualHost>
"@
$existingVhost = Get-Content $vhostConf -Raw -ErrorAction SilentlyContinue
if ($existingVhost -notmatch "hotel-osamma") {
    Add-Content -Path $vhostConf -Value $siteBlock
}
OK "Virtual host configured"

# ---- 8. Windows Service ------------------------------------------------------------
Step "Registering the web server as a permanent Windows Service"
$apacheExe = Join-Path $ApacheDir.FullName "bin\httpd.exe"
$serviceName = "HotelOsammaApache"

# Stop and disable any conflicting Apache service bound to port 80 (e.g. from
# XAMPP) so it cannot fight this one for the port after a reboot.
Get-Service -Name "Apache*" -ErrorAction SilentlyContinue |
    Where-Object { $_.Name -ne $serviceName } |
    ForEach-Object {
        Stop-Service -Name $_.Name -Force -ErrorAction SilentlyContinue
        Set-Service -Name $_.Name -StartupType Manual -ErrorAction SilentlyContinue
    }

if (Get-Service -Name $serviceName -ErrorAction SilentlyContinue) {
    Stop-Service -Name $serviceName -Force -ErrorAction SilentlyContinue
    & $apacheExe -k uninstall -n $serviceName
}
Stop-Process -Name httpd -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 1
& $apacheExe -k install -n $serviceName -d $ApacheDir.FullName
Set-Service -Name $serviceName -StartupType Automatic
Start-Service -Name $serviceName
sc.exe failure $serviceName reset= 86400 actions= restart/5000/restart/10000/restart/30000
sc.exe failureflag $serviceName 1

Start-Sleep -Seconds 2
$svc = Get-Service -Name $serviceName
if ($svc.Status -ne "Running") { Fail "The $serviceName service did not start." }
OK "$serviceName service is running and set to start automatically"

# ---- 9. Production caches + shortcut + backups ------------------------------------
Step "Optimizing and finishing touches"
& $PhpExe artisan config:cache
& $PhpExe artisan route:cache
& $PhpExe artisan view:cache

& powershell -ExecutionPolicy Bypass -File "$ProjectDir\deploy\create_desktop_shortcut.ps1"
& powershell -ExecutionPolicy Bypass -File "$ProjectDir\deploy\install_scheduled_backup.ps1"
OK "Desktop shortcut and automatic cloud backup are set up"

# ---- 10. Final check ----------------------------------------------------------------
Step "Testing the site"
Start-Sleep -Seconds 2
try {
    $resp = Invoke-WebRequest -Uri "http://localhost/login" -UseBasicParsing -TimeoutSec 15
    if ($resp.StatusCode -eq 200) {
        OK "Site responded successfully (HTTP 200)"
    } else {
        Fail "Site responded with unexpected status $($resp.StatusCode)"
    }
} catch {
    Fail "Could not reach http://localhost/login : $_"
}

Write-Host ""
Write-Host "=================================================================" -ForegroundColor Green
Write-Host "  Installation complete!" -ForegroundColor Green
Write-Host "  Double-click the hotel system icon on the Desktop to open it." -ForegroundColor Green
Write-Host "=================================================================" -ForegroundColor Green

Stop-Transcript | Out-Null
Read-Host "Press Enter to close this window"
