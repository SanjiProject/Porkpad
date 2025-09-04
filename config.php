<?php
// paste Configuration

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'porkpad');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application Configuration
define('BASE_URL', 'http://localhost/paste');
define('SITE_NAME', 'PORKPAD');
define('SITE_DESCRIPTION', 'Share code snippets and text with ease');

// Security Configuration
define('CSRF_TOKEN_NAME', 'csrf_token');
define('MAX_paste_SIZE', 10485760); // 10MB in bytes


// Supported languages for syntax highlighting
$supported_languages = [
    'text' => 'Plain Text',
    'php' => 'PHP',
    'javascript' => 'JavaScript',
    'html' => 'HTML',
    'css' => 'CSS',
    'python' => 'Python',
    'java' => 'Java',
    'cpp' => 'C++',
    'c' => 'C',
    'csharp' => 'C#',
    'sql' => 'SQL',
    'json' => 'JSON',
    'xml' => 'XML',
    'bash' => 'Bash',
    'powershell' => 'PowerShell',
    'ruby' => 'Ruby',
    'go' => 'Go',
    'rust' => 'Rust',
    'swift' => 'Swift',
    'kotlin' => 'Kotlin',
    'typescript' => 'TypeScript'
];



// Categories for paste organization
$categories = [
    0 => 'None',
    4 => 'Cybersecurity',
    5 => 'Cryptocurrency',
    6 => 'Movies',
    7 => 'Fixit',
    8 => 'Food',
    9 => 'Gaming',
    10 => 'Haiku',
    11 => 'Help',
    12 => 'History',
    13 => 'Housing',
    14 => 'Jokes',
    15 => 'Legal',
    16 => 'Money',
    17 => 'Music',
    18 => 'Pets',
    19 => 'Photo',
    20 => 'Science',
    21 => 'Software',
    22 => 'Spirit',
    23 => 'Sports',
    24 => 'Travel',
    25 => 'TV',
    26 => 'Writing',
    27 => 'Source Code'
];

// Timezone
date_default_timezone_set('UTC');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Global variables are now properly defined above
?>
