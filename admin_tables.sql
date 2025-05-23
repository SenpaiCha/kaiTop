-- MySQL table for purchases and logs
CREATE TABLE IF NOT EXISTS purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    buyer VARCHAR(100) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    total DECIMAL(10,2) NOT NULL,
    purchased_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE IF NOT EXISTS purchase_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor VARCHAR(100) NOT NULL,
    product_id INT NOT NULL,
    buyer VARCHAR(100) NOT NULL,
    quantity INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    log_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Add cash column to users
ALTER TABLE users ADD COLUMN cash DECIMAL(10,2) NOT NULL DEFAULT 0;

-- Add suspended column to products
ALTER TABLE products ADD COLUMN suspended TINYINT(1) NOT NULL DEFAULT 0;
-- Add suspend_reason column to products
ALTER TABLE products ADD COLUMN suspend_reason VARCHAR(255) DEFAULT NULL;
