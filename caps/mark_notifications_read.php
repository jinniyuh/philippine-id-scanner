<?php
session_start();
include 'includes/conn.php';

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); 
    echo "Access denied.";
    exit();
}

$admin_id = $_SESSION['user_id'];

// Update unread notifications to 'read'
$stmt = $conn->prepare("UPDATE notifications SET status = 'Read' WHERE user_id = ? AND status = 'Unread'");

if ($stmt) {
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $stmt->close();
    echo "Notifications marked as read.";
} else {
    http_response_code(500);
    echo "Failed to mark notifications as read.";
}
?>
