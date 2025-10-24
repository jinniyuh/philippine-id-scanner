<?php
session_start();
include 'includes/conn.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['count' => 0]);
    exit();
}

// Get notifications count for admin
$admin_id = $_SESSION['user_id'] ?? 1;
$notif_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND (status = 'Unread' OR status = 'unread' OR status = 0)";
$notif_stmt = $conn->prepare($notif_query);

if ($notif_stmt) {
    $notif_stmt->bind_param("i", $admin_id);
    $notif_stmt->execute();
    $notif_result = $notif_stmt->get_result();
    $unread_data = $notif_result->fetch_assoc();
    $unread_count = $unread_data['count'];
} else {
    $unread_count = 0;
}

echo json_encode(['count' => (int)$unread_count]);
?>
