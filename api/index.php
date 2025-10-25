<?php
/**
 * API Entry Point - Routes all requests to appropriate handlers
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Restaurant.php';
require_once __DIR__ . '/../classes/Subscription.php';
require_once __DIR__ . '/../classes/Menu.php';
require_once __DIR__ . '/../classes/Review.php';
require_once __DIR__ . '/../classes/Statistics.php';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/../classes/Settings.php';

// Parse the request
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove base path if needed
$basePath = '/e-menu';
if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

// Remove /api prefix
if (strpos($requestUri, '/api') === 0) {
    $requestUri = substr($requestUri, 4);
}

$requestUri = trim($requestUri, '/');
$segments = explode('/', $requestUri);

// Get request data
$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    $data = $_POST;
}

$queryParams = $_GET;

// Route the request
try {
    $controller = $segments[0] ?? '';
    $action = $segments[1] ?? '';
    $param = $segments[2] ?? null;
    
    // Make variables available to required files
    $requestUri = $requestUri;
    $requestMethod = $requestMethod;
    $segments = $segments;
    $data = $data;
    $queryParams = $queryParams;
    
    switch ($controller) {
        case 'health':
            require 'endpoints/health.php';
            break;
            
        case 'auth':
            require 'endpoints/auth.php';
            break;
            
        case 'restaurants':
            require 'endpoints/restaurants.php';
            break;
            
        case 'restaurant':
            require 'endpoints/restaurant.php';
            break;
            
        case 'admin':
            require 'endpoints/admin.php';
            break;
            
        case 'menu':
            require 'endpoints/menu.php';
            break;
            
        case 'review':
            require 'endpoints/review.php';
            break;
            
        case 'analytics':
            require 'endpoints/analytics.php';
            break;
            
        default:
            sendError('Endpoint not found', 404);
    }
} catch (Exception $e) {
    sendError($e->getMessage(), 500);
}

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function sendError($message, $statusCode = 400) {
    sendResponse([
        'ok' => false,
        'message' => $message
    ], $statusCode);
}