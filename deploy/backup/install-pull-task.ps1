# ============================================================
# Mahru - register the weekly "copy newest server backup to this PC" task (2026-11-11)
#
# Run once, in PowerShell, from the project folder:
#   powershell -ExecutionPolicy Bypass -File deploy\backup\install-pull-task.ps1
#
# Creates the Task Scheduler task "Mahru weekly backup pull": every Friday 13:00, and if the PC was off
# at that time it runs as soon as possible afterwards (StartWhenAvailable). Runs as you, only while
# you are logged on, so it can use your ssh-agent key. Remove: Unregister-ScheduledTask "Mahru weekly backup pull"
# ============================================================
param(
    [string]$Dest = (Join-Path $HOME 'MahruBackups'),
    [string]$At = '13:00',
    [string]$Day = 'Friday'
)

# Continue, not Stop: in Windows PowerShell 5.1 any stderr line of ssh/scp under 2>&1 would become a terminating error
$ErrorActionPreference = 'Continue'
$script = Join-Path $PSScriptRoot 'pull-backup.ps1'
if (-not (Test-Path $script)) { throw "not found: $script" }

# The task cannot type the key passphrase: the key must be in the Windows ssh-agent.
& ssh -o BatchMode=yes -o ConnectTimeout=20 mahru true 2>$null
if ($LASTEXITCODE -ne 0) {
    Write-Host ''
    Write-Host 'ssh mahru does not work without a passphrase prompt yet. Once, in an ADMIN PowerShell:' -ForegroundColor Yellow
    Write-Host '    Get-Service ssh-agent | Set-Service -StartupType Automatic'
    Write-Host '    Start-Service ssh-agent'
    Write-Host 'then in a normal PowerShell (asks the passphrase one last time; remembered after reboots):'
    Write-Host '    ssh-add $HOME\.ssh\mahru_prod'
    Write-Host 'and run this script again.'
    exit 1
}

$name = 'Mahru weekly backup pull'
$action = New-ScheduledTaskAction -Execute 'powershell.exe' `
    -Argument ('-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "{0}" -Dest "{1}"' -f $script, $Dest)
$trigger = New-ScheduledTaskTrigger -Weekly -DaysOfWeek $Day -At $At
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -RunOnlyIfNetworkAvailable `
    -ExecutionTimeLimit (New-TimeSpan -Hours 2) -RestartCount 3 -RestartInterval (New-TimeSpan -Minutes 30)
$principal = New-ScheduledTaskPrincipal -UserId "$env:USERDOMAIN\$env:USERNAME" -LogonType Interactive -RunLevel Limited

Register-ScheduledTask -TaskName $name -Action $action -Trigger $trigger -Settings $settings -Principal $principal -Force | Out-Null
Write-Host "Task '$name' registered: every $Day $At -> $Dest" -ForegroundColor Green
Write-Host 'First run now (to check it works):'
Start-ScheduledTask -TaskName $name
Start-Sleep -Seconds 20
Get-Content (Join-Path $Dest 'pull-backup.log') -Tail 5 -ErrorAction SilentlyContinue
