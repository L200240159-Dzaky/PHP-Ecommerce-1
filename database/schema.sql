CREATE DATABASE IF NOT EXISTS ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecommerce;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('guest', 'member', 'admin') NOT NULL DEFAULT 'member',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NULL,
    name VARCHAR(180) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL DEFAULT NULL,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (name, email, password, role, created_at) VALUES
('Admin', 'admin@example.com', '$2y$10$oWrYk9CJZwkbhI2mNg0bbedSYM/jQ5KqnBRnQyBKtT0gFDYNmpws6', 'admin', NOW())
ON DUPLICATE KEY UPDATE email = email;

INSERT INTO categories (name, slug, created_at, updated_at)
VALUES
('Electronics', 'electronics', NOW(), NOW()),
('Books', 'books', NOW(), NOW()),
('Fashion', 'fashion', NOW(), NOW()),
('Home & Garden', 'home-garden', NOW(), NOW()),
('Sports & Outdoors', 'sports-outdoors', NOW(), NOW()),
('Toys & Games', 'toys-games', NOW(), NOW()),
('Health & Beauty', 'health-beauty', NOW(), NOW()),
('Automotive', 'automotive', NOW(), NOW()),
('Music & Instruments', 'music-instruments', NOW(), NOW()),
('Office Supplies', 'office-supplies', NOW(), NOW());

INSERT INTO products (
    category_id,
    name,
    description,
    price,
    created_at,
    updated_at
)
VALUES
(
    1,
    'Wireless Mouse',
    'Ergonomic wireless mouse with USB receiver.',
    199000.00,
    NOW(),
    NOW()
),
(
    1,
    'Mechanical Keyboard',
    'RGB mechanical keyboard with blue switches.',
    799000.00,
    NOW(),
    NOW()
),
(
    2,
    'Clean Code',
    'A Handbook of Agile Software Craftsmanship.',
    350000.00,
    NOW(),
    NOW()
),
(
    3,
    'Cotton T-Shirt',
    'Comfortable 100% cotton t-shirt.',
    129000,
    NOW(),
    NOW()
),
(
    4,
    'Garden Hose',
    'Durable garden hose with adjustable nozzle.',
    299000.00,
    NOW(),
    NOW()
),
(
    5,
    'Yoga Mat',
    'Non-slip yoga mat with carrying strap.',
    199000.0,
    NOW(),
    NOW()
),
(
    6,
    'Building Blocks Set',
    'Creative building blocks for kids.',
    499000.00,
    NOW(),
    NOW()
),
(
    7,
    'Skincare Set',
    'Complete skincare set for all skin types.',
    899000.00,
    
    NOW(),
    NOW()
),
(
    8,
    'Car Vacuum Cleaner',
    'Portable car vacuum cleaner with strong suction.',
    299000.00,
    NOW(),
    NOW()
),
(
    9,
    'Acoustic Guitar',
    'Full-size acoustic guitar with case.',
    1499000.00,
    NOW(),
    NOW()
),
(
    10,
    'Office Chair',
    'Ergonomic office chair with adjustable height.',
    599000.00,
    NOW(),
    NOW()
)
;