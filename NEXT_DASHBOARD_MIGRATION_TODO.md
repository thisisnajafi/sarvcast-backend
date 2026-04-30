# SarvCast Next.js Dashboard Migration To-Do

## Goal

Migrate the current Laravel Blade admin dashboard to a separate Next.js project using shadcn/ui, while keeping Laravel as backend/API.

## Current State Summary

- Admin currently runs on Laravel web routes under `admin/*`.
- Access control mixes `auth:web + admin` (Blade routes) and `auth:sanctum + role` (API routes).
- Middleware aliases are defined in `bootstrap/app.php` (`admin`, `role`, `permission`, `2fa`, `security`, etc.).
- The admin surface is large: dashboard, content, users, roles, moderation, subscriptions/plans, coins/coupons, payments, affiliate programs, analytics, backup/performance/versions, and notifications.

---

## 1) Route Audit and Contract Freeze

- [x] Export all current admin routes into a migration sheet:
  - [x] Route name
  - [x] Method
  - [x] Path
  - [x] Controller action
  - [x] Middlewares
  - [x] Required role/permission
- [x] Mark each route as:
  - [x] Keep in phase 1
  - [x] Move to phase 2+
  - [x] Merge
  - [x] Deprecate
- [x] Define canonical admin API namespace (recommended: `/api/admin/v1/*`).
- [x] Remove duplicate or overlapping endpoint shapes.
- [x] Freeze request/response contracts for phase 1 endpoints.

Phase 1 deliverables:
- `docs/next-dashboard/route-list-all.txt`
- `docs/next-dashboard/route-list-admin-filter.txt`
- `docs/next-dashboard/01-route-audit-and-contract-freeze.md`

## 2) Authentication, Security, and Access Control

- [x] Finalize admin login flow for separate frontend:
  - [x] OTP send
  - [x] OTP verify/login
  - [x] Optional 2FA verification
  - [x] Logout
- [x] Add/normalize dedicated auth endpoints for dashboard frontend:
  - [x] `POST /admin/auth/login`
  - [x] `POST /admin/auth/logout`
  - [x] `GET /admin/auth/me`
  - [x] `POST /admin/auth/refresh` (if needed)
- [x] Standardize RBAC:
  - [x] Roles (`super_admin`, `admin`, others if needed)
  - [x] Fine-grained permissions (`stories.read`, `stories.write`, etc.)
- [x] Enforce explicit middleware on all admin APIs:
  - [x] Auth middleware
  - [x] Role middleware
  - [x] Permission middleware
- [x] Add route-level rate limiting (strict for auth, moderate for bulk/export).
- [x] Add audit logging for sensitive actions (role changes, delete, publish, refund, restore).
- [x] Configure CORS/CSRF/session policy for separate Next.js origin.

Phase 2 spec baseline:
- `docs/next-dashboard/02-auth-security-and-access-spec.md`
- Implemented:
  - canonical admin auth routes: `/api/admin/v1/auth/*`
  - origin allowlist middleware: `admin.origin` (`EnsureAllowedAdminOrigin`)
  - new env keys: `ADMIN_DASHBOARD_URL`, `ADMIN_DASHBOARD_ENFORCE_ORIGIN`

## 3) Backend API Standardization for Dashboard

- [ ] Normalize admin API patterns per resource (full admin surface):
  - [x] List
  - [x] Detail
  - [x] Create
  - [x] Update
  - [x] Delete
  - [x] Bulk actions
  - [x] Statistics
  - [x] Export
- [x] Standardize pagination/sorting/filter contracts (spec + `AdminApiResponse`; list endpoints **coins/coupons/commission-payments/payments/subscriptions/subscription-plans/affiliate/roles/users/categories/episodes/stories/people/notifications/comment-moderation/content-moderation/quiz/referrals** implement `meta` + query params).
- [x] Standardize error response format and HTTP status usage (documented; existing admin middleware aligns with spec).
- [x] Generate or update OpenAPI/Swagger docs for admin APIs (**coins/coupons/commission-payments/payments/subscriptions/subscription-plans/affiliate/roles/users/categories/episodes/stories/people/notifications/comment-moderation/content-moderation/quiz/referrals** fragments under `docs/next-dashboard/`; extend per resource).
- [x] Add/extend backend tests:
  - [ ] Auth and access tests (comprehensive matrix)
  - [x] Core CRUD tests (existing `ApiIntegrationTest` + resource contract tests: coins/coupons/commission-payments/payments/subscriptions/subscription-plans/affiliate/roles/users/categories/episodes/stories/people/notifications/comment-moderation/content-moderation/quiz/referrals meta/export)
  - [ ] Permission boundary tests (comprehensive matrix)

**Pilot (complete):** `App\Http\Support\AdminApiResponse`; `Admin\CoinController`, `Admin\CouponController`, `Admin\CommissionPaymentController`, `Admin\PaymentController`, `Admin\SubscriptionController`, `Admin\SubscriptionPlanController`, `Admin\AffiliateController`, `Admin\RoleController`, `Admin\UserController`, `Admin\CategoryController`, `Admin\EpisodeController`, `Admin\StoryController`, `Admin\PersonController`, `Admin\NotificationController`, `Admin\CommentModerationController`, `Admin\ContentModerationController`, `Admin\QuizController`, `Admin\ReferralController` (reordered routes, `GET /export`, list `meta` + filters); `docs/next-dashboard/03-backend-api-standardization.md`. **Next rollout:** apply the same patterns to other `routes/api.php` `admin/*` controllers (backup, performance-monitoring, app-versions, audio, timeline, file-upload, voice-actors, analytics/dashboards, …).

