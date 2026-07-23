<#
.SYNOPSIS
  Prepare a story package locally and upload it to the production server.

.DESCRIPTION
  End-to-end local → server flow:
    1) Optionally prepare staging package (characters JSON, episode MD, scene prompts JSON)
    2) Verify LOCAL_IMPORT_API_* credentials from manji-laravel/.env
    3) php artisan stories:import-old --remote --create-db --force …

  Creates / updates on the server:
    - story editor filesystem folder under STORY_EDITOR_STORIES_PATH
    - draft Story + Episode DB rows (--create-db)
    - production imports: characters_and_objects.json, *_story.md, *_image_prompts.json

.EXAMPLE
  # From a raw writer folder:
  .\scripts\upload-story-to-server.ps1 -StoryFolder "..\manji-stories\21 - gulliver in lilliput"

.EXAMPLE
  # From an existing staging package (already has import_manifest.json):
  .\scripts\upload-story-to-server.ps1 -StagingFolder "..\manji-stories\staging\86 - happiness toolbox"

.EXAMPLE
  .\scripts\upload-story-to-server.ps1 -StoryFolder "..\manji-stories\21 - gulliver in lilliput" -DryRun
#>

[CmdletBinding()]
param(
    [string]$StoryFolder = "",

    [string]$StagingFolder = "",

    [switch]$SkipPrepare,

    [switch]$DryRun,

    [switch]$DeployOnly,

    [switch]$ImportOnly,

    [switch]$NoCreateDb,

    [switch]$ForcePrepare
)

$ErrorActionPreference = "Stop"
Set-Location (Split-Path $PSScriptRoot -Parent) # manji-laravel

function Read-DotEnvValue([string]$key) {
    $envFile = Join-Path (Get-Location) ".env"
    if (-not (Test-Path -LiteralPath $envFile)) {
        throw ".env not found in $(Get-Location)"
    }
    foreach ($line in Get-Content -LiteralPath $envFile) {
        if ($line -match "^\s*#" -or $line -notmatch "=") { continue }
        $name, $value = $line.Split("=", 2)
        if ($name.Trim() -eq $key) {
            return $value.Trim().Trim("'").Trim('"')
        }
    }
    return $null
}

$baseUrl = Read-DotEnvValue "LOCAL_IMPORT_API_BASE_URL"
$token = Read-DotEnvValue "LOCAL_IMPORT_API_TOKEN"
if ([string]::IsNullOrWhiteSpace($baseUrl) -or [string]::IsNullOrWhiteSpace($token)) {
    throw "Set LOCAL_IMPORT_API_BASE_URL and LOCAL_IMPORT_API_TOKEN in manji-laravel/.env"
}

Write-Host "Remote API: $baseUrl" -ForegroundColor Cyan

$packagePath = $null
$onlyFilter = $null

if ($StagingFolder -ne "") {
    $packagePath = (Resolve-Path -LiteralPath $StagingFolder).Path
    $SkipPrepare = $true
}
elseif ($StoryFolder -ne "") {
    $source = (Resolve-Path -LiteralPath $StoryFolder).Path
    $folderName = Split-Path $source -Leaf
    $stagingRoot = Join-Path (Split-Path $source -Parent) "staging"
    $packagePath = Join-Path $stagingRoot $folderName

    if (-not $SkipPrepare) {
        $prepareArgs = @{
            StoryFolder = $source
            StagingRoot = $stagingRoot
        }
        if ($ForcePrepare -or (Test-Path -LiteralPath $packagePath)) {
            $prepareArgs["Force"] = $true
        }
        Write-Host "Preparing staging package..." -ForegroundColor Cyan
        & (Join-Path $PSScriptRoot "prepare-story-package.ps1") @prepareArgs | Out-Null
    }

    if (-not (Test-Path -LiteralPath $packagePath)) {
        throw "Staging package not found: $packagePath"
    }
}
else {
    throw "Pass -StoryFolder (raw writer folder) or -StagingFolder (existing package)."
}

$onlyFilter = Split-Path $packagePath -Leaf
$manifest = Join-Path $packagePath "import_manifest.json"
if (-not (Test-Path -LiteralPath $manifest)) {
    throw "import_manifest.json missing in $packagePath — run prepare-story-package.ps1 first."
}

Write-Host "Package: $packagePath" -ForegroundColor Green
Write-Host "Filter:  --only=$onlyFilter" -ForegroundColor Green

$artisanArgs = @(
    "stories:import-old",
    "--remote",
    "--source=$(Split-Path $packagePath -Parent)",
    "--only=$onlyFilter",
    "--force"
)

if (-not $NoCreateDb) { $artisanArgs += "--create-db" }
if ($DryRun) { $artisanArgs += "--dry-run" }
if ($DeployOnly) { $artisanArgs += "--deploy-only" }
if ($ImportOnly) { $artisanArgs += "--import-only" }

Write-Host ""
Write-Host "Running: php artisan $($artisanArgs -join ' ')" -ForegroundColor Yellow
Write-Host ""

& php artisan @artisanArgs
if ($LASTEXITCODE -ne 0) {
    throw "stories:import-old failed with exit code $LASTEXITCODE"
}

Write-Host ""
Write-Host "Done. Next steps on dashboard:" -ForegroundColor Green
Write-Host "  1) Story editor → package → assign character/scene images if needed"
Write-Host "  2) Stories admin → set cover, audio, timelines, publish"
Write-Host "  Doc: docs/LOCAL_TO_SERVER_STORY_IMPORT.md"
