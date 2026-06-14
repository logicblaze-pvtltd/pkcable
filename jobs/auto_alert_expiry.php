<?php
require_once __DIR__ . '/../include/connection.php';
require_once __DIR__ . '/../include/mailer.php';

$logFile = __DIR__ . '/notification_expired.log';

$sql = "SELECT u.name, u.email, p.name AS package, s.end_date, DATEDIFF(s.end_date, CURRENT_DATE()) AS left_days
FROM subscriptions s
JOIN users u on s.user_id = u.id  
JOIN packages p on s.package_id = p.id 
WHERE DATEDIFF(s.end_date, CURRENT_DATE()) BETWEEN 1 AND 2
AND s.status = 'active'
LIMIT 20";

try {
    $result = $conn->query($sql);
    $sentCount = 0;
    $errors = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['email'])) {
                $emailResult = send_expiry_alert_email(
                    (string) $row['name'],
                    (string) $row['email'],
                    (string) $row['package'],
                    (int) $row['left_days'],
                    (string) $row['end_date']
                );
                if (!empty($emailResult['success'])) {
                    $sentCount++;
                } else {
                    $errors[] = "Failed sending to " . $row['email'] . ": " . ($emailResult['error'] ?? 'Unknown error');
                }
            }
        }
        $message = "[" . date('Y-m-d H:i:s') . "] SUCCESS - Expiring subscriptions checked. Sent " . $sentCount . " email(s). " . (count($errors) > 0 ? "Errors: " . implode(', ', $errors) : "") . PHP_EOL;
    } else {
        $message = "[" . date('Y-m-d H:i:s') . "] INFO - No subscriptions expiring soon." . PHP_EOL;
    }
} catch (Exception $e) {
    $message = "[" . date('Y-m-d H:i:s') . "] ERROR - " . $e->getMessage() . PHP_EOL;
}

file_put_contents($logFile, $message, FILE_APPEND);

$conn->close();

