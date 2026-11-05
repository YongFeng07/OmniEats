# OmniEats — Project Blueprint

**Multi-Restaurant Food Ordering SaaS Platform**
TAR UMT — AMCS2194 Mini Project · Web Application Development
Version 0.1 (draft for team approval) · 2026-11-05
Team: Member A · Member B · Member C

---

## 1. Project Understanding

OmniEats is a **shared multi-tenant SaaS platform** where multiple independent restaurants
register, subscribe to a plan, and run their own online food ordering business inside one
web application and one shared MySQL database.

The system has **three user roles**:

| Role | Scope |
|---|---|
| Super Admin | Platform-wide: user management, restaurant approval, subscription plans, activity monitoring |
| Restaurant Owner / Staff | Tenant-level: their own restaurant's profile, branches, menu, orders, payments, subscription |
| Customer | Own account: browse restaurants, cart, place & track orders, order history |

The assignment priorities are **functional correctness, database integrity, RBAC, tenant
isolation, validation, integration and testing** — not maximum features. The grading focus is:

- Every module demonstrates **complete CRUD** backed by the database.
- Every module has **real business rules**, not just forms.
- Every module has **server-side validation** and success/error messages.
- Modules **integrate** through shared database records (cart → order → payment → fulfilment).
- Each student owns **exactly 2 modules and exactly 1 unique feature**.
- Security: PDO prepared statements, `password_hash()`, sessions, CSRF, output escaping, tenant checks.

## 2. Phase 1 — Repository Analysis (completed 2026-11-05)

| Item | Status |
|---|---|
| Existing source code | None — repository is empty |
| Programming language | None yet (will be PHP 8.x) |
| Database | None yet (will be MySQL via XAMPP) |
| Git | Initialised; 1 commit (`6356c6e Add hi`); remote `origin` → `https://github.com/YongFeng07/OmniEats.git` |
| Dependencies | None |
| Existing functionality to preserve | None |
| Leftover file | Empty file `hi` (harmless; may be removed later) |

**Conclusion:** the project is built from scratch following the recommended structure below.

## 3. Proposed System Architecture

Three-tier architecture with an MVC-style application layer:

```
Presentation Layer      HTML5 / CSS3 / Bootstrap 5 / JavaScript
                        resources/views/*, public/assets/*

Application Layer       PHP 8 — Controllers, Services, Validators, Middleware
                        Routing (public/index.php), Session Auth, RBAC, CSRF

Data Layer              MySQL — PDO Models/Repositories
                        shared multi-tenant schema (database/schema.sql)
```

**Request lifecycle:** `public/index.php` → session + CSRF middleware → route in `routes/web.php`
→ auth/role middleware → controller → validator → service (business logic) → model (PDO) → view + flash messages.

**Proposed directory structure:**

```
OmniEats/
├── app/
│   ├── Controllers/      # one controller per domain (Auth, User, Restaurant, Branch,
│   │                     #   Category, FoodItem, Plan, Subscription, Cart, Order,
│   │                     #   Payment, Fulfilment, Dashboard, Home)
│   ├── Models/           # one PDO-backed model per table
│   ├── Services/         # business logic: AuthService, CartService, OrderService,
│   │                     #   PaymentService, SubscriptionService, UploadService
│   ├── Middleware/       # AuthMiddleware (role gates), CsrfMiddleware
│   ├── Validators/       # Validator base + one validator per entity
│   └── Helpers/          # e(), flash(), redirect(), url(), csrf_field(), auth()
├── config/               # database.php, app.php (reads .env)
├── database/             # schema.sql, seed.sql, migrations/
├── public/
│   ├── index.php         # front controller
│   ├── .htaccess         # route all requests through index.php
│   ├── assets/           # css/, js/, images/
│   └── uploads/          # restaurant/food images (PHP execution denied via .htaccess)
├── resources/views/      # shared layouts + auth/ customer/ owner/ admin/ errors/
├── routes/web.php        # route table
├── docs/                 # assignment documentation
├── storage/logs/         # application log
├── .env.example          # DB credentials template (never committed)
├── .gitignore
└── README.md
```

**Key technical decisions**

1. **Front controller + `routes/web.php`** (pending approval, see D2) — one entry point,
   clean separation, easier for all 3 members to follow the same pattern.
2. **Session-based authentication** — `$_SESSION['user_id']`, role resolved from DB on
   every request (a deactivated user is logged out immediately).
