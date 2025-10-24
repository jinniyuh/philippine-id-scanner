<?php
/**
 * Bago City Residency Validation
 * Validates that users are residents of Bago City and its 24 barangays
 */

// List of 24 Barangays in Bago City, Negros Occidental
$BAGO_BARANGAYS = [
    'Abuanan',
    'Alangilan', 
    'Atipuluan',
    'Bacong-Montilla',
    'Bagroy',
    'Balingasag',
    'Binubuhan',
    'Busay',
    'Calumangan',
    'Caridad',
    'Dulao',
    'Ilijan',
    'Lag-Asan',
    'Ma-ao',
    'Mailum',
    'Malingin',
    'Nabitasan',
    'Pacol',
    'Poblacion',
    'Sagasa',
    'Tabunan',
    'Taloc',
    'Sampinit',
    'Don Jorge L. Araneta'
];

/**
 * Validate if an address is within Bago City and its barangays
 * @param string $address The address to validate
 * @return array [is_valid, error_message]
 */
function validateBagoCityResidency($address) {
    global $BAGO_BARANGAYS;
    
    if (empty($address)) {
        return [false, "Address is required"];
    }
    
    // Normalize the address for comparison
    $normalizedAddress = strtoupper(trim($address));
    
    // Check if address contains other city/municipality names (reject non-Bago residents first)
    $otherCities = [
        'PULUPANDAN', 'TALISAY', 'BACOLOD', 'SILAY', 'VICTORIAS', 'CADIZ', 
        'SAGAY', 'ESCALANTE', 'MANAPLA', 'VALLADOLID', 'MURCIA', 'SALVADOR BENEDICTO',
        'LA CARLOTA', 'LA CASTELLANA', 'MOISES PADILLA', 'ISABELA', 'BINALBAGAN',
        'HIMAMAYLAN', 'KABANKALAN', 'ILOG', 'CAUAYAN', 'CANDONI', 'HINIGARAN',
        'PONTEVEDRA', 'HINOBA AN', 'SIPALAY', 'CALATRAVA', 'TOBOSO', 'SAN CARLOS'
    ];
    
    foreach ($otherCities as $city) {
        if (strpos($normalizedAddress, $city) !== false) {
            return [false, "❌ You are NOT a Bago City resident. You CANNOT register to our system. Your address shows you are from " . ucwords(strtolower($city)) . ", not Bago City."];
        }
    }
    
    // Check if address contains STRICT "BAGO CITY NEGROS OCCIDENTAL" or "CITY OF BAGO NEGROS OCCIDENTAL" or OCR variation
    $hasBagoCity = (
        strpos($normalizedAddress, 'BAGO CITY NEGROS OCCIDENTAL') !== false ||
        strpos($normalizedAddress, 'CITY OF BAGO NEGROS OCCIDENTAL') !== false ||
        strpos($normalizedAddress, 'CITY OF H L BAGO NEGROS OCCIDENTAL') !== false
    );
    
    if (!$hasBagoCity) {
        return [false, "❌ You are NOT a Bago City resident. You CANNOT register to our system. Only Bago City residents are allowed to register."];
    }
    
    // Check if address contains any of the valid barangays
    $hasValidBarangay = false;
    $foundBarangay = '';
    
    foreach ($BAGO_BARANGAYS as $barangay) {
        $normalizedBarangay = strtoupper($barangay);
        
        // Check for exact barangay name or with "BRGY", "BRG", "BARANGAY"
        if (
            strpos($normalizedAddress, $normalizedBarangay) !== false ||
            strpos($normalizedAddress, 'BRGY ' . $normalizedBarangay) !== false ||
            strpos($normalizedAddress, 'BRG ' . $normalizedBarangay) !== false ||
            strpos($normalizedAddress, 'BARANGAY ' . $normalizedBarangay) !== false
        ) {
            $hasValidBarangay = true;
            $foundBarangay = $barangay;
            break;
        }
    }
    
    if (!$hasValidBarangay) {
        return [false, "❌ You are NOT a Bago City resident. You CANNOT register to our system. You must be from one of the 24 barangays of Bago City."];
    }
    
    return [true, "✅ Valid Bago City resident - Barangay: " . $foundBarangay];
}

/**
 * Extract barangay from address for display
 * @param string $address The address to extract from
 * @return string The barangay name
 */
function extractBarangayFromAddress($address) {
    global $BAGO_BARANGAYS;
    
    $normalizedAddress = strtoupper(trim($address));
    
    foreach ($BAGO_BARANGAYS as $barangay) {
        $normalizedBarangay = strtoupper($barangay);
        
        if (
            strpos($normalizedAddress, $normalizedBarangay) !== false ||
            strpos($normalizedAddress, 'BRGY ' . $normalizedBarangay) !== false ||
            strpos($normalizedAddress, 'BRG ' . $normalizedBarangay) !== false ||
            strpos($normalizedAddress, 'BARANGAY ' . $normalizedBarangay) !== false
        ) {
            return $barangay;
        }
    }
    
    return 'Unknown';
}

