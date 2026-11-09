# OmniEats — Phase 1 Audit & Compliance Review

Date: 2026-11-09 · Per the Master Development Instructions §14 (Phase 1 = audit only).
**No code was modified during this phase.**

---

## 1. Repository Structure

```
main branch (HEAD e53e096, tag php-legacy) — PHP 8.2 + PDO + XAMPP MariaDB 10.4
├── app/          Controllers (17 + Admin/Owner), Models (20), Services (14),
│                 Middleware, Validators (12), Core (Router), Helpers
├── config/       app.php, database.php (.env-based)
├── database/     schema.sql (20 tables), seed.sql
├── public/       front controller, assets, uploads/
├── resources/views/  (42 views, Cream Soft UI theme)
├── routes/web.php    (route table + guards)
├── docs/         blueprint, DB design, DFD, class diagram, module specs,
│                 test plan (140+ executed tests), user manual, gap analyses
└── storage/logs/

branch feature/aspnet-foundation — an abandoned C#/.NET scaffold started on
2026-11-09 under a previous spec revision. The current spec mandates PHP/XAMPP,
so this branch is OUT OF SCOPE. Decision needed: delete the branch or keep it
untouched (recommend delete to avoid confusion; no PHP files affected).
```

## 2. Existing Working Features (verified, tested)

| Area | State |
|---|---|
| Auth: register/login/logout, session + CSRF, 3 roles + staff, RBAC guards | ✅ 140+ executed tests |
| Restaurants: application, approval, profile, hours, images, OPEN/CLOSED | ✅ |
| Menu: categories + food items, images, prices, availability, search/filter | ✅ |
| Cart/checkout: single-restaurant rule, server totals, snapshots, promo | ✅ |
| Orders: history, detail, cancel, ★ status timeline (DB-backed, 5 s polling) | ✅ |
| Payments: cash + simulated online, decline control, void (never delete) | ✅ |
| Reviews: 1–5 star + comment on completed orders (unique per order), rating recompute | ✅ (added 2026-11-08) |
| Address book: label/address/phone/default CRUD + checkout picker | ✅ (added 2026-11-08) |
| Fulfilments: delivery/pickup records, status transitions, driver name/vehicle/phone fields (owner-entered) | ✅ |
| Extras: order again, archive, ETA, merchant polling, auto-accept, 7-day chart, favourites, item notes | ✅ |

Known-broken items: none. (Site-wide search 500 was fixed and re-tested 2026-11-08.)

## 3–5. Incomplete / Missing vs the New Module Allocation

### Student 1 — LIM LI ZHE

| Module | CRUD | Status |
|---|---|---|
| M1 Restaurant Menu Management | Create/View/Update/Delete for restaurants, categories, food items | ✅ exists — gaps below |
| M2 Promotion & Voucher Management | Create/View/Update/Delete vouchers | ❌ **missing entirely** — only a hardcoded session promo (OMNIEATS5, RM5) exists |
| ★ Smart Voucher Validation | eligibility + discount calculation | ❌ missing (needs vouchers + voucher_usages tables) |

M1 gaps (Medium): food delete currently uses FK SET NULL on order_items (snapshot
survives) — safe, but the spec says "prevent inappropriate deletion of food items
referenced by existing orders"; propose blocking delete of referenced items with a
clear message, keeping the snapshot design as a second line of defence.
Search/filter: ✅ exists.

### Student 2 — WONG YONG FENG

| Module | CRUD | Status |
|---|---|---|
| M1 Customer Profile (addresses) | Create/View/Update/Delete addresses | ✅ exists — field gaps: add recipient_name, city, state, postcode (we have label/address/phone/default); add Update (edit an address — currently add/delete/default only) |
| M2 Order Processing & Checkout | Create order / View history+details / Update remarks + cancel / Delete cart items | ✅ exists — remarks (note) ✓, saved-address selection ✓, snapshots ✓ |
| ★ Order Status Timeline | DB-backed progress | ✅ exists (order_status_history + 5 s polling) — must be extended with the delivery statuses (Ready for Pickup / Picked Up / Out for Delivery / Delivered) per this spec's list |

M1 gaps: address edit form (Medium); recipient/city/state/postcode fields (Medium);
"prevent deleting an address required by an active checkout" (Low — checkout snapshots
the address into the order, so no live dependency exists; document it).

### Student 3 — LEONG HONG JUN

| Module | CRUD | Status |
|---|---|---|
| M1 Delivery & Rider Management | Register rider / View riders / Update rider + availability / Suspend | ❌ **missing** — riders are only 3 text fields on fulfilments; no rider accounts, no portal, no admin assignment UI |
| M2 Payment & Review Management | Create payment / View payments + reviews / Update payment status + reply to reviews / Delete-or-hide reviews | ⚠️ partially — payments ✅; reviews create/view ✅; **review replies ❌, hide/moderate ❌** |
| ★ Admin Manual Rider Assignment | assign/reassign + history | ❌ missing (fulfilment driver fields are a stopgap, not the workflow) |

### Shared functions

- 4th role "Rider": ❌ (roles table has 4 rows but no rider).
- Role-based dashboards: admin ✅, owner ✅, customer ✅, rider ❌.
- Voucher ↔ checkout integration: ❌ (module absent).

## 6. Duplicate Functionality

