<?php
header('Content-Type: application/json');

session_start();

try {
    session_unset();
    session_destroy();
    header("Location: ../../login.php");

} catch (Exception $e) {
    customer_respond(false, 'Server Error: ' . $e->getMessage(), [], 500);
}