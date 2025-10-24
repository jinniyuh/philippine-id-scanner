<?php
/**
 * Barcode Handler for Bago City Veterinary IMS
 * Handles barcode scanning requests and returns resident information
 */

// Set JSON header
header('Content-Type: application/json');

// Note: No login required for barcode scanning during registration

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
            if (!mkdir($upload_dir, 0755, true)) {
                echo json_encode(['error' => 'Failed to create upload directory. Please check server permissions.']);
                exit();
            }
        }
        
        // Check if directory is writable
        if (!is_writable($upload_dir)) {
            echo json_encode(['error' => 'Upload directory is not writable. Please check server permissions.']);
            exit();
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
        
        // Call Python DocTR scanner for text recognition
        $python_script = 'doctr_scanner.py'; // Use DocTR for Philippine ID text recognition
        $python_paths = [
            'python3', // Try python3 first (common on Linux servers)
            'python', // Try default python
            '/usr/bin/python3', // Common Linux path
            '/usr/bin/python', // Common Linux path
            '/usr/local/bin/python3', // Alternative Linux path
            '/usr/local/bin/python', // Alternative Linux path
            // Windows paths (for local development)
            'C:\\Users\\patri\\AppData\\Local\\Programs\\Python\\Python311\\python.exe',
            'C:\\Python39\\python.exe',
            'C:\\Python310\\python.exe',
            'C:\\Python311\\python.exe',
            'C:\\Python312\\python.exe'
        ];
        
        $command = null;
        $output = null;
        
        // Try different Python paths
        foreach ($python_paths as $python_path) {
            $test_command = escapeshellarg($python_path) . " --version 2>&1";
            $test_output = shell_exec($test_command);
            
            if ($test_output && strpos($test_output, 'Python') !== false) {
                $command = escapeshellarg($python_path) . " " . escapeshellarg($python_script) . " " . escapeshellarg($file_path) . " 2>&1";
                break;
            }
        }
        
        // If no Python found, try a fallback approach
        if (!$command) {
            // Try to use the system's default Python
            $command = "python " . escapeshellarg($python_script) . " " . escapeshellarg($file_path) . " 2>&1";
        }
        
        $output = shell_exec($command);
        
        // Debug: Log the command and output
        error_log("DEBUG: Python command: $command");
        error_log("DEBUG: Python output: " . ($output ?: 'NULL/EMPTY'));
        
        if ($output === null || empty($output)) {
            // Fallback: Use basic image processing without Python
            echo json_encode([
                'success' => false,
                'error' => 'ID scanning is currently unavailable on this server. Please contact the administrator to enable this feature.',
                'fallback' => true,
                'message' => 'For now, please fill in your details manually.',
                'debug' => 'Command: ' . $command . ', Output: ' . ($output ?: 'NULL/EMPTY')
            ]);
            exit();
        }
        
        // Extract JSON from output (handle warnings that might be mixed in)
        $json_start = strpos($output, '{');
        $json_end = strrpos($output, '}');
        
        if ($json_start !== false && $json_end !== false) {
            $json_output = substr($output, $json_start, $json_end - $json_start + 1);
            $result = json_decode($json_output, true);
        } else {
            $result = json_decode($output, true);
        }
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // If Python output is not valid JSON, it might be an error message
            if (strpos($output, 'not recognized') !== false || strpos($output, 'command not found') !== false) {
                echo json_encode([
                    'error' => 'Python is not installed or not in PATH. Please install Python and the required dependencies.',
                    'fallback' => true,
                    'message' => 'Text recognition requires Python to be installed. Please contact the administrator.'
                ]);
            } else {
                echo json_encode(['error' => 'Invalid response from text recognition scanner: ' . $output]);
            }
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
