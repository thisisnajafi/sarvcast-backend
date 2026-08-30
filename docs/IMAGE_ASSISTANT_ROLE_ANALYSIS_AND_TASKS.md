# Image Assistant Role — Analysis & Task Plan

**Feature:** Let selected users help create images (covers, characters, timelines) by viewing story/episode prompts and managing episode image timelines — **only for stories an admin assigns to them** — inside `https://admin.manjiapp.ir`.

**Status:** Implementation in progress (Phase 1–4 landed locally) · Deploy seeder + migrations before production use  
**Date:** 2026-08-30  
**Related systems:** Custom RBAC · `ContributorStoryAccessService` · `story_production_assets` · `image_timelines` · Next dashboard authz

### Implemented (code)

- Role `image_assistant` + permissions `prompts.read`, `timeline.read`, `timeline.update`, `stories.assign_image_assistant`
- Table `story_image_assistants` + assignment APIs
- Scoped prompts API `GET /api/admin/stories/{id}/production-assets`
- Timeline access scoped for assistants; middleware allowlist
- Dashboard: authz flags, sidebar, assign panel, prompts panel, timeline link
- Feature tests: `tests/Feature/Admin/ImageAssistantAccessApiTest.php` (7 passing)

### Deploy checklist

```bash
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
```

Then deploy `next-dashboard` static export.

---

## 1. Product intent (what we are building)

| Need | Meaning |
|------|---------|
| New collaborator type | Users who create / support **images** (cover, characters, scene frames), not writers or voice actors |
| See prompts | Read prompts for covers, characters, objects, settings, and episode scenes |
| Story-scoped grant | Admin assigns a user to **one specific story** (or several); they only see that story’s prompts |
| Dashboard visibility | Assigned stories appear in their panel on `admin.manjiapp.ir` |
| Timeline management | For episodes of assigned stories, they can manage the **image timeline** (frames synced to audio) |

### Suggested Persian labels

| English (code) | Persian (UI) |
|----------------|--------------|
| Role: `image_assistant` | دستیار تصویر / تصویرساز |
| Assign to story | اختصاص دستیار تصویر به داستان |
| My assigned stories | داستان‌های تصویری من |
| Prompts | پرامپت‌ها |
| Episode timeline | تایم‌لاین قسمت |

---

## 2. Current state (what already exists)

### 2.1 Prompts

| Layer | Reality |
|-------|---------|
| Storage | `story_production_assets.prompt` (+ `metadata` JSON); types: `character`, `object`, `setting`, `scene`, `cover` |
| Files | `characters_and_objects.json`, `*_image_prompts.json` under story-editor package |
| Admin UI | Story production panels, episode scene prompt cards, story-editor package/assets |
| Contributor access | **Blocked** — package/assets are admin-only; `ApiContributorGuardMiddleware` denies non-admin `/assets`, `/package`, `/import` |

### 2.2 Image timeline

| Layer | Reality |
|-------|---------|
| Meaning | Ordered frames on episode audio: `start_time`, `end_time`, `image_url`, transitions, key frames |
| Table | `image_timelines` |
| Admin API | `/api/admin/timeline-management` (+ sync-from-scenes, batch-update, upload-image) |
| Dashboard | `/timeline-management`, `/episodes/[id]/timeline`, `/stories/[id]/timeline` |
| Contributor access | **Blocked** — sidebar is full-admin only; middleware blocks `timeline-management` for contributors |

### 2.3 Existing story-level assignment (pattern to reuse)

| Role | How story access is granted |
|------|-----------------------------|
| Writer | `stories.author_id` |
| Narrator | `stories.narrator_id` |
| Character VA | `characters.voice_actor_id` |
| Head writer | Sees all stories; can assign writers |
| **Image assistant** | **Does not exist** — no role, no pivot, no permissions |

Core files to extend (do not reinvent):

- `app/Services/ContributorStoryAccessService.php`
- `app/Http/Middleware/ApiContributorGuardMiddleware.php`
- `app/Http/Middleware/ApiAdminMiddleware.php` (panel login allowlist via access service)
- `database/seeders/RolePermissionSeeder.php`
- `next-dashboard/hooks/use-authz.ts`
- `next-dashboard/components/layout/sidebar-nav.tsx`
- Writer assign UX on story detail (`stories.assign_writer`) as UI reference for “assign image assistant”

### 2.4 Gaps (must build)

