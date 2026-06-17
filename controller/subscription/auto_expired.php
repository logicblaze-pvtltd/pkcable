
<?php
function auto_expired()
{
    global $db;

    try {
        $today = date('Y-m-d');
        $stmt = $db->update('subscriptions', ['status' => 'expired'], "end_date <= ? AND status = ?", [$today, 'active']);

        // respond(true, 'Expired subscriptions updated successfully');
    } catch (Exception $e) {
        respond(false, 'Error updating expired subscriptions: ' . $e->getMessage(), [], 500);
    }
}