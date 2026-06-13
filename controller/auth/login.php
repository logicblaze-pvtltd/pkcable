<?php
header('Content-Type: application/json');

require_once '../../include/connection.php';
require_once __DIR__ . '/helpers.php';

session_start();

try {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        customer_respond(false, 'Method not allowed', [], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        customer_respond(false, 'Valid email required', [], 422);
    }

    if ($password === '') {
        customer_respond(false, 'Password required', [], 422);
    }

    // Get user
    $user = $db->select(
        "SELECT * FROM users WHERE email = ? LIMIT 1",
        [$email]
    );

    if (isset($user['error'])) {
        customer_respond(false, 'Database error: ' . $user['error'], [], 500);
    }

    if (empty($user)) {
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
        customer_respond(false, 'Account is inactive', [], 403);
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
        'role' => $user['user_role']
    ];

    customer_respond(true, 'Login successful', [
        'user' => $_SESSION['user']
    ]);

} catch (Exception $e) {
    customer_respond(false, 'Server Error: ' . $e->getMessage(), [], 500);
}