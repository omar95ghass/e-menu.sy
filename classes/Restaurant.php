<?php
/**
 * Restaurant Management Class
 * Handles restaurant profile, settings, and operations
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/../config/config.php';

class Restaurant {
    private $db;
    private $auth;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->auth = new Auth();
    }

    /**
     * Get restaurant by ID
     */
    public function getById($id) {
        $restaurant = $this->db->fetch(
            "SELECT r.*, u.name as owner_name, u.email as owner_email,
                    sp.name as plan_name, sp.name_ar as plan_name_ar,
                    sp.max_categories, sp.max_items, sp.max_images,
                    sp.color_customization, sp.analytics, sp.reviews,
                    sp.online_ordering, sp.custom_domain
             FROM restaurants r
             JOIN users u ON r.user_id = u.id
             JOIN subscription_plans sp ON r.subscription_plan_id = sp.id
             WHERE r.id = :id",
            ['id' => $id]
        );

        if (!$restaurant) {
            return null;
        }

        return $this->formatRestaurantData($restaurant);
    }

    /**
     * Get restaurant by slug
     */
    public function getBySlug($slug) {
        $restaurant = $this->db->fetch(
            "SELECT r.*, u.name as owner_name, u.email as owner_email,
                    sp.name as plan_name, sp.name_ar as plan_name_ar,
                    sp.max_categories, sp.max_items, sp.max_images,
                    sp.color_customization, sp.analytics, sp.reviews,
                    sp.online_ordering, sp.custom_domain
             FROM restaurants r
             JOIN users u ON r.user_id = u.id
             JOIN subscription_plans sp ON r.subscription_plan_id = sp.id
             WHERE r.slug = :slug AND r.is_active = 1 AND r.is_approved = 1",
            ['slug' => $slug]
        );

        if (!$restaurant) {
            return null;
        }

        return $this->formatRestaurantData($restaurant);
    }

    /**
     * Get restaurant by subdomain
     */
    public function getBySubdomain($subdomain) {
        $restaurant = $this->db->fetch(
            "SELECT r.*, u.name as owner_name, u.email as owner_email,
                    sp.name as plan_name, sp.name_ar as plan_name_ar,
                    sp.max_categories, sp.max_items, sp.max_images,
                    sp.color_customization, sp.analytics, sp.reviews,
                    sp.online_ordering, sp.custom_domain
             FROM restaurants r
             JOIN users u ON r.user_id = u.id
             JOIN subscription_plans sp ON r.subscription_plan_id = sp.id
             WHERE r.subdomain = :subdomain AND r.is_active = 1 AND r.is_approved = 1",
            ['subdomain' => $subdomain]
        );

        if (!$restaurant) {
            return null;
        }

        return $this->formatRestaurantData($restaurant);
    }

    /**
     * Get current user's restaurant
     */
    public function getCurrentRestaurant() {
        $user = $this->auth->getCurrentUser();
        
        if (!$user || !$user['restaurant_id']) {
            return null;
        }

        return $this->getById($user['restaurant_id']);
    }

    /**
     * Update restaurant profile
     */
    public function updateProfile($data) {
        try {
            $user = $this->auth->getCurrentUser();
            
            if (!$user || !$user['restaurant_id']) {
                throw new Exception('غير مصرح لك بتعديل هذا المطعم');
            }

            $restaurantId = $user['restaurant_id'];

            // Validate data
            $this->validateProfileData($data);

            // Prepare update data
            $updateData = [];
            $allowedFields = [
                'name', 'name_ar', 'description', 'description_ar',
                'phone', 'email', 'website', 'address', 'address_ar',
                'city', 'city_ar', 'latitude', 'longitude',
                'cuisine_type', 'cuisine_type_ar', 'opening_time',
                'closing_time', 'working_days', 'theme_color',
                'meta_title', 'meta_description', 'meta_keywords',
                'social_facebook', 'social_instagram', 'social_twitter',
                'social_youtube'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            // Update working_days if provided
            if (isset($data['working_days']) && is_array($data['working_days'])) {
                $updateData['working_days'] = json_encode($data['working_days']);
            }

            if (empty($updateData)) {
                throw new Exception('لا توجد بيانات للتحديث');
            }

            // Update restaurant
            $this->db->update('restaurants', $updateData, 'id = :id', ['id' => $restaurantId]);

            // Log activity
            $this->logActivity($restaurantId, 'profile_updated', 'تم تحديث بيانات المطعم');

            return [
                'success' => true,
                'message' => 'تم تحديث بيانات المطعم بنجاح',
                'data' => $this->getById($restaurantId)
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Upload restaurant logo
     */
    public function uploadLogo($file) {
        try {
            $user = $this->auth->getCurrentUser();
            
            if (!$user || !$user['restaurant_id']) {
                throw new Exception('غير مصرح لك برفع صورة لهذا المطعم');
            }

            $restaurantId = $user['restaurant_id'];

            // Validate file
            $this->validateImageFile($file);

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'logo_' . $restaurantId . '_' . time() . '.' . $extension;
            $filepath = UPLOAD_LOGOS_PATH . $filename;

            // Create directory if not exists
            if (!is_dir(UPLOAD_LOGOS_PATH)) {
                mkdir(UPLOAD_LOGOS_PATH, 0755, true);
            }

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception('فشل في رفع الصورة');
            }

            // Update restaurant logo
            $this->db->update('restaurants', 
                ['logo' => $filename], 
                'id = :id', 
                ['id' => $restaurantId]
            );

            // Log file upload
            $this->db->insert('file_uploads', [
                'restaurant_id' => $restaurantId,
                'user_id' => $user['id'],
                'original_name' => $file['name'],
                'stored_name' => $filename,
                'file_path' => $filepath,
                'file_size' => $file['size'],
                'mime_type' => $file['type'],
                'file_type' => 'logo'
            ]);

            // Log activity
            $this->logActivity($restaurantId, 'logo_uploaded', 'تم رفع شعار المطعم');

            return [
                'success' => true,
                'message' => 'تم رفع الشعار بنجاح',
                'data' => [
                    'logo' => $filename,
                    'logo_url' => '/uploads/logos/' . $filename
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
     * Upload restaurant cover image
     */
    public function uploadCoverImage($file) {
        try {
            $user = $this->auth->getCurrentUser();
            
            if (!$user || !$user['restaurant_id']) {
                throw new Exception('غير مصرح لك برفع صورة لهذا المطعم');
            }

            $restaurantId = $user['restaurant_id'];

            // Validate file
            $this->validateImageFile($file);

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'cover_' . $restaurantId . '_' . time() . '.' . $extension;
            $filepath = UPLOAD_IMAGES_PATH . $filename;

            // Create directory if not exists
            if (!is_dir(UPLOAD_IMAGES_PATH)) {
                mkdir(UPLOAD_IMAGES_PATH, 0755, true);
            }

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception('فشل في رفع الصورة');
            }

            // Update restaurant cover image
            $this->db->update('restaurants', 
                ['cover_image' => $filename], 
                'id = :id', 
                ['id' => $restaurantId]
            );

            // Log file upload
            $this->db->insert('file_uploads', [
                'restaurant_id' => $restaurantId,
                'user_id' => $user['id'],
                'original_name' => $file['name'],
                'stored_name' => $filename,
                'file_path' => $filepath,
                'file_size' => $file['size'],
                'mime_type' => $file['type'],
                'file_type' => 'image'
            ]);

            // Log activity
            $this->logActivity($restaurantId, 'cover_uploaded', 'تم رفع صورة الغلاف');

            return [
                'success' => true,
                'message' => 'تم رفع صورة الغلاف بنجاح',
                'data' => [
                    'cover_image' => $filename,
                    'cover_url' => '/uploads/images/' . $filename
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
     * Get restaurant statistics
     */
    public function getStatistics($restaurantId = null, $dateRange = null) {
        try {
            $user = $this->auth->getCurrentUser();
            
            if (!$user || !$user['restaurant_id']) {
                throw new Exception('غير مصرح لك بالوصول إلى هذه الإحصائيات');
            }

            $restaurantId = $restaurantId ?: $user['restaurant_id'];

            // Verify ownership
            if (!$this->db->exists('restaurants', 'id = :id AND user_id = :user_id', 
                ['id' => $restaurantId, 'user_id' => $user['id']])) {
                throw new Exception('غير مصرح لك بالوصول إلى هذه الإحصائيات');
            }

            $whereClause = 'restaurant_id = :restaurant_id';
            $params = ['restaurant_id' => $restaurantId];

            if ($dateRange) {
                if (isset($dateRange['start'])) {
                    $whereClause .= ' AND date >= :start_date';
                    $params['start_date'] = $dateRange['start'];
                }
                if (isset($dateRange['end'])) {
                    $whereClause .= ' AND date <= :end_date';
                    $params['end_date'] = $dateRange['end'];
                }
            }

            // Get statistics
            $stats = $this->db->fetchAll(
                "SELECT * FROM statistics WHERE {$whereClause} ORDER BY date DESC",
                $params
            );

            // Calculate totals
            $totals = [
                'total_visitors' => 0,
                'total_page_views' => 0,
                'total_menu_views' => 0,
                'total_item_views' => 0,
                'total_reviews' => 0,
                'total_orders' => 0,
                'total_revenue' => 0
            ];

            foreach ($stats as $stat) {
                $totals['total_visitors'] += $stat['visitors_count'];
                $totals['total_page_views'] += $stat['page_views'];
                $totals['total_menu_views'] += $stat['menu_views'];
                $totals['total_item_views'] += $stat['item_views'];
                $totals['total_reviews'] += $stat['reviews_count'];
                $totals['total_orders'] += $stat['orders_count'];
                $totals['total_revenue'] += $stat['revenue'];
            }

            // Get most popular items
            $popularItems = $this->db->fetchAll(
                "SELECT mi.id, mi.name, mi.name_ar, mi.price, mi.image,
                        mi.views_count, mi.orders_count,
                        COUNT(rev.id) as reviews_count,
                        COALESCE(AVG(rev.rating), 0) as average_rating
                 FROM menu_items mi
                 LEFT JOIN reviews rev ON mi.id = rev.menu_item_id AND rev.is_approved = 1
                 WHERE mi.restaurant_id = :restaurant_id AND mi.is_available = 1
                 GROUP BY mi.id
                 ORDER BY mi.orders_count DESC, mi.views_count DESC
                 LIMIT 10",
                ['restaurant_id' => $restaurantId]
            );

            return [
                'success' => true,
                'data' => [
                    'statistics' => $stats,
                    'totals' => $totals,
                    'popular_items' => $popularItems
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
     * Get all restaurants (for admin or public listing)
     */
    public function getAll($filters = [], $page = 1, $limit = 20) {
        try {
            $whereClause = 'r.is_active = 1 AND r.is_approved = 1';
            $params = [];

            // Apply filters
            if (isset($filters['city']) && !empty($filters['city'])) {
                $whereClause .= ' AND r.city = :city';
                $params['city'] = $filters['city'];
            }

            if (isset($filters['cuisine_type']) && !empty($filters['cuisine_type'])) {
                $whereClause .= ' AND r.cuisine_type = :cuisine_type';
                $params['cuisine_type'] = $filters['cuisine_type'];
            }

            if (isset($filters['search']) && !empty($filters['search'])) {
                $whereClause .= ' AND (r.name LIKE :search OR r.name_ar LIKE :search OR r.description LIKE :search)';
                $params['search'] = '%' . $filters['search'] . '%';
            }

            $sql = "SELECT r.*, 
                           COUNT(DISTINCT c.id) as categories_count,
                           COUNT(DISTINCT mi.id) as items_count,
                           COUNT(DISTINCT rev.id) as reviews_count,
                           COALESCE(AVG(rev.rating), 0) as average_rating
                    FROM restaurants r
                    LEFT JOIN categories c ON r.id = c.restaurant_id AND c.is_active = 1
                    LEFT JOIN menu_items mi ON r.id = mi.restaurant_id AND mi.is_available = 1
                    LEFT JOIN reviews rev ON r.id = rev.restaurant_id AND rev.is_approved = 1
                    WHERE {$whereClause}
                    GROUP BY r.id
                    ORDER BY r.created_at DESC";

            return $this->db->paginate($sql, $params, $page, $limit);

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Format restaurant data for response
     */
    private function formatRestaurantData($restaurant) {
        $data = [
            'id' => $restaurant['id'],
            'name' => $restaurant['name'],
            'name_ar' => $restaurant['name_ar'],
            'slug' => $restaurant['slug'],
            'subdomain' => $restaurant['subdomain'],
            'description' => $restaurant['description'],
            'description_ar' => $restaurant['description_ar'],
            'logo' => $restaurant['logo'],
            'cover_image' => $restaurant['cover_image'],
            'phone' => $restaurant['phone'],
            'email' => $restaurant['email'],
            'website' => $restaurant['website'],
            'address' => $restaurant['address'],
            'address_ar' => $restaurant['address_ar'],
            'city' => $restaurant['city'],
            'city_ar' => $restaurant['city_ar'],
            'latitude' => $restaurant['latitude'],
            'longitude' => $restaurant['longitude'],
            'cuisine_type' => $restaurant['cuisine_type'],
            'cuisine_type_ar' => $restaurant['cuisine_type_ar'],
            'opening_time' => $restaurant['opening_time'],
            'closing_time' => $restaurant['closing_time'],
            'working_days' => json_decode($restaurant['working_days'], true),
            'theme_color' => $restaurant['theme_color'],
            'meta_title' => $restaurant['meta_title'],
            'meta_description' => $restaurant['meta_description'],
            'social_facebook' => $restaurant['social_facebook'],
            'social_instagram' => $restaurant['social_instagram'],
            'social_twitter' => $restaurant['social_twitter'],
            'social_youtube' => $restaurant['social_youtube'],
            'created_at' => $restaurant['created_at'],
            'updated_at' => $restaurant['updated_at']
        ];

        // Add URLs for images
        if ($restaurant['logo']) {
            $data['logo_url'] = '/uploads/logos/' . $restaurant['logo'];
        }
        if ($restaurant['cover_image']) {
            $data['cover_url'] = '/uploads/images/' . $restaurant['cover_image'];
        }

        // Add subscription info if available
        if (isset($restaurant['plan_name'])) {
            $data['subscription'] = [
                'plan_id' => $restaurant['subscription_plan_id'],
                'plan_name' => $restaurant['plan_name'],
                'plan_name_ar' => $restaurant['plan_name_ar'],
                'status' => $restaurant['subscription_status'],
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
                ]
            ];
        }

        return $data;
    }

    /**
     * Validate profile data
     */
    private function validateProfileData($data) {
        if (isset($data['email']) && !$this->db->validateEmail($data['email'])) {
            throw new Exception('البريد الإلكتروني غير صحيح');
        }

        if (isset($data['phone']) && !$this->db->validatePhone($data['phone'])) {
            throw new Exception('رقم الهاتف غير صحيح');
        }

        if (isset($data['latitude']) && !is_numeric($data['latitude'])) {
            throw new Exception('خط العرض غير صحيح');
        }

        if (isset($data['longitude']) && !is_numeric($data['longitude'])) {
            throw new Exception('خط الطول غير صحيح');
        }
    }

    /**
     * Validate image file
     */
    private function validateImageFile($file) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('لم يتم رفع الملف بشكل صحيح');
        }

        if ($file['size'] > UPLOAD_MAX_SIZE) {
            throw new Exception('حجم الملف كبير جداً. الحد الأقصى ' . (UPLOAD_MAX_SIZE / 1024 / 1024) . ' ميجابايت');
        }

        $allowedTypes = UPLOAD_ALLOWED_TYPES;
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedTypes)) {
            throw new Exception('نوع الملف غير مدعوم. الأنواع المدعومة: ' . implode(', ', $allowedTypes));
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp'
        ];

        if (!in_array($mimeType, $allowedMimes)) {
            throw new Exception('نوع الملف غير صحيح');
        }
    }

    /**
     * Log restaurant activity
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
