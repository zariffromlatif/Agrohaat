-- Migration: Add PENDING and INACTIVE status to products table
-- This allows products to be in a pending approval state before being active

ALTER TABLE `products` 
MODIFY COLUMN `status` ENUM('PENDING', 'ACTIVE', 'INACTIVE', 'SOLD_OUT', 'HIDDEN') DEFAULT 'PENDING';

