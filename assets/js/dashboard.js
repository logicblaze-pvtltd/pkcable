document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('subscription-alerts-table');
    if (!table || typeof simpleDatatables === 'undefined') return;

    const dataTable = new simpleDatatables.DataTable('#subscription-alerts-table', {
        searchable: true,
        fixedHeight: false,
        perPage: 10,
        perPageSelect: [5, 10, 20, 50],
        labels: {
            placeholder: 'Search subscriptions...',
            perPage: 'entries per page',
            noRows: 'No subscription alerts found',
            info: 'Showing {start} to {end} of {rows} alerts',
        },
    });

    window.subscriptionAlertsDataTable = dataTable;
    window.refreshSubscriptionAlertsTable = function () {
        if (typeof dataTable.refresh === 'function') {
            dataTable.refresh();
        } else if (typeof dataTable.update === 'function') {
            dataTable.update();
        }

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    };

    window.removeSubscriptionAlertRow = function (row) {
        const rowEl = row instanceof HTMLElement ? row : row?.closest?.('tr');
        if (!rowEl) return false;

        rowEl.remove();
        if (typeof dataTable.refresh === 'function') {
            dataTable.refresh();
        } else if (typeof dataTable.update === 'function') {
            dataTable.update();
        }

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        return true;
    };

    if (typeof lucide !== 'undefined') lucide.createIcons();
});
