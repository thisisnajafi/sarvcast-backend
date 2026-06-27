# SMS Template Management & Bulk Campaign Feature

**Project:** SarvCast  
**Scope:** Laravel backend (`sarvcast-laravel`) + Next.js admin dashboard (`next-dashboard`)  
**Provider:** Melipayamak (ملی‌پیامک) — pattern/template-based SMS  
**Status:** Specification (pre-implementation)  
**Last updated:** June 2026

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Current State Analysis](#2-current-state-analysis)
3. [Goals & Non-Goals](#3-goals--non-goals)
4. [Melipayamak Template Model](#4-melipayamak-template-model)
5. [Data Model Design](#5-data-model-design)
6. [Backend Architecture](#6-backend-architecture)
7. [API Specification](#7-api-specification)
8. [Audience Targeting (Segmentation)](#8-audience-targeting-segmentation)
9. [Bulk Send Flow & Queue Design](#9-bulk-send-flow--queue-design)
10. [Next.js Dashboard UI](#10-nextjs-dashboard-ui)
11. [Permissions & RBAC](#11-permissions--rbac)
12. [Logging, Monitoring & Audit](#12-logging-monitoring--audit)
13. [Security & Rate Limiting](#13-security--rate-limiting)
14. [Existing Gaps to Fix First](#14-existing-gaps-to-fix-first)
15. [Implementation Phases](#15-implementation-phases)
16. [Testing Plan](#16-testing-plan)
17. [Appendix: Reference Code & Patterns](#17-appendix-reference-code--patterns)

---

## 1. Executive Summary

SarvCast already sends OTP and transactional SMS via **Melipayamak pattern templates** (`sendByBaseNumber` / `BaseServiceNumber`). This feature adds an **admin-managed SMS template registry** and a **bulk SMS campaign tool** in the Next.js dashboard.

Admins will:

1. **Register Melipayamak templates** — enter the panel-assigned `bodyId` (pattern code), template preview text, and define dynamic parameters (e.g. `{0}` = first name, `{1}` = last name).
2. **Send bulk SMS** to segmented audiences — all users, premium only, non-premium, by role, custom user list, or manual phone numbers — using a selected template with per-user parameter resolution.

Templates are **created and approved in the Melipayamak web panel**; SarvCast stores metadata and maps dynamic fields to user attributes. There is **no Melipayamak API to auto-fetch pattern lists** — registration is manual.

---

## 2. Current State Analysis

### 2.1 What exists today

| Component | Location | Status |
|-----------|----------|--------|
| Core SMS service | `app/Services/SmsService.php` | ✅ Working for OTP + single template send |
| Template send method | `SmsService::sendSmsWithTemplate($to, $templateId, $parameters)` | ✅ Uses cURL `BaseServiceNumber` + library fallback |
| OTP template | `config/services.php` → `MELIPAYAMK_VERIFICATION_TEMPLATE` (default `372382`) | ✅ Hardcoded in config |
| SMS log model | `app/Models/SmsLog.php` + `sms_logs` table | ⚠️ Exists but **not written to** by `SmsService` |
| Local template strings | `config/sms.php` → `templates` array | ⚠️ App-side strings only; not linked to Melipayamak |
| Mobile bulk SMS route | `POST /api/v1/mobile/sms/bulk` | ❌ Calls `SmsService::sendBulkSms()` which **does not exist** |
| Admin notification SMS | `Admin\NotificationController` | ❌ `send_sms` checkbox is a **commented stub** |
| Marketing SMS | `NotificationService::sendBulkMarketingNotification()` | ⚠️ Uses **sms.ir**, not Melipayamak |
| Dashboard SMS UI | `next-dashboard` | ❌ No SMS templates or campaign pages |

### 2.2 Melipayamak integration pattern (current)

```php
// Parameters joined with semicolon for Melipayamak REST API
$text = implode(';', $parameters); // e.g. "علی;احمدی"

// POST https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber
[
    'username' => config('melipayamak.username'),
    'password' => config('melipayamak.password'),
    'to'       => '09123456789',
    'bodyId'   => 372382,          // Pattern ID from Melipayamak panel
    'text'     => '123456',        // Semicolon-separated parameter values
]
```

**Credentials (split across two config files — must unify):**

| Env variable | Config path | Purpose |
|--------------|-------------|---------|
| `MELIPAYAMAK_USERNAME` | `config/melipayamak.php` | API username |
| `MELIPAYAMAK_PASSWORD` | `config/melipayamak.php` | API password |
| `MELIPAYAMK_SENDER` | `config/services.php` | Sender line |
| `MELIPAYAMK_VERIFICATION_TEMPLATE` | `config/services.php` | OTP pattern ID |

### 2.3 User & subscription model (for targeting)

- **Users** identified by `phone_number` (required for SMS).
- **Premium status** derived from `subscriptions` table — no `is_premium` on users.
- **Active premium:** `status = 'active'` AND `end_date > now()`.
- **Roles:** dual system — `users.role` column (`parent`, `child`, `admin`, `basic`, `super_admin`, `voice_actor`) + RBAC pivot (`roles`, `user_role`).

Existing query patterns in `Admin\UserController`:

```php
// Premium users
User::whereHas('subscriptions', fn ($q) =>
    $q->where('status', 'active')->where('end_date', '>', now())
);

// Non-premium users
User::whereDoesntHave('subscriptions', fn ($q) =>
    $q->where('status', 'active')->where('end_date', '>', now())
);
```

---

## 3. Goals & Non-Goals

### Goals

- CRUD for SMS templates in admin dashboard and Laravel API.
- Bulk SMS campaigns with audience segmentation.
- Per-recipient dynamic parameter resolution from user fields.
- Queue-based async sending for large audiences.
- Full audit trail in `sms_logs` and a new `sms_campaigns` table.
- RBAC permissions aligned with existing dashboard patterns.

### Non-Goals (v1)

- Auto-sync templates from Melipayamak panel (no public list API).
- Scheduled/recurring campaigns (can be phase 2).
- A/B testing or drip sequences.
- Replacing OTP/auth SMS flow (stays as-is with hardcoded verification template).
- Migrating `NotificationService` marketing SMS from sms.ir to Melipayamak (separate task).

---

## 4. Melipayamak Template Model

### 4.1 How Melipayamak patterns work

1. Admin creates a **pattern (الگو)** in [Melipayamak panel](https://panel.melipayamak.com).
2. Melipayamak assigns a numeric **`bodyId`** (e.g. `372382`).
3. Pattern text uses placeholders: `{0}`, `{1}`, `{2}`, …
4. When sending, pass parameter values as a **semicolon-separated string** in `text`.

**Example:**

| Panel pattern text | bodyId | Parameters sent |
|--------------------|--------|-----------------|
| `سلام {0} {1}، اشتراک شما تا {2} فعال است. سروکست` | `380001` | `text=علی;احمدی;۱۴۰۴/۰۴/۰۱` |

### 4.2 What admin enters in SarvCast

Because Melipayamak does not expose a “list my patterns” REST endpoint, the admin manually registers:

| Field | Required | Description |
|-------|----------|-------------|
| `name` | Yes | Internal label, e.g. "یادآوری انقضای اشتراک" |
| `melipayamak_body_id` | Yes | Numeric pattern code from panel |
| `preview_text` | Yes | Copy of approved pattern from panel (for UI preview) |
| `parameters` | Yes | JSON array defining each `{N}` slot |
| `category` | No | `marketing`, `transactional`, `system` |
| `is_active` | Yes | Enable/disable without deleting |
| `description` | No | Admin notes |

### 4.3 Dynamic parameter sources

Each parameter slot maps to a **source type** resolved at send time:

| Source key | Resolves to | Example |
|------------|-------------|---------|
| `user.first_name` | `$user->first_name` | علی |
| `user.last_name` | `$user->last_name` | احمدی |
| `user.full_name` | `$user->full_name` accessor | علی احمدی |
| `user.phone_number` | `$user->phone_number` | 09123456789 |
| `subscription.end_date_jalali` | Active sub end date (Jalali) | ۱۴۰۴/۰۴/۰۱ |
| `subscription.type_label` | Plan type in Persian | یک‌ساله |
| `static` | Fixed value from campaign form | تخفیف ۲۰٪ |
| `custom` | Admin-entered per campaign | — |

**Parameter definition schema (stored in `sms_templates.parameters` JSON):**

```json
[
  {
    "index": 0,
    "label": "نام",
    "source": "user.first_name",
    "fallback": "کاربر"
  },
  {
    "index": 1,
    "label": "نام خانوادگی",
    "source": "user.last_name",
    "fallback": ""
  },
  {
    "index": 2,
    "label": "متن تبلیغاتی",
    "source": "static",
    "static_value": "تخفیف ویژه ۳۰٪"
  }
]
```

At send time, values are ordered by `index` and joined with `;` for Melipayamak.

---

## 5. Data Model Design

### 5.1 New table: `sms_templates`

```sql
CREATE TABLE sms_templates (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(255) NOT NULL,
    slug                VARCHAR(255) NOT NULL UNIQUE,
    melipayamak_body_id INT UNSIGNED NOT NULL,
    preview_text        TEXT NOT NULL,
    parameters          JSON NOT NULL,           -- parameter definitions (see §4.3)
    category            VARCHAR(50) DEFAULT 'marketing',
    description         TEXT NULL,
    is_active           BOOLEAN DEFAULT TRUE,
    created_by          BIGINT UNSIGNED NULL,
    updated_by          BIGINT UNSIGNED NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,

    INDEX idx_body_id (melipayamak_body_id),
    INDEX idx_active (is_active),
    INDEX idx_category (category),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### 5.2 New table: `sms_campaigns`

```sql
CREATE TABLE sms_campaigns (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(255) NOT NULL,
    sms_template_id     BIGINT UNSIGNED NOT NULL,
    audience_type       VARCHAR(50) NOT NULL,    -- see §8
    audience_filters    JSON NULL,                 -- role ids, exclude ids, static overrides
    status              VARCHAR(20) DEFAULT 'draft',
    -- draft | queued | processing | completed | failed | cancelled
    total_recipients    INT UNSIGNED DEFAULT 0,
    sent_count          INT UNSIGNED DEFAULT 0,
    failed_count        INT UNSIGNED DEFAULT 0,
    skipped_count       INT UNSIGNED DEFAULT 0,  -- no phone, opted out, etc.
    scheduled_at        TIMESTAMP NULL,
    started_at          TIMESTAMP NULL,
    completed_at        TIMESTAMP NULL,
    created_by          BIGINT UNSIGNED NOT NULL,
    notes               TEXT NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,

    INDEX idx_status (status),
    INDEX idx_template (sms_template_id),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (sms_template_id) REFERENCES sms_templates(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

### 5.3 New table: `sms_campaign_recipients` (optional but recommended for large sends)

```sql
CREATE TABLE sms_campaign_recipients (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sms_campaign_id     BIGINT UNSIGNED NOT NULL,
    user_id             BIGINT UNSIGNED NULL,
    phone_number        VARCHAR(15) NOT NULL,
    resolved_parameters JSON NULL,
    status              VARCHAR(20) DEFAULT 'pending',
    -- pending | sent | failed | skipped
    sms_log_id          BIGINT UNSIGNED NULL,
    error_message       TEXT NULL,
    sent_at             TIMESTAMP NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,

    INDEX idx_campaign_status (sms_campaign_id, status),
    FOREIGN KEY (sms_campaign_id) REFERENCES sms_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (sms_log_id) REFERENCES sms_logs(id) ON DELETE SET NULL
);
```

### 5.4 Extend `sms_logs` table

Add columns via migration:

```sql
ALTER TABLE sms_logs
    ADD COLUMN sms_template_id BIGINT UNSIGNED NULL AFTER template_key,
    ADD COLUMN sms_campaign_id BIGINT UNSIGNED NULL AFTER sms_template_id,
    ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER phone_number,
    ADD FOREIGN KEY (sms_template_id) REFERENCES sms_templates(id) ON DELETE SET NULL,
    ADD FOREIGN KEY (sms_campaign_id) REFERENCES sms_campaigns(id) ON DELETE SET NULL;
```

Keep `template_key` for backward compatibility with OTP/system sends.

### 5.5 Eloquent models

| Model | Path | Relationships |
|-------|------|---------------|
| `SmsTemplate` | `app/Models/SmsTemplate.php` | `campaigns()`, `creator()`, `updater()` |
| `SmsCampaign` | `app/Models/SmsCampaign.php` | `template()`, `recipients()`, `creator()` |
| `SmsCampaignRecipient` | `app/Models/SmsCampaignRecipient.php` | `campaign()`, `user()`, `smsLog()` |

---

## 6. Backend Architecture

### 6.1 Service layer

```
app/Services/
├── SmsService.php                    # Extend: logging, bulk chunk helper
├── SmsTemplateService.php            # NEW: CRUD + parameter validation
├── SmsCampaignService.php            # NEW: audience resolution, campaign lifecycle
├── SmsParameterResolver.php          # NEW: user → parameter array
└── SmsAudienceBuilder.php            # NEW: query builders per audience_type
```

#### `SmsParameterResolver`

```php
class SmsParameterResolver
{
    public function resolve(User $user, array $parameterDefinitions, array $overrides = []): array
    {
        // Returns ordered array of strings for Melipayamak implode(';')
    }
}
```

#### `SmsAudienceBuilder`

```php
class SmsAudienceBuilder
{
    public function buildQuery(string $audienceType, array $filters): Builder
    {
        return match ($audienceType) {
            'all'              => User::active()->whereNotNull('phone_number'),
            'premium'          => User::active()->whereHas('subscriptions', fn ($q) => $q->active()),
            'non_premium'      => User::active()->whereDoesntHave('subscriptions', fn ($q) => $q->active()),
            'role_column'      => User::active()->where('role', $filters['role']),
            'rbac_role'        => User::active()->whereHas('roles', fn ($q) => $q->whereIn('id', $filters['role_ids'])),
            'specific_users'   => User::active()->whereIn('id', $filters['user_ids']),
            'manual_phones'    => /* no User query — handled separately */,
            default            => throw new InvalidAudienceException($audienceType),
        };
    }

    public function applyExclusions(Builder $query, array $excludeUserIds): Builder
    {
        return $query->whereNotIn('id', $excludeUserIds);
    }
}
```

#### `SmsCampaignService`

Key methods:

- `createCampaign(array $data): SmsCampaign`
- `previewAudience(SmsCampaign $campaign): array` — count + sample 5 users
- `dispatch(SmsCampaign $campaign): void` — queue job
- `processCampaign(SmsCampaign $campaign): void` — chunk recipients, dispatch per-SMS jobs
- `cancel(SmsCampaign $campaign): void`

### 6.2 Jobs

```
app/Jobs/
├── ProcessSmsCampaign.php           # Orchestrator: builds recipient list, chunks
└── SendCampaignSms.php              # Sends one SMS, writes sms_log, updates recipient
```

**Queue config suggestion:**

- Queue name: `sms`
- Chunk size: 50 recipients per batch job
- Delay between sends: 200ms (configurable) to respect Melipayamak rate limits
- Retry: 3 attempts with exponential backoff

### 6.3 Controllers

```
app/Http/Controllers/Admin/
├── SmsTemplateController.php        # Template CRUD API
└── SmsCampaignController.php        # Campaign CRUD + send/preview/cancel
```

Follow existing admin API patterns from `CategoryController` and `NotificationController`:

- `apiIndex`, `apiStore`, `apiShow`, `apiUpdate`, `apiDestroy`
- `apiBulkAction`, `apiExport`, `apiStatistics`

---

## 7. API Specification

All routes under existing admin middleware group in `routes/api.php`:

```php
Route::middleware(['auth:sanctum', 'api.admin', 'api.permission', 'throttle', 'api.audit'])
    ->prefix('admin')
    ->group(function () {
        // ...
    });
```

### 7.1 SMS Templates

| Method | Route | Permission | Description |
|--------|-------|------------|-------------|
| GET | `/admin/sms-templates` | `sms_templates.read` | Paginated list |
| POST | `/admin/sms-templates` | `sms_templates.create` | Create template |
| GET | `/admin/sms-templates/{id}` | `sms_templates.read` | Show template |
| PUT | `/admin/sms-templates/{id}` | `sms_templates.update` | Update template |
| DELETE | `/admin/sms-templates/{id}` | `sms_templates.delete` | Soft-delete or hard-delete |
| POST | `/admin/sms-templates/bulk-action` | `sms_templates.bulk` | activate/deactivate/delete |
| GET | `/admin/sms-templates/export` | `sms_templates.export` | CSV export |
| GET | `/admin/sms-templates/statistics/data` | `sms_templates.read` | Usage stats |
| POST | `/admin/sms-templates/{id}/test-send` | `sms_templates.send_test` | Send test to one phone |

#### Create template request

```json
POST /api/admin/sms-templates
{
  "name": "یادآوری اشتراک",
  "melipayamak_body_id": 380001,
  "preview_text": "سلام {0} {1}، اشتراک شما تا {2} فعال است. سروکست",
  "category": "marketing",
  "description": "برای کاربران پریمیوم",
  "is_active": true,
  "parameters": [
    { "index": 0, "label": "نام", "source": "user.first_name", "fallback": "کاربر" },
    { "index": 1, "label": "نام خانوادگی", "source": "user.last_name", "fallback": "" },
    { "index": 2, "label": "تاریخ انقضا", "source": "subscription.end_date_jalali", "fallback": "—" }
  ]
}
```

#### Test send request

```json
POST /api/admin/sms-templates/{id}/test-send
{
  "phone_number": "09123456789",
  "parameter_overrides": {
    "0": "علی",
    "1": "تست",
    "2": "۱۴۰۴/۰۴/۰۱"
  }
}
```

### 7.2 SMS Campaigns

| Method | Route | Permission | Description |
|--------|-------|------------|-------------|
| GET | `/admin/sms-campaigns` | `sms_campaigns.read` | Paginated list |
| POST | `/admin/sms-campaigns` | `sms_campaigns.create` | Create draft campaign |
| GET | `/admin/sms-campaigns/{id}` | `sms_campaigns.read` | Show + progress |
| PUT | `/admin/sms-campaigns/{id}` | `sms_campaigns.update` | Update draft |
| DELETE | `/admin/sms-campaigns/{id}` | `sms_campaigns.delete` | Delete draft |
| POST | `/admin/sms-campaigns/{id}/preview` | `sms_campaigns.read` | Audience count + samples |
| POST | `/admin/sms-campaigns/{id}/dispatch` | `sms_campaigns.send` | Start sending |
| POST | `/admin/sms-campaigns/{id}/cancel` | `sms_campaigns.send` | Cancel in-progress |
| GET | `/admin/sms-campaigns/{id}/recipients` | `sms_campaigns.read` | Paginated recipient status |
| GET | `/admin/sms-campaigns/statistics/data` | `sms_campaigns.read` | Dashboard stats |
| GET | `/admin/sms-campaigns/export` | `sms_campaigns.export` | CSV export |

#### Create campaign request

```json
POST /api/admin/sms-campaigns
{
  "name": "تبلیغ تابستان — غیر پریمیوم",
  "sms_template_id": 3,
  "audience_type": "non_premium",
  "audience_filters": {
    "exclude_user_ids": [12, 45],
    "role_column": null,
    "rbac_role_ids": [],
    "static_parameter_overrides": {
      "2": "تخفیف ۳۰٪ تا پایان تابستان"
    }
  },
  "scheduled_at": null,
  "notes": "ارسال یک‌باره"
}
```

#### Preview response

```json
{
  "success": true,
  "data": {
    "total_recipients": 1247,
    "sample_users": [
      { "id": 101, "full_name": "علی احمدی", "phone_number": "0912***6789", "preview_message": "سلام علی احمدی، ..." }
    ],
    "skipped_no_phone": 3,
    "excluded_count": 2
  }
}
```

### 7.3 Response envelope

Follow existing `AdminApiResponse::success()` pattern used across admin controllers.

---

## 8. Audience Targeting (Segmentation)

### 8.1 Supported `audience_type` values

| Type | Label (FA) | Query logic |
|------|------------|-------------|
| `all` | همه کاربران فعال | `User::active()->whereNotNull('phone_number')` |
| `premium` | فقط اعضای پریمیوم | Active subscription (`status=active`, `end_date > now()`) |
| `non_premium` | بدون اشتراک فعال | Inverse of premium |
| `role_column` | نقش کاربری (ستون) | `users.role IN (...)` — parent, child, basic, voice_actor, etc. |
| `rbac_role` | نقش پنل مدیریت | `whereHas('roles', ...)` pivot |
| `specific_users` | کاربران انتخابی | `whereIn('id', user_ids)` |
| `manual_phones` | شماره‌های دستی | No user query; phones from `audience_filters.phone_numbers[]` |

### 8.2 Exclusion options (all types except `manual_phones`)

```json
"audience_filters": {
  "exclude_user_ids": [1, 2, 3],
  "exclude_premium": false,
  "exclude_suspended": true
}
```

When `exclude_premium: true` on an `all` campaign, premium users are removed even though the base audience is everyone.

### 8.3 User eligibility rules

A user is **eligible** if:

- `status = 'active'`
- `phone_number` is not null and matches Iranian mobile pattern (`09XXXXXXXXX`)
- Not in `exclude_user_ids`
- Not an admin/super_admin (configurable — recommend excluding by default for marketing)

### 8.4 Audience builder reference (premium / non-premium)

Reuse patterns from `Admin\UserController::apiIndex`:

```php
// Premium
$query->whereHas('subscriptions', function ($q) {
    $q->where('status', 'active')->where('end_date', '>', now());
});

// Non-premium
$query->whereDoesntHave('subscriptions', function ($q) {
    $q->where('status', 'active')->where('end_date', '>', now());
});
```

---

## 9. Bulk Send Flow & Queue Design

### 9.1 Sequence diagram

```
Admin Dashboard                Laravel API                  Queue Worker
     |                              |                            |
     |-- POST /sms-campaigns ------>|                            |
     |<-- campaign (draft) ---------|                            |
     |                              |                            |
     |-- POST /preview ------------>|                            |
     |<-- count + samples ----------|                            |
     |                              |                            |
     |-- POST /dispatch ------------>|                            |
     |                              |-- ProcessSmsCampaign ----->|
     |<-- status: queued -----------|                            |
     |                              |                            |-- resolve audience
     |                              |                            |-- insert recipients
     |                              |                            |-- chunk → SendCampaignSms × N
     |                              |                            |     |
     |                              |                            |     |-- SmsService::sendSmsWithTemplate
     |                              |                            |     |-- SmsLog::create
     |                              |                            |     |-- update recipient status
     |                              |                            |-- mark campaign completed
     |-- GET /campaigns/{id} ------>|                            |
     |<-- progress (sent/failed) ---|                            |
```

### 9.2 Campaign status transitions

```
draft → queued → processing → completed
                           ↘ failed
draft/processing → cancelled
```

### 9.3 Idempotency & safety

- Only `draft` campaigns can be edited.
- `dispatch` is allowed only from `draft` with `total_recipients > 0`.
- Re-dispatch of `completed` campaigns creates a **new** campaign (no re-send by default).
- Admin confirmation step in UI before dispatch (show recipient count).

### 9.4 Rate limiting

| Level | Limit | Config key |
|-------|-------|------------|
| Per campaign | Max 10,000 recipients | `SMS_CAMPAIGN_MAX_RECIPIENTS` |
| Per admin per day | Max 3 campaigns | `SMS_CAMPAIGN_DAILY_LIMIT` |
| Per phone per hour | 5 SMS (existing OTP limit) | Reuse `SmsService::hasTooManyAttempts` logic with purpose `campaign` |
| Send throttle | 200ms between jobs | `SMS_CAMPAIGN_SEND_DELAY_MS` |

---

## 10. Next.js Dashboard UI

**Project:** `next-dashboard`  
**Pattern references:** `categories/` (CRUD), `notifications/` (stats + bulk actions)

### 10.1 Navigation

Add under **کاربران و عوامل** section in `components/layout/sidebar-nav.tsx`:

```
{ href: "/sms-templates", label: "قالب‌های پیامک" }
{ href: "/sms-campaigns", label: "ارسال گروهی پیامک" }
```

### 10.2 SMS Templates pages

| Route | File | Description |
|-------|------|-------------|
| `/sms-templates` | `app/(dashboard)/sms-templates/page.tsx` | List with search, category filter, active toggle, export |
| `/sms-templates/new` | `app/(dashboard)/sms-templates/new/page.tsx` | Create form |
| `/sms-templates/[id]` | `app/(dashboard)/sms-templates/[id]/page.client.tsx` | Detail + usage stats |
| `/sms-templates/[id]/edit` | `app/(dashboard)/sms-templates/[id]/edit/page.client.tsx` | Edit form |

#### Template form fields (`components/sms-templates/sms-template-form.tsx`)

| Field | UI component | Notes |
|-------|--------------|-------|
| نام قالب | `Input` | Required |
| کد الگو (bodyId) | `Input` type number | Melipayamak panel code |
| متن پیش‌نمایش | `Textarea` | Copy from panel; show `{0}`, `{1}` placeholders |
| دسته‌بندی | `<select>` | marketing / transactional / system |
| پارامترها | Dynamic field array | Add row: index, label, source dropdown, fallback |
| فعال | Checkbox | |
| توضیحات | Textarea | Optional |
| **تست ارسال** | Phone input + button | Calls `test-send` endpoint |

**Parameter source dropdown options:**

- نام (`user.first_name`)
- نام خانوادگی (`user.last_name`)
- نام کامل (`user.full_name`)
- شماره موبایل (`user.phone_number`)
- تاریخ انقضای اشتراک (`subscription.end_date_jalali`)
- نوع اشتراک (`subscription.type_label`)
- مقدار ثابت (`static`) → shows extra text input
- سفارشی (`custom`) → filled at campaign time

**Live preview:** As admin fills preview text + parameter labels, show rendered example with sample data.

### 10.3 SMS Campaigns pages

| Route | File | Description |
|-------|------|-------------|
| `/sms-campaigns` | `app/(dashboard)/sms-campaigns/page.tsx` | List with status badges, stats cards |
| `/sms-campaigns/new` | `app/(dashboard)/sms-campaigns/new/page.tsx` | Multi-step campaign wizard |
| `/sms-campaigns/[id]` | `app/(dashboard)/sms-campaigns/[id]/page.client.tsx` | Progress, recipient log, cancel button |

#### Campaign wizard steps

**Step 1 — انتخاب قالب**
- Dropdown of active templates
- Show preview text + parameter list

**Step 2 — مخاطبان**
- Radio: همه / پریمیوم / غیر پریمیوم / نقش / کاربران خاص / شماره دستی
- Conditional fields:
  - Role: multi-select (column roles + RBAC roles)
  - Specific users: searchable multi-select (reuse user search from `/users`)
  - Manual phones: textarea (one per line)
- Exclusion: multi-select users to exclude
- Checkbox: exclude admins from marketing sends

**Step 3 — پارامترهای ثابت**
- For parameters with `source: static` or `custom`, show inputs
- Live preview for one sample user

**Step 4 — بررسی و ارسال**
- Show recipient count (call preview API)
- Sample messages (3 users)
- Confirm button → dispatch
- Optional: schedule for later (phase 2)

### 10.4 TypeScript types

`next-dashboard/types/sms-template.ts`:

```typescript
export type SmsTemplateParameter = {
  index: number;
  label: string;
  source: string;
  fallback?: string;
  static_value?: string;
};

export type SmsTemplate = {
  id: number;
  name: string;
  slug: string;
  melipayamak_body_id: number;
  preview_text: string;
  parameters: SmsTemplateParameter[];
  category: string;
  is_active: boolean;
  description?: string;
  created_at: string;
  updated_at: string;
};
```

`next-dashboard/types/sms-campaign.ts` — mirror backend campaign + recipient types.

### 10.5 API mapping

Frontend calls (via `dashboardFetch`):

| Frontend | Laravel |
|----------|---------|
| `GET /api/sms-templates` | `GET /api/admin/sms-templates` |
| `POST /api/sms-campaigns/{id}/dispatch` | `POST /api/admin/sms-campaigns/{id}/dispatch` |

---

## 11. Permissions & RBAC

Add to `RolePermissionSeeder` (or dedicated seeder):

| Permission | Display name (FA) | Group |
|------------|-------------------|-------|
| `sms_templates.read` | مشاهده قالب‌های پیامک | sms |
| `sms_templates.create` | ایجاد قالب پیامک | sms |
| `sms_templates.update` | ویرایش قالب پیامک | sms |
| `sms_templates.delete` | حذف قالب پیامک | sms |
| `sms_templates.bulk` | عملیات گروهی قالب‌ها | sms |
| `sms_templates.export` | خروجی قالب‌ها | sms |
| `sms_templates.send_test` | ارسال تست قالب | sms |
| `sms_campaigns.read` | مشاهده کمپین‌های پیامک | sms |
| `sms_campaigns.create` | ایجاد کمپین | sms |
| `sms_campaigns.update` | ویرایش کمپین | sms |
| `sms_campaigns.delete` | حذف کمپین | sms |
| `sms_campaigns.send` | ارسال/لغو کمپین | sms |
| `sms_campaigns.export` | خروجی کمپین | sms |

Assign all to `super_admin`; assign read + send to `admin` (configurable).

Register in `api.permission` middleware mapping (follow existing permission name conventions in the project).

---

## 12. Logging, Monitoring & Audit

### 12.1 Extend `SmsService` to write logs

After every send (success or failure):

```php
SmsLog::create([
    'phone_number'   => $to,
    'user_id'        => $userId,
    'message'        => $previewText, // or rendered message
    'template_key'   => null,
    'sms_template_id'=> $templateId,
    'sms_campaign_id'=> $campaignId,
    'variables'      => $parameters,
    'provider'       => 'melipayamak',
    'status'         => $success ? 'sent' : 'failed',
    'message_id'     => $result['message_id'] ?? null,
    'error_message'  => $result['error'] ?? null,
    'response_data'  => $result['response'] ?? null,
    'sent_at'        => $success ? now() : null,
]);
```

### 12.2 Campaign dashboard metrics

Stats cards on `/sms-campaigns`:

- Total campaigns (this month)
- SMS sent today / this month
- Success rate %
- Failed count
- Melipayamak credit (optional — call `GetCredit` API)

### 12.3 Audit trail

Use existing `api.audit` middleware — log:

- Template create/update/delete
- Campaign dispatch/cancel
- Test sends

---

## 13. Security & Rate Limiting

1. **Permission-gated** — all endpoints require Sanctum + RBAC.
2. **Origin check** — existing `admin.origin` middleware.
3. **No PII in logs** — mask phone numbers in application logs (`0912***6789`); store full number only in `sms_logs` (admin-only).
4. **Marketing consent** — consider adding `users.sms_marketing_opt_in` (phase 2); v1 excludes suspended users only.
5. **Admin exclusion** — default exclude `super_admin`, `admin`, `voice_actor` from marketing campaigns.
6. **Throttling** — campaign dispatch limited to 3/day per admin; test send limited to 5/hour.
7. **Validation** — validate `melipayamak_body_id` is positive integer; parameter count must match template definition count.

---

## 14. Existing Gaps to Fix First

Before or during implementation, address these technical debts:

| Issue | Fix |
|-------|-----|
| `SmsController::sendBulk()` calls missing `sendBulkSms()` | Implement in `SmsService` or remove mobile route; admin feature uses new campaign flow |
| `SmsService` doesn't write to `SmsLog` | Add logging helper used by all send paths |
| Config split `MELIPAYAMAK_*` vs `MELIPAYAMK_*` | Document clearly; optionally unify under one config file |
| Template ID inconsistency (`371085` vs `372382`) | Align docs and `.env.example` |
| `NotificationController` SMS stub | Optionally wire to new campaign service later |
| `RolePermissionSeeder` missing granular permissions | Extend with SMS permissions |
| Verification template hardcoded in config | Keep as-is; optionally migrate to `sms_templates` row with `category=system` |

---

## 15. Implementation Phases

### Phase 1 — Foundation (Backend)

- [ ] Migrations: `sms_templates`, `sms_campaigns`, `sms_campaign_recipients`, extend `sms_logs`
- [ ] Models + relationships
- [ ] `SmsParameterResolver`, `SmsAudienceBuilder`
- [ ] Extend `SmsService` with logging
- [ ] `SmsTemplateService` + `SmsTemplateController` (full CRUD)
- [ ] Permissions seeder
- [ ] Feature tests for template CRUD + parameter resolution

### Phase 2 — Campaign Engine

- [ ] `SmsCampaignService`
- [ ] Jobs: `ProcessSmsCampaign`, `SendCampaignSms`
- [ ] `SmsCampaignController` (CRUD + preview + dispatch + cancel)
- [ ] Queue config (`sms` queue in supervisor)
- [ ] Feature tests for audience queries + campaign lifecycle

### Phase 3 — Dashboard UI

- [ ] Types: `sms-template.ts`, `sms-campaign.ts`
- [ ] Template CRUD pages + form component
- [ ] Campaign wizard + progress page
- [ ] Sidebar navigation entries
- [ ] Permission gates

### Phase 4 — Polish & Ops

- [x] CSV export for templates and campaigns
- [x] CSV export for campaign recipients
- [x] Combined statistics endpoint (`/api/admin/sms/overview/statistics/data`) + dashboard cards
- [x] Test send from template form (edit mode)
- [x] `.env.example` documentation (`MELIPAYAMAK_*`, `SMS_CAMPAIGN_*`, `SMS_DELIVERY_*`)
- [x] Migrate verification template to DB (`SmsSystemTemplateSeeder` + `resolveVerificationTemplateId()`)
- [x] Delivery status polling via `GetDeliveries2` (`PollSmsLogDeliveryJob`, `sms:poll-deliveries` scheduler)

---

## 16. Testing Plan

### Unit tests

- `SmsParameterResolver` — all source types, fallbacks, Jalali dates
- `SmsAudienceBuilder` — each audience type + exclusions
- Template validation — parameter count matches `{N}` placeholders in preview text

### Feature tests

- Template CRUD API with permission checks
- Campaign preview returns correct counts for premium/non-premium fixtures
- Dispatch queues jobs and updates campaign status
- Test send writes to `sms_logs`
- Cancel stops pending jobs

### Manual QA checklist

- [ ] Create template with bodyId from Melipayamak panel
- [ ] Test send to own phone — verify SMS content matches pattern
- [ ] Preview non-premium audience count matches user list filter
- [ ] Dispatch small campaign (5 users) — verify all statuses in recipient log
- [ ] Verify excluded users don't receive SMS
- [ ] Verify admin users excluded from marketing campaign
- [ ] Dashboard progress updates during send
- [ ] Failed send (invalid phone) recorded with error message

---

## 17. Appendix: Reference Code & Patterns

### A. Existing template send (reuse as-is)

File: `app/Services/SmsService.php`

```php
public function sendSmsWithTemplate(string $to, int $templateId, array $parameters = []): array
{
    $text = implode(';', $parameters);
    // Primary: cURL BaseServiceNumber
    // Fallback: MelipayamakApi sendByBaseNumber
}
```

### B. Admin CRUD route pattern (copy from categories)

File: `routes/api.php` (~line 1048)

```php
Route::prefix('sms-templates')->group(function () {
    Route::get('/', [SmsTemplateController::class, 'apiIndex']);
    Route::post('/', [SmsTemplateController::class, 'apiStore']);
    Route::get('/export', [SmsTemplateController::class, 'apiExport']);
    Route::get('/statistics/data', [SmsTemplateController::class, 'apiStatistics']);
    Route::post('/bulk-action', [SmsTemplateController::class, 'apiBulkAction']);
    Route::get('/{smsTemplate}', [SmsTemplateController::class, 'apiShow']);
    Route::put('/{smsTemplate}', [SmsTemplateController::class, 'apiUpdate']);
    Route::delete('/{smsTemplate}', [SmsTemplateController::class, 'apiDestroy']);
    Route::post('/{smsTemplate}/test-send', [SmsTemplateController::class, 'apiTestSend']);
});
```

### C. Dashboard list page pattern

Reference: `next-dashboard/app/(dashboard)/categories/page.tsx`

- React Query for list + stats
- `PermissionGate` for create/bulk actions
- `ListExportAction`, `ListBulkActions`, manual table, pagination

### D. Dashboard form pattern

Reference: `next-dashboard/components/categories/category-form.tsx`

- Zod schema + react-hook-form
- `FormAccessGuard`
- `dashboardFetch` POST/PUT

### E. Verification template (keep during migration)

Current OTP template in production config:

- **bodyId:** `372382` (config default; docs mention `371085`)
- **Pattern:** `کد ورود شما: {0} این کد 5 دقیقه اعتبار دارد سروکست`
- **Parameters:** `[otp_code]`

### F. Environment variables to document in `.env.example`

```env
# Melipayamak SMS
MELIPAYAMAK_USERNAME=
MELIPAYAMAK_PASSWORD=
MELIPAYAMK_SENDER=50002710008883
MELIPAYAMK_VERIFICATION_TEMPLATE=372382

# SMS Campaign limits
SMS_CAMPAIGN_MAX_RECIPIENTS=10000
SMS_CAMPAIGN_DAILY_LIMIT=3
SMS_CAMPAIGN_SEND_DELAY_MS=200
SMS_LOGGING_ENABLED=true
```

---

## Document History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-06-27 | — | Initial specification |
