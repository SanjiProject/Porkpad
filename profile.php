<?php
require_once 'config.php';
require_once 'classes/User.php';

$user = new User();

// Check if user is logged in
if (!$user->isLoggedIn()) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$currentUser = $user->getCurrentUser();
$message = '';
$messageType = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $bio = trim($_POST['bio'] ?? '');
        $result = $user->updateProfile($currentUser['id'], ['bio' => $bio]);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
        
        if ($result['success']) {
            $currentUser = $user->getCurrentUser(); // Refresh user data
        }
    } 
    elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if ($newPassword !== $confirmPassword) {
            $message = 'New passwords do not match';
            $messageType = 'error';
        } else {
            $result = $user->changePassword($currentUser['id'], $currentPassword, $newPassword);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
        }
    }
}

// Get user stats
$userStats = $user->getUserStats($currentUser['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - <?php echo SITE_NAME; ?></title>
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

    <section style="padding: 0;">
        <div class="container-lg">
            <div class="profile-container">
                

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="profile-grid">
                    <!-- Main Content -->
                    <div class="profile-main">
                        <!-- Profile Information Section -->
                        <div class="profile-section">
                            <div class="section-header">👤 PROFILE INFORMATION</div>
                            <div class="section-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_profile">
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="username">USERNAME</label>
                                            <input type="text" id="username" value="<?php echo htmlspecialchars($currentUser['username']); ?>" readonly>
                                            <small class="form-help">Username cannot be changed</small>
                                        </div>
                                        <div class="form-group">
                                            <label for="email">EMAIL ADDRESS</label>
                                            <input type="email" id="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" readonly>
                                            <small class="form-help">Email cannot be changed</small>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="bio">BIO (OPTIONAL)</label>
                                        <textarea id="bio" name="bio" placeholder="Tell us about yourself..." 
                                                  maxlength="500" rows="4"><?php echo htmlspecialchars($currentUser['bio'] ?? ''); ?></textarea>
                                        <small class="form-help" id="bio-counter">Maximum 500 characters</small>
                                    </div>

                                    <button type="submit" class="btn btn-primary">UPDATE PROFILE</button>
                                </form>
                            </div>
                        </div>

                        <!-- Change Password Section -->
                        <div class="profile-section">
                            <div class="section-header">🔒 CHANGE PASSWORD</div>
                            <div class="section-body">
                                <form method="POST" onsubmit="return validatePassword()">
                                    <input type="hidden" name="action" value="change_password">
                                    
                                    <div class="form-group">
                                        <label for="current_password">CURRENT PASSWORD</label>
                                        <input type="password" id="current_password" name="current_password" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="new_password">NEW PASSWORD</label>
                                        <input type="password" id="new_password" name="new_password" 
                                               minlength="6" required>
                                        <div class="password-indicator" id="password-strength"></div>
                                    </div>

                                    <div class="form-group">
                                        <label for="confirm_password">CONFIRM NEW PASSWORD</label>
                                        <input type="password" id="confirm_password" name="confirm_password" 
                                               minlength="6" required>
                                        <div class="password-indicator" id="password-match"></div>
                                    </div>

                                    <button type="submit" class="btn btn-primary">CHANGE PASSWORD</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="profile-sidebar">
                        <!-- Account Stats -->
                        <div class="card">
                            <div class="card-header">📊 ACCOUNT STATISTICS</div>
                            <div class="card-body">
                                <div class="stats-grid">
                                    <div class="stat-item">
                                        <div class="stat-number"><?php echo number_format($userStats['total_pastes']); ?></div>
                                        <div class="stat-label">TOTAL PASTES</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number"><?php echo number_format($userStats['total_views']); ?></div>
                                        <div class="stat-label">TOTAL VIEWS</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number"><?php echo number_format($userStats['private_pastes']); ?></div>
                                        <div class="stat-label">PRIVATE PASTES</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number"><?php echo number_format($userStats['week_pastes']); ?></div>
                                        <div class="stat-label">THIS WEEK</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Account Info -->
                        <div class="card">
                            <div class="card-header">ℹ️ ACCOUNT INFORMATION</div>
                            <div class="card-body">
                                <div class="info-item">
                                    <div class="info-label">MEMBER SINCE</div>
                                    <div class="info-value"><?php echo date('F j, Y', strtotime($currentUser['created_at'])); ?></div>
                                </div>
                                <?php if ($currentUser['last_login']): ?>
                                    <div class="info-item">
                                        <div class="info-label">LAST LOGIN</div>
                                        <div class="info-value"><?php echo date('M j, Y g:i A', strtotime($currentUser['last_login'])); ?></div>
                                    </div>
                                <?php endif; ?>
                                <div class="info-item">
                                    <div class="info-label">ACCOUNT TYPE</div>
                                    <div class="info-value"><?php echo $currentUser['is_admin'] ? '👑 Administrator' : '👤 Regular User'; ?></div>
                                </div>
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
        /* Profile Page Specific Styles */
        .profile-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: var(--space-8) 0;
        }

        .profile-header {
            text-align: center;
            margin-bottom: var(--space-8);
        }

        .profile-header h1 {
            font-size: 2.5rem;
            font-weight: 900;
            color: var(--black);
            margin-bottom: var(--space-2);
        }

        .profile-header p {
            font-size: 1.1rem;
            color: var(--gray-600);
            margin-bottom: var(--space-6);
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: var(--space-8);
        }

        .profile-section {
            background: var(--white);
            border: 2px solid var(--black);
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-6);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            background: var(--gray-50);
            color: var(--black);
            padding: var(--space-3) var(--space-4);
            font-size: 0.9rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: -0.025em;
            border-bottom: 2px solid var(--black);
        }

        .section-body {
            padding: var(--space-6);
        }

        .form-group {
            margin-bottom: var(--space-6);
        }

        .form-group label {
            display: block;
            font-weight: 700;
            margin-bottom: var(--space-2);
            color: var(--black);
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.05em;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: var(--space-3);
            border: 2px solid var(--black);
            border-radius: var(--radius);
            font-size: 1rem;
            background: #fffffb;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--black);
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
        }

        .form-group input[readonly] {
            background: var(--gray-50);
            color: var(--gray-600);
            cursor: not-allowed;
            border-color: var(--gray-300);
        }

        .form-help {
            font-size: 0.8rem;
            color: var(--gray-600);
            margin-top: var(--space-1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-4);
        }

        .password-indicator {
            height: 4px;
            border-radius: 2px;
            margin-top: var(--space-1);
            transition: all 0.3s ease;
        }

        .password-indicator.weak {
            background: #ef4444;
            width: 33%;
        }

        .password-indicator.medium {
            background: #f59e0b;
            width: 66%;
        }

        .password-indicator.strong {
            background: #22c55e;
            width: 100%;
        }

        .password-indicator.match {
            background: #22c55e;
            width: 100%;
        }

        .password-indicator.no-match {
            background: #ef4444;
            width: 100%;
        }

        /* Sidebar */
        .profile-sidebar .card {
            background: var(--white);
            border: 2px solid var(--black);
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-4);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .profile-sidebar .card-header {
            background: var(--gray-50);
            color: var(--black);
            padding: var(--space-3) var(--space-4);
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: -0.025em;
            border-bottom: 2px solid var(--black);
        }

        .profile-sidebar .card-body {
            padding: var(--space-4);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-3);
        }

        .stat-item {
            text-align: center;
            padding: var(--space-3);
            background: var(--gray-50);
            border: 2px solid var(--black);
            border-radius: var(--radius);
            transition: all 0.2s ease;
        }

        .stat-item:hover {
            transform: translateY(-1px);
            box-shadow: 2px 2px 0px var(--black);
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 900;
            color: var(--black);
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--gray-600);
            text-transform: uppercase;
            margin-top: var(--space-1);
        }

        .info-item {
            margin-bottom: var(--space-3);
            padding-bottom: var(--space-3);
            border-bottom: 1px solid var(--gray-200);
        }

        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .info-label {
            font-weight: 700;
            font-size: 0.8rem;
            color: var(--gray-600);
            text-transform: uppercase;
            margin-bottom: var(--space-1);
        }

        .info-value {
            color: var(--black);
            font-weight: 600;
        }

        .action-link {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            padding: var(--space-2) var(--space-3);
            background: var(--gray-50);
            border: 2px solid var(--black);
            border-radius: var(--radius);
            text-decoration: none;
            color: var(--black);
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: var(--space-2);
            transition: all 0.2s ease;
        }

        .action-link:hover {
            background: var(--black);
            color: var(--white);
            transform: translateY(-1px);
            box-shadow: 2px 2px 0px var(--gray-300);
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        function validatePassword() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                alert('New passwords do not match!');
                return false;
            }
            
            if (newPassword.length < 6) {
                alert('New password must be at least 6 characters long!');
                return false;
            }
            
            return true;
        }

        // Password strength indicator
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('password-strength');
            
            if (password.length === 0) {
                strengthDiv.className = 'password-indicator';
                return;
            }
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            if (strength <= 1) {
                strengthDiv.className = 'password-indicator weak';
            } else if (strength <= 2) {
                strengthDiv.className = 'password-indicator medium';
            } else {
                strengthDiv.className = 'password-indicator strong';
            }
        });

        // Password match indicator
        function checkPasswordMatch() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('password-match');
            
            if (confirmPassword.length === 0) {
                matchDiv.className = 'password-indicator';
                return;
            }
            
            if (newPassword === confirmPassword) {
                matchDiv.className = 'password-indicator match';
            } else {
                matchDiv.className = 'password-indicator no-match';
            }
        }

        document.getElementById('new_password').addEventListener('input', checkPasswordMatch);
        document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);

        // Bio character counter
        const bioTextarea = document.querySelector('textarea[name="bio"]');
        const bioCounter = document.getElementById('bio-counter');
        
        if (bioTextarea && bioCounter) {
            const maxLength = 500;
            
            function updateCounter() {
                const remaining = maxLength - bioTextarea.value.length;
                bioCounter.textContent = `${remaining} characters remaining`;
                
                if (remaining < 50) {
                    bioCounter.style.color = '#ef4444';
                } else if (remaining < 100) {
                    bioCounter.style.color = '#f59e0b';
                } else {
                    bioCounter.style.color = '#6b7280';
                }
            }
            
            bioTextarea.addEventListener('input', updateCounter);
            updateCounter(); // Initial call
        }

        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Fade in animation for cards
            const cards = document.querySelectorAll('.profile-card, .stats-card-beautiful, .info-card-beautiful, .actions-card-beautiful');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>