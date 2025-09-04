<?php
require_once 'config.php';
require_once 'classes/Paste.php';
require_once 'classes/User.php';

$paste = new Paste();
$user = new User();

// Get current user if logged in
$currentUser = $user->isLoggedIn() ? $user->getCurrentUser() : null;

// Get category ID from URL
$categoryId = $_GET['id'] ?? '';
if (empty($categoryId) || !is_numeric($categoryId)) {
    header("Location: recent");
    exit;
}

$categoryId = intval($categoryId);

// Check if category exists
if (!isset($categories[$categoryId])) {
    header("Location: recent");
    exit;
}

$categoryName = $categories[$categoryId];

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Search functionality
$searchQuery = trim($_GET['search'] ?? '');

if ($searchQuery) {
    // Search within this category
    $categorypastes = $paste->searchByCategory($searchQuery, $categoryId, $limit);
    $pageTitle = 'SEARCH RESULTS';
    $pageSubtitle = 'Results for "' . htmlspecialchars($searchQuery) . '" in ' . $categoryName;
} else {
    // Get all pastes in this category
    $categorypastes = $paste->getRecent($limit, $offset, $categoryId);
    
    $pageTitle = strtoupper($categoryName);
    $pageSubtitle = 'Browse all pastes in this category';
}

// Get category stats
$categoryStats = $paste->getCategoryStats($categoryId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
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
                    <a href="recent">Recent</a>
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
            <!-- Category Header -->
            <div class="card" style="margin-bottom: var(--space-8);">
                <div class="card-body">
                    <div style="display: flex; align-items: center; gap: var(--space-6); flex-wrap: wrap;">
                        <!-- Category Icon -->
                        <div style="width: 80px; height: 80px; background: var(--yellow); border: 4px solid var(--black); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem;">
                            📁
                        </div>
                        
                        <!-- Category Info -->
                        <div style="flex: 1;">
                            <h1 style="font-size: 2rem; font-weight: 900; margin-bottom: var(--space-2); text-transform: uppercase;">
                                <?php echo htmlspecialchars($categoryName); ?>
                            </h1>
                            <div style="display: flex; gap: var(--space-4); font-size: 0.875rem; color: var(--gray-600); flex-wrap: wrap;">
                                <span>📄 <?php echo number_format($categoryStats['total_pastes'] ?? 0); ?> pastes</span>
                                <span>👁️ <?php echo number_format($categoryStats['total_views'] ?? 0); ?> total views</span>
                                <span>📅 Last updated <?php echo isset($categoryStats['last_updated']) ? date('M j, Y', strtotime($categoryStats['last_updated'])) : 'Never'; ?></span>
                            </div>
                        </div>

                        <!-- Back Button -->
                        <div>
                            <a href="recent" class="btn btn-secondary">
                                ← BACK TO ALL pastes
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="card" style="margin-bottom: var(--space-8);">
                <div class="card-body">
                    <form method="GET" style="display: flex; gap: var(--space-4); align-items: end;">
                        <input type="hidden" name="id" value="<?php echo $categoryId; ?>">
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label for="search" class="form-label">Search in <?php echo htmlspecialchars($categoryName); ?></label>
                            <input type="text" id="search" name="search" class="form-control" 
                                   placeholder="Search by title or content in this category..." 
                                   value="<?php echo htmlspecialchars($searchQuery); ?>">
                        </div>
                        <div style="display: flex; gap: var(--space-3);">
                            <button type="submit" class="btn btn-primary">SEARCH</button>
                            <?php if ($searchQuery): ?>
                                <a href="category.php?id=<?php echo $categoryId; ?>" class="btn btn-secondary">CLEAR</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Main Content -->
                <div class="dashboard-main">
                    <?php if (empty($categorypastes)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <?php if ($searchQuery): ?>
                                    🔍
                                <?php else: ?>
                                    📁
                                <?php endif; ?>
                            </div>
                            <h3>
                                <?php if ($searchQuery): ?>
                                    NO RESULTS FOUND
                                <?php else: ?>
                                    NO pastes IN THIS CATEGORY
                                <?php endif; ?>
                            </h3>
                            <p>
                                <?php if ($searchQuery): ?>
                                    Try different search terms
                                <?php else: ?>
                                    This category doesn't have any pastes yet. Be the first to create one!
                                <?php endif; ?>
                            </p>
                            <div style="display: flex; gap: var(--space-3); justify-content: center; flex-wrap: wrap;">
                                <a href="index.php?category=<?php echo $categoryId; ?>" class="btn btn-primary">
                                    CREATE PORKPAD IN <?php echo strtoupper($categoryName); ?>
                                </a>
                                <?php if ($searchQuery): ?>
                                    <a href="category.php?id=<?php echo $categoryId; ?>" class="btn btn-secondary">VIEW ALL IN CATEGORY</a>
                                <?php else: ?>
                                    <a href="recent" class="btn btn-secondary">BROWSE ALL CATEGORIES</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: var(--space-6);">
                            <?php foreach ($categorypastes as $categorypaste): ?>
                                <div class="card fade-in-up" style="transition: all 0.3s ease;">
                                    <div class="card-header">
                                        <div style="display: flex; justify-content: space-between; align-items: start;">
                                            <div style="flex: 1;">
                                                <h4 style="margin: 0; font-size: 1.1rem;">
                                                    <a href="<?php echo $categorypaste['id']; ?>" style="color: var(--black); border: none;">
                                                        <?php echo htmlspecialchars($categorypaste['title']); ?>
                                                    </a>
                                                </h4>
                                                <div style="font-family: var(--font-mono); font-size: 0.75rem; color: var(--gray-500); margin-top: var(--space-1);">
                                                    #<?php echo $categorypaste['id']; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card-body">
                                        <div style="display: flex; flex-wrap: wrap; gap: var(--space-2); margin-bottom: var(--space-4);">
                                            <span class="badge badge-secondary">
                                                <?php echo $supported_languages[$categorypaste['language']] ?? $categorypaste['language']; ?>
                                            </span>
                                            <?php if (!empty($categorypaste['password'])): ?>
                                                <span class="badge" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
                                                    🔒 PROTECTED
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; color: var(--gray-600); margin-bottom: var(--space-4);">
                                            <span><?php echo number_format($categorypaste['views']); ?> views</span>
                                            <span><?php echo date('M j, Y', strtotime($categorypaste['created_at'])); ?></span>
                                        </div>
                                        
                                        <div style="display: flex; gap: var(--space-3);">
                                            <a href="<?php echo $categorypaste['id']; ?>" class="btn btn-primary btn-sm" style="width: 100%; text-align: center;">
                                                VIEW
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if (!$searchQuery && count($categorypastes) === $limit): ?>
                            <div style="display: flex; justify-content: center; align-items: center; gap: var(--space-4); margin-top: var(--space-8); padding: var(--space-6);">
                                <?php if ($page > 1): ?>
                                    <a href="?id=<?php echo $categoryId; ?>&page=<?php echo $page - 1; ?>" class="btn btn-secondary">
                                        PREVIOUS
                                    </a>
                                <?php endif; ?>
                                
                                <span style="padding: var(--space-3); font-weight: 700; color: var(--gray-600);">
                                    PAGE <?php echo $page; ?>
                                </span>
                                
                                <?php if (count($categorypastes) === $limit): ?>
                                    <a href="?id=<?php echo $categoryId; ?>&page=<?php echo $page + 1; ?>" class="btn btn-secondary">
                                        NEXT
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="dashboard-sidebar">
                    <!-- Category Stats -->
                    <div class="card">
                        <div class="card-header">
                            <h3>CATEGORY STATS</h3>
                        </div>
                        <div class="card-body">
                            <div style="display: flex; flex-direction: column; gap: var(--space-4);">
                                <div style="text-align: center;">
                                    <div style="font-size: 2rem; font-weight: 900; color: var(--black);">
                                        <?php echo number_format($categoryStats['total_pastes'] ?? 0); ?>
                                    </div>
                                    <div style="font-size: 0.8rem; font-weight: 700; color: var(--gray-600); text-transform: uppercase;">
                                        Total pastes
                                    </div>
                                </div>
                                <div style="text-align: center;">
                                    <div style="font-size: 2rem; font-weight: 900; color: var(--black);">
                                        <?php echo number_format($categoryStats['total_views'] ?? 0); ?>
                                    </div>
                                    <div style="font-size: 0.8rem; font-weight: 700; color: var(--gray-600); text-transform: uppercase;">
                                        Total Views
                                    </div>
                                </div>
                                <div style="text-align: center;">
                                    <div style="font-size: 2rem; font-weight: 900; color: var(--black);">
                                        <?php echo number_format($categoryStats['public_pastes'] ?? 0); ?>
                                    </div>
                                    <div style="font-size: 0.8rem; font-weight: 700; color: var(--gray-600); text-transform: uppercase;">
                                        Public pastes
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card" style="margin-top: var(--space-6);">
                        <div class="card-header">
                            <h3>QUICK ACTIONS</h3>
                        </div>
                        <div class="card-body">
                            <div style="display: flex; flex-direction: column; gap: var(--space-3);">
                                <a href="index.php?category=<?php echo $categoryId; ?>" class="btn btn-primary" style="text-align: center;">
                                    CREATE IN <?php echo strtoupper($categoryName); ?>
                                </a>
                                <a href="recent" class="btn btn-secondary" style="text-align: center;">
                                    BROWSE ALL CATEGORIES
                                </a>
                                <?php if ($currentUser): ?>
                                    <a href="dashboard" class="btn btn-secondary" style="text-align: center;">
                                        MY DASHBOARD
                                    </a>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-secondary" style="text-align: center;">
                                        LOGIN
                                    </a>
                                    <a href="register.php" class="btn btn-secondary" style="text-align: center;">
                                        REGISTER
                                    </a>
                                <?php endif; ?>
                            </div>
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
            
            [style*="grid-template-columns: repeat(auto-fill, minmax(400px, 1fr))"] {
                grid-template-columns: 1fr !important;
            }
            
            .content-section form {
                flex-direction: column;
            }
            
            .content-section form > div {
                flex: none !important;
            }
        }
    </style>
</body>
</html>
