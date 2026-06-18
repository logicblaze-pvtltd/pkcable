<!-- ========================================
     PAGE LOADER - Include this file in your pages
     Place it right after the <body> tag
     ======================================== -->

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