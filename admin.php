<?php
require_once 'config.php';
require_once 'classes/User.php';
require_once 'classes/Paste.php';

$user = new User();
$paste = new Paste();

// Check if user is admin
if (!$user->isLoggedIn() || !$user->isAdmin()) {
    header("Location: login.php");
    exit;
}

$currentUser = $user->getCurrentUser();
$message = '';
$messageType = '';

// Handle admin actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'toggle_user_status':
            $userId = intval($_POST['user_id']);
            $result = $user->toggleUserStatus($userId);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            break;
            
        case 'make_admin':
            $userId = intval($_POST['user_id']);
            $result = $user->makeAdmin($userId);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            break;
            
        case 'delete_paste':
            $pasteId = $_POST['paste_id'];
            $result = $paste->delete($pasteId);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            break;
            

            
        case 'update_settings':
            $message = "Settings updated successfully.";
            $messageType = 'success';
            break;
    }
}

// Get dashboard data
$totalUsers = 0;
$totalpastes = 0;
$recentUsers = [];
$recentpastes = [];
$stats = [];

try {
    // Get all users
    $allUsers = $user->getAllUsers(10, 0);
    $recentUsers = array_slice($allUsers, 0, 5);
    
    // Get RECENT PORKPAD (admin view - includes private pastes)
    $recentpastes = $paste->getRecentForAdmin(10);
    
    // Get overall stats
    $stats = $paste->getStats();
    
    // Count total users
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch()['total'];
    
} catch (Exception $e) {
    $message = "Error loading admin data: " . $e->getMessage();
    $messageType = 'error';
}

