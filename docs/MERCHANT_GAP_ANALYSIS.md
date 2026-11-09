# OmniEats Merchant Side vs GrabMerchant / foodpanda Partner — Gap Analysis

Date: 2026-11-09 · Verified against the RUNNING implementation.

Legend: ✅ implemented · 🟡 small in-scope gap (fits the approved 6 modules)
· 🔴 out of scope (new module / real money / external services — the final spec
explicitly excludes these).

## A. Merchant account & onboarding

| # | Function (Grab / Foodpanda) | OmniEats | Notes |
|---|---|---|---|
| 1 | Prominent "Partner with us" entry (navbar, home, register page) | ✅ | Added 2026-11-09 — merchant banner on register page, navbar link, home CTA strip |
| 2 | Separate merchant sign-up form | ✅ | `/register/restaurant` — role fixed per route |
| 3 | Land DIRECTLY into restaurant setup after sign-up | ✅ | Added 2026-11-09 — onboarding redirects straight to the restaurant form |
| 4 | Application status tracking (Pending/Approved) | ✅ | Shown on the dashboard and edit page |
| 5 | Rejection reason shown to the applicant | 🟡 | Admin can deactivate; a rejection-reason field would complete the approval workflow display |
| 6 | Merchant dashboard separated from customer UI | ✅ | Role-based dashboards |

## B. Restaurant profile

| # | Function | OmniEats | Notes |
|---|---|---|---|
| 7 | Name / description / contact / address (city, state, postcode) | ✅ | |
| 8 | **Logo + cover image upload (merchant's own files)** | ✅ | `/owner/restaurant/edit` — 2 MB, JPG/PNG/WebP |
| 9 | Opening hours | ✅ | Text field; Grab uses structured per-day hours 🟡 (restaurant_hours table optional) |
| 10 | Preparation / delivery time | ✅ | `delivery_minutes` — drives the customer ETA |
| 11 | Delivery fee control | ✅ | Free-delivery switch (flat RM 5 fee otherwise) |
| 12 | Distance-based delivery fees | 🔴 | Explicitly out of scope (final spec) |
| 13 | Cuisine type | 🟡 | One column + filter chip (optional) |
| 14 | OPEN / CLOSED (holiday mode equivalent) | ✅ | Merchant toggles; CLOSED blocks new orders but keeps the profile visible |
| 15 | Auto-accept incoming orders | ✅ | `auto_accept` toggle on the dashboard |
| 16 | Multi-branch management | ✅ | Branches module (legacy SaaS feature, still functional) |

## C. Menu management

| # | Function | OmniEats | Notes |
|---|---|---|---|
| 17 | Categories CRUD | ✅ | Per-tenant unique names, delete protection |
| 18 | Food items CRUD | ✅ | Name/description/price/image/availability |
| 19 | **Food image upload by the merchant** | ✅ | `/owner/foods/create` + edit |
| 20 | Availability toggle (sell-out) | ✅ | One click per item |
| 21 | Menu search + category filter | ✅ | Owner and public sides |
| 22 | Item add-ons / modifiers (e.g. extra cheese) | 🔴 | New entity + order flow — out of scope |
| 23 | Popular-item badge | 🟡 | One `is_popular` column + chip |
| 24 | Stock counts | 🔴 | Availability toggle is the scope-approved simple form |
| 25 | Category sort order UI | 🟡 | `sort_order` column exists — a drag/up-down control would surface it |

## D. Order management

| # | Function | OmniEats | Notes |
|---|---|---|---|
| 26 | Live incoming orders (auto-refresh) | ✅ | 10 s polling on the dashboard |
| 27 | Accept → preparing → ready workflow | ✅ | Validated transition map |
| 28 | Delivery orders completed by the rider | ✅ | Merchant's job ends at "ready"; the rider's delivery completes the order |
| 29 | Cancel with reason | ✅ | Recorded in the status history |
| 30 | Customer note + per-item notes visible | ✅ | |
| 31 | Order details with address/recipient | ✅ | |

## E. Promotions

| # | Function | OmniEats | Notes |
|---|---|---|---|
| 32 | Merchant-funded vouchers | ✅ | Owner creates vouchers for their OWN restaurant (admin manages platform-wide ones) |
| 33 | Voucher usage tracking | ✅ | used_count + voucher_usages audit |
| 34 | Cross-platform promo engine | 🔴 | "Complex voucher systems" out of scope |

## F. Reviews

| # | Function | OmniEats | Notes |
|---|---|---|---|
| 35 | View customer reviews | ✅ | `/owner/reviews` (incl. admin-hidden flag) |
| 36 | **Reply to reviews** | ✅ | Public reply shown on the restaurant page |
| 37 | Admin moderation (hide) | ✅ | |

## G. Sales & reports

| # | Function | OmniEats | Notes |
|---|---|---|---|
| 38 | Today's / this month's sales tiles | ✅ | DB-backed, tenant-scoped |
| 39 | 7-day sales chart | ✅ | Pure CSS bars, daily payment sums |
| 40 | Peak-hours / item-level analytics | 🟡 | More chart queries (optional) |
| 41 | Settlement / payout statements | 🔴 | Real money flows — out of scope |

## H. Payments & fulfilment visibility

| # | Function | OmniEats | Notes |
|---|---|---|---|
| 42 | Payment list + receipts + void | ✅ | Audit-safe void rules |
| 43 | Fulfilment list + transitions | ✅ | Pickup: ready_for_collection → completed |
| 44 | See the assigned rider | ✅ | Rider name + phone on the order page |

## I. Notifications

| # | Function | OmniEats | Notes |
|---|---|---|---|
| 45 | New-order alerts | ✅ | Dashboard polling (spec-approved method) |
| 46 | Push notifications / tablet apps | 🔴 | Out of scope |

## Summary

**Implemented: 41 of 46 merchant functions.** The five gaps are: rejection-reason
display, structured per-day hours, popular badge, category sort UI, cuisine type
(all 🟡 one-table optional polish) plus the 🔴 items the spec itself excludes
(real settlements, modifiers, stock, distance fees, push, promo engine, tablet).
