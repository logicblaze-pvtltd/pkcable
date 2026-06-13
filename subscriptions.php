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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $appName = getenv('APP_NAME') ?: ''; ?>
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> - Subscriptions</title>

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
                <?php include "./include/breadcrumbs.php" ?>

                <!-- Subscriptions Section -->
                <div class="animate-fade-in-up">
                    <!-- Header with Search and Add Button -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Subscriptions</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage all Subscriptions and their information</p>
                        </div>
                        <!-- <button id="add-user-btn" class="flex items-center gap-2 px-4 py-2.5 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors font-medium shadow-md hover:shadow-lg">
                            <i data-lucide="plus" class="w-5 h-5"></i>
                            <span>Add Subscription</span>
                        </button> -->
                    </div>

                    <?php
                    $sql =
                    "SELECT
                                    s.id,
                                    s.user_id,
                                    s.package_id,
                                    u.name AS name,
                                    p.name AS package_name,
                                    p.price AS package_price,
                                    s.discount,
                                    (p.price - s.discount) AS paid_amount,
                                    DATE_FORMAT(s.start_date, '%d-%b-%Y') AS start_date,
                                    DATE_FORMAT(s.end_date, '%d-%b-%Y') AS end_date,
                                    DATE_FORMAT(s.start_date, '%Y-%m-%d') AS start_raw,
                                    DATE_FORMAT(s.end_date, '%Y-%m-%d') AS end_raw,
                                    DATE_FORMAT(s.start_date, '%M %Y') AS package_month,
                                    s.status
                                FROM subscriptions s
                                JOIN users u ON s.user_id = u.id
                                JOIN packages p ON s.package_id = p.id";
                                $params = [];
                    if(!empty($_GET['month'])) {
                        $sql .= " WHERE DATE_FORMAT(s.created_at, '%Y-%m') = ?";
                        $params[] = $_GET['month'];
                    }
                    $sql .= " ORDER BY s.id DESC";
                    $data = $db->select($sql, $params);

                    if (isset($data['error'])) {
                        echo '<div class="p-4 bg-red-100 border border-red-400 text-red-700 rounded">';
                        echo 'Database Error: ' . htmlspecialchars($data['error']);
                        echo '</div>';
                        $data = [];
                    }

                    // Status badge helper
                    function subStatusBadge($status) {
                        $s = strtolower($status ?? '');
                        $classes = match($s) {
                            'active'    => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                            'expired'   => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                            'cancelled' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                            default     => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                        };
                        $label = ucfirst($s ?: 'Unknown');
                        return "<span class=\"inline-flex px-3 py-1 rounded-full text-xs font-medium {$classes}\">{$label}</span>";
                    }
                    ?>

                    <div id="subscriptions-table-shell" class="subscriptions-table-shell bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
                        <table id="subscriptions-table" class="w-full overflow-hidden" data-responsive="auto">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th class="hidden md:table-cell">Customer</th>
                                    <th class="hidden md:table-cell">Package</th>
                                    <th class="hidden">Price</th>
                                    <th class="hidden">Discount</th>
                                    <th class="hidden md:table-cell">Paid Amount</th>
                                    <th class="hidden md:table-cell">Start Date</th>
                                    <th class="hidden md:table-cell">End Date</th>
                                    <th class="hidden">Month</th>
                                    <th class="hidden md:table-cell">Status</th>
                                    <th class="hidden md:table-cell" data-sortable="false">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data as $row):
                                    $formattedId = '#S' . str_pad($row['id'], 3, '0', STR_PAD_LEFT);
                                    $rawStatus   = strtolower($row['status'] ?? '');
                                ?>
                                    <tr class="subscription-row"
                                        data-id="<?= htmlspecialchars($row['id']) ?>"
                                        data-name="<?= htmlspecialchars(strtoupper($row['name'])) ?>"
                                        data-package-name="<?= htmlspecialchars($row['package_name']) ?>"
                                        data-package-price="<?= htmlspecialchars($row['package_price']) ?>"
                                        data-discount="<?= htmlspecialchars($row['discount']) ?>"
                                        data-paid-amount="<?= htmlspecialchars($row['paid_amount']) ?>"
                                        data-start-date="<?= htmlspecialchars($row['start_date']) ?>"
                                        data-end-date="<?= htmlspecialchars($row['end_date']) ?>"
                                        data-month="<?= htmlspecialchars($row['package_month']) ?>"
                                        data-status="<?= htmlspecialchars($rawStatus) ?>"
                                        data-user-id="<?= htmlspecialchars($row['user_id']) ?>"
                                        data-package-id="<?= htmlspecialchars($row['package_id']) ?>"
                                        data-start-raw="<?= htmlspecialchars($row['start_raw']) ?>"
                                        data-end-raw="<?= htmlspecialchars($row['end_raw']) ?>"
                                    >
                                        <td class="align-top">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-sm shrink-0">
                                                    <i data-lucide="receipt" class="w-5 h-5"></i>
                                                </div>
                                                <span data-role="desktop-id" class="hidden md:block font-medium text-gray-900 dark:text-gray-100"><?= htmlspecialchars($formattedId) ?></span>
                                                <div class="min-w-0 flex-1 md:hidden">
                                                    <p data-role="mobile-name" class="truncate font-medium text-gray-900 dark:text-gray-100"><?= htmlspecialchars(strtoupper($row['name'])) ?></p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">ID: <span data-role="mobile-id"><?= htmlspecialchars($formattedId) ?></span></p>
                                                </div>
                                                <button type="button" class="mobile-row-toggle inline-flex items-center justify-center w-8 h-8 rounded-full text-gray-500 dark:text-gray-400" aria-expanded="false" aria-label="Toggle details">
                                                    <i data-lucide="chevron-down" class="mobile-row-chevron w-4 h-4 transition-transform duration-200"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="hidden md:table-cell text-gray-700 dark:text-gray-300"><?= htmlspecialchars(strtoupper($row['name'])) ?></td>
                                        <td class="hidden md:table-cell text-gray-700 dark:text-gray-300"><?= htmlspecialchars($row['package_name']) ?></td>
                                        <td class="hidden">Rs.<?= htmlspecialchars($row['package_price']) ?></td>
                                        <td class="hidden">Rs.<?= htmlspecialchars($row['discount']) ?></td>
                                        <td class="hidden md:table-cell font-semibold text-blue-600 dark:text-blue-400">Rs.<?= htmlspecialchars($row['paid_amount']) ?></td>
                                        <td class="hidden md:table-cell text-gray-700 dark:text-gray-300"><?= htmlspecialchars($row['start_date']) ?></td>
                                        <td class="hidden md:table-cell text-gray-700 dark:text-gray-300"><?= htmlspecialchars($row['end_date']) ?></td>
                                        <td class="hidden"><?= htmlspecialchars($row['package_month']) ?></td>
                                        <td class="hidden md:table-cell"><?= subStatusBadge($row['status']) ?></td>
                                        <td class="hidden md:table-cell whitespace-nowrap">
                                            <div class="flex items-center gap-2 justify-center">
                                                <button class="view-subscription-btn p-2 hover:bg-blue-100 dark:hover:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded transition-colors" title="View">
                                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                                </button>
                                                <button class="edit-subscription-btn p-2 hover:bg-amber-100 dark:hover:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded transition-colors" title="Edit">
                                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
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
    <!-- Simple-DataTables JS -->
    <script src="node_modules/simple-datatables/dist/umd/simple-datatables.js"></script>
    <script type="module" src="assets/js/subscriptions.js"></script>
</body>

</html>
