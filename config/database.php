<?php
/**
 * 2SureSub - Database Configuration
 * Handles database connection using PDO
 */

define('DB_HOST', 'localhost');
define('DB_NAME', '2suresubs');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO Database Connection
 */
function getDbConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

/**
 * Execute a query and return results
 */
function dbQuery($sql, $params = []) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Get single row
 */
function dbFetchOne($sql, $params = []) {
    return dbQuery($sql, $params)->fetch();
}

/**
 * Get all rows
 */
function dbFetchAll($sql, $params = []) {
    return dbQuery($sql, $params)->fetchAll();
}

/**
 * Insert and return last ID
 */
function dbInsert($sql, $params = []) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $pdo->lastInsertId();
}

/**
 * Update/Delete and return affected rows
 */
function dbExecute($sql, $params = []) {
    return dbQuery($sql, $params)->rowCount();
}
