# AgroHaat - Project Documentation

## 📋 Table of Contents
1. [Project Overview](#project-overview)
2. [System Architecture](#system-architecture)
3. [User Roles & Features](#user-roles--features)
4. [Database Schema](#database-schema)
5. [Installation & Setup](#installation--setup)
6. [API Endpoints](#api-endpoints)
7. [Security Features](#security-features)
8. [Future Enhancements](#future-enhancements)

---

## 🎯 Project Overview

**AgroHaat** is a comprehensive digital marketplace platform designed to bridge the gap between farmers and buyers, eliminating middlemen and ensuring fair prices for quality agricultural products. The platform leverages technology to transform agricultural trade, making it more transparent, efficient, and profitable for all stakeholders.

### Key Objectives
- Direct farmer-to-buyer connectivity
- Fair pricing for agricultural products
- Quality assurance and product grading
- Integrated logistics and delivery management
- Secure payment processing
- Transparent transaction management

### Technology Stack
- **Backend:** PHP 8.2+
- **Database:** MySQL
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap
- **Server:** Apache (XAMPP)
- **Architecture:** MVC Pattern

---

## 🏗️ System Architecture

### Directory Structure
```
Agrohaat/
├── config/
│   └── config.php              # Database connection & configuration
├── controllers/                 # Business logic controllers
│   ├── AdminAuthController.php
│   ├── AdminController.php
│   ├── BuyerAuthController.php
│   ├── FarmerAuthController.php
│   ├── TransporterAuthController.php
│   └── ...
├── models/                      # Data models
│   ├── User.php
│   ├── Product.php
│   ├── Order.php
│   └── ...
├── includes/                    # Reusable components
│   ├── header.php
│   ├── footer.php
│   └── main-menu.php
├── public/                      # Public-facing pages
│   ├── index.php               # Homepage
│   ├── about.php               # About page
│   ├── shop.php                # Marketplace
│   ├── farmer/                 # Farmer module
│   ├── buyer/                  # Buyer module
│   ├── admin/                  # Admin module
│   └── transporter/            # Transporter module
└── database/                    # Database schemas
    ├── agrohaat_schema.sql
    ├── admin_disputes_table.sql
    └── transporter_delivery_tables.sql
```

---

## 👥 User Roles & Features

### 1. 🌾 Farmer Module

#### Authentication
- **Registration:** Create farmer account with personal details
- **Login/Logout:** Secure session-based authentication
- **Profile Management:** Update personal information, location details

#### Product Management
- **Add Products:** 
  - Product title, description, category
  - Quantity available, unit (kg, piece, etc.)
  - Price per unit
  - Quality grade
  - Product images
  - Harvest date, batch number
- **Edit Products:** Update product details
- **Delete Products:** Remove products from marketplace
- **View Products:** List all products with status

#### Order Management
- **View Orders:** See all orders for their products
- **Order Status:** Track order status (PENDING, CONFIRMED, SHIPPED, DELIVERED, CANCELLED)
- **Order Details:** View buyer information, order items, total amount

#### Communication
- **Chat System:** Direct messaging with buyers
- **Notifications:** Receive notifications for new orders, messages

#### Dashboard
- **Overview:** Statistics on products, orders, earnings
- **Quick Actions:** Quick access to common tasks

---

### 2. 🛒 Buyer Module

#### Authentication
- **Registration:** Create buyer account
- **Login/Logout:** Secure authentication
- **Profile Management:** Manage personal information and shipping addresses

#### Marketplace Features
- **Browse Products:** View all available products from farmers
- **Search & Filter:** Search by category, price range, location
- **Product Details:** View detailed product information
- **Product Details:** View comprehensive product information including origin and quality

#### Shopping Cart
- **Add to Cart:** Add products to shopping cart
- **Cart Management:** Update quantities, remove items
- **Cart Summary:** View total amount, item count

#### Order Management
- **Place Orders:** Checkout and place orders
- **Payment:** Multiple payment methods (Cash on Delivery, Online)
- **Order Tracking:** Track order status in real-time
- **Order History:** View all past and current orders

#### Communication
- **Chat with Farmers:** Direct messaging with product sellers
- **Reviews & Ratings:** Rate and review products and farmers

#### Dashboard
- **Order Overview:** View recent orders, pending orders
- **Account Summary:** Personal information, preferences

---

### 3. 🚚 Transporter Module

#### Authentication
- **Registration:** Create transporter account with vehicle details
- **Login/Logout:** Secure authentication
- **Profile Management:** 
  - Vehicle type (Truck, Van, Motorcycle, etc.)
  - License plate number
  - Maximum capacity (kg)
  - Service area districts
  - Verification status

#### Job Marketplace
- **Browse Jobs:** View available delivery jobs
- **Job Details:** See pickup/dropoff locations, order details
- **Filter Jobs:** Filter by location, capacity, status

#### Bidding System
- **Place Bids:** Submit bids on delivery jobs
  - Bid amount
  - Message to buyer/farmer
  - Bid status (PENDING, ACCEPTED, REJECTED, WITHDRAWN)
- **View Bids:** See all submitted bids
- **Bid Management:** Withdraw or update bids

#### Delivery Management
- **My Deliveries:** View all assigned deliveries
- **Delivery Tracking:** 
  - Update delivery status (ASSIGNED, PICKED_UP, IN_TRANSIT, DELIVERED)
  - Add tracking number
  - Record pickup time
  - Record delivery time
  - Add delivery notes
- **Status Updates:** Real-time status updates with notifications

#### Dashboard
- **Job Statistics:** Total jobs, completed deliveries, earnings
- **Active Deliveries:** Current deliveries in progress
- **Bid History:** Track all bids and their status

---

### 4. 👨‍💼 Admin Module

#### Authentication
- **Admin Login:** Secure admin-only access
- **Session Management:** Secure session handling

#### User Management
- **View All Users:** List farmers, buyers, transporters
- **User Details:** View user profiles, activity
- **User Verification:** Verify/unverify user accounts
- **Account Management:** Enable/disable user accounts

#### Product Management
- **View All Products:** Browse all products in the marketplace
- **Product Moderation:** 
  - Approve/reject products
  - Edit product details
  - Remove inappropriate products
- **Category Management:** Manage product categories

#### Order Management
- **View All Orders:** Monitor all transactions
- **Order Details:** View complete order information
- **Order Status:** Update order status if needed

#### Dispute Resolution
- **View Disputes:** See all raised disputes
- **Dispute Details:** Review dispute information, evidence
- **Resolve Disputes:** 
  - Mark as RESOLVED
  - Process REFUNDS
  - REJECT invalid disputes
- **Dispute History:** Track all resolved disputes

#### Review Management
- **View Reviews:** Monitor all product and user reviews
- **Moderate Reviews:** Remove inappropriate reviews
- **Review Analytics:** Track review ratings and trends

#### Dashboard
- **Platform Statistics:** 
  - Total users (farmers, buyers, transporters)
  - Total products
  - Total orders
  - Revenue statistics
  - Active disputes
- **System Overview:** Platform health, recent activities

---

## 🗄️ Database Schema

### Core Tables

#### Users Table
- Stores all user accounts (Farmers, Buyers, Admins, Transporters)
- Fields: user_id, full_name, email, phone_number, password_hash, role, location details, verification status

#### Categories Table
- Product categories
- Fields: category_id, name, description

#### Products Table
- Product listings from farmers
- Fields: product_id, farmer_id, category_id, title, description, quantity, price, quality_grade, images, harvest_date, batch_number

#### Orders Table
- Order transactions
- Fields: order_id, buyer_id, farmer_id, total_amount, status, payment_status, payment_method, transaction_id

#### Order Items Table
- Individual items in orders
- Fields: item_id, order_id, product_id, quantity, unit_price, subtotal

### Communication Tables

#### Messages Table
- Direct messaging between users
- Fields: message_id, order_id, sender_id, receiver_id, content, is_read

#### Reviews Table
- Product and user reviews
- Fields: review_id, order_id, reviewer_id, reviewee_id, rating, comment

#### Disputes Table
- Dispute management
- Fields: dispute_id, order_id, complainant_id, description, evidence_url, status

### Transporter Tables

#### Transporter Profiles Table
- Transporter account details
- Fields: profile_id, user_id, vehicle_type, license_plate, max_capacity_kg, service_area_districts, is_verified

#### Delivery Jobs Table
- Available delivery jobs
- Fields: job_id, order_id, pickup_location, dropoff_location, status

#### Delivery Bids Table
- Transporter bids on jobs
- Fields: bid_id, job_id, transporter_id, bid_amount, message, status

#### Deliveries Table
- Active and completed deliveries
- Fields: delivery_id, job_id, order_id, transporter_id, bid_id, status, tracking_number, pickup_time, delivery_time, notes

### System Tables

#### Notifications Table
- User notifications
- Fields: notification_id, user_id, title, message, is_read, created_at

---

## 🚀 Installation & Setup

### Prerequisites
- XAMPP (Apache + MySQL + PHP)
- PHP 8.2 or higher
- MySQL 5.7+ or MariaDB 10.4+

### Installation Steps

1. **Extract Project**
   - Place project in `C:\xampp\htdocs\Agrohaat\`

2. **Start XAMPP Services**
   - Start Apache
   - Start MySQL

3. **Create Database**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Create database: `agrohaat_db`

4. **Import Database Schema**
   - Import `database/agrohaat_schema.sql`
   - Import `database/admin_disputes_table.sql`
   - Import `database/transporter_delivery_tables.sql`

5. **Configure Database**
   - Edit `config/config.php`
   - Update database credentials if needed

6. **Create Admin Account**
   - Run SQL from `database/create_admin_account.sql`
   - Or use phpMyAdmin to insert admin user manually

7. **Access Application**
   - Homepage: `http://localhost/Agrohaat/public/index.php`
   - Admin Login: `http://localhost/Agrohaat/public/admin/login.php`

### Default Configuration
- **Base URL:** `http://localhost/Agrohaat/public/`
- **Database:** `agrohaat_db`
- **Database Host:** `localhost`
- **Database User:** `root`
- **Database Password:** (empty by default)

---

## 🔌 API Endpoints

### Farmer API
- `GET /api/farmer/products.php` - Get farmer's products (JSON)

### Public Endpoints
- `GET /shop.php` - Browse marketplace
- `GET /product-details.php?id={id}` - Product details

---

## 🔒 Security Features

### Authentication & Authorization
- **Password Hashing:** Bcrypt password hashing
- **Session Management:** Secure PHP sessions
- **Role-Based Access:** Role-based access control (FARMER, BUYER, ADMIN, TRANSPORTER)
- **Login Protection:** Session-based authentication for all protected pages

### Data Protection
- **Prepared Statements:** All database queries use PDO prepared statements
- **Input Validation:** Server-side validation for all inputs
- **XSS Protection:** HTML escaping for user-generated content
- **SQL Injection Prevention:** Parameterized queries

### File Upload Security
- **Upload Directory:** Secure upload directories with proper permissions
- **File Type Validation:** Image file type validation
- **File Size Limits:** Maximum file size restrictions

---

## 📊 Key Features Summary

### For Farmers
✅ Product listing and management  
✅ Order management  
✅ Direct communication with buyers  
✅ Product quality grading system  
✅ Earnings tracking  

### For Buyers
✅ Product browsing and search  
✅ Shopping cart functionality  
✅ Secure checkout and payment  
✅ Order tracking  
✅ Product information and origin details  
✅ Reviews and ratings  

### For Transporters
✅ Job marketplace browsing  
✅ Bidding system for delivery jobs  
✅ Delivery tracking and status updates  
✅ Profile management  
✅ Earnings tracking  

### For Admins
✅ User management  
✅ Product moderation  
✅ Order monitoring  
✅ Dispute resolution  
✅ Review management  
✅ Platform analytics  

---

## 🎨 Design Features

- **Responsive Design:** Mobile-friendly layout
- **Modern UI:** Clean, professional interface
- **Bootstrap Integration:** Bootstrap 5 for styling
- **Custom Styling:** Custom CSS for brand identity
- **Icon Support:** Flaticon and FontAwesome icons

---

## 🔄 Workflow Examples

### Order Processing Flow
1. Buyer browses marketplace
2. Buyer adds products to cart
3. Buyer places order and makes payment
4. Order notification sent to farmer
5. Farmer confirms order
6. Delivery job created (if paid)
7. Transporters bid on delivery job
8. Farmer/buyer accepts bid
9. Transporter picks up and delivers
10. Order marked as delivered
11. Buyer can leave review

### Dispute Resolution Flow
1. User raises dispute with order
2. Admin receives dispute notification
3. Admin reviews dispute and evidence
4. Admin resolves dispute (RESOLVED/REFUNDED/REJECTED)
5. Notification sent to involved parties

---

## 📈 Future Enhancements

### Planned Features
- [ ] Mobile app (iOS/Android)
- [ ] Real-time notifications (WebSocket)
- [ ] Advanced analytics dashboard
- [ ] Payment gateway integration (bKash, Nagad)
- [ ] SMS/Email notifications
- [ ] Multi-language support
- [ ] Advanced search with filters
- [ ] Product recommendations
- [ ] Loyalty program
- [ ] Subscription plans for farmers
- [ ] Weather integration for farmers
- [ ] Inventory management system
- [ ] Automated pricing suggestions
- [ ] Social media integration

---

## 🐛 Known Issues & Limitations

- Display errors are ON (should be OFF in production)
- Some helper scripts need to be removed before production
- Email functionality not yet implemented
- SMS notifications not yet implemented
- Payment gateway integration pending

---

## 📝 Development Notes

### Code Structure
- **MVC Pattern:** Controllers handle logic, Models handle data, Views handle presentation
- **Separation of Concerns:** Clear separation between modules
- **Reusable Components:** Header, footer, and menu components

### Best Practices
- Prepared statements for all database queries
- Session-based authentication
- Input validation and sanitization
- Error handling and logging
- Code comments for complex logic

---

## 👨‍💻 Development Team

**Project:** CSE470 Group Project  
**Institution:** BRAC University, Dhaka  
**Year:** 2025

---

## 📞 Support & Contact

**Email:** info@agrohaat.local  
**Phone:** +880 1XXXXXXXXX  
**Address:** BRAC University, Dhaka

---

## 📄 License

© 2025 AgroHaat. All rights reserved.

---

## 🔄 Version History

- **v1.1** (January 2025) - Current Version
  - Removed QR trace functionality
  - Fixed buyer dashboard functionality
  - Cleaned up test/debug files
  - Improved payment processing
  - Enhanced order management
  - Code cleanup and optimization

- **v1.0** (2025) - Initial release
  - Core marketplace functionality
  - Farmer, Buyer, Admin, Transporter modules
  - Delivery management system
  - Dispute resolution system
  - Payment processing system

---

*Last Updated: January 2025*

