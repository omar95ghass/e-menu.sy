<?php
/**
 * Menu Management Class
 * Handles categories and menu items CRUD operations
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Subscription.php';
require_once __DIR__ . '/../config/config.php';

class Menu {
    private $db;
    private $auth;
    private $subscription;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->auth = new Auth();
        $this->subscription = new Subscription();
    }

    /**
     * Get restaurant menu
     */
    public function getMenu($restaurantId) {
        try {
            // Get categories with items
            $categories = $this->db->fetchAll(
                "SELECT c.*, 
                        COUNT(mi.id) as items_count
                 FROM categories c
                 LEFT JOIN menu_items mi ON c.id = mi.category_id AND mi.is_available = 1
                 WHERE c.restaurant_id = :restaurant_id AND c.is_active = 1
                 GROUP BY c.id
                 ORDER BY c.sort_order ASC, c.name ASC",
                ['restaurant_id' => $restaurantId]
            );

            $menu = [];
            foreach ($categories as $category) {
                $items = $this->db->fetchAll(
                    "SELECT mi.*, 
                            COUNT(rev.id) as reviews_count,
                            COALESCE(AVG(rev.rating), 0) as average_rating
                     FROM menu_items mi
                     LEFT JOIN reviews rev ON mi.id = rev.menu_item_id AND rev.is_approved = 1
                     WHERE mi.category_id = :category_id AND mi.is_available = 1
                     GROUP BY mi.id
                     ORDER BY mi.sort_order ASC, mi.name ASC",
                    ['category_id' => $category['id']]
                );

                $category['items'] = array_map([$this, 'formatItemData'], $items);
                $menu[] = $this->formatCategoryData($category);
            }

            return [
                'success' => true,
                'data' => $menu
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Add new category
     */
    public function addCategory($data) {
        try {
            $user = $this->auth->getCurrentUser();
            
            if (!$user || !$user['restaurant_id']) {
                throw new Exception('غير مصرح لك بإضافة فئات');
            }

            $restaurantId = $user['restaurant_id'];

            // Check subscription limits
            if (!$this->subscription->checkPermission('add_category', $restaurantId)) {
                throw new Exception($this->subscription->getPermissionErrorMessage('add_category'));
            }

            // Validate data
            $this->validateCategoryData($data);

            $categoryData = [
                'restaurant_id' => $restaurantId,
                'name' => $data['name'],
                'name_ar' => $data['name_ar'] ?? $data['name'],
                'description' => $data['description'] ?? null,
                'description_ar' => $data['description_ar'] ?? $data['description'],
                'sort_order' => $data['sort_order'] ?? 0
            ];

            $categoryId = $this->db->insert('categories', $categoryData);

            // Log activity
            $this->logActivity($restaurantId, 'category_added', "تم إضافة فئة جديدة: {$data['name']}");

            return [
                'success' => true,
                'message' => 'تم إضافة الفئة بنجاح',
                'data' => ['category_id' => $categoryId]
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update category
     */
    public function updateCategory($categoryId, $data) {
        try {
            $user = $this->auth->getCurrentUser();
            
            if (!$user || !$user['restaurant_id']) {
                throw new Exception('غير مصرح لك بتعديل الفئات');
            }

            $restaurantId = $user['restaurant_id'];

            // Verify category belongs to user's restaurant
            $category = $this->db->fetch(
                "SELECT * FROM categories WHERE id = :id AND restaurant_id = :restaurant_id",
                ['id' => $categoryId, 'restaurant_id' => $restaurantId]
            );

            if (!$category) {
                throw new Exception('الفئة غير موجودة');
            }

            // Validate data
            $this->validateCategoryData($data, true);

            $updateData = [];
            $allowedFields = ['name', 'name_ar', 'description', 'description_ar', 'sort_order', 'is_active'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (empty($updateData)) {
                throw new Exception('لا توجد بيانات للتحديث');
            }

            $this->db->update('categories', $updateData, 'id = :id', ['id' => $categoryId]);

            // Log activity
            $this->logActivity($restaurantId, 'category_updated', "تم تحديث الفئة: {$category['name']}");

            return [
                'success' => true,
                'message' => 'تم تحديث الفئة بنجاح'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete category
     */
    public function deleteCategory($categoryId) {
        try {
            $user = $this->auth->getCurrentUser();
            
            if (!$user || !$user['restaurant_id']) {
                throw new Exception('غير مصرح لك بحذف الفئات');
            }

            $restaurantId = $user['restaurant_id'];

            // Verify category belongs to user's restaurant
            $category = $this->db->fetch(
                "SELECT * FROM categories WHERE id = :id AND restaurant_id = :restaurant_id",
                ['id' => $categoryId, 'restaurant_id' => $restaurantId]
            );

            if (!$category) {
                throw new Exception('الفئة غير موجودة');
            }

            // Check if category has items
            $itemsCount = $this->db->count('menu_items', 'category_id = :id', ['id' => $categoryId]);
            
            if ($itemsCount > 0) {
                throw new Exception('لا يمكن حذف الفئة لأنها تحتوي على أصناف. يرجى حذف الأصناف أولاً');
            }

            $this->db->delete('categories', 'id = :id', ['id' => $categoryId]);

            // Log activity
            $this->logActivity($restaurantId, 'category_deleted', "تم حذف الفئة: {$category['name']}");

            return [
                'success' => true,
                'message' => 'تم حذف الفئة بنجاح'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Add new menu item
     */
    public function addItem($data) {
        try {
            $user = $this->auth->getCurrentUser();
            
            if (!$user || !$user['restaurant_id']) {
                throw new Exception('غير مصرح لك بإضافة أصناف');
            }

            $restaurantId = $user['restaurant_id'];

            // Check subscription limits
            if (!$this->subscription->checkPermission('add_item', $restaurantId)) {
                throw new Exception($this->subscription->getPermissionErrorMessage('add_item'));
            }

            // Validate data
            $this->validateItemData($data);

            // Verify category belongs to user's restaurant
            $category = $this->db->fetch(
                "SELECT * FROM categories WHERE id = :id AND restaurant_id = :restaurant_id",
                ['id' => $data['category_id'], 'restaurant_id' => $restaurantId]
            );

            if (!$category) {
                throw new Exception('الفئة غير موجودة');
            }

            $itemData = [
                'restaurant_id' => $restaurantId,
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'name_ar' => $data['name_ar'] ?? $data['name'],
                'description' => $data['description'] ?? null,
                'description_ar' => $data['description_ar'] ?? $data['description'],
                'price' => $data['price'],
                'currency' => $data['currency'] ?? 'SYP',
                'ingredients' => $data['ingredients'] ?? null,
                'ingredients_ar' => $data['ingredients_ar'] ?? $data['ingredients'],
                'allergens' => $data['allergens'] ?? null,
                'allergens_ar' => $data['allergens_ar'] ?? $data['allergens'],
                'nutritional_info' => isset($data['nutritional_info']) ? json_encode($data['nutritional_info']) : null,
                'is_vegetarian' => $data['is_vegetarian'] ?? 0,
                'is_vegan' => $data['is_vegan'] ?? 0,
                'is_gluten_free' => $data['is_gluten_free'] ?? 0,
                'is_halal' => $data['is_halal'] ?? 0,
                'is_spicy' => $data['is_spicy'] ?? 0,
                'spice_level' => $data['spice_level'] ?? null,
                'preparation_time' => $data['preparation_time'] ?? null,
                'is_featured' => $data['is_featured'] ?? 0,
                'sort_order' => $data['sort_order'] ?? 0
            ];

            $itemId = $this->db->insert('menu_items', $itemData);

            // Log activity
            $this->logActivity($restaurantId, 'item_added', "تم إضافة صنف جديد: {$data['name']}");

            return [
                'success' => true,
                'message' => 'تم إضافة الصنف بنجاح',
                'data' => ['item_id' => $itemId]
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update menu item
     */
    public function updateItem($itemId, $data) {
        try {
            $user = $this->auth->getCurrentUser();
            
            if (!$user || !$user['restaurant_id']) {
                throw new Exception('غير مصرح لك بتعديل الأصناف');
            }

            $restaurantId = $user['restaurant_id'];

            // Verify item belongs to user's restaurant
            $item = $this->db->fetch(
                "SELECT * FROM menu_items WHERE id = :id AND restaurant_id = :restaurant_id",
                ['id' => $itemId, 'restaurant_id' => $restaurantId]
            );

            if (!$item) {
                throw new Exception('الصنف غير موجود');
            }

            // Validate data
            $this->validateItemData($data, true);

            $updateData = [];
            $allowedFields = [
                'name', 'name_ar', 'description', 'description_ar', 'price', 'currency',
                'ingredients', 'ingredients_ar', 'allergens', 'allergens_ar',
                'nutritional_info', 'is_vegetarian', 'is_vegan', 'is_gluten_free',
                'is_halal', 'is_spicy', 'spice_level', 'preparation_time',
                'is_featured', 'sort_order', 'is_available'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    if ($field === 'nutritional_info' && is_array($data[$field])) {
                        $updateData[$field] = json_encode($data[$field]);
                    } else {
                        $updateData[$field] = $data[$field];
                    }
                }
            }

            if (empty($updateData)) {
                throw new Exception('لا توجد بيانات للتحديث');
            }

            $this->db->update('menu_items', $updateData, 'id = :id', ['id' => $itemId]);

            // Log activity
            $this->logActivity($restaurantId, 'item_updated', "تم تحديث الصنف: {$item['name']}");

            return [
                'success' => true,
                'message' => 'تم تحديث الصنف بنجاح'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete menu item
     */
    public function deleteItem($itemId) {
        try {
            $user = $this->auth->getCurrentUser();
            
            if (!$user || !$user['restaurant_id']) {
                throw new Exception('غير مصرح لك بحذف الأصناف');
            }

            $restaurantId = $user['restaurant_id'];

            // Verify item belongs to user's restaurant
            $item = $this->db->fetch(
                "SELECT * FROM menu_items WHERE id = :id AND restaurant_id = :restaurant_id",
                ['id' => $itemId, 'restaurant_id' => $restaurantId]
            );

            if (!$item) {
                throw new Exception('الصنف غير موجود');
            }

            $this->db->delete('menu_items', 'id = :id', ['id' => $itemId]);

            // Log activity
            $this->logActivity($restaurantId, 'item_deleted', "تم حذف الصنف: {$item['name']}");

            return [
                'success' => true,
                'message' => 'تم حذف الصنف بنجاح'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Upload item image
     */
    public function uploadItemImage($itemId, $file) {
        try {
            $user = $this->auth->getCurrentUser();
            
            if (!$user || !$user['restaurant_id']) {
                throw new Exception('غير مصرح لك برفع صور الأصناف');
            }

            $restaurantId = $user['restaurant_id'];

            // Check subscription limits
            if (!$this->subscription->checkPermission('upload_image', $restaurantId)) {
                throw new Exception($this->subscription->getPermissionErrorMessage('upload_image'));
            }

            // Verify item belongs to user's restaurant
            $item = $this->db->fetch(
                "SELECT * FROM menu_items WHERE id = :id AND restaurant_id = :restaurant_id",
                ['id' => $itemId, 'restaurant_id' => $restaurantId]
            );

            if (!$item) {
                throw new Exception('الصنف غير موجود');
            }

            // Validate file
            $this->validateImageFile($file);

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'item_' . $itemId . '_' . time() . '.' . $extension;
            $filepath = UPLOAD_IMAGES_PATH . $filename;

            // Create directory if not exists
            if (!is_dir(UPLOAD_IMAGES_PATH)) {
                mkdir(UPLOAD_IMAGES_PATH, 0755, true);
            }

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception('فشل في رفع الصورة');
            }

            // Update item image
            $this->db->update('menu_items', 
                ['image' => $filename], 
                'id = :id', 
                ['id' => $itemId]
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
            $this->logActivity($restaurantId, 'item_image_uploaded', "تم رفع صورة الصنف: {$item['name']}");

            return [
                'success' => true,
                'message' => 'تم رفع صورة الصنف بنجاح',
                'data' => [
                    'image' => $filename,
                    'image_url' => '/uploads/images/' . $filename
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
     * Get item by ID
     */
    public function getItemById($itemId) {
        try {
            $item = $this->db->fetch(
                "SELECT mi.*, c.name as category_name, c.name_ar as category_name_ar,
                        r.name as restaurant_name, r.slug as restaurant_slug,
                        COUNT(rev.id) as reviews_count,
                        COALESCE(AVG(rev.rating), 0) as average_rating
                 FROM menu_items mi
                 JOIN categories c ON mi.category_id = c.id
                 JOIN restaurants r ON mi.restaurant_id = r.id
                 LEFT JOIN reviews rev ON mi.id = rev.menu_item_id AND rev.is_approved = 1
                 WHERE mi.id = :id AND mi.is_available = 1
                 GROUP BY mi.id",
                ['id' => $itemId]
            );

            if (!$item) {
                return null;
            }

            return $this->formatItemData($item);

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Increment item views
     */
    public function incrementViews($itemId) {
        try {
            $this->db->query(
                "UPDATE menu_items SET views_count = views_count + 1 WHERE id = :id",
                ['id' => $itemId]
            );
        } catch (Exception $e) {
            // Silently fail
        }
    }

    /**
     * Format category data for response
     */
    private function formatCategoryData($category) {
        return [
            'id' => $category['id'],
            'name' => $category['name'],
            'name_ar' => $category['name_ar'],
            'description' => $category['description'],
            'description_ar' => $category['description_ar'],
            'image' => $category['image'],
            'image_url' => $category['image'] ? '/uploads/images/' . $category['image'] : null,
            'sort_order' => $category['sort_order'],
            'is_active' => (bool)$category['is_active'],
            'items_count' => (int)$category['items_count'],
            'items' => $category['items'] ?? []
        ];
    }

    /**
     * Format item data for response
     */
    private function formatItemData($item) {
        $data = [
            'id' => $item['id'],
            'name' => $item['name'],
            'name_ar' => $item['name_ar'],
            'description' => $item['description'],
            'description_ar' => $item['description_ar'],
            'price' => (float)$item['price'],
            'currency' => $item['currency'],
            'image' => $item['image'],
            'image_url' => $item['image'] ? '/uploads/images/' . $item['image'] : null,
            'ingredients' => $item['ingredients'],
            'ingredients_ar' => $item['ingredients_ar'],
            'allergens' => $item['allergens'],
            'allergens_ar' => $item['allergens_ar'],
            'nutritional_info' => $item['nutritional_info'] ? json_decode($item['nutritional_info'], true) : null,
            'dietary_info' => [
                'is_vegetarian' => (bool)$item['is_vegetarian'],
                'is_vegan' => (bool)$item['is_vegan'],
                'is_gluten_free' => (bool)$item['is_gluten_free'],
                'is_halal' => (bool)$item['is_halal'],
                'is_spicy' => (bool)$item['is_spicy'],
                'spice_level' => $item['spice_level']
            ],
            'preparation_time' => $item['preparation_time'],
            'is_featured' => (bool)$item['is_featured'],
            'sort_order' => $item['sort_order'],
            'is_available' => (bool)$item['is_available'],
            'views_count' => (int)$item['views_count'],
            'orders_count' => (int)$item['orders_count'],
            'reviews_count' => (int)($item['reviews_count'] ?? 0),
            'average_rating' => round((float)($item['average_rating'] ?? 0), 1),
            'created_at' => $item['created_at'],
            'updated_at' => $item['updated_at']
        ];

        // Add category info if available
        if (isset($item['category_name'])) {
            $data['category'] = [
                'id' => $item['category_id'],
                'name' => $item['category_name'],
                'name_ar' => $item['category_name_ar']
            ];
        }

        // Add restaurant info if available
        if (isset($item['restaurant_name'])) {
            $data['restaurant'] = [
                'id' => $item['restaurant_id'],
                'name' => $item['restaurant_name'],
                'slug' => $item['restaurant_slug']
            ];
        }

        return $data;
    }

    /**
     * Validate category data
     */
    private function validateCategoryData($data, $isUpdate = false) {
        if (!$isUpdate && empty($data['name'])) {
            throw new Exception('اسم الفئة مطلوب');
        }

        if (isset($data['sort_order']) && !is_numeric($data['sort_order'])) {
            throw new Exception('ترتيب الفئة يجب أن يكون رقماً');
        }
    }

    /**
     * Validate item data
     */
    private function validateItemData($data, $isUpdate = false) {
        $required = ['name', 'price'];
        
        foreach ($required as $field) {
            if (!$isUpdate && !isset($data[$field])) {
                throw new Exception("حقل {$field} مطلوب");
            }
        }

        if (isset($data['price']) && (!is_numeric($data['price']) || $data['price'] < 0)) {
            throw new Exception('السعر يجب أن يكون رقماً موجباً');
        }

        if (isset($data['preparation_time']) && (!is_numeric($data['preparation_time']) || $data['preparation_time'] < 0)) {
            throw new Exception('وقت التحضير يجب أن يكون رقماً موجباً');
        }

        if (isset($data['spice_level']) && !in_array($data['spice_level'], ['mild', 'medium', 'hot', 'extra_hot'])) {
            throw new Exception('مستوى الحرارة غير صحيح');
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
     * Log menu activity
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
