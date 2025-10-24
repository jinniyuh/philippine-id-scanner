<?php
// Set headers for JSON response
header('Content-Type: application/json');

// Enable error reporting but don't display errors
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
include 'includes/conn.php';

if (!isset($_SESSION['client_id']) || $_SESSION['role'] !== 'client') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

$client_id = $_SESSION['client_id'];

if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
    exit();
}

$file = $_FILES['profile_photo'];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$max_size = 5 * 1024 * 1024; // 5MB

// Validate file type
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Please upload JPG, PNG, or GIF']);
    exit();
}

// Remove the file size check and continue with upload
$upload_dir = 'uploads/profile_pictures/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'profile_' . $client_id . '_' . time() . '.' . $extension;
$filepath = $upload_dir . $filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    // Update database with new profile picture path
    $stmt = $conn->prepare("UPDATE clients SET profile_photo = ? WHERE client_id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("si", $filepath, $client_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'filepath' => $filepath, 'message' => 'Profile picture updated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update database: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save file. Check directory permissions.']);
}

$conn->close();
?>