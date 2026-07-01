<?php
header('Content-Type: application/json');

require_once '../../include/connection.php';
require_once __DIR__ . '/helpers.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        customer_respond(false, 'Method not allowed', [], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];

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
    if ($packageId === '' || $packageId === null) {
        customer_respond(false, 'Valid package is required', [], 422);
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
    if (!empty($email)) {
        // check duplicate email
        $existing = $db->select('SELECT id FROM users WHERE email = ?', [$email]);
        if (isset($existing['error'])) {
            customer_respond(false, 'Database Error: ' . $existing['error'], [], 500);
        }
        if (!empty($existing)) {
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
    // validate package exists
    if ($packageId !== null) {
        $packageExists = $db->select('SELECT id FROM packages WHERE id = ?', [$packageId]);
        if (isset($packageExists['error'])) {
            customer_respond(false, 'Database Error: ' . $packageExists['error'], [], 500);
        }
        if (empty($packageExists)) {
            customer_respond(false, 'Selected package not found', [], 422);
        }
    }

    /**
     * ✅ AUTO PASSWORD GENERATION
     * If password empty → generate random secure password
     */
    $plainPassword = null;

    if ($password === '' && $email !== '') {
        $plainPassword = bin2hex(random_bytes(4)); // 8-character random password
        $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
    } else {
        $plainPassword = $password;
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    }

    $insertData = [
        'name' => $name,
        'email' => $email,
        'password' => ($email === '') ? '' : $passwordHash,
        'user_role' => $userRole,
        'status' => $status,
        'package' => $packageId,
        'address' => $address !== '' ? $address : null,
        'mobile' => $mobile,
    ];

    $insert = $db->insert('users', $insertData);

    if (isset($insert['error'])) {
        customer_respond(false, 'Database Error: ' . $insert['error'], [], 500);
    }

    if (empty($insert['success'])) {
        customer_respond(false, 'Failed to create customer', [], 500);
    }

    $createdUser = customer_fetch_user_record($db, (int) $insert['id']);

    if (isset($createdUser['error'])) {
        customer_respond(false, 'Database Error: ' . $createdUser['error'], [], 500);
    }

    customer_respond(true, 'Customer created successfully', [
        'user' => $createdUser,
        'generated_password' => $password === '' ? $plainPassword : null,
        'welcome_password' => $plainPassword,
    ]);
} catch (Exception $e) {
    customer_respond(false, 'Server Error: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 500);
}
