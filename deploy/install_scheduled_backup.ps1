# Registers a Windows Scheduled Task that runs backup_to_cloud.ps1 every hour,
# for the currently logged-in user. Does NOT require Administrator rights -
# per-user scheduled tasks are allowed under the invoking user's own account.
#
# Run this ONCE after deploying the app on the client's machine:
#   powershell -ExecutionPolicy Bypass -File install_scheduled_backup.ps1

$ErrorActionPreference = "Stop"

$TaskName   = "HotelOsammaBackup"
$ScriptPath = Join-Path $PSScriptRoot "backup_to_cloud.ps1"

if (!(Test-Path $ScriptPath)) {
    Write-Error "Cannot find backup_to_cloud.ps1 next to this installer at $ScriptPath"
    exit 1
}

$action  = New-ScheduledTaskAction -Execute "powershell.exe" `
    -Argument "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File `"$ScriptPath`""

$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) `
    -RepetitionInterval (New-TimeSpan -Hours 1) `
    -RepetitionDuration (New-TimeSpan -Days 3650)

$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries `
    -StartWhenAvailable -ExecutionTimeLimit (New-TimeSpan -Minutes 15)

Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger `
    -Settings $settings -Description "Hourly hotel system backup to OneDrive/Google Drive" `
    -Force | Out-Null

Write-Output "Scheduled task '$TaskName' installed - backups will run every hour."

# Run it once immediately so a first backup exists right away.
Start-ScheduledTask -TaskName $TaskName
Write-Output "Triggered an immediate first run."
