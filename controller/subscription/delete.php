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

    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(false, 'Method not allowed', [], 405);
    }

    $input = json_decode(file_get_contents("php://input"), true);

    $id = $input['id'] ?? null;

    if (empty($id) || !is_numeric($id)) {
        respond(false, 'Valid subscription ID is required', [], 422);
    }

    // Check if subscription exists
    $check = $conn->prepare("SELECT id FROM subscriptions WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        respond(false, 'Subscription not found', [], 404);
    }

    // Delete record
    $stmt = $conn->prepare("DELETE FROM subscriptions WHERE id = ?");
    if (!$stmt) {
        respond(false, 'Prepare failed: ' . $conn->error, [], 500);
    }

    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        respond(false, 'Database Error: ' . $stmt->error, [], 500);
    }

    respond(true, 'Subscription deleted successfully', ['id' => $id]);

} catch (Exception $e) {
    respond(false, 'Server Error: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 500);
}
