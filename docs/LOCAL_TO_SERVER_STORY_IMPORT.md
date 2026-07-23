# Local → Server Story & Episode Import

This document describes what is already built for uploading and managing stories/episodes from a local `manji-stories` folder to production, what an **AI agent** should run, and what remains to finish the pipeline.

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

## Tell an AI agent to post stories (recommended)

1. Open a new agent chat in this repo.
2. Paste the full prompt from:
   - [`docs/AGENT_STORY_UPLOAD_PROMPT.md`](AGENT_STORY_UPLOAD_PROMPT.md)
3. Then say which stories to upload, for example:
   - `Upload stories 21 and 22 to the server`
   - `Upload "22 - tiddalik the thirsty frog"`

The agent should run (from `manji-laravel/`):

```powershell
.\scripts\agent-upload-stories.ps1 -Stories "21","22" -JsonSummary
```

That single command:

1. Finds folders under `../manji-stories`
2. Preflights required files
3. Builds staging packages (`prepare-story-package.ps1`)
4. Posts them with `php artisan stories:import-old --remote --create-db --force`

### Agent script reference

| Script | Who uses it | Purpose |
|--------|-------------|---------|
| `scripts/agent-upload-stories.ps1` | **AI agents / batch ops** | Resolve stories → preflight → prepare → remote upload; exit codes + optional JSON summary |
| `scripts/prepare-story-package.ps1` | Agent / human | Build `staging/<folder>/import_manifest.json` |
| `scripts/upload-story-to-server.ps1` | Human (single story) | Same pipeline for one `-StoryFolder` / `-StagingFolder` |

### Exit codes (`agent-upload-stories.ps1`)

| Code | Meaning |
|------|---------|
| `0` | All requested stories succeeded |
| `1` | Config / usage / fatal error |
| `2` | One or more stories failed (others may have uploaded) |

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
| `scripts/agent-upload-stories.ps1` | Agent-friendly multi-story uploader (preflight + prepare + remote) |
| `scripts/prepare-story-package.ps1` | Turn a raw writer folder into a staging package + `import_manifest.json` |
| `scripts/upload-story-to-server.ps1` | Prepare (optional) + remote import for one story |

### 6. Production `.env` keys (deployed with backend)
- `STORY_EDITOR_STORIES_PATH`
- `LOCAL_IMPORT_BOOTSTRAP_SECRET`
- `LOCAL_IMPORT_API_BASE_URL`
- `LOCAL_IMPORT_API_TOKEN`

### 7. Agent documentation
- [`AGENT_STORY_UPLOAD_PROMPT.md`](AGENT_STORY_UPLOAD_PROMPT.md) — copy/paste instructions for Cursor/other AI agents

---

## How to upload one story (human recipe)

From `manji-laravel/` on your PC (with network access to `my.manjiapp.ir`):

```powershell
# Preferred (same path agents use):
.\scripts\agent-upload-stories.ps1 -Stories "21" -JsonSummary

# Or single-folder helper:
.\scripts\upload-story-to-server.ps1 `
  -StoryFolder "..\manji-stories\21 - gulliver in lilliput" `
  -ForcePrepare

# Dry run:
.\scripts\agent-upload-stories.ps1 -Stories "21" -DryRun -JsonSummary
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
- [x] Preflight in `agent-upload-stories.ps1` (characters JSON, episode `.md`, prompts JSON)
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
| Scripts | `scripts/agent-upload-stories.ps1`, `scripts/prepare-story-package.ps1`, `scripts/upload-story-to-server.ps1` |
| Agent prompt | `docs/AGENT_STORY_UPLOAD_PROMPT.md` |

---

## فارسی (خلاصه)

**برای سپردن به ایجنت AI:** محتوای `docs/AGENT_STORY_UPLOAD_PROMPT.md` را در چت ایجنت پیست کنید و بگویید کدام داستان‌ها آپلود شوند. ایجنت باید `.\scripts\agent-upload-stories.ps1 -Stories "…" -JsonSummary` را از پوشه `manji-laravel` اجرا کند.

**انجام‌شده:** نوشتن در `manji-stories`، اسکریپت ایجنت/آپلود، پکیج staging، آپلود ریموت، import اسکریپت/JSON شخصیت‌ها و پرامپت صحنه، همگام‌سازی تصویر شخصیت.

**باقی‌مانده:** آپلود دسته‌ای تصویر صحنه و صوت، ساخت خودکار تایم‌لاین، اعتبارسنجی گوینده‌ها، چرخش امن توکن، و چک‌لیست انتشار کامل تا اپ Flutter.
