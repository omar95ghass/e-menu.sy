<?php
if (!defined('API_BOOTSTRAPPED')) {
    require_once __DIR__ . '/bootstrap.php';
}

// Health check endpoint
if ($requestMethod === 'GET') {
    sendResponse([
        'status' => 'ok',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => APP_VERSION
    ]);
} else {
    sendError('Method not allowed', 405);
}
