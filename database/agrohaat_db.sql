-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 01, 2026 at 09:22 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `agrohaat_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` bigint(20) NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyerprofiles`
--

CREATE TABLE `buyerprofiles` (
  `profile_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `buyer_type` enum('INDIVIDUAL','RESTAURANT','EXPORTER','RETAILER') DEFAULT NULL,
  `company_name` varchar(150) DEFAULT NULL,
  `trade_license_no` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buyerprofiles`
--

INSERT INTO `buyerprofiles` (`profile_id`, `user_id`, `buyer_type`, `company_name`, `trade_license_no`) VALUES
(1, 2, 'RETAILER', 'Shwapno', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `description`, `image_url`, `created_at`) VALUES
(1, 'Cereals', NULL, NULL, '2025-12-05 13:26:08'),
(2, 'Vegetables', NULL, NULL, '2025-12-05 13:26:08'),
(3, 'Fruits', NULL, NULL, '2025-12-05 13:26:08'),
(4, 'Fish', NULL, NULL, '2025-12-05 13:26:08'),
(5, 'Rice', 'Various types of rice', NULL, '2025-12-05 13:34:23'),
(6, 'Vegetables', 'Fresh vegetables', NULL, '2025-12-05 13:34:23'),
(7, 'Fruits', 'Fresh fruits', NULL, '2025-12-05 13:34:23'),
(8, 'Fish', 'Fresh fish and seafood', NULL, '2025-12-05 13:34:23'),
(9, 'Livestock', 'Cattle, goats, poultry', NULL, '2025-12-05 13:34:23'),
(10, 'Grains', 'Wheat, corn, barley', NULL, '2025-12-05 13:34:23'),
(11, 'Spices', 'Various spices', NULL, '2025-12-05 13:34:23'),
(12, 'Dairy', 'Milk, cheese, yogurt', NULL, '2025-12-05 13:34:23');

-- --------------------------------------------------------

--
-- Table structure for table `chatmessages`
--

CREATE TABLE `chatmessages` (
  `message_id` bigint(20) NOT NULL,
  `sender_id` bigint(20) NOT NULL,
  `receiver_id` bigint(20) NOT NULL,
  `message_text` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deliveries`
--

