# Phase 3 — Backend API Standardization (Dashboard)

## Status

Active. **Reference implementations:** `Admin\CoinController`, `Admin\CouponController`, `Admin\CommissionPaymentController`, `Admin\PaymentController`, `Admin\SubscriptionController`, `Admin\SubscriptionPlanController`, `Admin\AffiliateController`, `Admin\RoleController`, `Admin\UserController`, `Admin\CategoryController`, `Admin\EpisodeController`, `Admin\StoryController`, `Admin\PersonController`, `Admin\NotificationController`, `Admin\CommentModerationController`, `Admin\ContentModerationController`, `Admin\QuizController`, `Admin\ReferralController` + `App\Http\Support\AdminApiResponse`.

Other admin resources should adopt the same response envelope, query parameters, and route ordering (static segments like `/export` and `/statistics/data` before `/{id}`) incrementally.

## Canonical routes for Next dashboard

For Next dashboard integrations, canonical route forms are:

- Auth: `/api/admin/v1/auth/*`
- Dashboard: `/api/admin/v1/dashboard/*`
- Resource APIs consumed by dashboard modules: `/api/admin/*`

`/api/admin/v1/auth/*` and `/api/admin/v1/dashboard/*` are frozen canonical contracts and should not be duplicated under alternative namespace shapes.

## Deprecated/unsupported fallback patterns (removed)

The following fallback patterns are deprecated and intentionally unsupported for the Next dashboard:

- `/api/v1/admin/*`
- `/api/v1/auth/admin/*`
- `/api/admin/dashboard/*`
- `/api/v1/dashboard/*`

If old clients require these temporarily, they must be added as explicit compatibility aliases with a documented deprecation window; they should not be used by current Next dashboard code.

## Optional backward-compatibility aliases (Section F)

Legacy aliases are available only as an opt-in migration bridge:

- `ADMIN_ENABLE_LEGACY_API_ALIASES=true`
- `ADMIN_LEGACY_API_SUNSET_AT=2026-12-31T23:59:59Z` (example)
- `ADMIN_LEGACY_API_MIGRATION_DOC_URL=<docs-url>` (optional)

When enabled, these legacy namespace aliases are activated:

- `/api/v1/admin/auth/*`
- `/api/v1/auth/admin/*`
- `/api/admin/dashboard/*`
- `/api/v1/dashboard/*`

All legacy alias responses include deprecation headers via `legacy.api.deprecation` middleware:

- `Deprecation: true`
- `Sunset: <timestamp from ADMIN_LEGACY_API_SUNSET_AT>`
- `Warning: 299 - "Deprecated API namespace..."`
- `Link: <...>; rel="deprecation"` (when migration doc URL is configured)

### Deprecation timeline template

1. **T0 (announce):** enable aliases only if required by confirmed legacy clients.
2. **T0 + 1 week:** publish migration notice and canonical endpoint mapping.
3. **T0 + 4 weeks:** monitor alias traffic and block new consumers from legacy endpoints.
4. **T0 + 8 weeks:** set and communicate hard sunset date.
5. **Sunset date:** disable `ADMIN_ENABLE_LEGACY_API_ALIASES`; remove aliases in next cleanup release.

## Response envelope

### Success

- `success`: `true`
- `data`: payload (object, array, or `null` when only a message is needed)
- `message`: optional human-readable string (create/update/delete flows)

### Paginated list

- `success`: `true`
- `data`: array of items (current page)
- `meta`: canonical pagination (aligned with `01-route-audit-and-contract-freeze.md`)
  - `page`, `perPage`, `total`, `lastPage`
- `pagination`: **legacy** mirror for existing clients (`current_page`, `last_page`, `per_page`, `total`)

### Statistics / aggregates

- `success`: `true`
- `data`: object (e.g. `{ "stats": { ... }, "daily_stats": [...] }`)

### Errors (API / JSON)

Validation and auth layers should return:

- `success`: `false`
- `message`: human-readable summary
- `error`: stable machine code (e.g. `UNAUTHENTICATED`, `FORBIDDEN`, `VALIDATION_ERROR`)
- `errors`: optional object of field → messages (validation)

HTTP status: `401` / `403` / `422` / `404` / `429` as appropriate. `200` is reserved for successful operations with a body; use `204` only if you standardize empty body deletes (not used in current admin API).

## List endpoints — query parameters

| Parameter | Description |
|-----------|-------------|
| `page` | 1-based page index |
| `perPage` or `per_page` | Page size (clamped **1–100**; default **20**) |
| `q` or `search` | Free-text search (resource-defined) |
| `sortBy` | Whitelisted column / field name |
| `sortDir` | `asc` or `desc` (default `desc` where applicable) |
| `status`, `type`, … | Resource filters |
| `dateFrom`, `dateTo` | Inclusive date range on `created_at` unless documented otherwise |
| `date_range` | Optional preset: `today`, `week`, `month`, `year` (coins) |

## Resource verbs (standard set)

| Verb | HTTP | Path segment (typical) |
|------|------|-------------------------|
| List | GET | `/` |
| Detail | GET | `/{id}` |
| Create | POST | `/` |
| Update | PUT/PATCH | `/{id}` |
| Delete | DELETE | `/{id}` |
| Bulk actions | POST | `/bulk-action` |
| Statistics | GET | `/statistics/data` |
| Export | GET | `/export` |

**Route order:** register `/export`, `/statistics/data`, `/bulk-action` **before** `/{resource}` so IDs do not capture static paths.

## OpenAPI

- Admin coins: `docs/next-dashboard/openapi-admin-v1-coins.yaml`
- Admin coupons: `docs/next-dashboard/openapi-admin-v1-coupons.yaml`
- Admin commission payments: `docs/next-dashboard/openapi-admin-v1-commission-payments.yaml`
- Admin payments: `docs/next-dashboard/openapi-admin-v1-payments.yaml`
- Admin subscriptions: `docs/next-dashboard/openapi-admin-v1-subscriptions.yaml`
- Admin subscription plans: `docs/next-dashboard/openapi-admin-v1-subscription-plans.yaml`
- Admin affiliate: `docs/next-dashboard/openapi-admin-v1-affiliate.yaml`
- Admin roles: `docs/next-dashboard/openapi-admin-v1-roles.yaml`
- Admin users: `docs/next-dashboard/openapi-admin-v1-users.yaml`
- Admin categories: `docs/next-dashboard/openapi-admin-v1-categories.yaml`
- Admin episodes: `docs/next-dashboard/openapi-admin-v1-episodes.yaml`
- Admin stories: `docs/next-dashboard/openapi-admin-v1-stories.yaml`
- Admin people: `docs/next-dashboard/openapi-admin-v1-people.yaml`
- Admin notifications: `docs/next-dashboard/openapi-admin-v1-notifications.yaml`
- Admin comment moderation: `docs/next-dashboard/openapi-admin-v1-comment-moderation.yaml`
- Admin content moderation: `docs/next-dashboard/openapi-admin-v1-content-moderation.yaml`
- Admin quiz: `docs/next-dashboard/openapi-admin-v1-quiz.yaml`
- Admin referrals: `docs/next-dashboard/openapi-admin-v1-referrals.yaml`

## Implementation helper

`App\Http\Support\AdminApiResponse`:

- `paginated(LengthAwarePaginator $paginator)`
- `success($data, ?string $message = null, int $status = 200, array $extra = [])`
- `okMessage(string $message, int $status = 200)`

## Rollout

Apply `AdminApiResponse` and list/meta conventions to remaining `App\Http\Controllers\Admin\*` API methods in small PRs, one domain at a time (coupons, payments, subscriptions, …), keeping legacy `pagination` until the Next dashboard no longer needs it.
