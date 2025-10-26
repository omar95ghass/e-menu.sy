<?php
if (!defined('API_BOOTSTRAPPED')) {
    require_once __DIR__ . '/bootstrap.php';
}

// Restaurant endpoints (authenticated)
$restaurant = new Restaurant();
$auth = new Auth();
$auth->requireAuth();

switch ($action) {
    case 'dashboard':
        // GET /api/restaurant/dashboard
        if ($requestMethod === 'GET') {
            $result = $restaurant->getCurrentRestaurant();
            sendResponse([
                'ok' => true,
                'data' => $result
            ]);
        } else {
            sendError('Method not allowed', 405);
        }
        break;
        
    case 'update':
        // PUT /api/restaurant/update
        if ($requestMethod === 'PUT') {
            $result = $restaurant->updateProfile($data);
            sendResponse($result);
        } else {
            sendError('Method not allowed', 405);
        }
        break;
        
    case 'stats':
        // GET /api/restaurant/stats
        if ($requestMethod === 'GET') {
            $result = $restaurant->getStatistics();
            sendResponse($result);
        } else {
            sendError('Method not allowed', 405);
        }
        break;
        
    case 'upload-logo':
        // POST /api/restaurant/upload-logo
        if ($requestMethod === 'POST') {
            $result = $restaurant->uploadLogo($_FILES['logo'] ?? null);
            sendResponse($result);
        } else {
            sendError('Method not allowed', 405);
        }
        break;
        
    case 'upload-cover':
        // POST /api/restaurant/upload-cover
        if ($requestMethod === 'POST') {
            $result = $restaurant->uploadCoverImage($_FILES['cover'] ?? null);
            sendResponse($result);
        } else {
            sendError('Method not allowed', 405);
        }
        break;
        
    default:
        // GET /api/restaurant/{slug}
        if ($requestMethod === 'GET') {
            $slug = $action;
            $result = $restaurant->getBySlug($slug);
            
            if ($result) {
                sendResponse([
                    'ok' => true,
                    'data' => $result
                ]);
            } else {
                sendError('Restaurant not found', 404);
            }
        } else {
            sendError('Method not allowed', 405);
        }
}
