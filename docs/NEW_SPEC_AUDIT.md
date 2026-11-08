# OmniEats / FoodFlow — Repository Audit Against the New Master Specification

Date: 2026-11-09 · Per spec §13.1 (Mandatory Initial Audit) — no code changes made.

---

## 1. Existing Project Structure

```
OmniEats/                      (git: origin → github.com/YongFeng07/OmniEats, HEAD b50f0ef)
├── app/
│   ├── Controllers/  (16 + Admin/ + Owner/ subdirs)
│   ├── Models/       (20 PDO models)
│   ├── Services/     (13 business services)
│   ├── Middleware/   (Auth, CSRF)
│   ├── Validators/   (base + 12)
│   ├── Core/         (Router)
│   └── Helpers/      (e(), url(), csrf_field(), activity(), …)
├── config/           (app.php, database.php — reads .env)
├── database/         (schema.sql — 20 tables, seed.sql)
├── public/           (front controller index.php, assets, uploads/)
├── resources/views/  (42 views: shared/auth/customer/owner/admin/errors)
├── routes/web.php    (front-controller route table + guards)
├── docs/             (blueprint, DB design, DFD, class diagram, module specs,
│                      test plan — 140+ executed tests, user manual, gap analysis)
└── storage/logs/
```

Stack: **PHP 8.2 + PDO + MySQL/MariaDB 10.4 (XAMPP) + Bootstrap 5 + vanilla JS**, session
auth, CSRF, RBAC guards, tenant isolation, simulated payments.

## 2. Existing Implementation Summary (verified working)

| Area | State |
|---|---|
| Modules | 6 implemented, complete CRUD each, all integrated |
| Roles | super_admin, restaurant_owner, restaurant_staff, customer (3 primary + staff) |
| Unique features | M2 OPEN/CLOSED · M4 auto-expiry + quota · M5 live status timeline (polling) |
| Testing | 140+ executed curl/DB test cases, 0 failures, honest records |
| UI | Warm-cream theme (terracotta palette), responsive, AJAX cart |
| Extra (benchmark round) | order again, archive, ETA, merchant polling, reviews, favourites, saved addresses, item notes, auto-accept, rider fields on fulfilments |
| Uncommitted | **34 files** from the latest tested round (reviews / rider / auto-accept / addresses / favourites / notes / chart / customer hub) — working, 45/46 automated checks passed (1 test expectation corrected, security behaviour correct) |

## 3. Missing Features vs the New Specification

### 3.1 Stack (biggest gap)

| Required (new spec) | Current | Verdict |
|---|---|---|
| C# / .NET 10 LTS | PHP 8.2 | **Full rewrite of all application code** |
| ASP.NET Core MVC 10 + Razor | custom front controller + PHP views | rewrite (~60+ files) |
| EF Core 10 + Fluent API | hand-written PDO models | rewrite (schema concepts reusable) |
| ASP.NET Core Identity | custom session auth + custom RBAC | rewrite (Identity provides hashing, lockout, cookies natively) |
| xUnit | curl + DB shell suites | rewrite test harness (cases reusable) |
| MySQL 8.4 LTS | **MariaDB 10.4 (XAMPP)** | environment decision — see §4 |

### 3.2 Roles

| Required | Current | Action |
|---|---|---|
| Customer | ✓ customer | keep |
| RestaurantOwner | ✓ restaurant_owner | keep |
| Rider | ✗ absent | **new** — RiderProfiles + portal; created by admin only |
| SuperAdmin | ✓ super_admin | keep |
| — | restaurant_staff | **not in the new spec (exactly 4 roles)** → recommend dropping staff |

### 3.3 Module remap

