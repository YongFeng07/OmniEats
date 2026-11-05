# OmniEats — System Architecture

TAR UMT AMCS2194 · Chapter 3 material · matches the implemented code (commit `baec864`+)

## 1. Three-Tier Architecture

```
┌──────────────────────────────────────────────────────────────┐
│ PRESENTATION LAYER                                           │
│ HTML5 · CSS3 · Bootstrap 5 (vendored) · JavaScript (polling) │
│ resources/views/ (shared layout + area views)                │
│ public/assets/ · public/uploads/                             │
└──────────────────────────┬───────────────────────────────────┘
                           │ HTTP (front controller: public/index.php)
┌──────────────────────────▼───────────────────────────────────┐
│ APPLICATION LAYER (PHP 8)                                    │
│ routes/web.php → App\Core\Router                             │
│ Middleware: AuthMiddleware (RBAC guards) · CsrfMiddleware    │
│ Controllers (request handling)                               │
│ Services (business rules: cart, order, payment, subscription)│
│ Validators (server-side input validation)                    │
└──────────────────────────┬───────────────────────────────────┘
                           │ PDO prepared statements
┌──────────────────────────▼───────────────────────────────────┐
│ DATA LAYER                                                  │
│ MySQL/MariaDB — omnieats (shared, tenant-isolated schema)   │
│ Models (one class per table) · database/schema.sql          │
└──────────────────────────────────────────────────────────────┘
```

## 2. Request Lifecycle

1. `public/index.php` (front controller) — error handling, session start, `.env` config.
2. Global CSRF check for every POST (`CsrfMiddleware::verify()`).
3. `App\Core\Router` matches the URI (base-path aware — works under `/omnieats/public/`).
4. Route guards run (`guest` / `auth` / `roles:...` — `AuthMiddleware::apply()`).
5. Controller collects input → Validator → Service (business rules) → Model (PDO).
6. View renders into the shared layout with flash messages (success/error).

## 3. Directory Structure

```
app/
├── Controllers/   Admin/ · Owner/ · top-level (public + customer pages)
├── Core/          Router
├── Helpers/       functions.php (e, url, redirect, flash, csrf, view, activity, badge_class)
├── Middleware/    AuthMiddleware · CsrfMiddleware
├── Models/        Model (base) + one class per table
├── Services/      Auth · User · Restaurant · Branch · Category · FoodItem ·
│                  Subscription · Plan · Cart · Order · Payment · Fulfilment · Upload
└── Validators/    Validator (base) + one validator per form
config/            app.php (.env loader) · database.php (PDO singleton)
database/          schema.sql (canonical) · seed.sql
public/            index.php · .htaccess · assets/ · uploads/ (PHP execution blocked)
resources/views/   shared/ auth/ admin/ owner/ orders/ cart/ checkout/ restaurants/ foods/
routes/web.php     route table with per-route RBAC guards
```

## 4. Key Design Decisions

| Decision | Why |
|---|---|
| Front controller + route table | One entry point, consistent pattern for all 3 members, easy to explain |
| Plain PHP, no framework | Every line is explainable (Diploma level); nothing hidden |
| Services hold business rules | Controllers stay thin; rules are testable and not duplicated |
| Tenant id from session only | Browser values are never trusted (cross-tenant protection) |
| Snapshot columns (order_items, subscriptions) | History must survive later menu/plan changes |
| Soft delete (is_active/status) + payment void | Audit trail; completed financial records never deleted |
| Simulated payments only | Academic scope; no card data stored, no real gateway |
| Auto-expiry sweep on relevant requests | No cron needed — expiry is enforced when the platform runs |

## 5. Security Controls (implemented)

- PDO prepared statements everywhere (no string-built SQL with values).
- `password_hash()` / `password_verify()`; hashes never sent to the frontend.
- Session auth with `session_regenerate_id()` on login/logout; deactivated users lose access on the next request.
- CSRF token per session, verified for every POST.
- RBAC: route guards + ownership checks in controllers/services (never just hidden buttons).
- Tenant isolation: every tenant query filters by the session-derived `restaurant_id`.
- XSS: all dynamic output escaped with `e()`; JSON endpoint escapes via `json_encode`.
- Uploads: extension whitelist + real MIME check + 2 MB limit + random names + `.htaccess` blocks PHP in `uploads/`.
- Friendly 404/403/500 pages; real error details go to the log only.