3. **RBAC in middleware, not views** — every protected route declares the roles allowed;
   controllers additionally re-verify ownership (never trust `restaurant_id` from POST).
4. **Tenant isolation** — the tenant (`restaurant_id`) always comes from the
   authenticated session's user record, never from the browser.
5. **CSRF** — one token per session, verified on every POST.
6. **Flash messages** — success/error feedback stored in session, rendered in layouts.
7. **No frameworks** — plain PHP + PDO so the team can explain every line.

## 4. Six-Module Breakdown

### Module 1 — User & Authentication Management (Member A)
- **CRUD:** create customer / owner / staff / admin accounts; list, search, filter by role &
  status; view details; update profile, role, status; soft-delete (deactivate) accounts.
- **Related:** registration, login/logout, profile management, password change
  (old + new + confirm), role assignment without escalation.
- **Validation:** required fields, email format + uniqueness, password strength +
  confirmation, valid role assignment, admin cannot be demoted/deactivated by non-admin.
- **Security:** `password_hash()` / `password_verify()`, prepared statements, CSRF.
- **Integration:** feeds all other modules (auth + RBAC); registration creates the tenant
  workflow entry point for owners.

### Module 2 — Restaurant & Branch Management (Member B)
- **CRUD:** register restaurant (creates owner account + tenant); create branches;
  view/search/filter restaurants; update profiles, addresses, opening hours, branches;
  deactivate/archive restaurants; delete eligible branches.
- **Related:** logo & cover image upload, opening hours, admin approval workflow
  (`pending → approved / deactivated`).
- **★ UNIQUE FEATURE — Restaurant Availability Control:** owner toggles restaurant
  **OPEN / CLOSED**. When CLOSED: public profile still viewable, **new orders blocked**,
  status clearly shown on the listing/detail pages, existing orders remain accessible to staff.

### Module 3 — Menu & Food Catalogue Management (Member B)
- **CRUD:** create categories & food items; display, search, filter by category;
  update name/description/price/image/availability; delete eligible items & categories.
- **Business rules:** every item/category belongs to a valid restaurant; a category with
  items cannot be deleted until emptied (or items are reassigned); availability is
  **re-checked during checkout**; order items store name + price snapshots.

### Module 4 — Subscription Management (Member A)
- **CRUD:** admin creates/updates/archives subscription plans (Free / Basic / Premium,
  limits configurable); restaurants subscribe / upgrade / downgrade / renew; view active
  subscriptions, history, expiry dates and statuses; cancel (soft) subscriptions.
- **★ UNIQUE FEATURE — Automatic Subscription Expiry & Quota Enforcement:**
  (1) a service checks expiry on every relevant request and marks overdue subscriptions
  `expired`; (2) plan limits (branches / food items) are enforced server-side when records
  are created; (3) clear upgrade / expiry messages shown. Payment is **simulated** — no
  real gateway.

### Module 5 — Ordering & Order Management (Member C)
- **CRUD:** create carts + cart items; view cart, order details, customer history,
  restaurant orders; update quantities and order statuses; remove/clear cart items;
  cancel eligible orders; archive safely.
- **Business rules:** one restaurant per cart; unavailable items cannot be ordered;
  totals computed **server-side**; completed orders immutable for customers; customers
  see only their orders, staff only their restaurant's.
- **★ UNIQUE FEATURE — Live Order Status Tracking:** DB-backed status timeline
  (`pending_payment → confirmed → preparing → ready → completed`, `cancelled`),
  rendered as a visual progress timeline; status transitions validated against a
  transition map in `OrderService`; JavaScript polling refresh (no WebSockets).

### Module 6 — Payment & Fulfilment Management (Member C)
- **CRUD:** create payment records and fulfilment records; view payments, receipts,
  delivery/pickup details; update payment & fulfilment statuses; delete eligible
  pending/test records; **void** (never physically delete) completed financial records.
- **Payment verification simulation (standard workflow, NOT a unique feature):**
  customer selects method (cash / online) → payment record created (`pending`) →
  simulated gateway returns success or failure → status updated → success marks the
  order paid (`confirmed`) and the fulfilment created; failure never marks the order paid.
- **No unique feature here (mandated).** No card numbers / CVVs stored.

## 5. Task Allocation & Unique Features

