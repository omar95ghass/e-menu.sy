<?php
/**
 * Review Management Class
 * Handles restaurant and item reviews
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Subscription.php';
require_once __DIR__ . '/../config/config.php';

class Review {
    private $db;
    private $auth;
    private $subscription;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->auth = new Auth();
        $this->subscription = new Subscription();
    }

    /**
     * Add new review
     */
    public function addReview($data) {
        try {
            // Check if reviews are enabled for this restaurant
            $restaurantId = $data['restaurant_id'];
            
            if (!$this->subscription->checkPermission('reviews', $restaurantId)) {
                throw new Exception($this->subscription->getPermissionErrorMessage('reviews'));
            }

            // Validate data
            $this->validateReviewData($data);

            // Verify restaurant exists and is active
            $restaurant = $this->db->fetch(
                "SELECT * FROM restaurants WHERE id = :id AND is_active = 1 AND is_approved = 1",
                ['id' => $restaurantId]
            );

            if (!$restaurant) {
                throw new Exception('المطعم غير موجود أو غير نشط');
            }

            // Verify menu item if provided
            if (isset($data['menu_item_id']) && $data['menu_item_id']) {
                $menuItem = $this->db->fetch(
                    "SELECT * FROM menu_items WHERE id = :id AND restaurant_id = :restaurant_id AND is_available = 1",
                    ['id' => $data['menu_item_id'], 'restaurant_id' => $restaurantId]
                );

                if (!$menuItem) {
                    throw new Exception('الصنف غير موجود');
                }
            }

            $reviewData = [
                'restaurant_id' => $restaurantId,
                'menu_item_id' => $data['menu_item_id'] ?? null,
                'user_name' => $data['user_name'],
                'user_email' => $data['user_email'] ?? null,
                'user_phone' => $data['user_phone'] ?? null,
                'rating' => $data['rating'],
                'title' => $data['title'] ?? null,
                'title_ar' => $data['title_ar'] ?? $data['title'],
                'comment' => $data['comment'] ?? null,
                'comment_ar' => $data['comment_ar'] ?? $data['comment'],
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ];

            $reviewId = $this->db->insert('reviews', $reviewData);

            // Update restaurant average rating
            $this->updateRestaurantRating($restaurantId);

            // Update menu item average rating if applicable
            if (isset($data['menu_item_id']) && $data['menu_item_id']) {
                $this->updateMenuItemRating($data['menu_item_id']);
            }

            // Log activity
            $this->logActivity($restaurantId, 'review_added', "تم إضافة تقييم جديد من {$data['user_name']}");

            return [
                'success' => true,
                'message' => 'تم إضافة التقييم بنجاح. شكراً لك!',
                'data' => ['review_id' => $reviewId]
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get restaurant reviews
     */
    public function getRestaurantReviews($restaurantId, $filters = [], $page = 1, $limit = 20) {
        try {
            $whereClause = 'r.restaurant_id = :restaurant_id AND r.is_approved = 1';
            $params = ['restaurant_id' => $restaurantId];

            // Apply filters
            if (isset($filters['rating']) && !empty($filters['rating'])) {
                $whereClause .= ' AND r.rating = :rating';
                $params['rating'] = $filters['rating'];
            }

            if (isset($filters['menu_item_id']) && !empty($filters['menu_item_id'])) {
                $whereClause .= ' AND r.menu_item_id = :menu_item_id';
                $params['menu_item_id'] = $filters['menu_item_id'];
            }

            if (isset($filters['featured']) && $filters['featured']) {
                $whereClause .= ' AND r.is_featured = 1';
            }

            $sql = "SELECT r.*, mi.name as item_name, mi.name_ar as item_name_ar
                    FROM reviews r
                    LEFT JOIN menu_items mi ON r.menu_item_id = mi.id
                    WHERE {$whereClause}
                    ORDER BY r.is_featured DESC, r.created_at DESC";

            $result = $this->db->paginate($sql, $params, $page, $limit);

            // Format reviews
            $result['data'] = array_map([$this, 'formatReviewData'], $result['data']);

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
     * Get menu item reviews
     */
    public function getMenuItemReviews($menuItemId, $page = 1, $limit = 20) {
        try {
            $sql = "SELECT r.* FROM reviews r
                    WHERE r.menu_item_id = :menu_item_id AND r.is_approved = 1
                    ORDER BY r.is_featured DESC, r.created_at DESC";

            $result = $this->db->paginate($sql, ['menu_item_id' => $menuItemId], $page, $limit);

            // Format reviews
            $result['data'] = array_map([$this, 'formatReviewData'], $result['data']);

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
     * Get review statistics for restaurant
     */
    public function getRestaurantReviewStats($restaurantId) {
        try {
            $stats = $this->db->fetch(
                "SELECT 
                    COUNT(*) as total_reviews,
                    AVG(rating) as average_rating,
                    COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star,
                    COUNT(CASE WHEN rating = 4 THEN 1 END) as four_star,
                    COUNT(CASE WHEN rating = 3 THEN 1 END) as three_star,
                    COUNT(CASE WHEN rating = 2 THEN 1 END) as two_star,
                    COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star
                 FROM reviews 
                 WHERE restaurant_id = :restaurant_id AND is_approved = 1",
                ['restaurant_id' => $restaurantId]
            );

            if (!$stats) {
                return [
                    'success' => true,
                    'data' => [
                        'total_reviews' => 0,
                        'average_rating' => 0,
                        'rating_distribution' => [
                            5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0
                        ]
                    ]
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'total_reviews' => (int)$stats['total_reviews'],
                    'average_rating' => round((float)$stats['average_rating'], 1),
                    'rating_distribution' => [
                        5 => (int)$stats['five_star'],
                        4 => (int)$stats['four_star'],
                        3 => (int)$stats['three_star'],
                        2 => (int)$stats['two_star'],
                        1 => (int)$stats['one_star']
                    ]
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
     * Approve review (admin/restaurant owner)
     */
    public function approveReview($reviewId) {
        try {
            $user = $this->auth->getCurrentUser();
            
            if (!$user) {
                throw new Exception('يجب تسجيل الدخول أولاً');
            }

            // Get review
            $review = $this->db->fetch(
                "SELECT r.*, res.user_id as restaurant_owner_id
                 FROM reviews r
                 JOIN restaurants res ON r.restaurant_id = res.id
                 WHERE r.id = :id",
                ['id' => $reviewId]
            );

            if (!$review) {
                throw new Exception('التقييم غير موجود');
            }

            // Check permissions
            if (!$this->auth->isAdmin() && $review['restaurant_owner_id'] != $user['id']) {
                throw new Exception('ليس لديك صلاحية للموافقة على هذا التقييم');
            }

            $this->db->update('reviews', 
                ['is_approved' => 1], 
                'id = :id', 
                ['id' => $reviewId]
            );

            // Update restaurant rating
            $this->updateRestaurantRating($review['restaurant_id']);

            // Update menu item rating if applicable
            if ($review['menu_item_id']) {
                $this->updateMenuItemRating($review['menu_item_id']);
            }

            // Log activity
            $this->logActivity($review['restaurant_id'], 'review_approved', "تم الموافقة على التقييم: {$reviewId}");

            return [
                'success' => true,
                'message' => 'تم الموافقة على التقييم بنجاح'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Reject review (admin/restaurant owner)
     */
    public function rejectReview($reviewId) {
        try {
            $user = $this->auth->getCurrentUser();
            
            if (!$user) {
                throw new Exception('يجب تسجيل الدخول أولاً');
            }

            // Get review
            $review = $this->db->fetch(
                "SELECT r.*, res.user_id as restaurant_owner_id
                 FROM reviews r
                 JOIN restaurants res ON r.restaurant_id = res.id
                 WHERE r.id = :id",
                ['id' => $reviewId]
            );

            if (!$review) {
                throw new Exception('التقييم غير موجود');
            }

            // Check permissions
            if (!$this->auth->isAdmin() && $review['restaurant_owner_id'] != $user['id']) {
                throw new Exception('ليس لديك صلاحية لرفض هذا التقييم');
            }

            $this->db->update('reviews', 
                ['is_approved' => 0], 
                'id = :id', 
                ['id' => $reviewId]
            );

            // Update restaurant rating
            $this->updateRestaurantRating($review['restaurant_id']);

            // Update menu item rating if applicable
            if ($review['menu_item_id']) {
                $this->updateMenuItemRating($review['menu_item_id']);
            }

            // Log activity
            $this->logActivity($review['restaurant_id'], 'review_rejected', "تم رفض التقييم: {$reviewId}");

            return [
                'success' => true,
                'message' => 'تم رفض التقييم بنجاح'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Feature review (admin/restaurant owner)
     */
    public function featureReview($reviewId) {
        try {
            $user = $this->auth->getCurrentUser();
            
            if (!$user) {
                throw new Exception('يجب تسجيل الدخول أولاً');
            }

            // Get review
            $review = $this->db->fetch(
                "SELECT r.*, res.user_id as restaurant_owner_id
                 FROM reviews r
                 JOIN restaurants res ON r.restaurant_id = res.id
                 WHERE r.id = :id",
                ['id' => $reviewId]
            );

            if (!$review) {
                throw new Exception('التقييم غير موجود');
            }

            // Check permissions
            if (!$this->auth->isAdmin() && $review['restaurant_owner_id'] != $user['id']) {
                throw new Exception('ليس لديك صلاحية لتسليط الضوء على هذا التقييم');
            }

            $this->db->update('reviews', 
                ['is_featured' => 1], 
                'id = :id', 
                ['id' => $reviewId]
            );

            // Log activity
            $this->logActivity($review['restaurant_id'], 'review_featured', "تم تسليط الضوء على التقييم: {$reviewId}");

            return [
                'success' => true,
                'message' => 'تم تسليط الضوء على التقييم بنجاح'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete review (admin only)
     */
    public function deleteReview($reviewId) {
        try {
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية لحذف التقييمات');
            }

            // Get review
            $review = $this->db->fetch(
                "SELECT * FROM reviews WHERE id = :id",
                ['id' => $reviewId]
            );

            if (!$review) {
                throw new Exception('التقييم غير موجود');
            }

            $this->db->delete('reviews', 'id = :id', ['id' => $reviewId]);

            // Update restaurant rating
            $this->updateRestaurantRating($review['restaurant_id']);

            // Update menu item rating if applicable
            if ($review['menu_item_id']) {
                $this->updateMenuItemRating($review['menu_item_id']);
            }

            // Log activity
            $this->logActivity($review['restaurant_id'], 'review_deleted', "تم حذف التقييم: {$reviewId}");

            return [
                'success' => true,
                'message' => 'تم حذف التقييم بنجاح'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update restaurant average rating
     */
    private function updateRestaurantRating($restaurantId) {
        try {
            $avgRating = $this->db->fetch(
                "SELECT AVG(rating) as avg_rating FROM reviews 
                 WHERE restaurant_id = :restaurant_id AND is_approved = 1",
                ['restaurant_id' => $restaurantId]
            );

            // Update restaurant table with average rating
            // Note: This would require adding an average_rating column to restaurants table
            // For now, we'll calculate it dynamically in queries
        } catch (Exception $e) {
            // Silently fail
        }
    }

    /**
     * Update menu item average rating
     */
    private function updateMenuItemRating($menuItemId) {
        try {
            $avgRating = $this->db->fetch(
                "SELECT AVG(rating) as avg_rating FROM reviews 
                 WHERE menu_item_id = :menu_item_id AND is_approved = 1",
                ['menu_item_id' => $menuItemId]
            );

            // Update menu_items table with average rating
            // Note: This would require adding an average_rating column to menu_items table
            // For now, we'll calculate it dynamically in queries
        } catch (Exception $e) {
            // Silently fail
        }
    }

    /**
     * Format review data for response
     */
    private function formatReviewData($review) {
        return [
            'id' => $review['id'],
            'user_name' => $review['user_name'],
            'rating' => (int)$review['rating'],
            'title' => $review['title'],
            'title_ar' => $review['title_ar'],
            'comment' => $review['comment'],
            'comment_ar' => $review['comment_ar'],
            'is_verified' => (bool)$review['is_verified'],
            'is_featured' => (bool)$review['is_featured'],
            'helpful_count' => (int)$review['helpful_count'],
            'created_at' => $review['created_at'],
            'item' => isset($review['item_name']) ? [
                'id' => $review['menu_item_id'],
                'name' => $review['item_name'],
                'name_ar' => $review['item_name_ar']
            ] : null
        ];
    }

    /**
     * Validate review data
     */
    private function validateReviewData($data) {
        if (empty($data['user_name'])) {
            throw new Exception('اسم المستخدم مطلوب');
        }

        if (empty($data['rating']) || !is_numeric($data['rating']) || $data['rating'] < 1 || $data['rating'] > 5) {
            throw new Exception('التقييم يجب أن يكون بين 1 و 5');
        }

        if (isset($data['comment']) && strlen($data['comment']) < MIN_REVIEW_LENGTH) {
            throw new Exception('التعليق يجب أن يكون على الأقل ' . MIN_REVIEW_LENGTH . ' أحرف');
        }

        if (isset($data['comment']) && strlen($data['comment']) > MAX_REVIEW_LENGTH) {
            throw new Exception('التعليق يجب أن يكون أقل من ' . MAX_REVIEW_LENGTH . ' حرف');
        }

        if (isset($data['user_email']) && !$this->db->validateEmail($data['user_email'])) {
            throw new Exception('البريد الإلكتروني غير صحيح');
        }

        if (isset($data['user_phone']) && !$this->db->validatePhone($data['user_phone'])) {
            throw new Exception('رقم الهاتف غير صحيح');
        }
    }

    /**
     * Log review activity
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
