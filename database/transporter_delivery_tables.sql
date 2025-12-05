-- Transporter Delivery System Tables
-- Run this SQL file to create tables needed for the delivery marketplace and bidding system

USE agrohaat_db;

-- Delivery Jobs table (jobs created from paid orders)
CREATE TABLE IF NOT EXISTS deliveryjobs (
  job_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT NOT NULL,
  pickup_location TEXT NOT NULL,
  dropoff_location TEXT NOT NULL,
  status ENUM('OPEN', 'BIDDING', 'ASSIGNED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED') DEFAULT 'OPEN',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
  INDEX idx_status (status),
  INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Delivery Bids table (bids placed by transporters)
CREATE TABLE IF NOT EXISTS deliverybids (
  bid_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  job_id BIGINT NOT NULL,
  transporter_id BIGINT NOT NULL,
  bid_amount DECIMAL(10,2) NOT NULL,
  message TEXT,
  status ENUM('PENDING', 'ACCEPTED', 'REJECTED', 'WITHDRAWN') DEFAULT 'PENDING',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (job_id) REFERENCES deliveryjobs(job_id) ON DELETE CASCADE,
  FOREIGN KEY (transporter_id) REFERENCES users(user_id) ON DELETE CASCADE,
  INDEX idx_job (job_id),
  INDEX idx_transporter (transporter_id),
  INDEX idx_status (status),
  UNIQUE KEY unique_job_transporter (job_id, transporter_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Deliveries table (assigned deliveries with status tracking)
CREATE TABLE IF NOT EXISTS deliveries (
  delivery_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  job_id BIGINT NOT NULL,
  order_id BIGINT NOT NULL,
  transporter_id BIGINT NOT NULL,
  bid_id BIGINT,
  status ENUM('ASSIGNED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED', 'CANCELLED') DEFAULT 'ASSIGNED',
  tracking_number VARCHAR(100) UNIQUE,
  pickup_time DATETIME,
  delivery_time DATETIME,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (job_id) REFERENCES deliveryjobs(job_id) ON DELETE CASCADE,
  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
  FOREIGN KEY (transporter_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (bid_id) REFERENCES deliverybids(bid_id) ON DELETE SET NULL,
  INDEX idx_status (status),
  INDEX idx_transporter (transporter_id),
  INDEX idx_order (order_id),
  INDEX idx_tracking (tracking_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications table (optional - for bid notifications)
CREATE TABLE IF NOT EXISTS notifications (
  notification_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT NOT NULL,
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  is_read BOOLEAN DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  INDEX idx_user (user_id),
  INDEX idx_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

