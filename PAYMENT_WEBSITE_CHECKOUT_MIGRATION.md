## Manji – Payment & Checkout on Website (`my.manji.ir`)

### 1. Goal

All payment-related flows must be handled **only on the website** (`my.manji.ir`, codebase: `manji-laravel`).  
The Flutter app (`manji-flutter`) must:

- Only **redirect users to the website** for:
  - plan selection,
  - entering coupon codes,
  - full checkout and payment.
- Only **read and display**:
  - current subscription status,
  - payment history,
  - payment result (via deep link or polling).

---

## 2. Current State (High-Level)

### 2.1 Flutter (`manji-flutter`)

**Active payment logic in the app:**

- `core/services/payment_service.dart`
  - `initiatePayment(subscriptionId)` → `POST https://my.manji.ir/api/v1/payments/initiate`
  - `verifyPayment(authority, status)` → `POST /payments/verify`
  - `getPaymentHistory()` → `GET /payments/history`
- `presentation/pages/payment_page.dart`
  - Full in-app checkout:
    - shows available plans,
    - handles promo code input/validation,
    - calculates discount,
    - selects payment method,
    - has a “پرداخت” (Pay) button that calls the payment service.
- `core/widgets/payment_webview.dart`
  - Opens payment gateway inside an **in-app WebView**.
  - Parses Zarinpal callback URLs and calls `onPaymentComplete(authority, status)`.
- `presentation/pages/payment_success_page.dart` / `payment_failure_page.dart`
  - Show payment result using deep-link data.

**Read‑only & acceptable:**

- `presentation/pages/payment_history_page.dart`
  - Uses `GET /payments/history` to **display** payment history.

### 2.2 Laravel API (`manji-laravel`, `my.manji.ir`)

- `routes/api.php`
  - Subscription:
    - `GET /api/v1/subscriptions/plans`
    - `POST /api/v1/subscriptions[...]`
    - `GET /api/v1/subscriptions/current`
  - Payment:
    - `POST /api/v1/payments/initiate` → `Api\PaymentController@initiate`
    - `POST /api/v1/payments/verify` → `Api\PaymentController@verify`
    - `GET  /api/v1/payments/history` → `Api\PaymentController@history`
  - Coupons:
    - `POST /api/v1/coupons/validate`
    - `POST /api/v1/coupons/use`

- `app/Http/Controllers/Api/PaymentController.php`
  - `initiate(Request $request)`:
    - Validates `subscription_id`.
    - Creates a `Payment` row (`status = pending`, `payment_method = zarinpal`, `transaction_id = PAY_...`).
    - Calls `PaymentService::initiateZarinPalPayment(...)`.
    - Returns `payment_url` + `authority` to client.
  - `verify(Request $request)`:
    - Validates `authority` + `status`.
    - Looks up matching `Payment` (pending, zarinpal).
    - For non–`OK` status: marks payment `cancelled`.
    - For `OK`: calls `PaymentService::verifyZarinPalPayment(...)`.
    - On success:
      - marks `Payment` as `completed`,
      - activates `Subscription` (status `active`, sets `start_date`, `end_date`, `transaction_id`, etc).
  - `history(Request $request)`:
    - Returns paginated list of user payments + `subscription` relation.

> **Conclusion:** Backend is already the right place for real payment processing and subscription activation; the app should not own any business logic beyond redirection and display.

---

## 3. Target Architecture

### 3.1 Responsibilities Split

- **Website (`my.manji.ir`, Laravel):**
  - Full checkout UI:
    - select plan,
    - enter and validate coupon code,
    - calculate and display final price,
    - call `/payments/initiate` and send user to Zarinpal,
    - handle payment callbacks and confirmation (`/payments/verify`),
    - optionally redirect back to the app via deep link.

- **Flutter app (`manji-flutter`):**
  - For starting a purchase:
    - open `https://my.manji.ir/checkout?...` in external browser (or SFSafariView/CustomTabs; not an API call).
  - For reflecting state:
    - show **current subscription**: `GET /subscriptions/current` (or mobile-specific endpoint),
    - show **payment history**: `GET /payments/history`.
  - For showing payment result:
    - handle deep links like `manji://payment/success?...` or `manji://payment/failure?...` and navigate to `PaymentSuccessPage` / `PaymentFailurePage`.

---

## 4. Backend Tasks (`manji-laravel`, Website)

### 4.1 Define / Stabilize Website Checkout Page

**Goal:** A canonical web checkout page that handles all payment UX.

**Suggested URL:**

- `GET https://my.manji.ir/checkout`
  - Query parameters:
    - `source=app|web`
    - `plan_slug=...` (optional; if app preselects a plan)
    - `return_scheme=manji` (or `return_url=manji://payment/...`) for deep link back to app
    - optional analytics params: `utm_source=app`, `utm_campaign=subscription`, etc.

