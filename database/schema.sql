-- =============================================================
-- Gas @ Midway Mews — MySQL Schema
-- =============================================================
-- Run this once to bootstrap the database.
-- Default admin password is "changeme123" (bcrypt hashed).
-- CHANGE IT IMMEDIATELY in production via the admin UI or SQL.
-- =============================================================

CREATE DATABASE IF NOT EXISTS midway_gas
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE midway_gas;

-- -------------------------------------------------------------
-- Admin users
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'admin',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -------------------------------------------------------------
-- Cylinder prices
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cylinder_prices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cylinder_size VARCHAR(20) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    is_popular BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- -------------------------------------------------------------
-- Stock status (one row per cylinder size)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS stock_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cylinder_price_id INT NOT NULL,
    status ENUM('available', 'low_stock', 'confirm_first', 'out_of_stock') DEFAULT 'available',
    notes VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cylinder_price_id) REFERENCES cylinder_prices(id) ON DELETE CASCADE
);

-- -------------------------------------------------------------
-- Customer reservations
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(30) NOT NULL,
    cylinder_size VARCHAR(20) NOT NULL,
    request_type ENUM('refill', 'exchange', 'availability_check') NOT NULL,
    preferred_collection_time VARCHAR(100),
    notes TEXT,
    status ENUM('pending', 'confirmed', 'collected', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- -------------------------------------------------------------
-- General customer enquiries
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS enquiries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100),
    phone_number VARCHAR(30),
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'closed') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -------------------------------------------------------------
-- Business settings (key/value)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS business_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================================
-- Seed data
-- =============================================================

-- Default admin user. Email: admin@midwaygas.local — Password: changeme123
-- This bcrypt hash is real and will work; CHANGE IT IMMEDIATELY in production.
INSERT IGNORE INTO users (full_name, email, password_hash, role)
VALUES
('Admin', 'admin@midwaygas.local',
 '$2y$10$fHtea7zblnCqJLL6V38s7u7btcXrKrjdDDoymIISJaJr7KnEbveFK',
 'admin');

-- Cylinder prices
INSERT IGNORE INTO cylinder_prices
(cylinder_size, price, is_popular, display_order)
VALUES
('1.5kg',   70.00, FALSE,  1),
('1.7kg',   75.00, FALSE,  2),
('2.5kg',  110.00, FALSE,  3),
('3kg',    125.00, TRUE,   4),
('4.5kg',  170.00, FALSE,  5),
('5kg',    200.00, TRUE,   6),
('6kg',    230.00, FALSE,  7),
('7kg',    285.00, FALSE,  8),
('9kg',    330.00, TRUE,   9),
('12kg',   450.00, FALSE, 10),
('14kg',   555.00, FALSE, 11),
('19kg',   660.00, TRUE,  12),
('48kg',  1660.00, TRUE,  13);

-- Default stock = available for every size
INSERT IGNORE INTO stock_status (cylinder_price_id, status)
SELECT id, 'available' FROM cylinder_prices
WHERE id NOT IN (SELECT cylinder_price_id FROM stock_status);

-- Business settings
INSERT IGNORE INTO business_settings (setting_key, setting_value) VALUES
('business_name',    'Gas @ Midway Mews'),
('primary_phone',    '073 068 1590'),
('secondary_phone',  '079 107 5377'),
('whatsapp_number',  '27730681590'),
('whatsapp_alt',     '27791075377'),
('address',          'Midway Mews'),
('trading_hours',    'Monday to Saturday: 08:00 - 17:00'),
('latitude',         '-25.98688339781942'),
('longitude',        '28.111762832127948'),
('google_maps_url',  'https://www.google.com/maps/dir/?api=1&destination=-25.98688339781942,28.111762832127948');
