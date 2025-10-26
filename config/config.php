<?php
/**
 * E-Menu Configuration File
 * All system constants and configuration settings
 */

if (defined('CONFIG_LOADED')) {
    return;
}

define('CONFIG_LOADED', true);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'e_menu');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'E-Menu');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/e-menu');
define('APP_DOMAIN', 'e-menu.sy');
define('APP_SUBDOMAIN_BASE', 'e-menu.sy');

// Security Configuration
define('JWT_SECRET', 'your-secret-key-change-this-in-production');
define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour
define('SESSION_LIFETIME', 86400); // 24 hours
define('PASSWORD_MIN_LENGTH', 8);

// File Upload Configuration
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_IMAGES_PATH', UPLOAD_PATH . 'images/');
define('UPLOAD_LOGOS_PATH', UPLOAD_PATH . 'logos/');

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'noreply@e-menu.sy');
define('SMTP_FROM_NAME', 'E-Menu System');

// Subscription Plans Configuration
define('FREE_PLAN_ID', 1);
define('BASIC_PLAN_ID', 2);
define('PREMIUM_PLAN_ID', 3);
define('ENTERPRISE_PLAN_ID', 4);

// Default Subscription Limits
define('DEFAULT_FREE_LIMITS', [
    'max_categories' => 3,
    'max_items' => 20,
    'max_images' => 50,
    'color_customization' => false,
    'analytics' => false,
    'reviews' => true,
    'online_ordering' => false,
    'custom_domain' => false
]);

define('DEFAULT_BASIC_LIMITS', [
    'max_categories' => 10,
    'max_items' => 100,
    'max_images' => 500,
    'color_customization' => true,
    'analytics' => true,
    'reviews' => true,
    'online_ordering' => true,
    'custom_domain' => false
]);

define('DEFAULT_PREMIUM_LIMITS', [
    'max_categories' => 25,
    'max_items' => 500,
    'max_images' => 2000,
    'color_customization' => true,
    'analytics' => true,
    'reviews' => true,
    'online_ordering' => true,
    'custom_domain' => true
]);

define('DEFAULT_ENTERPRISE_LIMITS', [
    'max_categories' => -1, // unlimited
    'max_items' => -1, // unlimited
    'max_images' => -1, // unlimited
    'color_customization' => true,
    'analytics' => true,
    'reviews' => true,
    'online_ordering' => true,
    'custom_domain' => true
]);

// Language Configuration
define('DEFAULT_LANGUAGE', 'ar');
define('SUPPORTED_LANGUAGES', ['ar', 'en']);

// API Configuration
define('API_VERSION', 'v1');
define('API_RATE_LIMIT', 1000); // requests per hour
define('API_RATE_LIMIT_WINDOW', 3600); // 1 hour

// Cache Configuration
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600); // 1 hour

// Logging Configuration
define('LOG_ENABLED', true);
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('LOG_PATH', __DIR__ . '/../logs/');

// Subdomain Configuration
define('SUBDOMAIN_WILDCARD', '*.e-menu.sy');
define('SUBDOMAIN_DNS_SERVER', '8.8.8.8');

// Timezone Configuration
date_default_timezone_set('Asia/Damascus');

// Error Reporting (disable in production)
if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

// CORS Configuration
define('CORS_ALLOWED_ORIGINS', [
    'http://localhost',
    'http://localhost:8080',
    'https://e-menu.sy',
    'https://*.e-menu.sy'
]);

// Pagination Configuration
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Image Processing Configuration
define('IMAGE_QUALITY', 85);
define('THUMBNAIL_SIZE', 300);
define('MEDIUM_SIZE', 600);
define('LARGE_SIZE', 1200);

// Notification Configuration
define('NOTIFICATION_EMAIL_ENABLED', true);
define('NOTIFICATION_SMS_ENABLED', false);
define('NOTIFICATION_PUSH_ENABLED', false);

// Backup Configuration
define('BACKUP_ENABLED', true);
define('BACKUP_PATH', __DIR__ . '/../backups/');
define('BACKUP_RETENTION_DAYS', 30);

// Maintenance Mode
define('MAINTENANCE_MODE', false);
define('MAINTENANCE_MESSAGE', 'نظام الصيانة - سنعود قريباً');

// Feature Flags
define('FEATURE_ONLINE_ORDERING', true);
define('FEATURE_PAYMENT_INTEGRATION', false);
define('FEATURE_MULTI_LANGUAGE', true);
define('FEATURE_ANALYTICS', true);
define('FEATURE_REVIEWS', true);
define('FEATURE_SUBDOMAINS', true);

// Development Configuration
define('DEBUG_MODE', true);
define('SHOW_SQL_QUERIES', false);
define('ENABLE_PROFILER', false);

// Third-party Integrations
define('GOOGLE_MAPS_API_KEY', 'your-google-maps-api-key');
define('FACEBOOK_APP_ID', 'your-facebook-app-id');
define('TWITTER_API_KEY', 'your-twitter-api-key');

// Currency Configuration
define('DEFAULT_CURRENCY', 'SYP');
define('APP_CURRENCY_SYMBOL', 'ل.س');
define('CURRENCY_POSITION', 'after'); // before or after

// Business Hours Configuration
define('DEFAULT_OPENING_TIME', '09:00');
define('DEFAULT_CLOSING_TIME', '23:00');
define('DEFAULT_TIMEZONE', 'Asia/Damascus');

// Review Configuration
define('MIN_REVIEW_LENGTH', 10);
define('MAX_REVIEW_LENGTH', 500);
define('REVIEW_MODERATION_ENABLED', true);

// Search Configuration
define('SEARCH_MIN_LENGTH', 2);
define('SEARCH_MAX_RESULTS', 50);
define('SEARCH_FUZZY_MATCH', true);

// Performance Configuration
define('ENABLE_COMPRESSION', true);
define('ENABLE_CACHING', true);
define('CACHE_DRIVER', 'file'); // file, redis, memcached

// Security Headers
define('SECURITY_HEADERS', [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'DENY',
    'X-XSS-Protection' => '1; mode=block',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:;"
]);

// Load environment-specific configuration
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}
