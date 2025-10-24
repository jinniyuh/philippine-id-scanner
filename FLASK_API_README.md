# Philippine ID Scanner Flask API

This Flask API provides a RESTful interface for scanning Philippine National IDs and extracting name and barangay information using DocTR (Document Text Recognition).

## 🚀 Quick Start

### 1. Install Dependencies

```bash
# Install Python requirements
pip install -r flask_requirements.txt
```

### 2. Start the API

**Windows:**
```bash
start_flask_api.bat
```

**Linux/Mac:**
```bash
./start_flask_api.sh
```

**Manual:**
```bash
python flask_id_scanner.py
```

The API will be available at: `http://localhost:5000`

### 3. Test the API

Open `test_flask_api.html` in your browser to test the API functionality.

## 📡 API Endpoints

### 1. Health Check
```
GET /api/health
```
Returns API status and version information.

**Response:**
```json
{
  "status": "healthy",
  "service": "Philippine ID Scanner API",
  "version": "1.0.0"
}
```

### 2. Scan ID (File Upload)
```
POST /api/scan-id
Content-Type: multipart/form-data
```
Upload an image file for ID scanning.

**Request:**
- `image`: Image file (PNG, JPG, JPEG, GIF)
- Maximum file size: 5MB

**Response:**
```json
{
  "success": true,
  "name": "JANNAH MARIE CARMONA",
  "barangay": "POBLACION",
  "city": "Bago City",
  "province": "Negros Occidental",
  "is_bago_resident": true,
  "message": "Text extracted successfully from Philippine ID"
}
```

### 3. Scan ID (Base64)
```
POST /api/scan-id-base64
Content-Type: application/json
```
Send base64 encoded image for ID scanning.

**Request:**
```json
{
  "image": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQ..."
}
```

### 4. Validate Name and Barangay
```
POST /api/validate-name
Content-Type: application/json
```
Validate entered name and barangay against scanned data.

**Request:**
```json
{
  "entered_name": "JANNAH MARIE CARMONA",
  "entered_barangay": "POBLACION",
  "scanned_name": "JANNAH MARIE CARMONA",
  "scanned_barangay": "POBLACION"
}
```

**Response:**
```json
{
  "success": true,
  "name_match": true,
  "barangay_match": true,
  "significant_difference": false,
  "length_difference": 0
}
```

## 🔧 Integration with PHP

### Using the PHP Client

```php
<?php
require_once 'flask_api_client.php';

// Initialize client
$client = new FlaskAPIClient();

// Scan ID from uploaded file
$result = $client->scanIdFromFile('path/to/id_image.jpg');

if ($result['success']) {
    echo "Name: " . $result['name'];
    echo "Barangay: " . $result['barangay'];
} else {
    echo "Error: " . $result['error'];
}

// Validate name and barangay
$validation = $client->validateName(
    'JANNAH MARIE CARMONA',
    'POBLACION',
    'JANNAH MARIE CARMONA',
    'POBLACION'
);

if ($validation['success']) {
    echo "Validation passed!";
} else {
    echo "Validation failed: " . $validation['error'];
}
?>
```

### Using cURL

```bash
# Health check
curl http://localhost:5000/api/health

# Scan ID
curl -X POST -F "image=@id_image.jpg" http://localhost:5000/api/scan-id

# Validate name
curl -X POST -H "Content-Type: application/json" \
  -d '{"entered_name":"JANNAH MARIE CARMONA","entered_barangay":"POBLACION","scanned_name":"JANNAH MARIE CARMONA","scanned_barangay":"POBLACION"}' \
  http://localhost:5000/api/validate-name
```

## 🛠️ Configuration

### Environment Variables
- `FLASK_ENV`: Set to `development` for debug mode
- `FLASK_DEBUG`: Set to `True` for debug output
- `UPLOAD_FOLDER`: Directory for temporary file uploads (default: `uploads/id_scans`)

### File Upload Settings
- **Allowed file types:** PNG, JPG, JPEG, GIF
- **Maximum file size:** 5MB
- **Upload directory:** `uploads/id_scans/`

## 🔍 Features

### Text Recognition
- Uses DocTR for high-accuracy text recognition
- Optimized for Philippine National ID format
- Handles multi-word given names correctly
- Removes middle name contamination from given names

### Validation
- Strict name and barangay matching
- Length difference detection
- Character-by-character comparison
- Support for Bago City, Negros Occidental residents only

### Error Handling
- Comprehensive error messages
- Fallback mechanisms
- Debug information for troubleshooting
- Graceful degradation when DocTR is unavailable

## 🐛 Troubleshooting

### Common Issues

1. **"DocTR is not installed"**
   ```bash
   pip install python-doctr[torch]
   ```

2. **"Flask API is not running"**
   - Check if the Flask server is running on port 5000
   - Verify no firewall blocking the connection
   - Check the console for error messages

3. **"File too large"**
   - Reduce image file size to under 5MB
   - Compress the image before uploading

4. **"Invalid file type"**
   - Use only PNG, JPG, JPEG, or GIF files
   - Check file extension and MIME type

### Debug Mode

Enable debug mode by setting environment variables:
```bash
export FLASK_ENV=development
export FLASK_DEBUG=True
python flask_id_scanner.py
```

### Logs

Check the console output for detailed error messages and debug information.

## 📁 File Structure

```
├── flask_id_scanner.py          # Main Flask API
├── flask_api_client.php         # PHP client for API
├── barcode_handler_flask.php    # Updated barcode handler
├── test_flask_api.html          # Test interface
├── start_flask_api.bat          # Windows startup script
├── start_flask_api.sh           # Linux startup script
├── flask_requirements.txt      # Python dependencies
└── FLASK_API_README.md          # This documentation
```

## 🔄 Migration from Direct Python

To migrate from the direct Python execution to Flask API:

1. **Replace the barcode handler:**
   - Change `barcode_handler.php` to use `barcode_handler_flask.php`
   - Update the form action in your HTML

2. **Update the client-side JavaScript:**
   - No changes needed - the API maintains the same response format

3. **Start the Flask API:**
   - Run the startup script for your platform
   - Ensure the API is running before testing

## 🚀 Deployment

### Production Deployment

1. **Use a production WSGI server:**
   ```bash
   pip install gunicorn
   gunicorn -w 4 -b 0.0.0.0:5000 flask_id_scanner:app
   ```

2. **Set up as a system service:**
   - Create a systemd service file
   - Configure auto-restart on failure
   - Set up log rotation

3. **Configure reverse proxy:**
   - Use Nginx or Apache as reverse proxy
   - Handle SSL termination
   - Configure load balancing if needed

### Docker Deployment

```dockerfile
FROM python:3.9-slim

WORKDIR /app
COPY requirements.txt .
RUN pip install -r requirements.txt

COPY . .
EXPOSE 5000

CMD ["python", "flask_id_scanner.py"]
```

## 📞 Support

For issues and questions:
1. Check the console output for error messages
2. Verify the Flask API is running
3. Test with the provided test interface
4. Check the troubleshooting section above