<?php
require_once 'config.php';
require_once 'classes/User.php';
require_once 'classes/Paste.php';

$user = new User();
$paste = new Paste();

// Check if user is logged in
if (!$user->isLoggedIn()) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$currentUser = $user->getCurrentUser();
$message = '';
$messageType = '';

// Handle paste deletion
if (isset($_POST['delete_paste'])) {
    $pasteId = $_POST['paste_id'];
    $result = $paste->delete($pasteId);
    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';
}

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Get user's pastes and stats
$userpastes = $user->getUserpastes($currentUser['id'], $limit, $offset);
$userStats = $user->getUserStats($currentUser['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    
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
                    <a href="dashboard" class="active">Dashboard</a>
                    <?php if ($currentUser['is_admin']): ?>
                        <a href="admin.php">Admin</a>
                    <?php endif; ?>
                    <span class="user-info"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                    <a href="logout.php" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <section style="padding: 0;">
        <div class="container-lg">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> fade-in-up">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card fade-in-up">
                    <div class="stat-number"><?php echo number_format($userStats['total_pastes']); ?></div>
                    <div class="stat-label">Total pastes</div>
                </div>
                <div class="stat-card fade-in-up" style="animation-delay: 0.1s;">
                    <div class="stat-number"><?php echo number_format($userStats['total_views']); ?></div>
                    <div class="stat-label">Total Views</div>
                </div>
                <div class="stat-card fade-in-up" style="animation-delay: 0.2s;">
                    <div class="stat-number"><?php echo number_format($userStats['week_pastes']); ?></div>
                    <div class="stat-label">This Week</div>
                </div>
                <div class="stat-card fade-in-up" style="animation-delay: 0.3s;">
                    <div class="stat-number"><?php echo number_format($userStats['private_pastes']); ?></div>
                    <div class="stat-label">Private</div>
                </div>
            </div>

            <!-- Dashboard Grid Layout -->
            <div class="dashboard-grid">
                <!-- Main Content -->
                <div class="dashboard-main">
                    <!-- My pastes Header -->
                    <div class="pastes-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-4); padding: var(--space-3) 0;">
                        <h2 style="margin: 0; font-size: 1.5rem; font-weight: 900; color: var(--black); text-transform: uppercase; letter-spacing: 0.05em;">MY PORKPAD</h2>
                        <a href="index.php" class="btn btn-accent btn-sm">NEW PORKPAD</a>
                    </div>
                    
                    <!-- pastes Grid Container -->
                    <div class="pastes-grid">
                        <?php if (empty($userpastes)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">📝</div>
                                <h3>NO pastes YET</h3>
                                <p>Create your first paste to get started</p>
                                <a href="index.php" class="btn btn-primary">CREATE PORKPAD</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($userpastes as $userpaste): ?>
                                <div class="paste-card">
                                    <div class="paste-card-header">
                                        <h3 class="paste-title">
                                            <a href="<?php echo $userpaste['id']; ?>">
                                                <?php echo htmlspecialchars($userpaste['title']); ?>
                                            </a>
                                        </h3>
                                        <span class="paste-id">#<?php echo $userpaste['id']; ?></span>
                                    </div>
                                    
                                    <div class="paste-card-body">
                                        <div class="paste-badges">
                                            <span class="badge badge-language">
                                                <?php echo $supported_languages[$userpaste['language']] ?? $userpaste['language']; ?>
                                            </span>
                                            
                                            <?php if (isset($userpaste['category_id']) && $userpaste['category_id'] > 0): ?>
                                                <span class="badge badge-category">
                                                    <?php echo $categories[$userpaste['category_id']] ?? 'Unknown'; ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <span class="badge badge-<?php echo $userpaste['is_private'] ? 'private' : 'public'; ?>">
                                                <?php echo $userpaste['is_private'] ? 'PRIVATE' : 'PUBLIC'; ?>
                                            </span>
                                            
                                            <?php if (!empty($userpaste['password'])): ?>
                                                <span class="badge badge-password">
                                                    🔒 PROTECTED
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="paste-stats">
                                            <span class="views-count">👁 <?php echo number_format($userpaste['views']); ?></span>
                                        </div>
                                    </div>

                                    <div class="paste-card-actions">
                                        <a href="<?php echo $userpaste['id']; ?>" class="btn-action btn-view">
                                            👁 VIEW
                                        </a>
                                        <?php if ($paste->canEdit($userpaste['id'])): ?>
                                            <a href="edit.php?id=<?php echo $userpaste['id']; ?>" class="btn-action btn-edit">
                                                ✏️ EDIT
                                            </a>
                                        <?php endif; ?>
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Are you sure you want to delete this paste?')">
                                            <input type="hidden" name="paste_id" value="<?php echo $userpaste['id']; ?>">
                                            <button type="submit" name="delete_paste" class="btn-action btn-delete">
                                                🗑 DELETE
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                        <?php endif; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if (count($userpastes) === $limit): ?>
                        <div class="pagination-container">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>" class="btn btn-secondary">PREVIOUS</a>
                            <?php endif; ?>
                            <span class="page-info">PAGE <?php echo $page; ?></span>
                            <a href="?page=<?php echo $page + 1; ?>" class="btn btn-secondary">NEXT</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="dashboard-sidebar">
                    <!-- Profile Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3>PROFILE</h3>
                        </div>
                        <div class="card-body">
                            <div style="margin-bottom: var(--space-6);">
                                <div style="margin-bottom: var(--space-3);">
                                    <strong>Username:</strong><br>
                                    <?php echo htmlspecialchars($currentUser['username']); ?>
                                </div>
                                <div style="margin-bottom: var(--space-3);">
                                    <strong>Email:</strong><br>
                                    <?php echo htmlspecialchars($currentUser['email']); ?>
                                </div>
                                <div style="margin-bottom: var(--space-3);">
                                    <strong>Member since:</strong><br>
                                    <?php echo date('M Y', strtotime($currentUser['created_at'])); ?>
                                </div>
                                <?php if ($currentUser['last_login']): ?>
                                    <div>
                                        <strong>Last login:</strong><br>
                                        <?php echo date('M j, g:i A', strtotime($currentUser['last_login'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <a href="profile.php" class="btn btn-primary btn-sm" style="width: 100%;">EDIT PROFILE</a>
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
        .paste-item:last-child {
            border-bottom: none;
        }
        
        .paste-id {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            color: var(--gray-500);
            margin-left: var(--space-2);
        }
        
        .views {
            color: var(--gray-600);
            font-weight: 600;
            font-size: 0.8rem;
        }
        
        .pastes-header {
            border-bottom: 1px solid var(--gray-200);
        }
        
        /* New paste Cards Design */
        .pastes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: var(--space-4);
            margin-bottom: var(--space-6);
        }
        
        .paste-card {
            background: var(--white);
            border: 2px solid var(--black);
            border-radius: var(--radius-lg);
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 2px 2px 0px var(--black);
        }
        
        .paste-card:hover {
            transform: translateY(-4px);
            box-shadow: 6px 6px 0px var(--black);
        }
        
        .paste-card-header {
            padding: var(--space-4);
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
        }
        
        .paste-card-header .paste-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.2;
        }
        
        .paste-card-header .paste-title a {
            color: var(--black);
            text-decoration: none;
        }
        
        .paste-card-header .paste-title a:hover {
            color: var(--primary);
        }
        
        .paste-card-header .paste-id {
            font-family: var(--font-mono);
            font-size: 0.7rem;
            color: var(--gray-500);
            margin-left: var(--space-2);
        }
        
        .paste-card-body {
            padding: var(--space-4);
        }
        
        .paste-badges {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-2);
            margin-bottom: var(--space-3);
        }
        
        .paste-badges .badge {
            font-size: 0.7rem;
            padding: var(--space-1) var(--space-2);
            border-radius: var(--radius-sm);
        }
        
        .badge-language {
            background: #e0e7ff;
            color: #3730a3;
            border: 1px solid #c7d2fe;
        }
        
        .badge-category {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        
        .badge-public {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .badge-private {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .badge-password {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        
        .paste-stats {
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }
        
        .views-count {
            font-size: 0.8rem;
            color: var(--gray-600);
            font-weight: 600;
        }
        
        .paste-card-actions {
            padding: var(--space-3);
            background: var(--gray-50);
            border-top: 1px solid var(--gray-200);
            display: flex;
            gap: var(--space-2);
            flex-wrap: wrap;
        }
        
        .btn-action {
            flex: 1;
            min-width: 80px;
            padding: var(--space-2) var(--space-3);
            font-size: 0.75rem;
            font-weight: 700;
            border: 1px solid var(--black);
            border-radius: var(--radius);
            text-decoration: none;
            text-align: center;
            transition: all 0.2s ease;
            cursor: pointer;
            background: none;
            font-family: inherit;
        }
        
        .btn-view {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .btn-view:hover {
            background: #1e40af;
            color: white;
        }
        
        .btn-edit {
            background: #d1fae5;
            color: #065f46;
        }
        
        .btn-edit:hover {
            background: #065f46;
            color: white;
        }
        
        .btn-delete {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-delete:hover {
            background: #991b1b;
            color: white;
        }
        
        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: var(--space-4);
            padding: var(--space-6) 0;
            margin-top: var(--space-6);
            border-top: 1px solid var(--gray-200);
        }
        
        .page-info {
            padding: var(--space-3);
            font-weight: 700;
            color: var(--gray-600);
        }
        
        @media (max-width: 768px) {
            .pastes-header {
                flex-direction: column;
                gap: var(--space-3);
                align-items: flex-start !important;
                padding: var(--space-2) 0;
            }
            
            .pastes-header h2 {
                font-size: 1.2rem !important;
            }
            
            .pastes-header .btn {
                width: 100%;
                text-align: center;
            }
            
            .pastes-grid {
                grid-template-columns: 1fr;
                gap: var(--space-3);
            }
            
            .paste-card-header {
                padding: var(--space-3);
            }
            
            .paste-card-body {
                padding: var(--space-3);
            }
            
            .paste-card-actions {
                padding: var(--space-2);
                flex-direction: column;
            }
            
            .btn-action {
                min-width: auto;
                width: 100%;
            }
            
            .pagination-container {
                flex-direction: column;
                gap: var(--space-3);
            }
        }

    </style>
</body>
</html>