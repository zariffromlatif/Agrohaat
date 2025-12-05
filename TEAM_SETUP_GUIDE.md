# AgroHaat - Team Setup & Testing Guide

## 📦 How to Share the Project with Teammates

### Method 1: Zip File (Easiest)

1. **Create a Zip File:**
   - Right-click on the `Agrohaat` folder
   - Select "Send to" → "Compressed (zipped) folder"
   - Name it: `Agrohaat_Project.zip`

2. **Share the Zip File:**
   - Upload to Google Drive, Dropbox, or OneDrive
   - Share the link with your teammates
   - Or send via email (if file size allows)

3. **What to Include:**
   - ✅ All project files
   - ✅ Database SQL files
   - ✅ PROJECT_DOCUMENTATION.md
   - ✅ This TEAM_SETUP_GUIDE.md
   - ❌ Don't include: `uploads/` folder (can be empty), `node_modules` (if any)

---

### Method 2: Git Repository (Recommended for Development)

1. **Initialize Git (if not already):**
   ```bash
   cd C:\xampp\htdocs\Agrohaat
   git init
   git add .
   git commit -m "Initial commit - AgroHaat Project"
   ```

2. **Push to GitHub/GitLab:**
   - Create a repository on GitHub
   - Push your code:
   ```bash
   git remote add origin https://github.com/yourusername/Agrohaat.git
   git push -u origin main
   ```

3. **Share Repository Link:**
   - Share the GitHub/GitLab link with teammates
   - They can clone it: `git clone https://github.com/yourusername/Agrohaat.git`

---

## 🚀 Setup Instructions for Teammates

### Prerequisites (Required for Everyone)

1. **Install XAMPP:**
   - Download from: https://www.apachefriends.org/
   - Install XAMPP (includes Apache, MySQL, PHP)
   - Version: PHP 8.2+ recommended

2. **Verify Installation:**
   - Open XAMPP Control Panel
   - Start Apache
   - Start MySQL
   - Test: Open `http://localhost` - should see XAMPP welcome page

---

### Step-by-Step Setup

#### Step 1: Extract/Clone Project

**If using Zip:**
1. Extract `Agrohaat_Project.zip` to: `C:\xampp\htdocs\`
2. Final path should be: `C:\xampp\htdocs\Agrohaat\`

**If using Git:**
```bash
cd C:\xampp\htdocs
git clone https://github.com/yourusername/Agrohaat.git
```

---

#### Step 2: Start XAMPP Services

1. Open **XAMPP Control Panel**
2. Click **Start** for **Apache**
3. Click **Start** for **MySQL**
4. Both should show green "Running" status

---

#### Step 3: Create Database

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click **"New"** in the left sidebar
3. Database name: `agrohaat_db`
4. Collation: `utf8mb4_unicode_ci`
5. Click **"Create"**

---

#### Step 4: Import Database Tables

1. In phpMyAdmin, select `agrohaat_db` database
2. Click **"Import"** tab
3. Import in this order:

   **First:** `database/agrohaat_schema.sql`
   - Click "Choose File"
   - Select: `C:\xampp\htdocs\Agrohaat\database\agrohaat_schema.sql`
   - Click "Go"
   - Wait for "Import has been successfully finished"

   **Second:** `database/admin_disputes_table.sql`
   - Repeat the import process

   **Third:** `database/transporter_delivery_tables.sql`
   - Repeat the import process

4. **Verify:** You should see 13 tables in the database

---

#### Step 5: Create Admin Account

1. In phpMyAdmin, select `agrohaat_db` database
2. Click **"SQL"** tab
3. Copy and paste this SQL:

```sql
USE agrohaat_db;

INSERT INTO users 
(full_name, email, phone_number, password_hash, role, is_verified, is_deleted) 
VALUES 
('Admin User', 'admin@agrohaat.com', '+8801234567890', 
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 'ADMIN', 1, 0);
```

4. Click **"Go"**
5. **Login Credentials:**
   - Email: `admin@agrohaat.com`
   - Password: `admin123`

---

#### Step 6: Verify Configuration

1. Open: `config/config.php`
2. Verify these settings (should be correct by default):
   ```php
   $BASE_URL = 'http://localhost/Agrohaat/public/';
   $db_host = "localhost";
   $db_name = "agrohaat_db";
   $db_user = "root";
   $db_pass = "";
   ```

---

#### Step 7: Test the Application

1. **Homepage:**
   - Open: `http://localhost/Agrohaat/public/index.php`
   - Should see the AgroHaat homepage

