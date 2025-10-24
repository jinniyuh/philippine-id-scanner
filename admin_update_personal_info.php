<?php
session_start();
include 'includes/conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get and sanitize input data
$name = trim($_POST['name'] ?? '');
$username = trim($_POST['username'] ?? '');
$contact_number = trim($_POST['contact_number'] ?? '');
$address = trim($_POST['address'] ?? '');

// Validate inputs
if (empty($name) || empty($username) || empty($contact_number) || empty($address)) {
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit();
}

try {
    // Update admin information
    $stmt = $conn->prepare("UPDATE users SET name = ?, username = ?, contact_number = ?, address = ? WHERE user_id = ?");
    $stmt->bind_param("ssssi", $name, $username, $contact_number, $address, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update information: ' . $stmt->error]);
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error updating admin information: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}

$conn->close();
?>