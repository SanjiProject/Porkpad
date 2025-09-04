<?php
require_once 'Database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function register($username, $email, $password) {
        // Validate input
        if (strlen($username) < 3 || strlen($username) > 50) {
            return ['success' => false, 'message' => 'Username must be 3-50 characters'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address'];
        }
        
        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters'];
        }
        
        // Check if username or email already exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }
        
        // Create user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        
        try {
            $result = $stmt->execute([$username, $email, $hashedPassword]);
            if ($result) {
                return ['success' => true, 'message' => 'Registration successful'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Registration failed'];
        }
        
        return ['success' => false, 'message' => 'Registration failed'];
    }
    
    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT id, username, email, password, is_admin, is_active FROM users WHERE (username = ? OR email = ?) AND is_active = 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        // Update last login
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['is_admin'] = $user['is_admin'];
        
        return ['success' => true, 'user' => $user];
    }
    
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        return true;
    }
    
    public function getCurrentUser() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['user_id'])) {
            $stmt = $this->db->prepare("SELECT id, username, email, is_admin, created_at, last_login FROM users WHERE id = ? AND is_active = 1");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        }
        return null;
    }
    
    public function isLoggedIn() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']);
    }
    
    public function isAdmin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
    }
    
    public function getById($userId) {
        $stmt = $this->db->prepare("SELECT id, username, email, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getUserpastes($userId, $limit = 20, $offset = 0) {
        try {
            // Simple, direct query - all columns should exist after migration
            $stmt = $this->db->prepare("
                SELECT id, title, language, category_id, is_private, created_at, views
                FROM pastes 
                WHERE user_id = ?
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$userId, $limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // If query fails, return empty array
            error_log("getUserpastes error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getUserStats($userId) {
        try {
            // Simple, direct query without column checking complications
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_pastes,
                    COALESCE(SUM(views), 0) as total_views,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_pastes,
                    COUNT(CASE WHEN is_private = 1 THEN 1 END) as private_pastes
                FROM pastes 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            // Ensure we always return proper array structure
            return [
                'total_pastes' => $result['total_pastes'] ?? 0,
                'total_views' => $result['total_views'] ?? 0,
                'week_pastes' => $result['week_pastes'] ?? 0,
                'private_pastes' => $result['private_pastes'] ?? 0
            ];
        } catch (Exception $e) {
            // Fallback if query fails
            return [
                'total_pastes' => 0,
                'total_views' => 0,
                'week_pastes' => 0,
                'private_pastes' => 0
            ];
        }
    }
    
    public function updateProfile($userId, $data) {
        $allowedFields = ['bio', 'avatar'];
        $setClause = [];
        $params = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $setClause[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($setClause)) {
            return ['success' => false, 'message' => 'No fields to update'];
        }
        
        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $setClause) . " WHERE id = ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            return ['success' => $result, 'message' => $result ? 'Profile updated' : 'Update failed'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Update failed'];
        }
    }
    
    public function changePassword($userId, $currentPassword, $newPassword) {
        // Get current password
        $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'New password must be at least 6 characters'];
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        
        try {
            $result = $stmt->execute([$hashedPassword, $userId]);
            return ['success' => $result, 'message' => $result ? 'Password changed successfully' : 'Password change failed'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Password change failed'];
        }
    }
    
    public function getAllUsers($limit = 50, $offset = 0) {
        if (!$this->isAdmin()) {
            return [];
        }
        
        $stmt = $this->db->prepare("
            SELECT id, username, email, is_admin, is_active, created_at, last_login,
                   (SELECT COUNT(*) FROM pastes WHERE user_id = users.id) as paste_count
            FROM users 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }
    
    public function toggleUserStatus($userId) {
        if (!$this->isAdmin()) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }
        
        $stmt = $this->db->prepare("UPDATE users SET is_active = 1 - is_active WHERE id = ?");
        $result = $stmt->execute([$userId]);
        
        return ['success' => $result, 'message' => $result ? 'User status updated' : 'Update failed'];
    }
    
    public function makeAdmin($userId) {
        if (!$this->isAdmin()) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }
        
        $stmt = $this->db->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
        $result = $stmt->execute([$userId]);
        
        return ['success' => $result, 'message' => $result ? 'User promoted to admin' : 'Update failed'];
    }
    
    private function columnExists($tableName, $columnName) {
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM $tableName LIKE ?");
            $stmt->execute([$columnName]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
