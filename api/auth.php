<?php
// Auth endpoints
$auth = new Auth();

switch ($action) {
    case 'login':
        if ($requestMethod === 'POST') {
            $result = $auth->login($data['email'], $data['password']);
            sendResponse($result);
        } else {
            sendError('Method not allowed', 405);
        }
        break;
        
    case 'register':
        if ($requestMethod === 'POST') {
            $result = $auth->register($data);
            sendResponse($result);
        } else {
            sendError('Method not allowed', 405);
        }
        break;
        
    case 'logout':
        if ($requestMethod === 'POST') {
            $result = $auth->logout();
            sendResponse($result);
        } else {
            sendError('Method not allowed', 405);
        }
        break;
        
    case 'csrf-token':
        if ($requestMethod === 'GET') {
            $token = $auth->generateCSRFToken();
            sendResponse([
                'ok' => true,
                'token' => $token
            ]);
        } else {
            sendError('Method not allowed', 405);
        }
        break;
        
    default:
        sendError('Auth endpoint not found', 404);
}
