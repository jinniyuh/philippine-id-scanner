<?php
/**
 * Security Headers and Functions
 * Provides additional security measures
 */

/**
 * Set security headers
 */
function set_security_headers() {
    // Prevent clickjacking
    header("X-Frame-Options: SAMEORIGIN");
    
    // Prevent MIME type sniffing
    header("X-Content-Type-Options: nosniff");
    
    // Enable XSS protection
    header("X-XSS-Protection: 1; mode=block");
    
    // Content Security Policy (adjust as needed)
    header("Content-Security-Policy: default-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com 'unsafe-inline' 'unsafe-eval'; img-src 'self' data: https:;");
    
    // Referrer Policy
    header("Referrer-Policy: strict-origin-when-cross-origin");
    
    // Permissions Policy
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
}

/**
 * Regenerate session ID (call after login)
 */
function regenerate_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * Sanitize output for HTML
 * @param string $string String to sanitize
 * @return string Sanitized string
 */
function html_escape($string) {
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Sanitize for JavaScript
 * @param string $string String to sanitize
 * @return string Sanitized string
 */
function js_escape($string) {
    return json_encode($string, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

/**
 * Validate and sanitize filename
 * @param string $filename Filename to sanitize
 * @return string Safe filename
 */
function sanitize_filename($filename) {
    // Remove any path information
    $filename = basename($filename);
    
    // Remove special characters
    $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
    
    // Limit length
    $filename = substr($filename, 0, 255);
    
    return $filename;
}

/**
 * Check rate limiting (simple implementation)
 * @param string $action Action being rate limited
 * @param int $max_attempts Maximum attempts allowed
 * @param int $time_window Time window in seconds
 * @return bool True if allowed, false if rate limited
 */
function check_rate_limit($action, $max_attempts = 5, $time_window = 300) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $key = 'rate_limit_' . $action;
    $now = time();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }
    
    // Remove old attempts outside time window
    $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($now, $time_window) {
        return ($now - $timestamp) < $time_window;
    });
    
    // Check if rate limit exceeded
    if (count($_SESSION[$key]) >= $max_attempts) {
        return false;
    }
    
    // Record this attempt
    $_SESSION[$key][] = $now;
    
    return true;
}

/**
 * Verify user has required role
 * @param array $allowed_roles Allowed roles
 * @return bool True if authorized, exits if not
 */
function require_role($allowed_roles = []) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        http_response_code(403);
        if (strpos($_SERVER['REQUEST_URI'], '.php') !== false && 
            strpos($_SERVER['REQUEST_URI'], 'get_') === false) {
            header("Location: login.php");
            exit();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit();
        }
    }
    
    if (!empty($allowed_roles) && !in_array($_SESSION['role'], $allowed_roles)) {
        http_response_code(403);
        if (strpos($_SERVER['REQUEST_URI'], '.php') !== false) {
            header("Location: login.php");
            exit();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
            exit();
        }
    }
    
    return true;
}
?>

