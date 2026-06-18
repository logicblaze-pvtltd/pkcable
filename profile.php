<?php
session_start();
require_once './include/connection.php';

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Get user data from session
$sessionUser = $_SESSION['user'];
$userId = $sessionUser['id'] ?? 0;
$userData = null;
$userPackage = null;
$userSubscription = null;

// Fetch user data with package and subscription info
if ($userId) {
    $stmt = $conn->prepare("
        SELECT u.*, p.name as package_name, p.price as package_price,
               s.start_date, s.end_date, s.status as subscription_status,
               s.discount
        FROM users u
        LEFT JOIN packages p ON u.package = p.id
        LEFT JOIN subscriptions s ON u.id = s.user_id AND s.status = 'active'
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    $stmt->close();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'Invalid action'];

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $bio = trim($_POST['bio'] ?? '');

        if (empty($name)) {
            $response['message'] = 'Name is required';
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, address = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $bio, $userId);
            if ($stmt->execute()) {
                $_SESSION['user']['name'] = $name;
                $response['success'] = true;
                $response['message'] = 'Profile updated successfully';
            } else {
                $response['message'] = 'Failed to update profile';
            }
            $stmt->close();
        }
    }
    if ($action === 'update_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!$currentPassword || !$newPassword || !$confirmPassword) {
            $response['message'] = 'All password fields are required';
        } elseif ($newPassword !== $confirmPassword) {
            $response['message'] = 'New passwords do not match';
        } elseif (strlen($newPassword) < 6) {
            $response['message'] = 'Password must be at least 6 characters';
        } else {

            // Get current password from DB
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if (!$user || !password_verify($currentPassword, $user['password'])) {
                $response['message'] = 'Current password is incorrect';
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashedPassword, $userId);

                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Password updated successfully';
                } else {
                    $response['message'] = 'Failed to update password';
                }
                $stmt->close();
            }
        }
    }
    echo json_encode($response);
    exit();
}

// Helper function to get user initials
function getUserInitials($name)
{
    if (empty($name)) return 'U';
    $words = explode(' ', trim($name));
    $initials = '';
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper($word[0]);
        }
        if (strlen($initials) >= 2) break;
    }
    return $initials ?: 'U';
}

// Calculate days remaining in subscription
function getDaysRemaining($endDate)
{
    if (!$endDate) return null;
    $now = new DateTime();
    $end = new DateTime($endDate);
    if ($now > $end) return 0;
    return $now->diff($end)->days;
}