1. Role `image_assistant` + permission set  
2. Story ↔ user assignment store (many-to-many recommended)  
3. Admin APIs to assign / revoke / list assistants per story  
4. Object-level checks: prompts + timelines only for assigned stories  
5. Middleware allowlist for prompt read + timeline write paths  
6. Dashboard nav + pages for this role  
7. `auth/me` access payload flags + synthetic permissions  
8. Tests (Feature) mirroring `HeadWriterAccessApiTest` / contributor tests  

---

## 3. Proposed design

### 3.1 Role & global permissions

**New RBAC role:** `image_assistant`  
**Legacy `users.role`:** prefer `image_assistant` (add enum/constant) so OTP / panel login work like `voice_actor` / `writer`. Sync via `applyLegacyRoleChange` / role attach hooks like other staff roles.

| Permission name | Group | Purpose |
|-----------------|-------|---------|
| `dashboard.view` | `dashboard` | Enter panel home |
| `stories.read` | `stories` | List/view **assigned** stories only (enforced in service, not global read) |
| `stories.assign_image_assistant` | `stories` | **Admin/head?** Assign assistants to a story (admins only recommended) |
| `prompts.read` | `prompts` | View production prompts (cover / character / scene / …) for assigned stories |
| `prompts.copy` *(optional)* | `prompts` | Explicit “copy prompt” if you want audit/analytics later — can skip v1 |
| `timeline.read` | `timeline` | View episode timelines for assigned stories |
| `timeline.update` | `timeline` | Create/update/delete/reorder frames, upload frame images, batch-update for assigned episodes |
| `timeline.sync_from_scenes` *(optional v1.1)* | `timeline` | Rebuild frames from scene assets — powerful; decide if assistants may do this |

**Do not grant by default:** `story_editor.update`, package import, media library global, users, roles, payments, SMS, analytics export.

**Role matrix (seed):**

| Role | Gets |
|------|------|
| `image_assistant` | `dashboard.view`, `stories.read`, `prompts.read`, `timeline.read`, `timeline.update` |
| `admin` / `super_admin` | All of the above + `stories.assign_image_assistant` |
| `writer` / `voice_actor` / `head_writer` | Unchanged (no prompt package / timeline unless also assigned as image assistant) |

### 3.2 Story-scoped assignment (object level)

**Recommended table:** `story_image_assistants`

| Column | Notes |
|--------|-------|
| `id` | PK |
| `story_id` | FK → `stories`, cascade delete |
| `user_id` | FK → `users`, cascade delete |
| `assigned_by` | nullable FK → `users` |
| `notes` | nullable text |
| `created_at` / `updated_at` | timestamps |
| Unique | `(story_id, user_id)` |

**Why not a single `stories.image_assistant_id`?** Multiple artists may work on one story (cover vs characters vs timelines).

**Model:** `StoryImageAssistant` + `Story::imageAssistants()` / `User::assistedStories()`.

### 3.3 Access rules (backend)

Extend `ContributorStoryAccessService`:

| Method / flag | Behavior for image assistant |
|---------------|------------------------------|
| `mayAccessAdminPanel` / `mayReceiveAdminOtp` | Include `image_assistant` |
| `isImageAssistant` | Has role or has any row in `story_image_assistants` |
| `isContributor` | Treat as contributor variant (restricted panel) |
| `canViewStory` | True if assigned on that story (or full admin) |
| `scopeStoriesForUser` | OR `whereHas('imageAssistants', user_id)` |
| `canViewPrompts(User, Story)` | Assigned (or admin) |
| `canManageTimeline(User, Story\|Episode)` | Assigned to parent story (or admin) |
| `canAccessPackage` | Still **false** for assistants (unless you later grant read-only assets API) |
| `accessPayload()` | Add: `is_image_assistant`, `can_view_prompts`, `can_manage_timeline`, `can_assign_image_assistants` |
| `contributorPermissions()` | Inject `prompts.read`, `timeline.read`, `timeline.update`, `stories.read`, `dashboard.view` when applicable |

**Prompt data access path (choose one for v1 — recommended A):**

| Option | Description |
|--------|-------------|
| **A (recommended)** | New read-only admin API: `/api/admin/stories/{id}/production-assets` (and episode-scoped scene list) that returns prompts + image URLs **without** full package/import. Guard with `canViewPrompts`. |
| B | Relax story-editor `/assets` GET for assigned stories only; keep POST/import admin-only. More coupling to editor. |

