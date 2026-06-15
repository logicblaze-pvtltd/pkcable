<?php
// Fetch all users (customers) for the dropdown
// SELECT u.id, u.name
// FROM users u
// LEFT JOIN subscriptions s
//     ON u.id = s.user_id
//     AND MONTH(s.start_date) = MONTH(CURDATE())
//     AND YEAR(s.start_date) = YEAR(CURDATE())
// WHERE (u.user_role = 'customer' OR u.user_role IS NULL)
// AND s.user_id IS NULL
// ORDER BY u.name ASC
$allUsers = $db->select("SELECT id, name FROM users WHERE user_role = 'customer' AND status ='active' ORDER BY name ASC");
if (isset($allUsers['error'])) {
    $allUsers = [];
}

// Fetch all packages for the dropdown
$allPackages = $db->select("SELECT id, name, price FROM packages ORDER BY name ASC");
if (isset($allPackages['error'])) {
    $allPackages = [];
}
?>

<!-- Create/Edit Subscription Modal -->
<div id="subscription-modal" class="modal-shell hidden" aria-hidden="true">
    <div class="modal-backdrop"></div>
    <div class="modal-card modal-card-lg">
        <div class="modal-header">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-500 dark:text-blue-400">Subscription Form</p>
                <h2 id="sub-modal-title" class="text-xl font-bold text-gray-900 dark:text-gray-100 mt-1">Add New Subscription</h2>
            </div>
            <button id="sub-close-modal-btn" class="modal-icon-btn" type="button" aria-label="Close modal">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="modal-body space-y-4">
            <!-- Customer -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Customer</label>
                <select id="sub-user-id" class="modal-input">
                    <option value="">-- Select Customer --</option>
                    <?php if (!empty($allUsers)): ?>
                        <?php foreach ($allUsers as $u): ?>
                            <option value="<?= htmlspecialchars($u['id']) ?>">
                                <?= htmlspecialchars(strtoupper($u['name'])) ?>
                                (#U<?= str_pad($u['id'], 3, '0', STR_PAD_LEFT) ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled selected>No active users found</option>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Package -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Package</label>
                <select id="sub-package-id" class="modal-input">
                    <option value="">-- Select Package --</option>
                    <?php foreach ($allPackages as $p): ?>
                        <option value="<?= htmlspecialchars($p['id']) ?>" data-price="<?= htmlspecialchars($p['price']) ?>">
                            <?= htmlspecialchars($p['name']) ?> — Rs.<?= htmlspecialchars($p['price']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Discount & Paid Amount -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                 <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Package Price (Rs.)</label>
                    <input type="text" id="package-price" placeholder="e.g. 0" min="0" class="modal-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Discount (Rs.)</label>
                    <input type="number" id="sub-discount" placeholder="e.g. 0" min="0" class="modal-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Paid Amount (Rs.)</label>
                    <input type="text" id="sub-paid-amount" class="modal-input bg-gray-50 dark:bg-gray-700 cursor-not-allowed" readonly placeholder="Auto-calculated">
                </div>
            </div>

            <!-- Start & End Date — custom date pickers -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <!-- ── Start Date ── -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition-colors">
                            <i class="far fa-calendar text-base"></i>
                        </div>
                        <input type="text" id="sub-start-date-display"
                            class="modal-input pl-9 pr-9 cursor-pointer"
                            placeholder="Choose start date" readonly>
                        <button id="sub-start-date-clear"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors focus:outline-none invisible"
                            type="button" aria-label="Clear start date">
                            <i class="fas fa-times-circle text-sm"></i>
                        </button>
                    </div>
                    <input type="hidden" id="sub-start-date" name="start_date">

                    <!-- Start Date Calendar Modal -->
                    <div id="sub-start-cal-modal"
                        class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/30 backdrop-blur-sm transition-all duration-300 opacity-0 invisible pointer-events-none">
                        <div id="sub-start-cal-panel"
                            class="bg-white dark:bg-gray-800 w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-300 scale-95 opacity-0">
                            <!-- Header -->
                            <div class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 px-4 py-3 flex items-center justify-between">
                                <button id="sub-start-cal-prev" type="button"
                                    class="w-9 h-9 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition flex items-center justify-center text-gray-600 dark:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                    <i class="fas fa-chevron-left text-sm"></i>
                                </button>
                                <span id="sub-start-cal-month" class="text-gray-800 dark:text-gray-200 font-semibold text-base"></span>
                                <button id="sub-start-cal-next" type="button"
                                    class="w-9 h-9 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition flex items-center justify-center text-gray-600 dark:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                    <i class="fas fa-chevron-right text-sm"></i>
                                </button>
                            </div>
                            <!-- Weekday labels -->
                            <div class="grid grid-cols-7 gap-1 px-4 pt-3 pb-1 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                            </div>
                            <!-- Days grid -->
                            <div id="sub-start-cal-grid" class="grid grid-cols-7 gap-1 px-4 pb-3 pt-1"></div>
                            <!-- Footer -->
                            <div class="border-t border-gray-100 dark:border-gray-700 p-3 flex justify-between items-center bg-gray-50/60 dark:bg-gray-800/80">
                                <button id="sub-start-cal-cancel" type="button"
                                    class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 rounded-xl transition">
                                    Cancel
                                </button>
                                <button id="sub-start-cal-today" type="button"
                                    class="px-4 py-2 text-sm font-medium bg-blue-50 dark:bg-blue-700/80 text-blue-700 dark:text-blue-300 hover:bg-blue-100 rounded-xl transition">
                                    <i class="far fa-calendar-check mr-1"></i> Today
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── End Date ── -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition-colors">
                            <i class="far fa-calendar text-base"></i>
                        </div>
                        <input type="text" id="sub-end-date-display"
                            class="modal-input pl-9 pr-9 cursor-pointer"
                            placeholder="Choose end date" readonly>
                        <button id="sub-end-date-clear"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors focus:outline-none invisible"
                            type="button" aria-label="Clear end date">
                            <i class="fas fa-times-circle text-sm"></i>
                        </button>
                    </div>
                    <input type="hidden" id="sub-end-date" name="end_date">

                    <!-- End Date Calendar Modal -->
                    <div id="sub-end-cal-modal"
                        class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/30 backdrop-blur-sm transition-all duration-300 opacity-0 invisible pointer-events-none">
                        <div id="sub-end-cal-panel"
                            class="bg-white dark:bg-gray-800 w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-300 scale-95 opacity-0">
                            <!-- Header -->
                            <div class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 px-4 py-3 flex items-center justify-between">
                                <button id="sub-end-cal-prev" type="button"
                                    class="w-9 h-9 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition flex items-center justify-center text-gray-600 dark:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                    <i class="fas fa-chevron-left text-sm"></i>
                                </button>
                                <span id="sub-end-cal-month" class="text-gray-800 dark:text-gray-200 font-semibold text-base"></span>
                                <button id="sub-end-cal-next" type="button"
                                    class="w-9 h-9 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition flex items-center justify-center text-gray-600 dark:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                    <i class="fas fa-chevron-right text-sm"></i>
                                </button>
                            </div>
                            <!-- Weekday labels -->
                            <div class="grid grid-cols-7 gap-1 px-4 pt-3 pb-1 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                            </div>
                            <!-- Days grid -->
                            <div id="sub-end-cal-grid" class="grid grid-cols-7 gap-1 px-4 pb-3 pt-1"></div>
                            <!-- Footer -->
                            <div class="border-t border-gray-100 dark:border-gray-700 p-3 flex justify-between items-center bg-gray-50/60 dark:bg-gray-800/80">
                                <button id="sub-end-cal-cancel" type="button"
                                    class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 rounded-xl transition">
                                    Cancel
                                </button>
                                <button id="sub-end-cal-today" type="button"
                                    class="px-4 py-2 text-sm font-medium bg-blue-50 dark:bg-blue-700/80 text-blue-700 dark:text-blue-300 hover:bg-blue-100 rounded-xl transition">
                                    <i class="far fa-calendar-check mr-1"></i> Today
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                <select id="sub-status" class="modal-input">
                    <option value="active">Active</option>
                    <option value="expired">Expired</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>

        <div class="modal-footer">
            <button id="sub-cancel-modal-btn" class="modal-secondary-btn" type="button">Cancel</button>
            <button id="sub-save-btn" class="modal-primary-btn" type="button">Save Subscription</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="sub-delete-modal" class="modal-shell hidden" aria-hidden="true">
    <div class="modal-backdrop"></div>
    <div class="modal-card modal-card-sm">
        <div class="modal-body text-center space-y-4">
            <div class="w-14 h-14 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-full flex items-center justify-center mx-auto">
                <i data-lucide="alert-triangle" class="w-7 h-7"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Delete Subscription?</h3>
                <p id="sub-delete-message" class="text-gray-600 dark:text-gray-400 mt-2">This action cannot be undone. The subscription will be permanently deleted.</p>
            </div>
        </div>
        <div class="modal-footer">
            <button id="sub-cancel-delete-btn" class="modal-secondary-btn" type="button">Cancel</button>
            <button id="sub-confirm-delete-btn" class="modal-danger-btn" type="button">Delete</button>
        </div>
    </div>
</div>

<!-- View Subscription Modal -->
<div id="sub-view-modal" class="modal-shell hidden" aria-hidden="true">
    <div class="modal-backdrop"></div>
    <div class="modal-card modal-card-lg">
        <div class="modal-header">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-500 dark:text-blue-400">Subscription Preview</p>
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mt-1">Subscription Details</h2>
            </div>
            <button id="sub-close-view-modal-btn" class="modal-icon-btn" type="button" aria-label="Close modal">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="modal-body space-y-5">
            <div class="flex justify-center">
                <div class="w-16 h-16 rounded-2xl bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center shadow-sm">
                    <i data-lucide="receipt" class="w-8 h-8"></i>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="modal-detail-card">
                    <p class="modal-label">Subscription ID</p>
                    <p id="sub-view-id" class="modal-value"></p>
                </div>
                <div class="modal-detail-card">
                    <p class="modal-label">Customer</p>
                    <p id="sub-view-customer" class="modal-value"></p>
                </div>
                <div class="modal-detail-card">
                    <p class="modal-label">Package</p>
                    <p id="sub-view-package" class="modal-value"></p>
                </div>
                <div class="modal-detail-card">
                    <p class="modal-label">Package Price</p>
                    <p id="sub-view-price" class="modal-value font-semibold text-green-600 dark:text-green-400"></p>
                </div>
                <div class="modal-detail-card">
                    <p class="modal-label">Discount</p>
                    <p id="sub-view-discount" class="modal-value text-amber-600 dark:text-amber-400"></p>
                </div>
                <div class="modal-detail-card">
                    <p class="modal-label">Paid Amount</p>
                    <p id="sub-view-paid" class="modal-value font-bold text-blue-600 dark:text-blue-400"></p>
                </div>
                <div class="modal-detail-card">
                    <p class="modal-label">Start Date</p>
                    <p id="sub-view-start" class="modal-value"></p>
                </div>
                <div class="modal-detail-card">
                    <p class="modal-label">End Date</p>
                    <p id="sub-view-end" class="modal-value"></p>
                </div>
                <div class="modal-detail-card">
                    <p class="modal-label">Month</p>
                    <p id="sub-view-month" class="modal-value"></p>
                </div>
                <div class="modal-detail-card">
                    <p class="modal-label">Status</p>
                    <span id="sub-view-status" class="inline-flex px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">Active</span>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button id="sub-close-view-btn" class="modal-secondary-btn" type="button">Close</button>
            <button id="sub-view-edit-btn" class="modal-primary-btn" type="button">Edit</button>
        </div>
    </div>
</div>