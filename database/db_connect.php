<?php
/**
 * Database Connection File
 * 
 * This file establishes a connection to the MySQL database for the TECH13 Garage website.
 */

// Database configuration
$db_host = 'localhost';    // Database host (usually localhost)
$db_name = 'tech13_garage'; // Database name
$db_user = 'root';         // Database username (change in production)
$db_pass = '';             // Database password (change in production)

// Database connection options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Create database connection
try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        $options
    );
    
    // Set the connection to be used globally
    $GLOBALS['db'] = $pdo;
    
} catch (PDOException $e) {
    // Handle database connection error
    die('Database Connection Failed: ' . $e->getMessage());
}

/**
 * Helper function to execute database queries
 *
 * @param string $sql      The SQL query
 * @param array  $params   The parameters to bind
 * @return PDOStatement    The statement after execution
 */
function executeQuery($sql, $params = []) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        // Log the error
        error_log('Database Query Error: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Helper function to fetch a single row from the database
 *
 * @param string $sql      The SQL query
 * @param array  $params   The parameters to bind
 * @return array|null      The row or null if not found
 */
function fetchRow($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Helper function to fetch all rows from the database
 *
 * @param string $sql      The SQL query
 * @param array  $params   The parameters to bind
 * @return array           The rows
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Helper function to insert a record into the database
 *
 * @param string $table    The table name
 * @param array  $data     The data to insert as associative array
 * @return int             The ID of the inserted record
 */
function insert($table, $data) {
    global $pdo;
    
    $fields = array_keys($data);
    $placeholders = array_map(function($field) {
        return ":$field";
    }, $fields);
    
    $sql = "INSERT INTO $table (" . implode(', ', $fields) . ") 
            VALUES (" . implode(', ', $placeholders) . ")";
    
    executeQuery($sql, $data);
    return $pdo->lastInsertId();
}

/**
 * Helper function to update a record in the database
 *
 * @param string $table    The table name
 * @param array  $data     The data to update as associative array
 * @param string $where    The where clause
 * @param array  $params   The parameters for the where clause
 * @return int             The number of affected rows
 */
function update($table, $data, $where, $params = []) {
    $fields = array_map(function($field) {
        return "$field = :$field";
    }, array_keys($data));
    
    $sql = "UPDATE $table SET " . implode(', ', $fields) . " WHERE $where";
    
    $params = array_merge($data, $params);
    $stmt = executeQuery($sql, $params);
    
    return $stmt->rowCount();
}

/**
 * Helper function to delete a record from the database
 *
 * @param string $table    The table name
 * @param string $where    The where clause
 * @param array  $params   The parameters for the where clause
 * @return int             The number of affected rows
 */
function delete($table, $where, $params = []) {
    $sql = "DELETE FROM $table WHERE $where";
    $stmt = executeQuery($sql, $params);
    
    return $stmt->rowCount();
}

/**
 * Helper function to get a setting from the database
 *
 * @param string $settingName    The setting name
 * @param mixed  $default        Default value if setting not found
 * @return mixed                 The setting value
 */
function getSetting($settingName, $default = null) {
    $sql = "SELECT setting_value FROM settings WHERE setting_name = :name";
    $result = fetchRow($sql, ['name' => $settingName]);
    
    return $result ? $result['setting_value'] : $default;
} 