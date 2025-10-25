<?php
/**
 * Statistics and Analytics Class
 * Handles data collection and analytics
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Subscription.php';
require_once __DIR__ . '/../config/config.php';

class Statistics {
    private $db;
    private $auth;
    private $subscription;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->auth = new Auth();
        $this->subscription = new Subscription();
    }

    /**
     * Record page view
     */
    public function recordPageView($restaurantId, $pageType = 'restaurant') {
        try {
            $today = date('Y-m-d');
            
            // Check if record exists for today
            $existingRecord = $this->db->fetch(
                "SELECT * FROM statistics WHERE restaurant_id = :restaurant_id AND date = :date",
                ['restaurant_id' => $restaurantId, 'date' => $today]
            );

            if ($existingRecord) {
                // Update existing record
                $this->db->query(
                    "UPDATE statistics SET page_views = page_views + 1 WHERE restaurant_id = :restaurant_id AND date = :date",
                    ['restaurant_id' => $restaurantId, 'date' => $today]
                );
            } else {
                // Create new record
                $this->db->insert('statistics', [
                    'restaurant_id' => $restaurantId,
                    'date' => $today,
                    'page_views' => 1,
                    'visitors_count' => 1
                ]);
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Record menu view
     */
    public function recordMenuView($restaurantId) {
        try {
            $today = date('Y-m-d');
            
            // Check if record exists for today
            $existingRecord = $this->db->fetch(
                "SELECT * FROM statistics WHERE restaurant_id = :restaurant_id AND date = :date",
                ['restaurant_id' => $restaurantId, 'date' => $today]
            );

            if ($existingRecord) {
                // Update existing record
                $this->db->query(
                    "UPDATE statistics SET menu_views = menu_views + 1 WHERE restaurant_id = :restaurant_id AND date = :date",
                    ['restaurant_id' => $restaurantId, 'date' => $today]
                );
            } else {
                // Create new record
                $this->db->insert('statistics', [
                    'restaurant_id' => $restaurantId,
                    'date' => $today,
                    'menu_views' => 1,
                    'visitors_count' => 1
                ]);
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Record item view
     */
    public function recordItemView($itemId) {
        try {
            // Get restaurant ID from item
            $item = $this->db->fetch(
                "SELECT restaurant_id FROM menu_items WHERE id = :id",
                ['id' => $itemId]
            );

            if (!$item) {
                return false;
            }

            $restaurantId = $item['restaurant_id'];
            $today = date('Y-m-d');

            // Update item views count
            $this->db->query(
                "UPDATE menu_items SET views_count = views_count + 1 WHERE id = :id",
                ['id' => $itemId]
            );

            // Check if record exists for today
            $existingRecord = $this->db->fetch(
                "SELECT * FROM statistics WHERE restaurant_id = :restaurant_id AND date = :date",
                ['restaurant_id' => $restaurantId, 'date' => $today]
            );

            if ($existingRecord) {
                // Update existing record
                $this->db->query(
                    "UPDATE statistics SET item_views = item_views + 1 WHERE restaurant_id = :restaurant_id AND date = :date",
                    ['restaurant_id' => $restaurantId, 'date' => $today]
                );
            } else {
                // Create new record
                $this->db->insert('statistics', [
                    'restaurant_id' => $restaurantId,
                    'date' => $today,
                    'item_views' => 1,
                    'visitors_count' => 1
                ]);
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Record visitor
     */
    public function recordVisitor($restaurantId, $ipAddress = null) {
        try {
            $today = date('Y-m-d');
            $ipAddress = $ipAddress ?: ($_SERVER['REMOTE_ADDR'] ?? null);

            // Check if record exists for today
            $existingRecord = $this->db->fetch(
                "SELECT * FROM statistics WHERE restaurant_id = :restaurant_id AND date = :date",
                ['restaurant_id' => $restaurantId, 'date' => $today]
            );

            if ($existingRecord) {
                // Update existing record
                $this->db->query(
                    "UPDATE statistics SET visitors_count = visitors_count + 1 WHERE restaurant_id = :restaurant_id AND date = :date",
                    ['restaurant_id' => $restaurantId, 'date' => $today]
                );
            } else {
                // Create new record
                $this->db->insert('statistics', [
                    'restaurant_id' => $restaurantId,
                    'date' => $today,
                    'visitors_count' => 1
                ]);
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get restaurant analytics
     */
    public function getRestaurantAnalytics($restaurantId, $dateRange = null) {
        try {
            $user = $this->auth->getCurrentUser();
            
            if (!$user) {
                throw new Exception('يجب تسجيل الدخول أولاً');
            }

            // Check if user owns this restaurant or is admin
            if (!$this->auth->isAdmin()) {
                $restaurant = $this->db->fetch(
                    "SELECT * FROM restaurants WHERE id = :id AND user_id = :user_id",
                    ['id' => $restaurantId, 'user_id' => $user['id']]
                );

                if (!$restaurant) {
                    throw new Exception('ليس لديك صلاحية للوصول إلى هذه الإحصائيات');
                }
            }

            // Check if analytics are enabled for this restaurant
            if (!$this->subscription->checkPermission('analytics', $restaurantId)) {
                throw new Exception($this->subscription->getPermissionErrorMessage('analytics'));
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
            } else {
                // Default to last 30 days
                $whereClause .= ' AND date >= :start_date';
                $params['start_date'] = date('Y-m-d', strtotime('-30 days'));
            }

            // Get daily statistics
            $dailyStats = $this->db->fetchAll(
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

            foreach ($dailyStats as $stat) {
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

            // Get recent reviews
            $recentReviews = $this->db->fetchAll(
                "SELECT r.*, mi.name as item_name, mi.name_ar as item_name_ar
                 FROM reviews r
                 LEFT JOIN menu_items mi ON r.menu_item_id = mi.id
                 WHERE r.restaurant_id = :restaurant_id AND r.is_approved = 1
                 ORDER BY r.created_at DESC
                 LIMIT 5",
                ['restaurant_id' => $restaurantId]
            );

            // Calculate growth rates
            $growthRates = $this->calculateGrowthRates($restaurantId, $dateRange);

            return [
                'success' => true,
                'data' => [
                    'daily_stats' => $dailyStats,
                    'totals' => $totals,
                    'popular_items' => $popularItems,
                    'recent_reviews' => $recentReviews,
                    'growth_rates' => $growthRates
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
     * Get system-wide analytics (admin only)
     */
    public function getSystemAnalytics($dateRange = null) {
        try {
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية للوصول إلى إحصائيات النظام');
            }

            $whereClause = '1=1';
            $params = [];

            if ($dateRange) {
                if (isset($dateRange['start'])) {
                    $whereClause .= ' AND date >= :start_date';
                    $params['start_date'] = $dateRange['start'];
                }
                if (isset($dateRange['end'])) {
                    $whereClause .= ' AND date <= :end_date';
                    $params['end_date'] = $dateRange['end'];
                }
            } else {
                // Default to last 30 days
                $whereClause .= ' AND date >= :start_date';
                $params['start_date'] = date('Y-m-d', strtotime('-30 days'));
            }

            // Get system totals
            $systemTotals = $this->db->fetch(
                "SELECT 
                    SUM(visitors_count) as total_visitors,
                    SUM(page_views) as total_page_views,
                    SUM(menu_views) as total_menu_views,
                    SUM(item_views) as total_item_views,
                    SUM(reviews_count) as total_reviews,
                    SUM(orders_count) as total_orders,
                    SUM(revenue) as total_revenue
                 FROM statistics WHERE {$whereClause}",
                $params
            );

            // Get restaurant counts
            $restaurantStats = $this->db->fetch(
                "SELECT 
                    COUNT(*) as total_restaurants,
                    COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_restaurants,
                    COUNT(CASE WHEN is_approved = 1 THEN 1 END) as approved_restaurants,
                    COUNT(CASE WHEN subscription_status = 'active' THEN 1 END) as subscribed_restaurants
                 FROM restaurants"
            );

            // Get subscription plan distribution
            $planDistribution = $this->db->fetchAll(
                "SELECT sp.name, sp.name_ar, COUNT(r.id) as restaurant_count
                 FROM subscription_plans sp
                 LEFT JOIN restaurants r ON sp.id = r.subscription_plan_id
                 GROUP BY sp.id, sp.name, sp.name_ar
                 ORDER BY restaurant_count DESC"
            );

            // Get top restaurants by views
            $topRestaurants = $this->db->fetchAll(
                "SELECT r.id, r.name, r.name_ar, r.slug,
                        SUM(s.visitors_count) as total_visitors,
                        SUM(s.page_views) as total_page_views,
                        COUNT(DISTINCT rev.id) as reviews_count,
                        COALESCE(AVG(rev.rating), 0) as average_rating
                 FROM restaurants r
                 LEFT JOIN statistics s ON r.id = s.restaurant_id
                 LEFT JOIN reviews rev ON r.id = rev.restaurant_id AND rev.is_approved = 1
                 WHERE r.is_active = 1 AND r.is_approved = 1
                 GROUP BY r.id
                 ORDER BY total_visitors DESC
                 LIMIT 10"
            );

            return [
                'success' => true,
                'data' => [
                    'system_totals' => $systemTotals,
                    'restaurant_stats' => $restaurantStats,
                    'plan_distribution' => $planDistribution,
                    'top_restaurants' => $topRestaurants
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
     * Calculate growth rates
     */
    private function calculateGrowthRates($restaurantId, $dateRange = null) {
        try {
            $endDate = $dateRange['end'] ?? date('Y-m-d');
            $startDate = $dateRange['start'] ?? date('Y-m-d', strtotime('-30 days'));
            
            $periodLength = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24);
            $halfPeriod = floor($periodLength / 2);
            
            $midDate = date('Y-m-d', strtotime($startDate . " +{$halfPeriod} days"));
            
            // Get first half totals
            $firstHalf = $this->db->fetch(
                "SELECT 
                    SUM(visitors_count) as visitors,
                    SUM(page_views) as page_views,
                    SUM(menu_views) as menu_views,
                    SUM(item_views) as item_views
                 FROM statistics 
                 WHERE restaurant_id = :restaurant_id 
                 AND date >= :start_date 
                 AND date < :mid_date",
                ['restaurant_id' => $restaurantId, 'start_date' => $startDate, 'mid_date' => $midDate]
            );
            
            // Get second half totals
            $secondHalf = $this->db->fetch(
                "SELECT 
                    SUM(visitors_count) as visitors,
                    SUM(page_views) as page_views,
                    SUM(menu_views) as menu_views,
                    SUM(item_views) as item_views
                 FROM statistics 
                 WHERE restaurant_id = :restaurant_id 
                 AND date >= :mid_date 
                 AND date <= :end_date",
                ['restaurant_id' => $restaurantId, 'mid_date' => $midDate, 'end_date' => $endDate]
            );
            
            $growthRates = [];
            $metrics = ['visitors', 'page_views', 'menu_views', 'item_views'];
            
            foreach ($metrics as $metric) {
                $firstValue = (float)($firstHalf[$metric] ?? 0);
                $secondValue = (float)($secondHalf[$metric] ?? 0);
                
                if ($firstValue > 0) {
                    $growthRates[$metric] = round((($secondValue - $firstValue) / $firstValue) * 100, 1);
                } else {
                    $growthRates[$metric] = $secondValue > 0 ? 100 : 0;
                }
            }
            
            return $growthRates;
            
        } catch (Exception $e) {
            return [
                'visitors' => 0,
                'page_views' => 0,
                'menu_views' => 0,
                'item_views' => 0
            ];
        }
    }

    /**
     * Export analytics data
     */
    public function exportAnalytics($restaurantId, $format = 'csv', $dateRange = null) {
        try {
            $user = $this->auth->getCurrentUser();
            
            if (!$user) {
                throw new Exception('يجب تسجيل الدخول أولاً');
            }

            // Check permissions
            if (!$this->auth->isAdmin()) {
                $restaurant = $this->db->fetch(
                    "SELECT * FROM restaurants WHERE id = :id AND user_id = :user_id",
                    ['id' => $restaurantId, 'user_id' => $user['id']]
                );

                if (!$restaurant) {
                    throw new Exception('ليس لديك صلاحية لتصدير هذه الإحصائيات');
                }
            }

            // Check if analytics are enabled
            if (!$this->subscription->checkPermission('analytics', $restaurantId)) {
                throw new Exception($this->subscription->getPermissionErrorMessage('analytics'));
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

            $data = $this->db->fetchAll(
                "SELECT * FROM statistics WHERE {$whereClause} ORDER BY date DESC",
                $params
            );

            if ($format === 'csv') {
                return $this->exportToCSV($data);
            } elseif ($format === 'json') {
                return $this->exportToJSON($data);
            } else {
                throw new Exception('تنسيق التصدير غير مدعوم');
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Export data to CSV
     */
    private function exportToCSV($data) {
        $filename = 'analytics_' . date('Y-m-d_H-i-s') . '.csv';
        $filepath = sys_get_temp_dir() . '/' . $filename;
        
        $file = fopen($filepath, 'w');
        
        // Write headers
        fputcsv($file, [
            'Date', 'Visitors', 'Page Views', 'Menu Views', 'Item Views', 
            'Reviews', 'Orders', 'Revenue'
        ]);
        
        // Write data
        foreach ($data as $row) {
            fputcsv($file, [
                $row['date'],
                $row['visitors_count'],
                $row['page_views'],
                $row['menu_views'],
                $row['item_views'],
                $row['reviews_count'],
                $row['orders_count'],
                $row['revenue']
            ]);
        }
        
        fclose($file);
        
        return [
            'success' => true,
            'data' => [
                'filename' => $filename,
                'filepath' => $filepath,
                'download_url' => '/api/analytics/download/' . $filename
            ]
        ];
    }

    /**
     * Export data to JSON
     */
    private function exportToJSON($data) {
        $filename = 'analytics_' . date('Y-m-d_H-i-s') . '.json';
        $filepath = sys_get_temp_dir() . '/' . $filename;
        
        file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT));
        
        return [
            'success' => true,
            'data' => [
                'filename' => $filename,
                'filepath' => $filepath,
                'download_url' => '/api/analytics/download/' . $filename
            ]
        ];
    }

    /**
     * Log analytics activity
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
