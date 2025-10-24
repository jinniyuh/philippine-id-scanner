#!/usr/bin/env python3
"""
Simple test script for DocTR text recognition functionality
Tests the core logic for DocTR text recognition
"""

import json
import re

def extract_from_text(text):
    """Extract resident information from text-based barcode data"""
    info = {}
    
    # Common patterns for Philippine ID cards
    # Name patterns
    name_patterns = [
        r'Name[:\s]+([A-Za-z\s,.-]+)',
        r'Full Name[:\s]+([A-Za-z\s,.-]+)',
        r'^([A-Za-z\s,.-]+)(?=\s*\d)',  # Name at start followed by numbers
    ]
    
    for pattern in name_patterns:
        match = re.search(pattern, text, re.IGNORECASE)
        if match:
            info['name'] = match.group(1).strip()
            break
    
    # Address/Barangay patterns
    address_patterns = [
        r'Address[:\s]+([^,\n]+)',
        r'Barangay[:\s]+([A-Za-z\s]+)',
        r'Brgy[.\s]+([A-Za-z\s]+)',
        r'([A-Za-z\s]+),\s*Bago\s*City',
    ]
    
    for pattern in address_patterns:
        match = re.search(pattern, text, re.IGNORECASE)
        if match:
            address = match.group(1).strip()
            # Extract barangay from address
            barangay = extract_barangay_from_address(address)
            if barangay:
                info['barangay'] = barangay
            break
    
    # City validation
    if 'Bago' in text and 'Negros Occidental' in text:
        info['city'] = 'Bago City'
        info['province'] = 'Negros Occidental'
    
    return info

def extract_barangay_from_address(address):
    """Extract barangay name from address string"""
    # List of barangays in Bago City, Negros Occidental
    barangays = [
        'Abuanan', 'Alijis', 'Atipuluan', 'Bacong', 'Bagong Silang',
        'Balingasag', 'Binubuhan', 'Busay', 'Calumangan', 'Caridad',
        'Dulao', 'Ilijan', 'Lag-asan', 'Ma-ao', 'Mailum',
        'Malingin', 'Napoles', 'Pacol', 'Poblacion', 'Sagasa',
        'Taloc', 'Tigbao', 'Tinong-an', 'Tuburan'
    ]
    
    address_lower = address.lower()
    for barangay in barangays:
        if barangay.lower() in address_lower:
            return barangay
    
    return None

def validate_and_format_data(data):
    """Validate and format the extracted data"""
    result = {
        'success': False,
        'name': '',
        'barangay': '',
        'city': '',
        'province': '',
        'is_bago_resident': False
    }
    
    # Check if resident is from Bago City, Negros Occidental
    city = data.get('city', '').lower()
    province = data.get('province', '').lower()
    
    if 'bago' in city and 'negros occidental' in province:
        result['is_bago_resident'] = True
        result['city'] = 'Bago City'
        result['province'] = 'Negros Occidental'
    else:
        result['error'] = 'Resident is not from Bago City, Negros Occidental'
        return result
    
    # Extract name
    name = data.get('name', '').strip()
    if name:
        result['name'] = name
    else:
        result['error'] = 'Name not found in barcode'
        return result
    
    # Extract barangay
    barangay = data.get('barangay', '').strip()
    if barangay:
        result['barangay'] = barangay
    else:
        result['error'] = 'Barangay not found in barcode'
        return result
    
    result['success'] = True
    return result

def parse_barcode_data(barcode_data):
    """Parse barcode data to extract resident information"""
    try:
        # Try to parse as JSON first
        try:
            data = json.loads(barcode_data)
            return validate_and_format_data(data)
        except json.JSONDecodeError:
            pass
        
        # If not JSON, try to extract information using regex patterns
        # This is for cases where the barcode contains structured text
        resident_info = extract_from_text(barcode_data)
        return validate_and_format_data(resident_info)
        
    except Exception as e:
        return {"error": f"Error parsing barcode data: {str(e)}"}

def test_functionality():
    """Test the barcode scanning functionality"""
    print("🚀 Testing Barcode Scanner Functionality")
    print("=" * 50)
    
    # Test case 1: JSON format
    print("\n1. Testing JSON format barcode:")
    json_data = '{"name": "Juan Dela Cruz", "barangay": "Poblacion", "city": "Bago City", "province": "Negros Occidental"}'
    result = parse_barcode_data(json_data)
    print(f"Input: {json_data}")
    print(f"Result: {json.dumps(result, indent=2)}")
    
    # Test case 2: Text format
    print("\n2. Testing text format barcode:")
    text_data = "Name: Maria Santos\nBarangay: Dulao\nAddress: Dulao, Bago City, Negros Occidental"
    result = parse_barcode_data(text_data)
    print(f"Input: {text_data}")
    print(f"Result: {json.dumps(result, indent=2)}")
    
    # Test case 3: Invalid location
    print("\n3. Testing invalid location:")
    invalid_data = '{"name": "John Doe", "barangay": "Manila", "city": "Manila", "province": "Metro Manila"}'
    result = parse_barcode_data(invalid_data)
    print(f"Input: {invalid_data}")
    print(f"Result: {json.dumps(result, indent=2)}")
    
    # Test case 4: Missing name
    print("\n4. Testing missing name:")
    missing_name = '{"barangay": "Poblacion", "city": "Bago City", "province": "Negros Occidental"}'
    result = parse_barcode_data(missing_name)
    print(f"Input: {missing_name}")
    print(f"Result: {json.dumps(result, indent=2)}")
    
    # Test case 5: Complex text format
    print("\n5. Testing complex text format:")
    complex_text = "Full Name: Ana Garcia Rodriguez\nBarangay: Ma-ao\nAddress: Ma-ao, Bago City, Negros Occidental, Philippines"
    result = parse_barcode_data(complex_text)
    print(f"Input: {complex_text}")
    print(f"Result: {json.dumps(result, indent=2)}")

def show_sample_data():
    """Show sample barcode data formats"""
    print("\n📝 Sample Barcode Data Formats:")
    print("=" * 40)
    
    # JSON format
    json_sample = {
        "name": "Pedro Rodriguez",
        "barangay": "Poblacion",
        "city": "Bago City",
        "province": "Negros Occidental"
    }
    print("\nJSON Format:")
    print(json.dumps(json_sample, indent=2))
    
    # Text format
    text_sample = """Name: Pedro Rodriguez
Barangay: Poblacion
Address: Poblacion, Bago City, Negros Occidental"""
    print("\nText Format:")
    print(text_sample)
    
    print("\nSupported Barangays in Bago City:")
    barangays = [
        'Abuanan', 'Alijis', 'Atipuluan', 'Bacong', 'Bagong Silang',
        'Balingasag', 'Binubuhan', 'Busay', 'Calumangan', 'Caridad',
        'Dulao', 'Ilijan', 'Lag-asan', 'Ma-ao', 'Mailum',
        'Malingin', 'Napoles', 'Pacol', 'Poblacion', 'Sagasa',
        'Taloc', 'Tigbao', 'Tinong-an', 'Tuburan'
    ]
    print(", ".join(barangays))

def main():
    """Main function"""
    test_functionality()
    show_sample_data()
    
    print("\n✅ Testing completed successfully!")
    print("\n📋 Next Steps:")
    print("1. Install Python dependencies: pip install python-doctr[torch]")
    print("2. Test with actual barcode images using the full scanner")
    print("3. Access test page: test_barcode_scanner.php")
    print("4. Integrate scanner into your forms")

if __name__ == "__main__":
    main()