| Member | Module 1 | Module 2 | Unique feature |
|---|---|---|---|
| A | M1 User & Authentication | M4 Subscription | Automatic Subscription Expiry & Quota Enforcement |
| B | M2 Restaurant & Branch | M3 Menu & Catalogue | Restaurant Availability Control (OPEN/CLOSED) |
| C | M5 Ordering & Orders | M6 Payment & Fulfilment | Live Order Status Tracking |

Each member: exactly 2 modules, exactly 1 unique feature. Module 6 intentionally has none.

## 6. Role & Permission Matrix

| Capability | Super Admin | Owner | Staff | Customer |
|---|:---:|:---:|:---:|:---:|
| Manage users (CRUD, roles, status) | ✓ | – | – | own profile only |
| Approve / deactivate restaurants | ✓ | – | – | – |
| Manage subscription plans | ✓ | – | – | – |
| Monitor subscriptions & activity logs | ✓ | – | – | – |
| Restaurant profile, branches, opening hours | read all | own only | – | read public |
| Categories & food items | read all | own only | own only | read public |
| Availability toggle (OPEN/CLOSED) | – | own only | – | – |
| Subscription select / renew / upgrade | – | own only | – | – |
| Orders | read all | own only | own only | own only |
| Payment & fulfilment management | read all | own only | own only | pay own orders |
| Cart / checkout / track orders | – | – | – | own only |

"–" = no access. Every protected backend query enforces this matrix server-side.

## 7. Database Design Proposal (summary)

See [DATABASE_DESIGN.md](DATABASE_DESIGN.md) for the full ERD, data dictionary, 3NF
explanation and relationship documentation. Source of truth: [database/schema.sql](../database/schema.sql).

- **17 tables:** the 16 recommended entities plus `activity_logs` (platform monitoring).
- Shared MySQL database, **tenant-isolated** by `restaurant_id` foreign keys on
  restaurant-owned records (branches, categories, food_items, subscriptions, orders…).
- Historical snapshots: `order_items` (name + price), `subscriptions` (plan name + price).
- Soft deletion for users (`is_active`), restaurants (`status = deactivated`), plans
  (`is_active`); payments use `void` — completed financial records are never deleted.
- Monetary values: `DECIMAL(10,2)`. Order totals computed on the backend.
- Enforced transition rules for order status and payment status.

## 8. Main Navigation Structure

| Area | Pages |
|---|---|
| Public / Customer | Home, Restaurant Listing, Restaurant Detail + Menu, Food Detail, Login, Register (customer / restaurant owner), Cart, Checkout (fulfilment + payment method), Payment simulation, Order Confirmation, Order Tracking, Order History, Profile |
| Owner dashboard | Dashboard Overview, Restaurant Profile, Branch Management, Categories, Food Items, Orders, Payments & Fulfilments, Subscription |
| Admin dashboard | Platform Overview, User Management, Restaurant Management (approval), Subscription Plans, Subscription Monitoring, Activity Overview |
| Shared | 403 Forbidden, 404 Not Found |

Every navigation link leads to a working page (or a clearly labelled placeholder during development).

## 9. Core Workflows

1. **Registration** — user registers (customer or restaurant owner) → User module creates
   account + role → restaurant registration creates tenant + initial Free subscription
   (pending admin approval).
2. **Restaurant setup** — owner logs in → creates branches/categories/food items (quota
   enforced by Subscription module) → approved & OPEN restaurant becomes visible to customers.
3. **Food ordering** — customer browses restaurant → adds items to cart (single restaurant)
   → adjusts quantities → backend computes summary → checkout → Order module creates order
   (`pending_payment`) → Payment module creates payment record → simulated result →
   order moves to `confirmed`, fulfilment record created.
4. **Order processing** — owner views incoming orders → confirms → preparing → ready
   (for collection / out for delivery) → completed; customer sees the live timeline.
5. **SaaS subscription** — admin defines plans → restaurant selects/renews plan →
   Subscription module validates, enforces limits, auto-expires overdue subscriptions,
   shows upgrade messages.

All workflows use real database records — no hardcoded data in place of backend logic.

## 10. Development Roadmap

