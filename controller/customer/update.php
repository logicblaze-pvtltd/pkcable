<?php
header('Content-Type: application/json');

require_once '../../include/connection.php';
require_once __DIR__ . '/helpers.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
        customer_respond(false, 'Method not allowed', [], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    $id = $input['id'] ?? null;
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');
    $userRole = trim($input['user_role'] ?? 'customer');
    $status = trim($input['status'] ?? 'active');
    $rawPackage = $input['package'] ?? null;
    $packageId = customer_normalize_package_id($rawPackage);
    $address = trim($input['address'] ?? '');
    $rawMobile = trim($input['mobile'] ?? '');
    $mobile = null;
    if (empty($id) || !is_numeric($id)) {
        customer_respond(false, 'Valid customer ID is required', [], 422);
    }

    $id = (int) $id;

    if ($name === '') {
        customer_respond(false, 'Name is required', [], 422);
    }

    // if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    //     customer_respond(false, 'Valid email is required', [], 422);
    // }
    if (!empty($rawMobile)) {
        // 1. Saare non-numeric characters (spaces, +, -, brackets) khatam karein
        $digits = preg_replace('/\D/', '', $rawMobile);

        // 2. Agar shuru mein '92' hai, toh usko remove kar dein
        if (str_starts_with($digits, '92')) {
            $digits = substr($digits, 2);
        }

        // 3. Agar shuru mein '0' hai, toh woh bhi remove kar dein (taake clean number bache)
        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        // 4. Ab shuru mein '0' laga kar standard Pakistani format bana lein
        $mobile = '0' . $digits;
    }
    if (!customer_valid_role($userRole)) {
        customer_respond(false, 'Valid user role is required', [], 422);
    }

    if (!customer_valid_status($status)) {
        customer_respond(false, 'Valid status is required', [], 422);
    }

    if ($rawPackage !== null && $rawPackage !== '' && $packageId === null) {
        customer_respond(false, 'Valid package is required', [], 422);
    }

    $existingUser = customer_fetch_user_record($db, $id);
    if (isset($existingUser['error'])) {
        customer_respond(false, 'Database Error: ' . $existingUser['error'], [], 500);
    }
    if (!$existingUser) {
        customer_respond(false, 'Customer not found', [], 404);
    }
    if (!empty($email)) {
        $duplicateEmail = $db->select('SELECT id FROM users WHERE email = ? AND id <> ?', [$email, $id]);
        if (isset($duplicateEmail['error'])) {
            customer_respond(false, 'Database Error: ' . $duplicateEmail['error'], [], 500);
        }
        if (!empty($duplicateEmail)) {
            customer_respond(false, 'Email already exists', [], 409);
        }
    }
    if (!empty($mobile)) {

        $existingMobile = $db->select(
            'SELECT id FROM users WHERE mobile = ?',
            [$mobile]
        );

        if (isset($existingMobile['error'])) {
            customer_respond(
                false,
                'Database Error: ' . $existingMobile['error'],
                [],
                500
            );
        }

        if (!empty($existingMobile)) {
            customer_respond(
                false,
                'A customer with this mobile number already exists.',
                [],
                409
            );
        }
    }
    if ($packageId !== null) {
        $packageExists = $db->select('SELECT id FROM packages WHERE id = ?', [$packageId]);
        if (isset($packageExists['error'])) {
            customer_respond(false, 'Database Error: ' . $packageExists['error'], [], 500);
        }
        if (empty($packageExists)) {
            customer_respond(false, 'Selected package not found', [], 422);
        }
    }

    $updateData = [
        'name' => $name,
        'email' => $email,
        'user_role' => $userRole,
        'status' => $status,
        'package' => $packageId,
        'address' => $address !== '' ? $address : null,
        'mobile' => $mobile,
    ];

    if ($password !== '') {
        $updateData['password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    $update = $db->update('users', $updateData, 'id = ?', [$id]);
    if (isset($update['error'])) {
        customer_respond(false, 'Database Error: ' . $update['error'], [], 500);
    }
    if (empty($update['success'])) {
        customer_respond(false, 'Failed to update customer', [], 500);
    }

    $updatedUser = customer_fetch_user_record($db, $id);
    if (isset($updatedUser['error'])) {
        customer_respond(false, 'Database Error: ' . $updatedUser['error'], [], 500);
    }

    customer_respond(true, 'Customer updated successfully', $updatedUser);
} catch (Exception $e) {
    customer_respond(false, 'Server Error: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 500);
}
