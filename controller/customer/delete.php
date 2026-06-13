<?php
header('Content-Type: application/json');

require_once '../../include/connection.php';
require_once __DIR__ . '/helpers.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        customer_respond(false, 'Method not allowed', [], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    $id = $input['id'] ?? null;

    if (empty($id) || !is_numeric($id)) {
        customer_respond(false, 'Valid customer ID is required', [], 422);
    }

    $id = (int) $id;

    $existingUser = customer_fetch_user_record($db, $id);
    if (isset($existingUser['error'])) {
        customer_respond(false, 'Database Error: ' . $existingUser['error'], [], 500);
    }
    if (!$existingUser) {
        customer_respond(false, 'Customer not found', [], 404);
    }

    $delete = $db->delete('users', 'id = ?', [$id]);
    if (isset($delete['error'])) {
        customer_respond(false, 'Database Error: ' . $delete['error'], [], 500);
    }
    if (empty($delete['success'])) {
        customer_respond(false, 'Failed to delete customer', [], 500);
    }

    customer_respond(true, 'Customer deleted successfully', [
        'id' => $id
    ]);
} catch (Exception $e) {
    customer_respond(false, 'Server Error: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 500);
}
