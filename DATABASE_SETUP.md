# Pakistan Cable Database Setup Guide

## Database Connection Setup

### 1. Database Configuration (`.env` file)
The database configuration is stored in the `.env` file in the root directory:

```env
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASSWORD=
DB_NAME=pkcable
```

**Update these values** if your database configuration is different.

---

## 2. Create Database Tables

### Option A: Using phpMyAdmin
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Copy all SQL from `database/schema.sql`
3. Paste into the SQL tab and execute

### Option B: Using MySQL Command Line
```bash
mysql -u root -p < database/schema.sql
```

### Option C: Manual Creation
Run the following SQL queries in phpMyAdmin:

```sql
-- Create Database
CREATE DATABASE IF NOT EXISTS `pkcable` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `pkcable`;

-- Packages Table
CREATE TABLE IF NOT EXISTS `packages` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `price` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;

-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NULL,
    `email` VARCHAR(255) NULL,
    `password` VARCHAR(255) NULL,
    `user_role` ENUM('super admin', 'admin', 'manager', 'customer') DEFAULT 'customer',
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `package` INT NULL,
    `address` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`package`) REFERENCES `packages` (`id`) ON DELETE SET NULL,
    INDEX `idx_email` (`email`),
    INDEX `idx_status` (`status`),
    INDEX `idx_user_role` (`user_role`)
) ENGINE = InnoDB;

-- Subscriptions Table
CREATE TABLE IF NOT EXISTS `subscriptions` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `package_id` INT NOT NULL,
    `start_date` DATE NULL,
    `end_date` DATE NULL,
    `status` ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    `discount` VARCHAR(255) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_package_id` (`package_id`),
    INDEX `idx_status` (`status`)
) ENGINE = InnoDB;

-- Insert Sample Packages
INSERT INTO `packages` (`name`, `price`) VALUES
('4 Mb', '20'),
('8 Mb', '30'),
('10 Mb', '40');
```

---

## 3. Using the Database Connection

### Include the Connection File
```php
<?php
require_once './include/connection.php';
// Now you can use $db and $conn globally
?>
```

### SELECT Query
```php
// Get all packages
$packages = $db->select("SELECT * FROM packages");

// Get with WHERE clause (Prepared Statement)
$packages = $db->select(
    "SELECT * FROM packages WHERE id = ?",
    [1]
);

foreach ($packages as $package) {
    echo $package['name'] . " - " . $package['price'];
}
```

### INSERT Query
```php
$result = $db->insert('packages', [
    'name' => '12 Mb',
    'price' => '50'
]);

if ($result['success']) {
    echo "Inserted with ID: " . $result['id'];
} else {
    echo "Error: " . $result['error'];
}
```

### UPDATE Query
```php
$result = $db->update(
    'packages',
    ['price' => '55'],
    'id = ?',
    [1]
);

if ($result['success']) {
    echo "Updated " . $result['affected_rows'] . " rows";
}
```

### DELETE Query
```php
$result = $db->delete(
    'packages',
    'id = ?',
    [1]
);

if ($result['success']) {
    echo "Deleted " . $result['affected_rows'] . " rows";
}
```

### Complex JOIN Query
```php
$users = $db->select("
    SELECT u.*, p.name as package_name, p.price 
    FROM users u 
    LEFT JOIN packages p ON u.package = p.id
");

print_r($users);
```

### Using Raw Connection for Complex Queries
```php
$connection = $db->getConnection();
$result = $connection->query("
    SELECT p.*, COUNT(u.id) as total_users
    FROM packages p
    LEFT JOIN users u ON p.id = u.package
    GROUP BY p.id
");

while ($row = $result->fetch_assoc()) {
    echo $row['name'] . ": " . $row['total_users'] . " users<br>";
}
```

---

## Database Tables Structure

### **packages** Table
| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary Key, Auto Increment |
| name | VARCHAR(255) | Package name (e.g., "4 Mb", "8 Mb") |
| price | VARCHAR(255) | Package price |

### **users** Table
| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary Key, Auto Increment |
| name | VARCHAR(255) | User's full name |
| email | VARCHAR(255) | User's email |
| password | VARCHAR(255) | Hashed password |
| user_role | ENUM | Role: 'super admin', 'admin', 'manager', 'customer' |
| status | ENUM | Status: 'active', 'inactive' |
| package | INT | Foreign Key to packages table |
| address | TEXT | User's address |
| created_at | DATETIME | Account creation timestamp |

### **subscriptions** Table
| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary Key, Auto Increment |
| user_id | INT | Foreign Key to users table |
| package_id | INT | Foreign Key to packages table |
| start_date | DATE | Subscription start date |
| end_date | DATE | Subscription end date |
| status | ENUM | Status: 'active', 'expired', 'cancelled' |
| discount | VARCHAR(255) | Discount applied |
| created_at | DATETIME | Subscription creation timestamp |

---

## Example Usage in Your PHP Files

```php
<?php
require_once './include/connection.php';

// Get all packages for display
$packages = $db->select("SELECT * FROM packages");

// Get user details
$user = $db->select(
    "SELECT * FROM users WHERE id = ?",
    [$_GET['user_id'] ?? 1]
);

// Insert new user
if ($_POST) {
    $result = $db->insert('users', [
        'name' => $_POST['name'],
        'email' => $_POST['email'],
        'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
        'user_role' => 'customer',
        'status' => 'active',
        'package' => $_POST['package'] ?? null,
        'address' => $_POST['address']
    ]);
    
    if ($result['success']) {
        echo "User created successfully!";
    }
}
?>
```

---

## Helper Methods

### `$db->select($query, $params = [])`
Execute SELECT queries with optional prepared statement parameters.

### `$db->insert($table, $data)`
Insert data into a table. Returns array with `success`, `id`, and `error`.

### `$db->update($table, $data, $where_clause, $where_params = [])`
Update table records. Returns array with `success`, `affected_rows`, and `error`.

### `$db->delete($table, $where_clause, $params = [])`
Delete records from table. Returns array with `success`, `affected_rows`, and `error`.

### `$db->getConnection()`
Get the raw MySQLi connection object for custom queries.

### `$db->closeConnection()`
Close the database connection.

---

## Security Notes

✅ **Good Practices:**
- Always use prepared statements (?) for user input
- Hash passwords using `password_hash()`
- Store `.env` in `.gitignore` (do NOT commit)
- Validate and sanitize all user input
- Use parameterized queries to prevent SQL injection

❌ **Never Do:**
- Store plain text passwords
- Use string concatenation for queries with user input
- Commit `.env` file to version control
- Expose database credentials in code

---

## Troubleshooting

### Error: Connection refused
- Check if MySQL server is running
- Verify `DB_HOST`, `DB_PORT` in `.env`

### Error: Unknown database 'pkcable'
- Run the SQL schema from `database/schema.sql`
- Verify database name in `.env`

### Error: Access denied for user 'root'
- Check `DB_USER` and `DB_PASSWORD` in `.env`
- Verify XAMPP MySQL credentials

### File not found: .env
- Create `.env` in the root directory
- Copy the template from this file

---

For more examples, see `database/examples.php`
