# ============================================================
# Mahru - copy the newest server backup to this Windows PC (2026-11-11)
#
# The server keeps only the last BACKUP_KEEP=3 nightly backups in /srv/mahru/backups.
# This script copies the newest one to -Dest so a copy survives losing the server.
# Backups are age-encrypted on the server; they stay encrypted here (private key only on this PC).
#
# Run once by hand:   powershell -ExecutionPolicy Bypass -File deploy\backup\pull-backup.ps1
# Weekly, unattended: deploy\backup\install-pull-task.ps1   (docs/deployment/BACKUP.md)
#
# Needs: "Host mahru" in ~/.ssh/config and the key loaded in the Windows ssh-agent
# (a scheduled task cannot type a passphrase). Exit code 0 = OK, 1 = failed (see pull-backup.log).
# Text is ASCII on purpose: Windows PowerShell 5.1 misreads UTF-8 scripts without a BOM.
# ============================================================
param(
    [string]$Dest = (Join-Path $HOME 'MahruBackups'),
    [int]$Keep = 8,
    [string]$SshHost = 'mahru',
    [string]$RemoteDir = '/srv/mahru/backups',
    [int]$Retries = 5
)

# Continue, not Stop: in Windows PowerShell 5.1 any stderr line of ssh/scp under 2>&1 would become a terminating error
$ErrorActionPreference = 'Continue'
New-Item -ItemType Directory -Force -Path $Dest | Out-Null
$log = Join-Path $Dest 'pull-backup.log'
$failFlag = Join-Path $Dest 'LAST-PULL-FAILED.txt'

function Log([string]$msg) {
    $line = '{0}  {1}' -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $msg
    Write-Host $line
    Add-Content -Path $log -Value $line -Encoding UTF8
}

function Fail([string]$msg) {
    Log "FAILED: $msg"
    Set-Content -Path $failFlag -Value ("{0}`r`n{1}`r`nSee {2}" -f (Get-Date), $msg, $log) -Encoding UTF8
    exit 1
}

# Not named "Ssh": PowerShell names are case-insensitive, so "& ssh" inside would call this function again
function Invoke-Remote([string]$command) {
    $all = & ssh -o BatchMode=yes -o ConnectTimeout=20 $SshHost $command 2>&1
    # stderr lines (warnings like "Permanently added ...") arrive as ErrorRecord: never treat them as output
    $err = @($all | Where-Object { $_ -is [System.Management.Automation.ErrorRecord] } | ForEach-Object { $_.ToString() })
    $out = @($all | Where-Object { $_ -isnot [System.Management.Automation.ErrorRecord] } | ForEach-Object { $_.ToString() })
    if ($LASTEXITCODE -ne 0) { throw "ssh failed ($LASTEXITCODE): $($err -join ' ')" }
    return $out
}

# 1) newest backup on the server
$latest = $null
for ($i = 1; $i -le $Retries -and -not $latest; $i++) {
    try {
        $latest = (Invoke-Remote "ls -1d $RemoteDir/20* 2>/dev/null | tail -1" | Select-Object -Last 1).ToString().Trim()
    } catch {
        Log "attempt $i/${Retries}: $($_.Exception.Message)"
        if ($i -lt $Retries) { Start-Sleep -Seconds (30 * $i) }
    }
}
if (-not $latest) { Fail "could not list backups on $SshHost (server off, network down, or key not in ssh-agent?)" }
$name = Split-Path $latest -Leaf
if ($name -notmatch '^\d{8}_\d{6}$') { Fail "unexpected backup name: '$latest'" }

$target = Join-Path $Dest $name
if (Test-Path (Join-Path $target '.complete')) {
    Log "already have $name - nothing to do"
    Remove-Item $failFlag -ErrorAction SilentlyContinue
    exit 0
}

# 2) remote checksums (verified after download)
try {
    $remoteSums = @{}
    foreach ($line in (Invoke-Remote "cd $latest && sha256sum *")) {
        if ($line -match '^([0-9a-fA-F]{64})\s+\*?(\S.*)$') { $remoteSums[$Matches[2]] = $Matches[1].ToLower() }
    }
} catch { Fail "could not read checksums: $($_.Exception.Message)" }
if ($remoteSums.Count -eq 0) { Fail "backup $name is empty on the server" }

# 3) download into a temp folder, then rename (a half-copied backup never looks complete)
$partial = Join-Path $Dest ".partial-$name"
$ok = $false
for ($i = 1; $i -le $Retries -and -not $ok; $i++) {
    Remove-Item $partial -Recurse -Force -ErrorAction SilentlyContinue
    New-Item -ItemType Directory -Force -Path $partial | Out-Null
    & scp -q -o BatchMode=yes -o ConnectTimeout=20 -o ServerAliveInterval=15 "${SshHost}:$latest/*" $partial 2>&1 | ForEach-Object { Log "scp: $_" }
    if ($LASTEXITCODE -eq 0) { $ok = $true } else { Log "scp attempt $i/$Retries failed ($LASTEXITCODE)"; if ($i -lt $Retries) { Start-Sleep -Seconds (30 * $i) } }
}
if (-not $ok) { Fail "download of $name failed after $Retries attempts" }

foreach ($file in $remoteSums.Keys) {
    $local = Join-Path $partial $file
    if (-not (Test-Path $local)) { Fail "missing after download: $file" }
    $hash = (Get-FileHash -Algorithm SHA256 -Path $local).Hash.ToLower()
    if ($hash -ne $remoteSums[$file]) { Fail "checksum mismatch: $file" }
}
Set-Content -Path (Join-Path $partial '.complete') -Value (Get-Date -Format o)
if (Test-Path $target) { Remove-Item $target -Recurse -Force }
Rename-Item -Path $partial -NewName $name
$size = '{0:N1} MB' -f ((Get-ChildItem $target -File | Measure-Object Length -Sum).Sum / 1MB)
Log "OK $name ($($remoteSums.Count) files, $size) -> $target"
Remove-Item $failFlag -ErrorAction SilentlyContinue

# 4) keep only the newest $Keep complete backups here
$all = Get-ChildItem $Dest -Directory | Where-Object { $_.Name -match '^\d{8}_\d{6}$' } | Sort-Object Name -Descending
$all | Select-Object -Skip $Keep | ForEach-Object { Log "removing old local backup $($_.Name)"; Remove-Item $_.FullName -Recurse -Force }
exit 0
