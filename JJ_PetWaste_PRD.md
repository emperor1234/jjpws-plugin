# Product Requirements Document (PRD)
## JJ Pet Waste Services — Smart Booking & Subscription System
**Version:** 1.0.0  
**Prepared For:** jonahdirrim (Client)  
**Prepared By:** William (Developer)  
**Date:** April 27, 2026  
**Status:** Active Development  
**Delivery Target:** April 29, 2026  

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Goals & Success Criteria](#2-goals--success-criteria)
3. [Technical Stack](#3-technical-stack)
4. [System Architecture](#4-system-architecture)
5. [Feature Specifications](#5-feature-specifications)
   - 5.1 Smart Booking Form
   - 5.2 Address & Lot Size Lookup
   - 5.3 Pricing Engine
   - 5.4 Authentication & Account Flow
   - 5.5 Stripe Subscription Integration
   - 5.6 Customer Management Dashboard
6. [Pricing Matrix](#6-pricing-matrix)
7. [Database Schema](#7-database-schema)
8. [API Contracts](#8-api-contracts)
9. [UI/UX Flow](#9-uiux-flow)
10. [Security & Validation Rules](#10-security--validation-rules)
11. [Error Handling Strategy](#11-error-handling-strategy)
12. [Plugin File Structure](#12-plugin-file-structure)
13. [Deliverables Checklist](#13-deliverables-checklist)
14. [Out of Scope](#14-out-of-scope)
15. [Revision & Bug Policy](#15-revision--bug-policy)

---

## 1. Project Overview

**Business:** JJ Pet Waste Services — a recurring poop scooping service billed monthly.  
**Website:** [jjpetwasteservices.com](https://jjpetwasteservices.com) (WordPress + Elementor)  
**Placement:** The entire feature lives on the `/book` page (NOT the `/pricing` page — client confirmed change on April 22).

### What We Are Building

A fully custom WordPress plugin (zero dependency on Gravity Forms or similar SaaS form builders) that:

1. Presents a multi-step booking form on the **Book** page.
2. Accepts customer address → resolves lot size via a **free/low-cost geocoding + parcel API**.
3. Accepts number of dogs and service frequency selection.
4. Calculates the monthly subscription cost in **real-time** using a configurable pricing matrix stored in the WordPress database.
5. Gates checkout behind **WordPress account authentication** — unauthenticated users are redirected to register/login before completing a booking.
6. Creates a **Stripe recurring subscription** upon confirmed checkout.
7. Saves the subscription record to the customer's WordPress account for self-service cancellation.
8. Exposes a **private WordPress admin dashboard** where the business owner can view all customers, contact details, lot sizes, dog counts, service plans, and subscription statuses.

---

## 2. Goals & Success Criteria

| Goal | Measurable Success Criterion |
|---|---|
| Replace manual quoting | Customer receives an accurate monthly price with zero owner involvement |
| Automate recurring billing | Stripe subscription created and first charge processed without manual steps |
| Self-service cancellation | Customer can cancel from their account; Stripe subscription cancelled automatically |
| Owner visibility | Admin dashboard lists every customer with full service + contact details |
| No SaaS form fees | No Gravity Forms, WPForms Pro, or equivalent annual subscription required |
| Pricing flexibility | Owner can update the pricing matrix from the WP admin without touching code |

---

## 3. Technical Stack

| Layer | Technology | Notes |
|---|---|---|
| CMS | WordPress (latest stable) | Existing site |
| Page Builder | Elementor | Plugin renders inside an Elementor HTML widget or shortcode |
| Plugin Language | PHP 8.1+ | OOP, namespaced, PSR-4 autoloaded |
| Frontend | Vanilla JS (ES6+) + CSS3 | No jQuery dependency; keeps footprint small |
| Address / Lot Size | Google Maps Geocoding API (free tier) + Regrid API (free tier) OR Zimas/county parcel data | Free tier; fallback to manual entry if lookup fails |
| Payments | Stripe PHP SDK (v10+) | Recurring subscriptions via Stripe Billing |
| Auth | WordPress native `wp_login_form`, `wp_register_form`, `wp_create_user` | No third-party auth plugin required |
| Database | WordPress `$wpdb` custom tables + `wp_usermeta` | See schema section |
| Email | `wp_mail()` + WP SMTP (existing) | Confirmation emails |

> **Cost commitment:** All APIs used are kept to free tier. Stripe charges only transaction fees (no monthly platform fee).

---

## 4. System Architecture

```
[Book Page - Elementor]
        |
        v
[Shortcode: jjpws_booking_form]
        |
        +-------> [Step 1: Address Input]
        |               |
        |               v
        |         [AJAX: jjpws_lookup_lot_size]
        |               |
        |         [Geocoding API -> Parcel API]
        |               |
        |         Returns: lot_size_sqft, lot_size_category
        |
        +-------> [Step 2: Dog Count + Service Frequency]
        |               |
        |               v
        |         [AJAX: jjpws_calculate_price]
        |               |
        |         [PricingEngine::calculate()]
        |               |
        |         Returns: monthly_price (real-time preview)
        |
        +-------> [Step 3: Review & Checkout]
                        |
                        v
              [Auth Check: is_user_logged_in()]
                /               \
             YES                 NO
              |                   |
              v                   v
    [Stripe Checkout]   [Redirect to Register/Login]
              |                   |
              v                   v
    [Stripe Subscription      [Return to Step 3
     Created via API]          after auth]
              |
              v
    [Save: jjpws_subscriptions table]
    [Save: wp_usermeta for customer profile]
              |
              v
    [Confirmation Email -> Customer]
    [Admin Notification Email]
```

---

## 5. Feature Specifications

---

### 5.1 Smart Booking Form

**Location:** `/book` page via shortcode `[jjpws_booking_form]`  
**Type:** Multi-step (3 steps), single-page with JS step transitions (no full page reload)

#### Step 1 — Service Address

| Field | Type | Validation | Notes |
|---|---|---|---|
| Street Address | Text input with autocomplete | Required, min 5 chars | Google Places Autocomplete (JS) |
| City | Text | Required | Auto-populated from Places API |
| State | Text | Required | Auto-populated |
| ZIP Code | Text | Required, 5-digit US ZIP | Auto-populated |

- On address selection or manual entry submission, an AJAX call fires to resolve lot size.
- A loading indicator shows while the API resolves.
- If the API fails or returns no result, the system falls back to a **manual lot size selection dropdown** (Small / Medium / Large / Extra Large) with a helper tooltip explaining typical ranges.
- The resolved (or manually selected) lot size category is stored in a hidden field for the pricing engine.

#### Step 2 — Service Details

| Field | Type | Validation | Notes |
|---|---|---|---|
| Number of Dogs | Number input (stepper) | Required, integer, min 1, max 10 | Inline real-time price update on change |
| Service Frequency | Radio button group | Required, one selection | Options: Twice-a-Week, Weekly, Bi-Weekly |

- Price preview updates in real-time via debounced AJAX (300ms debounce) whenever dog count or frequency changes.
- Price preview is displayed as: **"Estimated Monthly Cost: $XX.00"** below the fields.

#### Step 3 — Review & Confirm

- Displays a read-only summary: address, lot size category, number of dogs, frequency, and monthly price.
- A **"Complete Booking"** CTA button triggers the auth check.
- If not authenticated: stores form state in `sessionStorage` and redirects to `/register` or `/login` page with a `?redirect_to=/book` query param so the user returns seamlessly after auth.
- If authenticated: triggers Stripe Checkout session creation.

---

### 5.2 Address & Lot Size Lookup

**Purpose:** Automatically determine the lot size category for the entered address so pricing can be applied correctly.

#### Lookup Flow

```
Customer enters address
        |
        v
Google Maps Geocoding API
→ Returns: lat, lng, formatted_address
        |
        v
Regrid Parcel API (free tier, 1,000 req/month)
OR OpenStreetMap Nominatim (100% free, slower)
→ Returns: parcel area in sq ft
        |
        v
LotSizeClassifier::classify(sqft)
→ Returns: lot_size_category (enum)
        |
        v
Stored in hidden form field + displayed to customer
```

#### Lot Size Categories

These map to pricing tiers. The exact square footage thresholds are configurable via the admin pricing settings.

| Category | Default Range (sq ft) | Display Label |
|---|---|---|
| `xs` | 0 – 2,999 | Under 3,000 sq ft |
| `sm` | 3,000 – 5,999 | 3,000 – 6,000 sq ft |
| `md` | 6,000 – 9,999 | 6,000 – 10,000 sq ft |
| `lg` | 10,000 – 17,999 | 10,000 – 18,000 sq ft |
| `xl` | 18,000+ | 18,000+ sq ft |

#### API Fallback Hierarchy

1. **Regrid API** (primary — returns precise parcel sq ft)
2. **Google Maps + estimated residential average** (secondary — if Regrid has no parcel data for the address)
3. **Manual selection by customer** (final fallback — a dropdown appears with lot size categories)

> The fallback must never block the customer from completing checkout. If lot size cannot be determined automatically, the manual dropdown activates silently without error messaging.

---

### 5.3 Pricing Engine

**Class:** `JJPWS\Core\PricingEngine`  
**Access:** Called server-side only. Never expose the raw matrix to the client.

#### How the Engine Works

The pricing engine accepts three inputs and returns a monthly price in cents (integer, to avoid floating-point issues):

```
PricingEngine::calculate(
    lot_size_category: string,   // 'xs' | 'sm' | 'md' | 'lg' | 'xl'
    dog_count: int,              // 1–10
    frequency: string            // 'twice_weekly' | 'weekly' | 'biweekly'
) : int  // price in cents, e.g. 4500 = $45.00
```

#### Calculation Formula

```
monthly_price = base_price[lot_size][frequency]
              + (dog_adder[frequency] * (dog_count - 1))
```

- `base_price` = price for the lot size + frequency combination for **1 dog**.
- `dog_adder` = additional cost per **additional dog** (above the first).
- All values stored in `wp_options` as a serialized JSON matrix under key `jjpws_pricing_matrix`.

> **Note to client:** The exact dollar values in the pricing matrix below are placeholders. You must review and update them in the WordPress admin before going live. The system is built so you can change any value at any time without developer involvement.

---

## 6. Pricing Matrix

### Base Price Table (1 Dog, Monthly)

| Lot Size | Twice-a-Week | Weekly | Bi-Weekly |
|---|---|---|---|
| Under 3,000 sq ft | $60.00 | $40.00 | $25.00 |
| 3,000 – 6,000 sq ft | $75.00 | $50.00 | $32.00 |
| 6,000 – 10,000 sq ft | $90.00 | $60.00 | $38.00 |
| 10,000 – 18,000 sq ft | $110.00 | $75.00 | $48.00 |
| 18,000+ sq ft | $130.00 | $90.00 | $58.00 |

### Per Additional Dog Adder (Monthly)

| Frequency | Add Per Extra Dog |
|---|---|
| Twice-a-Week | +$15.00 |
| Weekly | +$10.00 |
| Bi-Weekly | +$7.00 |

### Example Calculation

> Customer has: Medium lot (6,000–10,000 sq ft), 3 dogs, Weekly service.
>
> Base price (1 dog, weekly, md) = $60.00  
> Extra dogs = 2 × $10.00 = $20.00  
> **Monthly total = $80.00**

The admin can update every cell of this matrix from **WordPress Admin → JJ Pet Waste → Pricing Settings** without writing any code.

---

## 7. Database Schema

All tables use the WordPress table prefix (e.g., `wp_`). Tables are created on plugin activation via `dbDelta()`.

### Table: `{prefix}jjpws_subscriptions`

Stores every subscription record created through the booking form.

```sql
CREATE TABLE {prefix}jjpws_subscriptions (
    id                  BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id             BIGINT(20) UNSIGNED NOT NULL,          -- FK to wp_users.ID
    stripe_customer_id  VARCHAR(255) NOT NULL,                 -- cus_xxxx
    stripe_sub_id       VARCHAR(255) NOT NULL,                 -- sub_xxxx
    stripe_price_id     VARCHAR(255) NOT NULL,                 -- price_xxxx (Stripe Price object)
    street_address      VARCHAR(255) NOT NULL,
    city                VARCHAR(100) NOT NULL,
    state               VARCHAR(50)  NOT NULL,
    zip_code            VARCHAR(10)  NOT NULL,
    lat                 DECIMAL(10,7) DEFAULT NULL,
    lng                 DECIMAL(10,7) DEFAULT NULL,
    lot_size_sqft       INT(11) UNSIGNED DEFAULT NULL,
    lot_size_category   VARCHAR(10) NOT NULL,                  -- xs|sm|md|lg|xl
    dog_count           TINYINT(3) UNSIGNED NOT NULL,
    frequency           VARCHAR(20) NOT NULL,                  -- twice_weekly|weekly|biweekly
    monthly_price_cents INT(11) UNSIGNED NOT NULL,             -- stored in cents
    status              VARCHAR(20) NOT NULL DEFAULT 'active', -- active|cancelled|past_due
    stripe_status       VARCHAR(50) DEFAULT NULL,              -- raw Stripe status
    current_period_end  DATETIME DEFAULT NULL,                 -- next billing date
    cancelled_at        DATETIME DEFAULT NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_user_id (user_id),
    KEY idx_status (status),
    KEY idx_stripe_sub_id (stripe_sub_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### `wp_usermeta` Keys (per user)

| Meta Key | Type | Description |
|---|---|---|
| `jjpws_stripe_customer_id` | string | Stripe customer ID, saved on first checkout |
| `jjpws_phone` | string | Collected at registration (optional enhancement) |

### `wp_options` Keys

| Option Name | Type | Description |
|---|---|---|
| `jjpws_pricing_matrix` | JSON | Full pricing matrix (editable in admin) |
| `jjpws_lot_size_thresholds` | JSON | Sq ft boundaries for each category |
| `jjpws_stripe_mode` | string | `'test'` or `'live'` |
| `jjpws_api_keys` | serialized | Encrypted API keys for Regrid, Google Maps |

---

## 8. API Contracts

### 8.1 AJAX: Lot Size Lookup

**Action:** `jjpws_lookup_lot_size`  
**Method:** POST  
**Auth:** None required (pre-login step)  
**Nonce:** `jjpws_nonce`

**Request:**
```json
{
  "nonce": "abc123",
  "street": "123 Main St",
  "city": "Springfield",
  "state": "IL",
  "zip": "62701"
}
```

**Success Response:**
```json
{
  "success": true,
  "data": {
    "lot_size_sqft": 7200,
    "lot_size_category": "md",
    "lot_size_label": "6,000 – 10,000 sq ft",
    "source": "regrid",
    "lat": 39.7817,
    "lng": -89.6501
  }
}
```

**Fallback Response (API miss):**
```json
{
  "success": true,
  "data": {
    "lot_size_sqft": null,
    "lot_size_category": null,
    "lot_size_label": null,
    "source": "manual_required",
    "message": "We couldn't auto-detect your lot size. Please select it below."
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "data": {
    "code": "INVALID_ADDRESS",
    "message": "The address could not be verified. Please check and try again."
  }
}
```

---

### 8.2 AJAX: Price Calculation

**Action:** `jjpws_calculate_price`  
**Method:** POST  
**Auth:** None required (price preview is public)  
**Nonce:** `jjpws_nonce`

**Request:**
```json
{
  "nonce": "abc123",
  "lot_size_category": "md",
  "dog_count": 3,
  "frequency": "weekly"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "monthly_price_cents": 8000,
    "monthly_price_formatted": "$80.00",
    "breakdown": {
      "base_price_cents": 6000,
      "extra_dogs_adder_cents": 2000,
      "extra_dog_count": 2,
      "adder_per_dog_cents": 1000
    }
  }
}
```

---

### 8.3 AJAX: Create Stripe Checkout Session

**Action:** `jjpws_create_checkout_session`  
**Method:** POST  
**Auth:** **Required** — `is_user_logged_in()` must be true  
**Nonce:** `jjpws_nonce`

**Request:**
```json
{
  "nonce": "abc123",
  "street": "123 Main St",
  "city": "Springfield",
  "state": "IL",
  "zip": "62701",
  "lat": 39.7817,
  "lng": -89.6501,
  "lot_size_sqft": 7200,
  "lot_size_category": "md",
  "dog_count": 3,
  "frequency": "weekly",
  "monthly_price_cents": 8000
}
```

> **Security note:** The server recalculates `monthly_price_cents` independently using `PricingEngine::calculate()` and compares it against the submitted value. If they differ by more than $0.01 (rounding tolerance), the request is rejected. This prevents client-side price tampering.

**Response:**
```json
{
  "success": true,
  "data": {
    "checkout_url": "https://checkout.stripe.com/pay/cs_live_xxxx"
  }
}
```

---

### 8.4 Stripe Webhook Handler

**Endpoint:** `/wp-json/jjpws/v1/stripe-webhook`  
**Method:** POST  
**Auth:** Stripe webhook signature verification (`Stripe-Signature` header)

**Handled Events:**

| Stripe Event | Action |
|---|---|
| `checkout.session.completed` | Create subscription record in DB, send confirmation email |
| `invoice.payment_succeeded` | Update `current_period_end`, ensure status = `active` |
| `invoice.payment_failed` | Set status = `past_due`, send payment failure email to customer |
| `customer.subscription.deleted` | Set status = `cancelled`, record `cancelled_at` |
| `customer.subscription.updated` | Sync `stripe_status` field |

---

## 9. UI/UX Flow

### Happy Path (Authenticated User)

```
/book page loads
    → Form renders (Step 1 active)
    → Customer enters address
    → Lot size resolves automatically
    → Customer sets dog count (default: 1) + selects frequency
    → Real-time price preview appears
    → Customer clicks "Continue to Review"
    → Step 3 shows summary + "Complete Booking" button
    → Auth check: PASS (user is logged in)
    → AJAX creates Stripe Checkout Session
    → Customer redirected to Stripe hosted checkout
    → Customer enters card details on Stripe
    → Stripe redirects to /book?booking=success
    → Success message displayed
    → Confirmation email sent to customer
    → Admin notification email sent to owner
```

### Unauthenticated Checkout Flow

```
Customer reaches Step 3
    → Clicks "Complete Booking"
    → Auth check: FAIL (not logged in)
    → Form state saved to sessionStorage
    → Customer redirected to /register (or /login)
        with ?redirect_to=%2Fbook&jjpws_resume=1
    → Customer registers or logs in
    → Redirected back to /book
    → JS detects ?jjpws_resume=1
    → Restores form state from sessionStorage
    → Customer is now at Step 3 again, authenticated
    → Proceeds to Stripe checkout normally
```

### Cancellation Flow (Customer Self-Service)

```
Customer logs in → My Account page
    → "My Subscriptions" section (custom WP account tab)
    → Subscription listed with: service details, next billing date, status
    → "Cancel Subscription" button
    → Confirmation modal: "Are you sure? Your service ends on [date]."
    → Customer confirms
    → AJAX: jjpws_cancel_subscription
        → Stripe API: cancel at period end (not immediately)
        → DB: status = 'cancelled', cancelled_at = now()
    → Success message: "Your subscription has been cancelled. Service continues until [date]."
    → Cancellation confirmation email sent
```

---

## 10. Security & Validation Rules

Every AJAX endpoint must implement ALL of the following checks. No exceptions.

| Check | Implementation |
|---|---|
| Nonce verification | `check_ajax_referer('jjpws_nonce', 'nonce')` on every request |
| Input sanitization | `sanitize_text_field()`, `absint()`, `floatval()` before any use |
| Server-side price verification | Recalculate price from DB matrix; reject if client value doesn't match |
| Auth enforcement | `is_user_logged_in()` checked before Stripe session creation |
| Capability check (admin) | `current_user_can('manage_options')` for all admin-only endpoints |
| Stripe webhook signature | `\Stripe\Webhook::constructEvent()` with secret from `wp_options` |
| SQL injection prevention | All DB queries use `$wpdb->prepare()` with placeholders |
| XSS prevention | All output escaped with `esc_html()`, `esc_attr()`, `wp_kses_post()` |
| CSRF protection | WP nonces on all forms and AJAX calls |
| Rate limiting | Simple transient-based rate limiter: max 10 lot-size lookups per IP per hour |

---

## 11. Error Handling Strategy

### Frontend (JavaScript)

- All AJAX calls wrapped in `try/catch` with `.then()/.catch()` promise chains.
- Network failures → inline error message: "Something went wrong. Please try again."
- Lot size API failure → graceful fallback to manual selection (never blocks the form).
- Stripe session creation failure → inline error with support contact prompt.
- Validation errors → inline field-level error messages (not browser alerts).

### Backend (PHP)

- All external API calls (Geocoding, Regrid, Stripe) wrapped in try/catch.
- API failures logged to WordPress debug log (`WP_DEBUG_LOG`) with context.
- Stripe webhook failures return HTTP 400 (Stripe retries on non-200 responses).
- Unexpected exceptions return `wp_send_json_error(['code' => 'INTERNAL_ERROR', 'message' => '...'])`.
- Never expose raw exception messages or stack traces to the client.

---

## 12. Plugin File Structure

```
jjpws-booking/
├── jjpws-booking.php                    # Plugin bootstrap, header, activation hook
├── uninstall.php                        # Cleanup on plugin deletion
├── composer.json                        # Autoload config (PSR-4)
├── assets/
│   ├── css/
│   │   └── booking-form.css            # All frontend styles
│   └── js/
│       ├── booking-form.js             # Main form controller (step transitions, AJAX)
│       └── address-autocomplete.js     # Google Places Autocomplete wrapper
├── includes/
│   ├── Core/
│   │   ├── Plugin.php                  # Main plugin class, hooks registration
│   │   ├── Activator.php               # DB table creation on activation
│   │   ├── Deactivator.php             # Cleanup on deactivation
│   │   └── Loader.php                  # Action/filter hook loader
│   ├── Services/
│   │   ├── PricingEngine.php           # calculate() — reads matrix from wp_options
│   │   ├── LotSizeService.php          # resolveFromAddress() — calls Geocoding + Parcel API
│   │   ├── LotSizeClassifier.php       # classify(sqft) → category enum
│   │   ├── StripeService.php           # createCheckoutSession(), cancelSubscription()
│   │   └── EmailService.php            # sendConfirmation(), sendCancellation()
│   ├── Controllers/
│   │   ├── BookingController.php       # AJAX: lot size lookup, price calc
│   │   ├── CheckoutController.php      # AJAX: create Stripe session, post-checkout
│   │   ├── WebhookController.php       # REST API: Stripe webhook handler
│   │   ├── AccountController.php       # AJAX: cancel subscription
│   │   └── AdminController.php         # Admin page rendering and save
│   ├── Models/
│   │   └── Subscription.php            # DB read/write for jjpws_subscriptions
│   ├── Admin/
│   │   ├── AdminDashboard.php          # Customer list with filters
│   │   └── PricingSettings.php         # Editable pricing matrix UI
│   └── Frontend/
│       ├── BookingForm.php             # Shortcode: [jjpws_booking_form]
│       └── AccountTab.php              # Injects "My Subscriptions" tab in WP My Account
├── templates/
│   ├── booking-form.php                # HTML template for the multi-step form
│   ├── account-subscriptions.php       # Template for My Account subscriptions tab
│   ├── admin-dashboard.php             # Template for admin customer list
│   ├── admin-pricing.php               # Template for pricing matrix settings
│   └── emails/
│       ├── confirmation.php            # Customer booking confirmation email
│       └── cancellation.php            # Customer cancellation email
└── languages/
    └── jjpws-booking.pot               # Translation-ready strings
```

---

## 13. Deliverables Checklist

The following must ALL be complete before this project is considered delivered:

### Code Deliverables
- [ ] Complete WordPress plugin as `.zip` (installable via WP Admin → Plugins → Add New → Upload)
- [ ] Full source code in organized folder structure (as above)
- [ ] All source files provided to client for ownership (confirmed in chat April 21)
- [ ] Plugin activates without PHP errors on a standard WordPress installation
- [ ] Plugin does not conflict with Elementor or other standard WordPress plugins

### Functional Deliverables
- [ ] Multi-step booking form live on `/book` page
- [ ] Address autocomplete working
- [ ] Lot size auto-resolution working (with graceful manual fallback)
- [ ] Real-time price preview updates correctly with dog count and frequency changes
- [ ] Unauthenticated users redirected to register/login, returned to form after auth
- [ ] Form state preserved through auth redirect
- [ ] Stripe Checkout session created with correct recurring amount
- [ ] Subscription record saved to DB after successful Stripe payment
- [ ] Customer sees subscription in WP My Account
- [ ] Customer can cancel subscription from My Account
- [ ] Stripe subscription cancelled at period end on customer cancellation
- [ ] Admin dashboard shows all customers with: name, email, address, lot size, dogs, frequency, price, status, next billing date
- [ ] Pricing matrix editable from WP Admin without code changes
- [ ] Confirmation email sent to customer after booking
- [ ] Admin notification email sent after new booking

### Testing Deliverables
- [ ] Stripe test mode checkout tested end-to-end with test card `4242 4242 4242 4242`
- [ ] Webhook handling tested for: payment success, payment failure, subscription cancellation
- [ ] Edge cases tested: lot size API failure fallback, invalid address, 0-dog input rejected, >10 dogs rejected
- [ ] Tested on both mobile and desktop viewports

---

## 14. Out of Scope

The following are explicitly **not included** in this engagement:

- Scheduling or calendar/route management for the owner
- SMS notifications
- Multi-location or franchise support
- Discount / coupon code UI inside WordPress (Stripe coupons can be applied manually by owner in Stripe Dashboard)
- Any changes to the `/pricing` page (client confirmed this page is used for other purposes)
- Mobile app
- Customer-facing subscription upgrade/downgrade (v2 enhancement, quoted separately if desired)
- Integration with QuickBooks, Google Calendar, or CRM systems

---

## 15. Revision & Bug Policy

As explicitly agreed in client conversation on **April 21, 2026:**

> "If the finished product is faulty, it will be fixed without any additional cost, because the developer would have been the one to deliver a faulty product." — Confirmed by developer.

### Policy Details

**Bug Definition:** A bug is any behavior that contradicts a requirement explicitly defined in this PRD, including but not limited to: broken form steps, incorrect pricing calculations, failed Stripe integrations, missing subscription records, or broken cancellation flow.

**Revision Scope:** Bugs discovered within **30 days of final delivery** are fixed at no cost. This does not include new feature requests (e.g., adding scheduling, adding SMS), which are quoted as separate work.

**Revision Process:**
1. Client identifies the issue with a description and steps to reproduce.
2. Developer acknowledges within 24 hours.
3. Fix delivered within 48–72 hours depending on complexity.

**Excluded from no-cost revisions:**
- Changes to scope items listed in Section 14 (Out of Scope)
- Breaking changes introduced by client modifications to the delivered code
- WordPress or PHP version incompatibilities introduced after delivery

---

*Document maintained by William. Last updated: April 27, 2026.*
*Source code delivered to client upon project completion as agreed.*
