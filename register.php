<?php
require_once 'config.php';
require_once 'classes/User.php';

$user = new User();
$message = '';
$messageType = '';

// Redirect if already logged in
if ($user->isLoggedIn()) {
    header("Location: dashboard");
    exit;
}

// Handle registration form submission
if ($_POST) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Basic validation
    if (empty($username) || empty($email) || empty($password)) {
        $message = 'Please fill in all required fields';
        $messageType = 'error';
    } elseif ($password !== $confirmPassword) {
        $message = 'Passwords do not match';
        $messageType = 'error';
    } else {
        $result = $user->register($username, $email, $password);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
        
        if ($result['success']) {
            // Auto-login after successful registration
            $loginResult = $user->login($username, $password);
            if ($loginResult['success']) {
                header("Location: dashboard");
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
    
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
                    <a href="login.php" class="btn btn-secondary btn-sm">Login</a>
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
    </div>
            
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form" onsubmit="return validateForm()">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" 
                           placeholder="Choose a username (3-50 characters)"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                           pattern="[a-zA-Z0-9_-]{3,50}" required>
                    <small class="form-help">Only letters, numbers, hyphens and underscores allowed</small>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" 
                           placeholder="Enter your email address"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Create a strong password (min 6 characters)"
                           minlength="6" required>
                    <div class="password-strength" id="password-strength"></div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           placeholder="Confirm your password" required>
                    <div class="password-match" id="password-match"></div>
                </div>

                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" id="terms" required>
                        I agree to use paste responsibly and follow community guidelines
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-large auth-submit">
                    Create Account
                </button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Sign in here</a></p>
                <p><a href="index.php">← Back to paste</a></p>
            </div>

            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 <?php echo SITE_NAME; ?>. Made with ❤️ by <a href="https://github.com/SanjiProject">Sanji Project</a></p>
        </div>
    </footer>

    <style>
        /* Auth-specific styles */
        .auth-container {
            min-height: 70vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--space-6);
        }

        .auth-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--space-8);
            border: 4px solid var(--black);
            width: 100%;
            max-width: 500px;
            box-shadow: 8px 8px 0px var(--black);
            transition: all 0.2s ease;
        }

        .auth-card:hover {
            transform: translateY(-2px);
            box-shadow: 12px 12px 0px var(--black);
        }

        .auth-header {
            text-align: center;
            margin-bottom: var(--space-8);
        }

        .auth-header h1 {
            color: var(--black);
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: var(--space-3);
            text-transform: uppercase;
            letter-spacing: -1px;
        }

        .auth-header p {
            color: var(--gray-600);
            font-size: 1.1rem;
            font-weight: 500;
        }

        .auth-form .form-group {
            margin-bottom: var(--space-6);
        }

        .auth-form .form-group label {
            display: block;
            margin-bottom: var(--space-2);
            font-weight: 700;
            color: var(--black);
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 0.5px;
        }

        .auth-form input[type="text"],
        .auth-form input[type="email"], 
        .auth-form input[type="password"],
        .auth-form select,
        .auth-form textarea,
        .form-control {
            width: 100%;
            padding: var(--space-4);
            border: 3px solid var(--black);
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-weight: 500;
            font-family: inherit;
            transition: all 0.2s ease;
            background: white;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            box-sizing: border-box;
        }

        .auth-form input[type="text"]:focus,
        .auth-form input[type="email"]:focus,
        .auth-form input[type="password"]:focus,
        .auth-form select:focus,
        .auth-form textarea:focus,
        .form-control:focus {
            outline: none;
            box-shadow: 4px 4px 0px var(--black);
            transform: translateY(-1px);
            border-color: var(--black);
        }

        .auth-form input[type="text"]::placeholder,
        .auth-form input[type="email"]::placeholder,
        .auth-form input[type="password"]::placeholder {
            color: var(--gray-500);
            font-weight: 400;
            opacity: 0.8;
        }

        .form-help {
            font-size: 0.8rem;
            color: var(--gray-500);
            margin-top: var(--space-1);
            font-weight: 500;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            font-weight: 500;
            color: var(--gray-700);
            cursor: pointer;
        }

        .checkbox-label input[type="checkbox"] {
            width: 20px;
            height: 20px;
            border: 3px solid var(--black);
            border-radius: var(--radius-sm);
            background: white;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            cursor: pointer;
            position: relative;
            margin: 0;
        }

        .checkbox-label input[type="checkbox"]:checked {
            background: var(--black);
        }

        .checkbox-label input[type="checkbox"]:checked::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-weight: bold;
            font-size: 12px;
        }

        .checkbox-label input[type="checkbox"]:focus {
            outline: none;
            box-shadow: 2px 2px 0px var(--black);
        }

        .auth-submit {
            width: 100%;
            margin-bottom: var(--space-6);
            padding: var(--space-4);
            font-size: 1.1rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .auth-footer {
            text-align: center;
            border-top: 3px solid var(--black);
            padding-top: var(--space-6);
            margin-top: var(--space-6);
        }

        .auth-footer p {
            color: var(--gray-600);
            margin-bottom: var(--space-3);
            font-weight: 500;
        }

        .auth-footer a {
            color: var(--black);
            text-decoration: none;
            font-weight: 700;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
        }

        .auth-footer a:hover {
            border-bottom-color: var(--black);
            transform: translateY(-1px);
        }

        /* Registration-specific styles */

        .password-strength, .password-match {
            height: 4px;
            border-radius: 2px;
            margin-top: 5px;
            transition: all 0.3s ease;
        }

        .password-strength.weak {
            background: var(--error-color);
            width: 33%;
        }

        .password-strength.medium {
            background: var(--warning-color);
            width: 66%;
        }

        .password-strength.strong {
            background: var(--success-color);
            width: 100%;
        }

        .password-match.match {
            background: var(--success-color);
            width: 100%;
        }

        .password-match.no-match {
            background: var(--error-color);
            width: 100%;
        }

        .features-preview {
            background: var(--yellow);
            border: 3px solid var(--black);
            border-radius: var(--radius-md);
            padding: var(--space-6);
            margin-top: var(--space-6);
            box-shadow: 4px 4px 0px var(--black);
        }

        .features-preview h3 {
            color: var(--black);
            font-size: 1rem;
            font-weight: 800;
            margin-bottom: var(--space-4);
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .features-preview ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: var(--space-2);
        }

        .features-preview li {
            color: var(--black);
            font-size: 0.9rem;
            font-weight: 600;
            text-align: center;
            padding: var(--space-2);
            background: rgba(255, 255, 255, 0.7);
            border-radius: var(--radius-sm);
            border: 2px solid var(--black);
        }
    </style>

    <script>
        function validateForm() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 6) {
                alert('Password must be at least 6 characters long!');
                return false;
            }
            
            return true;
        }

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('password-strength');
            
            if (password.length === 0) {
                strengthDiv.className = 'password-strength';
                return;
            }
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            if (strength <= 1) {
                strengthDiv.className = 'password-strength weak';
            } else if (strength <= 2) {
                strengthDiv.className = 'password-strength medium';
            } else {
                strengthDiv.className = 'password-strength strong';
            }
        });

        // Password match indicator
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('password-match');
            
            if (confirmPassword.length === 0) {
                matchDiv.className = 'password-match';
                return;
            }
            
            if (password === confirmPassword) {
                matchDiv.className = 'password-match match';
            } else {
                matchDiv.className = 'password-match no-match';
            }
        }

        document.getElementById('password').addEventListener('input', checkPasswordMatch);
        document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);
    </script>
</body>
</html>

