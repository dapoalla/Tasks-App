-- Database Schema for Church Fund Tracker
-- Consolidated: Funds, Fund Items, and Expenses

CREATE TABLE IF NOT EXISTS `funds` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `amount` DECIMAL(15, 2) NOT NULL,
    `date_released` DATE NOT NULL,
    `description` TEXT,
    `received_by` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `fund_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `fund_id` INT NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `amount` DECIMAL(15, 2) NOT NULL,
    FOREIGN KEY (`fund_id`) REFERENCES `funds`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `expenses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `fund_id` INT DEFAULT NULL,
    `item_name` VARCHAR(255) NOT NULL,
    `amount` DECIMAL(15, 2) NOT NULL,
    `date_incurred` DATE NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `vendor` VARCHAR(100),
    `receipt_path` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`fund_id`) REFERENCES `funds`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT PRIMARY KEY CHECK (id = 1),
    `church_name` VARCHAR(255) NOT NULL DEFAULT 'Apostolic Faith WECA',
    `dept_name` VARCHAR(255) NOT NULL DEFAULT 'ICT Department',
    `app_name` VARCHAR(100) NOT NULL DEFAULT 'Church Funds Manager',
    `smtp_host` VARCHAR(255),
    `smtp_user` VARCHAR(255),
    `smtp_pass` VARCHAR(255),
    `smtp_port` INT DEFAULT 587,
    `smtp_from_email` VARCHAR(255),
    `smtp_from_name` VARCHAR(255),
    `notification_emails` TEXT,
    `report_recipient_email` VARCHAR(255),
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `settings` (`id`, `church_name`, `dept_name`, `app_name`) 
VALUES (1, 'Apostolic Faith WECA', 'ICT Department', 'Tasks Manager');

CREATE TABLE IF NOT EXISTS `tasks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `task_name` VARCHAR(255) NOT NULL,
    `location_dept` VARCHAR(255),
    `description` TEXT,
    `assigned_to` VARCHAR(255),
    `due_date` DATE,
    `priority` ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
    `status` ENUM('Pending', 'In Progress', 'Completed') DEFAULT 'Pending',
    `status_details` TEXT,
    `document_path` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `internet_subscriptions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `location_dept` VARCHAR(255) NOT NULL,
    `provider` VARCHAR(255),
    `plan_name` VARCHAR(255),
    `amount` DECIMAL(15, 2),
    `expiry_date` DATE,
    `renewal_status` ENUM('Done', 'Not Done', 'Waiting for Funds') DEFAULT 'Not Done',
    `last_paid_date` DATE,
    `notes` TEXT,
    `document_path` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(20) DEFAULT 'admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
