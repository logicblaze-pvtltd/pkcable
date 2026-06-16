<?php
/**
 * Verify OTP — controller/auth/verify-otp.php
 * Accepts: POST JSON { email, otp }
 */

header('Content-Type: application/json');

require_once '../../include/connection.php';

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
    $otp   = trim($input['otp']   ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(false, 'Valid email is required', [], 422);
    }

    if ($otp === '' || !preg_match('/^\d{6}$/', $otp)) {
        respond(false, 'A valid 6-digit code is required', [], 422);
    }

    // Fetch user with OTP fields
    $users = $db->select(
        "SELECT id, otp_code, otp_expires_at FROM users WHERE email = ? LIMIT 1",
        [$email]
    );

    if (isset($users['error']) || empty($users)) {
        respond(false, 'Invalid request', [], 400);
    }

    $user = $users[0];

    // Check OTP exists
    if (empty($user['otp_code'])) {
        respond(false, 'No OTP was requested for this email. Please request a new code.', [], 400);
    }

    // Check expiry
    if (empty($user['otp_expires_at']) || strtotime($user['otp_expires_at']) < time()) {
        // Clear expired OTP
        $db->update('users', ['otp_code' => null, 'otp_expires_at' => null], 'id = ?', [$user['id']]);
        respond(false, 'This code has expired. Please request a new one.', [], 410);
    }

    // Compare OTP
    if ($otp !== $user['otp_code']) {
        respond(false, 'Incorrect code. Please double-check and try again.', [], 401);
    }

    // OTP is valid — do NOT clear it yet, reset-password.php will verify email again
    // Just mark it as verified by keeping the same OTP (reset-password.php will clear it)
    respond(true, 'Code verified successfully');

} catch (Exception $e) {
    respond(false, 'Server error: ' . $e->getMessage(), [], 500);
}
