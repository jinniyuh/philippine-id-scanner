<?php
/**
 * Secure Database Connection
 * Auto-detects environment and loads appropriate credentials
 */

// Session Security Configuration
if (session_status() === PHP_SESSION_NONE) {
    // Harden session security
    ini_set('session.cookie_httponly', 1);      // Prevent JavaScript access to session cookie
    ini_set('session.use_only_cookies', 1);      // Only use cookies, not URL params
    ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
    
    // Enable secure flag on HTTPS
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', 1);
    }
    
    session_start();
}

// Auto-detect environment and use appropriate database credentials
$is_localhost = false;

// Check if running from command line (CLI)
if (php_sapi_name() === 'cli') {
    // When running from CLI, assume localhost for development
    $is_localhost = true;
} else {
    // When running from web server, check HTTP_HOST
    $http_host = $_SERVER['HTTP_HOST'] ?? '';
    $is_localhost = ($http_host == 'localhost' || 
                     $http_host == '127.0.0.1' || 
                     strpos($http_host, 'localhost:') === 0 ||
                     strpos($http_host, '127.0.0.1:') === 0);
}

if ($is_localhost) {
    // Local development settings
    $servername = "localhost";
    $username = "root"; 
    $password = ""; 
    $database = "bagovets";
} else {
    // Live server settings - Load from secure config file
    $config_file = __DIR__ . '/../config.env.php';
    
    if (file_exists($config_file)) {
        $config = require $config_file;
        $servername = $config['db_host'];
        $username = $config['db_user'];
        $password = $config['db_pass'];
        $database = $config['db_name'];
    } else {
        // Fallback (should create config.env.php on live server)
        die("SECURITY ERROR: Configuration file missing! Create config.env.php from config.env.example.php");
    }
}

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Align PHP and MySQL timezones to avoid month mismatches between local and live
@date_default_timezone_set('Asia/Manila');
@$conn->query("SET time_zone = '+08:00'");
