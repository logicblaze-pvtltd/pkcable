 <header id="header" class="sticky top-0 z-30 backdrop-blur-md border-b transition-all duration-300 border-gray-200 dark:border-gray-800 bg-[#f3f4f4]/70 dark:bg-gray-900/70">
     <div class="flex justify-between items-center px-6 sm:px-10 py-3">
         <div class="flex items-center">
             <!-- Mobile Menu Toggle -->
             <button id="mobile-menu-toggle" class="mr-4 p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors lg:hidden">
                 <i data-lucide="menu" class="w-6 h-6 text-gray-600 dark:text-gray-300"></i>
             </button>
         </div>

         <div class="flex gap-4 items-center">

             <!-- Notification Bell -->
             <div class="relative">
                 <button id="notification-btn" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors relative">
                     <i data-lucide="bell" class="w-6 h-6 text-gray-600 dark:text-gray-300"></i>
                     <span class="absolute right-0 top-0 rounded-full bg-red-500 text-white text-[10px] w-4 h-4 flex items-center justify-center">3</span>
                 </button>

                 <!-- Notification Dropdown -->
                 <div id="notification-dropdown" class="hidden absolute right-0 mt-2 w-72 bg-white dark:bg-gray-800 shadow-xl rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden z-50 transform opacity-0 transition-all duration-200 scale-95 origin-top-right">
                     <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                         <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Notifications</h3>
                         <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100 rounded-full">3 new</span>
                     </div>
                     <div class="max-h-96 overflow-y-auto custom-scrollbar">
                         <!-- Item -->
                         <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors bg-blue-50 dark:bg-blue-900/20">
                             <div class="flex gap-3">
                                 <div class="mt-0.5 flex-shrink-0 text-blue-500"><i data-lucide="mail" class="w-4 h-4"></i></div>
                                 <div class="flex-1 min-w-0">
                                     <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">New Message</p>
                                     <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">You have a new message from support.</p>
                                     <p class="text-xs text-gray-500 mt-2">Just now</p>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>
             </div>

             <!-- User Profile -->
             <div class="relative">
                 <div class="relative inline-block w-10 h-10">
                     <button id="profile-btn" class="w-10 h-10 rounded-full flex items-center font-semibold text-base select-none shadow-sm hover:shadow-md transition-shadow overflow-hidden bg-gray-200 justify-center text-gray-500 mx-auto border-2 dark:bg-gray-700 dark:text-gray-400 border-white dark:shadow-gray-700/50 dark:border-gray-800">
                         <span class="leading-none"><?= getInitials($_SESSION['user']['name']) ?></span>
                     </button>
                     <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-green-400 border-2 border-white dark:border-gray-800"></span>
                 </div>

                 <!-- Profile Dropdown -->
                 <div id="profile-dropdown" class="hidden absolute right-0 mt-2 w-[300px] bg-white dark:bg-gray-800 shadow-xl rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden z-50 transform opacity-0 transition-all duration-200 scale-95 origin-top-right">
                     <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                         <div class="flex items-center gap-3">
                             <div class="relative">
                                 <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 mx-auto border-2 dark:bg-gray-700 dark:text-gray-400 border-white shadow-sm dark:shadow-gray-700/50 dark:border-gray-800 font-bold text-lg"><?= getInitials($_SESSION['user']['name']) ?></div>
                                 <span class="absolute bottom-0 right-0 w-3 h-3 rounded-full bg-green-400 border-2 border-white dark:border-gray-800"></span>
                             </div>
                             <div>
                                 <p class="text-sm font-semibold text-gray-800 dark:text-gray-200"><?= $_SESSION['user']['name'] ?></p>
                                 <p class="text-xs text-gray-500 dark:text-gray-400"><?= $_SESSION['user']['email'] ?></p>
                                 <p class="text-xs text-gray-300 dark:text-gray-500"><?= ucfirst($_SESSION['user']['role']) ?></p>
                             </div>
                         </div>
                     </div>
                     <div class="py-2">
                         <a href="profile.php" class="w-full flex items-center justify-between px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors rounded-md mx-2">
                             <div class="flex items-center"><i data-lucide="user" class="w-[18px] h-[18px] mr-3 text-gray-500"></i> Profile</div>
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
                         <a href="./controller/auth/logout.php"
                             onclick="
                                    const withButtonLoading = window.AppButtonLoading?.withButtonLoading;
                                    if (typeof withButtonLoading === 'function') {
                                        event.preventDefault();
                                        const url = this.href;
                                        withButtonLoading(this, () => new Promise(res => setTimeout(() => { window.location.href = url; res(); }, 600)), { label: 'Logging out...' });
                                    }
                                "
                             class="flex justify-center items-center gap-2 w-full px-4 py-2 text-sm text-white rounded-md transition-opacity font-medium bg-red-500 hover:opacity-90">
                             <i data-lucide="log-out" class="w-4 h-4 mr-2"></i> Logout
                         </a>
                     </div>
                 </div>
             </div>

         </div>
     </div>
 </header>