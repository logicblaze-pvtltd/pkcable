<?php

function manager_respond($status, $message, $data = [], $httpCode = 200)
{
    http_response_code($httpCode);
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ], JSON_PRETTY_PRINT);
    exit;
}

function manager_valid_role($role)
{
    return $role === 'manager';
}

function manager_valid_status($status)
{
    return in_array($status, ['active', 'inactive'], true);
}

function manager_format_user_row(array $row)
{
    $id = (int)($row['id'] ?? 0);
    $createdAt = $row['created_at'] ?? '';
    $createdAtDisplay = '';

    if (!empty($createdAt)) {
        $timestamp = strtotime($createdAt);
        $createdAtDisplay = $timestamp ? date('M j, Y', $timestamp) : (string) $createdAt;
    }

    return [
        'id' => $id,
        'id_display' => '#M' . str_pad((string) $id, 3, '0', STR_PAD_LEFT),
        'name' => (string)($row['name'] ?? ''),
        'email' => (string)($row['email'] ?? ''),
        'user_role' => (string)($row['user_role'] ?? 'manager'),
        'status' => (string)($row['status'] ?? 'active'),
        'address' => (string)($row['address'] ?? ''),
        'created_at' => (string) $createdAt,
        'created_at_display' => $createdAtDisplay,
    ];
}

function manager_fetch_user_record($db, $id)
{
    $rows = $db->select(
        "SELECT u.id, u.name, u.email, u.user_role, u.status, u.address, u.created_at
         FROM users u
         WHERE u.id = ? AND u.user_role = 'manager'
         LIMIT 1",
        [$id]
    );

    if (isset($rows['error'])) {
        return ['error' => $rows['error']];
    }

    if (empty($rows)) {
        return null;
    }

    return manager_format_user_row($rows[0]);
}
