# Upload production .env to GitHub Actions secret PRODUCTION_DOTENV.
# Requires: GitHub CLI (gh) installed and authenticated.
#
# Usage (from manji-laravel/):
#   .\scripts\set-production-dotenv-github-secret.ps1

param(
    [string]$Source = ".env",
    [string]$Repo = "thisisnajafi/sarvcast-backend"
)

$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$path = Join-Path $root $Source

if (-not (Test-Path $path)) {
    Write-Error "Env file not found: $path"
    exit 1
}

$lines = Get-Content -Path $path
$out = New-Object System.Collections.Generic.List[string]

foreach ($line in $lines) {
    if ($line -match '^\s*#' -or $line -match '^\s*$') {
        $out.Add($line)
        continue
    }
    if ($line -match '^APP_NAME=') { $out.Add('APP_NAME=Manji'); continue }
    if ($line -match '^APP_ENV=') { $out.Add('APP_ENV=production'); continue }
    if ($line -match '^APP_DEBUG=') { $out.Add('APP_DEBUG=false'); continue }
    if ($line -match '^APP_URL=') { $out.Add('APP_URL=https://my.manjiapp.ir'); continue }
    if ($line -match '^FIREBASE_SERVICE_ACCOUNT_PATH=') {
        $out.Add('FIREBASE_SERVICE_ACCOUNT_PATH=storage/app/firebase-service-account.json')
        continue
    }
    if ($line -match '^LOCAL_IMPORT_') { continue }
    if ($line -match '^ZARINPAL_CALLBACK_URL=') {
        $out.Add('ZARINPAL_CALLBACK_URL=https://my.manjiapp.ir')
        continue
    }
    $out.Add($line)
}

if (-not ($out -match '^ADMIN_DASHBOARD_URL=')) {
    $out.Add('ADMIN_DASHBOARD_URL=https://admin.manjiapp.ir')
}

$payload = ($out -join "`n").TrimEnd() + "`n"
$tmp = Join-Path $env:TEMP "manji-production.env"
Set-Content -Path $tmp -Value $payload -NoNewline -Encoding utf8

$gh = Get-Command gh -ErrorAction SilentlyContinue
if (-not $gh) {
    Write-Host @"

GitHub CLI (gh) is not installed.

Set the secret manually:
  1. Open https://github.com/$Repo/settings/secrets/actions
  2. New repository secret
  3. Name: PRODUCTION_DOTENV
  4. Value: contents of $tmp

"@ -ForegroundColor Yellow
    Write-Host "Production .env preview written to: $tmp"
    exit 1
}

Get-Content -Raw -Path $tmp | gh secret set PRODUCTION_DOTENV --repo $Repo
Write-Host "OK: PRODUCTION_DOTENV set for $Repo" -ForegroundColor Green
Write-Host "Preview file: $tmp"
