<?php
/**
 * Security Helper Functions
 */

/**
 * Check rate limit for an action
 * @param string $action Action identifier (e.g., 'login')
 * @param int $max_attempts Maximum allowed attempts
 * @param int $window Time window in seconds
 * @return bool True if allowed, false if rate limited
 */
function check_rate_limit($action, $max_attempts = 5, $window = 300) {
    $key = 'rate_limit_' . $action;
    $now = time();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 1, 'start' => $now];
        return true;
    }
    
    $data = $_SESSION[$key];
    
    // Reset if window expired
    if (($now - $data['start']) > $window) {
        $_SESSION[$key] = ['count' => 1, 'start' => $now];
        return true;
    }
    
    // Check if limit exceeded
    if ($data['count'] >= $max_attempts) {
        return false;
    }
    
    // Increment counter
    $_SESSION[$key]['count']++;
    return true;
}

/**
 * Clear rate limit for an action (e.g., after successful login)
 * @param string $action Action identifier
 */
function clear_rate_limit($action) {
    $key = 'rate_limit_' . $action;
    if (isset($_SESSION[$key])) {
        unset($_SESSION[$key]);
    }
}

/**
 * Get remaining attempts for an action
 * @param string $action Action identifier
 * @param int $max_attempts Maximum allowed attempts
 * @return int Remaining attempts
 */
function get_remaining_attempts($action, $max_attempts = 5) {
    $key = 'rate_limit_' . $action;
    if (!isset($_SESSION[$key])) {
        return $max_attempts;
    }
    return max(0, $max_attempts - $_SESSION[$key]['count']);
}

/**
 * Regenerate session ID to prevent session fixation
 */
function regenerate_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * Sanitize input data
 * @param string $data Input data
 * @return string Sanitized data
 */
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a secure random token
 * @param int $length Token length in bytes
 * @return string Hex token
 */
function generate_secure_token($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Generate CSRF token
 * @return string CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generate_secure_token(32);
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 * @param string $token Token to validate
 * @return bool True if valid
 */
function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

