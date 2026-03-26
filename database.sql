CREATE DATABASE IF NOT EXISTS smart_menu_saas;
USE smart_menu_saas;

CREATE TABLE IF NOT EXISTS `hotels` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `hotel_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `tax_percent` DECIMAL(5,2) DEFAULT 5.00,
    `currency` VARCHAR(10) DEFAULT 'INR',
    `logo_url` VARCHAR(255) DEFAULT 'default-logo.png',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `hotel_id` INT NOT NULL,
    `category_name` VARCHAR(50) NOT NULL,
    FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `menu_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `hotel_id` INT NOT NULL,
    `category_id` INT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `price` DECIMAL(10,2) NOT NULL,
    `image_url` VARCHAR(255),
    `calories` INT DEFAULT 0,
    `protein` INT DEFAULT 0,
    `carbs` INT DEFAULT 0,
    `fats` INT DEFAULT 0,
    `is_available` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `tables` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `hotel_id` INT NOT NULL,
    `table_number` INT NOT NULL,
    `seating_capacity` INT DEFAULT 2,
    UNIQUE KEY `hotel_table` (`hotel_id`, `table_number`),
    FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `hotel_id` INT NOT NULL,
    `table_number` INT NOT NULL,
    `items_summary` TEXT NOT NULL,
    `total_price` DECIMAL(10,2) NOT NULL,
    `status` ENUM('pending', 'preparing', 'served', 'cancelled') DEFAULT 'pending',
    `order_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `feedback` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `hotel_id` INT NOT NULL,
    `order_id` INT,
    `rating` INT CHECK (rating >= 1 AND rating <= 5),
    `comment` TEXT,
    `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_hotel_order_status ON orders (hotel_id, status);
CREATE INDEX idx_table_lookup ON tables (hotel_id, table_number);
