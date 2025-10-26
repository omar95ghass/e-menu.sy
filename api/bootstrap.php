<?php
if (defined('API_BOOTSTRAPPED')) {
    return;
}

define('API_BOOTSTRAPPED', true);

// Set common headers once per request
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
}

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($requestMethod === 'OPTIONS') {
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

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    $data = $_POST ?? [];
}

$queryParams = $_GET ?? [];

$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$scriptPath = parse_url($_SERVER['SCRIPT_NAME'] ?? '/', PHP_URL_PATH) ?: '/';
$scriptDir = rtrim(str_replace('\\', '/', dirname($scriptPath)), '/');

$pathInfo = $_SERVER['PATH_INFO'] ?? '';
if ($pathInfo === '') {
    $pathInfo = $requestUri;

    if ($scriptPath !== '/' && strpos($pathInfo, $scriptPath) === 0) {
        $pathInfo = substr($pathInfo, strlen($scriptPath));
    } elseif ($scriptDir && strpos($pathInfo, $scriptDir) === 0) {
        $pathInfo = substr($pathInfo, strlen($scriptDir));
    }
}

$pathInfo = trim($pathInfo, '/');
$rawSegments = $pathInfo === '' ? [] : explode('/', $pathInfo);

$scriptController = basename($scriptPath, '.php');
if ($scriptController === '') {
    $scriptController = 'index';
}

if ($scriptController === 'index') {
    $segments = $rawSegments;
    $controller = $segments[0] ?? '';
} else {
    $segments = array_merge([$scriptController], $rawSegments);
    $controller = $scriptController;
}

$action = $segments[1] ?? '';
$param = $segments[2] ?? null;
$requestUri = implode('/', $segments);

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function sendError($message, $statusCode = 400) {
    sendResponse([
        'ok' => false,
        'message' => $message,
    ], $statusCode);
}
