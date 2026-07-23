<#
.SYNOPSIS
  Build a staging package (import_manifest.json + normalized episode folders)
  from a raw manji-stories story folder.

.DESCRIPTION
  Source layout (writer folder):
    manji-stories/21 - gulliver in lilliput/
      characters_and_objects.json
      episode 1 - گالیور در لیلی‌پوت/
        *_story.md
        *_image_prompts.json

  Output layout (staging package):
    manji-stories/staging/21 - gulliver in lilliput/
      import_manifest.json
      characters_and_objects.json
      episode_1_gulliver_in_lilliput/
        *_story.md
        *_image_prompts.json

.EXAMPLE
  .\scripts\prepare-story-package.ps1 -StoryFolder "..\manji-stories\21 - gulliver in lilliput"
#>

[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string]$StoryFolder,

    [string]$StagingRoot = "",

    [switch]$Force
)

$ErrorActionPreference = "Stop"

function Get-Slug([string]$text) {
    $t = $text.ToLowerInvariant()
    $t = [regex]::Replace($t, "[^a-z0-9]+", "_")
    $t = $t.Trim("_")
    if ([string]::IsNullOrWhiteSpace($t)) { return "episode" }
    return $t
}

$StoryFolder = (Resolve-Path -LiteralPath $StoryFolder).Path
if (-not (Test-Path -LiteralPath $StoryFolder -PathType Container)) {
    throw "Story folder not found: $StoryFolder"
}

$folderName = Split-Path $StoryFolder -Leaf
if ($StagingRoot -eq "") {
    $StagingRoot = Join-Path (Split-Path $StoryFolder -Parent) "staging"
}

$dest = Join-Path $StagingRoot $folderName
if ((Test-Path -LiteralPath $dest) -and -not $Force) {
    throw "Staging package already exists: $dest (use -Force to overwrite)"
}

New-Item -ItemType Directory -Force -Path $StagingRoot | Out-Null
if (Test-Path -LiteralPath $dest) {
    Remove-Item -LiteralPath $dest -Recurse -Force
}
New-Item -ItemType Directory -Force -Path $dest | Out-Null

# Copy root JSON / misc files (not episode dirs)
Get-ChildItem -LiteralPath $StoryFolder -File | ForEach-Object {
    Copy-Item -LiteralPath $_.FullName -Destination (Join-Path $dest $_.Name) -Force
}

$englishHint = $folderName
if ($folderName -match '^\d+\s*-\s*(.+)$') {
    $englishHint = $Matches[1].Trim()
}
$storySlugBase = Get-Slug $englishHint

$episodes = @()
$episodeDirs = Get-ChildItem -LiteralPath $StoryFolder -Directory |
    Where-Object { $_.Name -match '^episode\s+(\d+)\b' }

foreach ($epDir in $episodeDirs) {
    if ($epDir.Name -notmatch '^episode\s+(\d+)\b') { continue }
    $num = [int]$Matches[1]

    $persianTitle = $epDir.Name
    if ($epDir.Name -match '^episode\s+\d+\s*-\s*(.+)$') {
        $persianTitle = $Matches[1].Trim()
    }

    $targetFolder = "episode_{0}_{1}" -f $num, $storySlugBase
    $targetPath = Join-Path $dest $targetFolder
    New-Item -ItemType Directory -Force -Path $targetPath | Out-Null

    Get-ChildItem -LiteralPath $epDir.FullName -File | ForEach-Object {
        Copy-Item -LiteralPath $_.FullName -Destination (Join-Path $targetPath $_.Name) -Force
    }

    $hasScript = @(Get-ChildItem -LiteralPath $targetPath -Filter "*_story.md" -ErrorAction SilentlyContinue).Count -gt 0 `
        -or @(Get-ChildItem -LiteralPath $targetPath -Filter "*.md" -ErrorAction SilentlyContinue).Count -gt 0
    $hasPrompts = @(Get-ChildItem -LiteralPath $targetPath -Filter "*_image_prompts.json" -ErrorAction SilentlyContinue).Count -gt 0 `
        -or @(Get-ChildItem -LiteralPath $targetPath -Filter "*prompts*.json" -ErrorAction SilentlyContinue).Count -gt 0

    $episodes += [ordered]@{
        episode_number = $num
        episode_slug   = $storySlugBase
        source_folder  = $epDir.Name
        target_folder  = $targetFolder
        has_script     = [bool]$hasScript
        has_prompts    = [bool]$hasPrompts
        needs_script   = -not $hasScript
        title_hint     = $persianTitle
    }
}

if ($episodes.Count -eq 0) {
    Write-Warning "No episode folders matching 'episode N - …' found under $StoryFolder"
}

$manifest = [ordered]@{
    story_title    = $englishHint
    story_summary  = $null
    total_episodes = $episodes.Count
    episodes       = @($episodes | Sort-Object { $_.episode_number })
}

$manifestPath = Join-Path $dest "import_manifest.json"
$json = $manifest | ConvertTo-Json -Depth 8
[System.IO.File]::WriteAllText($manifestPath, $json, [System.Text.UTF8Encoding]::new($false))

$hasCharacters = Test-Path -LiteralPath (Join-Path $dest "characters_and_objects.json")

Write-Host ""
Write-Host "Prepared staging package:" -ForegroundColor Green
Write-Host "  $dest"
Write-Host "  characters_and_objects.json: $(if ($hasCharacters) { 'yes' } else { 'MISSING' })"
Write-Host "  episodes: $($episodes.Count)"
Write-Host "  manifest: $manifestPath"
Write-Host ""
Write-Output $dest
