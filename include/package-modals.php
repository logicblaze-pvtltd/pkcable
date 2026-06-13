<!-- Create/Edit Package Modal -->
<div id="package-modal" class="modal-shell hidden" aria-hidden="true">
    <div class="modal-backdrop"></div>
    <div class="modal-card modal-card-lg">
        <div class="modal-header">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-500 dark:text-blue-400">Package Form</p>
                <h2 id="modal-title" class="text-xl font-bold text-gray-900 dark:text-gray-100 mt-1">Add New Package</h2>
            </div>
            <button id="close-modal-btn" class="modal-icon-btn" type="button" aria-label="Close modal">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="modal-body space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Package Name</label>
                <input type="text" id="package-name" placeholder="e.g., 4 Mb, 8 Mb, 10 Mb" class="modal-input">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Price</label>
                <input type="text" id="package-price" placeholder="e.g., Rs.800, Rs.1200" class="modal-input">
            </div>
        </div>

        <div class="modal-footer">
            <button id="cancel-modal-btn" class="modal-secondary-btn" type="button">Cancel</button>
            <button id="save-package-btn" class="modal-primary-btn" type="button">Save Package</button>
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
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Delete Package?</h3>
                <p id="delete-message" class="text-gray-600 dark:text-gray-400 mt-2">This action cannot be undone. The package will be permanently deleted.</p>
            </div>
        </div>
        <div class="modal-footer">
            <button id="cancel-delete-btn" class="modal-secondary-btn" type="button">Cancel</button>
            <button id="confirm-delete-btn" class="modal-danger-btn" type="button">Delete</button>
        </div>
    </div>
</div>

<!-- View Package Modal -->
<div id="view-modal" class="modal-shell hidden" aria-hidden="true">
    <div class="modal-backdrop"></div>
    <div class="modal-card modal-card-lg">
        <div class="modal-header">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-500 dark:text-blue-400">Package Preview</p>
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mt-1">Package Details</h2>
            </div>
            <button id="close-view-modal-btn" class="modal-icon-btn" type="button" aria-label="Close modal">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="modal-body space-y-5">
            <div class="flex justify-center">
                <div id="view-icon" class="w-16 h-16 rounded-2xl bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-2xl shadow-sm">
                    <i data-lucide="package" class="w-8 h-8"></i>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="modal-detail-card">
                    <p class="modal-label">Package ID</p>
                    <p id="view-id" class="modal-value"></p>
                </div>
                <div class="modal-detail-card">
                    <p class="modal-label">Package Name</p>
                    <p id="view-name" class="modal-value"></p>
                </div>
                <div class="modal-detail-card sm:col-span-2">
                    <p class="modal-label">Price</p>
                    <p id="view-price" class="modal-value font-semibold text-green-600 dark:text-green-400"></p>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button id="close-view-btn" class="modal-secondary-btn" type="button">Close</button>
            <button id="view-edit-btn" class="modal-primary-btn" type="button">Edit</button>
        </div>
    </div>
</div>
