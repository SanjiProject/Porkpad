<?php
require_once 'config.php';
require_once 'classes/Paste.php';
require_once 'classes/User.php';

$paste = new Paste();
$user = new User();

// Get current user if logged in
$currentUser = $user->isLoggedIn() ? $user->getCurrentUser() : null;

// Get user ID from URL
$userId = $_GET['id'] ?? '';
if (empty($userId)) {
    header("Location: index.php");
    exit;
}

// Get user information
$profileUser = $user->getById($userId);
if (!$profileUser) {
    $error = 'User not found';
} else {
    // Pagination
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;

    // Get user's public pastes
    $userpastes = $user->getUserpastes($userId, $limit, $offset);
    
    // Filter out private pastes for non-owners
    if (!$currentUser || $currentUser['id'] != $userId) {
        $userpastes = array_filter($userpastes, function($paste) {
            return !$paste['is_private'];
        });
    }

    // Get user stats
    $userStats = $user->getUserStats($userId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($profileUser) ? htmlspecialchars($profileUser['username']) . "'s Profile" : 'User Not Found'; ?> - <?php echo SITE_NAME; ?></title>
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
            <?php if (isset($error)): ?>
                <div class="empty-state">
                    <div class="empty-icon">👤</div>
                    <h3>USER NOT FOUND</h3>
                    <p>The user you're looking for doesn't exist</p>
                    <a href="index.php" class="btn btn-primary">GO HOME</a>
                </div>
            <?php else: ?>
                <!-- User Profile Header -->
                <div class="card" style="margin-bottom: var(--space-8);">
                    <div class="card-body">
                        <div style="display: flex; align-items: center; gap: var(--space-6); flex-wrap: wrap;">
                            <!-- Avatar -->
                            <div style="width: 80px; height: 80px; background: var(--yellow); border: 4px solid var(--black); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 900;">
                                <?php echo strtoupper(substr($profileUser['username'], 0, 1)); ?>
                            </div>
                            
                            <!-- User Info -->
                            <div style="flex: 1;">
                                <h1 style="font-size: 2rem; font-weight: 900; margin-bottom: var(--space-2); text-transform: uppercase;">
                                    <?php echo htmlspecialchars($profileUser['username']); ?>
                                </h1>
                                <div style="display: flex; gap: var(--space-4); font-size: 0.875rem; color: var(--gray-600); flex-wrap: wrap;">
                                    <span>📅 Joined <?php echo date('M j, Y', strtotime($profileUser['created_at'])); ?></span>
                                    <?php if (isset($userStats)): ?>
                                        <span>📄 <?php echo number_format($userStats['total_pastes'] ?? 0); ?> pastes</span>
                                        <span>👁️ <?php echo number_format($userStats['total_views'] ?? 0); ?> total views</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <!-- Main Content -->
                    <div class="dashboard-main">
                        <?php if (empty($userpastes)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">📄</div>
                                <h3>NO PUBLIC pastes</h3>
                                <p><?php echo htmlspecialchars($profileUser['username']); ?> hasn't created any public pastes yet</p>
                                <?php if ($currentUser && $currentUser['id'] == $userId): ?>
                                    <a href="index.php" class="btn btn-primary">CREATE YOUR FIRST paste</a>
                                <?php else: ?>
                                    <a href="index.php" class="btn btn-primary">CREATE A paste</a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: var(--space-6);">
                                <?php foreach ($userpastes as $userpaste): ?>
                                    <div class="card fade-in-up" style="transition: all 0.3s ease;">
                                        <div class="card-header">
                                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                                <div style="flex: 1;">
                                                    <h4 style="margin: 0; font-size: 1.1rem;">
                                                        <a href="<?php echo $userpaste['id']; ?>" style="color: var(--black); border: none;">
                                                            <?php echo htmlspecialchars($userpaste['title']); ?>
                                                        </a>
                                                    </h4>
                                                    <div style="font-family: var(--font-mono); font-size: 0.75rem; color: var(--gray-500); margin-top: var(--space-1);">
                                                        #<?php echo $userpaste['id']; ?>
                                                    </div>
                                                </div>
                                                <?php if ($userpaste['is_private']): ?>
                                                    <span class="badge badge-accent" style="font-size: 0.7rem;">🔒 PRIVATE</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="card-body">
                                            <div style="display: flex; flex-wrap: wrap; gap: var(--space-2); margin-bottom: var(--space-4);">
                                                <span class="badge badge-secondary">
                                                    <?php echo $supported_languages[$userpaste['language']] ?? $userpaste['language']; ?>
                                                </span>
                                                <?php if (!empty($userpaste['password'])): ?>
                                                    <span class="badge" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
                                                        🔒 PROTECTED
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; color: var(--gray-600); margin-bottom: var(--space-4);">
                                                <span><?php echo number_format($userpaste['views']); ?> views</span>
                                                <span><?php echo date('M j, Y', strtotime($userpaste['created_at'])); ?></span>
                                            </div>
                                            
                                            <div style="display: flex; gap: var(--space-3);">
                                                <a href="<?php echo $userpaste['id']; ?>" class="btn btn-primary btn-sm" style="width: 100%; text-align: center;">
                                                    VIEW
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Pagination -->
                            <?php if (count($userpastes) === $limit): ?>
                                <div style="display: flex; justify-content: center; align-items: center; gap: var(--space-4); margin-top: var(--space-8); padding: var(--space-6);">
                                    <?php if ($page > 1): ?>
                                        <a href="?id=<?php echo $userId; ?>&page=<?php echo $page - 1; ?>" class="btn btn-secondary">
                                            PREVIOUS
                                        </a>
                                    <?php endif; ?>
                                    
                                    <span style="padding: var(--space-3); font-weight: 700; color: var(--gray-600);">
                                        PAGE <?php echo $page; ?>
                                    </span>
                                    
                                    <?php if (count($userpastes) === $limit): ?>
                                        <a href="?id=<?php echo $userId; ?>&page=<?php echo $page + 1; ?>" class="btn btn-secondary">
                                            NEXT
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Sidebar -->
                    <div class="dashboard-sidebar">
                        <!-- About User -->
                        <div class="card">
                            <div class="card-header">
                                <h3><?php echo strtoupper(htmlspecialchars($profileUser['username'])); ?></h3>
                            </div>
                            <div class="card-body">
                                <?php if (isset($userStats)): ?>
                                    <div style="display: flex; flex-direction: column; gap: var(--space-4);">
                                        <div style="text-align: center;">
                                            <div style="font-size: 2rem; font-weight: 900; color: var(--black);">
                                                <?php echo number_format($userStats['total_pastes'] ?? 0); ?>
                                            </div>
                                            <div style="font-size: 0.8rem; font-weight: 700; color: var(--gray-600); text-transform: uppercase;">
                                                Total pastes
                                            </div>
                                        </div>
                                        <div style="text-align: center;">
                                            <div style="font-size: 2rem; font-weight: 900; color: var(--black);">
                                                <?php echo number_format($userStats['total_views'] ?? 0); ?>
                                            </div>
                                            <div style="font-size: 0.8rem; font-weight: 700; color: var(--gray-600); text-transform: uppercase;">
                                                Total Views
                                            </div>
                                        </div>
                                        <div style="text-align: center;">
                                            <div style="font-size: 2rem; font-weight: 900; color: var(--black);">
                                                <?php echo number_format($userStats['public_pastes'] ?? 0); ?>
                                            </div>
                                            <div style="font-size: 0.8rem; font-weight: 700; color: var(--gray-600); text-transform: uppercase;">
                                                Public pastes
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div style="margin-top: var(--space-6); padding-top: var(--space-4); border-top: 3px solid var(--black);">
                                    <div style="font-size: 0.875rem; color: var(--gray-600); text-align: center;">
                                        <strong>Member since:</strong><br>
                                        <?php echo date('F j, Y', strtotime($profileUser['created_at'])); ?>
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
                                    <a href="index.php" class="btn btn-primary" style="text-align: center;">
                                        CREATE NEW paste
                                    </a>
                                    <a href="recent" class="btn btn-secondary" style="text-align: center;">
                                        BROWSE ALL pastes
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
            <?php endif; ?>
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
        }
    </style>
</body>
</html>
