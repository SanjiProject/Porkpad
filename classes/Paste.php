<?php
require_once 'Database.php';

class paste {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generateId($length = 8) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        do {
            $id = '';
            for ($i = 0; $i < $length; $i++) {
                $id .= $characters[random_int(0, strlen($characters) - 1)];
            }
        } while ($this->exists($id));
        
        return $id;
    }
    
    public function exists($id) {
        $stmt = $this->db->prepare("SELECT id FROM pastes WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() !== false;
    }
    
    public function urlExists($customUrl) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM pastes WHERE id = ?");
        $stmt->execute([$customUrl]);
        return $stmt->fetchColumn() > 0;
    }
    
    public function create($data) {
        // Use custom URL if provided, otherwise generate random ID
        if (!empty($data['custom_url'])) {
            $id = $data['custom_url'];
        } else {
            $id = $this->generateId();
        }
        

        
        // Hash password if provided
        $password = null;
        if (!empty($data['password'])) {
            $password = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        // Ensure title is not empty
        $title = trim($data['title'] ?? '');
        if (empty($title)) {
            $title = 'Untitled';
        }
        
        // Check if category_id column exists
        $hasCategories = $this->columnExists('pastes', 'category_id');
        
        // Get current user ID if logged in
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $userId = $_SESSION['user_id'] ?? null;
        
        // FIXED: Use direct SQL with all columns (assume migration was run)
        $stmt = $this->db->prepare("
            INSERT INTO pastes (id, title, content, language, category_id, user_id, is_private, password, author_ip, can_edit, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([
            $id,
            $title,
            $data['content'],
            $data['language'] ?? 'text',
            $data['category_id'] ?? 0,
            $userId, // This should now work correctly
            $data['is_private'] ?? 0,
            $password ?: null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            1 // can_edit = true
        ]);
        
        return $result ? $id : false;
    }
    
    public function get($id, $password = null) {
        // Check if paste exists and is not expired
        $stmt = $this->db->prepare("
            SELECT * FROM pastes 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        $paste = $stmt->fetch();
        
        if (!$paste) {
            return false;
        }
        
        // Check password if paste is protected
        if ($paste['password'] && !password_verify($password ?? '', $paste['password'])) {
            return 'password_required';
        }
        
        return $paste;
    }
    
    public function getForEdit($id) {
        // Get paste data for editing - bypasses password check since user owns it
        $stmt = $this->db->prepare("
            SELECT * FROM pastes 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function incrementViews($id) {
        $stmt = $this->db->prepare("UPDATE pastes SET views = views + 1 WHERE id = ?");
        $stmt->execute([$id]);
    }
    
    public function getRecent($limit = 10, $offset = 0, $category_id = null) {
        // Check if category_id column exists
        $hasCategories = $this->columnExists('pastes', 'category_id');
        // Check if is_private column exists
        $hasPrivacy = $this->columnExists('pastes', 'is_private');
        
        // Build the SELECT clause - always include category_id since we know it exists
        $columns = "id, title, language, created_at, views, password";
        if ($hasCategories) {
            $columns .= ", category_id";
        }
        if ($hasPrivacy) {
            $columns .= ", is_private";
        }
        
        // Column selection completed
        
        // Build the WHERE clause
        $whereConditions = [];
        $params = [];
        
        // Add privacy filter if column exists
        if ($hasPrivacy) {
            $whereConditions[] = "is_private = 0";
        }
        
        // Add password filter
        $whereConditions[] = "password IS NULL";
        

        
        // Add category filter if specified and column exists
        if ($category_id !== null && $hasCategories) {
            if ($category_id == 0) {
                // If requesting category 0 (None), show only uncategorized pastes
                $whereConditions[] = "(category_id = 0 OR category_id IS NULL)";
            } else {
                // If requesting a specific category, show ONLY that category
                $whereConditions[] = "category_id = ?";
                $params[] = $category_id;
            }
        }
        
        // Build final SQL query
        $sql = "SELECT $columns FROM pastes";
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions);
        }
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        
        // Add pagination parameters
        $params[] = $limit;
        $params[] = $offset;
        
        // Category filtering implemented and working
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function columnExists($table, $column) {
        try {
            // Use DESCRIBE instead of SHOW COLUMNS for better compatibility
            $stmt = $this->db->prepare("DESCRIBE `$table`");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($columns as $col) {
                if ($col['Field'] === $column) {
                    return true;
                }
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("columnExists error: " . $e->getMessage());
            return false;
        }
    }
    
    public function search($query, $limit = 10, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT id, title, language, created_at, views, password
            FROM pastes 
            WHERE is_private = 0 
            AND (title LIKE ? OR content LIKE ?)
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $searchTerm = '%' . $query . '%';
        $stmt->execute([$searchTerm, $searchTerm, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    public function getRecentForAdmin($limit = 10, $offset = 0) {
        // Admin method to get all pastes including private ones
        // Check if columns exist
        $hasCategories = $this->columnExists('pastes', 'category_id');
        $hasPrivacy = $this->columnExists('pastes', 'is_private');
        
        $columns = "id, title, language, created_at, views, password";
        if ($hasCategories) {
            $columns .= ", category_id";
        }
        if ($hasPrivacy) {
            $columns .= ", is_private";
        }
        
        $sql = "
            SELECT $columns
            FROM pastes 
            ORDER BY created_at DESC LIMIT ? OFFSET ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }
    
    public function getStats() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_pastes,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as today_pastes,
                SUM(views) as total_views
            FROM pastes 
        ");
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public function getCategoryCounts() {
        $hasCategories = $this->columnExists('pastes', 'category_id');
        
        if (!$hasCategories) {
            return [];
        }
        
        $stmt = $this->db->prepare("
            SELECT 
                category_id,
                COUNT(*) as count
            FROM pastes 
            WHERE category_id IS NOT NULL 
              AND category_id > 0
            GROUP BY category_id
            ORDER BY count DESC
        ");
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        // Convert to associative array
        $counts = [];
        foreach ($results as $row) {
            $counts[$row['category_id']] = $row['count'];
        }
        
        return $counts;
    }
    

    
    public function canEdit($pasteId, $userId = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $currentUserId = $userId ?? $_SESSION['user_id'] ?? null;
        
        if (!$currentUserId) {
            return false;
        }
        
        // Check if user owns the paste and if it's editable
        // Check if can_edit column exists
        $hasCanEdit = $this->columnExists('pastes', 'can_edit');
        $hasUserId = $this->columnExists('pastes', 'user_id');
        
        if ($hasCanEdit && $hasUserId) {
            $stmt = $this->db->prepare("
                SELECT user_id, can_edit FROM pastes 
                WHERE id = ?
            ");
        } else if ($hasUserId) {
            $stmt = $this->db->prepare("
                SELECT user_id FROM pastes 
                WHERE id = ?
            ");
        } else {
            // No user system, can't edit
            return false;
        }
        
        $stmt->execute([$pasteId]);
        $paste = $stmt->fetch();
        
        if (!$paste) {
            return false;
        }
        
        // Admin can edit any paste
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
            return true;
        }
        
        // Also check user object for admin status (fallback)
        $stmt = $this->db->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt->execute([$currentUserId]);
        $userInfo = $stmt->fetch();
        if ($userInfo && $userInfo['is_admin']) {
            return true;
        }
        
        // User can edit their own paste if can_edit is true (or if can_edit column doesn't exist, assume true)
        if (isset($paste['can_edit'])) {
            return $paste['user_id'] == $currentUserId && $paste['can_edit'];
        } else {
            // If can_edit column doesn't exist, user can edit their own paste
            return $paste['user_id'] == $currentUserId;
        }
    }
    
    public function update($pasteId, $data, $userId = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $currentUserId = $userId ?? $_SESSION['user_id'] ?? null;
        
        if (!$this->canEdit($pasteId, $currentUserId)) {
            return ['success' => false, 'message' => 'You do not have permission to edit this paste'];
        }
        
        // Get current paste for revision history (bypasses password check since user owns it)
        $currentpaste = $this->getForEdit($pasteId);
        if (!$currentpaste) {
            return ['success' => false, 'message' => 'paste not found'];
        }
        
        // Ensure title is not empty
        $title = trim($data['title'] ?? '');
        if (empty($title)) {
            $title = 'Untitled';
        }
        
        // Create revision history
        $this->createRevision($pasteId, $currentpaste, $currentUserId);
        
        // Handle password
        $password = null;
        if (isset($data['password']) && !empty($data['password'])) {
            $password = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        // Check if category_id column exists
        $hasCategories = $this->columnExists('pastes', 'category_id');
        
        try {
            if ($hasCategories) {
                if (isset($data['password'])) {
                    $stmt = $this->db->prepare("
                        UPDATE pastes 
                        SET title = ?, content = ?, language = ?, category_id = ?, password = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $result = $stmt->execute([
                        $title,
                        $data['content'],
                        $data['language'] ?? 'text',
                        $data['category_id'] ?? 0,
                        $password,
                        $pasteId
                    ]);
                } else {
                    $stmt = $this->db->prepare("
                        UPDATE pastes 
                        SET title = ?, content = ?, language = ?, category_id = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $result = $stmt->execute([
                        $title,
                        $data['content'],
                        $data['language'] ?? 'text',
                        $data['category_id'] ?? 0,
                        $pasteId
                    ]);
                }
            } else {
                if (isset($data['password'])) {
                    $stmt = $this->db->prepare("
                        UPDATE pastes 
                        SET title = ?, content = ?, language = ?, password = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $result = $stmt->execute([
                        $title,
                        $data['content'],
                        $data['language'] ?? 'text',
                        $password,
                        $pasteId
                    ]);
                } else {
                    $stmt = $this->db->prepare("
                        UPDATE pastes 
                        SET title = ?, content = ?, language = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $result = $stmt->execute([
                        $title,
                        $data['content'],
                        $data['language'] ?? 'text',
                        $pasteId
                    ]);
                }
            }
            
            return ['success' => $result, 'message' => $result ? 'paste updated successfully' : 'Update failed'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Update failed: ' . $e->getMessage()];
        }
    }
    
    private function createRevision($pasteId, $currentpaste, $userId) {
        try {
            // Check if revisions table exists
            if (!$this->tableExists('paste_revisions')) {
                return false;
            }
            
            // Get current revision number
            $stmt = $this->db->prepare("SELECT MAX(revision_number) as max_rev FROM paste_revisions WHERE paste_id = ?");
            $stmt->execute([$pasteId]);
            $result = $stmt->fetch();
            $revisionNumber = ($result['max_rev'] ?? 0) + 1;
            
            // Create revision
            $stmt = $this->db->prepare("
                INSERT INTO paste_revisions (paste_id, title, content, language, category_id, revision_number, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $pasteId,
                $currentpaste['title'],
                $currentpaste['content'],
                $currentpaste['language'],
                $currentpaste['category_id'] ?? 0,
                $revisionNumber,
                $userId
            ]);
            
            return true;
        } catch (PDOException $e) {
            // Ignore revision creation errors
            return false;
        }
    }
    
    private function tableExists($tableName) {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function getRevisions($pasteId, $userId = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $currentUserId = $userId ?? $_SESSION['user_id'] ?? null;
        
        if (!$this->canEdit($pasteId, $currentUserId)) {
            return [];
        }
        
        try {
            if (!$this->tableExists('paste_revisions')) {
                return [];
            }
            
            $stmt = $this->db->prepare("
                SELECT pr.*, u.username 
                FROM paste_revisions pr
                LEFT JOIN users u ON pr.created_by = u.id
                WHERE pr.paste_id = ?
                ORDER BY pr.revision_number DESC
            ");
            $stmt->execute([$pasteId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function delete($pasteId, $userId = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $currentUserId = $userId ?? $_SESSION['user_id'] ?? null;
        
        if (!$currentUserId) {
            return ['success' => false, 'message' => 'You must be logged in to delete pastes'];
        }
        
        // Check ownership or admin
        $stmt = $this->db->prepare("SELECT user_id FROM pastes WHERE id = ?");
        $stmt->execute([$pasteId]);
        $paste = $stmt->fetch();
        
        if (!$paste) {
            return ['success' => false, 'message' => 'paste not found'];
        }
        
        $isAdmin = $_SESSION['is_admin'] ?? false;
        if (!$isAdmin && $paste['user_id'] != $currentUserId) {
            return ['success' => false, 'message' => 'You can only delete your own pastes'];
        }
        
        try {
            $stmt = $this->db->prepare("DELETE FROM pastes WHERE id = ?");
            $result = $stmt->execute([$pasteId]);
            
            return ['success' => $result, 'message' => $result ? 'paste deleted successfully' : 'Delete failed'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Delete failed'];
        }
    }
    
    public function searchByCategory($searchQuery, $categoryId, $limit = 10, $offset = 0) {
        $searchTerm = '%' . $searchQuery . '%';
        
        if ($categoryId == 0) {
            // Search in uncategorized pastes only
            $stmt = $this->db->prepare("
                SELECT * FROM pastes 
                WHERE (title LIKE ? OR content LIKE ?) 
                AND (category_id = 0 OR category_id IS NULL)
                AND is_private = 0 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$searchTerm, $searchTerm, $limit, $offset]);
        } else {
            // Search in specific category only
            $stmt = $this->db->prepare("
                SELECT * FROM pastes 
                WHERE (title LIKE ? OR content LIKE ?) 
                AND category_id = ? 
                AND is_private = 0 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$searchTerm, $searchTerm, $categoryId, $limit, $offset]);
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getCategoryStats($categoryId) {
        if ($categoryId == 0) {
            // Stats for uncategorized pastes
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_pastes,
                    SUM(views) as total_views,
                    COUNT(CASE WHEN is_private = 0 THEN 1 END) as public_pastes,
                    MAX(created_at) as last_updated
                FROM pastes 
                WHERE (category_id = 0 OR category_id IS NULL)
            ");
            $stmt->execute();
        } else {
            // Stats for specific category
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_pastes,
                    SUM(views) as total_views,
                    COUNT(CASE WHEN is_private = 0 THEN 1 END) as public_pastes,
                    MAX(created_at) as last_updated
                FROM pastes 
                WHERE category_id = ?
            ");
            $stmt->execute([$categoryId]);
        }
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
