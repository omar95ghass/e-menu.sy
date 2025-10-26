<?php
if (!defined('API_BOOTSTRAPPED')) {
    require_once __DIR__ . '/bootstrap.php';
}

// Review endpoints
$review = new Review();

switch ($action) {
    case 'add':
        // POST /api/review/add
        if ($requestMethod === 'POST') {
            $result = $review->addReview($data);
            sendResponse($result);
        } else {
            sendError('Method not allowed', 405);
        }
        break;
        
    default:
        // GET /api/review/{restaurant_id}
        if ($requestMethod === 'GET' && is_numeric($action)) {
            $result = $review->getRestaurantReviews($action, $queryParams);
            sendResponse($result);
        } else {
            sendError('Review endpoint not found', 404);
        }
}
