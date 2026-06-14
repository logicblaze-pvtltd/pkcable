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
        $line = trim($line);
        // Skip comments and empty lines
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        // Parse the line
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Strip inline comments if they exist (e.g. DB_HOST=localhost # database host)
            if (strpos($value, '#') !== false) {
                if (!preg_match('/^[\'"]/', $value)) {
                    $parts = explode('#', $value, 2);
                    $value = trim($parts[0]);
                }
            }

            // Strip enclosing quotes if they exist (both single and double)
            if (preg_match('/^([\'"])(.*)\1$/', $value, $matches)) {
                $value = $matches[2];
            }
            
            // Set as environment variable
            if (function_exists('putenv')) {
                @putenv("$key=$value");
            }
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

/**
 * Robust helper to retrieve environment variables on any hosting server.
 * Fallbacks to $_ENV and $_SERVER if putenv() or getenv() is disabled.
 */
if (!function_exists('get_env_value')) {
    function get_env_value($key, $default = '') {
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        $val = getenv($key);
        return $val !== false ? $val : $default;
    }
}

// Database configuration from .env
$db_host = get_env_value('DB_HOST', 'localhost');
$db_port = get_env_value('DB_PORT', 3306);
$db_user = get_env_value('DB_USER', 'root');
$db_password = get_env_value('DB_PASSWORD', '');
$db_name = get_env_value('DB_NAME', 'pkcable');


// Create MySQLi connection with try-catch for PHP 8.1+ compatibility
try {
    $conn = new mysqli($db_host, $db_user, $db_password, $db_name, $db_port);
    
    // Check connection (for older PHP versions where exception is not thrown)
    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }
} catch (Exception $e) {
    die('<div style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 16px; border-radius: 6px; margin: 20px; font-family: Arial, sans-serif; line-height: 1.5;">
        <strong style="font-size: 16px;">Database Connection Error:</strong> ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '
        <br><br>
        <strong style="color: #58151c;">What to do:</strong>
        <p style="margin: 5px 0;">Please check your <strong>.env</strong> file on your live hosting server and verify the following credentials:</p>
        <ul style="margin: 5px 0 0 20px; padding: 0;">
            <li><strong>DB_HOST</strong>: Live database hostname (For ByetHost, it is usually like <code>sqlXXX.byethost11.com</code> instead of <code>localhost</code>).</li>
            <li><strong>DB_USER</strong>: Your database user name (For ByetHost, e.g. <code>b11_XXXXXXXX</code>).</li>
            <li><strong>DB_PASSWORD</strong>: Your hosting password or specific database password.</li>
            <li><strong>DB_NAME</strong>: Your database name (For ByetHost, e.g. <code>b11_XXXXXXXX_pkcable</code>).</li>
        </ul>
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
