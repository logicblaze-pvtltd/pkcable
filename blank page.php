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
                <?php include "./include/breadcrumbs.php";?>
            </main>

            <!-- Footer -->
            <?php include "./include/footer.php" ?>
        </div>
    </div>
    <!-- footer links  -->
    <?php include "./include/footerLinks.php" ?>
    <?php include "./include/subscription-modals.php" ?>
    <script src="node_modules/simple-datatables/dist/umd/simple-datatables.js"></script>
    <script src="assets/js//button-loading.js"></script>
    <script type="module" src="assets/js/subscriptions.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>

</html>