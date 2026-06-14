import Swal from '../../node_modules/sweetalert2/dist/sweetalert2.esm.all.js';
document.addEventListener('DOMContentLoaded', function () {
    const packageModal = document.getElementById('package-modal');
    if (!packageModal) return;

    const deleteModal = document.getElementById('delete-modal');
    const viewModal = document.getElementById('view-modal');
    const packagesTable = document.getElementById('packages-table');

    const addPackageBtn = document.getElementById('add-user-btn');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const cancelModalBtn = document.getElementById('cancel-modal-btn');
    const savePackageBtn = document.getElementById('save-package-btn');

    const closeViewModalBtn = document.getElementById('close-view-modal-btn');
    const closeViewBtn = document.getElementById('close-view-btn');
    const viewEditBtn = document.getElementById('view-edit-btn');

const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
const deleteMessage = document.getElementById('delete-message');
const runWithButtonLoading = (button, label, action) => {
    if (window.AppButtonLoading?.withButtonLoading) {
        return window.AppButtonLoading.withButtonLoading(button, action, { label });
    }

    return action();
};

    const modalTitle = document.getElementById('modal-title');
    const packageNameInput = document.getElementById('package-name');
    const packagePriceInput = document.getElementById('package-price');

    let modalMode = 'create';
    let activeRow = null;
    let deleteRow = null;
    let dataTable = window.packagesDataTable || null;
    const apiBase = `${window.APP_URL || ''}/controller/package`;

    function lockBody(lock) {
        document.body.style.overflow = lock ? 'hidden' : 'auto';
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (character) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
            }[character];
        });
    }

    function extractNumericPackageId(value) {
        if (value === null || value === undefined) {
            return null;
        }

        const text = String(value).trim();
        const normalized = text.replace(/^#P/i, '');
        const numericId = parseInt(normalized, 10);

        return Number.isNaN(numericId) ? null : numericId;
    }

    function normalizePackageData(data) {
        const source = data || {};

        return {
            id: extractNumericPackageId(source.id),
            name: String(source.name ?? ''),
            price: source.price ?? '',
        };
    }

    function getNextPackageId() {
        const rows = packagesTable ? packagesTable.querySelectorAll('tbody tr.package-row[data-id]') : [];
        let highestId = 0;

        rows.forEach(function (row) {
            const rowId = extractNumericPackageId(row.getAttribute('data-id'));
            if (rowId !== null && rowId > highestId) {
                highestId = rowId;
            }
        });

        return highestId + 1;
    }

    function formatPackageId(id) {
        const numericId = extractNumericPackageId(id);
        if (numericId === null) {
            return String(id ?? '');
        }

        return `#P${String(numericId).padStart(3, '0')}`;
    }

    function refreshIcons() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    function parseRow(row) {
        const idEl = row.querySelector('td:nth-child(1)');
        const nameEl = row.querySelector('td:nth-child(2)');
        const priceEl = row.querySelector('td:nth-child(3)');

        return {
            row,
            id: row.getAttribute('data-id') || (idEl ? idEl.textContent.trim() : ''),
            name: nameEl ? nameEl.textContent.trim() : '',
            price: priceEl ? priceEl.textContent.trim() : '',
        };
    }

    function parsePackageElement(element) {
        return element ? parseRow(element) : null;
    }

    function setModalState(mode, row = null) {
        modalMode = mode;
        activeRow = row;

        if (mode === 'edit' && row) {
            const data = parsePackageElement(row);
            modalTitle.textContent = 'Edit Package';
            packageNameInput.value = data.name;
            packagePriceInput.value = data.price;
            savePackageBtn.textContent = 'Update Package';
            return;
        }

        modalTitle.textContent = 'Add New Package';
        packageNameInput.value = '';
        packagePriceInput.value = '';
        savePackageBtn.textContent = 'Save Package';
    }

    function openPackageModal(mode = 'create', row = null) {
        setModalState(mode, row);
        packageModal.classList.remove('hidden');
        lockBody(true);
        refreshIcons();
    }

    function closePackageModal() {
        packageModal.classList.add('hidden');
        lockBody(false);
    }

    function openViewModal(row) {
        const data = parsePackageElement(row);

        document.getElementById('view-id').textContent = formatPackageId(data.id);
        document.getElementById('view-name').textContent = data.name;
        document.getElementById('view-price').textContent = data.price;

        viewModal.classList.remove('hidden');
        lockBody(true);
        refreshIcons();
    }

    function closeViewModal() {
        viewModal.classList.add('hidden');
        lockBody(false);
    }

    function openDeleteModal(row) {
        deleteRow = row;
        const data = parsePackageElement(row);
        if (deleteMessage) {
            deleteMessage.textContent = `This action cannot be undone. Package "${data.name}" (${formatPackageId(data.id)}) will be permanently deleted.`;
        }

        deleteModal.classList.remove('hidden');
        lockBody(true);
        refreshIcons();
    }

    function closeDeleteModal() {
        deleteModal.classList.add('hidden');
        lockBody(false);
        deleteRow = null;
    }

    function buildRowMarkup(data) {
        const packageData = normalizePackageData(data);
        const packageId = packageData.id ?? getNextPackageId();
        const formattedId = formatPackageId(packageId);
        const escapedId = escapeHtml(formattedId);
        const escapedName = escapeHtml(packageData.name);
        const escapedPrice = escapeHtml(packageData.price);

        return `
            <tr class="package-row" data-id="${escapeHtml(packageId)}">
                <td class="align-top">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-sm shrink-0">
                            <i data-lucide="package" class="w-5 h-5"></i>
                        </div>
                        <span data-role="desktop-id" class="hidden md:block font-medium text-gray-900 dark:text-gray-100">${escapedId}</span>
                        <div class="min-w-0 flex-1 md:hidden">
                            <p data-role="mobile-name" class="truncate font-medium text-gray-900 dark:text-gray-100">${escapedName}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">ID: <span data-role="mobile-id">${escapedId}</span></p>
                        </div>
                        <button type="button" class="mobile-row-toggle inline-flex md:hidden items-center justify-center w-8 h-8 rounded-full text-gray-500 dark:text-gray-400" aria-expanded="false" aria-label="Toggle details">
                            <i data-lucide="chevron-down" class="mobile-row-chevron w-4 h-4 transition-transform duration-200"></i>
                        </button>
                    </div>
                    <div class="mobile-row-details hidden md:hidden pt-3 mt-3 border-t border-gray-200 dark:border-gray-700">
                        <div class="grid grid-cols-1 gap-2 text-sm">
                            <div class="flex items-start justify-between gap-3">
                                <span class="text-gray-500 dark:text-gray-400">Package ID</span>
                                <span data-role="detail-id" class="text-right text-gray-800 dark:text-gray-200">${escapedId}</span>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <span class="text-gray-500 dark:text-gray-400">Package Name</span>
                                <span data-role="detail-name" class="text-right text-gray-800 dark:text-gray-200 break-all">${escapedName}</span>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <span class="text-gray-500 dark:text-gray-400">Price</span>
                                <span data-role="detail-price" class="text-right text-gray-800 dark:text-gray-200 font-semibold">${escapedPrice}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-2 mt-3">
                            <button class="view-package-btn inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 font-medium" title="View">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                                <span class="text-sm">View</span>
                            </button>
                            <button class="edit-package-btn inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400 font-medium" title="Edit">
                                <i data-lucide="edit" class="w-4 h-4"></i>
                                <span class="text-sm">Edit</span>
                            </button>
                            <button class="delete-package-btn inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400 font-medium" title="Delete">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                <span class="text-sm">Delete</span>
                            </button>
                        </div>
                    </div>
                </td>
                <td class="col-name hidden md:table-cell text-gray-700 dark:text-gray-300">
                    <span data-role="desktop-name" class="block truncate">${escapedName}</span>
                </td>
                <td class="col-price hidden md:table-cell text-gray-700 dark:text-gray-300">
                    <span data-role="desktop-price" class="block font-semibold text-green-600 dark:text-green-400">${escapedPrice}</span>
                </td>
                <td class="hidden md:table-cell whitespace-nowrap">
                    <div class="flex items-center gap-2 justify-center">
                        <button class="view-package-btn p-2 hover:bg-blue-100 dark:hover:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded transition-colors" title="View">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                        <button class="edit-package-btn p-2 hover:bg-amber-100 dark:hover:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded transition-colors" title="Edit">
                            <i data-lucide="edit" class="w-4 h-4"></i>
                        </button>
                        <button class="delete-package-btn p-2 hover:bg-red-100 dark:hover:bg-red-900/30 text-red-600 dark:text-red-400 rounded transition-colors" title="Delete">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }

    function refreshTable() {
        if (dataTable) {
            if (typeof dataTable.refresh === 'function') {
                dataTable.refresh();
            } else if (typeof dataTable.update === 'function') {
                dataTable.update();
            }
        }
        refreshIcons();
    }

    function addPackageToTable(data) {
        const tbody = packagesTable?.querySelector('tbody');
        if (!tbody) return;
        tbody.insertAdjacentHTML('afterbegin', buildRowMarkup(data));
        refreshTable();
    }

    function updatePackageRow(row, data) {
        const rowElement = getPackageRowElement(row);
        if (!rowElement) return;

        const packageData = normalizePackageData(data);
        const packageId = packageData.id ?? extractNumericPackageId(rowElement.getAttribute('data-id'));
        const formattedId = formatPackageId(packageId);

        rowElement.setAttribute('data-id', packageId ?? '');

        const desktopId = rowElement.querySelector('[data-role="desktop-id"]');
        const mobileId = rowElement.querySelector('[data-role="mobile-id"]');
        const detailId = rowElement.querySelector('[data-role="detail-id"]');
        const mobileName = rowElement.querySelector('[data-role="mobile-name"]');
        const detailName = rowElement.querySelector('[data-role="detail-name"]');
        const desktopName = rowElement.querySelector('[data-role="desktop-name"]');
        const detailPrice = rowElement.querySelector('[data-role="detail-price"]');
        const desktopPrice = rowElement.querySelector('[data-role="desktop-price"]');

        if (desktopId) desktopId.textContent = formattedId;
        if (mobileId) mobileId.textContent = formattedId;
        if (detailId) detailId.textContent = formattedId;
        if (mobileName) mobileName.textContent = packageData.name;
        if (detailName) detailName.textContent = packageData.name;
        if (desktopName) desktopName.textContent = packageData.name;
        if (detailPrice) detailPrice.textContent = packageData.price;
        if (desktopPrice) desktopPrice.textContent = packageData.price;

        refreshTable();
    }

    function removePackageRow(row) {
        const rowElement = getPackageRowElement(row);
        if (!rowElement) return;
        rowElement.remove();
        refreshTable();
    }

    function getPackageRowElement(row) {
        if (row instanceof HTMLElement) return row;
        if (!row) return null;
        const rawId = typeof row === 'string' ? row : row.id;
        const id = extractNumericPackageId(rawId);
        const selectorId = id ?? rawId;
        return packagesTable?.querySelector(`tr.package-row[data-id="${selectorId}"]`) || null;
    }

    function getFormData() {
        const name = packageNameInput.value.trim();
        const price = packagePriceInput.value.trim();

        return {
            name,
            price: parseFloat(price),
            id: activeRow ? extractNumericPackageId(parsePackageElement(activeRow)?.id) : null,
        };
    }

    addPackageBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        openPackageModal('create');
    });

    closeModalBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        closePackageModal();
    });

    cancelModalBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        closePackageModal();
    });

    savePackageBtn?.addEventListener('click', function (e) {
        e.preventDefault();

        const data = getFormData();
        if (!data.name) {
            Swal.mixin({
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                }
            }).fire({
                icon: "error",
                title: "Please enter package name"
            });
            return;
        }

        if (!data.price) {
            Swal.mixin({
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                }
            }).fire({
                icon: "error",
                title: "Please enter package price"
            });
            return;
        }
        return runWithButtonLoading(savePackageBtn, modalMode === 'create' ? 'Saving Package...' : 'Updating Package...', function () {
            if (modalMode === 'create') {
            fetch(`${apiBase}/create.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    name: data.name,
                    price: data.price
                })
            })
                .then(response => response.json())
                .then(result => {
                    if (result.status) {
                        Swal.mixin({
                            toast: true,
                            position: "top-end",
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.onmouseenter = Swal.stopTimer;
                                toast.onmouseleave = Swal.resumeTimer;
                            }
                        }).fire({
                            icon: "success",
                            title: "Package created successfully"
                        });
                        const createdPackage = normalizePackageData(result.data || {});
                        if (createdPackage.id === null) {
                            createdPackage.id = getNextPackageId();
                        }
                        createdPackage.name = createdPackage.name || data.name;
                        if (createdPackage.price === '' || createdPackage.price === null || createdPackage.price === undefined) {
                            createdPackage.price = data.price;
                        }
                        addPackageToTable(createdPackage);
                        closePackageModal();
                        setModalState('create');
                    } else {
                        Swal.mixin({
                            toast: true,
                            position: "top-end",
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.onmouseenter = Swal.stopTimer;
                                toast.onmouseleave = Swal.resumeTimer;
                            }
                        }).fire({
                            icon: "error",
                            title: result.message || 'Something went wrong'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.mixin({
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.onmouseenter = Swal.stopTimer;
                            toast.onmouseleave = Swal.resumeTimer;
                        }
                    }).fire({
                        icon: "error",
                        title: "Server error occurred"
                    });
                });
                return;
            }

            if (modalMode === 'edit' && activeRow) {
            fetch(`${apiBase}/update.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: data.id,
                    name: data.name,
                    price: data.price
                })
            })
                .then(response => response.json())
                .then(result => {
                    if (result.status) {
                        Swal.mixin({
                            toast: true,
                            position: "top-end",
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.onmouseenter = Swal.stopTimer;
                                toast.onmouseleave = Swal.resumeTimer;
                            }
                        }).fire({
                            icon: "success",
                            title: "Package updated successfully"
                        });
                        updatePackageRow(activeRow, result.data || data);
                        closePackageModal();
                        setModalState('create');
                    } else {
                        Swal.mixin({
                            toast: true,
                            position: "top-end",
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.onmouseenter = Swal.stopTimer;
                                toast.onmouseleave = Swal.resumeTimer;
                            }
                        }).fire({
                            icon: "error",
                            title: result.message || 'Something went wrong'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.mixin({
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.onmouseenter = Swal.stopTimer;
                            toast.onmouseleave = Swal.resumeTimer;
                        }
                    }).fire({
                        icon: "error",
                        title: "Server error occurred"
                    });
                });
            }
        });
    });

    closeViewModalBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        closeViewModal();
    });

    closeViewBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        closeViewModal();
    });

    viewEditBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        closeViewModal();
        if (activeRow) {
            openPackageModal('edit', activeRow);
        }
    });

    cancelDeleteBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        closeDeleteModal();
    });

    confirmDeleteBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        const data = parsePackageElement(deleteRow);
        const packageId = extractNumericPackageId(data?.id);

        if (deleteRow && packageId !== null) {
            fetch(`${apiBase}/delete.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: packageId
                })
            })
                .then(response => response.json())
                .then(result => {
                    if (result.status) {
                        Swal.mixin({
                            toast: true,
                            position: "top-end",
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.onmouseenter = Swal.stopTimer;
                                toast.onmouseleave = Swal.resumeTimer;
                            }
                        }).fire({
                            icon: "success",
                            title: "Package deleted successfully"
                        });
                        removePackageRow(deleteRow);
                        closeDeleteModal();
                    } else {
                        Swal.mixin({
                            toast: true,
                            position: "top-end",
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.onmouseenter = Swal.stopTimer;
                                toast.onmouseleave = Swal.resumeTimer;
                            }
                        }).fire({
                            icon: "error",
                            title: result.message || 'Something went wrong'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.mixin({
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.onmouseenter = Swal.stopTimer;
                            toast.onmouseleave = Swal.resumeTimer;
                        }
                    }).fire({
                        icon: "error",
                        title: "Server error occurred"
                    });
                });
        }
    });

    packagesTable?.addEventListener('click', function (e) {
        const mobileToggle = e.target.closest('.mobile-row-toggle');
        const viewBtn = e.target.closest('.view-package-btn');
        const editBtn = e.target.closest('.edit-package-btn');
        const deleteBtn = e.target.closest('.delete-package-btn');

        if (mobileToggle) {
            const row = mobileToggle.closest('tr.package-row');
            const details = row?.querySelector('.mobile-row-details');
            const chevron = row?.querySelector('.mobile-row-chevron');
            const isOpen = details && !details.classList.contains('hidden');

            if (details) {
                details.classList.toggle('hidden', isOpen);
            }

            chevron?.classList.toggle('rotate-180', !isOpen);
            mobileToggle.setAttribute('aria-expanded', String(!isOpen));
            return;
        }

        if (viewBtn) {
            const row = viewBtn.closest('tr.package-row');
            if (row) {
                activeRow = row;
                openViewModal(row);
            }
        }

        if (editBtn) {
            const row = editBtn.closest('tr.package-row');
            if (row) {
                activeRow = row;
                openPackageModal('edit', row);
            }
        }

        if (deleteBtn) {
            const row = deleteBtn.closest('tr.package-row');
            if (row) {
                openDeleteModal(row);
            }
        }
    });

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('modal-backdrop')) {
            closePackageModal();
            closeViewModal();
            closeDeleteModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closePackageModal();
            closeViewModal();
            closeDeleteModal();
        }
    });

});
document.addEventListener('DOMContentLoaded', function () {
    if (!document.getElementById('packages-table') || typeof simpleDatatables === 'undefined') {
        return;
    }

    const dataTable = new simpleDatatables.DataTable("#packages-table", {
        searchable: true,
        fixedHeight: false,
        perPage: 10,
        perPageSelect: [5, 10, 20, 50],
        labels: {
            placeholder: "Search packages...",
            perPage: "entries per page",
            noRows: "No packages found",
            info: "Showing {start} to {end} of {rows} packages",
        }
    });

    dataTable.on('datatable.page', function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
    dataTable.on('datatable.sort', function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
    dataTable.on('datatable.search', function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
    window.packagesDataTable = dataTable;
});