| New module (member) | Current equivalent | Gap to build |
|---|---|---|
| M1 User & Access (A) | M1 User & Auth | maps; + admin-only rider account creation; + **lockout unique feature** (Identity config) |
| M2 Payment (A) | M6 Payment part | maps (records/statuses/void/simulation); + **COD cash-collection confirmation from the Rider Portal via PaymentService** |
| M3 Restaurant (B) | M2 Restaurant | maps; + structured RestaurantHours table (replaces text opening_hours); + ApplicationStatus Pending/Approved/**Rejected** + RejectionReason; **approval workflow as the unique feature** (approve exists; reject+reason is new) |
| M4 Menu (B) | M3 Menu | maps ~1:1 |
| M5 Cart & Checkout (C) | M5 cart/checkout | maps ~1:1 (single-restaurant cart, server-side totals, snapshots already implemented) |
| M6 Order & Delivery (C) | M5 orders + M6 fulfilment | **largest gap**: Deliveries table (separate status model), DeliveryAssignmentHistory, admin manual assignment UI, Rider Portal (accept/reject/pickup/out-for-delivery/delivered), reassignment; order timeline unique feature maps from order_status_history |

### 3.4 Explicitly out of scope now (must be REMOVED from the rebuilt system)

- **Subscription plans & subscriptions** (current M4 entirely — plans, quota, auto-expiry)
- **Restaurant branches** (restaurant_branches)
- OPEN/CLOSED availability toggle as a feature (operating-hours rules instead)
- Promo/voucher engine (current OMNIEATS5 session promo — simple, but unrequested)
- Loyalty, GPS, chat, auto-assignment, rider self-claim, distance-based fees

### 3.5 Decisions needed on current extras

| Feature (currently working) | New spec mentions? | Recommendation |
|---|---|---|
| Saved addresses | ✓ (CustomerAddresses table specified) | keep (rename/reshape) |
| Reviews & ratings | ✗ (spec says "avoid fake ratings/reviews" — ours are REAL submitted data) | keep (real data, no fabrication) or drop for scope |
| Favourites | ✗ | optional drop |
| Item notes | ✗ | harmless, optional keep |
| Order again / archive / ETA / auto-accept | ✗ | optional keep (nice demo, no spec conflict) |

## 4. Technical Problems & Risks

1. **Rewrite cost.** All PHP code is replaced; only the DB concepts, business rules, test
   cases, seed data ideas and documentation structure carry over. This is a large,
   honest effort for a 3-member team — the current PHP system (working, tested) should
   be preserved as a fallback until the new system passes the same test suite.
2. **Database engine.** XAMPP ships MariaDB 10.4, not MySQL 8.4 LTS. Options:
   (a) install MySQL Server 8.4 separately (exact spec compliance);
   (b) use MariaDB 10.4 with the Pomelo EF Core provider (widely used, works with
   MariaDB) **with tutor approval**. Recommend (b) for the XAMPP workflow, or (a) if
   strict compliance matters.
3. **Package compatibility.** Pomelo ↔ EF Core 10 ↔ .NET 10 version matrix must be
   verified at install time (the spec itself warns). Also confirm VS 2026/.NET 10 SDK
   — verified present on this machine (SDK 10.0.400, VS 2026).
4. **XAMPP becomes mostly irrelevant** (Kestrel serves the app; only the DB is used).
   README/runbook must change.
5. **Naming inconsistency:** document title says "OmniEats", body says "FoodFlow" —
   pick one (repo, branding, docs).
6. **Tutor approval** for the four-role design, six-module allocation and Rider Portal
   is required by the spec itself before treating it as final.
7. **Concurrency on assignment** ("one active delivery per rider") needs a transaction
   + unique guard in EF Core — flag for Member C's implementation.
8. **Palette change:** new warm-cream palette (#F8F6F0 / #EEE7DB / #A5B49C / #795B45 /
   #292821) replaces the current terracotta cream.

## 5. Database Issues (schema delta)

| Current table | New spec | Action |
|---|---|---|
| users, user_roles, roles | AspNetUsers, AspNetRoles, AspNetUserRoles (+4 Identity tables) | replace with Identity |
| restaurants | Restaurants (+ApplicationStatus incl. Rejected, RejectionReason, CuisineType, IsActive; drop is_open/slug semantics?) | reshape |
| — | RestaurantHours | **new** |
| categories, food_items, carts, cart_items | same | reshape (column names) |
| orders | Orders (+DeliveryRecipientName, PaymentMethod; drop branch_id, promo discount?) | reshape |
| order_items | OrderItems | same (snapshots kept) |
| order_status_history | OrderStatusHistory | same (unique feature) |
| payments | Payments (COD/SimulatedOnline, Pending/Paid/Failed/Voided) | reshape |
| fulfilments | **replaced by Deliveries** (separate business process) | replace |
| — | DeliveryAssignmentHistory | **new** |
| — | RiderProfiles | **new** |
| user_addresses | CustomerAddresses (+RecipientName/City/State/PostalCode) | reshape |
| subscription_plans, subscriptions | — | **drop (out of scope)** |
| restaurant_branches | — | **drop (out of scope)** |
| reviews, user_favourites | — | decision (real data, optional) |
| activity_logs | — (not specified) | optional keep (admin monitoring) |

Integrity rules to enforce in EF Fluent API: no cascade on history (orders/order_items/
payments/assignment history), unique OrderNumber, unique Delivery.OrderId, unique
RiderProfile.UserId, DECIMAL money, UTC timestamps, transactions for checkout &
assignment.

## 6. Security Concerns (current → new)

| Concern | Current | New spec |
|---|---|---|
| Password hashing | password_hash/verify ✓ | Identity (PBKDF2 default) |
| Account lockout | ✗ absent | **required — Member A unique feature** (5 fails / 15 min, configurable) |
| CSRF | per-session token ✓ | Anti-forgery tokens |
| RBAC backend enforcement | guards + service checks ✓ | Areas + authorization policies + ownership checks |
| Ownership (ID tampering) | tenant/customer checks everywhere ✓ | same pattern, re-verified per module |
| Rider rules | n/a | DeliveryService server-side transitions + assignment transaction + no client-trusted IDs |
| XSS | e() escaping ✓ | Razor encoding |
| Uploads | whitelist + MIME + size ✓ | same rules in ASP.NET |

## 7. Recommended Implementation Phases (mapped to spec §13.4)

- **Phase 0 — Audit (this document).** Await approval of the decisions below.
- **Phase 1 — Foundation:** .NET solution, OmniEats.Web with Areas, Program.cs,
  appsettings + user-secrets, shared layout & warm-cream theme, CLAUDE.md, branch
  workflow (feature/user-payment, feature/restaurant-menu, feature/cart-order-delivery,
  feature/rider-portal).
- **Phase 2 — Database & Identity:** ApplicationUser, 4 roles, DbContext, entities +
  Fluent configurations, initial migration, seed (demo accounts incl. 1 rider).
- **Phase 3 — Shared UI:** layouts, navigation per role, auth pages, dashboards.
- **Phase 4 — Modules by member:** A: M1 (incl. lockout) + M2 payments; B: M3 (incl.
  approval workflow) + M4 menu; C: M5 cart/checkout + M6 orders (incl. timeline).
- **Phase 5 — Rider delivery integration:** RiderProfiles, Deliveries,
  DeliveryAssignmentHistory, admin assignment/reassignment UI, Rider Portal,
  COD collection confirmation.
- **Phase 6 — Integration validation:** role access, ownership, checkout transaction,
  assignment transaction, transition maps.
- **Phase 7 — Testing:** xUnit smoke tests + full manual case set (incl. the complete
  rider assignment workflow from §10.6).
- **Phase 8 — Documentation:** ERD, class diagram, DFD, activity diagrams, data
  dictionary, 3NF, test cases, screenshots, user manual, README.

## 8. Decisions Requiring Approval

1. **Pivot confirmation** — full C#/.NET rewrite per this spec (PHP system archived as
   fallback), or present both options to the tutor first?
2. **Name** — OmniEats or FoodFlow?
3. **Database** — MariaDB 10.4 via Pomelo (tutor approval) or install MySQL 8.4?
4. **Preservation** — commit the 34 uncommitted tested PHP files and tag the PHP
   system (`php-legacy`) before starting Phase 1?
5. **Scope choices** — keep reviews/addresses/notes/order-again in the new system?
   (recommend keep: they are real, tested features that match Grab/Foodpanda parity)
6. **Staff role** — drop (spec says exactly 4 roles)?
