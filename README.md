# 🌾 AgroHaat - Direct Farmer-to-Market Linkage Platform

A comprehensive digital marketplace connecting farmers directly with buyers, eliminating middlemen and ensuring fair prices for quality agricultural products.

---

## 🚀 Quick Start

### Prerequisites
- XAMPP (Apache + MySQL + PHP 8.2+)
- Web browser

### Installation (5 Minutes)

1. **Extract Project**
   ```
   Extract to: C:\xampp\htdocs\Agrohaat\
   ```

2. **Start XAMPP**
   - Open XAMPP Control Panel
   - Start Apache
   - Start MySQL

3. **Create Database**
   - Open: `http://localhost/phpmyadmin`
   - Create database: `agrohaat_db`

4. **Import Database**
   - Import `database/agrohaat_schema.sql`
   - Import `database/admin_disputes_table.sql`
   - Import `database/transporter_delivery_tables.sql`

5. **Create Admin Account**
   ```sql
   INSERT INTO users (full_name, email, phone_number, password_hash, role, is_verified, is_deleted) 
   VALUES ('Admin User', 'admin@agrohaat.com', '+8801234567890', 
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADMIN', 1, 0);
   ```

6. **Access Application**
   - Homepage: `http://localhost/Agrohaat/public/index.php`
   - Admin: `http://localhost/Agrohaat/public/admin/login.php`
   - Login: `admin@agrohaat.com` / `admin123`

---

## 📚 Documentation

- **Full Documentation:** See `PROJECT_DOCUMENTATION.md`
- **Team Setup Guide:** See `TEAM_SETUP_GUIDE.md`

---

## 👥 User Roles

- **🌾 Farmers** - List products, manage orders
- **🛒 Buyers** - Browse marketplace, place orders
- **🚚 Transporters** - Bid on delivery jobs, track deliveries
- **👨‍💼 Admins** - Manage platform, resolve disputes

---

## 🗄️ Database

- **Database Name:** `agrohaat_db`
- **Tables:** 13 core tables
- **Schema Files:** `database/` folder

---

## 🔑 Default Credentials

**Admin:**
- Email: `admin@agrohaat.com`
- Password: `admin123`

**Database:**
- Host: `localhost`
- User: `root`
- Password: (empty)
- Database: `agrohaat_db`

---

## 📁 Project Structure

```
Agrohaat/
├── config/           # Configuration
├── controllers/      # Business logic
├── models/           # Data models
├── includes/         # Reusable components
├── public/           # Public pages
└── database/         # SQL schemas
```

---

## 🛠️ Technology Stack

- **Backend:** PHP 8.2+
- **Database:** MySQL/MariaDB
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap
- **Server:** Apache (XAMPP)
- **Architecture:** MVC Pattern

---

## 📞 Support

**Email:** info@agrohaat.local  
**Institution:** BRAC University, Dhaka  
**Project:** CSE470 Group Project

---

## 📄 License

© 2025 AgroHaat. All rights reserved.

---

**For detailed setup instructions, see `TEAM_SETUP_GUIDE.md`**

