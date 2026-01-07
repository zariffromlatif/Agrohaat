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
   - Import `database/agrohaat_db.sql` (complete database export)

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

- **API Documentation:** See `public/api/*/README.md` for API endpoints
- **Test Documentation:** See `tests/README.md` for testing information

---

## 👥 User Roles

- **🌾 Farmers** - List products, manage orders
- **🛒 Buyers** - Browse marketplace, place orders
- **🚚 Transporters** - Bid on delivery jobs, track deliveries
- **👨‍💼 Admins** - Manage platform, resolve disputes

---

## 🗄️ Database

- **Database Name:** `agrohaat_db`
- **Database File:** `database/agrohaat_db.sql` (complete export)
- **Tables:** 13+ core tables (users, products, orders, payments, etc.)

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
├── tests/            # Unit tests
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

**Institution:** BRAC University, Dhaka  
**Project:** CSE470 Group Project

---

## 📄 License

© 2025 AgroHaat. All rights reserved.

---

## 🔄 Recent Updates (v1.2 - January 2025)

- ✅ Added unit tests for Farmer Product API (Assignment 2)
- ✅ Improved test infrastructure with SQLite support
- ✅ Enhanced API testability with test mode support
- ✅ Code cleanup and documentation updates

---

## 🧪 Testing

The project includes comprehensive unit tests. See `tests/README.md` for details on running tests.

**Quick Test Run:**
```bash
php phpunit.phar --configuration tests/phpunit.xml tests/
```

