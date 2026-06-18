<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$isLoginPage = ($currentPage === 'login.php' || $currentPage === 'forgot-password.php');
?>
<!-- ========================================
     PAGE LOADER - Include this file in your pages
     Place it right after the <body> tag
     ======================================== -->

<?php if ($isLoginPage): ?>
<div id="page-loader">
    <div class="loader-container">
        <!-- Spinner -->
        <div class="loader-spinner">
            <div class="spinner-ring"></div>
            <div class="spinner-ring"></div>
            <div class="spinner-ring"></div>
            <div class="spinner-ring"></div>
            <div class="loader-icon">
                <svg class="w-10 h-10 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="cableGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#ffffff;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#e0f2fe;stop-opacity:1" />
                        </linearGradient>
                    </defs>
                    <!-- Cable connector -->
                    <rect x="4" y="6" width="16" height="12" rx="2" fill="url(#cableGradient)" opacity="0.95" />
                    <!-- Connection dots -->
                    <circle cx="8" cy="12" r="1.5" fill="#1e40af" />
                    <circle cx="12" cy="12" r="1.5" fill="#1e40af" />
                    <circle cx="16" cy="12" r="1.5" fill="#1e40af" />
                    <!-- Signal waves -->
                    <path d="M6 9 Q7 8 8 9" stroke="#1e40af" stroke-width="1" fill="none" stroke-linecap="round" />
                    <path d="M16 9 Q17 8 18 9" stroke="#1e40af" stroke-width="1" fill="none" stroke-linecap="round" />
                </svg>
            </div>
        </div>

        <!-- Text -->
        <div class="loader-text">
            <span class="loader-app-name">Pakistan Cable</span>
            <span class="dot">.</span>
            <span class="dot">.</span>
            <span class="dot">.</span>
        </div>

        <!-- Progress Bar -->
        <div class="loader-progress-wrapper">
            <div class="loader-progress-bar">
                <div class="progress-fill"></div>
            </div>
            <span class="loader-progress-text">0%</span>
        </div>
    </div>
</div>
<?php else: ?>
<div id="page-loader" class="skeleton-page-loader">
    <!-- Sidebar Skeleton -->
    <div class="skeleton-sidebar">
        <!-- Logo skeleton -->
        <div class="skeleton-logo-wrap">
            <div class="skeleton-logo-icon skeleton-shimmer-block"></div>
            <div class="skeleton-logo-text skeleton-shimmer-block"></div>
        </div>
        <!-- Menu list -->
        <div class="skeleton-menu">
            <div class="skeleton-menu-item skeleton-shimmer-block"></div>
            <div class="skeleton-menu-item skeleton-shimmer-block"></div>
            <div class="skeleton-menu-item skeleton-shimmer-block"></div>
            <div class="skeleton-menu-item skeleton-shimmer-block"></div>
            <div class="skeleton-menu-item skeleton-shimmer-block"></div>
            <div class="skeleton-menu-item skeleton-shimmer-block"></div>
        </div>
    </div>
    <!-- Main content skeleton -->
    <div class="skeleton-main">
        <!-- Header skeleton -->
        <div class="skeleton-header">
            <div class="skeleton-header-left">
                <div class="skeleton-menu-toggle skeleton-shimmer-block"></div>
            </div>
            <div class="skeleton-header-right">
                <div class="skeleton-avatar-ring">
                    <div class="skeleton-avatar skeleton-shimmer-block"></div>
                </div>
            </div>
        </div>
        <!-- Main content area -->
        <div class="skeleton-content">
            <!-- Title / Breadcrumbs -->
            <div class="skeleton-breadcrumbs skeleton-shimmer-block"></div>
            <div class="skeleton-title skeleton-shimmer-block"></div>

            <!-- Cards Grid -->
            <div class="skeleton-cards-grid">
                <div class="skeleton-card skeleton-shimmer-block"></div>
                <div class="skeleton-card skeleton-shimmer-block"></div>
                <div class="skeleton-card skeleton-shimmer-block"></div>
                <div class="skeleton-card skeleton-shimmer-block"></div>
            </div>

            <!-- Two Column Layout (like charts or table + chart) -->
            <div class="skeleton-grid-two">
                <div class="skeleton-large-block skeleton-shimmer-block"></div>
                <div class="skeleton-large-block skeleton-shimmer-block"></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>