<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'includes/conn.php';
include 'upload_time_helper.php';

// Set timezone to Asia/Manila (Philippines)
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['client_id']) || $_SESSION['role'] !== 'client') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if (empty($_FILES['animal_photos']) || !isset($_FILES['animal_photos']['tmp_name']) || empty($_FILES['animal_photos']['tmp_name'][0])) {
    echo json_encode(['success' => false, 'error' => 'No photos uploaded. Please select photos to upload.']);
    exit();
}

$client_id = $_SESSION['client_id'];
$animal_id = null;

if (isset($_POST['animal_id']) && !empty($_POST['animal_id'])) {
    $animal_id = $_POST['animal_id'];
    
    // Verify that this animal belongs to the client
    $stmt = $conn->prepare("SELECT animal_id FROM livestock_poultry WHERE animal_id = ? AND client_id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("ii", $animal_id, $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid animal selected']);
        exit();
    }
    
    // Check if client can upload for this animal
    $upload_check = canClientUpload($client_id, $animal_id, $conn);
    if (!$upload_check['can_upload']) {
        echo json_encode(['success' => false, 'error' => $upload_check['reason']]);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Please select an animal']);
    exit();
}

$upload_dir = 'uploads/animal_photos/';

// Create directory if it doesn't exist
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true)) {
        echo json_encode(['success' => false, 'error' => 'Failed to create upload directory']);
        exit();
    }
}

$uploaded_files = [];
$errors = [];

// Get animal details for notification (only once)
$stmt = $conn->prepare("SELECT species, animal_type FROM livestock_poultry WHERE animal_id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Failed to prepare animal query: ' . $conn->error]);
    exit();
}
$stmt->bind_param("i", $animal_id);
$stmt->execute();
$animal = $stmt->get_result()->fetch_assoc();

if (!$animal) {
    echo json_encode(['success' => false, 'error' => 'Animal not found']);
    exit();
}

$photo_count = 0;
foreach ($_FILES['animal_photos']['tmp_name'] as $key => $tmp_name) {
    $file_name = $_FILES['animal_photos']['name'][$key];
    $file_size = $_FILES['animal_photos']['size'][$key];
    $file_tmp = $_FILES['animal_photos']['tmp_name'][$key];
    $file_type = $_FILES['animal_photos']['type'][$key];
    
    // Skip empty files
    if (empty($tmp_name) || $file_size === 0) {
        continue;
    }
    
    // Check for upload errors
    if ($_FILES['animal_photos']['error'][$key] !== UPLOAD_ERR_OK) {
        $errors[] = "Upload error for $file_name: " . $_FILES['animal_photos']['error'][$key];
        continue;
    }
    
    // Generate unique filename
    $unique_name = uniqid() . '_' . $file_name;
    $file_path = $upload_dir . $unique_name;
    
    // Check if image file is a actual image or fake image
    $check = getimagesize($file_tmp);
    if($check === false) {
        $errors[] = "File $file_name is not an image.";
        continue;
    }
    
    // Check file size (limit to 5MB)
    if ($file_size > 5000000) {
        $errors[] = "File $file_name is too large.";
        continue;
    }
    
    // Add this after line 2 for debugging
    error_log("Upload attempt started. POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    
    if (move_uploaded_file($file_tmp, $file_path)) {
        // Begin transaction
        $conn->begin_transaction();
        try {
            // Save photo to database with current timestamp
            $current_timestamp = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("INSERT INTO animal_photos (animal_id, photo_path, uploaded_at) VALUES (?, ?, ?)");
            if (!$stmt) {
                throw new Exception('Failed to prepare photo insert: ' . $conn->error);
            }
            $stmt->bind_param("iss", $animal_id, $file_path, $current_timestamp);
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute photo insert: ' . $stmt->error);
            }
    
            $conn->commit();
            $uploaded_files[] = $file_path;
            $photo_count++;
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Database error details: " . $e->getMessage());
            error_log("SQL Error: " . $conn->error);
            $errors[] = "Database error for $file_name: " . $e->getMessage();
            // Clean up the uploaded file if database insert failed
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
    } else {
        $errors[] = "Failed to upload $file_name";
    }
}

// Create notification for all admin and staff users (only once after all photos are uploaded)
if ($photo_count > 0) {
    // Get client name from session (set during login)
    $client_name = $_SESSION['name'] ?? 'Unknown Client';
    
    $message = $client_name . " uploaded new photos for " . $animal['species'] . " (" . $animal['animal_type'] . ")";
    
    // Get all admin and staff users
    $admin_staff_stmt = $conn->prepare("SELECT user_id FROM users WHERE role IN ('admin', 'staff')");
    if ($admin_staff_stmt) {
        $admin_staff_stmt->execute();
        $admin_staff_result = $admin_staff_stmt->get_result();
        
        // Send notification to each admin and staff member
        while ($user = $admin_staff_result->fetch_assoc()) {
            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, timestamp, status) VALUES (?, ?, NOW(), 'Unread')");
            if ($notif_stmt) {
                $notif_stmt->bind_param("is", $user['user_id'], $message);
                $notif_stmt->execute();
            }
        }
    }
}

if (empty($errors)) {
    echo json_encode(['success' => true, 'files' => $uploaded_files, 'message' => "$photo_count photo(s) uploaded successfully"]);
} else {
    echo json_encode(['success' => false, 'error' => implode(", ", $errors), 'uploaded_count' => $photo_count]);
}
?>