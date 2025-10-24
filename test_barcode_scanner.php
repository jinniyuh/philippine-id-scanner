<?php
/**
 * Test file for barcode scanning functionality
 * This file demonstrates how to integrate the barcode scanner into existing forms
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
    <title>Barcode Scanner Test - Bago City Veterinary IMS</title>
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
        .scanner-info {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Barcode Scanner Test</h1>
        <p>This page tests the barcode scanning functionality for the Bago City Veterinary IMS.</p>
        
        <div class="scanner-info">
            <h3>📷 Barcode Scanner Features</h3>
            <ul>
                <li>Real-time camera scanning</li>
                <li>File upload option</li>
                <li>Automatic form field population</li>
                <li>Location validation (Bago City, Negros Occidental only)</li>
                <li>Support for various barcode formats</li>
            </ul>
        </div>

        <form id="test-form">
            <h2>Resident Information Form</h2>
            
            <div class="form-group">
                <label for="fullname">Full Name:</label>
                <input type="text" id="fullname" name="fullname" placeholder="Will be auto-filled from barcode">
                <button type="button" id="scan-barcode-btn" class="btn btn-success">📷 Scan Barcode</button>
            </div>

            <div class="form-group">
                <label for="barangay">Barangay:</label>
                <input type="text" id="barangay" name="barangay" placeholder="Will be auto-filled from barcode">
            </div>

            <div class="form-group">
                <label for="city">City:</label>
                <input type="text" id="city" name="city" value="Bago City" readonly>
            </div>

            <div class="form-group">
                <label for="province">Province:</label>
                <input type="text" id="province" name="province" value="Negros Occidental" readonly>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number:</label>
                <input type="text" id="phone" name="phone" placeholder="Enter phone number">
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" placeholder="Enter email address">
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <button type="submit" class="btn btn-primary">Submit Form</button>
                <button type="button" onclick="clearForm()" class="btn btn-secondary">Clear Form</button>
            </div>
        </form>

        <div id="status-messages"></div>
    </div>

    <!-- Include Barcode Scanner Modal -->
    <div id="barcode-scanner-modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8);">
        <div style="position: relative; background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 600px; border-radius: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0; color: #333;">Barcode Scanner</h2>
                <button id="close-scanner-btn" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #aaa;">&times;</button>
            </div>
            
            <div style="text-align: center; margin-bottom: 20px;">
                <p style="color: #666; margin-bottom: 15px;">Position the barcode within the camera view or upload an image file</p>
                
                <!-- Camera View -->
                <div id="camera-container" style="position: relative; margin-bottom: 20px;">
                    <video id="barcode-video" style="width: 100%; max-width: 500px; height: auto; border: 2px solid #ddd; border-radius: 5px;" autoplay muted playsinline></video>
                    <canvas id="barcode-canvas" style="display: none;"></canvas>
                </div>
                
                <!-- File Upload Option -->
                <div style="margin-bottom: 20px;">
                    <label for="barcode-file-input" style="display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; border-radius: 5px; cursor: pointer; text-decoration: none;">
                        📁 Upload Image File
                    </label>
                    <input type="file" id="barcode-file-input" accept="image/*" style="display: none;">
                </div>
                
                <!-- Instructions -->
                <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; text-align: left;">
                    <h4 style="margin-top: 0; color: #333;">Instructions:</h4>
                    <ul style="margin: 0; padding-left: 20px; color: #666;">
                        <li>Ensure the barcode is clearly visible and well-lit</li>
                        <li>Hold the camera steady and wait for automatic detection</li>
                        <li>Or upload a clear image file containing the barcode</li>
                        <li>Only residents from Bago City, Negros Occidental will be accepted</li>
                    </ul>
                </div>
            </div>
            
            <div style="text-align: center;">
                <button id="close-scanner-btn" style="padding: 10px 20px; background-color: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Include JavaScript -->
    <script src="assets/js/barcode_scanner.js"></script>
    
    <script>
        // Form submission handler
        document.getElementById('test-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            showStatus('Form submitted successfully!', 'success');
            console.log('Form data:', data);
        });

        // Clear form function
        function clearForm() {
            document.getElementById('test-form').reset();
            document.getElementById('city').value = 'Bago City';
            document.getElementById('province').value = 'Negros Occidental';
            showStatus('Form cleared', 'success');
        }

        // Show status messages
        function showStatus(message, type) {
            const statusDiv = document.getElementById('status-messages');
            const statusEl = document.createElement('div');
            statusEl.className = `status ${type}`;
            statusEl.textContent = message;
            statusDiv.appendChild(statusEl);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                statusEl.remove();
            }, 5000);
        }

        // Override the barcode scanner success handler for testing
        if (window.barcodeScanner) {
            const originalHandleScanSuccess = window.barcodeScanner.handleScanSuccess;
            window.barcodeScanner.handleScanSuccess = function(result) {
                originalHandleScanSuccess.call(this, result);
                showStatus(`Barcode scanned: ${result.name} from ${result.barangay}`, 'success');
            };
        }
    </script>
</body>
</html>
