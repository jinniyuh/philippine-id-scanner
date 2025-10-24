<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
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
    // Live server settings
    $servername = "localhost";
    $username = "u520834156_userIMSvet25"; 
    $password = "Uk~V3GKL4"; 
    $database = "u520834156_dbBagoVetIMS";
}

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Align PHP and MySQL timezones to avoid month mismatches between local and live
@date_default_timezone_set('Asia/Manila');
@$conn->query("SET time_zone = '+08:00'");