// Get safe value for display
function safe($value, $default = '')
{
    if (is_array($value)) {
        return $default;
    }
    return htmlspecialchars($value ?? $default, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="./assets/favicon_io/favicon.ico">
    <?php $appName = get_env_value('APP_NAME') ?: 'AppName'; ?>
    <title><?= safe($appName) ?> - Dashboard</title>

    <!-- header links -->
    <?php include "./include/headerLinks.php" ?>
    <link rel="stylesheet" href="assets/css/datePicker.css">

    <!-- Modern UI Styles -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        /* Modern glass morphism effects */
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 12px 35px -12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .dark .glass-card {
            background: rgba(30, 35, 48, 0.85);
            border-color: rgba(255, 255, 255, 0.08);
        }

        .glass-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 25px 40px -18px rgba(0, 0, 0, 0.2);
        }

        /* Animated gradient banner */
        .gradient-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }

        .gradient-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            animation: shimmer 8s infinite;
        }

        @keyframes shimmer {
            0% {
                left: -100%;
            }

            50% {
                left: 100%;
            }

            100% {
                left: 100%;
            }
        }

        /* Avatar pulse animation */
        .avatar-pulse {
            animation: pulseGlow 2s infinite;
        }

        @keyframes pulseGlow {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4);
            }

            50% {
                box-shadow: 0 0 0 15px rgba(99, 102, 241, 0);
            }
        }

        /* Modern input styles */
        .modern-input {
            transition: all 0.2s ease;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(203, 213, 225, 0.5);
        }

        .modern-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            background: white;
        }

        .dark .modern-input {
            background: rgba(51, 65, 85, 0.7);
            border-color: rgba(71, 85, 105, 0.5);
        }

        /* Button hover effects */
        .btn-modern {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .btn-modern::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-modern:hover::before {
            width: 300px;
            height: 300px;
        }

        /* Toast animation */
        .toast-modern {
            animation: slideUpFade 0.3s ease-out;
        }

        @keyframes slideUpFade {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Table styles */
        .table-modern {
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-modern tbody tr {
            transition: all 0.2s;
        }

        .table-modern tbody tr:hover {
            background: rgba(99, 102, 241, 0.05);
            transform: scale(1.01);
        }

        /* Scrollbar */
        .custom-scroll::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .custom-scroll::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 10px;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        /* Badge styles */
        .badge-active {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }

        .badge-expired {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-50 via-indigo-50/30 to-blue-50/40 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 text-gray-800 dark:text-gray-200">
    <!-- ======================================== -->
    <!-- PAGE LOADER - Include right after body -->
    <!-- ======================================== -->
    <?php include "./include/loader.php"; ?>
    <div class="flex flex-col min-h-screen overflow-hidden">

        <!-- Overlay for mobile sidebar -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black opacity-0 z-40 hidden lg:hidden transition-opacity duration-300"></div>

        <!-- Sidebar -->
        <?php include "./include/sidebar.php" ?>

        <!-- Main Content Wrapper -->
        <div id="main-content-wrapper" class="flex-1 flex flex-col w-full">

            <!-- Header -->
            <?php include "./include/header.php" ?>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto py-3 px-8 w-full min-h-screen custom-scroll">
                <!-- Breadcrumbs -->
                <?php include "./include/breadcrumbs.php" ?>

                <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">

                    <!-- Modern Hero Section -->
                    <div class="relative mb-20">
                        <!-- Animated Banner -->
                        <div class="gradient-banner h-48 w-full rounded-3xl overflow-hidden shadow-2xl">
                            <div class="absolute inset-0 bg-black/20"></div>
                            <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=" 60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg" %3E%3Cg fill="none" fill-rule="evenodd" %3E%3Cg fill="%23ffffff" fill-opacity="0.08" %3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z" /%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-30"></div>
                        </div>

                        <!-- Floating Avatar Card -->
                        <div class="absolute -bottom-16 left-8 flex items-end gap-6">
                            <div class="relative group">
                                <div class="w-32 h-32 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 border-4 border-white dark:border-gray-900 shadow-2xl flex items-center justify-center avatar-pulse">
                                    <span id="avatar-initials" class="text-4xl font-bold text-white"><?= safe(getUserInitials($userData['name'] ?? $sessionUser['name'] ?? 'User')) ?></span>
                                </div>
                            </div>
                            <div class="mb-4">
                                <h1 id="profile-name" class="text-3xl font-bold text-white drop-shadow-lg capitalize"><?= safe(ucfirst($userData['name'] ?? $sessionUser['name'] ?? 'User')) ?></h1>
                                <div class="flex items-center gap-2 mt-1">
                                    <div class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></div>
                                    <p id="profile-email" class="dark:text-indigo-100 text-indigo-500 font-medium"><?= safe($userData['email'] ?? $sessionUser['email'] ?? 'user@example.com') ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Profile Button -->
                        <div class="absolute top-4 right-4">
                            <button id="edit-profile-btn" class="flex items-center gap-2 px-5 py-2.5 bg-white/20 backdrop-blur-md text-white rounded-xl border border-white/30 hover:bg-white/30 transition-all font-medium shadow-lg">
                                <i data-lucide="edit-2" class="w-4 h-4"></i>
                                Edit Profile
                            </button>
                        </div>
                    </div>

                    <!-- Content Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 pt-12">

                        <!-- Left Column -->
                        <div class="lg:col-span-1 space-y-6">
                            <!-- Quick Overview Card -->
                            <div class="glass-card rounded-3xl p-6">
                                <div class="flex items-center gap-2 mb-6">
                                    <div class="p-2 bg-indigo-100 dark:bg-indigo-500/20 rounded-xl">
                                        <i data-lucide="gauge" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Overview</h3>
                                </div>
                                <div class="space-y-4">
                                    <div class="flex items-center gap-4 p-3 rounded-2xl hover:bg-white/40 dark:hover:bg-white/5 transition-all cursor-default group">
                                        <div class="p-2.5 rounded-xl bg-purple-100 text-purple-600 dark:bg-purple-500/20 group-hover:scale-110 transition-transform">
                                            <i data-lucide="shield" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Role</p>
                                            <p id="overview-role" class="font-bold text-gray-900 dark:text-white text-lg capitalize"><?= safe($userData['user_role'] ?? $sessionUser['role'] ?? 'customer') ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4 p-3 rounded-2xl hover:bg-white/40 dark:hover:bg-white/5 transition-all cursor-default group">
                                        <div class="p-2.5 rounded-xl bg-green-100 text-green-600 dark:bg-green-500/20 group-hover:scale-110 transition-transform">
                                            <i data-lucide="calendar" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Joined</p>
                                            <p class="font-bold text-gray-900 dark:text-white"><?= date('F Y', strtotime($userData['created_at'] ?? 'now')) ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4 p-3 rounded-2xl hover:bg-white/40 dark:hover:bg-white/5 transition-all cursor-default group">
                                        <div class="p-2.5 rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-500/20 group-hover:scale-110 transition-transform">
                                            <i data-lucide="package" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Current Package</p>
                                            <p class="font-bold text-gray-900 dark:text-white"><?= safe($userData['package_name'] ?? 'No Active Package') ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Information Card -->
                            <div class="glass-card rounded-3xl p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                    <i data-lucide="contact" class="w-5 h-5 text-blue-500"></i>
                                    Contact Information
                                </h3>
                                <div class="space-y-4">
                                    <div class="flex items-center gap-3 text-gray-600 dark:text-gray-300 p-2 rounded-xl hover:bg-white/30 transition">
                                        <i data-lucide="mail" class="w-[18px] h-[18px] text-blue-500 flex-shrink-0"></i>
                                        <span id="contact-email" class="font-medium truncate"><?= safe($userData['email'] ?? $sessionUser['email'] ?? 'N/A') ?></span>
                                    </div>
                                    <div class="flex items-center gap-3 text-gray-600 dark:text-gray-300 p-2 rounded-xl hover:bg-white/30 transition">
                                        <i data-lucide="user" class="w-[18px] h-[18px] text-blue-500 flex-shrink-0"></i>
                                        <span id="contact-username" class="font-medium">@<?= safe(explode('@', ($userData['email'] ?? $sessionUser['email'] ?? 'user'))[0]) ?></span>
                                    </div>
                                    <div class="flex items-start gap-3 text-gray-600 dark:text-gray-300 p-2 rounded-xl hover:bg-white/30 transition">
                                        <i data-lucide="map-pin" class="w-[18px] h-[18px] text-blue-500 flex-shrink-0 mt-0.5"></i>
                                        <span id="contact-address" class="font-medium"><?= safe($userData['address'] ?? 'No address provided') ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Subscription Status Card -->
                            <?php if (!empty($userData['package_name']) && ($userData['subscription_status'] ?? '') === 'active'): ?>
                                <div class="glass-card rounded-3xl p-6 bg-gradient-to-br from-blue-50/50 to-indigo-50/50 dark:from-gray-800/80 dark:to-gray-800/80">
                                    <div class="flex justify-between items-start mb-4">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Subscription Details</h3>
                                        <span class="px-3 py-1 rounded-full text-xs font-bold badge-active text-white">ACTIVE</span>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Plan</span>
                                            <span class="font-bold text-gray-900 dark:text-white"><?= safe($userData['package_name']) ?></span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Price</span>
                                            <span class="font-bold text-green-600 dark:text-green-400">$<?= safe($userData['package_price']) ?></span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Valid Until</span>
                                            <span class="font-bold text-gray-900 dark:text-white"><?= date('M d, Y', strtotime($userData['end_date'])) ?></span>
                                        </div>
                                        <?php $daysLeft = getDaysRemaining($userData['end_date'] ?? null); ?>
                                        <div class="mt-3 pt-2">
                                            <div class="flex justify-between items-center mb-2">
                                                <span class="text-sm text-gray-600 dark:text-gray-400">Days Remaining</span>
                                                <span class="font-bold <?= ($daysLeft ?? 0) <= 7 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' ?>"><?= $daysLeft ?? 0 ?> days</span>
                                            </div>
                                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                                                <?php
                                                if (!empty($userData['start_date']) && !empty($userData['end_date'])) {
                                                    $totalDays = (new DateTime($userData['start_date']))->diff(new DateTime($userData['end_date']))->days;
                                                    $percent = $totalDays > 0 ? (($daysLeft ?? 0) / $totalDays) * 100 : 0;
                                                } else {
                                                    $percent = 0;
                                                }
                                                ?>
                                                <div class="bg-gradient-to-r from-blue-500 to-indigo-500 h-2.5 rounded-full transition-all" style="width: <?= $percent ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Right Column -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Account Settings Card -->
                            <div class="glass-card rounded-3xl p-8">
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 border-b border-gray-200 dark:border-gray-700 pb-4 flex items-center gap-2">
                                    <i data-lucide="settings" class="w-5 h-5 text-indigo-500"></i>
                                    Account Settings
                                </h3>
                                <form id="profile-form" class="space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <label class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Full Name</label>
                                            <input id="form-name" type="text" value="<?= safe($userData['name'] ?? $sessionUser['name'] ?? '') ?>" class="modern-input w-full px-4 py-3 rounded-xl focus:outline-none transition-all" placeholder="Your full name" />
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Email Address</label>
                                            <input id="form-email" type="email" value="<?= safe($userData['email'] ?? $sessionUser['email'] ?? '') ?>" disabled class="w-full px-4 py-3 rounded-xl bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 opacity-70 cursor-not-allowed" placeholder="Email" />
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Address</label>
                                        <textarea id="form-bio" rows="4" placeholder="Enter your address..." class="modern-input w-full px-4 py-3 rounded-xl focus:outline-none transition-all resize-none"><?= safe($userData['address'] ?? '') ?></textarea>
                                    </div>
                                    <div class="flex justify-end pt-4">
                                        <button type="submit" id="save-btn" class="btn-modern px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-blue-200 dark:shadow-none transition-all">
                                            <i data-lucide="save" class="w-4 h-4 inline mr-2"></i>
                                            Save Changes
                                        </button>
                                    </div>
                                    <div id="save-success" class="hidden p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl text-green-700 dark:text-green-400 text-sm font-medium animate-pulse">
                                        ✓ Profile updated successfully!
                                    </div>
                                </form>

                                <!-- Change Password Section -->
                                <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                                    <h3 class="text-xl font-bold mb-6 flex items-center gap-2">
                                        <i data-lucide="key" class="w-5 h-5 text-amber-500"></i>
                                        Change Password
                                    </h3>
                                    <form id="password-form" class="space-y-4">
                                        <input type="password" id="current-password" placeholder="Current Password"
                                            class="modern-input w-full px-4 py-3 rounded-xl focus:outline-none transition-all" />
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <input type="password" id="new-password" placeholder="New Password"
                                                class="modern-input w-full px-4 py-3 rounded-xl focus:outline-none transition-all" />
                                            <input type="password" id="confirm-password" placeholder="Confirm Password"
                                                class="modern-input w-full px-4 py-3 rounded-xl focus:outline-none transition-all" />
                                        </div>
                                        <button type="submit"
                                            class="btn-modern px-6 py-3 bg-gradient-to-r from-amber-500 to-red-500 hover:from-amber-600 hover:to-red-600 text-white rounded-xl font-medium transition-all shadow-md">
                                            <i data-lucide="lock" class="w-4 h-4 inline mr-2"></i>
                                            Update Password
                                        </button>
                                    </form>
                                </div>
                                <!-- Subscription History Card -->
                                <div class="glass-card rounded-3xl p-8" <?php if (!empty($subscriptionHistory)): ?> style="display: block;" <?php else: ?> style="display: none;" <?php endif; ?>>
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 border-b border-gray-200 dark:border-gray-700 pb-4 flex items-center gap-2">
                                        <i data-lucide="history" class="w-5 h-5 text-purple-500"></i>
                                        Subscription History
                                    </h3>
                                    <div class="overflow-x-auto">
                                        <table class="table-modern w-full">
                                            <thead>
                                                <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                                                    <th class="text-left py-3 px-2 text-sm font-semibold text-gray-600 dark:text-gray-400">Package</th>
                                                    <th class="text-left py-3 px-2 text-sm font-semibold text-gray-600 dark:text-gray-400">Start Date</th>
                                                    <th class="text-left py-3 px-2 text-sm font-semibold text-gray-600 dark:text-gray-400">End Date</th>
                                                    <th class="text-left py-3 px-2 text-sm font-semibold text-gray-600 dark:text-gray-400">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody id="subscription-history">
                                                <tr>
                                                    <td colspan="4" class="text-center py-8 text-gray-500">Loading subscription history...</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            </main>

            <!-- Footer -->
            <?php include "./include/footer.php" ?>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-5 right-5 z-50 hidden">
        <div class="glass-card rounded-xl shadow-2xl border-l-4 border-green-500 p-4 min-w-[280px] toast-modern">
            <div class="flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5 text-green-500"></i>
                <span id="toast-message" class="text-gray-700 dark:text-gray-300 font-medium">Profile updated!</span>
            </div>
        </div>
    </div>

    <!-- footer links -->
    <?php include "./include/footerLinks.php" ?>
    <?php include "./include/subscription-modals.php" ?>
    <script src="node_modules/simple-datatables/dist/umd/simple-datatables.js"></script>
    <script type="module" src="assets/js/subscriptions.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Lucide icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            // Fetch subscription history
            fetchSubscriptionHistory();

            // Profile form submission
            const profileForm = document.getElementById('profile-form');
            const saveBtn = document.getElementById('save-btn');
            const successMsg = document.getElementById('save-success');

            profileForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const name = document.getElementById('form-name').value.trim();
                const bio = document.getElementById('form-bio').value.trim();

                if (!name) {
                    showToast('Name is required', 'error');
                    return;
                }

                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i data-lucide="loader-circle" class="w-5 h-5 animate-spin inline mr-2"></i>Saving...';

                try {
                    const formData = new URLSearchParams();
                    formData.append('action', 'update_profile');
                    formData.append('name', name);
                    formData.append('bio', bio);

                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData.toString()
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Update UI with new data
                        document.getElementById('profile-name').textContent = name.charAt(0).toUpperCase() + name.slice(1);
                        document.getElementById('contact-address').textContent = bio || 'No address provided';

                        // Update avatar initials
                        const initials = name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
                        document.getElementById('avatar-initials').textContent = initials || 'U';

                        successMsg.classList.remove('hidden');
                        showToast('Profile updated successfully!', 'success');

                        setTimeout(() => {
                            successMsg.classList.add('hidden');
                        }, 3000);
                    } else {
                        showToast(data.message || 'Failed to update profile', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('An error occurred. Please try again.', 'error');
                } finally {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i data-lucide="save" class="w-4 h-4 inline mr-2"></i>Save Changes';
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            });

            // Edit profile button - scroll to form
            document.getElementById('edit-profile-btn').addEventListener('click', function() {
                document.getElementById('profile-form').scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                document.getElementById('form-name').focus();
            });

            // Function to fetch subscription history
            async function fetchSubscriptionHistory() {
                try {
                    const response = await fetch('/pakistan-cable/controller/subscription/get_subscription_history.php?user_id=<?= $userId ?>');
                    const data = await response.json();

                    const tbody = document.getElementById('subscription-history');

                    if (data.success && data.subscriptions && data.subscriptions.length > 0) {
                        tbody.innerHTML = data.subscriptions.map(sub => `
                            <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-all">
                                <td class="py-3 px-2 font-medium text-gray-800 dark:text-gray-200">${escapeHtml(sub.package_name)}</td>
                                <td class="py-3 px-2 text-gray-600 dark:text-gray-400">${escapeHtml(sub.start_date) || 'N/A'}</td>
                                <td class="py-3 px-2 text-gray-600 dark:text-gray-400">${escapeHtml(sub.end_date) || 'N/A'}</td>
                                <td class="py-3 px-2">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium ${
                                        sub.status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' :
                                        sub.status === 'expired' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' :
                                        'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                    }">
                                        ${escapeHtml(sub.status) || 'N/A'}
                                    </span>
                                </td>
                            </tr>
                        `).join('');
                    } else {
                        tbody.innerHTML = '<tr><td colspan="4" class="text-center py-8 text-gray-500">No subscription history found</td></tr>';
                    }
                } catch (error) {
                    console.error('Error fetching subscription history:', error);
                    document.getElementById('subscription-history').innerHTML = '<tr><td colspan="4" class="text-center py-8 text-gray-500">Failed to load subscription history</td></tr>';
                }
            }

            // Helper function to escape HTML
            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Show toast notification
            function showToast(message, type = 'success') {
                const toast = document.getElementById('toast');
                const toastMessage = document.getElementById('toast-message');
                const toastIcon = toast.querySelector('i');

                toastMessage.textContent = message;

                if (type === 'error') {
                    toastIcon.setAttribute('data-lucide', 'alert-circle');
                    toast.querySelector('.border-l-4').classList.remove('border-green-500');
                    toast.querySelector('.border-l-4').classList.add('border-red-500');
                    toastIcon.classList.add('text-red-500');
                    toastIcon.classList.remove('text-green-500');
                } else {
                    toastIcon.setAttribute('data-lucide', 'check-circle');
                    toast.querySelector('.border-l-4').classList.remove('border-red-500');
                    toast.querySelector('.border-l-4').classList.add('border-green-500');
                    toastIcon.classList.remove('text-red-500');
                    toastIcon.classList.add('text-green-500');
                }

                if (typeof lucide !== 'undefined') lucide.createIcons();

                toast.classList.remove('hidden');
                setTimeout(() => {
                    toast.classList.add('hidden');
                }, 3000);
            }
        });

        // Password form submission
        document.getElementById('password-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            const current = document.getElementById('current-password').value;
            const newPass = document.getElementById('new-password').value;
            const confirm = document.getElementById('confirm-password').value;

            if (!current || !newPass || !confirm) {
                showToast('All fields required', 'error');
                return;
            }

            if (newPass !== confirm) {
                showToast('New passwords do not match', 'error');
                return;
            }

            if (newPass.length < 6) {
                showToast('Password must be at least 6 characters', 'error');
                return;
            }

            try {
                const formData = new URLSearchParams();
                formData.append('action', 'update_password');
                formData.append('current_password', current);
                formData.append('new_password', newPass);
                formData.append('confirm_password', confirm);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData.toString()
                });

                const data = await response.json();

                if (data.success) {
                    showToast('Password updated successfully!', 'success');
                    document.getElementById('password-form').reset();
                } else {
                    showToast(data.message, 'error');
                }

            } catch (err) {
                console.error(err);
                showToast('An error occurred', 'error');
            }
        });
    </script>
</body>

</html>