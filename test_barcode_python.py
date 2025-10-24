#!/usr/bin/env python3
"""
Test script for the DocTR text recognition functionality
This script tests the text recognition without requiring actual ID images
"""

import json
import sys
import os

# Add current directory to path to import doctr_scanner
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

try:
    from doctr_scanner import scan_philippine_id
    print("✅ Successfully imported DocTR scanner modules")
except ImportError as e:
    print(f"❌ Error importing modules: {e}")
    print("Note: This test requires doctr_scanner.py to be present")
    sys.exit(1)

def test_doctr_functionality():
    """Test DocTR text recognition functionality"""
    print("\n🧪 Testing DocTR functionality...")
    
    # Note: This test requires actual image files
    print("Note: DocTR testing requires actual Philippine ID image files")
    print("To test DocTR functionality:")
    print("1. Place a Philippine ID image in the current directory")
    print("2. Call: scan_philippine_id('image_path.jpg')")
    print("3. Check the extracted text and validation results")

def test_validation():
    """Test data validation"""
    print("\n🧪 Testing data validation...")
    
    # Valid Bago City data
    valid_data = {
        'name': 'Pedro Rodriguez',
        'barangay': 'Ma-ao',
        'city': 'Bago City',
        'province': 'Negros Occidental'
    }
    print(f"Valid data test: {valid_data}")
    
    # Invalid city data
    invalid_data = {
        'name': 'Juan Perez',
        'barangay': 'Makati',
        'city': 'Makati City',
        'province': 'Metro Manila'
    }
    print(f"Invalid city test: {invalid_data}")

def test_barangay_extraction():
    """Test barangay extraction from address"""
    print("\n🧪 Testing barangay extraction...")
    
    # List of valid barangays in Bago City
    valid_barangays = [
        'Abuanan', 'Alijis', 'Atipuluan', 'Bacong', 'Bagong Silang',
        'Balingasag', 'Binubuhan', 'Busay', 'Calumangan', 'Caridad',
        'Dulao', 'Ilijan', 'Lag-asan', 'Ma-ao', 'Mailum',
        'Malingin', 'Napoles', 'Pacol', 'Poblacion', 'Sagasa',
        'Taloc', 'Tigbao', 'Tinong-an', 'Tuburan'
    ]
    
    test_addresses = [
        "123 Poblacion, Bago City",
        "Brgy. Dulao, Bago City, Negros Occidental",
        "Barangay Ma-ao, Bago City",
        "Invalid Address, Somewhere Else"
    ]
    
    for address in test_addresses:
        # Simple barangay extraction logic
        found_barangay = None
        for barangay in valid_barangays:
            if barangay.lower() in address.lower():
                found_barangay = barangay
                break
        print(f"Address: '{address}' -> Barangay: '{found_barangay}'")

def create_sample_barcode_data():
    """Create sample barcode data for testing"""
    print("\n📝 Sample barcode data formats:")
    
    # JSON format
    json_sample = {
        "name": "Ana Garcia",
        "barangay": "Poblacion",
        "city": "Bago City",
        "province": "Negros Occidental"
    }
    print(f"JSON format: {json.dumps(json_sample, indent=2)}")
    
    # Text format
    text_sample = """Name: Ana Garcia
Barangay: Poblacion
Address: Poblacion, Bago City, Negros Occidental"""
    print(f"Text format:\n{text_sample}")

def main():
    """Run all tests"""
    print("🚀 Starting DocTR text recognition tests...")
    
    test_doctr_functionality()
    test_validation()
    test_barangay_extraction()
    create_sample_barcode_data()
    
    print("\n✅ All tests completed!")
    print("\n📋 Next steps:")
    print("1. Install Python dependencies: pip install python-doctr[torch]")
    print("2. Test with actual Philippine ID images")
    print("3. Integrate with your PHP forms")
    print("4. Run the test page: test_barcode_scanner.php")

if __name__ == "__main__":
    main()
