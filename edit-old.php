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

$pasteId = $_GET['id'] ?? '';
if (empty($pasteId)) {
    header("Location: dashboard.php");
    exit;
}

// Check if user can edit this paste
if (!$paste->canEdit($pasteId)) {
    header("Location: view.php?id=" . $pasteId);
    exit;
}

$currentUser = $user->getCurrentUser();
$message = '';
$messageType = '';

// Get current paste data
$pasteData = $paste->get($pasteId);
if (!$pasteData) {
    header("Location: dashboard.php");
    exit;
}

// Handle form submission
if ($_POST) {
    // Verify CSRF token
    session_start();
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $message = 'Invalid CSRF token';
        $messageType = 'error';
    } else {
        // Validate input
        $content = trim($_POST['content'] ?? '');
        if (empty($content)) {
            $message = 'Content cannot be empty';
            $messageType = 'error';
        } elseif (strlen($content) > MAX_paste_SIZE) {
            $message = 'Content exceeds maximum size limit';
            $messageType = 'error';
        } else {
            // Update paste
            $title = trim($_POST['title'] ?? '');
            if (empty($title)) {
                $title = 'Untitled';
            }
            
            $updateData = [
                'title' => $title,
                'content' => $content,
                'language' => $_POST['language'] ?? 'text',
                'category_id' => intval($_POST['category_id'] ?? 0)
            ];
            
            $result = $paste->update($pasteId, $updateData);
            if ($result['success']) {
                header("Location: view.php?id=" . $pasteId . "&updated=1");
                exit;
            } else {
                $message = $result['message'];
                $messageType = 'error';
            }
        }
    }
}

// Generate CSRF token
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get revisions for this paste
$revisions = $paste->getRevisions($pasteId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit: <?php echo htmlspecialchars($pasteData['title']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
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
                    <a href="recent.php">Recent</a>
                    <?php if ($currentUser): ?>
                        <a href="dashboard.php">Dashboard</a>
                        <?php if ($currentUser['is_admin']): ?>
                            <a href="admin.php">Admin</a>
                        <?php endif; ?>
                        <span class="user-info"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                        <a href="logout.php" class="btn btn-secondary btn-sm">Logout</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <section style="padding: 0;">
        <div class="container-lg">
            <div class="auth-container">
                <div class="auth-card">
                    <div class="auth-header">
                        <div style="text-align: center;">
                            <h1>✏️ EDIT paste</h1>
                            <p>Make changes to your paste. A revision will be saved automatically.</p>
                        </div>
                        
                        <div class="demo-box">
                            <h3>paste INFO</h3>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: var(--space-3); margin-top: var(--space-3);">
                                <div><strong>ID:</strong> #<?php echo $pasteId; ?></div>
                                <div><strong>Created:</strong> <?php echo date('M j, Y', strtotime($pasteData['created_at'])); ?></div>
                                <div><strong>Views:</strong> <?php echo number_format($pasteData['views']); ?></div>
                            </div>
                        </div>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="auth-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" placeholder="Enter a title for your paste..." 
                                   value="<?php echo htmlspecialchars($_POST['title'] ?? $pasteData['title']); ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="language">Language</label>
                                <select id="language" name="language">
                                    <?php foreach ($supported_languages as $lang => $label): ?>
                                        <option value="<?php echo $lang; ?>" 
                                            <?php echo (($_POST['language'] ?? $pasteData['language']) === $lang) ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if (isset($categories)): ?>
                            <div class="form-group">
                                <label for="category_id">Category</label>
                                <select id="category_id" name="category_id">
                                    <?php foreach ($categories as $catId => $catName): ?>
                                        <option value="<?php echo $catId; ?>" 
                                            <?php echo (($_POST['category_id'] ?? $pasteData['category_id'] ?? 0) == $catId) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($catName); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="content">Content</label>
                            <textarea id="content" name="content" placeholder="paste your code or text here..." rows="15"
                                      required><?php echo htmlspecialchars($_POST['content'] ?? $pasteData['content']); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-large auth-submit">
                            💾 Save Changes
                        </button>
                    </form>

                    <div class="auth-footer">
                        <p><a href="view.php?id=<?php echo $pasteId; ?>">👁️ View paste</a> • <a href="dashboard.php">← Back to Dashboard</a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="footer-content">
            <p>&copy; 2025 <?php echo SITE_NAME; ?>. A modern paste sharing platform.</p>
        </div>
    </footer>

    <style>
        /* Edit-specific styles */
        /* Custom textarea styling for edit page */
        #content {
            min-height: 400px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .edit-info-card, .revisions-card, .help-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
        }

        .info-item {
            margin-bottom: var(--spacing-sm);
            font-size: 0.9rem;
        }

        .info-item strong {
            color: var(--text-primary);
        }

        .revisions-list {
            max-height: 200px;
            overflow-y: auto;
        }

        .revision-item {
            padding: var(--spacing-sm);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            margin-bottom: var(--spacing-xs);
        }

        .revision-number {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.85rem;
        }

        .revision-date, .revision-author {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .more-revisions {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.8rem;
            margin-top: var(--spacing-sm);
        }

        .help-card ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .help-card li {
            color: var(--text-secondary);
            font-size: 0.85rem;
            margin-bottom: var(--spacing-xs);
            padding-left: var(--spacing-md);
            position: relative;
        }

        .help-card li:before {
            content: '•';
            color: var(--primary-color);
            position: absolute;
            left: 0;
        }

        @media (max-width: 768px) {
            .edit-header {
                flex-direction: column;
                align-items: stretch;
            }

            .edit-actions {
                justify-content: center;
            }
        }
    </style>

    <script src="assets/js/main.js"></script>
    <script>
        // Save with Ctrl+S
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                document.querySelector('form').submit();
            }
        });

        // Auto-save draft (could be enhanced later)
        let autoSaveTimer;
        const textarea = document.getElementById('content');
        
        if (textarea) {
            textarea.addEventListener('input', function() {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(() => {
                    // Could implement auto-save to localStorage here
                    console.log('Auto-save draft...');
                }, 5000);
            });
        }
    </script>
</body>
</html>
