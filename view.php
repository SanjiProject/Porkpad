<?php
require_once 'config.php';
require_once 'classes/Paste.php';
require_once 'classes/User.php';
require_once 'classes/SEO.php';

session_start();

$paste = new Paste();
$user = new User();
$pasteData = null;
$error = '';
$passwordRequired = false;

$id = $_GET['id'] ?? '';
if (empty($id)) {
    header("Location: index.php");
    exit;
}

// Handle password submission
if ($_POST && isset($_POST['password'])) {
    $pasteData = $paste->get($id, $_POST['password']);
} else {
    $pasteData = $paste->get($id);
}

// Get user information if paste has an owner
$pasteOwner = null;
if (is_array($pasteData) && isset($pasteData['user_id']) && $pasteData['user_id']) {
    $pasteOwner = $user->getById($pasteData['user_id']);
}

if ($pasteData === false) {
    $error = 'paste not found or has expired';
} elseif ($pasteData === 'password_required') {
    $passwordRequired = true;
    if ($_POST) {
        $error = 'Incorrect password';
    }
} else {
    // Increment view count only when content is actually displayed
    $paste->incrementViews($id);
}

// Get current user if logged in
$currentUser = $user->isLoggedIn() ? $user->getCurrentUser() : null;

// Get RECENT PORKPAD for sidebar
$recentpastes = $paste->getRecent(5);
$stats = $paste->getStats();

