# ============================================================
# Mahru - upload an image tar to the server, resuming and retrying until it is complete (2026-11-11)
#
#   powershell -ExecutionPolicy Bypass -File deploy\single\upload-image.ps1 build-images\mahru-single-<commit>.tar
#
# The home line drops (1-300 KB/s, "Connection reset", "Timeout, server not responding"); by hand that meant
# typing sftp/reput again and again. This script loops: put the first time, reput (resume) after that,
# waits a little longer after each failure, and at the end compares SHA-256 on both sides.
# Safe to stop with Ctrl+C and start again: it continues from what is already on the server.
# Needs "Host mahru" in ~/.ssh/config and the key in the Windows ssh-agent (no passphrase prompts).
# ASCII on purpose: Windows PowerShell 5.1 misreads UTF-8 scripts without a BOM.
# ============================================================
param(
    [Parameter(Mandatory = $true, Position = 0)][string]$File,
    [string]$SshHost = 'mahru',
    [string]$RemoteDir = '/srv/mahru/images',
    [int]$MaxAttempts = 500,
    [int]$MaxWaitSeconds = 120
)

$ErrorActionPreference = 'Continue'
if (-not (Test-Path $File)) { Write-Host "not found: $File" -ForegroundColor Red; exit 1 }
$item = Get-Item $File
$name = $item.Name
$size = $item.Length
$remote = "$RemoteDir/$name"
$opts = @('-o', 'BatchMode=yes', '-o', 'ConnectTimeout=20', '-o', 'ServerAliveInterval=10', '-o', 'ServerAliveCountMax=3')

# Not named "Ssh": PowerShell names are case-insensitive, so "& ssh" inside would call this function again
function Get-RemoteSize {
    $all = & ssh @opts $SshHost "stat -c %s '$remote' 2>/dev/null || echo missing" 2>&1
    if ($LASTEXITCODE -ne 0) { return $null }
    $line = @($all | Where-Object { $_ -isnot [System.Management.Automation.ErrorRecord] } | ForEach-Object { $_.ToString().Trim() }) | Select-Object -Last 1
    if ($line -eq 'missing') { return -1 }
    if ($line -match '^\d+$') { return [int64]$line }
    return $null
}

Write-Host ("{0}  ({1:N1} MB)  ->  {2}:{3}" -f $name, ($size / 1MB), $SshHost, $remote)
$batch = Join-Path $env:TEMP "mahru-upload-$PID.sftp"
$wait = 5
$done = $false

for ($i = 1; $i -le $MaxAttempts; $i++) {
    $have = Get-RemoteSize
    if ($null -eq $have) {
        Write-Host ("[{0}] server not reachable, retry in {1}s" -f (Get-Date -Format 'HH:mm:ss'), $wait) -ForegroundColor Yellow
        Start-Sleep -Seconds $wait; $wait = [Math]::Min($wait * 2, $MaxWaitSeconds); continue
    }
    if ($have -eq $size) { $done = $true; break }
    if ($have -gt $size) {
        Write-Host "remote file is larger than local ($have > $size): removing it and starting over" -ForegroundColor Yellow
        & ssh @opts $SshHost "rm -f '$remote'" 2>&1 | Out-Null
        $have = -1
    }

    $verb = if ($have -lt 0) { 'put' } else { 'reput' }
    $pct = if ($have -gt 0) { [Math]::Round(100.0 * $have / $size, 1) } else { 0 }
    Write-Host ("[{0}] attempt {1}: {2} from {3}%" -f (Get-Date -Format 'HH:mm:ss'), $i, $verb, $pct)
    Set-Content -Path $batch -Encoding ASCII -Value @("cd $RemoteDir", ('{0} "{1}"' -f $verb, $item.FullName.Replace('\', '/')))
    & sftp @opts -b $batch $SshHost
    if ($LASTEXITCODE -eq 0) { $wait = 5; continue }
    # The line drops every few minutes but each connection still moves data: after progress, retry almost at once.
    # Only back off (up to MaxWaitSeconds) while nothing gets through.
    $now = Get-RemoteSize
    if ($null -ne $now -and $now -gt [Math]::Max($have, 0)) {
        Write-Host ("connection dropped after {0:N1} MB, resuming in 3s" -f (($now - [Math]::Max($have, 0)) / 1MB)) -ForegroundColor Yellow
        $wait = 5; Start-Sleep -Seconds 3
    } else {
        Write-Host ("connection dropped, no progress, resume in {0}s" -f $wait) -ForegroundColor Yellow
        Start-Sleep -Seconds $wait; $wait = [Math]::Min($wait * 2, $MaxWaitSeconds)
    }
}
Remove-Item $batch -ErrorAction SilentlyContinue
if (-not $done) { Write-Host "gave up after $MaxAttempts attempts - run the same command again to continue" -ForegroundColor Red; exit 1 }

Write-Host 'upload complete, comparing SHA-256 ...'
$local = (Get-FileHash -Algorithm SHA256 -Path $item.FullName).Hash.ToLower()
$sum = $null
for ($j = 1; $j -le 10 -and -not $sum; $j++) {
    $out = & ssh @opts $SshHost "sha256sum '$remote'" 2>&1
    if ($LASTEXITCODE -eq 0) {
        $line = @($out | Where-Object { $_ -isnot [System.Management.Automation.ErrorRecord] } | ForEach-Object { $_.ToString() }) | Select-Object -Last 1
        if ($line -match '^([0-9a-f]{64})\s') { $sum = $Matches[1] }
    } else { Start-Sleep -Seconds 10 }
}
if (-not $sum) { Write-Host "could not read the checksum on the server - check by hand: sha256sum $remote" -ForegroundColor Yellow; Write-Host "local: $local"; exit 1 }
if ($sum -ne $local) {
    Write-Host "CHECKSUM MISMATCH - removing the remote copy; run the same command again" -ForegroundColor Red
    & ssh @opts $SshHost "rm -f '$remote'" 2>&1 | Out-Null
    exit 1
}
Write-Host "OK  sha256 $local" -ForegroundColor Green
Write-Host "on the server:  cd /srv/mahru && bash deploy/single/server-deploy.sh images/$name"
exit 0
