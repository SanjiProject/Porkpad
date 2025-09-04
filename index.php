<?php
// Start session and check for existing session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and required classes
require_once 'config.php';
require_once 'classes/SEO.php';

// Check if required files exist
$required_files = [
    'classes/Database.php',
    'classes/Paste.php',
    'classes/User.php',
    'classes/Captcha.php'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        die("Error: Required file $file not found.");
    }
}

require_once 'classes/Database.php';
require_once 'classes/Paste.php';
require_once 'classes/User.php';
require_once 'classes/Captcha.php';

// Initialize classes
try {
    $paste = new Paste();
    $user = new User();
    $captcha = new Captcha();
} catch (Exception $e) {
    die("Error: Unable to initialize classes. " . $e->getMessage());
}

// Initialize CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get current user
$currentUser = $user->getCurrentUser();

// Initialize variables
$message = '';
$messageType = 'success';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Invalid security token. Please try again.';
        $messageType = 'error';
    } else {
        // Validate captcha for all users (bot protection)
        $showCaptcha = $captcha->requiresCaptcha();
        if ($showCaptcha && !$captcha->verify($_POST['captcha_answer'] ?? '')) {
            $message = 'Incorrect answer to security question. Please try again.';
            $messageType = 'error';
        } else {
            // Prepare data
            $title = trim($_POST['title'] ?? '');
            if (empty($title)) {
                $title = 'Untitled';
            }
            
            $data = [
                'title' => $title,
                'content' => $_POST['content'] ?? '',
                'language' => $_POST['language'] ?? 'text',
                'category_id' => (int)($_POST['category_id'] ?? 0),
                'is_private' => isset($_POST['is_private']) ? 1 : 0,
                'password' => $_POST['password'] ?? '',

                'custom_url' => trim($_POST['custom_url'] ?? '')
            ];
            
            // Validate required fields
            if (empty($data['content'])) {
                $message = 'Content is required.';
                $messageType = 'error';
            } elseif (!empty($data['custom_url'])) {
                // Validate custom URL format (only for logged-in users)
                if (!$currentUser) {
                    $message = 'Custom URLs are only available for registered users.';
                    $messageType = 'error';
                } elseif (!preg_match('/^[a-zA-Z0-9\-_]+$/', $data['custom_url'])) {
                    $message = 'Custom URL can only contain letters, numbers, hyphens, and underscores.';
                    $messageType = 'error';
                } elseif (strlen($data['custom_url']) < 3) {
                    $message = 'Custom URL must be at least 3 characters long.';
                    $messageType = 'error';
                } elseif (strlen($data['custom_url']) > 50) {
                    $message = 'Custom URL must be less than 50 characters long.';
                    $messageType = 'error';
                } elseif ($paste->urlExists($data['custom_url'])) {
                    $message = 'This custom URL is already taken. Please choose a different one.';
                    $messageType = 'error';
                } else {
                    // Custom URL is valid, proceed with creation
                    $pasteId = $paste->create($data);
                    
                    if ($pasteId) {
                        // Redirect directly to the paste view using clean URL
                        header("Location: " . $pasteId);
                        exit;
                    } else {
                        $message = 'Failed to create paste. Please try again.';
                        $messageType = 'error';
                    }
                }
            } else {
                // CREATE PORKPAD with auto-generated ID
                $pasteId = $paste->create($data);
                
                if ($pasteId) {
                    // Redirect directly to the paste view using clean URL
                    header("Location: " . $pasteId);
                    exit;
                } else {
                    $message = 'Failed to create paste. Please try again.';
                    $messageType = 'error';
                }
            }
        }
    }
    
    // Regenerate CSRF token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get captcha question if needed
$showCaptcha = $captcha->requiresCaptcha();
$captchaQuestion = $showCaptcha ? $captcha->generateQuestion() : '';

// Get RECENT PORKPAD and stats
$recentpastes = $paste->getRecent(5);
$stats = $paste->getStats();

