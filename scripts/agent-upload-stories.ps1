<#
.SYNOPSIS
  Non-interactive story uploader for AI agents / CI.

.DESCRIPTION
  Resolves story folders under manji-stories, prepares staging packages,
  and posts them to production via stories:import-old --remote.

  Exit codes:
    0 = all requested stories uploaded (or dry-run planned) successfully
    1 = usage / config / fatal error
    2 = one or more stories failed (partial success possible)

.EXAMPLE
  # Upload one story by folder name or numeric id
  .\scripts\agent-upload-stories.ps1 -Stories "22","21 - gulliver in lilliput"

.EXAMPLE
  # Upload every folder matching a prefix
  .\scripts\agent-upload-stories.ps1 -Stories "22","21","20" -DryRun

.EXAMPLE
  # Machine-readable summary
  .\scripts\agent-upload-stories.ps1 -Stories "22" -JsonSummary
#>

[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string[]]$Stories,

    [string]$ManjiStoriesRoot = "",

    [switch]$DryRun,

    [switch]$DeployOnly,

    [switch]$NoCreateDb,

    [switch]$SkipPreflight,

    [switch]$JsonSummary
)

$ErrorActionPreference = "Stop"
$repoLaravel = Split-Path $PSScriptRoot -Parent
$repoRoot = Split-Path $repoLaravel -Parent
Set-Location $repoLaravel

if ($ManjiStoriesRoot -eq "") {
    $ManjiStoriesRoot = Join-Path $repoRoot "manji-stories"
}
$ManjiStoriesRoot = (Resolve-Path -LiteralPath $ManjiStoriesRoot).Path
$stagingRoot = Join-Path $ManjiStoriesRoot "staging"

function Read-DotEnvValue([string]$key) {
    $envFile = Join-Path $repoLaravel ".env"
    if (-not (Test-Path -LiteralPath $envFile)) { return $null }
    foreach ($line in Get-Content -LiteralPath $envFile) {
        if ($line -match "^\s*#" -or $line -notmatch "=") { continue }
        $name, $value = $line.Split("=", 2)
        if ($name.Trim() -eq $key) {
            return $value.Trim().Trim("'").Trim('"')
        }
    }
    return $null
}

function Resolve-StoryFolder([string]$query) {
    $query = $query.Trim()
    $direct = Join-Path $ManjiStoriesRoot $query
    if (Test-Path -LiteralPath $direct -PathType Container) {
        return (Resolve-Path -LiteralPath $direct).Path
    }

    if ($query -match '^\d+$') {
        $matches = Get-ChildItem -LiteralPath $ManjiStoriesRoot -Directory |
            Where-Object { $_.Name -match ("^{0}\s*-" -f [regex]::Escape($query)) }
        if ($matches.Count -eq 1) { return $matches[0].FullName }
        if ($matches.Count -gt 1) {
            throw "Ambiguous story id ${query}: $($matches.Name -join ', ')"
        }
    }

    $fuzzy = Get-ChildItem -LiteralPath $ManjiStoriesRoot -Directory |
        Where-Object { $_.Name -like "*$query*" -and $_.Name -ne "staging" -and $_.Name -ne "library-original-30" }
    if ($fuzzy.Count -eq 1) { return $fuzzy[0].FullName }
    if ($fuzzy.Count -gt 1) {
        throw "Ambiguous story query '${query}': $($fuzzy.Name -join ', ')"
    }

    throw "Story folder not found for '${query}' under $ManjiStoriesRoot"
}