/**
 * Get list of valid barangays for dropdown/display
 * @return array List of barangays
 */
function getBagoBarangays() {
    global $BAGO_BARANGAYS;
    return $BAGO_BARANGAYS;
}

/**
 * Enhanced ID validation that scans ID address for Bago City residency
 * @param string $ocrText OCR text from ID
 * @param string $fullName Full name from registration
 * @return array [is_valid, error_message]
 */
function validateIDForBagoResidency($ocrText, $fullName) {
    // RULE 0: Check if OCR text is readable
    if (empty($ocrText) || strlen(trim($ocrText)) < 20) {
        return [false, "❌ We cannot read your ID clearly. Please upload a clearer, high-quality image of your valid ID."];
    }
    
    // First, normalize the OCR text
    $ocrNorm = strtoupper(preg_replace('/[^A-Za-z0-9\s]/', ' ', $ocrText));
    
    // Extract name tokens
    $nameTokens = array_filter(array_map('trim', explode(' ', strtoupper($fullName))));
    $nameMatchCount = 0;
    
    foreach ($nameTokens as $token) {
        if (strpos($ocrNorm, $token) !== false) {
            $nameMatchCount++;
        }
    }
    
    // RULE 1: Must match at least 2 name tokens (First + Last)
    if ($nameMatchCount < 2) {
        return [false, "❌ The full name you entered does not match the name on your ID. Please check and try again."];
    }
    
    // RULE 2: Must NOT contain other city/municipality names (reject non-Bago residents first)
    $otherCities = [
        'PULUPANDAN', 'TALISAY', 'BACOLOD', 'SILAY', 'VICTORIAS', 'CADIZ', 
        'SAGAY', 'ESCALANTE', 'MANAPLA', 'VALLADOLID', 'MURCIA', 'SALVADOR BENEDICTO',
        'LA CARLOTA', 'LA CASTELLANA', 'MOISES PADILLA', 'ISABELA', 'BINALBAGAN',
        'HIMAMAYLAN', 'KABANKALAN', 'ILOG', 'CAUAYAN', 'CANDONI', 'HINIGARAN',
        'PONTEVEDRA', 'HINOBA AN', 'SIPALAY', 'CALATRAVA', 'TOBOSO', 'SAN CARLOS'
    ];
    
    foreach ($otherCities as $city) {
        if (strpos($ocrNorm, $city) !== false) {
            return [false, "❌ You are NOT a Bago City resident. You CANNOT register to our system. Your ID shows you are from " . ucwords(strtolower($city)) . ", not Bago City."];
        }
    }
    
    // RULE 3: Must contain STRICT "BAGO CITY NEGROS OCCIDENTAL" or "CITY OF BAGO NEGROS OCCIDENTAL" or OCR variations
    $hasBagoCity = (
        strpos($ocrNorm, "BAGO CITY NEGROS OCCIDENTAL") !== false ||
        strpos($ocrNorm, "CITY OF BAGO NEGROS OCCIDENTAL") !== false ||
        strpos($ocrNorm, "CITY OF H L BAGO NEGROS OCCIDENTAL") !== false ||
        strpos($ocrNorm, "CITY OF H.L BAGO NEGROS OCCIDENTAL") !== false ||
        strpos($ocrNorm, "CITY OF HL BAGO NEGROS OCCIDENTAL") !== false ||
        strpos($ocrNorm, "CHFY 0FBAGO NEGROS OCCIDENTAL") !== false ||
        strpos($ocrNorm, "CHFY OF BAGO NEGROS OCCIDENTAL") !== false
    );
    
    // Additional flexible pattern matching for OCR variations
    if (!$hasBagoCity) {
        $hasBago = strpos($ocrNorm, "BAGO") !== false;
        $hasNegrosOccidental = strpos($ocrNorm, "NEGROS OCCIDENTAL") !== false;
        
        if ($hasBago && $hasNegrosOccidental) {
            // Check if it's not another city
            $otherCities = [
                'PULUPANDAN', 'TALISAY', 'BACOLOD', 'SILAY', 'VICTORIAS', 'CADIZ', 
                'SAGAY', 'ESCALANTE', 'MANAPLA', 'VALLADOLID', 'MURCIA', 'SALVADOR BENEDICTO',
                'LA CARLOTA', 'LA CASTELLANA', 'MOISES PADILLA', 'ISABELA', 'BINALBAGAN',
                'HIMAMAYLAN', 'KABANKALAN', 'ILOG', 'CAUAYAN', 'CANDONI', 'HINIGARAN',
                'PONTEVEDRA', 'HINOBA AN', 'SIPALAY', 'CALATRAVA', 'TOBOSO', 'SAN CARLOS',
                'MANILA'
            ];
            $hasOtherCity = false;
            
            foreach ($otherCities as $city) {
                if (strpos($ocrNorm, $city) !== false) {
                    $hasOtherCity = true;
                    break;
                }
            }
            
            if (!$hasOtherCity) {
                $hasBagoCity = true; // Flexible match: BAGO + NEGROS OCCIDENTAL found, no other cities
            }
        }
        
        // ULTRA FLEXIBLE: Check for "NEGROS" alone (might be missing "OCCIDENTAL")
        if (!$hasBagoCity && $hasBago) {
            $hasNegros = strpos($ocrNorm, "NEGROS") !== false;
            
            if ($hasNegros) {
                $negrosIndex = strpos($ocrNorm, "NEGROS");
                $textAfterNegros = substr($ocrNorm, $negrosIndex, 50);
                
                // Check if "OCCIDENTAL" appears within 50 characters of "NEGROS"
                if (strpos($textAfterNegros, "OCCIDENTAL") !== false || 
                    strpos($textAfterNegros, "0CCIDENTAL") !== false || 
                    strpos($textAfterNegros, "OCCID") !== false) {
                    $hasBagoCity = true; // Ultra flexible match
                }
            }
        }
    }
    
    if (!$hasBagoCity) {
        return [false, "❌ You are NOT a Bago City resident. You CANNOT register to our system. Only Bago City residents are allowed to register."];
    }
    
    // RULE 4: Check for Negros Occidental in the ID address
    $hasNegrosOccidental = (
        strpos($ocrNorm, "NEGROS OCCIDENTAL") !== false ||
        strpos($ocrNorm, "NEGROS OCC") !== false
    );
    
    if (!$hasNegrosOccidental) {
        return [false, "❌ You are NOT a Bago City resident. You CANNOT register to our system. Only Bago City residents from Negros Occidental are allowed to register."];
    }
    
    // RULE 5: MUST find one of the 24 barangays in the ID address
    global $BAGO_BARANGAYS;
    $foundBarangay = '';
    $foundBarangayVariations = '';
    
    foreach ($BAGO_BARANGAYS as $barangay) {
        $barangayUpper = strtoupper($barangay);
        
        // Check for exact barangay name
        if (strpos($ocrNorm, $barangayUpper) !== false) {
            $foundBarangay = $barangay;
            break;
        }
        
        // Check for barangay with BRGY prefix
        if (strpos($ocrNorm, "BRGY " . $barangayUpper) !== false || 
            strpos($ocrNorm, "BRG " . $barangayUpper) !== false ||
            strpos($ocrNorm, "BARANGAY " . $barangayUpper) !== false) {
            $foundBarangay = $barangay;
            $foundBarangayVariations = " (found as BRGY " . $barangay . ")";
            break;
        }
        
        // Check for special cases (e.g., "DON JORGE" instead of full name)
        if ($barangay === "Don Jorge L. Araneta") {
            if (strpos($ocrNorm, "DON JORGE") !== false) {
                $foundBarangay = $barangay;
                break;
            }
        }
        
        // Check for hyphenated barangays
        if ($barangay === "Bacong-Montilla") {
            if (strpos($ocrNorm, "BACONG MONTILLA") !== false || 
                strpos($ocrNorm, "BACONG-MONTILLA") !== false) {
                $foundBarangay = $barangay;
                break;
            }
        }
    }
    
    // RULE 5: MUST find a barangay in the ID address - this is now REQUIRED
    if (!$foundBarangay) {
        return [false, "❌ You are NOT a Bago City resident. You CANNOT register to our system. Your ID must clearly show one of the 24 barangays of Bago City. If your ID is unclear, please upload a clearer image."];
    }
    
    // SUCCESS: Found both Bago City and a valid barangay
    return [true, "✅ ID Verified - Bago City resident from Barangay " . $foundBarangay . $foundBarangayVariations];
}

/**
 * Generate HTML select dropdown for barangays
 * @param string $selectedValue Currently selected value
 * @return string HTML select element
 */
function generateBarangayDropdown($selectedValue = '') {
    $barangays = getBagoBarangays();
    $html = '<select name="address" id="address" class="form-control" required>';
    $html .= '<option value="">-- Select Barangay --</option>';
    
    foreach ($barangays as $barangay) {
        $selected = ($selectedValue === $barangay) ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($barangay) . '"' . $selected . '>' . htmlspecialchars($barangay) . '</option>';
    }
    
    $html .= '</select>';
    return $html;
}
?>
