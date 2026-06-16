/**
 * Real-time stats listener using Server-Sent Events (SSE)
 * Animates updates, handles real-time data binding, and triggers SweetAlert2 toasts.
 */

document.addEventListener('DOMContentLoaded', () => {
    let lastVersion = 0;
    let eventSource = null;

    // Helper to parse stats string (e.g. "Rs. 24,000" -> {prefix: "Rs. ", value: 24000, suffix: ""})
    function parseStatString(str) {
        str = String(str).trim();
        const cleanStr = str.replace(/,/g, '');
        // Match prefix, numeric value (including negative), and suffix
        const match = cleanStr.match(/^([^\d-]*)(-?\d+)([^\d]*)$/);
        if (match) {
            return {
                prefix: match[1],
                value: parseInt(match[2], 10),
                suffix: match[3]
            };
        }
        return { prefix: '', value: parseInt(cleanStr, 10) || 0, suffix: '' };
    }

    // Helper to animate count changes
    function animateValue(element, endString, duration = 800) {
        if (!element) return;
        
        const currentText = element.textContent.trim();
        const startParsed = parseStatString(currentText);
        const endParsed = parseStatString(endString);
        
        const start = startParsed.value;
        const end = endParsed.value;
        const prefix = endParsed.prefix || startParsed.prefix || '';
        const suffix = endParsed.suffix || startParsed.suffix || '';
        
        if (start === end) {
            element.textContent = endString;
            return;
        }

        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const currentVal = Math.floor(progress * (end - start) + start);
            
            // Format number with commas
            element.textContent = prefix + currentVal.toLocaleString() + suffix;
            
            if (progress < 1) {
                window.requestAnimationFrame(step);
            } else {
                element.textContent = endString;
            }
        };
        window.requestAnimationFrame(step);
    }

    // Micro-animation for stats card update
    function triggerCardFlash(element) {
        const card = element.closest('a, .group');
        if (!card) return;

        // Apply dynamic premium ring and scale classes
        card.classList.add('ring-2', 'ring-blue-500', 'shadow-xl', 'scale-[1.02]');
        
        setTimeout(() => {
            card.classList.remove('ring-2', 'ring-blue-500', 'shadow-xl', 'scale-[1.02]');
        }, 1200);
    }

    // Fetch and update stats
    function fetchAndUpdateStats() {
        // Build base URL helper (handles subdirectory installations)
        const baseUrl = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
        const statsUrl = `${baseUrl}/controller/dashboard/get_stats.php`;

        fetch(statsUrl)
            .then(res => {
                if (!res.ok) throw new Error('Network response not ok');
                return res.json();
            })
            .then(data => {
                if (!data.success) return;

                let updatedAny = false;

                if (data.role !== 'customer') {
                    // ---------------------------------------------
                    // Admin / Manager View updates
                    // ---------------------------------------------
                    const mapping = {
                        'stat-active-customers': data.stats.active_customers,
                        'stat-inactive-customers': data.stats.inactive_customers,
                        'stat-total-earnings': data.stats.total_earnings,
                        'stat-pending-payments': data.stats.pending_payments
                    };

                    Object.entries(mapping).forEach(([id, val]) => {
                        const el = document.getElementById(id);
                        if (el) {
                            const currentValStr = String(val);
                            if (el.textContent.trim() !== currentValStr) {
                                animateValue(el, currentValStr);
                                triggerCardFlash(el);
                                updatedAny = true;
                            }
                        }
                    });

                    // Update comparison elements directly
                    const activePctEl = document.getElementById('stat-active-pct');
                    if (activePctEl && data.stats.active_percentage) {
                        activePctEl.textContent = data.stats.active_percentage;
                    }

                    const activeCompEl = document.getElementById('stat-active-comparison');
                    if (activeCompEl && data.stats.active_last_month_msg) {
                        activeCompEl.textContent = data.stats.active_last_month_msg;
                    }

                    // Update Alerts Table
                    const alertsTable = document.getElementById('subscription-alerts-table');
                    if (alertsTable && data.alerts_html) {
                        const alertsTableBody = alertsTable.querySelector('tbody');
                        if (alertsTableBody) {
                            // Check if alerts table content actually changed before replacing
                            const normalizedCurrent = alertsTableBody.innerHTML.replace(/\s+/g, '');
                            const normalizedNew = data.alerts_html.replace(/\s+/g, '');
                            
                            if (normalizedCurrent !== normalizedNew) {
                                if (window.subscriptionAlertsDataTable) {
                                    window.subscriptionAlertsDataTable.destroy();
                                }
                                
                                // Re-query the table from the document AFTER destroy() has replaced the DOM element!
                                const freshTable = document.getElementById('subscription-alerts-table');
                                if (freshTable) {
                                    const freshTableBody = freshTable.querySelector('tbody');
                                    if (freshTableBody) {
                                        freshTableBody.innerHTML = data.alerts_html;
                                    }
                                }
                                
                                // Re-initialize Simple Datatable if constructor exists
                                if (typeof simpleDatatables !== 'undefined' && simpleDatatables.DataTable) {
                                    window.subscriptionAlertsDataTable = new simpleDatatables.DataTable('#subscription-alerts-table', {
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
                                }
                                
                                // Re-initialize Lucide Icons for table buttons
                                if (typeof lucide !== 'undefined') {
                                    lucide.createIcons();
                                }
                                updatedAny = true;
                            }
                        }
                    }
                } else {
                    // ---------------------------------------------
                    // Customer View updates
                    // ---------------------------------------------
                    const mapping = {
                        'cust-stat-total': data.stats.total_subscriptions,
                        'cust-stat-active': data.stats.active_subscriptions,
                        'cust-stat-expired': data.stats.expired_subscriptions,
                        'cust-stat-spent': data.stats.total_spent
                    };

                    Object.entries(mapping).forEach(([id, val]) => {
                        const el = document.getElementById(id);
                        if (el) {
                            const currentValStr = String(val);
                            if (el.textContent.trim() !== currentValStr) {
                                animateValue(el, currentValStr);
                                triggerCardFlash(el);
                                updatedAny = true;
                            }
                        }
                    });

                    // Update Customer Active Package Card HTML
                    const activePkgContainer = document.getElementById('cust-active-package-container');
                    if (activePkgContainer && typeof data.active_package_html !== 'undefined') {
                        const normalizedCurrent = activePkgContainer.innerHTML.replace(/\s+/g, '');
                        const normalizedNew = data.active_package_html.replace(/\s+/g, '');
                        
                        if (normalizedCurrent !== normalizedNew) {
                            activePkgContainer.innerHTML = data.active_package_html;
                            
                            if (typeof lucide !== 'undefined') {
                                lucide.createIcons();
                            }
                            updatedAny = true;
                        }
                    }
                }

                // Show SweetAlert2 Toast notification if something was updated
                if (updatedAny && typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Stats updated',
                        text: 'Dashboard has been updated in real-time.',
                        showConfirmButton: false,
                        timer: 3500,
                        timerProgressBar: true,
                        background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                        color: document.documentElement.classList.contains('dark') ? '#f3f4f6' : '#1f2937'
                    });
                }
            })
            .catch(err => {
                console.error('Error fetching real-time stats:', err);
            });
    }

    // Connect to SSE Endpoint
    function connectSSE() {
        const baseUrl = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
        const sseUrl = `${baseUrl}/sse.php?v=${lastVersion}`;

        eventSource = new EventSource(sseUrl);

        // Listen for open confirmation
        eventSource.addEventListener('open', (e) => {
            const data = JSON.parse(e.data);
            if (data.version) {
                lastVersion = data.version;
            }
        });

        // Listen for database changes
        eventSource.addEventListener('update', (e) => {
            const data = JSON.parse(e.data);
            if (data.version && data.version > lastVersion) {
                lastVersion = data.version;
                fetchAndUpdateStats();
            }
        });

        // Error handling & auto reconnection
        eventSource.onerror = (err) => {
            console.warn('SSE connection failed. Reconnecting in 5s...', err);
            eventSource.close();
            setTimeout(connectSSE, 5000);
        };
    }

    // Initialize Connection
    connectSSE();
});
