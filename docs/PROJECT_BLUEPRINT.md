# OmniEats — Project Blueprint (Final)

**Multi-Restaurant Food Ordering & Delivery Platform**
TAR UMT — AMCS2194 Mini Project · Web Application Development
Revision 2 · 2026-11-09 · matches the implemented code
Team: Lim Li Zhe · Wong Yong Feng · Leong Hong Jun

---

## 1. Project Understanding

OmniEats is a shared multi-tenant food ordering & delivery platform where
independent restaurants run their own ordering business inside one PHP web
application and one shared MySQL/MariaDB database (XAMPP).

The system has **four roles**:

| Role | Scope |
|---|---|
| Super Admin | Platform-wide: users, restaurant approval, vouchers, riders, manual delivery assignment, review moderation, activity monitoring |
| Restaurant Owner / Staff | Tenant-level: restaurant profile, menu, vouchers, incoming orders, reviews, payments, subscription |
| Rider | Own deliveries: assignments, pickup, delivery progress, history, availability |
| Customer | Own account: browse, cart, vouchers, checkout, track orders, addresses, reviews |

The assignment priorities are functional correctness, database integrity, RBAC,
tenant isolation, validation, integration and testing — not maximum features.

## 2. Technology (course-approved only)

PHP 8 · PDO prepared statements · MySQL/MariaDB via XAMPP · phpMyAdmin ·
HTML5 · CSS3 · vanilla JavaScript · Bootstrap 5 (vendored) · sessions · CSRF.
No frameworks, no ORM, no external services — every line explainable in a
presentation.

## 3. System Architecture (three-tier)

```
Presentation     resources/views/* (shared layout + area views), public/assets
Application      routes/web.php → Router → Middleware (auth/CSRF)
                 → Controllers → Validators → Services (business rules) → Models
Data             MySQL/MariaDB "omnieats" — 24 tables, tenant-isolated, PDO
```

## 4. Six-Module Breakdown (final allocation)

### Student 1 — LIM LI ZHE

**M1 Restaurant Menu Management** — CRUD: restaurants / categories / food items
(images, prices, availability, search & filter). Food delete is blocked when the
item appears in past orders (mark unavailable instead); order snapshots preserve
history.

**M2 Promotion & Voucher Management** — CRUD: vouchers (admin: platform or
per-restaurant; owner: own restaurant). Soft archive as DELETE.
**★ UNIQUE FEATURE — Smart Voucher Validation**: one service validates
active/date-window/usage-limit/min-order/max-discount/tenant and returns the
exact discount; the same check runs in the voucher tray, on tap, and inside the
checkout transaction (usage row + counter, atomic with the order).

### Student 2 — WONG YONG FENG

**M1 Customer Profile** — CRUD: saved delivery addresses (recipient, city, state,
postcode, default). Ownership enforced; checkout picker fills the delivery form.

**M2 Order Processing & Checkout** — CRUD: create orders (transaction with item
snapshots), view history/details, update remarks/cancel, remove cart items.
**★ UNIQUE FEATURE — Order Status Timeline**: order history MERGED with delivery
timestamps (Picked Up / Out for Delivery / Delivered) into one progress bar;
5-second polling of an endpoint that reports both order and fulfilment status.

### Student 3 — LEONG HONG JUN

**M1 Delivery & Rider Management** — CRUD: rider accounts (admin-created only),
rider list/status, vehicle + availability updates, suspend/reactivate.
**★ UNIQUE FEATURE — Admin Manual Rider Assignment**: admin assigns eligible
riders (active + available + one active delivery max); acceptance/rejection with
reason; reassignment; every attempt preserved in `rider_assignment_history`;
pickup → out for delivery → delivered, each step validated; delivery
auto-completes the order.

**M2 Payment & Review Management** — CRUD: payment transactions (COD + simulated
online, decline control, void never delete), customer reviews (completed orders),
staff replies, admin hide/unhide moderation. No card data stored.

### Shared functions (distributed)

Auth + RBAC (4 roles), session/CSRF, activity log, dashboards, warm-cream UI,
validation framework.

## 5. Role & Permission Matrix

