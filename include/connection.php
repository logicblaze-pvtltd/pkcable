<?php
/**
 * Database Connection Handler
 * Supports Pakistan Cable database schema with packages, users, and subscriptions tables
 */

// Load environment variables from .env file
$envFile = __DIR__ . '/../.env';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos($line, '#') === 0) {
            continue;
        }

        // Parse the line
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Set as environment variable
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Database configuration from .env
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_port = getenv('DB_PORT') ?: 3306;
$db_user = getenv('DB_USER') ?: 'root';
$db_password = getenv('DB_PASSWORD') ?: '';
$db_name = getenv('DB_NAME') ?: 'pkcable';

// Create MySQLi connection
$conn = new mysqli($db_host, $db_user, $db_password, $db_name, $db_port);

// Check connection
if ($conn->connect_error) {
    die('<div style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px; border-radius: 4px; margin: 20px;">
        <strong>Database Connection Error:</strong> ' . $conn->connect_error . '
        <br><small>Check your .env file configuration</small>
    </div>');
}

// Set charset to UTF-8
$conn->set_charset('utf8mb4');

// Set timezone
$conn->query("SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");

// Helper functions for database operations
class DatabaseHelper {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Execute a SELECT query
     */
    public function select($query, $params = []) {
        if (!empty($params)) {
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                return ['error' => $this->conn->error];
            }
            
            // Bind parameters
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
            
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        
        $result = $this->conn->query($query);
        if (!$result) {
            return ['error' => $this->conn->error];
        }
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Execute an INSERT query
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return ['error' => $this->conn->error, 'success' => false];
        }
        
        // Bind parameters
        $types = '';
        $values = [];
        foreach ($data as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $value;
        }
        
        $stmt->bind_param($types, ...$values);
        $result = $stmt->execute();
        $stmt->close();
        
        return [
            'success' => $result,
            'id' => $this->conn->insert_id,
            'error' => $result ? null : $this->conn->error
        ];
    }

    /**
     * Execute an UPDATE query
     */
    public function update($table, $data, $where_clause, $where_params = []) {
        $set_clause = implode(', ', array_map(function($key) {
            return "$key = ?";
        }, array_keys($data)));
        
        $query = "UPDATE $table SET $set_clause WHERE $where_clause";
        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return ['error' => $this->conn->error, 'success' => false];
        }
        
        // Prepare parameters
        $types = '';
        $values = array_values($data);
        
        foreach ($values as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        
        // Add where clause parameters
        foreach ($where_params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $param;
        }
        
        $stmt->bind_param($types, ...$values);
        $result = $stmt->execute();
        $stmt->close();
        
        return [
            'success' => $result,
            'affected_rows' => $this->conn->affected_rows,
            'error' => $result ? null : $this->conn->error
        ];
    }

    /**
     * Execute a DELETE query
     */
    public function delete($table, $where_clause, $params = []) {
        $query = "DELETE FROM $table WHERE $where_clause";
        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return ['error' => $this->conn->error, 'success' => false];
        }
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            $stmt->bind_param($types, ...$params);
        }
        
        $result = $stmt->execute();
        $stmt->close();
        
        return [
            'success' => $result,
            'affected_rows' => $this->conn->affected_rows,
            'error' => $result ? null : $this->conn->error
        ];
    }

    /**
     * Get connection object for custom queries
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Close connection
     */
    public function closeConnection() {
        $this->conn->close();
    }
}

// Create database helper instance
$db = new DatabaseHelper($conn);

// Make both $conn and $db available globally
global $conn, $db;
?>
