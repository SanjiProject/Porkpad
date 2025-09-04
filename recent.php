<?php
require_once 'config.php';
require_once 'classes/Paste.php';
require_once 'classes/User.php';
require_once 'classes/SEO.php';

$paste = new Paste();
$user = new User();

// Get current user if logged in
$currentUser = $user->isLoggedIn() ? $user->getCurrentUser() : null;

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Search functionality
$searchQuery = trim($_GET['search'] ?? '');
$categoryFilter = intval($_GET['category'] ?? 0);

if ($searchQuery || $categoryFilter > 0) {
    if ($searchQuery && $categoryFilter > 0) {
        $recentpastes = $paste->searchByCategory($searchQuery, $categoryFilter, $limit, $offset);
        $pageTitle = 'SEARCH RESULTS';
        $pageSubtitle = 'Results for "' . htmlspecialchars($searchQuery) . '" in ' . ($categories[$categoryFilter] ?? 'Unknown Category');
    } elseif ($searchQuery) {
        $recentpastes = $paste->search($searchQuery, $limit, $offset);
        $pageTitle = 'SEARCH RESULTS';
        $pageSubtitle = 'Results for "' . htmlspecialchars($searchQuery) . '"';
    } else {
        $recentpastes = $paste->getRecent($limit, $offset, $categoryFilter);
        $pageTitle = 'CATEGORY: ' . strtoupper($categories[$categoryFilter] ?? 'UNKNOWN');
        $pageSubtitle = 'pastes in ' . ($categories[$categoryFilter] ?? 'Unknown Category');
    }
} else {
    $recentpastes = $paste->getRecent($limit, $offset);
    $pageTitle = 'RECENT PORKPAD';
    $pageSubtitle = 'Discover public pastes from the community';
}

$stats = $paste->getStats();

