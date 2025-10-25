-- E-Menu Database Schema
-- MySQL 8.0+ compatible

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `e_menu` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `e_menu`;

-- Users table (restaurant owners and admins)
CREATE TABLE `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `email` varchar(255) NOT NULL UNIQUE,
    `password` varchar(255) NOT NULL,
    `name` varchar(255) NOT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `role` enum('admin', 'restaurant') NOT NULL DEFAULT 'restaurant',
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `email_verified` tinyint(1) NOT NULL DEFAULT 0,
    `email_verification_token` varchar(255) DEFAULT NULL,
    `password_reset_token` varchar(255) DEFAULT NULL,
    `password_reset_expires` datetime DEFAULT NULL,
    `last_login` datetime DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_email` (`email`),
    KEY `idx_role` (`role`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Subscription plans table
CREATE TABLE `subscription_plans` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `name_ar` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `description_ar` text DEFAULT NULL,
    `price` decimal(10,2) NOT NULL DEFAULT 0.00,
    `currency` varchar(3) NOT NULL DEFAULT 'SYP',
    `billing_cycle` enum('monthly', 'yearly') NOT NULL DEFAULT 'monthly',
    `max_categories` int(11) NOT NULL DEFAULT 10,
    `max_items` int(11) NOT NULL DEFAULT 100,
    `max_images` int(11) NOT NULL DEFAULT 500,
    `color_customization` tinyint(1) NOT NULL DEFAULT 0,
    `analytics` tinyint(1) NOT NULL DEFAULT 0,
    `reviews` tinyint(1) NOT NULL DEFAULT 1,
    `online_ordering` tinyint(1) NOT NULL DEFAULT 0,
    `custom_domain` tinyint(1) NOT NULL DEFAULT 0,
    `priority_support` tinyint(1) NOT NULL DEFAULT 0,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `is_popular` tinyint(1) NOT NULL DEFAULT 0,
    `sort_order` int(11) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_is_active` (`is_active`),
    KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Restaurants table
CREATE TABLE `restaurants` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `subscription_plan_id` int(11) NOT NULL,
    `name` varchar(255) NOT NULL,
    `name_ar` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL UNIQUE,
    `subdomain` varchar(255) NOT NULL UNIQUE,
    `description` text DEFAULT NULL,
    `description_ar` text DEFAULT NULL,
    `logo` varchar(255) DEFAULT NULL,
    `cover_image` varchar(255) DEFAULT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `email` varchar(255) DEFAULT NULL,
    `website` varchar(255) DEFAULT NULL,
    `address` text DEFAULT NULL,
    `address_ar` text DEFAULT NULL,
    `city` varchar(100) DEFAULT NULL,
    `city_ar` varchar(100) DEFAULT NULL,
    `latitude` decimal(10,8) DEFAULT NULL,
    `longitude` decimal(11,8) DEFAULT NULL,
    `cuisine_type` varchar(100) DEFAULT NULL,
    `cuisine_type_ar` varchar(100) DEFAULT NULL,
    `opening_time` time DEFAULT '09:00:00',
    `closing_time` time DEFAULT '23:00:00',
    `working_days` json DEFAULT NULL,
    `is_active` tinyint(1) NOT NULL DEFAULT 0,
    `is_approved` tinyint(1) NOT NULL DEFAULT 0,
    `subscription_start` date DEFAULT NULL,
    `subscription_end` date DEFAULT NULL,
    `subscription_status` enum('active', 'expired', 'suspended', 'cancelled') NOT NULL DEFAULT 'active',
    `theme_color` varchar(7) DEFAULT '#f97316',
    `custom_css` text DEFAULT NULL,
    `meta_title` varchar(255) DEFAULT NULL,
    `meta_description` text DEFAULT NULL,
    `meta_keywords` text DEFAULT NULL,
    `social_facebook` varchar(255) DEFAULT NULL,
    `social_instagram` varchar(255) DEFAULT NULL,
    `social_twitter` varchar(255) DEFAULT NULL,
    `social_youtube` varchar(255) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_slug` (`slug`),
    UNIQUE KEY `unique_subdomain` (`subdomain`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_subscription_plan_id` (`subscription_plan_id`),
    KEY `idx_is_active` (`is_active`),
    KEY `idx_is_approved` (`is_approved`),
    KEY `idx_city` (`city`),
    KEY `idx_cuisine_type` (`cuisine_type`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories table
CREATE TABLE `categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `restaurant_id` int(11) NOT NULL,
    `name` varchar(255) NOT NULL,
    `name_ar` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `description_ar` text DEFAULT NULL,
    `image` varchar(255) DEFAULT NULL,
    `sort_order` int(11) NOT NULL DEFAULT 0,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_restaurant_id` (`restaurant_id`),
    KEY `idx_sort_order` (`sort_order`),
    KEY `idx_is_active` (`is_active`),
    FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menu items table
CREATE TABLE `menu_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `restaurant_id` int(11) NOT NULL,
    `category_id` int(11) NOT NULL,
    `name` varchar(190) NOT NULL,
    `name_ar` varchar(255) NOT NULL,
    `description` varchar(190) DEFAULT NULL,
    `description_ar` text DEFAULT NULL,
    `price` decimal(10,2) NOT NULL,
    `currency` varchar(3) NOT NULL DEFAULT 'SYP',
    `image` varchar(255) DEFAULT NULL,
    `ingredients` text DEFAULT NULL,
    `ingredients_ar` text DEFAULT NULL,
    `allergens` text DEFAULT NULL,
    `allergens_ar` text DEFAULT NULL,
    `nutritional_info` json DEFAULT NULL,
    `is_vegetarian` tinyint(1) NOT NULL DEFAULT 0,
    `is_vegan` tinyint(1) NOT NULL DEFAULT 0,
    `is_gluten_free` tinyint(1) NOT NULL DEFAULT 0,
    `is_halal` tinyint(1) NOT NULL DEFAULT 0,
    `is_spicy` tinyint(1) NOT NULL DEFAULT 0,
    `spice_level` enum('mild', 'medium', 'hot', 'extra_hot') DEFAULT NULL,
    `preparation_time` int(11) DEFAULT NULL COMMENT 'Preparation time in minutes',
    `is_available` tinyint(1) NOT NULL DEFAULT 1,
    `is_featured` tinyint(1) NOT NULL DEFAULT 0,
    `sort_order` int(11) NOT NULL DEFAULT 0,
    `views_count` int(11) NOT NULL DEFAULT 0,
    `orders_count` int(11) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_restaurant_id` (`restaurant_id`),
    KEY `idx_category_id` (`category_id`),
    KEY `idx_is_available` (`is_available`),
    KEY `idx_is_featured` (`is_featured`),
    KEY `idx_sort_order` (`sort_order`),
    KEY `idx_price` (`price`),
    FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reviews table
CREATE TABLE `reviews` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `restaurant_id` int(11) NOT NULL,
    `menu_item_id` int(11) DEFAULT NULL,
    `user_name` varchar(255) NOT NULL,
    `user_email` varchar(255) DEFAULT NULL,
    `user_phone` varchar(20) DEFAULT NULL,
    `rating` tinyint(1) NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
    `title` varchar(255) DEFAULT NULL,
    `title_ar` varchar(255) DEFAULT NULL,
    `comment` text DEFAULT NULL,
    `comment_ar` text DEFAULT NULL,
    `is_verified` tinyint(1) NOT NULL DEFAULT 0,
    `is_approved` tinyint(1) NOT NULL DEFAULT 1,
    `is_featured` tinyint(1) NOT NULL DEFAULT 0,
    `helpful_count` int(11) NOT NULL DEFAULT 0,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_restaurant_id` (`restaurant_id`),
    KEY `idx_menu_item_id` (`menu_item_id`),
    KEY `idx_rating` (`rating`),
    KEY `idx_is_approved` (`is_approved`),
    KEY `idx_is_featured` (`is_featured`),
    KEY `idx_created_at` (`created_at`),
    FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Statistics table
CREATE TABLE `statistics` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `restaurant_id` int(11) NOT NULL,
    `date` date NOT NULL,
    `visitors_count` int(11) NOT NULL DEFAULT 0,
    `page_views` int(11) NOT NULL DEFAULT 0,
    `menu_views` int(11) NOT NULL DEFAULT 0,
    `item_views` int(11) NOT NULL DEFAULT 0,
    `reviews_count` int(11) NOT NULL DEFAULT 0,
    `orders_count` int(11) NOT NULL DEFAULT 0,
    `revenue` decimal(10,2) NOT NULL DEFAULT 0.00,
    `most_viewed_item_id` int(11) DEFAULT NULL,
    `most_ordered_item_id` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_restaurant_date` (`restaurant_id`, `date`),
    KEY `idx_date` (`date`),
    KEY `idx_visitors_count` (`visitors_count`),
    FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`most_viewed_item_id`) REFERENCES `menu_items` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`most_ordered_item_id`) REFERENCES `menu_items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions table
CREATE TABLE `sessions` (
    `id` varchar(128) NOT NULL,
    `user_id` int(11) DEFAULT NULL,
    `restaurant_id` int(11) DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `data` longtext NOT NULL,
    `last_activity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_restaurant_id` (`restaurant_id`),
    KEY `idx_last_activity` (`last_activity`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CSRF tokens table
CREATE TABLE `csrf_tokens` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `token` varchar(255) NOT NULL UNIQUE,
    `user_id` int(11) DEFAULT NULL,
    `expires_at` timestamp NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_token` (`token`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_expires_at` (`expires_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System settings table
CREATE TABLE `system_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `key` varchar(255) NOT NULL UNIQUE,
    `value` text DEFAULT NULL,
    `type` enum('string', 'integer', 'boolean', 'json', 'text') NOT NULL DEFAULT 'string',
    `description` text DEFAULT NULL,
    `is_public` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_key` (`key`),
    KEY `idx_is_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- File uploads table
CREATE TABLE `file_uploads` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `restaurant_id` int(11) DEFAULT NULL,
    `user_id` int(11) DEFAULT NULL,
    `original_name` varchar(255) NOT NULL,
    `stored_name` varchar(255) NOT NULL,
    `file_path` varchar(500) NOT NULL,
    `file_size` int(11) NOT NULL,
    `mime_type` varchar(100) NOT NULL,
    `file_type` enum('image', 'logo', 'document') NOT NULL DEFAULT 'image',
    `is_public` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_restaurant_id` (`restaurant_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_file_type` (`file_type`),
    KEY `idx_created_at` (`created_at`),
    FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity logs table
CREATE TABLE `activity_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT NULL,
    `restaurant_id` int(11) DEFAULT NULL,
    `action` varchar(100) NOT NULL,
    `description` text DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `metadata` json DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_restaurant_id` (`restaurant_id`),
    KEY `idx_action` (`action`),
    KEY `idx_created_at` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default subscription plans
INSERT INTO `subscription_plans` (`id`, `name`, `name_ar`, `description`, `description_ar`, `price`, `max_categories`, `max_items`, `max_images`, `color_customization`, `analytics`, `reviews`, `online_ordering`, `custom_domain`, `is_popular`, `sort_order`) VALUES
(1, 'Free', 'مجاني', 'Basic features for small restaurants', 'مميزات أساسية للمطاعم الصغيرة', 0.00, 3, 20, 50, 0, 0, 1, 0, 0, 0, 1),
(2, 'Basic', 'أساسي', 'Perfect for growing restaurants', 'مثالي للمطاعم النامية', 50000.00, 10, 100, 500, 1, 1, 1, 1, 0, 0, 2),
(3, 'Premium', 'مميز', 'Advanced features for established restaurants', 'مميزات متقدمة للمطاعم الراسخة', 100000.00, 25, 500, 2000, 1, 1, 1, 1, 1, 1, 3),
(4, 'Enterprise', 'مؤسسي', 'Unlimited features for large restaurants', 'مميزات غير محدودة للمطاعم الكبيرة', 200000.00, -1, -1, -1, 1, 1, 1, 1, 1, 0, 4);

-- Insert default admin user
INSERT INTO `users` (`id`, `email`, `password`, `name`, `role`, `is_active`, `email_verified`) VALUES
(1, 'admin@e-menu.sy', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', 1, 1);

-- Insert default system settings
INSERT INTO `system_settings` (`key`, `value`, `type`, `description`, `is_public`) VALUES
('app_name', 'E-Menu', 'string', 'Application name', 1),
('app_version', '1.0.0', 'string', 'Application version', 1),
('default_language', 'ar', 'string', 'Default language', 1),
('maintenance_mode', 'false', 'boolean', 'Maintenance mode status', 1),
('registration_enabled', 'true', 'boolean', 'Allow new registrations', 1),
('email_verification_required', 'true', 'boolean', 'Require email verification', 0),
('max_file_upload_size', '5242880', 'integer', 'Maximum file upload size in bytes', 0),
('allowed_file_types', '["jpg", "jpeg", "png", "gif", "webp"]', 'json', 'Allowed file types for upload', 0),
('default_currency', 'SYP', 'string', 'Default currency', 1),
('currency_symbol', 'ل.س', 'string', 'Currency symbol', 1),
('timezone', 'Asia/Damascus', 'string', 'Default timezone', 1),
('date_format', 'Y-m-d', 'string', 'Date format', 1),
('time_format', 'H:i', 'string', 'Time format', 1);

-- Create indexes for better performance
CREATE INDEX `idx_restaurants_search` ON `restaurants` (`name`, `city`, `cuisine_type`);
CREATE INDEX `idx_menu_items_search` ON `menu_items` (`name`, `description`);
CREATE INDEX `idx_reviews_rating` ON `reviews` (`rating`, `created_at`);
CREATE INDEX `idx_statistics_date_range` ON `statistics` (`date`, `restaurant_id`);

-- Create views for common queries
CREATE VIEW `restaurant_stats` AS
SELECT 
    r.id,
    r.name,
    r.slug,
    COUNT(DISTINCT c.id) as categories_count,
    COUNT(DISTINCT mi.id) as items_count,
    COUNT(DISTINCT rev.id) as reviews_count,
    COALESCE(AVG(rev.rating), 0) as average_rating,
    COUNT(DISTINCT s.date) as active_days
FROM restaurants r
LEFT JOIN categories c ON r.id = c.restaurant_id AND c.is_active = 1
LEFT JOIN menu_items mi ON r.id = mi.restaurant_id AND mi.is_available = 1
LEFT JOIN reviews rev ON r.id = rev.restaurant_id AND rev.is_approved = 1
LEFT JOIN statistics s ON r.id = s.restaurant_id
GROUP BY r.id, r.name, r.slug;

CREATE VIEW `popular_items` AS
SELECT 
    mi.id,
    mi.name,
    mi.name_ar,
    mi.price,
    mi.image,
    r.name as restaurant_name,
    r.slug as restaurant_slug,
    COUNT(DISTINCT rev.id) as reviews_count,
    COALESCE(AVG(rev.rating), 0) as average_rating,
    mi.views_count,
    mi.orders_count
FROM menu_items mi
JOIN restaurants r ON mi.restaurant_id = r.id
LEFT JOIN reviews rev ON mi.id = rev.menu_item_id AND rev.is_approved = 1
WHERE mi.is_available = 1 AND r.is_active = 1 AND r.is_approved = 1
GROUP BY mi.id, mi.name, mi.name_ar, mi.price, mi.image, r.name, r.slug, mi.views_count, mi.orders_count
ORDER BY mi.orders_count DESC, mi.views_count DESC;
