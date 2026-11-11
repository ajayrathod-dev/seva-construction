-- ============================================
-- Seva Construction - Database Setup Script
-- ============================================
-- Run this SQL script in phpMyAdmin or MySQL CLI
-- to create the database and required tables.
-- ============================================

CREATE DATABASE IF NOT EXISTS `seva_construction` 
  DEFAULT CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `seva_construction`;

-- -------------------------------------------
-- Table: construction (Projects Showcase)
-- Stores all uploaded construction project showcase images
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `construction` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `image_path` VARCHAR(500) NOT NULL,
  `category` ENUM('Brickwork', 'Plastering', 'House Design', 'Full Construction', 'Renovation') NOT NULL DEFAULT 'Brickwork',
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------
-- Table: hero_slides
-- Stores banner slides for the homepage carousel
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `hero_slides` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `image_path` VARCHAR(500) NOT NULL,
  `title` VARCHAR(255) DEFAULT NULL,
  `subtitle` VARCHAR(255) DEFAULT NULL,
  `sort_order` INT(11) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------
-- Table: site_settings
-- Stores custom appearance and configuration key-values
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `site_settings` (
  `setting_key` VARCHAR(50) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------
-- Table: admins
-- Stores admin login credentials
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------
-- Default Admin User
-- Username: admin
-- Password: password (change after first login)
-- Hash generated using password_hash('password', PASSWORD_BCRYPT)
-- -------------------------------------------
INSERT INTO `admins` (`username`, `password`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE `username` = `username`;

-- -------------------------------------------
-- Sample Project Data (Optional)
-- -------------------------------------------
INSERT INTO `construction` (`title`, `image_path`, `category`, `description`) VALUES
('Traditional Brick Masonry', 'uploads/project_1775212593_8aad93c6.jpg', 'Brickwork', 'Expertly laid red brick walls with precision alignment and durable finishing.'),
('Exterior Cement Plastering', 'uploads/project_1775228232_c57a3231.jpg', 'Plastering', 'High-quality smooth plaster finish for exterior walls.')
ON DUPLICATE KEY UPDATE `id` = `id`;
