/**
 * ========================================
 * PAGE LOADER - Reusable Component
 * ========================================
 * 
 * Usage:
 * 1. Include this file in your HTML
 * 2. Add <div id="page-loader">...</div> in your HTML
 * 3. Loader will auto-hide when page is fully loaded
 * 
 * Customization:
 * - Change appName variable below
 * - Modify progress update intervals
 * ========================================
 */

(function() {
    'use strict';

    // ========================================
    // CONFIGURATION
    // ========================================

    const CONFIG = {
        // App name to display
        appName: 'Pakistan Cable',
        
        // Minimum display time (ms) - prevents loader from hiding too quickly
        minDisplayTime: 800,
        
        // Maximum display time (ms) - forces hide after this time
        maxDisplayTime: 8000,
        
        // Progress bar update interval (ms)
        progressInterval: 80,
        
        // Enable/disable progress bar
        showProgress: true,
        
        // Enable/disable console logging (for debugging)
        debug: false
    };

    // ========================================
    // STATE
    // ========================================

    let startTime = Date.now();
    let isHidden = false;
    let progressInterval = null;
    let progressValue = 0;
    let forceHideTimeout = null;

    // ========================================
    // DOM REFERENCES
    // ========================================

    const loader = document.getElementById('page-loader');
    const progressFill = document.querySelector('.progress-fill');
    const progressText = document.querySelector('.loader-progress-text');

    // ========================================
    // LOGGER
    // ========================================

    function log(message, type = 'info') {
        if (!CONFIG.debug) return;
        
        const prefix = '[PageLoader]';
        if (type === 'info') console.log(prefix, message);
        else if (type === 'warn') console.warn(prefix, message);
        else if (type === 'error') console.error(prefix, message);
    }

    // ========================================
    // UPDATE PROGRESS
    // ========================================

    function updateProgress() {
        if (isHidden) return;

        // Simulate progress - slows down as it approaches 100%
        const increment = Math.max(1, (100 - progressValue) * 0.08);
        progressValue = Math.min(95, progressValue + increment);

        if (progressFill && CONFIG.showProgress) {
            progressFill.style.width = progressValue + '%';
        }

        if (progressText && CONFIG.showProgress) {
            progressText.textContent = Math.round(progressValue) + '%';
        }

        log(`Progress: ${Math.round(progressValue)}%`);
    }

    // ========================================
    // HIDE LOADER
    // ========================================

    function hideLoader() {
        if (isHidden) return;

        // Ensure minimum display time
        const elapsed = Date.now() - startTime;
        const remaining = CONFIG.minDisplayTime - elapsed;

        if (remaining > 0) {
            log(`Waiting ${remaining}ms for minimum display time`);
            setTimeout(hideLoader, remaining);
            return;
        }

        log('Hiding loader...');
        isHidden = true;

        // Clear intervals and timeouts
        if (progressInterval) {
            clearInterval(progressInterval);
            progressInterval = null;
        }

        if (forceHideTimeout) {
            clearTimeout(forceHideTimeout);
            forceHideTimeout = null;
        }

        // Set progress to 100% before hiding
        if (progressFill && CONFIG.showProgress) {
            progressFill.style.width = '100%';
        }
        if (progressText && CONFIG.showProgress) {
            progressText.textContent = '100%';
        }

        // Add hidden class with delay for smooth transition
        setTimeout(() => {
            if (loader) {
                loader.classList.add('loader-hidden');
                log('Loader hidden successfully');
            }
        }, 200);
    }

    // ========================================
    // FORCE HIDE (fallback)
    // ========================================

    function forceHideLoader() {
        log('Force hiding loader (timeout reached)', 'warn');
        if (!isHidden) {
            hideLoader();
        }
    }

    // ========================================
    // INITIALIZE LOADER
    // ========================================

    function initLoader() {
        log('Initializing page loader...');

        // Set app name in loader
        const appNameElements = document.querySelectorAll('.loader-app-name');
        appNameElements.forEach(el => {
            el.textContent = CONFIG.appName;
        });

        // Start progress
        if (CONFIG.showProgress) {
            progressValue = 0;
            if (progressFill) {
                progressFill.style.width = '0%';
            }
            if (progressText) {
                progressText.textContent = '0%';
            }

            progressInterval = setInterval(updateProgress, CONFIG.progressInterval);
        }

        // Set max display timeout (safety net)
        forceHideTimeout = setTimeout(forceHideLoader, CONFIG.maxDisplayTime);

        // Listen for page load events
        if (document.readyState === 'complete') {
            log('Document already loaded');
            setTimeout(hideLoader, 300);
        } else {
            window.addEventListener('load', function onLoad() {
                log('Window load event fired');
                setTimeout(hideLoader, 300);
                window.removeEventListener('load', onLoad);
            });
        }

        // Also listen for DOMContentLoaded as fallback
        document.addEventListener('DOMContentLoaded', function() {
            log('DOMContentLoaded event fired');
            // If window hasn't loaded yet, this ensures loader shows at least
        });

        log('Loader initialized successfully');
    }

    // ========================================
    // START
    // ========================================

    // Start when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLoader);
    } else {
        initLoader();
    }

    // ========================================
    // EXPOSE PUBLIC API (optional)
    // ========================================

    window.PageLoader = {
        hide: hideLoader,
        show: function() {
            if (loader) {
                loader.classList.remove('loader-hidden');
                isHidden = false;
                startTime = Date.now();
                log('Loader shown manually');
            }
        },
        updateProgress: function(value) {
            if (value >= 0 && value <= 100) {
                progressValue = value;
                if (progressFill) {
                    progressFill.style.width = value + '%';
                }
                if (progressText) {
                    progressText.textContent = Math.round(value) + '%';
                }
            }
        },
        config: CONFIG
    };

    log('PageLoader API exposed');

})();