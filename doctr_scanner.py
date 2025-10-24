#!/usr/bin/env python3
"""
DocTR-based Text Recognition for Philippine National ID
Extracts real text from uploaded Philippine ID images
"""

import sys
import json
import re
import warnings
from typing import Dict, List, Tuple

# Suppress PyTorch warnings
warnings.filterwarnings("ignore", category=FutureWarning)

def scan_philippine_id(image_path: str) -> Dict:
    """
    Scan Philippine ID and extract real information using DocTR
    Optimized for faster processing
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

def extract_name_from_text(text: str) -> str:
    """
    Extract name from Philippine ID text using various patterns
    """
    # Philippine ID name patterns (prioritize specific field patterns)
    name_patterns = [
        # Pattern to combine given name and last name from Philippine ID format (PRIORITY)
        # Improved patterns to capture multi-word given names until next field
        r'MGA\s+PANGALAN\s*/\s*GIVEN\s+NAMES:\s*([A-Za-z\s,.-]+?)(?=\s*(?:GITNANG|APELYIDO|APELYDO|LAST\s+NAME|$)).*?APELYIDO\s*/\s*LAST\s+NAME:\s*([A-Za-z\s,.-]+?)(?:\s|$)',
        r'Mga Pangalan/Given Names\s+([A-Za-z\s,.-]+?)(?=\s*(?:Gitnang|Apelyido|Last\s+Name|$)).*?Apelyido/Last Name\s+([A-Za-z\s,.-]+?)(?:\s|$)',
        r'MGAPANGALAN/GIVENNAMES\s+([A-Za-z\s,.-]+?)(?=\s*(?:GITNANG|APELYIDO|APELYDO|LASTNAME|$)).*?APELYDO/LASTNAME\s+([A-Za-z\s,.-]+?)(?:\s|$)',
        r'MGAPANGALAN/GIVENNAMES\s+([A-Za-z\s,.-]+?)(?=\s*(?:GITNANG|APELYIDO|APELYDO|LASTNAME|$)).*?APELYDO/LASTNAVE\s+([A-Za-z\s,.-]+?)(?:\s|$)',
        
        # Fallback patterns for cases where next field might not be present
        r'MGA\s+PANGALAN\s*/\s*GIVEN\s+NAMES:\s*([A-Za-z\s,.-]+?)(?:\s|$).*?APELYIDO\s*/\s*LAST\s+NAME:\s*([A-Za-z\s,.-]+?)(?:\s|$)',
        r'Mga Pangalan/Given Names\s+([A-Za-z\s,.-]+?)(?:\s|$).*?Apelyido/Last Name\s+([A-Za-z\s,.-]+?)(?:\s|$)',
        r'MGAPANGALAN/GIVENNAMES\s+([A-Za-z\s,.-]+?)(?:\s|$).*?APELYDO/LASTNAME\s+([A-Za-z\s,.-]+?)(?:\s|$)',
        r'MGAPANGALAN/GIVENNAMES\s+([A-Za-z\s,.-]+?)(?:\s|$).*?APELYDO/LASTNAVE\s+([A-Za-z\s,.-]+?)(?:\s|$)',
        
        # Individual field patterns (extract VALUES after the labels)
        r'APELYIDO\s*/\s*LAST\s+NAME:\s*([A-Za-z\s,.-]+?)(?:\s|$)',
        r'MGA\s+PANGALAN\s*/\s*GIVEN\s+NAMES:\s*([A-Za-z\s,.-]+?)(?:\s|$)',
        
        # Alternative pattern for the specific format seen (PRIORITY) - improved for multi-word names
        r'Mga Pangalan/Given Names\s+([A-Za-z\s,.-]+?)(?=\s*(?:Gitnang|Apelyido|Last\s+Name|$)).*?Gitnang Apelyido/Middle Name\s+([A-Za-z\s,.-]+?)(?=\s*(?:Apelyido|Last\s+Name|$)).*?Apelyido/Last Name\s+([A-Za-z\s,.-]+?)(?:\s|$)',
        r'MGAPANGALAN/GIVENNAMES\s+([A-Za-z\s,.-]+?)(?=\s*(?:GITNANG|APELYIDO|APELYDO|LASTNAME|$)).*?GITNANG\s+PANGALAN/MIDDLENAME\s+([A-Za-z\s,.-]+?)(?=\s*(?:APELYIDO|APELYDO|LASTNAME|$)).*?APELYDO/LASTNAME\s+([A-Za-z\s,.-]+?)(?:\s|$)',
        
        
        # Individual field patterns (extract VALUES after the labels)
        r'Apelyido/Last Name\s+([A-Za-z\s,.-]+?)(?:\s|$)',
        r'APELYDO/LASTNAME\s+([A-Za-z\s,.-]+?)(?:\s|$)',
        r'APELYDO/LASTNAVE\s+([A-Za-z\s,.-]+?)(?:\s|$)',
        r'APELYIDO/LASINAME:\s*([A-Za-z\s,.-]+?)(?:\s|$)',
        r'LAST\s+NAME:\s*([A-Za-z\s,.-]+?)(?:\s|$)',
        
        # Given name patterns (extract VALUES after the labels)
        r'Mga Pangalan/Given Names\s+([A-Za-z\s,.-]+?)(?:\s|$)',
        r'MGAPANGALAN/GIVENNAMES\s+([A-Za-z\s,.-]+?)(?:\s|$)',
        r'MGA\s+PANGALAN/GIVEN\s+NAME\(S\):\s*([A-Za-z\s,.-]+?)(?:\s|$)',
        r'GIVEN\s+NAME\(S\):\s*([A-Za-z\s,.-]+?)(?:\s|$)',
        r'FIRST\s+NAME:\s*([A-Za-z\s,.-]+?)(?:\s|$)',
        
        # General name patterns (LOW PRIORITY - only if specific patterns fail)
        r'NAME:\s*([A-Za-z\s,.-]+?)(?:\s|$)',
        r'FULL\s+NAME:\s*([A-Za-z\s,.-]+?)(?:\s|$)',
        
        # Pattern for complete names (First Last) (LOW PRIORITY)
        r'([A-Z][a-z]+\s+[A-Z][a-z]+(?:\s+[A-Z][a-z]+)*)',
    ]
    
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
                # Debug: Print extracted given name
                print(f"DEBUG: Extracted given name: '{given_name}'", file=sys.stderr)
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
                # Debug: Print extracted last name
                print(f"DEBUG: Extracted last name: '{last_name}'", file=sys.stderr)
                break
        if last_name:
            break
    
    # Clean up given name to remove any middle name contamination
    if given_name:
        # Remove middle name patterns that might have been captured using full text context
        given_name = clean_given_name(given_name, text)
        print(f"DEBUG: Cleaned given name: '{given_name}'", file=sys.stderr)
    
    # Combine given name and last name
    if given_name and last_name:
        name = f"{given_name} {last_name}".strip()
        # Debug: Print combined name
        print(f"DEBUG: Combined name: '{name}'", file=sys.stderr)
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
            print(f"DEBUG: Valid name found: '{name}'", file=sys.stderr)
            return name
    
    # Fallback to original pattern matching
    for pattern in name_patterns:
        matches = re.findall(pattern, text, re.IGNORECASE | re.MULTILINE)
        for match in matches:
            if isinstance(match, tuple):
                if len(match) == 2:
                    # Handle combined name pattern (given name, last name)
                    given_name = match[0].strip()
                    last_name = match[1].strip()
                    name = f"{given_name} {last_name}".strip()
                elif len(match) == 3:
                    # Handle 3-part name pattern (given, middle, last)
                    given_name = match[0].strip()
                    middle_name = match[1].strip()
                    last_name = match[2].strip()
                    name = f"{given_name} {middle_name} {last_name}".strip()
                else:
                    name = " ".join([m.strip() for m in match if m.strip()]).strip()
            else:
                name = match.strip()
            
            # Validate name (should be at least 3 characters, contain letters, not be field labels or ID header)
            if (len(name) >= 3 and 
                any(c.isalpha() for c in name) and 
                not name.upper() in ['REPUBLIC', 'PHILIPPINES', 'PAMBANSANG', 'PAGKAKAKILAN', 'IDENTIFICATION', 'CARD', 'REPUBLIKA', 'NG', 'PILIPINAS', 'PAMBANSANG PAGKAKAKILAN'] and
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
        'Dulao', 'Ilijan', 'Lag-Asan', 'Ma-ao', 'Mailum',
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

def main():
    """
    Main function for command line usage
    """
    if len(sys.argv) != 2:
        result = {"error": "Usage: python doctr_scanner.py <image_path>"}
    else:
        image_path = sys.argv[1]
        result = scan_philippine_id(image_path)
    
    print(json.dumps(result))

if __name__ == "__main__":
    main()