# OmniEats — Module Specifications (Final Allocation)

TAR UMT AMCS2194 · Chapter 3 material · matches the implemented code
Allocation approved 2026-11-09: three members, six modules, one unique feature each.

## Student 1 — LIM LI ZHE

### M1 — Restaurant Menu Management

| Item | Implementation |
|---|---|
| Create | Restaurant application (owner), categories, food items (with image upload) |
| Retrieve | Public restaurant listing (search + OPEN/CLOSED filter), restaurant detail with grouped menu, public food detail, owner management lists |
| Update | Restaurant profile (hours, delivery time, free-delivery, images), category edit, food edit (name/description/price/image/availability) |
| Delete | Restaurant deactivate/archive; category delete blocked while it contains items; **food delete blocked when referenced by past orders** (mark unavailable instead — order snapshots preserve history) |
| Business rules | Pending restaurants hidden from public (404); cross-tenant ids rejected as "not found"; availability re-checked at checkout; order items store name/price/note snapshots |
| Tests | Original T32–T52 (legacy allocation), T125 (food-delete protection), GET/POST sweeps |

### M2 — Promotion & Voucher Management

| Item | Implementation |
|---|---|
| Create | Vouchers — admin creates platform-wide or per-restaurant; owners create vouchers for their own restaurant (tenant forced from session) |
| Retrieve | Admin list (search + status filter, used counts), owner list, customer voucher tray (eligible only, Grab-style) |
| Update | Voucher rules: code, title, discount type (percent/fixed), value, min order, max discount, date window, usage limit, restaurant, active switch |
| Delete | Soft archive (`is_active`) — redemption history is never deleted |
| ★ UNIQUE FEATURE | **Smart Voucher Validation**: one `VoucherService::validate()` checks active status, validity window, usage limit, minimum spending, discount cap and applicable restaurant, returning the exact discount or a clear reason. Runs in the tray, on tap, and AGAIN inside the checkout transaction. Redemption records a `voucher_usages` row and increments the counter atomically with the order |
| Business rules | Duplicate codes rejected; percent 1–100; expiry after start; usage limit ≥ 1; discount never exceeds the subtotal |
| Tests | T121–T124 (41 checks) |

## Student 2 — WONG YONG FENG

### M1 — Customer Profile (Saved Addresses)

| Item | Implementation |
|---|---|
| Create | Saved delivery address (label, recipient, address, city, state, postcode, phone, default flag) |
| Retrieve | Address book on the profile page; checkout picker |
| Update | Address edit form (`/profile/addresses/{id}/edit`); make-default |
| Delete | Address delete; ownership enforced (other users' addresses are unreachable) |
| Business rules | Required fields; phone format; 5-digit postcode; profile name/email/phone edit as the supporting function; customers manage ONLY their own addresses |
| Tests | T115, T126 (14 checks) |

### M2 — Order Processing & Checkout

| Item | Implementation |
|---|---|
| Create | Orders via checkout transaction (order + item snapshots + first history row; cart consumed; voucher redeemed) |
| Retrieve | Order history (active/archived), order details, cart page |
| Update | Cart quantities (AJAX), per-item notes, order remarks at checkout, cancel eligible orders |
| Delete | Remove/clear cart items; archive terminal orders (soft, reversible) |
| ★ UNIQUE FEATURE | **Order Status Timeline**: DB-backed `order_status_history` MERGED with delivery timestamps (picked_up / out_for_delivery / delivered) into one Grab-style progress bar — Payment → Confirmed → Preparing → Ready for Pickup → Picked Up → Out for Delivery → Delivered (pickup orders end at Completed). JS polls `/orders/{id}/status` (which also returns the fulfilment status) every 5 s and reloads on either change |
| Business rules | One restaurant per cart; server-side totals; recipient/address/phone required for delivery; vouchers applied via the voucher tray; snapshots preserve prices/names/notes/addresses; customers see only their own orders |
| Tests | Original T64–T77, T109–T110, T127 (14 checks) |

## Student 3 — LEONG HONG JUN

### M1 — Delivery & Rider Management

| Item | Implementation |
|---|---|
| Create | Rider accounts created by Super Admin ONLY (users + rider role + rider profile in one transaction — no public registration) |
| Retrieve | Admin rider list (status, availability, vehicle), Rider Portal (own task, own history, own profile) |
| Update | Rider vehicle details (admin), availability (rider + admin), suspension/reactivation, delivery status via the validated workflow |
| Delete | Suspend rider (soft — records preserved; pending assignment returned to the pool) |
| ★ UNIQUE FEATURE | **Admin Manual Rider Assignment**: the admin picks an eligible rider (active account + active profile + available + no other active delivery) for each READY delivery order. Every attempt is recorded in `rider_assignment_history`; rejection (with reason) returns the delivery to the pool; reassignment marks the previous attempt "Reassigned" — history is never overwritten. Rider workflow: assigned → accepted/rejected → picked_up → out_for_delivery → delivered (each step validated in order; delivery auto-completes the order) |
| Business rules | Riders cannot claim deliveries; riders see only their own assignments; suspended/unavailable riders receive nothing; one active delivery per rider |
| Tests | T128–T131 |

### M2 — Payment & Review Management

| Item | Implementation |
|---|---|
| Create | Payment transaction (cash / simulated online) when the customer pays; customer reviews on completed orders (1–5 stars + comment, one per order) |
| Retrieve | Payment receipts and lists; public reviews with staff replies; owner/admin review lists |
| Update | Payment status via the simulated gateway (pending/success/failed/void — decline control included); **staff reply to reviews** |
| Delete | Void payments (never physically deleted); delete eligible pending/failed test records; **admin hide/unhide reviews** (hidden reviews disappear from public pages; moderation recorded) |
| Business rules | One payment per order; duplicate payment blocked; no card data stored; success marks the order paid and creates the fulfilment (delivery → pending_assignment, pickup → pending); reviews recompute the restaurant rating; COD follows the same simulated confirmation flow |
| Tests | Original T78–T91, T132 |

## Shared system functions (distributed across the team)

Auth & RBAC (4 roles: Super Admin / Restaurant Owner+Staff / Customer / Rider),
session + CSRF, activity log, role dashboards, warm-cream responsive UI,
validation framework, and cross-module integration through shared database records.
