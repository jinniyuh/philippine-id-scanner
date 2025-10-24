<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['notification_id'])) {
    echo json_encode(['success' => false, 'error' => 'Notification ID required']);
    exit();
}

$notification_id = $_POST['notification_id'];
$admin_id = $_SESSION['user_id'];

// Verify that the notification belongs to this admin
$stmt = $conn->prepare("SELECT notification_id FROM notifications WHERE notification_id = ? AND user_id = ?");
$stmt->bind_param("ii", $notification_id, $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Notification not found or access denied']);
    exit();
}

// Delete the notification
$stmt = $conn->prepare("DELETE FROM notifications WHERE notification_id = ? AND user_id = ?");
$stmt->bind_param("ii", $notification_id, $admin_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Notification deleted successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete notification']);
}
?> 