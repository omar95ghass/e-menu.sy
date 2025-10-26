<?php
/**
 * Authentication and Authorization Class
 * Handles login, logout, registration, and session management
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../config/config.php';

class Auth {
    private $db;
    private $sessionStarted = false;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->startSession();
    }

    /**
     * Start session if not already started
     */
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            $this->sessionStarted = true;
        }
    }

    /**
     * Register new restaurant
     */
    public function register($data) {
        try {
            // Validate input data
            $this->validateRegistrationData($data);

            // Check if email already exists
            if ($this->db->exists('users', 'email = :email', ['email' => $data['email']])) {
                throw new Exception('البريد الإلكتروني مستخدم بالفعل');
            }

            // Generate unique slug and subdomain
            $slug = $this->db->generateSlug($data['restaurant_name'], 'restaurants', 'slug');
            $subdomain = $this->generateSubdomain($data['restaurant_name']);

            // Check if subdomain is available
            if ($this->db->exists('restaurants', 'subdomain = :subdomain', ['subdomain' => $subdomain])) {
                $subdomain = $this->generateUniqueSubdomain($data['restaurant_name']);
            }

            $this->db->beginTransaction();

            try {
                // Create user account
                $userData = [
                    'email' => $data['email'],
                    'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                    'name' => $data['owner_name'],
                    'phone' => $data['phone'] ?? null,
                    'role' => 'restaurant',
                    'is_active' => 1,
                    'email_verified' => 0
                ];

                $userId = $this->db->insert('users', $userData);

                // Create restaurant
                $restaurantData = [
                    'user_id' => $userId,
                    'subscription_plan_id' => FREE_PLAN_ID,
                    'name' => $data['restaurant_name'],
                    'name_ar' => $data['restaurant_name'],
                    'slug' => $slug,
                    'subdomain' => $subdomain,
                    'description' => $data['description'] ?? null,
                    'description_ar' => $data['description'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'],
                    'address' => $data['address'] ?? null,
                    'address_ar' => $data['address'] ?? null,
                    'city' => $data['city'] ?? null,
                    'city_ar' => $data['city'] ?? null,
                    'cuisine_type' => $data['cuisine_type'] ?? null,
                    'cuisine_type_ar' => $data['cuisine_type'] ?? null,
                    'is_active' => 0, // Requires admin approval
                    'is_approved' => 0,
                    'subscription_start' => date('Y-m-d'),
                    'subscription_end' => date('Y-m-d', strtotime('+1 month')),
                    'subscription_status' => 'active'
                ];

                $restaurantId = $this->db->insert('restaurants', $restaurantData);

                // Log activity
                $this->logActivity($userId, $restaurantId, 'restaurant_registered', 'تم تسجيل مطعم جديد');

                $this->db->commit();

                return [
                    'success' => true,
                    'message' => 'تم تسجيل المطعم بنجاح. سيتم مراجعة الطلب من قبل الإدارة.',
                    'data' => [
                        'user_id' => $userId,
                        'restaurant_id' => $restaurantId,
                        'slug' => $slug,
                        'subdomain' => $subdomain
                    ]
                ];

            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Login user
     */
    public function login($email, $password) {
        try {
            // Find user
            $user = $this->db->fetch(
                "SELECT u.*, r.id as restaurant_id, r.name as restaurant_name, r.slug, r.subdomain, 
                        sp.*, r.subscription_status, r.subscription_end
                 FROM users u 
                 LEFT JOIN restaurants r ON u.id = r.user_id 
                 LEFT JOIN subscription_plans sp ON r.subscription_plan_id = sp.id
                 WHERE u.email = :email AND u.is_active = 1",
                ['email' => $email]
            );

            if (!$user) {
                throw new Exception('البريد الإلكتروني أو كلمة المرور غير صحيحة');
            }

            // Verify password
            if (!password_verify($password, $user['password'])) {
                throw new Exception('البريد الإلكتروني أو كلمة المرور غير صحيحة');
            }

            // Check if restaurant is approved (for restaurant users)
            if ($user['role'] === 'restaurant' && $user['restaurant_id'] && !$user['is_approved']) {
                throw new Exception('حساب المطعم في انتظار الموافقة من الإدارة');
            }

            // Check subscription status
            if ($user['role'] === 'restaurant' && $user['restaurant_id']) {
                if ($user['subscription_status'] === 'expired') {
                    throw new Exception('انتهت صلاحية الاشتراك. يرجى تجديد الاشتراك');
                } elseif ($user['subscription_status'] === 'suspended') {
                    throw new Exception('تم تعليق الحساب. يرجى التواصل مع الإدارة');
                }
            }

            // Update last login
            $this->db->update('users', 
                ['last_login' => date('Y-m-d H:i:s')], 
                'id = :id', 
                ['id' => $user['id']]
            );

            // Set session data
            $this->setSessionData($user);

            // Log activity
            $this->logActivity($user['id'], $user['restaurant_id'] ?? null, 'user_login', 'تم تسجيل الدخول');

            return [
                'success' => true,
                'message' => 'تم تسجيل الدخول بنجاح',
                'data' => [
                    'user' => $this->getUserData($user),
                    'restaurant' => $user['restaurant_id'] ? $this->getRestaurantData($user) : null,
                    'subscription' => $user['restaurant_id'] ? $this->getSubscriptionData($user) : null
                ]
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Logout user
     */
    public function logout() {
        if ($this->isLoggedIn()) {
            // Log activity
            $this->logActivity($_SESSION['user_id'], $_SESSION['restaurant_id'] ?? null, 'user_logout', 'تم تسجيل الخروج');
        }

        // Destroy session
        session_destroy();
        
        return [
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح'
        ];
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Get current user data
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['email'],
            'name' => $_SESSION['name'],
            'role' => $_SESSION['role'],
            'restaurant_id' => $_SESSION['restaurant_id'] ?? null,
            'restaurant_name' => $_SESSION['restaurant_name'] ?? null,
            'subscription_plan' => $_SESSION['subscription_plan'] ?? null
        ];
    }

    /**
     * Check if user has specific role
     */
    public function hasRole($role) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        return $_SESSION['role'] === $role;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin() {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is restaurant owner
     */
    public function isRestaurantOwner() {
        return $this->hasRole('restaurant');
    }

    /**
     * Require authentication
     */
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'يجب تسجيل الدخول أولاً'
            ]);
            exit;
        }
    }

    /**
     * Require specific role
     */
    public function requireRole($role) {
        $this->requireAuth();
        
        if (!$this->hasRole($role)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول إلى هذا المورد'
            ]);
            exit;
        }
    }

    /**
     * Generate CSRF token
     */
    public function generateCSRFToken() {
        $token = bin2hex(random_bytes(32));
        
        $this->db->insert('csrf_tokens', [
            'token' => $token,
            'user_id' => $_SESSION['user_id'] ?? null,
            'expires_at' => date('Y-m-d H:i:s', time() + CSRF_TOKEN_LIFETIME)
        ]);

        return $token;
    }

    /**
     * Verify CSRF token
     */
    public function verifyCSRFToken($token) {
        $csrfToken = $this->db->fetch(
            "SELECT * FROM csrf_tokens WHERE token = :token AND expires_at > NOW()",
            ['token' => $token]
        );

        if (!$csrfToken) {
            return false;
        }

        // Delete used token
        $this->db->delete('csrf_tokens', 'token = :token', ['token' => $token]);
        
        return true;
    }

    /**
     * Clean expired CSRF tokens
     */
    public function cleanExpiredTokens() {
        $this->db->delete('csrf_tokens', 'expires_at < NOW()');
    }

    /**
     * Generate subdomain from restaurant name
     */
    private function generateSubdomain($restaurantName) {
        $subdomain = $this->db->slugify($restaurantName);
        return $subdomain . '.' . APP_SUBDOMAIN_BASE;
    }

    /**
     * Generate unique subdomain
     */
    private function generateUniqueSubdomain($restaurantName) {
        $baseSubdomain = $this->db->slugify($restaurantName);
        $counter = 1;
        $subdomain = $baseSubdomain . '-' . $counter . '.' . APP_SUBDOMAIN_BASE;
        
        while ($this->db->exists('restaurants', 'subdomain = :subdomain', ['subdomain' => $subdomain])) {
            $counter++;
            $subdomain = $baseSubdomain . '-' . $counter . '.' . APP_SUBDOMAIN_BASE;
        }
        
        return $subdomain;
    }

    /**
     * Validate registration data
     */
    private function validateRegistrationData($data) {
        $required = ['email', 'password', 'owner_name', 'restaurant_name'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("حقل {$field} مطلوب");
            }
        }

        if (!$this->db->validateEmail($data['email'])) {
            throw new Exception('البريد الإلكتروني غير صحيح');
        }

        if (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
            throw new Exception('كلمة المرور يجب أن تكون على الأقل ' . PASSWORD_MIN_LENGTH . ' أحرف');
        }

        if (isset($data['phone']) && !$this->db->validatePhone($data['phone'])) {
            throw new Exception('رقم الهاتف غير صحيح');
        }
    }

    /**
     * Set session data
     */
    private function setSessionData($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        
        if ($user['restaurant_id']) {
            $_SESSION['restaurant_id'] = $user['restaurant_id'];
            $_SESSION['restaurant_name'] = $user['restaurant_name'];
            $_SESSION['restaurant_slug'] = $user['slug'];
            $_SESSION['restaurant_subdomain'] = $user['subdomain'];
            
            // Store subscription plan data in session
            $_SESSION['subscription_plan'] = [
                'id' => $user['subscription_plan_id'],
                'name' => $user['name'],
                'max_categories' => $user['max_categories'],
                'max_items' => $user['max_items'],
                'max_images' => $user['max_images'],
                'color_customization' => $user['color_customization'],
                'analytics' => $user['analytics'],
                'reviews' => $user['reviews'],
                'online_ordering' => $user['online_ordering'],
                'custom_domain' => $user['custom_domain']
            ];
        }
    }

    /**
     * Get user data for response
     */
    private function getUserData($user) {
        return [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role'],
            'last_login' => $user['last_login']
        ];
    }

    /**
     * Get restaurant data for response
     */
    private function getRestaurantData($user) {
        return [
            'id' => $user['restaurant_id'],
            'name' => $user['restaurant_name'],
            'slug' => $user['slug'],
            'subdomain' => $user['subdomain'],
            'subscription_status' => $user['subscription_status']
        ];
    }

    /**
     * Get subscription data for response
     */
    private function getSubscriptionData($user) {
        return [
            'plan_id' => $user['subscription_plan_id'],
            'plan_name' => $user['name'],
            'max_categories' => $user['max_categories'],
            'max_items' => $user['max_items'],
            'max_images' => $user['max_images'],
            'features' => [
                'color_customization' => (bool)$user['color_customization'],
                'analytics' => (bool)$user['analytics'],
                'reviews' => (bool)$user['reviews'],
                'online_ordering' => (bool)$user['online_ordering'],
                'custom_domain' => (bool)$user['custom_domain']
            ]
        ];
    }

    /**
     * Log user activity
     */
    private function logActivity($userId, $restaurantId, $action, $description) {
        try {
            $this->db->insert('activity_logs', [
                'user_id' => $userId,
                'restaurant_id' => $restaurantId,
                'action' => $action,
                'description' => $description,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            if (LOG_ENABLED) {
                error_log('[Auth] Failed to record activity: ' . $e->getMessage());
            }
        }
    }

    /**
     * Password reset request
     */
    public function requestPasswordReset($email) {
        try {
            $user = $this->db->fetch(
                "SELECT * FROM users WHERE email = :email AND is_active = 1",
                ['email' => $email]
            );

            if (!$user) {
                throw new Exception('البريد الإلكتروني غير موجود');
            }

            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            $this->db->update('users', [
                'password_reset_token' => $token,
                'password_reset_expires' => $expires
            ], 'id = :id', ['id' => $user['id']]);

            // TODO: Send email with reset link
            // $this->sendPasswordResetEmail($user['email'], $token);

            return [
                'success' => true,
                'message' => 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Reset password with token
     */
    public function resetPassword($token, $newPassword) {
        try {
            $user = $this->db->fetch(
                "SELECT * FROM users WHERE password_reset_token = :token 
                 AND password_reset_expires > NOW() AND is_active = 1",
                ['token' => $token]
            );

            if (!$user) {
                throw new Exception('رابط إعادة تعيين كلمة المرور غير صحيح أو منتهي الصلاحية');
            }

            if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                throw new Exception('كلمة المرور يجب أن تكون على الأقل ' . PASSWORD_MIN_LENGTH . ' أحرف');
            }

            $this->db->update('users', [
                'password' => password_hash($newPassword, PASSWORD_DEFAULT),
                'password_reset_token' => null,
                'password_reset_expires' => null
            ], 'id = :id', ['id' => $user['id']]);

            return [
                'success' => true,
                'message' => 'تم تغيير كلمة المرور بنجاح'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
