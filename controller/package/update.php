<?php
header('Content-Type: application/json');
include '../../include/connection.php';

function respond($status, $message, $data = [], $httpCode = 200)
{
    http_response_code($httpCode);
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ], JSON_PRETTY_PRINT);
    exit;
}

try {

    // Allow POST or PUT (flexible API)
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
        respond(false, 'Method not allowed', [], 405);
    }

    // Read JSON input
    $input = json_decode(file_get_contents("php://input"), true);

    $id    = $input['id'] ?? null;
    $name  = trim($input['name'] ?? '');
    $price = $input['price'] ?? '';

    // Validation
    if (empty($id) || !is_numeric($id)) {
        respond(false, 'Valid package ID is required', [], 422);
    }

    if (empty($name)) {
        respond(false, 'Package name is required', [], 422);
    }

    if (!is_numeric($price) || $price < 0) {
        respond(false, 'Valid price is required', [], 422);
    }

    // Check if record exists (optional but good practice)
    $check = $conn->prepare("SELECT id FROM packages WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        respond(false, 'Package not found', [], 404);
    }

    // Update query
    $stmt = $conn->prepare("UPDATE packages SET name = ?, price = ? WHERE id = ?");
    if (!$stmt) {
        respond(false, 'Prepare failed: ' . $conn->error, [], 500);
    }

    $stmt->bind_param("sdi", $name, $price, $id);

    $result = $stmt->execute();

    if (!$result) {
        respond(false, 'Database Error: ' . $stmt->error, [], 500);
    }

    respond(true, 'Package updated successfully', [
        'id' => $id,
        'name' => $name,
        'price' => $price
    ]);

} catch (Exception $e) {
    respond(false, 'Server Error: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 500);
}