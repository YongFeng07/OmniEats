# OmniEats vs GrabFood / Foodpanda — Feature & Module Gap Analysis

TAR UMT AMCS2194 · Decision support for the team · 2026-11-08

Every row below was verified against the RUNNING OmniEats implementation and the
publicly known GrabFood / Foodpanda product feature sets.

Legend:

- ✅ **Have it** — implemented, tested, working.
- 🟡 **In-scope gap** — can be added inside the six approved modules without new
  roles, without new modules, and without breaking the assignment rules.
- 🔴 **Out-of-scope gap** — would require a new role, a new module, real payments,
  or infrastructure the assignment explicitly excludes. Listed for completeness;
  NOT recommended before submission.

Assignment constraints that frame this analysis (from the brief):

1. Exactly **six modules**, exactly **three roles** (super_admin, owner/staff, customer).
2. Each member gets **exactly one unique feature**; **M6 must have none**.
3. Payments are **simulated** — no real gateway, no wallet.
4. Order tracking via **polling** is sufficient — WebSockets/maps not required.
5. "Do not create additional independent modules… more features ≠ more marks."
6. No hardcoded demonstration data in place of backend logic.

---

## 1. Customer side

| # | Feature (Grab / Foodpanda) | OmniEats status | Notes |
|---|---|---|---|
| 1 | Home page with search | ✅ | Category chips + promo banner + featured cards (search bug fixed 2026-11-08) |
| 2 | Restaurant cards: rating, delivery time, delivery fee, badges | ✅ | Real DB columns `rating` / `delivery_minutes` / `free_delivery` |
| 3 | Restaurant listing with search + filters | ✅ | Search + OPEN/CLOSED filter |
| 4 | Restaurant detail: info, hours, branches, menu grouped by category | ✅ | With search + category filter |
| 5 | Menu category quick tabs (jump to section) | 🟡 | Minor UX — anchor links |
| 6 | Item add-ons / customisation (modifiers) | 🔴 | New entity (item_options + order_item_options) — effectively a new module |
| 7 | Food detail page | ✅ | With add-to-cart |
| 8 | Cart: quantities, single-restaurant rule | ✅ | AJAX cart, no reload |
| 9 | Cart promo / voucher code | ✅ | `OMNIEATS5` session promo, persisted as `orders.discount` |
| 10 | Item-level notes (e.g. "no chilli") | 🟡 | One `cart_items.note` column + input |
| 11 | Saved delivery addresses (address book) | 🟡 | Small `user_addresses` table + checkout picker |
| 12 | Checkout: delivery vs pickup, address, contact | ✅ | Card-split UI, live totals |
| 13 | Scheduled / pre-order (choose a future slot) | 🔴 | Requires time-slot engine + status machine changes |
| 14 | Payment methods: online + cash | ✅ | Simulated gateway with deterministic decline control |
| 15 | Wallet / stored balance / GrabPay | 🔴 | Real-money feature — brief requires simulated payments only |
| 16 | Order confirmation + receipt | ✅ | Receipt card with transaction ref |
| 17 | Live order tracking timeline | ✅ | ★ M5 unique feature — 5 s polling, DB history |
| 18 | ETA display | ✅ | Computed from real `delivery_minutes` + payment time |
| 19 | Rider card (name / plate / phone) | 🟡 | Currently hardcoded demo rider (user-requested). Upgrade: owner-entered driver fields on the fulfilment |
| 20 | Live map with rider GPS | 🔴 | Requires rider app, map API, location streaming |
| 21 | Push notifications | 🔴 | Brief says polling is sufficient; web-push needs service workers + keys |
| 22 | Order history | ✅ | With active/archived views |
| 23 | Order again (reorder) | ✅ | Grab-style — re-runs all cart rules |
| 24 | Ratings & reviews after completion | 🟡 | `restaurants.rating` column exists but is seeded only; a reviews table + post-completion rating form makes it real |
| 25 | Favourite / saved restaurants | 🟡 | `user_favourites` table + heart on cards |
| 26 | Loyalty points / stamps | 🔴 | Separate rewards module |
| 27 | pandapro-style free-delivery subscription | 🔴 | New product (customer-side subscription) — SaaS subscription is merchant-side here |
| 28 | In-app chat with rider / support | 🔴 | New module + realtime channel |

## 2. Merchant side (owner / staff)

