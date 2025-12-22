-- Payment System Tables for AgroHaat
-- Run this SQL to add payment functionality

USE agrohaat_db;

-- Payment Methods table
CREATE TABLE IF NOT EXISTS payment_methods (
  method_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('MOBILE_BANKING', 'BANK_TRANSFER', 'CARD', 'CASH_ON_DELIVERY') NOT NULL,
  provider VARCHAR(100) NOT NULL, -- bKash, Nagad, DBBL, Visa, MasterCard, etc.
  name VARCHAR(255) NOT NULL,
  logo_url VARCHAR(500),
  is_active BOOLEAN DEFAULT 1,
  processing_fee_percentage DECIMAL(5,2) DEFAULT 0,
  min_amount DECIMAL(10,2) DEFAULT 0,
  max_amount DECIMAL(10,2),
  instructions TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
  payment_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT NOT NULL,
  user_id BIGINT NOT NULL,
  method_id BIGINT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  processing_fee DECIMAL(10,2) DEFAULT 0,
  total_amount DECIMAL(10,2) NOT NULL,
  status ENUM('PENDING', 'PROCESSING', 'COMPLETED', 'FAILED', 'CANCELLED', 'REFUNDED') DEFAULT 'PENDING',
  transaction_id VARCHAR(255),
  gateway_transaction_id VARCHAR(255),
  gateway_response TEXT,
  payment_details JSON, -- Store card details, bank info, etc.
  reference_number VARCHAR(100),
  notes TEXT,
  processed_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (method_id) REFERENCES payment_methods(method_id),
  INDEX idx_order (order_id),
  INDEX idx_user (user_id),
  INDEX idx_status (status),
  INDEX idx_transaction (transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bank accounts for bank transfers
CREATE TABLE IF NOT EXISTS bank_accounts (
  account_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  bank_name VARCHAR(100) NOT NULL,
  account_name VARCHAR(255) NOT NULL,
  account_number VARCHAR(50) NOT NULL,
  branch_name VARCHAR(255),
  routing_number VARCHAR(20),
  swift_code VARCHAR(20),
  is_active BOOLEAN DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default payment methods
INSERT INTO payment_methods (type, provider, name, logo_url, instructions) VALUES
-- Mobile Banking
('MOBILE_BANKING', 'BKASH', 'bKash', '/assets/images/payment/bkash.svg', 
'1. Dial *247# or use bKash app\n2. Select "Send Money"\n3. Enter merchant number: 01700000000\n4. Enter amount and reference\n5. Enter PIN to confirm\n6. Save transaction ID'),

('MOBILE_BANKING', 'NAGAD', 'Nagad', '/assets/images/payment/nagad.svg',
'1. Dial *167# or use Nagad app\n2. Select "Send Money"\n3. Enter merchant number: 01800000000\n4. Enter amount and reference\n5. Enter PIN to confirm\n6. Save transaction ID'),

('MOBILE_BANKING', 'ROCKET', 'Rocket', '/assets/images/payment/rocket.svg',
'1. Dial *322# or use Rocket app\n2. Select "Send Money"\n3. Enter merchant number: 01900000000\n4. Enter amount and reference\n5. Enter PIN to confirm\n6. Save transaction ID'),

('MOBILE_BANKING', 'UPAY', 'Upay', '/assets/images/payment/upay.svg',
'1. Use Upay app\n2. Select "Send Money"\n3. Enter merchant number: 01600000000\n4. Enter amount and reference\n5. Enter PIN to confirm\n6. Save transaction ID'),

-- Bank Transfer
('BANK_TRANSFER', 'BANK', 'Bank Transfer', '/assets/images/payment/bank.svg',
'1. Visit your bank or use online banking\n2. Transfer to our account details\n3. Use order ID as reference\n4. Keep transaction receipt\n5. Enter transaction details below'),

-- Card Payment
('CARD', 'VISA', 'Visa Card', '/assets/images/payment/visa.svg',
'Pay securely with your Visa debit or credit card'),

('CARD', 'MASTERCARD', 'MasterCard', '/assets/images/payment/mastercard.svg',
'Pay securely with your MasterCard debit or credit card'),

('CARD', 'AMEX', 'American Express', '/assets/images/payment/amex.svg',
'Pay securely with your American Express card'),

-- Cash on Delivery
('CASH_ON_DELIVERY', 'COD', 'Cash on Delivery', '/assets/images/payment/cod.svg',
'Pay cash when you receive your order. Additional delivery charges may apply.');

-- Insert default bank accounts
INSERT INTO bank_accounts (bank_name, account_name, account_number, branch_name, routing_number) VALUES
('Dutch Bangla Bank Limited', 'AgroHaat Limited', '1234567890123456', 'Dhanmondi Branch', '090'),
('Islami Bank Bangladesh Limited', 'AgroHaat Limited', '2345678901234567', 'Gulshan Branch', '125'),
('BRAC Bank Limited', 'AgroHaat Limited', '3456789012345678', 'Uttara Branch', '060');
