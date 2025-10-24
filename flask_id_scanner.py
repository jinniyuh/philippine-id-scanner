#!/usr/bin/env python3
"""
Flask API for Philippine ID Scanning
Converts the existing ID scanning system into a REST API
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
import os
import sys
import json
import re
import warnings
from typing import Dict, List, Tuple
import tempfile
import base64
from werkzeug.utils import secure_filename

# Suppress PyTorch warnings
warnings.filterwarnings("ignore", category=FutureWarning)

app = Flask(__name__)
CORS(app)  # Enable CORS for all routes

# Configuration
UPLOAD_FOLDER = 'uploads/id_scans'
ALLOWED_EXTENSIONS = {'png', 'jpg', 'jpeg', 'gif'}
MAX_FILE_SIZE = 5 * 1024 * 1024  # 5MB

# Ensure upload directory exists
os.makedirs(UPLOAD_FOLDER, exist_ok=True)

app.config['UPLOAD_FOLDER'] = UPLOAD_FOLDER
app.config['MAX_CONTENT_LENGTH'] = MAX_FILE_SIZE

def allowed_file(filename):
    """Check if file extension is allowed"""
    return '.' in filename and \
           filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

def normalize_text(text: str) -> str:
    """Normalize text for comparison"""
    if not text:
        return ""
    text = text.upper()
    text = re.sub(r'BRGY\.?', 'BARANGAY', text)
    text = re.sub(r'[^A-Z0-9\s]', ' ', text)
    text = re.sub(r'\s+', ' ', text)
    return text.strip()

def clean_given_name(given_name: str, full_text: str = "") -> str:
    """
    Clean up given name to remove middle name contamination using context from full text
    """
    if not given_name:
        return given_name
    
    # If we have the full text, try to extract the actual middle name from it
    if full_text:
        # Look for middle name patterns in the full text
        middle_name_patterns = [
            r'GITNANG\s+APELYIDO/MIDDLE\s+NAME\s+([A-Za-z\s,.-]+?)(?:\s|$)',
            r'Gitnang Apelyido/Middle Name\s+([A-Za-z\s,.-]+?)(?:\s|$)',
            r'GITNANG\s+PANGALAN/MIDDLENAME\s+([A-Za-z\s,.-]+?)(?:\s|$)',
            r'MIDDLE\s+NAME:\s*([A-Za-z\s,.-]+?)(?:\s|$)',
        ]
        
        extracted_middle_name = ""
        for pattern in middle_name_patterns:
            matches = re.findall(pattern, full_text, re.IGNORECASE | re.MULTILINE)
            for match in matches:
                if match and match.strip():
                    extracted_middle_name = match.strip().upper()
                    break
            if extracted_middle_name:
                break
        
        # If we found the middle name in the text, remove it from given name
        if extracted_middle_name:
            words = given_name.strip().split()
            cleaned_words = []
            
            for word in words:
                if word.upper() == extracted_middle_name:
                    # Stop at the middle name
                    break
                cleaned_words.append(word)
            
            return ' '.join(cleaned_words).strip()
    
    # Fallback: Use heuristics to detect likely middle names
    words = given_name.strip().split()
    if len(words) <= 2:
        # If only 1-2 words, likely no middle name contamination
        return given_name
    
    # If more than 2 words, the last word might be a middle name
    # Keep only the first 2 words as given names
    return ' '.join(words[:2]).strip()

def scan_philippine_id(image_path: str) -> Dict:
    """
    Scan Philippine ID and extract information using DocTR
    """
    try:
        # Import DocTR
        from doctr.io import DocumentFile
        from doctr.models import ocr_predictor
        
        # Load image with optimization
        doc = DocumentFile.from_images(image_path)
        
        # Initialize DocTR model with faster settings
        model = ocr_predictor(pretrained=True, assume_straight_pages=True)
        
        # Perform OCR with optimization
        result = model(doc)
        
        # Extract all text with confidence scores
        extracted_texts = []
        for page in result.pages:
            for block in page.blocks:
                for line in block.lines:
                    for word in line.words:
                        extracted_texts.append({
                            'text': word.value,
                            'confidence': word.confidence
                        })
        
        # Combine all text
        all_text = " ".join([item['text'] for item in extracted_texts])
        
        # Extract name from Philippine ID patterns
        name = extract_name_from_text(all_text)
        
        # Extract barangay from Philippine ID patterns  
        barangay = extract_barangay_from_text(all_text)
        
        # Check if resident is from Bago City, Negros Occidental
        is_bago_resident = check_bago_resident(all_text)
        
        # Validate extracted data
        if name and barangay and is_bago_resident:
            return {
                "success": True,
                "name": name,
                "barangay": barangay,
                "city": "Bago City",
                "province": "Negros Occidental",
                "is_bago_resident": True,
                "message": "Text extracted successfully from Philippine ID"
            }
        else:
            # Debug information
            debug_info = {
                "name_found": bool(name),
                "barangay_found": bool(barangay),
                "is_bago_resident": is_bago_resident,
                "extracted_name": name if name else "Not found",
                "extracted_barangay": barangay if barangay else "Not found"
            }
            
            return {
                "success": False,
                "error": "Could not extract valid information from Philippine ID. Please ensure the image is clear and contains a valid Philippine National ID from Bago City, Negros Occidental.",
                "extracted_text": all_text[:200] + "..." if len(all_text) > 200 else all_text,
                "debug": debug_info
            }
            
    except ImportError:
        return {
            "success": False,
            "error": "DocTR is not installed. Please install python-doctr[torch] to use text recognition."
        }
    except Exception as e:
        return {
            "success": False,
            "error": f"Error processing image: {str(e)}"
        }

def extract_name_from_text(text: str) -> str:
    """
    Extract name from Philippine ID text using various patterns
    """
    # First try to extract given name and last name separately, then combine
    given_name = ""
    last_name = ""
    
    # Extract given name - improved patterns to capture multi-word names but stop at middle name
    given_patterns = [
        # Pattern to capture until Gitnang Apelyido/Middle Name field (most specific)
        r'MGA\s+PANGALAN\s*/\s*GIVEN\s+NAMES:\s*([A-Za-z\s,.-]+?)(?=\s*GITNANG\s+APELYIDO/MIDDLE\s+NAME)',
        r'Mga Pangalan/Given Names\s+([A-Za-z\s,.-]+?)(?=\s*Gitnang Apelyido/Middle Name)',
        r'MGAPANGALAN/GIVENNAMES\s+([A-Za-z\s,.-]+?)(?=\s*GITNANG\s+APELYIDO/MIDDLENAME)',
        r'MGA\s+PANGALAN/GIVEN\s+NAME\(S\):\s*([A-Za-z\s,.-]+?)(?=\s*GITNANG\s+APELYIDO/MIDDLE\s+NAME)',
        r'GIVEN\s+NAME\(S\):\s*([A-Za-z\s,.-]+?)(?=\s*GITNANG\s+APELYIDO/MIDDLE\s+NAME)',
        r'FIRST\s+NAME:\s*([A-Za-z\s,.-]+?)(?=\s*GITNANG\s+APELYIDO/MIDDLE\s+NAME)',
        
        # Pattern to capture until any next field (Gitnang, Apelyido, etc.)
        r'MGA\s+PANGALAN\s*/\s*GIVEN\s+NAMES:\s*([A-Za-z\s,.-]+?)(?=\s*(?:GITNANG|APELYIDO|APELYDO|LAST\s+NAME|$))',
        r'Mga Pangalan/Given Names\s+([A-Za-z\s,.-]+?)(?=\s*(?:Gitnang|Apelyido|Last\s+Name|$))',
        r'MGAPANGALAN/GIVENNAMES\s+([A-Za-z\s,.-]+?)(?=\s*(?:GITNANG|APELYIDO|APELYDO|LASTNAME|$))',
        r'MGA\s+PANGALAN/GIVEN\s+NAME\(S\):\s*([A-Za-z\s,.-]+?)(?=\s*(?:GITNANG|APELYIDO|APELYDO|LAST\s+NAME|$))',
        r'GIVEN\s+NAME\(S\):\s*([A-Za-z\s,.-]+?)(?=\s*(?:GITNANG|APELYIDO|APELYDO|LAST\s+NAME|$))',
        r'FIRST\s+NAME:\s*([A-Za-z\s,.-]+?)(?=\s*(?:GITNANG|APELYIDO|APELYDO|LAST\s+NAME|$))',
        
        # Fallback patterns for cases where next field might not be present
        r'MGA\s+PANGALAN\s*/\s*GIVEN\s+NAMES:\s*([A-Za-z\s,.-]+?)(?:\s|$)',
        r'Mga Pangalan/Given Names\s+([A-Za-z\s,.-]+?)(?:\s|$)',
        r'MGAPANGALAN/GIVENNAMES\s+([A-Za-z\s,.-]+?)(?:\s|$)',
        r'MGA\s+PANGALAN/GIVEN\s+NAME\(S\):\s*([A-Za-z\s,.-]+?)(?:\s|$)',
        r'GIVEN\s+NAME\(S\):\s*([A-Za-z\s,.-]+?)(?:\s|$)',
        r'FIRST\s+NAME:\s*([A-Za-z\s,.-]+?)(?:\s|$)',
    ]
    
    for pattern in given_patterns:
        matches = re.findall(pattern, text, re.IGNORECASE | re.MULTILINE)
        for match in matches:
            if match and match.strip():
                given_name = match.strip()
                break
        if given_name:
            break
    
    # Extract last name
    last_patterns = [
        r'APELYIDO\s*/\s*LAST\s+NAME:\s*([A-Za-z\s,.-]+?)(?:\s|$)',
        r'Apelyido/Last Name\s+([A-Za-z\s,.-]+?)(?:\s|$)',
        r'APELYDO/LASTNAME\s+([A-Za-z\s,.-]+?)(?:\s|$)',
        r'APELYDO/LASTNAVE\s+([A-Za-z\s,.-]+?)(?:\s|$)',
        r'APELYIDO/LASINAME:\s*([A-Za-z\s,.-]+?)(?:\s|$)',
        r'LAST\s+NAME:\s*([A-Za-z\s,.-]+?)(?:\s|$)',
    ]
    
    for pattern in last_patterns:
        matches = re.findall(pattern, text, re.IGNORECASE | re.MULTILINE)
        for match in matches:
            if match and match.strip():
                last_name = match.strip()
                break
        if last_name:
            break
    
    # Clean up given name to remove any middle name contamination
    if given_name:
        given_name = clean_given_name(given_name, text)
    
    # Combine given name and last name
    if given_name and last_name:
        name = f"{given_name} {last_name}".strip()
        # Validate name
        if (len(name) >= 3 and 
            any(c.isalpha() for c in name) and 
            not 'REPUBLIKA' in name.upper() and
            not 'PHILIPPINES' in name.upper() and
            not 'PAMBANSANG' in name.upper() and
            not 'APELYIDO' in name.upper() and
            not 'LAST NAME' in name.upper() and
            not 'MGA PANGALAN' in name.upper() and
            not 'GIVEN NAMES' in name.upper() and
            not 'GITNANG' in name.upper() and
            not 'MIDDLE NAME' in name.upper()):
            return name
    
    return ""

def extract_barangay_from_text(text: str) -> str:
    """
    Extract barangay from Philippine ID text
    """
    # Valid barangays in Bago City, Negros Occidental
    valid_barangays = [
        'Abuanan', 'Alijis', 'Atipuluan', 'Bacong', 'Bacong-Montilla',
        'Balingasag', 'Binubuhan', 'Busay', 'Calumangan', 'Caridad',
        'Dulao', 'Ilijan', 'Lag-asan', 'Ma-ao', 'Mailum',
        'Malingin', 'Napoles', 'Pacol', 'Poblacion', 'Sagasa',
        'Taloc', 'Tigbao', 'Tinong-an', 'Tuburan'
    ]
    
    # Barangay patterns
    barangay_patterns = [
        r'TIRAHAN\s*/\s*ADDRESS:\s*[^,]+,\s*([A-Za-z\s-]+),\s*CITY\s*OF\s*BAGO',
        r'TIRAHAN/ADDRESS\s+[^,]+,\s*([A-Za-z\s-]+),\s*CITY\s*OF\s*BAGO',
        r'Address[:\s]+[^,]+,\s*([A-Za-z\s-]+),\s*CITY\s*OF\s*BAGO',
        r'Barangay[:\s]+([A-Za-z\s-]+?)(?:\s|$)',
        r'Brgy[.\s]+([A-Za-z\s-]+?)(?:\s|$)',
        r'([A-Za-z\s-]+),\s*Bago\s*City',
        r'([A-Za-z\s-]+),\s*CITY\s*OF\s*BAGO',
        # Simple pattern for DULAO
        r'DULAO,\s*CITY\s*OF\s*BAGO',
        r'PUROK\s+STA\.\s+RITA,\s*DULAO',
        # Pattern for LAG-ASAN
        r'LAG-ASAN,\s*CITY\s*OF\s*BAGO',
        r'MARINA\s+BAY\s+SUBD\.\s*,\s*LAG-ASAN',
        r'BLK\s+\d+\s+LOT\s+\d+\s+MARINA\s+BAY\s+SUBD\.\s*,\s*LAG-ASAN',
    ]
    
    for pattern in barangay_patterns:
        matches = re.findall(pattern, text, re.IGNORECASE)
        for match in matches:
            barangay = match.strip()
            # Check if it's a valid barangay - prioritize exact matches and longer names
            best_match = None
            best_match_length = 0
            
            for valid_barangay in valid_barangays:
                if valid_barangay.lower() == barangay.lower():
                    return valid_barangay  # Exact match - return immediately
                elif valid_barangay.lower() in barangay.lower() and len(valid_barangay) > best_match_length:
                    best_match = valid_barangay
                    best_match_length = len(valid_barangay)
            
            if best_match:
                return best_match
    
    return ""

def check_bago_resident(text: str) -> bool:
    """
    Check if the resident is from Bago City, Negros Occidental
    """
    # Check for Bago City and Negros Occidental in the text
    bago_indicators = [
        'Bago City', 'Bago', 'CITY OF BAGO',
        'Negros Occidental', 'Negros Occ.'
    ]
    
    text_upper = text.upper()
    bago_found = any(indicator.upper() in text_upper for indicator in bago_indicators)
    
    return bago_found

@app.route('/api/scan-id', methods=['POST'])
def scan_id():
    """
    API endpoint to scan Philippine ID
    """
    try:
        # Check if file is present
        if 'image' not in request.files:
            return jsonify({
                'success': False,
                'error': 'No image file provided'
            }), 400
        
        file = request.files['image']
        
        if file.filename == '':
            return jsonify({
                'success': False,
                'error': 'No file selected'
            }), 400
        
        if not allowed_file(file.filename):
            return jsonify({
                'success': False,
                'error': 'Invalid file type. Only PNG, JPG, JPEG, and GIF are allowed.'
            }), 400
        
        # Check file size
        file.seek(0, 2)  # Seek to end
        file_size = file.tell()
        file.seek(0)  # Reset to beginning
        
        if file_size > MAX_FILE_SIZE:
            return jsonify({
                'success': False,
                'error': f'File too large. Maximum size is {MAX_FILE_SIZE // (1024*1024)}MB'
            }), 400
        
        # Save file temporarily
        filename = secure_filename(file.filename)
        file_path = os.path.join(app.config['UPLOAD_FOLDER'], filename)
        file.save(file_path)
        
        try:
            # Process the image
            result = scan_philippine_id(file_path)
            
            # Clean up the temporary file
            os.remove(file_path)
            
            return jsonify(result)
            
        except Exception as e:
            # Clean up the temporary file in case of error
            if os.path.exists(file_path):
                os.remove(file_path)
            raise e
            
    except Exception as e:
        return jsonify({
            'success': False,
            'error': f'Server error: {str(e)}'
        }), 500

@app.route('/api/scan-id-base64', methods=['POST'])
def scan_id_base64():
    """
    API endpoint to scan Philippine ID from base64 encoded image
    """
    try:
        data = request.get_json()
        
        if not data or 'image' not in data:
            return jsonify({
                'success': False,
                'error': 'No image data provided'
            }), 400
        
        # Decode base64 image
        try:
            image_data = data['image']
            if image_data.startswith('data:image'):
                # Remove data URL prefix
                image_data = image_data.split(',')[1]
            
            image_bytes = base64.b64decode(image_data)
            
            # Save to temporary file
            with tempfile.NamedTemporaryFile(delete=False, suffix='.jpg') as temp_file:
                temp_file.write(image_bytes)
                temp_path = temp_file.name
            
            try:
                # Process the image
                result = scan_philippine_id(temp_path)
                
                # Clean up the temporary file
                os.remove(temp_path)
                
                return jsonify(result)
                
            except Exception as e:
                # Clean up the temporary file in case of error
                if os.path.exists(temp_path):
                    os.remove(temp_path)
                raise e
                
        except Exception as e:
            return jsonify({
                'success': False,
                'error': f'Invalid image data: {str(e)}'
            }), 400
            
    except Exception as e:
        return jsonify({
            'success': False,
            'error': f'Server error: {str(e)}'
        }), 500

@app.route('/api/health', methods=['GET'])
def health_check():
    """
    Health check endpoint
    """
    return jsonify({
        'status': 'healthy',
        'service': 'Philippine ID Scanner API',
        'version': '1.0.0'
    })

@app.route('/api/validate-name', methods=['POST'])
def validate_name():
    """
    API endpoint to validate name and barangay against scanned data
    """
    try:
        data = request.get_json()
        
        if not data:
            return jsonify({
                'success': False,
                'error': 'No data provided'
            }), 400
        
        required_fields = ['entered_name', 'entered_barangay', 'scanned_name', 'scanned_barangay']
        for field in required_fields:
            if field not in data:
                return jsonify({
                    'success': False,
                    'error': f'Missing required field: {field}'
                }), 400
        
        # Normalize the data
        entered_name_norm = normalize_text(data['entered_name'])
        scanned_name_norm = normalize_text(data['scanned_name'])
        entered_barangay_norm = normalize_text(data['entered_barangay'])
        scanned_barangay_norm = normalize_text(data['scanned_barangay'])
        
        # Validate matches
        name_match = (entered_name_norm == scanned_name_norm)
        barangay_match = (entered_barangay_norm == scanned_barangay_norm)
        
        # Check for significant differences
        entered_length = len(entered_name_norm)
        scanned_length = len(scanned_name_norm)
        length_difference = abs(entered_length - scanned_length)
        significant_difference = (length_difference > 3)
        
        validation_passed = name_match and barangay_match
        
        result = {
            'success': validation_passed,
            'name_match': name_match,
            'barangay_match': barangay_match,
            'significant_difference': significant_difference,
            'length_difference': length_difference
        }
        
        if not validation_passed:
            if not name_match and not barangay_match:
                result['error'] = 'Both name and barangay do not match the Philippine ID'
            elif not name_match:
                if significant_difference:
                    result['error'] = 'Name is missing characters compared to the Philippine ID'
                else:
                    result['error'] = 'Name does not match the Philippine ID'
            else:
                result['error'] = 'Barangay does not match the Philippine ID'
        
        return jsonify(result)
        
    except Exception as e:
        return jsonify({
            'success': False,
            'error': f'Server error: {str(e)}'
        }), 500

if __name__ == '__main__':
    print("Starting Philippine ID Scanner API...")
    print("Available endpoints:")
    print("  POST /api/scan-id - Upload image file")
    print("  POST /api/scan-id-base64 - Send base64 encoded image")
    print("  POST /api/validate-name - Validate name and barangay")
    print("  GET /api/health - Health check")
    print("\nStarting server on http://localhost:5000")
    
    app.run(debug=True, host='0.0.0.0', port=5000)
