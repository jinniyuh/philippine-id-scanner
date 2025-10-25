#!/usr/bin/env python3
"""
Simple Flask API for Philippine ID Scanning - Render Compatible
This version works without DocTR initially to ensure deployment success
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

# Suppress warnings
warnings.filterwarnings("ignore", category=FutureWarning)

app = Flask(__name__)
CORS(app)

# Configuration for Render
UPLOAD_FOLDER = '/tmp/uploads/id_scans'
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

def scan_philippine_id_simple(image_path: str) -> Dict:
    """
    Simple ID scanning that returns mock data for testing
    This ensures the API works before adding DocTR
    """
    try:
        # For now, return mock data to test the API structure
        # In production, you would implement actual OCR here
        
        return {
            "success": False,
            "error": "DocTR not yet installed. This is a test deployment to ensure the API structure works.",
            "message": "API is running successfully. DocTR will be added in the next deployment.",
            "fallback": True
        }
        
    except Exception as e:
        return {
            "success": False,
            "error": f"Error processing image: {str(e)}"
        }

@app.route('/api/scan-id', methods=['POST'])
def scan_id():
    """API endpoint to scan Philippine ID"""
    try:
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
        
        file.seek(0, 2)
        file_size = file.tell()
        file.seek(0)
        
        if file_size > MAX_FILE_SIZE:
            return jsonify({
                'success': False,
                'error': f'File too large. Maximum size is {MAX_FILE_SIZE // (1024*1024)}MB'
            }), 400
        
        filename = secure_filename(file.filename)
        file_path = os.path.join(app.config['UPLOAD_FOLDER'], filename)
        file.save(file_path)
        
        try:
            result = scan_philippine_id_simple(file_path)
            os.remove(file_path)
            return jsonify(result)
        except Exception as e:
            if os.path.exists(file_path):
                os.remove(file_path)
            raise e
            
    except Exception as e:
        return jsonify({
            'success': False,
            'error': f'Server error: {str(e)}'
        }), 500

@app.route('/api/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'service': 'Philippine ID Scanner API (Simple Version)',
        'version': '1.0.0-simple',
        'platform': 'Render',
        'note': 'This is a simplified version for initial deployment testing'
    })

@app.route('/api/validate-name', methods=['POST'])
def validate_name():
    """API endpoint to validate name and barangay against scanned data"""
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
        
        entered_name_norm = normalize_text(data['entered_name'])
        scanned_name_norm = normalize_text(data['scanned_name'])
        entered_barangay_norm = normalize_text(data['entered_barangay'])
        scanned_barangay_norm = normalize_text(data['scanned_barangay'])
        
        name_match = (entered_name_norm == scanned_name_norm)
        barangay_match = (entered_barangay_norm == scanned_barangay_norm)
        
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

@app.route('/', methods=['GET'])
def home():
    """Home endpoint"""
    return jsonify({
        'message': 'Philippine ID Scanner API',
        'version': '1.0.0-simple',
        'status': 'running',
        'endpoints': {
            'health': '/api/health',
            'scan': '/api/scan-id',
            'validate': '/api/validate-name'
        }
    })

if __name__ == '__main__':
    port = int(os.environ.get('PORT', 5000))
    print("Starting Simple Philippine ID Scanner API on Render...")
    print("Available endpoints:")
    print("  GET / - Home")
    print("  GET /api/health - Health check")
    print("  POST /api/scan-id - Upload image file")
    print("  POST /api/validate-name - Validate name and barangay")
    print(f"\nStarting server on port {port}...")
    
    app.run(debug=False, host='0.0.0.0', port=port)
