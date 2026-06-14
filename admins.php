<?php
session_start();
require_once './include/connection.php';
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['user']['role'] !== 'super admin') {
    header("Location: access_denied.php");
    exit();
}
function adminEsc($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function adminFormatId($id)
{
    return '#A' . str_pad((string) ((int) $id), 3, '0', STR_PAD_LEFT);
}

function adminInitials($name)
{
    $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
    $first = isset($parts[0]) && $parts[0] !== '' ? substr($parts[0], 0, 1) : '';
    $second = isset($parts[1]) && $parts[1] !== '' ? substr($parts[1], 0, 1) : '';

    return strtoupper($first . $second);
}

function adminRoleLabel($role)
{
    $role = trim((string) $role);
    return $role === '' ? 'Admin' : ucwords(str_replace('_', ' ', $role));
}

function adminRoleClasses($role)
{
    return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
}

function adminAvatarClasses($role)
{
    return 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400';
}

function adminStatusLabel($status)
{
    $status = trim((string) $status);
    return $status === '' ? 'Active' : ucwords(str_replace('_', ' ', $status));
}

function adminStatusClasses($status)
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

function adminFormatDate($value)
{
    $value = trim((string) $value);

    if ($value === '') {
        return '';
    }

    $timestamp = strtotime($value);

    return $timestamp ? date('M j, Y', $timestamp) : $value;
}

$admins = $db->select(
    "SELECT u.id, u.name, u.email, u.user_role, u.status, u.address, u.created_at
     FROM users u
     WHERE u.user_role = 'admin'
     ORDER BY u.id ASC"
);

if (isset($admins['error'])) {
    $adminError = $admins['error'];
    $admins = [];
} else {
    $adminError = null;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $appName = get_env_value('APP_NAME') ?>
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> - Admins</title>

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
                    ['title' => 'Admins']
                ];
                include "./include/breadcrumbs.php";
                ?>

                <div class="animate-fade-in-up">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Admins</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage all admins and their information</p>
                        </div>
                        <button id="add-user-btn" class="flex items-center gap-2 px-4 py-2.5 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors font-medium shadow-md hover:shadow-lg">
                            <i data-lucide="plus" class="w-5 h-5"></i>
                            <span>Add Admin</span>
                        </button>
                    </div>

                    <?php if ($adminError): ?>
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            Database Error: <?= adminEsc($adminError) ?>
                        </div>
                    <?php endif; ?>

                    <div id="admins-table-shell" class="customers-table-shell bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                        <table id="admins-table" class="w-full overflow-hidden">
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
                                <?php foreach ($admins as $row):
                                    $adminId = (int) ($row['id'] ?? 0);
                                    $adminIdDisplay = adminFormatId($adminId);
                                    $name = (string) (strtoupper($row['name']) ?? '');
                                    $email = (string) ($row['email'] ?? '');
                                    $userRole = 'admin';
                                    $status = strtolower(trim((string) ($row['status'] ?? 'active')));
                                    $address = (string) ($row['address'] ?? '');
                                    $createdAt = adminFormatDate($row['created_at'] ?? '');
                                    $initials = adminInitials($name);
                                    $avatarClasses = adminAvatarClasses($userRole);
                                    $statusClasses = adminStatusClasses($status);
                                    $statusLabel = adminStatusLabel($status);
                                ?>
                                    <tr
                                        class="user-row"
                                        data-id="<?= adminEsc($adminId) ?>"
                                        data-name="<?= adminEsc($name) ?>"
                                        data-email="<?= adminEsc($email) ?>"
                                        data-user-role="admin"
                                        data-status="<?= adminEsc($status) ?>"
                                        data-address="<?= adminEsc($address) ?>"
                                        data-created-at="<?= adminEsc($createdAt) ?>">
                                        <td class="align-top">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full <?= adminEsc($avatarClasses) ?> flex items-center justify-center font-bold text-sm shrink-0">
                                                    <?= adminEsc($initials) ?>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate font-medium text-gray-900 dark:text-gray-100"><?= adminEsc($name) ?></p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">ID: <?= adminEsc($adminIdDisplay) ?></p>
                                                </div>
                                                <div class="md:hidden flex items-center gap-2 shrink-0">
                                                    <span class="px-2.5 py-1 <?= adminEsc($statusClasses) ?> rounded-full text-[11px] font-medium">
                                                        <?= adminEsc($statusLabel) ?>
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
                                                        <span class="text-right text-gray-800 dark:text-gray-200 break-all"><?= adminEsc($email) ?></span>
                                                    </div>
                                                    <div class="flex items-start justify-between gap-3">
                                                        <span class="text-gray-500 dark:text-gray-400">Address</span>
                                                        <span class="text-right text-gray-800 dark:text-gray-200 break-all"><?= adminEsc($address !== '' ? $address : '—') ?></span>
                                                    </div>
                                                    <div class="flex items-start justify-between gap-3">
                                                        <span class="text-gray-500 dark:text-gray-400">Created</span>
                                                        <span class="text-right text-gray-800 dark:text-gray-200"><?= adminEsc($createdAt !== '' ? $createdAt : '—') ?></span>
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
                                            <span class="block truncate"><?= adminEsc($email) ?></span>
                                        </td>
                                        <td class="col-status hidden md:table-cell text-gray-700 dark:text-gray-300">
                                            <span class="inline-flex px-3 py-1 <?= adminEsc($statusClasses) ?> rounded-full text-xs font-medium">
                                                <?= adminEsc($statusLabel) ?>
                                            </span>
                                        </td>
                                        <td class="col-created hidden md:table-cell text-gray-700 dark:text-gray-300">
                                            <span class="block truncate"><?= adminEsc($createdAt !== '' ? $createdAt : '—') ?></span>
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
    <?php include "./include/admin-modals.php" ?>
    <script src="node_modules/simple-datatables/dist/umd/simple-datatables.js"></script>
    <script type="module" src="assets/js/admins.js"></script>
</body>

</html>