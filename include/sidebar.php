<aside id="sidebar" class="flex flex-col h-screen fixed z-50 bg-[#f3f4f4]/70 dark:bg-gray-900/70 backdrop-blur-md border-gray-300 dark:border-gray-800 transition-all duration-300 ease-in-out border-r w-0 -translate-x-full overflow-visible">

    <!-- Sidebar Header -->
    <div class="flex items-center justify-between p-4 shadow-lg shadow-[#EEF0F3] sticky top-0 z-50 bg-[#f3f4f4]/70 dark:bg-gray-900/70 backdrop-blur-md dark:shadow-gray-900">
        <div class="flex items-center gap-3">
            <span class="flex items-center justify-center text-black dark:text-gray-100 font-bold text-3xl">PK</span>
            <a href="index.php" id="brand-text" class="text-2xl font-bold text-black dark:text-gray-100 truncate block">Cable</a>
        </div>

        <!-- Pin Button (Desktop) -->
        <button id="pin-sidebar-btn" class="hidden relative w-5 h-5 items-center justify-center rounded-full border-2 border-blue-500 hover:border-blue-500 transition-colors duration-200">
            <span id="pin-dot" class="w-2 h-2 rounded-full border-2 border-blue-500"></span>
        </button>

        <!-- Mobile Close Button -->
        <button id="close-sidebar" class="lg:hidden p-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
            <i data-lucide="x" class="w-[18px] h-[18px] text-gray-600 dark:text-gray-300"></i>
        </button>
    </div>

    <!-- Sidebar Navigation -->
    <nav id="sidebar-nav" class="flex-1 px-2 py-4 space-y-1 relative overflow-x-hidden ps-container">
        <p id="main-menu-label" class="text-xs text-gray-500 dark:text-gray-400 uppercase font-medium mb-3 px-3 tracking-wider transition-opacity duration-200">Main Menu</p>

        <!-- Animated Link -->
        <a href="index.php" class="animated-link-group flex items-center justify-between w-full px-3 py-2.5 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition-colors duration-200 group <?php echo basename($_SERVER['PHP_SELF'], '.php') === 'index' ? 'bg-gray-200 dark:bg-gray-800' : ''; ?>">
            <div class="flex items-center">
                <span class="flex items-center justify-center w-8"><i data-lucide="layout-dashboard" class="w-[18px] h-[18px]"></i></span>
                <div class="wave-label-container ml-2 overflow-hidden h-5" data-wave-label="Dashboard"></div>
            </div>
        </a>
        <?php
        if ($_SESSION['user']['role'] == 'manager' || $_SESSION['user']['role'] === 'admin') {
        ?>
            <a href="revenue_reports.php" class="animated-link-group flex items-center justify-between w-full px-3 py-2.5 rounded-lg  hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition-colors duration-200 group <?php echo basename($_SERVER['PHP_SELF'], '.php') === 'revenue_reports' ? 'bg-gray-200 dark:bg-gray-800' : ''; ?>">
                <div class="flex items-center">
                    <span class="flex items-center justify-center w-8"><i data-lucide="bar-chart-2" class="w-[18px] h-[18px]"></i></span>
                    <div class="wave-label-container ml-2 overflow-hidden h-5" data-wave-label="Revenue Reports"></div>
                </div>
            </a>
        <?php
        }
        if ($_SESSION['user']['role'] === 'admin') {
        ?>
            <a href="managers.php" class="animated-link-group flex items-center justify-between w-full px-3 py-2.5 rounded-lg  hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition-colors duration-200 group <?php echo basename($_SERVER['PHP_SELF'], '.php') === 'managers' ? 'bg-gray-200 dark:bg-gray-800' : ''; ?>">
                <div class="flex items-center">
                    <span class="flex items-center justify-center w-8"><i data-lucide="user-cog" class="w-[18px] h-[18px]"></i></span>
                    <div class="wave-label-container ml-2 overflow-hidden h-5" data-wave-label="Managers"></div>
                </div>
            </a>
        <?php
        }
        ?>
        <!-- Submenu Item -->
        <!-- <div class="relative group submenu-wrapper">
            <button class="submenu-toggle flex items-center justify-between w-full px-3 py-2.5 rounded-lg dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition-colors duration-200 group animated-link-group">
                <div class="flex items-center">
                    <span class="flex items-center justify-center w-8"><i data-lucide="users" class="w-[18px] h-[18px]"></i></span>
                    <div class="wave-label-container ml-2 overflow-hidden h-5" data-wave-label="Users"></div>
                </div>
                <span class="submenu-icon-wrapper ml-auto text-gray-400 transition-transform duration-300 rotate-0"><i data-lucide="chevron-right" class="w-4 h-4"></i></span>
            </button>
            <div class="submenu-content max-h-0 opacity-0 overflow-hidden transition-all duration-300 ease-in-out bg-blue-50/50 dark:bg-gray-800/50 rounded-lg">
                <a href="#" class="block w-full text-left px-6 py-2.5 hover:bg-blue-100 dark:hover:bg-gray-700 text-sm text-gray-600 dark:text-gray-400 font-medium transition-colors duration-200" style="transform: translateY(-10px);">Admins</a>
                <a href="#" class="block w-full text-left px-6 py-2.5 hover:bg-blue-100 dark:hover:bg-gray-700 text-sm text-gray-600 dark:text-gray-400 font-medium transition-colors duration-200" style="transform: translateY(-10px);">Interns</a>
            </div>
        </div> -->
        <?php
        if ($_SESSION['user']['role'] == 'manager' || $_SESSION['user']['role'] === 'admin') {
        ?>
            <a href="packages.php" class="animated-link-group flex items-center justify-between w-full px-3 py-2.5 rounded-lg  hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition-colors duration-200 group <?php echo basename($_SERVER['PHP_SELF'], '.php') === 'packages' ? 'bg-gray-200 dark:bg-gray-800' : ''; ?>">
                <div class="flex items-center">
                    <span class="flex items-center justify-center w-8"><i data-lucide="package" class="w-[18px] h-[18px]"></i></span>
                    <div class="wave-label-container ml-2 overflow-hidden h-5" data-wave-label="Packages"></div>
                </div>
            </a>

            <a href="customers.php" class="animated-link-group flex items-center justify-between w-full px-3 py-2.5 rounded-lg  hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition-colors duration-200 group <?php echo basename($_SERVER['PHP_SELF'], '.php') === 'customers' ? 'bg-gray-200 dark:bg-gray-800' : ''; ?>">
                <div class="flex items-center">
                    <span class="flex items-center justify-center w-8"><i data-lucide="users" class="w-[18px] h-[18px]"></i></span>
                    <div class="wave-label-container ml-2 overflow-hidden h-5" data-wave-label="Customers"></div>
                </div>
            </a>
        <?php
        }
        ?>

        <?php
        if ($_SESSION['user']['role'] === 'super admin') {
        ?>
            <a href="tenants.php" class="animated-link-group flex items-center justify-between w-full px-3 py-2.5 rounded-lg  hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition-colors duration-200 group <?php echo basename($_SERVER['PHP_SELF'], '.php') === 'tenants' ? 'bg-gray-200 dark:bg-gray-800' : ''; ?>">
                <div class="flex items-center">
                    <span class="flex items-center justify-center w-8"><i data-lucide="building-2" class="w-[18px] h-[18px]"></i></span>
                    <div class="wave-label-container ml-2 overflow-hidden h-5" data-wave-label="Tenants"></div>
                </div>
            </a>
            <a href="admins.php" class="animated-link-group flex items-center justify-between w-full px-3 py-2.5 rounded-lg  hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition-colors duration-200 group <?php echo basename($_SERVER['PHP_SELF'], '.php') === 'admins' ? 'bg-gray-200 dark:bg-gray-800' : ''; ?>">
                <div class="flex items-center">
                    <span class="flex items-center justify-center w-8"><i data-lucide="shield" class="w-[18px] h-[18px]"></i></span>
                    <div class="wave-label-container ml-2 overflow-hidden h-5" data-wave-label="Admins"></div>
                </div>
            </a>
        <?php
        }
        if ($_SESSION['user']['role'] === 'manager' || $_SESSION['user']['role'] === 'admin') {
        ?>
            <a href="subscriptions.php" class="animated-link-group flex items-center justify-between w-full px-3 py-2.5 rounded-lg  hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition-colors duration-200 group <?php echo basename($_SERVER['PHP_SELF'], '.php') === 'subscriptions' ? 'bg-gray-200 dark:bg-gray-800' : ''; ?>">
                <div class="flex items-center">
                    <span class="flex items-center justify-center w-8"><i data-lucide="shopping-bag" class="w-[18px] h-[18px]"></i></span>
                    <div class="wave-label-container ml-2 overflow-hidden h-5" data-wave-label="Subscriptions"></div>
                </div>
            </a>
        <?php
        }
        ?>
    </nav>
</aside>