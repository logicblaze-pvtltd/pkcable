<?php
header('Content-Type: application/json');
include '../../include/connection.php';
require_once '../../include/mailer.php';

function respond($status, $message, $data = [], $httpCode = 200)
{
    http_response_code($httpCode);
    echo json_encode([
        'status'  => $status,
        'message' => $message,
        'data'    => $data
    ], JSON_PRETTY_PRINT);
    exit;
}

try {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(false, 'Method not allowed', [], 405);
    }

    $input = json_decode(file_get_contents("php://input"), true);

    $user_id    = $input['user_id']    ?? null;
    $package_id = $input['package_id'] ?? null;
    $package_price = $input['package_price'] ?? 0;
    $discount   = $input['discount']   ?? 0;
    $start_date = trim($input['start_date'] ?? '');
    $end_date   = trim($input['end_date']   ?? '');
    $status     = trim($input['status']     ?? 'active');
    $sendWelcomeEmail = !empty($input['send_welcome_email']);
    $welcomePassword = trim($input['welcome_password'] ?? '');

    // Validation
    if (empty($user_id) || !is_numeric($user_id)) {
        respond(false, 'Valid customer is required', [], 422);
    }

    if (empty($package_id) || !is_numeric($package_id)) {
        respond(false, 'Valid package is required', [], 422);
    }

    if (!is_numeric($discount) || $discount < 0) {
        respond(false, 'Valid discount amount is required', [], 422);
    }

    if (empty($start_date)) {
        respond(false, 'Start date is required', [], 422);
    }

    if (empty($end_date)) {
        respond(false, 'End date is required', [], 422);
    }

    if (strtotime($end_date) < strtotime($start_date)) {
        respond(false, 'End date cannot be before start date', [], 422);
    }

    $allowed_statuses = ['active', 'expired', 'cancelled'];
    if (!in_array($status, $allowed_statuses)) {
        respond(false, 'Invalid status value', [], 422);
    }

    // Check if user exists
    $checkUser = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $checkUser->bind_param("i", $user_id);
    $checkUser->execute();
    $checkUser->store_result();
    if ($checkUser->num_rows === 0) {
        respond(false, 'Customer not found', [], 404);
    }

    // Check if package exists
    $checkPkg = $conn->prepare("SELECT id FROM packages WHERE id = ?");
    $checkPkg->bind_param("i", $package_id);
    $checkPkg->execute();
    $checkPkg->store_result();
    if ($checkPkg->num_rows === 0) {
        respond(false, 'Package not found', [], 404);
    }
    // Check duplicate active subscription in same month
    $monthCheck = $conn->prepare("
    SELECT id 
    FROM subscriptions 
    WHERE user_id = ? 
      AND package_id = ? 
      AND status = 'active'
      AND DATE_FORMAT(start_date, '%Y-%m') = DATE_FORMAT(?, '%Y-%m')
    LIMIT 1
");

    $monthCheck->bind_param("iis", $user_id, $package_id, $start_date);
    $monthCheck->execute();
    $monthCheck->store_result();

    if ($monthCheck->num_rows > 0) {
        respond(false, 'Subscription already active for this month.', [], 409);
    }
    // Insert subscription
    $stmt = $conn->prepare("INSERT INTO subscriptions (user_id, package_id, discount, package_price, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        respond(false, 'Prepare failed: ' . $conn->error, [], 500);
    }

    $stmt->bind_param("iidisss", $user_id, $package_id, $discount, $package_price, $start_date, $end_date, $status);

    if (!$stmt->execute()) {
        respond(false, 'Database Error: ' . $stmt->error, [], 500);
    }

    $new_id = $stmt->insert_id;

    $updateUser = $conn->prepare("UPDATE users SET package = ?, status = 'active' WHERE id = ?");
    if ($updateUser) {
        $updateUser->bind_param("ii", $package_id, $user_id);
        $updateUser->execute();
    }

    // Fetch the full subscription to return
    $fetch = $conn->prepare("
        SELECT
            s.id,
            s.package_id,
            u.name AS name,
            u.email AS email,
            p.name AS package_name,
            s.package_price AS package_price,
            s.discount,
            (s.package_price - s.discount) AS paid_amount,
            DATE_FORMAT(s.start_date, '%d-%b-%Y') AS start_date,
            DATE_FORMAT(s.end_date, '%d-%b-%Y') AS end_date,
            DATE_FORMAT(s.start_date, '%M %Y') AS package_month,
            s.status
        FROM subscriptions s
        JOIN users u ON s.user_id = u.id
        JOIN packages p ON s.package_id = p.id
        WHERE s.id = ?
    ");
    $fetch->bind_param("i", $new_id);
    $fetch->execute();
    $result = $fetch->get_result();
    $row = $result->fetch_assoc() ?: ['id' => $new_id];

    $emailSent = false;
    $emailError = null;
    $responseMessage = 'Subscription created successfully';

    if ($sendWelcomeEmail && $welcomePassword !== '') {
        $userFetch = $conn->prepare("SELECT name, email FROM users WHERE id = ? LIMIT 1");
        if ($userFetch) {
            $userFetch->bind_param('i', $user_id);
            $userFetch->execute();
            $userResult = $userFetch->get_result();
            $userRow = $userResult ? $userResult->fetch_assoc() : null;

            if (!empty($userRow['email'])) {
                $emailResult = send_customer_welcome_email(
                    (string) ($userRow['name'] ?? ''),
                    (string) $userRow['email'],
                    $welcomePassword,
                    [
                        'package_name' => $row['package_name'] ?? '',
                        'package_price' => $row['package_price'] ?? 0,
                        'discount' => $row['discount'] ?? 0,
                        'paid_amount' => $row['paid_amount'] ?? null,
                        'start_date' => $row['start_date'] ?? '',
                        'end_date' => $row['end_date'] ?? '',
                    ]
                );

                $emailSent = !empty($emailResult['success']);
                $emailError = $emailResult['error'] ?? null;

                if (!$emailSent) {
                    $responseMessage = 'Subscription created successfully, but welcome email could not be sent';
                }
            }
        }
    } else {
        if (!empty($row['email'])) {
            $emailResult = send_subscription_notification_email(
                (string) ($row['name'] ?? ''),
                (string) $row['email'],
                'created',
                [
                    'package_name' => $row['package_name'] ?? '',
                    'package_price' => $row['package_price'] ?? 0,
                    'discount' => $row['discount'] ?? 0,
                    'paid_amount' => $row['paid_amount'] ?? null,
                    'start_date' => $row['start_date'] ?? '',
                    'end_date' => $row['end_date'] ?? '',
                ]
            );

            $emailSent = !empty($emailResult['success']);
            $emailError = $emailResult['error'] ?? null;

            if (!$emailSent) {
                $responseMessage = 'Subscription created successfully, but notification email could not be sent';
            }
        }
    }

    $row['email_sent'] = $emailSent;
    $row['email_error'] = $emailError;

    respond(true, $responseMessage, $row);
} catch (Exception $e) {
    respond(false, 'Server Error: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 500);
}
