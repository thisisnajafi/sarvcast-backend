# Upload FIREBASE_SERVICE_ACCOUNT_JSON to GitHub Actions secrets.
# Requires: GitHub CLI (gh) installed and authenticated.
#
# Usage (from manji-laravel/):
#   .\scripts\set-firebase-github-secret.ps1
#   .\scripts\set-firebase-github-secret.ps1 -Source storage\app\manjiapp-3028e-firebase-adminsdk.json

param(
    [string]$Source = "storage\app\manjiapp-3028e-firebase-adminsdk.json",
    [string]$Repo = "thisisnajafi/sarvcast-backend"
)

$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$path = Join-Path $root $Source

if (-not (Test-Path $path)) {
    Write-Error "Firebase JSON not found: $path"
    exit 1
}

$gh = Get-Command gh -ErrorAction SilentlyContinue
if (-not $gh) {
    Write-Host @"

GitHub CLI (gh) is not installed.

Set the secret manually:
  1. Open https://github.com/$Repo/settings/secrets/actions
  2. New repository secret
  3. Name: FIREBASE_SERVICE_ACCOUNT_JSON
  4. Value: paste the full contents of:
     $path

"@ -ForegroundColor Yellow
    exit 1
}

Get-Content -Raw -Path $path | gh secret set FIREBASE_SERVICE_ACCOUNT_JSON --repo $Repo
Write-Host "OK: FIREBASE_SERVICE_ACCOUNT_JSON set for $Repo" -ForegroundColor Green
