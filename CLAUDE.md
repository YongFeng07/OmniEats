# CLAUDE.md — OmniEats

Source of truth for this repository. TAR UMT AMCS2194 mini project.
Stack: **PHP 8 + PDO + MySQL/MariaDB via XAMPP + Bootstrap 5 + vanilla JS** —
no frameworks, no ORM, nothing outside the Diploma course scope.

## Final module allocation (approved 2026-11-09)

| Member | Modules | Unique feature |
|---|---|---|
| Lim Li Zhe | M1 Restaurant Menu, M2 Promotion & Voucher | Smart Voucher Validation |
| Wong Yong Feng | M1 Customer Profile, M2 Order Processing & Checkout | Order Status Timeline |
| Leong Hong Jun | M1 Delivery & Rider, M2 Payment & Review | Admin Manual Rider Assignment |

Four roles: Super Admin, Restaurant Owner/Staff, Rider, Customer.

## Status: ALL SIX MODULES IMPLEMENTED & TESTED (277 executed checks, 0 failures)

Remaining work is documentation/screenshots only (Chapter 2/3/4 reports).

## Architecture

- Three-tier: views (`resources/views`) → controllers/validators/services
  (`app/`) → PDO models (`app/Models`, one per table) → MySQL/MariaDB `omnieats`
  (24 tables, `database/schema.sql` is canonical).
- Front controller `public/index.php` + `routes/web.php` (per-route guards:
  guest / auth / roles:...). CSRF on every POST. Tenant id ALWAYS from the
  session.
- Business rules live in services (OrderService transitions, VoucherService
  validation, RiderService assignment, PaymentService gateway).

## Key implementation facts (read before changing anything)

- PDO uses native prepared statements: NEVER reuse a named placeholder twice in
  one query (HY093) — use `:q1/:q2/:q3`.
- `User::setRoles` opens its own transaction; inside an existing transaction use
  `User::addRole`.
- Fulfilment statuses: delivery → pending_assignment|assigned|accepted|
  picked_up|out_for_delivery|delivered|cancelled; pickup → pending|
  ready_for_collection|completed|cancelled. Delivery fulfilments start at
  pending_assignment when the payment succeeds.
- Order statuses: pending_payment|confirmed|preparing|ready|completed|cancelled.
  Delivery orders are completed BY THE RIDER (delivered step), not the
  restaurant. The customer timeline merges order history + fulfilment
  timestamps (`shared/order_timeline.php`); the poll endpoint
  `/orders/{id}/status` returns both statuses.
- Voucher discount: session holds the voucher id; validated in the tray, on
  tap, and inside the checkout transaction (usage row + counter, atomic).
- Rider assignment: admin-only; eligibility = active account + active profile +
  available + no other active delivery; every attempt in
  rider_assignment_history (Reassigned marks the previous one).

## Test suites (run them — never claim untested)

Bash scripts in `%LOCALAPPDATA%\Temp\`: `oe-sweep-get.sh` (83 GET checks),
`oe-post-sweep.sh` (34 POST checks), `oe-vouchers.sh` (41), `oe-wong.sh` (14),
`oe-rider.sh` (50), `oe-exercise-models.php` (model queries). Each script has
an idempotency cleanup at the top.

## Demo accounts (Password@123)

superadmin@omnieats.com · owner.nasilemak@omnieats.com · owner.pizza@omnieats.com
· staff.nasilemak@omnieats.com · rider.raj@omnieats.com (WXY 1234) ·
customer@omnieats.com

## Run

XAMPP MySQL only → open http://localhost/omnieats/ (Apache junction
C:\xampp\htdocs\omnieats → this repo). Dev server: `C:\xampp\php\php.exe -S
127.0.0.1:8080 -t public public/index.php`.

## Docs

`docs/` — PROJECT_BLUEPRINT (final revision), MODULE_SPECIFICATIONS,
DATABASE_DESIGN (ERD + dictionary + 3NF), DFD, CLASS_DIAGRAM, TEST_PLAN,
USER_MANUAL, SYSTEM_ARCHITECTURE + audit/gap analyses. Keep docs matching the
implementation.
