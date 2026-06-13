<?php
header('Content-Type: application/json');

require_once '../../include/connection.php';
require_once __DIR__ . '/helpers.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        admin_respond(false, 'Method not allowed', [], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    $id = $input['id'] ?? null;

    if (empty($id) || !is_numeric($id)) {
        admin_respond(false, 'Valid admin ID is required', [], 422);
    }

    $id = (int) $id;

    $existingUser = admin_fetch_user_record($db, $id);
    if (isset($existingUser['error'])) {
        admin_respond(false, 'Database Error: ' . $existingUser['error'], [], 500);
    }
    if (!$existingUser) {
        admin_respond(false, 'Admin not found', [], 404);
    }

    $delete = $db->delete('users', 'id = ? AND user_role = ?', [$id, 'admin']);
    if (isset($delete['error'])) {
        admin_respond(false, 'Database Error: ' . $delete['error'], [], 500);
    }
    if (empty($delete['success'])) {
        admin_respond(false, 'Failed to delete admin', [], 500);
    }

    admin_respond(true, 'Admin deleted successfully', [
        'id' => $id
    ]);
} catch (Exception $e) {
    admin_respond(false, 'Server Error: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 500);
}