// Setup SEO
$seo = new SEO();
$seo->setTitle('Create and Share Code Snippets')
    ->setDescription('Create, share, and collaborate on code snippets and text. Fast, secure, and easy to use paste sharing platform with syntax highlighting and custom URLs.')
    ->setKeywords(['pastebin', 'code sharing', 'text sharing', 'snippet sharing', 'developer tools', 'programming', 'syntax highlighting', 'custom urls'])
    ->setType('website');
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
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> fade-in-up">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <!-- Main Content - Create Form -->
                <div class="dashboard-main">
                    <div class="card fade-in-up">
                        <div class="card-header">
                            <h3>CREATE NEW PORKPAD</h3>
                        </div>
                <div class="card-body">
                    <form method="POST" id="pasteForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" id="title" name="title" class="form-control" 
                                       placeholder="Enter a descriptive title..." 
                                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                            </div>
                            <?php if ($currentUser): ?>
                            <div class="form-group">
                                <label for="custom_url" class="form-label">Custom URL (Optional)</label>
                                <div style="display: flex; align-items: center; gap: var(--space-2);">
                                    <span style="font-size: 0.9rem; color: var(--gray-600); white-space: nowrap;">
                                        <?php echo $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['REQUEST_URI']), '/'); ?>/
                                    </span>
                                    <input type="text" id="custom_url" name="custom_url" class="form-control" 
                                           placeholder="my-custom-name" 
                                           pattern="[a-zA-Z0-9\-_]+"
                                           title="Only letters, numbers, hyphens, and underscores allowed"
                                           minlength="3" maxlength="50"
                                           oninput="validateCustomUrl(this)"
                                           value="<?php echo htmlspecialchars($_POST['custom_url'] ?? ''); ?>">
                                </div>
                                <small class="form-help">Leave empty for auto-generated URL. Only letters, numbers, hyphens, and underscores allowed.</small>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="language" class="form-label">Language</label>
                                <select id="language" name="language" class="form-control">
                                    <?php foreach ($supported_languages as $lang => $label): ?>
                                        <option value="<?php echo $lang; ?>" 
                                            <?php echo (($_POST['language'] ?? 'text') === $lang) ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="category_id" class="form-label">Category</label>
                                <select id="category_id" name="category_id" class="form-control">
                                    <?php foreach ($categories as $catId => $catName): ?>
                                        <option value="<?php echo $catId; ?>" 
                                            <?php echo (($_POST['category_id'] ?? $_GET['category'] ?? 0) == $catId) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($catName); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>


                        <div class="form-group">
                            <label for="content" class="form-label">Content</label>
                            <textarea id="content" name="content" class="form-control" 
                                      placeholder="paste your code, text, or any content here..." 
                                      required><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                        </div>



                        <?php if ($showCaptcha): ?>
                        <div class="form-group">
                            <label for="captcha_answer" class="form-label">Security Check</label>
                            <div style="background: var(--gray-50); padding: var(--space-4); border-radius: var(--radius-lg); margin-bottom: var(--space-3);">
                                <strong>What is <?php echo $captchaQuestion; ?>?</strong>
                            </div>
                            <input type="number" id="captcha_answer" name="captcha_answer" class="form-control"
                                   placeholder="Enter your answer" required style="max-width: 200px;">
                        </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <div class="checkbox-wrapper">
                                <input type="checkbox" id="is_private" name="is_private" 
                                       <?php echo isset($_POST['is_private']) ? 'checked' : ''; ?>>
                                <label for="is_private" class="form-label" style="margin: 0; cursor: pointer;">
                                    MAKE THIS PORKPAD PRIVATE
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Password Protection (Optional)</label>
                            <input type="password" id="password" name="password" class="form-control"
                                   placeholder="Enter password to protect this paste..."
                                   value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>">
                            <small class="form-help">Leave empty for no password protection</small>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-lg">
                                CREATE PORKPAD
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                CLEAR
                            </button>
                        </div>
                    </form>
                </div>
            </div>
                </div>

                <!-- Sidebar - RECENT PORKPAD -->
                <div class="dashboard-sidebar">
                    <!-- RECENT PORKPAD Card -->
                    <div class="card fade-in-up" style="animation-delay: 0.2s;">
                        <div class="card-header">
                            <h3>RECENT PORKPAD</h3>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <?php if (empty($recentpastes)): ?>
                                <div style="padding: var(--space-6); text-align: center; color: var(--gray-500);">
                                    <div style="font-size: 2rem; margin-bottom: var(--space-2);">📄</div>
                                    <div>No pastes yet</div>
                                    <div style="font-size: 0.8rem; margin-top: var(--space-1);">Be the first to create one!</div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recentpastes as $recentpaste): ?>
                                    <div style="padding: var(--space-4); border-bottom: 1px solid var(--gray-200); transition: all 0.2s ease;" 
                                         onmouseover="this.style.background='var(--gray-50)'" 
                                         onmouseout="this.style.background='transparent'">
                                        <div style="margin-bottom: var(--space-2);">
                                            <a href="view.php?id=<?php echo $recentpaste['id']; ?>" 
                                               style="font-weight: 700; color: var(--black); text-decoration: none; border: none;">
                                                <?php echo htmlspecialchars($recentpaste['title']); ?>
                                            </a>
                                        </div>
                                        <div style="display: flex; gap: var(--space-2); font-size: 0.75rem; color: var(--gray-600); flex-wrap: wrap;">
                                            <span class="badge badge-secondary" style="font-size: 0.7rem;">
                                                <?php echo $supported_languages[$recentpaste['language']] ?? $recentpaste['language']; ?>
                                            </span>
                                            <?php if (isset($recentpaste['category_id']) && $recentpaste['category_id'] > 0): ?>
                                                <span class="badge badge-primary" style="font-size: 0.7rem;">
                                                    <?php echo $categories[$recentpaste['category_id']] ?? 'Unknown'; ?>
                                                </span>
                                            <?php endif; ?>
                                            <span><?php echo number_format($recentpaste['views']); ?> views</span>
                                            <span><?php echo date('M j', strtotime($recentpaste['created_at'])); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <div style="padding: var(--space-4); text-align: center;">
                                    <a href="recent" class="btn btn-secondary btn-sm" style="width: 100%; text-align: center;">
                                        VIEW ALL PORKPAD
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Info Card -->
                    <div class="card fade-in-up" style="animation-delay: 0.3s; margin-top: var(--space-6);">
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

    <script>
        // Simple form enhancement
        document.getElementById('pasteForm').addEventListener('submit', function(e) {
            const content = document.getElementById('content').value.trim();
            if (!content) {
                e.preventDefault();
                alert('Please enter some content for your paste.');
                return;
            }
            
            // Add loading state
            const submitBtn = document.querySelector('button[type="submit"]');
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
        });

        // Character counter for content
        const contentTextarea = document.getElementById('content');
        const counter = document.createElement('div');
        counter.style.cssText = 'font-size: 0.8rem; color: var(--gray-500); margin-top: var(--space-2); text-align: right;';
        contentTextarea.parentNode.appendChild(counter);

        function updateCounter() {
            const length = contentTextarea.value.length;
            counter.textContent = `${length.toLocaleString()} characters`;
        }

        contentTextarea.addEventListener('input', updateCounter);
        updateCounter();

        // Auto-resize textarea
        contentTextarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.max(200, this.scrollHeight) + 'px';
        });

        // Custom URL validation
        function validateCustomUrl(input) {
            const value = input.value;
            const helpText = input.parentNode.parentNode.querySelector('.form-help');
            
            if (value === '') {
                helpText.textContent = 'Leave empty for auto-generated URL. Only letters, numbers, hyphens, and underscores allowed.';
                helpText.style.color = 'var(--gray-600)';
                input.style.borderColor = '';
                return;
            }
            
            if (value.length < 3) {
                helpText.textContent = 'Custom URL must be at least 3 characters long.';
                helpText.style.color = '#ef4444';
                input.style.borderColor = '#ef4444';
                return;
            }
            
            if (value.length > 50) {
                helpText.textContent = 'Custom URL must be less than 50 characters long.';
                helpText.style.color = '#ef4444';
                input.style.borderColor = '#ef4444';
                return;
            }
            
            if (!/^[a-zA-Z0-9\-_]+$/.test(value)) {
                helpText.textContent = 'Only letters, numbers, hyphens, and underscores allowed.';
                helpText.style.color = '#ef4444';
                input.style.borderColor = '#ef4444';
                return;
            }
            
            // Valid URL
            helpText.textContent = 'URL looks good! ✓';
            helpText.style.color = '#22c55e';
            input.style.borderColor = '#22c55e';
        }
    </script>
</body>
</html>