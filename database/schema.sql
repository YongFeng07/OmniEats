-- ============================================================
-- OmniEats — Multi-Restaurant Food Ordering SaaS Platform
-- MySQL / MariaDB schema (shared database, tenant-isolated)
-- TAR UMT AMCS2194 Mini Project
--
-- Conventions:
--   * InnoDB + utf8mb4 everywhere
--   * PK: id INT UNSIGNED AUTO_INCREMENT
--   * FK: <table>_id, named fk_<table>_<reference>
--   * Monetary values: DECIMAL(10,2) / DECIMAL(8,2)
--   * Status values are documented constants (see docs/DATABASE_DESIGN.md §7)
--   * Soft deletion: is_active / status columns. Completed payment and
--     subscription records are never physically deleted.
--
-- This file is canonical: the ERD and data dictionary in
-- docs/DATABASE_DESIGN.md must match it exactly.
-- ============================================================

CREATE DATABASE IF NOT EXISTS omnieats
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE omnieats;

-- Re-runnable: drop in reverse dependency order
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS fulfilments;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS order_status_history;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS carts;
DROP TABLE IF EXISTS subscriptions;
DROP TABLE IF EXISTS subscription_plans;
DROP TABLE IF EXISTS food_items;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS restaurant_branches;
DROP TABLE IF EXISTS restaurants;
DROP TABLE IF EXISTS user_roles;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

