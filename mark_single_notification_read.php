<?php
session_start();
include 'includes/conn.php';

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); 
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}

if (!isset($_POST['notification_id'])) {
    echo json_encode(['success' => false, 'error' => 'Notification ID required']);
    exit();
}

$admin_id = $_SESSION['user_id'];
$notification_id = $_POST['notification_id'];

// Update the specific notification to 'read' status
$stmt = $conn->prepare("UPDATE notifications SET status = 'Read' WHERE notification_id = ? AND user_id = ?");

if ($stmt) {
    $stmt->bind_param("ii", $notification_id, $admin_id);
    $result = $stmt->execute();
    $stmt->close();
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to mark notification as read']);
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
