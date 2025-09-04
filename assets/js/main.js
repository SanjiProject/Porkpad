// paste JavaScript functionality

document.addEventListener('DOMContentLoaded', function() {
    // Add animations to elements
    addAnimations();
    
    // Character counter for textarea
    const textarea = document.getElementById('content');
    if (textarea) {
        const maxLength = 10485760; // 10MB in bytes
        
        // Create character counter
        const counterDiv = document.createElement('div');
        counterDiv.className = 'character-counter';
        counterDiv.style.cssText = `
            text-align: right;
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        `;
        textarea.parentNode.appendChild(counterDiv);
        
        function updateCounter() {
            const length = new Blob([textarea.value]).size;
            const remaining = maxLength - length;
            
            if (remaining < 0) {
                counterDiv.style.color = 'var(--error-color)';
                counterDiv.textContent = `Exceeds limit by ${Math.abs(remaining).toLocaleString()} bytes`;
            } else {
                counterDiv.style.color = 'var(--text-muted)';
                counterDiv.textContent = `${length.toLocaleString()} / ${maxLength.toLocaleString()} bytes`;
            }
        }
        
        textarea.addEventListener('input', updateCounter);
        updateCounter();
    }
    
    // Auto-resize textarea
    if (textarea) {
        function autoResize() {
            textarea.style.height = 'auto';
            textarea.style.height = Math.max(300, textarea.scrollHeight) + 'px';
        }
        
        textarea.addEventListener('input', autoResize);
        autoResize();
    }
    
    // Form validation
    const pasteForm = document.querySelector('.paste-form');
    if (pasteForm) {
        pasteForm.addEventListener('submit', function(e) {
            const content = textarea.value.trim();
            const maxSize = 10485760; // 10MB
            
            if (!content) {
                e.preventDefault();
                showNotification('Content cannot be empty', 'error');
                return;
            }
            
            if (new Blob([content]).size > maxSize) {
                e.preventDefault();
                showNotification('Content exceeds maximum size limit', 'error');
                return;
            }
        });
    }
    
    // Language detection
    const languageSelect = document.getElementById('language');
    if (languageSelect && textarea) {
        const languagePatterns = {
            'php': /(<\?php|<\?=|\$\w+|->|::)/,
            'javascript': /(function\s+\w+|var\s+\w+|let\s+\w+|const\s+\w+|=>\s*{|console\.)/,
            'python': /(def\s+\w+|import\s+\w+|from\s+\w+|print\(|if\s+__name__)/,
            'html': /(<html|<head|<body|<div|<span|<p>)/i,
            'css': /({[^}]*}|@media|@import|\.[a-zA-Z][\w-]*\s*{)/,
            'sql': /(SELECT|FROM|WHERE|INSERT|UPDATE|DELETE|CREATE|ALTER)/i,
            'json': /^\s*[\{\[]/,
            'xml': /<\?xml|<[a-zA-Z][\w:]*>/
        };
        
        function detectLanguage(content) {
            for (const [lang, pattern] of Object.entries(languagePatterns)) {
                if (pattern.test(content)) {
                    return lang;
                }
            }
            return 'text';
        }
        
        textarea.addEventListener('blur', function() {
            if (languageSelect.value === 'text' && textarea.value.trim()) {
                const detected = detectLanguage(textarea.value);
                if (detected !== 'text') {
                    languageSelect.value = detected;
                    showNotification(`Language detected as ${detected}`, 'success');
                }
            }
        });
    }
    
    // Search functionality
    const searchForm = document.querySelector('.search-form');
    if (searchForm) {
        const searchInput = searchForm.querySelector('input[name="search"]');
        
        // Search suggestions (simple implementation)
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    // Could implement live search suggestions here
                }, 300);
            });
        }
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+S or Cmd+S to submit form (if textarea is focused)
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            const activeElement = document.activeElement;
            if (activeElement && activeElement.tagName === 'TEXTAREA') {
                e.preventDefault();
                const form = activeElement.closest('form');
                if (form) {
                    form.submit();
                }
            }
        }
        
        // Ctrl+N or Cmd+N for new paste
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            window.location.href = 'index.php';
        }
    });
    
    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.3s ease';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Copy functionality enhancement
    if (typeof copyToClipboard === 'undefined') {
        window.copyToClipboard = function() {
            const content = document.getElementById('paste-content');
            if (!content) return;
            
            const text = content.textContent;
            navigator.clipboard.writeText(text).then(() => {
                showNotification('Content copied to clipboard!', 'success');
            }).catch(() => {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showNotification('Content copied to clipboard!', 'success');
            });
        };
    }
    
    // Enhanced share URL copy
    if (typeof copyShareUrl === 'undefined') {
        window.copyShareUrl = function() {
            const shareUrl = document.getElementById('share-url');
            if (!shareUrl) return;
            
            shareUrl.select();
            shareUrl.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(shareUrl.value).then(() => {
                showNotification('Share URL copied to clipboard!', 'success');
            });
        };
    }
    
    // Theme toggle (for future implementation)
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        const currentTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', currentTheme);
        
        themeToggle.addEventListener('click', function() {
            const theme = document.documentElement.getAttribute('data-theme');
            const newTheme = theme === 'light' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        });
    }
});

