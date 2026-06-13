<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restricted Portal</title>
    <link rel="stylesheet" href="assets/css/tailwind.css">
</head>
<body class="bg-[#f8fafc] dark:bg-slate-900 min-h-screen flex items-center justify-center overflow-hidden relative font-sans text-slate-600 dark:text-slate-400 antialiased p-4 transition-colors duration-300">

    <div class="absolute inset-0 z-0 pointer-events-none">
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#e2e8f0_1px,transparent_1px),linear-gradient(to_bottom,#e2e8f0_1px,transparent_1px)] dark:bg-[linear-gradient(to_right,#334155_0.5px,transparent_0.5px),linear-gradient(to_bottom,#334155_0.5px,transparent_0.5px)] bg-[size:5rem_5rem] [mask-image:radial-gradient(ellipse_50%_50%_at_50%_50%,#000_60%,transparent_100%)] opacity-70 dark:opacity-40"></div>
        
        <div id="fluid-aurora-1" class="absolute -top-20 -left-20 w-[50vw] h-[50vw] rounded-full bg-gradient-to-tr from-sky-200/40 to-indigo-100/40 dark:from-sky-500/10 dark:to-indigo-500/10 blur-[130px] transition-transform duration-500 cubic-bezier(0.1, 0.8, 0.2, 1)"></div>
        <div id="fluid-aurora-2" class="absolute -bottom-20 -right-20 w-[45vw] h-[45vw] rounded-full bg-gradient-to-bl from-rose-100/30 to-amber-100/40 dark:from-rose-500/5 dark:to-amber-500/10 blur-[120px] transition-transform duration-500 cubic-bezier(0.1, 0.8, 0.2, 1)"></div>
    </div>

    <div id="glass-portal" class="relative z-10 w-full max-w-md bg-white/75 dark:bg-slate-800/75 backdrop-blur-3xl border border-white/80 dark:border-slate-700/80 shadow-[0_40px_80px_-15px_rgba(15,23,42,0.06)] dark:shadow-[0_40px_80px_-15px_rgba(0,0,0,0.5)] rounded-3xl p-10 text-center transition-all duration-300 ease-out">
        
        <div class="mx-auto w-14 h-14 bg-slate-900 dark:bg-slate-100 rounded-2xl flex items-center justify-center shadow-md shadow-slate-900/10 mb-6 group transition-transform duration-300 hover:scale-110">
            <svg class="w-5 h-5 text-white dark:text-slate-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
        </div>

        <h1 class="text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">Access Denied</h1>
        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400 leading-relaxed max-w-xs mx-auto">
            Access Denied. This layout is restricted to specific organizational persons only. If you need access, please reach out to Superadmin
        </p>

        <div class="my-8 w-12 h-0.5 bg-slate-200/80 dark:bg-slate-700 mx-auto rounded-full"></div>

        <div class="flex flex-col space-y-2">
            <a href="index.php" class="w-full inline-flex items-center justify-center px-5 py-3 bg-slate-950 dark:bg-slate-100 hover:bg-slate-800 dark:hover:bg-slate-200 text-white dark:text-slate-950 font-medium text-xs uppercase tracking-wider rounded-xl shadow-sm transition-all duration-150 active:scale-[0.98]">
                Return to Workspace
            </a>
        </div>
    </div>

    <script>
        const portal = document.getElementById('glass-portal');
        const aurora1 = document.getElementById('fluid-aurora-1');
        const aurora2 = document.getElementById('fluid-aurora-2');

        window.addEventListener('mousemove', (e) => {
            const xOffset = (window.innerWidth / 2 - e.clientX) / 30;
            const yOffset = (window.innerHeight / 2 - e.clientY) / 30;

            portal.style.transform = `translate3d(${-xOffset * 0.4}px, ${-yOffset * 0.4}px, 0)`;
            aurora1.style.transform = `translate3d(${xOffset * 1.8}px, ${yOffset * 1.8}px, 0)`;
            aurora2.style.transform = `translate3d(${xOffset * -1.2}px, ${yOffset * -1.2}px, 0)`;
        });
    </script>
</body>
</html>