<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once './include/connection.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $appName = get_env_value('APP_NAME') ?: ''; ?>
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
        <!-- main-content-wrapper: JS sets margin/width immediately on load without transition -->
        <div id="main-content-wrapper" class="flex-1 flex flex-col w-full">

            <!-- Header -->
            <?php include "./include/header.php" ?>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto py-3 px-8 w-full min-h-screen">
                <!-- Breadcrumbs -->
                <?php include "./include/breadcrumbs.php" ?>

                <div class="w-full max-w-md mx-auto md:max-w-lg lg:max-w-md">
                    <!-- UI Card Container -->

                    <!-- Date Input Field (readonly for custom picker but we also accept manual?) we'll make it interactive to open calendar -->
                    <div class="relative mb-1">
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="far fa-calendar text-gray-400 group-focus-within:text-blue-500 transition-colors text-lg sm:text-base"></i>
                            </div>
                            <input type="text" id="dateInputDisplay"
                                class="block w-full pl-10 pr-12 py-3 sm:py-3.5 text-gray-800 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-400 focus:border-blue-500 transition-all duration-200 cursor-pointer text-base sm:text-sm"
                                placeholder="Choose a date" readonly
                                value="">
                            <button id="clearDateBtn" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors focus:outline-none invisible" type="button" aria-label="Clear date">
                                <i class="fas fa-times-circle text-sm"></i>
                            </button>
                        </div>
                        <!-- <p class="text-xs text-gray-500 mt-1.5 ml-1 flex items-center gap-1">
                                <i class="fas fa-mobile-alt text-gray-400 text-xs"></i>
                                <span>Tap to open calendar — responsive for any screen</span>
                            </p> -->
                    </div>

                    <!-- Hidden native input to maintain form compatibility -->
                    <input type="hidden" id="hiddenDateValue" name="selectedDate" value="">

                    <!-- Display selected date preview chip (optional but nice feedback)
                        <div id="selectedPreview" class="mt-3 text-sm text-gray-600 bg-gray-50 rounded-lg px-3 py-2 border border-gray-200 flex items-center justify-between">
                            <span class="truncate"><i class="far fa-check-circle text-green-500 mr-1.5"></i> <span id="selectedDateText">No date selected</span></span>
                            <span class="text-[11px] font-mono text-gray-400">📅</span>
                        </div> -->
                </div>

                <!-- Custom Date Picker Modal (responsive slide/fade) -->
                <div id="calendarModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/30 backdrop-blur-sm transition-all duration-300 opacity-0 invisible pointer-events-none">
                    <div id="calendarPanel" class="bg-white dark:bg-gray-800 w-full max-w-sm md:max-w-md rounded-2xl shadow-popover overflow-hidden transform transition-all duration-300 scale-95 opacity-0">
                        <!-- Calendar Header: month navigation -->
                        <div class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-600 px-4 py-3 flex items-center justify-between">
                            <button id="prevMonthBtn" class="w-9 h-9 rounded-full hover:bg-gray-100 dark:hover:bg-gray-600 transition flex items-center justify-center text-gray-600 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                <i class="fas fa-chevron-left text-sm dark:text-gray-500"></i>
                            </button>
                            <div class="text-gray-800 dark:text-gray-300 font-semibold text-base md:text-lg" id="monthYearDisplay"></div>
                            <button id="nextMonthBtn" class="w-9 h-9 rounded-full hover:bg-gray-100 dark:hover:bg-gray-600 transition flex items-center justify-center text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                <i class="fas fa-chevron-right text-sm dark:text-gray-500"></i>
                            </button>
                        </div>

                        <!-- Week days (responsive grid) -->
                        <div class="grid grid-cols-7 gap-1 px-4 pt-4 pb-2 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                        </div>

                        <!-- Calendar days grid -->
                        <div id="calendarDaysGrid" class="grid grid-cols-7 gap-1 px-4 pb-4 pt-1">
                            <!-- JS will fill days here -->
                        </div>

                        <!-- Action footer buttons -->
                        <div class="border-t border-gray-100 dark:border-gray-600 p-3 flex justify-between items-center bg-gray-50/60 dark:bg-gray-800">
                            <button id="cancelCalendarBtn" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-600 rounded-xl transition">Cancel</button>
                            <button id="todayCalendarBtn" class="px-4 py-2 text-sm font-medium bg-blue-50 dark:bg-blue-700/80 dark:text-blue-300 text-blue-700 hover:bg-blue-100 rounded-xl transition"><i class="far fa-calendar-check mr-1"></i> Today</button>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <?php include "./include/footer.php" ?>
        </div>
    </div>
    <!-- footer links  -->
    <?php include "./include/footerLinks.php" ?>
    <script type="module">
        import { DatePicker } from './assets/js/datePicker.js';
        new DatePicker({
            inputDisplayId : 'dateInputDisplay',
            hiddenInputId  : 'hiddenDateValue',
            clearBtnId     : 'clearDateBtn',
            modalId        : 'calendarModal',
            panelId        : 'calendarPanel',
            monthYearId    : 'monthYearDisplay',
            daysGridId     : 'calendarDaysGrid',
            prevBtnId      : 'prevMonthBtn',
            nextBtnId      : 'nextMonthBtn',
            cancelBtnId    : 'cancelCalendarBtn',
            todayBtnId     : 'todayCalendarBtn',
        });
    </script>
</body>

</html>