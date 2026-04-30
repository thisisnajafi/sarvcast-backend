# Phase 1 - Route Audit and Contract Freeze

## Status

Completed.

## Deliverables

- Route snapshots exported from live Laravel routing:
  - `docs/next-dashboard/route-list-all.txt`
  - `docs/next-dashboard/route-list-admin-filter.txt`
- Admin-focused route inventory baseline created from route snapshot and `routes/web.php` + `routes/api.php`.
- Canonical admin API namespace proposed and frozen for migration work.
- Route migration classification model defined (Keep, Later, Merge, Deprecate).

## Source of Truth

- `routes/web.php`
- `routes/api.php`
- `bootstrap/app.php` middleware aliases
- `docs/next-dashboard/route-list-admin-filter.txt` (`php artisan route:list --path=admin --except-vendor`)

## Canonical Namespace Decision (Frozen)

For new Next.js dashboard integration, backend admin APIs will converge to:

- `/api/admin/v1/auth/*`
- `/api/admin/v1/dashboard/*`
- `/api/admin/v1/{resource}/*`

Where existing endpoints currently exist under mixed namespaces (`/admin/api/*`, `/api/admin/*`, `/api/v1/admin-panel/*`), they will be normalized behind this canonical namespace using compatibility aliases during migration.

## Route Classification Model (Frozen)

Each route must be tagged with one of:

- `Keep` - required for phase 1 launch
- `Later` - phase 2 or phase 3
- `Merge` - overlapping with another route shape and should be consolidated
- `Deprecate` - no longer needed in Next dashboard flow

## Initial Module Classification

### Keep (Phase 1)

- Admin auth and session:
  - `admin/auth/*`, `admin/2fa/*`
- Core dashboard:
  - `admin`, `admin/dashboard/export`, dashboard stats APIs
- Content core:
  - `admin/stories/*`
  - `admin/episodes/*`
  - story/episode timeline endpoints used by content editors
- Taxonomy:
  - `admin/categories/*`
- User operations:
  - `admin/users/*` (+ activity/profile/search/change-role/suspend/activate)
- Moderation:
  - `admin/comments/*`
  - `admin/moderation/*`
- Profile:
  - `admin/profile/*`

### Later (Phase 2+)

- Billing/business modules:
  - subscriptions, plans, subscription-plans
  - coupons, coins, commission-payments, payments/refund
- Extended management:
  - people, voice actors, notifications
  - audio/file upload
- Program modules:
  - affiliate, teachers, influencers, schools, corporate
- Analytics suites:
  - user/content/revenue/system analytics
- Ops modules:
  - backup, performance, versions/app-versions

### Merge (Normalization Candidates)

- Overlapping route namespaces for admin APIs:
  - `/admin/api/*`
  - `/api/admin/*`
  - `/api/v1/admin-panel/*`
- Duplicate management surfaces:
  - `notifications` and `notifications-management`
  - `timelines` and `timeline-management`
- Repeated API CRUD style for same domains in both web-admin and api-admin groups.

### Deprecate (After Parity Validation)

- Blade-specific route dependencies that become UI-only concerns once Next is the dashboard UI.
- Legacy/duplicated endpoints kept only for temporary compatibility.

## Response Contract Freeze (Phase 1 Scope)

All phase 1 endpoints should align to:

- Pagination:
  - `page`, `perPage`, `total`, `lastPage`
- Sorting:
  - `sortBy`, `sortDir`
- Filtering:
  - `q`, `status`, `dateFrom`, `dateTo`, module-specific filters
- Standard response envelope:
  - `success`
  - `message`
  - `data`
  - optional `meta`, `errors`
- Standard error shape:
  - `success: false`
  - `message`
  - `error` code
  - optional `errors` object

## Compatibility Plan

- Keep old endpoints during migration behind feature flags.
- New Next dashboard consumes canonical `/api/admin/v1/*`.
- Add adapter controllers or route aliases where immediate backend refactor is risky.
- Remove aliases only after parity and rollout sign-off.

## Exit Criteria for Phase 1

- [x] Route snapshots exported
- [x] Canonical namespace decision documented
- [x] Classification model documented
- [x] Initial module classification completed
- [x] Contract envelope and query conventions defined
- [x] Compatibility strategy defined