function formatFileSize($bytes) {
    if ($bytes == 0) return '0 Bytes';
    $k = 1024;
    $sizes = array('Bytes', 'KB', 'MB', 'GB');
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo SITE_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="./favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="./favicon.ico">
    <link rel="img/apple-touch-icon" sizes="180x180" href="./img/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="./favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="./favicon-16x16.png">
    <link rel="manifest" href="./site.webmanifest">
    
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
                    <a href="dashboard">Dashboard</a>
                    <a href="admin.php" class="active">Admin</a>
                    <span class="user-info"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                    <a href="logout.php" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1 class="page-title">ADMIN PANEL</h1>
            <p class="page-subtitle">Manage users, pastes, and site settings</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content-section">
        <div class="container-lg">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> fade-in-up">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card fade-in-up">
                    <div class="stat-number"><?php echo number_format($totalUsers); ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card fade-in-up" style="animation-delay: 0.1s;">
                    <div class="stat-number"><?php echo number_format($stats['total_pastes'] ?? 0); ?></div>
                    <div class="stat-label">Total pastes</div>
                </div>
                <div class="stat-card fade-in-up" style="animation-delay: 0.2s;">
                    <div class="stat-number"><?php echo number_format($stats['total_views'] ?? 0); ?></div>
                    <div class="stat-label">Total Views</div>
                </div>
                <div class="stat-card fade-in-up" style="animation-delay: 0.3s;">
                    <div class="stat-number"><?php echo number_format($stats['today_pastes'] ?? 0); ?></div>
                    <div class="stat-label">Today's pastes</div>
                </div>
            </div>

            <!-- Admin Tabs -->
            <div style="margin-bottom: var(--space-8);">
                <div style="display: flex; border-bottom: 2px solid var(--black); margin-bottom: var(--space-6);">
                    <button class="admin-tab active" onclick="showTab('users')" id="users-btn">USERS</button>
                    <button class="admin-tab" onclick="showTab('pastes')" id="pastes-btn">pastes</button>
                    <button class="admin-tab" onclick="showTab('maintenance')" id="maintenance-btn">MAINTENANCE</button>
                    <button class="admin-tab" onclick="showTab('settings')" id="settings-btn">SETTINGS</button>
                </div>

                <!-- Users Tab -->
                <div id="users-tab" class="tab-content active">
                    <div class="card">
                        <div class="card-header">
                            <h3>USER MANAGEMENT</h3>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <?php if (empty($recentUsers)): ?>
                                <div class="empty-state">
                                    <div class="empty-icon">👥</div>
                                    <h3>NO USERS FOUND</h3>
                                    <p>No users to manage</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recentUsers as $userData): ?>
                                    <div class="paste-item">
                                        <div class="paste-title">
                                            <span><?php echo htmlspecialchars($userData['username']); ?></span>
                                            <?php if ($userData['is_admin']): ?>
                                                <span class="badge badge-accent">ADMIN</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="paste-meta">
                                            <span><?php echo htmlspecialchars($userData['email']); ?></span>
                                            <span class="badge <?php echo $userData['is_active'] ? 'badge-primary' : 'badge-secondary'; ?>">
                                                <?php echo $userData['is_active'] ? 'ACTIVE' : 'INACTIVE'; ?>
                                            </span>
                                            <span><?php echo number_format($userData['paste_count']); ?> pastes</span>
                                            <span class="date">Joined <?php echo date('M j, Y', strtotime($userData['created_at'])); ?></span>
                                        </div>

                                        <div class="paste-actions">
                                            <?php if ($userData['id'] != $currentUser['id']): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="toggle_user_status">
                                                    <input type="hidden" name="user_id" value="<?php echo $userData['id']; ?>">
                                                    <button type="submit" class="btn btn-secondary btn-sm" 
                                                            onclick="return confirm('Toggle user status?')">
                                                        <?php echo $userData['is_active'] ? 'DISABLE' : 'ENABLE'; ?>
                                                    </button>
                                                </form>
                                                <?php if (!$userData['is_admin']): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="make_admin">
                                                        <input type="hidden" name="user_id" value="<?php echo $userData['id']; ?>">
                                                        <button type="submit" class="btn btn-accent btn-sm" 
                                                                onclick="return confirm('Make this user an admin?')">
                                                            MAKE ADMIN
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge badge-primary">YOU</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- pastes Tab -->
                <div id="pastes-tab" class="tab-content">
                    <div class="card">
                        <div class="card-header">
                            <h3>RECENT PORKPAD</h3>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <?php if (empty($recentpastes)): ?>
                                <div class="empty-state">
                                    <div class="empty-icon">📄</div>
                                    <h3>NO pastes FOUND</h3>
                                    <p>No pastes to manage</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recentpastes as $pasteData): ?>
                                    <div class="paste-item">
                                        <div class="paste-title">
                                            <a href="<?php echo $pasteData['id']; ?>">
                                                <?php echo htmlspecialchars($pasteData['title']); ?>
                                            </a>
                                            <span class="paste-id">#<?php echo $pasteData['id']; ?></span>
                                        </div>
                                        
                                        <div class="paste-meta">
                                            <span class="badge badge-secondary">
                                                <?php echo $supported_languages[$pasteData['language']] ?? $pasteData['language']; ?>
                                            </span>
                                            
                                            <span class="badge <?php echo isset($pasteData['is_private']) && $pasteData['is_private'] ? 'badge-accent' : 'badge-primary'; ?>">
                                                <?php echo isset($pasteData['is_private']) && $pasteData['is_private'] ? 'PRIVATE' : 'PUBLIC'; ?>
                                            </span>
                                            
                                            <span class="views"><?php echo number_format($pasteData['views']); ?> views</span>
                                            <span class="date"><?php echo date('M j, Y', strtotime($pasteData['created_at'])); ?></span>
                                        </div>

                                        <div class="paste-actions">
                                            <a href="<?php echo $pasteData['id']; ?>" class="btn btn-secondary btn-sm">
                                                VIEW
                                            </a>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_paste">
                                                <input type="hidden" name="paste_id" value="<?php echo $pasteData['id']; ?>">
                                                <button type="submit" class="btn btn-accent btn-sm" 
                                                        onclick="return confirm('Delete this paste?')">
                                                    DELETE
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Maintenance Tab -->
                <div id="maintenance-tab" class="tab-content">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--space-6);">
                        <!-- Cleanup Card -->


                        <!-- Database Info Card -->
                        <div class="card">
                            <div class="card-header">
                                <h3>DATABASE INFO</h3>
                            </div>
                            <div class="card-body">
                                <div style="display: flex; flex-direction: column; gap: var(--space-3);">
                                    <div style="display: flex; justify-content: space-between;">
                                        <strong>Users:</strong> 
                                        <span><?php echo number_format($totalUsers); ?></span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between;">
                                        <strong>pastes:</strong> 
                                        <span><?php echo number_format($stats['total_pastes'] ?? 0); ?></span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between;">
                                        <strong>Views:</strong> 
                                        <span><?php echo number_format($stats['total_views'] ?? 0); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- System Status Card -->
                        <div class="card">
                            <div class="card-header">
                                <h3>SYSTEM STATUS</h3>
                            </div>
                            <div class="card-body">
                                <div style="display: flex; flex-direction: column; gap: var(--space-3);">
                                    <div style="display: flex; align-items: center; gap: var(--space-2);">
                                        <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--success);"></div>
                                        <span>Database Connected</span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: var(--space-2);">
                                        <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--success);"></div>
                                        <span>File System OK</span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: var(--space-2);">
                                        <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--success);"></div>
                                        <span>PHP <?php echo phpversion(); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Settings Tab -->
                <div id="settings-tab" class="tab-content">
                    <div class="card" style="max-width: 600px;">
                        <div class="card-header">
                            <h3>SITE SETTINGS</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_settings">
                                
                                <div style="margin-bottom: var(--space-6); padding-bottom: var(--space-6); border-bottom: 2px solid var(--gray-200);">
                                    <h4 style="margin-bottom: var(--space-4);">GENERAL SETTINGS</h4>
                                    <div class="form-group">
                                        <label class="form-label">Site Name</label>
                                        <input type="text" class="form-control" value="<?php echo SITE_NAME; ?>" readonly>
                                        <div style="font-size: 0.8rem; color: var(--gray-500); margin-top: var(--space-1);">
                                            Defined in config.php
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Maximum paste Size</label>
                                        <input type="text" class="form-control" value="<?php echo formatFileSize(MAX_paste_SIZE); ?>" readonly>
                                        <div style="font-size: 0.8rem; color: var(--gray-500); margin-top: var(--space-1);">
                                            Defined in config.php
                                        </div>
                                    </div>
                                </div>

                                <div style="margin-bottom: var(--space-6);">
                                    <h4 style="margin-bottom: var(--space-4);">SECURITY SETTINGS</h4>
                                    <div class="checkbox-wrapper" style="margin-bottom: var(--space-4);">
                                        <input type="checkbox" id="require_captcha" name="require_captcha" checked>
                                        <label for="require_captcha" class="form-label" style="margin: 0;">
                                            Require captcha for all users (bot protection)
                                        </label>
                                    </div>
                                    <div class="checkbox-wrapper">
                                        <input type="checkbox" id="allow_anonymous" name="allow_anonymous" checked>
                                        <label for="allow_anonymous" class="form-label" style="margin: 0;">
                                            Allow anonymous paste creation
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary" style="width: 100%; text-align: center;">
                                    SAVE SETTINGS
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 <?php echo SITE_NAME; ?>. ADMIN PANEL - MANAGE WITH CARE.</p>
        </div>
    </footer>

    <style>
        .admin-tab {
            background: none;
            border: none;
            padding: var(--space-4) var(--space-6);
            color: var(--gray-600);
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            border-bottom: 2px solid transparent;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.875rem;
        }

        .admin-tab:hover {
            color: var(--black);
            background: var(--gray-100);
        }

        .admin-tab.active {
            color: var(--black);
            border-bottom-color: var(--accent);
            background: var(--white);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        @media (max-width: 768px) {
            .admin-tab {
                padding: var(--space-3) var(--space-4);
                font-size: 0.8rem;
            }
            
            [style*="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr))"] {
                grid-template-columns: 1fr !important;
            }
        }
    </style>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active from all buttons
            document.querySelectorAll('.admin-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            document.getElementById(tabName + '-btn').classList.add('active');
        }
    </script>
</body>
</html>