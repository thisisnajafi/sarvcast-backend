# Phase 2 - Auth, Security, and Access Spec

## Status

Completed for backend baseline (auth/role/permission/origin/rate-limit/audit).

## Objective

Define a single secure auth and authorization model for the separate Next.js dashboard while Laravel remains backend authority.

## Current Behavior (Observed)

- Blade admin routes use `auth:web` + `admin` middleware.
- API routes use `auth:sanctum` + `role` checks in multiple areas.
- Middleware aliases are configured in `bootstrap/app.php`:
  - `admin`, `role`, `permission`, `2fa`, `security`, `cache.api`, `premium.access`, `content.access`.

## Target Auth Flow (Frozen)

Use token-based admin auth for Next dashboard with optional 2FA challenge:

1. `POST /api/admin/v1/auth/send-otp`
2. `POST /api/admin/v1/auth/login`
3. `GET /api/admin/v1/auth/me`
4. `POST /api/admin/v1/auth/logout`
5. `POST /api/admin/v1/auth/refresh` (optional if token rotation policy requires)
6. `POST /api/admin/v1/auth/2fa/verify` (only when required)

## RBAC Baseline (Frozen)

### Roles

- `super_admin`
- `admin`

### Permission Naming Convention

- `resource.action`
- Examples:
  - `stories.read`, `stories.write`, `stories.publish`
  - `users.read`, `users.write`, `users.change_role`
  - `payments.read`, `payments.refund`
  - `backup.restore`, `roles.manage`

## Access Matrix (Phase 1 Core)

- Dashboard
  - `admin`, `super_admin`
- Stories/Episodes/Categories
  - read/write for `admin`, `super_admin`
- Users
  - read/write for `admin`, `super_admin`
  - high-risk role changes can be `super_admin` only (policy decision)
- Moderation (comments/content)
  - read/review for `admin`, `super_admin`
- Profile
  - own profile for `admin`, `super_admin`

## Sensitive Operations (Super Admin Candidate List)

- Roles create/update/delete
- Backup restore/delete
- Potentially refunds above threshold
- System-level toggles (version set-latest, cache/global operations)

## API Security Requirements

- All `/api/admin/v1/*` endpoints must include:
  - auth middleware
  - role middleware
  - permission middleware for write/sensitive routes
- Add route-level rate limits:
  - strict on auth endpoints
  - moderate on bulk/export
- Enforce audit logging for:
  - create/update/delete
  - publish/unpublish
  - role and permission changes
  - suspend/activate/refund
- Enforce CORS allowlist to admin frontend origin only.

## Next.js Guarding Requirements

- Route-level guard in `middleware.ts` for authenticated admin users.
- Server-side guard for protected layouts/pages.
- UI-level component guard for action buttons and destructive controls.
- Standard unauthorized UX (`/401`, `/403`) with safe navigation options.

## Exit Criteria for Phase 2

- [x] Auth flow contract defined
- [x] RBAC role/permission conventions defined
- [x] Core access matrix defined
- [x] Sensitive operation policy draft defined
- [x] Laravel middleware baseline completed for admin API auth/role/origin/audit/rate-limit
- [x] Laravel permission middleware rollout completed for all sensitive API actions
- [x] Next.js guard implementation completed (proxy-based auth gate baseline)
- [ ] End-to-end auth + permission tests completed