2. **Admin Login:**
   - Go to: `http://localhost/Agrohaat/public/admin/login.php`
   - Login with: `admin@agrohaat.com` / `admin123`
   - Should redirect to admin dashboard

3. **Marketplace:**
   - Go to: `http://localhost/Agrohaat/public/shop.php`
   - Should see product marketplace

---

## 🧪 Testing Guide

### Test Scenarios

#### 1. Test Farmer Module

**Registration:**
1. Go to: `http://localhost/Agrohaat/public/farmer/register.php`
2. Fill in registration form
3. Submit and verify account created

**Login:**
1. Go to: `http://localhost/Agrohaat/public/farmer/login.php`
2. Login with registered credentials
3. Should redirect to farmer dashboard

**Add Product:**
1. Go to: `http://localhost/Agrohaat/public/farmer/product_add.php`
2. Fill product details
3. Upload product image
4. Submit and verify product appears in "My Products"

**View Orders:**
1. Go to: `http://localhost/Agrohaat/public/farmer/orders.php`
2. Should see orders (if any exist)

---

#### 2. Test Buyer Module

**Registration:**
1. Go to: `http://localhost/Agrohaat/public/buyer/register.php`
2. Create buyer account

**Browse Marketplace:**
1. Go to: `http://localhost/Agrohaat/public/shop.php`
2. Browse products
3. Click on a product to see details

**Add to Cart:**
1. Click "Add to Cart" on a product
2. Go to: `http://localhost/Agrohaat/public/cart.php`
3. Verify product in cart

**Place Order:**
1. Proceed to checkout
2. Fill shipping address
3. Select payment method
4. Place order
5. Verify order appears in "My Orders"

---

#### 3. Test Transporter Module

**Registration:**
1. Go to: `http://localhost/Agrohaat/public/transporter/register.php`
2. Fill transporter details (vehicle type, capacity, etc.)
3. Submit registration

**Browse Jobs:**
1. Login as transporter
2. Go to: `http://localhost/Agrohaat/public/transporter/jobs.php`
3. Should see available delivery jobs

**Place Bid:**
1. Click on a job
2. Go to: `http://localhost/Agrohaat/public/transporter/placebid.php?job_id=X`
3. Enter bid amount and message
4. Submit bid
5. Verify bid appears in "My Bids"

**Track Delivery:**
1. If assigned a delivery, go to: `http://localhost/Agrohaat/public/transporter/track_delivery.php?job_id=X`
2. Update delivery status
3. Verify status updates

---

#### 4. Test Admin Module

**Login:**
1. Go to: `http://localhost/Agrohaat/public/admin/login.php`
2. Login with admin credentials

**View Users:**
1. Go to: `http://localhost/Agrohaat/public/admin/users.php`
2. Should see all registered users

**View Products:**
1. Go to: `http://localhost/Agrohaat/public/admin/products.php`
2. Should see all products

**View Disputes:**
1. Go to: `http://localhost/Agrohaat/public/admin/disputes.php`
2. Should see disputes (if any)

**View Reviews:**
1. Go to: `http://localhost/Agrohaat/public/admin/reviews.php`
2. Should see all reviews

---

## 🔧 Common Issues & Solutions

### Issue 1: "Database Connection Failed"

**Solution:**
- Check if MySQL is running in XAMPP
- Verify database name is `agrohaat_db`
- Check `config/config.php` credentials

---

### Issue 2: "Page Not Found" or 404 Error

