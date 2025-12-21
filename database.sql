-- =====================================================
-- 2SURESUB DATABASE SCHEMA
-- Data Reselling Platform
-- =====================================================

CREATE DATABASE IF NOT EXISTS `2suresubs` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `2suresubs`;

-- =====================================================
-- USERS & AUTHENTICATION
-- =====================================================

CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `role` ENUM('user', 'reseller', 'admin', 'superadmin') DEFAULT 'user',
    `status` ENUM('active', 'suspended', 'pending') DEFAULT 'active',
    `email_verified` TINYINT(1) DEFAULT 0,
    `phone_verified` TINYINT(1) DEFAULT 0,
    `referral_code` VARCHAR(20) UNIQUE,
    `referred_by` INT NULL,
    `profile_image` VARCHAR(255) DEFAULT NULL,
    `pin` VARCHAR(10) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`referred_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- WALLET SYSTEM
-- =====================================================

CREATE TABLE `wallets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `balance` DECIMAL(15, 2) DEFAULT 0.00,
    `bonus_balance` DECIMAL(15, 2) DEFAULT 0.00,
    `total_funded` DECIMAL(15, 2) DEFAULT 0.00,
    `total_spent` DECIMAL(15, 2) DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `wallet_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `type` ENUM('credit', 'debit') NOT NULL,
    `amount` DECIMAL(15, 2) NOT NULL,
    `balance_before` DECIMAL(15, 2) NOT NULL,
    `balance_after` DECIMAL(15, 2) NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `reference` VARCHAR(100) UNIQUE NOT NULL,
    `status` ENUM('pending', 'completed', 'failed', 'reversed') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TRANSACTIONS (All Services)
-- =====================================================

CREATE TABLE `transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `type` ENUM('data', 'airtime', 'cable', 'electricity', 'exam', 'funding', 'transfer', 'commission') NOT NULL,
    `network` VARCHAR(50) NULL,
    `phone_number` VARCHAR(20) NULL,
    `amount` DECIMAL(15, 2) NOT NULL,
    `cost_price` DECIMAL(15, 2) DEFAULT 0.00,
    `profit` DECIMAL(15, 2) DEFAULT 0.00,
    `plan_name` VARCHAR(100) NULL,
    `smart_card_number` VARCHAR(50) NULL,
    `meter_number` VARCHAR(50) NULL,
    `token` TEXT NULL,
    `api_response` TEXT NULL,
    `reference` VARCHAR(100) UNIQUE NOT NULL,
    `external_reference` VARCHAR(100) NULL,
    `status` ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- DATA PLANS
-- =====================================================

CREATE TABLE `networks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `code` VARCHAR(10) NOT NULL,
    `logo` VARCHAR(255) NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `data_plans` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `network_id` INT NOT NULL,
    `plan_name` VARCHAR(100) NOT NULL,
    `plan_code` VARCHAR(50) NOT NULL,
    `data_amount` VARCHAR(50) NOT NULL,
    `validity` VARCHAR(50) NOT NULL,
    `price_user` DECIMAL(10, 2) NOT NULL,
    `price_reseller` DECIMAL(10, 2) NOT NULL,
    `price_api` DECIMAL(10, 2) NOT NULL,
    `cost_price` DECIMAL(10, 2) NOT NULL,
    `plan_type` ENUM('sme', 'gifting', 'corporate', 'direct') DEFAULT 'sme',
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`network_id`) REFERENCES `networks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- CABLE TV (DStv, GOtv, StarTimes)
-- =====================================================

CREATE TABLE `cable_providers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `code` VARCHAR(20) NOT NULL,
    `logo` VARCHAR(255) NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `cable_plans` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `provider_id` INT NOT NULL,
    `plan_name` VARCHAR(100) NOT NULL,
    `plan_code` VARCHAR(50) NOT NULL,
    `price_user` DECIMAL(10, 2) NOT NULL,
    `price_reseller` DECIMAL(10, 2) NOT NULL,
    `cost_price` DECIMAL(10, 2) NOT NULL,
    `validity` VARCHAR(50) DEFAULT '30 days',
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`provider_id`) REFERENCES `cable_providers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- ELECTRICITY
-- =====================================================

