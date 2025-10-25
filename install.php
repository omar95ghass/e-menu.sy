<?php
/**
 * E-Menu Installation Script
 * Sets up the database and initial configuration
 */

require_once __DIR__ . '/config/config.php';

class Installer {
    private $db;
    private $errors = [];
    private $success = [];

    public function __construct() {
        try {
            $this->db = new PDO(
                "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            $this->errors[] = "Database connection failed: " . $e->getMessage();
        }
    }

    public function install() {
        echo "<h1>E-Menu Installation</h1>\n";
        
        if (!empty($this->errors)) {
            $this->showErrors();
            return false;
        }

        // Check if database exists
        if (!$this->databaseExists()) {
            $this->createDatabase();
        }

        // Connect to the application database
        $this->connectToDatabase();

        // Check if tables exist
        if ($this->tablesExist()) {
            echo "<p style='color: orange;'>Database tables already exist. Skipping table creation.</p>\n";
        } else {
            $this->createTables();
        }

        // Check if default data exists
        if (!$this->defaultDataExists()) {
            $this->insertDefaultData();
        }

        // Create upload directories
        $this->createDirectories();

        // Set file permissions
        $this->setPermissions();

        $this->showSuccess();
        return true;
    }

    private function databaseExists() {
        try {
            $result = $this->db->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
            return $result->rowCount() > 0;
        } catch (PDOException $e) {
            $this->errors[] = "Error checking database: " . $e->getMessage();
            return false;
        }
    }

    private function createDatabase() {
        try {
            $this->db->exec("CREATE DATABASE `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->success[] = "Database '" . DB_NAME . "' created successfully";
        } catch (PDOException $e) {
            $this->errors[] = "Error creating database: " . $e->getMessage();
        }
    }

    private function connectToDatabase() {
        try {
            $this->db->exec("USE `" . DB_NAME . "`");
            $this->success[] = "Connected to database '" . DB_NAME . "'";
        } catch (PDOException $e) {
            $this->errors[] = "Error connecting to database: " . $e->getMessage();
        }
    }

    private function tablesExist() {
        try {
            $result = $this->db->query("SHOW TABLES");
            return $result->rowCount() > 0;
        } catch (PDOException $e) {
            $this->errors[] = "Error checking tables: " . $e->getMessage();
            return false;
        }
    }

    private function createTables() {
        $sqlFile = __DIR__ . '/database/schema.sql';
        
        if (!file_exists($sqlFile)) {
            $this->errors[] = "Schema file not found: " . $sqlFile;
            return;
        }

        $sql = file_get_contents($sqlFile);
        
        // Split SQL into individual statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^(--|\#)/', $stmt);
            }
        );

        foreach ($statements as $statement) {
            try {
                $this->db->exec($statement);
            } catch (PDOException $e) {
                $this->errors[] = "Error executing SQL: " . $e->getMessage() . "\nStatement: " . substr($statement, 0, 100) . "...";
            }
        }

        $this->success[] = "Database tables created successfully";
    }

    private function defaultDataExists() {
        try {
            $result = $this->db->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
            $count = $result->fetch()['count'];
            return $count > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    private function insertDefaultData() {
        try {
            // Insert default admin user
            $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $this->db->exec("
                INSERT INTO users (email, password, name, role, is_active, email_verified) 
                VALUES ('admin@e-menu.sy', '{$adminPassword}', 'System Administrator', 'admin', 1, 1)
                ON DUPLICATE KEY UPDATE email = email
            ");

            $this->success[] = "Default admin user created (email: admin@e-menu.sy, password: admin123)";
        } catch (PDOException $e) {
            $this->errors[] = "Error creating default admin user: " . $e->getMessage();
        }
    }

    private function createDirectories() {
        $directories = [
            LOG_PATH,
            UPLOAD_PATH,
            UPLOAD_IMAGES_PATH,
            UPLOAD_LOGOS_PATH,
            BACKUP_PATH
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                if (mkdir($dir, 0755, true)) {
                    $this->success[] = "Created directory: " . $dir;
                } else {
                    $this->errors[] = "Failed to create directory: " . $dir;
                }
            } else {
                $this->success[] = "Directory already exists: " . $dir;
            }
        }
    }

    private function setPermissions() {
        $directories = [
            LOG_PATH,
            UPLOAD_PATH,
            UPLOAD_IMAGES_PATH,
            UPLOAD_LOGOS_PATH,
            BACKUP_PATH
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                if (chmod($dir, 0755)) {
                    $this->success[] = "Set permissions for: " . $dir;
                } else {
                    $this->errors[] = "Failed to set permissions for: " . $dir;
                }
            }
        }
    }

    private function showErrors() {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
        echo "<h3>Installation Errors:</h3>";
        echo "<ul>";
        foreach ($this->errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
        echo "</div>";
    }

    private function showSuccess() {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 10px; border: 1px solid #c3e6cb; border-radius: 4px;'>";
        echo "<h3>Installation Completed Successfully!</h3>";
        echo "<ul>";
        foreach ($this->success as $message) {
            echo "<li>" . htmlspecialchars($message) . "</li>";
        }
        echo "</ul>";
        echo "</div>";

        echo "<div style='background: #d1ecf1; color: #0c5460; padding: 15px; margin: 10px; border: 1px solid #bee5eb; border-radius: 4px;'>";
        echo "<h3>Next Steps:</h3>";
        echo "<ol>";
        echo "<li>Delete this installation file (install.php) for security</li>";
        echo "<li>Update the admin password in the admin panel</li>";
        echo "<li>Configure your email settings in the admin panel</li>";
        echo "<li>Test the API endpoints</li>";
        echo "<li>Configure your web server for subdomain handling</li>";
        echo "</ol>";
        echo "</div>";

        echo "<div style='background: #fff3cd; color: #856404; padding: 15px; margin: 10px; border: 1px solid #ffeaa7; border-radius: 4px;'>";
        echo "<h3>Important Security Notes:</h3>";
        echo "<ul>";
        echo "<li>Change the default admin password immediately</li>";
        echo "<li>Update the JWT_SECRET in config/config.php</li>";
        echo "<li>Enable HTTPS in production</li>";
        echo "<li>Set up proper file permissions</li>";
        echo "<li>Configure firewall rules</li>";
        echo "</ul>";
        echo "</div>";
    }
}

// Run installation
if (isset($_GET['install']) && $_GET['install'] === 'run') {
    $installer = new Installer();
    $installer->install();
} else {
    echo "<h1>E-Menu Installation</h1>";
    echo "<p>This script will install the E-Menu system.</p>";
    echo "<p><strong>Before proceeding, make sure:</strong></p>";
    echo "<ul>";
    echo "<li>You have created a MySQL database</li>";
    echo "<li>You have updated the database credentials in config/config.php</li>";
    echo "<li>You have proper permissions to create tables and directories</li>";
    echo "</ul>";
    echo "<p><a href='?install=run' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Start Installation</a></p>";
}
?>
