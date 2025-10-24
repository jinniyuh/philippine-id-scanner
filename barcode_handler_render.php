<?php
/**
 * Barcode Handler for Render Flask API
 * This handler connects to your Render Flask API for ID scanning
 */

// Set content type to JSON
header('Content-Type: application/json');

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Configuration - UPDATE THIS WITH YOUR RENDER URL
$RENDER_API_URL = 'https://your-app-name.onrender.com'; // Replace with your actual Render URL

// Function to scan ID using Render API
function scanIdWithRender($image_path) {
    global $RENDER_API_URL;
    
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $RENDER_API_URL . '/api/scan-id',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'image' => new CURLFile($image_path)
        ]
    ]);
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    
    curl_close($curl);
    
    if ($error) {
        return [
            'success' => false,
            'error' => 'cURL error: ' . $error
        ];
    }
    
    if ($http_code !== 200) {
        return [
            'success' => false,
            'error' => 'HTTP error: ' . $http_code
        ];
    }
    
    $result = json_decode($response, true);
    return $result ?: [
        'success' => false,
        'error' => 'Invalid JSON response'
    ];
}

// Function to validate name and barangay using Render API
function validateWithRender($entered_name, $entered_barangay, $scanned_name, $scanned_barangay) {
    global $RENDER_API_URL;
    
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $RENDER_API_URL . '/api/validate-name',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'entered_name' => $entered_name,
            'entered_barangay' => $entered_barangay,
            'scanned_name' => $scanned_name,
            'scanned_barangay' => $scanned_barangay
        ])
    ]);
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    
    curl_close($curl);
    
    if ($error) {
        return [
            'success' => false,
            'error' => 'cURL error: ' . $error
        ];
    }
    
    if ($http_code !== 200) {
        return [
            'success' => false,
            'error' => 'HTTP error: ' . $http_code
        ];
    }
    
    $result = json_decode($response, true);
    return $result ?: [
        'success' => false,
        'error' => 'Invalid JSON response'
    ];
}

// Function to check if Render API is available
function checkRenderAPI() {
    global $RENDER_API_URL;
    
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $RENDER_API_URL . '/api/health',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    
    curl_close($curl);
    
    if ($error || $http_code !== 200) {
        return false;
    }
    
    $result = json_decode($response, true);
    return $result && isset($result['status']) && $result['status'] === 'healthy';
}

// Main processing logic
try {
    // Check if Render API is available
    if (!checkRenderAPI()) {
        echo json_encode([
            'success' => false,
            'error' => 'Render API is not available. Please check your deployment.',
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
    error_log("DEBUG: Render API URL: $RENDER_API_URL");
    
    // Scan the ID using Render API
    $scan_result = scanIdWithRender($file_path);
    
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
    error_log("DEBUG: Render API response: " . json_encode($scan_result));
    
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
