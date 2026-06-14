<?php
session_start();
require_once './include/connection.php';
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['user']['role'] !== 'admin') {
    header("Location: access_denied.php");
    exit();
}
function managerEsc($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function managerFormatId($id)
{
    return '#M' . str_pad((string) ((int) $id), 3, '0', STR_PAD_LEFT);
}

function managerInitials($name)
{
    $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
    $first = isset($parts[0]) && $parts[0] !== '' ? substr($parts[0], 0, 1) : '';
    $second = isset($parts[1]) && $parts[1] !== '' ? substr($parts[1], 0, 1) : '';

    return strtoupper($first . $second);
}

function managerRoleLabel($role)
{
    $role = trim((string) $role);
    return $role === '' ? 'Manager' : ucwords(str_replace('_', ' ', $role));
}

function managerRoleClasses($role)
{
    return 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400';
}

function managerAvatarClasses($role)
{
    return 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400';
}

function managerStatusLabel($status)
{
    $status = trim((string) $status);
    return $status === '' ? 'Active' : ucwords(str_replace('_', ' ', $status));
}

function managerStatusClasses($status)
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

function managerFormatDate($value)
{
    $value = trim((string) $value);

    if ($value === '') {
        return '';
    }

    $timestamp = strtotime($value);

    return $timestamp ? date('M j, Y', $timestamp) : $value;
}

$managers = $db->select(
    "SELECT u.id, u.name, u.email, u.user_role, u.status, u.address, u.created_at
     FROM users u
     WHERE u.user_role = 'manager'
     ORDER BY u.id ASC"
);

if (isset($managers['error'])) {
    $managerError = $managers['error'];
    $managers = [];
} else {
    $managerError = null;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="./assets/favicon_io/favicon.ico">
    <?php $appName = get_env_value('APP_NAME') ?>
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> - Managers</title>

    <!-- header links -->
    <?php include "./include/headerLinks.php" ?>
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
                <?php
                $breadcrumbs = [
                    ['title' => 'Home', 'url' => 'index.php'],
                    ['title' => 'Managers']
                ];
                include "./include/breadcrumbs.php";
                ?>

                <div class="animate-fade-in-up">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Managers</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage all managers and their information</p>
                        </div>
                        <button id="add-user-btn" class="flex items-center gap-2 px-4 py-2.5 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors font-medium shadow-md hover:shadow-lg">
                            <i data-lucide="plus" class="w-5 h-5"></i>
                            <span>Add Manager</span>
                        </button>
                    </div>

                    <?php if ($managerError): ?>
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            Database Error: <?= managerEsc($managerError) ?>
                        </div>
                    <?php endif; ?>

                    <div id="managers-table-shell" class="customers-table-shell bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                        <table id="managers-table" class="w-full overflow-hidden">
                            <thead>
                                <tr>
                                    <th class="col-name">Name</th>
                                    <th class="col-email hidden md:table-cell">Email</th>
                                    <th class="col-status hidden md:table-cell">Status</th>
                                    <th class="col-created hidden md:table-cell">Created</th>
                                    <th class="hidden md:table-cell" data-sortable="false">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($managers as $row):
                                    $managerId = (int) ($row['id'] ?? 0);
                                    $managerIdDisplay = managerFormatId($managerId);
                                    $name = (string) (strtoupper($row['name']) ?? '');
                                    $email = (string) ($row['email'] ?? '');
                                    $userRole = 'manager';
                                    $status = strtolower(trim((string) ($row['status'] ?? 'active')));
                                    $address = (string) ($row['address'] ?? '');
                                    $createdAt = managerFormatDate($row['created_at'] ?? '');
                                    $initials = managerInitials($name);
                                    $avatarClasses = managerAvatarClasses($userRole);
                                    $statusClasses = managerStatusClasses($status);
                                    $statusLabel = managerStatusLabel($status);
                                ?>
                                    <tr
                                        class="user-row"
                                        data-id="<?= managerEsc($managerId) ?>"
                                        data-name="<?= managerEsc($name) ?>"
                                        data-email="<?= managerEsc($email) ?>"
                                        data-user-role="manager"
                                        data-status="<?= managerEsc($status) ?>"
                                        data-address="<?= managerEsc($address) ?>"
                                        data-created-at="<?= managerEsc($createdAt) ?>">
                                        <td class="align-top">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full <?= managerEsc($avatarClasses) ?> flex items-center justify-center font-bold text-sm shrink-0">
                                                    <?= managerEsc($initials) ?>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate font-medium text-gray-900 dark:text-gray-100"><?= managerEsc($name) ?></p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">ID: <?= managerEsc($managerIdDisplay) ?></p>
                                                </div>
                                                <div class="md:hidden flex items-center gap-2 shrink-0">
                                                    <span class="px-2.5 py-1 <?= managerEsc($statusClasses) ?> rounded-full text-[11px] font-medium">
                                                        <?= managerEsc($statusLabel) ?>
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
                                                        <span class="text-right text-gray-800 dark:text-gray-200 break-all"><?= managerEsc($email) ?></span>
                                                    </div>
                                                    <div class="flex items-start justify-between gap-3">
                                                        <span class="text-gray-500 dark:text-gray-400">Address</span>
                                                        <span class="text-right text-gray-800 dark:text-gray-200 break-all"><?= managerEsc($address !== '' ? $address : '—') ?></span>
                                                    </div>
                                                    <div class="flex items-start justify-between gap-3">
                                                        <span class="text-gray-500 dark:text-gray-400">Created</span>
                                                        <span class="text-right text-gray-800 dark:text-gray-200"><?= managerEsc($createdAt !== '' ? $createdAt : '—') ?></span>
                                                    </div>
                                                </div>
                                                <div class="grid grid-cols-3 gap-2 mt-3">
                                                    <button class="view-user-btn inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 font-medium" title="View">
                                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                                        <span class="text-sm">View</span>
                                                    </button>
                                                    <button class="edit-user-btn inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400 font-medium" title="Edit">
                                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                                        <span class="text-sm">Edit</span>
                                                    </button>
                                                    <button class="delete-user-btn inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400 font-medium" title="Delete">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                        <span class="text-sm">Delete</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="col-email hidden md:table-cell text-gray-700 dark:text-gray-300">
                                            <span class="block truncate"><?= managerEsc($email) ?></span>
                                        </td>
                                        <td class="col-status hidden md:table-cell text-gray-700 dark:text-gray-300">
                                            <span class="inline-flex px-3 py-1 <?= managerEsc($statusClasses) ?> rounded-full text-xs font-medium">
                                                <?= managerEsc($statusLabel) ?>
                                            </span>
                                        </td>
                                        <td class="col-created hidden md:table-cell text-gray-700 dark:text-gray-300">
                                            <span class="block truncate"><?= managerEsc($createdAt !== '' ? $createdAt : '—') ?></span>
                                        </td>
                                        <td class="hidden md:table-cell whitespace-nowrap">
                                            <div class="flex items-center gap-2 justify-center">
                                                <button class="view-user-btn p-2 hover:bg-blue-100 dark:hover:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded transition-colors" title="View">
                                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                                </button>
                                                <button class="edit-user-btn p-2 hover:bg-amber-100 dark:hover:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded transition-colors" title="Edit">
                                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                                </button>
                                                <button class="delete-user-btn p-2 hover:bg-red-100 dark:hover:bg-red-900/30 text-red-600 dark:text-red-400 rounded transition-colors" title="Delete">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
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
    <?php include "./include/manager-modals.php" ?>
    <script src="node_modules/simple-datatables/dist/umd/simple-datatables.js"></script>
    <script type="module" src="assets/js/managers.js"></script>
</body>

</html>