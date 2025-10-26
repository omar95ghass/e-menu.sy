<?php
/**
 * Admin Management Class
 * Handles admin operations and system management
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Restaurant.php';
require_once __DIR__ . '/Subscription.php';
require_once __DIR__ . '/Statistics.php';
require_once __DIR__ . '/../config/config.php';

class Admin {
    private $db;
    private $auth;
    private $restaurant;
    private $subscription;
    private $statistics;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->auth = new Auth();
        $this->restaurant = new Restaurant();
        $this->subscription = new Subscription();
        $this->statistics = new Statistics();
    }

    /**
     * Get all restaurants for admin management
     */
    public function getAllRestaurants($filters = [], $page = 1, $limit = 20) {
        try {
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية للوصول إلى هذه البيانات');
            }

            $whereClause = '1=1';
            $params = [];

            // Apply filters
            if (isset($filters['status']) && !empty($filters['status'])) {
                if ($filters['status'] === 'pending') {
                    $whereClause .= ' AND r.is_approved = 0';
                } elseif ($filters['status'] === 'active') {
                    $whereClause .= ' AND r.is_active = 1 AND r.is_approved = 1';
                } elseif ($filters['status'] === 'inactive') {
                    $whereClause .= ' AND r.is_active = 0';
                } elseif ($filters['status'] === 'expired') {
                    $whereClause .= ' AND r.subscription_status = "expired"';
                }
            }

            if (isset($filters['plan_id']) && !empty($filters['plan_id'])) {
                $whereClause .= ' AND r.subscription_plan_id = :plan_id';
                $params['plan_id'] = $filters['plan_id'];
            }

            if (isset($filters['search']) && !empty($filters['search'])) {
                $whereClause .= ' AND (r.name LIKE :search OR r.name_ar LIKE :search OR u.email LIKE :search)';
                $params['search'] = '%' . $filters['search'] . '%';
            }

            $sql = "SELECT r.*, u.name as owner_name, u.email as owner_email,
                           sp.name as plan_name, sp.name_ar as plan_name_ar,
                           COUNT(DISTINCT c.id) as categories_count,
                           COUNT(DISTINCT mi.id) as items_count,
                           COUNT(DISTINCT rev.id) as reviews_count,
                           COALESCE(AVG(rev.rating), 0) as average_rating
                    FROM restaurants r
                    JOIN users u ON r.user_id = u.id
                    JOIN subscription_plans sp ON r.subscription_plan_id = sp.id
                    LEFT JOIN categories c ON r.id = c.restaurant_id AND c.is_active = 1
                    LEFT JOIN menu_items mi ON r.id = mi.restaurant_id AND mi.is_available = 1
                    LEFT JOIN reviews rev ON r.id = rev.restaurant_id AND rev.is_approved = 1
                    WHERE {$whereClause}
                    GROUP BY r.id
                    ORDER BY r.created_at DESC";

            $result = $this->db->paginate($sql, $params, $page, $limit);

            // Format restaurant data
            $result['data'] = array_map([$this, 'formatRestaurantData'], $result['data']);

            return [
                'success' => true,
                'data' => $result
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Activate restaurant
     */
    public function activateRestaurant($restaurantId) {
        try {
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية لتفعيل المطاعم');
            }

            $restaurant = $this->db->fetch(
                "SELECT * FROM restaurants WHERE id = :id",
                ['id' => $restaurantId]
            );

            if (!$restaurant) {
                throw new Exception('المطعم غير موجود');
            }

            $this->db->update('restaurants', [
                'is_active' => 1,
                'is_approved' => 1,
                'subscription_status' => 'active'
            ], 'id = :id', ['id' => $restaurantId]);

            // Activate subdomain
            $this->activateSubdomain($restaurant['subdomain']);

            // Log activity
            $this->logActivity($restaurantId, 'restaurant_activated', "تم تفعيل المطعم: {$restaurant['name']}");

            return [
                'success' => true,
                'message' => 'تم تفعيل المطعم بنجاح'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Deactivate restaurant
     */
    public function deactivateRestaurant($restaurantId) {
        try {
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية لإلغاء تفعيل المطاعم');
            }

            $restaurant = $this->db->fetch(
                "SELECT * FROM restaurants WHERE id = :id",
                ['id' => $restaurantId]
            );

            if (!$restaurant) {
                throw new Exception('المطعم غير موجود');
            }

            $this->db->update('restaurants', [
                'is_active' => 0,
                'subscription_status' => 'suspended'
            ], 'id = :id', ['id' => $restaurantId]);

            // Deactivate subdomain
            $this->deactivateSubdomain($restaurant['subdomain']);

            // Log activity
            $this->logActivity($restaurantId, 'restaurant_deactivated', "تم إلغاء تفعيل المطعم: {$restaurant['name']}");

            return [
                'success' => true,
                'message' => 'تم إلغاء تفعيل المطعم بنجاح'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete restaurant
     */
    public function deleteRestaurant($restaurantId) {
        try {
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية لحذف المطاعم');
            }

            $restaurant = $this->db->fetch(
                "SELECT * FROM restaurants WHERE id = :id",
                ['id' => $restaurantId]
            );

            if (!$restaurant) {
                throw new Exception('المطعم غير موجود');
            }

            $this->db->beginTransaction();

            try {
                // Delete subdomain
                $this->deleteSubdomain($restaurant['subdomain']);

                // Delete restaurant (cascade will handle related data)
                $this->db->delete('restaurants', 'id = :id', ['id' => $restaurantId]);

                // Delete user account
                $this->db->delete('users', 'id = :id', ['id' => $restaurant['user_id']]);

                $this->db->commit();

                // Log activity
                $this->logActivity(null, 'restaurant_deleted', "تم حذف المطعم: {$restaurant['name']}");

                return [
                    'success' => true,
                    'message' => 'تم حذف المطعم بنجاح'
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
     * Assign subscription plan to restaurant
     */
    public function assignPlan($restaurantId, $planId) {
        try {
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية لتعيين خطط الاشتراك');
            }

            return $this->subscription->assignPlan($restaurantId, $planId);

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Create new subscription plan
     */
    public function createPlan($data) {
        try {
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية لإنشاء خطط الاشتراك');
            }

            return $this->subscription->createPlan($data);

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update subscription plan
     */
    public function updatePlan($planId, $data) {
        try {
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية لتعديل خطط الاشتراك');
            }

            return $this->subscription->updatePlan($planId, $data);

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete subscription plan
     */
    public function deletePlan($planId) {
        try {
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية لحذف خطط الاشتراك');
            }

            return $this->subscription->deletePlan($planId);

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get available subscription plans
     */
    public function getSubscriptionPlans() {
        try {
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية للوصول إلى خطط الاشتراك');
            }

            return $this->subscription->getAllPlans();

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get system statistics
     */
    public function getSystemStats() {
        try {
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية للوصول إلى إحصائيات النظام');
            }

            return $this->statistics->getSystemAnalytics();

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get all users
     */
    public function getAllUsers($filters = [], $page = 1, $limit = 20) {
        try {
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية للوصول إلى بيانات المستخدمين');
            }

            $whereClause = '1=1';
            $params = [];

            // Apply filters
            if (isset($filters['role']) && !empty($filters['role'])) {
                $whereClause .= ' AND u.role = :role';
                $params['role'] = $filters['role'];
            }

            if (isset($filters['is_active']) && $filters['is_active'] !== '') {
                $whereClause .= ' AND u.is_active = :is_active';
                $params['is_active'] = $filters['is_active'];
            }

            if (isset($filters['search']) && !empty($filters['search'])) {
                $whereClause .= ' AND (u.name LIKE :search OR u.email LIKE :search)';
                $params['search'] = '%' . $filters['search'] . '%';
            }

            $sql = "SELECT u.*, r.name as restaurant_name, r.slug as restaurant_slug,
                           r.subscription_status, sp.name as plan_name
                    FROM users u
                    LEFT JOIN restaurants r ON u.id = r.user_id
                    LEFT JOIN subscription_plans sp ON r.subscription_plan_id = sp.id
                    WHERE {$whereClause}
                    ORDER BY u.created_at DESC";

            $result = $this->db->paginate($sql, $params, $page, $limit);

            // Format user data
            $result['data'] = array_map([$this, 'formatUserData'], $result['data']);

            return [
                'success' => true,
                'data' => $result
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update user status
     */
    public function updateUserStatus($userId, $isActive) {
        try {
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية لتعديل حالة المستخدمين');
            }

            $user = $this->db->fetch(
                "SELECT * FROM users WHERE id = :id",
                ['id' => $userId]
            );

            if (!$user) {
                throw new Exception('المستخدم غير موجود');
            }

            $this->db->update('users', 
                ['is_active' => $isActive ? 1 : 0], 
                'id = :id', 
                ['id' => $userId]
            );

            // If deactivating restaurant user, also deactivate restaurant
            if (!$isActive && $user['role'] === 'restaurant') {
                $restaurant = $this->db->fetch(
                    "SELECT * FROM restaurants WHERE user_id = :user_id",
                    ['user_id' => $userId]
                );

                if ($restaurant) {
                    $this->db->update('restaurants', [
                        'is_active' => 0,
                        'subscription_status' => 'suspended'
                    ], 'id = :id', ['id' => $restaurant['id']]);
                }
            }

            // Log activity
            $this->logActivity(null, 'user_status_updated', 
                "تم تحديث حالة المستخدم: {$user['name']} إلى " . ($isActive ? 'نشط' : 'غير نشط'));

            return [
                'success' => true,
                'message' => 'تم تحديث حالة المستخدم بنجاح'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get activity logs
     */
    public function getActivityLogs($filters = [], $page = 1, $limit = 50) {
        try {
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية للوصول إلى سجل الأنشطة');
            }

            $whereClause = '1=1';
            $params = [];

            // Apply filters
            if (isset($filters['action']) && !empty($filters['action'])) {
                $whereClause .= ' AND al.action = :action';
                $params['action'] = $filters['action'];
            }

            if (isset($filters['user_id']) && !empty($filters['user_id'])) {
                $whereClause .= ' AND al.user_id = :user_id';
                $params['user_id'] = $filters['user_id'];
            }

            if (isset($filters['restaurant_id']) && !empty($filters['restaurant_id'])) {
                $whereClause .= ' AND al.restaurant_id = :restaurant_id';
                $params['restaurant_id'] = $filters['restaurant_id'];
            }

            if (isset($filters['date_from']) && !empty($filters['date_from'])) {
                $whereClause .= ' AND DATE(al.created_at) >= :date_from';
                $params['date_from'] = $filters['date_from'];
            }

            if (isset($filters['date_to']) && !empty($filters['date_to'])) {
                $whereClause .= ' AND DATE(al.created_at) <= :date_to';
                $params['date_to'] = $filters['date_to'];
            }

            $sql = "SELECT al.*, u.name as user_name, u.email as user_email,
                           r.name as restaurant_name, r.slug as restaurant_slug
                    FROM activity_logs al
                    LEFT JOIN users u ON al.user_id = u.id
                    LEFT JOIN restaurants r ON al.restaurant_id = r.id
                    WHERE {$whereClause}
                    ORDER BY al.created_at DESC";

            $result = $this->db->paginate($sql, $params, $page, $limit);

            return [
                'success' => true,
                'data' => $result
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Activate subdomain
     */
    private function activateSubdomain($subdomain) {
        try {
            // In a real implementation, this would:
            // 1. Create DNS A record pointing to server IP
            // 2. Configure web server virtual host
            // 3. Update SSL certificate if needed
            
            // For now, we'll just log the action
            $this->logActivity(null, 'subdomain_activated', "تم تفعيل النطاق الفرعي: {$subdomain}");
            
            return true;
        } catch (Exception $e) {
            $this->logActivity(null, 'subdomain_activation_failed', 
                "فشل في تفعيل النطاق الفرعي: {$subdomain} - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deactivate subdomain
     */
    private function deactivateSubdomain($subdomain) {
        try {
            // In a real implementation, this would:
            // 1. Remove DNS A record
            // 2. Remove web server virtual host configuration
            
            // For now, we'll just log the action
            $this->logActivity(null, 'subdomain_deactivated', "تم إلغاء تفعيل النطاق الفرعي: {$subdomain}");
            
            return true;
        } catch (Exception $e) {
            $this->logActivity(null, 'subdomain_deactivation_failed', 
                "فشل في إلغاء تفعيل النطاق الفرعي: {$subdomain} - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete subdomain
     */
    private function deleteSubdomain($subdomain) {
        try {
            // In a real implementation, this would:
            // 1. Remove DNS A record
            // 2. Remove web server virtual host configuration
            // 3. Clean up any SSL certificates
            
            // For now, we'll just log the action
            $this->logActivity(null, 'subdomain_deleted', "تم حذف النطاق الفرعي: {$subdomain}");
            
            return true;
        } catch (Exception $e) {
            $this->logActivity(null, 'subdomain_deletion_failed', 
                "فشل في حذف النطاق الفرعي: {$subdomain} - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Format restaurant data for admin response
     */
    private function formatRestaurantData($restaurant) {
        return [
            'id' => $restaurant['id'],
            'name' => $restaurant['name'],
            'name_ar' => $restaurant['name_ar'],
            'slug' => $restaurant['slug'],
            'subdomain' => $restaurant['subdomain'],
            'owner_name' => $restaurant['owner_name'],
            'owner_email' => $restaurant['owner_email'],
            'city' => $restaurant['city'],
            'cuisine_type' => $restaurant['cuisine_type'],
            'is_active' => (bool)$restaurant['is_active'],
            'is_approved' => (bool)$restaurant['is_approved'],
            'subscription_status' => $restaurant['subscription_status'],
            'subscription_start' => $restaurant['subscription_start'],
            'subscription_end' => $restaurant['subscription_end'],
            'plan_name' => $restaurant['plan_name'],
            'plan_name_ar' => $restaurant['plan_name_ar'],
            'stats' => [
                'categories_count' => (int)$restaurant['categories_count'],
                'items_count' => (int)$restaurant['items_count'],
                'reviews_count' => (int)$restaurant['reviews_count'],
                'average_rating' => round((float)$restaurant['average_rating'], 1)
            ],
            'created_at' => $restaurant['created_at'],
            'updated_at' => $restaurant['updated_at']
        ];
    }

    /**
     * Format user data for admin response
     */
    private function formatUserData($user) {
        return [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'role' => $user['role'],
            'is_active' => (bool)$user['is_active'],
            'email_verified' => (bool)$user['email_verified'],
            'last_login' => $user['last_login'],
            'restaurant' => $user['restaurant_name'] ? [
                'name' => $user['restaurant_name'],
                'slug' => $user['restaurant_slug'],
                'subscription_status' => $user['subscription_status'],
                'plan_name' => $user['plan_name']
            ] : null,
            'created_at' => $user['created_at'],
            'updated_at' => $user['updated_at']
        ];
    }

    /**
     * Log admin activity
     */
    private function logActivity($restaurantId, $action, $description) {
        $user = $this->auth->getCurrentUser();
        
        $this->db->insert('activity_logs', [
            'user_id' => $user['id'] ?? null,
            'restaurant_id' => $restaurantId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
}