| Phase | Deliverable | Status |
|---|---|---|
| 1 | Repository analysis | ✅ done |
| 2 | Blueprint, architecture, DB design, schema, module specs | ✅ done |
| 3 | Database implementation: run `schema.sql`, verify relations in phpMyAdmin, `seed.sql`, demo accounts | ✅ done (verified in MariaDB 10.4) |
| 4 | Core application: DB config, `.env`, helpers, router, auth, sessions, CSRF, RBAC middleware, shared layouts, flash messages, error pages | ✅ done (tested with curl test suite; 2 bugs found & fixed) |
| 5 | Module development M1 → M6 (per-module checklist below) | ⏳ in progress — **M1 ✅ · M2 ✅ · M3 ✅ · M4 ✅ · M5 ✅ (2026-11-05)** |
| 6 | Integration: run the 5 workflows end-to-end; tenant isolation & cross-tenant tests | 🔜 |
| 7 | UI/UX polish: responsive, consistent, professional SaaS look | 🔜 |
| 8 | Final testing: TEST_PLAN execution, screenshot capture, README, user manual | 🔜 |

**Definition of done for every module** (checked before the module is declared complete):
Create ✅ Retrieve ✅ Update ✅ Delete ✅ validation ✅ success messages ✅ error messages ✅
business logic ✅ permissions ✅ integrated with related modules ✅ tested ✅.

**Test plan:** [TEST_PLAN.md](TEST_PLAN.md) will cover all CRUD with valid input, invalid
input, missing fields, duplicates, invalid FKs, boundary conditions, unauthorised access,
cross-tenant attempts, invalid status transitions and invalid payment operations.
Only actually-executed tests will be recorded — no fabricated results.

## 11. Risks & Technical Challenges

| Risk | Mitigation |
|---|---|
| Cross-tenant ID manipulation (owner A edits restaurant B) | Tenant always derived from session; central ownership check in every controller/service; dedicated test cases |
| Cart mixing items from two restaurants | `UNIQUE(customer_id)` on carts + service-level check when adding items |
| Quota enforcement missed on some path | Single `SubscriptionService::enforce()` called when creating branches, food items, and at checkout |
| Invalid order status transitions | One transition map inside `OrderService`; every status change goes through it and writes history |
| Double payment processing | `UNIQUE(order_id)` on payments + service re-checks status before simulating |
| Unsafe image uploads | Whitelist + MIME check + size limit + randomised filenames; `.htaccess` blocks PHP execution in `public/uploads` |
| Documentation drifts from implementation | `schema.sql` is canonical; ERD regenerated from it; docs updated after each phase |
| Inconsistent code style across members | One shared pattern (controller → service → model), shared helpers, code review at integration |
| MariaDB (XAMPP) vs MySQL differences | SQL kept compatible (InnoDB, utf8mb4, no exotic features) |

## 12. Decisions Requiring Approval

| # | Decision | Recommendation | Status |
|---|---|---|---|
| D1 | Team size | 3 members (A/B/C as allocated above) | ✅ approved 2026-11-05 |
| D2 | Routing style | Front controller (`public/index.php` + `routes/web.php`) | ✅ approved 2026-11-05 |
| D3 | Database connection | XAMPP default: `localhost`, `root`, empty password, DB `omnieats`, credentials in `.env` | ✅ approved 2026-11-05 |
| D4 | Seed data scope | Full demo data: 3 plans, demo admin/owners/staff/customers, 2 restaurants with menus, subscriptions (one active, one expired to demonstrate auto-expiry) | ✅ approved 2026-11-05 |
| D5 | Demo images | Bundled sample images in `public/assets/images` for seed data (no copyright issues: royalty-free images) | ✅ approved (follows from D4) |
| D6 | Code comments language | English (assignment language) | set unless objected |

## 13. Completion Checklist (final sign-off)

- [ ] All six official modules implemented
- [ ] Every module has Create / Retrieve / Update / Delete
- [ ] Each student has exactly one designated unique feature
- [ ] All input validation works (frontend + backend)
- [ ] Success messages work / Error messages work
- [ ] All user roles work / Tenant data isolation works
- [ ] Customer registration and login work
- [ ] Restaurant registration and management work
- [ ] Menu management works / Subscription management works
- [ ] Shopping cart works / Order creation works
- [ ] Payment simulation works / Order tracking works
- [ ] All modules are integrated / Database relationships are correct
- [ ] Application follows Three-Tier Architecture
- [ ] UI is consistent and responsive / Main navigation works
- [ ] No major critical errors
- [ ] All test cases have been executed
- [ ] Project setup instructions are complete
- [ ] Documentation diagrams match the implementation
- [ ] All source code can be explained by the responsible student
