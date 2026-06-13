<?php
header('Content-Type: application/json');

require_once '../../include/connection.php';
require_once __DIR__ . '/helpers.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
        manager_respond(false, 'Method not allowed', [], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    $id = $input['id'] ?? null;
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');
    $userRole = trim($input['user_role'] ?? 'manager');
    $status = trim($input['status'] ?? 'active');
    $address = trim($input['address'] ?? '');

    if (empty($id) || !is_numeric($id)) {
        manager_respond(false, 'Valid manager ID is required', [], 422);
    }

    $id = (int) $id;

    if ($name === '') {
        manager_respond(false, 'Name is required', [], 422);
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        manager_respond(false, 'Valid email is required', [], 422);
    }

    if (!manager_valid_role($userRole)) {
        manager_respond(false, 'Valid user role is required', [], 422);
    }

    if (!manager_valid_status($status)) {
        manager_respond(false, 'Valid status is required', [], 422);
    }

    $existingUser = manager_fetch_user_record($db, $id);
    if (isset($existingUser['error'])) {
        manager_respond(false, 'Database Error: ' . $existingUser['error'], [], 500);
    }
    if (!$existingUser) {
        manager_respond(false, 'Manager not found', [], 404);
    }

    $duplicateEmail = $db->select('SELECT id FROM users WHERE email = ? AND id <> ?', [$email, $id]);
    if (isset($duplicateEmail['error'])) {
        manager_respond(false, 'Database Error: ' . $duplicateEmail['error'], [], 500);
    }
    if (!empty($duplicateEmail)) {
        manager_respond(false, 'Email already exists', [], 409);
    }

    $updateData = [
        'name' => $name,
        'email' => $email,
        'user_role' => $userRole,
        'status' => $status,
        'package' => null, // Managers don't have packages
        'address' => $address !== '' ? $address : null,
    ];

    if ($password !== '') {
        $updateData['password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    $update = $db->update('users', $updateData, 'id = ?', [$id]);
    if (isset($update['error'])) {
        manager_respond(false, 'Database Error: ' . $update['error'], [], 500);
    }
    if (empty($update['success'])) {
        manager_respond(false, 'Failed to update manager', [], 500);
    }

    $updatedUser = manager_fetch_user_record($db, $id);
    if (isset($updatedUser['error'])) {
        manager_respond(false, 'Database Error: ' . $updatedUser['error'], [], 500);
    }

    manager_respond(true, 'Manager updated successfully', $updatedUser);
} catch (Exception $e) {
    manager_respond(false, 'Server Error: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 500);
}
