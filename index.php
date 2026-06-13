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
    <?php $appName = getenv('APP_NAME') ?: ''; ?>
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
        <!-- main-content-wrapper: JS sets margin/width immediately on load without transition -->
        <div id="main-content-wrapper" class="flex-1 flex flex-col w-full">

            <!-- Header -->
            <?php include "./include/header.php" ?>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto py-3 px-8 w-full min-h-screen">
                <!-- Breadcrumbs -->
                <?php include "./include/breadcrumbs.php" ?>

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