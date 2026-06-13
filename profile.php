<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logic Blaze - Profile</title>
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <script src="node_modules/lucide/dist/umd/lucide.min.js"></script>
    <link rel="stylesheet" href="node_modules/perfect-scrollbar/css/perfect-scrollbar.css"/>
    <link rel="stylesheet" href="assets/css/animation.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-in { animation: fadeIn 0.5s ease forwards; }
    </style>
</head>
<body class="bg-[#f3f4f4] text-gray-800 dark:text-gray-200 transition-colors duration-300">
    <div class="flex flex-col min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 dark:from-slate-900 dark:to-slate-800 overflow-hidden">

        <!-- Overlay for mobile sidebar -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black opacity-0 z-40 hidden lg:hidden transition-opacity duration-300"></div>

        <!-- Sidebar -->
        <?php include './include/sidebar.php'; ?>

        <!-- Main Content Wrapper -->
        <div id="main-content-wrapper" class="flex-1 flex flex-col transition-all duration-300 w-full lg:ml-[260px] lg:w-[calc(100vw-260px)]">
            <!-- Header -->
            <header class="sticky top-0 z-30 backdrop-blur-md border-b border-gray-200 dark:border-gray-800 bg-[#f3f4f4]/70 dark:bg-gray-900/70">
                <div class="flex justify-between items-center px-6 sm:px-10 py-3">
                    <div class="flex items-center">
                        <button id="mobile-menu-toggle" class="mr-4 p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 lg:hidden">
                            <i data-lucide="menu" class="w-6 h-6 text-gray-600 dark:text-gray-300"></i>
                        </button>
                        <nav class="text-sm text-gray-500 dark:text-gray-400 hidden sm:flex items-center gap-2">
                            <a href="index.html" class="hover:text-blue-500">Home</a>
                            <i data-lucide="chevron-right" class="w-4 h-4"></i>
                            <span class="text-gray-800 dark:text-gray-200 font-medium">Profile</span>
                        </nav>
                    </div>
                    <div class="flex gap-4 items-center">
                        <!-- Notification -->
                        <div class="relative">
                            <button id="notification-btn" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors relative">
                                <i data-lucide="bell" class="w-6 h-6 text-gray-600 dark:text-gray-300"></i>
                                <span class="absolute right-0 top-0 rounded-full bg-red-500 text-white text-[10px] w-4 h-4 flex items-center justify-center">3</span>
                            </button>
                            <div id="notification-dropdown" class="hidden absolute right-0 mt-2 w-72 bg-white dark:bg-gray-800 shadow-xl rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden z-50 opacity-0 scale-95 transform transition-all duration-200 origin-top-right">
                                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Notifications</h3>
                                    <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100 rounded-full">3 new</span>
                                </div>
                                <div class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">No new notifications</div>
                            </div>
                        </div>
                        <!-- Profile Avatar -->
                        <div class="relative">
                            <div class="relative inline-block w-10 h-10">
                                <button id="profile-btn" class="w-10 h-10 rounded-full flex items-center font-semibold text-base select-none shadow-sm hover:shadow-md bg-gray-200 justify-center text-gray-500 mx-auto border-2 dark:bg-gray-700 dark:text-gray-400 border-white dark:border-gray-800">
                                    <span id="avatar-initials" class="leading-none">JD</span>
                                </button>
                                <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-green-400 border-2 border-white dark:border-gray-800"></span>
                            </div>
                            <div id="profile-dropdown" class="hidden absolute right-0 mt-2 w-[300px] bg-white dark:bg-gray-800 shadow-xl rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden z-50 opacity-0 scale-95 transform transition-all duration-200 origin-top-right">
                                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center gap-3">
                                        <div class="relative">
                                            <div id="dropdown-avatar" class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold text-lg dark:bg-gray-700 dark:text-gray-400 border-2 border-white dark:border-gray-800">JD</div>
                                            <span class="absolute bottom-0 right-0 w-3 h-3 rounded-full bg-green-400 border-2 border-white dark:border-gray-800"></span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200" id="dropdown-name">John Doe</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400" id="dropdown-email">admin@example.com</p>
                                            <p class="text-xs text-gray-300 dark:text-gray-500" id="dropdown-role">Super Admin</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="py-2">
                                    <a href="profile.html" class="w-full flex items-center px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors rounded-md mx-2">
                                        <i data-lucide="user" class="w-[18px] h-[18px] mr-3 text-gray-500"></i> Profile
                                    </a>
                                    <button id="theme-toggle" class="w-full flex items-center justify-between px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors rounded-md mx-2">
                                        <div class="flex items-center">
                                            <i data-lucide="moon" class="w-[18px] h-[18px] mr-3 text-gray-500" id="theme-icon"></i>
                                            <span id="theme-text">Dark Mode</span>
                                        </div>
                                        <div class="relative w-10 h-5 flex items-center bg-gray-300 dark:bg-blue-500 rounded-full p-1 transition-colors duration-300">
                                            <div id="theme-toggle-circle" class="w-3 h-3 rounded-full bg-white shadow-md transform transition-transform duration-300 translate-x-0 dark:translate-x-5"></div>
                                        </div>
                                    </button>
                                </div>
                                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                                    <button id="logout-btn" class="flex justify-center items-center gap-2 w-full px-4 py-2 text-sm text-white rounded-md bg-red-500 hover:opacity-90">
                                        <i data-lucide="log-out" class="w-4 h-4 mr-2"></i> Logout
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Profile Page Content (matches ProfilePage.jsx exactly) -->
            <main class="flex-1 py-3 px-8 w-full animate-in">
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
                                <div class="w-32 h-32 rounded-2xl border-4 border-white dark:border-gray-900 bg-white dark:bg-gray-800 overflow-hidden shadow-xl">
                                    <img id="profile-avatar-img" src="" alt="Profile" class="w-full h-full object-cover"/>
                                </div>
                                <button class="absolute -bottom-2 -right-2 p-2 bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-100 dark:border-gray-700 hover:text-blue-600 transition-colors">
                                    <i data-lucide="camera" class="w-[18px] h-[18px]"></i>
                                </button>
                            </div>
                            <div class="mb-4">
                                <h1 id="profile-name" class="text-3xl font-bold text-white capitalize">John Doe</h1>
                                <p id="profile-email" class="text-blue-100 font-medium">admin@example.com</p>
                            </div>
                        </div>

                        <!-- Edit Profile Button -->
                        <div class="absolute top-4 right-4">
                            <button class="flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-md text-white rounded-xl border border-white/30 hover:bg-white/30 transition-all font-medium">
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
                                            <p id="overview-role" class="font-bold text-gray-900 dark:text-white">Super Admin</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4 p-3 rounded-2xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-default">
                                        <div class="p-3 rounded-xl bg-green-100 text-green-600 dark:bg-gray-700">
                                            <i data-lucide="calendar" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Joined</p>
                                            <p class="font-bold text-gray-900 dark:text-white">January 2024</p>
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
                                        <span id="contact-email" class="font-medium truncate">admin@example.com</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-gray-600 dark:text-gray-300">
                                        <i data-lucide="user" class="w-[18px] h-[18px] text-blue-500 flex-shrink-0"></i>
                                        <span id="contact-username" class="font-medium">@johndoe</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="lg:col-span-2 space-y-6">
                            <div class="bg-white dark:bg-gray-800 p-8 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all">
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 border-b border-gray-100 dark:border-gray-700 pb-4">Account Settings</h3>
                                <form id="profile-form" class="space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <label class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Full Name</label>
                                            <input id="form-name" type="text" class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 outline-none transition-all" placeholder="Your full name"/>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Email Address</label>
                                            <input id="form-email" type="email" disabled class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900 bg-gray-50 dark:bg-gray-800 opacity-70 cursor-not-allowed" placeholder="Email"/>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Biography</label>
                                        <textarea rows="4" placeholder="Tell us about yourself..." class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 outline-none transition-all resize-none"></textarea>
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
                        </div>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <div class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 p-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">© 2024 Logic Blaze. All rights reserved.</div>
            </div>
        </div>
    </div>

    <script src="node_modules/perfect-scrollbar/dist/perfect-scrollbar.min.js"></script>
    <script src="assets/js/button-loading.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        // ─── Load user from localStorage ────────────────────────────────────
        function createAvatarDataUri(name) {
            const safeName = String(name || 'Profile').trim();
            const initials = safeName
                .split(/\s+/)
                .filter(Boolean)
                .slice(0, 2)
                .map((part) => part.charAt(0))
                .join('')
                .toUpperCase() || 'P';

            const svg = `
                <svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128">
                    <defs>
                        <linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#2563eb"/>
                            <stop offset="100%" stop-color="#4f46e5"/>
                        </linearGradient>
                    </defs>
                    <rect width="128" height="128" rx="24" fill="url(#g)"/>
                    <circle cx="64" cy="44" r="24" fill="rgba(255,255,255,0.18)"/>
                    <path d="M30 108c5-20 21-30 34-30s29 10 34 30" fill="rgba(255,255,255,0.18)"/>
                    <text x="50%" y="55%" text-anchor="middle" dominant-baseline="middle" fill="#ffffff" font-family="Arial, sans-serif" font-size="40" font-weight="700">${initials}</text>
                </svg>
            `;

            return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`;
        }

        const user = JSON.parse(localStorage.getItem('user') || '{}');
        const name = user.name || 'John Doe';
        const email = user.email || 'admin@example.com';
        const role = user.user_role === 0 ? 'Super Admin' : user.user_role === 1 ? 'Admin' : 'Manager';
        const initials = name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
        const username = '@' + name.toLowerCase().replace(/\s+/g, '');

        // Avatar image generated locally
        document.getElementById('profile-avatar-img').src = createAvatarDataUri(name);

        // Populate all fields
        document.getElementById('profile-name').textContent = name;
        document.getElementById('profile-email').textContent = email;
        document.getElementById('overview-role').textContent = role;
        document.getElementById('contact-email').textContent = email;
        document.getElementById('contact-username').textContent = username;
        document.getElementById('form-name').value = name;
        document.getElementById('form-email').value = email;
        document.getElementById('avatar-initials').textContent = initials;
        document.getElementById('dropdown-avatar').textContent = initials;
        document.getElementById('dropdown-name').textContent = name;
        document.getElementById('dropdown-email').textContent = email;
        document.getElementById('dropdown-role').textContent = role;

        // ─── Save form ────────────────────────────────────────────────────────
        const saveBtn = document.getElementById('save-btn');

        document.getElementById('profile-form').addEventListener('submit', (e) => {
            e.preventDefault();
            const submitProfile = async () => {
                const newName = document.getElementById('form-name').value.trim();
                if (newName) {
                    user.name = newName;
                    localStorage.setItem('user', JSON.stringify(user));
                    document.getElementById('profile-avatar-img').src = createAvatarDataUri(newName);
                    const updatedInitials = newName.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
                    document.getElementById('avatar-initials').textContent = updatedInitials;
                    document.getElementById('dropdown-avatar').textContent = updatedInitials;
                    document.getElementById('dropdown-name').textContent = newName;
                }
                const success = document.getElementById('save-success');
                success.classList.remove('hidden');
                setTimeout(() => success.classList.add('hidden'), 3000);
            };

            if (window.AppButtonLoading?.withButtonLoading && saveBtn) {
                window.AppButtonLoading.withButtonLoading(saveBtn, submitProfile, { label: 'Saving Changes...' });
            } else {
                submitProfile();
            }
        });

        // ─── Logout ───────────────────────────────────────────────────────────
        document.getElementById('logout-btn')?.addEventListener('click', () => {
            localStorage.removeItem('user');
            window.location.href = 'login.php';
        });
    </script>
</body>
</html>
