<?php
session_start();
include 'includes/conn.php';

if (!isset($_SESSION['client_id']) || $_SESSION['role'] !== 'client') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

$client_id = $_SESSION['client_id'];
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validate inputs
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit();
}

if ($new_password !== $confirm_password) {
    echo json_encode(['success' => false, 'error' => 'New passwords do not match']);
    exit();
}

// Verify current password
$stmt = $conn->prepare("SELECT password FROM clients WHERE client_id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!password_verify($current_password, $result['password'])) {
    echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
    exit();
}

// Update password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE clients SET password = ?, password_changed_at = NOW() WHERE client_id = ?");
$stmt->bind_param("si", $hashed_password, $client_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update password']);
}

$stmt->close();
$conn->close();
?>