**Tasks:**

- [ ] Implement or confirm existence of `/checkout` page.
- [ ] Checkout page must:
  - [ ] Load plans via `/api/v1/subscriptions/plans`.
  - [ ] Render plan selection and plan summary.
  - [ ] Provide coupon input that uses `/api/v1/coupons/validate` and `/api/v1/coupons/use`.
  - [ ] On “Pay”:
    - [ ] Make sure a `Subscription` exists (`/api/v1/subscriptions[...]`).
    - [ ] Call `POST /api/v1/payments/initiate` from the **website**, not from the app.
    - [ ] Redirect user to the **Zarinpal** URL from `payment_url`.

### 4.2 Coupons: Keep Everything on Website

**Goal:** Validation & application of coupon codes only inside the website’s checkout.

**Tasks:**

- [ ] Audit usage of:
  - `POST /api/v1/coupons/validate`
  - `POST /api/v1/coupons/use`
  - in the Flutter app (see Flutter tasks in section 5).
- [ ] Ensure web checkout:
  - [ ] Uses `validate` to:
    - check code validity,
    - compute final amount, discount, commission, etc.
  - [ ] Uses `use` to:
    - link coupon usage to subscription/payment once user proceeds.
- [ ] (Optional security hardening):
  - [ ] If needed, distinguish web checkout vs. other clients via headers (`User-Agent`, custom `X-Client-Type: web_checkout`, etc.) or auth scopes.

### 4.3 Callback & Deep Link Handling for App

**Goal:** After payment on the website, if the user started from the app, they should be able to jump back into the app with proper context.

**Tasks:**

- [ ] In the payment callback/controller (e.g. `PaymentCallbackController` or equivalent route):
  - For **app-originated** flows (based on `return_scheme` / `return_url` or `source=app`):
    - On **success**:
      - [ ] Redirect user to a deep link like:
        - `manji://payment/success?transactionId=...&amount=...&paymentId=...&subscriptionId=...&timestamp=...`
    - On **failure/cancel**:
      - [ ] Redirect to:
        - `manji://payment/failure?error=...&timestamp=...`
  - For **pure web flows** (no deep link info):
    - [ ] Show normal HTML success/failure pages.
- [ ] Align deep link payload fields with Flutter models:
  - `DeepLinkSuccessData` expects:
    - `transactionId`, `paymentId`, `subscriptionId`, `amount`, `timestamp`, etc.
  - `DeepLinkFailureData` expects:
    - `error`, `timestamp`, optionally `paymentId`/`subscriptionId`.

### 4.4 Usage Rules for Payment APIs

**Design rule (for documentation and future changes):**

- `POST /api/v1/payments/initiate`:
  - Only the **website checkout** should call this.
- `POST /api/v1/payments/verify`:
  - Should only be called by the **server-side callback flow** or web checkout.
- Coupons:
  - `POST /api/v1/coupons/validate` & `POST /api/v1/coupons/use`:
    - Should be used by **website**, not by mobile client logic.
- The app may call:
  - `GET /api/v1/subscriptions/current` or mobile equivalents (status only).
  - `GET /api/v1/payments/history` (read-only).

If needed in the future, consider:

- Restricting these write endpoints to specific OAuth client IDs / access tokens used by the web app.

---

## 5. Flutter Tasks (`manji-flutter`)

### 5.1 Remove / Disable In‑App Checkout Screen

**Files:**

- `presentation/pages/payment_page.dart`
- Any navigation points that push `PaymentPage`.

**Tasks:**

- [ ] Find all usages of `PaymentPage`:
  - e.g. from profile page, subscription CTA, etc.
- [ ] Replace navigation to `PaymentPage` with the **new behavior** (section 5.2: open website).
- [ ] Decide what to do with `PaymentPage`:
  - Option A (recommended): **Remove** the file and all related code.
  - Option B: Keep only as an informational screen that:
    - explains “Purchases are completed on `my.manji.ir`”,
    - has a single button “Open Website” that uses `url_launcher` to open checkout URL.

### 5.2 Replace In‑App Payment Logic with “Open Website”

**Goal:** For any “Buy subscription” or “Renew” button, open website checkout instead of using the API.

**Tasks:**

- [ ] Identify all CTAs like:
  - “خرید اشتراک”, “ارتقاء به پریمیوم”, etc.
- [ ] For each:
  - [ ] Use `url_launcher` (or platform-appropriate launcher) to open:
    - `https://my.manji.ir/checkout?source=app[&plan_slug=xyz][&return_scheme=manji]`
  - [ ] If user has pre-selected a plan in app:
    - include `plan_slug` so website can preselect the same plan.
- [ ] Optionally, pass tracking info:
  - `utm_source=app&utm_campaign=subscription`.