- `fulfilments` already models delivery execution (type, status, timestamps,
  driver fields) — per spec §7 ("Do not create separate delivery tables if an
  existing fulfilment table already performs that responsibility"): **reuse and
  extend fulfilments** rather than a new deliveries table. Add: rider_id FK,
  assigned_by, assignment timestamps, rejection reason.
- Assignment history: no existing table → one new table is genuinely needed
  (`rider_assignment_history`).
- Session promo (CartService) must be REPLACED by the voucher module (remove
  the hardcoded OMNIEATS5 path) to avoid two discount systems.

## 7–8. Database Review (proposed changes, Phase 3 — no changes made now)

| Action | Tables |
|---|---|
| Retain as-is | users, roles, user_roles, restaurants, categories, food_items, carts, cart_items, orders, order_items, order_status_history, payments, reviews, activity_logs |
| Extend | user_addresses (+recipient_name, city, state, postcode); fulfilments (+rider_id, assigned_by, assigned_at, accepted_at, rejected_at, rejection_reason, picked_up_at, out_for_delivery_at, delivered_at); reviews (+reply, reply_by, hidden, hidden_by) ; roles (+rider seed row) |
| New (genuinely missing) | vouchers, voucher_usages (order_id FK), rider_profiles (user_id FK unique, vehicle_type, plate, is_available, is_active) — or rider columns on users; rider_assignment_history (fulfilment_id, rider_id, assigned_by, status, responded_at, reason) |
| Drop / replace | OMNIEATS5 session promo logic (replaced by vouchers); `restaurants.free_delivery` can stay (flat-fee rule) |
| Do NOT drop | any existing table — everything is retained, only extended |

Relationships to correct: user_addresses already FKs users ✓; reviews need no
change besides moderation columns; fulfilments.rider_id → users (role Rider)
with RESTRICT; voucher_usages unique (voucher_id, order_id).

## 9. Missing Module Integrations

1. Voucher → checkout: apply + usage recording + smart validation (new).
2. Order timeline → delivery statuses: merge order + fulfilment/delivery events
   into the customer timeline (Ready for Pickup / Picked Up / Out for Delivery /
   Delivered).
3. Admin assignment → rider portal → fulfilment status updates.
4. Review replies: restaurant staff reply; admin hide.
5. Rider login → own deliveries only (new role + guards).

## 10. UI Problems

- Rider portal does not exist (new).
- Voucher admin pages do not exist (new).
- Review moderation/reply UI missing (extend existing order/restaurant pages).
- Existing Cream Soft UI is consistent and responsive — retained as the base.

## 11. Security & Validation Problems

- None critical in existing code (PDO prepared statements, CSRF, RBAC, tenant
  checks, upload whitelist all verified).
- New work must add: rider ownership checks (own assignments only), voucher
  server-side validation (never trust client discount), review moderation rules,
  assignment rules (active + available rider, one active assignment), and
  address ownership checks (already patterned).

## Findings by Severity

| # | Finding | Severity |
|---|---|---|
| F1 | Voucher module entirely missing (Student 1 M2 + unique feature) | **Critical** |
| F2 | Rider module missing (accounts, portal, assignment workflow, unique feature) | **Critical** |
| F3 | Review reply + moderation missing (Student 3 M2) | **High** |
| F4 | Order timeline lacks delivery statuses (Ready for Pickup/Picked Up/Out for Delivery/Delivered) | **High** |
| F5 | Address fields incomplete (recipient/city/state/postcode) + no edit form | **Medium** |
| F6 | Food-item deletion allowed while referenced (history safe, but spec wording wants prevention) | **Medium** |
| F7 | Hardcoded session promo overlaps the future voucher module (must be replaced) | **Medium** |
| F8 | Rider role absent from roles/seed | **High** |
| F9 | C# scaffold branch conflicts with the mandated PHP stack (repo hygiene) | **Low** |
| F10 | Member names in old docs still say "Member A/B/C" | **Low** |

## Proposed Improvement Plan (Phases 3–7, for approval)

- **Phase 3 (DB):** extend user_addresses / fulfilments / reviews; add vouchers,
  voucher_usages, rider_profiles, rider_assignment_history; seed a rider role +
  demo rider; keep every existing table and all data.
- **Phase 4 (UI):** rider portal + voucher admin pages + review reply/hide UI +
  admin assignment page; keep the existing theme.
- **Phase 5 (implementation, module by module):**
  - Lim Li Zhe: voucher CRUD + smart validation + checkout integration; food
    delete protection.
  - Wong Yong Feng: address fields + edit; timeline extension with delivery statuses.
  - Leong Hong Jun: riders (accounts via admin, portal, availability, suspension),
    admin manual assignment + history + reassignment, review replies + moderation.
- **Phase 6:** full workflow E2E: menu → cart → address → voucher → checkout →
  payment → assignment → rider progress → delivered → review → reply.
- **Phase 7:** test cases for all six modules + documentation regeneration
  (ERD/data dictionary/UI mockups match the final system; replace "Member A/B/C"
  with the real names).

## Decisions Requiring Approval

1. Delete the out-of-scope `feature/aspnet-foundation` branch? (recommended)
2. Reuse fulfilments as the delivery table (spec explicitly allows) — confirm.
3. Block food-item deletion when referenced by orders (spec wording) vs keep the
   snapshot/SET NULL approach? (recommend block with message)
4. Keep the session promo as a demo voucher seed or remove it entirely?