**Timeline path:** Allow `timeline-management` for image assistants **only** when the target episode/story is assigned; list endpoints must scope to assigned story IDs. Prefer also using `/episodes/{id}/timeline` editor API with the same check.

### 3.4 Middleware

Update `ApiContributorGuardMiddleware`:

1. Detect image-assistant contributor.  
2. Allow segments: `stories` (GET only, no export/bulk), **prompt assets route(s)**, `timeline-management` (GET + mutating methods but object-scoped in controller/service), and existing `resumes` if needed (probably N/A).  
3. Deny: users, roles, payments, story-editor write, package import, local-import, etc.  
4. For `timeline-management` and story show: after allowlist, controllers must call `canManageTimeline` / `canViewPrompts` — middleware alone is not enough.

Update `ApiAdminPermissionMiddleware` mapping if new segments (`prompts` or nested paths) need `{resource}.{action}` names that match the seeder.

### 3.5 Dashboard UX (admin.manjiapp.ir)

#### For image assistant (restricted nav)

Suggested sidebar section **«کار تصویری من»**:

| Item | Route | Notes |
|------|-------|-------|
| داستان‌های من | `/stories` | Only assigned stories (API already scoped) |
| پرامپت‌ها | `/my-prompts` **or** story detail tab | List assigned stories → open prompts |
| تایم‌لاین | From story → `/stories/{id}/timeline` → episode editor | Hide global `/timeline-management` list **or** show scoped list only |

**Story detail (assigned):**

- Show production prompts (cover, characters, objects, settings) — **read-only** in v1  
- Episodes list → each episode: scene prompts (read-only) + link **مدیریت تایم‌لاین**  
- Hide: publish, delete, assign writer, package import, script edit (unless they also have writer role)

#### For admin / super_admin

On story detail (next to writer assignment):

- Panel **«دستیاران تصویر»**: search user → assign / revoke  
- Requires `stories.assign_image_assistant`  
- Optional: users list filter “image assistants”; show `assigned_stories` count  

#### Auth / login

- OTP admin login must accept `image_assistant` (`mayReceiveAdminOtp`)  
- Post-login: `resolvePostAuthPath` → `/dashboard` or `/stories`  
- `useAuthz`: new flags; sidebar branch like head_writer / contributor  

### 3.6 Capability boundaries (v1 recommendations)

| Capability | v1 | Later |
|------------|----|-------|
| View prompts (all asset types) | ✅ | |
| Copy prompt to clipboard (UI only) | ✅ | |
| Edit / overwrite prompts | ❌ | Optional |
| Upload / replace production asset images (character/cover/scene) | ❌ or optional | Strongly useful for artists |
| Manage timeline frames (CRUD, times, upload frame image) | ✅ | |
| Sync timeline from scenes | ❓ decide | Safer as admin-only first |
| See unassigned stories | ❌ | |
| Edit scripts / story-editor write | ❌ | |
| Assign other assistants | ❌ (admin only) | |

---

## 4. Open decisions (confirm before coding)

1. **Role name:** `image_assistant` vs `visual_artist` vs Persian slug — pick one for DB + code.  
2. **Prompt edit:** read-only vs read+edit.  
3. **Image upload for assets:** only timeline frames, or also character/cover/scene `image_url` on production assets?  
4. **Who can assign:** only `super_admin`/`admin`, or also `head_writer`?  
5. **Users without staff role:** Can admin attach `image_assistant` role to an existing `parent` account, or must they be dedicated staff users? (Recommend: dedicated staff + role attach, same as writers.)  
6. **Global timeline page:** scoped list vs only deep-links from assigned stories.  
7. **Audit logging:** log assign/revoke + timeline mutations for assistants.

---

## 5. Task backlog

Priority tags: **P0** must-ship · **P1** polish · **P2** nice-to-have

---

### 5.1 Roles & permissions