CREATE TABLE `electricity_discos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `logo` VARCHAR(255) NULL,
    `min_amount` DECIMAL(10, 2) DEFAULT 500.00,
    `max_amount` DECIMAL(10, 2) DEFAULT 100000.00,
    `service_charge` DECIMAL(10, 2) DEFAULT 100.00,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- EXAM RESULT CHECKERS
-- =====================================================

CREATE TABLE `exam_types` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `code` VARCHAR(20) NOT NULL,
    `logo` VARCHAR(255) NULL,
    `price_user` DECIMAL(10, 2) NOT NULL,
    `price_reseller` DECIMAL(10, 2) NOT NULL,
    `cost_price` DECIMAL(10, 2) NOT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- API SETTINGS (Superadmin Configurable)
-- =====================================================

CREATE TABLE `api_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `provider_name` VARCHAR(50) NOT NULL,
    `provider_type` ENUM('vtu', 'payment', 'sms', 'email') NOT NULL,
    `api_key` TEXT NULL,
    `secret_key` TEXT NULL,
    `base_url` VARCHAR(255) NULL,
    `username` VARCHAR(100) NULL,
    `password` TEXT NULL,
    `webhook_url` VARCHAR(255) NULL,
    `is_active` TINYINT(1) DEFAULT 0,
    `is_live` TINYINT(1) DEFAULT 0,
    `extra_config` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- SITE SETTINGS
-- =====================================================

CREATE TABLE `site_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) UNIQUE NOT NULL,
    `setting_value` TEXT NULL,
    `setting_type` ENUM('text', 'number', 'boolean', 'json', 'image') DEFAULT 'text',
    `category` VARCHAR(50) DEFAULT 'general',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- ACTIVITY LOG (Track Everything)
-- =====================================================

CREATE TABLE `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `action` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `module` VARCHAR(50) NOT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `old_data` JSON NULL,
    `new_data` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- SUPPORT TICKETS
-- =====================================================

CREATE TABLE `support_tickets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `ticket_number` VARCHAR(20) UNIQUE NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `category` ENUM('billing', 'technical', 'transaction', 'general') DEFAULT 'general',
    `priority` ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    `status` ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `ticket_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `message` TEXT NOT NULL,
    `attachment` VARCHAR(255) NULL,
    `is_staff` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- NOTIFICATIONS
-- =====================================================

CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    `is_read` TINYINT(1) DEFAULT 0,
    `link` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- COMMISSIONS (Reseller)
-- =====================================================

