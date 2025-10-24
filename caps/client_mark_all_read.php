<?php
session_start();
include 'includes/conn.php';

if (!isset($_SESSION['client_id']) || $_SESSION['role'] !== 'client') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$client_id = $_SESSION['client_id'];

// Update all unread notifications for this client
$stmt = $conn->prepare("UPDATE notifications SET status = 'Read' WHERE client_id = ? AND status = 'Unread'");

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $client_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'count' => $stmt->affected_rows]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>