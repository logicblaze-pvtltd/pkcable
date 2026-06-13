import Swal from '../../node_modules/sweetalert2/dist/sweetalert2.esm.all.js';

document.addEventListener('DOMContentLoaded', function () {
    const userModal = document.getElementById('user-modal');
    if (!userModal) return;

    const deleteModal = document.getElementById('delete-modal');
    const viewModal = document.getElementById('view-modal');
    const managersTable = document.getElementById('managers-table');

    const addUserBtn = document.getElementById('add-user-btn');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const cancelModalBtn = document.getElementById('cancel-modal-btn');
    const saveUserBtn = document.getElementById('save-user-btn');

    const closeViewModalBtn = document.getElementById('close-view-modal-btn');
    const closeViewBtn = document.getElementById('close-view-btn');
    const viewEditBtn = document.getElementById('view-edit-btn');

    const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
    const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
    const deleteMessage = document.getElementById('delete-message');

    const modalTitle = document.getElementById('modal-title');
    const userNameInput = document.getElementById('user-name');
    const userEmailInput = document.getElementById('user-email');
    const userPasswordInput = document.getElementById('user-password');
    const userStatusSelect = document.getElementById('user-status');
    const userAddressInput = document.getElementById('user-address');

    const viewAvatar = document.getElementById('view-avatar');
    const viewName = document.getElementById('view-name');
    const viewId = document.getElementById('view-id');
    const viewEmail = document.getElementById('view-email');
    const viewRole = document.getElementById('view-role');
    const viewStatus = document.getElementById('view-status');
    const viewAddress = document.getElementById('view-address');
    const viewDate = document.getElementById('view-date');

    const apiBase = 'controller/manager';

    let modalMode = 'create';
    let activeRow = null;
    let deleteRow = null;
    let dataTable = window.managersDataTable || null;

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

    function extractNumericId(value) {
        if (value === null || value === undefined) {
            return null;
        }

        const text = String(value).trim().replace(/^#M/i, '');
        const numericId = parseInt(text, 10);

        return Number.isNaN(numericId) ? null : numericId;
    }

    function formatManagerId(id) {
        const numericId = extractNumericId(id);
        if (numericId === null) {
            return String(id ?? '');
        }

        return `#M${String(numericId).padStart(3, '0')}`;
    }

    function getInitials(name) {
        const parts = String(name ?? '')
            .trim()
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2);

        const initials = parts
            .map(function (part) {
                return part.charAt(0).toUpperCase();
            })
            .join('');

        return initials || 'M';
    }

    function formatDateDisplay(value) {
        const rawValue = String(value ?? '').trim();
        if (!rawValue) {
            return '';
        }

        const normalizedValue = rawValue.replace(' ', 'T');
        const date = new Date(normalizedValue);

        if (!Number.isNaN(date.getTime())) {
            return new Intl.DateTimeFormat('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
            }).format(date);
        }

        return rawValue;
    }

    function refreshIcons() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    function normalizeManagerData(data) {
        const source = data || {};
        const managerId = extractNumericId(source.id ?? source.id_display ?? null);
        const createdAtRaw = String(source.created_at ?? '').trim();
        const createdAtDisplay = String(source.created_at_display ?? '').trim() || formatDateDisplay(createdAtRaw);

        return {
            id: managerId,
            idDisplay: String(source.id_display ?? '').trim() || (managerId !== null ? formatManagerId(managerId) : ''),
            name: String(source.name ?? '').trim(),
            email: String(source.email ?? '').trim(),
            password: String(source.password ?? '').trim(),
            userRole: 'manager',
            status: String(source.status ?? 'active').trim().toLowerCase() || 'active',
            address: String(source.address ?? '').trim(),
            createdAt: createdAtRaw,
            createdAtDisplay,
        };
    }

    function parseRow(row) {
        const rowElement = row instanceof HTMLElement ? row : null;
        const dataset = rowElement?.dataset || {};
        const firstCell = rowElement?.querySelector('td:nth-child(1)');
        const nameElement = firstCell?.querySelector('p:first-child');
        const idElement = firstCell?.querySelector('p:nth-child(2)');
        const emailElement = rowElement?.querySelector('td:nth-child(2)');
        const statusElement = rowElement?.querySelector('td:nth-child(3) span');
        const createdElement = rowElement?.querySelector('td:nth-child(4)');

        const idValue = dataset.id || idElement?.textContent?.replace(/^ID:\s*/i, '') || '';

        return {
            row: rowElement,
            id: extractNumericId(idValue),
            idDisplay: idElement?.textContent?.replace(/^ID:\s*/i, '') || '',
            name: dataset.name || nameElement?.textContent?.trim() || '',
            email: dataset.email || emailElement?.textContent?.trim() || '',
            userRole: 'manager',
            status: String(dataset.status || statusElement?.textContent || 'active').trim().toLowerCase(),
            address: dataset.address || '',
            createdAtDisplay: dataset.createdAt || createdElement?.textContent?.trim() || '',
        };
    }

    function getRowElement(row) {
        if (row instanceof HTMLElement) {
            return row.matches('tr.user-row') ? row : row.closest('tr.user-row');
        }

        if (row === null || row === undefined) {
            return null;
        }

        const rowId = extractNumericId(row);
        if (rowId === null) {
            return null;
        }

        return managersTable?.querySelector(`tr.user-row[data-id="${rowId}"]`) || null;
    }

    function getNextId() {
        const rows = managersTable ? managersTable.querySelectorAll('tbody tr.user-row[data-id]') : [];
        let highestId = 0;

        rows.forEach(function (row) {
            const rowId = extractNumericId(row.getAttribute('data-id'));
            if (rowId !== null && rowId > highestId) {
                highestId = rowId;
            }
        });

        return highestId + 1;
    }

    function setText(element, value) {
        if (element) {
            element.textContent = value;
        }
    }

    function setBadge(element, label, classes, baseClasses) {
        if (!element) {
            return;
        }

        element.textContent = label;
        element.className = `${baseClasses} ${classes}`;
    }

    function setAvatar(element, initials) {
        if (!element) {
            return;
        }

        const baseClasses = 'w-16 h-16 rounded-2xl flex items-center justify-center font-bold text-2xl shadow-sm';
        element.textContent = initials;
        element.className = `${baseClasses} bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400`;
    }

    function buildRowMarkup(data) {
        const manager = normalizeManagerData(data);
        const managerId = manager.id !== null ? manager.id : getNextId();
        const managerIdDisplay = manager.idDisplay || formatManagerId(managerId);
        const avatarInitials = escapeHtml(getInitials(manager.name));
        const name = escapeHtml(manager.name || 'Unnamed Manager');
        const email = escapeHtml(manager.email || '—');
        const address = escapeHtml(manager.address || '—');
        const createdAt = escapeHtml(manager.createdAtDisplay || '—');
        const statusLabel = escapeHtml(manager.status.replace(/\b\w/g, l => l.toUpperCase()));
        const statusClasses = manager.status === 'active' 
            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' 
            : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';

        return `
            <tr
                class="user-row"
                data-id="${managerId}"
                data-name="${name}"
                data-email="${email}"
                data-user-role="manager"
                data-status="${escapeHtml(manager.status)}"
                data-address="${address}"
                data-created-at="${createdAt}"
            >
                <td class="align-top">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center font-bold text-sm shrink-0">
                            ${avatarInitials}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-gray-900 dark:text-gray-100">${name}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">ID: ${escapeHtml(managerIdDisplay)}</p>
                        </div>
                        <div class="md:hidden flex items-center gap-2 shrink-0">
                            <span class="px-2.5 py-1 ${statusClasses} rounded-full text-[11px] font-medium">
                                ${statusLabel}
                            </span>
                            <button type="button" class="mobile-row-toggle inline-flex items-center justify-center w-8 h-8 rounded-full text-gray-500 dark:text-gray-400" aria-expanded="false" aria-label="Toggle details">
                                <i data-lucide="chevron-down" class="mobile-row-chevron w-4 h-4 transition-transform duration-200"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mobile-row-details hidden md:hidden pt-3 mt-3 border-t border-gray-200 dark:border-gray-700">
                        <div class="grid grid-cols-1 gap-2 text-sm">
                            <div class="flex items-start justify-between gap-3">
                                <span class="text-gray-500 dark:text-gray-400">Email</span>
                                <span class="text-right text-gray-800 dark:text-gray-200 break-all">${email}</span>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <span class="text-gray-500 dark:text-gray-400">Address</span>
                                <span class="text-right text-gray-800 dark:text-gray-200 break-all">${address}</span>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <span class="text-gray-500 dark:text-gray-400">Created</span>
                                <span class="text-right text-gray-800 dark:text-gray-200">${createdAt}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-2 mt-3">
                            <button type="button" class="view-user-btn inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 font-medium" title="View">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                                <span class="text-sm">View</span>
                            </button>
                            <button type="button" class="edit-user-btn inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400 font-medium" title="Edit">
                                <i data-lucide="edit" class="w-4 h-4"></i>
                                <span class="text-sm">Edit</span>
                            </button>
                            <button type="button" class="delete-user-btn inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400 font-medium" title="Delete">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                <span class="text-sm">Delete</span>
                            </button>
                        </div>
                    </div>
                </td>
                <td class="hidden md:table-cell text-gray-700 dark:text-gray-300">
                    <span class="block truncate">${email}</span>
                </td>
                <td class="hidden md:table-cell text-gray-700 dark:text-gray-300">
                    <span class="inline-flex px-3 py-1 ${statusClasses} rounded-full text-xs font-medium">${statusLabel}</span>
                </td>
                <td class="hidden md:table-cell text-gray-700 dark:text-gray-300">
                    <span class="block truncate">${createdAt}</span>
                </td>
                <td class="hidden md:table-cell whitespace-nowrap">
                    <div class="flex items-center gap-2 justify-center">
                        <button type="button" class="view-user-btn p-2 hover:bg-blue-100 dark:hover:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded transition-colors" title="View">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                        <button type="button" class="edit-user-btn p-2 hover:bg-amber-100 dark:hover:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded transition-colors" title="Edit">
                            <i data-lucide="edit" class="w-4 h-4"></i>
                        </button>
                        <button type="button" class="delete-user-btn p-2 hover:bg-red-100 dark:hover:bg-red-900/30 text-red-600 dark:text-red-400 rounded transition-colors" title="Delete">
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

    function addManagerToTable(data) {
        const tbody = managersTable?.querySelector('tbody');
        if (!tbody) return;
        tbody.insertAdjacentHTML('afterbegin', buildRowMarkup(data));
        refreshTable();
    }

    function replaceManagerRow(row, data) {
        const rowElement = getRowElement(row);
        if (!rowElement) return null;

        const manager = normalizeManagerData(data);
        rowElement.outerHTML = buildRowMarkup(manager);
        refreshTable();

        return getRowElement(manager.id);
    }

    function removeManagerRow(row) {
        const rowElement = getRowElement(row);
        if (!rowElement) return;
        rowElement.remove();
        refreshTable();
    }

    function clearForm() {
        userNameInput.value = '';
        userEmailInput.value = '';
        userPasswordInput.value = '';
        userStatusSelect.value = 'active';
        userAddressInput.value = '';
        userPasswordInput.placeholder = 'Enter password';
    }

    function setModalState(mode, row = null) {
        modalMode = mode;
        activeRow = row ? getRowElement(row) : null;

        if (mode === 'edit' && activeRow) {
            const data = parseRow(activeRow);
            modalTitle.textContent = 'Edit Manager';
            userNameInput.value = data.name;
            userEmailInput.value = data.email;
            userPasswordInput.value = '';
            userPasswordInput.placeholder = 'Leave blank to keep current password';
            userStatusSelect.value = data.status || 'active';
            userAddressInput.value = data.address;
            saveUserBtn.textContent = 'Update Manager';
            return;
        }

        modalTitle.textContent = 'Add New Manager';
        clearForm();
        saveUserBtn.textContent = 'Save Manager';
    }

    function openUserModal(mode = 'create', row = null) {
        setModalState(mode, row);
        userModal.classList.remove('hidden');
        lockBody(true);
        refreshIcons();
    }

    function closeUserModal() {
        userModal.classList.add('hidden');
        lockBody(false);
    }

    function openViewModal(row) {
        const rowElement = getRowElement(row);
        if (!rowElement) return;

        activeRow = rowElement;
        const data = parseRow(rowElement);
        const statusLabel = data.status.replace(/\b\w/g, l => l.toUpperCase());
        const statusClasses = data.status === 'active' 
            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' 
            : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';

        setAvatar(viewAvatar, getInitials(data.name));
        setText(viewName, data.name || '—');
        setText(viewId, formatManagerId(data.id));
        setText(viewEmail, data.email || '—');
        setBadge(viewRole, 'Manager', 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400', 'inline-flex px-3 py-1 rounded-full text-xs font-medium');
        setBadge(viewStatus, statusLabel, statusClasses, 'inline-flex px-3 py-1 rounded-full text-xs font-medium');
        setText(viewAddress, data.address || '—');
        setText(viewDate, data.createdAtDisplay || '—');

        viewModal.classList.remove('hidden');
        lockBody(true);
        refreshIcons();
    }

    function closeViewModal() {
        viewModal.classList.add('hidden');
        lockBody(false);
    }

    function openDeleteModal(row) {
        const rowElement = getRowElement(row);
        if (!rowElement) return;

        deleteRow = rowElement;
        const data = parseRow(rowElement);

        if (deleteMessage) {
            deleteMessage.textContent = `This action cannot be undone. Manager "${data.name}" (${formatManagerId(data.id)}) will be permanently deleted.`;
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

    function getFormData() {
        return {
            id: activeRow ? extractNumericId(activeRow.getAttribute('data-id')) : null,
            name: userNameInput.value.trim(),
            email: userEmailInput.value.trim(),
            password: userPasswordInput.value,
            user_role: 'manager',
            status: userStatusSelect.value,
            address: userAddressInput.value.trim(),
        };
    }

    function validateFormData(data) {
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
                title: "Please enter manager name"
            });
            return false;
        }

        if (!data.email) {
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
                title: "Please enter manager email"
            });
            return false;
        }

        if (!/^\S+@\S+\.\S+$/.test(data.email)) {
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
                title: "Please enter a valid email address"
            });
            return false;
        }

        return true;
    }

    function sendRequest(url, payload) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        }).then(function (response) {
            return response.json();
        });
    }

    function handleCreate() {
        const data = getFormData();
        if (!validateFormData(data)) return;

        sendRequest(`${apiBase}/create.php`, data)
            .then(function (result) {
                if (result?.status) {
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
                        title: result.message || 'Manager created successfully'
                    });
                    
                    const addedUser = result.data?.user || data;
                    if (result.data?.generated_password) {
                        Swal.fire({
                            title: 'Manager Created!',
                            html: `Manager has been created. <br><br><b>Email:</b> ${escapeHtml(addedUser.email)}<br><b>Password:</b> <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 rounded text-red-500 font-mono select-all">${escapeHtml(result.data.generated_password)}</code><br><br>Please copy this password. It will not be shown again.`,
                            icon: 'success',
                            confirmButtonText: 'Okay'
                        });
                    }

                    addManagerToTable(addedUser);
                    closeUserModal();
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
                        title: result?.message || 'Something went wrong'
                    });
                }
            })
            .catch(function (error) {
                console.error('Create manager error:', error);
                Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                }).fire({
                    icon: "error",
                    title: "Server error occurred"
                });
            });
    }

    function handleUpdate() {
        const data = getFormData();
        if (!validateFormData(data)) return;

        if (data.id === null) {
            Swal.mixin({
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 3000
            }).fire({
                icon: "error",
                title: "Manager ID is missing"
            });
            return;
        }

        sendRequest(`${apiBase}/update.php`, data)
            .then(function (result) {
                if (result?.status) {
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
                        title: "Manager updated successfully"
                    });
                    activeRow = replaceManagerRow(activeRow, result.data || data);
                    closeUserModal();
                    setModalState('create');
                } else {
                    Swal.mixin({
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 3000
                    }).fire({
                        icon: "error",
                        title: result?.message || 'Something went wrong'
                    });
                }
            })
            .catch(function (error) {
                console.error('Update manager error:', error);
                Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000
                }).fire({
                    icon: "error",
                    title: "Server error occurred"
                });
            });
    }

    addUserBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        openUserModal('create');
    });

    closeModalBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        closeUserModal();
    });

    cancelModalBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        closeUserModal();
    });

    saveUserBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        if (modalMode === 'create') {
            handleCreate();
        } else {
            handleUpdate();
        }
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
            openUserModal('edit', activeRow);
        }
    });

    cancelDeleteBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        closeDeleteModal();
    });

    confirmDeleteBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        const data = parseRow(deleteRow);
        if (deleteRow && data.id !== null) {
            sendRequest(`${apiBase}/delete.php`, { id: data.id })
                .then(function (result) {
                    if (result?.status) {
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
                            title: "Manager deleted successfully"
                        });
                        removeManagerRow(deleteRow);
                        closeDeleteModal();
                    } else {
                        Swal.mixin({
                            toast: true,
                            position: "top-end",
                            showConfirmButton: false,
                            timer: 3000
                        }).fire({
                            icon: "error",
                            title: result?.message || 'Something went wrong'
                        });
                    }
                })
                .catch(function (error) {
                    console.error('Delete manager error:', error);
                    Swal.mixin({
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 3000
                    }).fire({
                        icon: "error",
                        title: "Server error occurred"
                    });
                });
        }
    });

    managersTable?.addEventListener('click', function (e) {
        const mobileToggle = e.target.closest('.mobile-row-toggle');
        const viewBtn = e.target.closest('.view-user-btn');
        const editBtn = e.target.closest('.edit-user-btn');
        const deleteBtn = e.target.closest('.delete-user-btn');

        if (mobileToggle) {
            const row = mobileToggle.closest('tr.user-row');
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
            const row = viewBtn.closest('tr.user-row');
            if (row) {
                openViewModal(row);
            }
        }

        if (editBtn) {
            const row = editBtn.closest('tr.user-row');
            if (row) {
                openUserModal('edit', row);
            }
        }

        if (deleteBtn) {
            const row = deleteBtn.closest('tr.user-row');
            if (row) {
                openDeleteModal(row);
            }
        }
    });

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('modal-backdrop')) {
            closeUserModal();
            closeViewModal();
            closeDeleteModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeUserModal();
            closeViewModal();
            closeDeleteModal();
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const tableEl = document.getElementById('managers-table');
    if (!tableEl || typeof simpleDatatables === 'undefined') {
        return;
    }

    const dataTable = new simpleDatatables.DataTable("#managers-table", {
        searchable: true,
        fixedHeight: false,
        perPage: 10,
        perPageSelect: [5, 10, 20, 50],
        labels: {
            placeholder: "Search managers...",
            perPage: "entries per page",
            noRows: "No managers found",
            info: "Showing {start} to {end} of {rows} managers",
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
    window.managersDataTable = dataTable;
});