CREATE TABLE `commissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `transaction_id` INT NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `status` ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- DEFAULT DATA INSERTS
-- =====================================================

-- Networks
INSERT INTO `networks` (`name`, `code`, `logo`) VALUES
('MTN', 'mtn', 'mtn.png'),
('Airtel', 'airtel', 'airtel.png'),
('Glo', 'glo', 'glo.png'),
('9Mobile', '9mobile', '9mobile.png');

-- Cable Providers
INSERT INTO `cable_providers` (`name`, `code`, `logo`) VALUES
('DStv', 'dstv', 'dstv.png'),
('GOtv', 'gotv', 'gotv.png'),
('StarTimes', 'startimes', 'startimes.png'),
('Showmax', 'showmax', 'showmax.png');

-- Electricity Discos
INSERT INTO `electricity_discos` (`name`, `code`) VALUES
('Ikeja Electric', 'ikeja-electric'),
('Eko Electric', 'eko-electric'),
('Abuja Electric', 'abuja-electric'),
('Kano Electric', 'kano-electric'),
('Port Harcourt Electric', 'portharcourt-electric'),
('Ibadan Electric', 'ibadan-electric'),
('Kaduna Electric', 'kaduna-electric'),
('Jos Electric', 'jos-electric'),
('Enugu Electric', 'enugu-electric'),
('Benin Electric', 'benin-electric'),
('Yola Electric', 'yola-electric');

-- Exam Types
INSERT INTO `exam_types` (`name`, `code`, `price_user`, `price_reseller`, `cost_price`) VALUES
('WAEC Result Checker', 'waec', 3500.00, 3200.00, 3000.00),
('NECO Result Checker', 'neco', 1200.00, 1000.00, 800.00),
('NABTEB Result Checker', 'nabteb', 1200.00, 1000.00, 800.00),
('NBAIS Result Checker', 'nbais', 1200.00, 1000.00, 800.00);

-- Sample Cable Plans (GOtv)
INSERT INTO `cable_plans` (`provider_id`, `plan_name`, `plan_code`, `price_user`, `price_reseller`, `cost_price`) VALUES
(2, 'GOtv Smallie', 'gotv-smallie', 1575.00, 1500.00, 1400.00),
(2, 'GOtv Jinja', 'gotv-jinja', 2700.00, 2600.00, 2500.00),
(2, 'GOtv Jolli', 'gotv-jolli', 4150.00, 4000.00, 3800.00),
(2, 'GOtv Max', 'gotv-max', 5700.00, 5500.00, 5200.00),
(2, 'GOtv Supa', 'gotv-supa', 9600.00, 9300.00, 9000.00);

-- Sample Cable Plans (StarTimes)
INSERT INTO `cable_plans` (`provider_id`, `plan_name`, `plan_code`, `price_user`, `price_reseller`, `cost_price`) VALUES
(3, 'StarTimes Nova', 'startimes-nova', 1200.00, 1100.00, 1000.00),
(3, 'StarTimes Basic', 'startimes-basic', 2100.00, 2000.00, 1900.00),
(3, 'StarTimes Smart', 'startimes-smart', 3200.00, 3100.00, 2900.00),
(3, 'StarTimes Classic', 'startimes-classic', 3800.00, 3600.00, 3400.00),
(3, 'StarTimes Super', 'startimes-super', 6200.00, 6000.00, 5700.00);

-- Sample Cable Plans (DStv)
INSERT INTO `cable_plans` (`provider_id`, `plan_name`, `plan_code`, `price_user`, `price_reseller`, `cost_price`) VALUES
(1, 'DStv Padi', 'dstv-padi', 2950.00, 2800.00, 2600.00),
(1, 'DStv Yanga', 'dstv-yanga', 4615.00, 4400.00, 4200.00),
(1, 'DStv Confam', 'dstv-confam', 7900.00, 7600.00, 7300.00),
(1, 'DStv Compact', 'dstv-compact', 14250.00, 13800.00, 13200.00),
(1, 'DStv Compact Plus', 'dstv-compactplus', 25550.00, 25000.00, 24000.00),
(1, 'DStv Premium', 'dstv-premium', 37000.00, 36000.00, 35000.00);

-- Sample Data Plans (MTN)
INSERT INTO `data_plans` (`network_id`, `plan_name`, `plan_code`, `data_amount`, `validity`, `price_user`, `price_reseller`, `price_api`, `cost_price`, `plan_type`) VALUES
(1, 'MTN 500MB SME', 'mtn-500mb-sme', '500MB', '30 Days', 150.00, 140.00, 135.00, 130.00, 'sme'),
(1, 'MTN 1GB SME', 'mtn-1gb-sme', '1GB', '30 Days', 280.00, 265.00, 260.00, 250.00, 'sme'),
(1, 'MTN 2GB SME', 'mtn-2gb-sme', '2GB', '30 Days', 560.00, 530.00, 520.00, 500.00, 'sme'),
(1, 'MTN 3GB SME', 'mtn-3gb-sme', '3GB', '30 Days', 840.00, 800.00, 780.00, 750.00, 'sme'),
(1, 'MTN 5GB SME', 'mtn-5gb-sme', '5GB', '30 Days', 1400.00, 1330.00, 1300.00, 1250.00, 'sme'),
(1, 'MTN 10GB SME', 'mtn-10gb-sme', '10GB', '30 Days', 2800.00, 2660.00, 2600.00, 2500.00, 'sme');

-- Sample Data Plans (Airtel)
INSERT INTO `data_plans` (`network_id`, `plan_name`, `plan_code`, `data_amount`, `validity`, `price_user`, `price_reseller`, `price_api`, `cost_price`, `plan_type`) VALUES
(2, 'Airtel 500MB CG', 'airtel-500mb-cg', '500MB', '30 Days', 150.00, 140.00, 135.00, 130.00, 'corporate'),
(2, 'Airtel 1GB CG', 'airtel-1gb-cg', '1GB', '30 Days', 280.00, 265.00, 260.00, 250.00, 'corporate'),
(2, 'Airtel 2GB CG', 'airtel-2gb-cg', '2GB', '30 Days', 560.00, 530.00, 520.00, 500.00, 'corporate'),
(2, 'Airtel 5GB CG', 'airtel-5gb-cg', '5GB', '30 Days', 1400.00, 1330.00, 1300.00, 1250.00, 'corporate'),
(2, 'Airtel 10GB CG', 'airtel-10gb-cg', '10GB', '30 Days', 2800.00, 2660.00, 2600.00, 2500.00, 'corporate');

-- Sample Data Plans (Glo)
INSERT INTO `data_plans` (`network_id`, `plan_name`, `plan_code`, `data_amount`, `validity`, `price_user`, `price_reseller`, `price_api`, `cost_price`, `plan_type`) VALUES
(3, 'Glo 500MB CG', 'glo-500mb-cg', '500MB', '30 Days', 150.00, 140.00, 135.00, 130.00, 'corporate'),
(3, 'Glo 1GB CG', 'glo-1gb-cg', '1GB', '30 Days', 280.00, 265.00, 260.00, 250.00, 'corporate'),
(3, 'Glo 2GB CG', 'glo-2gb-cg', '2GB', '30 Days', 560.00, 530.00, 520.00, 500.00, 'corporate'),
(3, 'Glo 5GB CG', 'glo-5gb-cg', '5GB', '30 Days', 1400.00, 1330.00, 1300.00, 1250.00, 'corporate'),
(3, 'Glo 10GB CG', 'glo-10gb-cg', '10GB', '30 Days', 2800.00, 2660.00, 2600.00, 2500.00, 'corporate');

-- Sample Data Plans (9Mobile)
INSERT INTO `data_plans` (`network_id`, `plan_name`, `plan_code`, `data_amount`, `validity`, `price_user`, `price_reseller`, `price_api`, `cost_price`, `plan_type`) VALUES
(4, '9Mobile 500MB CG', '9mobile-500mb-cg', '500MB', '30 Days', 150.00, 140.00, 135.00, 130.00, 'corporate'),
(4, '9Mobile 1GB CG', '9mobile-1gb-cg', '1GB', '30 Days', 280.00, 265.00, 260.00, 250.00, 'corporate'),
(4, '9Mobile 2GB CG', '9mobile-2gb-cg', '2GB', '30 Days', 560.00, 530.00, 520.00, 500.00, 'corporate'),
(4, '9Mobile 5GB CG', '9mobile-5gb-cg', '5GB', '30 Days', 1400.00, 1330.00, 1300.00, 1250.00, 'corporate');

-- Default Site Settings
INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_type`, `category`) VALUES
('site_name', '2SureSub', 'text', 'general'),
('site_tagline', 'Your Trusted VTU Platform', 'text', 'general'),
('site_email', 'support@2suresub.com', 'text', 'general'),
('site_phone', '+234 XXX XXX XXXX', 'text', 'general'),
('site_address', 'Lagos, Nigeria', 'text', 'general'),
('primary_color', '#3B82F6', 'text', 'appearance'),
('secondary_color', '#6366F1', 'text', 'appearance'),
('maintenance_mode', '0', 'boolean', 'general'),
('allow_registration', '1', 'boolean', 'general'),
('min_wallet_fund', '100', 'number', 'wallet'),
('max_wallet_fund', '1000000', 'number', 'wallet'),
('referral_bonus', '100', 'number', 'referral'),
('whatsapp_number', '+234 XXX XXX XXXX', 'text', 'contact'),
('facebook_url', '', 'text', 'social'),
('twitter_url', '', 'text', 'social'),
('instagram_url', '', 'text', 'social');

-- Default Superadmin User (Password: Admin@123)
INSERT INTO `users` (`username`, `email`, `phone`, `password`, `first_name`, `last_name`, `role`, `status`, `email_verified`, `referral_code`) VALUES
('superadmin', 'admin@2suresub.com', '08012345678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super', 'Admin', 'superadmin', 'active', 1, 'SUPER001');

-- Create wallet for superadmin
INSERT INTO `wallets` (`user_id`, `balance`) VALUES (1, 1000000.00);

-- Default API Settings
INSERT INTO `api_settings` (`provider_name`, `provider_type`, `base_url`, `is_active`) VALUES
('Inlomax', 'vtu', 'https://inlomax.com.ng/api', 1),
('VTpass', 'vtu', 'https://vtpass.com/api', 0),
('Gsubz', 'vtu', 'https://www.gsubz.com/api', 0),
('PayStack', 'payment', 'https://api.paystack.co', 0),
('Flutterwave', 'payment', 'https://api.flutterwave.com/v3', 0),
('Termii', 'sms', 'https://api.ng.termii.com/api', 0);
