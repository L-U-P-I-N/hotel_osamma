# Creates a timestamped backup of the hotel system (database + attached files)
# and drops it into a OneDrive/Google Drive folder so it uploads to the cloud
# automatically whenever the hotel's internet connection is available.
#
# This script does NOT need to detect internet itself - OneDrive/Google Drive's
# own desktop client already queues and uploads reliably once online, which is
# more robust than any custom connectivity check.

$ErrorActionPreference = "Stop"

# ---- Configuration ---------------------------------------------------------
$ProjectDir = "C:\laragon\www\hotel_osamma"
$PhpExe     = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
$KeepLast   = 20   # how many recent backups to retain in the cloud folder

# Auto-detect a cloud-synced folder: prefer OneDrive, then Google Drive, else
# fall back to a local folder (backups still happen, just won't reach the cloud
# until this is pointed at a real synced folder).
function Get-CloudBackupFolder {
    if ($env:OneDrive -and (Test-Path $env:OneDrive)) {
        return Join-Path $env:OneDrive "HotelOsammaBackups"
    }
    $gdrive = Join-Path $env:USERPROFILE "Google Drive"
    if (Test-Path $gdrive) {
        return Join-Path $gdrive "HotelOsammaBackups"
    }
    $gdriveNew = "G:\My Drive"
    if (Test-Path $gdriveNew) {
        return Join-Path $gdriveNew "HotelOsammaBackups"
    }
    return "$ProjectDir\backups_local_only"
}

$BackupFolder = Get-CloudBackupFolder
# -----------------------------------------------------------------------------

$dbFile      = Join-Path $ProjectDir "database\database.sqlite"
$storageDir  = Join-Path $ProjectDir "storage\app\private"
$snapshotPhp = Join-Path $ProjectDir "deploy\backup_snapshot.php"

if (!(Test-Path $dbFile)) {
    Write-Error "Database not found at $dbFile - aborting backup."
    exit 1
}

$timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$tempDir = Join-Path $env:TEMP "hotel_backup_$timestamp"
New-Item -ItemType Directory -Path $tempDir -Force | Out-Null

try {
    # 1. Safe, consistent DB snapshot (works even while the app is live).
    $snapshotDb = Join-Path $tempDir "database.sqlite"
    & $PhpExe $snapshotPhp $dbFile $snapshotDb
    if ($LASTEXITCODE -ne 0) { throw "Database snapshot step failed." }

    # 2. Copy attached files (guest IDs, hotel logo, etc.) if any exist.
    if (Test-Path $storageDir) {
        Copy-Item -Path $storageDir -Destination (Join-Path $tempDir "files") -Recurse -Force
    }

    # 3. Zip it up.
    if (!(Test-Path $BackupFolder)) {
        New-Item -ItemType Directory -Path $BackupFolder -Force | Out-Null
    }
    $zipPath = Join-Path $BackupFolder "hotel-backup-$timestamp.zip"
    Compress-Archive -Path (Join-Path $tempDir "*") -DestinationPath $zipPath -Force

    Write-Output "Backup created: $zipPath"

    # 4. Retention - keep only the most recent $KeepLast backups.
    $allBackups = Get-ChildItem -Path $BackupFolder -Filter "hotel-backup-*.zip" |
        Sort-Object LastWriteTime -Descending
    if ($allBackups.Count -gt $KeepLast) {
        $allBackups | Select-Object -Skip $KeepLast | Remove-Item -Force
        Write-Output "Pruned old backups, kept last $KeepLast."
    }
}
finally {
    if (Test-Path $tempDir) {
        Remove-Item -Path $tempDir -Recurse -Force -ErrorAction SilentlyContinue
    }
}
