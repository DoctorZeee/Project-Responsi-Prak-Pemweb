-- ============================================================
-- CraftBazaar — Database Schema
-- Jalankan file ini di phpMyAdmin atau terminal MySQL:
--   mysql -u root -p < schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS craftbazaar
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE craftbazaar;

-- ============================================================
-- TABLE: users
-- Role: buyer | seller | admin
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,                      -- bcrypt hash
    role        ENUM('buyer','seller','admin') NOT NULL DEFAULT 'buyer',
    avatar      VARCHAR(255) DEFAULT NULL,
    balance     DECIMAL(12,2) NOT NULL DEFAULT 0.00,        -- saldo buyer
    bio         TEXT DEFAULT NULL,
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- TABLE: categories
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(50)  NOT NULL UNIQUE,               -- Skin, Resource Pack, Tools, Armor, ...
    slug        VARCHAR(50)  NOT NULL UNIQUE,
    icon        VARCHAR(100) DEFAULT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TABLE: items
-- Rarity: common | uncommon | rare | epic | legendary
-- ============================================================
CREATE TABLE IF NOT EXISTS items (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seller_id    INT UNSIGNED NOT NULL,
    category_id  INT UNSIGNED NOT NULL,
    name         VARCHAR(100) NOT NULL,
    slug         VARCHAR(120) NOT NULL UNIQUE,
    description  TEXT         DEFAULT NULL,
    price        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    rarity       ENUM('common','uncommon','rare','epic','legendary') NOT NULL DEFAULT 'common',
    image        VARCHAR(255) DEFAULT NULL,                 -- path file upload
    stock        INT UNSIGNED NOT NULL DEFAULT 1,
    is_approved  TINYINT(1)   NOT NULL DEFAULT 0,           -- admin approve
    is_active    TINYINT(1)   NOT NULL DEFAULT 1,
    total_sold   INT UNSIGNED NOT NULL DEFAULT 0,
    avg_rating   DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (seller_id)   REFERENCES users(id)      ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
);

-- ============================================================
-- TABLE: cart
-- ============================================================
CREATE TABLE IF NOT EXISTS cart (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    item_id    INT UNSIGNED NOT NULL,
    quantity   INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_cart (user_id, item_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: orders
-- Status: pending | paid | completed | cancelled
-- ============================================================
CREATE TABLE IF NOT EXISTS orders (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    buyer_id     INT UNSIGNED NOT NULL,
    total_price  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status       ENUM('pending','paid','completed','cancelled') NOT NULL DEFAULT 'pending',
    note         TEXT DEFAULT NULL,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: order_items
-- ============================================================
CREATE TABLE IF NOT EXISTS order_items (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id   INT UNSIGNED NOT NULL,
    item_id    INT UNSIGNED NOT NULL,
    seller_id  INT UNSIGNED NOT NULL,
    quantity   INT UNSIGNED NOT NULL DEFAULT 1,
    price      DECIMAL(12,2) NOT NULL,                      -- snapshot harga saat beli

    FOREIGN KEY (order_id)  REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id)   REFERENCES items(id)  ON DELETE RESTRICT,
    FOREIGN KEY (seller_id) REFERENCES users(id)  ON DELETE RESTRICT
);

-- ============================================================
-- TABLE: reviews
-- ============================================================
CREATE TABLE IF NOT EXISTS reviews (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id    INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    rating     TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment    TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_review (item_id, user_id),             -- 1 review per user per item
    FOREIGN KEY (item_id)  REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- SEED DATA — Categories
-- ============================================================
INSERT INTO categories (name, slug) VALUES
    ('Skin',          'skin'          ),
    ('Resource Pack', 'resource-pack' ),
    ('Tools',         'tools'         ),
    ('Armor',         'armor'         ),
    ('Weapon',        'weapon'        ),
    ('Building',      'building'      ),
    ('Food',          'food'          ),
    ('Mob',           'mob'           );

-- ============================================================
-- SEED DATA — Admin User (password: password)
-- ============================================================
INSERT INTO users (username, email, password, role) VALUES
    ('admin', 'admin@craftbazaar.com', '$2y$12$BQ6RvtsTs9bS.eG22VuAyOINCLulPRzvJ/qtdWQ2Jc5CR4dd02y0G', 'admin');

-- ============================================================
-- SEED DATA — Dummy Seller & Buyers
-- password semua: password
-- hash: $2y$12$BQ6RvtsTs9bS.eG22VuAyOINCLulPRzvJ/qtdWQ2Jc5CR4dd02y0G
-- ============================================================
INSERT INTO users (username, email, password, role, balance) VALUES
    ('steve_builder', 'steve@craftbazaar.com', '$2y$12$BQ6RvtsTs9bS.eG22VuAyOINCLulPRzvJ/qtdWQ2Jc5CR4dd02y0G', 'seller', 0),
    ('alex_crafter',  'alex@craftbazaar.com',  '$2y$12$BQ6RvtsTs9bS.eG22VuAyOINCLulPRzvJ/qtdWQ2Jc5CR4dd02y0G', 'seller', 0),
    ('notch_fan',     'notch@craftbazaar.com', '$2y$12$BQ6RvtsTs9bS.eG22VuAyOINCLulPRzvJ/qtdWQ2Jc5CR4dd02y0G', 'buyer',  500000),
    ('herobrine99',   'hero@craftbazaar.com',  '$2y$12$BQ6RvtsTs9bS.eG22VuAyOINCLulPRzvJ/qtdWQ2Jc5CR4dd02y0G', 'buyer',  250000);

-- ============================================================
-- SEED DATA — Dummy Items
-- ============================================================
INSERT INTO items (seller_id, category_id, name, slug, description, price, rarity, stock, is_approved) VALUES
    (2, 1, 'Dragon Skin Pro',       'dragon-skin-pro',       'Skin naga epic dengan animasi sayap',      75000,  'epic',      10, 1),
    (2, 1, 'Herobrine Classic',     'herobrine-classic',     'Skin klasik Herobrine versi HD',           25000,  'rare',      99, 1),
    (2, 2, 'Ultra Realism Pack',    'ultra-realism-pack',    'Resource pack 64x64 ultra realistis',      120000, 'legendary', 5,  1),
    (3, 3, 'Netherite Pickaxe+',    'netherite-pickaxe-plus','Custom pickaxe dengan enchant visual',     45000,  'uncommon',  20, 1),
    (3, 4, 'Diamond Armor Set',     'diamond-armor-set',     'Full set diamond armor custom texture',    95000,  'rare',      8,  1),
    (3, 5, 'Excalibur Sword',       'excalibur-sword',       'Pedang legendaris dengan efek glow',       200000, 'legendary', 3,  1),
    (2, 6, 'Medieval Castle Pack',  'medieval-castle-pack',  'Schematic kastil abad pertengahan',        60000,  'epic',      15, 1),
    (3, 1, 'Enderman Skin',         'enderman-skin',         'Skin Enderman slim dengan mata ungu',      15000,  'common',    50, 1);
