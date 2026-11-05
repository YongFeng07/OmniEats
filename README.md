# OmniEats — Multi-Restaurant Food Ordering SaaS Platform

TAR UMT **AMCS2194** Mini Project · Web Application Development · Team of 3

OmniEats is a shared multi-tenant SaaS platform where independent restaurants register,
subscribe to a plan, and run their own online food ordering business — menus, orders,
payments and fulfilment — inside one web application and one MySQL database.

## Technology Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.x, PDO, MVC-style structure (controllers / services / models) |
| Database | MySQL / MariaDB (XAMPP) — shared schema, tenant-isolated |
| Frontend | HTML5, CSS3, JavaScript, Bootstrap 5 (vendored locally) |
| Server | Apache (XAMPP) — front controller via `public/index.php` |

No frameworks. Plain PHP so every line can be explained in the report.

## Project Documentation

| Document | Contents |
|---|---|
| [docs/PROJECT_BLUEPRINT.md](docs/PROJECT_BLUEPRINT.md) | Understanding, architecture, 6 modules, task allocation, permission matrix, workflows, roadmap, risks |
| [docs/DATABASE_DESIGN.md](docs/DATABASE_DESIGN.md) | ERD, data dictionary, relationships, 3NF, status values |
| [database/schema.sql](database/schema.sql) | Canonical SQL schema (17 tables) |
| [database/seed.sql](database/seed.sql) | Seed data (roles, plans, accounts, demo restaurants) |

## Setup (Windows + XAMPP)

1. **Start MySQL** — XAMPP Control Panel → start *MySQL* (MariaDB 10.4+).
2. **Create the database** — phpMyAdmin → SQL tab, or command line:
   ```bash
   mysql -u root < database/schema.sql
   mysql -u root omnieats < database/seed.sql
   ```
3. **Configure environment** — copy `.env.example` to `.env` (XAMPP defaults work
   out of the box: `root`, empty password, database `omnieats`).
4. **Serve the app** — either:
   - **Apache (XAMPP):** place/copy the project into `C:\xampp\htdocs\omnieats`
     and open `http://localhost/omnieats/public/` (the front controller derives its
     base URL automatically), or
   - **PHP built-in server** (development only):
     ```bash
     php -S localhost:8080 -t public public/index.php
     ```

### Demo accounts (password for all: `Password@123`)

| Role | Email |
|---|---|
| Super Admin | `superadmin@omnieats.com` |
| Restaurant Owner (Nasi Lemak House) | `owner.nasilemak@omnieats.com` |
| Restaurant Owner (Pizza & Pasta Corner) | `owner.pizza@omnieats.com` |
| Restaurant Staff | `staff.nasilemak@omnieats.com` |
| Customer | `customer@omnieats.com` |

## Structure

```
app/            Controllers, Models, Services, Middleware, Validators, Helpers, Core (Router)
config/         database.php (PDO), app.php (.env loader)
database/       schema.sql, seed.sql
public/         index.php (front controller), assets/, uploads/
resources/views shared layout + auth / customer / owner / admin / errors
routes/web.php  route table with RBAC guards
docs/           assignment documentation
```

## Security

- PDO prepared statements everywhere · `password_hash()` / `password_verify()`
- Session authentication with regeneration on login · CSRF token on every POST
- RBAC middleware per route + ownership checks in controllers (tenant isolation)
- Output escaping (`e()`) against XSS · uploads dir blocks PHP execution
- Friendly error pages; real errors go to the log only

## Development Status

- [x] Phase 1 — repository analysis
- [x] Phase 2 — planning (blueprint, database design)
- [x] Phase 3 — database implementation (schema + seed verified in MariaDB)
- [x] Phase 4 — core application (routing, auth, sessions, CSRF, RBAC, layouts)
- [ ] Phase 5 — module development (M1 → M6)
  - **M1 User & Auth done**: registration (customer + owner), login, profile, password change, admin user CRUD with search/filter, role assignment, soft delete
  - **M2 Restaurant & Branch done**: restaurant registration (tenant + approval), profile/branch CRUD, image uploads, admin approval lifecycle, ★ OPEN/CLOSED availability control
  - **M3 Menu & Catalogue done**: category & food item CRUD, per-tenant unique names, delete protection for non-empty categories, availability toggles, image uploads, public menu with search + category filter
  - **M4 Subscription done**: plan CRUD + archive protection, simulated subscription payments, renew/upgrade/cancel with full history, ★ automatic expiry sweep + branch/food-item quota enforcement
  - **M5 Ordering done**: single-restaurant cart, backend-computed checkout with availability re-checks, order history, restaurant order management, ★ live status timeline with validated transitions + JS polling
- [ ] Phase 6 — integration testing
- [ ] Phase 7 — UI/UX polish
- [ ] Phase 8 — final testing & documentation
