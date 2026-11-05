-- ============================================================
-- OmniEats — Seed data
-- TAR UMT AMCS2194 Mini Project
--
-- IMPORTANT: the bcrypt password hashes below are PLACEHOLDERS.
-- Replace each {{BCRYPT:...}} with the output of:
--     php -r "echo password_hash('Password@123', PASSWORD_DEFAULT), PHP_EOL;"
-- (All demo accounts use the password: Password@123)
--
-- Run AFTER schema.sql:
--     mysql -u root omnieats < seed.sql
-- ============================================================

USE omnieats;

-- ------------------------------------------------------------
-- Roles
-- ------------------------------------------------------------
INSERT INTO roles (name, description) VALUES
('super_admin',       'Platform administrator with full platform access'),
('restaurant_owner',  'Owns and manages one restaurant tenant'),
('restaurant_staff',  'Staff of a restaurant; manages menu and orders'),
('customer',          'Registered customer who orders food');

-- ------------------------------------------------------------
-- Subscription plans (limits configurable by Super Admin)
-- ------------------------------------------------------------
INSERT INTO subscription_plans
  (name, price, billing_cycle, max_branches, max_food_items, description, features, sort_order)
VALUES
('Free',    0.00,   'monthly', 1,  10,   'For new restaurants getting started',          '1 branch\nUp to 10 food items\nBasic order management', 1),
('Basic',   49.90,  'monthly', 3,  50,   'For growing restaurants',                       '3 branches\nUp to 50 food items\nOrder management\nBasic sales reporting', 2),
('Premium', 129.90, 'monthly', 10, NULL, 'For established restaurants (unlimited menu)',  '10 branches\nUnlimited food items\nOrder management\nAdvanced reporting', 3);

-- ------------------------------------------------------------
-- Users (passwords: Password@123 — replace hashes first)
-- ------------------------------------------------------------
INSERT INTO users (name, email, password_hash, phone, is_active) VALUES
('Super Admin',      'superadmin@omnieats.com',     '{{BCRYPT:Password@123}}', NULL,          1),
('Ali Restaurant',   'owner.nasilemak@omnieats.com','{{BCRYPT:Password@123}}', '012-3456789', 1),
('Bella Pizza',      'owner.pizza@omnieats.com',    '{{BCRYPT:Password@123}}', '013-4567890', 1),
('Cindy Staff',      'staff.nasilemak@omnieats.com','{{BCRYPT:Password@123}}', '014-5678901', 1),
('Danny Customer',   'customer@omnieats.com',       '{{BCRYPT:Password@123}}', '016-1234567', 1),
('Elaine Customer',  'alice@omnieats.com',          '{{BCRYPT:Password@123}}', '017-2345678', 1);

-- Role assignment
INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u JOIN roles r
WHERE (u.email = 'superadmin@omnieats.com'       AND r.name = 'super_admin')
   OR (u.email = 'owner.nasilemak@omnieats.com'  AND r.name = 'restaurant_owner')
   OR (u.email = 'owner.pizza@omnieats.com'      AND r.name = 'restaurant_owner')
   OR (u.email = 'staff.nasilemak@omnieats.com'  AND r.name = 'restaurant_staff')
   OR (u.email IN ('customer@omnieats.com','alice@omnieats.com') AND r.name = 'customer');

-- ------------------------------------------------------------
-- Restaurants (tenants)
-- ------------------------------------------------------------
INSERT INTO restaurants
  (owner_id, name, slug, description, phone, email, address, city, state, postcode,
   opening_hours, is_open, status)
VALUES
((SELECT id FROM users WHERE email = 'owner.nasilemak@omnieats.com'),
 'Nasi Lemak House', 'nasi-lemak-house',
 'Authentic Malaysian nasi lemak and mamak favourites, cooked fresh daily.',
 '03-7876 1234', 'hello@nasilemakhouse.my',
 '12, Jalan SS2/61', 'Petaling Jaya', 'Selangor', '47300',
 'Mon-Sun 10:00 - 22:00', 1, 'approved'),