| ID | Task | Priority | Notes |
|----|------|----------|-------|
| RP-01 | Add permissions to `RolePermissionSeeder`: `prompts.read`, `timeline.read`, `timeline.update`, `stories.assign_image_assistant` (+ optional sync/copy) | P0 | Idempotent `firstOrCreate` |
| RP-02 | Create RBAC role `image_assistant` and sync default permission IDs | P0 | |
| RP-03 | Attach new perms to `admin` / `super_admin` (super via `Permission::all()`) | P0 | |
| RP-04 | Add `User::ROLE_IMAGE_ASSISTANT = 'image_assistant'` and helpers `isImageAssistant()` | P0 | Migration if `users.role` is ENUM — alter enum |
| RP-05 | Ensure role attach updates legacy `users.role` / 2FA policy if required | P0 | Admins have 2FA; decide if assistants need 2FA (recommend yes if panel access) |
| RP-06 | Document new permissions in OpenAPI / internal role doc; fix outdated `ROLE_PERMISSION_SYSTEM_DOCUMENTATION.md` section | P1 | |
| RP-07 | Seeder command note / ops: `php artisan db:seed --class=RolePermissionSeeder` on production | P0 | Not in DatabaseSeeder today |

---

### 5.2 Backend (Laravel)

| ID | Task | Priority | Files / area |
|----|------|----------|--------------|
| BE-01 | Migration `story_image_assistants` + model + relations on `Story` / `User` | P0 | |
| BE-02 | Service methods: assign, revoke, list assistants, `isAssignedToStory` | P0 | New service or extend `ContributorStoryAccessService` / `StoryWriterAssignmentService` pattern |
| BE-03 | Extend `ContributorStoryAccessService` (panel login, contributor detection, `canViewStory`, `scopeStoriesForUser`, prompt/timeline helpers, `accessPayload`, `contributorPermissions`) | P0 | |
| BE-04 | Admin API: `GET/POST/DELETE /api/admin/stories/{story}/image-assistants` (or `.../image-assistants/{user}`) | P0 | Permission: `stories.assign_image_assistant` |
| BE-05 | Story show/list payloads: include `image_assistants[]`, `can_assign_image_assistant` | P0 | Mirror writer fields |
| BE-06 | Read-only production prompts API for assigned stories (story-level + episode scenes) | P0 | Option A in §3.3 |
| BE-07 | Scope `TimelineManagementController` (and episode timeline endpoints used by editor) with `canManageTimeline` | P0 | List must not leak other stories |
| BE-08 | Update `ApiContributorGuardMiddleware` allowlist for image assistants | P0 | |
| BE-09 | Wire `api.permission` names for timeline/prompts if not bypassed for contributors | P0 | Contributors currently skip some permission middleware — keep object checks authoritative |
| BE-10 | `AuthController` admin `me` response: new access flags + permission names | P0 | |
| BE-11 | Reject assigning non-eligible users (must have role or auto-attach role on assign) | P0 | Product decision §4.5 |
| BE-12 | Activity / audit log for assign/revoke and timeline writes by assistants | P1 | |
| BE-13 | Optional: allow GET story-editor assets for assigned stories only | P2 | If not using dedicated prompts API |
| BE-14 | Optional: upload/update production asset images for assigned stories | P2 | |
| BE-15 | Feature tests: assign, scope list, prompt 403/200, timeline 403/200, middleware deny other segments | P0 | Mirror `HeadWriterAccessApiTest` |
| BE-16 | Migration + seeder deploy notes for FTP / GitHub Actions | P0 | |

---

### 5.3 Dashboard (Next.js — `next-dashboard`)

| ID | Task | Priority | Files / area |
|----|------|----------|--------------|
| FE-01 | Types: role enum + `image_assistants` on story; authz access flags | P0 | `types/user.ts`, `types/story.ts`, `hooks/use-authz.ts` |
| FE-02 | Sidebar branch for image assistant (prompts + assigned stories + timeline entry points) | P0 | `sidebar-nav.tsx` |
| FE-03 | Story detail: **Image assistants** assign/revoke panel (admin) | P0 | Like writer assignment UI |
| FE-04 | Story detail / new page: **Prompts viewer** (read-only, grouped by type) | P0 | Reuse `PromptBlock` / production panels in read-only mode |
| FE-05 | Episode prompts viewer for assigned episodes | P0 | Reuse episode production prompt cards |
| FE-06 | Enable `/stories/[id]/timeline` + `/episodes/[id]/timeline` for assistants; hide forbidden actions | P0 | `PermissionGate` / access flags |
| FE-07 | Hide global admin-only nav; hide script edit, package, publish, bulk, export | P0 | |
| FE-08 | Dashboard home card for assistants (“N stories assigned”, links) | P1 | Similar to `writing-home.tsx` |
| FE-09 | Users form: allow selecting role `image_assistant` + show assigned stories | P1 | `user-form.tsx` |
| FE-10 | Roles UI: new permissions appear in groups automatically from API | P1 | If permissions seeded |
| FE-11 | Empty states: no assignments yet | P1 | |
| FE-12 | Copy-prompt buttons + toast | P1 | |
| FE-13 | Align `lib/permissions.ts` / `FormAccessGuard` for timeline module names | P0 | `timeline.update` etc. |
| FE-14 | Manual QA checklist on static export build | P0 | |

