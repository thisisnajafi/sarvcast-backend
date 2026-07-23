# Local → Server Story & Episode Import

This document describes what is already built for uploading and managing stories/episodes from a local `manji-stories` folder to production, and what remains to finish the pipeline.

## Architecture (current)

```text
Local PC                              Production server
─────────                             ────────────────
manji-stories/                        STORY_EDITOR_STORIES_PATH
  21 - gulliver…/                       (storage/app/manji-stories)
  staging/21 - …/  ──zip+HTTPS──►     /api/admin/local-import/stories/import-old
       │                                │
       │                                ├─ unpack into story-editor filesystem
       │                                ├─ optional draft Story/Episode DB rows
       │                                └─ import JSON/MD into story_production_*
       │
manji-laravel/.env                    same .env uploaded by CI/FTP
  LOCAL_IMPORT_API_BASE_URL
  LOCAL_IMPORT_API_TOKEN
  LOCAL_IMPORT_BOOTSTRAP_SECRET
  STORY_EDITOR_STORIES_PATH
```

Admin dashboard (`admin.manjiapp.ir`) then manages package images, audio, timelines, and publish status.

Flutter / public API read **database** rows (`stories`, `episodes`, `characters`), not the raw writer folders.

---

## What we have done (done)

### 1. Local story source of truth
- Writer folders live in `manji-stories/` (scripts `.md`, `characters_and_objects.json`, `*_image_prompts.json`).
- Staging packages under `manji-stories/staging/` use `import_manifest.json`.

### 2. Server story-editor filesystem API
- Laravel story-editor reads/writes markdown under `STORY_EDITOR_STORIES_PATH`.
- Dashboard routes: `/story-editor`, `/story-editor/{slug}`, `/story-editor/{slug}/package`.

### 3. Production import into DB tables
- `StoryProductionImportService` imports:
  - `characters_and_objects.json` → `story_production_assets` (+ sync `characters` rows)
  - `*_image_prompts.json` → scene/cover production assets
  - `*_story.md` → episode script / scenes
- Character **images** assigned in the package UI sync to `characters.image_url` (dashboard + Flutter).
- Repair command: `php artisan stories:sync-production-character-images {slug?}`

### 4. Local → server remote upload (Phase C)
- Sanctum-protected API:
  - `POST /api/admin/local-import/bootstrap` (bootstrap secret)
  - `GET  /api/admin/local-import/verify`
  - `POST /api/admin/local-import/stories/import-old` (zip package)
- CLI: `php artisan stories:import-old --remote --create-db --force --only=…`
- Token helper: `php artisan admin:create-local-import-token`

### 5. Local helper scripts (this repo)
| Script | Purpose |
|--------|---------|
| `scripts/prepare-story-package.ps1` | Turn a raw writer folder into a staging package + `import_manifest.json` |
| `scripts/upload-story-to-server.ps1` | Prepare (optional) + remote import with `--create-db` |

### 6. Production `.env` keys (deployed with backend)
- `STORY_EDITOR_STORIES_PATH`
- `LOCAL_IMPORT_BOOTSTRAP_SECRET`
- `LOCAL_IMPORT_API_BASE_URL`
- `LOCAL_IMPORT_API_TOKEN`

---

## How to upload one story (operator recipe)

From `manji-laravel/` on your PC (with network access to `my.manjiapp.ir`):

```powershell
# 1) Prepare + upload (creates DB draft story/episodes + imports JSON/MD)
.\scripts\upload-story-to-server.ps1 `
  -StoryFolder "..\manji-stories\21 - gulliver in lilliput" `
  -ForcePrepare

# Dry run first (optional):
.\scripts\upload-story-to-server.ps1 `
  -StoryFolder "..\manji-stories\21 - gulliver in lilliput" `
  -ForcePrepare -DryRun
```

Or manually:

```powershell
.\scripts\prepare-story-package.ps1 -StoryFolder "..\manji-stories\86 - happiness toolbox" -Force
php artisan stories:import-old --remote --create-db --force --only="86 - happiness toolbox"
```

Then in the dashboard:

1. Open **ویرایشگر داستان → بسته** for that story slug.
2. Assign character / scene / cover images if not already set.
3. Open **داستان‌ها** admin: set cover, category, premium flags, audio, image timelines.
4. Publish story + episodes.

---

## What we still need (to fulfill completely)

These gaps are not fully automated yet:

### A. Richer DB scaffolding from local
- [ ] Map category / age group / tags from JSON reliably on `--create-db`
- [ ] Set `story_editor_slug` / production workflow status consistently after remote import
- [ ] Create empty timeline slots from scene prompts automatically (today: prompts import assets; timelines/audio still manual)

### B. Media pipeline
- [ ] Batch upload generated scene images from local disk → production assets (today: assign in package UI / media library)
- [ ] Batch upload episode audio files from local → episode `audio_url`
- [ ] One-command “full publish prep” (images + audio + timelines)

### C. Writer folder completeness checks
- [ ] Preflight script that fails if `characters_and_objects.json`, script `.md`, or prompts JSON are missing
- [ ] Validate speaker IDs in `.md` against character keys in JSON

### D. Auth / ops hardening
- [ ] Token rotation runbook (bootstrap secret → new Sanctum token → update `.env` → redeploy)
- [ ] Prefer keeping secrets in GitHub `PRODUCTION_DOTENV` / host `.env` only; avoid long-lived tokens in chat logs
- [ ] Rate limits / audit log review for `local-import` endpoints in production

### E. Dashboard UX
- [ ] “Import from local package” button that uses the same remote API (optional; CLI works today)
- [ ] Clear badge when production assets have images but `characters.image_url` was empty (sync now auto-heals on package/characters/story show)

### F. End-to-end acceptance checklist (per story)
- [ ] Staging package prepared
- [ ] Remote import success (filesystem + production tables)
- [ ] Characters visible in dashboard with images
- [ ] Episodes have script scenes in story-editor
- [ ] Audio + image timelines attached
- [ ] Published and visible in Flutter

---

## Related code

| Area | Path |
|------|------|
| Remote client | `app/Services/OldStoriesRemoteImportClient.php` |
| Import service | `app/Services/OldStoriesImportService.php` |
| Production import | `app/Services/StoryProductionImportService.php` |
| API routes | `routes/api.php` (`admin/local-import`, `story-editor`) |
| Artisan | `stories:import-old`, `admin:create-local-import-token`, `stories:sync-production-character-images` |
| Scripts | `scripts/prepare-story-package.ps1`, `scripts/upload-story-to-server.ps1` |

---

## فارسی (خلاصه)

**انجام‌شده:** نوشتن در `manji-stories`، ساخت پکیج staging، آپلود ریموت با توکن به سرور، import اسکریپت/JSON شخصیت‌ها و پرامپت صحنه، همگام‌سازی تصویر شخصیت با دیتابیس، مدیریت در داشبورد.

**باقی‌مانده:** آپلود دسته‌ای تصویر صحنه و صوت، ساخت خودکار تایم‌لاین، چک کامل بودن فایل‌های نویسنده، چرخش امن توکن، و چک‌لیست انتشار کامل تا اپ Flutter.
