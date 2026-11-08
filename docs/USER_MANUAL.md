# OmniEats — User Manual

TAR UMT AMCS2194 · Chapter 4 material · step-by-step instructions per role

Demo accounts (password for all: **`Password@123`**):

| Role | Email | Sees |
|---|---|---|
| Super Admin | `superadmin@omnieats.com` | /admin dashboard |
| Owner — Nasi Lemak House (OPEN, Premium) | `owner.nasilemak@omnieats.com` | /owner dashboard |
| Owner — Pizza & Pasta Corner (CLOSED, expired Free) | `owner.pizza@omnieats.com` | /owner dashboard |
| Staff — Nasi Lemak House | `staff.nasilemak@omnieats.com` | /owner dashboard (menu + orders only) |
| Customer | `customer@omnieats.com` | /customer dashboard |

## 1. Customer

1. **Browse**: Home → Restaurants → pick a restaurant → menu is grouped by category
   (search box + category filter). OPEN/CLOSED badge shows availability.
2. **Cart**: on an available item click **Add** (quantity editable) → the nav bar shows
   the cart count. Only ONE restaurant per cart.
3. **Checkout**: Cart → Proceed to checkout → choose *Self-pickup* or *Delivery*
   (delivery needs address + phone; RM 5.00 fee shown live — restaurants with the
   🛵 Free Delivery badge waive the fee) → Place order.
4. **Pay** (simulated): order page → choose *Online* or *Cash* → Pay now.
   Tick **"Simulate card decline"** to demonstrate the failure path (no real money).
5. **Track**: the order page shows the ★ live timeline — leave it open and watch it
   update while the restaurant prepares the order.
6. **History**: My Orders lists every order; completed orders show their receipt.

## 2. Restaurant Owner / Staff

1. **Dashboard**: shows restaurant status (pending/approved), OPEN/CLOSED toggle,
   subscription card and quick links.
2. **Register restaurant** (first time): Dashboard → Register your restaurant → fill
   profile (including estimated delivery time and the free-delivery offer switch) →
   it becomes *pending platform approval* (admin approves). A **Free plan
   subscription is created automatically** so you can build the menu right away.
3. **Branches**: Branches → add/edit/delete (the only branch cannot be deleted;
   the plan limit applies).
4. **Menu**: Menu → categories then food items (photo, price, availability toggle).
5. **Subscription**: Subscription → pick Free/Basic/Premium → simulated payment →
   quota bars show branch/item usage; upgrade when a limit blocks you.
6. **Orders**: Orders → open an order → **Start preparing → Mark ready → Complete**;
   cancel with a reason. The customer's timeline updates automatically.
7. **Payments**: Payments → receipts, void (refund) successful payments.
   Completed records cannot be deleted (audit trail).
8. **Fulfilments**: Fulfilments → *Out for delivery* / *Ready for collection* → *Complete*.
9. **★ Close the restaurant**: Dashboard → Close restaurant — the public page keeps
   the profile visible but blocks all new orders.

## 3. Super Admin

1. **Dashboard**: platform stats (users, restaurants, active subscriptions, orders,
   successful-payment revenue).
2. **Users**: create accounts (role + tenant), search/filter, edit roles, deactivate.
3. **Tenants**: review registrations → **Approve**; deactivate/reactivate restaurants.
4. **Plans**: edit Free/Basic/Premium limits and prices; archive plans without
   active subscribers.
5. **Subscriptions**: monitor every subscription (status filter); the expiry sweep
   runs automatically.
6. **Activity**: the full platform audit trail (actor, action, entity, IP).

## 4. Common Tasks

| Task | Where |
|---|---|
| Change my profile / password | Profile (nav) |
| Register as a customer / owner | Home → Register / Sign in page |
| See the database diagram | phpMyAdmin → omnieats → Designer tab |

## 5. Troubleshooting

| Symptom | Cause / fix |
|---|---|
| "403 Invalid or missing CSRF token" | Session expired — sign in again and retry |
| "You need an active subscription…" | Restaurant's plan expired — choose a plan on the Subscription page |
| "Your … plan allows up to N" | Plan limit reached — upgrade the plan |
| Restaurant hidden from the public listing | Still pending approval, or deactivated — ask the admin |
