<?php
session_start();
include 'includes/conn.php';

if (!isset($_SESSION['client_id']) || $_SESSION['role'] !== 'client') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['notification_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing notification ID']);
    exit();
}

$notification_id = $_POST['notification_id'];
$client_id = $_SESSION['client_id'];

// Update notification as read if targeted to this client
$stmt = $conn->prepare("UPDATE notifications SET status = 'Read' WHERE notification_id = ? AND client_id = ?");
$stmt->bind_param("ii", $notification_id, $client_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

$stmt->close();
$conn->close();
?>