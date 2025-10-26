<?php
if (!defined('API_BOOTSTRAPPED')) {
    require_once __DIR__ . '/bootstrap.php';
}

// Admin endpoints (admin only)
$admin = new Admin();
$auth = new Auth();
$auth->requireRole('admin');

switch ($action) {
    case 'restaurants':
        // GET /api/admin/restaurants
        if ($requestMethod === 'GET') {
            $page = isset($queryParams['page']) ? max(1, (int) $queryParams['page']) : 1;
            $limit = isset($queryParams['limit']) ? max(1, (int) $queryParams['limit']) : 20;
            $filters = $queryParams;
            unset($filters['page'], $filters['limit']);
            $result = $admin->getAllRestaurants($filters, $page, $limit);
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

    case 'deactivate':
        // PUT /api/admin/deactivate/{id}
        if ($requestMethod === 'PUT') {
            $result = $admin->deactivateRestaurant($param);
            sendResponse($result);
        } else {
            sendError('Method not allowed', 405);
        }
        break;

    case 'assign-plan':
        // PUT /api/admin/assign-plan/{restaurantId}
        if ($requestMethod === 'PUT') {
            $planId = $data['plan_id'] ?? null;
            if (!$planId) {
                sendError('plan_id is required', 422);
            }

            $result = $admin->assignPlan($param, $planId);
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

    case 'plans':
        // GET /api/admin/plans
        if ($requestMethod === 'GET') {
            $result = $admin->getSubscriptionPlans();
            sendResponse($result);
        } else {
            sendError('Method not allowed', 405);
        }
        break;

    default:
        sendError('Admin endpoint not found', 404);
}
