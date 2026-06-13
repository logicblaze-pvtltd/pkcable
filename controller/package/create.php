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

    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(false, 'Method not allowed', [], 405);
    }

    // Read input (JSON or form-data support)
    $input = json_decode(file_get_contents("php://input"), true);

    $name  = trim($input['name'] ?? '');
    $price = $input['price'] ?? '';

    // Validation
    if (empty($name)) {
        respond(false, 'Package name is required', [], 422);
    }

    if (!is_numeric($price) || $price < 0) {
        respond(false, 'Valid price is required', [], 422);
    }

   // Insert query (correct mysqli way)
$stmt = $conn->prepare("INSERT INTO packages (name, price) VALUES (?, ?)");

if (!$stmt) {
    respond(false, 'Prepare failed: ' . $conn->error, [], 500);
}

$stmt->bind_param("sd", $name, $price); 
// s = string (name), d = double/decimal (price)

$result = $stmt->execute();

if (!$result) {
    respond(false, 'Database Error: ' . $stmt->error, [], 500);
}

respond(true, 'Package created successfully', [
    'id' => $stmt->insert_id,
    'name' => $name,
    'price' => $price
]);

} catch (Exception $e) {
    respond(false, 'Server Error: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 500);
}