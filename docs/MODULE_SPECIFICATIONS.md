# OmniEats — Module Specifications

TAR UMT AMCS2194 · Chapter 3 material · matches the implemented code

## M1 — User & Authentication Management (Member A)

| Item | Implementation |
|---|---|
| Create | Public registration (`/register` customer, `/register/restaurant` owner — fixed role per route, no escalation); admin create user (`/admin/users/create`) with role whitelist + tenant requirement for owner/staff |
| Retrieve | Admin list with search + role/status filters (`/admin/users`), user detail with recent activity (`/admin/users/{id}`) |
| Update | Own profile (`/profile`), password change (`/profile/password`), admin edit (name/email/phone/role/tenant/status/optional password) |
| Delete | Soft delete via deactivate/activate toggle; self-deactivation blocked; deactivated users lose access on next request |
| Business rules | Email unique; role from whitelist (`Role::NAMES`); only super_admin can grant super_admin; admins cannot demote/deactivate themselves; password strength (8+, letters+numbers) |
| Integration | Auth drives every route guard; registration auto-logs-in and starts Workflow 1; activity log rows for every action |
| Tests | T13–T31 |

## M2 — Restaurant & Branch Management (Member B)

| Item | Implementation |
|---|---|
| Create | Owner registers tenant (`/owner/restaurant/create`, status=pending, slug generated, logo/cover upload, owner linked as tenant); branches (`/owner/branches/create`, M4 quota enforced) |
| Retrieve | Public listing with search + open/closed filter (approved only), public detail with branches; admin listing with approval filter |
| Update | Profile edit (address, hours, images, estimated delivery time, free-delivery offer); branch edit; admin approval lifecycle |
| Delete | Restaurant deactivate (archive — records preserved) / reactivate; branch delete (only-branch protected) |
| ★ UNIQUE FEATURE | **Availability Control**: `toggleOpen` flips OPEN/CLOSED; CLOSED → profile still visible, new orders blocked (re-checked in cart add AND checkout), status shown everywhere |
| Business rules | Pending restaurants hidden from public (404); cross-tenant ids rejected as "not found"; rating/delivery-time/free-delivery come from real columns (rating NULL → "New"; seeded demo values only) |
| Tests | T32–T40, CT-1, CT-7, CT-9 |

## M3 — Menu & Food Catalogue Management (Member B)

| Item | Implementation |
|---|---|
| Create | Categories + food items (owner/staff); food image upload |
| Retrieve | Public menu grouped by category with server-side search + category filter; owner list with the same filters; public food detail |
| Update | Name/description/price/category/image; availability toggle per item |
| Delete | Item delete; category delete blocked while it contains items |
| Business rules | Category names unique per tenant; category must belong to the tenant (validated server-side); price > 0 (DECIMAL); availability re-checked at checkout; order items keep name/price snapshots |
| Tests | T41–T52, CT-2, CT-3 |

## M4 — Subscription Management (Member A)

| Item | Implementation |
|---|---|
| Create | Admin creates plans (price, branch/item limits — max_food_items NULL = unlimited); restaurant chooses a plan (simulated payment); **registration bootstraps an initial Free subscription** (Workflow 1) |
| Retrieve | Owner: current plan + quota usage bars + full history; Admin: plan list with subscriber counts + subscription monitoring with status filter |
| Update | Plan edit; renew (extends same row), upgrade/downgrade (old row cancelled, new row with snapshots) |
| Delete | Cancel subscription (soft); archive plan (blocked while it has active subscribers) |
| ★ UNIQUE FEATURE | **Automatic Expiry & Quota Enforcement**: `syncExpiry()` marks overdue subscriptions expired on relevant requests (no cron); `enforceForBranch/FoodItem` called by Branch/FoodItem services BEFORE creating records; clear upgrade/expiry messages |
| Business rules | History never deleted (plan_name/price_paid snapshots); simulated payments only (activity log records the amount) |
| Tests | T53–T63 |

## M5 — Ordering & Order Management (Member C)

| Item | Implementation |
|---|---|
| Create | Cart + cart items (`UNIQUE(customer_id)` = one active cart); orders via checkout transaction (order + snapshot items + first history row, cart consumed) |
| Retrieve | Cart page, customer order history, restaurant order list (status filter), order detail for both sides |
| Update | Cart quantities; order status via validated transitions |
| Delete | Remove/clear cart items; cancel eligible orders |
| ★ UNIQUE FEATURE | **Live Order Status Tracking**: DB-backed `order_status_history` timeline (pending_payment → confirmed → preparing → ready → completed / cancelled); transitions validated against `OrderService::TRANSITIONS`; JS polls `/orders/{id}/status` every 5 s and reloads on change |
| Business rules | One restaurant per cart; totals backend-computed (subtotal + RM 5.00 delivery fee, waived for restaurants with free_delivery = 1); unavailable items and CLOSED restaurants re-checked at checkout; customers cannot modify completed orders; snapshots preserve history |
| Tests | T64–T77, CT-4, CT-6, CT-8 |

## M6 — Payment & Fulfilment Management (Member C) — no unique feature (mandated)

| Item | Implementation |
|---|---|
| Create | Payment record (pending) when the customer pays; fulfilment record created automatically on payment success (delivery address snapshot) |
| Retrieve | Payment receipt on the order page; restaurant payment list + detail; fulfilment list |
| Update | Payment status via the simulated gateway (success/failed/void); fulfilment status transitions (pending → out_for_delivery/ready_for_collection → completed, type-validated) |
| Delete | Pending/failed test payment records deletable; **success/void records never deleted** (audit trail — void only) |
| Payment simulation | Method selection (cash/online) → record created → simulated result (deterministic "simulate decline" demo control) → success advances the order via `transitionBySystem` + creates fulfilment; failure never marks the order paid |
| Business rules | One payment per order (UNIQUE); duplicate payment blocked; no card data stored |
| Tests | T78–T91, CT-5 |
