<?php
session_start();
header('Content-Type: application/json');

require_once '../../include/connection.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../subscription/auto_expired.php';


try {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        customer_respond(false, 'Method not allowed', [], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');

    if ($username === '') {
        customer_respond(false, 'Email or Mobile number required', [], 422);
    }

    if ($password === '') {
        customer_respond(false, 'Password required', [], 422);
    }

    // Get user
    $user = $db->select(
        "SELECT * FROM users WHERE email = ? OR mobile = ? LIMIT 1",
        [$username, $username]
    );

    if (isset($user['error'])) {
        customer_respond(false, 'Database error: ' . $user['error'], [], 500);
    }

    if (empty($user)) {
        auto_expired(); // Ensure expired subscriptions are updated before login
        customer_respond(false, 'Invalid credentials', [], 401);
    }

    $user = $user[0];
    // echo gettype($password);
    // echo "\n";
    // echo gettype($user['password']);
    // echo "\n";
    // echo password_verify($password, $user['password']) ? 'true' : 'false'; // Debug: Remove in production
    // exit;

    // Check status
    if ($user['status'] !== 'active') {
        customer_respond(false, 'Account is inactive please contact pakistan Cable Team', [], 403);
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        customer_respond(false, 'Invalid credentials', [], 401);
    }

    // Create session
    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'mobile' => $user['mobile'],
        'role' => $user['user_role']
    ];

    customer_respond(true, 'Login successful', [
        'user' => $_SESSION['user']
    ]);

} catch (Exception $e) {
    customer_respond(false, 'Server Error: ' . $e->getMessage(), [], 500);
}