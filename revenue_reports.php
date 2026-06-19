<?php
session_start();
require_once './include/connection.php';

// Access Control: Deny customers
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['user']['role'] === 'customer') {
    header("Location: access_denied.php");
    exit();
}

$isManager   = ($_SESSION['user']['role'] === 'manager');
$isAdmin     = in_array($_SESSION['user']['role'], ['admin', 'super admin']);
$managerId   = (int)$_SESSION['user']['id'];

// Admin can optionally filter by a specific manager via GET param
$selectedManagerId = 0;
if ($isAdmin && isset($_GET['filter_manager']) && (int)$_GET['filter_manager'] > 0) {
    $selectedManagerId = (int)$_GET['filter_manager'];
}

// Effective filter: manager always filters by self, admin filters only if a specific manager is chosen
$filterByUser = $isManager ? true : ($selectedManagerId > 0);
$filterUserId = $isManager ? $managerId : $selectedManagerId;

// Fetch manager list for admin dropdown
$allManagersList = [];
if ($isAdmin) {
    $mgrRes = $conn->query("SELECT id, name, user_role FROM users WHERE user_role IN ('manager','admin','super admin') AND status = 'active' ORDER BY name ASC");
    while ($mRow = $mgrRes->fetch_assoc()) {
        $allManagersList[] = $mRow;
    }
}

// Fetch selected manager name for display
$selectedManagerName = '';
if ($filterByUser) {
    $mnRes = $conn->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
    $mnRes->bind_param("i", $filterUserId);
    $mnRes->execute();
    $mnRow = $mnRes->get_result()->fetch_assoc();
    $selectedManagerName = $mnRow['name'] ?? 'Unknown';
}

// AJAX Request Handler for Daily Collector navigation
if (isset($_GET['ajax_collector_date'])) {
    header('Content-Type: application/json');
    $ajaxDate = $_GET['ajax_collector_date'];
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ajaxDate)) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        exit();
    }

    $collectorsList = [];
    $sqlAllCollectors = "SELECT id, name, user_role FROM users WHERE user_role NOT IN ('customer','super admin')";
    if ($filterByUser) {
        $sqlAllCollectors .= " AND id = " . $filterUserId;
    }
    $allColsRes = $conn->query($sqlAllCollectors);
    while ($row = $allColsRes->fetch_assoc()) {
        $collectorsList[(int)$row['id']] = [
            'name' => $row['name'],
            'role' => $row['user_role']
        ];
    }

    $startDateTime = $ajaxDate . ' 00:00:00';
    $endDateTime   = $ajaxDate . ' 23:59:59';
    $sqlCollector = "SELECT 
                         s.active_by,
                         COUNT(s.id) AS subs_count,
                         SUM(s.package_price - COALESCE(NULLIF(s.discount, ''), 0)) AS user_revenue
                     FROM subscriptions s
                     WHERE s.status != 'cancelled'
                       AND s.created_at >= ? AND s.created_at <= ?";
    if ($filterByUser) {
        $sqlCollector .= " AND s.active_by = ?";
    }
    $sqlCollector .= " GROUP BY s.active_by";

    $stmtCollector = $conn->prepare($sqlCollector);
    if ($filterByUser) {
        $stmtCollector->bind_param("ssi", $startDateTime, $endDateTime, $filterUserId);
    } else {
        $stmtCollector->bind_param("ss", $startDateTime, $endDateTime);
    }
    $stmtCollector->execute();
    $dbCollections = $stmtCollector->get_result()->fetch_all(MYSQLI_ASSOC);

    $collectionsMap = [];
    foreach ($dbCollections as $row) {
        $userId = $row['active_by'] !== null ? (int)$row['active_by'] : 0;

        if ($userId !== 0 && !isset($collectorsList[$userId])) {
            $userQuery = $conn->query("SELECT name, user_role FROM users WHERE id = $userId");
            if ($userQuery && $userRow = $userQuery->fetch_assoc()) {
                $collectorsList[$userId] = [
                    'name' => $userRow['name'],
                    'role' => $userRow['user_role']
                ];
            } else {
                $collectorsList[$userId] = [
                    'name' => 'Unknown User (ID ' . $userId . ')',
                    'role' => 'manager'
                ];
            }
        } else if ($userId === 0 && !isset($collectorsList[0])) {
            $collectorsList[0] = [
                'name' => 'System / Auto-activated',
                'role' => 'system'
            ];
        }

        $collectionsMap[$userId] = [
            'subs_count' => (int)$row['subs_count'],
            'user_revenue' => (float)$row['user_revenue']
        ];
    }

    $breakdown = [];
    foreach ($collectorsList as $colId => $colData) {
        $subsCount = 0;
        $revenue = 0.0;
        if (isset($collectionsMap[$colId])) {
            $subsCount = $collectionsMap[$colId]['subs_count'];
            $revenue   = $collectionsMap[$colId]['user_revenue'];
        }
        $breakdown[] = [
            'collector_id'   => $colId,
            'collector_name' => $colData['name'],
            'collector_role' => $colData['role'],
            'subs_count'     => $subsCount,
            'user_revenue'   => $revenue
        ];
    }

    usort($breakdown, function ($a, $b) {
        return $b['user_revenue'] <=> $a['user_revenue'];
    });

    echo json_encode([
        'success' => true,
        'date' => date('d-M-Y', strtotime($ajaxDate)),
        'raw_date' => $ajaxDate,
        'data' => $breakdown
    ]);
    exit();
}

// -------------------------------------------------------
// AJAX: Collector Detail — subscriptions collected on a date
// -------------------------------------------------------
if (isset($_GET['ajax_collector_detail'])) {
    header('Content-Type: application/json');
    $ajaxDate      = $_GET['ajax_collector_detail'];
    $ajaxUserId    = isset($_GET['collector_id']) ? (int)$_GET['collector_id'] : 0;

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ajaxDate) || $ajaxUserId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit();
    }

    // Security: manager can only see their own detail
    if ($isManager && $ajaxUserId !== $managerId) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }

    $detailStart = $ajaxDate . ' 00:00:00';
    $detailEnd   = $ajaxDate . ' 23:59:59';

    $collectorNameRes = $conn->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
    $collectorNameRes->bind_param("i", $ajaxUserId);
    $collectorNameRes->execute();
    $collectorNameRow = $collectorNameRes->get_result()->fetch_assoc();
    $collectorName = $collectorNameRow['name'] ?? 'Unknown';

    $sqlDetail = "SELECT 
                      s.id,
                      u.name AS customer_name,
                      u.address AS customer_address,
                      s.package_price,
                      COALESCE(NULLIF(s.discount,''),0) AS discount,
                      (s.package_price - COALESCE(NULLIF(s.discount,''),0)) AS net_amount,
                      p.name AS package_name,
                      s.status,
                      s.created_at,
                      s.end_date AS expiry_date
                  FROM subscriptions s
                  LEFT JOIN users u ON u.id = s.user_id
                  LEFT JOIN packages p ON p.id = s.package_id
                  WHERE s.active_by = ?
                    AND s.status != 'cancelled'
                    AND s.created_at >= ? AND s.created_at <= ?
                  ORDER BY s.created_at ASC";
    $stmtDetail = $conn->prepare($sqlDetail);
    $stmtDetail->bind_param("iss", $ajaxUserId, $detailStart, $detailEnd);
    $stmtDetail->execute();
    $resDetail = $stmtDetail->get_result();

    $records = [];
    $totalNet = 0.0;
    while ($r = $resDetail->fetch_assoc()) {
        $totalNet += (float)$r['net_amount'];
        $records[] = [
            'sub_id'          => (int)$r['id'],
            'customer_name'   => $r['customer_name'] ?? 'N/A',
            'customer_address'  => $r['customer_address'] ?? '',
            'package_name'    => $r['package_name'] ?? '',
            'package_price'   => (float)$r['package_price'],
            'discount'        => (float)$r['discount'],
            'net_amount'      => (float)$r['net_amount'],
            'status'          => $r['status'],
            'created_at'      => date('d-M-Y g:i A', strtotime($r['created_at'])),
            'expiry_date'     => $r['expiry_date'] ? date('d-M-Y', strtotime($r['expiry_date'])) : '-',
        ];
    }

    echo json_encode([
        'success'        => true,
        'collector_name' => $collectorName,
        'date'           => date('d-M-Y', strtotime($ajaxDate)),
        'total_net'      => $totalNet,
        'records'        => $records
    ]);
    exit();
}

