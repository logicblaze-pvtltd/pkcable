<?php
session_start();
require_once './include/connection.php';
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['user']['role'] === 'customer') {
    header("Location: access_denied.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="./assets/favicon_io/favicon.ico">
    <?php $appName = get_env_value('APP_NAME') ?: ''; ?>
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> - Packages</title>

    <!-- header links -->
    <?php include "./include/headerLinks.php" ?>
</head>

<body class="bg-[#f3f4f4] text-gray-800 dark:text-gray-200 no-transition" style="overflow-x:hidden">
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
        <!-- main-content-wrapper: JS sets margin/width immediately on load without transition -->
        <div id="main-content-wrapper" class="flex-1 flex flex-col w-full">

            <!-- Header -->
            <?php include "./include/header.php" ?>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto py-3 px-8 w-full min-h-screen">
                <!-- Breadcrumbs -->
                <?php include "./include/breadcrumbs.php" ?>

                <!-- Packages Section -->
                <div class="animate-fade-in-up">
                    <!-- Header with Search and Add Button -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Packages</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage all packages and their information</p>
                        </div>
                        <button id="add-user-btn" class="flex items-center gap-2 px-4 py-2.5 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors font-medium shadow-md hover:shadow-lg">
                            <i data-lucide="plus" class="w-5 h-5"></i>
                            <span>Add Package</span>
                        </button>
                    </div>

                    <?php


                    $data = $db->select("SELECT id, name, price FROM packages ORDER BY id ASC");

                    if (isset($data['error'])) {
                        echo '<div class="p-4 bg-red-100 border border-red-400 text-red-700 rounded">';
                        echo 'Database Error: ' . htmlspecialchars($data['error']);
                        echo '</div>';
                        $data = [];
                    }
                    ?>

                    <div id="packages-table-shell" class="packages-table-shell bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                        <table id="packages-table" class="w-full overflow-hidden">
                            <thead>
                                <tr>
                                    <th class="col-id">Package ID</th>
                                    <th class="col-name hidden md:table-cell">Package Name</th>
                                    <th class="col-price hidden md:table-cell">Price in (Rs)</th>
                                    <th class="hidden md:table-cell" data-sortable="false">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data as $row):
                                    $formattedId = '#P' . str_pad($row['id'], 3, '0', STR_PAD_LEFT);
                                ?>
                                    <tr class="package-row" data-id="<?= htmlspecialchars($row['id']) ?>">
                                        <td class="align-top">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-sm shrink-0">
                                                    <i data-lucide="package" class="w-5 h-5"></i>
                                                </div>
                                                <span data-role="desktop-id" class="hidden md:block font-medium text-gray-900 dark:text-gray-100"><?= htmlspecialchars($formattedId) ?></span>
                                                <div class="min-w-0 flex-1 md:hidden">
                                                    <p data-role="mobile-name" class="truncate font-medium text-gray-900 dark:text-gray-100"><?= htmlspecialchars(strtoupper($row['name'])) ?></p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">ID: <span data-role="mobile-id"><?= htmlspecialchars($formattedId) ?></span></p>
                                                </div>
                                                <button type="button" class="mobile-row-toggle inline-flex md:hidden items-center justify-center w-8 h-8 rounded-full text-gray-500 dark:text-gray-400" aria-expanded="false" aria-label="Toggle details">
                                                    <i data-lucide="chevron-down" class="mobile-row-chevron w-4 h-4 transition-transform duration-200"></i>
                                                </button>
                                            </div>
                                            <div class="mobile-row-details hidden md:hidden pt-3 mt-3 border-t border-gray-200 dark:border-gray-700">
                                                <div class="grid grid-cols-1 gap-2 text-sm">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <span class="text-gray-500 dark:text-gray-400">Package ID</span>
                                                        <span data-role="detail-id" class="text-right text-gray-800 dark:text-gray-200"><?= htmlspecialchars($formattedId) ?></span>
                                                    </div>
                                                    <div class="flex items-start justify-between gap-3">
                                                        <span class="text-gray-500 dark:text-gray-400">Package Name</span>
                                                        <span data-role="detail-name" class="text-right text-gray-800 dark:text-gray-200 break-all"><?= htmlspecialchars(strtoupper($row['name'])) ?></span>
                                                    </div>
                                                    <div class="flex items-start justify-between gap-3">
                                                        <span class="text-gray-500 dark:text-gray-400">Price</span>
                                                        <span data-role="detail-price" class="text-right text-gray-800 dark:text-gray-200 font-semibold"><?= htmlspecialchars($row['price']) ?></span>
                                                    </div>
                                                </div>
                                                <div class="grid grid-cols-3 gap-2 mt-3">
                                                    <button class="view-package-btn inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 font-medium" title="View">
                                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                                        <span class="text-sm">View</span>
                                                    </button>
                                                    <button class="edit-package-btn inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400 font-medium" title="Edit">
                                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                                        <span class="text-sm">Edit</span>
                                                    </button>
                                                    <button class="delete-package-btn inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400 font-medium" title="Delete">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                        <span class="text-sm">Delete</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="col-name hidden md:table-cell text-gray-700 dark:text-gray-300">
                                            <span data-role="desktop-name" class="block truncate"><?= htmlspecialchars(strtoupper($row['name'])) ?></span>
                                        </td>
                                        <td class="col-price hidden md:table-cell text-gray-700 dark:text-gray-300">
                                            <span data-role="desktop-price" class="block font-semibold text-green-600 dark:text-green-400"><?= htmlspecialchars($row['price']) ?></span>
                                        </td>
                                        <td class="hidden md:table-cell whitespace-nowrap">
                                            <div class="flex items-center gap-2 justify-center">
                                                <button class="view-package-btn p-2 hover:bg-blue-100 dark:hover:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded transition-colors" title="View">
                                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                                </button>
                                                <button class="edit-package-btn p-2 hover:bg-amber-100 dark:hover:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded transition-colors" title="Edit">
                                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                                </button>
                                                <button class="delete-package-btn p-2 hover:bg-red-100 dark:hover:bg-red-900/30 text-red-600 dark:text-red-400 rounded transition-colors" title="Delete">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
            </main>

            <!-- Footer -->
            <?php include "./include/footer.php" ?>
        </div>
    </div>
    <!-- footer links  -->
    <?php include "./include/footerLinks.php" ?>
    <?php include "./include/package-modals.php" ?>
    <!-- packages page specific script -->
    <!-- Simple-DataTables JS -->
    <script src="node_modules/simple-datatables/dist/umd/simple-datatables.js"></script>
    <script type="module" src="assets/js/packages.js"></script>
</body>

</html>