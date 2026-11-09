<?php
class Security {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

// CSRF Protection
    public function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

    public function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
    return true;
}

// Rate Limiting
    public function check_rate_limit($user_id, $action, $max_attempts = 5, $time_window = 300) {
    $query = "SELECT COUNT(*) as attempts FROM logs 
              WHERE user_id = ? AND action = ? 
              AND timestamp > DATE_SUB(NOW(), INTERVAL ? SECOND)";
        $stmt = $this->conn->prepare($query);
    $stmt->execute([$user_id, $action, $time_window]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['attempts'] < $max_attempts;
}

// Session Management
    public function regenerate_session() {
    if (!isset($_SESSION['last_regeneration']) || 
        time() - $_SESSION['last_regeneration'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

    public function check_session_timeout() {
    if (isset($_SESSION['last_activity']) && 
        time() - $_SESSION['last_activity'] > 7200) {
        session_unset();
        session_destroy();
        header('Location: index.php?timeout=1');
        exit();
    }
    $_SESSION['last_activity'] = time();
}

// Input Sanitization
    public function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Password Reset
    public function generate_password_reset_token() {
    return bin2hex(random_bytes(32));
}

    public function store_password_reset_token($user_id, $token) {
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    $query = "UPDATE users SET 
              password_reset_token = ?, 
              password_reset_expires = ? 
              WHERE id = ?";
        $stmt = $this->conn->prepare($query);
    return $stmt->execute([$token, $expires, $user_id]);
}

// Logging
    public function logActivity($user_id, $action) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
        $query = "INSERT INTO logs (user_id, action, ip_address, user_agent) 
                  VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$user_id, $action, $ip_address, $user_agent]);
}

// User Activity Tracking
    public function update_last_activity($user_id) {
    $query = "UPDATE users SET last_login = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($query);
    return $stmt->execute([$user_id]);
}

// Login Attempt Tracking
    public function increment_login_attempts($user_id) {
    $query = "UPDATE users SET login_attempts = login_attempts + 1 WHERE id = ?";
        $stmt = $this->conn->prepare($query);
    return $stmt->execute([$user_id]);
}

    public function reset_login_attempts($user_id) {
    $query = "UPDATE users SET login_attempts = 0 WHERE id = ?";
        $stmt = $this->conn->prepare($query);
    return $stmt->execute([$user_id]);
}

// Check if user is locked out
    public function is_user_locked_out($user_id) {
    $query = "SELECT login_attempts FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($query);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['login_attempts'] >= 5;
}

// Initialize security features
    public function init_security() {
        $this->regenerate_session();
        $this->check_session_timeout();
    
    if (!isset($_SESSION['csrf_token'])) {
            $this->generate_csrf_token();
        }
    }
} 