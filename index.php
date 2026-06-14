<?php
session_start();
require_once './include/connection.php';
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once './include/connection.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="./assets/favicon_io/favicon.ico">
    <?php $appName = get_env_value('APP_NAME') ?: 'Pakistan Cable'; ?>
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> - Dashboard</title>
    <!-- header links -->
    <?php include "./include/headerLinks.php" ?>
    <link rel="stylesheet" href="assets/css/datePicker.css">
</head>

<body class="bg-[#f3f4f4] text-gray-800 dark:text-gray-200 no-transition" style="overflow-x:hidden">
    <div class="flex flex-col min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 dark:from-slate-900 dark:to-slate-800 overflow-hidden">

        <!-- Overlay for mobile sidebar -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black opacity-0 z-40 hidden lg:hidden transition-opacity duration-300"></div>

        <!-- Sidebar -->
        <?php include "./include/sidebar.php" ?>

        <!-- Main Content Wrapper -->
        <div id="main-content-wrapper" class="flex-1 flex flex-col w-full">

            <!-- Header -->
            <?php include "./include/header.php" ?>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto py-3 px-8 w-full min-h-screen">
                <!-- Breadcrumbs -->
                <?php include "./include/breadcrumbs.php";
                if ($_SESSION['user']['role'] !== 'customer') {
                ?>

                    <!-- Dashboard Cards -->
                    <div class="mb-4 grid lg:grid-cols-4 gap-4 animate-fade-in-up">
                        <?php
                        $current = $conn->query("
                            SELECT COUNT(*) AS total
                            FROM users
                            WHERE status = 'active'
                            AND user_role = 'customer'
                            ")->fetch_assoc()['total'];

                        $lastMonth = $conn->query("
                            SELECT COUNT(*) AS total
                            FROM users
                            WHERE status = 'active'
                            AND created_at < DATE_FORMAT(CURDATE(), '%Y-%m-01')
                            ")->fetch_assoc()['total'];

                        $percentage = 0;

                        if ($lastMonth > 0) {
                            $percentage = round((($current - $lastMonth) / $lastMonth) * 100);
                        }
                        ?>
                        <!-- Stat Card 1: Total Users -->
                        <a href="customers.php?status=active" class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-md hover:shadow-xl transition-all duration-300">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/10 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-700"></div>
                            <div class="px-4 py-2">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="p-3 rounded-xl bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                            </path>
                                        </svg>
                                    </div>

                                    <span class="text-xs font-medium <?= $percentage >= 0 ? 'text-green-600 bg-green-50' : 'text-red-600 bg-red-50' ?> px-2 py-1 rounded-full">
                                        <?= ($percentage >= 0 ? '+' : '') . $percentage ?>%
                                    </span>
                                </div>

                                <h3 class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">
                                    Active Customers
                                </h3>

                                <p class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                                    <?= number_format($current) ?>
                                </p>

                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Compared to <?= number_format($lastMonth) ?> previous customers
                                </p>
                            </div>
                        </a>
                        <?php
                        $inactiveUsers = $conn->query("
                            SELECT COUNT(*) AS total
                            FROM users
                            WHERE status = 'inactive'
                            AND user_role = 'customer'
                        ")->fetch_assoc()['total'];
                        ?>
                        <a href="customers.php?status=inactive" class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-md hover:shadow-xl transition-all duration-300">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-red-500/10 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-700"></div>

                            <div class="px-4 py-2">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="p-3 rounded-xl bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                            </path>
                                        </svg>
                                    </div>

                                    <span class="text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-500/10 px-2 py-1 rounded-full">
                                        Inactive
                                    </span>
                                </div>

                                <h3 class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">
                                    Inactive Customers
                                </h3>

                                <p class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                                    <?= number_format($inactiveUsers) ?>
                                </p>

                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Currently inactive customer accounts
                                </p>
                            </div>
                        </a>
                        <?php
                        $earnings = $conn->query("
                                SELECT 
                                    COALESCE(SUM(p.price - IFNULL(s.discount, 0)), 0) AS total_earnings
                                FROM subscriptions s
                                JOIN packages p ON s.package_id = p.id
                                WHERE MONTH(s.created_at) = MONTH(CURDATE())
                                AND YEAR(s.created_at) = YEAR(CURDATE())
                                AND s.status != 'cancelled'
                            ")->fetch_assoc();

                        $totalEarnings = $earnings['total_earnings'];
                        ?>
                        <a href="subscriptions.php?month=<?= date('Y-m') ?>" class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-md hover:shadow-xl transition-all duration-300">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-purple-500/10 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-700"></div>

                            <div class="px-4 py-2">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="p-3 rounded-xl bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-wallet-icon lucide-wallet">
                                            <path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1" />
                                            <path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4" />
                                        </svg>
                                    </div>

                                    <span class="text-xs font-medium text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-500/10 px-2 py-1 rounded-full">
                                        Revenue
                                    </span>
                                </div>

                                <h3 class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">
                                    Total Earnings
                                </h3>

                                <p class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                                    Rs. <?= number_format($totalEarnings, 0) ?>
                                </p>

                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    This month
                                </p>
                            </div>
                        </a>
                        <?php
                        $pending = $conn->query("
                            SELECT COALESCE(SUM(p.price), 0) AS pending_amount
                            FROM users u
                            JOIN packages p ON u.package = p.id
                            WHERE u.user_role = 'customer'
                            AND u.status = 'active'
                            AND NOT EXISTS (
                                    SELECT 1
                                    FROM subscriptions s
                                    WHERE s.user_id = u.id
                                    AND MONTH(s.start_date) = MONTH(CURDATE())
                                    AND YEAR(s.start_date) = YEAR(CURDATE())
                            )
                        ")->fetch_assoc();

                        $pendingAmount = $pending['pending_amount'];
                        ?>
                        <div class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-md hover:shadow-xl transition-all duration-300">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-yellow-500/10 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-700"></div>
                            <div class="px-4 py-2">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="p-3 rounded-xl bg-yellow-50 dark:bg-yellow-500/10 text-yellow-600 dark:text-yellow-400">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                        </svg>
                                    </div>
                                    <span class="text-xs font-medium text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-500/10 px-2 py-1 rounded-full">Pending Payments</span>
                                </div>
                                <h3 class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">Pending Ammount</h3>
                                <p class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                                    Rs. <?= number_format($pendingAmount) ?>
                                </p>

                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Pending for this month
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content Card -->
                    <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden animate-fade-in-up" style="animation-delay: 0.1s;">
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Subscription Alerts</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Active customers with subscriptions expiring soon, already expired, or not assigned yet</p>
                        </div>
                        <?php
                        $subscriptionAlerts = $conn->query("
                    SELECT
                        u.id AS user_id,
                        u.name AS user_name,
                        u.status AS user_status,
                        u.package AS user_package_id,
                        u.address AS address,
                        s.id AS subscription_id,
                        -- Package ID logic: prefer user's package, fallback to subscription's package
                        COALESCE(u.package, s.package_id) AS package_id,
                        COALESCE(up.name, sp.name) AS package_name,
                        COALESCE(up.price, sp.price) AS package_price,
                        -- Discount only applies if user's package matches subscription's package
                        CASE 
                            WHEN s.id IS NOT NULL AND u.package = s.package_id THEN COALESCE(s.discount, 0)
                            ELSE 0
                        END AS discount,
                        DATE_FORMAT(s.start_date, '%d-%b-%Y') AS start_date_label,
                        DATE_FORMAT(s.end_date, '%d-%b-%Y') AS end_date_label,
                        DATE_FORMAT(s.end_date, '%Y-%m-%d') AS end_date_raw,
                        CASE
                            WHEN s.id IS NULL THEN CURDATE()
                            WHEN s.end_date >= CURDATE() THEN DATE_ADD(s.end_date, INTERVAL 1 DAY)
                            ELSE CURDATE()
                        END AS renewal_start_raw,
                        DATE_FORMAT(
                            DATE_ADD(
                                CASE
                                    WHEN s.id IS NULL THEN CURDATE()
                                    WHEN s.end_date >= CURDATE() THEN DATE_ADD(s.end_date, INTERVAL 1 DAY)
                                    ELSE CURDATE()
                                END,
                                INTERVAL 30 DAY
                            ),
                            '%Y-%m-%d'
                        ) AS renewal_end_raw,
                        CASE
                            WHEN s.id IS NULL THEN NULL
                            ELSE DATEDIFF(s.end_date, CURDATE())
                        END AS days_left,
                        CASE
                            WHEN s.id IS NULL THEN 1
                            ELSE 0
                        END AS has_no_subscription
                    FROM users u
                    LEFT JOIN subscriptions s
                        ON s.id = (
                            SELECT s2.id
                            FROM subscriptions s2
                            WHERE s2.user_id = u.id
                            ORDER BY s2.end_date DESC, s2.id DESC
                            LIMIT 1
                        )
                    LEFT JOIN packages sp ON sp.id = s.package_id
                    LEFT JOIN packages up ON up.id = u.package
                    WHERE u.user_role = 'customer'
                    AND u.status = 'active'
                    AND (
                            s.id IS NULL
                            OR DATEDIFF(s.end_date, CURDATE()) <= 2
                    )
                    ORDER BY
                        CASE WHEN s.id IS NULL THEN 0 ELSE 1 END,
                        s.end_date ASC,
                        u.name ASC");

                        if (!$subscriptionAlerts) {
                            $subscriptionAlerts = false;
                        }

                        function alertBadgeClasses($daysLeft, $hasNoSubscription = false)
                        {
                            if ($hasNoSubscription) return 'bg-slate-100 text-slate-700 dark:bg-slate-700/60 dark:text-slate-300';
                            if ($daysLeft < 0) return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
                            if ($daysLeft === 0) return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
                            if ($daysLeft === 1) return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
                            return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400';
                        }

                        function alertStatusLabel($daysLeft, $hasNoSubscription = false)
                        {
                            if ($hasNoSubscription) return 'No Subscription';
                            if ($daysLeft < 0) {
                                return 'Expired ' . abs((int)$daysLeft) . ' day' . (abs((int)$daysLeft) === 1 ? '' : 's') . ' ago';
                            }
                            if ($daysLeft === 0) return 'Expires today';
                            if ($daysLeft === 1) return '1 day left';
                            return $daysLeft . ' days left';
                        }

                        function userInitials($name)
                        {
                            $parts = preg_split('/\s+/', trim((string)$name));
                            $first = $parts[0] ?? '';
                            $last = $parts[count($parts) - 1] ?? '';
                            return strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
                        }
                        ?>
                        <div class="p-0 overflow-x-auto">
                            <table id="subscription-alerts-table" class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-700/50 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        <th class="p-4 font-medium">User</th>
                                        <th class="p-4 font-medium">Package</th>
                                        <th class="p-4 font-medium">Status</th>
                                        <th class="p-4 font-medium">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-700">
                                    <?php if ($subscriptionAlerts && $subscriptionAlerts->num_rows > 0): ?>
                                        <?php while ($alert = $subscriptionAlerts->fetch_assoc()): ?>
                                            <?php
                                            $daysLeft = $alert['days_left'] !== null ? (int)$alert['days_left'] : null;
                                            $hasNoSubscription = (int)($alert['has_no_subscription'] ?? 0) === 1;
                                            $renewalStartRaw = htmlspecialchars($alert['renewal_start_raw'] ?? '');
                                            $renewalEndRaw = htmlspecialchars($alert['renewal_end_raw'] ?? '');
                                            $discountValue = htmlspecialchars($alert['discount'] ?? '0');
                                            ?>
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                                <td class="p-4 text-gray-800 dark:text-gray-200">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 flex items-center justify-center font-bold shrink-0">
                                                            <?= htmlspecialchars(userInitials($alert['user_name'] ?? '')) ?>
                                                        </div>
                                                        <div>
                                                            <p class="font-medium text-gray-900 dark:text-gray-100"><?= htmlspecialchars(strtoupper($alert['user_name'] ?? '')) ?></p>
                                                            <p class="text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($alert['address'] ?? '') ?></p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="p-4 text-gray-600 dark:text-gray-400">
                                                    <div class="font-medium text-gray-800 dark:text-gray-200">
                                                        <?= htmlspecialchars($alert['package_name'] ?? 'No Package Assigned') ?>
                                                    </div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        <?= !empty($alert['package_price']) ? 'Rs. ' . htmlspecialchars($alert['package_price']) : 'Package not assigned' ?>
                                                    </div>
                                                </td>
                                                <td class="p-4">
                                                    <span class="inline-flex px-2.5 py-1 rounded-md text-xs font-medium <?= alertBadgeClasses($daysLeft ?? 0, $hasNoSubscription) ?>">
                                                        <?= htmlspecialchars(alertStatusLabel($daysLeft ?? 0, $hasNoSubscription)) ?>
                                                    </span>
                                                    <?php if (!$hasNoSubscription): ?>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                                            Ends: <?= htmlspecialchars($alert['end_date_label'] ?? '') ?>
                                                        </p>
                                                    <?php else: ?>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                                            This customer has no active subscription yet.
                                                        </p>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="p-4">
                                                    <button
                                                        type="button"
                                                        class="activate-subscription-btn inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium transition-colors"
                                                        data-user-id="<?= htmlspecialchars($alert['user_id'] ?? '') ?>"
                                                        data-user-name="<?= htmlspecialchars($alert['user_name'] ?? '') ?>"
                                                        data-package-id="<?= htmlspecialchars($alert['package_id'] ?? '') ?>"
                                                        data-package-name="<?= htmlspecialchars($alert['package_name'] ?? '') ?>"
                                                        data-package-price="<?= htmlspecialchars($alert['package_price'] ?? '') ?>"
                                                        data-discount="<?= $discountValue ?>"
                                                        data-subscription-id="<?= htmlspecialchars($alert['subscription_id'] ?? '') ?>"
                                                        data-start-date="<?= $renewalStartRaw ?>"
                                                        data-end-date="<?= $renewalEndRaw ?>"
                                                        data-user-package-id="<?= htmlspecialchars($alert['user_package_id'] ?? '') ?>">
                                                        <i data-lucide="zap" class="w-4 h-4"></i>
                                                        <?= $hasNoSubscription ? 'Create Subscription' : 'Activate Subscription' ?>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="p-8 text-center">
                                                <div class="mx-auto max-w-md">
                                                    <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                                        <i data-lucide="badge-check" class="w-7 h-7"></i>
                                                    </div>
                                                    <h3 class="mt-4 text-base font-semibold text-gray-900 dark:text-gray-100">No subscription alerts right now</h3>
                                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">All active customers currently have subscriptions that are not near expiry.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php } else { ?>
                    <!-- Customer Package History Section -->
                    <!-- Customer Package History Section - Complete Code -->
                    <div class="space-y-6">
                        <?php
                        $customerId = $_SESSION['user']['id'];

                        // Get current active package
                        $currentPackage = $conn->query("
        SELECT 
            s.id AS subscription_id,
            p.name AS package_name,
            CAST(p.price AS DECIMAL(10,2)) AS package_price,
            CAST(COALESCE(s.discount, 0) AS DECIMAL(10,2)) AS discount,
            CAST((CAST(p.price AS DECIMAL(10,2)) - CAST(COALESCE(s.discount, 0) AS DECIMAL(10,2))) AS DECIMAL(10,2)) AS paid_amount,
            s.start_date,
            s.end_date,
            DATEDIFF(s.end_date, CURDATE()) AS days_remaining,
            s.status,
            DATE_FORMAT(s.start_date, '%d %b %Y') AS start_date_formatted,
            DATE_FORMAT(s.end_date, '%d %b %Y') AS end_date_formatted
        FROM subscriptions s
        INNER JOIN packages p ON s.package_id = p.id
        WHERE s.user_id = $customerId 
            AND s.status = 'active' 
            AND s.end_date >= CURDATE()
        ORDER BY s.end_date ASC
        LIMIT 1
    ");

                        // Pagination variables
                        $page = isset($_GET['history_page']) ? max(1, (int)$_GET['history_page']) : 1;
                        $records_per_page = 10;
                        $offset = ($page - 1) * $records_per_page;

                        // Get total records count
                        $totalQuery = $conn->query("
        SELECT COUNT(*) as total
        FROM subscriptions s
        INNER JOIN packages p ON s.package_id = p.id
        WHERE s.user_id = $customerId
    ");
                        $total_records = $totalQuery->fetch_assoc()['total'];
                        $total_pages = ceil($total_records / $records_per_page);

                        // Get paginated package history
                        $packageHistory = $conn->query("
        SELECT 
            s.id AS subscription_id,
            p.name AS package_name,
            CAST(p.price AS DECIMAL(10,2)) AS package_price,
            CAST(COALESCE(s.discount, 0) AS DECIMAL(10,2)) AS discount,
            CAST((CAST(p.price AS DECIMAL(10,2)) - CAST(COALESCE(s.discount, 0) AS DECIMAL(10,2))) AS DECIMAL(10,2)) AS paid_amount,
            s.start_date,
            s.end_date,
            s.status AS subscription_status,
            s.created_at,
            DATE_FORMAT(s.start_date, '%d %b %Y') AS start_date_formatted,
            DATE_FORMAT(s.end_date, '%d %b %Y') AS end_date_formatted,
            CASE 
                WHEN s.status = 'cancelled' THEN 'cancelled'
                WHEN s.end_date >= CURDATE() AND s.status = 'active' THEN 'active'
                WHEN s.end_date < CURDATE() THEN 'expired'
                ELSE s.status
            END AS current_status,
            DATEDIFF(s.end_date, CURDATE()) AS days_remaining
        FROM subscriptions s
        INNER JOIN packages p ON s.package_id = p.id
        WHERE s.user_id = $customerId
        ORDER BY 
            CASE 
                WHEN s.status = 'active' AND s.end_date >= CURDATE() THEN 0 
                ELSE 1 
            END,
            s.start_date DESC
        LIMIT $offset, $records_per_page
    ");

                        // Get summary statistics
                        $stats = $conn->query("
        SELECT 
            COUNT(*) AS total_subscriptions,
            SUM(CASE 
                WHEN s.status = 'active' AND s.end_date >= CURDATE() THEN 1 
                ELSE 0 
            END) AS active_subscriptions,
            SUM(CASE 
                WHEN s.end_date < CURDATE() OR s.status = 'expired' THEN 1 
                ELSE 0 
            END) AS expired_subscriptions,
            SUM(CASE 
                WHEN s.status = 'cancelled' THEN 1 
                ELSE 0 
            END) AS cancelled_subscriptions,
            SUM(CASE 
                WHEN s.status != 'cancelled' THEN (CAST(p.price AS DECIMAL(10,2)) - CAST(COALESCE(s.discount, 0) AS DECIMAL(10,2)))
                ELSE 0 
            END) AS total_spent,
            AVG(CASE 
                WHEN s.status != 'cancelled' THEN (CAST(p.price AS DECIMAL(10,2)) - CAST(COALESCE(s.discount, 0) AS DECIMAL(10,2)))
                ELSE NULL 
            END) AS avg_payment
        FROM subscriptions s
        INNER JOIN packages p ON s.package_id = p.id
        WHERE s.user_id = $customerId
    ")->fetch_assoc();
                        ?>

                        <!-- Current Active Package Hero Card -->
                        <?php if ($currentPackage && $currentPackage->num_rows > 0):
                            $active = $currentPackage->fetch_assoc();
                        ?>
                            <div class="relative overflow-hidden bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 rounded-2xl shadow-xl">
                                <div class="absolute inset-0 opacity-10">
                                    <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full filter blur-3xl"></div>
                                    <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full filter blur-3xl"></div>
                                </div>

                                <div class="relative p-6 lg:p-8">
                                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                                        <div class="flex-1">
                                            <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-white text-xs font-medium mb-4">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                                <span>CURRENTLY ACTIVE</span>
                                            </div>

                                            <h2 class="text-2xl lg:text-3xl font-bold text-white mb-2">
                                                <?= htmlspecialchars($active['package_name']) ?>
                                            </h2>

                                            <div class="flex flex-wrap gap-4 text-white/90 text-sm mb-4">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                    <span>Started: <?= htmlspecialchars($active['start_date_formatted']) ?></span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span>Expires: <?= htmlspecialchars($active['end_date_formatted']) ?></span>
                                                </div>
                                            </div>

                                            <?php
                                            $daysLeft = (int)$active['days_remaining'];
                                            $percentageLeft = min(100, max(0, ($daysLeft / 30) * 100));
                                            ?>
                                            <div class="max-w-md">
                                                <div class="flex justify-between text-white/90 text-xs mb-1">
                                                    <span>Subscription Period</span>
                                                    <span><?= $daysLeft ?> days remaining</span>
                                                </div>
                                                <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                                                    <div class="h-full bg-gradient-to-r from-green-400 to-green-300 rounded-full transition-all duration-500" style="width: <?= $percentageLeft ?>%"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="text-center lg:text-right">
                                            <div class="inline-block">
                                                <p class="text-white/70 text-sm uppercase tracking-wide mb-1">Paid Amount</p>
                                                <p class="text-4xl lg:text-5xl font-bold text-white mb-2">
                                                    Rs. <?= number_format($active['paid_amount'], 0) ?>
                                                </p>
                                                <?php if ($active['discount'] > 0): ?>
                                                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-green-500/20 backdrop-blur-sm rounded-lg">
                                                        <svg class="w-4 h-4 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <span class="text-white font-medium">Saved Rs. <?= number_format($active['discount'], 0) ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Package History Card -->
                        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-700">
                            <!-- Header -->
                            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-800">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                            </svg>
                                            Subscription History
                                        </h3>
                                        <?php if ($total_records > 0): ?>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                Showing <?= min($offset + 1, $total_records) ?> to <?= min($offset + $records_per_page, $total_records) ?> of <?= $total_records ?> subscriptions
                                            </p>
                                        <?php else: ?>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">No subscriptions found</p>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Filter Tabs -->
                                    <div class="flex gap-1 p-1 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                        <button data-filter="all" class="filter-btn active px-4 py-1.5 text-sm font-medium rounded-md transition-all duration-200">
                                            All (<?= $stats['total_subscriptions'] ?? 0 ?>)
                                        </button>
                                        <button data-filter="active" class="filter-btn px-4 py-1.5 text-sm font-medium rounded-md transition-all duration-200">
                                            Active (<?= $stats['active_subscriptions'] ?? 0 ?>)
                                        </button>
                                        <button data-filter="expired" class="filter-btn px-4 py-1.5 text-sm font-medium rounded-md transition-all duration-200">
                                            Expired (<?= $stats['expired_subscriptions'] ?? 0 ?>)
                                        </button>
                                        <button data-filter="cancelled" class="filter-btn px-4 py-1.5 text-sm font-medium rounded-md transition-all duration-200">
                                            Cancelled (<?= $stats['cancelled_subscriptions'] ?? 0 ?>)
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Stats Row -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-px bg-gray-200 dark:bg-gray-700">
                                <div class="bg-white dark:bg-gray-800 p-4 text-center">
                                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100"><?= number_format($stats['total_subscriptions'] ?? 0) ?></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mt-1">Total Subscriptions</p>
                                </div>
                                <div class="bg-white dark:bg-gray-800 p-4 text-center">
                                    <p class="text-2xl font-bold text-green-600 dark:text-green-400"><?= number_format($stats['active_subscriptions'] ?? 0) ?></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mt-1">Active</p>
                                </div>
                                <div class="bg-white dark:bg-gray-800 p-4 text-center">
                                    <p class="text-2xl font-bold text-red-600 dark:text-red-400"><?= number_format($stats['expired_subscriptions'] ?? 0) ?></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mt-1">Expired</p>
                                </div>
                                <div class="bg-white dark:bg-gray-800 p-4 text-center">
                                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">Rs. <?= number_format($stats['total_spent'] ?? 0, 0) ?></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mt-1">Total Spent</p>
                                </div>
                            </div>

                            <!-- History Table -->
                            <div class="overflow-x-auto">
                                <?php if (!$packageHistory || $packageHistory->num_rows === 0): ?>
                                    <!-- Empty State -->
                                    <div class="text-center py-16 px-4">
                                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 dark:bg-gray-700 mb-6">
                                            <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                            </svg>
                                        </div>
                                        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">No subscription history found</h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
                                            You haven't subscribed to any packages yet.
                                        </p>
                                    </div>
                                <?php else: ?>
                                    <table class="w-full">
                                        <thead class="bg-gray-50 dark:bg-gray-700/50 sticky top-0">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Package</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Price</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Discount</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Paid</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Period</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                            <?php while ($history = $packageHistory->fetch_assoc()):
                                                $statusColor = [
                                                    'active' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                                    'expired' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                                    'cancelled' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400'
                                                ][$history['current_status']] ?? 'bg-gray-100 text-gray-700';

                                                $statusIcon = [
                                                    'active' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>',
                                                    'expired' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                                                    'cancelled' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>'
                                                ][$history['current_status']] ?? '';
                                            ?>
                                                <tr class="history-row hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors duration-150" data-status="<?= $history['current_status'] ?>">
                                                    <td class="px-6 py-4">
                                                        <div class="flex items-center gap-3">
                                                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-sm">
                                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                                </svg>
                                                            </div>
                                                            <div>
                                                                <p class="font-medium text-gray-900 dark:text-gray-100"><?= htmlspecialchars($history['package_name']) ?></p>
                                                                <?php if ($history['subscription_status'] === 'cancelled'): ?>
                                                                    <span class="inline-block text-xs text-gray-500 dark:text-gray-400 mt-0.5">Cancelled</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <span class="text-gray-900 dark:text-gray-100 font-medium">Rs. <?= number_format($history['package_price'], 2) ?></span>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <?php if ($history['discount'] > 0): ?>
                                                            <div class="flex flex-col">
                                                                <span class="text-green-600 dark:text-green-400 font-medium">- Rs. <?= number_format($history['discount'], 2) ?></span>
                                                                <span class="text-xs text-gray-500 dark:text-gray-400"><?= round(($history['discount'] / $history['package_price']) * 100) ?>% off</span>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-gray-400 dark:text-gray-500">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <div class="flex flex-col">
                                                            <span class="text-gray-900 dark:text-gray-100 font-semibold">Rs. <?= number_format($history['paid_amount'], 2) ?></span>
                                                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 mt-1.5 max-w-[80px]">
                                                                <div class="bg-green-500 h-1.5 rounded-full" style="width: <?= round(($history['paid_amount'] / $history['package_price']) * 100) ?>%"></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <div class="flex flex-col text-sm">
                                                            <span class="text-gray-700 dark:text-gray-300"><?= htmlspecialchars($history['start_date_formatted']) ?></span>
                                                            <span class="text-gray-400 dark:text-gray-500 text-xs">↓</span>
                                                            <span class="text-gray-700 dark:text-gray-300"><?= htmlspecialchars($history['end_date_formatted']) ?></span>
                                                            <?php if ($history['current_status'] === 'active' && $history['days_remaining'] >= 0): ?>
                                                                <span class="text-xs text-blue-600 dark:text-blue-400 mt-1 font-medium"><?= $history['days_remaining'] ?> days left</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium <?= $statusColor ?>">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <?= $statusIcon ?>
                                                            </svg>
                                                            <?= ucfirst($history['current_status']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 text-right">
                                                        <?php if ($history['current_status'] === 'active' && isset($active) && $history['subscription_id'] === ($active['subscription_id'] ?? null)): ?>
                                                            <button class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                                </svg>
                                                                Current
                                                            </button>
                                                        <?php elseif ($history['current_status'] === 'cancelled'): ?>
                                                            <button class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-500 dark:text-gray-400 cursor-not-allowed">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                </svg>
                                                                Inactive
                                                            </button>
                                                        <?php else: ?>
                                                            <button class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-400 dark:text-gray-500 cursor-not-allowed">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                                </svg>
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1 && $total_records > 0): ?>
                                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            Showing <span class="font-medium"><?= min($offset + 1, $total_records) ?></span> to
                                            <span class="font-medium"><?= min($offset + $records_per_page, $total_records) ?></span> of
                                            <span class="font-medium"><?= $total_records ?></span> results
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <!-- Previous Page Button -->
                                            <a href="?history_page=<?= max(1, $page - 1) ?>"
                                                class="pagination-btn <?= $page <= 1 ? 'opacity-50 cursor-not-allowed pointer-events-none' : '' ?> inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                                </svg>
                                                Previous
                                            </a>

                                            <!-- Page Numbers -->
                                            <div class="hidden sm:flex items-center gap-1">
                                                <?php
                                                $start_page = max(1, $page - 2);
                                                $end_page = min($total_pages, $page + 2);

                                                if ($start_page > 1): ?>
                                                    <a href="?history_page=1" class="page-number px-3 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">1</a>
                                                    <?php if ($start_page > 2): ?>
                                                        <span class="px-2 text-gray-500 dark:text-gray-400">...</span>
                                                    <?php endif;
                                                endif;

                                                for ($i = $start_page; $i <= $end_page; $i++):
                                                    $isActive = ($i == $page);
                                                    ?>
                                                    <a href="?history_page=<?= $i ?>"
                                                        class="page-number px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= $isActive ? 'bg-blue-600 text-white border-blue-600' : 'text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600' ?>">
                                                        <?= $i ?>
                                                    </a>
                                                    <?php endfor;

                                                if ($end_page < $total_pages):
                                                    if ($end_page < $total_pages - 1): ?>
                                                        <span class="px-2 text-gray-500 dark:text-gray-400">...</span>
                                                    <?php endif; ?>
                                                    <a href="?history_page=<?= $total_pages ?>" class="page-number px-3 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"><?= $total_pages ?></a>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Next Page Button -->
                                            <a href="?history_page=<?= min($total_pages, $page + 1) ?>"
                                                class="pagination-btn <?= $page >= $total_pages ? 'opacity-50 cursor-not-allowed pointer-events-none' : '' ?> inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                                Next
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <script>
                        // Filter functionality
                        document.addEventListener('DOMContentLoaded', function() {
                            const filterBtns = document.querySelectorAll('.filter-btn');
                            const rows = document.querySelectorAll('.history-row');

                            function filterTable(status) {
                                rows.forEach(row => {
                                    if (status === 'all' || row.dataset.status === status) {
                                        row.style.display = '';
                                    } else {
                                        row.style.display = 'none';
                                    }
                                });
                            }

                            filterBtns.forEach(btn => {
                                btn.addEventListener('click', function() {
                                    const status = this.dataset.filter;

                                    // Update active state
                                    filterBtns.forEach(b => {
                                        b.classList.remove('active', 'bg-white', 'dark:bg-gray-600', 'text-gray-900', 'dark:text-white', 'shadow-sm');
                                        b.classList.add('text-gray-600', 'dark:text-gray-400');
                                    });
                                    this.classList.remove('text-gray-600', 'dark:text-gray-400');
                                    this.classList.add('active', 'bg-white', 'dark:bg-gray-600', 'text-gray-900', 'dark:text-white', 'shadow-sm');

                                    filterTable(status);
                                });
                            });
                        });
                    </script>

                    <style>
                        .filter-btn.active {
                            background-color: white;
                            color: #1f2937;
                            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                        }

                        .dark .filter-btn.active {
                            background-color: #4b5563;
                            color: white;
                        }

                        .filter-btn:not(.active):hover {
                            background-color: rgba(0, 0, 0, 0.05);
                        }

                        .dark .filter-btn:not(.active):hover {
                            background-color: rgba(255, 255, 255, 0.1);
                        }

                        .page-number {
                            min-width: 40px;
                            text-align: center;
                        }
                    </style>
                <?php } ?>
            </main>

            <!-- Footer -->
            <?php include "./include/footer.php" ?>
        </div>
    </div>
    <!-- footer links  -->
    <?php include "./include/footerLinks.php" ?>
    <?php include "./include/subscription-modals.php" ?>
    <script src="node_modules/simple-datatables/dist/umd/simple-datatables.js"></script>
    <script type="module" src="assets/js/subscriptions.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>

</html>