<?php
/**
 * CSRF (Cross-Site Request Forgery) Protection
 * Prevents unauthorized form submissions from malicious sites
 */

/**
 * Generate or retrieve CSRF token
 * @return string CSRF token
 */
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token Token to verify
 * @return bool True if valid, false otherwise
 */
function verify_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    // Use hash_equals to prevent timing attacks
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate hidden input field with CSRF token
 * @return string HTML input field
 */
function csrf_token_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Get CSRF token value (for AJAX requests)
 * @return string CSRF token
 */
function get_csrf_token() {
    return generate_csrf_token();
}

/**
 * Validate CSRF token from POST request
 * Dies with JSON error if invalid
 */
function validate_csrf_or_die() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    
    if (!verify_csrf_token($token)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'CSRF validation failed',
            'message' => 'Invalid security token. Please refresh and try again.'
        ]);
        exit();
    }
}

/**
 * Get CSRF token as meta tag for JavaScript
 * @return string HTML meta tag
 */
function csrf_meta_tag() {
    $token = generate_csrf_token();
    return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}
?>

