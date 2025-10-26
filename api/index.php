<?php
/**
 * API Entry Point - Routes all requests to appropriate handlers
 */

require_once __DIR__ . '/bootstrap.php';

try {
    switch ($controller) {
        case 'health':
            require __DIR__ . '/health.php';
            break;

        case 'auth':
            require __DIR__ . '/auth.php';
            break;

        case 'restaurants':
            require __DIR__ . '/restaurants.php';
            break;

        case 'restaurant':
            require __DIR__ . '/restaurant.php';
            break;

        case 'admin':
            require __DIR__ . '/admin.php';
            break;

        case 'menu':
            require __DIR__ . '/menu.php';
            break;

        case 'review':
            require __DIR__ . '/review.php';
            break;

        case 'analytics':
            require __DIR__ . '/analytics.php';
            break;

        default:
            sendError('Endpoint not found', 404);
    }
} catch (Throwable $error) {
    sendError($error->getMessage(), 500);
}

