<?php
require_once './include/connect.php';

$logFile = __DIR__ . '/notification_expired.log';

$sql = "SELECT u.name, p.name AS package, s.end_date, CONCAT(DATEDIFF(s.end_date, CURRENT_DATE()) , ' Days') AS left_days
FROM subscriptions s
JOIN users u on s.user_id = u.id  
JOIN packages p on s.package_id = p.id 
WHERE DATEDIFF(s.end_date, CURRENT_DATE()) BETWEEN 1 AND 2
AND s.status = 'active'";

try {

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $message = "[" . date('Y-m-d H:i:s') . "] SUCCESS - Found " . $result->num_rows . " subscription(s) expiring soon." . PHP_EOL;
    } else {
        $message = "[" . date('Y-m-d H:i:s') . "] INFO - No subscriptions expiring soon." . PHP_EOL;
    }
} catch (Exception $e) {

    $message = "[" . date('Y-m-d H:i:s') . "] ERROR - " . $e->getMessage() . PHP_EOL;
}

file_put_contents($logFile, $message, FILE_APPEND);

$conn->close();
