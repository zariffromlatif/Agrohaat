-- ============================================
-- Create Admin Account - SQL Script
-- Run this in phpMyAdmin or MySQL command line
-- ============================================

USE agrohaat_db;

-- ============================================
-- Method 1: Create Admin with Plain Password
-- Replace 'admin123' with your desired password
-- ============================================
-- Note: You'll need to hash the password first using PHP or online tool

-- Step 1: Generate password hash (run this in PHP or use online tool)
-- Example PHP code:
-- <?php echo password_hash('admin123', PASSWORD_BCRYPT); ?>

-- Step 2: Insert admin user (replace the hash with generated hash)
INSERT INTO users 
(full_name, email, phone_number, password_hash, role, is_verified, is_deleted) 
VALUES 
('Admin User', 'admin@agrohaat.com', '+8801234567890', 
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 'ADMIN', 1, 0);

-- ============================================
-- Method 2: Create Multiple Admin Accounts
-- ============================================
-- Admin 1
INSERT INTO users 
(full_name, email, phone_number, password_hash, role, is_verified, is_deleted) 
VALUES 
('Super Admin', 'superadmin@agrohaat.com', '+8801234567891', 
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 'ADMIN', 1, 0);

-- Admin 2
INSERT INTO users 
(full_name, email, phone_number, password_hash, role, is_verified, is_deleted) 
VALUES 
('Support Admin', 'support@agrohaat.com', '+8801234567892', 
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 'ADMIN', 1, 0);

-- ============================================
-- Verify Admin Account Created
-- ============================================
SELECT user_id, full_name, email, role, is_verified, created_at 
FROM users 
WHERE role = 'ADMIN';

-- ============================================
-- Default Login Credentials (if using Method 1)
-- ============================================
-- Email: admin@agrohaat.com
-- Password: admin123
-- 
-- NOTE: The password hash above is for 'admin123'
-- Change it to your own secure password!