((SELECT id FROM users WHERE email = 'owner.pizza@omnieats.com'),
 'Pizza & Pasta Corner', 'pizza-pasta-corner',
 'Hand-tossed pizzas and classic Italian pasta in a cosy corner restaurant.',
 '03-7956 4321', 'hello@pizzapastacorner.my',
 '88, Jalan SS15/4B', 'Subang Jaya', 'Selangor', '47500',
 'Tue-Sun 11:30 - 22:00', 0, 'approved');

-- Tenant membership for owner/staff accounts
UPDATE users SET restaurant_id = (SELECT id FROM restaurants WHERE slug = 'nasi-lemak-house')
WHERE email IN ('owner.nasilemak@omnieats.com', 'staff.nasilemak@omnieats.com');

UPDATE users SET restaurant_id = (SELECT id FROM restaurants WHERE slug = 'pizza-pasta-corner')
WHERE email = 'owner.pizza@omnieats.com';

-- ------------------------------------------------------------
-- Branches
-- ------------------------------------------------------------
INSERT INTO restaurant_branches (restaurant_id, name, address, phone, opening_hours, is_main, is_active)
VALUES
((SELECT id FROM restaurants WHERE slug = 'nasi-lemak-house'),
 'Nasi Lemak House — SS2 (Main)', '12, Jalan SS2/61, Petaling Jaya', '03-7876 1234', 'Mon-Sun 10:00 - 22:00', 1, 1),

((SELECT id FROM restaurants WHERE slug = 'pizza-pasta-corner'),
 'Pizza & Pasta — SS15 (Main)', '88, Jalan SS15/4B, Subang Jaya', '03-7956 4321', 'Tue-Sun 11:30 - 22:00', 1, 1);

-- ------------------------------------------------------------
-- Categories
-- ------------------------------------------------------------
INSERT INTO categories (restaurant_id, name, description, sort_order) VALUES
((SELECT id FROM restaurants WHERE slug = 'nasi-lemak-house'), 'Mains',    'Nasi lemak and main dishes', 1),
((SELECT id FROM restaurants WHERE slug = 'nasi-lemak-house'), 'Sides',    'Mamak-style side dishes',    2),
((SELECT id FROM restaurants WHERE slug = 'nasi-lemak-house'), 'Drinks',   'Hot and cold beverages',     3),
((SELECT id FROM restaurants WHERE slug = 'pizza-pasta-corner'), 'Pizza',  'Hand-tossed pizzas',         1),
((SELECT id FROM restaurants WHERE slug = 'pizza-pasta-corner'), 'Pasta',  'Classic Italian pasta',      2),
((SELECT id FROM restaurants WHERE slug = 'pizza-pasta-corner'), 'Beverages', 'Soft drinks',            3);

-- ------------------------------------------------------------
-- Food items
-- ------------------------------------------------------------
INSERT INTO food_items (restaurant_id, category_id, name, description, price, is_available) VALUES
-- Nasi Lemak House / Mains
((SELECT id FROM restaurants WHERE slug = 'nasi-lemak-house'),
 (SELECT c.id FROM categories c WHERE c.name = 'Mains' AND c.restaurant_id = (SELECT id FROM restaurants WHERE slug = 'nasi-lemak-house')),
 'Nasi Lemak Ayam Goreng', 'Coconut rice with crispy fried chicken, sambal, egg, peanuts and anchovies.', 8.90, 1),
((SELECT id FROM restaurants WHERE slug = 'nasi-lemak-house'),
 (SELECT c.id FROM categories c WHERE c.name = 'Mains' AND c.restaurant_id = (SELECT id FROM restaurants WHERE slug = 'nasi-lemak-house')),
 'Nasi Lemak Rendang', 'Coconut rice with slow-cooked beef rendang and traditional condiments.', 9.90, 1),
