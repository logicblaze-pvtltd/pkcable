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
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> - Forgot Password</title>
    <meta name="description" content="Reset your Pakistan Cable account password securely using a one-time code sent to your email.">
    <?php include './include/headerLinks.php' ?>
    <link rel="stylesheet" href="./assets/css/login.css">
    <link rel="stylesheet" href="./assets/css/forgot-password.css">
</head>

<body class="bg-gray-900 dark:bg-gray-900 h-screen overflow-hidden transition-colors duration-500">

    <!-- Parallax Background -->
    <div id="parallax-bg" class="fixed inset-0 w-full h-full z-0 transition-colors duration-500 ease-in-out bg-[#f3f4f4] dark:bg-[#030712]">
        <!-- Gradient overlay -->
        <div id="bg-overlay" class="absolute inset-0 opacity-40 mode-transition" style="background: radial-gradient(circle at 30% 70%, rgba(200, 200, 220, 0.5), transparent 70%);"></div>

        <!-- Layer 1 -->
        <div class="paralexx-layer absolute inset-0" data-speed="-0.04">
            <div id="blob-1" class="absolute top-[10%] left-[5%] w-96 h-96 rounded-full blur-3xl animate-float-slow mode-transition" style="background: radial-gradient(circle at 40% 60%, rgba(139, 92, 246, 0.55), rgba(59, 130, 246, 0.3));"></div>
            <div id="blob-2" class="absolute bottom-[15%] right-[2%] w-[30rem] h-[30rem] rounded-full blur-3xl animate-float-medium mode-transition" style="background: radial-gradient(circle at 70% 30%, rgba(236, 72, 153, 0.4), rgba(139, 92, 246, 0.3));"></div>
        </div>

        <!-- Layer 2 -->
        <div class="paralexx-layer absolute inset-0" data-speed="0.08">
            <div id="blob-3" class="absolute top-[55%] left-[20%] w-80 h-80 rounded-full blur-2xl animate-float-medium mode-transition" style="background: radial-gradient(circle at 40% 60%, rgba(139, 92, 246, 0.35), rgba(59, 130, 246, 0.2));"></div>
            <div id="blob-4" class="absolute top-[20%] right-[15%] w-72 h-72 rounded-full blur-2xl animate-float-fast mode-transition" style="background: radial-gradient(circle at 30% 60%, rgba(99, 102, 241, 0.4), rgba(59, 130, 246, 0.25));"></div>
        </div>

        <!-- Layer 3 -->
        <div class="paralexx-layer absolute inset-0" data-speed="0.18">
            <div id="blob-5" class="absolute bottom-[35%] left-[30%] w-44 h-44 rounded-full blur-xl animate-float-fast mode-transition" style="background: radial-gradient(circle at 40% 60%, rgba(139, 92, 246, 0.45), rgba(59, 130, 246, 0.25));"></div>
            <div id="blob-6" class="absolute top-[40%] left-[70%] w-32 h-32 rounded-full blur-xl animate-float-slow mode-transition" style="background: radial-gradient(circle at 40% 60%, rgba(236, 72, 153, 0.4), rgba(139, 92, 246, 0.25));"></div>
        </div>
    </div>

    <div class="flex w-full h-screen relative z-10 shadow-2xl overflow-hidden">

        <!-- Left Side: Illustration -->
        <div class="hidden lg:flex flex-grow h-screen items-center justify-center relative opacity-95">
            <div class="w-2/3 max-w-md px-8">
                <!-- Lock illustration SVG -->
                <svg viewBox="0 0 400 420" xmlns="http://www.w3.org/2000/svg" class="svg-float w-full h-auto">
                    <defs>
                        <linearGradient id="lockGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#7C3AED;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#4F46E5;stop-opacity:1" />
                        </linearGradient>
                        <linearGradient id="shieldGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#8B5CF6;stop-opacity:0.15" />
                            <stop offset="100%" style="stop-color:#6366F1;stop-opacity:0.05" />
                        </linearGradient>
                        <filter id="glow2">
                            <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                            <feMerge>
                                <feMergeNode in="coloredBlur"/>
                                <feMergeNode in="SourceGraphic"/>
                            </feMerge>
                        </filter>
                    </defs>

                    <!-- Outer ring -->
                    <circle cx="200" cy="190" r="140" fill="url(#shieldGrad)" stroke="#7C3AED" stroke-width="1.5" stroke-dasharray="8,4" opacity="0.5"/>
                    <circle cx="200" cy="190" r="115" fill="url(#shieldGrad)" stroke="#8B5CF6" stroke-width="1" opacity="0.4"/>

                    <!-- Shield body -->
                    <path d="M200 70 L290 110 L290 195 Q290 265 200 305 Q110 265 110 195 L110 110 Z" fill="url(#lockGrad)" opacity="0.15" stroke="#7C3AED" stroke-width="2"/>

                    <!-- Lock body -->
                    <rect x="155" y="175" width="90" height="80" rx="12" fill="url(#lockGrad)" filter="url(#glow2)"/>

                    <!-- Lock shackle -->
                    <path d="M173 175 L173 152 Q173 130 200 130 Q227 130 227 152 L227 175" fill="none" stroke="url(#lockGrad)" stroke-width="14" stroke-linecap="round"/>
                    <path d="M173 175 L173 152 Q173 133 200 133 Q224 133 224 152 L224 175" fill="none" stroke="#A78BFA" stroke-width="8" stroke-linecap="round" opacity="0.5"/>

                    <!-- Keyhole -->
                    <circle cx="200" cy="210" r="10" fill="rgba(255,255,255,0.9)"/>
                    <rect x="196" y="210" width="8" height="16" rx="2" fill="rgba(255,255,255,0.9)"/>

                    <!-- OTP dots below lock -->
                    <g>
                        <rect x="120" y="340" width="35" height="42" rx="8" fill="#7C3AED" opacity="0.8"/>
                        <text x="137.5" y="367" font-size="18" font-weight="700" fill="white" text-anchor="middle">3</text>

                        <rect x="165" y="340" width="35" height="42" rx="8" fill="#6366F1" opacity="0.8"/>
                        <text x="182.5" y="367" font-size="18" font-weight="700" fill="white" text-anchor="middle">8</text>

                        <rect x="210" y="340" width="35" height="42" rx="8" fill="#7C3AED" opacity="0.8"/>
                        <text x="227.5" y="367" font-size="18" font-weight="700" fill="white" text-anchor="middle">4</text>

                        <rect x="255" y="340" width="35" height="42" rx="8" fill="#8B5CF6" opacity="0.6"/>
                        <text x="272.5" y="367" font-size="18" font-weight="700" fill="rgba(255,255,255,0.5)" text-anchor="middle">•</text>
                    </g>

                    <!-- Floating particles -->
                    <circle cx="95" cy="140" r="4" fill="#A78BFA" opacity="0.7">
                        <animate attributeName="cy" values="140;125;140" dur="3s" repeatCount="indefinite"/>
                        <animate attributeName="opacity" values="0.7;1;0.7" dur="3s" repeatCount="indefinite"/>
                    </circle>
                    <circle cx="310" cy="170" r="3" fill="#818CF8" opacity="0.6">
                        <animate attributeName="cy" values="170;155;170" dur="4s" repeatCount="indefinite"/>
                    </circle>
                    <circle cx="320" cy="250" r="5" fill="#7C3AED" opacity="0.5">
                        <animate attributeName="cy" values="250;235;250" dur="3.5s" repeatCount="indefinite"/>
                    </circle>
                    <circle cx="80" cy="260" r="3" fill="#A78BFA" opacity="0.6">
                        <animate attributeName="cy" values="260;245;260" dur="2.8s" repeatCount="indefinite"/>
                    </circle>

                    <!-- Email icon top right -->
                    <g transform="translate(295, 80)">
                        <rect x="0" y="0" width="50" height="36" rx="6" fill="#6366F1" opacity="0.8"/>
                        <path d="M2 4 L25 20 L48 4" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" opacity="0.9"/>
                    </g>

                    <!-- Connecting line -->
                    <line x1="295" y1="116" x2="240" y2="155" stroke="#8B5CF6" stroke-width="1.5" stroke-dasharray="4,3" opacity="0.5"/>
                </svg>

                <!-- Text below illustration -->
                <div class="text-center mt-4">
                    <h2 class="text-xl font-bold text-gray-700 dark:text-gray-200">Secure Password Reset</h2>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mt-2">We'll send a one-time code to your email to verify your identity before resetting your password.</p>
                </div>
            </div>
        </div>

        <!-- Right Side: Multi-step Form Panel -->
        <div class="glass-panel flex justify-center lg:w-1/3 w-full p-8 sm:p-12 z-20 shadow-2xl transition-colors duration-500 overflow-y-auto">
            <div class="max-w-md w-full mt-4">

                <!-- Logo + Brand -->
                <div class="flex gap-3 items-center mb-8">
                    <div class="relative w-10 h-10 bg-gradient-to-br from-violet-500 to-indigo-700 dark:from-violet-600 dark:to-indigo-500 rounded-lg shadow-lg flex items-center justify-center overflow-hidden">
                        <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="cableGrad2" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:#ffffff;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#e0e7ff;stop-opacity:1" />
                                </linearGradient>
                            </defs>
                            <rect x="4" y="6" width="16" height="12" rx="2" fill="url(#cableGrad2)" opacity="0.95" />
                            <circle cx="8" cy="12" r="1.5" fill="#4338ca" />
                            <circle cx="12" cy="12" r="1.5" fill="#4338ca" />
                            <circle cx="16" cy="12" r="1.5" fill="#4338ca" />
                            <path d="M6 9 Q7 8 8 9" stroke="#4338ca" stroke-width="1" fill="none" stroke-linecap="round" />
                            <path d="M16 9 Q17 8 18 9" stroke="#4338ca" stroke-width="1" fill="none" stroke-linecap="round" />
                        </svg>
                        <div class="absolute top-1 right-1 w-1.5 h-1.5 bg-violet-300 rounded-full animate-pulse"></div>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">Pakistan</h1>
                        <p class="text-xs font-semibold text-violet-600 dark:text-violet-400 tracking-widest uppercase">Cable</p>
                    </div>
                </div>

                <!-- Step Indicator -->
                <div id="step-indicator" class="flex items-center gap-2 mb-8">
                    <div class="fp-step-dot fp-step-active" id="dot-1"></div>
                    <div class="fp-step-line" id="line-1"></div>
                    <div class="fp-step-dot" id="dot-2"></div>
                    <div class="fp-step-line" id="line-2"></div>
                    <div class="fp-step-dot" id="dot-3"></div>
                    <span class="ml-auto text-xs text-gray-400 dark:text-gray-500" id="step-label">Step 1 of 3</span>
                </div>

                <!-- ======== STEP 1: Email ======== -->
                <div id="step-1" class="fp-step-panel">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-1">Forgot Password? 🔐</h2>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mb-7">Enter your registered email address. We'll send you a one-time code to reset your password.</p>

                    <!-- Error box -->
                    <div id="s1-error" class="hidden mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800">
                        <p class="text-red-600 dark:text-red-400 text-sm" id="s1-error-text"></p>
                    </div>

                    <form id="form-step1" novalidate>
                        <div class="mb-6">
                            <label class="block text-gray-500 dark:text-gray-400 text-xs font-normal mb-2 uppercase tracking-wider">Email Address</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                </span>
                                <input
                                    id="s1-email"
                                    type="email"
                                    placeholder="yourname@example.com"
                                    autocomplete="email"
                                    class="ani-input w-full pl-10 pr-4 py-3 border rounded-[4px] focus:outline-none focus:ring-1 focus:shadow-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white border-gray-300 dark:border-gray-600 focus:border-violet-400 focus:ring-violet-400" />
                            </div>
                            <p id="s1-email-error" class="hidden text-red-500 text-xs mt-1.5">Please enter a valid email address</p>
                        </div>

                        <button type="submit" id="s1-btn"
                            class="fp-btn-primary flex justify-center items-center gap-2 w-full py-3 rounded-lg font-medium transition duration-200">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4 20-7z"/></svg>
                            Send OTP Code
                        </button>
                    </form>

                    <p class="text-center mt-6 text-sm text-gray-500 dark:text-gray-400">
                        Remember your password? <a href="./login.php" class="text-violet-600 dark:text-violet-400 font-medium hover:underline">Sign in</a>
                    </p>
                </div>

                <!-- ======== STEP 2: OTP Verification ======== -->
                <div id="step-2" class="fp-step-panel hidden">
                    <div class="flex items-center gap-3 mb-1">
                        <div class="w-10 h-10 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-800 dark:text-white">Check Your Email ✉️</h2>
                        </div>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mb-1 mt-2">We've sent a <strong>6-digit one-time code</strong> to:</p>
                    <p class="text-violet-600 dark:text-violet-400 font-semibold text-sm mb-7" id="s2-email-display">—</p>

                    <!-- Error box -->
                    <div id="s2-error" class="hidden mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800">
                        <p class="text-red-600 dark:text-red-400 text-sm" id="s2-error-text"></p>
                    </div>

                    <form id="form-step2" novalidate>
                        <div class="mb-2">
                            <label class="block text-gray-500 dark:text-gray-400 text-xs font-normal mb-3 uppercase tracking-wider">Enter 6-Digit Code</label>
                            <!-- OTP Input Boxes -->
                            <div id="otp-inputs" class="flex gap-2 justify-between">
                                <input type="text" inputmode="numeric" maxlength="1" class="otp-box" data-index="0" autocomplete="off" />
                                <input type="text" inputmode="numeric" maxlength="1" class="otp-box" data-index="1" autocomplete="off" />
                                <input type="text" inputmode="numeric" maxlength="1" class="otp-box" data-index="2" autocomplete="off" />
                                <input type="text" inputmode="numeric" maxlength="1" class="otp-box" data-index="3" autocomplete="off" />
                                <input type="text" inputmode="numeric" maxlength="1" class="otp-box" data-index="4" autocomplete="off" />
                                <input type="text" inputmode="numeric" maxlength="1" class="otp-box" data-index="5" autocomplete="off" />
                            </div>
                            <p id="s2-otp-error" class="hidden text-red-500 text-xs mt-2">Please enter the complete 6-digit code</p>
                        </div>

                        <!-- Timer -->
                        <div class="flex items-center justify-between mt-3 mb-6">
                            <p class="text-xs text-gray-400 dark:text-gray-500">Code expires in <span id="otp-timer" class="text-violet-600 dark:text-violet-400 font-semibold">10:00</span></p>
                            <button type="button" id="resend-btn" class="text-xs text-violet-600 dark:text-violet-400 hover:underline font-medium disabled:opacity-40 disabled:cursor-not-allowed disabled:no-underline" disabled>Resend Code</button>
                        </div>

                        <button type="submit" id="s2-btn"
                            class="fp-btn-primary flex justify-center items-center gap-2 w-full py-3 rounded-lg font-medium transition duration-200">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            Verify Code
                        </button>
                    </form>

                    <p class="text-center mt-4 text-sm text-gray-400 dark:text-gray-500">
                        <button type="button" id="s2-back-btn" class="hover:text-violet-500 transition-colors">← Back to email</button>
                    </p>
                </div>

                <!-- ======== STEP 3: Reset Password ======== -->
                <div id="step-3" class="fp-step-panel hidden">
                    <div class="flex items-center gap-3 mb-1">
                        <div class="w-10 h-10 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-800 dark:text-white">New Password 🔑</h2>
                        </div>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mb-7 mt-2">Create a strong new password for your account. Make sure it's at least 8 characters long.</p>

                    <!-- Error box -->
                    <div id="s3-error" class="hidden mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800">
                        <p class="text-red-600 dark:text-red-400 text-sm" id="s3-error-text"></p>
                    </div>

                    <form id="form-step3" novalidate>
                        <!-- New Password -->
                        <div class="mb-4 relative">
                            <label class="block text-gray-500 dark:text-gray-400 text-xs font-normal mb-2 uppercase tracking-wider">New Password</label>
                            <input
                                id="s3-password"
                                type="password"
                                placeholder="Minimum 8 characters"
                                class="ani-input w-full px-4 py-3 border rounded-[4px] focus:outline-none focus:ring-1 focus:shadow-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white border-gray-300 dark:border-gray-600 focus:border-violet-400 focus:ring-violet-400 pr-10" />
                            <button type="button" class="fp-eye-btn" data-target="s3-password">
                                <svg class="fp-eye-show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 12C4 12 5.6 7 12 7M12 7C18.4 7 20 12 20 12M12 7V4M18 5L16 7.5M6 5L8 7.5M15 13C15 14.6569 13.6569 16 12 16C10.3431 16 9 14.6569 9 13C9 11.3431 10.3431 10 12 10C13.6569 10 15 11.3431 15 13Z" stroke-linejoin="round"/></svg>
                                <svg class="fp-eye-hide hidden" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 10C4 10 5.6 15 12 15M12 15C18.4 15 20 10 20 10M12 15V18M18 17L16 14.5M6 17L8 14.5" stroke-linejoin="round"/></svg>
                            </button>
                            <p id="s3-password-error" class="hidden text-red-500 text-xs mt-1.5">Password must be at least 8 characters</p>

                            <!-- Password Strength Bar -->
                            <div class="mt-2">
                                <div class="h-1 w-full bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                    <div id="strength-bar" class="h-full rounded-full transition-all duration-300 w-0"></div>
                                </div>
                                <p id="strength-label" class="text-xs mt-1 text-gray-400 dark:text-gray-500 hidden"></p>
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-6 relative">
                            <label class="block text-gray-500 dark:text-gray-400 text-xs font-normal mb-2 uppercase tracking-wider">Confirm Password</label>
                            <input
                                id="s3-confirm"
                                type="password"
                                placeholder="Re-enter your password"
                                class="ani-input w-full px-4 py-3 border rounded-[4px] focus:outline-none focus:ring-1 focus:shadow-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white border-gray-300 dark:border-gray-600 focus:border-violet-400 focus:ring-violet-400 pr-10" />
                            <button type="button" class="fp-eye-btn" data-target="s3-confirm">
                                <svg class="fp-eye-show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 12C4 12 5.6 7 12 7M12 7C18.4 7 20 12 20 12M12 7V4M18 5L16 7.5M6 5L8 7.5M15 13C15 14.6569 13.6569 16 12 16C10.3431 16 9 14.6569 9 13C9 11.3431 10.3431 10 12 10C13.6569 10 15 11.3431 15 13Z" stroke-linejoin="round"/></svg>
                                <svg class="fp-eye-hide hidden" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 10C4 10 5.6 15 12 15M12 15C18.4 15 20 10 20 10M12 15V18M18 17L16 14.5M6 17L8 14.5" stroke-linejoin="round"/></svg>
                            </button>
                            <p id="s3-confirm-error" class="hidden text-red-500 text-xs mt-1.5">Passwords do not match</p>
                        </div>

                        <button type="submit" id="s3-btn"
                            class="fp-btn-primary flex justify-center items-center gap-2 w-full py-3 rounded-lg font-medium transition duration-200">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            Reset Password
                        </button>
                    </form>
                </div>

                <!-- ======== STEP 4: Success ======== -->
                <div id="step-success" class="fp-step-panel hidden text-center py-6">
                    <div class="fp-success-icon mx-auto mb-6">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">Password Reset! 🎉</h2>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mb-8">Your password has been successfully updated. You can now sign in with your new password.</p>
                    <a href="./login.php"
                        class="fp-btn-primary inline-flex justify-center items-center gap-2 px-8 py-3 rounded-lg font-medium transition duration-200">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                        Go to Sign In
                    </a>
                </div>

            </div>
        </div>
    </div>

    <script src="./assets/js/forgot-password.js"></script>
</body>

</html>
