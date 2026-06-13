import Swal from '../../node_modules/sweetalert2/dist/sweetalert2.esm.all.js';
import { DatePicker } from './datePicker.js';

document.addEventListener('DOMContentLoaded', function () {
    // ─── Modal & Table references ────────────────────────────────────────────
    const subscriptionModal = document.getElementById('subscription-modal');
    if (!subscriptionModal) return;

    const deleteModal = document.getElementById('sub-delete-modal');
    const viewModal = document.getElementById('sub-view-modal');
    const subscriptionsTable = document.getElementById('subscriptions-table');

    // Buttons – create/edit modal
    const addBtn = document.getElementById('add-user-btn');
    const closeModalBtn = document.getElementById('sub-close-modal-btn');
    const cancelModalBtn = document.getElementById('sub-cancel-modal-btn');
    const saveBtn = document.getElementById('sub-save-btn');

    // Buttons – view modal
    const closeViewModalBtn = document.getElementById('sub-close-view-modal-btn');
    const closeViewBtn = document.getElementById('sub-close-view-btn');
    const viewEditBtn = document.getElementById('sub-view-edit-btn');

    // Buttons – delete modal
    const cancelDeleteBtn = document.getElementById('sub-cancel-delete-btn');
    const confirmDeleteBtn = document.getElementById('sub-confirm-delete-btn');
    const deleteMessage = document.getElementById('sub-delete-message');

    // Form fields
    const modalTitle = document.getElementById('sub-modal-title');
    const userIdSelect = document.getElementById('sub-user-id');
    const packageIdSelect = document.getElementById('sub-package-id');
    const discountInput = document.getElementById('sub-discount');
    const paidAmountInput = document.getElementById('sub-paid-amount');
    const statusSelect = document.getElementById('sub-status');

    // ─── Custom Date Pickers ──────────────────────────────────────────────────
    const startDatePicker = new DatePicker({
        inputDisplayId: 'sub-start-date-display',
        hiddenInputId: 'sub-start-date',
        clearBtnId: 'sub-start-date-clear',
        modalId: 'sub-start-cal-modal',
        panelId: 'sub-start-cal-panel',
        monthYearId: 'sub-start-cal-month',
        daysGridId: 'sub-start-cal-grid',
        prevBtnId: 'sub-start-cal-prev',
        nextBtnId: 'sub-start-cal-next',
        cancelBtnId: 'sub-start-cal-cancel',
        todayBtnId: 'sub-start-cal-today',
    });

    const endDatePicker = new DatePicker({
        inputDisplayId: 'sub-end-date-display',
        hiddenInputId: 'sub-end-date',
        clearBtnId: 'sub-end-date-clear',
        modalId: 'sub-end-cal-modal',
        panelId: 'sub-end-cal-panel',
        monthYearId: 'sub-end-cal-month',
        daysGridId: 'sub-end-cal-grid',
        prevBtnId: 'sub-end-cal-prev',
        nextBtnId: 'sub-end-cal-next',
        cancelBtnId: 'sub-end-cal-cancel',
        todayBtnId: 'sub-end-cal-today',
    });

    // View modal fields
    const viewId = document.getElementById('sub-view-id');
    const viewCustomer = document.getElementById('sub-view-customer');
    const viewPackage = document.getElementById('sub-view-package');
    const viewPrice = document.getElementById('sub-view-price');
    const viewDiscount = document.getElementById('sub-view-discount');
    const viewPaid = document.getElementById('sub-view-paid');
    const viewStart = document.getElementById('sub-view-start');
    const viewEnd = document.getElementById('sub-view-end');
    const viewMonth = document.getElementById('sub-view-month');
    const viewStatus = document.getElementById('sub-view-status');

    const subApiBase = 'http://localhost/pakistan-cable/controller/subscription';
    const runWithButtonLoading = (button, label, action) => {
        if (window.AppButtonLoading?.withButtonLoading) {
            return window.AppButtonLoading.withButtonLoading(button, action, { label });
        }

        return action();
    };

    let modalMode = 'create';
    let activeRow = null;
    let deleteRow = null;
    let renewalMode = false;
    let renewalContext = null;
    let dashboardAlertRow = null;
    let dataTable = window.subscriptionsDataTable || null;

    // ─── Utility helpers ─────────────────────────────────────────────────────
    function lockBody(lock) {
        document.body.style.overflow = lock ? 'hidden' : 'auto';
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function extractNumericId(value) {
        if (value === null || value === undefined) return null;
        const text = String(value).trim().replace(/^#S/i, '');
        const n = parseInt(text, 10);
        return Number.isNaN(n) ? null : n;
    }

    function formatSubId(id) {
        const n = extractNumericId(id);
        return n !== null ? `#S${String(n).padStart(3, '0')}` : String(id ?? '');
    }

    function refreshIcons() {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function refreshTable() {
        if (dataTable) {
            if (typeof dataTable.refresh === 'function') dataTable.refresh();
            else if (typeof dataTable.update === 'function') dataTable.update();
        }
        refreshIcons();
    }

    function toast(icon, title) {
        Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (t) => { t.onmouseenter = Swal.stopTimer; t.onmouseleave = Swal.resumeTimer; }
        }).fire({ icon, title });
    }

    // ─── Paid Amount auto-calculation ────────────────────────────────────────
    function recalcPaid() {
        const selectedOption = packageIdSelect.options[packageIdSelect.selectedIndex];
        const price = parseFloat(selectedOption?.dataset?.price ?? 0) || 0;
        const discount = parseFloat(discountInput.value) || 0;
        const paid = Math.max(0, price - discount);
        paidAmountInput.value = paid > 0 ? paid.toFixed(2) : '';
    }

    function parseISODate(value) {
        if (!value) return null;
        const parts = String(value).split('-').map(Number);
        if (parts.length !== 3 || parts.some((n) => Number.isNaN(n))) return null;
        const date = new Date(parts[0], parts[1] - 1, parts[2]);
        return Number.isNaN(date.getTime()) ? null : date;
    }

    function formatISODate(date) {
        if (!(date instanceof Date) || Number.isNaN(date.getTime())) return '';
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function getAutoEndDate(startDateValue, days = 30) {
        const startDate = parseISODate(startDateValue);
        if (!startDate) return '';
        startDate.setDate(startDate.getDate() + days);
        return formatISODate(startDate);
    }

    function syncEndDateFromStart() {
        const startDateValue = startDatePicker.getValue();
        if (!startDateValue) {
            endDatePicker.clear();
            return;
        }
        endDatePicker.setValue(getAutoEndDate(startDateValue, 30));
    }

    function setRenewalMode(enabled, context = null) {
        renewalMode = !!enabled;
        renewalContext = context;

        if (userIdSelect) userIdSelect.disabled = renewalMode;
        if (packageIdSelect) packageIdSelect.disabled = renewalMode;
        startDatePicker.setDisabled?.(renewalMode);
        endDatePicker.setDisabled?.(renewalMode);

        if (renewalMode) {
            modalTitle.textContent = 'Activate Subscription';
            saveBtn.textContent = 'Activate Subscription';
        }
    }

    packageIdSelect.addEventListener('change', recalcPaid);
    discountInput.addEventListener('input', recalcPaid);
    document.getElementById('sub-start-date')?.addEventListener('change', syncEndDateFromStart);

    // ─── Status badge helpers ─────────────────────────────────────────────────
    function statusBadgeClasses(status) {
        const s = String(status ?? '').toLowerCase();
        if (s === 'active') return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
        if (s === 'expired') return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
        if (s === 'cancelled') return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
        return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
    }

    function statusLabel(status) {
        const s = String(status ?? '').toLowerCase();
        if (s === 'active') return 'Active';
        if (s === 'expired') return 'Expired';
        if (s === 'cancelled') return 'Cancelled';
        return status || '—';
    }

    // ─── Parse a subscription row from the DOM ────────────────────────────────
    function parseRow(row) {
        const dataset = row?.dataset || {};
        return {
            row,
            id: extractNumericId(dataset.id),
            name: dataset.name || '',
            packageName: dataset.packageName || '',
            packagePrice: dataset.packagePrice || '',
            discount: dataset.discount || '0',
            paidAmount: dataset.paidAmount || '',
            startDate: dataset.startDate || '',
            endDate: dataset.endDate || '',
            month: dataset.month || '',
            status: dataset.status || 'active',
            userId: dataset.userId || '',
            packageId: dataset.packageId || '',
            startRaw: dataset.startRaw || '',
            endRaw: dataset.endRaw || '',
        };
    }

    function getRowElement(row) {
        if (row instanceof HTMLElement) return row.matches('tr.subscription-row') ? row : row.closest('tr.subscription-row');
        if (!row) return null;
        const id = extractNumericId(typeof row === 'object' ? row.id : row);
        if (id === null) return null;
        return subscriptionsTable?.querySelector(`tr.subscription-row[data-id="${id}"]`) || null;
    }

    // ─── Build Row HTML ───────────────────────────────────────────────────────
    function buildRowMarkup(d) {
        const formattedId = formatSubId(d.id);
        const name = escapeHtml((d.name ?? '').toUpperCase());
        const packageName = escapeHtml(d.package_name ?? d.packageName ?? '');
        const packagePrice = escapeHtml(d.package_price ?? d.packagePrice ?? '');
        const discount = escapeHtml(d.discount ?? '0');
        const paidAmount = escapeHtml(d.paid_amount ?? d.paidAmount ?? '');
        const startDate = escapeHtml(d.start_date ?? d.startDate ?? '');
        const endDate = escapeHtml(d.end_date ?? d.endDate ?? '');
        const month = escapeHtml(d.package_month ?? d.month ?? '');
        const rawStatus = (d.status ?? 'active').toLowerCase();
        const statusText = escapeHtml(statusLabel(rawStatus));
        const badgeClasses = statusBadgeClasses(rawStatus);
        const userId = escapeHtml(d.user_id ?? d.userId ?? '');
        const packageId = escapeHtml(d.package_id ?? d.packageId ?? '');
        const startRaw = escapeHtml(d.start_raw ?? d.startRaw ?? startDate);
        const endRaw = escapeHtml(d.end_raw ?? d.endRaw ?? endDate);
        const numericId = escapeHtml(d.id ?? '');

        return `
            <tr class="subscription-row"
                data-id="${numericId}"
                data-name="${name}"
                data-package-name="${packageName}"
                data-package-price="${packagePrice}"
                data-discount="${discount}"
                data-paid-amount="${paidAmount}"
                data-start-date="${startDate}"
                data-end-date="${endDate}"
                data-month="${month}"
                data-status="${escapeHtml(rawStatus)}"
                data-user-id="${userId}"
                data-package-id="${packageId}"
                data-start-raw="${startRaw}"
                data-end-raw="${endRaw}"
            >
                <td class="align-top">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-sm shrink-0">
                            <i data-lucide="receipt" class="w-5 h-5"></i>
                        </div>
                        <span data-role="desktop-id" class="hidden md:block font-medium text-gray-900 dark:text-gray-100">${escapeHtml(formattedId)}</span>
                        <div class="min-w-0 flex-1 md:hidden">
                            <p data-role="mobile-name" class="truncate font-medium text-gray-900 dark:text-gray-100">${name}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">ID: <span data-role="mobile-id">${escapeHtml(formattedId)}</span></p>
                        </div>
                        <button type="button" class="mobile-row-toggle inline-flex items-center justify-center w-8 h-8 rounded-full text-gray-500 dark:text-gray-400" aria-expanded="false" aria-label="Toggle details">
                            <i data-lucide="chevron-down" class="mobile-row-chevron w-4 h-4 transition-transform duration-200"></i>
                        </button>
                    </div>
                </td>
                <td class="hidden md:table-cell text-gray-700 dark:text-gray-300">${name}</td>
                <td class="hidden md:table-cell text-gray-700 dark:text-gray-300">${packageName}</td>
                <td class="hidden">Rs.${packagePrice}</td>
                <td class="hidden">Rs.${discount}</td>
                <td class="hidden md:table-cell font-semibold text-blue-600 dark:text-blue-400">Rs.${paidAmount}</td>
                <td class="hidden md:table-cell text-gray-700 dark:text-gray-300">${startDate}</td>
                <td class="hidden md:table-cell text-gray-700 dark:text-gray-300">${endDate}</td>
                <td class="hidden">${month}</td>
                <td class="hidden md:table-cell">
                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium ${badgeClasses}">${statusText}</span>
                </td>
                <td class="hidden md:table-cell whitespace-nowrap">
                    <div class="flex items-center gap-2 justify-center">
                        <button class="view-subscription-btn p-2 hover:bg-blue-100 dark:hover:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded transition-colors" title="View">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                        <button class="edit-subscription-btn p-2 hover:bg-amber-100 dark:hover:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded transition-colors" title="Edit">
                            <i data-lucide="edit" class="w-4 h-4"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }

    // ─── Table operations ─────────────────────────────────────────────────────
    function addRowToTable(data) {
        const tbody = subscriptionsTable?.querySelector('tbody');
        if (!tbody) return;
        tbody.insertAdjacentHTML('afterbegin', buildRowMarkup(data));
        refreshTable();
    }

    function updateRowInTable(row, data) {
        const rowEl = getRowElement(row);
        if (!rowEl) return null;
        rowEl.outerHTML = buildRowMarkup(data);
        refreshTable();
        const updatedId = extractNumericId(data.id);
        return updatedId !== null ? getRowElement(updatedId) : null;
    }

    function removeRowFromTable(row) {
        const rowEl = getRowElement(row);
        if (!rowEl) return;
        rowEl.remove();
        refreshTable();
    }

    // ─── Modal open/close ─────────────────────────────────────────────────────
    function clearForm() {
        userIdSelect.value = '';
        packageIdSelect.value = '';
        discountInput.value = '';
        paidAmountInput.value = '';
        startDatePicker.clear();
        endDatePicker.clear();
        statusSelect.value = 'active';
        setRenewalMode(false, null);
    }

    function prepareActivationForm(context) {
        const data = context || {};
        clearForm();
        setRenewalMode(true, data);

        if (data.userId !== undefined) userIdSelect.value = String(data.userId || '');
        if (data.packageId !== undefined) packageIdSelect.value = String(data.packageId || '');
        discountInput.value = String(data.discount ?? '0');

        const startDate = data.startDate || formatISODate(new Date());
        const endDate = data.endDate || getAutoEndDate(startDate, 30);

        startDatePicker.setValue(startDate);
        endDatePicker.setValue(endDate);
        statusSelect.value = 'active';
        recalcPaid();
    }

    function prepareCreateForm(context) {
        const data = context || {};
        clearForm();

        if (data.userId !== undefined) userIdSelect.value = String(data.userId || '');
        if (data.packageId !== undefined) packageIdSelect.value = String(data.packageId || '');
        if (data.discount !== undefined) discountInput.value = String(data.discount ?? '0');

        const startDate = data.startDate || formatISODate(new Date());
        const endDate = data.endDate || getAutoEndDate(startDate, 30);

        startDatePicker.setValue(startDate);
        endDatePicker.setValue(endDate);
        statusSelect.value = 'active';
        recalcPaid();
    }

    function setModalState(mode, row = null) {
        modalMode = mode;
        activeRow = row ? getRowElement(row) : null;
        setRenewalMode(false, null);

        if (mode === 'edit' && activeRow) {
            const d = parseRow(activeRow);
            modalTitle.textContent = 'Edit Subscription';
            userIdSelect.value = d.userId || '';
            packageIdSelect.value = d.packageId || '';
            discountInput.value = d.discount || '0';
            paidAmountInput.value = d.paidAmount || '';
            startDatePicker.setValue(d.startRaw || '');
            endDatePicker.setValue(d.endRaw || getAutoEndDate(d.startRaw || '', 30));
            statusSelect.value = d.status || 'active';
            saveBtn.textContent = 'Update Subscription';
            recalcPaid();
            return;
        }

        modalTitle.textContent = 'Add New Subscription';
        clearForm();
        saveBtn.textContent = 'Save Subscription';
    }

    function openModal(mode = 'create', row = null) {
        setModalState(mode, row);
        subscriptionModal.classList.remove('hidden');
        lockBody(true);
        refreshIcons();
    }

    function openActivationModal(context) {
        prepareActivationForm(context);
        subscriptionModal.classList.remove('hidden');
        lockBody(true);
        refreshIcons();
    }

    function openPreFilledCreateModal(context) {
        prepareCreateForm(context);
        modalTitle.textContent = 'Add New Subscription';
        saveBtn.textContent = 'Save Subscription';
        subscriptionModal.classList.remove('hidden');
        lockBody(true);
        refreshIcons();
    }

    function closeModal() {
        subscriptionModal.classList.add('hidden');
        lockBody(false);
        setRenewalMode(false, null);
        dashboardAlertRow = null;
    }

    function openViewModal(row) {
        const rowEl = getRowElement(row);
        if (!rowEl) return;
        activeRow = rowEl;
        const d = parseRow(rowEl);

        viewId.textContent = formatSubId(d.id);
        viewCustomer.textContent = d.name || '—';
        viewPackage.textContent = d.packageName || '—';
        viewPrice.textContent = d.packagePrice ? `Rs.${d.packagePrice}` : '—';
        viewDiscount.textContent = d.discount ? `Rs.${d.discount}` : 'Rs.0';
        viewPaid.textContent = d.paidAmount ? `Rs.${d.paidAmount}` : '—';
        viewStart.textContent = d.startDate || '—';
        viewEnd.textContent = d.endDate || '—';
        viewMonth.textContent = d.month || '—';

        const rawStatus = d.status || 'active';
        viewStatus.textContent = statusLabel(rawStatus);
        viewStatus.className = `inline-flex px-3 py-1 rounded-full text-xs font-medium ${statusBadgeClasses(rawStatus)}`;

        viewModal.classList.remove('hidden');
        lockBody(true);
        refreshIcons();
    }

    function closeViewModal() {
        viewModal.classList.add('hidden');
        lockBody(false);
    }

    function openDeleteModal(row) {
        const rowEl = getRowElement(row);
        if (!rowEl) return;
        deleteRow = rowEl;
        const d = parseRow(rowEl);

        if (deleteMessage) {
            deleteMessage.textContent = `This action cannot be undone. Subscription ${formatSubId(d.id)} for "${d.name}" will be permanently deleted.`;
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

    // ─── Form data & validation ───────────────────────────────────────────────
    function getFormData() {
        return {
            id: activeRow ? extractNumericId(activeRow.getAttribute('data-id')) : null,
            user_id: userIdSelect.value || null,
            package_id: packageIdSelect.value || null,
            discount: parseFloat(discountInput.value) || 0,
            start_date: startDatePicker.getValue() || '',
            end_date: endDatePicker.getValue() || '',
            status: statusSelect.value || 'active',
        };
    }

    function validateForm(data) {
        if (!data.user_id) {
            toast('error', 'Please select a customer');
            return false;
        }
        if (!data.package_id) {
            toast('error', 'Please select a package');
            return false;
        }
        if (!data.start_date) {
            toast('error', 'Please enter start date');
            return false;
        }
        if (!data.end_date) {
            toast('error', 'Please enter end date');
            return false;
        }
        if (new Date(data.end_date) < new Date(data.start_date)) {
            toast('error', 'End date cannot be before start date');
            return false;
        }
        return true;
    }

    function sendRequest(url, payload) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        }).then(r => r.json());
    }

    // ─── Save (create / update) ───────────────────────────────────────────────
    saveBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        const data = getFormData();
        if (!validateForm(data)) return;

        return runWithButtonLoading(saveBtn, 'Saving Subscription...', function () {
            if (renewalMode) {
                const payload = {
                    ...data,
                    base_subscription_id: renewalContext?.subscriptionId || null,
                };

                sendRequest(`${subApiBase}/activate.php`, payload)
                    .then(function (result) {
                        if (result.status) {
                            toast('success', 'Subscription activated successfully');
                            if (dashboardAlertRow && typeof window.removeSubscriptionAlertRow === 'function') {
                                window.removeSubscriptionAlertRow(dashboardAlertRow);
                                dashboardAlertRow = null;
                            } else if (typeof window.refreshSubscriptionAlertsTable === 'function') {
                                window.refreshSubscriptionAlertsTable();
                            }
                            closeModal();
                        } else {
                            toast('error', result.message || 'Something went wrong');
                        }
                    })
                    .catch(function () { toast('error', 'Server error occurred'); });
                return;
            }

            if (modalMode === 'create') {
                sendRequest(`${subApiBase}/create.php`, data)
                    .then(function (result) {
                        if (result.status) {
                            toast('success', 'Subscription created successfully');
                            addRowToTable(result.data || data);
                            if (dashboardAlertRow && typeof window.removeSubscriptionAlertRow === 'function') {
                                window.removeSubscriptionAlertRow(dashboardAlertRow);
                                dashboardAlertRow = null;
                            } else if (typeof window.refreshSubscriptionAlertsTable === 'function') {
                                window.refreshSubscriptionAlertsTable();
                            }
                            closeModal();
                            setModalState('create');
                        } else {
                            toast('error', result.message || 'Something went wrong');
                        }
                    })
                    .catch(function () { toast('error', 'Server error occurred'); });
                return;
            }

            if (modalMode === 'edit' && activeRow) {
                sendRequest(`${subApiBase}/update.php`, data)
                    .then(function (result) {
                        if (result.status) {
                            toast('success', 'Subscription updated successfully');
                            activeRow = updateRowInTable(activeRow, result.data || data);
                            closeModal();
                            setModalState('create');
                        } else {
                            toast('error', result.message || 'Something went wrong');
                        }
                    })
                    .catch(function () { toast('error', 'Server error occurred'); });
            }
        });
    });

    // ─── Delete confirmation ──────────────────────────────────────────────────
    confirmDeleteBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        if (!deleteRow) return;

        const d = parseRow(deleteRow);
        const id = d.id;

        if (id === null) {
            toast('error', 'Subscription ID is missing');
            return;
        }

        return runWithButtonLoading(confirmDeleteBtn, 'Deleting...', function () {
            return sendRequest(`${subApiBase}/delete.php`, { id })
                .then(function (result) {
                    if (result.status) {
                        toast('success', 'Subscription deleted successfully');
                        removeRowFromTable(deleteRow);
                        closeDeleteModal();
                    } else {
                        toast('error', result.message || 'Something went wrong');
                    }
                })
                .catch(function () { toast('error', 'Server error occurred'); });
        });
    });

    // ─── Button listeners ─────────────────────────────────────────────────────
    addBtn?.addEventListener('click', (e) => { e.preventDefault(); openModal('create'); });
    closeModalBtn?.addEventListener('click', (e) => { e.preventDefault(); closeModal(); });
    cancelModalBtn?.addEventListener('click', (e) => { e.preventDefault(); closeModal(); });
    closeViewModalBtn?.addEventListener('click', (e) => { e.preventDefault(); closeViewModal(); });
    closeViewBtn?.addEventListener('click', (e) => { e.preventDefault(); closeViewModal(); });
    viewEditBtn?.addEventListener('click', (e) => { e.preventDefault(); closeViewModal(); if (activeRow) openModal('edit', activeRow); });
    cancelDeleteBtn?.addEventListener('click', (e) => { e.preventDefault(); closeDeleteModal(); });

    // ─── Table click delegation ───────────────────────────────────────────────
    // Note: .mobile-row-toggle is handled globally by the auto-toggle handler
    // in script.js (see section 7).  We only need to handle action buttons here,
    // including when they appear inside a tr.dt-details-row (global details row).
    subscriptionsTable?.addEventListener('click', function (e) {
        const viewBtn = e.target.closest('.view-subscription-btn');
        const editBtn = e.target.closest('.edit-subscription-btn');
        const deleteBtn = e.target.closest('.delete-subscription-btn');

        if (viewBtn) {
            let row = viewBtn.closest('tr.subscription-row');
            if (!row) {
                const detailsRow = viewBtn.closest('tr.dt-details-row');
                row = detailsRow?.previousElementSibling;
            }
            if (row) openViewModal(row);
        }

        if (editBtn) {
            let row = editBtn.closest('tr.subscription-row');
            if (!row) {
                const detailsRow = editBtn.closest('tr.dt-details-row');
                row = detailsRow?.previousElementSibling;
            }
            if (row) openModal('edit', row);
        }

        if (deleteBtn) {
            let row = deleteBtn.closest('tr.subscription-row');
            if (!row) {
                const detailsRow = deleteBtn.closest('tr.dt-details-row');
                row = detailsRow?.previousElementSibling;
            }
            if (row) openDeleteModal(row);
        }
    });

    // ─── Backdrop & Escape close ──────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('modal-backdrop')) {
            closeModal();
            closeViewModal();
            closeDeleteModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeModal();
            closeViewModal();
            closeDeleteModal();
        }
    });

    document.addEventListener('click', function (e) {
        const activateBtn = e.target.closest('.activate-subscription-btn');
        if (!activateBtn) return;

        dashboardAlertRow = activateBtn.closest('tr');

        const context = {
            userId: activateBtn.dataset.userId || '',
            packageId: activateBtn.dataset.packageId || '',
            discount: activateBtn.dataset.discount || '0',
            subscriptionId: activateBtn.dataset.subscriptionId || '',
            startDate: activateBtn.dataset.startDate || '',
            endDate: activateBtn.dataset.endDate || '',
            userPackageId: activateBtn.dataset.userPackageId || '',
        };

        if (context.subscriptionId) {
            openActivationModal(context);
            return;
        }

        if (!context.packageId && context.userPackageId) {
            context.packageId = context.userPackageId;
        }

        openPreFilledCreateModal(context);
    });

    window.openSubscriptionActivationModal = openActivationModal;
});

// ─── DataTable initialisation ─────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    if (!document.getElementById('subscriptions-table') || typeof simpleDatatables === 'undefined') return;

    const dataTable = new simpleDatatables.DataTable('#subscriptions-table', {
        searchable: true,
        fixedHeight: false,
        perPage: 10,
        perPageSelect: [5, 10, 20, 50],
        labels: {
            placeholder: 'Search subscriptions...',
            perPage: 'entries per page',
            noRows: 'No subscriptions found',
            info: 'Showing {start} to {end} of {rows} subscriptions',
        }
    });

    const refreshLucide = () => { if (typeof lucide !== 'undefined') lucide.createIcons(); };
    dataTable.on('datatable.page', refreshLucide);
    dataTable.on('datatable.sort', refreshLucide);
    dataTable.on('datatable.search', refreshLucide);
    window.subscriptionsDataTable = dataTable;
});
