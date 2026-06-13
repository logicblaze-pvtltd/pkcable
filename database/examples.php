<?php
/**
 * Database Usage Examples
 * Shows how to use the connection helper for CRUD operations
 */

require_once '../include/connection.php';

// ===========================================
// PACKAGES - SELECT ALL
// ===========================================
/*
$packages = $db->select("SELECT * FROM packages");
if (isset($packages['error'])) {
    echo "Error: " . $packages['error'];
} else {
    foreach ($packages as $package) {
        echo "ID: " . $package['id'] . ", Name: " . $package['name'] . ", Price: " . $package['price'] . "<br>";
    }
}
*/

// ===========================================
// PACKAGES - SELECT WITH WHERE CLAUSE (Prepared Statement)
// ===========================================
/*
$package_id = 1;
$packages = $db->select(
    "SELECT * FROM packages WHERE id = ?",
    [$package_id]
);
if (isset($packages['error'])) {
    echo "Error: " . $packages['error'];
} else {
    print_r($packages);
}
*/

// ===========================================
// PACKAGES - INSERT NEW
// ===========================================
/*
$result = $db->insert('packages', [
    'name' => '12 Mb',
    'price' => '50'
]);
if ($result['success']) {
    echo "Package inserted successfully! ID: " . $result['id'];
} else {
    echo "Error: " . $result['error'];
}
*/

// ===========================================
// PACKAGES - UPDATE
// ===========================================
/*
$result = $db->update(
    'packages',
    ['price' => '55'],
    'id = ?',
    [4]
);
if ($result['success']) {
    echo "Package updated successfully! Affected rows: " . $result['affected_rows'];
} else {
    echo "Error: " . $result['error'];
}
*/

// ===========================================
// PACKAGES - DELETE
// ===========================================
/*
$result = $db->delete(
    'packages',
    'id = ?',
    [4]
);
if ($result['success']) {
    echo "Package deleted successfully! Affected rows: " . $result['affected_rows'];
} else {
    echo "Error: " . $result['error'];
}
*/

// ===========================================
// USERS - INSERT NEW
// ===========================================
/*
$result = $db->insert('users', [
    'name' => 'Ahmed Sultan',
    'email' => 'ahmed@example.com',
    'password' => password_hash('password123', PASSWORD_DEFAULT),
    'user_role' => 'customer',
    'status' => 'active',
    'package' => 1,
    'address' => '123 Street, City'
]);
if ($result['success']) {
    echo "User inserted successfully! ID: " . $result['id'];
} else {
    echo "Error: " . $result['error'];
}
*/

// ===========================================
// SUBSCRIPTIONS - INSERT NEW
// ===========================================
/*
$result = $db->insert('subscriptions', [
    'user_id' => 1,
    'package_id' => 1,
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+30 days')),
    'status' => 'active',
    'discount' => '0'
]);
if ($result['success']) {
    echo "Subscription inserted successfully! ID: " . $result['id'];
} else {
    echo "Error: " . $result['error'];
}
*/

// ===========================================
// GET ALL USERS WITH THEIR PACKAGE INFO
// ===========================================
/*
$users = $db->select("
    SELECT u.*, p.name as package_name, p.price 
    FROM users u 
    LEFT JOIN packages p ON u.package = p.id
");
if (isset($users['error'])) {
    echo "Error: " . $users['error'];
} else {
    print_r($users);
}
*/

// ===========================================
// GET USER SUBSCRIPTIONS
// ===========================================
/*
$user_id = 1;
$subscriptions = $db->select(
    "SELECT s.*, p.name as package_name, p.price 
    FROM subscriptions s 
    JOIN packages p ON s.package_id = p.id 
    WHERE s.user_id = ?",
    [$user_id]
);
if (isset($subscriptions['error'])) {
    echo "Error: " . $subscriptions['error'];
} else {
    print_r($subscriptions);
}
*/

// ===========================================
// USING RAW CONNECTION FOR COMPLEX QUERIES
// ===========================================
/*
$connection = $db->getConnection();
$result = $connection->query("
    SELECT 
        p.id, 
        p.name, 
        p.price,
        COUNT(u.id) as total_users
    FROM packages p
    LEFT JOIN users u ON p.id = u.package
    GROUP BY p.id
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "Package: " . $row['name'] . " - Total Users: " . $row['total_users'] . "<br>";
    }
}
*/

// echo "Connection established successfully!<br>";
// echo "Database: " . getenv('DB_NAME') . "<br>";
// echo "Host: " . getenv('DB_HOST') . "<br>";
// echo "Uncomment the examples above to test database operations.";
?>
