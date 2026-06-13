<?php
header('Content-Type: application/json');

require_once '../../include/connection.php';
require_once __DIR__ . '/helpers.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        admin_respond(false, 'Method not allowed', [], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');
    $userRole = trim($input['user_role'] ?? 'admin');
    $status = trim($input['status'] ?? 'active');
    $address = trim($input['address'] ?? '');

    if ($name === '') {
        admin_respond(false, 'Name is required', [], 422);
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        admin_respond(false, 'Valid email is required', [], 422);
    }

    if (!admin_valid_role($userRole)) {
        admin_respond(false, 'Valid user role is required', [], 422);
    }

    if (!admin_valid_status($status)) {
        admin_respond(false, 'Valid status is required', [], 422);
    }

    // check duplicate email
    $existing = $db->select('SELECT id FROM users WHERE email = ?', [$email]);
    if (isset($existing['error'])) {
        admin_respond(false, 'Database Error: ' . $existing['error'], [], 500);
    }
    if (!empty($existing)) {
        admin_respond(false, 'Email already exists', [], 409);
    }

    $plainPassword = null;
    if ($password === '') {
        $plainPassword = bin2hex(random_bytes(4)); // 8-character random password
        $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    }

    $insertData = [
        'name' => $name,
        'email' => $email,
        'password' => $passwordHash,
        'user_role' => $userRole,
        'status' => $status,
        'package' => null, // Admins don't have packages
        'address' => $address !== '' ? $address : null,
    ];

    $insert = $db->insert('users', $insertData);

    if (isset($insert['error'])) {
        admin_respond(false, 'Database Error: ' . $insert['error'], [], 500);
    }

    if (empty($insert['success'])) {
        admin_respond(false, 'Failed to create admin', [], 500);
    }

    $createdUser = admin_fetch_user_record($db, (int) $insert['id']);

    if (isset($createdUser['error'])) {
        admin_respond(false, 'Database Error: ' . $createdUser['error'], [], 500);
    }

    admin_respond(true, 'Admin created successfully', [
        'user' => $createdUser,
        'generated_password' => $plainPassword
    ]);

} catch (Exception $e) {
    admin_respond(false, 'Server Error: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 500);
}
