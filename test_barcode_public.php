<?php
/**
 * Public test page for barcode scanning (no login required)
 * This simulates the registration process barcode scanning
 */

// Set JSON header for AJAX requests
if (isset($_POST['test_barcode'])) {
    header('Content-Type: application/json');
    
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
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Barcode Scanner Test - Bago City Veterinary IMS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 5px;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .status {
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .status.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .status.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .image-preview {
            max-width: 300px;
            max-height: 200px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin: 10px 0;
        }
        .result-box {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Public Barcode Scanner Test</h1>
        <p>Test the barcode scanning functionality without requiring login.</p>
        
        <div class="form-group">
            <label for="barcode-image">Upload Image with Barcode:</label>
            <input type="file" id="barcode-image" accept="image/*" class="form-control">
            <small class="form-text text-muted">Upload an image containing a barcode (QR code, etc.)</small>
        </div>
        
        <div class="form-group">
            <button class="btn btn-primary" onclick="scanBarcode()">📷 Scan Barcode</button>
            <button class="btn btn-secondary" onclick="clearForm()">🗑️ Clear</button>
        </div>
        
        <div id="image-preview-container" style="display: none;">
            <label>Image Preview:</label>
            <img id="image-preview" class="image-preview" alt="Image Preview">
        </div>
        
        <div id="scan-status"></div>
        <div id="scan-results"></div>
        
        <div class="result-box">
            <h3>📋 Expected Barcode Format</h3>
            <p>The barcode should contain JSON data like:</p>
            <pre style="background: #f1f1f1; padding: 10px; border-radius: 3px;">
{
    "name": "Juan Dela Cruz",
    "barangay": "Poblacion",
    "city": "Bago City", 
    "province": "Negros Occidental"
}
            </pre>
            <p><strong>Note:</strong> Only residents from Bago City, Negros Occidental will be accepted.</p>
        </div>
        
        <div class="result-box">
            <h3>🧪 Test with Sample Data</h3>
            <button class="btn btn-success" onclick="testWithSampleData()">Test Sample Barcode</button>
            <p>This will simulate a successful barcode scan with sample data.</p>
        </div>
    </div>

    <script>
        // Handle file selection
        document.getElementById('barcode-image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const previewContainer = document.getElementById('image-preview-container');
            const previewImg = document.getElementById('image-preview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                previewContainer.style.display = 'none';
            }
        });
        
        // Scan barcode
        async function scanBarcode() {
            const fileInput = document.getElementById('barcode-image');
            const statusDiv = document.getElementById('scan-status');
            const resultsDiv = document.getElementById('scan-results');
            
            if (!fileInput.files || !fileInput.files[0]) {
                showStatus('Please select an image file first.', 'error');
                return;
            }
            
            showStatus('Scanning barcode...', 'info');
            resultsDiv.innerHTML = '';
            
            try {
                const formData = new FormData();
                formData.append('barcode_image', fileInput.files[0]);
                formData.append('test_barcode', '1');
                
                const response = await fetch('test_barcode_public.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showStatus('✅ Barcode scanned successfully!', 'success');
                    showResults(result);
                } else {
                    showStatus(`❌ Scan failed: ${result.error}`, 'error');
                }
            } catch (error) {
                showStatus(`❌ Error: ${error.message}`, 'error');
            }
        }
        
        // Test with sample data
        function testWithSampleData() {
            const sampleResult = {
                success: true,
                name: 'Maria Santos',
                barangay: 'Dulao',
                city: 'Bago City',
                province: 'Negros Occidental',
                is_bago_resident: true
            };
            
            showStatus('✅ Sample barcode test successful!', 'success');
            showResults(sampleResult);
        }
        
        // Show status
        function showStatus(message, type) {
            const statusDiv = document.getElementById('scan-status');
            statusDiv.innerHTML = `<div class="status ${type}">${message}</div>`;
        }
        
        // Show results
        function showResults(result) {
            const resultsDiv = document.getElementById('scan-results');
            resultsDiv.innerHTML = `
                <div class="result-box">
                    <h3>📊 Scan Results</h3>
                    <p><strong>Name:</strong> ${result.name || 'N/A'}</p>
                    <p><strong>Barangay:</strong> ${result.barangay || 'N/A'}</p>
                    <p><strong>City:</strong> ${result.city || 'N/A'}</p>
                    <p><strong>Province:</strong> ${result.province || 'N/A'}</p>
                    <p><strong>Bago Resident:</strong> ${result.is_bago_resident ? '✅ Yes' : '❌ No'}</p>
                    ${result.error ? `<p><strong>Error:</strong> ${result.error}</p>` : ''}
                </div>
            `;
        }
        
        // Clear form
        function clearForm() {
            document.getElementById('barcode-image').value = '';
            document.getElementById('image-preview-container').style.display = 'none';
            document.getElementById('scan-status').innerHTML = '';
            document.getElementById('scan-results').innerHTML = '';
        }
    </script>
</body>
</html>