---

### 5.4 Cross-cutting / QA

| ID | Task | Priority |
|----|------|----------|
| QA-01 | Create test user → assign to one story → verify only that story in list | P0 |
| QA-02 | Verify prompts visible; second story prompts 403/hidden | P0 |
| QA-03 | Edit timeline on assigned episode; attempt on other episode fails | P0 |
| QA-04 | Confirm writer/VA unchanged | P0 |
| QA-05 | Confirm admin still has full timeline + prompts | P0 |
| QA-06 | OTP login as image assistant works; parent/child still cannot | P0 |
| QA-07 | Mobile/narrow layout of prompts + timeline editor | P1 |

---

## 6. Suggested implementation order

```
Phase 1 — Foundation
  RP-01..05 → BE-01..03 → BE-10 → BE-08

Phase 2 — Admin assignment
  BE-04..05 → FE-01 → FE-03 → QA assign flow

Phase 3 — Prompts for assistants
  BE-06 → FE-02 → FE-04..05 → QA-02

Phase 4 — Timeline for assistants
  BE-07 → FE-06..07 → FE-13 → QA-03

Phase 5 — Harden
  BE-15 → QA-01..06 → RP-06..07 → deploy seeder + migration
```

---

## 7. Out of scope (unless requested later)

- AI image generation inside the dashboard  
- CafeBazaar / Flutter app changes  
- Public API exposure of prompts  
- Automatic assignment from workflow status  
- Blade Laravel admin UI parity (Next dashboard is canonical for this feature)

---

## 8. Summary

We need a **new staff role** with **story-scoped assignment**, **read access to production prompts**, and **write access to episode image timelines**, built on the same contributor patterns as writers/voice actors — but with a **dedicated pivot table** and **explicit prompt/timeline permissions**, because those surfaces are currently admin-only.

---

## فارسی — خلاصه تحلیل و کارها

### هدف
کاربرانی که در ساخت تصویر (کاور، شخصیت، تایم‌لاین) کمک می‌کنند باید در داشبورد آنلاین فقط داستان‌هایی را ببینند که ادمین به آن‌ها اختصاص داده؛ پرامپت‌های همان داستان/قسمت را بخوانند و تایم‌لاین تصویری هر قسمت را مدیریت کنند.

### وضع فعلی
- پرامپت‌ها در `story_production_assets` هستند ولی برای غیر‌ادمین بسته است.  
- تایم‌لاین در `image_timelines` است و فقط ادمین به `/timeline-management` دسترسی دارد.  
- اختصاص داستان فقط برای نویسنده / راوی / صداپیشه شخصیت وجود دارد — **دستیار تصویر وجود ندارد.**

### طراحی پیشنهادی
- نقش: `image_assistant`  
- مجوزها: `prompts.read`, `timeline.read`, `timeline.update`, `stories.assign_image_assistant`, …  
- جدول: `story_image_assistants` (چند دستیار برای یک داستان)  
- گسترش `ContributorStoryAccessService` + middleware + سایدبار محدود داشبورد  
- API فقط‌خواندنی برای پرامپت‌ها + محدودسازی API تایم‌لاین به داستان‌های اختصاص‌یافته  

### کارهای اصلی
1. **نقش و مجوز** — سیدر، ثابت نقش، ENUM کاربر  
2. **بک‌اند** — مایگریشن، assign/revoke، دسترسی شیءمحور، API پرامپت و تایم‌لاین، تست  
3. **داشبورد** — منوی دستیار، پنل اختصاص ادمین، صفحه پرامپت، فعال‌سازی ادیتور تایم‌لاین  
4. **QA و دیپلوی** — سیدر روی پروداکشن، سناریوهای دسترسی  

قبل از کدنویسی باید تصمیم‌های بخش ۴ (نام نقش، ویرایش پرامپت، آپلود تصویر دارایی، چه کسی assign کند) تأیید شوند.