// Setup SEO
$seo = new SEO();
if ($searchQuery) {
    $seo->setTitle('Search Results for "' . $searchQuery . '"')
        ->setDescription('Search results for "' . $searchQuery . '" on ' . SITE_NAME . '. Find code snippets and text pastes.')
        ->setKeywords(['search', 'code search', $searchQuery, 'pastebin', SITE_NAME]);
} elseif ($categoryFilter > 0) {
    $categoryName = isset($categories[$categoryFilter]) ? $categories[$categoryFilter] : 'Category';
    $seo->setTitle('Recent Pastes in ' . $categoryName)
        ->setDescription('Browse recent code snippets and text pastes in the ' . $categoryName . ' category on ' . SITE_NAME . '.')
        ->setKeywords([$categoryName, 'category', 'code snippets', 'pastebin', SITE_NAME]);
} else {
    $seo->setTitle('Recent Pastes')
        ->setDescription('Browse the latest code snippets and text pastes shared by the community on ' . SITE_NAME . '. Discover useful code examples and solutions.')
        ->setKeywords(['recent pastes', 'latest code', 'community snippets', 'pastebin', SITE_NAME]);
}
$seo->setType('website');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo $seo->renderMetaTags(); ?>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <?php echo $seo->renderStructuredData(); ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-container">
                <a href="/" class="nav-brand">
                    <img src="./img/header.webp" alt="<?php echo SITE_NAME; ?>" style="height: 80px; width: auto;">
                </a>
                <div class="nav-links">
                    <a href="recent" class="active">Recent</a>
                    <?php if ($currentUser): ?>
                        <a href="dashboard">Dashboard</a>
                        <?php if ($currentUser['is_admin']): ?>
                            <a href="admin.php">Admin</a>
                        <?php endif; ?>
                        <span class="user-info"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                        <a href="logout.php" class="btn btn-secondary btn-sm">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-secondary btn-sm">Login</a>
                        <a href="register.php" class="btn btn-primary btn-sm">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <section style="padding: 0;">
        <div class="container-lg">
            <!-- Search Section -->
            <div class="card" style="margin-bottom: var(--space-8);">
                <div class="card-body">
                    <form method="GET">
                        <div style="display: flex; gap: var(--space-4); align-items: end; margin-bottom: var(--space-4);">
                            <div class="form-group" style="flex: 2; margin-bottom: 0;">
                                <label for="search" class="form-label">SEARCH PORKPAD</label>
                                <input type="text" id="search" name="search" class="form-control" 
                                       placeholder="Search by title or content..." 
                                       value="<?php echo htmlspecialchars($searchQuery); ?>">
                            </div>
                            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                <label for="category" class="form-label">Category</label>
                                <select id="category" name="category" class="form-control">
                                    <option value="0" <?php echo $categoryFilter == 0 ? 'selected' : ''; ?>>All Categories</option>
                                    <?php foreach ($categories as $catId => $catName): ?>
                                        <?php if ($catId > 0): ?>
                                            <option value="<?php echo $catId; ?>" <?php echo $categoryFilter == $catId ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($catName); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div style="display: flex; gap: var(--space-3);">
                            <button type="submit" class="btn btn-primary">SEARCH</button>
                            <?php if ($searchQuery || $categoryFilter > 0): ?>
                                <a href="recent" class="btn btn-secondary">CLEAR</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>



            <div class="dashboard-grid">
                <!-- Main Content -->
                <div class="dashboard-main">
                    <?php if (empty($recentpastes)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <?php if ($searchQuery || $categoryFilter > 0): ?>
                                    🔍
                                <?php else: ?>
                                    📄
                                <?php endif; ?>
                            </div>
                            <h3>
                                <?php if ($searchQuery || $categoryFilter > 0): ?>
                                    NO RESULTS FOUND
                                <?php else: ?>
                                    NO pastes YET
                                <?php endif; ?>
                            </h3>
                            <p>
                                <?php if ($searchQuery && $categoryFilter > 0): ?>
                                    No pastes found for "<?php echo htmlspecialchars($searchQuery); ?>" in <?php echo htmlspecialchars($categories[$categoryFilter] ?? 'Unknown Category'); ?>
                                <?php elseif ($searchQuery): ?>
                                    No pastes found for "<?php echo htmlspecialchars($searchQuery); ?>"
                                <?php elseif ($categoryFilter > 0): ?>
                                    No pastes found in <?php echo htmlspecialchars($categories[$categoryFilter] ?? 'Unknown Category'); ?>
                                <?php else: ?>
                                    Be the first to create a paste!
                                <?php endif; ?>
                            </p>
                            <div style="display: flex; gap: var(--space-3); justify-content: center; flex-wrap: wrap;">
                                <a href="index.php" class="btn btn-primary">
                                    CREATE PORKPAD
                                </a>
                                <?php if ($searchQuery): ?>
                                    <a href="recent" class="btn btn-secondary">VIEW ALL PORKPAD</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: var(--space-6);">
                            <?php foreach ($recentpastes as $recentpaste): ?>
                                <div class="card fade-in-up" style="transition: all 0.3s ease;">
                                    <div class="card-header">
                                        <div style="display: flex; justify-content: space-between; align-items: start;">
                                            <div style="flex: 1;">
                                                <h4 style="margin: 0; font-size: 1.1rem;">
                                                    <a href="<?php echo $recentpaste['id']; ?>" style="color: var(--black); border: none;">
                                                        <?php echo htmlspecialchars($recentpaste['title']); ?>
                                                    </a>
                                                </h4>
                                                <div style="font-family: var(--font-mono); font-size: 0.75rem; color: var(--gray-500); margin-top: var(--space-1);">
                                                    #<?php echo $recentpaste['id']; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card-body">
                                        <div style="display: flex; flex-wrap: wrap; gap: var(--space-2); margin-bottom: var(--space-4);">
                                            <span class="badge badge-secondary">
                                                <?php echo $supported_languages[$recentpaste['language']] ?? $recentpaste['language']; ?>
                                            </span>
                                            <?php if (!empty($recentpaste['password'])): ?>
                                                <span class="badge" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
                                                    🔒 PROTECTED
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; color: var(--gray-600); margin-bottom: var(--space-4);">
                                            <span><?php echo number_format($recentpaste['views']); ?> views</span>
                                            <span><?php echo date('M j, Y', strtotime($recentpaste['created_at'])); ?></span>
                                        </div>
                                        
                                        <div style="display: flex; gap: var(--space-3);">
                                            <a href="<?php echo $recentpaste['id']; ?>" class="btn btn-primary btn-sm" style="width: 100%; text-align: center;">
                                                VIEW
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php 
                        $showPagination = count($recentpastes) === $limit || $page > 1;
                        $queryParams = [];
                        if ($searchQuery) $queryParams['search'] = $searchQuery;
                        if ($categoryFilter > 0) $queryParams['category'] = $categoryFilter;
                        
                        if ($showPagination): 
                        ?>
                            <div style="display: flex; justify-content: center; align-items: center; gap: var(--space-4); margin-top: var(--space-8); padding: var(--space-6); border-top: 1px solid var(--gray-200);">
                                <?php if ($page > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($queryParams, ['page' => $page - 1])); ?>" class="btn btn-secondary">
                                        ← PREVIOUS
                                    </a>
                                <?php endif; ?>
                                
                                <span style="padding: var(--space-3); font-weight: 700; color: var(--black); background: var(--gray-100); border-radius: var(--radius); border: 1px solid var(--gray-200);">
                                    PAGE <?php echo $page; ?>
                                </span>
                                
                                <?php if (count($recentpastes) === $limit): ?>
                                    <a href="?<?php echo http_build_query(array_merge($queryParams, ['page' => $page + 1])); ?>" class="btn btn-secondary">
                                        NEXT →
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="dashboard-sidebar">
                    <!-- About Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3><?php echo SITE_NAME; ?></h3>
                        </div>
                        <div class="card-body">
                            <p style="margin-bottom: var(--space-4); color: var(--gray-600); font-size: 0.875rem;">
                                Share code snippets, text, and collaborate with developers worldwide.
                            </p>
                            <div style="display: flex; flex-direction: column; gap: var(--space-2); font-size: 0.8rem;">
                                <div>✓ Syntax highlighting</div>
                                <div>✓ Password protection</div>

                                <div>✓ Categories & search</div>
                                <div>✓ User accounts</div>
                            </div>
                            <?php if (!$currentUser): ?>
                                <div style="margin-top: var(--space-6); display: flex; flex-direction: column; gap: var(--space-3);">
                                    <a href="register.php" class="btn btn-primary" style="text-align: center;">
                                        SIGN UP FREE
                                    </a>
                                    <a href="login.php" class="btn btn-secondary" style="text-align: center;">
                                        LOGIN
                                    </a>
                                </div>
                            <?php else: ?>
                                <a href="index.php" class="btn btn-primary" style="width: 100%; margin-top: var(--space-6); text-align: center;">
                                    CREATE PORKPAD
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 <?php echo SITE_NAME; ?>. Made with ❤️ by <a href="https://github.com/SanjiProject">Sanji Project</a></p>
        </div>
    </footer>



    <style>
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 6px 6px 0px var(--black);
        }
        

        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .content-section form {
                flex-direction: column;
            }
            
            .content-section form > div {
                flex: none !important;
            }
            
            [style*="grid-template-columns: repeat(auto-fill, minmax(400px, 1fr))"] {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</body>
</html>