<#
.SYNOPSIS
  Verify local-import API access and that a story slug exists on the server.

.DESCRIPTION
  1) GET /local-import/verify with LOCAL_IMPORT_API_TOKEN
  2) GET /story-editor/stories and match -StoryFolder / -StorySlug / numeric id

.EXAMPLE
  .\scripts\verify-story-on-server.ps1 -Stories "21","22"

.EXAMPLE
  .\scripts\verify-story-on-server.ps1 -StorySlug "gulliver_in_lilliput" -JsonSummary
#>

[CmdletBinding()]
param(
    [string[]]$Stories = @(),

    [string]$StorySlug = "",

    [string]$StoryFolder = "",

    [switch]$JsonSummary
)

$ErrorActionPreference = "Stop"
$repoLaravel = Split-Path $PSScriptRoot -Parent
Set-Location $repoLaravel

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

function Get-EditorSlugFromFolder([string]$folder) {
    $name = $folder.Trim()
    if ($name -match '^\d+\s*-\s*(.+)$') {
        $name = $Matches[1].Trim()
    }
    $slug = ($name.ToLower() -replace '[^a-z0-9]+', '_').Trim('_')
    if ([string]::IsNullOrWhiteSpace($slug)) { return $null }
    return $slug
}

$baseUrl = Read-DotEnvValue "LOCAL_IMPORT_API_BASE_URL"
$token = Read-DotEnvValue "LOCAL_IMPORT_API_TOKEN"
if ([string]::IsNullOrWhiteSpace($baseUrl) -or [string]::IsNullOrWhiteSpace($token)) {
    Write-Error "LOCAL_IMPORT_API_BASE_URL / LOCAL_IMPORT_API_TOKEN missing in manji-laravel/.env"
    exit 1
}

$baseUrl = $baseUrl.TrimEnd('/')
$headers = @{
    Authorization = "Bearer $token"
    Accept        = "application/json"
}

Write-Host "=== verify-story-on-server ===" -ForegroundColor Cyan
Write-Host "API: $baseUrl"

$verifyUrl = "$baseUrl/local-import/verify"
try {
    $verify = Invoke-RestMethod -Method Get -Uri $verifyUrl -Headers $headers
}
catch {
    Write-Error "Token verify failed: $_"
    exit 1
}

if (-not $verify.success) {
    Write-Error "Verify endpoint returned success=false"
    exit 1
}

Write-Host "Auth OK (user_id=$($verify.data.user_id))" -ForegroundColor Green

$queries = @()
foreach ($s in $Stories) { if ($s) { $queries += $s } }
if ($StoryFolder) { $queries += $StoryFolder }
if ($StorySlug) { $queries += $StorySlug }

if ($queries.Count -eq 0) {
    $payload = [ordered]@{
        authenticated = $true
        user_id       = $verify.data.user_id
        stories       = @()
        note          = "No story filters passed; auth-only check."
    }
    if ($JsonSummary) { $payload | ConvertTo-Json -Depth 6 }
    exit 0
}

$listUrl = "$baseUrl/story-editor/stories"
try {
    $list = Invoke-RestMethod -Method Get -Uri $listUrl -Headers $headers
}
catch {
    Write-Error "Could not list story-editor stories: $_"
    exit 1
}

$remoteStories = @()
if ($list.data -is [System.Array]) {
    $remoteStories = @($list.data)
}
elseif ($list.data.stories) {
    $remoteStories = @($list.data.stories)
}
elseif ($list.data -is [psobject]) {
    $remoteStories = @($list.data)
}

$results = @()
$failures = 0

foreach ($query in $queries) {
    $entry = [ordered]@{
        query       = $query
        expected_slug = $null
        found       = $false
        match       = $null
        error       = $null
    }

    try {
        $slugHint = $null
        if ($query -match '^[a-z0-9_]+$') {
            $slugHint = $query
        }
        else {
            $slugHint = Get-EditorSlugFromFolder $query
        }
        $entry.expected_slug = $slugHint

        $match = $null
        foreach ($story in $remoteStories) {
            $id = [string]($story.id)
            $folder = [string]($story.folder_name)
            $title = [string]($story.title)
            if ($slugHint -and ($id -eq $slugHint -or $id -like "*$slugHint*")) {
                $match = $story
                break
            }
            if ($folder -and ($folder -eq $query -or $folder -like "*$query*")) {
                $match = $story
                break
            }
            if ($query -match '^\d+$' -and $folder -match ("^{0}\s*-" -f [regex]::Escape($query))) {
                $match = $story
                break
            }
            if ($title -and $title -like "*$query*") {
                $match = $story
                break
            }
        }

        if ($null -eq $match) {
            throw "Story not found on server for '$query' (slug hint: $slugHint)"
        }

        $entry.found = $true
        $entry.match = [ordered]@{
            id          = $match.id
            folder_name = $match.folder_name
            title       = $match.title
            episode_count = $match.episode_count
        }
        Write-Host "OK: $query → $($match.id) ($($match.folder_name))" -ForegroundColor Green
    }
    catch {
        $failures++
        $entry.error = "$_"
        Write-Host "MISSING: $query — $_" -ForegroundColor Red
    }

    $results += [pscustomobject]$entry
}

if ($JsonSummary) {
    [ordered]@{
        authenticated = $true
        user_id       = $verify.data.user_id
        results       = $results
        failed_count  = $failures
    } | ConvertTo-Json -Depth 8
}

if ($failures -gt 0) { exit 2 }
exit 0
