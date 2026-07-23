# AI Agent Prompt — Upload Manji Stories to Production Server

Copy everything below the line into a Cursor / ChatGPT / Claude agent chat when you want stories posted to the server.

---

## Mission

You are an automation agent for the Manji project. Your job is to **prepare and upload** local story packages from `manji-stories/` to production (`https://my.manjiapp.ir`) using the existing PowerShell + Artisan pipeline. Do **not** invent new APIs. Do **not** ask the user for tokens if `manji-laravel/.env` already has `LOCAL_IMPORT_API_*`.

## Workspace roots

- Backend / scripts: `manji-laravel/`
- Stories: `manji-stories/`
- Doc: `manji-laravel/docs/LOCAL_TO_SERVER_STORY_IMPORT.md`

## Mandatory steps (in order)

1. Confirm credentials exist (do not print the token value):
   ```powershell
   cd manji-laravel
   Select-String -Path .env -Pattern '^LOCAL_IMPORT_API_(BASE_URL|TOKEN)='
   ```
   If missing, stop and tell the user to set them.

2. Identify which stories to upload from the user message (folder name or numeric id, e.g. `22`, `21 - gulliver in lilliput`).

3. Optional dry-run first if the user is unsure:
   ```powershell
   cd manji-laravel
   .\scripts\agent-upload-stories.ps1 -Stories "22" -DryRun -JsonSummary
   ```

4. Upload for real:
   ```powershell
   cd manji-laravel
   .\scripts\agent-upload-stories.ps1 -Stories "22","21" -JsonSummary
   ```

5. Report results from the JSON summary / console:
   - `uploaded` / `dry_run_ok` / `failed`
   - preflight errors (missing `characters_and_objects.json`, `.md`, or `*_image_prompts.json`)
   - artisan errors

6. Tell the user the **manual dashboard follow-up** still required:
   - Story editor → package → assign character/scene images (if not already)
   - Stories admin → cover, audio, timelines, publish

## What the script already does on the server

For each story it will:

1. Build `manji-stories/staging/<folder>/import_manifest.json`
2. Zip + `POST /api/admin/local-import/stories/import-old`
3. Write files under server `STORY_EDITOR_STORIES_PATH`
4. Create draft `stories` / `episodes` (`--create-db`)
5. Import:
   - `characters_and_objects.json`
   - episode `*_story.md`
   - episode `*_image_prompts.json`

## Hard rules

- Work from `manji-laravel` as cwd for scripts.
- Use `-LiteralPath` / quoted paths (Persian folder names).
- Never commit or echo `LOCAL_IMPORT_API_TOKEN`.
- Never run destructive git commands.
- If preflight fails, fix or report missing files — do not skip preflight unless the user explicitly says so (`-SkipPreflight`).
- Exit code `2` from the script means partial failure; keep uploading remaining stories only if the user asked for a batch and failures are per-story.

## Example user asks → commands

| User says | Agent runs |
|-----------|------------|
| “Upload story 22 to server” | `.\scripts\agent-upload-stories.ps1 -Stories "22" -JsonSummary` |
| “Upload gulliver and tiddalik” | `.\scripts\agent-upload-stories.ps1 -Stories "21","22" -JsonSummary` |
| “Only prepare, don’t upload” | `.\scripts\prepare-story-package.ps1 -StoryFolder "..\manji-stories\22 - tiddalik the thirsty frog" -Force` |
| “Dry run first” | add `-DryRun` |

## Success message template

```text
Uploaded: <folder names>
Server: files in story-editor + draft DB rows + production JSON/MD imported.
Still manual: images (if needed), audio, timelines, publish in admin dashboard.
```
