<?php
require_once './include/connection.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="./assets/favicon_io/favicon.ico">
    <?php $appName = get_env_value('APP_NAME') ?: 'Pakistan Cable'; ?>
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> - Sign In</title>
    <?php include './include/headerLinks.php' ?>
    <link rel="stylesheet" href="./assets/css/login.css">
    <link rel="stylesheet" href="./assets/css/loader.css">
</head>

<body class="bg-gray-900 dark:bg-gray-900 h-screen overflow-hidden transition-colors duration-500">
    <!-- ======================================== -->
    <!-- PAGE LOADER - Include right after body -->
    <!-- ======================================== -->
    <?php include "./include/loader.php"; ?>
    <!-- Parallax Background -->
    <div id="parallax-bg" class="fixed inset-0 w-full h-full z-0 transition-colors duration-500 ease-in-out bg-[#f3f4f4] dark:bg-[#030712]">
        <!-- Gradient overlay -->
        <div id="bg-overlay" class="absolute inset-0 opacity-40 mode-transition" style="background: radial-gradient(circle at 70% 30%, rgba(200, 200, 220, 0.5), transparent 70%);"></div>

        <!-- Layer 1 -->
        <div class="paralexx-layer absolute inset-0" data-speed="-0.04">
            <div id="blob-1" class="absolute top-[10%] left-[5%] w-96 h-96 rounded-full blur-3xl animate-float-slow mode-transition" style="background: radial-gradient(circle at 40% 60%, rgba(200, 140, 240, 0.55), rgba(130, 180, 255, 0.3));"></div>
            <div id="blob-2" class="absolute bottom-[15%] right-[2%] w-[30rem] h-[30rem] rounded-full blur-3xl animate-float-medium mode-transition" style="background: radial-gradient(circle at 70% 30%, rgba(255, 170, 140, 0.6), rgba(255, 120, 180, 0.3));"></div>
        </div>

        <!-- Layer 2 -->
        <div class="paralexx-layer absolute inset-0" data-speed="0.08">
            <div id="blob-3" class="absolute top-[55%] left-[20%] w-80 h-80 rounded-full blur-2xl animate-float-medium mode-transition" style="background: radial-gradient(circle at 40% 60%, rgba(200, 140, 240, 0.55), rgba(130, 180, 255, 0.3));"></div>
            <div id="blob-4" class="absolute top-[20%] right-[15%] w-72 h-72 rounded-full blur-2xl animate-float-fast mode-transition" style="background: radial-gradient(circle at 30% 60%, rgba(150, 210, 255, 0.6), rgba(100, 140, 230, 0.3));"></div>
        </div>

        <!-- Layer 3 -->
        <div class="paralexx-layer absolute inset-0" data-speed="0.18">
            <div id="blob-5" class="absolute bottom-[35%] left-[30%] w-44 h-44 rounded-full blur-xl animate-float-fast mode-transition" style="background: radial-gradient(circle at 40% 60%, rgba(200, 140, 240, 0.55), rgba(130, 180, 255, 0.3));"></div>
            <div id="blob-6" class="absolute top-[40%] left-[70%] w-32 h-32 rounded-full blur-xl animate-float-slow mode-transition" style="background: radial-gradient(circle at 40% 60%, rgba(200, 140, 240, 0.55), rgba(130, 180, 255, 0.3));"></div>
            <div id="bg-pattern" class="absolute inset-0 opacity-30 transition-all duration-500" style="background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgdmlld0JveD0iMCAwIDQwIDQwIj48Y2lyY2xlIGN4PSIyMCIgY3k9IjIwIiByPSIxLjUiIGZpbGw9ImJsYWNrIiBmaWxsLW9wYWNpdHk9IjAuMTUiLz48Y2lyY2xlIGN4PSI1IiBjeT0iMzAiIHI9IjEiIGZpbGw9ImJsYWNrIiBmaWxsLW9wYWNpdHk9IjAuMTUiLz48Y2lyY2xlIGN4PSIzNSIgY3k9IjEwIiByPSIxIiBmaWxsPSJibGFjayIgZmlsbC1vcGFjaXR5PSIwLjEiLz48L3N2Zz4=');"></div>
        </div>
    </div>

    <div class="flex w-full h-screen relative z-10 shadow-2xl overflow-hidden">

        <!-- Left Side: SVG Illustration -->
        <div class="hidden lg:flex flex-grow h-screen items-start justify-center relative opacity-95 pt-8">
            <div class="svg-float w-1/2 h-auto">
                <svg viewBox="0 0 400 500" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid meet">
                    <defs>
                        <linearGradient id="globeGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#3B82F6;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#1E40AF;stop-opacity:1" />
                        </linearGradient>
                        <linearGradient id="waveGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" style="stop-color:#60A5FA;stop-opacity:0" />
                            <stop offset="50%" style="stop-color:#60A5FA;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#60A5FA;stop-opacity:0" />
                        </linearGradient>
                        <filter id="glow">
                            <feGaussianBlur stdDeviation="2" result="coloredBlur" />
                            <feMerge>
                                <feMergeNode in="coloredBlur" />
                                <feMergeNode in="SourceGraphic" />
                            </feMerge>
                        </filter>
                    </defs>

                    <!-- Globe with network connections -->
                    <g>
                        <!-- Central globe circle -->
                        <circle cx="200" cy="150" r="80" fill="url(#globeGradient)" opacity="0.9" />

                        <!-- Globe wireframe -->
                        <circle cx="200" cy="150" r="80" fill="none" stroke="#93C5FD" stroke-width="1" opacity="0.5" />
                        <ellipse cx="200" cy="150" rx="80" ry="25" fill="none" stroke="#93C5FD" stroke-width="1" opacity="0.4" />
                        <ellipse cx="200" cy="150" rx="60" ry="75" fill="none" stroke="#93C5FD" stroke-width="1" opacity="0.4" />

                        <!-- Globe highlight -->
                        <ellipse cx="170" cy="120" rx="35" ry="40" fill="#DBEAFE" opacity="0.3" />
                    </g>

                    <!-- Network connection nodes -->
                    <g>
                        <!-- Node 1: Top Left -->
                        <circle cx="100" cy="80" r="6" fill="#3B82F6" />
                        <circle cx="100" cy="80" r="12" fill="#3B82F6" opacity="0.2" />
                        <line x1="100" y1="80" x2="200" y2="150" stroke="#60A5FA" stroke-width="2" opacity="0.6" />

                        <!-- Node 2: Top Right -->
                        <circle cx="300" cy="90" r="6" fill="#3B82F6" />
                        <circle cx="300" cy="90" r="12" fill="#3B82F6" opacity="0.2" />
                        <line x1="300" y1="90" x2="200" y2="150" stroke="#60A5FA" stroke-width="2" opacity="0.6" />

                        <!-- Node 3: Left -->
                        <circle cx="80" cy="160" r="6" fill="#3B82F6" />
                        <circle cx="80" cy="160" r="12" fill="#3B82F6" opacity="0.2" />
                        <line x1="80" y1="160" x2="200" y2="150" stroke="#60A5FA" stroke-width="2" opacity="0.6" />

                        <!-- Node 4: Right -->
                        <circle cx="320" cy="170" r="6" fill="#3B82F6" />
                        <circle cx="320" cy="170" r="12" fill="#3B82F6" opacity="0.2" />
                        <line x1="320" y1="170" x2="200" y2="150" stroke="#60A5FA" stroke-width="2" opacity="0.6" />
                    </g>

                    <!-- Signal waves -->
                    <g opacity="0.7">
                        <!-- Wave 1 -->
                        <path d="M 200 220 Q 220 210, 240 220" stroke="#60A5FA" stroke-width="2" fill="none" stroke-linecap="round" />
                        <!-- Wave 2 -->
                        <path d="M 200 235 Q 215 220, 255 235" stroke="#60A5FA" stroke-width="2" fill="none" stroke-linecap="round" opacity="0.7" />
                        <!-- Wave 3 -->
                        <path d="M 200 250 Q 210 230, 270 250" stroke="#60A5FA" stroke-width="2" fill="none" stroke-linecap="round" opacity="0.5" />
                    </g>

                    <!-- Speed indicator bars -->
                    <g>
                        <text x="80" y="310" font-size="14" fill="#1E40AF" font-weight="600">Upload</text>
                        <!-- Speed bar background -->
                        <rect x="80" y="320" width="120" height="8" rx="4" fill="#DBEAFE" />
                        <!-- Speed bar fill -->
                        <rect x="80" y="320" width="95" height="8" rx="4" fill="#3B82F6" />
                        <text x="85" y="345" font-size="12" fill="#60A5FA">95 Mbps</text>

                        <text x="220" y="310" font-size="14" fill="#1E40AF" font-weight="600">Download</text>
                        <!-- Speed bar background -->
                        <rect x="220" y="320" width="120" height="8" rx="4" fill="#DBEAFE" />
                        <!-- Speed bar fill -->
                        <rect x="220" y="320" width="110" height="8" rx="4" fill="#3B82F6" />
                        <text x="225" y="345" font-size="12" fill="#60A5FA">110 Mbps</text>
                    </g>

                    <!-- Animated data packets (circles moving along path) -->
                    <g>
                        <circle cx="150" cy="115" r="3" fill="#60A5FA" filter="url(#glow)">
                            <animate attributeName="cx" values="100;200;300;100" dur="4s" repeatCount="indefinite" />
                            <animate attributeName="cy" values="80;150;90;80" dur="4s" repeatCount="indefinite" />
                            <animate attributeName="opacity" values="0;1;1;0" dur="4s" repeatCount="indefinite" />
                        </circle>
                        <circle cx="240" cy="145" r="3" fill="#60A5FA" filter="url(#glow)">
                            <animate attributeName="cx" values="300;200;80;300" dur="4.5s" repeatCount="indefinite" />
                            <animate attributeName="cy" values="90;150;160;90" dur="4.5s" repeatCount="indefinite" />
                            <animate attributeName="opacity" values="0;1;1;0" dur="4.5s" repeatCount="indefinite" />
                        </circle>
                    </g>

                    <!-- Router/Server icon at bottom -->
                    <g>
                        <rect x="140" y="380" width="120" height="70" rx="8" fill="#DBEAFE" stroke="#3B82F6" stroke-width="2" />
                        <!-- Router slots -->
                        <rect x="155" y="395" width="30" height="4" fill="#3B82F6" rx="2" />
                        <rect x="215" y="395" width="30" height="4" fill="#3B82F6" rx="2" />
                        <rect x="155" y="410" width="30" height="4" fill="#60A5FA" rx="2" />
                        <rect x="215" y="410" width="30" height="4" fill="#60A5FA" rx="2" />
                        <!-- LED indicators -->
                        <circle cx="160" cy="430" r="2.5" fill="#10B981" />
                        <circle cx="175" cy="430" r="2.5" fill="#10B981" />
                        <circle cx="225" cy="430" r="2.5" fill="#F59E0B" />
                        <circle cx="240" cy="430" r="2.5" fill="#10B981" />
                    </g>

                    <!-- Connection lines from globe to router -->
                    <line x1="200" y1="230" x2="200" y2="380" stroke="#60A5FA" stroke-width="2" opacity="0.4" stroke-dasharray="5,5" />
                </svg>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="glass-panel flex justify-center lg:w-1/3 w-full p-8 sm:p-12 z-20 shadow-2xl transition-colors duration-500 overflow-y-auto">
            <div class="max-w-md w-full mt-4">
                <!-- Logo + Brand -->
                <div class="flex gap-3 items-center mb-8">
                    <div class="relative w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-700 dark:from-blue-600 dark:to-cyan-500 rounded-lg shadow-lg flex items-center justify-center overflow-hidden">
                        <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
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
                        <!-- Accent dot -->
                        <div class="absolute top-1 right-1 w-1.5 h-1.5 bg-cyan-300 rounded-full animate-pulse"></div>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">Pakistan</h1>
                        <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 tracking-widest uppercase">Cable</p>
                    </div>
                </div>

                <!-- Heading -->
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Welcome Back! 👋</h2>
                <p class="text-gray-600 dark:text-gray-300 mb-10">Please sign-in to your account and start the adventure</p>

                <!-- Form -->
                <form id="login-form" novalidate>
                    <!-- General Error -->
                    <div id="form-error" class="hidden mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800">
                        <p class="text-red-600 dark:text-red-400 text-sm" id="form-error-text"></p>
                    </div>

                    <!-- Email -->
                    <div class="mb-4">
                        <label class="block text-gray-500 dark:text-gray-400 text-xs font-normal mb-2 uppercase tracking-wider">Email or Mobile Number</label>
                        <input
                            id="username-input"
                            class="ani-input w-full px-3 py-3 border rounded-[4px] focus:outline-none focus:ring-1 focus:shadow-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white border-gray-300 dark:border-gray-600 focus:border-blue-400 focus:ring-blue-400"
                            type="text"
                            placeholder="Enter your email or mobile number" />
                        <p id="email-error" class="hidden text-red-500 text-sm mt-1">Email is required</p>
                    </div>

                    <!-- Password -->
                    <div class="mb-4 relative">
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-gray-500 dark:text-gray-400 text-xs font-normal uppercase tracking-wider">Password</label>
                            <a href="./forgot-password.php" class="text-xs text-blue-600 dark:text-blue-400 hover:underline font-medium">Forgot password?</a>
                        </div>
                        <input
                            id="password-input"
                            class="ani-input w-full px-3 py-3 border rounded-[4px] focus:outline-none focus:ring-1 focus:shadow-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white border-gray-300 dark:border-gray-600 focus:border-blue-400 focus:ring-blue-400 pr-10"
                            type="password"
                            placeholder="Enter your password"
                            autocomplete="current-password" />
                        <!-- Show/Hide password button -->
                        <button type="button" id="toggle-password" class="absolute right-3 top-[40px] text-gray-500 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 focus:outline-none">
                            <!-- Eye icon (show) -->
                            <svg id="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 12C4 12 5.6 7 12 7M12 7C18.4 7 20 12 20 12M12 7V4M18 5L16 7.5M6 5L8 7.5M15 13C15 14.6569 13.6569 16 12 16C10.3431 16 9 14.6569 9 13C9 11.3431 10.3431 10 12 10C13.6569 10 15 11.3431 15 13Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <!-- Eye-off icon (hide) — hidden by default -->
                            <svg id="eye-off-icon" class="hidden" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 10C4 10 5.6 15 12 15M12 15C18.4 15 20 10 20 10M12 15V18M18 17L16 14.5M6 17L8 14.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                        <p id="password-error" class="hidden text-red-500 text-sm mt-1">Password is required</p>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        id="submit-btn"
                        class="flex justify-center items-center gap-2 w-full text-center py-3 rounded-lg transition duration-200 font-medium bg-blue-800 text-white hover:bg-blue-900">
                        Sign In
                    </button>
                </form>

                <!-- Demo credentials hint -->
                <!-- <div class="mt-6 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800">
                    <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">Demo credentials:</p>
                    <p class="text-xs text-blue-500 dark:text-blue-300 mt-1">Email: <strong>admin@example.com</strong> &nbsp;|&nbsp; Password: <strong>password</strong></p>
                </div> -->
            </div>
        </div>
    </div>
    <script src="./assets/js/loader.js"></script>
    <script src="./assets/js/button-loading.js"></script>
    <script src="./assets/js/login.js"></script>
    <script src="./assets/js/passwordShowHide.js"></script>
</body>

</html>
