<?php

/**
 * Breadcrumbs Component
 * 
 * Usage: 
 * On each page, set $breadcrumbs array before including this file:
 * $breadcrumbs = [
 *     ['title' => 'Dashboard', 'url' => 'index.php'],
 *     ['title' => 'Current Page']
 * ];
 * include './include/breadcrumbs.php';
 */

// Get current page filename
$current_page = basename($_SERVER['PHP_SELF']);

// Default breadcrumbs based on current page
if (!isset($breadcrumbs)) {
    $breadcrumbs = [
        ['title' => 'Home', 'url' => 'index.php']
    ];

    // Add page-specific breadcrumb
    switch ($current_page) {
        case 'index.php':
            $breadcrumbs[] = ['title' => 'Dashboard'];
            break;
        case 'users.php':
            $breadcrumbs[] = ['title' => 'Dashboard', 'url' => 'index.php'];
            $breadcrumbs[] = ['title' => 'Users'];
            break;
        case 'profile.php':
        case 'profile.html':
            $breadcrumbs[] = ['title' => 'Dashboard', 'url' => 'index.php'];
            $breadcrumbs[] = ['title' => 'Profile'];
            break;
        case 'login.php':
        case 'login.html':
            $breadcrumbs[] = ['title' => 'Login'];
            break;
        default:
            $page = pathinfo($current_page, PATHINFO_FILENAME);
            $page = str_replace(['_', '-'], ' ', $page);
            $page = ucwords($page);
            $breadcrumbs[] = ['title' => $page];
    }
}
?>

<div class="breadcrumbs-container">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb-list">
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <li class="breadcrumb-item">
                    <?php if (isset($crumb['url']) && $index < count($breadcrumbs) - 1): ?>
                        <a href="<?php echo htmlspecialchars($crumb['url']); ?>" class="breadcrumb-link">
                            <?php echo htmlspecialchars($crumb['title']); ?>
                        </a>
                    <?php else: ?>
                        <span class="breadcrumb-current">
                            <?php echo htmlspecialchars($crumb['title']); ?>
                        </span>
                    <?php endif; ?>
                </li>
                <?php if ($index < count($breadcrumbs) - 1): ?>
                    <li class="breadcrumb-separator">/</li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </nav>
</div>