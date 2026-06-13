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
    
    echo json_encode($response);
    exit();
}

// Helper function to get user initials
function getUserInitials($name) {
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
function getDaysRemaining($endDate) {
    if (!$endDate) return null;
    $now = new DateTime();
    $end = new DateTime($endDate);
    if ($now > $end) return 0;
    return $now->diff($end)->days;
}

// Get safe value for display
function safe($value, $default = '') {
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
    <?php $appName = getenv('APP_NAME') ?: 'AppName'; ?>
    <title><?= safe($appName) ?> - Dashboard</title>

    <!-- header links -->
    <?php include "./include/headerLinks.php" ?>
    <link rel="stylesheet" href="assets/css/datePicker.css">
    <style>
        /* Custom animations */
        .transition-all {
            transition-duration: 0.2s;
        }
        .hover-scale:hover {
            transform: scale(1.02);
        }
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }
        .toast-notification {
            animation: slideInRight 0.3s ease-out;
        }
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
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

                <div class="max-w-6xl mx-auto p-4 sm:p-6 lg:p-8">

                    <!-- Hero Banner + Avatar -->
                    <div class="relative mb-8">
                        <!-- Banner -->
                        <div class="h-48 w-full bg-gradient-to-r from-blue-600 to-indigo-700 rounded-3xl overflow-hidden shadow-lg relative">
                            <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.35) 1px, transparent 0); background-size: 24px 24px;"></div>
                        </div>

                        <!-- Avatar + Name -->
                        <div class="absolute -bottom-12 left-8 flex items-end gap-6">
                            <div class="relative group">
                                <div class="w-32 h-32 rounded-2xl border-4 border-white dark:border-gray-900 bg-gradient-to-br from-blue-500 to-indigo-600 overflow-hidden shadow-xl flex items-center justify-center">
                                    <span id="avatar-initials" class="text-4xl font-bold text-white"><?= safe(getUserInitials($userData['name'] ?? $sessionUser['name'] ?? 'User')) ?></span>
                                </div>
                                <button class="absolute -bottom-2 -right-2 p-2 bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-100 dark:border-gray-700 hover:text-blue-600 transition-colors">
                                    <i data-lucide="camera" class="w-[18px] h-[18px]"></i>
                                </button>
                            </div>
                            <div class="mb-4">
                                <h1 id="profile-name" class="text-3xl font-bold text-white capitalize"><?= safe(ucfirst($userData['name'] ?? $sessionUser['name'] ?? 'User')) ?></h1>
                                <p id="profile-email" class="text-blue-100 font-medium"><?= safe($userData['email'] ?? $sessionUser['email'] ?? 'user@example.com') ?></p>
                            </div>
                        </div>

                        <!-- Edit Profile Button -->
                        <div class="absolute top-4 right-4">
                            <button id="edit-profile-btn" class="flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-md text-white rounded-xl border border-white/30 hover:bg-white/30 transition-all font-medium">
                                <i data-lucide="edit-2" class="w-4 h-4"></i>
                                Edit Profile
                            </button>
                        </div>
                    </div>

                    <!-- Content Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 pt-12">

                        <!-- Left Column -->
                        <div class="lg:col-span-1 space-y-6">
                            <!-- Quick Overview -->
                            <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Quick Overview</h3>
                                <div class="space-y-4">
                                    <div class="flex items-center gap-4 p-3 rounded-2xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-default">
                                        <div class="p-3 rounded-xl bg-purple-100 text-purple-600 dark:bg-gray-700">
                                            <i data-lucide="shield" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Role</p>
                                            <p id="overview-role" class="font-bold text-gray-900 dark:text-white capitalize"><?= safe($userData['user_role'] ?? $sessionUser['role'] ?? 'customer') ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4 p-3 rounded-2xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-default">
                                        <div class="p-3 rounded-xl bg-green-100 text-green-600 dark:bg-gray-700">
                                            <i data-lucide="calendar" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Joined</p>
                                            <p class="font-bold text-gray-900 dark:text-white"><?= date('F Y', strtotime($userData['created_at'] ?? 'now')) ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4 p-3 rounded-2xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-default">
                                        <div class="p-3 rounded-xl bg-amber-100 text-amber-600 dark:bg-gray-700">
                                            <i data-lucide="package" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Current Package</p>
                                            <p class="font-bold text-gray-900 dark:text-white"><?= safe($userData['package_name'] ?? 'No Active Package') ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Contact Information</h3>
                                <div class="space-y-4">
                                    <div class="flex items-center gap-3 text-gray-600 dark:text-gray-300">
                                        <i data-lucide="mail" class="w-[18px] h-[18px] text-blue-500 flex-shrink-0"></i>
                                        <span id="contact-email" class="font-medium truncate"><?= safe($userData['email'] ?? $sessionUser['email'] ?? 'N/A') ?></span>
                                    </div>
                                    <div class="flex items-center gap-3 text-gray-600 dark:text-gray-300">
                                        <i data-lucide="user" class="w-[18px] h-[18px] text-blue-500 flex-shrink-0"></i>
                                        <span id="contact-username" class="font-medium">@<?= safe(explode('@', ($userData['email'] ?? $sessionUser['email'] ?? 'user'))[0]) ?></span>
                                    </div>
                                    <div class="flex items-start gap-3 text-gray-600 dark:text-gray-300">
                                        <i data-lucide="map-pin" class="w-[18px] h-[18px] text-blue-500 flex-shrink-0 mt-0.5"></i>
                                        <span id="contact-address" class="font-medium"><?= safe($userData['address'] ?? 'No address provided') ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Subscription Status -->
                            <?php if (!empty($userData['package_name']) && ($userData['subscription_status'] ?? '') === 'active'): ?>
                            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-800 p-6 rounded-3xl shadow-sm border border-blue-100 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Subscription Details</h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Plan</span>
                                        <span class="font-bold text-gray-900 dark:text-white"><?= safe($userData['package_name']) ?></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Price</span>
                                        <span class="font-bold text-gray-900 dark:text-white">$<?= safe($userData['package_price']) ?></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Valid Until</span>
                                        <span class="font-bold text-gray-900 dark:text-white"><?= date('M d, Y', strtotime($userData['end_date'])) ?></span>
                                    </div>
                                    <?php $daysLeft = getDaysRemaining($userData['end_date'] ?? null); ?>
                                    <div class="mt-3 pt-3 border-t border-blue-200 dark:border-gray-700">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Days Remaining</span>
                                            <span class="font-bold <?= ($daysLeft ?? 0) <= 7 ? 'text-red-600' : 'text-green-600' ?>"><?= $daysLeft ?? 0 ?> days</span>
                                        </div>
                                        <div class="mt-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <?php 
                                            if (!empty($userData['start_date']) && !empty($userData['end_date'])) {
                                                $totalDays = (new DateTime($userData['start_date']))->diff(new DateTime($userData['end_date']))->days;
                                                $percent = $totalDays > 0 ? (($daysLeft ?? 0) / $totalDays) * 100 : 0;
                                            } else {
                                                $percent = 0;
                                            }
                                            ?>
                                            <div class="bg-blue-600 h-2 rounded-full" style="width: <?= $percent ?>%"></div>
                                        </div>
                                    </div>
                                    <?php if (($daysLeft ?? 0) <= 7): ?>
                                        <button class="mt-4 w-full py-2 bg-red-500 hover:bg-red-600 text-white rounded-xl font-medium transition-colors">
                                            Renew Now
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-800 p-6 rounded-3xl shadow-sm border border-gray-200 dark:border-gray-700">
                                <div class="text-center">
                                    <i data-lucide="credit-card" class="w-12 h-12 text-gray-400 mx-auto mb-3"></i>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">No Active Subscription</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Subscribe to a package to unlock premium features</p>
                                    <button class="mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium transition-colors">
                                        View Packages
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Right Column -->
                        <div class="lg:col-span-2 space-y-6">
                            <div class="bg-white dark:bg-gray-800 p-8 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all">
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 border-b border-gray-100 dark:border-gray-700 pb-4">Account Settings</h3>
                                <form id="profile-form" class="space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <label class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Full Name</label>
                                            <input id="form-name" type="text" value="<?= safe($userData['name'] ?? $sessionUser['name'] ?? '') ?>" class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 outline-none transition-all" placeholder="Your full name" />
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Email Address</label>
                                            <input id="form-email" type="email" value="<?= safe($userData['email'] ?? $sessionUser['email'] ?? '') ?>" disabled class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900 bg-gray-50 dark:bg-gray-800 opacity-70 cursor-not-allowed" placeholder="Email" />
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Address / Biography</label>
                                        <textarea id="form-bio" rows="4" placeholder="Tell us about yourself or enter your address..." class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 outline-none transition-all resize-none"><?= safe($userData['address'] ?? '') ?></textarea>
                                    </div>
                                    <div class="flex justify-end pt-4">
                                        <button type="submit" id="save-btn" class="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-blue-200 dark:shadow-none transition-all active:scale-95">
                                            Save Changes
                                        </button>
                                    </div>
                                    <div id="save-success" class="hidden p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl text-green-700 dark:text-green-400 text-sm font-medium">
                                        ✓ Profile updated successfully!
                                    </div>
                                </form>
                            </div>

                            <!-- Recent Activity / Subscription History -->
                            <div class="bg-white dark:bg-gray-800 p-8 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 border-b border-gray-100 dark:border-gray-700 pb-4">Subscription History</h3>
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                                <th class="text-left py-3 px-2 text-sm font-semibold text-gray-600 dark:text-gray-400">Package</th>
                                                <th class="text-left py-3 px-2 text-sm font-semibold text-gray-600 dark:text-gray-400">Start Date</th>
                                                <th class="text-left py-3 px-2 text-sm font-semibold text-gray-600 dark:text-gray-400">End Date</th>
                                                <th class="text-left py-3 px-2 text-sm font-semibold text-gray-600 dark:text-gray-400">Status</th>
                                             </tr>
                                        </thead>
                                        <tbody id="subscription-history">
                                            <tr>
                                                <td colspan="4" class="text-center py-6 text-gray-500">Loading subscription history...</td>
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
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border-l-4 border-green-500 p-4 min-w-[280px]">
            <div class="flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5 text-green-500"></i>
                <span id="toast-message" class="text-gray-700 dark:text-gray-300">Profile updated!</span>
            </div>
        </div>
    </div>

    <!-- footer links  -->
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
                    showToast('Please enter your name', 'error');
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
                        document.getElementById('avatar-initials').textContent = initials;
                        
                        successMsg.classList.remove('hidden');
                        showToast('Profile updated successfully!');
                        
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
                    saveBtn.innerHTML = 'Save Changes';
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            });
            
            // Edit profile button - scroll to form
            document.getElementById('edit-profile-btn').addEventListener('click', function() {
                document.getElementById('profile-form').scrollIntoView({ behavior: 'smooth', block: 'start' });
                document.getElementById('form-name').focus();
            });
            
            // Function to fetch subscription history
            async function fetchSubscriptionHistory() {
                try {
                    const response = await fetch('get_subscription_history.php?user_id=<?= $userId ?>');
                    const data = await response.json();
                    
                    const tbody = document.getElementById('subscription-history');
                    
                    if (data.success && data.subscriptions && data.subscriptions.length > 0) {
                        tbody.innerHTML = data.subscriptions.map(sub => `
                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                <td class="py-3 px-2 font-medium text-gray-800 dark:text-gray-200">${escapeHtml(sub.package_name)}</td>
                                <td class="py-3 px-2 text-gray-600 dark:text-gray-400">${escapeHtml(sub.start_date) || 'N/A'}</td>
                                <td class="py-3 px-2 text-gray-600 dark:text-gray-400">${escapeHtml(sub.end_date) || 'N/A'}</td>
                                <td class="py-3 px-2">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium ${
                                        sub.status === 'active' ? 'bg-green-100 text-green-700' :
                                        sub.status === 'expired' ? 'bg-red-100 text-red-700' :
                                        'bg-gray-100 text-gray-700'
                                    }">
                                        ${escapeHtml(sub.status) || 'N/A'}
                                    </span>
                                </td>
                            </tr>
                        `).join('');
                    } else {
                        tbody.innerHTML = '<tr><td colspan="4" class="text-center py-6 text-gray-500">No subscription history found</td></tr>';
                    }
                } catch (error) {
                    console.error('Error fetching subscription history:', error);
                    document.getElementById('subscription-history').innerHTML = '<tr><td colspan="4" class="text-center py-6 text-gray-500">Failed to load subscription history</td></tr>';
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
                const borderDiv = toast.querySelector('.border-l-4');
                const icon = toast.querySelector('.text-green-500');
                
                toastMessage.textContent = message;
                
                if (type === 'error') {
                    borderDiv.className = 'border-l-4 border-red-500 p-4 min-w-[280px]';
                    if (icon) icon.className = 'w-5 h-5 text-red-500';
                } else {
                    borderDiv.className = 'border-l-4 border-green-500 p-4 min-w-[280px]';
                    if (icon) icon.className = 'w-5 h-5 text-green-500';
                }
                
                toast.classList.remove('hidden');
                
                setTimeout(() => {
                    toast.classList.add('hidden');
                    if (type === 'error' && icon) icon.className = 'w-5 h-5 text-green-500';
                    if (type === 'error') borderDiv.className = 'border-l-4 border-green-500 p-4 min-w-[280px]';
                }, 3000);
            }
        });
    </script>
</body>

</html>