> The app **must not** call `/payments/initiate` or `/payments/verify` directly anymore.

### 5.3 Remove Coupon & Discount Logic from App

**Files mostly affected:**

- `payment_page.dart` (promo code UI & logic).
- Any `CouponService` implementation in Flutter (if present separately).

**Tasks:**

- [ ] Remove promo code input and all associated UI from `PaymentPage` or any other screen.
- [ ] Remove (or deprecate) any methods that:
  - call `/coupons/validate`,
  - call `/coupons/use`,
  - compute discount totals client-side.
- [ ] Add clear comments in code where necessary:
  - “Coupon codes are handled only on `my.manji.ir` checkout.”

### 5.4 Keep Read‑Only Subscription & History UI

**Files:**

- `payment_history_page.dart`
- Any screens showing current subscription in profile/settings.

**Tasks:**

- [ ] Confirm that these screens:
  - only call **read-only** endpoints:
    - `GET /payments/history`,
    - `GET /subscriptions/current` or mobile equivalent,
  - do not try to:
    - create subscriptions,
    - apply coupons,
    - initiate payments.
- [ ] If these screens have **Pay / Renew / Upgrade** actions:
  - [ ] Change them to open website checkout (section 5.2) instead of any local payment logic.

### 5.5 Deep Link Handling: Show Success / Failure Pages

Existing pages:

- `PaymentSuccessPage` (with `DeepLinkSuccessData`).
- `PaymentFailurePage` (with `DeepLinkFailureData`).

**Tasks:**

- [ ] In the deep-link handler (per `FLUTTER_DEEP_LINK_IMPLEMENTATION.md` / `FLUTTER_DEEP_LINK_INTEGRATION.md`):
  - [ ] Parse:
    - `manji://payment/success?...`
    - `manji://payment/failure?...`
  - [ ] Map query parameters to:
    - `DeepLinkSuccessData` (transactionId, paymentId, subscriptionId, amount, timestamp, etc.)
    - `DeepLinkFailureData` (error, timestamp, optional IDs).
  - [ ] Navigate to:
    - `PaymentSuccessPage(data: ...)` on success,
    - `PaymentFailurePage(data: ...)` on failure.
- [ ] After showing success:
  - [ ] Trigger a lightweight refresh:
    - `GET /subscriptions/current`,
    - `GET /payments/history`,
    - so UI reflects the new subscription status and latest payment entry.

### 5.6 Clean Up Payment Services in App

**Files:**

- `core/services/payment_service.dart`
- `core/services/secure_payment_service.dart`

**Tasks:**

- `payment_service.dart`:
  - [ ] Remove or stop using:
    - `initiatePayment`,
    - `verifyPayment`.
  - [ ] Keep (if needed) only **read-only** helpers:
    - `getPaymentHistory` for `PaymentHistoryPage`.
  - [ ] Update any comments to say:
    - “Payment initiation and verification are handled on `my.manji.ir`. This service only reads history.”

- `secure_payment_service.dart`:
  - This file currently simulates a full gateway client (own gateway URL, encryption, fraud detection, etc.).
  - [ ] Check if it is used anywhere in production code:
    - If **not used**:
      - [ ] Mark as deprecated or remove to avoid confusion (preferred).
    - If **used**:
      - [ ] Replace all usages with:
        - the new pattern: open website + receive result via deep link,
        - and remove direct gateway processing logic.

---

## 6. Design Rules for Future Changes

To prevent re-introduction of in-app payments, enforce these rules in code review and documentation:

### Rule 1 – Payment Location

> **All** plan selection, coupon entry, price calculation, and gateway interaction **must** occur on `my.manji.ir` (Laravel).  
> The Flutter app must never directly collect card/payment details or talk to payment gateways.

### Rule 2 – App Responsibilities

- The app **may**:
  - open `https://my.manji.ir/checkout?...` in a browser,
  - show payment result pages (`PaymentSuccessPage` / `PaymentFailurePage`),
  - show subscription status and payment history from read-only APIs.
- The app **may not**:
  - create subscriptions by itself,
  - validate or apply coupon codes,
  - call `/payments/initiate` or `/payments/verify` directly.

### Rule 3 – Payment API Usage

- **Write endpoints – website only:**
  - `POST /api/v1/payments/initiate`
  - `POST /api/v1/payments/verify`
  - `POST /api/v1/coupons/validate`
  - `POST /api/v1/coupons/use`
- **Read-only endpoints – app allowed:**
  - `GET /api/v1/subscriptions/current` (or mobile-specific endpoints),
  - `GET /api/v1/payments/history`.

### Rule 4 – Deep Link Contract