// Setup SEO
$seo = new SEO();
if (is_array($pasteData)) {
    // Get paste author
    $pasteOwner = null;
    if (is_array($pasteData) && isset($pasteData['user_id']) && $pasteData['user_id']) {
        $pasteOwner = $user->getById($pasteData['user_id']);
    }
    
    // Create description from content preview
    $contentPreview = substr(strip_tags($pasteData['content']), 0, 150);
    if (strlen($pasteData['content']) > 150) {
        $contentPreview .= '...';
    }
    
    $seo->setTitle($pasteData['title'])
        ->setDescription($contentPreview)
        ->setKeywords(['code snippet', $pasteData['language'], 'programming', 'pastebin', SITE_NAME])
        ->setType('article')
        ->setPublishedTime($pasteData['created_at']);
    
    if (isset($pasteData['updated_at'])) {
        $seo->setModifiedTime($pasteData['updated_at']);
    }
    
    if ($pasteOwner) {
        $seo->setAuthor($pasteOwner['username']);
    }
} else {
    $seo->setTitle('Paste Not Found')
        ->setDescription('The requested paste could not be found on ' . SITE_NAME)
        ->setType('website');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo $seo->renderMetaTags(); ?>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <?php 
    if (is_array($pasteData)) {
        echo $seo->renderPasteStructuredData($pasteData, $pasteOwner);
    } else {
        echo $seo->renderStructuredData();
    }
    ?>
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

    <?php if ($error): ?>
        <!-- Error Page -->
        <div style="padding: 0;">
            <div class="container-sm">
                <div class="empty-state">
                    <div class="empty-icon">❌</div>
                    <h3>paste NOT FOUND</h3>
                    <p>The paste you're looking for doesn't exist or has expired</p>
                    <a href="index.php" class="btn btn-primary">CREATE NEW PORKPAD</a>
                </div>
            </div>
        </div>

    <?php elseif ($passwordRequired): ?>
        <!-- Main Content -->
        <section style="padding: 0; margin: 0;">
            <div class="container-lg">
                <div class="dashboard-grid">
                    <!-- Main Content -->
                    <div class="dashboard-main">
                        <?php if ($_POST): ?>
                            <div class="alert alert-error fade-in-up">
                                Incorrect password. Please try again.
                            </div>
                        <?php endif; ?>

                        <!-- Password Protected Content -->
                        <div class="card">
                            <div class="card-header">
                                <h3>🔒 PASSWORD PROTECTED</h3>
                            </div>
                            <div class="card-body">
                                <p style="margin-bottom: var(--space-6); color: var(--gray-600);">
                                    This paste is password protected. Please enter the password to view the content.
                                </p>
                                <form method="POST">
                                    <div class="form-group">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" id="password" name="password" class="form-control"
                                               placeholder="Enter the paste password..." required autofocus>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; text-align: center;">
                                        VIEW PASTE
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="dashboard-sidebar">
                        <!-- About Section -->
                        <div class="card">
                            <div class="card-header">
                                <h3>ABOUT <?php echo SITE_NAME; ?></h3>
                            </div>
                            <div class="card-body">
                                <p>Share text and code snippets easily. Create pastes and organize with categories.</p>
                                
                                <?php if ($currentUser): ?>
                                    <a href="dashboard" class="btn btn-secondary" style="width: 100%; margin-top: var(--space-6); text-align: center;">
                                        MY DASHBOARD
                                    </a>
                                <?php else: ?>
                                    <a href="index.php" class="btn btn-primary" style="width: 100%; margin-top: var(--space-6); text-align: center;">
                                        CREATE PASTE
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    <?php else: ?>
        <!-- paste Content -->
        <?php if (isset($_GET['raw'])): ?>
            <?php
            header('Content-Type: text/plain; charset=utf-8');
            header('Content-Disposition: inline; filename="' . $pasteData['title'] . '.txt"');
            echo $pasteData['content'];
            exit;
            ?>
        <?php endif; ?>

        <!-- Main Content -->
        <section style="padding: 0; margin: 0;">
            <div class="container-lg">
                <div class="dashboard-grid">
                    <!-- Main Content -->
                    <div class="dashboard-main">


                        <!-- Code Block -->
                        <div class="card">
                            <div class="card-header">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <h3><?php echo htmlspecialchars($pasteData['title']); ?></h3>
                                        <div style="margin-top: var(--space-2); display: flex; align-items: center; gap: var(--space-4); flex-wrap: wrap;">
                                            <!-- User Info -->
                                            <div style="display: flex; align-items: center; gap: var(--space-2);">
                                                <span style="font-size: 0.875rem; color: var(--gray-600); font-weight: 500;">By:</span>
                                                <?php if ($pasteOwner): ?>
                                                    <a href="user.php?id=<?php echo $pasteOwner['id']; ?>" 
                                                       style="font-size: 0.875rem; font-weight: 700; color: var(--black); text-decoration: none; border-bottom: 2px solid transparent; transition: all 0.2s ease;"
                                                       onmouseover="this.style.borderBottomColor='var(--black)'"
                                                       onmouseout="this.style.borderBottomColor='transparent'">
                                                        <?php echo htmlspecialchars($pasteOwner['username']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span style="font-size: 0.875rem; color: var(--gray-500); font-weight: 500; font-style: italic;">Anonymous</span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- paste Info -->
                                            <div style="display: flex; align-items: center; gap: var(--space-4); font-size: 0.875rem; color: var(--gray-600); flex-wrap: wrap;">
                                                <span class="badge badge-secondary" style="font-size: 0.75rem;">
                                                    <?php echo $supported_languages[$pasteData['language']] ?? $pasteData['language']; ?>
                                                </span>
                                                
                                                <?php if (isset($pasteData['category_id']) && $pasteData['category_id'] > 0 && isset($categories[$pasteData['category_id']])): ?>
                                                    <a href="category.php?id=<?php echo $pasteData['category_id']; ?>" 
                                                       class="badge badge-primary" 
                                                       style="font-size: 0.75rem; text-decoration: none; transition: all 0.2s ease;"
                                                       onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='2px 2px 0px var(--black)'"
                                                       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                                        📁 <?php echo htmlspecialchars($categories[$pasteData['category_id']]); ?>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <span><?php echo number_format($pasteData['views']); ?> views</span>
                                                <span><?php echo date('M j, Y \a\t g:i A', strtotime($pasteData['created_at'])); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: var(--space-2);">
                                        <button onclick="copyToClipboard()" class="btn btn-accent btn-sm">COPY</button>
                                        <?php if ($currentUser && $paste->canEdit($id)): ?>
                                            <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-primary btn-sm">EDIT</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body" style="padding: 0;">
                                <div style="background: #ffffff; color: var(--black); padding: var(--space-6); border-radius: 0 0 var(--radius-xl) var(--radius-xl); overflow-x: auto; border: 2px solid var(--black); border-top: none;">
                                    <pre style="margin: 0; font-family: var(--font-mono); font-size: 0.875rem; line-height: 1.6; white-space: pre-wrap; word-break: break-all;"><code class="language-<?php echo htmlspecialchars($pasteData['language']); ?>" id="paste-content"><?php echo htmlspecialchars($pasteData['content']); ?></code></pre>
                                </div>
                            </div>
                        </div>

                        <!-- Spacing -->
                        <div style="margin: var(--space-8) 0;"></div>

                        <!-- Share Section -->
                        <div class="card">
                            <div class="card-header">
                                <h3>SHARE THIS PORKPAD</h3>
                            </div>
                            <div class="card-body">
                                <!-- URL Copy Section -->
                                <div style="display: flex; gap: var(--space-4); align-items: center; margin-bottom: var(--space-6);">
                                    <input type="text" id="share-url" class="form-control" 
                                           value="<?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['REQUEST_URI']), '/') . '/' . $id; ?>" 
                                           readonly style="flex: 1;">
                                    <button onclick="copyShareUrl()" class="btn btn-primary">COPY LINK</button>
                                </div>
                                
                                <!-- Social Share Buttons -->
                                <div style="display: flex; gap: var(--space-4); align-items: center; justify-content: center;">
                                    <button onclick="shareOnFacebook()" class="btn btn-secondary" style="background: #1877f2; color: white; border: 2px solid #1877f2; display: flex; align-items: center; gap: var(--space-2);">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                        </svg>
                                        FACEBOOK
                                    </button>
                                    <button onclick="shareOnX()" class="btn btn-secondary" style="background: #000000; color: white; border: 2px solid #000000; display: flex; align-items: center; gap: var(--space-2);">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.80l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                        </svg>
                                        X (TWITTER)
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="dashboard-sidebar">
                        <!-- RECENT PORKPAD Card -->
                        <div class="card">
                            <div class="card-header">
                                <h3>RECENT PORKPAD</h3>
                            </div>
                            <div class="card-body" style="padding: 0;">
                                <?php if (empty($recentpastes)): ?>
                                    <div style="padding: var(--space-6); text-align: center; color: var(--gray-500);">
                                        <div style="font-size: 2rem; margin-bottom: var(--space-2);">📄</div>
                                        <div>No RECENT PORKPAD</div>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recentpastes as $recentpaste): ?>
                                        <div style="padding: var(--space-4); border-bottom: 1px solid var(--gray-200); transition: all 0.2s ease;" 
                                             onmouseover="this.style.background='var(--gray-50)'" 
                                             onmouseout="this.style.background='transparent'">
                                            <div style="margin-bottom: var(--space-2);">
                                                <a href="<?php echo $recentpaste['id']; ?>" 
                                                   style="font-weight: 700; color: var(--black); text-decoration: none; border: none;">
                                                    <?php echo htmlspecialchars($recentpaste['title']); ?>
                                                </a>
                                            </div>
                                            <div style="display: flex; gap: var(--space-2); font-size: 0.75rem; color: var(--gray-600);">
                                                <span class="badge badge-secondary" style="font-size: 0.7rem;">
                                                    <?php echo $supported_languages[$recentpaste['language']] ?? $recentpaste['language']; ?>
                                                </span>
                                                <span><?php echo number_format($recentpaste['views']); ?> views</span>
                                                <span><?php echo date('M j', strtotime($recentpaste['created_at'])); ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <div style="padding: var(--space-4); text-align: center;">
                                        <a href="recent" class="btn btn-secondary btn-sm" style="width: 100%; text-align: center;">
                                            VIEW ALL
                                        </a>
                                    </div>
                                <?php endif; ?>
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
                                    <?php if ($currentUser): ?>
                                        <a href="dashboard" class="btn btn-secondary" style="text-align: center;">
                                            MY DASHBOARD
                                        </a>
                                        <a href="logout.php" class="btn btn-secondary" style="text-align: center;">
                                            LOGOUT
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
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 <?php echo SITE_NAME; ?>. Made with ❤️ by <a href="https://github.com/SanjiProject">Sanji Project</a></p>
        </div>
    </footer>

    <!-- Prism.js for syntax highlighting -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
    
    <script>
        function copyToClipboard() {
            const content = document.getElementById('paste-content').textContent;
            navigator.clipboard.writeText(content).then(() => {
                showNotification('Content copied to clipboard!');
            }).catch(() => {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = content;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showNotification('Content copied to clipboard!');
            });
        }

        function copyShareUrl() {
            const shareUrl = document.getElementById('share-url');
            shareUrl.select();
            shareUrl.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(shareUrl.value).then(() => {
                showNotification('Share URL copied to clipboard!');
            });
        }

        function downloadpaste() {
            const content = document.getElementById('paste-content').textContent;
            const title = "<?php echo addslashes($pasteData['title'] ?? 'paste'); ?>";
            const language = "<?php echo $pasteData['language'] ?? 'txt'; ?>";
            
            const blob = new Blob([content], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = title + '.' + (language === 'text' ? 'txt' : language);
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            showNotification('File downloaded!');
        }

        function showNotification(message) {
            // Create notification element
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: var(--black);
                color: var(--white);
                padding: var(--space-4) var(--space-6);
                border-radius: var(--radius);
                font-weight: 700;
                font-size: 0.875rem;
                z-index: 1000;
                transform: translateX(100%);
                transition: transform 0.3s ease;
                border: 2px solid var(--black);
                box-shadow: 4px 4px 0px var(--gray-300);
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            // Show notification
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 10);
            
            // Hide notification
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        function shareOnFacebook() {
            const url = document.getElementById('share-url').value;
            const title = "<?php echo addslashes($pasteData['title'] ?? 'Check out this paste'); ?>";
            // Use Facebook sharer with the format you specified
            const facebookUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
            const popup = window.open(facebookUrl, 'facebook-share', 'width=626,height=436,resizable=yes,scrollbars=yes');
            if (popup) {
                popup.focus();
                showNotification('Opening Facebook share...');
            } else {
                showNotification('Please enable popups to share on Facebook');
            }
        }

        function shareOnX() {
            const url = document.getElementById('share-url').value;
            const title = "<?php echo addslashes($pasteData['title'] ?? 'Check out this paste'); ?>";
            const text = `${title} - Check it out on paste!`;
            const twitterUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}&hashtags=paste,pastebin,code`;
            const popup = window.open(twitterUrl, 'twitter-share', 'width=600,height=500,resizable=yes,scrollbars=yes');
            if (popup) {
                popup.focus();
                showNotification('Opening X (Twitter) share...');
            } else {
                showNotification('Please enable popups to share on X');
            }
        }


    </script>

    <style>
        .card-body pre {
            background: #ffffff !important;
            color: var(--black) !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        .card-body code {
            background: transparent !important;
            color: var(--black) !important;
            font-family: var(--font-mono) !important;
        }
        
        /* Override Prism styles for light theme */
        .token.comment,
        .token.prolog,
        .token.doctype,
        .token.cdata {
            color: #6a737d !important;
        }
        
        .token.punctuation {
            color: #24292e !important;
        }
        
        .token.property,
        .token.tag,
        .token.boolean,
        .token.number,
        .token.constant,
        .token.symbol,
        .token.deleted {
            color: #005cc5 !important;
        }
        
        .token.selector,
        .token.attr-name,
        .token.string,
        .token.char,
        .token.builtin,
        .token.inserted {
            color: #032f62 !important;
        }
        
        .token.operator,
        .token.entity,
        .token.url,
        .language-css .token.string,
        .style .token.string {
            color: #24292e !important;
        }
        
        .token.atrule,
        .token.attr-value,
        .token.keyword {
            color: #d73a49 !important;
        }
        
        .token.function,
        .token.class-name {
            color: #6f42c1 !important;
        }
        
        .token.regex,
        .token.important,
        .token.variable {
            color: #e36209 !important;
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            [style*="display: flex; gap: var(--space-4)"] {
                flex-direction: column;
            }
            
            [style*="display: flex; gap: var(--space-4); align-items: center;"] {
                flex-direction: column;
                align-items: stretch !important;
            }
        }
    </style>
</body>
</html>