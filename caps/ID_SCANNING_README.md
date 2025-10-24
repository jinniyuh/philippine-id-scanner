# Philippine National ID Scanning Feature

## Overview
This feature implements a comprehensive Philippine National ID card scanning system for client registration validation. It uses camera access and OCR (Optical Character Recognition) technology to extract and validate client information from their National ID cards.

## Features

### 🔍 **ID Card Scanning**
- **Camera Integration**: Access device camera for real-time ID scanning
- **Photo Capture**: Capture high-quality images of ID cards
- **OCR Processing**: Extract text from captured images using Tesseract.js
- **Data Extraction**: Automatically identify and extract key information

### 📋 **Extracted Information**
- **Full Name**: Client's complete name from the ID
- **ID Number**: Philippine National ID number (XXXX-XXXX-XXXX-XXXX format)
- **Birth Date**: Date of birth in various formats
- **Address**: Barangay information (Bago City specific)
- **Contact Number**: Phone number in Philippine format

### ✅ **Validation System**
- **Real-time Comparison**: Compare scanned data with form inputs
- **Name Matching**: Fuzzy matching for names with similarity algorithm
- **Contact Validation**: Phone number format and digit comparison
- **Address Verification**: Barangay name matching
- **Verification Status**: Clear indication of validation success/failure

## Technical Implementation

### **Frontend Technologies**
- **HTML5**: Camera API for video capture
- **JavaScript**: OCR processing and data validation
- **Tesseract.js**: Open-source OCR engine
- **Bootstrap 5**: Responsive UI components
- **FontAwesome**: Icons and visual indicators

### **Backend Integration**
- **PHP**: Server-side validation and data storage
- **MySQL**: Database storage for verification data
- **JSON**: Structured data storage for scanned information

### **Database Schema**
```sql
-- Additional columns for ID verification
ALTER TABLE `clients` 
ADD COLUMN `id_verified` TINYINT(1) DEFAULT 0,
ADD COLUMN `scanned_id_data` TEXT NULL,
ADD COLUMN `id_verification_date` TIMESTAMP NULL;
```

## Usage Instructions

### **For Clients (Registration)**
1. **Fill Registration Form**: Complete all required fields
2. **Start Camera**: Click "Start Camera" button
3. **Position ID**: Hold Philippine National ID in front of camera
4. **Capture Photo**: Click "Capture ID" when ID is clearly visible
5. **Scan Text**: Click "Scan Text" to extract information
6. **Verify Data**: Check that scanned data matches form inputs
7. **Complete Registration**: Submit form only after successful verification

### **For Administrators**
1. **Review Verification**: Check client verification status in admin panel
2. **View Scanned Data**: Access stored ID verification information
3. **Monitor Compliance**: Track ID verification rates and compliance

## Setup Instructions

### **1. Database Setup**
```bash
# Run the SQL migration script
mysql -u username -p database_name < add_id_verification_columns.sql
```

### **2. File Installation**
- Ensure `login.php` is updated with ID scanning functionality
- Place `test_id_scanner.html` for testing purposes
- Verify Tesseract.js CDN is accessible

### **3. Browser Requirements**
- **HTTPS Required**: Camera access requires secure connection
- **Modern Browser**: Chrome, Firefox, Safari, Edge (latest versions)
- **Camera Permission**: Users must grant camera access
- **JavaScript Enabled**: Required for OCR processing

## Testing

### **Test Environment**
- Access `test_id_scanner.html` for standalone testing
- Use sample Philippine National ID cards
- Test with different lighting conditions
- Verify OCR accuracy with various ID formats

### **Test Scenarios**
1. **Valid ID**: Clear, well-lit ID card
2. **Blurry Image**: Test with slightly blurred capture
3. **Poor Lighting**: Test with low light conditions
4. **Different Angles**: Test with ID at various angles
5. **No ID**: Test with no ID card present

## Security Considerations

### **Data Privacy**
- **Local Processing**: OCR processing happens in browser
- **No Image Storage**: Captured images are not permanently stored
- **Encrypted Transmission**: Data transmitted securely to server
- **Access Control**: Only authorized users can access verification data

### **Validation Security**
- **Server-side Validation**: All data validated on server
- **Database Integrity**: Proper data sanitization and storage
- **Audit Trail**: Verification timestamps and user tracking

## Troubleshooting

### **Common Issues**

#### **Camera Not Working**
- **Check Permissions**: Ensure camera access is granted
- **HTTPS Required**: Camera only works on secure connections
- **Browser Compatibility**: Use supported browser version
- **Device Support**: Ensure device has working camera

#### **OCR Not Accurate**
- **Image Quality**: Ensure ID is clearly visible and well-lit
- **ID Position**: Keep ID flat and centered in frame
- **Text Clarity**: Ensure text on ID is not blurred or damaged
- **Retry Process**: Try capturing multiple times for better results

#### **Validation Fails**
- **Data Mismatch**: Check that form data matches ID information
- **Name Variations**: Account for nicknames vs. full names
- **Phone Format**: Ensure phone numbers are in correct format
- **Address Spelling**: Verify barangay name spelling

### **Performance Optimization**
- **Image Compression**: Optimize captured images for faster processing
- **OCR Settings**: Adjust Tesseract.js settings for better accuracy
- **Caching**: Implement caching for repeated scans
- **Error Handling**: Provide clear error messages and recovery options

## Future Enhancements

### **Planned Features**
- **Multiple ID Types**: Support for other government IDs
- **Face Recognition**: Biometric verification integration
- **Blockchain Verification**: Immutable verification records
- **Mobile App**: Dedicated mobile application
- **API Integration**: Third-party verification services

### **Improvements**
- **OCR Accuracy**: Enhanced text recognition algorithms
- **Real-time Processing**: Faster OCR processing
- **Offline Support**: Local processing without internet
- **Multi-language**: Support for multiple languages
- **Batch Processing**: Multiple ID verification at once

## Support

### **Technical Support**
- **Documentation**: Refer to this README for setup and usage
- **Testing**: Use test environment for troubleshooting
- **Logs**: Check browser console for error messages
- **Updates**: Keep Tesseract.js and dependencies updated

### **Contact Information**
- **Development Team**: Bago City Veterinary Office IT Department
- **Issue Reporting**: Report bugs and feature requests
- **Training**: Request training sessions for staff
- **Maintenance**: Schedule regular system maintenance

---

**Version**: 1.0  
**Last Updated**: January 2025  
**Compatibility**: PHP 7.4+, MySQL 5.7+, Modern Browsers