function Invoke-Preflight([string]$storyPath) {
    # Prefer PHP preflight (files + speaker IDs vs characters JSON).
    $jsonOut = & php artisan stories:preflight-package $storyPath --json 2>&1
    if ($LASTEXITCODE -eq 0 -or $LASTEXITCODE -eq 1) {
        try {
            $parsed = $jsonOut | Out-String | ConvertFrom-Json
            $issues = @()
            if ($parsed.issues) { $issues += @($parsed.issues) }
            if ($parsed.warnings) {
                foreach ($w in @($parsed.warnings)) {
                    Write-Host "  warning: $w" -ForegroundColor DarkYellow
                }
            }
            return $issues
        }
        catch {
            # fall through to local file checks
        }
    }

    $issues = @()
    $chars = Join-Path $storyPath "characters_and_objects.json"
    if (-not (Test-Path -LiteralPath $chars)) {
        $issues += "missing characters_and_objects.json"
    }

    $episodeDirs = Get-ChildItem -LiteralPath $storyPath -Directory -ErrorAction SilentlyContinue |
        Where-Object { $_.Name -match '^episode\s+\d+' }
    if ($episodeDirs.Count -eq 0) {
        $issues += "no episode folders (expected 'episode N - …')"
    }

    foreach ($ep in $episodeDirs) {
        $md = @(Get-ChildItem -LiteralPath $ep.FullName -Filter "*.md" -ErrorAction SilentlyContinue)
        $prompts = @(Get-ChildItem -LiteralPath $ep.FullName -Filter "*image_prompts*.json" -ErrorAction SilentlyContinue)
        if ($md.Count -eq 0) { $issues += "episode '$($ep.Name)': missing .md script" }
        if ($prompts.Count -eq 0) { $issues += "episode '$($ep.Name)': missing *_image_prompts.json" }
    }

    return $issues
}

$baseUrl = Read-DotEnvValue "LOCAL_IMPORT_API_BASE_URL"
$token = Read-DotEnvValue "LOCAL_IMPORT_API_TOKEN"
if ([string]::IsNullOrWhiteSpace($baseUrl) -or [string]::IsNullOrWhiteSpace($token)) {
    Write-Error "LOCAL_IMPORT_API_BASE_URL / LOCAL_IMPORT_API_TOKEN missing in manji-laravel/.env"
    exit 1
}

Write-Host "=== agent-upload-stories ===" -ForegroundColor Cyan
Write-Host "API:     $baseUrl"
Write-Host "Stories: $ManjiStoriesRoot"
Write-Host "Request: $($Stories -join ', ')"
Write-Host ""

$results = @()
$failures = 0

foreach ($storyQuery in $Stories) {
    $entry = [ordered]@{
        query       = $storyQuery
        folder      = $null
        status      = "pending"
        preflight   = @()
        package     = $null
        error       = $null
    }

    try {
        $storyPath = Resolve-StoryFolder $storyQuery
        $entry.folder = Split-Path $storyPath -Leaf
        Write-Host "→ $($entry.folder)" -ForegroundColor Yellow

        if (-not $SkipPreflight) {
            $issues = Invoke-Preflight $storyPath
            $entry.preflight = $issues
            if ($issues.Count -gt 0) {
                throw ("Preflight failed: " + ($issues -join "; "))
            }
        }

        $prepare = Join-Path $PSScriptRoot "prepare-story-package.ps1"
        & $prepare -StoryFolder $storyPath -StagingRoot $stagingRoot -Force | Out-Null
        $packagePath = Join-Path $stagingRoot $entry.folder
        $entry.package = $packagePath

        if (-not (Test-Path -LiteralPath (Join-Path $packagePath "import_manifest.json"))) {
            throw "import_manifest.json missing after prepare"
        }

        $artisanArgs = @(
            "stories:import-old",
            "--remote",
            "--source=$stagingRoot",
            "--only=$($entry.folder)",
            "--force"
        )
        if (-not $NoCreateDb) { $artisanArgs += "--create-db" }
        if ($DryRun) { $artisanArgs += "--dry-run" }
        if ($DeployOnly) { $artisanArgs += "--deploy-only" }

        Write-Host "  php artisan $($artisanArgs -join ' ')"
        & php artisan @artisanArgs
        if ($LASTEXITCODE -ne 0) {
            throw "artisan exit code $LASTEXITCODE"
        }

        $entry.status = if ($DryRun) { "dry_run_ok" } else { "uploaded" }
        Write-Host "  OK ($($entry.status))" -ForegroundColor Green
    }
    catch {
        $failures++
        $entry.status = "failed"
        $entry.error = "$_"
        Write-Host "  FAILED: $_" -ForegroundColor Red
    }

    $results += [pscustomobject]$entry
}

Write-Host ""
Write-Host "=== summary ===" -ForegroundColor Cyan
$results | ForEach-Object {
    $line = "$($_.status) | $($_.folder)"
    if ($_.error) { $line += " | $($_.error)" }
    Write-Host $line
}

if ($JsonSummary) {
    $payload = [ordered]@{
        api_base_url = $baseUrl
        dry_run      = [bool]$DryRun
        results      = $results
        failed_count = $failures
    }
    $payload | ConvertTo-Json -Depth 8
}

if ($failures -gt 0) { exit 2 }
exit 0
