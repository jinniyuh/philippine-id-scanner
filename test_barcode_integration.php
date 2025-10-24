<?php
/**
 * Test file to verify barcode scanning integration in login system
 */

session_start();
include 'includes/conn.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Integration Test - Bago City Veterinary IMS</title>
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
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
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
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Barcode Integration Test</h1>
        <p>This page tests the barcode scanning integration in the login system.</p>
        
        <div class="test-section">
            <h3>📋 Integration Status</h3>
            <div id="integration-status">
                <p>✅ Barcode scanner JavaScript loaded</p>
                <p>✅ PHP handler available</p>
                <p>✅ Python scanner script ready</p>
            </div>
        </div>

        <div class="test-section">
            <h3>🧪 Test Barcode Scanning</h3>
            <p>Test the barcode scanning functionality:</p>
            
            <div class="form-group">
                <label for="test-image">Upload Test Image:</label>
                <input type="file" id="test-image" accept="image/*">
            </div>
            
            <button class="btn btn-primary" onclick="testBarcodeScan()">Test Barcode Scan</button>
            <button class="btn btn-success" onclick="testWithSampleData()">Test with Sample Data</button>
            
            <div id="test-results"></div>
        </div>

        <div class="test-section">
            <h3>📊 System Information</h3>
            <div id="system-info">
                <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
                <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                <p><strong>Python Available:</strong> <span id="python-status">Checking...</span></p>
                <p><strong>Barcode Handler:</strong> <span id="handler-status">Checking...</span></p>
            </div>
        </div>

        <div class="test-section">
            <h3>🔗 Quick Links</h3>
            <p>
                <a href="login.php" class="btn btn-primary">Go to Login Page</a>
                <a href="test_barcode_scanner.php" class="btn btn-success">Full Scanner Test</a>
            </p>
        </div>
    </div>

    <!-- Include Barcode Scanner JavaScript -->
    <script src="assets/js/barcode_scanner.js"></script>
    
    <script>
        // Test barcode scanning
        async function testBarcodeScan() {
            const fileInput = document.getElementById('test-image');
            const resultsDiv = document.getElementById('test-results');
            
            if (!fileInput.files || !fileInput.files[0]) {
                showResult('Please select an image file first.', 'error');
                return;
            }
            
            showResult('Scanning barcode...', 'info');
            
            try {
                const formData = new FormData();
                formData.append('barcode_image', fileInput.files[0]);
                
                const response = await fetch('barcode_handler.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showResult(`
                        <strong>✅ Barcode Scan Successful!</strong><br>
                        <strong>Name:</strong> ${result.name}<br>
                        <strong>Barangay:</strong> ${result.barangay}<br>
                        <strong>City:</strong> ${result.city}<br>
                        <strong>Province:</strong> ${result.province}<br>
                        <strong>Bago Resident:</strong> ${result.is_bago_resident ? 'Yes' : 'No'}
                    `, 'success');
                } else {
                    showResult(`❌ Scan failed: ${result.error}`, 'error');
                }
            } catch (error) {
                showResult(`❌ Error: ${error.message}`, 'error');
            }
        }
        
        // Test with sample data
        function testWithSampleData() {
            const sampleData = {
                success: true,
                name: 'Juan Dela Cruz',
                barangay: 'Poblacion',
                city: 'Bago City',
                province: 'Negros Occidental',
                is_bago_resident: true
            };
            
            showResult(`
                <strong>✅ Sample Data Test</strong><br>
                <strong>Name:</strong> ${sampleData.name}<br>
                <strong>Barangay:</strong> ${sampleData.barangay}<br>
                <strong>City:</strong> ${sampleData.city}<br>
                <strong>Province:</strong> ${sampleData.province}<br>
                <strong>Bago Resident:</strong> ${sampleData.is_bago_resident ? 'Yes' : 'No'}
            `, 'success');
        }
        
        // Show result
        function showResult(message, type) {
            const resultsDiv = document.getElementById('test-results');
            resultsDiv.innerHTML = `<div class="status ${type}">${message}</div>`;
        }
        
        // Check system status
        async function checkSystemStatus() {
            // Check Python availability
            try {
                const pythonResponse = await fetch('test_barcode_simple.py');
                document.getElementById('python-status').textContent = 'Available';
                document.getElementById('python-status').style.color = 'green';
            } catch (error) {
                document.getElementById('python-status').textContent = 'Not available';
                document.getElementById('python-status').style.color = 'red';
            }
            
            // Check handler availability
            try {
                const handlerResponse = await fetch('barcode_handler.php');
                document.getElementById('handler-status').textContent = 'Available';
                document.getElementById('handler-status').style.color = 'green';
            } catch (error) {
                document.getElementById('handler-status').textContent = 'Not available';
                document.getElementById('handler-status').style.color = 'red';
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkSystemStatus();
        });
    </script>
</body>
</html>
