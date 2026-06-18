     <link rel="manifest" href="/manifest.json">
     <meta name="theme-color" content="#000000">
     <!-- ANTI-FOUC: Apply dark mode BEFORE any CSS loads to prevent flash -->
     <script>
          ! function() {
               var e = localStorage.getItem('theme');
               if (e === 'dark' || (e === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark')
               }
          }();
     </script>

     <!-- Dynamic APP URL Configuration -->
     <script>
          window.APP_URL = <?php
                              $configured = get_env_value('APP_URL');
                              if ($configured) {
                                   $url = rtrim($configured, '/');
                              } else {
                                   $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                                   $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                                   $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
                                   $basePath = preg_replace('#/(?:assets|controller|include)(?:/.*)?$#', '', $scriptDir);
                                   $url = rtrim($scheme . '://' . $host . ($basePath === '/' ? '' : $basePath), '/');
                              }
                              echo json_encode($url);
                              ?>;
     </script>


     <!-- Tailwind CSS -->
     <link rel="stylesheet" href="assets/css/tailwind.css">
     <!-- Lucide Icons -->
     <script src="node_modules/lucide/dist/umd/lucide.min.js"></script>
     <!-- Font Awesome -->
     <link rel="stylesheet" href="node_modules/@fortawesome/fontawesome-free/css/all.min.css">
     <!-- Perfect Scrollbar CSS -->
     <link rel="stylesheet" href="node_modules/perfect-scrollbar/css/perfect-scrollbar.css" />

     <!-- Custom CSS -->
     <link rel="stylesheet" href="assets/css/animation.css">
     <link rel="stylesheet" href="assets/css/style.css">
     <link rel="stylesheet" href="assets/css/breadcrumbs.css">
     <link rel="stylesheet" href="assets/css/modal.css">
     <link rel="stylesheet" href="node_modules/simple-datatables/dist/style.css">
     <link rel="stylesheet" href="assets/css/simple-datatables.css">
     <!-- ======================================== -->
     <!-- PAGE LOADER CSS -->
     <!-- ======================================== -->
     <link rel="stylesheet" href="assets/css/loader.css">
     <?php include_once "./helpers/helpers.php" ?>