- Official flow when starting purchase from app:

  ```text
  Flutter app
    → open https://my.manji.ir/checkout?source=app&return_scheme=manji
      → Zarinpal
      → my.manji.ir callback
      → manji://payment/success|failure?... deep link
      → Flutter app (PaymentSuccessPage / PaymentFailurePage)
  ```

- Any change to:
  - deep link path (`payment/success`, `payment/failure`),
  - or its query parameters,
  must be reflected in both:
  - Laravel deep link generation,
  - Flutter `DeepLinkSuccessData` / `DeepLinkFailureData` parsing.

### Rule 5 – Required Tests for Any Payment Change

Before merging any change in:

- `PaymentController`, `SubscriptionController`, `CouponController` (Laravel),
- any `*payment*` / `*coupon*` service or page in Flutter,

run this checklist:

- [ ] App has **no** forms for card number, CVV, or coupon code.
- [ ] App only opens `https://my.manji.ir` URLs for purchase/renewal.
- [ ] Deep links `manji://payment/success|failure` still navigate correctly and display expected data.
- [ ] No direct calls from app to:
  - `/payments/initiate`,
  - `/payments/verify`,
  - `/coupons/validate`,
  - `/coupons/use`.

---

## 7. Summary

- Laravel (`my.manji.ir`) is the **source of truth** for all checkout, coupons, and payment gateway integrations.
- The Flutter app is a **viewer + launcher**:
  - launches web checkout,
  - shows results and state from read-only APIs.
- This document is the canonical reference for any future payment-related changes in both `manji-laravel` and `manji-flutter`.

---

## 8. Progress Log (Implementation Status)

Last updated: **2025-11-26**

- **Flutter – subscription CTAs now open website checkout**
  - [x] `SubscriptionPage` now opens `https://my.manji.ir/checkout?source=app&plan_slug=...&utm_source=app&utm_campaign=subscription` in the external browser instead of running an in-app checkout flow.
  - [x] `SubscriptionPlansPage` “خرید ...” buttons now open the same checkout URL (no navigation to in-app confirmation/payment pages).
- **Flutter – in-app coupon & price calculation removed from main subscription entry points**
  - [x] `SubscriptionPage` no longer uses `CouponService` or any in-app coupon/discount UI; it delegates all coupons and price calculation to the website.
  - [x] `SubscriptionPage` no longer has a local “پرداخت” button; it only shows plans and a single “ادامه خرید در وب‌سایت” action.
- **Flutter – read-only payment views kept**
  - [x] `PaymentHistoryPage` still uses read-only history APIs only (no initiation/verification calls).
- **Still pending (per sections above)**
  - [x] Implement `/checkout` page on `my.manji.ir`:
    - [x] `GET /checkout` (auth required) → `CheckoutController@index` + `checkout/index.blade.php`:
      - [x] loads active `SubscriptionPlan`s,
      - [x] supports `?plan_slug=...` and `?source=app|web`,
      - [x] renders plan list + coupon input UI + payment summary.
    - [x] `POST /checkout` (auth required) → `CheckoutController@store`:
      - [x] validates `plan_id`,
      - [x] creates `Subscription` with status `pending` for current user based on selected `SubscriptionPlan`,
      - [x] creates `Payment` with status `pending` and method `zarinpal`,
      - [x] calls `PaymentService::initiateZarinPalPayment` and redirects user to Zarinpal `payment_url`.
  - [x] Wire Zarinpal callback + deep-link redirects (`manji://payment/success|failure?...`) as described in section 4.3:
    - [x] When checkout starts from app, `CheckoutController@store` stores `source=app` and `return_scheme=manji` in `Payment.metadata`.
    - [x] `PaymentCallbackController@zarinpalCallback`:
      - [x] Reads `source` / `return_scheme` from `Payment.metadata`.
      - [x] On success and `source=app`, redirects to  
        `manji://payment/success?transactionId=...&paymentId=...&subscriptionId=...&amount=...&currency=...&timestamp=...`.
      - [x] On failure and `source=app`, redirects to  
        `manji://payment/failure?paymentId=...&subscriptionId=...&error=...&timestamp=...`.
      - [x] For pure web flows (no `source=app`), keeps existing HTML success/failure pages.
  - [x] Remove or fully deprecate in-app payment flows:
    - [x] Removed direct calls from Flutter to:
      - [x] `POST /payments/initiate` and `POST /payments/verify` (`PaymentService` / `SubscriptionService` now only provide read-only helpers like history/status).
    - [x] Marked legacy in-app payment classes as deprecated (kept only for reference, not used by navigation):
      - [x] `PaymentPage` (full in-app checkout UI).
      - [x] `PlanConfirmationPage` (in-app confirmation + coupon + purchase flow).
      - [x] `SecurePaymentService` (local gateway client with encryption/fraud checks).
      - [x] `SubscriptionManagementService` (local subscription + payment lifecycle).


