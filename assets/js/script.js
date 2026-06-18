document.addEventListener("DOMContentLoaded", () => {
    // Initialize Lucide Icons
    lucide.createIcons();

    // 1. Initialize Perfect Scrollbar
    const psContainers = document.querySelectorAll('.ps-container, .custom-scrollbar');
    psContainers.forEach(container => {
        new PerfectScrollbar(container, {
            wheelPropagation: false,
            suppressScrollX: true,
        });
    });

    // 2. WaveLabel Logic
    const waveLabels = document.querySelectorAll('.wave-label-container');
    waveLabels.forEach(container => {
        const labelText = container.getAttribute('data-wave-label');
        if (!labelText) return;

        const letters = labelText.split('');
        
        const wrapper = document.createElement('div');
        wrapper.className = 'relative flex';

        // Original Letters
        letters.forEach((letter, index) => {
            const span = document.createElement('span');
            span.className = 'text-sm font-medium inline-block transition-all duration-300 group-hover:translate-y-[-100%] group-hover:opacity-0 orig-letter';
            span.style.transitionDelay = `${index * 40}ms`;
            span.textContent = letter;
            span.addEventListener('mouseenter', (e) => {
                e.currentTarget.style.animation = "wave 0.5s ease";
            });
            span.addEventListener('animationend', (e) => {
                e.currentTarget.style.animation = "none";
            });
            wrapper.appendChild(span);
        });

        // New Letters container
        const newLettersContainer = document.createElement('div');
        newLettersContainer.className = 'absolute top-0 left-0 flex';
        
        letters.forEach((letter, index) => {
            const span = document.createElement('span');
            span.className = 'text-sm font-medium text-blue-600 dark:text-blue-400 inline-block transition-all duration-300 translate-y-[100%] opacity-0 group-hover:translate-y-0 group-hover:opacity-100 new-letter';
            span.style.transitionDelay = `${index * 40 + 200}ms`;
            span.textContent = letter;
            span.addEventListener('mouseenter', (e) => {
                e.currentTarget.style.animation = "wave 0.5s ease";
            });
            span.addEventListener('animationend', (e) => {
                e.currentTarget.style.animation = "none";
            });
            newLettersContainer.appendChild(span);
        });

        wrapper.appendChild(newLettersContainer);
        container.appendChild(wrapper);
    });

    // 3. Theme Setup
    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');
    const themeText = document.getElementById('theme-text');
    const htmlElement = document.documentElement;

    const setDarkMode = (isDark) => {
        if (isDark) {
            htmlElement.classList.add('dark');
            if(themeIcon) themeIcon.setAttribute('data-lucide', 'sun');
            if(themeText) themeText.textContent = 'Light Mode';
            localStorage.setItem('theme', 'dark');
        } else {
            htmlElement.classList.remove('dark');
            if(themeIcon) themeIcon.setAttribute('data-lucide', 'moon');
            if(themeText) themeText.textContent = 'Dark Mode';
            localStorage.setItem('theme', 'light');
        }
        lucide.createIcons(); // Re-render icons
    };

    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
        setDarkMode(true);
    }

    if(themeToggle) {
        themeToggle.addEventListener('click', (event) => {
            const isDark = htmlElement.classList.contains('dark');
            const targetDark = !isDark;

            // Check if document.startViewTransition is supported
            if (!document.startViewTransition) {
                setDarkMode(targetDark);
                return;
            }

            // Always use page/viewport center for the ripple circle
            const x = window.innerWidth / 2;
            const y = window.innerHeight / 2;

            // Calculate distance from center to the furthest corner (which is any corner)
            const endRadius = Math.hypot(x, y);

            const transition = document.startViewTransition(() => {
                setDarkMode(targetDark);
            });

            transition.ready.then(() => {
                // Always expand the NEW view as a circle from center
                // This works correctly for BOTH light→dark and dark→light
                document.documentElement.animate(
                    {
                        clipPath: [
                            `circle(0px at ${x}px ${y}px)`,
                            `circle(${endRadius}px at ${x}px ${y}px)`
                        ],
                    },
                    {
                        duration: 500,
                        easing: 'ease-in-out',
                        pseudoElement: '::view-transition-new(root)',
                    }
                );
            });
        });
    }

    // 4. Sidebar Logic (Pinning, Hover, Mobile Toggle)
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content-wrapper');
    const pinBtn = document.getElementById('pin-sidebar-btn');
    const pinDot = document.getElementById('pin-dot');
    const brandText = document.getElementById('brand-text');
    const mainMenuLabel = document.getElementById('main-menu-label');
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const closeSidebarBtn = document.getElementById('close-sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    
    let isMobile = window.innerWidth < 1024;
    let sidebarPinned = localStorage.getItem('sidebarPinned') !== 'false';
    let sidebarOpenMobile = false; // For mobile only

    const updateSidebarWidths = () => {
        if (isMobile) {
            if (sidebarOpenMobile) {
                sidebar.classList.remove('-translate-x-full', 'w-0', 'w-[70px]', 'overflow-hidden');
                sidebar.classList.add('translate-x-0', 'w-[260px]', 'overflow-visible');
                sidebarOverlay.classList.remove('hidden');
                setTimeout(() => {
                    sidebarOverlay.classList.remove('opacity-0');
                    sidebarOverlay.classList.add('opacity-60');
                }, 10);
            } else {
                sidebar.classList.add('-translate-x-full', 'w-[260px]', 'overflow-hidden');
                sidebar.classList.remove('translate-x-0', 'w-[70px]', 'w-0', 'overflow-visible');
                sidebarOverlay.classList.remove('opacity-60');
                sidebarOverlay.classList.add('opacity-0');
                setTimeout(() => {
                    sidebarOverlay.classList.add('hidden');
                }, 300);
            }
            mainContent.className = 'flex-1 flex flex-col transition-all duration-300 w-full';
            brandText.classList.remove('hidden');
            brandText.classList.add('block');
            mainMenuLabel.style.display = 'block';
            setTimeout(() => { mainMenuLabel.style.opacity = '1'; }, 10);
            pinBtn.classList.add('hidden');
            pinBtn.classList.remove('flex');
            document.querySelectorAll('.wave-label-container, .submenu-icon-wrapper').forEach(el => el.style.display = 'block');
        } else {
            // Desktop
            sidebar.classList.remove('-translate-x-full', 'w-0', 'translate-x-0', 'overflow-hidden');
            sidebar.classList.add('overflow-visible');
            sidebarOverlay.classList.add('hidden');
            
            if (sidebarPinned) {
                sidebar.classList.remove('w-[70px]', 'shadow-2xl');
                sidebar.classList.add('w-[260px]', 'border-r');
                mainContent.className = 'flex-1 flex flex-col transition-all duration-300 w-full lg:ml-[260px] lg:w-[calc(100vw-260px)]';
                pinBtn.classList.remove('hidden');
                pinBtn.classList.add('flex');
                pinBtn.classList.add('border-blue-500');
                pinBtn.classList.remove('border-gray-300', 'dark:border-gray-600');
                pinDot.style.display = 'block';
                brandText.classList.remove('hidden');
                brandText.classList.add('block');
                mainMenuLabel.style.opacity = '1';
                document.querySelectorAll('.wave-label-container, .submenu-icon-wrapper').forEach(el => el.style.display = 'block');
            } else {
                sidebar.classList.remove('w-[260px]');
                sidebar.classList.add('w-[70px]', 'border-r');
                mainContent.className = 'flex-1 flex flex-col transition-all duration-300 w-full lg:ml-[70px] lg:w-[calc(100vw-70px)]';
                pinBtn.classList.add('hidden');
                pinBtn.classList.remove('flex');
                pinBtn.classList.remove('border-blue-500');
                pinBtn.classList.add('border-gray-300', 'dark:border-gray-600');
                pinDot.style.display = 'none';
                brandText.classList.add('hidden');
                brandText.classList.remove('block');
                mainMenuLabel.style.opacity = '0';
                mainMenuLabel.style.display = 'none';
                document.querySelectorAll('.wave-label-container, .submenu-icon-wrapper').forEach(el => el.style.display = 'none');
            }
        }
    };

    // Run initial width setup
    updateSidebarWidths();

    // Remove no-transition class to enable animations after initial layout is set
    setTimeout(() => {
        document.body.classList.remove('no-transition');
        document.body.classList.add('transition-colors', 'duration-300');
        mainContent.classList.add('transition-all', 'duration-300');
    }, 50);

    // Resize listener
    window.addEventListener('resize', () => {
        const newIsMobile = window.innerWidth < 1024;
        if (newIsMobile !== isMobile) {
            isMobile = newIsMobile;
            updateSidebarWidths();
        }
    });

    // Hover logic for Desktop unpinned sidebar
    let hoverTimeout;
    sidebar.addEventListener('mouseenter', () => {
        if (!isMobile && !sidebarPinned) {
            hoverTimeout = setTimeout(() => {
                sidebar.classList.remove('w-[70px]', 'border-r');
                sidebar.classList.add('w-[260px]', 'shadow-2xl', 'dark:shadow-gray-700');
                pinBtn.classList.remove('hidden');
                pinBtn.classList.add('flex');
                brandText.classList.remove('hidden');
                brandText.classList.add('block');
                mainMenuLabel.style.opacity = '1';
                document.querySelectorAll('.wave-label-container, .submenu-icon-wrapper').forEach(el => el.style.display = 'block');
            }, 150);
        }
    });

    sidebar.addEventListener('mouseleave', () => {
        if (!isMobile && !sidebarPinned) {
            clearTimeout(hoverTimeout);
            sidebar.classList.remove('w-[260px]', 'shadow-2xl', 'dark:shadow-gray-700');
            sidebar.classList.add('w-[70px]', 'border-r');
            pinBtn.classList.add('hidden');
            pinBtn.classList.remove('flex');
            brandText.classList.add('hidden');
            brandText.classList.remove('block');
            mainMenuLabel.style.opacity = '0';
            mainMenuLabel.style.display = 'none';
            document.querySelectorAll('.wave-label-container, .submenu-icon-wrapper').forEach(el => el.style.display = 'none');
            
            // Close any open submenus
            const openSubmenus = document.querySelectorAll('.submenu-content:not(.max-h-0)');
            openSubmenus.forEach(submenu => {
                const icon = submenu.previousElementSibling.querySelector('.submenu-icon-wrapper');
                submenu.classList.add('max-h-0', 'opacity-0');
                submenu.classList.remove('max-h-[500px]', 'opacity-100', 'py-2');
                if(icon) icon.classList.remove('rotate-90');
            });
        }
    });

    // Pin button click
    if (pinBtn) {
        pinBtn.addEventListener('click', () => {
            sidebarPinned = !sidebarPinned;
            localStorage.setItem('sidebarPinned', sidebarPinned);
            updateSidebarWidths();
            
            // Remove hover shadow if pinned while hovered
            if (sidebarPinned) {
                sidebar.classList.remove('shadow-2xl', 'dark:shadow-gray-700');
            }

            // Force charts to resize after sidebar transition completes
            setTimeout(() => {
                window.dispatchEvent(new Event('resize'));
            }, 350);
        });
    }

    // Mobile Toggle
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', () => {
            sidebarOpenMobile = true;
            updateSidebarWidths();
        });
    }
    
    if (closeSidebarBtn) {
        closeSidebarBtn.addEventListener('click', () => {
            sidebarOpenMobile = false;
            updateSidebarWidths();
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', () => {
            sidebarOpenMobile = false;
            updateSidebarWidths();
        });
    }

    // 5. Sidebar Submenu Toggling
    const submenuToggles = document.querySelectorAll('.submenu-toggle');
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
            // Expand sidebar if collapsed
            if (!isMobile && !sidebarPinned && sidebar.classList.contains('w-[70px]')) {
                return; 
            }

            const submenu = toggle.nextElementSibling;
            const icon = toggle.querySelector('.submenu-icon-wrapper');
            
            if (submenu.classList.contains('max-h-0')) {
                submenu.classList.remove('max-h-0', 'opacity-0');
                submenu.classList.add('max-h-[500px]', 'opacity-100', 'py-2');
                if(icon) icon.classList.add('rotate-90');
            } else {
                submenu.classList.add('max-h-0', 'opacity-0');
                submenu.classList.remove('max-h-[500px]', 'opacity-100', 'py-2');
                if(icon) icon.classList.remove('rotate-90');
            }
        });
    });

    // 6. Dropdowns
    const profileBtn = document.getElementById('profile-btn');
    const profileDropdown = document.getElementById('profile-dropdown');
    
    const notificationBtn = document.getElementById('notification-btn');
    const notificationDropdown = document.getElementById('notification-dropdown');

    const toggleDropdown = (dropdown) => {
        if (dropdown.classList.contains('hidden')) {
            dropdown.classList.remove('hidden');
            setTimeout(() => {
                dropdown.classList.remove('opacity-0', 'scale-95');
                dropdown.classList.add('opacity-100', 'scale-100');
            }, 10);
        } else {
            dropdown.classList.remove('opacity-100', 'scale-100');
            dropdown.classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                dropdown.classList.add('hidden');
            }, 200);
        }
    };

    if(profileBtn) {
        profileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if(!notificationDropdown.classList.contains('hidden')) toggleDropdown(notificationDropdown);
            toggleDropdown(profileDropdown);
        });
    }

    if(notificationBtn) {
        notificationBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if(!profileDropdown.classList.contains('hidden')) toggleDropdown(profileDropdown);
            toggleDropdown(notificationDropdown);
        });
    }

    document.addEventListener('click', (e) => {
        if (profileDropdown && !profileDropdown.classList.contains('hidden') && !profileDropdown.contains(e.target)) {
            toggleDropdown(profileDropdown);
        }
        if (notificationDropdown && !notificationDropdown.classList.contains('hidden') && !notificationDropdown.contains(e.target)) {
            toggleDropdown(notificationDropdown);
        }
    });
});


