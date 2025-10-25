<?php
/**
 * Subscription Management Class
 * Handles subscription plans, limits, and permissions
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/../config/config.php';

class Subscription {
    private $db;
    private $auth;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->auth = new Auth();
    }

    /**
     * Get all subscription plans
     */
    public function getAllPlans() {
        try {
            $plans = $this->db->fetchAll(
                "SELECT * FROM subscription_plans 
                 WHERE is_active = 1 
                 ORDER BY sort_order ASC, price ASC"
            );

            return [
                'success' => true,
                'data' => array_map([$this, 'formatPlanData'], $plans)
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get subscription plan by ID
     */
    public function getPlanById($planId) {
        try {
            $plan = $this->db->fetch(
                "SELECT * FROM subscription_plans WHERE id = :id AND is_active = 1",
                ['id' => $planId]
            );

            if (!$plan) {
                throw new Exception('خطة الاشتراك غير موجودة');
            }

            return [
                'success' => true,
                'data' => $this->formatPlanData($plan)
            ];

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
            // Check if user is admin
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية لتعيين خطط الاشتراك');
            }

            // Verify plan exists
            $plan = $this->db->fetch(
                "SELECT * FROM subscription_plans WHERE id = :id AND is_active = 1",
                ['id' => $planId]
            );

            if (!$plan) {
                throw new Exception('خطة الاشتراك غير موجودة');
            }

            // Verify restaurant exists
            $restaurant = $this->db->fetch(
                "SELECT * FROM restaurants WHERE id = :id",
                ['id' => $restaurantId]
            );

            if (!$restaurant) {
                throw new Exception('المطعم غير موجود');
            }

            // Calculate subscription dates
            $startDate = date('Y-m-d');
            $endDate = $plan['billing_cycle'] === 'yearly' 
                ? date('Y-m-d', strtotime('+1 year'))
                : date('Y-m-d', strtotime('+1 month'));

            // Update restaurant subscription
            $this->db->update('restaurants', [
                'subscription_plan_id' => $planId,
                'subscription_start' => $startDate,
                'subscription_end' => $endDate,
                'subscription_status' => 'active'
            ], 'id = :id', ['id' => $restaurantId]);

            // Update session if current user's restaurant
            $user = $this->auth->getCurrentUser();
            if ($user && $user['restaurant_id'] == $restaurantId) {
                $_SESSION['subscription_plan'] = [
                    'id' => $plan['id'],
                    'name' => $plan['name'],
                    'max_categories' => $plan['max_categories'],
                    'max_items' => $plan['max_items'],
                    'max_images' => $plan['max_images'],
                    'color_customization' => $plan['color_customization'],
                    'analytics' => $plan['analytics'],
                    'reviews' => $plan['reviews'],
                    'online_ordering' => $plan['online_ordering'],
                    'custom_domain' => $plan['custom_domain']
                ];
            }

            // Log activity
            $this->logActivity($restaurantId, 'plan_assigned', 
                "تم تعيين خطة الاشتراك: {$plan['name']}");

            return [
                'success' => true,
                'message' => 'تم تعيين خطة الاشتراك بنجاح',
                'data' => [
                    'plan' => $this->formatPlanData($plan),
                    'subscription_start' => $startDate,
                    'subscription_end' => $endDate
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
     * Get restaurant's current subscription limits
     */
    public function getRestaurantLimits($restaurantId = null) {
        try {
            $user = $this->auth->getCurrentUser();
            
            if (!$user) {
                throw new Exception('يجب تسجيل الدخول أولاً');
            }

            $restaurantId = $restaurantId ?: $user['restaurant_id'];

            if (!$restaurantId) {
                throw new Exception('المطعم غير موجود');
            }

            // Get restaurant with subscription plan
            $restaurant = $this->db->fetch(
                "SELECT r.*, sp.* FROM restaurants r
                 JOIN subscription_plans sp ON r.subscription_plan_id = sp.id
                 WHERE r.id = :id",
                ['id' => $restaurantId]
            );

            if (!$restaurant) {
                throw new Exception('المطعم غير موجود');
            }

            // Check if user owns this restaurant or is admin
            if (!$this->auth->isAdmin() && $restaurant['user_id'] != $user['id']) {
                throw new Exception('ليس لديك صلاحية للوصول إلى هذه المعلومات');
            }

            // Get current usage
            $usage = $this->getCurrentUsage($restaurantId);

            return [
                'success' => true,
                'data' => [
                    'plan' => $this->formatPlanData($restaurant),
                    'usage' => $usage,
                    'limits' => [
                        'max_categories' => $restaurant['max_categories'],
                        'max_items' => $restaurant['max_items'],
                        'max_images' => $restaurant['max_images']
                    ],
                    'features' => [
                        'color_customization' => (bool)$restaurant['color_customization'],
                        'analytics' => (bool)$restaurant['analytics'],
                        'reviews' => (bool)$restaurant['reviews'],
                        'online_ordering' => (bool)$restaurant['online_ordering'],
                        'custom_domain' => (bool)$restaurant['custom_domain']
                    ],
                    'subscription_status' => $restaurant['subscription_status'],
                    'subscription_end' => $restaurant['subscription_end']
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
     * Check if restaurant can perform action based on subscription limits
     */
    public function checkPermission($action, $restaurantId = null) {
        try {
            $user = $this->auth->getCurrentUser();
            
            if (!$user) {
                return false;
            }

            $restaurantId = $restaurantId ?: $user['restaurant_id'];

            if (!$restaurantId) {
                return false;
            }

            // Get subscription limits
            $limits = $this->getRestaurantLimits($restaurantId);
            
            if (!$limits['success']) {
                return false;
            }

            $plan = $limits['data']['plan'];
            $usage = $limits['data']['usage'];

            // Check specific permissions
            switch ($action) {
                case 'add_category':
                    return $plan['max_categories'] == -1 || $usage['categories_count'] < $plan['max_categories'];

                case 'add_item':
                    return $plan['max_items'] == -1 || $usage['items_count'] < $plan['max_items'];

                case 'upload_image':
                    return $plan['max_images'] == -1 || $usage['images_count'] < $plan['max_images'];

                case 'color_customization':
                    return $plan['features']['color_customization'];

                case 'analytics':
                    return $plan['features']['analytics'];

                case 'reviews':
                    return $plan['features']['reviews'];

                case 'online_ordering':
                    return $plan['features']['online_ordering'];

                case 'custom_domain':
                    return $plan['features']['custom_domain'];

                default:
                    return true;
            }

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get permission error message
     */
    public function getPermissionErrorMessage($action) {
        $messages = [
            'add_category' => 'لقد وصلت إلى الحد الأقصى لعدد الفئات المسموح به في خطتك الحالية',
            'add_item' => 'لقد وصلت إلى الحد الأقصى لعدد الأصناف المسموح به في خطتك الحالية',
            'upload_image' => 'لقد وصلت إلى الحد الأقصى لعدد الصور المسموح به في خطتك الحالية',
            'color_customization' => 'تخصيص الألوان غير متاح في خطتك الحالية',
            'analytics' => 'الإحصائيات غير متاحة في خطتك الحالية',
            'reviews' => 'التقييمات غير متاحة في خطتك الحالية',
            'online_ordering' => 'الطلب عبر الإنترنت غير متاح في خطتك الحالية',
            'custom_domain' => 'النطاق المخصص غير متاح في خطتك الحالية'
        ];

        return $messages[$action] ?? 'ليس لديك صلاحية لهذا الإجراء';
    }

    /**
     * Check subscription expiry and update status
     */
    public function checkExpiredSubscriptions() {
        try {
            // Find expired subscriptions
            $expiredRestaurants = $this->db->fetchAll(
                "SELECT * FROM restaurants 
                 WHERE subscription_status = 'active' 
                 AND subscription_end < CURDATE()"
            );

            foreach ($expiredRestaurants as $restaurant) {
                // Update status to expired
                $this->db->update('restaurants', [
                    'subscription_status' => 'expired',
                    'is_active' => 0
                ], 'id = :id', ['id' => $restaurant['id']]);

                // Log activity
                $this->logActivity($restaurant['id'], 'subscription_expired', 
                    'انتهت صلاحية الاشتراك');

                // TODO: Send notification email to restaurant owner
            }

            return count($expiredRestaurants);

        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Extend subscription
     */
    public function extendSubscription($restaurantId, $months = 1) {
        try {
            // Check if user is admin
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية لتجديد الاشتراكات');
            }

            $restaurant = $this->db->fetch(
                "SELECT * FROM restaurants WHERE id = :id",
                ['id' => $restaurantId]
            );

            if (!$restaurant) {
                throw new Exception('المطعم غير موجود');
            }

            $currentEndDate = $restaurant['subscription_end'];
            $newEndDate = date('Y-m-d', strtotime($currentEndDate . " +{$months} months"));

            $this->db->update('restaurants', [
                'subscription_end' => $newEndDate,
                'subscription_status' => 'active',
                'is_active' => 1
            ], 'id = :id', ['id' => $restaurantId]);

            // Log activity
            $this->logActivity($restaurantId, 'subscription_extended', 
                "تم تجديد الاشتراك لمدة {$months} شهر");

            return [
                'success' => true,
                'message' => 'تم تجديد الاشتراك بنجاح',
                'data' => [
                    'new_end_date' => $newEndDate
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
     * Create new subscription plan (admin only)
     */
    public function createPlan($data) {
        try {
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية لإنشاء خطط الاشتراك');
            }

            // Validate data
            $this->validatePlanData($data);

            $planData = [
                'name' => $data['name'],
                'name_ar' => $data['name_ar'],
                'description' => $data['description'] ?? null,
                'description_ar' => $data['description_ar'] ?? null,
                'price' => $data['price'],
                'currency' => $data['currency'] ?? 'SYP',
                'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
                'max_categories' => $data['max_categories'],
                'max_items' => $data['max_items'],
                'max_images' => $data['max_images'],
                'color_customization' => $data['color_customization'] ?? 0,
                'analytics' => $data['analytics'] ?? 0,
                'reviews' => $data['reviews'] ?? 1,
                'online_ordering' => $data['online_ordering'] ?? 0,
                'custom_domain' => $data['custom_domain'] ?? 0,
                'priority_support' => $data['priority_support'] ?? 0,
                'is_popular' => $data['is_popular'] ?? 0,
                'sort_order' => $data['sort_order'] ?? 0
            ];

            $planId = $this->db->insert('subscription_plans', $planData);

            // Log activity
            $this->logActivity(null, 'plan_created', "تم إنشاء خطة اشتراك جديدة: {$data['name']}");

            return [
                'success' => true,
                'message' => 'تم إنشاء خطة الاشتراك بنجاح',
                'data' => ['plan_id' => $planId]
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update subscription plan (admin only)
     */
    public function updatePlan($planId, $data) {
        try {
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية لتعديل خطط الاشتراك');
            }

            // Check if plan exists
            if (!$this->db->exists('subscription_plans', 'id = :id', ['id' => $planId])) {
                throw new Exception('خطة الاشتراك غير موجودة');
            }

            // Validate data
            $this->validatePlanData($data, true);

            $updateData = [];
            $allowedFields = [
                'name', 'name_ar', 'description', 'description_ar', 'price',
                'currency', 'billing_cycle', 'max_categories', 'max_items',
                'max_images', 'color_customization', 'analytics', 'reviews',
                'online_ordering', 'custom_domain', 'priority_support',
                'is_popular', 'sort_order', 'is_active'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (empty($updateData)) {
                throw new Exception('لا توجد بيانات للتحديث');
            }

            $this->db->update('subscription_plans', $updateData, 'id = :id', ['id' => $planId]);

            // Log activity
            $this->logActivity(null, 'plan_updated', "تم تحديث خطة الاشتراك: {$planId}");

            return [
                'success' => true,
                'message' => 'تم تحديث خطة الاشتراك بنجاح'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete subscription plan (admin only)
     */
    public function deletePlan($planId) {
        try {
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية لحذف خطط الاشتراك');
            }

            // Check if plan exists
            $plan = $this->db->fetch(
                "SELECT * FROM subscription_plans WHERE id = :id",
                ['id' => $planId]
            );

            if (!$plan) {
                throw new Exception('خطة الاشتراك غير موجودة');
            }

            // Check if any restaurants are using this plan
            $restaurantsCount = $this->db->count('restaurants', 'subscription_plan_id = :plan_id', ['plan_id' => $planId]);
            
            if ($restaurantsCount > 0) {
                throw new Exception('لا يمكن حذف هذه الخطة لأنها مستخدمة من قبل ' . $restaurantsCount . ' مطعم');
            }

            $this->db->delete('subscription_plans', 'id = :id', ['id' => $planId]);

            // Log activity
            $this->logActivity(null, 'plan_deleted', "تم حذف خطة الاشتراك: {$plan['name']}");

            return [
                'success' => true,
                'message' => 'تم حذف خطة الاشتراك بنجاح'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get current usage for restaurant
     */
    private function getCurrentUsage($restaurantId) {
        $categoriesCount = $this->db->count('categories', 'restaurant_id = :id AND is_active = 1', ['id' => $restaurantId]);
        $itemsCount = $this->db->count('menu_items', 'restaurant_id = :id AND is_available = 1', ['id' => $restaurantId]);
        $imagesCount = $this->db->count('file_uploads', 'restaurant_id = :id AND file_type IN ("image", "logo")', ['id' => $restaurantId]);

        return [
            'categories_count' => $categoriesCount,
            'items_count' => $itemsCount,
            'images_count' => $imagesCount
        ];
    }

    /**
     * Format plan data for response
     */
    private function formatPlanData($plan) {
        return [
            'id' => $plan['id'],
            'name' => $plan['name'],
            'name_ar' => $plan['name_ar'],
            'description' => $plan['description'],
            'description_ar' => $plan['description_ar'],
            'price' => (float)$plan['price'],
            'currency' => $plan['currency'],
            'billing_cycle' => $plan['billing_cycle'],
            'limits' => [
                'max_categories' => $plan['max_categories'],
                'max_items' => $plan['max_items'],
                'max_images' => $plan['max_images']
            ],
            'features' => [
                'color_customization' => (bool)$plan['color_customization'],
                'analytics' => (bool)$plan['analytics'],
                'reviews' => (bool)$plan['reviews'],
                'online_ordering' => (bool)$plan['online_ordering'],
                'custom_domain' => (bool)$plan['custom_domain'],
                'priority_support' => (bool)$plan['priority_support']
            ],
            'is_popular' => (bool)$plan['is_popular'],
            'sort_order' => $plan['sort_order'],
            'is_active' => (bool)$plan['is_active']
        ];
    }

    /**
     * Validate plan data
     */
    private function validatePlanData($data, $isUpdate = false) {
        $required = ['name', 'name_ar', 'price', 'max_categories', 'max_items', 'max_images'];
        
        foreach ($required as $field) {
            if (!$isUpdate && !isset($data[$field])) {
                throw new Exception("حقل {$field} مطلوب");
            }
        }

        if (isset($data['price']) && !is_numeric($data['price'])) {
            throw new Exception('السعر يجب أن يكون رقماً');
        }

        if (isset($data['max_categories']) && !is_numeric($data['max_categories'])) {
            throw new Exception('الحد الأقصى للفئات يجب أن يكون رقماً');
        }

        if (isset($data['max_items']) && !is_numeric($data['max_items'])) {
            throw new Exception('الحد الأقصى للأصناف يجب أن يكون رقماً');
        }

        if (isset($data['max_images']) && !is_numeric($data['max_images'])) {
            throw new Exception('الحد الأقصى للصور يجب أن يكون رقماً');
        }
    }

    /**
     * Log subscription activity
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
