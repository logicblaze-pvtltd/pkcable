<?php
header('Content-Type: application/json');

require_once '../../include/connection.php';
require_once __DIR__ . '/helpers.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        manager_respond(false, 'Method not allowed', [], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    $id = $input['id'] ?? null;

    if (empty($id) || !is_numeric($id)) {
        manager_respond(false, 'Valid manager ID is required', [], 422);
    }

    $id = (int) $id;

    $existingUser = manager_fetch_user_record($db, $id);
    if (isset($existingUser['error'])) {
        manager_respond(false, 'Database Error: ' . $existingUser['error'], [], 500);
    }
    if (!$existingUser) {
        manager_respond(false, 'Manager not found', [], 404);
    }

    $delete = $db->delete('users', 'id = ? AND user_role = ?', [$id, 'manager']);
    if (isset($delete['error'])) {
        manager_respond(false, 'Database Error: ' . $delete['error'], [], 500);
    }
    if (empty($delete['success'])) {
        manager_respond(false, 'Failed to delete manager', [], 500);
    }

    manager_respond(true, 'Manager deleted successfully', [
        'id' => $id
    ]);
} catch (Exception $e) {
    manager_respond(false, 'Server Error: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 500);
}