// Global notification function
function showNotification(message, type = 'success') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => {
        notification.remove();
    });
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    // Set styles based on type
    const styles = {
        success: {
            backgroundColor: 'var(--success-color)',
            color: 'white'
        },
        error: {
            backgroundColor: 'var(--error-color)',
            color: 'white'
        },
        warning: {
            backgroundColor: 'var(--warning-color)',
            color: 'white'
        },
        info: {
            backgroundColor: 'var(--primary-color)',
            color: 'white'
        }
    };
    
    const style = styles[type] || styles.success;
    Object.assign(notification.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        padding: '1rem 1.5rem',
        borderRadius: 'var(--radius-md)',
        boxShadow: 'var(--shadow-lg)',
        transform: 'translateX(100%)',
        transition: 'transform 0.3s ease',
        zIndex: '1000',
        fontSize: '0.875rem',
        fontWeight: '500',
        maxWidth: '400px',
        ...style
    });
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 10);
    
    // Auto remove
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Utility function for formatting file sizes
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Utility function for time formatting
function timeAgo(date) {
    const now = new Date();
    const diffInSeconds = Math.floor((now - new Date(date)) / 1000);
    
    const intervals = {
        year: 31536000,
        month: 2592000,
        week: 604800,
        day: 86400,
        hour: 3600,
        minute: 60
    };
    
    for (const [unit, seconds] of Object.entries(intervals)) {
        const interval = Math.floor(diffInSeconds / seconds);
        if (interval >= 1) {
            return interval === 1 ? `1 ${unit} ago` : `${interval} ${unit}s ago`;
        }
    }
    
    return 'Just now';
}

// Add animations to page elements
function addAnimations() {
    // Add fade-in animation to main content
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.classList.add('slide-in-up');
    }
    
    // Add slide-in animation to sidebar
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.add('slide-in-left');
    }
    
    // Add staggered animations to cards
    const cards = document.querySelectorAll('.stats-card, .recent-card, .info-card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.classList.add('fade-in');
        }, index * 100);
    });
    
    // Add hover effects to form inputs
    const inputs = document.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.closest('.form-group').classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.closest('.form-group').classList.remove('focused');
        });
    });
    
    // Add sparkle effect to create button
    const createBtn = document.querySelector('.btn-primary');
    if (createBtn && createBtn.textContent.includes('Create')) {
        createBtn.classList.add('sparkle');
    }
}

// Enhanced form validation with better UX
function validateForm(form) {
    const content = form.querySelector('[name="content"]');
    const title = form.querySelector('[name="title"]');
    let isValid = true;
    
    // Clear previous errors
    clearFormErrors(form);
    
    if (!content.value.trim()) {
        showFieldError(content, 'Content cannot be empty');
        isValid = false;
    }
    
    if (content.value.length > 10485760) {
        showFieldError(content, 'Content exceeds maximum size limit');
        isValid = false;
    }
    
    return isValid;
}

function showFieldError(field, message) {
    field.classList.add('error');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    errorDiv.style.cssText = `
        color: var(--error-color);
        font-size: 0.75rem;
        margin-top: 0.25rem;
        animation: slideInUp 0.3s ease;
    `;
    
    field.parentNode.appendChild(errorDiv);
    
    // Shake effect
    field.style.animation = 'shake 0.5s ease-in-out';
    setTimeout(() => {
        field.style.animation = '';
    }, 500);
}

function clearFormErrors(form) {
    const errors = form.querySelectorAll('.field-error');
    errors.forEach(error => error.remove());
    
    const errorFields = form.querySelectorAll('.error');
    errorFields.forEach(field => field.classList.remove('error'));
}

// Add shake animation CSS if not present
if (!document.querySelector('#shake-animation')) {
    const shakeCSS = document.createElement('style');
    shakeCSS.id = 'shake-animation';
    shakeCSS.textContent = `
        @keyframes shake {
            0%, 20%, 40%, 60%, 80% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        }
        .error {
            border-color: var(--error-color) !important;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1) !important;
        }
    `;
    document.head.appendChild(shakeCSS);
}

// Enhanced notification with better styling
function showEnhancedNotification(message, type = 'success', duration = 3000) {
    const notification = document.createElement('div');
    notification.className = `enhanced-notification notification-${type}`;
    
    const icons = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };
    
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-icon">${icons[type] || icons.success}</span>
            <span class="notification-message">${message}</span>
        </div>
    `;
    
    const styles = {
        success: { bg: '#10b981', color: '#ffffff' },
        error: { bg: '#ef4444', color: '#ffffff' },
        warning: { bg: '#f59e0b', color: '#ffffff' },
        info: { bg: '#667eea', color: '#ffffff' }
    };
    
    const style = styles[type] || styles.success;
    
    Object.assign(notification.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        background: style.bg,
        color: style.color,
        padding: '1rem 1.5rem',
        borderRadius: '16px',
        boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
        transform: 'translateX(100%)',
        transition: 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
        zIndex: '1000',
        fontSize: '0.875rem',
        fontWeight: '500',
        maxWidth: '400px',
        backdropFilter: 'blur(20px)',
        border: '1px solid rgba(255, 255, 255, 0.2)'
    });
    
    notification.querySelector('.notification-content').style.cssText = `
        display: flex;
        align-items: center;
        gap: 0.5rem;
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 10);
    
    // Auto remove
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, duration);
}
