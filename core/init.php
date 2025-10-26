<?php
/**
 * Core Initialization File
 * Sets up the application environment and autoloading
 */

// Start output buffering
ob_start();

// Set error reporting based on environment
if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/error.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Set timezone
date_default_timezone_set('Asia/Damascus');

// Set default charset
ini_set('default_charset', 'UTF-8');

// Set session configuration (skip when the session is already active to avoid warnings)
$sessionStatus = session_status();
if ($sessionStatus === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', 86400); // 24 hours
    session_start();
} elseif ($sessionStatus === PHP_SESSION_ACTIVE) {
    // Session already active; leave ini settings as-is to prevent PHP warnings.
}

// Load configuration
require_once __DIR__ . '/config/config.php';

// Create necessary directories
$directories = [
    LOG_PATH,
    UPLOAD_PATH,
    UPLOAD_IMAGES_PATH,
    UPLOAD_LOGOS_PATH,
    BACKUP_PATH
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Set up error handler
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    $errorMessage = "PHP Error: {$message} in {$file} on line {$line}";
    
    if (LOG_ENABLED) {
        $logFile = LOG_PATH . 'php_errors_' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$errorMessage}" . PHP_EOL;
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    if (DEBUG_MODE) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
        echo "<strong>PHP Error:</strong> {$message}<br>";
        echo "<strong>File:</strong> {$file}<br>";
        echo "<strong>Line:</strong> {$line}";
        echo "</div>";
    }
    
    return true;
});

// Set up exception handler
set_exception_handler(function($exception) {
    $errorMessage = "Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
    
    if (LOG_ENABLED) {
        $logFile = LOG_PATH . 'exceptions_' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$errorMessage}" . PHP_EOL;
        $logMessage .= "Stack trace:" . PHP_EOL . $exception->getTraceAsString() . PHP_EOL;
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    if (DEBUG_MODE) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
        echo "<strong>Uncaught Exception:</strong> " . $exception->getMessage() . "<br>";
        echo "<strong>File:</strong> " . $exception->getFile() . "<br>";
        echo "<strong>Line:</strong> " . $exception->getLine() . "<br>";
        echo "<strong>Stack Trace:</strong><br><pre>" . $exception->getTraceAsString() . "</pre>";
        echo "</div>";
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Internal server error'
        ]);
    }
});

// Clean up expired sessions
if (rand(1, 100) === 1) { // 1% chance on each request
    $db = Database::getInstance();
    $db->delete('sessions', 'last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR)');
}

// Clean up expired CSRF tokens
if (rand(1, 100) === 1) { // 1% chance on each request
    $auth = new Auth();
    $auth->cleanExpiredTokens();
}

// Check maintenance mode
if (MAINTENANCE_MODE && !$this->isAdminUser()) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'message' => MAINTENANCE_MESSAGE
    ]);
    exit;
}

// Helper function to check if current user is admin
function isAdminUser() {
    if (session_status() === PHP_SESSION_NONE) {
        return false;
    }
    
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Helper function to get client IP
function getClientIP() {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// Helper function to sanitize input
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Helper function to validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Helper function to validate phone
function validatePhone($phone) {
    return preg_match('/^[\+]?[0-9\s\-\(\)]{10,20}$/', $phone);
}

// Helper function to generate random string
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Helper function to format currency
function formatCurrency($amount, $currency = 'SYP') {
    $symbols = [
        'SYP' => 'ل.س',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£'
    ];
    
    $symbol = $symbols[$currency] ?? $currency;
    return number_format($amount, 0) . ' ' . $symbol;
}

// Helper function to format date
function formatDate($date, $format = 'Y-m-d') {
    if (is_string($date)) {
        $date = new DateTime($date);
    }
    return $date->format($format);
}

// Helper function to get time ago
function getTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'منذ لحظات';
    if ($time < 3600) return 'منذ ' . floor($time/60) . ' دقيقة';
    if ($time < 86400) return 'منذ ' . floor($time/3600) . ' ساعة';
    if ($time < 2592000) return 'منذ ' . floor($time/86400) . ' يوم';
    if ($time < 31536000) return 'منذ ' . floor($time/2592000) . ' شهر';
    return 'منذ ' . floor($time/31536000) . ' سنة';
}

// Helper function to slugify text
function slugify($text) {
    // Convert to lowercase
    $text = strtolower($text);
    
    // Replace Arabic characters with transliterated equivalents
    $arabic = ['ا', 'أ', 'إ', 'آ', 'ب', 'ت', 'ث', 'ج', 'ح', 'خ', 'د', 'ذ', 'ر', 'ز', 'س', 'ش', 'ص', 'ض', 'ط', 'ظ', 'ع', 'غ', 'ف', 'ق', 'ك', 'ل', 'م', 'ن', 'ه', 'و', 'ي', 'ة', 'ء'];
    $english = ['a', 'a', 'a', 'a', 'b', 't', 'th', 'j', 'h', 'kh', 'd', 'th', 'r', 'z', 's', 'sh', 's', 'd', 't', 'th', 'a', 'gh', 'f', 'q', 'k', 'l', 'm', 'n', 'h', 'w', 'y', 'h', 'a'];
    $text = str_replace($arabic, $english, $text);
    
    // Remove special characters and replace spaces with hyphens
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = trim($text, '-');
    
    return $text;
}

// Helper function to send JSON response
function sendJSONResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Helper function to send error response
function sendErrorResponse($message, $statusCode = 400) {
    sendJSONResponse([
        'success' => false,
        'message' => $message
    ], $statusCode);
}

// Helper function to send success response
function sendSuccessResponse($data = null, $message = 'Success') {
    $response = [
        'success' => true,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    sendJSONResponse($response);
}

// Auto-load classes
spl_autoload_register(function ($class) {
    $classFile = __DIR__ . '/classes/' . $class . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
    }
});

// Initialize database connection
try {
    $db = Database::getInstance();
} catch (Exception $e) {
    if (DEBUG_MODE) {
        die("Database connection failed: " . $e->getMessage());
    } else {
        die("Database connection failed");
    }
}

// End output buffering and send any buffered content
ob_end_flush();
