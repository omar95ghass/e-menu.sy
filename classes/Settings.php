<?php
/**
 * Settings Management Class
 * Handles system settings and configuration
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/../config/config.php';

class Settings {
    private $db;
    private $auth;
    private $cache = [];

    public function __construct() {
        $this->db = Database::getInstance();
        $this->auth = new Auth();
    }

    /**
     * Get system setting
     */
    public function get($key, $default = null) {
        try {
            // Check cache first
            if (isset($this->cache[$key])) {
                return $this->cache[$key];
            }

            $setting = $this->db->fetch(
                "SELECT * FROM system_settings WHERE `key` = :key",
                ['key' => $key]
            );

            if (!$setting) {
                return $default;
            }

            $value = $this->parseSettingValue($setting['value'], $setting['type']);
            
            // Cache the value
            $this->cache[$key] = $value;
            
            return $value;

        } catch (Exception $e) {
            return $default;
        }
    }

    /**
     * Set system setting
     */
    public function set($key, $value, $type = 'string', $description = null, $isPublic = false) {
        try {
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية لتعديل إعدادات النظام');
            }

            $serializedValue = $this->serializeSettingValue($value, $type);

            // Check if setting exists
            $existing = $this->db->fetch(
                "SELECT * FROM system_settings WHERE `key` = :key",
                ['key' => $key]
            );

            if ($existing) {
                // Update existing setting
                $this->db->update('system_settings', [
                    'value' => $serializedValue,
                    'type' => $type,
                    'description' => $description ?? $existing['description'],
                    'is_public' => $isPublic ? 1 : 0
                ], '`key` = :key', ['key' => $key]);
            } else {
                // Create new setting
                $this->db->insert('system_settings', [
                    'key' => $key,
                    'value' => $serializedValue,
                    'type' => $type,
                    'description' => $description,
                    'is_public' => $isPublic ? 1 : 0
                ]);
            }

            // Update cache
            $this->cache[$key] = $value;

            // Log activity
            $this->logActivity('setting_updated', "تم تحديث الإعداد: {$key}");

            return [
                'success' => true,
                'message' => 'تم تحديث الإعداد بنجاح'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get all public settings
     */
    public function getPublicSettings() {
        try {
            $settings = $this->db->fetchAll(
                "SELECT `key`, `value`, `type` FROM system_settings WHERE is_public = 1 ORDER BY `key`"
            );

            $result = [];
            foreach ($settings as $setting) {
                $result[$setting['key']] = $this->parseSettingValue($setting['value'], $setting['type']);
            }

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
     * Get all settings (admin only)
     */
    public function getAllSettings() {
        try {
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية للوصول إلى جميع إعدادات النظام');
            }

            $settings = $this->db->fetchAll(
                "SELECT * FROM system_settings ORDER BY `key`"
            );

            $result = [];
            foreach ($settings as $setting) {
                $result[] = [
                    'key' => $setting['key'],
                    'value' => $this->parseSettingValue($setting['value'], $setting['type']),
                    'type' => $setting['type'],
                    'description' => $setting['description'],
                    'is_public' => (bool)$setting['is_public'],
                    'created_at' => $setting['created_at'],
                    'updated_at' => $setting['updated_at']
                ];
            }

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
     * Delete setting (admin only)
     */
    public function delete($key) {
        try {
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية لحذف إعدادات النظام');
            }

            $setting = $this->db->fetch(
                "SELECT * FROM system_settings WHERE `key` = :key",
                ['key' => $key]
            );

            if (!$setting) {
                throw new Exception('الإعداد غير موجود');
            }

            $this->db->delete('system_settings', '`key` = :key', ['key' => $key]);

            // Remove from cache
            unset($this->cache[$key]);

            // Log activity
            $this->logActivity('setting_deleted', "تم حذف الإعداد: {$key}");

            return [
                'success' => true,
                'message' => 'تم حذف الإعداد بنجاح'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Bulk update settings
     */
    public function bulkUpdate($settings) {
        try {
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية لتعديل إعدادات النظام');
            }

            $this->db->beginTransaction();

            try {
                foreach ($settings as $key => $settingData) {
                    $value = $settingData['value'] ?? null;
                    $type = $settingData['type'] ?? 'string';
                    $description = $settingData['description'] ?? null;
                    $isPublic = $settingData['is_public'] ?? false;

                    $serializedValue = $this->serializeSettingValue($value, $type);

                    // Check if setting exists
                    $existing = $this->db->fetch(
                        "SELECT * FROM system_settings WHERE `key` = :key",
                        ['key' => $key]
                    );

                    if ($existing) {
                        // Update existing setting
                        $this->db->update('system_settings', [
                            'value' => $serializedValue,
                            'type' => $type,
                            'description' => $description ?? $existing['description'],
                            'is_public' => $isPublic ? 1 : 0
                        ], '`key` = :key', ['key' => $key]);
                    } else {
                        // Create new setting
                        $this->db->insert('system_settings', [
                            'key' => $key,
                            'value' => $serializedValue,
                            'type' => $type,
                            'description' => $description,
                            'is_public' => $isPublic ? 1 : 0
                        ]);
                    }

                    // Update cache
                    $this->cache[$key] = $value;
                }

                $this->db->commit();

                // Log activity
                $this->logActivity('settings_bulk_updated', 'تم تحديث عدة إعدادات');

                return [
                    'success' => true,
                    'message' => 'تم تحديث الإعدادات بنجاح'
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
     * Reset settings to defaults
     */
    public function resetToDefaults() {
        try {
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية لإعادة تعيين إعدادات النظام');
            }

            $defaultSettings = [
                'app_name' => ['value' => 'E-Menu', 'type' => 'string', 'description' => 'Application name', 'is_public' => true],
                'app_version' => ['value' => '1.0.0', 'type' => 'string', 'description' => 'Application version', 'is_public' => true],
                'default_language' => ['value' => 'ar', 'type' => 'string', 'description' => 'Default language', 'is_public' => true],
                'maintenance_mode' => ['value' => false, 'type' => 'boolean', 'description' => 'Maintenance mode status', 'is_public' => true],
                'registration_enabled' => ['value' => true, 'type' => 'boolean', 'description' => 'Allow new registrations', 'is_public' => true],
                'email_verification_required' => ['value' => true, 'type' => 'boolean', 'description' => 'Require email verification', 'is_public' => false],
                'max_file_upload_size' => ['value' => 5242880, 'type' => 'integer', 'description' => 'Maximum file upload size in bytes', 'is_public' => false],
                'allowed_file_types' => ['value' => ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'type' => 'json', 'description' => 'Allowed file types for upload', 'is_public' => false],
                'default_currency' => ['value' => 'SYP', 'type' => 'string', 'description' => 'Default currency', 'is_public' => true],
                'currency_symbol' => ['value' => 'ل.س', 'type' => 'string', 'description' => 'Currency symbol', 'is_public' => true],
                'timezone' => ['value' => 'Asia/Damascus', 'type' => 'string', 'description' => 'Default timezone', 'is_public' => true],
                'date_format' => ['value' => 'Y-m-d', 'type' => 'string', 'description' => 'Date format', 'is_public' => true],
                'time_format' => ['value' => 'H:i', 'type' => 'string', 'description' => 'Time format', 'is_public' => true]
            ];

            return $this->bulkUpdate($defaultSettings);

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Export settings
     */
    public function exportSettings() {
        try {
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية لتصدير إعدادات النظام');
            }

            $settings = $this->db->fetchAll(
                "SELECT * FROM system_settings ORDER BY `key`"
            );

            $exportData = [];
            foreach ($settings as $setting) {
                $exportData[$setting['key']] = [
                    'value' => $this->parseSettingValue($setting['value'], $setting['type']),
                    'type' => $setting['type'],
                    'description' => $setting['description'],
                    'is_public' => (bool)$setting['is_public']
                ];
            }

            $filename = 'settings_' . date('Y-m-d_H-i-s') . '.json';
            $filepath = sys_get_temp_dir() . '/' . $filename;
            
            file_put_contents($filepath, json_encode($exportData, JSON_PRETTY_PRINT));

            return [
                'success' => true,
                'data' => [
                    'filename' => $filename,
                    'filepath' => $filepath,
                    'download_url' => '/api/settings/download/' . $filename
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
     * Import settings
     */
    public function importSettings($filePath) {
        try {
            if (!$this->auth->isAdmin()) {
                throw new Exception('ليس لديك صلاحية لاستيراد إعدادات النظام');
            }

            if (!file_exists($filePath)) {
                throw new Exception('ملف الإعدادات غير موجود');
            }

            $content = file_get_contents($filePath);
            $settings = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('ملف الإعدادات غير صحيح');
            }

            return $this->bulkUpdate($settings);

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Parse setting value based on type
     */
    private function parseSettingValue($value, $type) {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int)$value;
            case 'json':
                return json_decode($value, true);
            case 'text':
            case 'string':
            default:
                return $value;
        }
    }

    /**
     * Serialize setting value based on type
     */
    private function serializeSettingValue($value, $type) {
        switch ($type) {
            case 'boolean':
                return $value ? 'true' : 'false';
            case 'integer':
                return (string)(int)$value;
            case 'json':
                return json_encode($value);
            case 'text':
            case 'string':
            default:
                return (string)$value;
        }
    }

    /**
     * Clear settings cache
     */
    public function clearCache() {
        $this->cache = [];
    }

    /**
     * Get cached settings
     */
    public function getCache() {
        return $this->cache;
    }

    /**
     * Log settings activity
     */
    private function logActivity($action, $description) {
        $user = $this->auth->getCurrentUser();
        
        $this->db->insert('activity_logs', [
            'user_id' => $user['id'] ?? null,
            'restaurant_id' => null,
            'action' => $action,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
}
