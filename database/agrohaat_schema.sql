-- AgroHaat Database Schema
-- Run this SQL file to create all necessary tables for the application

-- Create database (if not exists)
CREATE DATABASE IF NOT EXISTS agrohaat_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE agrohaat_db;

-- Users table (for Farmers, Buyers, Admins, Transporters)
CREATE TABLE IF NOT EXISTS users (
  user_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE,
  phone_number VARCHAR(20) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('FARMER', 'BUYER', 'ADMIN', 'TRANSPORTER') NOT NULL,
  division VARCHAR(100),
  district VARCHAR(100),
  upazila VARCHAR(100),
  address_details TEXT,
  is_verified BOOLEAN DEFAULT 0,
  is_deleted BOOLEAN DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_role (role),
  INDEX idx_email (email),
  INDEX idx_phone (phone_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
  category_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add description column if it doesn't exist (for existing tables)
SET @dbname = DATABASE();
SET @tablename = 'categories';
SET @columnname = 'description';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' TEXT AFTER name')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Products table
CREATE TABLE IF NOT EXISTS products (
  product_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  farmer_id BIGINT NOT NULL,
  category_id BIGINT,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  quantity_available DECIMAL(10,2) NOT NULL,
  unit VARCHAR(20) NOT NULL DEFAULT 'kg',
  price_per_unit DECIMAL(10,2) NOT NULL,
  quality_grade ENUM('A', 'B', 'C', 'EXPORT_QUALITY') DEFAULT 'A',
  image_url VARCHAR(500),
  harvest_date DATE,
  batch_number VARCHAR(100),
  trace_id VARCHAR(100) UNIQUE,
  qr_code_url VARCHAR(500),
  status ENUM('ACTIVE', 'INACTIVE', 'SOLD_OUT') DEFAULT 'ACTIVE',
  is_deleted BOOLEAN DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (farmer_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
  INDEX idx_farmer (farmer_id),
  INDEX idx_category (category_id),
  INDEX idx_status (status),
  INDEX idx_trace (trace_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
  order_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  buyer_id BIGINT NOT NULL,
  farmer_id BIGINT NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  status ENUM('PENDING', 'PAID', 'CONFIRMED', 'PROCESSING', 'SHIPPED', 'DELIVERED', 'CANCELLED') DEFAULT 'PENDING',
  payment_status ENUM('UNPAID', 'PENDING', 'PAID', 'PARTIAL', 'REFUNDED') DEFAULT 'UNPAID',
  payment_method VARCHAR(50),
  transaction_id VARCHAR(255),
  shipping_address TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (buyer_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (farmer_id) REFERENCES users(user_id) ON DELETE CASCADE,
  INDEX idx_buyer (buyer_id),
  INDEX idx_farmer (farmer_id),
  INDEX idx_status (status),
  INDEX idx_payment_status (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order Items table
CREATE TABLE IF NOT EXISTS order_items (
  item_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT NOT NULL,
  product_id BIGINT NOT NULL,
  quantity DECIMAL(10,2) NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
  INDEX idx_order (order_id),
  INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Messages table (for chat)
CREATE TABLE IF NOT EXISTS messages (
  message_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT NOT NULL,
  sender_id BIGINT NOT NULL,
  receiver_id BIGINT NOT NULL,
  content TEXT NOT NULL,
  is_read BOOLEAN DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE,
  INDEX idx_order (order_id),
  INDEX idx_sender (sender_id),
  INDEX idx_receiver (receiver_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Disputes table
CREATE TABLE IF NOT EXISTS disputes (
  dispute_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT NOT NULL,
  complainant_id BIGINT NOT NULL,
  description TEXT,
  evidence_url VARCHAR(255),
  status ENUM('OPEN','RESOLVED','REFUNDED','REJECTED') DEFAULT 'OPEN',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
  FOREIGN KEY (complainant_id) REFERENCES users(user_id) ON DELETE CASCADE,
  INDEX idx_status (status),
  INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reviews table
CREATE TABLE IF NOT EXISTS reviews (
  review_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT NOT NULL,
  reviewer_id BIGINT NOT NULL,
  reviewee_id BIGINT NOT NULL,
  rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
  FOREIGN KEY (reviewer_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (reviewee_id) REFERENCES users(user_id) ON DELETE CASCADE,
  INDEX idx_order (order_id),
  INDEX idx_reviewer (reviewer_id),
  INDEX idx_reviewee (reviewee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Transporter Profiles table
CREATE TABLE IF NOT EXISTS transporter_profiles (
  profile_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT NOT NULL UNIQUE,
  vehicle_type ENUM('TRUCK', 'PICKUP', 'VAN', 'CNG', 'BOAT') NOT NULL,
  license_plate VARCHAR(50) NOT NULL UNIQUE,
  max_capacity_kg INT NOT NULL,
  service_area_districts TEXT,
  is_verified BOOLEAN DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  INDEX idx_vehicle_type (vehicle_type),
  INDEX idx_license_plate (license_plate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample categories (only if table is empty)
INSERT IGNORE INTO categories (name, description) VALUES
('Rice', 'Various types of rice'),
('Vegetables', 'Fresh vegetables'),
('Fruits', 'Fresh fruits'),
('Fish', 'Fresh fish and seafood'),
('Livestock', 'Cattle, goats, poultry'),
('Grains', 'Wheat, corn, barley'),
('Spices', 'Various spices'),
('Dairy', 'Milk, cheese, yogurt');

