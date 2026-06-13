<?php
header('Content-Type: application/json');
include '../../include/connection.php';

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

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
        respond(false, 'Method not allowed', [], 405);
    }

    $input = json_decode(file_get_contents("php://input"), true);

    $id         = $input['id']         ?? null;
    $user_id    = $input['user_id']    ?? null;
    $package_id = $input['package_id'] ?? null;
    $discount   = $input['discount']   ?? 0;
    $start_date = trim($input['start_date'] ?? '');
    $end_date   = trim($input['end_date']   ?? '');
    $status     = trim($input['status']     ?? 'active');

    // Validation
    if (empty($id) || !is_numeric($id)) {
        respond(false, 'Valid subscription ID is required', [], 422);
    }

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

    // Check subscription exists
    $check = $conn->prepare("SELECT id FROM subscriptions WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows === 0) {
        respond(false, 'Subscription not found', [], 404);
    }

    // Update subscription
    $stmt = $conn->prepare("UPDATE subscriptions SET user_id = ?, package_id = ?, discount = ?, start_date = ?, end_date = ?, status = ? WHERE id = ?");
    if (!$stmt) {
        respond(false, 'Prepare failed: ' . $conn->error, [], 500);
    }

    $stmt->bind_param("iidsssi", $user_id, $package_id, $discount, $start_date, $end_date, $status, $id);

    if (!$stmt->execute()) {
        respond(false, 'Database Error: ' . $stmt->error, [], 500);
    }

    // Fetch updated subscription
    $fetch = $conn->prepare("
        SELECT
            s.id,
            u.name AS name,
            p.name AS package_name,
            p.price AS package_price,
            s.discount,
            (p.price - s.discount) AS paid_amount,
            DATE_FORMAT(s.start_date, '%d-%b-%Y') AS start_date,
            DATE_FORMAT(s.end_date, '%d-%b-%Y') AS end_date,
            DATE_FORMAT(s.start_date, '%M %Y') AS package_month,
            s.status
        FROM subscriptions s
        JOIN users u ON s.user_id = u.id
        JOIN packages p ON s.package_id = p.id
        WHERE s.id = ?
    ");
    $fetch->bind_param("i", $id);
    $fetch->execute();
    $result = $fetch->get_result();
    $row = $result->fetch_assoc();

    respond(true, 'Subscription updated successfully', $row ?? ['id' => $id]);

} catch (Exception $e) {
    respond(false, 'Server Error: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 500);
}