**Solution:**
- Verify Apache is running
- Check URL: Must be `http://localhost/Agrohaat/public/index.php`
- Verify project is in `C:\xampp\htdocs\Agrohaat\`

---

### Issue 3: "Table doesn't exist"

**Solution:**
- Re-import database SQL files
- Import in correct order:
  1. `agrohaat_schema.sql`
  2. `admin_disputes_table.sql`
  3. `transporter_delivery_tables.sql`

---

### Issue 4: "Access Denied" or "Cannot Login"

**Solution:**
- Verify admin account exists in database
- Check password hash is correct
- Try creating new admin account using SQL

---

### Issue 5: Images/Logos Not Showing

**Solution:**
- Hard refresh: `Ctrl + F5`
- Check file paths in browser console (F12)
- Verify image files exist in correct folders

---

## 📋 Quick Checklist for Teammates

- [ ] XAMPP installed and running
- [ ] Apache started (green in XAMPP)
- [ ] MySQL started (green in XAMPP)
- [ ] Project extracted to `C:\xampp\htdocs\Agrohaat\`
- [ ] Database `agrohaat_db` created
- [ ] All 3 SQL files imported successfully
- [ ] Admin account created
- [ ] Can access homepage: `http://localhost/Agrohaat/public/index.php`
- [ ] Can login as admin
- [ ] Can browse marketplace

---

## 🎯 Testing Checklist

### Basic Functionality
- [ ] Homepage loads correctly
- [ ] Navigation menu works
- [ ] About page displays
- [ ] Marketplace shows products

### User Registration
- [ ] Farmer registration works
- [ ] Buyer registration works
- [ ] Transporter registration works

### User Login
- [ ] Farmer can login
- [ ] Buyer can login
- [ ] Transporter can login
- [ ] Admin can login

### Core Features
- [ ] Farmer can add products
- [ ] Buyer can browse products
- [ ] Buyer can add to cart
- [ ] Buyer can place orders
- [ ] Transporter can see jobs
- [ ] Transporter can place bids
- [ ] Admin can view users
- [ ] Admin can view products

---

## 📞 Getting Help

If teammates encounter issues:

1. **Check Error Messages:**
   - Look at browser console (F12)
   - Check PHP error logs in XAMPP

2. **Verify Setup:**
   - Go through setup steps again
   - Check database connection
   - Verify file paths

3. **Common Solutions:**
   - Restart Apache and MySQL
   - Clear browser cache
   - Re-import database

---

## 📝 Notes for Teammates

### Important Files
- `config/config.php` - Database configuration
- `PROJECT_DOCUMENTATION.md` - Full project documentation
- `database/` - All SQL schema files

### Default Credentials
- **Admin:** admin@agrohaat.com / admin123
- **Database:** agrohaat_db
- **Database User:** root (no password)

### Development Tips
- Always start Apache and MySQL before testing
- Use `Ctrl + F5` to hard refresh and clear cache
- Check browser console (F12) for JavaScript errors
- Check PHP error logs in XAMPP for backend errors

---

## 🎉 Success Indicators

Your setup is successful if:
- ✅ Homepage loads without errors
- ✅ Can login as admin
- ✅ Can register new users
- ✅ Can browse marketplace
- ✅ Database has 13 tables
- ✅ No PHP errors in browser

---

## 📦 Project Files to Share

**Essential Files:**
- All PHP files (controllers, models, views)
- All SQL files in `database/` folder
- `config/config.php`
- `PROJECT_DOCUMENTATION.md`
- `TEAM_SETUP_GUIDE.md` (this file)

**Optional (can be regenerated):**
- `uploads/` folder (can be empty)
- Cache files (if any)

---

## 🚀 Quick Start Command Summary

```bash
# 1. Start XAMPP Services
# Open XAMPP Control Panel → Start Apache → Start MySQL

# 2. Access phpMyAdmin
# http://localhost/phpmyadmin

# 3. Create Database
# Database name: agrohaat_db

# 4. Import SQL Files (in order)
# - agrohaat_schema.sql
# - admin_disputes_table.sql
# - transporter_delivery_tables.sql

# 5. Create Admin Account (SQL)
# Use SQL from Step 5 above

# 6. Access Application
# http://localhost/Agrohaat/public/index.php
```

---

**Good luck with your project! 🎉**

*If you encounter any issues not covered here, check the PROJECT_DOCUMENTATION.md file or contact your team lead.*

