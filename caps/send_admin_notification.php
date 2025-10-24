<?php
session_start();
include 'includes/conn.php';

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get the JSON data from the request
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Validate the data
if (!isset($data['client_id']) || !isset($data['notification_type']) || !isset($data['message'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Prepare the data for insertion
$client_id = $data['client_id'];
$notification_type = $data['notification_type'];
$message = $data['message'];
$created_at = date('Y-m-d H:i:s');
$status = 'Unread';

// Insert the notification into the database
$stmt = $conn->prepare("INSERT INTO notifications (user_id, notification_type, message, created_at, status, target_role) 
                        VALUES (?, ?, ?, ?, ?, 'admin')");
$stmt->bind_param("issss", $client_id, $notification_type, $message, $created_at, $status);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}

$stmt->close();
$conn->close();
?>