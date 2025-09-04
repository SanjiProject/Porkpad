# 🐷 PorkPad

<div align="center">

![PorkPad Platform Screenshot](https://porkpad.com/img/screenshot.png)

*A Simple & Fast Paste Sharing Platform*

</div>

**PorkPad** is a modern, feature-rich Pastebin application that allows users to create, share, and manage code snippets and text content with ease. Built with PHP and designed for simplicity, security, and performance.

Share your code, organize with categories, and collaborate effortlessly with custom URLs, password protection, and comprehensive user management.

[![Website](https://img.shields.io/badge/Website-porkpad.com-FF6B6B?style=for-the-badge)](https://porkpad.com)
[![Demo](https://img.shields.io/badge/Live_Demo-porkpad.com-FF6B6B?style=for-the-badge&logo=globe)](https://porkpad.com)
[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

## ✨ Features

### 🔐 User Management
- **User Registration & Login** - Secure authentication system
- **User Profiles** - Personal dashboards and profile management
- **Admin Panel** - Comprehensive administrative controls
- **User Statistics** - Track paste views, counts, and activity

### 📝 Paste Management
- **Syntax Highlighting** - Support for multiple programming languages
- **Category Organization** - Organize pastes with custom categories
- **Password Protection** - Secure sensitive content with passwords
- **Custom URLs** - Create memorable, custom URLs for registered users
- **Private Pastes** - Control visibility of your content
- **Edit & Update** - Modify existing pastes with revision history

### 🌐 Modern Interface
- **Responsive Design** - Works perfectly on desktop and mobile
- **Clean URLs** - SEO-friendly URLs without .php extensions
- **Dark Theme** - Modern, easy-on-the-eyes interface
- **Fast Performance** - Optimized for speed and efficiency

### 🛡️ Security Features
- **CAPTCHA Protection** - Prevents automated bot submissions
- **Input Validation** - Comprehensive server-side validation
- **SQL Injection Protection** - Uses prepared statements
- **XSS Prevention** - Proper output escaping
- **Password Hashing** - Secure password storage

### 🔍 SEO & Discovery
- **Dynamic Sitemap** - Automatically generated XML sitemap
- **Meta Tags** - Open Graph and Twitter Card support
- **Structured Data** - JSON-LD for search engines
- **Clean URLs** - SEO-optimized URL structure

## 🚀 Quick Start

### Prerequisites
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Web server (Apache/Nginx)
- Composer (optional, for dependencies)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/SanjiProject/porkpad.git
   cd porkpad
   ```

2. **Configure database**
   ```sql
   CREATE DATABASE porkpad;
   ```

3. **Update configuration**
   ```php
   // config.php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'porkpad');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

4. **Import database schema**
   ```bash
   mysql -u username -p porkpad < porkpad.sql
   ```

5. **Set file permissions**
   ```bash
   chmod 755 classes/
   chmod 644 *.php
   ```

6. **Configure web server**
   - For Apache: Use included `.htaccess`
   - For Nginx: Use provided nginx configuration

## 📁 Project Structure

```
porkpad/
├── classes/                 # Core application classes
│   ├── Database.php        # Database connection handler
│   ├── Paste.php          # Paste management logic
│   ├── User.php           # User management system
│   ├── Captcha.php        # CAPTCHA generation
│   └── SEO.php            # SEO meta tags and sitemap
├── assets/                 # Static assets
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript files
│   └── img/               # Images and icons
├── admin/                  # Administrative tools
├── api/                    # API endpoints
├── index.php              # Homepage and paste creation
├── view.php               # Paste viewing page
├── recent.php             # Recent pastes listing
├── dashboard.php          # User dashboard
├── admin.php              # Admin panel
├── login.php              # User authentication
├── register.php           # User registration
├── edit.php               # Paste editing
├── profile.php            # User profile management
├── category.php           # Category browsing
├── user.php               # Public user profiles
├── sitemap.php            # Dynamic XML sitemap
├── config.php             # Application configuration
└── .htaccess              # URL rewriting rules
```

## 🛠️ Configuration

### Environment Setup
Configure your environment in `config.php`:

```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'porkpad');
define('DB_USER', 'username');
define('DB_PASS', 'password');
```

### URL Rewriting

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteRule ^recent/?$ recent.php [L,QSA]
RewriteRule ^dashboard/?$ dashboard.php [L,QSA]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([a-zA-Z0-9\-_]+)/?$ view.php?id=$1 [L,QSA]
```

#### Nginx
```nginx
rewrite ^/recent/?$ /recent.php last;
rewrite ^/dashboard/?$ /dashboard.php last;
rewrite ^/recent\.php$ /recent permanent;
rewrite ^/dashboard\.php$ /dashboard permanent;
rewrite "^/([a-zA-Z0-9][a-zA-Z0-9\-_]{2,49})/?$" /view.php?id=$1 last;
```
### Categories
Add custom categories in your database:
```sql
INSERT INTO categories (name, description) VALUES 
('Python', 'Python code snippets'),
('JavaScript', 'JavaScript and Node.js code'),
('PHP', 'PHP scripts and functions');
```

### Languages
Syntax highlighting supports 50+ languages including:
- Programming: PHP, Python, JavaScript, Java, C++, Go, Rust
- Web: HTML, CSS, TypeScript, Vue, React
- Data: JSON, XML, YAML, SQL
- Config: Nginx, Apache, Docker

---

<div align="center">

**Made with ❤️ by Sanji**

</div>
