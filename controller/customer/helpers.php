<?php

function customer_respond($status, $message, $data = [], $httpCode = 200)
{
    http_response_code($httpCode);
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ], JSON_PRETTY_PRINT);
    exit;
}

function customer_valid_role($role)
{
    return in_array($role, ['super admin', 'admin', 'manager', 'customer'], true);
}

function customer_valid_status($status)
{
    return in_array($status, ['active', 'inactive'], true);
}

function customer_normalize_package_id($value)
{
    if ($value === null) {
        return null;
    }

    if (is_string($value) && trim($value) === '') {
        return null;
    }

    if (!is_numeric($value)) {
        return null;
    }

    $packageId = (int) $value;

    return $packageId > 0 ? $packageId : null;
}

function customer_format_user_row(array $row)
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
        'id_display' => '#C' . str_pad((string) $id, 3, '0', STR_PAD_LEFT),
        'name' => (string)($row['name'] ?? ''),
        'email' => (string)($row['email'] ?? ''),
        'user_role' => (string)($row['user_role'] ?? 'customer'),
        'status' => (string)($row['status'] ?? 'active'),
        'package_id' => isset($row['package']) && $row['package'] !== null ? (int) $row['package'] : null,
        'package_name' => (string)($row['package_name'] ?? ''),
        'address' => (string)($row['address'] ?? ''),
        'created_at' => (string) $createdAt,
        'created_at_display' => $createdAtDisplay,
    ];
}

function customer_fetch_user_record($db, $id)
{
    $rows = $db->select(
        "SELECT u.id, u.name, u.email, u.user_role, u.status, u.package, u.address, u.created_at, p.name AS package_name
         FROM users u
         LEFT JOIN packages p ON u.package = p.id
         WHERE u.id = ?
         LIMIT 1",
        [$id]
    );

    if (isset($rows['error'])) {
        return ['error' => $rows['error']];
    }

    if (empty($rows)) {
        return null;
    }

    return customer_format_user_row($rows[0]);
}