-- ------------------------------------------------------------
-- roles
-- ------------------------------------------------------------
CREATE TABLE roles (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(30)  NOT NULL COMMENT 'super_admin|restaurant_owner|restaurant_staff|customer',
  description VARCHAR(255) NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_roles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- users
-- ------------------------------------------------------------
CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(191) NOT NULL,
  password_hash VARCHAR(255) NOT NULL COMMENT 'PHP password_hash() output; never expose in responses',
  phone         VARCHAR(20)  NULL,
  restaurant_id INT UNSIGNED NULL COMMENT 'tenant link for owner/staff accounts',
  is_active     TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '0 = deactivated (soft delete)',
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_restaurant (restaurant_id),
  KEY idx_users_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- user_roles (composite PK: user_id + role_id)
-- ------------------------------------------------------------
CREATE TABLE user_roles (
  user_id    INT UNSIGNED NOT NULL,
  role_id    INT UNSIGNED NOT NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, role_id),
  CONSTRAINT fk_userroles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_userroles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- restaurants (tenants)
-- ------------------------------------------------------------
CREATE TABLE restaurants (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  owner_id       INT UNSIGNED NOT NULL,
  name           VARCHAR(100) NOT NULL,
  slug           VARCHAR(120) NOT NULL,
  description    TEXT         NULL,
  logo           VARCHAR(255) NULL COMMENT 'path under public/uploads/',
  cover_image    VARCHAR(255) NULL,
  phone          VARCHAR(20)  NULL,
  email          VARCHAR(191) NULL,
  address        VARCHAR(255) NULL,
  city           VARCHAR(100) NULL,
  state          VARCHAR(100) NULL,
  postcode       VARCHAR(10)  NULL,
  opening_hours  VARCHAR(255) NULL COMMENT 'e.g. Mon-Sun 10:00 - 22:00',
  rating         DECIMAL(2,1) NULL COMMENT 'aggregate rating 0.0-5.0; NULL = no ratings yet',
  delivery_minutes INT UNSIGNED NULL COMMENT 'estimated delivery time in minutes (owner-set)',
  free_delivery  TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'free delivery offer flag',
  is_open        TINYINT(1)   NOT NULL DEFAULT 1 COMMENT 'availability control: 1 open, 0 closed',
  status         VARCHAR(20)  NOT NULL DEFAULT 'pending' COMMENT 'pending|approved|deactivated',
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_restaurants_slug (slug),
  KEY idx_restaurants_owner (owner_id),
  KEY idx_restaurants_status (status),
  CONSTRAINT fk_restaurants_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Circular FK resolved here (users -> restaurants)
ALTER TABLE users
  ADD CONSTRAINT fk_users_restaurant
  FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE SET NULL;

-- ------------------------------------------------------------
-- restaurant_branches
-- ------------------------------------------------------------
CREATE TABLE restaurant_branches (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  restaurant_id INT UNSIGNED NOT NULL,
  name          VARCHAR(100) NOT NULL,
  address       VARCHAR(255) NULL,
  phone         VARCHAR(20)  NULL,
  opening_hours VARCHAR(255) NULL,
  is_main       TINYINT(1)   NOT NULL DEFAULT 0,
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_branches_restaurant (restaurant_id),
  CONSTRAINT fk_branches_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- categories
-- ------------------------------------------------------------
CREATE TABLE categories (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  restaurant_id INT UNSIGNED NOT NULL,
  name          VARCHAR(100) NOT NULL,
  description   VARCHAR(255) NULL,
  sort_order    INT          NOT NULL DEFAULT 0,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_restaurant_name (restaurant_id, name),
  KEY idx_categories_restaurant (restaurant_id),
  CONSTRAINT fk_categories_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- food_items
-- ------------------------------------------------------------
CREATE TABLE food_items (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  restaurant_id INT UNSIGNED NOT NULL COMMENT 'tenant reference (must equal its category''s restaurant)',
  category_id   INT UNSIGNED NOT NULL,
  name          VARCHAR(100) NOT NULL,
  description   TEXT         NULL,
  price         DECIMAL(8,2) NOT NULL,
  image         VARCHAR(255) NULL,
  is_available  TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_food_restaurant (restaurant_id),
  KEY idx_food_category (category_id),
  KEY idx_food_available (is_available),
  CONSTRAINT fk_food_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
  CONSTRAINT fk_food_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
  CONSTRAINT chk_food_price CHECK (price > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- subscription_plans
-- ------------------------------------------------------------
CREATE TABLE subscription_plans (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name           VARCHAR(50)  NOT NULL,
  price          DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT 'monthly price; 0.00 = free',
  billing_cycle  VARCHAR(20)  NOT NULL DEFAULT 'monthly',
  max_branches   INT UNSIGNED NOT NULL,
  max_food_items INT UNSIGNED NULL COMMENT 'NULL = unlimited',
  description    TEXT         NULL,
  features       TEXT         NULL COMMENT 'one feature per line',
  is_active      TINYINT(1)   NOT NULL DEFAULT 1,
  sort_order     INT          NOT NULL DEFAULT 0,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_plans_name (name),
  KEY idx_plans_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- subscriptions (history preserved; rows are never deleted)
-- ------------------------------------------------------------
CREATE TABLE subscriptions (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  restaurant_id INT UNSIGNED NOT NULL,
  plan_id       INT UNSIGNED NOT NULL,
  plan_name     VARCHAR(50)  NOT NULL COMMENT 'snapshot for history',
  price_paid    DECIMAL(8,2) NOT NULL COMMENT 'snapshot for history',
  starts_at     DATETIME     NOT NULL,
  ends_at       DATETIME     NOT NULL,
  status        VARCHAR(20)  NOT NULL DEFAULT 'active' COMMENT 'active|expired|cancelled',
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_subs_restaurant (restaurant_id),
  KEY idx_subs_status_ends (status, ends_at),
  CONSTRAINT fk_subs_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
  CONSTRAINT fk_subs_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- carts (one active cart per customer; single-restaurant rule)
-- ------------------------------------------------------------
CREATE TABLE carts (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  customer_id   INT UNSIGNED NOT NULL,
  restaurant_id INT UNSIGNED NOT NULL COMMENT 'cart may hold ONE restaurant''s items only',
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_carts_customer (customer_id),
  KEY idx_carts_restaurant (restaurant_id),
  CONSTRAINT fk_carts_customer FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_carts_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- cart_items
-- ------------------------------------------------------------
CREATE TABLE cart_items (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cart_id      INT UNSIGNED NOT NULL,
  food_item_id INT UNSIGNED NOT NULL,
  quantity     INT          NOT NULL DEFAULT 1,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cartitems_cart_food (cart_id, food_item_id),
  KEY idx_cartitems_food (food_item_id),
  CONSTRAINT fk_cartitems_cart FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
  CONSTRAINT fk_cartitems_food FOREIGN KEY (food_item_id) REFERENCES food_items(id) ON DELETE RESTRICT,
  CONSTRAINT chk_cartitems_qty CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- orders
-- ------------------------------------------------------------
CREATE TABLE orders (
  id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  order_no         VARCHAR(20)   NOT NULL COMMENT 'e.g. OE-20261105-0001',
  customer_id      INT UNSIGNED  NOT NULL,
  restaurant_id    INT UNSIGNED  NOT NULL COMMENT 'tenant reference',
  branch_id        INT UNSIGNED  NULL,
  fulfilment_type  VARCHAR(20)   NOT NULL COMMENT 'delivery|pickup',
  delivery_address VARCHAR(255)  NULL,
  delivery_phone   VARCHAR(20)   NULL,
  subtotal         DECIMAL(10,2) NOT NULL,
  delivery_fee     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  discount         DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'promo discount deducted from total',
  total            DECIMAL(10,2) NOT NULL,
  status           VARCHAR(30)   NOT NULL DEFAULT 'pending_payment'
                   COMMENT 'pending_payment|confirmed|preparing|ready|completed|cancelled',
  note             VARCHAR(255)  NULL,
  created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_orders_no (order_no),
  KEY idx_orders_customer (customer_id),
  KEY idx_orders_restaurant_status (restaurant_id, status),
  CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_orders_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE RESTRICT,
  CONSTRAINT fk_orders_branch FOREIGN KEY (branch_id) REFERENCES restaurant_branches(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- order_items (historical name/price snapshots)
-- ------------------------------------------------------------
CREATE TABLE order_items (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id     INT UNSIGNED NOT NULL,
  food_item_id INT UNSIGNED NULL COMMENT 'SET NULL if item is later deleted; snapshot survives',
  food_name    VARCHAR(100) NOT NULL COMMENT 'snapshot at purchase time',
  unit_price   DECIMAL(8,2) NOT NULL COMMENT 'snapshot at purchase time',
  quantity     INT          NOT NULL,
  subtotal     DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_orderitems_order (order_id),
  CONSTRAINT fk_orderitems_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_orderitems_food FOREIGN KEY (food_item_id) REFERENCES food_items(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- order_status_history (live order tracking feature)
-- ------------------------------------------------------------
CREATE TABLE order_status_history (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id    INT UNSIGNED NOT NULL,
  from_status VARCHAR(30)  NULL,
  to_status   VARCHAR(30)  NOT NULL,
  changed_by  INT UNSIGNED NULL COMMENT 'user who made the change; NULL for system',
  note        VARCHAR(255) NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_osh_order (order_id, created_at),
  CONSTRAINT fk_osh_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_osh_user FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- payments (audit trail; success records are never deleted)
-- ------------------------------------------------------------
CREATE TABLE payments (
  id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  order_id        INT UNSIGNED  NOT NULL,
  method          VARCHAR(20)   NOT NULL COMMENT 'cash|online',
  amount          DECIMAL(10,2) NOT NULL,
  status          VARCHAR(20)   NOT NULL DEFAULT 'pending' COMMENT 'pending|success|failed|void',
  transaction_ref VARCHAR(64)   NULL COMMENT 'simulated gateway reference',
  paid_at         DATETIME      NULL,
  created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_payments_order (order_id) COMMENT 'one payment record per order',
  KEY idx_payments_status (status),
  CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- fulfilments
-- ------------------------------------------------------------
CREATE TABLE fulfilments (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id     INT UNSIGNED NOT NULL,
  type         VARCHAR(20)  NOT NULL COMMENT 'delivery|pickup',
  address      VARCHAR(255) NULL COMMENT 'snapshot for delivery',
  phone        VARCHAR(20)  NULL,
  status       VARCHAR(30)  NOT NULL DEFAULT 'pending'
               COMMENT 'pending|out_for_delivery|ready_for_collection|completed|cancelled',
  completed_at DATETIME     NULL,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_fulfilments_order (order_id),
  KEY idx_fulfilments_status (status),
  CONSTRAINT fk_fulfilments_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- activity_logs (platform monitoring, supporting table)
-- ------------------------------------------------------------
CREATE TABLE activity_logs (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NULL,
  action     VARCHAR(50)  NOT NULL COMMENT 'e.g. login, create_food_item, approve_restaurant',
  entity     VARCHAR(50)  NULL,
  entity_id  INT UNSIGNED NULL,
  details    TEXT         NULL,
  ip_address VARCHAR(45)  NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_logs_user (user_id),
  KEY idx_logs_created (created_at),
  CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
