<?php
if (defined('API_BOOTSTRAPPED')) {
    return;
}

define('API_BOOTSTRAPPED', true);

// Set common headers once per request
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');

    $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
    if ($origin) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
    } else {
        header('Access-Control-Allow-Origin: *');
    }

    header('Vary: Origin');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Requested-With');
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

function sendResponse($payload, $statusCode = 200) {
    if (!is_array($payload)) {
        $payload = [
            'success' => true,
            'ok' => true,
            'data' => $payload,
        ];
    } else {
        $hasSuccess = array_key_exists('success', $payload);
        $hasOk = array_key_exists('ok', $payload);

        if (!$hasSuccess && !$hasOk) {
            $payload = [
                'success' => true,
                'ok' => true,
                'data' => $payload,
            ];
        } else {
            if ($hasSuccess) {
                $payload['success'] = (bool) $payload['success'];
            }
            if ($hasOk) {
                $payload['ok'] = (bool) $payload['ok'];
            }
            if ($hasSuccess && !$hasOk) {
                $payload['ok'] = (bool) $payload['success'];
            }
            if ($hasOk && !$hasSuccess) {
                $payload['success'] = (bool) $payload['ok'];
            }
        }
    }

    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function sendError($message, $statusCode = 400, array $context = []) {
    http_response_code($statusCode);
    echo json_encode(array_merge([
        'success' => false,
        'ok' => false,
        'message' => $message,
    ], $context), JSON_UNESCAPED_UNICODE);
    exit;
}

function getRequestHeaders(): array {
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        return array_change_key_case($headers, CASE_LOWER);
    }

    $headers = [];
    foreach ($_SERVER as $key => $value) {
        if (strpos($key, 'HTTP_') === 0) {
            $normalized = strtolower(str_replace('_', '-', substr($key, 5)));
            $headers[$normalized] = $value;
        }
    }

    return $headers;
}

// $requiresCsrfVerification = in_array($requestMethod, ['POST', 'PUT', 'PATCH', 'DELETE'], true);
// if ($requiresCsrfVerification) {
//     $headers = getRequestHeaders();
//     $csrfToken = $headers['x-csrf-token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

//     if (empty($csrfToken)) {
//         sendError('رمز الحماية غير موجود', 419);
//     }

//     try {
//         $csrfAuth = new Auth();
//         if (!$csrfAuth->verifyCSRFToken($csrfToken)) {
//             sendError('رمز الحماية غير صالح أو منتهي الصلاحية', 419);
//         }
//     } catch (Throwable $exception) {
//         sendError('تعذر التحقق من رمز الحماية', 500, ['details' => $exception->getMessage()]);
//     }
// }