-- Nasi Lemak House / Sides
((SELECT id FROM restaurants WHERE slug = 'nasi-lemak-house'),
 (SELECT c.id FROM categories c WHERE c.name = 'Sides' AND c.restaurant_id = (SELECT id FROM restaurants WHERE slug = 'nasi-lemak-house')),
 'Roti Canai', 'Flaky flatbread served with dhal curry.', 2.50, 1),
((SELECT id FROM restaurants WHERE slug = 'nasi-lemak-house'),
 (SELECT c.id FROM categories c WHERE c.name = 'Sides' AND c.restaurant_id = (SELECT id FROM restaurants WHERE slug = 'nasi-lemak-house')),
 'Keropok Lekor', 'Crispy fried fish crackers with sweet chilli dip.', 4.00, 1),
-- Nasi Lemak House / Drinks
((SELECT id FROM restaurants WHERE slug = 'nasi-lemak-house'),
 (SELECT c.id FROM categories c WHERE c.name = 'Drinks' AND c.restaurant_id = (SELECT id FROM restaurants WHERE slug = 'nasi-lemak-house')),
 'Teh Tarik', 'Malaysian pulled milk tea, hot.', 2.20, 1),
((SELECT id FROM restaurants WHERE slug = 'nasi-lemak-house'),
 (SELECT c.id FROM categories c WHERE c.name = 'Drinks' AND c.restaurant_id = (SELECT id FROM restaurants WHERE slug = 'nasi-lemak-house')),
 'Milo Ice', 'Iced chocolate malt drink.', 3.00, 1),
-- Pizza & Pasta Corner / Pizza
((SELECT id FROM restaurants WHERE slug = 'pizza-pasta-corner'),
 (SELECT c.id FROM categories c WHERE c.name = 'Pizza' AND c.restaurant_id = (SELECT id FROM restaurants WHERE slug = 'pizza-pasta-corner')),
 'Margherita Pizza', 'Classic tomato, mozzarella and fresh basil on a hand-tossed base.', 22.90, 1),
((SELECT id FROM restaurants WHERE slug = 'pizza-pasta-corner'),
 (SELECT c.id FROM categories c WHERE c.name = 'Pizza' AND c.restaurant_id = (SELECT id FROM restaurants WHERE slug = 'pizza-pasta-corner')),
 'Pepperoni Pizza', 'Loaded with beef pepperoni and extra mozzarella.', 27.90, 1),
-- Pizza & Pasta Corner / Pasta
((SELECT id FROM restaurants WHERE slug = 'pizza-pasta-corner'),
 (SELECT c.id FROM categories c WHERE c.name = 'Pasta' AND c.restaurant_id = (SELECT id FROM restaurants WHERE slug = 'pizza-pasta-corner')),
 'Carbonara', 'Creamy egg-based sauce with smoked beef and parmesan.', 18.90, 1),
-- Pizza & Pasta Corner / Beverages
((SELECT id FROM restaurants WHERE slug = 'pizza-pasta-corner'),
 (SELECT c.id FROM categories c WHERE c.name = 'Beverages' AND c.restaurant_id = (SELECT id FROM restaurants WHERE slug = 'pizza-pasta-corner')),
 'Cola', 'Chilled soft drink, 330ml.', 3.50, 1);

-- ------------------------------------------------------------
-- Subscriptions
--   Nasi Lemak House: Premium, active for 60 days
--   Pizza & Pasta Corner: Free, EXPIRED (demonstrates auto-expiry handling)
-- ------------------------------------------------------------
INSERT INTO subscriptions (restaurant_id, plan_id, plan_name, price_paid, starts_at, ends_at, status)
VALUES
((SELECT id FROM restaurants WHERE slug = 'nasi-lemak-house'),
 (SELECT id FROM subscription_plans WHERE name = 'Premium'), 'Premium', 129.90,
 NOW(), DATE_ADD(NOW(), INTERVAL 60 DAY), 'active'),

((SELECT id FROM restaurants WHERE slug = 'pizza-pasta-corner'),
 (SELECT id FROM subscription_plans WHERE name = 'Free'), 'Free', 0.00,
 DATE_SUB(NOW(), INTERVAL 40 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY), 'expired');
