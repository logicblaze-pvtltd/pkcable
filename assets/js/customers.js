import Swal from "../../node_modules/sweetalert2/dist/sweetalert2.esm.all.js";
import { DatePicker } from "./datePicker.js";
document.addEventListener("DOMContentLoaded", function () {
  const userModal = document.getElementById("user-modal");
  if (!userModal) return;

  const deleteModal = document.getElementById("delete-modal");
  const viewModal = document.getElementById("view-modal");
  const customersTable = document.getElementById("customers-table");

  const addUserBtn = document.getElementById("add-user-btn");
  const closeModalBtn = document.getElementById("close-modal-btn");
  const cancelModalBtn = document.getElementById("cancel-modal-btn");
  const saveUserBtn = document.getElementById("save-user-btn");

  const runWithButtonLoading = (button, label, action) => {
    if (window.AppButtonLoading?.withButtonLoading) {
      return window.AppButtonLoading.withButtonLoading(button, action, {
        label,
      });
    }

    return action();
  };

  const closeViewModalBtn = document.getElementById("close-view-modal-btn");
  const closeViewBtn = document.getElementById("close-view-btn");
  const viewEditBtn = document.getElementById("view-edit-btn");

  const cancelDeleteBtn = document.getElementById("cancel-delete-btn");
  const confirmDeleteBtn = document.getElementById("confirm-delete-btn");
  const deleteMessage = document.getElementById("delete-message");

  const modalTitle = document.getElementById("modal-title");
  const userNameInput = document.getElementById("user-name");
  const userEmailInput = document.getElementById("user-email");
  const userPasswordInput = document.getElementById("user-password");
  const userStatusSelect = document.getElementById("user-status");
  const userPackageSelect = document.getElementById("user-package");
  const userAddressInput = document.getElementById("user-address");

  const viewAvatar = document.getElementById("view-avatar");
  const viewName = document.getElementById("view-name");
  const viewId = document.getElementById("view-id");
  const viewEmail = document.getElementById("view-email");
  const viewRole = document.getElementById("view-role");
  const viewPackage = document.getElementById("view-package");
  const viewPackageId = document.getElementById("view-package-id");
  const viewStatus = document.getElementById("view-status");
  const viewAddress = document.getElementById("view-address");
  const viewDate = document.getElementById("view-date");

  const subscriptionModal = document.getElementById("subscription-modal");
  const subCloseModalBtn = document.getElementById("sub-close-modal-btn");
  const subCancelModalBtn = document.getElementById("sub-cancel-modal-btn");
  const subSaveBtn = document.getElementById("sub-save-btn");
  const subModalTitle = document.getElementById("sub-modal-title");
  const subUserId = document.getElementById("sub-user-id");
  const subPackageId = document.getElementById("sub-package-id");
  const subPackagePrice = document.getElementById("package-price");
  const subDiscount = document.getElementById("sub-discount");
  const subPaidAmount = document.getElementById("sub-paid-amount");
  const subStatus = document.getElementById("sub-status");
  const subStartDateDisplay = document.getElementById("sub-start-date-display");
  const subStartDateHidden = document.getElementById("sub-start-date");
  const subStartDateClear = document.getElementById("sub-start-date-clear");
  const subStartCalModal = document.getElementById("sub-start-cal-modal");
  const subStartCalPanel = document.getElementById("sub-start-cal-panel");
  const subStartCalMonth = document.getElementById("sub-start-cal-month");
  const subStartCalGrid = document.getElementById("sub-start-cal-grid");
  const subStartCalPrev = document.getElementById("sub-start-cal-prev");
  const subStartCalNext = document.getElementById("sub-start-cal-next");
  const subStartCalCancel = document.getElementById("sub-start-cal-cancel");
  const subStartCalToday = document.getElementById("sub-start-cal-today");
  const subEndDateDisplay = document.getElementById("sub-end-date-display");
  const subEndDateHidden = document.getElementById("sub-end-date");
  const subEndDateClear = document.getElementById("sub-end-date-clear");
  const subEndCalModal = document.getElementById("sub-end-cal-modal");
  const subEndCalPanel = document.getElementById("sub-end-cal-panel");
  const subEndCalMonth = document.getElementById("sub-end-cal-month");
  const subEndCalGrid = document.getElementById("sub-end-cal-grid");
  const subEndCalPrev = document.getElementById("sub-end-cal-prev");
  const subEndCalNext = document.getElementById("sub-end-cal-next");
  const subEndCalCancel = document.getElementById("sub-end-cal-cancel");
  const subEndCalToday = document.getElementById("sub-end-cal-today");

  const customerApiBase = `${window.APP_URL || ""}/controller/customer`;

  let modalMode = "create";
  let activeRow = null;
  let deleteRow = null;
  let dataTable = window.customersDataTable || null;
  let pendingWelcomeEmail = null;
  let welcomeEmailSent = false;

  function lockBody(lock) {
    document.body.style.overflow = lock ? "hidden" : "auto";
  }

  function escapeHtml(value) {
    return String(value ?? "").replace(/[&<>"']/g, function (character) {
      return {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
      }[character];
    });
  }

  function extractNumericCustomerId(value) {
    if (value === null || value === undefined) {
      return null;
    }

    const text = String(value).trim().replace(/^#C/i, "");
    const numericId = parseInt(text, 10);

    return Number.isNaN(numericId) ? null : numericId;
  }

  function extractNumericPackageId(value) {
    if (value === null || value === undefined) {
      return null;
    }

    const text = String(value).trim().replace(/^#P/i, "");
    const numericId = parseInt(text, 10);

    return Number.isNaN(numericId) ? null : numericId;
  }

  function formatCustomerId(id) {
    const numericId = extractNumericCustomerId(id);
    if (numericId === null) {
      return String(id ?? "");
    }

    return `#C${String(numericId).padStart(3, "0")}`;
  }

  function formatPackageId(id) {
    const numericId = extractNumericPackageId(id);
    if (numericId === null) {
      return String(id ?? "");
    }

    return `#P${String(numericId).padStart(3, "0")}`;
  }

  function getInitials(name) {
    const parts = String(name ?? "")
      .trim()
      .split(/\s+/)
      .filter(Boolean)
      .slice(0, 2);

    const initials = parts
      .map(function (part) {
        return part.charAt(0).toUpperCase();
      })
      .join("");

    return initials || "U";
  }

  function customerRoleLabel(role) {
    const value = String(role ?? "").trim();

    return value === ""
      ? "Customer"
      : value.replace(/\b\w/g, function (character) {
          return character.toUpperCase();
        });
  }

  function customerStatusLabel(status) {
    const value = String(status ?? "").trim();

    return value === ""
      ? "Active"
      : value.replace(/\b\w/g, function (character) {
          return character.toUpperCase();
        });
  }

  function customerRoleClasses(role) {
    const normalizedRole = String(role ?? "")
      .trim()
      .toLowerCase();

    if (normalizedRole === "super admin") {
      return "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400";
    }

    if (normalizedRole === "admin") {
      return "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400";
    }

    if (normalizedRole === "manager") {
      return "bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400";
    }

    if (normalizedRole === "customer") {
      return "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400";
    }

    return "bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300";
  }

  function customerAvatarClasses(role) {
    const normalizedRole = String(role ?? "")
      .trim()
      .toLowerCase();

    if (normalizedRole === "super admin") {
      return "bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400";
    }

    if (normalizedRole === "admin") {
      return "bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400";
    }

    if (normalizedRole === "manager") {
      return "bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400";
    }

    if (normalizedRole === "customer") {
      return "bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400";
    }

    return "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300";
  }

  function customerStatusClasses(status) {
    const normalizedStatus = String(status ?? "")
      .trim()
      .toLowerCase();

    if (normalizedStatus === "active") {
      return "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400";
    }

    if (normalizedStatus === "inactive") {
      return "bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400";
    }

    return "bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300";
  }

  function formatDateDisplay(value) {
    const rawValue = String(value ?? "").trim();
    if (!rawValue) {
      return "";
    }

    const normalizedValue = rawValue.replace(" ", "T");
    const date = new Date(normalizedValue);

    if (!Number.isNaN(date.getTime())) {
      return new Intl.DateTimeFormat("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
      }).format(date);
    }

    return rawValue;
  }

  function refreshIcons() {
    if (typeof lucide !== "undefined") {
      lucide.createIcons();
    }
  }

  function normalizeCustomerData(data) {
    const source = data || {};
    const customerId = extractNumericCustomerId(
      source.id ?? source.id_display ?? source.customer_id ?? null,
    );
    const packageId = extractNumericPackageId(
      source.package_id ?? source.packageId ?? source.package ?? null,
    );
    const createdAtRaw = String(
      source.created_at ?? source.createdAt ?? "",
    ).trim();
    const createdAtDisplay =
      String(
        source.created_at_display ?? source.createdAtDisplay ?? "",
      ).trim() || formatDateDisplay(createdAtRaw);

    return {
      id: customerId,
      idDisplay:
        String(source.id_display ?? "").trim() ||
        (customerId !== null ? formatCustomerId(customerId) : ""),
      name: String(source.name ?? "").trim(),
      email: String(source.email ?? "").trim(),
      password: String(source.password ?? "").trim(),
      userRole:
        String(source.user_role ?? source.userRole ?? "customer")
          .trim()
          .toLowerCase() || "customer",
      status:
        String(source.status ?? "active")
          .trim()
          .toLowerCase() || "active",
      packageId,
      packageName: String(
        source.package_name ?? source.packageName ?? "",
      ).trim(),
      address: String(source.address ?? "").trim(),
      createdAt: createdAtRaw,
      createdAtDisplay,
    };
  }

  function parseCustomerRow(row) {
    const rowElement = row instanceof HTMLElement ? row : null;
    const dataset = rowElement?.dataset || {};
    const firstCell = rowElement?.querySelector("td:nth-child(1)");
    const nameElement =
      firstCell?.querySelector('[data-role="name"]') ||
      firstCell?.querySelector("p:first-child");
    const idElement =
      firstCell?.querySelector('[data-role="id"]') ||
      firstCell?.querySelector("p:nth-child(2)");
    const emailElement =
      rowElement?.querySelector('[data-role="email"]') ||
      rowElement?.querySelector("td:nth-child(2)");
    const mobileElement =
      rowElement?.querySelector('[data-role="mobile"]') ||
      rowElement?.querySelector("td:nth-child(2)");
    const packageNameElement =
      rowElement?.querySelector('[data-role="package-name"]') ||
      rowElement?.querySelector("td:nth-child(3)");
    const roleElement =
      rowElement?.querySelector('[data-role="role-badge"]') ||
      rowElement?.querySelector("td:nth-child(4)");
    const statusElement =
      rowElement?.querySelector('[data-role="status-badge"]') ||
      rowElement?.querySelector("td:nth-child(5)");
    const createdElement =
      rowElement?.querySelector('[data-role="created"]') ||
      rowElement?.querySelector("td:nth-child(6)");
    const mobileEmail = firstCell?.querySelector('[data-role="mobile-email"]');
    const mobileMobile = firstCell?.querySelector(
      '[data-role="mobile-mobile"]',
    );
    const mobilePackageName = firstCell?.querySelector(
      '[data-role="mobile-package-name"]',
    );
    const mobilePackageId = firstCell?.querySelector(
      '[data-role="mobile-package-id"]',
    );
    const mobileRoleBadge = firstCell?.querySelector(
      '[data-role="mobile-role-badge"]',
    );
    const mobileAddress = firstCell?.querySelector(
      '[data-role="mobile-address"]',
    );
    const mobileCreated = firstCell?.querySelector(
      '[data-role="mobile-created"]',
    );

    const idValue =
      dataset.id || idElement?.textContent?.replace(/^ID:\s*/i, "") || "";
    const packageIdValue =
      dataset.packageId ||
      mobilePackageId?.textContent ||
      packageNameElement?.textContent ||
      "";

    return {
      row: rowElement,
      id: extractNumericCustomerId(idValue),
      idDisplay:
        dataset.idDisplay ||
        idElement?.textContent?.replace(/^ID:\s*/i, "") ||
        "",
      name: dataset.name || nameElement?.textContent?.trim() || "",
      email:
        dataset.email ||
        emailElement?.textContent?.trim() ||
        mobileEmail?.textContent?.trim() ||
        "",
      userRole: String(
        dataset.userRole ||
          mobileRoleBadge?.textContent ||
          roleElement?.textContent ||
          "customer",
      )
        .trim()
        .toLowerCase(),
      status: String(dataset.status || statusElement?.textContent || "active")
        .trim()
        .toLowerCase(),
      packageId: extractNumericPackageId(
        dataset.packageId || packageIdValue || "",
      ),
      packageName:
        dataset.packageName ||
        mobilePackageName?.textContent?.trim() ||
        packageNameElement?.textContent?.trim() ||
        "",
      address: dataset.address || mobileAddress?.textContent?.trim() || "",
      createdAtDisplay:
        dataset.createdAt ||
        createdElement?.textContent?.trim() ||
        mobileCreated?.textContent?.trim() ||
        "",
    };
  }

  function getCustomerRowElement(row) {
    if (row instanceof HTMLElement) {
      return row.matches("tr.user-row") ? row : row.closest("tr.user-row");
    }

    if (row === null || row === undefined) {
      return null;
    }

    const rowId = extractNumericCustomerId(
      typeof row === "object" ? row.id : row,
    );
    if (rowId === null) {
      return null;
    }

    return (
      customersTable?.querySelector(`tr.user-row[data-id="${rowId}"]`) || null
    );
  }

  function getNextCustomerId() {
    const rows = customersTable
      ? customersTable.querySelectorAll("tbody tr.user-row[data-id]")
      : [];
    let highestId = 0;

    rows.forEach(function (row) {
      const rowId = extractNumericCustomerId(row.getAttribute("data-id"));
      if (rowId !== null && rowId > highestId) {
        highestId = rowId;
      }
    });

    return highestId + 1;
  }

  function getPackageDisplayName(customer) {
    const packageName = String(customer.packageName ?? "").trim();
    if (packageName) {
      return packageName;
    }

    return customer.packageId !== null
      ? formatPackageId(customer.packageId)
      : "No Package";
  }

  function getPackageDisplayId(customer) {
    return customer.packageId !== null
      ? formatPackageId(customer.packageId)
      : "No package assigned";
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

  function setAvatar(element, initials, role, isLarge) {
    if (!element) {
      return;
    }

    const baseClasses = isLarge
      ? "w-16 h-16 rounded-2xl flex items-center justify-center font-bold text-2xl shadow-sm"
      : "w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm shrink-0";

    element.textContent = initials;
    element.className = `${baseClasses} ${customerAvatarClasses(role)}`;
  }

  function setRowDataAttributes(rowElement, customer) {
    rowElement.dataset.id = customer.id !== null ? String(customer.id) : "";
    rowElement.dataset.name = customer.name;
    rowElement.dataset.email = customer.email;
    rowElement.dataset.userRole = customer.userRole;
    rowElement.dataset.status = customer.status;
    rowElement.dataset.packageId =
      customer.packageId !== null ? String(customer.packageId) : "";
    rowElement.dataset.packageName =
      customer.packageName || getPackageDisplayName(customer);
    rowElement.dataset.address = customer.address;
    rowElement.dataset.createdAt = customer.createdAtDisplay;
  }

  function buildCustomerRowMarkup(data) {
    // Debug karein:
    console.log("Current Role:", currentUserRole);
    console.log("Is Admin?", currentUserRole == "admin");
    console.log(
      "Condition result:",
      currentUserRole == "admin" ? "SHOWING" : "HIDDEN",
    );
    const customer = normalizeCustomerData(data);
    const customerId = customer.id !== null ? customer.id : getNextCustomerId();
    const customerIdDisplay =
      customer.idDisplay || formatCustomerId(customerId);
    const avatarInitials = escapeHtml(getInitials(customer.name));
    const name = escapeHtml(customer.name || "Unnamed Customer");
    const email = escapeHtml(customer.email || "No email provided");
    const mobile = escapeHtml(customer.mobile || "No mobile number provided");
    const address = escapeHtml(customer.address || "—");
    const createdAt = escapeHtml(customer.createdAtDisplay || "—");
    const roleLabel = escapeHtml(customerRoleLabel(customer.userRole));
    const statusLabel = escapeHtml(customerStatusLabel(customer.status));
    const roleClasses = customerRoleClasses(customer.userRole);
    const statusClasses = customerStatusClasses(customer.status);
    const avatarClasses = customerAvatarClasses(customer.userRole);
    const packageNameDisplay = escapeHtml(getPackageDisplayName(customer));
    const packageIdDisplay = escapeHtml(getPackageDisplayId(customer));
    const packageIdAttribute =
      customer.packageId !== null ? escapeHtml(customer.packageId) : "";
    const customerIdAttribute = escapeHtml(customerId);

    return `
            <tr
                class="user-row"
                data-id="${customerIdAttribute}"
                data-name="${name}"
                data-email="${email}"
                data-mobile="${mobile}"
                data-user-role="${escapeHtml(customer.userRole)}"
                data-status="${escapeHtml(customer.status)}"
                data-package-id="${packageIdAttribute}"
                data-package-name="${packageNameDisplay}"
                data-address="${address}"
                data-created-at="${createdAt}"
            >
                <td class="align-top">
                    <div class="flex items-center gap-3">
                        <div data-role="avatar" class="w-10 h-10 rounded-full ${avatarClasses} flex items-center justify-center font-bold text-sm shrink-0">
                            ${avatarInitials}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p data-role="name" class="truncate font-medium text-gray-900 dark:text-gray-100">${name}</p>
                            <p data-role="id" class="text-xs text-gray-500 dark:text-gray-400">ID: ${escapeHtml(customerIdDisplay)}</p>
                        </div>
                        <div class="md:hidden flex items-center gap-2 shrink-0">
                            <span data-role="mobile-status-badge" class="px-2.5 py-1 ${statusClasses} rounded-full text-[11px] font-medium">
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
                                <span data-role="mobile-email" class="text-right text-gray-800 dark:text-gray-200 break-all">${email}</span>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                span class="text-gray-500 dark:text-gray-400">Mobile</span>
                                <span data-role="mobile-mobile" class="text-right text-gray-800 dark:text-gray-200">${mobile}</span>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <span class="text-gray-500 dark:text-gray-400">Package</span>
                                <span class="text-right text-gray-800 dark:text-gray-200">
                                    <span data-role="mobile-package-name" class="block">${packageNameDisplay}</span>
                                    <span data-role="mobile-package-id" class="block text-xs text-gray-500 dark:text-gray-400">${packageIdDisplay}</span>
                                </span>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <span class="text-gray-500 dark:text-gray-400">Address</span>
                                <span data-role="mobile-address" class="text-right text-gray-800 dark:text-gray-200 break-all">${address}</span>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <span class="text-gray-500 dark:text-gray-400">Created</span>
                                <span data-role="mobile-created" class="text-right text-gray-800 dark:text-gray-200">${createdAt}</span>
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
                          ${
                            currentUserRole === "admin"
                              ? `
                                <button type="button" id="admin-delete-btn" class="delete-user-btn ...">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>`
                              : ""
                          }
                        </div>
                    </div>
                </td>
                <td class="col-contact hidden md:table-cell text-gray-700 dark:text-gray-300">
                    <span data-role="email" class="block truncate">${email}</span>
                    <span data-role="mobile" class="block text-xs text-gray-500 dark:text-gray-400">${mobile}</span>
                </td>
                <td class="col-package hidden md:table-cell text-gray-700 dark:text-gray-300">
                    <span data-role="package-name" class="block font-medium">${packageNameDisplay}</span>
                    <span data-role="package-id" class="block text-xs text-gray-500 dark:text-gray-400">${packageIdDisplay}</span>
                </td>
                <td class="col-status hidden md:table-cell text-gray-700 dark:text-gray-300">
                    <span data-role="status-badge" class="inline-flex px-3 py-1 ${statusClasses} rounded-full text-xs font-medium">${statusLabel}</span>
                </td>
                <td class="col-created hidden md:table-cell text-gray-700 dark:text-gray-300">
                    <span data-role="created" class="block truncate">${createdAt}</span>
                </td>
                <td class="hidden md:table-cell whitespace-nowrap">
                    <div class="flex items-center gap-2 justify-center">
                        <button type="button" class="view-user-btn p-2 hover:bg-blue-100 dark:hover:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded transition-colors" title="View">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                        <button type="button" class="edit-user-btn p-2 hover:bg-amber-100 dark:hover:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded transition-colors" title="Edit">
                            <i data-lucide="edit" class="w-4 h-4"></i>
                        </button>
                        ${
                          currentUserRole === "admin"
                            ? `
                        <button type="button" class="delete-user-btn p-2 hover:bg-red-100 dark:hover:bg-red-900/30 text-red-600 dark:text-red-400 rounded transition-colors" title="Delete">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>`
                            : ""
                        }
                    </div>
                </td>
            </tr>
        `;
  }

  function refreshTable() {
    if (dataTable) {
      if (typeof dataTable.refresh === "function") {
        dataTable.refresh();
      } else if (typeof dataTable.update === "function") {
        dataTable.update();
      }
    }

    refreshIcons();
  }

  function addCustomerToTable(data) {
    const tbody = customersTable?.querySelector("tbody");
    if (!tbody) {
      return null;
    }

    tbody.insertAdjacentHTML("afterbegin", buildCustomerRowMarkup(data));
    refreshTable();
    const newId = extractNumericCustomerId(
      data?.id ?? data?.id_display ?? null,
    );
    return newId !== null ? getCustomerRowElement(newId) : null;
  }

  function replaceCustomerRow(row, data) {
    const rowElement = getCustomerRowElement(row);
    if (!rowElement) {
      return null;
    }

    const customer = normalizeCustomerData(data);
    rowElement.outerHTML = buildCustomerRowMarkup(customer);
    refreshTable();

    const updatedId =
      customer.id !== null
        ? customer.id
        : extractNumericCustomerId(data?.id ?? data?.id_display ?? null);
    return updatedId !== null ? getCustomerRowElement(updatedId) : null;
  }

  function removeCustomerRow(row) {
    const rowElement = getCustomerRowElement(row);
    if (!rowElement) {
      return;
    }

    rowElement.remove();
    refreshTable();
  }

  const subscriptionApiBase = `${window.APP_URL || ""}/controller/subscription`;
  let pendingSubscriptionCustomer = null;
  let pendingSubscriptionRow = null;
  let subscriptionStartPicker = null;
  let subscriptionEndPicker = null;

  function formatISODate(date) {
    if (!(date instanceof Date) || Number.isNaN(date.getTime())) return "";
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
  }

  function getAutoEndDate(startDateValue, days = 30) {
    const date = new Date(`${startDateValue}T00:00:00`);
    if (Number.isNaN(date.getTime())) return "";
    date.setDate(date.getDate() + days);
    return formatISODate(date);
  }

  function getPackageLabelFromSelect() {
    const option = subPackageId?.options?.[subPackageId.selectedIndex];
    return option ? option.textContent.trim().replace(/\s+/g, " ") : "";
  }

  function ensureSubscriptionCustomerOption(customer) {
    if (
      !subUserId ||
      !customer ||
      customer.id === null ||
      customer.id === undefined
    )
      return;

    const customerId = String(customer.id);
    const customerLabel = `${String(customer.name || "").toUpperCase()} (#U${String(customer.id).padStart(3, "0")})`;
    let option =
      Array.from(subUserId.options || []).find(
        (item) => String(item.value) === customerId,
      ) || null;

    if (!option) {
      option = new Option(customerLabel, customerId, true, true);
      subUserId.add(option);
    } else {
      option.text = customerLabel;
      option.selected = true;
    }

    subUserId.value = customerId;
    subUserId.dispatchEvent(new Event("change", { bubbles: true }));
  }

  function recalcSubscriptionPaid() {
    const selectedOption = subPackageId?.options?.[subPackageId.selectedIndex];
    const price = parseFloat(selectedOption?.dataset?.price ?? 0) || 0;
    const discount = parseFloat(subDiscount?.value ?? 0) || 0;
    const paid = Math.max(0, price - discount);
    if (subPaidAmount) {
      subPaidAmount.value = paid > 0 ? paid.toFixed(2) : "";
    }
    subPackagePrice.value = price > 0 ? `${price.toFixed(2)}` : "";
  }

  function openCustomerSubscriptionModal(
    customer,
    packageId = null,
    rowElement = null,
  ) {
    if (!subscriptionModal) return;

    pendingSubscriptionCustomer = customer || null;
    pendingSubscriptionRow = rowElement || null;

    if (subModalTitle) subModalTitle.textContent = "Add New Subscription";
    if (subSaveBtn) subSaveBtn.textContent = "Save Subscription";
    if (subUserId) {
      subUserId.disabled = true;
      ensureSubscriptionCustomerOption(customer);
    }
    if (subPackageId) {
      subPackageId.value = packageId
        ? String(packageId)
        : customer?.packageId !== null && customer?.packageId !== undefined
          ? String(customer.packageId)
          : "";
      subPackageId.disabled = false;
    }
    if (subDiscount) subDiscount.value = "0";
    if (subStatus) subStatus.value = "active";

    const startDate = formatISODate(new Date());
    const endDate = getAutoEndDate(startDate, 30);

    subscriptionStartPicker?.setValue(startDate);
    subscriptionEndPicker?.setValue(endDate);
    recalcSubscriptionPaid();

    subscriptionModal.classList.remove("hidden");
    lockBody(true);
    refreshIcons();

    requestAnimationFrame(function () {
      ensureSubscriptionCustomerOption(customer);
    });
  }

  function clearPendingWelcomeEmail() {
    pendingWelcomeEmail = null;
    welcomeEmailSent = false;
  }

  function sendPendingWelcomeEmailFallback() {
    if (
      !pendingWelcomeEmail ||
      welcomeEmailSent ||
      !pendingWelcomeEmail.password
    ) {
      clearPendingWelcomeEmail();
      return Promise.resolve();
    }

    return sendCustomerRequest(`${customerApiBase}/send-welcome-email.php`, {
      user_id: pendingWelcomeEmail.userId,
      welcome_password: pendingWelcomeEmail.password,
      package_id: pendingWelcomeEmail.packageId || null,
    })
      .then(function (result) {
        if (!result?.status) {
          console.error(
            "Welcome email fallback failed:",
            result?.message || result?.data?.email_error,
          );
        }
      })
      .catch(function (error) {
        console.error("Welcome email fallback error:", error);
      })
      .finally(function () {
        clearPendingWelcomeEmail();
      });
  }

  function closeCustomerSubscriptionModal() {
    if (!subscriptionModal) return;

    const shouldSendFallback = Boolean(
      pendingWelcomeEmail && !welcomeEmailSent,
    );

    subscriptionModal.classList.add("hidden");
    lockBody(false);
    pendingSubscriptionCustomer = null;
    pendingSubscriptionRow = null;

    if (shouldSendFallback) {
      sendPendingWelcomeEmailFallback();
    }
  }

  function sendSubscriptionRequest(url, payload) {
    return fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    }).then((response) => response.json());
  }

  function syncCustomerRowAfterSubscription(subscriptionData) {
    if (!pendingSubscriptionCustomer) return;

    const merged = {
      ...pendingSubscriptionCustomer,
      package_id: subscriptionData?.package_id ?? subPackageId?.value ?? null,
      package_name:
        subscriptionData?.package_name ?? getPackageLabelFromSelect(),
      status: "active",
    };

    if (pendingSubscriptionRow) {
      pendingSubscriptionRow = replaceCustomerRow(
        pendingSubscriptionRow,
        merged,
      );
    } else {
      replaceCustomerRow(pendingSubscriptionCustomer.id, merged);
    }
  }

  if (
    subscriptionModal &&
    subStartDateDisplay &&
    subStartDateHidden &&
    subStartCalModal &&
    subStartCalPanel &&
    subStartCalMonth &&
    subStartCalGrid &&
    subStartCalPrev &&
    subStartCalNext &&
    subStartCalCancel &&
    subStartCalToday &&
    subEndDateDisplay &&
    subEndDateHidden &&
    subEndCalModal &&
    subEndCalPanel &&
    subEndCalMonth &&
    subEndCalGrid &&
    subEndCalPrev &&
    subEndCalNext &&
    subEndCalCancel &&
    subEndCalToday
  ) {
    subscriptionStartPicker = new DatePicker({
      inputDisplayId: "sub-start-date-display",
      hiddenInputId: "sub-start-date",
      clearBtnId: "sub-start-date-clear",
      modalId: "sub-start-cal-modal",
      panelId: "sub-start-cal-panel",
      monthYearId: "sub-start-cal-month",
      daysGridId: "sub-start-cal-grid",
      prevBtnId: "sub-start-cal-prev",
      nextBtnId: "sub-start-cal-next",
      cancelBtnId: "sub-start-cal-cancel",
      todayBtnId: "sub-start-cal-today",
    });

    subscriptionEndPicker = new DatePicker({
      inputDisplayId: "sub-end-date-display",
      hiddenInputId: "sub-end-date",
      clearBtnId: "sub-end-date-clear",
      modalId: "sub-end-cal-modal",
      panelId: "sub-end-cal-panel",
      monthYearId: "sub-end-cal-month",
      daysGridId: "sub-end-cal-grid",
      prevBtnId: "sub-end-cal-prev",
      nextBtnId: "sub-end-cal-next",
      cancelBtnId: "sub-end-cal-cancel",
      todayBtnId: "sub-end-cal-today",
    });

    subPackageId?.addEventListener("change", recalcSubscriptionPaid);
    subDiscount?.addEventListener("input", recalcSubscriptionPaid);

    subStartDateHidden?.addEventListener("change", function () {
      const startValue = subscriptionStartPicker?.getValue();
      if (!startValue) {
        subscriptionEndPicker?.clear();
        return;
      }
      subscriptionEndPicker?.setValue(getAutoEndDate(startValue, 30));
    });
  }

  function clearForm() {
    userNameInput.value = "";
    userEmailInput.value = "";
    if (mobileInput) mobileInput.value = "";
    userPasswordInput.value = "";
    userStatusSelect.value = "active";
    userPackageSelect.value = "";
    userAddressInput.value = "";
    userPasswordInput.placeholder = "Enter password";
  }

  function setModalState(mode, row = null) {
    modalMode = mode;
    activeRow = row ? getCustomerRowElement(row) : null;

    if (mode === "edit" && activeRow) {
      const data = parseCustomerRow(activeRow);
      modalTitle.textContent = "Edit Customer";
      userNameInput.value = data.name;
      userEmailInput.value = data.email;
      if (mobileInput) mobileInput.value = data.mobile || "";
      userPasswordInput.value = "";
      userPasswordInput.placeholder = "Leave blank to keep current password";
      userStatusSelect.value = data.status || "active";
      userPackageSelect.value =
        data.packageId !== null ? String(data.packageId) : "";
      userAddressInput.value = data.address;
      saveUserBtn.textContent = "Update Customer";
      return;
    }

    modalTitle.textContent = "Add New Customer";
    clearForm();
    saveUserBtn.textContent = "Save Customer";
  }

  function openUserModal(mode = "create", row = null) {
    setModalState(mode, row);
    userModal.classList.remove("hidden");
    lockBody(true);
    refreshIcons();
  }

  function closeUserModal() {
    userModal.classList.add("hidden");
    lockBody(false);
  }

  function openViewModal(row) {
    const rowElement = getCustomerRowElement(row);
    if (!rowElement) {
      return;
    }

    activeRow = rowElement;
    const data = parseCustomerRow(rowElement);
    const roleLabel = customerRoleLabel(data.userRole);
    const statusLabel = customerStatusLabel(data.status);
    const packageName = getPackageDisplayName(data);
    const packageId = getPackageDisplayId(data);

    setAvatar(viewAvatar, getInitials(data.name), data.userRole, true);
    setText(viewName, data.name || "—");
    setText(viewId, data.id !== null ? formatCustomerId(data.id) : "—");
    setText(viewEmail, data.email || "—");
    setBadge(
      viewRole,
      roleLabel,
      customerRoleClasses(data.userRole),
      "inline-flex px-3 py-1 rounded-full text-xs font-medium",
    );
    setText(viewPackage, packageName);
    setText(viewPackageId, packageId);
    setBadge(
      viewStatus,
      statusLabel,
      customerStatusClasses(data.status),
      "inline-flex px-3 py-1 rounded-full text-xs font-medium",
    );
    setText(viewAddress, data.address || "—");
    setText(viewDate, data.createdAtDisplay || "—");

    viewModal.classList.remove("hidden");
    lockBody(true);
    refreshIcons();
  }

  function closeViewModal() {
    viewModal.classList.add("hidden");
    lockBody(false);
  }

  function openDeleteModal(row) {
    const rowElement = getCustomerRowElement(row);
    if (!rowElement) {
      return;
    }

    deleteRow = rowElement;
    const data = parseCustomerRow(rowElement);

    if (deleteMessage) {
      deleteMessage.textContent = `This action cannot be undone. Customer "${data.name || "Unnamed Customer"}" (${data.id !== null ? formatCustomerId(data.id) : "Unknown ID"}) will be permanently deleted.`;
    }

    deleteModal.classList.remove("hidden");
    lockBody(true);
    refreshIcons();
  }

  function closeDeleteModal() {
    deleteModal.classList.add("hidden");
    lockBody(false);
    deleteRow = null;
  }

  function getFormData() {
    return {
      id: activeRow
        ? extractNumericCustomerId(activeRow.getAttribute("data-id"))
        : null,
      name: userNameInput.value.trim(),
      email: userEmailInput.value.trim(),
      password: userPasswordInput.value,
      mobile: mobileInput ? mobileInput.value.trim() : "", // <-- Added
      user_role: "customer",
      status: userStatusSelect.value,
      package: userPackageSelect.value === "" ? null : userPackageSelect.value,
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
        },
      }).fire({
        icon: "error",
        title: "Please enter customer name",
      });
      return false;
    }

    // if (!data.email) {
    //     Swal.mixin({
    //         toast: true,
    //         position: "top-end",
    //         showConfirmButton: false,
    //         timer: 3000,
    //         timerProgressBar: true,
    //         didOpen: (toast) => {
    //             toast.onmouseenter = Swal.stopTimer;
    //             toast.onmouseleave = Swal.resumeTimer;
    //         }
    //     }).fire({
    //         icon: "error",
    //         title: "Please enter customer email"
    //     });
    //     return false;
    // }

    // if (!/^\S+@\S+\.\S+$/.test(data.email)) {
    //     Swal.mixin({
    //         toast: true,
    //         position: "top-end",
    //         showConfirmButton: false,
    //         timer: 3000,
    //         timerProgressBar: true,
    //         didOpen: (toast) => {
    //             toast.onmouseenter = Swal.stopTimer;
    //             toast.onmouseleave = Swal.resumeTimer;
    //         }
    //     }).fire({
    //         icon: "error",
    //         title: "Please enter a valid email address"
    //     });
    //     return false;
    // }
    const mobileValue = data.mobile || "";
    if (mobileValue !== "") {
      // Regex to match exactly "+92 3XX XXX XXXX" format
      const pakMobileRegex = /^\+92\s3\d{2}\s\d{3}\s\d{4}$/;
      if (!pakMobileRegex.test(mobileValue)) {
        Swal.mixin({
          toast: true,
          position: "top-end",
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
        }).fire({
          icon: "error",
          title:
            "Please enter a valid Pakistani mobile number (+92 3XX XXX XXXX)",
        });
        return false;
      }
    }
    if (!data.status) {
      Swal.mixin({
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
          toast.onmouseenter = Swal.stopTimer;
          toast.onmouseleave = Swal.resumeTimer;
        },
      }).fire({
        icon: "error",
        title: "Please select a status",
      });
      return false;
    }

    return true;
  }

  function sendCustomerRequest(url, payload) {
    return fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(payload),
    }).then(function (response) {
      return response.json();
    });
  }

  function handleCreateCustomer() {
    const data = getFormData();

    if (!validateFormData(data)) {
      return;
    }

    return runWithButtonLoading(saveUserBtn, "Creating...", function () {
      return sendCustomerRequest(`${customerApiBase}/create.php`, data)
        .then(function (result) {
          if (result?.status) {
            const addedUser = result.data?.user || result.data || data;
            const createdRow = addCustomerToTable(addedUser);
            const openSubscriptionFlow = function () {
              if (subscriptionModal) {
                openCustomerSubscriptionModal(
                  addedUser,
                  data.package,
                  createdRow,
                );
              }
            };

            const welcomePassword =
              result.data?.welcome_password ||
              result.data?.generated_password ||
              data.password ||
              "";
            pendingWelcomeEmail = {
              userId: addedUser.id,
              name: addedUser.name,
              email: addedUser.email,
              password: welcomePassword,
              packageId: data.package || addedUser.package_id || null,
            };
            welcomeEmailSent = false;

            const emailNote =
              "Login and package details will be emailed after the subscription is saved.";

            closeUserModal();
            setModalState("create");

            // If a password was auto-generated, show it in a modal
            if (result.data?.generated_password) {
              Swal.fire({
                title: "Customer Created!",
                html: `Customer has been created.<br><br><b>Email:</b> ${escapeHtml(addedUser.email)}<br><b>Password:</b> <code style="background:#f3f4f6;padding:2px 6px;border-radius:4px;color:#ef4444;font-family:monospace;">${escapeHtml(result.data.generated_password)}</code><br><br>${escapeHtml(emailNote)}`,
                icon: "success",
                confirmButtonText: "Okay",
              }).then(function () {
                openSubscriptionFlow();
              });
            } else {
              Swal.mixin({
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
                didOpen: (toast) => {
                  toast.onmouseenter = Swal.stopTimer;
                  toast.onmouseleave = Swal.resumeTimer;
                },
              }).fire({
                icon: "success",
                title: result.message || "Customer created successfully",
              });
              openSubscriptionFlow();
            }

            return;
          }

          Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
              toast.onmouseenter = Swal.stopTimer;
              toast.onmouseleave = Swal.resumeTimer;
            },
          }).fire({
            icon: "error",
            title: result?.message || "Something went wrong",
          });
        })
        .catch(function (error) {
          console.error("Create customer error:", error);
          Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
          }).fire({
            icon: "error",
            title: "Server error occurred",
          });
        });
    });
  }

  function handleUpdateCustomer() {
    const data = getFormData();

    if (!validateFormData(data)) {
      return;
    }

    if (data.id === null) {
      Swal.mixin({
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
          toast.onmouseenter = Swal.stopTimer;
          toast.onmouseleave = Swal.resumeTimer;
        },
      }).fire({
        icon: "error",
        title: "Customer ID is missing",
      });
      return;
    }

    // FIX HERE: Added button loading wrapper
    return runWithButtonLoading(saveUserBtn, "Updating...", function () {
      // FIX HERE: Added 'return' to pass promise to the wrapper
      return sendCustomerRequest(`${customerApiBase}/update.php`, data)
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
              },
            }).fire({
              icon: "success",
              title: "Customer updated successfully",
            });
            activeRow = replaceCustomerRow(activeRow, result.data || data);
            closeUserModal();
            setModalState("create");
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
              },
            }).fire({
              icon: "error",
              title: result?.message || "Something went wrong",
            });
          }
        })
        .catch(function (error) {
          console.error("Update customer error:", error);
          Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
              toast.onmouseenter = Swal.stopTimer;
              toast.onmouseleave = Swal.resumeTimer;
            },
          }).fire({
            icon: "error",
            title: "Server error occurred",
          });
        });
    });
  }

  function handleCreateSubscription() {
    if (!subscriptionModal) return;

    const userId = subUserId?.value || "";
    const packageId = subPackageId?.value || "";
    const packagePrice = parseFloat(subPackagePrice?.value);
    const discount = parseFloat(subDiscount?.value || "0") || 0;
    const startDate = subscriptionStartPicker?.getValue() || "";
    const endDate = subscriptionEndPicker?.getValue() || "";
    const status = subStatus?.value || "active";

    if (!userId) {
      Swal.mixin({
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
      }).fire({ icon: "error", title: "Please select a customer" });
      return;
    }

    if (!packageId) {
      Swal.mixin({
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
      }).fire({ icon: "error", title: "Please select a package" });
      return;
    }

    if (!startDate || !endDate) {
      Swal.mixin({
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
      }).fire({ icon: "error", title: "Please select subscription dates" });
      return;
    }

    const shouldSendWelcomeEmail = Boolean(
      pendingWelcomeEmail && pendingWelcomeEmail.password,
    );

    // FIX HERE: Wrapped inside runWithButtonLoading using subSaveBtn
    return runWithButtonLoading(subSaveBtn, "Saving...", function () {
      // FIX HERE: Added 'return' to pass promise to the wrapper
      return sendSubscriptionRequest(`${subscriptionApiBase}/create.php`, {
        user_id: userId,
        package_id: packageId,
        package_price: packagePrice,
        discount,
        start_date: startDate,
        end_date: endDate,
        status,
        send_welcome_email: shouldSendWelcomeEmail,
        welcome_password: shouldSendWelcomeEmail
          ? pendingWelcomeEmail.password
          : "",
      })
        .then(function (result) {
          if (result?.status) {
            const emailSent = result.data?.email_sent === true;
            welcomeEmailSent = emailSent || !shouldSendWelcomeEmail;

            Swal.mixin({
              toast: true,
              position: "top-end",
              showConfirmButton: false,
              timer: 3500,
              timerProgressBar: true,
            }).fire({
              icon: shouldSendWelcomeEmail
                ? emailSent
                  ? "success"
                  : "warning"
                : "success",
              title:
                result.message ||
                (emailSent
                  ? "Subscription created and welcome email sent"
                  : shouldSendWelcomeEmail
                    ? "Subscription created, welcome email failed"
                    : "Subscribed successfully"),
            });

            syncCustomerRowAfterSubscription(result.data || {});
            clearPendingWelcomeEmail();
            subscriptionModal.classList.add("hidden");
            lockBody(false);
            pendingSubscriptionCustomer = null;
            pendingSubscriptionRow = null;
          } else {
            Swal.mixin({
              toast: true,
              position: "top-end",
              showConfirmButton: false,
              timer: 3000,
              timerProgressBar: true,
            }).fire({
              icon: "error",
              title: result?.message || "Something went wrong",
            });
          }
        })
        .catch(function (error) {
          console.error("Create subscription error:", error);
          Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
          }).fire({
            icon: "error",
            title: "Server error occurred",
          });
        });
    });
  }

  function handleDeleteCustomer() {
    const rowElement = getCustomerRowElement(deleteRow);
    const rowData = parseCustomerRow(rowElement);
    const customerId = rowData.id;

    if (customerId === null) {
      Swal.mixin({
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
          toast.onmouseenter = Swal.stopTimer;
          toast.onmouseleave = Swal.resumeTimer;
        },
      }).fire({
        icon: "error",
        title: "Customer ID is missing",
      });
      return;
    }

    sendCustomerRequest(`${customerApiBase}/delete.php`, {
      id: customerId,
    })
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
            },
          }).fire({
            icon: "success",
            title: "Customer deleted successfully",
          });
          removeCustomerRow(deleteRow);
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
            },
          }).fire({
            icon: "error",
            title: result?.message || "Something went wrong",
          });
        }
      })
      .catch(function (error) {
        console.error("Delete customer error:", error);
        Swal.mixin({
          toast: true,
          position: "top-end",
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
          didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
          },
        }).fire({
          icon: "error",
          title: "Server error occurred",
        });
      });
  }

  addUserBtn?.addEventListener("click", function (e) {
    e.preventDefault();
    openUserModal("create");
  });

  closeModalBtn?.addEventListener("click", function (e) {
    e.preventDefault();
    closeUserModal();
  });

  cancelModalBtn?.addEventListener("click", function (e) {
    e.preventDefault();
    closeUserModal();
  });

  saveUserBtn?.addEventListener("click", function (e) {
    e.preventDefault();

    if (modalMode === "edit") {
      handleUpdateCustomer();
      return;
    }

    handleCreateCustomer();
  });

  subSaveBtn?.addEventListener("click", function (e) {
    e.preventDefault();
    handleCreateSubscription();
  });

  subCloseModalBtn?.addEventListener("click", function (e) {
    e.preventDefault();
    closeCustomerSubscriptionModal();
  });

  subCancelModalBtn?.addEventListener("click", function (e) {
    e.preventDefault();
    closeCustomerSubscriptionModal();
  });

  closeViewModalBtn?.addEventListener("click", function (e) {
    e.preventDefault();
    closeViewModal();
  });

  closeViewBtn?.addEventListener("click", function (e) {
    e.preventDefault();
    closeViewModal();
  });

  viewEditBtn?.addEventListener("click", function (e) {
    e.preventDefault();
    closeViewModal();

    if (activeRow) {
      openUserModal("edit", activeRow);
    }
  });

  cancelDeleteBtn?.addEventListener("click", function (e) {
    e.preventDefault();
    closeDeleteModal();
  });

  confirmDeleteBtn?.addEventListener("click", function (e) {
    e.preventDefault();
    handleDeleteCustomer();
  });

  customersTable?.addEventListener("click", function (e) {
    const mobileToggle = e.target.closest(".mobile-row-toggle");
    const viewButton = e.target.closest(".view-user-btn");
    const editButton = e.target.closest(".edit-user-btn");
    const deleteButton = e.target.closest(".delete-user-btn");

    if (mobileToggle) {
      const row = mobileToggle.closest("tr.user-row");
      const details = row?.querySelector(".mobile-row-details");
      const chevron = row?.querySelector(".mobile-row-chevron");
      const isOpen = details && !details.classList.contains("hidden");

      if (details) {
        details.classList.toggle("hidden", isOpen);
      }

      chevron?.classList.toggle("rotate-180", !isOpen);
      mobileToggle.setAttribute("aria-expanded", String(!isOpen));
      return;
    }

    if (viewButton) {
      const row = getCustomerRowElement(viewButton.closest("tr.user-row"));
      if (row) {
        openViewModal(row);
      }
      return;
    }

    if (editButton) {
      const row = getCustomerRowElement(editButton.closest("tr.user-row"));
      if (row) {
        openUserModal("edit", row);
      }
      return;
    }

    if (deleteButton) {
      const row = getCustomerRowElement(deleteButton.closest("tr.user-row"));
      if (row) {
        openDeleteModal(row);
      }
    }
  });

  document.addEventListener("click", function (e) {
    if (e.target.classList.contains("modal-backdrop")) {
      closeUserModal();
      closeViewModal();
      closeDeleteModal();
      closeCustomerSubscriptionModal();
    }
  });

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      if (!userModal.classList.contains("hidden")) {
        closeUserModal();
      }

      if (!viewModal.classList.contains("hidden")) {
        closeViewModal();
      }

      if (!deleteModal.classList.contains("hidden")) {
        closeDeleteModal();
      }

      if (
        subscriptionModal &&
        !subscriptionModal.classList.contains("hidden")
      ) {
        closeCustomerSubscriptionModal();
      }
    }
  });

  if (customersTable && typeof simpleDatatables !== "undefined") {
    dataTable = new simpleDatatables.DataTable("#customers-table", {
      searchable: true,
      fixedHeight: false,
      perPage: 10,
      perPageSelect: [5, 10, 20, 50],
      labels: {
        placeholder: "Search customers...",
        perPage: "entries per page",
        noRows: "No customers found",
        info: "Showing {start} to {end} of {rows} customers",
      },
    });

    dataTable.on("datatable.page", refreshIcons);
    dataTable.on("datatable.sort", refreshIcons);
    dataTable.on("datatable.search", refreshIcons);
    window.customersDataTable = dataTable;
  }

  refreshIcons();
  const mobileInput = document.getElementById("user-mobile");

  mobileInput?.addEventListener("input", function (e) {
    let value = e.target.value;

    // Agar input khali ho jaye toh format clear kar dein
    if (!value) return;

    // Sirf digits nikalen
    let digits = value.replace(/\D/g, "");

    // Agar start me 92 nahi hai aur user digit type kar raha hai, ya sirf '0' likha hai
    if (digits.startsWith("0")) {
      digits = "92" + digits.substring(1);
    } else if (!digits.startsWith("92") && digits.length > 0) {
      digits = "92" + digits;
    }

    // Format logical blocks: +92 3XX XXX XXXX
    let formatted = "";
    if (digits.length > 0) {
      formatted = "+" + digits.substring(0, 2); // +92
    }
    if (digits.length > 2) {
      formatted += " " + digits.substring(2, 5); // 3XX
    }
    if (digits.length > 5) {
      formatted += " " + digits.substring(5, 8); // XXX
    }
    if (digits.length > 8) {
      formatted += " " + digits.substring(8, 12); // XXXX
    }

    e.target.value = formatted;
  });

  // Backspace key handling taake formatting stuck na ho
  mobileInput?.addEventListener("keydown", function (e) {
    if (e.key === "Backspace") {
      let value = e.target.value;
      if (value === "+92 ") {
        e.target.value = "";
      }
    }
  });
});
