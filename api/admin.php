<?php
// Admin endpoints (admin only)
$admin = new Admin();
$auth = new Auth();
$auth->requireRole('admin');

switch ($action) {
    case 'restaurants':
        // GET /api/admin/restaurants
        if ($requestMethod === 'GET') {
            $result = $admin->getAllRestaurants($queryParams);
            sendResponse($result);
        } else {
            sendError('Method not allowed', 405);
        }
        break;
        
    case 'activate':
        // PUT /api/admin/activate/{id}
        if ($requestMethod === 'PUT') {
            $result = $admin->activateRestaurant($param);
            sendResponse($result);
        } else {
            sendError('Method not allowed', 405);
        }
        break;
        
    case 'remove':
        // DELETE /api/admin/remove/{id}
        if ($requestMethod === 'DELETE') {
            $result = $admin->deleteRestaurant($param);
            sendResponse($result);
        } else {
            sendError('Method not allowed', 405);
        }
        break;
        
    case 'add-plan':
        // POST /api/admin/add-plan
        if ($requestMethod === 'POST') {
            $result = $admin->createPlan($data);
            sendResponse($result);
        } else {
            sendError('Method not allowed', 405);
        }
        break;
        
    case 'edit-plan':
        // PUT /api/admin/edit-plan/{id}
        if ($requestMethod === 'PUT') {
            $result = $admin->updatePlan($param, $data);
            sendResponse($result);
        } else {
            sendError('Method not allowed', 405);
        }
        break;
        
    case 'delete-plan':
        // DELETE /api/admin/delete-plan/{id}
        if ($requestMethod === 'DELETE') {
            $result = $admin->deletePlan($param);
            sendResponse($result);
        } else {
            sendError('Method not allowed', 405);
        }
        break;
        
    default:
        sendError('Admin endpoint not found', 404);
}
