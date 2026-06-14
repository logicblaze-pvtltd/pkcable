<?php
header('Content-Type: application/json');

require_once '../../include/connection.php';
require_once __DIR__ . '/helpers.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
        admin_respond(false, 'Method not allowed', [], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    $id = $input['id'] ?? null;
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');
    $userRole = trim($input['user_role'] ?? 'admin');
    $status = trim($input['status'] ?? 'active');
    $address = trim($input['address'] ?? '');

    if (empty($id) || !is_numeric($id)) {
        admin_respond(false, 'Valid admin ID is required', [], 422);
    }

    $id = (int) $id;

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

    $existingUser = admin_fetch_user_record($db, $id);
    if (isset($existingUser['error'])) {
        admin_respond(false, 'Database Error: ' . $existingUser['error'], [], 500);
    }
    if (!$existingUser) {
        admin_respond(false, 'Admin not found', [], 404);
    }

    $duplicateEmail = $db->select('SELECT id FROM users WHERE email = ? AND id <> ?', [$email, $id]);
    if (isset($duplicateEmail['error'])) {
        admin_respond(false, 'Database Error: ' . $duplicateEmail['error'], [], 500);
    }
    if (!empty($duplicateEmail)) {
        admin_respond(false, 'Email already exists', [], 409);
    }

    $updateData = [
        'name' => $name,
        'email' => $email,
        'user_role' => $userRole,
        'status' => $status,
        'package' => null, // Admins don't have packages
        'address' => $address !== '' ? $address : null,
    ];

    if ($password !== '') {
        $updateData['password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    $update = $db->update('users', $updateData, 'id = ?', [$id]);
    if (isset($update['error'])) {
        admin_respond(false, 'Database Error: ' . $update['error'], [], 500);
    }
    if (empty($update['success'])) {
        admin_respond(false, 'Failed to update admin', [], 500);
    }

    $updatedUser = admin_fetch_user_record($db, $id);
    if (isset($updatedUser['error'])) {
        admin_respond(false, 'Database Error: ' . $updatedUser['error'], [], 500);
    }

    admin_respond(true, 'Admin updated successfully', $updatedUser);
    
} catch (Exception $e) {
    admin_respond(false, 'Server Error: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 500);
}
