<?php
header('Content-Type: application/json');

require_once '../../include/connection.php';
require_once '../../include/mailer.php';
require_once __DIR__ . '/helpers.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        customer_respond(false, 'Method not allowed', [], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    $userId = $input['user_id'] ?? null;
    $welcomePassword = trim($input['welcome_password'] ?? '');
    $packageId = customer_normalize_package_id($input['package_id'] ?? null);

    if (empty($userId) || !is_numeric($userId)) {
        customer_respond(false, 'Valid customer is required', [], 422);
    }

    if ($welcomePassword === '') {
        customer_respond(false, 'Welcome password is required', [], 422);
    }

    $userRows = $db->select(
        'SELECT u.id, u.name, u.email, u.package, p.name AS package_name, p.price AS package_price
         FROM users u
         LEFT JOIN packages p ON u.package = p.id
         WHERE u.id = ?
         LIMIT 1',
        [(int) $userId]
    );

    if (isset($userRows['error'])) {
        customer_respond(false, 'Database Error: ' . $userRows['error'], [], 500);
    }

    if (empty($userRows)) {
        customer_respond(false, 'Customer not found', [], 404);
    }

    $user = $userRows[0];

    if (empty($user['email'])) {
        customer_respond(false, 'Customer email is missing', [], 422);
    }

    $resolvedPackageId = $packageId ?? customer_normalize_package_id($user['package'] ?? null);
    $packageName = (string) ($user['package_name'] ?? '');
    $packagePrice = $user['package_price'] ?? 0;

    if ($resolvedPackageId !== null && ($packageName === '' || (string) $resolvedPackageId !== (string) ($user['package'] ?? ''))) {
        $packageRows = $db->select(
            'SELECT name, price FROM packages WHERE id = ? LIMIT 1',
            [$resolvedPackageId]
        );

        if (!isset($packageRows['error']) && !empty($packageRows)) {
            $packageName = (string) ($packageRows[0]['name'] ?? '');
            $packagePrice = $packageRows[0]['price'] ?? 0;
        }
    }

    $emailResult = send_customer_welcome_email(
        (string) ($user['name'] ?? ''),
        (string) $user['email'],
        $welcomePassword,
        [
            'package_name' => $packageName,
            'package_price' => $packagePrice,
            'discount' => 0,
            'paid_amount' => $packagePrice,
        ]
    );

    if (empty($emailResult['success'])) {
        customer_respond(false, 'Welcome email could not be sent', [
            'email_sent' => false,
            'email_error' => $emailResult['error'] ?? null,
        ], 500);
    }

    customer_respond(true, 'Welcome email sent successfully', [
        'email_sent' => true,
        'email_error' => null,
    ]);
} catch (Exception $e) {
    customer_respond(false, 'Server Error: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ], 500);
}
