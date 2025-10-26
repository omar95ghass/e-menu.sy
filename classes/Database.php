<?php
/**
 * Database Connection and Query Management Class
 * Handles all database operations using PDO
 */

require_once __DIR__ . '/../config/config.php';

class Database {
    private static $instance = null;
    private $pdo;
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset;

    private function __construct() {
        $this->host = DB_HOST;
        $this->dbname = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->charset = DB_CHARSET;
        
        $this->connect();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establish database connection
     */
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
            ];

            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            $this->logError('Database connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed');
        }
    }

    /**
     * Get PDO instance
     */
    public function getPDO() {
        return $this->pdo;
    }

    /**
     * Execute a prepared statement
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logError('Query execution failed: ' . $e->getMessage() . ' SQL: ' . $sql);
            throw new Exception('Database query failed');
        }
    }

    /**
     * Fetch single row
     */
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Insert data and return last insert ID
     */
    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        
        return $this->pdo->lastInsertId();
    }

    /**
     * Update data
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setParts = [];
        foreach (array_keys($data) as $key) {
            $setParts[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge($data, $whereParams);
        
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Delete data
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Check if record exists
     */
    public function exists($table, $where, $params = []) {
        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$where}";
        $result = $this->fetch($sql, $params);
        return $result['count'] > 0;
    }

    /**
     * Count records
     */
    public function count($table, $where = '1=1', $params = []) {
        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$where}";
        $result = $this->fetch($sql, $params);
        return (int)$result['count'];
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->pdo->rollback();
    }

    /**
     * Check if in transaction
     */
    public function inTransaction() {
        return $this->pdo->inTransaction();
    }

    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    /**
     * Paginate results
     */
    public function paginate($sql, $params = [], $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        // Count total records
        $countSql = "SELECT COUNT(*) as total FROM ({$sql}) as count_table";
        $totalResult = $this->fetch($countSql, $params);
        $total = (int)$totalResult['total'];
        
        // Get paginated results
        $paginatedSql = "{$sql} LIMIT {$limit} OFFSET {$offset}";
        $data = $this->fetchAll($paginatedSql, $params);
        
        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit),
            'has_next' => $page < ceil($total / $limit),
            'has_prev' => $page > 1
        ];
    }

    /**
     * Search with full-text search
     */
    public function search($table, $searchColumns, $searchTerm, $where = '1=1', $params = []) {
        $searchConditions = [];
        foreach ($searchColumns as $column) {
            $searchConditions[] = "{$column} LIKE :search_term";
        }
        $searchClause = implode(' OR ', $searchConditions);
        
        $sql = "SELECT * FROM {$table} WHERE ({$searchClause}) AND {$where}";
        $searchParams = array_merge(['search_term' => "%{$searchTerm}%"], $params);
        
        return $this->fetchAll($sql, $searchParams);
    }

    /**
     * Execute raw SQL (use with caution)
     */
    public function execute($sql, $params = []) {
        return $this->query($sql, $params);
    }

    /**
     * Get table information
     */
    public function getTableInfo($table) {
        $sql = "DESCRIBE {$table}";
        return $this->fetchAll($sql);
    }

    /**
     * Check if table exists
     */
    public function tableExists($table) {
        $sql = "SHOW TABLES LIKE :table";
        $result = $this->fetch($sql, ['table' => $table]);
        return !empty($result);
    }

    /**
     * Get database version
     */
    public function getVersion() {
        $sql = "SELECT VERSION() as version";
        $result = $this->fetch($sql);
        return $result['version'];
    }

    /**
     * Optimize table
     */
    public function optimizeTable($table) {
        $sql = "OPTIMIZE TABLE {$table}";
        return $this->query($sql);
    }

    /**
     * Repair table
     */
    public function repairTable($table) {
        $sql = "REPAIR TABLE {$table}";
        return $this->query($sql);
    }

    /**
     * Backup table structure
     */
    public function getTableStructure($table) {
        $sql = "SHOW CREATE TABLE {$table}";
        $result = $this->fetch($sql);
        return $result['Create Table'];
    }

    /**
     * Get table size
     */
    public function getTableSize($table) {
        $sql = "SELECT 
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
                FROM information_schema.TABLES 
                WHERE table_schema = :dbname 
                AND table_name = :table";
        
        $result = $this->fetch($sql, [
            'dbname' => $this->dbname,
            'table' => $table
        ]);
        
        return $result['Size (MB)'];
    }

    /**
     * Log database errors
     */
    private function logError($message) {
        if (LOG_ENABLED) {
            $logFile = LOG_PATH . 'database_' . date('Y-m-d') . '.log';
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
            file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * Sanitize input for security
     */
    public function sanitize($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitize'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate email
     */
    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate phone number
     */
    public function validatePhone($phone) {
        return preg_match('/^[\+]?[0-9\s\-\(\)]{10,20}$/', $phone);
    }

    /**
     * Generate unique slug
     */
    public function generateSlug($text, $table, $column = 'slug', $excludeId = null) {
        $slug = $this->slugify($text);
        $originalSlug = $slug;
        $counter = 1;
        
        $where = "{$column} = :slug";
        $params = ['slug' => $slug];
        
        if ($excludeId) {
            $where .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        while ($this->exists($table, $where, $params)) {
            $slug = $originalSlug . '-' . $counter;
            $params['slug'] = $slug;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Convert text to URL-friendly slug
     */
    public function slugify($text) {
        // Convert to lowercase
        $text = strtolower($text);
        
        // Replace Arabic characters with transliterated equivalents
        $arabic = ['ا', 'أ', 'إ', 'آ', 'ب', 'ت', 'ث', 'ج', 'ح', 'خ', 'د', 'ذ', 'ر', 'ز', 'س', 'ش', 'ص', 'ض', 'ط', 'ظ', 'ع', 'غ', 'ف', 'ق', 'ك', 'ل', 'م', 'ن', 'ه', 'و', 'ي', 'ة', 'ء'];
        $english = ['a', 'a', 'a', 'a', 'b', 't', 'th', 'j', 'h', 'kh', 'd', 'th', 'r', 'z', 's', 'sh', 's', 'd', 't', 'th', 'a', 'gh', 'f', 'q', 'k', 'l', 'm', 'n', 'h', 'w', 'y', 'h', 'a'];
        $text = str_replace($arabic, $english, $text);
        
        // Remove special characters and replace spaces with hyphens
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        $text = trim($text, '-');
        
        return $text;
    }

    /**
     * Close connection
     */
    public function close() {
        $this->pdo = null;
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
