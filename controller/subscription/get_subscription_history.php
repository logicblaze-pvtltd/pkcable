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

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(false, 'Method not allowed', [], 405);
    }

    // Get query params
    $user_id = $_GET['user_id'] ?? null;
    $status  = $_GET['status'] ?? null;
    $month   = $_GET['month'] ?? null; // format: 2025-06

    // Validation
    if (empty($user_id) || !is_numeric($user_id)) {
        respond(false, 'Valid user_id is required', [], 422);
    }

    // Check user exists
    $checkUser = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $checkUser->bind_param("i", $user_id);
    $checkUser->execute();
    $checkUser->store_result();

    if ($checkUser->num_rows === 0) {
        respond(false, 'User not found', [], 404);
    }

    // Base query
    $query = "
        SELECT
            s.id,
            s.package_id,
            p.name AS package_name,
            p.price AS package_price,
            u.name AS user_name,
            u.email,
            s.discount,
            (p.price - s.discount) AS paid_amount,
            DATE_FORMAT(s.start_date, '%d-%b-%Y') AS start_date,
            DATE_FORMAT(s.end_date, '%d-%b-%Y') AS end_date,
            DATE_FORMAT(s.start_date, '%M %Y') AS package_month,
            s.status,
            s.created_at
        FROM subscriptions s
        JOIN users u ON s.user_id = u.id
        JOIN packages p ON s.package_id = p.id
        WHERE s.user_id = ?
    ";

    $params = [$user_id];
    $types  = "i";

    // Optional filters
    if (!empty($status)) {
        $query .= " AND s.status = ?";
        $params[] = $status;
        $types .= "s";
    }

    if (!empty($month)) {
        $query .= " AND DATE_FORMAT(s.start_date, '%Y-%m') = ?";
        $params[] = $month;
        $types .= "s";
    }

    $query .= " ORDER BY s.start_date DESC";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        respond(false, 'Prepare failed: ' . $conn->error, [], 500);
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    $result = $stmt->get_result();
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    if (empty($data)) {
        respond(true, 'No subscription history found', []);
    }

    respond(true, 'Subscription history fetched successfully', $data);

} catch (Exception $e) {
    respond(false, 'Server Error: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 500);
}