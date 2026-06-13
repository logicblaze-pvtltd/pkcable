     <!-- ANTI-FOUC: Apply dark mode BEFORE any CSS loads to prevent flash -->
     <script>
          ! function() {
               var e = localStorage.getItem('theme');
               if (e === 'dark' || (e === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark')
               }
          }();
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
     <?php include_once "./helpers/helpers.php" ?>