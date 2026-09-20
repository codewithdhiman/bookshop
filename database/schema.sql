CREATE DATABASE IF NOT EXISTS bookshop
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE bookshop;

-- =========================================================
-- USERS
-- =========================================================
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    role ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_users_role (role),
    INDEX idx_users_status (status)
) ENGINE=InnoDB;


-- =========================================================
-- CATEGORIES
-- =========================================================
CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description TEXT NULL,
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_categories_status (status)
) ENGINE=InnoDB;


-- =========================================================
-- PRODUCTS / BOOKS
-- =========================================================
CREATE TABLE products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NOT NULL,

    title VARCHAR(255) NOT NULL,
    slug VARCHAR(280) NOT NULL UNIQUE,
    isbn VARCHAR(20) NULL UNIQUE,
    author VARCHAR(200) NOT NULL,
    publisher VARCHAR(200) NULL,

    description TEXT NULL,

    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_price DECIMAL(10,2) NULL,

    stock INT UNSIGNED NOT NULL DEFAULT 0,

    cover_image VARCHAR(255) NULL,
    youtube_video_url VARCHAR(500) NULL,

    status TINYINT(1) NOT NULL DEFAULT 1,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id)
        REFERENCES categories(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    INDEX idx_products_category (category_id),
    INDEX idx_products_author (author),
    INDEX idx_products_status (status),
    INDEX idx_products_featured (is_featured),
    INDEX idx_products_title (title)
) ENGINE=InnoDB;


-- =========================================================
-- PRODUCT IMAGES
-- =========================================================
CREATE TABLE product_images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    image VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_product_images_product
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_product_images_product (product_id)
) ENGINE=InnoDB;


-- =========================================================
-- ADDRESSES
-- =========================================================
CREATE TABLE addresses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,

    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255) NULL,

    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    pincode VARCHAR(10) NOT NULL,

    is_default TINYINT(1) NOT NULL DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_addresses_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_addresses_user (user_id)
) ENGINE=InnoDB;


-- =========================================================
-- ORDERS
-- =========================================================
CREATE TABLE orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    user_id BIGINT UNSIGNED NULL,

    order_number VARCHAR(30) NOT NULL UNIQUE,

    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    shipping_charge DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    payment_method ENUM(
        'cod',
        'online'
    ) NOT NULL DEFAULT 'cod',

    payment_status ENUM(
        'pending',
        'paid',
        'failed',
        'refunded'
    ) NOT NULL DEFAULT 'pending',

    order_status ENUM(
        'pending',
        'confirmed',
        'processing',
        'shipped',
        'delivered',
        'cancelled'
    ) NOT NULL DEFAULT 'pending',

    -- Shipping address snapshot
    shipping_name VARCHAR(150) NOT NULL,
    shipping_phone VARCHAR(20) NOT NULL,
    shipping_address VARCHAR(500) NOT NULL,
    shipping_city VARCHAR(100) NOT NULL,
    shipping_state VARCHAR(100) NOT NULL,
    shipping_pincode VARCHAR(10) NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    INDEX idx_orders_user (user_id),
    INDEX idx_orders_status (order_status),
    INDEX idx_orders_payment_status (payment_status),
    INDEX idx_orders_created_at (created_at)
) ENGINE=InnoDB;


-- =========================================================
-- ORDER ITEMS
-- =========================================================
CREATE TABLE order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,

    -- Snapshot of product information at purchase time
    product_name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    total DECIMAL(10,2) NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id)
        REFERENCES orders(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    INDEX idx_order_items_order (order_id),
    INDEX idx_order_items_product (product_id)
) ENGINE=InnoDB;


-- =========================================================
-- SAMPLE ADMIN
-- =========================================================
-- Password: password
-- IMPORTANT: Change this password immediately in a real system.
INSERT INTO users
    (name, email, password, role, status)
VALUES
    (
        'Administrator',
        'admin@bookshop.local',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC9xq3R2K4r3q3q3q3q3',
        'admin',
        1
    );


-- =========================================================
-- SAMPLE CATEGORIES
-- =========================================================
INSERT INTO categories (name, slug, description)
VALUES
    ('Fiction', 'fiction', 'Novels and fictional stories'),
    ('Non-Fiction', 'non-fiction', 'Books based on real events and knowledge'),
    ('Technology', 'technology', 'Programming, software and technology books'),
    ('Business', 'business', 'Business, management and entrepreneurship'),
    ('Self Help', 'self-help', 'Personal development and self improvement'),
    ('Children', 'children', 'Books for children'),
    ('Academic', 'academic', 'School and college academic books'),
    ('Competitive Exams', 'competitive-exams', 'Books for competitive examinations');


-- =========================================================
-- SAMPLE PRODUCTS
-- =========================================================
INSERT INTO products
    (
        category_id,
        title,
        slug,
        isbn,
        author,
        publisher,
        description,
        price,
        discount_price,
        stock,
        cover_image,
        status
    )
VALUES
(
    5,
    'Atomic Habits',
    'atomic-habits',
    '9780735211292',
    'James Clear',
    'Avery',
    'An easy and proven way to build good habits and break bad ones.',
    799.00,
    599.00,
    25,
    NULL,
    1
),
(
    4,
    'The Psychology of Money',
    'the-psychology-of-money',
    '9780857197689',
    'Morgan Housel',
    'Harriman House',
    'Timeless lessons on wealth, greed and happiness.',
    699.00,
    549.00,
    30,
    NULL,
    1
),
(
    3,
    'Clean Code',
    'clean-code',
    '9780132350884',
    'Robert C. Martin',
    'Prentice Hall',
    'A handbook of agile software craftsmanship.',
    999.00,
    799.00,
    15,
    NULL,
    1
),
(
    1,
    'Harry Potter and the Philosopher''s Stone',
    'harry-potter-philosophers-stone',
    '9781408855652',
    'J.K. Rowling',
    'Bloomsbury',
    'The first book in the Harry Potter series.',
    599.00,
    449.00,
    20,
    NULL,
    1
),
(
    6,
    'The Very Hungry Caterpillar',
    'the-very-hungry-caterpillar',
    '9780241003008',
    'Eric Carle',
    'Puffin',
    'A classic picture book for young readers.',
    399.00,
    299.00,
    18,
    NULL,
    1
);


-- =========================================================
-- SEO METADATA
-- =========================================================
CREATE TABLE IF NOT EXISTS seo_meta (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL,
    entity_id BIGINT UNSIGNED DEFAULT NULL,
    seo_title VARCHAR(255) DEFAULT NULL,
    seo_description TEXT DEFAULT NULL,
    seo_keywords TEXT DEFAULT NULL,
    canonical VARCHAR(255) DEFAULT NULL,
    og_title VARCHAR(255) DEFAULT NULL,
    og_description TEXT DEFAULT NULL,
    og_image VARCHAR(255) DEFAULT NULL,
    twitter_image VARCHAR(255) DEFAULT NULL,
    robots VARCHAR(100) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY ux_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