## 4) Next.js Project Foundation

- [x] Create separate project (recommended name: `sarvcast-dashboard-next`).
- [x] Setup stack:
  - [x] Next.js (App Router, TypeScript)
  - [x] Tailwind
  - [x] shadcn/ui
  - [x] ESLint + Prettier
- [x] Add app shell:
  - [x] Responsive sidebar
  - [x] Top bar
  - [x] Breadcrumbs
  - [x] User menu
  - [x] Command palette
- [x] Add data layer:
  - [x] API client wrapper
  - [x] Error handling and retries
  - [x] Auth token/session handling
- [x] Add TanStack Query for caching and server state.
- [x] Add forms with `react-hook-form + zod`.
- [x] Add reusable server-side data table framework.
- [x] Add route guards in Next middleware and server components.
- [x] Add i18n/RTL and Jalali date support.

Phase 4 deliverables (in `next-dashboard`):
- app shell + providers + auth/API routes
- guard/proxy, rtl layout, jalali formatter
- baseline pages (`/dashboard`, `/stories`, `/episodes`, `/users`, `/moderation`, `/login`)

## 5) Page Migration Plan

### Phase 1 (Core)

- [x] Auth screens (login/OTP/2FA/logout)
- [x] Main dashboard (`/dashboard`)
- [x] Stories module
- [x] Episodes module
- [x] Categories
- [x] Users
- [x] Comments moderation
- [x] Profile/settings

### Phase 2 (Business Modules)

- [x] Subscriptions
- [x] Plans
- [x] Subscription plans
- [x] Payments/refunds
- [x] Coupons
- [x] Coins
- [x] Commission payments
- [x] Notifications
- [x] People + voice actors
- [x] Timeline management

### Phase 3 (Advanced + Ops)

- [x] Specialized dashboards (stories/partners/sales)
- [x] User analytics
- [x] Content analytics
- [x] Revenue analytics
- [x] System analytics
- [x] Backup and recovery
- [x] Performance monitoring
- [x] Version/app-version management
- [x] Affiliate modules
- [ ] Teacher/influencer/school/corporate modules
- [ ] Quiz/referrals

## 6) Shared Component Checklist (shadcn/ui)

- [x] `AppSidebar`
- [x] `Topbar`
- [x] `Breadcrumbs`
- [x] `UserMenu`
- [x] `DataTable` (server mode)
- [ ] `FilterBar` (search/date/status/role/category)
- [ ] `StatCard`
- [ ] `TrendBadge`
- [ ] `ChartCard`
- [ ] `EntityFormLayout`
- [ ] `ConfirmDialog`
- [ ] `DangerZoneAction`
- [ ] `ExportButton`
- [x] `PermissionGate`
- [ ] `ActivityTimeline`
- [ ] `AuditLogPanel`

## 7) Charts and Analytics Requirements

- [x] KPI cards:
  - [x] Total users
  - [x] Active users
  - [x] Stories
  - [x] Episodes
  - [x] Revenue
  - [x] Active subscriptions
  - [x] Moderation pending
- [ ] Time series:
  - [x] Daily registrations
  - [x] Revenue trend
  - [ ] Play history trend
  - [x] Engagement trend
- [ ] Distribution:
  - [ ] Category performance
  - [ ] Platform/device split
  - [ ] Role distribution
- [ ] Funnel:
  - [ ] Subscription conversion
  - [ ] Onboarding funnel
  - [ ] Content completion funnel
- [ ] Moderation trend charts
- [x] System health/performance charts
- [ ] For every chart:
  - [ ] Date range filter
  - [ ] Compare period support
  - [ ] CSV/PNG export
  - [x] Loading, empty, and error states

## 8) Middleware and Access Matrix

- [ ] Build route-by-route role matrix.
- [ ] Build action-by-action permission matrix:
  - [ ] Read
  - [ ] Create
  - [ ] Update
  - [ ] Delete
  - [ ] Export
  - [ ] Bulk action
  - [ ] Approve/reject/refund
- [ ] Enforce matrix in Laravel policies/middleware.
- [ ] Mirror same matrix in Next UI route and component guards.
- [ ] Add 401/403 pages and safe fallback navigation.
- [ ] Add session-expiry handling UX.

## 9) Accessibility and UX Requirements

- [ ] Full keyboard navigation in all major flows.
- [ ] Semantic landmarks and heading hierarchy.
- [ ] Accessible labels and ARIA for controls/dialogs.
- [ ] WCAG AA color contrast in light/dark themes.
- [ ] Visible focus states.
- [ ] Screen reader friendly chart summaries.
- [ ] `aria-live` support for toasts/alerts.
- [ ] Responsive layout across mobile/tablet/desktop.
- [ ] Full RTL correctness for layout, forms, and charts.
- [ ] Consistent empty/error/retry UX across pages.

## 10) Testing, Rollout, and Decommission

- [ ] Unit tests for key components and guards.
- [ ] E2E tests for login, CRUD, RBAC, exports.
- [ ] API contract tests between Next frontend and Laravel backend.
- [ ] Monitoring integration (errors/performance).
- [ ] Feature flags for phased rollout.
- [ ] Run old Blade admin and new Next dashboard in parallel.
- [ ] Cutover checklist with rollback plan.
- [ ] Deprecate Blade admin routes only after parity sign-off.

---

## Recommended Execution Order

1. Route/API contract freeze  
2. Auth + RBAC finalization  
3. Next.js foundation  
4. Phase 1 modules  
5. Phase 2 modules  
6. Advanced analytics and ops modules  
7. QA, phased launch, Blade deprecation

