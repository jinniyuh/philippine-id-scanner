<?php
/**
 * Updated barcode handler that uses Flask API instead of direct Python execution
 */

// Set content type to JSON
header('Content-Type: application/json');

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include the Flask API client
require_once 'flask_api_client.php';

// Function to normalize text for comparison
function normalize_text($text) {
    if (!$text) return "";
    $text = strtoupper($text);
    $text = preg_replace('/BRGY\.?/', 'BARANGAY', $text);
    $text = preg_replace('/[^A-Z0-9\s]/', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

// Function to check if Flask API is available
function checkFlaskAPI() {
    $client = new FlaskAPIClient();
    return $client->healthCheck();
}

// Function to scan ID using Flask API
function scanIdWithFlask($image_path) {
    $client = new FlaskAPIClient();
    return $client->scanIdFromFile($image_path);
}

// Function to validate name and barangay using Flask API
function validateWithFlask($entered_name, $entered_barangay, $scanned_name, $scanned_barangay) {
    $client = new FlaskAPIClient();
    return $client->validateName($entered_name, $entered_barangay, $scanned_name, $scanned_barangay);
}

// Main processing logic
try {
    // Check if Flask API is running
    if (!checkFlaskAPI()) {
        echo json_encode([
            'success' => false,
            'error' => 'Flask API is not running. Please start the Flask API server first.',
            'fallback' => true
        ]);
        exit;
    }
    
    // Check if image was uploaded
    if (!isset($_FILES['id_image']) || $_FILES['id_image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            'success' => false,
            'error' => 'No image uploaded or upload error occurred'
        ]);
        exit;
    }
    
    $uploaded_file = $_FILES['id_image'];
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $file_type = mime_content_type($uploaded_file['tmp_name']);
    
    if (!in_array($file_type, $allowed_types)) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid file type. Please upload a valid image (JPEG, PNG, GIF)'
        ]);
        exit;
    }
    
    // Validate file size (5MB limit)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($uploaded_file['size'] > $max_size) {
        echo json_encode([
            'success' => false,
            'error' => 'File too large. Maximum size is 5MB'
        ]);
        exit;
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = 'uploads/barcode_scans';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to create upload directory'
            ]);
            exit;
        }
    }
    
    // Generate unique filename
    $file_extension = pathinfo($uploaded_file['name'], PATHINFO_EXTENSION);
    $filename = 'id_scan_' . time() . '_' . uniqid() . '.' . $file_extension;
    $file_path = $upload_dir . '/' . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($uploaded_file['tmp_name'], $file_path)) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to save uploaded file'
        ]);
        exit;
    }
    
    // Log the file path for debugging
    error_log("DEBUG: Processing file: $file_path");
    
    // Scan the ID using Flask API
    $scan_result = scanIdWithFlask($file_path);
    
    // Clean up the uploaded file
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    if (!$scan_result['success']) {
        echo json_encode([
            'success' => false,
            'error' => $scan_result['error'],
            'fallback' => true
        ]);
        exit;
    }
    
    // Extract the results
    $extracted_name = $scan_result['name'] ?? '';
    $extracted_barangay = $scan_result['barangay'] ?? '';
    
    // Log the extracted data
    error_log("DEBUG: Extracted name: '$extracted_name'");
    error_log("DEBUG: Extracted barangay: '$extracted_barangay'");
    
    // Return success response
    echo json_encode([
        'success' => true,
        'name' => $extracted_name,
        'barangay' => $extracted_barangay,
        'city' => $scan_result['city'] ?? 'Bago City',
        'province' => $scan_result['province'] ?? 'Negros Occidental',
        'message' => $scan_result['message'] ?? 'Text extracted successfully from Philippine ID'
    ]);
    
} catch (Exception $e) {
    error_log("ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage(),
        'fallback' => true
    ]);
}
?>
