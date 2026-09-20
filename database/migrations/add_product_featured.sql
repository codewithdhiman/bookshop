ALTER TABLE products
    ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER status,
    ADD INDEX idx_products_featured (is_featured);