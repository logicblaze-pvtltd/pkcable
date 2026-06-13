-- Pakistan Cable Database Schema

-- Create Database
CREATE DATABASE IF NOT EXISTS `pkcable` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `pkcable`;

-- ==========================================
-- Packages Table
-- ==========================================
CREATE TABLE IF NOT EXISTS `packages` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `price` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;

-- ==========================================
-- Users Table
-- ==========================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NULL,
    `email` VARCHAR(255) NULL,
    `password` VARCHAR(255) NULL,
    `user_role` ENUM('super admin', 'admin', 'manager', 'customer') DEFAULT 'customer',
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `package` INT NULL,
    `address` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`package`) REFERENCES `packages` (`id`) ON DELETE SET NULL,
    INDEX `idx_email` (`email`),
    INDEX `idx_status` (`status`),
    INDEX `idx_user_role` (`user_role`)
) ENGINE = InnoDB;

-- ==========================================
-- Subscriptions Table
-- ==========================================
CREATE TABLE IF NOT EXISTS `subscriptions` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `package_id` INT NOT NULL,
    `start_date` DATE NULL,
    `end_date` DATE NULL,
    `status` ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    `discount` VARCHAR(255) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_package_id` (`package_id`),
    INDEX `idx_status` (`status`)
) ENGINE = InnoDB;

-- ==========================================
-- Sample Data - Packages
-- ==========================================
INSERT INTO `packages` (`name`, `price`) VALUES
('4 Mb', '$20'),
('8 Mb', '$30'),
('10 Mb', '$40');
