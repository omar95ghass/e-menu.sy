<?php
// Restaurants endpoints
$restaurant = new Restaurant();

switch ($action) {
    case '':
        // GET /api/restaurants
        if ($requestMethod === 'GET') {
            $result = $restaurant->getAll($queryParams);
            sendResponse($result);
        } else {
            sendError('Method not allowed', 405);
        }
        break;
        
    default:
        // GET /api/restaurants/{slug}
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
        
        // GET /api/restaurants/{slug}/menu
        if (isset($segments[2]) && $segments[2] === 'menu') {
            $menu = new Menu();
            $restaurantData = $restaurant->getBySlug($slug);
            
            if ($restaurantData) {
                $result = $menu->getMenu($restaurantData['id']);
                sendResponse($result);
            } else {
                sendError('Restaurant not found', 404);
            }
        }
        
        // POST /api/restaurants/{slug}/review
        if (isset($segments[2]) && $segments[2] === 'review' && $requestMethod === 'POST') {
            $menuItem = new Review();
            $restaurantData = $restaurant->getBySlug($slug);
            
            if ($restaurantData) {
                $data['restaurant_id'] = $restaurantData['id'];
                $result = $menuItem->addReview($data);
                sendResponse($result);
            } else {
                sendError('Restaurant not found', 404);
            }
        }
}
