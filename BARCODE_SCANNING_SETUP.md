# DocTR Text Recognition System Setup Guide

## Overview
This system uses DocTR (Document Text Recognition) for extracting text from Philippine National ID images. It automatically fills name and barangay fields for residents from Bago City, Negros Occidental.

## Features
- Text recognition from Philippine ID images
- File upload option for ID images
- Automatic form field population
- Location validation (Bago City, Negros Occidental only)
- High accuracy text extraction

## Installation

### 1. Install Python Dependencies
Install DocTR for text recognition:
```bash
pip install python-doctr[torch]
```

### 2. Required Files
The following files are included in the system:
- `doctr_scanner.py` - Python DocTR text recognition script
- `barcode_handler.php` - PHP handler for image processing
- `assets/js/barcode_scanner.js` - JavaScript interface
- `barcode_scanner_modal.html` - HTML modal template

### 3. Directory Structure
Ensure the following directories exist:
```
uploads/barcode_scans/  (will be created automatically)
```

## Usage

### 1. Include JavaScript and Modal
Add to your HTML pages:
```html
<!-- Include the JavaScript -->
<script src="assets/js/barcode_scanner.js"></script>

<!-- Include the modal HTML -->
<!-- Copy content from barcode_scanner_modal.html -->
```

### 2. Add Scanner Button
Add this button to your forms:
```html
<button type="button" id="scan-barcode-btn">📷 Scan Barcode</button>
```

### 3. Form Field Requirements
Ensure your forms have these field IDs:
- `fullname` or `name` - for resident name
- `barangay` or `address` - for barangay
- `city` (optional) - for city
- `province` (optional) - for province

## Philippine ID Data Format

The system extracts information from Philippine National ID cards in the following format:

### Expected ID Information
```json
{
    "name": "John Doe",
    "barangay": "Poblacion",
    "city": "Bago City",
    "province": "Negros Occidental"
}
```

### Text Format
The system can also parse text-based barcodes with patterns like:
- Name: John Doe
- Barangay: Poblacion
- Address: Poblacion, Bago City, Negros Occidental

## Validation Rules

### Location Validation
- Only accepts residents from Bago City, Negros Occidental
- Validates city and province information
- Rejects non-Bago City residents

### Barangay Validation
Supported barangays in Bago City:
- Abuanan, Alijis, Atipuluan, Bacong, Bagong Silang
- Balingasag, Binubuhan, Busay, Calumangan, Caridad
- Dulao, Ilijan, Lag-asan, Ma-ao, Mailum
- Malingin, Napoles, Pacol, Poblacion, Sagasa
- Taloc, Tigbao, Tinong-an, Tuburan

## API Endpoints

### POST /barcode_handler.php
Processes barcode images and returns resident information.

**Request:**
- `barcode_image` (file) - Image file containing barcode

**Response:**
```json
{
    "success": true,
    "name": "John Doe",
    "barangay": "Poblacion",
    "city": "Bago City",
    "province": "Negros Occidental",
    "is_bago_resident": true
}
```

## Troubleshooting

### Common Issues

1. **Camera Access Denied**
   - Ensure HTTPS is used for camera access
   - Check browser permissions
   - Use file upload as fallback

2. **Python Dependencies Issues**
   - Install Microsoft Visual C++ Build Tools
   - Use conda: `conda install -c conda-forge python-doctr`
   - Try different Python versions

3. **No Barcode Detected**
   - Ensure barcode is clear and well-lit
   - Try different barcode formats
   - Check barcode quality

4. **Location Validation Failed**
   - Verify barcode contains correct city/province
   - Check barangay spelling
   - Ensure data format is correct

### Debug Mode
Enable debug logging by checking browser console and server logs.

## Security Considerations

- File uploads are validated for type and size
- Temporary files are automatically cleaned up
- User authentication is required
- Input validation prevents injection attacks

## Performance Notes

- Camera scanning runs at ~1 FPS to balance performance
- File uploads are limited to 5MB
- Images are automatically cleaned up after processing
- Consider server resources for concurrent scanning

## Browser Compatibility

- Chrome/Edge: Full support
- Firefox: Full support
- Safari: Limited camera support
- Mobile browsers: Good support

## Future Enhancements

- Support for additional barcode formats
- Batch processing capabilities
- Enhanced error handling
- Offline scanning support