| # | Feature (GrabMerchant / panda partner) | OmniEats status | Notes |
|---|---|---|---|
| 29 | Dashboard: today's sales, this month, today's orders | ✅ | DB-backed, tenant-scoped |
| 30 | Sales charts / trends (7-day) | 🟡 | Simple bar chart from daily `payments` sums |
| 31 | Incoming orders with live refresh | ✅ | 10 s polling of `/owner/orders/poll` (GrabMerchant-style) |
| 32 | Auto-accept toggle (new orders skip manual accept) | 🟡 | `restaurants.auto_accept` + skip `confirmed` wait |
| 33 | Order workflow: confirm → preparing → ready → complete | ✅ | Validated transition map |
| 34 | Order cancellation with note | ✅ | History records the note |
| 35 | Menu CRUD with categories, images, availability | ✅ | Per-tenant names, delete protection |
| 36 | Prep / delivery time management | ✅ | `delivery_minutes` in profile (drives customer ETA) |
| 37 | Popular items badge | 🟡 | `food_items.is_popular` + "Popular" chip |
| 38 | Stock / sell-out management | ✅ | `is_available` toggle (simplest honest form) |
| 39 | Merchant-funded promotions | 🔴 | Promo engine (voucher table, redemption flow) |
| 40 | Settlement / payout reports | 🔴 | Real financial flows — simulated payments only |
| 41 | Special/holiday opening hours | 🟡 | `opening_hours` is a text field today; a structured table would be nicer but low value |
| 42 | Branch (multi-outlet) management | ✅ | M2 with quota enforcement |
| 43 | Subscription management (plans, quota, expiry) | ✅ | ★ M4 unique feature — Grab/Foodpanda don't even have this (they charge commission) |
| 44 | Fulfilment management with rider assignment | 🟡 | Fulfilment exists; add driver name/plate/phone fields + customer sees them |
| 45 | Payment records + void/delete rules | ✅ | Audit-safe void, eligible deletes |

## 3. Rider side (Grab driver / panda rider apps)

Grab and Foodpanda run a **full third product** here: rider accounts, job offers,
acceptance, navigation, earnings, shift management.

| # | Feature | Verdict |
|---|---|---|
| 46 | Rider registration + rider role | 🔴 |
| 47 | Job offer / acceptance flow | 🔴 |
| 48 | Pickup → dropoff workflow with timestamps | 🔴 |
| 49 | Navigation / GPS | 🔴 |
| 50 | Earnings & settlement | 🔴 |

Why this is out of scope (and the recommendation): the assignment defines exactly
**three roles** and **six modules**, mandates **no unique feature in M6**, and warns
against extra modules. A rider system would be a seventh module with a fourth role
and would unbalance the A/B/C allocation. **Recommended compromise (🟡):** keep the
three roles but make riders REAL DATA — the owner assigns a driver (name, vehicle
plate, phone) to each delivery fulfilment; the customer tracking page shows that
real rider card. This matches how a small SaaS food platform actually operates
(restaurant's own delivery staff) without violating the brief.

## 4. Platform admin side

| # | Feature | OmniEats status | Notes |
|---|---|---|---|
| 51 | User management (search/filter/roles/status) | ✅ | M1 |
| 52 | Restaurant approval lifecycle | ✅ | M2 |
| 53 | Subscription plan management (limits configurable) | ✅ | M4 — matches "demonstration limits must be configurable" |
| 54 | Subscription monitoring + expiry sweep | ✅ | M4 unique feature |
| 55 | Platform activity log | ✅ | Audit trail |
| 56 | Platform stats dashboard | ✅ | Users/restaurants/subs/orders/revenue |

(Grab/Foodpanda's admin tools are internal ops systems; direct comparison N/A.)

## 5. What OmniEats has that Grab / Foodpanda do NOT

| Feature | OmniEats |
|---|---|
| True multi-tenant SaaS: subscription plans + quota enforcement | ✅ (Grab/Foodpanda charge commission instead — this is the assignment's core selling point) |
| Tenant isolation verified by a 10-case cross-tenant matrix | ✅ |
| Full audit trail (activity logs + payment void + order snapshots) | ✅ |
| OPEN/CLOSED availability control as a documented unique feature | ✅ |

## 6. Recommendation

**Before submission:** implement the 🟡 items the team picks from §1/§2 — all of
them fit inside the six modules and strengthen the Grab/Foodpanda comparison in
Chapter 1/4 without breaking any assignment rule.

**After submission (optional demo extras, lecturer permitting):** rider accounts,
reviews with photos, charts, web-push. None of these should go into the graded
submission before the lecturer approves, because they exceed the six-module scope.
