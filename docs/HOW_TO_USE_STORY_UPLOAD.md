# How to Use: Local → Server Story Upload

Practical guide for humans and AI agents to prepare, upload, and verify Manji stories from `manji-stories/` to production.

For architecture and remaining gaps, see [`LOCAL_TO_SERVER_STORY_IMPORT.md`](LOCAL_TO_SERVER_STORY_IMPORT.md).  
For a copy/paste agent prompt, see [`AGENT_STORY_UPLOAD_PROMPT.md`](AGENT_STORY_UPLOAD_PROMPT.md).

---

## Prerequisites

1. Work from the `manji-laravel/` directory.
2. Writer folders live in `../manji-stories/<N> - <english title>/`.
3. Each story folder should contain:
   - `characters_and_objects.json`
   - one or more `episode N - …/` folders, each with:
     - `*_story.md` (or any `.md` script)
     - `*_image_prompts.json`
4. Local `.env` must include:

```env
LOCAL_IMPORT_API_BASE_URL=https://my.manjiapp.ir/api/admin
LOCAL_IMPORT_API_TOKEN=your-sanctum-token
STORY_EDITOR_STORIES_PATH=/path/on/server/or/local/storage
```

If you do not have a token yet (server must have `LOCAL_IMPORT_BOOTSTRAP_SECRET`):

```powershell
curl.exe -X POST "https://my.manjiapp.ir/api/admin/local-import/bootstrap" `
  -H "X-Local-Import-Bootstrap: YOUR_BOOTSTRAP_SECRET" `
  -H "Accept: application/json"
```

Put the returned token into `LOCAL_IMPORT_API_TOKEN`.

---

## Quick start (recommended)

Upload one or more stories by numeric id or folder name:

```powershell
cd manji-laravel
.\scripts\agent-upload-stories.ps1 -Stories "21","22" -JsonSummary
```

What this does:

1. Resolves folders under `manji-stories`
2. Runs PHP preflight (`stories:preflight-package`) — required files + speaker warnings
3. Builds a staging package (`import_manifest.json`)
4. Posts the zip with `php artisan stories:import-old --remote --create-db --force`

Dry run (no remote write):

```powershell
.\scripts\agent-upload-stories.ps1 -Stories "21" -DryRun -JsonSummary
```

---

## Step-by-step (manual)

### 1) Preflight

```powershell
php artisan stories:preflight-package "..\manji-stories\21 - gulliver in lilliput"
# machine-readable:
php artisan stories:preflight-package "..\manji-stories\21 - gulliver in lilliput" --json
```

- **issues** → hard fail (missing files)
- **warnings** → unknown speakers in `.md` vs `characters_and_objects.json` (upload still allowed from agent script; fix when possible)

### 2) Prepare staging package

```powershell
php artisan stories:prepare-package "..\manji-stories\21 - gulliver in lilliput" --force
# or:
.\scripts\prepare-story-package.ps1 -StoryFolder "..\manji-stories\21 - gulliver in lilliput" -Force
```

Output: `manji-stories/staging/21 - gulliver in lilliput/` with `import_manifest.json`.

### 3) Upload to server

```powershell
.\scripts\upload-story-to-server.ps1 `
  -StoryFolder "..\manji-stories\21 - gulliver in lilliput" `
  -ForcePrepare
```

Or artisan only (staging already prepared):

```powershell
php artisan stories:import-old `
  --remote `
  --source="..\manji-stories\staging" `
  --only="21 - gulliver in lilliput" `
  --create-db `
  --force
```

Flags:

| Flag | Meaning |
|------|---------|
| `--remote` | Send zip to production API |
| `--create-db` | Create/update draft `stories` / `episodes` and link production files |
| `--force` | Overwrite existing editor folder on server |
| `--deploy-only` | Copy files only; skip JSON/MD import |
| `--dry-run` | Plan only |

### 4) Verify on server

```powershell
.\scripts\verify-story-on-server.ps1 -Stories "21","22" -JsonSummary
```

This checks:

1. `GET /api/admin/local-import/verify` (token works)
2. Story appears under `GET /api/admin/story-editor/stories`

---

## After upload (dashboard)

Automation stops at filesystem + production JSON/MD import. Finish in admin:

1. **ویرایشگر داستان → بسته** — assign character / scene / cover images
2. **داستان‌ها** — cover, category, premium, audio, image timelines
3. Publish story + episodes
4. Confirm in Flutter app

Optional repair if character images are missing in Flutter:

```powershell
php artisan stories:sync-production-character-images {story-slug}
```

---

## Exit codes

### `agent-upload-stories.ps1` / `verify-story-on-server.ps1`

| Code | Meaning |
|------|---------|
| `0` | Success |
| `1` | Config / auth / fatal error |
| `2` | Partial failure (some stories failed) |

---

## Tests (developers)

From `manji-laravel/`:

```powershell
.\vendor\bin\pest.bat tests\Unit\Services\StoryPackagePrepareAndPreflightTest.php
.\vendor\bin\pest.bat tests\Unit\Services\OldStoriesImportCreateDbTest.php
.\vendor\bin\pest.bat tests\Feature\Admin\LocalImportOldStoriesApiTest.php
.\vendor\bin\pest.bat tests\Feature\Admin\LocalImportAccessApiTest.php
```

---

## فارسی (خلاصه)

1. از پوشه `manji-laravel` اجرا کنید.
2. دستور پیشنهادی:

```powershell
.\scripts\agent-upload-stories.ps1 -Stories "21","22" -JsonSummary
```

3. بعد از آپلود، تصاویر بسته، صوت، تایم‌لاین و انتشار را در داشبورد کامل کنید.
4. برای اطمینان از وجود داستان روی سرور:

```powershell
.\scripts\verify-story-on-server.ps1 -Stories "21","22" -JsonSummary
```
