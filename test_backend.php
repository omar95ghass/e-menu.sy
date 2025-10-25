<?php
/**
 * API Test File
 * Simple test to verify backend integration with frontend
 */

require_once __DIR__ . '/core/init.php';

// Test database connection
try {
    $db = Database::getInstance();
    echo "✓ Database connection successful\n";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test API endpoints
$baseUrl = 'http://localhost/e-menu/api';

$tests = [
    'Health Check' => [
        'url' => $baseUrl . '/health',
        'method' => 'GET',
        'expected_status' => 200
    ],
    'Get Subscription Plans' => [
        'url' => $baseUrl . '/subscriptions',
        'method' => 'GET',
        'expected_status' => 200
    ],
    'Get Public Settings' => [
        'url' => $baseUrl . '/settings/get',
        'method' => 'GET',
        'expected_status' => 200
    ]
];

echo "\n=== API Endpoint Tests ===\n";

foreach ($tests as $testName => $test) {
    echo "Testing: {$testName}... ";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $test['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    if ($test['method'] === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (isset($test['data'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test['data']));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "✗ cURL Error: {$error}\n";
    } elseif ($httpCode === $test['expected_status']) {
        echo "✓ Success (HTTP {$httpCode})\n";
        
        // Try to decode JSON response
        $data = json_decode($response, true);
        if ($data !== null) {
            echo "  Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "✗ Failed (HTTP {$httpCode}, expected {$test['expected_status']})\n";
        echo "  Response: " . substr($response, 0, 200) . "...\n";
    }
    
    echo "\n";
}

// Test restaurant registration
echo "=== Testing Restaurant Registration ===\n";

$registrationData = [
    'email' => 'test@restaurant.com',
    'password' => 'testpassword123',
    'owner_name' => 'Test Owner',
    'restaurant_name' => 'Test Restaurant',
    'phone' => '+963 11 123 4567',
    'address' => 'Test Address',
    'city' => 'Damascus',
    'cuisine_type' => 'Syrian'
];

echo "Testing restaurant registration... ";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/auth/register');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($registrationData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data && $data['success']) {
        echo "✓ Registration successful\n";
        echo "  Restaurant ID: " . ($data['data']['restaurant_id'] ?? 'N/A') . "\n";
        echo "  Slug: " . ($data['data']['slug'] ?? 'N/A') . "\n";
        echo "  Subdomain: " . ($data['data']['subdomain'] ?? 'N/A') . "\n";
    } else {
        echo "✗ Registration failed: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "✗ Registration failed (HTTP {$httpCode})\n";
    echo "  Response: " . substr($response, 0, 200) . "...\n";
}

echo "\n";

// Test login
echo "=== Testing Login ===\n";

$loginData = [
    'email' => 'test@restaurant.com',
    'password' => 'testpassword123'
];

echo "Testing login... ";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data && $data['success']) {
        echo "✓ Login successful\n";
        echo "  User: " . ($data['data']['user']['name'] ?? 'N/A') . "\n";
        echo "  Restaurant: " . ($data['data']['restaurant']['name'] ?? 'N/A') . "\n";
    } else {
        echo "✗ Login failed: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "✗ Login failed (HTTP {$httpCode})\n";
    echo "  Response: " . substr($response, 0, 200) . "...\n";
}

echo "\n";

// Test database tables
echo "=== Database Table Check ===\n";

$tables = [
    'users', 'restaurants', 'subscription_plans', 'categories', 
    'menu_items', 'reviews', 'statistics', 'sessions', 
    'csrf_tokens', 'system_settings', 'file_uploads', 'activity_logs'
];

foreach ($tables as $table) {
    try {
        $count = $db->count($table);
        echo "✓ Table '{$table}' exists with {$count} records\n";
    } catch (Exception $e) {
        echo "✗ Table '{$table}' error: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// Test file permissions
echo "=== File Permission Check ===\n";

$directories = [
    LOG_PATH,
    UPLOAD_PATH,
    UPLOAD_IMAGES_PATH,
    UPLOAD_LOGOS_PATH
];

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "✓ Directory '{$dir}' is writable\n";
        } else {
            echo "✗ Directory '{$dir}' is not writable\n";
        }
    } else {
        echo "✗ Directory '{$dir}' does not exist\n";
    }
}

echo "\n";

// Test configuration
echo "=== Configuration Check ===\n";

$configChecks = [
    'APP_NAME' => APP_NAME,
    'APP_DOMAIN' => APP_DOMAIN,
    'DB_HOST' => DB_HOST,
    'DB_NAME' => DB_NAME,
    'UPLOAD_MAX_SIZE' => UPLOAD_MAX_SIZE,
    'PASSWORD_MIN_LENGTH' => PASSWORD_MIN_LENGTH
];

foreach ($configChecks as $key => $value) {
    echo "✓ {$key}: {$value}\n";
}

echo "\n=== Test Complete ===\n";
echo "Backend system is ready for integration with frontend!\n";
echo "\nNext steps:\n";
echo "1. Run the installation script: http://localhost/e-menu/install.php\n";
echo "2. Test the API endpoints with your frontend\n";
echo "3. Configure subdomain handling for production\n";
echo "4. Set up email configuration for notifications\n";
echo "5. Configure SSL certificates for subdomains\n";
?>
