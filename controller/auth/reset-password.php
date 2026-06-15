<?php
/**
 * Reset Password — controller/auth/reset-password.php
 * Accepts: POST JSON { email, password, confirm_password }
 * Re-verifies OTP is still valid before resetting the password
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

    $input          = json_decode(file_get_contents('php://input'), true) ?: [];
    $email          = trim($input['email']            ?? '');
    $password       = $input['password']               ?? '';
    $confirmPassword = $input['confirm_password']      ?? '';

    // --- Validate input ---
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(false, 'Valid email is required', [], 422);
    }

    if (strlen($password) < 8) {
        respond(false, 'Password must be at least 8 characters long', [], 422);
    }

    if ($password !== $confirmPassword) {
        respond(false, 'Passwords do not match', [], 422);
    }

    // --- Fetch user & re-verify OTP is still intact ---
    $users = $db->select(
        "SELECT id, otp_code, otp_expires_at FROM users WHERE email = ? LIMIT 1",
        [$email]
    );

    if (isset($users['error']) || empty($users)) {
        respond(false, 'Invalid request', [], 400);
    }

    $user = $users[0];

    // OTP must still exist and not be expired (guards against replaying step-3 directly)
    if (empty($user['otp_code'])) {
        respond(false, 'Session expired. Please start the reset process again.', [], 400);
    }

    if (empty($user['otp_expires_at']) || strtotime($user['otp_expires_at']) < time()) {
        $db->update('users', ['otp_code' => null, 'otp_expires_at' => null], 'id = ?', [$user['id']]);
        respond(false, 'Session expired. Please start the reset process again.', [], 410);
    }

    // --- Hash the new password ---
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // --- Update password and clear OTP ---
    $result = $db->update(
        'users',
        [
            'password'       => $hashedPassword,
            'otp_code'       => null,
            'otp_expires_at' => null,
        ],
        'id = ?',
        [$user['id']]
    );

    if (!$result['success']) {
        respond(false, 'Failed to update password. Please try again.', [], 500);
    }

    respond(true, 'Password reset successfully. You can now log in with your new password.');

} catch (Exception $e) {
    respond(false, 'Server error: ' . $e->getMessage(), [], 500);
}
