-- SQL migration for disputes table (Admin Module)
-- Run this if the disputes table doesn't exist yet

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

