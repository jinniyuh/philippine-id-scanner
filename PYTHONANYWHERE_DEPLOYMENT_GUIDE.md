# 🚀 PythonAnywhere Deployment Guide for Philippine ID Scanner

This guide will help you deploy your Flask API to PythonAnywhere so you can use it on your live server.

## 📋 Prerequisites

1. **PythonAnywhere Account** - Sign up at [pythonanywhere.com](https://pythonanywhere.com) (free tier available)
2. **Your Flask API files** - All the files we created
3. **Basic understanding** of web hosting concepts

## 🚀 Step-by-Step Deployment

### Step 1: Create PythonAnywhere Account

1. Go to [pythonanywhere.com](https://pythonanywhere.com)
2. Sign up for a free account
3. Verify your email address
4. Log into your dashboard

### Step 2: Upload Files to PythonAnywhere

#### Method A: Using the Files Tab
1. Go to the **Files** tab in your PythonAnywhere dashboard
2. Navigate to your home directory (`/home/yourusername/`)
3. Create a new directory called `capstoneidd`
4. Upload these files to `/home/yourusername/capstoneidd/`:
   - `flask_id_scanner_pythonanywhere.py`
   - `wsgi.py`
   - `requirements_pythonanywhere.txt`
   - Any other files from your project

#### Method B: Using Git (Recommended)
1. Push your code to GitHub
2. In PythonAnywhere console, run:
   ```bash
   git clone https://github.com/yourusername/your-repo.git capstoneidd
   ```

### Step 3: Install Dependencies

1. Go to the **Consoles** tab
2. Start a new **Bash console**
3. Run these commands:
   ```bash
   cd capstoneidd
   pip3.10 install --user -r requirements_pythonanywhere.txt
   ```

### Step 4: Configure Web App

1. Go to the **Web** tab in your PythonAnywhere dashboard
2. Click **"Add a new web app"**
3. Choose **"Manual configuration"**
4. Select **Python 3.10**
5. Configure the web app:
   - **Source code**: `/home/yourusername/capstoneidd`
   - **WSGI file**: `/home/yourusername/capstoneidd/wsgi.py`
   - **Working directory**: `/home/yourusername/capstoneidd`

### Step 5: Update WSGI File

1. Open the **Files** tab
2. Navigate to `/home/yourusername/capstoneidd/wsgi.py`
3. Edit the file and replace `yourusername` with your actual PythonAnywhere username:
   ```python
   path = '/home/yourusername/capstoneidd'  # Replace 'yourusername' with your actual username
   ```

### Step 6: Create Upload Directory

In the console, run:
```bash
mkdir -p ~/uploads/id_scans
chmod 755 ~/uploads/id_scans
```

### Step 7: Test Your API

1. Go to your web app URL: `https://yourusername.pythonanywhere.com`
2. Test the health endpoint: `https://yourusername.pythonanywhere.com/api/health`
3. You should see a JSON response with status "healthy"

## 🔧 Configuration Details

### WSGI Configuration (`wsgi.py`)
```python
import sys
import os

# Add your project directory to the Python path
path = '/home/yourusername/capstoneidd'  # Replace with your username
if path not in sys.path:
    sys.path.append(path)

# Import the Flask app
from flask_id_scanner_pythonanywhere import app as application

# Set environment variables
os.environ['FLASK_ENV'] = 'production'
os.environ['FLASK_DEBUG'] = 'False'
```

### Requirements (`requirements_pythonanywhere.txt`)
```
Flask==2.3.3
Flask-CORS==4.0.0
python-doctr[torch]==0.6.0
torch==2.0.1
torchvision==0.15.2
Pillow==10.0.0
numpy==1.24.3
opencv-python==4.8.0.76
Werkzeug==2.3.7
gunicorn==21.2.0
```

## 🌐 Update Your Live Server

Now you need to update your live server to use the PythonAnywhere API:

### Option 1: Update barcode_handler.php

Replace your existing `barcode_handler.php` with this updated version:

```php
<?php
/**
 * Barcode Handler for PythonAnywhere Flask API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Configuration
$PYTHONANYWHERE_API_URL = 'https://yourusername.pythonanywhere.com'; // Replace with your PythonAnywhere URL

function scanIdWithPythonAnywhere($image_path) {
    global $PYTHONANYWHERE_API_URL;
    
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $PYTHONANYWHERE_API_URL . '/api/scan-id',
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

// Main processing logic
try {
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
    
    // Scan the ID using PythonAnywhere API
    $scan_result = scanIdWithPythonAnywhere($file_path);
    
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
    
    // Return success response
    echo json_encode([
        'success' => true,
        'name' => $scan_result['name'],
        'barangay' => $scan_result['barangay'],
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
```

### Option 2: Update login.php

Update your login form to use the new barcode handler:

```html
<!-- In your login.php form -->
<form id="registrationForm" method="POST" enctype="multipart/form-data">
    <!-- Your existing form fields -->
    
    <!-- Update the barcode handler action -->
    <input type="hidden" name="action" value="scan_id">
</form>

<script>
// Update the scan function to use the new handler
function scanId() {
    // Your existing scan logic
    // The form will now submit to the updated barcode_handler.php
}
</script>
```

## 🧪 Testing Your Deployment

### 1. Test PythonAnywhere API
```bash
# Test health endpoint
curl https://yourusername.pythonanywhere.com/api/health

# Test with a sample image
curl -X POST -F "image=@test_id.jpg" https://yourusername.pythonanywhere.com/api/scan-id
```

### 2. Test Your Live Server
1. Upload a Philippine ID image
2. Check if the scanning works
3. Verify the extracted data is correct

## 🔧 Troubleshooting

### Common Issues

1. **"Module not found" errors**
   - Make sure you installed all dependencies
   - Check the Python path in wsgi.py

2. **"Permission denied" errors**
   - Check file permissions on upload directory
   - Run: `chmod 755 ~/uploads/id_scans`

3. **"API not responding" errors**
   - Check if your web app is running
   - Look at the error logs in PythonAnywhere

4. **"File too large" errors**
   - Check the file size limits
   - Optimize your images before uploading

### Debug Mode

To enable debug mode, update your wsgi.py:
```python
os.environ['FLASK_DEBUG'] = 'True'
```

## 📊 Performance Considerations

### PythonAnywhere Free Tier Limitations
- **CPU seconds**: 100 seconds per day
- **File storage**: 512MB
- **Web requests**: Limited

### Optimizations
1. **Image compression**: Compress images before sending
2. **Caching**: Cache results when possible
3. **Error handling**: Implement proper fallbacks

## 🚀 Production Deployment

### For Higher Traffic
1. **Upgrade to paid plan** for more resources
2. **Use CDN** for static files
3. **Implement caching** for better performance
4. **Monitor usage** to avoid hitting limits

### Security Considerations
1. **HTTPS**: Always use HTTPS for API calls
2. **File validation**: Validate all uploaded files
3. **Rate limiting**: Implement rate limiting
4. **Error handling**: Don't expose sensitive information

## 📞 Support

If you encounter issues:
1. Check the PythonAnywhere error logs
2. Verify your API is running
3. Test the endpoints individually
4. Check the file permissions

## 🎉 Success!

Once deployed, your Philippine ID Scanner will be available at:
- **API URL**: `https://yourusername.pythonanywhere.com`
- **Health Check**: `https://yourusername.pythonanywhere.com/api/health`
- **Scan Endpoint**: `https://yourusername.pythonanywhere.com/api/scan-id`

Your live server can now use this API for ID scanning without needing to install Python dependencies locally!
