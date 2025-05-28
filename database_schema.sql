-- Tạo database sunkissed_shop
CREATE DATABASE IF NOT EXISTS sunkissed_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sunkissed_shop;

-- Bảng sản phẩm
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    original_price DECIMAL(10,2) DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0,
    stock_status ENUM('in', 'almost', 'out') DEFAULT 'in',
    discount ENUM('yes', 'no') DEFAULT 'no',
    discount_percent INT DEFAULT 0,
    image VARCHAR(500) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng giỏ hàng
CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_price DECIMAL(10,2) NOT NULL,
    product_image VARCHAR(500) NOT NULL,
    product_category VARCHAR(100) NOT NULL,
    product_variant VARCHAR(50) DEFAULT '500ml',
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_product_id (product_id)
);

-- Bảng khách hàng
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    city VARCHAR(100),
    district VARCHAR(100),
    zipcode VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
);

-- Bảng đơn hàng
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(50) UNIQUE NOT NULL,
    user_id INT,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_address TEXT NOT NULL,
    delivery_method ENUM('store', 'delivery') NOT NULL,
    payment_method ENUM('visa', 'mastercard', 'momo', 'cod') NOT NULL,
    store_id VARCHAR(50),
    pickup_date DATE,
    pickup_time VARCHAR(20),
    delivery_date DATE,
    delivery_time VARCHAR(20),
    city VARCHAR(100),
    district VARCHAR(100),
    zipcode VARCHAR(20),
    subtotal DECIMAL(10,2) NOT NULL,
    shipping_fee DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    order_status ENUM('pending', 'confirmed', 'processing', 'shipping', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_code (order_code),
    INDEX idx_user_id (user_id),
    INDEX idx_customer_email (customer_email),
    INDEX idx_order_status (order_status),
    INDEX idx_payment_status (payment_status)
);

-- Bảng chi tiết đơn hàng
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_product_id (product_id)
);

-- Bảng cửa hàng
CREATE TABLE IF NOT EXISTS stores (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(20),
    hours VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng mã giảm giá
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    type ENUM('fixed', 'percentage', 'shipping') NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    max_discount DECIMAL(10,2),
    min_order_amount DECIMAL(10,2) DEFAULT 0,
    usage_limit INT DEFAULT 0,
    used_count INT DEFAULT 0,
    valid_from DATETIME,
    valid_until DATETIME,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_status (status)
);

-- Bảng lịch sử đơn hàng
CREATE TABLE IF NOT EXISTS order_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    notes TEXT,
    created_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id)
);

-- Thêm dữ liệu mẫu cho stores
INSERT INTO stores (id, name, address, phone, hours) VALUES
('store1', 'Sunkissed - Chi nhánh 1', '219 Nguyễn Tri Phương, Quận 10, TP. HCM', '0335993276', 'Giờ mở cửa: 8:00 - 21:00, Thứ 2 - Thứ 7'),
('store2', 'Sunkissed - Chi nhánh 2', '78 Nguyễn Văn Linh, Quận 7, TP. HCM', '0335993277', 'Giờ mở cửa: 8:00 - 21:00, Thứ 2 - Chủ Nhật');

-- Thêm dữ liệu mẫu cho coupons
INSERT INTO coupons (code, name, type, value, max_discount, min_order_amount, usage_limit, valid_from, valid_until) VALUES
('GIAIKHATHE', 'Giảm 10% phí vận chuyển', 'shipping', 10, 10000, 0, 100, '2024-01-01 00:00:00', '2024-12-31 23:59:59'),
('GIAIKHAT', 'Giảm 15% giá trị đơn hàng', 'percentage', 15, 100000, 50000, 100, '2024-01-01 00:00:00', '2024-12-31 23:59:59');

-- Thêm dữ liệu mẫu cho products
INSERT INTO products (name, category, price, original_price, rating, stock_status, discount, discount_percent, image, description) VALUES
('Hương Cam', 'Tropical', 35000, 55000, 4.3, 'in', 'yes', 15, 'assets/img/td_p1_bg.png', 'Nước uống hương cam tự nhiên'),
('Hương Socola', 'Trà Sữa', 30000, 50000, 4.5, 'in', 'yes', 15, 'assets/img/mt_p1_bg.png', 'Trà sữa hương socola thơm ngon'),
('Vị Sài Gòn', 'Cà Phê', 35000, 55000, 4.2, 'in', 'yes', 15, 'assets/img/cf_p1_bg.png', 'Cà phê đậm đà hương vị Sài Gòn'),
('Hương Chanh', 'Trà Thảo Mộc', 25000, 35000, 4.6, 'in', 'no', 0, 'assets/img/ht_p1_bg.png', 'Trà thảo mộc hương chanh tươi mát'),
('Hương Lựu Đỏ', 'Kombucha', 35000, 50000, 4.4, 'in', 'yes', 15, 'assets/img/kom_p1_bg.png', 'Kombucha hương lựu đỏ tự nhiên'); 