// ─── 7. Global Responsive Table Auto-Toggle ───────────────────────────────────
// Automatically shows/hides the `.mobile-row-toggle` chevron on any
// <table data-responsive="auto"> based on whether any <th> column is currently
// hidden (display:none).  Clicking the chevron inserts a full-width child row
// (tr.dt-details-row) that surfaces only the currently-hidden columns.
// Works across paginations via MutationObserver on <tbody>.
// ─────────────────────────────────────────────────────────────────────────────
(function () {
    'use strict';

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /** Returns 0-based indices of <th> elements whose computed display === 'none'. */
    function hiddenColIndices(table) {
        const ths = Array.from(table.querySelectorAll('thead tr:first-child th'));
        return ths.reduce(function (acc, th, i) {
            if (window.getComputedStyle(th).display === 'none') acc.push(i);
            return acc;
        }, []);
    }

    /** Show or hide every toggle button in the table depending on whether
     *  at least one column is hidden. */
    function syncToggleVisibility(table) {
        const hidden = hiddenColIndices(table);
        const show   = hidden.length > 0;
        table.querySelectorAll('tbody tr:not(.dt-details-row) .mobile-row-toggle').forEach(function (btn) {
            btn.style.display = show ? 'inline-flex' : 'none';
        });
    }

    /** Build the HTML string for the details child row. Returns null if there
     *  are no hidden, non-empty columns to show. */
    function buildDetailsRowHtml(dataRow, table) {
        const indices = hiddenColIndices(table);
        if (indices.length === 0) return null;

        const ths    = Array.from(table.querySelectorAll('thead tr:first-child th'));
        const tds    = Array.from(dataRow.querySelectorAll('td'));
        const colCount = ths.length;

        var items = '';
        indices.forEach(function (i) {
            var th = ths[i];
            var td = tds[i];
            if (!th || !td) return;
            var label = th.textContent.trim();
            if (!label) return;                          // skip un-labelled cols (Actions, etc.)
            var value = td.innerHTML.trim();
            if (!value) return;
            items += '<div class="flex flex-col gap-1.5">'
                   +   '<span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">' + label + '</span>'
                   +   '<div class="text-sm text-gray-800 dark:text-gray-200">' + value + '</div>'
                   + '</div>';
        });

        if (!items) return null;

        return '<tr class="dt-details-row bg-slate-50/50 dark:bg-slate-900/40 border-b border-gray-200 dark:border-gray-700 animate-fade-in">'
             +   '<td colspan="' + colCount + '" class="p-4">'
             +     '<div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">'
             +       items
             +     '</div>'
             +   '</td>'
             + '</tr>';
    }

    // ------------------------------------------------------------------
    // Per-table initialisation
    // ------------------------------------------------------------------

    function initTable(table) {
        // 1. Initial visibility pass
        syncToggleVisibility(table);

        // 2. Re-sync whenever simple-datatables rebuilds the tbody
        var tbody = table.querySelector('tbody');
        if (tbody) {
            new MutationObserver(function () {
                syncToggleVisibility(table);
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }).observe(tbody, { childList: true });
        }

        // 3. Click delegation for .mobile-row-toggle
        table.addEventListener('click', function (e) {
            var toggle = e.target.closest('.mobile-row-toggle');
            if (!toggle) return;

            var row = toggle.closest('tr');
            if (!row || row.classList.contains('dt-details-row')) return;

            // Skip if this row already uses the legacy inline mobile-row-details
            if (row.querySelector('.mobile-row-details')) return;

            var chevron = toggle.querySelector('.mobile-row-chevron');
            var next    = row.nextElementSibling;
            var isOpen  = next && next.classList.contains('dt-details-row');

            if (isOpen) {
                // Close
                next.remove();
                if (chevron) chevron.classList.remove('rotate-180');
                toggle.setAttribute('aria-expanded', 'false');
            } else {
                // Close any other open details rows in this table first
                table.querySelectorAll('tr.dt-details-row').forEach(function (r) { r.remove(); });
                table.querySelectorAll('.mobile-row-toggle').forEach(function (b) {
                    b.setAttribute('aria-expanded', 'false');
                    var ch = b.querySelector('.mobile-row-chevron');
                    if (ch) ch.classList.remove('rotate-180');
                });

                // Open
                var html = buildDetailsRowHtml(row, table);
                if (html) {
                    row.insertAdjacentHTML('afterend', html);
                    if (chevron) chevron.classList.add('rotate-180');
                    toggle.setAttribute('aria-expanded', 'true');
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            }
        });
    }

    // ------------------------------------------------------------------
    // Bootstrap
    // ------------------------------------------------------------------

    function initAll() {
        document.querySelectorAll('table[data-responsive="auto"]').forEach(initTable);
    }

    // Re-sync toggle visibility on resize (debounced)
    var resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            document.querySelectorAll('table[data-responsive="auto"]').forEach(syncToggleVisibility);
        }, 120);
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
}());
