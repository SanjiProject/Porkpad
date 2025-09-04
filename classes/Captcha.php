<?php
require_once 'Database.php';

class Captcha {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generateCaptcha() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Generate random math problem (only addition and subtraction with single digits 1-9)
        // This keeps the math simple and easy for users
        $num1 = rand(1, 9);
        $num2 = rand(1, 9);
        $operators = ['+', '-'];
        $operator = $operators[rand(0, 1)];
        
        switch ($operator) {
            case '+':
                $answer = $num1 + $num2;
                $question = "$num1 + $num2";
                break;
            case '-':
                // Ensure positive result
                if ($num1 < $num2) {
                    $temp = $num1;
                    $num1 = $num2;
                    $num2 = $temp;
                }
                $answer = $num1 - $num2;
                $question = "$num1 - $num2";
                break;
        }
        
        // Store in session
        $_SESSION['captcha_question'] = $question;
        $_SESSION['captcha_answer'] = $answer;
        $_SESSION['captcha_time'] = time();
        
        // Also store in database for additional security
        $sessionId = session_id();
        $expiresAt = date('Y-m-d H:i:s', time() + 300); // 5 minutes
        
        try {
            // Clean up old captchas
            $this->cleanupExpiredCaptchas();
            
            // Insert new captcha
            $stmt = $this->db->prepare("
                INSERT INTO captcha_sessions (session_id, question, answer, expires_at) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                question = VALUES(question), 
                answer = VALUES(answer), 
                expires_at = VALUES(expires_at),
                attempts = 0
            ");
            $stmt->execute([$sessionId, $question, $answer, $expiresAt]);
        } catch (PDOException $e) {
            // Fallback to session-only storage if DB fails
        }
        
        return $question;
    }
    
    public function verifyCaptcha($userAnswer) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($userAnswer)) {
            return ['success' => false, 'message' => 'Please solve the math problem'];
        }
        
        // Check session first
        if (!isset($_SESSION['captcha_answer']) || !isset($_SESSION['captcha_time'])) {
            return ['success' => false, 'message' => 'Captcha expired. Please refresh the page.'];
        }
        
        // Check if captcha is expired (5 minutes)
        if (time() - $_SESSION['captcha_time'] > 300) {
            $this->clearCaptcha();
            return ['success' => false, 'message' => 'Captcha expired. Please refresh the page.'];
        }
        
        $sessionId = session_id();
        $correctAnswer = $_SESSION['captcha_answer'];
        
        // Verify against database as well
        try {
            $stmt = $this->db->prepare("
                SELECT answer, attempts FROM captcha_sessions 
                WHERE session_id = ? AND expires_at > NOW()
            ");
            $stmt->execute([$sessionId]);
            $dbCaptcha = $stmt->fetch();
            
            if ($dbCaptcha) {
                // Check attempt limit
                if ($dbCaptcha['attempts'] >= 3) {
                    $this->clearCaptcha();
                    return ['success' => false, 'message' => 'Too many failed attempts. Please refresh the page.'];
                }
                
                // Increment attempts
                $stmt = $this->db->prepare("UPDATE captcha_sessions SET attempts = attempts + 1 WHERE session_id = ?");
                $stmt->execute([$sessionId]);
                
                $correctAnswer = $dbCaptcha['answer'];
            }
        } catch (PDOException $e) {
            // Continue with session-based verification
        }
        
        if (intval($userAnswer) === $correctAnswer) {
            $this->clearCaptcha();
            return ['success' => true, 'message' => 'Captcha verified'];
        } else {
            return ['success' => false, 'message' => 'Incorrect answer. Please try again.'];
        }
    }
    
    public function clearCaptcha() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['captcha_question']);
        unset($_SESSION['captcha_answer']);
        unset($_SESSION['captcha_time']);
        
        // Clear from database
        try {
            $sessionId = session_id();
            $stmt = $this->db->prepare("DELETE FROM captcha_sessions WHERE session_id = ?");
            $stmt->execute([$sessionId]);
        } catch (PDOException $e) {
            // Ignore database errors
        }
    }
    
    private function cleanupExpiredCaptchas() {
        try {
            $stmt = $this->db->prepare("DELETE FROM captcha_sessions WHERE expires_at < NOW()");
            $stmt->execute();
        } catch (PDOException $e) {
            // Ignore cleanup errors
        }
    }
    
    public function requiresCaptcha() {
        // Always require captcha for bot protection (both logged in and anonymous users)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check admin settings
        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM admin_settings WHERE setting_key = 'require_captcha'");
            $stmt->execute();
            $setting = $stmt->fetch();
            
            if ($setting && $setting['setting_value'] == '0') {
                return false;
            }
        } catch (PDOException $e) {
            // Default to requiring captcha if setting can't be read
        }
        
        return true; // Anonymous users need captcha by default
    }
    
    public function getCurrentCaptcha() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['captcha_question'] ?? null;
    }
    
    public function createImageCaptcha() {
        // Simple text-based math captcha for now
        // Could be enhanced with image generation later
        $question = $this->getCurrentCaptcha();
        if (!$question) {
            $question = $this->generateCaptcha();
        }
        
        return $question;
    }
    
    public function generateQuestion() {
        return $this->generateCaptcha();
    }
    
    public function verify($userAnswer) {
        $result = $this->verifyCaptcha($userAnswer);
        return $result['success'];
    }
}
?>
