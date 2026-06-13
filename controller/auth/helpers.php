<?php

// JSON response helper
function customer_respond($status, $message, $data = [], $httpCode = 200)
{
    http_response_code($httpCode);

    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);

    exit;
}

// Validate role
function customer_valid_role($role)
{
    $allowed = ['super admin', 'admin', 'manager', 'customer'];
    return in_array($role, $allowed);
}

// Validate status
function customer_valid_status($status)
{
    $allowed = ['active', 'inactive'];
    return in_array($status, $allowed);
}

// Normalize package id
function customer_normalize_package_id($package)
{
    if ($package === null || $package === '') return null;
    return is_numeric($package) ? (int)$package : null;
}

// Fetch user record (basic join ready)
function customer_fetch_user_record($db, $id)
{
    $result = $db->select("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.user_role,
            u.status,
            u.address,
            u.created_at,
            u.package,
            p.name AS package_name
        FROM users u
        LEFT JOIN packages p ON u.package = p.id
        WHERE u.id = ?
        LIMIT 1
    ", [$id]);

    if (isset($result['error'])) {
        return ['error' => $result['error']];
    }

    return $result[0] ?? null;
}