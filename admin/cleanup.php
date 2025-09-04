<?php
// Admin cleanup script for expired pastes
require_once '../config.php';
require_once '../classes/Paste.php';

// Simple authentication (you should implement proper authentication)
$admin_password = 'admin123'; // Change this!

if (!isset($_POST['password']) || $_POST['password'] !== $admin_password) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Admin Cleanup - <?php echo SITE_NAME; ?></title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 500px; margin: 100px auto; padding: 20px; }
            .form-group { margin-bottom: 15px; }
            label { display: block; margin-bottom: 5px; font-weight: bold; }
            input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
            button { background: #6366f1; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
            button:hover { background: #5b5bdb; }
        </style>
    </head>
    <body>
        <h2>Admin Cleanup Panel</h2>
        <form method="POST">
            <div class="form-group">
                <label for="password">Admin Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Access Cleanup Panel</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

$paste = new paste();

// Handle cleanup action
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'cleanup_expired':
            $deleted = $paste->cleanExpired();
            $message = $deleted ? "Expired pastes cleaned up successfully." : "No expired pastes found.";
            break;
    }
}

// Get statistics
$stats = $paste->getStats();
$recentpastes = $paste->getRecent(10);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container { max-width: 1000px; margin: 20px auto; padding: 20px; }
        .admin-card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .stat-box { text-align: center; padding: 20px; background: #f8fafc; border-radius: 8px; }
        .stat-number { font-size: 2rem; font-weight: bold; color: #6366f1; }
        .action-buttons { display: flex; gap: 10px; margin-top: 20px; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    </style>
</head>
<body>
    <div class="admin-container">
        <h1>🔧 Admin Panel - <?php echo SITE_NAME; ?></h1>
        
        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="admin-card">
            <h2>📊 Statistics</h2>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-number"><?php echo number_format($stats['total_pastes']); ?></div>
                    <div>Total pastes</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo number_format($stats['today_pastes']); ?></div>
                    <div>Today's pastes</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo number_format($stats['total_views']); ?></div>
                    <div>Total Views</div>
                </div>
            </div>
        </div>
        
        <div class="admin-card">
            <h2>🧹 Cleanup Actions</h2>
            <p>Manage and clean up the paste database.</p>
            <div class="action-buttons">
                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete all expired pastes?')">
                    <input type="hidden" name="password" value="<?php echo htmlspecialchars($_POST['password']); ?>">
                    <input type="hidden" name="action" value="cleanup_expired">
                    <button type="submit" class="btn btn-danger">🗑️ Clean Expired pastes</button>
                </form>
            </div>
        </div>
        
        <div class="admin-card">
            <h2>📋 RECENT PORKPAD</h2>
            <?php if (empty($recentpastes)): ?>
                <p>No pastes found.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="padding: 10px; border: 1px solid #e2e8f0; text-align: left;">ID</th>
                                <th style="padding: 10px; border: 1px solid #e2e8f0; text-align: left;">Title</th>
                                <th style="padding: 10px; border: 1px solid #e2e8f0; text-align: left;">Language</th>
                                <th style="padding: 10px; border: 1px solid #e2e8f0; text-align: left;">Views</th>
                                <th style="padding: 10px; border: 1px solid #e2e8f0; text-align: left;">Created</th>
                                <th style="padding: 10px; border: 1px solid #e2e8f0; text-align: left;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentpastes as $paste): ?>
                                <tr>
                                    <td style="padding: 10px; border: 1px solid #e2e8f0; font-family: monospace;"><?php echo $paste['id']; ?></td>
                                    <td style="padding: 10px; border: 1px solid #e2e8f0;"><?php echo htmlspecialchars($paste['title']); ?></td>
                                    <td style="padding: 10px; border: 1px solid #e2e8f0;"><?php echo $paste['language']; ?></td>
                                    <td style="padding: 10px; border: 1px solid #e2e8f0;"><?php echo number_format($paste['views']); ?></td>
                                    <td style="padding: 10px; border: 1px solid #e2e8f0;"><?php echo date('M j, Y H:i', strtotime($paste['created_at'])); ?></td>
                                    <td style="padding: 10px; border: 1px solid #e2e8f0;">
                                        <a href="../view.php?id=<?php echo $paste['id']; ?>" target="_blank" style="color: #6366f1;">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="admin-card">
            <h2>🔗 Quick Links</h2>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <a href="../index.php" class="btn btn-primary">🏠 Home</a>
                <a href="../recent.php" class="btn btn-secondary">📋 RECENT PORKPAD</a>
                <a href="?refresh=1" class="btn btn-secondary">🔄 Refresh Stats</a>
            </div>
        </div>
    </div>
</body>
</html>

