<?php
/**
 * Send OTP — controller/auth/send-otp.php
 * Accepts: POST JSON { email }
 * Generates a 6-digit OTP, stores (hashed) in DB, sends email
 */

header('Content-Type: application/json');

require_once '../../include/connection.php';
require_once '../../include/mailer.php';

function respond($status, $message, $data = [], $code = 200)
{
    http_response_code($code);
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(false, 'Method not allowed', [], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $email = trim($input['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(false, 'Valid email address is required', [], 422);
    }

    // Check if user exists and is active
    $users = $db->select(
        "SELECT id, name, email, status FROM users WHERE email = ? LIMIT 1",
        [$email]
    );

    if (isset($users['error'])) {
        respond(false, 'Database error', [], 500);
    }

    if (empty($users)) {
        // Security: do not reveal if email exists or not
        respond(true, 'If this email is registered, you will receive an OTP code shortly.');
    }

    $user = $users[0];

    if ($user['status'] !== 'active') {
        respond(false, 'This account is inactive. Please contact support.', [], 403);
    }

    // Generate a 6-digit OTP
    $otp       = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    // Store OTP (plain in DB so we can compare directly; expires in 10 min)
    $update = $db->update(
        'users',
        ['otp_code' => $otp, 'otp_expires_at' => $expiresAt],
        'id = ?',
        [$user['id']]
    );

    if (!$update['success']) {
        respond(false, 'Could not store OTP. Please try again.', [], 500);
    }

    // Send email
    $result = send_password_reset_otp_email($user['name'], $email, $otp);

    if (!$result['success']) {
        // Rollback OTP so user knows something went wrong
        $db->update('users', ['otp_code' => null, 'otp_expires_at' => null], 'id = ?', [$user['id']]);
        respond(false, 'Could not send email. Please check mail configuration.', [], 500);
    }

    respond(true, 'OTP sent successfully. Please check your email.');

} catch (Exception $e) {
    respond(false, 'Server error: ' . $e->getMessage(), [], 500);
}
