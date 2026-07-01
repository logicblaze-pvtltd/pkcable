<!-- Create/Edit Customer Modal -->
<div id="user-modal" class="modal-shell hidden" aria-hidden="true">
    <div class="modal-backdrop"></div>
    <div class="modal-card modal-card-lg">
        <div class="modal-header">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-500 dark:text-blue-400">Customer Form</p>
                <h2 id="modal-title" class="text-xl font-bold text-gray-900 dark:text-gray-100 mt-1">Add New Customer</h2>
            </div>
            <button id="close-modal-btn" class="modal-icon-btn" type="button" aria-label="Close modal">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="modal-body space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full Name</label>
                <input type="text" id="user-name" placeholder="Enter full name" class="modal-input" autocomplete="name">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Address</label>
                <input type="email" id="user-email" placeholder="customer@example.com" class="modal-input" autocomplete="email">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mobile Number (Optional)</label>
                <div class="relative">
                    <input
                        type="text"
                        id="user-mobile"
                        placeholder="+92 300 000 0000"
                        class="modal-input"
                        autocomplete="tel"
                        maxlength="16">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                <input type="password" id="user-password" placeholder="Enter password" class="modal-input" autocomplete="new-password">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Note. Leave this field blank if you want the system to generate a password for you.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Package</label>
                <select id="user-package" class="modal-input">
                    <option value="">No Package</option>
                    <?php if (!empty($packages)): ?>
                        <?php foreach ($packages as $package): ?>
                            <option value="<?= customerEsc($package['id'] ?? '') ?>">
                                <?= customerEsc(customerFormatPackageId($package['id'] ?? 0) . ' - ' . ($package['name'] ?? '')) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>No packages available</option>
                    <?php endif; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                <select id="user-status" class="modal-input">
                    <option value="active" selected>Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Address</label>
                <textarea id="user-address" placeholder="Enter customer address" rows="3" class="modal-input resize-none"></textarea>
            </div>
        </div>

        <div class="modal-footer">
            <button id="cancel-modal-btn" class="modal-secondary-btn" type="button">Cancel</button>
            <button id="save-user-btn" class="modal-primary-btn" type="button">Save Customer</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="modal-shell hidden" aria-hidden="true">
    <div class="modal-backdrop"></div>
    <div class="modal-card modal-card-sm">
        <div class="modal-body text-center space-y-4">
            <div class="w-14 h-14 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-full flex items-center justify-center mx-auto">
                <i data-lucide="alert-triangle" class="w-7 h-7"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Delete Customer?</h3>
                <p id="delete-message" class="text-gray-600 dark:text-gray-400 mt-2">This action cannot be undone. The customer record will be permanently deleted.</p>
            </div>
        </div>
        <div class="modal-footer">
            <button id="cancel-delete-btn" class="modal-secondary-btn" type="button">Cancel</button>
            <button id="confirm-delete-btn" class="modal-danger-btn" type="button">Delete</button>
        </div>
    </div>
</div>

<!-- View Customer Modal -->
<div id="view-modal" class="modal-shell hidden" aria-hidden="true">
    <div class="modal-backdrop"></div>
    <div class="modal-card modal-card-lg">
        <div class="modal-header">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-500 dark:text-blue-400">Customer Preview</p>
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mt-1">Customer Details</h2>
            </div>
            <button id="close-view-modal-btn" class="modal-icon-btn" type="button" aria-label="Close modal">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="modal-body space-y-5">
            <div class="flex justify-center">
                <div id="view-avatar" class="w-16 h-16 rounded-2xl bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-2xl shadow-sm">
                    AS
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="modal-detail-card">
                    <p class="modal-label">Full Name</p>
                    <p id="view-name" class="modal-value">Ahmed Sultan</p>
                </div>
                <div class="modal-detail-card">
                    <p class="modal-label">Customer ID</p>
                    <p id="view-id" class="modal-value">#C001</p>
                </div>
                <div class="modal-detail-card">
                    <p class="modal-label">Email Address</p>
                    <p id="view-email" class="modal-value break-all">ahmed.sultan@email.com</p>
                </div>
                <div class="modal-detail-card">
                    <p class="modal-label">Role</p>
                    <span id="view-role" class="inline-flex px-3 py-1 bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded-full text-xs font-medium">Customer</span>
                </div>
                <div class="modal-detail-card">
                    <p class="modal-label">Package</p>
                    <p id="view-package" class="modal-value">No Package</p>
                    <p id="view-package-id" class="text-sm text-gray-500 dark:text-gray-400 mt-1">No package assigned</p>
                </div>
                <div class="modal-detail-card">
                    <p class="modal-label">Status</p>
                    <span id="view-status" class="inline-flex px-3 py-1 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-full text-xs font-medium">Active</span>
                </div>
                <div class="modal-detail-card sm:col-span-2">
                    <p class="modal-label">Address</p>
                    <p id="view-address" class="modal-value break-all">&mdash;</p>
                </div>
                <div class="modal-detail-card sm:col-span-2">
                    <p class="modal-label">Created Date</p>
                    <p id="view-date" class="modal-value">Jan 15, 2026</p>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button id="close-view-btn" class="modal-secondary-btn" type="button">Close</button>
            <button id="view-edit-btn" class="modal-primary-btn" type="button">Edit</button>
        </div>
    </div>
</div>