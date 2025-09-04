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

// Handle login form submission
if ($_POST) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $message = 'Please enter both username and password';
        $messageType = 'error';
    } else {
        $result = $user->login($username, $password);
        if ($result['success']) {
            // Redirect to dashboard or intended page
            $redirect = $_GET['redirect'] ?? 'dashboard';
            header("Location: " . $redirect);
            exit;
        } else {
            $message = $result['message'];
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    
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
                    <a href="register.php" class="btn btn-primary btn-sm">Sign Up</a>
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

            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" 
                           placeholder="Enter your username or email"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Enter your password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-large auth-submit">
                    Sign In
                </button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php">Create one here</a></p>
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
            max-width: 450px;
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
        .auth-form input[type="number"],
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
        .auth-form input[type="number"]:focus,
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
        .auth-form input[type="password"]::placeholder,
        .auth-form input[type="number"]::placeholder {
            color: var(--gray-500);
            font-weight: 400;
            opacity: 0.8;
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

        .demo-credentials {
            background: var(--yellow);
            border: 3px solid var(--black);
            border-radius: var(--radius-md);
            padding: var(--space-6);
            margin-top: var(--space-6);
            text-align: center;
            box-shadow: 4px 4px 0px var(--black);
        }

        .demo-credentials h3 {
            color: var(--black);
            font-size: 1rem;
            font-weight: 800;
            margin-bottom: var(--space-4);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .demo-credentials p {
            color: var(--black);
            font-size: 0.9rem;
            font-weight: 600;
            margin: var(--space-2) 0;
            padding: var(--space-2);
            background: rgba(255, 255, 255, 0.7);
            border-radius: var(--radius-sm);
            border: 2px solid var(--black);
        }

        .demo-credentials small {
            color: var(--red);
            font-size: 0.8rem;
            font-weight: 700;
            display: block;
            margin-top: var(--space-3);
            padding: var(--space-2);
            background: rgba(255, 255, 255, 0.9);
            border-radius: var(--radius-sm);
            border: 2px solid var(--red);
        }
    </style>
</body>
</html>

