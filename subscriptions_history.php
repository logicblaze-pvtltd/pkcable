<?php
session_start();
include './include/connection.php';
$customerId = $_GET['user'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include './include/headerLinks.php'; ?>
</head>

<body>
    <!-- ======================================== -->
    <!-- PAGE LOADER - Include right after body -->
    <!-- ======================================== -->
    <?php include "./include/loader.php"; ?>
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
                <?php include "./include/breadcrumbs.php" ?>
                <!-- Customer Package History Section -->
                <div class="space-y-6">
                    <?php

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
                <!-- Footer -->
            </main>
            <?php include "./include/footer.php" ?>
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
    <?php include "./include/footerLinks.php" ?>
</body>

</html>