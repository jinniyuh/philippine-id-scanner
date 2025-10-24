<?php
/**
 * Simple test to verify barcode scanning works without login
 */

// Set JSON header
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check if image file was uploaded
        if (!isset($_FILES['barcode_image']) || $_FILES['barcode_image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['error' => 'No image file uploaded or upload error']);
            exit();
        }
        
        $uploaded_file = $_FILES['barcode_image'];
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($uploaded_file['type'], $allowed_types)) {
            echo json_encode(['error' => 'Invalid file type. Only JPEG, PNG, and GIF are allowed.']);
            exit();
        }
        
        // Validate file size (max 5MB)
        if ($uploaded_file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['error' => 'File size too large. Maximum 5MB allowed.']);
            exit();
        }
        
        // Create uploads directory if it doesn't exist
        $upload_dir = 'uploads/barcode_scans/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($uploaded_file['name'], PATHINFO_EXTENSION);
        $filename = 'barcode_scan_' . time() . '_' . uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($uploaded_file['tmp_name'], $file_path)) {
            echo json_encode(['error' => 'Failed to save uploaded file']);
            exit();
        }
        
        // Call Python DocTR scanner
        $python_script = 'doctr_scanner.py';
        $command = "python " . escapeshellarg($python_script) . " " . escapeshellarg($file_path) . " 2>&1";
        
        $output = shell_exec($command);
        
        if ($output === null) {
            echo json_encode(['error' => 'Failed to execute barcode scanner']);
            exit();
        }
        
        // Parse Python output
        $result = json_decode($output, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['error' => 'Invalid response from barcode scanner: ' . $output]);
            exit();
        }
        
        // Clean up uploaded file
        unlink($file_path);
        
        // Return the result
        echo json_encode($result);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?>
