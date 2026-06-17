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
function customerEsc($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function customerFormatId($id)
{
    return '#C' . str_pad((string) ((int) $id), 3, '0', STR_PAD_LEFT);
}

function customerFormatPackageId($id)
{
    return '#P' . str_pad((string) ((int) $id), 3, '0', STR_PAD_LEFT);
}

function customerInitials($name)
{
    $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
    $first = isset($parts[0]) && $parts[0] !== '' ? substr($parts[0], 0, 1) : '';
    $second = isset($parts[1]) && $parts[1] !== '' ? substr($parts[1], 0, 1) : '';

    return strtoupper($first . $second);
}

function customerRoleLabel($role)
{
    $role = trim((string) $role);

    return $role === '' ? 'Customer' : ucwords(str_replace('_', ' ', $role));
}

function customerRoleClasses($role)
{
    $role = strtolower(trim((string) $role));

    switch ($role) {
        case 'super admin':
            return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
        case 'admin':
            return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
        case 'manager':
            return 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400';
        case 'customer':
            return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
        default:
            return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
    }
}

function customerAvatarClasses($role)
{
    $role = strtolower(trim((string) $role));

    switch ($role) {
        case 'super admin':
            return 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400';
        case 'admin':
            return 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400';
        case 'manager':
            return 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400';
        case 'customer':
            return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400';
        default:
            return 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300';
    }
}

function customerStatusLabel($status)
{
    $status = trim((string) $status);

    return $status === '' ? 'Active' : ucwords(str_replace('_', ' ', $status));
}

function customerStatusClasses($status)
{
    $status = strtolower(trim((string) $status));

    if ($status === 'active') {
        return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
    }

    if ($status === 'inactive') {
        return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
    }

    return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
}

function customerFormatDate($value)
{
    $value = trim((string) $value);

    if ($value === '') {
        return '';
    }

    $timestamp = strtotime($value);

    return $timestamp ? date('M j, Y', $timestamp) : $value;
}

$sql = "SELECT u.id, u.name, u.email, u.user_role, u.status, u.package, u.address, u.created_at, p.name AS package_name
        FROM users u
        LEFT JOIN packages p ON u.package = p.id WHERE u.user_role = 'customer'";

$params = [];

if (!empty($_GET['status'])) {
    $sql .= " AND u.status = ?";
    $params[] = $_GET['status'];
}

$sql .= " ORDER BY u.id ASC";

$customers = $db->select($sql, $params);

if (isset($customers['error'])) {
    $customerError = $customers['error'];
    $customers = [];
} else {
    $customerError = null;
}

$packages = $db->select("SELECT id, name FROM packages ORDER BY id ASC");

if (isset($packages['error'])) {
    $packageError = $packages['error'];
    $packages = [];
} else {
    $packageError = null;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="./assets/favicon_io/favicon.ico">
    <?php $appName = get_env_value('APP_NAME') ?>
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> - Customers</title>

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

                <div class="animate-fade-in-up">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100"><?php echo !empty($_GET['status']) ? ucfirst($_GET['status']) . ' Customers' : 'All Customers'; ?></h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage <?php echo !empty($_GET['status']) ? ucfirst($_GET['status']) . ' Customers' : 'All Customers'; ?> and their information</p>
                        </div>
                        <button id="add-user-btn" class="flex items-center gap-2 px-4 py-2.5 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors font-medium shadow-md hover:shadow-lg">
                            <i data-lucide="plus" class="w-5 h-5"></i>
                            <span>Add Customer</span>
                        </button>
                    </div>

                    <?php if ($customerError): ?>
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            Database Error: <?= customerEsc($customerError) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($packageError): ?>
                        <div class="mb-4 p-4 bg-amber-100 border border-amber-400 text-amber-700 rounded">
                            Package list warning: <?= customerEsc($packageError) ?>
                        </div>
                    <?php endif; ?>

                    <div id="customers-table-shell" class="customers-table-shell bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                        <table id="customers-table" class="w-full overflow-hidden">
                            <thead>
                                <tr>
                                    <th class="col-name">Name</th>
                                    <th class="col-email hidden md:table-cell">Email</th>
                                    <th class="col-package hidden md:table-cell">Package</th>
                                    <th class="col-status hidden md:table-cell">Status</th>
                                    <th class="col-created hidden md:table-cell">Created</th>
                                    <th class="hidden md:table-cell" data-sortable="false">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $row):
                                    $customerId = (int) ($row['id'] ?? 0);
                                    $customerIdDisplay = customerFormatId($customerId);
                                    $name = (string) (strtoupper($row['name']) ?? '');
                                    $email = (string) ($row['email'] ?? '');
                                    $userRole = strtolower(trim((string) ($row['user_role'] ?? 'customer')));
                                    $status = strtolower(trim((string) ($row['status'] ?? 'active')));
                                    $packageId = isset($row['package']) && $row['package'] !== null ? (int) $row['package'] : null;
                                    $packageName = trim((string) (strtoupper($row['package_name']) ?? ''));
                                    $address = (string) ($row['address'] ?? '');
                                    $createdAt = customerFormatDate($row['created_at'] ?? '');
                                    $initials = customerInitials($name);
                                    $avatarClasses = customerAvatarClasses($userRole);
                                    $roleClasses = customerRoleClasses($userRole);
                                    $statusClasses = customerStatusClasses($status);
                                    $roleLabel = customerRoleLabel($userRole);
                                    $statusLabel = customerStatusLabel($status);
                                    $packageDisplay = $packageName !== '' ? $packageName : 'No Package';
                                    $packageIdDisplay = $packageId !== null ? customerFormatPackageId($packageId) : '';
                                ?>
                                    <tr
                                        class="user-row"
                                        data-id="<?= customerEsc($customerId) ?>"
                                        data-name="<?= customerEsc($name) ?>"
                                        data-email="<?= customerEsc($email) ?>"
                                        data-user-role="<?= customerEsc($userRole) ?>"
                                        data-status="<?= customerEsc($status) ?>"
                                        data-package-id="<?= customerEsc($packageId ?? '') ?>"
                                        data-package-name="<?= customerEsc($packageName) ?>"
                                        data-address="<?= customerEsc($address) ?>"
                                        data-created-at="<?= customerEsc($createdAt) ?>">
                                        <td class="align-top">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full <?= customerEsc($avatarClasses) ?> flex items-center justify-center font-bold text-sm shrink-0">
                                                    <?= customerEsc($initials) ?>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate font-medium text-gray-900 dark:text-gray-100"><?= customerEsc($name) ?></p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">ID: <?= customerEsc($customerIdDisplay) ?></p>
                                                </div>
                                                <div class="md:hidden flex items-center gap-2 shrink-0">
                                                    <span class="px-2.5 py-1 <?= customerEsc($statusClasses) ?> rounded-full text-[11px] font-medium">
                                                        <?= customerEsc($statusLabel) ?>
                                                    </span>
                                                    <button type="button" class="mobile-row-toggle inline-flex items-center justify-center w-8 h-8 rounded-full text-gray-500 dark:text-gray-400" aria-expanded="false" aria-label="Toggle details">
                                                        <i data-lucide="chevron-down" class="mobile-row-chevron w-4 h-4 transition-transform duration-200"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="mobile-row-details hidden md:hidden pt-3 mt-3 border-t border-gray-200 dark:border-gray-700">
                                                <div class="grid grid-cols-1 gap-2 text-sm">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <span class="text-gray-500 dark:text-gray-400">Email</span>
                                                        <span class="text-right text-gray-800 dark:text-gray-200 break-all"><?= customerEsc($email) ?></span>
                                                    </div>
                                                    <div class="flex items-start justify-between gap-3">
                                                        <span class="text-gray-500 dark:text-gray-400">Package</span>
                                                        <span class="text-right text-gray-800 dark:text-gray-200">
                                                            <span class="block"><?= customerEsc($packageDisplay) ?></span>
                                                            <?php if ($packageIdDisplay !== ''): ?>
                                                                <span class="block text-xs text-gray-500 dark:text-gray-400"><?= customerEsc($packageIdDisplay) ?></span>
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                    <div class="flex items-start justify-between gap-3">
                                                        <span class="text-gray-500 dark:text-gray-400">Address</span>
                                                        <span class="text-right text-gray-800 dark:text-gray-200 break-all"><?= customerEsc($address !== '' ? $address : '—') ?></span>
                                                    </div>
                                                    <div class="flex items-start justify-between gap-3">
                                                        <span class="text-gray-500 dark:text-gray-400">Created</span>
                                                        <span class="text-right text-gray-800 dark:text-gray-200"><?= customerEsc($createdAt !== '' ? $createdAt : '—') ?></span>
                                                    </div>
                                                </div>
                                                <div class="grid grid-cols-3 gap-2 mt-3">
                                                    <button class="view-user-btn inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 font-medium" title="View">
                                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                                        <span class="text-sm">View</span>
                                                    </button>
                                                    <?php if ($_SESSION['user']['role'] === 'super admin' || $_SESSION['user']['role'] === 'admin') { ?>
                                                        <button class="edit-user-btn inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400 font-medium" title="Edit">
                                                            <i data-lucide="edit" class="w-4 h-4"></i>
                                                            <span class="text-sm">Edit</span>
                                                        </button>
                                                        <button class="delete-user-btn inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400 font-medium" title="Delete">
                                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                            <span class="text-sm">Delete</span>
                                                        </button>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="col-email hidden md:table-cell text-gray-700 dark:text-gray-300">
                                            <span class="block truncate"><?= customerEsc($email) ?></span>
                                        </td>
                                        <td class="col-package hidden md:table-cell text-gray-700 dark:text-gray-300">
                                            <span class="block font-medium"><?= customerEsc($packageDisplay) ?></span>
                                            <?php if ($packageIdDisplay !== ''): ?>
                                                <span class="block text-xs text-gray-500 dark:text-gray-400"><?= customerEsc($packageIdDisplay) ?></span>
                                            <?php else: ?>
                                                <span class="block text-xs text-gray-500 dark:text-gray-400">No package assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="col-status hidden md:table-cell text-gray-700 dark:text-gray-300">
                                            <span class="inline-flex px-3 py-1 <?= customerEsc($statusClasses) ?> rounded-full text-xs font-medium">
                                                <?= customerEsc($statusLabel) ?>
                                            </span>
                                        </td>
                                        <td class="col-created hidden md:table-cell text-gray-700 dark:text-gray-300">
                                            <span class="block truncate"><?= customerEsc($createdAt !== '' ? $createdAt : '—') ?></span>
                                        </td>
                                        <td class="hidden md:table-cell whitespace-nowrap">
                                            <div class="flex items-center gap-2 justify-center">
                                                <button class="view-user-btn p-2 hover:bg-blue-100 dark:hover:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded transition-colors" title="View">
                                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                                </button>
                                                <button class="edit-user-btn p-2 hover:bg-amber-100 dark:hover:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded transition-colors" title="Edit">
                                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                                </button>
                                                <?php
                                                if ($_SESSION['user']['role'] === 'super admin' || $_SESSION['user']['role'] === 'admin') {
                                                    echo '<button class="delete-user-btn p-2 hover:bg-red-100 dark:hover:bg-red-900/30 text-red-600 dark:text-red-400 rounded transition-colors" title="Delete">
                                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                        </button>';
                                                }
                                                ?>

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
    <script>
        const currentUserRole = <?= json_encode($_SESSION['user']['role']) ?>;
    </script>
    <!-- footer links  -->
    <?php include "./include/footerLinks.php" ?>
    <?php include "./include/customer-modals.php" ?>
    <?php include "./include/subscription-modals.php" ?>
    <script src="node_modules/simple-datatables/dist/umd/simple-datatables.js"></script>
    <script type="module" src="assets/js/customers.js"></script>
</body>

</html>