CREATE TABLE `deliveries` (
  `delivery_id` bigint(20) NOT NULL,
  `job_id` bigint(20) DEFAULT NULL,
  `order_id` bigint(20) NOT NULL,
  `transporter_id` bigint(20) DEFAULT NULL,
  `bid_id` bigint(20) DEFAULT NULL,
  `status` enum('UNASSIGNED','ASSIGNED','PICKED_UP','IN_TRANSIT','DELIVERED','CANCELLED') NOT NULL DEFAULT 'UNASSIGNED',
  `tracking_number` varchar(100) DEFAULT NULL,
  `pickup_address` text NOT NULL,
  `dropoff_address` text NOT NULL,
  `pickup_time` datetime DEFAULT NULL,
  `delivery_time` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `delivered_time` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deliverybids`
--

CREATE TABLE `deliverybids` (
  `bid_id` bigint(20) NOT NULL,
  `job_id` bigint(20) NOT NULL,
  `transporter_id` bigint(20) NOT NULL,
  `bid_amount` decimal(10,2) NOT NULL,
  `message` varchar(255) DEFAULT NULL,
  `status` enum('PENDING','ACCEPTED','REJECTED','WITHDRAWN') DEFAULT 'PENDING',
  `is_accepted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deliveryjobs`
--

CREATE TABLE `deliveryjobs` (
  `job_id` bigint(20) NOT NULL,
  `order_id` bigint(20) NOT NULL,
  `assigned_transporter_id` bigint(20) DEFAULT NULL,
  `pickup_location` text NOT NULL,
  `dropoff_location` text NOT NULL,
  `pickup_lat` decimal(10,8) DEFAULT NULL,
  `pickup_lng` decimal(11,8) DEFAULT NULL,
  `status` enum('OPEN','BID_ACCEPTED','IN_TRANSIT','COMPLETED') DEFAULT 'OPEN',
  `final_delivery_cost` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_bids`
--

CREATE TABLE `delivery_bids` (
  `bid_id` bigint(20) NOT NULL,
  `delivery_id` bigint(20) NOT NULL,
  `transporter_id` bigint(20) NOT NULL,
  `bid_amount` decimal(12,2) NOT NULL,
  `status` enum('PENDING','ACCEPTED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disputes`
--

CREATE TABLE `disputes` (
  `dispute_id` bigint(20) NOT NULL,
  `order_id` bigint(20) NOT NULL,
  `complainant_id` bigint(20) DEFAULT NULL,
  `raised_by_id` bigint(20) NOT NULL,
  `against_id` bigint(20) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `evidence_url` varchar(255) DEFAULT NULL,
  `status` enum('OPEN','IN_REVIEW','RESOLVED','REJECTED') NOT NULL DEFAULT 'OPEN',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `farmerprofiles`
--

CREATE TABLE `farmerprofiles` (
  `profile_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `farm_name` varchar(100) DEFAULT NULL,
  `farm_size_acres` decimal(5,2) DEFAULT NULL,
  `soil_type` varchar(100) DEFAULT NULL,
  `specializations` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farmerprofiles`
--

INSERT INTO `farmerprofiles` (`profile_id`, `user_id`, `farm_name`, `farm_size_acres`, `soil_type`, `specializations`) VALUES
(1, 1, 'Rahim Agro Farm', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` bigint(20) NOT NULL,
  `order_id` bigint(20) NOT NULL,
  `sender_id` bigint(20) NOT NULL,
  `receiver_id` bigint(20) NOT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `order_id`, `sender_id`, `receiver_id`, `content`, `is_read`, `created_at`) VALUES
(1, 4, 8, 7, 'Hello', 0, '2025-12-21 15:07:07'),
(2, 5, 8, 7, 'Hello. your product is ready', 0, '2025-12-21 17:45:29');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orderitems`
--

CREATE TABLE `orderitems` (
  `item_id` bigint(20) NOT NULL,
  `order_id` bigint(20) NOT NULL,
  `product_id` bigint(20) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `price_at_purchase` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` bigint(20) NOT NULL,
  `buyer_id` bigint(20) NOT NULL,
  `farmer_id` bigint(20) DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `status` enum('PENDING','PAID','PROCESSING','READY_FOR_PICKUP','SHIPPED','DELIVERED','CANCELLED','DISPUTED') DEFAULT 'PENDING',
  `payment_status` enum('UNPAID','PENDING','PAID','PARTIAL','REFUNDED') DEFAULT 'UNPAID',
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `shipping_address` text NOT NULL,
  `delivery_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `buyer_id`, `farmer_id`, `total_amount`, `status`, `payment_status`, `payment_method`, `transaction_id`, `shipping_address`, `delivery_note`, `created_at`, `updated_at`) VALUES
(1, 7, 1, 65.00, 'PENDING', 'UNPAID', NULL, NULL, 'Adabor', NULL, '2025-12-21 02:59:15', NULL),
(2, 7, 1, 65.00, 'PENDING', 'UNPAID', NULL, NULL, 'Adabor', NULL, '2025-12-21 03:06:37', NULL),
(3, 7, 1, 65.00, '', 'PAID', NULL, NULL, 'Badda', NULL, '2025-12-21 04:08:59', '2025-12-21 04:51:33'),
(4, 7, 8, 20.00, 'PENDING', 'UNPAID', NULL, NULL, 'Badda', NULL, '2025-12-21 04:49:48', NULL),
(5, 7, 8, 200.00, 'PENDING', 'UNPAID', NULL, NULL, 'Badda', NULL, '2025-12-21 17:11:53', NULL),
(6, 7, 8, 20.00, 'PENDING', 'UNPAID', NULL, NULL, 'Badda', NULL, '2025-12-21 17:30:43', NULL),
(7, 7, 8, 400.00, 'PENDING', 'UNPAID', NULL, NULL, 'Badda', NULL, '2025-12-21 17:50:36', NULL),
(8, 7, 8, 20.00, 'PENDING', 'UNPAID', NULL, NULL, 'Badda', NULL, '2025-12-21 17:55:54', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `item_id` bigint(20) NOT NULL,
  `order_id` bigint(20) NOT NULL,
  `product_id` bigint(20) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`item_id`, `order_id`, `product_id`, `quantity`, `unit_price`, `subtotal`, `created_at`) VALUES
(1, 2, 1, 1.00, 65.00, 65.00, '2025-12-21 03:06:37'),
(2, 3, 1, 1.00, 65.00, 65.00, '2025-12-21 04:08:59'),
(3, 4, 2, 1.00, 20.00, 20.00, '2025-12-21 04:49:48'),
(4, 5, 9, 10.00, 20.00, 200.00, '2025-12-21 17:11:53'),
(5, 6, 9, 1.00, 20.00, 20.00, '2025-12-21 17:30:43'),
(6, 7, 9, 20.00, 20.00, 400.00, '2025-12-21 17:50:36'),
(7, 8, 9, 1.00, 20.00, 20.00, '2025-12-21 17:55:54');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` bigint(20) NOT NULL,
  `order_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `method_id` bigint(20) NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `gateway_transaction_id` varchar(255) DEFAULT NULL,
  `gateway_response` text DEFAULT NULL,
  `payment_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_details`)),
  `reference_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `processing_fee` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('PENDING','PROCESSING','COMPLETED','FAILED','CANCELLED','REFUNDED') DEFAULT 'PENDING',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `order_id`, `user_id`, `method_id`, `transaction_id`, `gateway_transaction_id`, `gateway_response`, `payment_details`, `reference_number`, `notes`, `processed_at`, `amount`, `processing_fee`, `total_amount`, `status`, `created_at`, `updated_at`) VALUES
(1, 3, 7, 1, 'TEST_1766290887', NULL, NULL, '{\"transaction_id\":\"TEST_1766290887\",\"payment_method\":\"bKash\",\"submitted_at\":\"2025-12-21 05:21:27\",\"notes\":\"Test payment creation\"}', 'PAY20251221493175', '', '2025-12-21 04:51:33', 65.00, 0.98, 65.98, 'COMPLETED', '2025-12-21 04:21:27', '2025-12-21 04:51:33');

-- --------------------------------------------------------

--
-- Table structure for table `payments_backup`
--

CREATE TABLE `payments_backup` (
  `payment_id` bigint(20) NOT NULL DEFAULT 0,
  `order_id` bigint(20) NOT NULL,
  `payment_method` enum('BKASH','NAGAD','ROCKET','CASH') NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `status` enum('PENDING','SUCCESS','FAILED') DEFAULT 'PENDING',
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `method_id` bigint(20) NOT NULL,
  `type` enum('MOBILE_BANKING','BANK_TRANSFER','CARD','CASH_ON_DELIVERY') NOT NULL,
  `provider` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `logo_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `processing_fee_percentage` decimal(5,2) DEFAULT 0.00,
  `min_amount` decimal(10,2) DEFAULT 0.00,
  `max_amount` decimal(10,2) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`method_id`, `type`, `provider`, `name`, `logo_url`, `is_active`, `processing_fee_percentage`, `min_amount`, `max_amount`, `instructions`, `created_at`) VALUES
(1, 'MOBILE_BANKING', 'bKash', 'bKash', NULL, 1, 1.50, 0.00, NULL, NULL, '2025-12-21 01:37:31'),
(2, 'MOBILE_BANKING', 'Nagad', 'Nagad', NULL, 1, 1.50, 0.00, NULL, NULL, '2025-12-21 01:37:31'),
(3, 'MOBILE_BANKING', 'Rocket', 'Rocket', NULL, 1, 1.80, 0.00, NULL, NULL, '2025-12-21 01:37:31'),
(4, 'MOBILE_BANKING', 'Upay', 'Upay', NULL, 1, 1.50, 0.00, NULL, NULL, '2025-12-21 01:37:31'),
(5, 'CARD', 'Visa', 'Visa Card', NULL, 1, 2.50, 0.00, NULL, NULL, '2025-12-21 01:37:31'),
(6, 'CARD', 'MasterCard', 'MasterCard', NULL, 1, 2.50, 0.00, NULL, NULL, '2025-12-21 01:37:31'),
(7, 'CARD', 'Amex', 'American Express', NULL, 1, 3.00, 0.00, NULL, NULL, '2025-12-21 01:37:31'),
(8, 'BANK_TRANSFER', 'DBBL', 'Bank Transfer', NULL, 1, 0.00, 0.00, NULL, NULL, '2025-12-21 01:37:31');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` bigint(20) NOT NULL,
  `farmer_id` bigint(20) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `quantity_available` decimal(10,2) NOT NULL,
  `unit` varchar(10) DEFAULT 'KG',
  `price_per_unit` decimal(10,2) NOT NULL,
  `quality_grade` enum('A','B','C','EXPORT_QUALITY') DEFAULT 'A',
  `harvest_date` date NOT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `trace_id` varchar(64) NOT NULL,
  `qr_code_url` varchar(255) DEFAULT NULL,
  `status` enum('PENDING','ACTIVE','INACTIVE','SOLD_OUT','HIDDEN') DEFAULT 'PENDING',
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `farmer_id`, `category_id`, `title`, `description`, `image_url`, `quantity_available`, `unit`, `price_per_unit`, `quality_grade`, `harvest_date`, `batch_number`, `trace_id`, `qr_code_url`, `status`, `is_deleted`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Premium Miniket Rice', NULL, NULL, 1000.00, 'KG', 65.00, 'A', '2025-11-15', NULL, 'UUID-TRACE-001', NULL, 'ACTIVE', 0, '2025-11-18 11:11:04', '2025-12-05 13:50:59'),
(2, 8, 1, 'Potato', 'Fresh potato', 'uploads/product_images/1766291319_download.jpeg', 500.00, 'KG', 20.00, 'A', '2025-11-29', '1', 'TRC694777773f2d8', 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=http%3A%2F%2Flocalhost%2FAgrohaat%2Fpublic%2Ftrace.php%3Ftid%3DTRC694777773f2d8', 'ACTIVE', 1, '2025-12-21 04:28:39', '2025-12-21 16:33:43'),
(3, 8, 2, 'Tomato', 'Fresh tomato', NULL, 200.00, 'KG', 70.00, 'A', '2025-12-10', '12', 'TRACE_1766333858_69481da298466', NULL, '', 1, '2025-12-21 16:17:38', '2025-12-21 16:29:08'),
(4, 8, 2, 'Tomato', 'Fresh tomato', NULL, 200.00, 'KG', 70.00, 'A', '2025-12-10', '12', 'TRACE_1766334539_6948204bd5798', NULL, '', 1, '2025-12-21 16:28:59', '2025-12-21 16:29:06'),
(5, 8, 2, 'Tomato', 'Fresh Tomato', NULL, 100.00, 'KG', 70.00, 'A', '2025-12-02', '12', 'TRACE_1766334610_6948209228208', NULL, '', 1, '2025-12-21 16:30:10', '2025-12-21 16:38:35'),
(6, 8, 1, 'Tomato', 'Fresh Tomato', NULL, 200.00, 'KG', 70.00, 'A', '2025-12-02', '1', 'TRACE_1766335076_69482264de3b3', NULL, '', 1, '2025-12-21 16:37:56', '2025-12-21 16:38:37'),
(7, 8, 3, 'Tomato', 'Fresh Tomato', NULL, 100.00, 'KG', 70.00, 'A', '2025-12-03', '1', 'TRACE_1766335153_694822b176134', NULL, '', 1, '2025-12-21 16:39:13', '2025-12-21 16:45:21'),
(8, 8, 1, 'Potato', 'Fresh', 'uploads/product_images/1766335515_download.jpeg', 100.00, 'KG', 20.00, 'A', '2025-12-05', '1', 'TRACE_1766335515_6948241b0b49c', NULL, '', 1, '2025-12-21 16:45:15', '2025-12-21 17:06:34'),
(9, 8, 1, 'Potato', 'Fresh', 'uploads/product_images/1766336849_download.jpeg', 200.00, 'kg', 20.00, 'B', '2025-11-06', '1', 'TRACE_1766336849_694829518bfaa', NULL, 'ACTIVE', 0, '2025-12-21 17:07:29', '2025-12-21 17:08:06'),
(10, 8, 2, 'Tomato', 'Fresh Natural Tomato', 'uploads/product_images/1766339198_7932idea99Tomatoes-HD-Image-011.jpg', 100.00, 'kg', 70.00, 'A', '2025-12-04', '2', 'TRACE_1766339198_6948327ec409a', NULL, 'ACTIVE', 0, '2025-12-21 17:46:38', '2025-12-21 17:58:02'),
(11, 8, 3, 'Cucumber', 'Natural Cucumber. No chemical, authentic', 'uploads/product_images/1767254110_0_cucumber-farming.jpg', 300.00, 'kg', 40.00, 'A', '2025-12-30', '456', 'TRACE_1767254110_6956285eb0d1e', 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=http%3A%2F%2Flocalhost%2FAgrohaat%2Fpublic%2Ftrace.php%3Ftrace_id%3DTRACE_1767254110_6956285eb0d1e', 'ACTIVE', 1, '2026-01-01 07:55:10', '2026-01-01 08:14:52'),
(12, 8, 5, 'Cucumber', 'Fresh, Natural, Authentic Cucumber', 'uploads/product_images/1767255352_0_cucumber-farming.jpg', 300.00, 'kg', 40.00, 'A', '2025-12-26', '456', 'TRACE_1767255352_69562d3858e55', 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=http%3A%2F%2Flocalhost%2FAgrohaat%2Fpublic%2Ftrace.php%3Ftrace_id%3DTRACE_1767255352_69562d3858e55', 'ACTIVE', 0, '2026-01-01 08:15:52', '2026-01-01 08:16:02');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `image_id` bigint(20) NOT NULL,
  `product_id` bigint(20) NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`image_id`, `product_id`, `image_url`, `display_order`, `is_primary`, `created_at`) VALUES
(1, 9, 'uploads/product_images/1766336849_download.jpeg', 0, 1, '2026-01-01 08:09:48'),
(2, 10, 'uploads/product_images/1766339198_7932idea99Tomatoes-HD-Image-011.jpg', 0, 1, '2026-01-01 08:09:48'),
(3, 11, 'uploads/product_images/1767254110_0_cucumber-farming.jpg', 0, 1, '2026-01-01 08:09:48'),
(4, 12, 'uploads/product_images/1767255352_0_cucumber-farming.jpg', 0, 1, '2026-01-01 08:15:52'),
(5, 12, 'uploads/product_images/1767255352_1_Step-by-Step-Guide-to-Cucumber-Farming1.jpg', 1, 0, '2026-01-01 08:15:52');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` bigint(20) NOT NULL,
  `order_id` bigint(20) NOT NULL,
  `reviewer_id` bigint(20) NOT NULL,
  `reviewee_id` bigint(20) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transporterprofiles`
--

CREATE TABLE `transporterprofiles` (
  `profile_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `vehicle_type` enum('TRUCK','PICKUP','VAN','CNG','BOAT') NOT NULL,
  `license_plate` varchar(50) NOT NULL,
  `max_capacity_kg` int(11) NOT NULL,
  `service_area_districts` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transporterprofiles`
--

INSERT INTO `transporterprofiles` (`profile_id`, `user_id`, `vehicle_type`, `license_plate`, `max_capacity_kg`, `service_area_districts`) VALUES
(1, 3, 'TRUCK', 'DHK-T-1234', 5000, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transporter_profiles`
--

CREATE TABLE `transporter_profiles` (
  `transporter_id` bigint(20) NOT NULL,
  `vehicle_type` varchar(50) NOT NULL,
  `license_plate` varchar(50) DEFAULT NULL,
  `max_capacity_kg` int(11) NOT NULL,
  `service_area_districts` text DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `vehicle_number` varchar(50) DEFAULT NULL,
  `capacity_kg` decimal(10,2) DEFAULT NULL,
  `service_area` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `user_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transporter_profiles`
--

INSERT INTO `transporter_profiles` (`transporter_id`, `vehicle_type`, `license_plate`, `max_capacity_kg`, `service_area_districts`, `is_verified`, `created_at`, `updated_at`, `vehicle_number`, `capacity_kg`, `service_area`, `is_available`, `user_id`) VALUES
(6, 'PICKUP', 'DHK-T-1234', 1000, 'Dhaka, Tangail', 0, '2025-12-21 15:05:42', '2025-12-21 18:01:07', NULL, NULL, NULL, 1, 6);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` bigint(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('ADMIN','FARMER','BUYER','TRANSPORTER') NOT NULL,
  `division` varchar(50) DEFAULT NULL,
  `district` varchar(50) DEFAULT NULL,
  `upazila` varchar(50) DEFAULT NULL,
  `address_details` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `phone_number`, `email`, `password_hash`, `role`, `division`, `district`, `upazila`, `address_details`, `latitude`, `longitude`, `is_verified`, `is_deleted`, `created_at`, `updated_at`) VALUES
(1, 'Rahim Mia', '01711111111', NULL, 'hashed_pass_123', 'FARMER', NULL, 'Dinajpur', NULL, NULL, NULL, NULL, 1, 0, '2025-11-18 11:11:04', '2025-12-07 20:23:12'),
(2, 'ACI Logistics', '01811111111', NULL, 'hashed_pass_123', 'BUYER', NULL, 'Dhaka', NULL, NULL, NULL, NULL, 1, 0, '2025-11-18 11:11:04', '2025-12-07 20:23:15'),
(3, 'Jamal Trucking', '01911111111', NULL, 'hashed_pass_123', 'TRANSPORTER', NULL, 'Tangail', NULL, NULL, NULL, NULL, 1, 0, '2025-11-18 11:11:04', '2025-12-07 20:23:17'),
(4, 'Zarif Latif', '01308035203', 'zariffromlatif@gmail.com', '$2y$10$66KzdBtn4zt8wKcWCuTtX.WG2zbadzbLiWhr3smx9AM/EBOU.B38O', 'FARMER', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2025-12-02 11:43:08', '2025-12-07 20:12:42'),
(5, 'Admin User', '+8801234567890', 'admin@agrohaat.com', '$2y$10$MNkgh5O9XevnkDCo87rHoe662XCL9MKT.6zpe15d6uCs3usRZECZG', 'ADMIN', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2025-12-05 14:30:14', '2025-12-05 14:46:16'),
(6, 'Karim', '+8801738675439', 'karim@transporter.com', '$2y$10$2kASfNK3MOwUePrV4jMCUu156GkWKPOysAbBuN1MVLbiHssbEl9U2', 'TRANSPORTER', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2025-12-05 14:53:43', '2025-12-07 20:12:40'),
(7, 'Rahim', '+8801748532954', 'rahim@buyer.com', '$2y$10$0lHCrwbNI1a.u9hUz0rkNO3MV4obNntHoparVYq94iTXxeIJbcORu', 'BUYER', NULL, 'Dhaka', 'Badda', '', NULL, NULL, 1, 0, '2025-12-05 14:55:24', '2025-12-21 17:53:28'),
(8, 'jamal', '+8801758356853', 'jamal@farmer.com', '$2y$10$XH5inZepYGRCv.AOZnYu1.pFyS8f02eHJ.kb5wJGVjWR78yln1zQm', 'FARMER', 'Tangail', 'Ghatail', 'Ghatail', NULL, NULL, NULL, 1, 0, '2025-12-05 14:57:04', '2025-12-21 04:32:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_activity_user` (`user_id`);

--
-- Indexes for table `buyerprofiles`
--
ALTER TABLE `buyerprofiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `chatmessages`
--
ALTER TABLE `chatmessages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD PRIMARY KEY (`delivery_id`),
  ADD UNIQUE KEY `tracking_number` (`tracking_number`),
  ADD KEY `fk_deliveries_order` (`order_id`),
  ADD KEY `fk_deliveries_transporter` (`transporter_id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `bid_id` (`bid_id`);

--
-- Indexes for table `deliverybids`
--
ALTER TABLE `deliverybids`
  ADD PRIMARY KEY (`bid_id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `transporter_id` (`transporter_id`);

--
-- Indexes for table `deliveryjobs`
--
ALTER TABLE `deliveryjobs`
  ADD PRIMARY KEY (`job_id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `assigned_transporter_id` (`assigned_transporter_id`),
  ADD KEY `idx_jobs_location` (`pickup_lat`,`pickup_lng`);

--
-- Indexes for table `delivery_bids`
--
ALTER TABLE `delivery_bids`
  ADD PRIMARY KEY (`bid_id`),
  ADD KEY `fk_bids_delivery` (`delivery_id`),
  ADD KEY `fk_bids_transporter` (`transporter_id`);

--
-- Indexes for table `disputes`
--
ALTER TABLE `disputes`
  ADD PRIMARY KEY (`dispute_id`),
  ADD KEY `fk_disputes_order` (`order_id`),
  ADD KEY `fk_disputes_raised_by` (`raised_by_id`),
  ADD KEY `fk_disputes_against` (`against_id`),
  ADD KEY `complainant_id` (`complainant_id`);

--
-- Indexes for table `farmerprofiles`
--
ALTER TABLE `farmerprofiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `fk_messages_order` (`order_id`),
  ADD KEY `fk_messages_sender` (`sender_id`),
  ADD KEY `fk_messages_receiver` (`receiver_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orderitems`
--
ALTER TABLE `orderitems`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `fk_orders_farmer` (`farmer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `fk_payments_method` (`method_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_transaction` (`transaction_id`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`method_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `trace_id` (`trace_id`),
  ADD UNIQUE KEY `uniq_products_trace_id` (`trace_id`),
  ADD KEY `farmer_id` (`farmer_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_products_search` (`title`,`category_id`,`status`),
  ADD KEY `idx_products_trace` (`trace_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_display_order` (`display_order`),
  ADD KEY `idx_primary` (`is_primary`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `transporterprofiles`
--
ALTER TABLE `transporterprofiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `license_plate` (`license_plate`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transporter_profiles`
--
ALTER TABLE `transporter_profiles`
  ADD PRIMARY KEY (`transporter_id`),
  ADD UNIQUE KEY `license_plate` (`license_plate`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `phone_number` (`phone_number`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_login` (`phone_number`,`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buyerprofiles`
--
ALTER TABLE `buyerprofiles`
  MODIFY `profile_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `chatmessages`
--
ALTER TABLE `chatmessages`
  MODIFY `message_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `delivery_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deliverybids`
--
ALTER TABLE `deliverybids`
  MODIFY `bid_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deliveryjobs`
--
ALTER TABLE `deliveryjobs`
  MODIFY `job_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_bids`
--
ALTER TABLE `delivery_bids`
  MODIFY `bid_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `disputes`
--
ALTER TABLE `disputes`
  MODIFY `dispute_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `farmerprofiles`
--
ALTER TABLE `farmerprofiles`
  MODIFY `profile_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orderitems`
--
ALTER TABLE `orderitems`
  MODIFY `item_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `item_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `method_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `image_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transporterprofiles`
--
ALTER TABLE `transporterprofiles`
  MODIFY `profile_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `buyerprofiles`
--
ALTER TABLE `buyerprofiles`
  ADD CONSTRAINT `buyerprofiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `chatmessages`
--
ALTER TABLE `chatmessages`
  ADD CONSTRAINT `chatmessages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `chatmessages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD CONSTRAINT `deliveries_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `deliveryjobs` (`job_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deliveries_ibfk_2` FOREIGN KEY (`bid_id`) REFERENCES `deliverybids` (`bid_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_deliveries_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_deliveries_transporter` FOREIGN KEY (`transporter_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `deliverybids`
--
ALTER TABLE `deliverybids`
  ADD CONSTRAINT `deliverybids_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `deliveryjobs` (`job_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deliverybids_ibfk_2` FOREIGN KEY (`transporter_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `deliveryjobs`
--
ALTER TABLE `deliveryjobs`
  ADD CONSTRAINT `deliveryjobs_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `deliveryjobs_ibfk_2` FOREIGN KEY (`assigned_transporter_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `delivery_bids`
--
ALTER TABLE `delivery_bids`
  ADD CONSTRAINT `fk_bids_delivery` FOREIGN KEY (`delivery_id`) REFERENCES `deliveries` (`delivery_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bids_transporter` FOREIGN KEY (`transporter_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `disputes`
--
ALTER TABLE `disputes`
  ADD CONSTRAINT `disputes_ibfk_1` FOREIGN KEY (`complainant_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_disputes_against` FOREIGN KEY (`against_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_disputes_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_disputes_raised_by` FOREIGN KEY (`raised_by_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `farmerprofiles`
--
ALTER TABLE `farmerprofiles`
  ADD CONSTRAINT `farmerprofiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_messages_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `orderitems`
--
ALTER TABLE `orderitems`
  ADD CONSTRAINT `orderitems_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orderitems_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_method` FOREIGN KEY (`method_id`) REFERENCES `payment_methods` (`method_id`),
  ADD CONSTRAINT `fk_payments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `transporterprofiles`
--
ALTER TABLE `transporterprofiles`
  ADD CONSTRAINT `transporterprofiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `transporter_profiles`
--
ALTER TABLE `transporter_profiles`
  ADD CONSTRAINT `fk_transporter_profiles_user` FOREIGN KEY (`transporter_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transporter_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transporter_profiles_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