| Capability | Super Admin | Owner | Staff | Rider | Customer |
|---|:---:|:---:|:---:|:---:|:---:|
| Users / riders / vouchers (platform) | ✓ | – | – | – | – |
| Manual rider assignment | ✓ | – | – | – | – |
| Review moderation (hide) | ✓ | – | – | – | – |
| Restaurant profile + menu | read all | own | own | – | read public |
| Own-restaurant vouchers / reviews reply | ✓ | ✓ | ✓ | – | – |
| Orders | read all | own | own | – | own |
| Payments | read all | own | own | – | pay own |
| Delivery tasks | – | – | – | own only | – |
| Cart / checkout / track / review | – | – | – | – | own |

"–" = no access. Every protected query enforces this matrix server-side.

## 6. Database (summary)

24 tables — see [DATABASE_DESIGN.md](DATABASE_DESIGN.md) for the ERD, data
dictionary, 3NF explanation and relationships. Key integrity rules:

- Tenant isolation via `restaurant_id` FKs; tenant always from the session.
- Snapshots: order_items (name/price/note), subscriptions (plan/price),
  voucher_usages (discount), order delivery address + recipient.
- Soft deletion: users (is_active), restaurants (status), vouchers (is_active),
  riders (is_active), orders (is_archived), reviews (is_hidden); payments void.
- DECIMAL(10,2) money; transitions validated for orders, deliveries, payments.
- Unique constraints: email, order_no, payment.order_id, fulfilment.order_id,
  voucher code, (voucher_id, order_id) usage, rider user_id, (user_id,
  restaurant_id) favourites.

## 7. Core Workflows

1. **Registration** — customer/owner registers; owner registers the restaurant
   (pending) and gets the initial Free subscription (legacy SaaS module).
2. **Ordering** — customer browses an OPEN restaurant, adds items (single
   restaurant per cart), taps a voucher (smart validation), selects a saved
   address, checks out (server totals + snapshots), pays (simulated).
3. **Restaurant processing** — owner confirms → preparing → ready. Pickup orders
   are completed by the restaurant; delivery orders now wait for a rider.
4. **Delivery (★ admin manual assignment)** — admin assigns an eligible rider to
   each READY delivery; rider accepts/rejects (reason recorded); pickup → out
   for delivery → delivered; delivery completes the order automatically.
5. **Feedback** — customer reviews a completed order; staff replies; admin hides
   inappropriate reviews.

All workflows use real database records — no hardcoded data.

## 8. Development Roadmap (final status)

| Phase | Deliverable | Status |
|---|---|---|
| 1 | Repository analysis | ✅ |
| 2 | Blueprint, architecture, DB design, module specs | ✅ (this revision) |
| 3 | Database implementation (24 tables, seeds incl. rider + vouchers) | ✅ |
| 4 | Core application (router, auth, RBAC, CSRF, layouts) | ✅ |
| 5 | Module development (final 6-module allocation) | ✅ all three members' modules tested |
| 6 | Integration (5 workflows, tenant matrix, assignment workflow) | ✅ |
| 7 | UI/UX (warm-cream theme, responsive, AJAX tray/cart) | ✅ |
| 8 | Final testing + documentation | testing ✅ (200+ executed cases); docs — this revision |

## 9. Completion Checklist

- [x] Six official modules implemented (final allocation)
- [x] Every module has Create / Retrieve / Update / Delete
- [x] One unique feature per student (voucher validation / status timeline / manual rider assignment)
- [x] Input validation server-side everywhere
- [x] Success and error messages on every CRUD path
- [x] Four roles work (admin / owner+staff / rider / customer)
- [x] Tenant isolation works (CT matrix)
- [x] Voucher smart validation works (41 checks)
- [x] Timeline shows order + delivery statuses (14 checks)
- [x] Rider assignment workflow works end-to-end (T128–T131)
- [x] Review replies + moderation work (T132)
- [x] All modules integrated (E2E order → ready → assign → delivered → review)
- [x] No hardcoded demonstration data
- [x] 200+ test cases actually executed — no fabricated results
- [ ] Screenshots for Chapter 4 (team to capture per TEST_PLAN §10)
