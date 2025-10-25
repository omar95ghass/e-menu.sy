<?php
// Menu endpoints (authenticated)
$menu = new Menu();
$auth = new Auth();
$auth->requireAuth();

switch ($action) {
    case 'add-category':
        // POST /api/menu/add-category
        if ($requestMethod === 'POST') {
            $result = $menu->addCategory($data);
            sendResponse($result);
        } else {
            sendError('Method not allowed', 405);
        }
        break;
        
    case 'add-item':
        // POST /api/menu/add-item
        if ($requestMethod === 'POST') {
            $result = $menu->addItem($data);
            sendResponse($result);
        } else {
            sendError('Method not allowed', 405);
        }
        break;
        
    case 'update-item':
        // PUT /api/menu/update-item
        if ($requestMethod === 'PUT') {
            $result = $menu->updateItem($data['id'], $data);
            sendResponse($result);
        } else {
            sendError('Method not allowed', 405);
        }
        break;
        
    case 'delete-item':
        // DELETE /api/menu/delete-item/{id}
        if ($requestMethod === 'DELETE') {
            $result = $menu->deleteItem($param);
            sendResponse($result);
        } else {
            sendError('Method not allowed', 405);
        }
        break;
        
    case 'upload-image':
        // POST /api/menu/upload-image
        if ($requestMethod === 'POST') {
            $result = $menu->uploadItemImage($data['item_id'], $_FILES['image'] ?? null);
            sendResponse($result);
        } else {
            sendError('Method not allowed', 405);
        }
        break;
        
    default:
        // GET /api/menu/{restaurant_id}
        if ($requestMethod === 'GET' && is_numeric($action)) {
            $result = $menu->getMenu($action);
            sendResponse($result);
        } else {
            sendError('Menu endpoint not found', 404);
        }
}
