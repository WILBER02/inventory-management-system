CREATE DATABASE IF NOT EXISTS inventory_db;

USE inventory_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Staff') DEFAULT 'Staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(30),
    email VARCHAR(100),
    address VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(150) NOT NULL,
    sku VARCHAR(100) NOT NULL UNIQUE,
    category_id INT,
    supplier_id INT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    quantity INT NOT NULL DEFAULT 0,
    minimum_stock INT NOT NULL DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
);

CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    movement_type ENUM('Stock In', 'Stock Out', 'Adjustment') NOT NULL,
    quantity INT NOT NULL,
    previous_quantity INT NOT NULL,
    new_quantity INT NOT NULL,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO users (full_name, email, password, role)
VALUES (
    'Demo Administrator',
    'demo@example.com',
    '$2y$12$CTMD02VpPF3Wom956LelN.0hRsHgxhfWnuR3OxTIH61db4i9RwPZG',
    'Admin'
);

INSERT INTO categories (name, description) VALUES
('Electronics', 'Electronic devices and accessories'),
('Computers', 'Computers and computer accessories'),
('Office Supplies', 'General office supplies'),
('Networking', 'Networking equipment and accessories');

INSERT INTO suppliers (name, phone, email, address) VALUES
('Demo Technology Supplies', '0700000000', 'supplier1@example.com', 'Nairobi'),
('Demo Office Solutions', '0711111111', 'supplier2@example.com', 'Eldoret'),
('Demo Network Equipment', '0722222222', 'supplier3@example.com', 'Nakuru');

INSERT INTO products (product_name, sku, category_id, supplier_id, price, quantity, minimum_stock) VALUES
('Business Laptop', 'LAP-001', 2, 1, 65000.00, 15, 5),
('Wireless Mouse', 'MOU-001', 2, 1, 1500.00, 35, 10),
('Office Chair', 'CHR-001', 3, 2, 8500.00, 8, 3),
('USB Keyboard', 'KEY-001', 2, 1, 1800.00, 20, 5),
('Network Router', 'RTR-001', 4, 3, 5500.00, 12, 4),
('Network Switch', 'SWT-001', 4, 3, 7200.00, 6, 3),
('LED Monitor', 'MON-001', 2, 1, 18500.00, 10, 4),
('Printer Paper', 'PPR-001', 3, 2, 850.00, 50, 10);

INSERT INTO stock_movements (product_id, movement_type, quantity, previous_quantity, new_quantity, notes, created_by) VALUES
(1, 'Stock In', 15, 0, 15, 'Initial inventory setup', 1),
(2, 'Stock In', 35, 0, 35, 'Initial inventory setup', 1),
(3, 'Stock In', 8, 0, 8, 'Initial inventory setup', 1),
(4, 'Stock In', 20, 0, 20, 'Initial inventory setup', 1),
(5, 'Stock In', 12, 0, 12, 'Initial inventory setup', 1),
(6, 'Stock In', 6, 0, 6, 'Initial inventory setup', 1),
(7, 'Stock In', 10, 0, 10, 'Initial inventory setup', 1),
(8, 'Stock In', 50, 0, 50, 'Initial inventory setup', 1);