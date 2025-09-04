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
    header("Location: dashboard");
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

// Get current paste data (bypasses password check since user owns it)
$pasteData = $paste->getForEdit($pasteId);
if (!$pasteData) {
    header("Location: dashboard");
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
                'category_id' => intval($_POST['category_id'] ?? 0),
                'password' => $_POST['password'] ?? ''
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
                <div class="alert alert-<?php echo $messageType; ?> alert-floating">
                    <div class="alert-icon">
                        <?php echo $messageType === 'error' ? '⚠️' : '✅'; ?>
                    </div>
                    <div class="alert-content">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Edit Form -->
            <div class="edit-form-container">
                <form method="POST" class="edit-form-beautiful">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <!-- Form Grid -->
                    <div class="form-grid">
                        <!-- Title Input -->
                        <div class="input-group">
                            <div class="input-label">
                                <span class="label-icon">📝</span>
                                <span class="label-text">paste Title</span>
                            </div>
                            <div class="input-wrapper">
                                <input type="text" id="title" name="title" 
                                       class="input-beautiful" 
                                       placeholder="Give your paste a memorable title..." 
                                       value="<?php echo htmlspecialchars($_POST['title'] ?? $pasteData['title']); ?>">
                                <div class="input-border"></div>
                            </div>
                        </div>

                        <!-- Language Select -->
                        <div class="input-group">
                            <div class="input-label">
                                <span class="label-icon">🔤</span>
                                <span class="label-text">Language</span>
                            </div>
                            <div class="select-wrapper">
                                <select id="language" name="language" class="select-beautiful">
                                    <?php foreach ($supported_languages as $lang => $label): ?>
                                        <option value="<?php echo $lang; ?>" 
                                            <?php echo (($_POST['language'] ?? $pasteData['language']) === $lang) ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="select-border"></div>
                                <div class="select-arrow">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <?php if (isset($categories)): ?>
                        <!-- Category Select -->
                        <div class="input-group">
                            <div class="input-label">
                                <span class="label-icon">🏷️</span>
                                <span class="label-text">Category</span>
                            </div>
                            <div class="select-wrapper">
                                <select id="category_id" name="category_id" class="select-beautiful">
                                    <?php foreach ($categories as $catId => $catName): ?>
                                        <option value="<?php echo $catId; ?>" 
                                            <?php echo (($_POST['category_id'] ?? $pasteData['category_id'] ?? 0) == $catId) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($catName); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="select-border"></div>
                                <div class="select-arrow">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Password Field -->
                        <div class="input-group">
                            <div class="input-label">
                                <span class="label-icon">🔒</span>
                                <span class="label-text">Password </span>
                                <span class="label-hint">Leave empty to remove password protection</span>
                            </div>
                            <div class="input-wrapper">
                                <input type="password" id="password" name="password" 
                                       class="input-beautiful" 
                                       placeholder="Enter new password (leave empty to remove protection)">
                                <div class="input-border"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Textarea -->
                    <div class="input-group content-group">
                        <div class="input-label">
                            <span class="label-icon">📄</span>
                            <span class="label-text">paste Content</span>
                            <span class="label-hint">Make your changes here</span>
                        </div>
                        <div class="textarea-wrapper">
                            <textarea id="content" name="content" 
                                      class="textarea-beautiful" 
                                      placeholder="paste your code or text here..." 
                                      required><?php echo htmlspecialchars($_POST['content'] ?? $pasteData['content']); ?></textarea>
                            <div class="textarea-border"></div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">SAVE CHANGES</button>
                        <a href="view.php?id=<?php echo $pasteId; ?>" class="btn btn-secondary">CANCEL</a>
                    </div>
                </form>
            </div>
        </div>


    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 <?php echo SITE_NAME; ?>. Made with ❤️ by <a href="https://github.com/SanjiProject">Sanji Project</a></p>
        </div>
    </footer>

    <style>
        /* Beautiful Edit Page Styles */
        
        .alert-floating {
            border-radius: var(--radius-lg);
            border: 2px solid var(--black);
            box-shadow: 4px 4px 0px var(--black);
            display: flex;
            align-items: center;
            gap: var(--space-3);
            margin-bottom: var(--space-6);
        }
        
        .alert-icon {
            font-size: 1.5rem;
        }
        
        .alert-content {
            flex: 1;
            font-weight: 600;
        }
        
        .edit-form-container {
            background: var(--white);
            border: 2px solid var(--black);
            border-radius: var(--radius-lg);
            box-shadow: 4px 4px 0px var(--black);
            overflow: hidden;
        }
        
        .edit-form-beautiful {
            padding: var(--space-8);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: var(--space-6);
            margin-bottom: var(--space-8);
        }
        
        .input-group {
            margin-bottom: var(--space-6);
        }
        
        .content-group {
            grid-column: 1 / -1;
        }
        
        .input-label {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            margin-bottom: var(--space-3);
        }
        
        .label-icon {
            font-size: 1.2rem;
        }
        
        .label-text {
            font-weight: 700;
            font-size: 1rem;
            color: var(--black);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .label-hint {
            font-size: 0.8rem;
            color: #666;
            font-weight: 400;
            text-transform: none;
            letter-spacing: normal;
            margin-left: auto;
        }
        
        .input-wrapper, .select-wrapper, .textarea-wrapper {
            position: relative;
        }
        
        .input-beautiful, .select-beautiful, .textarea-beautiful {
            width: 100%;
            padding: var(--space-4);
            font-size: 1rem;
            font-family: inherit;
            border: 2px solid var(--black);
            border-radius: var(--radius-lg);
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
        }
        
        .input-beautiful:hover, .select-beautiful:hover, .textarea-beautiful:hover,
        .input-beautiful:focus, .select-beautiful:focus, .textarea-beautiful:focus {
            outline: none;
            background: #ffffff !important;
            transform: translateY(-2px);
            box-shadow: 4px 4px 0px var(--black);
        }
        
        .textarea-beautiful {
            min-height: 300px;
            resize: vertical;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        .select-beautiful {
            appearance: none;
            cursor: pointer;
            padding-right: var(--space-10);
        }
        
        .select-arrow {
            position: absolute;
            right: var(--space-4);
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            pointer-events: none;
            z-index: 3;
        }
        
        .select-arrow svg {
            width: 100%;
            height: 100%;
            color: var(--black);
        }
        
        .input-border, .select-border, .textarea-border {
            position: absolute;
            top: 4px;
            left: 4px;
            right: -4px;
            bottom: -4px;
            background: var(--black);
            border-radius: var(--radius-lg);
            z-index: 1;
            transition: all 0.3s ease;
        }
        
        .form-actions {
            display: flex;
            gap: var(--space-4);
            justify-content: center;
            margin-top: var(--space-8);
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: var(--space-4);
            }
            
            .edit-form-beautiful {
                padding: var(--space-4);
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .form-actions .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .form-actions {
                flex-direction: column;
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