// Get available years for filtering
$yearsQuery = $conn->query("
    SELECT DISTINCT YEAR(created_at) AS yr 
    FROM subscriptions 
    WHERE created_at IS NOT NULL 
    ORDER BY yr DESC
");
$years = [];
while ($row = $yearsQuery->fetch_assoc()) {
    $years[] = (int)$row['yr'];
}
if (!in_array((int)date('Y'), $years)) {
    $years[] = (int)date('Y');
}
sort($years);
$years = array_reverse($years); // Show latest year first

// Filters
$selectedYear  = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');

// Date Range (default: start and end of selected month/year)
$defaultStartDate = sprintf('%04d-%02d-01', $selectedYear, $selectedMonth);
$defaultEndDate   = date('Y-m-t', strtotime($defaultStartDate));

$startDate = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : $defaultStartDate;
$endDate   = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : $defaultEndDate;

// Format dates for display
$displayStartDate = date('d-M-Y', strtotime($startDate));
$displayEndDate   = date('d-M-Y', strtotime($endDate));

// Format parameters for DB query (inclusive day boundaries)
$startDateTime = $startDate . ' 00:00:00';
$endDateTime   = $endDate . ' 23:59:59';

// ----------------------------------------------------
// 1. STATS METRICS CARDS
// ----------------------------------------------------

// Monthly Total Revenue
$sqlMonth = "SELECT SUM(s.package_price - COALESCE(NULLIF(s.discount, ''), 0)) AS total, COUNT(s.id) AS count
             FROM subscriptions s
             WHERE s.status != 'cancelled'
               AND YEAR(s.created_at) = ?
               AND MONTH(s.created_at) = ?";
if ($filterByUser) {
    $sqlMonth .= " AND s.active_by = ?";
}
$stmtMonth = $conn->prepare($sqlMonth);
if ($filterByUser) {
    $stmtMonth->bind_param("iii", $selectedYear, $selectedMonth, $filterUserId);
} else {
    $stmtMonth->bind_param("ii", $selectedYear, $selectedMonth);
}
$stmtMonth->execute();
$resMonth = $stmtMonth->get_result()->fetch_assoc();
$monthRevenue = (float)($resMonth['total'] ?? 0);
$monthSubsCount = (int)($resMonth['count'] ?? 0);

// Selected Year Total Revenue
$sqlYear = "SELECT SUM(s.package_price - COALESCE(NULLIF(s.discount, ''), 0)) AS total
            FROM subscriptions s
            WHERE s.status != 'cancelled'
              AND YEAR(s.created_at) = ?";
if ($filterByUser) {
    $sqlYear .= " AND s.active_by = ?";
}
$stmtYear = $conn->prepare($sqlYear);
if ($filterByUser) {
    $stmtYear->bind_param("ii", $selectedYear, $filterUserId);
} else {
    $stmtYear->bind_param("i", $selectedYear);
}
$stmtYear->execute();
$resYear = $stmtYear->get_result()->fetch_assoc();
$yearRevenue = (float)($resYear['total'] ?? 0);

// Total Active Subscriptions
$sqlActive = "SELECT COUNT(*) AS total FROM subscriptions s WHERE s.status = 'active'";
if ($filterByUser) {
    $sqlActive .= " AND s.active_by = $filterUserId";
}
$activeSubsCount = (int)($conn->query($sqlActive)->fetch_assoc()['total'] ?? 0);

// Growth (Selected Month vs Previous Month)
$prevYear = $selectedYear;
$prevMonth = $selectedMonth - 1;
if ($prevMonth == 0) {
    $prevMonth = 12;
    $prevYear--;
}
$sqlPrevMonth = "SELECT SUM(s.package_price - COALESCE(NULLIF(s.discount, ''), 0)) AS total
                 FROM subscriptions s
                 WHERE s.status != 'cancelled'
                   AND YEAR(s.created_at) = ?
                   AND MONTH(s.created_at) = ?";
if ($filterByUser) {
    $sqlPrevMonth .= " AND s.active_by = ?";
}
$stmtPrevMonth = $conn->prepare($sqlPrevMonth);
if ($filterByUser) {
    $stmtPrevMonth->bind_param("iii", $prevYear, $prevMonth, $filterUserId);
} else {
    $stmtPrevMonth->bind_param("ii", $prevYear, $prevMonth);
}
$stmtPrevMonth->execute();
$resPrevMonth = $stmtPrevMonth->get_result()->fetch_assoc();
$prevMonthRevenue = (float)($resPrevMonth['total'] ?? 0);

if ($prevMonthRevenue > 0) {
    $momGrowth = (($monthRevenue - $prevMonthRevenue) / $prevMonthRevenue) * 100;
} else {
    $momGrowth = $monthRevenue > 0 ? 100.0 : 0.0;
}

// ----------------------------------------------------
// 2. DAILY COLLECTION TREND DATA
// ----------------------------------------------------
$sqlDaily = "SELECT 
                 DATE(s.created_at) AS col_date,
                 COUNT(s.id) AS subs_count,
                 SUM(s.package_price - COALESCE(NULLIF(s.discount, ''), 0)) AS revenue
             FROM subscriptions s
             WHERE s.status != 'cancelled'
               AND s.created_at >= ? AND s.created_at <= ?";
if ($filterByUser) {
    $sqlDaily .= " AND s.active_by = ?";
}
$sqlDaily .= " GROUP BY DATE(s.created_at) ORDER BY col_date ASC";

$stmtDaily = $conn->prepare($sqlDaily);
if ($filterByUser) {
    $stmtDaily->bind_param("ssi", $startDateTime, $endDateTime, $filterUserId);
} else {
    $stmtDaily->bind_param("ss", $startDateTime, $endDateTime);
}
$stmtDaily->execute();
$resDaily = $stmtDaily->get_result();

$dailyDataMap = [];
while ($row = $resDaily->fetch_assoc()) {
    $dailyDataMap[$row['col_date']] = [
        'revenue' => (float)$row['revenue'],
        'count' => (int)$row['subs_count']
    ];
}

// Generate all dates in the range to avoid charts showing empty gaps
$dailyDates = [];
$dailyRevenues = [];
$dailyCounts = [];

$currentDate = $startDate;
while (strtotime($currentDate) <= strtotime($endDate)) {
    $formattedDate = date('Y-m-d', strtotime($currentDate));
    $dailyDates[] = date('d M', strtotime($currentDate));
    if (isset($dailyDataMap[$formattedDate])) {
        $dailyRevenues[] = $dailyDataMap[$formattedDate]['revenue'];
        $dailyCounts[] = $dailyDataMap[$formattedDate]['count'];
    } else {
        $dailyRevenues[] = 0;
        $dailyCounts[] = 0;
    }
    $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
}

// Daily Average calculation
$totalRangeRevenue = array_sum($dailyRevenues);
$daysCount = count($dailyDates);
$dailyAverage = $daysCount > 0 ? ($totalRangeRevenue / $daysCount) : 0;

// ----------------------------------------------------
// 3. MONTHLY REVENUE COMPARISON DATA (Selected vs Prev Year)
// ----------------------------------------------------
// Selected Year Monthly Breakdown
$sqlCurrYearMonthly = "SELECT 
                           MONTH(s.created_at) AS m_num,
                           SUM(s.package_price - COALESCE(NULLIF(s.discount, ''), 0)) AS revenue,
                           COUNT(s.id) AS subs_count
                       FROM subscriptions s
                       WHERE s.status != 'cancelled'
                         AND YEAR(s.created_at) = ?";
if ($filterByUser) {
    $sqlCurrYearMonthly .= " AND s.active_by = ?";
}
$sqlCurrYearMonthly .= " GROUP BY MONTH(s.created_at)";

$stmtCurrYear = $conn->prepare($sqlCurrYearMonthly);
if ($filterByUser) {
    $stmtCurrYear->bind_param("ii", $selectedYear, $filterUserId);
} else {
    $stmtCurrYear->bind_param("i", $selectedYear);
}
$stmtCurrYear->execute();
$resCurrYear = $stmtCurrYear->get_result();
$currYearMonthly = array_fill(1, 12, 0.0);
$currYearCounts  = array_fill(1, 12, 0);
while ($row = $resCurrYear->fetch_assoc()) {
    $currYearMonthly[(int)$row['m_num']] = (float)$row['revenue'];
    $currYearCounts[(int)$row['m_num']]  = (int)$row['subs_count'];
}

// Previous Year Monthly Breakdown
$prevYearVal = $selectedYear - 1;
$sqlPrevYearMonthly = "SELECT 
                           MONTH(s.created_at) AS m_num,
                           SUM(s.package_price - COALESCE(NULLIF(s.discount, ''), 0)) AS revenue
                       FROM subscriptions s
                       WHERE s.status != 'cancelled'
                         AND YEAR(s.created_at) = ?";
if ($filterByUser) {
    $sqlPrevYearMonthly .= " AND s.active_by = ?";
}
$sqlPrevYearMonthly .= " GROUP BY MONTH(s.created_at)";

$stmtPrevYear = $conn->prepare($sqlPrevYearMonthly);
if ($filterByUser) {
    $stmtPrevYear->bind_param("ii", $prevYearVal, $filterUserId);
} else {
    $stmtPrevYear->bind_param("i", $prevYearVal);
}
$stmtPrevYear->execute();
$resPrevYear = $stmtPrevYear->get_result();
$prevYearMonthly = array_fill(1, 12, 0.0);
while ($row = $resPrevYear->fetch_assoc()) {
    $prevYearMonthly[(int)$row['m_num']] = (float)$row['revenue'];
}

$monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

// ----------------------------------------------------
// 4. DAILY COLLECTOR BREAKDOWN (INITIAL RENDERING FOR TODAY)
// ----------------------------------------------------
$todayDate = date('Y-m-d');
$initialCollectorsList = [];
$sqlAllCollectors = "SELECT id, name, user_role FROM users WHERE user_role NOT IN ('customer','super admin')";
if ($filterByUser) {
    $sqlAllCollectors .= " AND id = " . $filterUserId;
}
$allColsRes = $conn->query($sqlAllCollectors);
while ($row = $allColsRes->fetch_assoc()) {
    $initialCollectorsList[(int)$row['id']] = [
        'name' => $row['name'],
        'role' => $row['user_role']
    ];
}

$startDateTimeToday = $todayDate . ' 00:00:00';
$endDateTimeToday   = $todayDate . ' 23:59:59';
$sqlCollectorToday = "SELECT 
                          s.active_by,
                          COUNT(s.id) AS subs_count,
                          SUM(s.package_price - COALESCE(NULLIF(s.discount, ''), 0)) AS user_revenue
                      FROM subscriptions s
                      WHERE s.status != 'cancelled'
                        AND s.created_at >= ? AND s.created_at <= ?";
if ($filterByUser) {
    $sqlCollectorToday .= " AND s.active_by = ?";
}
$sqlCollectorToday .= " GROUP BY s.active_by";

$stmtCollectorToday = $conn->prepare($sqlCollectorToday);
if ($filterByUser) {
    $stmtCollectorToday->bind_param("ssi", $startDateTimeToday, $endDateTimeToday, $filterUserId);
} else {
    $stmtCollectorToday->bind_param("ss", $startDateTimeToday, $endDateTimeToday);
}
$stmtCollectorToday->execute();
$dbCollectionsToday = $stmtCollectorToday->get_result()->fetch_all(MYSQLI_ASSOC);

$collectionsMapToday = [];
foreach ($dbCollectionsToday as $row) {
    $userId = $row['active_by'] !== null ? (int)$row['active_by'] : 0;

    if ($userId !== 0 && !isset($initialCollectorsList[$userId])) {
        $userQuery = $conn->query("SELECT name, user_role FROM users WHERE id = $userId");
        if ($userQuery && $userRow = $userQuery->fetch_assoc()) {
            $initialCollectorsList[$userId] = [
                'name' => $userRow['name'],
                'role' => $userRow['user_role']
            ];
        } else {
            $initialCollectorsList[$userId] = [
                'name' => 'Unknown User (ID ' . $userId . ')',
                'role' => 'manager'
            ];
        }
    } else if ($userId === 0 && !isset($initialCollectorsList[0])) {
        $initialCollectorsList[0] = [
            'name' => 'System / Auto-activated',
            'role' => 'system'
        ];
    }

    $collectionsMapToday[$userId] = [
        'subs_count' => (int)$row['subs_count'],
        'user_revenue' => (float)$row['user_revenue']
    ];
}

$collectors = [];
foreach ($initialCollectorsList as $colId => $colData) {
    $subsCount = 0;
    $revenue = 0.0;
    if (isset($collectionsMapToday[$colId])) {
        $subsCount = $collectionsMapToday[$colId]['subs_count'];
        $revenue   = $collectionsMapToday[$colId]['user_revenue'];
    }
    $collectors[] = [
        'collector_id'   => $colId,
        'collector_name' => $colData['name'],
        'collector_role' => $colData['role'],
        'subs_count'     => $subsCount,
        'user_revenue'   => $revenue
    ];
}

usort($collectors, function ($a, $b) {
    return $b['user_revenue'] <=> $a['user_revenue'];
});
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="./assets/favicon_io/favicon.ico">
    <?php $appName = get_env_value('APP_NAME') ?: 'Pakistan Cable'; ?>
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> - Revenue Reports</title>
    <!-- header links -->
    <?php include "./include/headerLinks.php" ?>
    <!-- ApexCharts (Local) -->
    <script src="node_modules/apexcharts/dist/apexcharts.min.js"></script>
    <link rel="stylesheet" href="assets/css/datePicker.css">
    <!-- For Excel Export (Local) -->
    <script src="node_modules/xlsx/dist/xlsx.full.min.js"></script>

    <!-- For PDF Export (Local) -->
    <script src="node_modules/jspdf/dist/jspdf.umd.min.js"></script>
    <script src="node_modules/jspdf-autotable/dist/jspdf.plugin.autotable.min.js"></script>
</head>

<body class="bg-[#f3f4f4] text-gray-800 dark:text-gray-200" style="overflow-x:hidden">
    <!-- ======================================== -->
    <!-- PAGE LOADER - Include right after body -->
    <!-- ======================================== -->
    <?php include "./include/loader.php"; ?>
    <style>
        /* Export button animations */
        #exportExcelBtn,
        #exportPdfBtn {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        #exportExcelBtn:hover,
        #exportPdfBtn:hover {
            transform: translateY(-2px);
        }

        #exportExcelBtn:active,
        #exportPdfBtn:active {
            transform: translateY(0px);
        }

        #exportExcelBtn::after,
        #exportPdfBtn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        #exportExcelBtn:active::after,
        #exportPdfBtn:active::after {
            width: 300px;
            height: 300px;
        }

        /* Modal animation */
        #exportModal .transform {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Responsive adjustments */
        @media (max-width: 640px) {

            #exportExcelBtn span,
            #exportPdfBtn span {
                display: none;
            }

            #exportExcelBtn,
            #exportPdfBtn {
                padding: 0.5rem 0.75rem;
            }
        }
    </style>
    <div class="flex flex-col min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 dark:from-slate-900 dark:to-slate-800 overflow-hidden">

        <!-- Overlay for mobile sidebar -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black opacity-0 z-40 hidden lg:hidden transition-opacity duration-300"></div>

        <!-- Sidebar -->
        <?php include "./include/sidebar.php" ?>

        <!-- Main Content Wrapper -->
        <div id="main-content-wrapper" class="flex-1 flex flex-col w-full">

            <!-- Header -->
            <?php include "./include/header.php" ?>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto py-3 px-6 w-full min-h-screen">
                <!-- Breadcrumbs -->
                <?php include "./include/breadcrumbs.php"; ?>

                <div class="animate-fade-in-up">
                    <!-- Page Header -->
                    <div class="flex flex-wrap justify-between items-start gap-4 mb-6">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                                <i data-lucide="bar-chart-3" class="w-8 h-8 text-blue-500"></i>
                                <span>Revenue Reports</span>
                            </h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Visualize collections trends, collector statistics, and comparisons.
                            </p>
                        </div>

                        <?php if ($isAdmin || $isManager): ?>
                        <div class="w-full sm:w-auto">
                            <?php if ($isManager): ?>
                                <div class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/30 rounded-xl">
                                    <i data-lucide="shield-check" class="w-4 h-4 text-indigo-500"></i>
                                    <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300">Viewing: My Records Only</span>
                                </div>
                            <?php elseif ($selectedManagerId > 0): ?>
                                <div class="inline-flex items-center gap-2 px-4 py-2 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 rounded-xl">
                                    <i data-lucide="user-check" class="w-4 h-4 text-amber-500"></i>
                                    <span class="text-sm font-medium text-amber-700 dark:text-amber-300">Filtered: <?= htmlspecialchars($selectedManagerName) ?></span>
                                </div>
                            <?php else: ?>
                                <div class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/30 rounded-xl">
                                    <i data-lucide="users" class="w-4 h-4 text-emerald-500"></i>
                                    <span class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Viewing: All Collectors</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Export Buttons -->
                        <div class="flex items-center gap-2 flex-wrap">
                            <button id="exportExcelBtn"
                                class="px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white rounded-xl transition-all text-sm font-medium flex items-center gap-2 shadow-md hover:shadow-lg">
                                <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                                <span>Export Excel</span>
                            </button>
                            <button id="exportPdfBtn"
                                class="px-4 py-2.5 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white rounded-xl transition-all text-sm font-medium flex items-center gap-2 shadow-md hover:shadow-lg">
                                <i data-lucide="file-text" class="w-4 h-4"></i>
                                <span>Export PDF</span>
                            </button>
                        </div>
                    </div>

                    <!-- Filters Panel -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-6 mb-6 border border-gray-100 dark:border-gray-700/50">
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center gap-2">
                            <i data-lucide="sliders" class="w-4 h-4 text-blue-500"></i>
                            <span>Interactive Filters</span>
                        </h2>
                        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 <?= $isAdmin ? 'lg:grid-cols-5' : 'lg:grid-cols-4' ?> gap-4 items-end">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Report Year</label>
                                <select id="filter-year" name="year" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm text-gray-800 dark:text-gray-200 transition-colors">
                                    <?php foreach ($years as $yr): ?>
                                        <option value="<?= $yr ?>" <?= $yr === $selectedYear ? 'selected' : '' ?>>Year: <?= $yr ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Report Month</label>
                                <select id="filter-month" name="month" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm text-gray-800 dark:text-gray-200 transition-colors">
                                    <?php foreach ($monthNames as $idx => $mName): $mVal = $idx + 1; ?>
                                        <option value="<?= $mVal ?>" <?= $mVal === $selectedMonth ? 'selected' : '' ?>><?= $mName ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Start Date (Daily Trend)</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="far fa-calendar text-gray-400 text-xs"></i>
                                    </div>
                                    <input type="text" id="startDateDisplay" readonly
                                        class="block w-full pl-9 pr-10 py-2 text-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm cursor-pointer transition-colors"
                                        placeholder="Choose start date">
                                    <button id="clearStartDateBtn" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors focus:outline-none invisible" type="button" aria-label="Clear date">
                                        <i class="fas fa-times-circle text-xs"></i>
                                    </button>
                                </div>
                                <input type="hidden" id="startDateHidden" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">End Date (Daily Trend)</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="far fa-calendar text-gray-400 text-xs"></i>
                                    </div>
                                    <input type="text" id="endDateDisplay" readonly
                                        class="block w-full pl-9 pr-10 py-2 text-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm cursor-pointer transition-colors"
                                        placeholder="Choose end date">
                                    <button id="clearEndDateBtn" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors focus:outline-none invisible" type="button" aria-label="Clear date">
                                        <i class="fas fa-times-circle text-xs"></i>
                                    </button>
                                </div>
                                <input type="hidden" id="endDateHidden" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
                            </div>

                            <?php if ($isAdmin): ?>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider">Filter by Collector</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="user" class="w-3.5 h-3.5 text-gray-400"></i>
                                    </div>
                                    <select id="filter-manager" name="filter_manager" class="w-full pl-9 pr-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm text-gray-800 dark:text-gray-200 transition-colors">
                                        <option value="0" <?= $selectedManagerId === 0 ? 'selected' : '' ?>>All Collectors</option>
                                        <?php foreach ($allManagersList as $mgr): ?>
                                            <option value="<?= (int)$mgr['id'] ?>" <?= $selectedManagerId === (int)$mgr['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($mgr['name']) ?> (<?= ucfirst($mgr['user_role']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="sm:col-span-2 lg:col-span-<?= $isAdmin ? '5' : '4' ?> flex justify-end gap-3 mt-2">
                                <a href="revenue_reports.php" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-xl transition-all text-sm font-medium flex items-center gap-1.5 shadow-sm">
                                    <i data-lucide="refresh-cw" class="w-4 h-4"></i> Reset
                                </a>
                                <button type="submit" class="px-5 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl transition-all text-sm font-medium flex items-center gap-1.5 shadow-md hover:shadow-lg">
                                    <i data-lucide="filter" class="w-4 h-4"></i> Apply Filter
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Metrics Summary Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <!-- Card 1: Monthly Total Revenue -->
                        <div class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-5 shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100 dark:border-gray-700/50">
                            <div class="absolute top-0 right-0 w-24 h-24 bg-blue-500/10 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-700"></div>
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 rounded-xl bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400">
                                    <i data-lucide="calendar" class="w-6 h-6"></i>
                                </div>
                                <span class="text-xs font-semibold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-500/10 px-2 py-1 rounded-full">
                                    <?= $monthNames[$selectedMonth - 1] ?> <?= $selectedYear ?>
                                </span>
                            </div>
                            <h3 class="text-gray-500 dark:text-gray-400 text-xs font-medium uppercase tracking-wider mb-1">
                                Monthly Collection
                            </h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                                Rs. <?= number_format($monthRevenue, 0) ?>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <i data-lucide="plus-circle" class="w-3.5 h-3.5 text-blue-400"></i>
                                <span><?= $monthSubsCount ?> subscriptions activated</span>
                            </p>
                        </div>

                        <!-- Card 2: Yearly Total Revenue -->
                        <div class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-5 shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100 dark:border-gray-700/50">
                            <div class="absolute top-0 right-0 w-24 h-24 bg-purple-500/10 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-700"></div>
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 rounded-xl bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400">
                                    <i data-lucide="landmark" class="w-6 h-6"></i>
                                </div>
                                <span class="text-xs font-semibold text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-500/10 px-2 py-1 rounded-full">
                                    Year <?= $selectedYear ?>
                                </span>
                            </div>
                            <h3 class="text-gray-500 dark:text-gray-400 text-xs font-medium uppercase tracking-wider mb-1">
                                Yearly Collection
                            </h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                                Rs. <?= number_format($yearRevenue, 0) ?>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Total recovery across selected year
                            </p>
                        </div>

                        <!-- Card 3: Daily Average in range -->
                        <div class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-5 shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100 dark:border-gray-700/50">
                            <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-500/10 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-700"></div>
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                    <i data-lucide="trending-up" class="w-6 h-6"></i>
                                </div>
                                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 px-2 py-1 rounded-full">
                                    Daily Trend
                                </span>
                            </div>
                            <h3 class="text-gray-500 dark:text-gray-400 text-xs font-medium uppercase tracking-wider mb-1">
                                Daily Average
                            </h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                                Rs. <?= number_format($dailyAverage, 0) ?>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Average collection in filtered date range
                            </p>
                        </div>

                        <!-- Card 4: MoM Growth -->
                        <div class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-5 shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100 dark:border-gray-700/50">
                            <div class="absolute top-0 right-0 w-24 h-24 bg-indigo-500/10 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-700"></div>
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                                    <i data-lucide="percent" class="w-6 h-6"></i>
                                </div>
                                <span class="text-xs font-semibold <?= $momGrowth >= 0 ? 'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-950/30' : 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-950/30' ?> px-2 py-1 rounded-full">
                                    <?= ($momGrowth >= 0 ? '+' : '') . number_format($momGrowth, 1) ?>%
                                </span>
                            </div>
                            <h3 class="text-gray-500 dark:text-gray-400 text-xs font-medium uppercase tracking-wider mb-1">
                                MoM Growth Rate
                            </h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                                <?= ($momGrowth >= 0 ? '+' : '') . number_format($momGrowth, 1) ?>%
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Compared to Rs. <?= number_format($prevMonthRevenue, 0) ?> (last month)
                            </p>
                        </div>
                    </div>

                    <!-- Charts Grid -->
                    <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 mb-4">
                        <!-- Chart 1: Monthly Comparison -->
                        <div class="xl:col-span-5 bg-white dark:bg-gray-800 rounded-2xl shadow-md p-3 border border-gray-100 dark:border-gray-700/50 min-w-0">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                                    <i data-lucide="columns-2" class="w-5 h-5 text-indigo-500"></i>
                                    <span>Monthly Comparison</span>
                                </h3>
                                <div class="text-xs text-gray-400 dark:text-gray-500 font-medium">
                                    Year <?= $selectedYear ?> vs <?= $selectedYear - 1 ?>
                                </div>
                            </div>
                            <div id="monthly-comparison-chart" class="w-full"></div>
                        </div>
                        <!-- Chart 2: Daily Area Trend -->
                        <div class="xl:col-span-7 bg-white dark:bg-gray-800 rounded-2xl shadow-md p-6 border border-gray-100 dark:border-gray-700/50 min-w-0">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                                    <i data-lucide="activity" class="w-5 h-5 text-blue-500"></i>
                                    <span>Daily Collection Trend</span>
                                </h3>
                                <div class="text-xs text-gray-400 dark:text-gray-500 font-medium">
                                    <?= $displayStartDate ?> to <?= $displayEndDate ?>
                                </div>
                            </div>
                            <div id="daily-trend-chart" class="w-full"></div>
                        </div>
                    </div>

                    <!-- Data Tables Grid -->
                    <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
                        <!-- Daily Collector List -->
                        <div class="xl:col-span-7 bg-white dark:bg-gray-800 rounded-2xl shadow-md p-6 border border-gray-100 dark:border-gray-700/50 overflow-hidden flex flex-col">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                                        <i data-lucide="users-2" class="w-5 h-5 text-blue-500"></i>
                                        <span>Daily Collectors Breakdown</span>
                                    </h3>
                                    <p class="text-xs text-gray-400 mt-1">Single day breakdown of all collectors</p>
                                </div>

                                <!-- Day Navigation -->
                                <div class="flex items-center gap-2 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-700 p-1.5 rounded-xl shadow-inner">
                                    <button id="prev-day-btn" class="w-8 h-8 rounded-lg bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 flex items-center justify-center transition-all text-gray-600 dark:text-gray-300 shadow-sm focus:outline-none" type="button" aria-label="Previous day">
                                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                                    </button>
                                    <span id="collector-date-display" class="px-3 text-sm font-bold text-gray-700 dark:text-gray-200 min-w-[100px] text-center whitespace-nowrap">
                                        <?= date('d-M-Y') ?>
                                    </span>
                                    <button id="next-day-btn" class="w-8 h-8 rounded-lg bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 flex items-center justify-center transition-all text-gray-600 dark:text-gray-300 shadow-sm focus:outline-none" type="button" aria-label="Next day">
                                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="overflow-x-auto relative flex-1">
                                <table id="collectors-table" class="w-full text-left border-collapse text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-100 dark:border-gray-700 text-gray-400 dark:text-gray-500 font-semibold uppercase tracking-wider text-[11px]">
                                            <th class="py-3 px-4">Collector Name</th>
                                            <th class="py-3 px-4">Role</th>
                                            <th class="py-3 px-4 text-center">Subscriptions</th>
                                            <th class="py-3 px-4 text-right">Amount Collected</th>
                                            <th class="py-3 px-4 text-center">Details</th>
                                        </tr>
                                    </thead>
                                    <tbody id="collectors-table-body" class="divide-y divide-gray-100 dark:divide-gray-700/50 text-gray-700 dark:text-gray-300 transition-opacity duration-200">
                                        <?php if (empty($collectors)): ?>
                                            <tr>
                                                <td colspan="5" class="py-8 text-center text-gray-400 dark:text-gray-500 italic">
                                                    No active collectors found.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($collectors as $row): ?>
                                                <tr class="collector-row hover:bg-blue-50/40 dark:hover:bg-blue-900/15 transition-colors cursor-pointer group"
                                                    data-collector-id="<?= (int)($row['collector_id'] ?? 0) ?>"
                                                    data-collector-date="<?= date('Y-m-d') ?>"
                                                    title="Click to view subscriptions">
                                                    <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">
                                                        <?= htmlspecialchars($row['collector_name']) ?>
                                                    </td>
                                                    <td class="py-3 px-4">
                                                        <?php
                                                        $roleClass = 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
                                                        $roleName  = ucfirst($row['collector_role']);
                                                        if ($row['collector_role'] === 'super admin') {
                                                            $roleClass = 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300';
                                                            $roleName = 'Super Admin';
                                                        } elseif ($row['collector_role'] === 'admin') {
                                                            $roleClass = 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
                                                        } elseif ($row['collector_role'] === 'manager') {
                                                            $roleClass = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300';
                                                        } elseif ($row['collector_role'] === 'system') {
                                                            $roleClass = 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
                                                        }
                                                        ?>
                                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full <?= $roleClass ?>">
                                                            <?= $roleName ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-3 px-4 text-center font-bold text-blue-600 dark:text-blue-400">
                                                        <?= $row['subs_count'] ?>
                                                    </td>
                                                    <td class="py-3 px-4 text-right font-bold text-gray-900 dark:text-white">
                                                        Rs. <?= number_format($row['user_revenue'], 0) ?>
                                                    </td>
                                                    <td class="py-3 px-4 text-center">
                                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-500 dark:text-blue-400 group-hover:bg-blue-100 dark:group-hover:bg-blue-800/40 transition-colors">
                                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Month on Month Comparison Details -->
                        <div class="xl:col-span-5 bg-white dark:bg-gray-800 rounded-2xl shadow-md p-6 border border-gray-100 dark:border-gray-700/50 overflow-hidden flex flex-col">
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2 mb-4">
                                <i data-lucide="git-compare" class="w-5 h-5 text-indigo-500"></i>
                                <span>MoM Growth Details (<?= $selectedYear ?>)</span>
                            </h3>
                            <div class="overflow-x-auto relative flex-1">
                                <table class="w-full text-left border-collapse text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-100 dark:border-gray-700 text-gray-400 dark:text-gray-500 font-semibold uppercase tracking-wider text-[11px]">
                                            <th class="py-3 px-2">Month</th>
                                            <th class="py-3 px-2 text-right">Selected (<?= $selectedYear ?>)</th>
                                            <th class="py-3 px-2 text-right">Previous (<?= $selectedYear - 1 ?>)</th>
                                            <th class="py-3 px-2 text-right">Growth %</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50 text-gray-700 dark:text-gray-300">
                                        <?php for ($m = 1; $m <= 12; $m++):
                                            $currRev = $currYearMonthly[$m];
                                            $prevRev = $prevYearMonthly[$m];
                                            $diff = $currRev - $prevRev;

                                            if ($prevRev > 0) {
                                                $growthPct = ($diff / $prevRev) * 100;
                                            } else {
                                                $growthPct = $currRev > 0 ? 100.0 : 0.0;
                                            }

                                            $isCurrentActive = ((int)date('Y') === $selectedYear && (int)date('m') === $m);
                                        ?>
                                            <tr class="hover:bg-indigo-50/20 dark:hover:bg-indigo-900/10 transition-colors <?= $isCurrentActive ? 'bg-indigo-50/40 dark:bg-indigo-900/10 border-l-4 border-indigo-500' : '' ?>">
                                                <td class="py-2.5 px-2 font-medium whitespace-nowrap">
                                                    <div class="flex items-center gap-1.5">
                                                        <p>
                                                        <span><?= $monthNames[$m - 1] ?></span>
                                                        <?php if ($isCurrentActive): ?>
                                                            <span class="text-[9px] px-1.5 py-0.5 bg-indigo-500 text-white font-bold rounded-full uppercase tracking-wide animate-pulse">Active</span>
                                                        <?php endif; ?>
                                                        </p>
                                                    </div>
                                                </td>
                                                <td class="py-2.5 px-2 text-right font-bold text-gray-900 dark:text-white">
                                                    Rs. <?= number_format($currRev, 0) ?>
                                                </td>
                                                <td class="py-2.5 px-2 text-right text-gray-500 dark:text-gray-400">
                                                    Rs. <?= number_format($prevRev, 0) ?>
                                                </td>
                                                <td class="py-2.5 px-2 text-right whitespace-nowrap">
                                                    <?php if ($currRev == 0 && $prevRev == 0): ?>
                                                        <span class="text-gray-400 dark:text-gray-600">-</span>
                                                    <?php elseif ($growthPct > 0): ?>
                                                        <span class="text-green-600 dark:text-green-400 font-semibold text-xs flex items-center justify-end gap-0.5">
                                                            <i data-lucide="trending-up" class="w-3 h-3"></i> <?= number_format($growthPct, 0) ?>%
                                                        </span>
                                                    <?php elseif ($growthPct < 0): ?>
                                                        <span class="text-red-600 dark:text-red-400 font-semibold text-xs flex items-center justify-end gap-0.5">
                                                            <i data-lucide="trending-down" class="w-3 h-3"></i> <?= number_format(abs($growthPct), 0) ?>%
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-gray-500 dark:text-gray-400 text-xs">0%</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endfor; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <!-- Footer -->
            <?php include "./include/footer.php" ?>
        </div>
    </div>

    <!-- Collector Detail Modal -->
    <div id="collectorDetailModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm transition-all duration-300 opacity-0 invisible pointer-events-none">
        <div id="collectorDetailPanel" class="bg-white dark:bg-gray-800 w-full max-w-4xl rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-300 scale-95 opacity-0 flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-t-2xl">
                <div>
                    <div id="detailModalTitle" class="text-lg font-bold text-white">Collector Detail</div>
                    <p id="detailModalSubtitle" class="text-xs text-blue-100 mt-0.5"></p>
                </div>
                <button id="closeCollectorDetailModal" class="w-8 h-8 rounded-lg bg-white/20 hover:bg-white/30 flex items-center justify-center text-white transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <!-- Summary bar -->
            <div id="detailModalSummary" class="px-6 py-3 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-100 dark:border-blue-800/30 flex items-center gap-6 text-sm flex-wrap">
                <!-- filled by JS -->
            </div>
            <!-- Loading state -->
            <div id="detailModalLoading" class="flex-1 flex items-center justify-center py-16">
                <div class="flex flex-col items-center gap-3">
                    <div class="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Loading subscriptions...</span>
                </div>
            </div>
            <!-- Table -->
            <div id="detailModalContent" class="flex-1 overflow-y-auto hidden">
                <table class="w-full text-left border-collapse text-sm">
                    <thead class="sticky top-0 bg-gray-50 dark:bg-gray-900 z-10">
                        <tr class="border-b border-gray-200 dark:border-gray-700 text-gray-400 dark:text-gray-500 font-semibold uppercase tracking-wider text-[11px]">
                            <th class="py-3 px-4">#</th>
                            <th class="py-3 px-4">Customer</th>
                            <th class="py-3 px-4">Address</th>
                            <th class="py-3 px-4">Package</th>
                            <th class="py-3 px-4 text-right">Price</th>
                            <th class="py-3 px-4 text-right">Discount</th>
                            <th class="py-3 px-4 text-right">Net</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Time</th>
                            <th class="py-3 px-4">Expiry</th>
                        </tr>
                    </thead>
                    <tbody id="detailModalTableBody" class="divide-y divide-gray-100 dark:divide-gray-700/50 text-gray-700 dark:text-gray-300">
                    </tbody>
                </table>
            </div>
            <!-- Empty state -->
            <div id="detailModalEmpty" class="flex-1 flex items-center justify-center py-16 hidden">
                <p class="text-gray-400 dark:text-gray-500 italic text-sm text-center">No subscriptions found for this collector on this date.</p>
            </div>
            <!-- Footer -->
            <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                <button id="closeCollectorDetailModalFooter" class="px-5 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-xl text-sm font-medium transition-colors">Close</button>
            </div>
        </div>
    </div>

    <!-- Start Date Picker Modal -->
    <div id="startDateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/30 backdrop-blur-sm transition-all duration-300 opacity-0 invisible pointer-events-none">
        <div id="startDatePanel" class="bg-white dark:bg-gray-800 w-full max-w-sm md:max-w-md rounded-2xl shadow-popover overflow-hidden transform transition-all duration-300 scale-95 opacity-0">
            <div class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-600 px-4 py-3 flex items-center justify-between">
                <button id="startDatePrev" class="w-9 h-9 rounded-full hover:bg-gray-100 dark:hover:bg-gray-600 transition flex items-center justify-center text-gray-600 dark:text-gray-300 focus:outline-none" type="button">
                    <i class="fas fa-chevron-left text-sm dark:text-gray-500"></i>
                </button>
                <div class="text-gray-800 dark:text-gray-300 font-semibold text-base md:text-lg" id="startDateMonthYear"></div>
                <button id="startDateNext" class="w-9 h-9 rounded-full hover:bg-gray-100 dark:hover:bg-gray-600 transition flex items-center justify-center text-gray-600 dark:text-gray-300 focus:outline-none" type="button">
                    <i class="fas fa-chevron-right text-sm dark:text-gray-500"></i>
                </button>
            </div>
            <div class="grid grid-cols-7 gap-1 px-4 pt-4 pb-2 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
            </div>
            <div id="startDateGrid" class="grid grid-cols-7 gap-1 px-4 pb-4 pt-1"></div>
            <div class="border-t border-gray-100 dark:border-gray-600 p-3 flex justify-between items-center bg-gray-50/60 dark:bg-gray-800">
                <button id="startDateCancel" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-600 rounded-xl transition" type="button">Cancel</button>
                <button id="startDateToday" class="px-4 py-2 text-sm font-medium bg-blue-50 dark:bg-blue-700/80 dark:text-blue-300 text-blue-700 hover:bg-blue-100 rounded-xl transition" type="button"><i class="far fa-calendar-check mr-1"></i> Today</button>
            </div>
        </div>
    </div>

    <!-- End Date Picker Modal -->
    <div id="endDateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/30 backdrop-blur-sm transition-all duration-300 opacity-0 invisible pointer-events-none">
        <div id="endDatePanel" class="bg-white dark:bg-gray-800 w-full max-w-sm md:max-w-md rounded-2xl shadow-popover overflow-hidden transform transition-all duration-300 scale-95 opacity-0">
            <div class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-600 px-4 py-3 flex items-center justify-between">
                <button id="endDatePrev" class="w-9 h-9 rounded-full hover:bg-gray-100 dark:hover:bg-gray-600 transition flex items-center justify-center text-gray-600 dark:text-gray-300 focus:outline-none" type="button">
                    <i class="fas fa-chevron-left text-sm dark:text-gray-500"></i>
                </button>
                <div class="text-gray-800 dark:text-gray-300 font-semibold text-base md:text-lg" id="endDateMonthYear"></div>
                <button id="endDateNext" class="w-9 h-9 rounded-full hover:bg-gray-100 dark:hover:bg-gray-600 transition flex items-center justify-center text-gray-600 dark:text-gray-300 focus:outline-none" type="button">
                    <i class="fas fa-chevron-right text-sm dark:text-gray-500"></i>
                </button>
            </div>
            <div class="grid grid-cols-7 gap-1 px-4 pt-4 pb-2 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
            </div>
            <div id="endDateGrid" class="grid grid-cols-7 gap-1 px-4 pb-4 pt-1"></div>
            <div class="border-t border-gray-100 dark:border-gray-600 p-3 flex justify-between items-center bg-gray-50/60 dark:bg-gray-800">
                <button id="endDateCancel" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-600 rounded-xl transition" type="button">Cancel</button>
                <button id="endDateToday" class="px-4 py-2 text-sm font-medium bg-blue-50 dark:bg-blue-700/80 dark:text-blue-300 text-blue-700 hover:bg-blue-100 rounded-xl transition" type="button"><i class="far fa-calendar-check mr-1"></i> Today</button>
            </div>
        </div>
    </div>

    <!-- footer links -->
    <?php include "./include/footerLinks.php" ?>
    <?php include "./include/subscription-modals.php" ?>
    <!-- ApexCharts rendering script -->
    <script type="module">
        import {
            DatePicker
        } from './assets/js/datePicker.js';

        document.addEventListener("DOMContentLoaded", () => {
            // State for the collector date navigation
            let currentCollectorDate = "<?= date('Y-m-d') ?>";

            const prevBtn = document.getElementById('prev-day-btn');
            const nextBtn = document.getElementById('next-day-btn');
            const dateDisplay = document.getElementById('collector-date-display');
            const tableBody = document.getElementById('collectors-table-body');

            function formatDateString(date) {
                const d = new Date(date);
                let month = '' + (d.getMonth() + 1);
                let day = '' + d.getDate();
                const year = d.getFullYear();

                if (month.length < 2) month = '0' + month;
                if (day.length < 2) day = '0' + day;

                return [year, month, day].join('-');
            }

            function fetchCollectorData(dateStr) {
                tableBody.classList.add('opacity-40');
                prevBtn.disabled = true;
                nextBtn.disabled = true;

                fetch(`revenue_reports.php?ajax_collector_date=${dateStr}`)
                    .then(response => response.json())
                    .then(result => {
                        tableBody.classList.remove('opacity-40');
                        prevBtn.disabled = false;
                        nextBtn.disabled = false;

                        if (result.success) {
                            dateDisplay.textContent = result.date;
                            currentCollectorDate = result.raw_date;

                            tableBody.innerHTML = '';
                            if (result.data.length === 0) {
                                tableBody.innerHTML = `
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-gray-400 dark:text-gray-500 italic">
                                            No active collectors found.
                                        </td>
                                    </tr>`;
                            } else {
                                result.data.forEach(row => {
                                    let roleClass = 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
                                    let roleName = row.collector_role.charAt(0).toUpperCase() + row.collector_role.slice(1);

                                    if (row.collector_role === 'super admin') {
                                        roleClass = 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300';
                                        roleName = 'Super Admin';
                                    } else if (row.collector_role === 'admin') {
                                        roleClass = 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
                                    } else if (row.collector_role === 'manager') {
                                        roleClass = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300';
                                    } else if (row.collector_role === 'system') {
                                        roleClass = 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
                                    }

                                    const formattedRevenue = parseFloat(row.user_revenue).toLocaleString();

                                    const tr = document.createElement('tr');
                                    tr.className = 'collector-row hover:bg-blue-50/40 dark:hover:bg-blue-900/15 transition-colors cursor-pointer group';
                                    tr.dataset.collectorId = row.collector_id;
                                    tr.dataset.collectorDate = currentCollectorDate;
                                    tr.title = 'Click to view subscriptions';
                                    tr.innerHTML = `
                                        <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">
                                            ${escapeHtml(row.collector_name)}
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full ${roleClass}">
                                                ${roleName}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center font-bold text-blue-600 dark:text-blue-400">
                                            ${row.subs_count}
                                        </td>
                                        <td class="py-3 px-4 text-right font-bold text-gray-900 dark:text-white">
                                            Rs. ${formattedRevenue}
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-500 dark:text-blue-400 group-hover:bg-blue-100 dark:group-hover:bg-blue-800/40 transition-colors">
                                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                            </span>
                                        </td>
                                    `;
                                    tr.addEventListener('click', () => {
                                        openCollectorDetail(row.collector_id, currentCollectorDate);
                                    });
                                    tableBody.appendChild(tr);
                                });
                                lucide.createIcons();
                            }
                        }
                    })
                    .catch(err => {
                        tableBody.classList.remove('opacity-40');
                        prevBtn.disabled = false;
                        nextBtn.disabled = false;
                        console.error('AJAX Error:', err);
                    });
            }

            function escapeHtml(text) {
                if (!text) return '';
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return text.replace(/[&<>"']/g, function(m) {
                    return map[m];
                });
            }

            // ---- Collector Detail Modal ----
            const detailModal = document.getElementById('collectorDetailModal');
            const detailPanel = document.getElementById('collectorDetailPanel');
            const detailTitle = document.getElementById('detailModalTitle');
            const detailSubtitle = document.getElementById('detailModalSubtitle');
            const detailSummary = document.getElementById('detailModalSummary');
            const detailLoading = document.getElementById('detailModalLoading');
            const detailContent = document.getElementById('detailModalContent');
            const detailEmpty = document.getElementById('detailModalEmpty');
            const detailTableBody = document.getElementById('detailModalTableBody');

            function openCollectorDetail(collectorId, dateStr) {
                if (!collectorId || collectorId === '0') return;
                detailModal.classList.remove('opacity-0','invisible','pointer-events-none');
                detailModal.classList.add('opacity-100');
                detailPanel.classList.remove('scale-95','opacity-0');
                detailPanel.classList.add('scale-100','opacity-100');
                detailLoading.classList.remove('hidden');
                detailContent.classList.add('hidden');
                detailEmpty.classList.add('hidden');
                detailTableBody.innerHTML = '';
                detailSummary.innerHTML = '';
                detailTitle.textContent = 'Loading...';
                detailSubtitle.textContent = '';
                document.body.style.overflow = 'hidden';

                fetch(`revenue_reports.php?ajax_collector_detail=${dateStr}&collector_id=${collectorId}`)
                    .then(r => r.json())
                    .then(result => {
                        detailLoading.classList.add('hidden');
                        if (!result.success) { detailEmpty.classList.remove('hidden'); return; }
                        detailTitle.textContent = result.collector_name + ' — Collections';
                        detailSubtitle.textContent = result.date;
                        detailSummary.innerHTML = `
                            <span class="font-semibold text-blue-700 dark:text-blue-300">${result.records.length} subscription${result.records.length !== 1 ? 's' : ''}</span>
                            <span class="text-gray-400">|</span>
                            <span class="font-bold text-gray-800 dark:text-gray-200">Total: Rs. ${parseFloat(result.total_net).toLocaleString()}</span>`;
                        if (result.records.length === 0) { detailEmpty.classList.remove('hidden'); return; }
                        detailContent.classList.remove('hidden');
                        result.records.forEach((r, i) => {
                            const statusClass = r.status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
                            const tr = document.createElement('tr');
                            tr.className = 'hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors';
                            tr.innerHTML = `
                                <td class="py-2.5 px-4 text-gray-400 text-xs">${i+1}</td>
                                <td class="py-2.5 px-4 font-semibold text-gray-900 dark:text-white">${escapeHtml(r.customer_name)}</td>
                                <td class="py-2.5 px-4 text-gray-500 text-xs">${escapeHtml(r.customer_address)}</td>
                                <td class="py-2.5 px-4 text-gray-700 dark:text-gray-300">${escapeHtml(r.package_name)}</td>
                                <td class="py-2.5 px-4 text-right text-gray-700 dark:text-gray-300">Rs. ${parseFloat(r.package_price).toLocaleString()}</td>
                                <td class="py-2.5 px-4 text-right text-red-500">${r.discount > 0 ? '-Rs. ' + parseFloat(r.discount).toLocaleString() : '-'}</td>
                                <td class="py-2.5 px-4 text-right font-bold text-gray-900 dark:text-white">Rs. ${parseFloat(r.net_amount).toLocaleString()}</td>
                                <td class="py-2.5 px-4"><span class="px-2 py-0.5 text-xs font-medium rounded-full ${statusClass}">${r.status}</span></td>
                                <td class="py-2.5 px-4 text-xs text-gray-500 whitespace-nowrap">${escapeHtml(r.created_at)}</td>
                                <td class="py-2.5 px-4 text-xs text-gray-500 whitespace-nowrap">${escapeHtml(r.expiry_date)}</td>`;
                            detailTableBody.appendChild(tr);
                        });
                        lucide.createIcons();
                    })
                    .catch((err) => { 
                        console.error("Fetch error:", err);
                        detailLoading.classList.add('hidden'); 
                        detailEmpty.classList.remove('hidden'); 
                    });
            }

            function closeCollectorDetail() {
                detailModal.classList.add('opacity-0','invisible','pointer-events-none');
                detailModal.classList.remove('opacity-100');
                detailPanel.classList.add('scale-95','opacity-0');
                detailPanel.classList.remove('scale-100','opacity-100');
                document.body.style.overflow = '';
            }

            document.getElementById('closeCollectorDetailModal').addEventListener('click', closeCollectorDetail);
            document.getElementById('closeCollectorDetailModalFooter').addEventListener('click', closeCollectorDetail);
            detailModal.addEventListener('click', e => { if (e.target === detailModal) closeCollectorDetail(); });

            // PHP-rendered initial rows click handlers
            document.querySelectorAll('.collector-row').forEach(tr => {
                tr.addEventListener('click', () => {
                    openCollectorDetail(tr.dataset.collectorId, tr.dataset.collectorDate);
                });
            });

            prevBtn.addEventListener('click', () => {
                const date = new Date(currentCollectorDate);
                date.setDate(date.getDate() - 1);
                fetchCollectorData(formatDateString(date));
            });

            nextBtn.addEventListener('click', () => {
                const date = new Date(currentCollectorDate);
                date.setDate(date.getDate() + 1);
                fetchCollectorData(formatDateString(date));
            });

            // Read dark mode state from documentElement
            const isDarkMode = document.documentElement.classList.contains('dark');
            const themeMode = isDarkMode ? 'dark' : 'light';
            const gridColor = isDarkMode ? '#334155' : '#e2e8f0';
            const labelColor = isDarkMode ? '#94a3b8' : '#64748b';

            // Daily Revenue Trend Data
            const dailyCategories = <?= json_encode($dailyDates) ?>;
            const dailyRevenues = <?= json_encode($dailyRevenues) ?>;
            const dailyCounts = <?= json_encode($dailyCounts) ?>;

            // Daily Area Chart Options
            const dailyOptions = {
                chart: {
                    type: 'area',
                    height: 320,
                    width: '100%',
                    toolbar: {
                        show: false
                    },
                    fontFamily: "'IBM Plex Sans', sans-serif",
                    zoom: {
                        enabled: false
                    },
                    background: 'transparent'
                },
                theme: {
                    mode: themeMode
                },
                colors: ['#3b82f6'],
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.45,
                        opacityTo: 0.05,
                        stops: [0, 90, 100]
                    }
                },
                dataLabels: {
                    enabled: false
                },
                markers: {
                    size: 4,
                    strokeWidth: 2,
                    hover: {
                        size: 6
                    }
                },
                grid: {
                    borderColor: gridColor,
                    strokeDashArray: 4
                },
                series: [{
                    name: 'Revenue Collected',
                    data: dailyRevenues
                }],
                xaxis: {
                    categories: dailyCategories,
                    labels: {
                        style: {
                            colors: labelColor,
                            fontSize: '12px'
                        }
                    },
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: labelColor,
                            fontSize: '12px'
                        },
                        formatter: function(value) {
                            return "Rs. " + value.toLocaleString();
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(value, {
                            seriesIndex,
                            dataPointIndex,
                            w
                        }) {
                            const subCount = dailyCounts[dataPointIndex];
                            return "Rs. " + value.toLocaleString() + " (" + subCount + " Subscriptions)";
                        }
                    }
                }
            };

            const dailyChart = new ApexCharts(document.querySelector("#daily-trend-chart"), dailyOptions);
            dailyChart.render();


            // Monthly Revenue Comparison Data
            const monthlyCategories = <?= json_encode($monthNames) ?>;
            const currYearMonthly = <?= json_encode(array_values($currYearMonthly)) ?>;
            const prevYearMonthly = <?= json_encode(array_values($prevYearMonthly)) ?>;

            // Monthly Bar Comparison Options
            const monthlyOptions = {
                chart: {
                    type: 'bar',
                    height: 320,
                    width: '100%',
                    toolbar: {
                        show: false
                    },
                    fontFamily: "'IBM Plex Sans', sans-serif",
                    background: 'transparent'
                },
                theme: {
                    mode: themeMode
                },
                colors: ['#3b82f6', '#8b5cf6'],
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        borderRadius: 4,
                        endingShape: 'rounded'
                    },
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    show: true,
                    width: 2,
                    colors: ['transparent']
                },
                grid: {
                    borderColor: gridColor,
                    strokeDashArray: 4
                },
                series: [{
                        name: '<?= $selectedYear ?> Collections',
                        data: currYearMonthly
                    },
                    {
                        name: '<?= $selectedYear - 1 ?> Collections',
                        data: prevYearMonthly
                    }
                ],
                xaxis: {
                    categories: monthlyCategories,
                    labels: {
                        style: {
                            colors: labelColor,
                            fontSize: '12px'
                        }
                    },
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: labelColor,
                            fontSize: '12px'
                        },
                        formatter: function(value) {
                            return "Rs. " + value.toLocaleString();
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return "Rs. " + val.toLocaleString();
                        }
                    }
                },
                legend: {
                    labels: {
                        colors: labelColor
                    }
                }
            };

            const monthlyChart = new ApexCharts(document.querySelector("#monthly-comparison-chart"), monthlyOptions);
            monthlyChart.render();


            // Dynamic Theme Sync with ApexCharts
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.attributeName === 'class') {
                        const isDarkNow = document.documentElement.classList.contains('dark');
                        const updatedTheme = isDarkNow ? 'dark' : 'light';
                        const updatedGrid = isDarkNow ? '#334155' : '#e2e8f0';
                        const updatedLabel = isDarkNow ? '#94a3b8' : '#64748b';

                        dailyChart.updateOptions({
                            theme: {
                                mode: updatedTheme
                            },
                            grid: {
                                borderColor: updatedGrid
                            },
                            xaxis: {
                                labels: {
                                    style: {
                                        colors: updatedLabel
                                    }
                                }
                            },
                            yaxis: {
                                labels: {
                                    style: {
                                        colors: updatedLabel
                                    }
                                }
                            }
                        });

                        monthlyChart.updateOptions({
                            theme: {
                                mode: updatedTheme
                            },
                            grid: {
                                borderColor: updatedGrid
                            },
                            xaxis: {
                                labels: {
                                    style: {
                                        colors: updatedLabel
                                    }
                                }
                            },
                            yaxis: {
                                labels: {
                                    style: {
                                        colors: updatedLabel
                                    }
                                },
                                legend: {
                                    labels: {
                                        colors: updatedLabel
                                    }
                                }
                            }
                        });
                    }
                });
            });
            observer.observe(document.documentElement, {
                attributes: true
            });

            // Initialize custom DatePickers
            const startPicker = new DatePicker({
                inputDisplayId: 'startDateDisplay',
                hiddenInputId: 'startDateHidden',
                clearBtnId: 'clearStartDateBtn',
                modalId: 'startDateModal',
                panelId: 'startDatePanel',
                monthYearId: 'startDateMonthYear',
                daysGridId: 'startDateGrid',
                prevBtnId: 'startDatePrev',
                nextBtnId: 'startDateNext',
                cancelBtnId: 'startDateCancel',
                todayBtnId: 'startDateToday',
            });

            const endPicker = new DatePicker({
                inputDisplayId: 'endDateDisplay',
                hiddenInputId: 'endDateHidden',
                clearBtnId: 'clearEndDateBtn',
                modalId: 'endDateModal',
                panelId: 'endDatePanel',
                monthYearId: 'endDateMonthYear',
                daysGridId: 'endDateGrid',
                prevBtnId: 'endDatePrev',
                nextBtnId: 'endDateNext',
                cancelBtnId: 'endDateCancel',
                todayBtnId: 'endDateToday',
            });

            // Set initial values from PHP variables
            startPicker.setValue("<?= $startDate ?>");
            endPicker.setValue("<?= $endDate ?>");

            // Interactive Auto-fill dates based on selected Year/Month dropdowns
            const yearSelector = document.getElementById('filter-year');
            const monthSelector = document.getElementById('filter-month');

            function syncDateInputs() {
                const year = yearSelector.value;
                const month = String(monthSelector.value).padStart(2, '0');

                // Get first and last day of that month
                const firstDay = `${year}-${month}-01`;
                const lastDayDate = new Date(year, month, 0);
                const lastDay = `${year}-${month}-${String(lastDayDate.getDate()).padStart(2, '0')}`;

                startPicker.setValue(firstDay);
                endPicker.setValue(lastDay);
            }

            // Sync dates on year or month change
            yearSelector.addEventListener('change', syncDateInputs);
            monthSelector.addEventListener('change', syncDateInputs);

            // Force redraw after page settles to fix initial layout sizing
            setTimeout(() => {
                window.dispatchEvent(new Event('resize'));
            }, 350);
        });
        // ============================================
        // EXPORT FUNCTIONS
        // ============================================

        // Get all current data for export
        function getExportData() {
            // Get current filter values
            const year = document.getElementById('filter-year').value;
            const month = document.getElementById('filter-month').value;
            const startDate = document.getElementById('startDateHidden').value;
            const endDate = document.getElementById('endDateHidden').value;
            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

            // Get collector table data
            const collectorRows = [];
            const tableRows = document.querySelectorAll('#collectors-table-body tr');
            tableRows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length === 4) {
                    collectorRows.push({
                        name: cells[0].textContent.trim(),
                        role: cells[1].textContent.trim(),
                        subscriptions: parseInt(cells[2].textContent.trim()),
                        revenue: cells[3].textContent.replace(/Rs\.?\s*/i, '').trim() // Remove "Rs." and commas
                    });
                }
                console.log('Collector Data for Export:', collectorRows);
            });
            // Get monthly comparison data from the table
            const monthlyData = [];
            const monthlyRows = document.querySelectorAll('.xl\\:col-span-5 tbody tr');
            monthlyRows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length === 4) {
                    const monthText = cells[0].textContent.trim().replace('Curent Month', '').trim();
                    monthlyData.push({
                        month: monthText,
                        currentYear: cells[1].textContent.replace(/Rs\.?\s*/i, '').trim(),
                        previousYear: cells[2].textContent.replace(/Rs\.?\s*/i, '').trim(),
                        growth: cells[3].textContent.trim()
                    });
                }
            });

            // Get metrics data from cards
            const metrics = {};
            const cardValues = document.querySelectorAll('.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-4 .group');
            if (cardValues.length >= 4) {
                const values = [];
                cardValues.forEach(card => {
                    const value = card.querySelector('.text-2xl.font-bold')?.textContent || '';
                    const label = card.querySelector('.text-gray-500.dark\\:text-gray-400.text-xs.font-medium')?.textContent || '';
                    values.push({
                        label: label.trim(),
                        value: value.trim()
                    });
                });
                metrics.monthly = values[0] || {};
                metrics.yearly = values[1] || {};
                metrics.dailyAvg = values[2] || {};
                metrics.momGrowth = values[3] || {};
            }

            // Get chart data (daily trend)
            const dailyCategories = <?= json_encode($dailyDates) ?>;
            const dailyRevenues = <?= json_encode($dailyRevenues) ?>;
            const dailyCounts = <?= json_encode($dailyCounts) ?>;

            const dailyData = dailyCategories.map((date, index) => ({
                date: date,
                revenue: dailyRevenues[index] || 0,
                subscriptions: dailyCounts[index] || 0
            }));

            return {
                filters: {
                    year,
                    month,
                    startDate,
                    endDate,
                    monthName: monthNames[parseInt(month) - 1]
                },
                metrics,
                dailyData,
                collectorData: collectorRows,
                monthlyComparison: monthlyData,
                summary: {
                    totalRevenue: collectorRows.reduce((sum, row) => sum + row.revenue, 0),
                    totalSubscriptions: collectorRows.reduce((sum, row) => sum + row.subscriptions, 0),
                    totalCollectors: collectorRows.length
                }
            };
        }

        // Export to Excel
        function exportToExcel() {
            const data = getExportData();

            // Create workbook
            const wb = XLSX.utils.book_new();
            wb.Props = {
                Title: "Revenue Report",
                Subject: "Revenue Report",
                Author: "Pakistan Cable",
                CreatedDate: new Date()
            };

            // 1. Summary Sheet
            const summaryData = [
                ['REVENUE REPORT SUMMARY'],
                [''],
                ['Filter Parameters'],
                ['Year', data.filters.year],
                ['Month', data.filters.monthName],
                ['Date Range', `${data.filters.startDate} to ${data.filters.endDate}`],
                [''],
                ['Key Metrics'],
                ['Metric', 'Value'],
                ['Monthly Collection', data.metrics.monthly?.value || ''],
                ['Yearly Collection', data.metrics.yearly?.value || ''],
                ['Daily Average', data.metrics.dailyAvg?.value || ''],
                ['MoM Growth', data.metrics.momGrowth?.value || ''],
                [''],
                ['Total Revenue', `Rs. ${data.summary.totalRevenue.toLocaleString()}`],
                ['Total Subscriptions', data.summary.totalSubscriptions],
                ['Active Collectors', data.summary.totalCollectors]
            ];
            const ws1 = XLSX.utils.aoa_to_sheet(summaryData);
            ws1['!cols'] = [{
                wch: 20
            }, {
                wch: 30
            }];
            XLSX.utils.book_append_sheet(wb, ws1, "Summary");

            // 2. Daily Trend Sheet
            const dailySheetData = [
                ['DAILY REVENUE TREND'],
                [''],
                ['Date', 'Revenue (Rs.)', 'Subscriptions']
            ];
            data.dailyData.forEach(item => {
                dailySheetData.push([item.date, item.revenue, item.subscriptions]);
            });
            const ws2 = XLSX.utils.aoa_to_sheet(dailySheetData);
            ws2['!cols'] = [{
                wch: 15
            }, {
                wch: 20
            }, {
                wch: 15
            }];
            XLSX.utils.book_append_sheet(wb, ws2, "Daily Trend");

            // 3. Collector Breakdown Sheet
            const collectorSheetData = [
                ['COLLECTOR BREAKDOWN'],
                [''],
                ['Collector Name', 'Role', 'Subscriptions', 'Amount Collected (Rs.)']
            ];
            data.collectorData.forEach(item => {
                collectorSheetData.push([item.name, item.role, item.subscriptions, item.revenue]);
            });
            const ws3 = XLSX.utils.aoa_to_sheet(collectorSheetData);
            ws3['!cols'] = [{
                wch: 30
            }, {
                wch: 15
            }, {
                wch: 15
            }, {
                wch: 25
            }];
            XLSX.utils.book_append_sheet(wb, ws3, "Collectors");

            // 4. Monthly Comparison Sheet
            const monthlySheetData = [
                ['MONTHLY COMPARISON'],
                [''],
                ['Month', `Year ${data.filters.year} (Rs.)`, `Year ${data.filters.year - 1} (Rs.)`, 'Growth %']
            ];
            data.monthlyComparison.forEach(item => {
                monthlySheetData.push([item.month, item.currentYear, item.previousYear, item.growth]);
            });
            const ws4 = XLSX.utils.aoa_to_sheet(monthlySheetData);
            ws4['!cols'] = [{
                wch: 15
            }, {
                wch: 25
            }, {
                wch: 25
            }, {
                wch: 15
            }];
            XLSX.utils.book_append_sheet(wb, ws4, "Monthly Comparison");

            // Generate Excel file
            const fileName = `Revenue_Report_${data.filters.startDate}_to_${data.filters.endDate}.xlsx`;
            XLSX.writeFile(wb, fileName);
        }

        // Export to PDF
        function exportToPDF() {
            const data = getExportData();
            const {
                jsPDF
            } = window.jspdf;
            const doc = new jsPDF('p', 'mm', 'a4');
            const pageWidth = doc.internal.pageSize.getWidth();
            const margin = 15;

            // Helper function to add a new page if needed
            function checkPageHeight(y, needed = 50) {
                if (y > doc.internal.pageSize.getHeight() - 20) {
                    doc.addPage();
                    return 20;
                }
                return y;
            }

            let y = 20;

            // Title
            doc.setFontSize(20);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(37, 99, 235);
            doc.text('Revenue Report', pageWidth / 2, y, {
                align: 'center'
            });
            y += 8;

            // Subtitle with filters
            doc.setFontSize(11);
            doc.setFont('helvetica', 'normal');
            doc.setTextColor(100, 116, 139);
            doc.text(`Period: ${data.filters.startDate} to ${data.filters.endDate}`, pageWidth / 2, y, {
                align: 'center'
            });
            y += 8;
            doc.text(`Year: ${data.filters.year} | Month: ${data.filters.monthName}`, pageWidth / 2, y, {
                align: 'center'
            });
            y += 12;

            // Separator line
            doc.setDrawColor(200, 200, 200);
            doc.line(margin, y, pageWidth - margin, y);
            y += 8;

            // ============ METRICS SECTION ============
            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(30, 41, 59);
            doc.text('Key Metrics', margin, y);
            y += 6;

            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.setTextColor(75, 85, 99);

            // Metrics in 2 columns
            const metricKeys = ['Monthly Collection', 'Yearly Collection', 'Daily Average', 'MoM Growth'];
            const metricValues = [
                data.metrics.monthly?.value || '-',
                data.metrics.yearly?.value || '-',
                data.metrics.dailyAvg?.value || '-',
                data.metrics.momGrowth?.value || '-'
            ];

            const colWidth = (pageWidth - 2 * margin) / 2;
            const rowHeight = 8;

            metricKeys.forEach((key, index) => {
                const col = index % 2;
                const row = Math.floor(index / 2);
                const x = margin + (col * colWidth);
                const yPos = y + (row * rowHeight);

                doc.setFont('helvetica', 'bold');
                doc.setTextColor(30, 41, 59);
                doc.text(key + ':', x, yPos);

                const valueX = x + 40;
                doc.setFont('helvetica', 'normal');
                doc.setTextColor(37, 99, 235);
                doc.text(metricValues[index] || '-', valueX, yPos);
            });

            y += 24;

            // ============ COLLECTOR BREAKDOWN ============
            y = checkPageHeight(y, 50);

            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(30, 41, 59);
            doc.text('Collector Breakdown', margin, y);
            y += 6;

            if (data.collectorData.length > 0) {
                const collectorTableData = data.collectorData.map(item => [
                    item.name,
                    item.role,
                    item.subscriptions.toString(),
                    `Rs. ${item.revenue.toString()}`
                ]);

                doc.autoTable({
                    startY: y,
                    head: [
                        ['Collector Name', 'Role', 'Subscriptions', 'Amount']
                    ],
                    body: collectorTableData,
                    theme: 'striped',
                    headStyles: {
                        fillColor: [37, 99, 235],
                        textColor: [255, 255, 255],
                        fontSize: 9,
                        fontStyle: 'bold'
                    },
                    bodyStyles: {
                        fontSize: 9,
                        textColor: [30, 41, 59]
                    },
                    alternateRowStyles: {
                        fillColor: [241, 245, 249]
                    },
                    columnStyles: {
                        0: {
                            cellWidth: 60
                        },
                        1: {
                            cellWidth: 35
                        },
                        2: {
                            cellWidth: 30,
                            halign: 'center'
                        },
                        3: {
                            cellWidth: 45,
                            halign: 'right'
                        }
                    },
                    margin: {
                        left: margin,
                        right: margin
                    }
                });
                y = doc.lastAutoTable.finalY + 8;
            } else {
                doc.setFontSize(10);
                doc.setFont('helvetica', 'italic');
                doc.setTextColor(148, 163, 184);
                doc.text('No collector data available', margin, y);
                y += 8;
            }

            // ============ DAILY TREND ============
            y = checkPageHeight(y, 50);

            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(30, 41, 59);
            doc.text('Daily Revenue Trend', margin, y);
            y += 6;

            if (data.dailyData.length > 0) {
                // Show only last 30 days if too many
                let dailyDisplay = data.dailyData;
                if (dailyDisplay.length > 30) {
                    dailyDisplay = dailyDisplay.slice(-30);
                }

                const dailyTableData = dailyDisplay.map(item => [
                    item.date,
                    `Rs. ${item.revenue.toLocaleString()}`,
                    item.subscriptions.toString()
                ]);

                doc.autoTable({
                    startY: y,
                    head: [
                        ['Date', 'Revenue', 'Subscriptions']
                    ],
                    body: dailyTableData,
                    theme: 'striped',
                    headStyles: {
                        fillColor: [37, 99, 235],
                        textColor: [255, 255, 255],
                        fontSize: 9,
                        fontStyle: 'bold'
                    },
                    bodyStyles: {
                        fontSize: 8,
                        textColor: [30, 41, 59]
                    },
                    alternateRowStyles: {
                        fillColor: [241, 245, 249]
                    },
                    columnStyles: {
                        0: {
                            cellWidth: 30
                        },
                        1: {
                            cellWidth: 50,
                            halign: 'right'
                        },
                        2: {
                            cellWidth: 30,
                            halign: 'center'
                        }
                    },
                    margin: {
                        left: margin,
                        right: margin
                    }
                });
                y = doc.lastAutoTable.finalY + 8;
            } else {
                doc.setFontSize(10);
                doc.setFont('helvetica', 'italic');
                doc.setTextColor(148, 163, 184);
                doc.text('No daily data available', margin, y);
                y += 8;
            }

            // ============ MONTHLY COMPARISON ============
            y = checkPageHeight(y, 50);

            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(30, 41, 59);
            doc.text(`Monthly Comparison (${data.filters.year} vs ${data.filters.year - 1})`, margin, y);
            y += 6;

            if (data.monthlyComparison.length > 0) {
                const monthlyTableData = data.monthlyComparison.map(item => [
                    item.month,
                    `Rs. ${item.currentYear.toLocaleString()}`,
                    `Rs. ${item.previousYear.toLocaleString()}`,
                    item.growth
                ]);

                doc.autoTable({
                    startY: y,
                    head: [
                        ['Month', `Year ${data.filters.year}`, `Year ${data.filters.year - 1}`, 'Growth %']
                    ],
                    body: monthlyTableData,
                    theme: 'striped',
                    headStyles: {
                        fillColor: [37, 99, 235],
                        textColor: [255, 255, 255],
                        fontSize: 9,
                        fontStyle: 'bold'
                    },
                    bodyStyles: {
                        fontSize: 8,
                        textColor: [30, 41, 59]
                    },
                    alternateRowStyles: {
                        fillColor: [241, 245, 249]
                    },
                    columnStyles: {
                        0: {
                            cellWidth: 25
                        },
                        1: {
                            cellWidth: 45,
                            halign: 'right'
                        },
                        2: {
                            cellWidth: 45,
                            halign: 'right'
                        },
                        3: {
                            cellWidth: 30,
                            halign: 'center'
                        }
                    },
                    margin: {
                        left: margin,
                        right: margin
                    }
                });
                y = doc.lastAutoTable.finalY + 8;
            } else {
                doc.setFontSize(10);
                doc.setFont('helvetica', 'italic');
                doc.setTextColor(148, 163, 184);
                doc.text('No monthly comparison data available', margin, y);
                y += 8;
            }

            // ============ FOOTER ============
            y = checkPageHeight(y, 20);

            doc.setFontSize(8);
            doc.setFont('helvetica', 'italic');
            doc.setTextColor(148, 163, 184);
            const footerText = `Generated on ${new Date().toLocaleString()} | Pakistan Cable Revenue Report`;
            doc.text(footerText, pageWidth / 2, doc.internal.pageSize.getHeight() - 10, {
                align: 'center'
            });

            // Save PDF
            const fileName = `Revenue_Report_${data.filters.startDate}_to_${data.filters.endDate}.pdf`;
            doc.save(fileName);
        }

        // ============================================
        // EVENT LISTENERS FOR EXPORT
        // ============================================

        // Direct export buttons
        document.getElementById('exportExcelBtn')?.addEventListener('click', exportToExcel);
        document.getElementById('exportPdfBtn')?.addEventListener('click', exportToPDF);

        // Export modal functionality (if you added the modal)
        const exportModal = document.getElementById('exportModal');
        const exportPanel = document.getElementById('exportPanel');
        const exportModalClose = document.getElementById('exportModalClose');
        const exportModalCancel = document.getElementById('exportModalCancel');
        const exportModalConfirm = document.getElementById('exportModalConfirm');

        // If modal exists, add event listeners
        if (exportModal) {
            // Open modal with options
            function openExportModal() {
                exportModal.classList.remove('opacity-0', 'invisible', 'pointer-events-none');
                exportPanel.classList.remove('scale-95', 'opacity-0');
                document.body.style.overflow = 'hidden';
            }

            function closeExportModal() {
                exportModal.classList.add('opacity-0', 'invisible', 'pointer-events-none');
                exportPanel.classList.add('scale-95', 'opacity-0');
                document.body.style.overflow = '';
            }

            // Click outside to close
            exportModal.addEventListener('click', (e) => {
                if (e.target === exportModal) closeExportModal();
            });

            exportModalClose?.addEventListener('click', closeExportModal);
            exportModalCancel?.addEventListener('click', closeExportModal);

            // Confirm export with selected options
            exportModalConfirm?.addEventListener('click', () => {
                const exportType = document.getElementById('exportType')?.value || 'excel';
                closeExportModal();

                if (exportType === 'excel') {
                    exportToExcel();
                } else {
                    exportToPDF();
                }
            });
        }

        // Add keyboard shortcut for export (Ctrl+E for Excel, Ctrl+P for PDF)
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
                e.preventDefault();
                exportToExcel();
            }
            if ((e.ctrlKey || e.metaKey) && e.key === 'p' && e.shiftKey) {
                e.preventDefault();
                exportToPDF();
            }
        });
    </script>
</body>

</html>