<?php
require_once __DIR__ . '/../include/connection.php';

$logFile = __DIR__ . '/subscription_expired.log';

$sql = "UPDATE subscriptions s 
        SET s.status = 'expired' 
        WHERE s.end_date = CURDATE() 
        AND s.status = 'active'";

try {

    if ($conn->query($sql) === TRUE) {

        $affectedRows = $conn->affected_rows;

        $message = "[" . date('Y-m-d H:i:s') . "] SUCCESS - {$affectedRows} subscription(s) updated to expired." . PHP_EOL;

    } else {

        $message = "[" . date('Y-m-d H:i:s') . "] ERROR - " . $conn->error . PHP_EOL;

    }

} catch (Exception $e) {

    $message = "[" . date('Y-m-d H:i:s') . "] ERROR - " . $e->getMessage() . PHP_EOL;

}

file_put_contents($logFile, $message, FILE_APPEND);

$conn->close();
?>