<?php
session_start();
include 'includes/conn.php';
include 'includes/activity_logger.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION['user_id'];

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
$filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
$filepath = $upload_dir . $filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    // Update database with new profile picture path
    $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE user_id = ?");
    $stmt->bind_param("si", $filepath, $user_id);
    
    if ($stmt->execute()) {
        // Log activity when profile picture is successfully updated
        $activity_message = "Updated profile picture";
        logActivity($conn, $user_id, $activity_message);
        
        echo json_encode(['success' => true, 'filepath' => $filepath]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update database']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save file']);
}

$conn->close();
?>
