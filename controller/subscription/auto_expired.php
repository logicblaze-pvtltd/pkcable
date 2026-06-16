
<?php
function auto_expired()
{
    global $db;

    try {
        $today = date('Y-m-d');
        $stmt = $db->prepare("UPDATE subscriptions SET status = 'expired' WHERE end_date <= :today AND status = 'active'");
        $stmt->execute([':today' => $today]);

        respond(true, 'Expired subscriptions updated successfully');
    } catch (Exception $e) {
        respond(false, 'Error updating expired subscriptions: ' . $e->getMessage(), [], 500);
    }
}