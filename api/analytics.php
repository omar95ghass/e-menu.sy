<?php
// Analytics endpoints
$statistics = new Statistics();
$auth = new Auth();

switch ($action) {
    case 'restaurant':
        // GET /api/analytics/restaurant
        if ($requestMethod === 'GET') {
            $auth->requireAuth();
            $result = $statistics->getRestaurantAnalytics(null, $queryParams);
            sendResponse($result);
        } else {
            sendError('Method not allowed', 405);
        }
        break;
        
    case 'system':
        // GET /api/analytics/system (admin only)
        if ($requestMethod === 'GET') {
            $auth->requireRole('admin');
            $result = $statistics->getSystemAnalytics($queryParams);
            sendResponse($result);
        } else {
            sendError('Method not allowed', 405);
        }
        break;
        
    default:
        sendError('Analytics endpoint not found', 404);
}
