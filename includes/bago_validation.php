<?php
/**
 * Bago City Residency Validation Functions
 */

/**
 * Get list of Bago City barangays
 * @return array List of barangays
 */
function getBagoBarangays() {
    return [
        'ABUANAN', 'ALIANZA', 'ATIPULUAN', 'BACONG-MONTILLA', 'BAGROY',
        'BALINGASAG', 'BINUBUHAN', 'BUSAY', 'CALUMANGAN', 'CARIDAD',
        'DULAO', 'ILIJAN', 'LAG-ASAN', 'LAGUNA', 'MA-AO',
        'MAILUM', 'MALINGIN', 'MANDALAGAN', 'NAPOLES', 'PACOL',
        'POBLACION', 'SAGASA', 'SAMPINIT', 'TABUNAN', 'TALOC'
    ];
}

/**
 * Get list of other Negros Occidental locations (for informative messages)
 * @return array List of other locations
 */
function getOtherNegrosLocations() {
    return [
        'BACOLOD', 'CADIZ', 'ESCALANTE', 'HIMAMAYLAN', 'KABANKALAN',
        'LA CARLOTA', 'SAGAY', 'SAN CARLOS', 'SILAY', 'SIPALAY',
        'TALISAY', 'VICTORIAS', 'BINALBAGAN', 'CALATRAVA', 'CANDONI',
        'CAUAYAN', 'ENRIQUE B. MAGALONA', 'HINIGARAN', 'HINOBA-AN', 'ILOG',
        'ISABELA', 'LA CASTELLANA', 'MANAPLA', 'MOISES PADILLA', 'MURCIA',
        'PONTEVEDRA', 'PULUPANDAN', 'SALVADOR BENEDICTO', 'SAN ENRIQUE', 'TOBOSO',
        'VALLADOLID'
    ];
}

/**
 * Validate ID for Bago City residency
 * @param string $ocrText OCR extracted text from ID
 * @param string $fullName User's full name for verification
 * @return array [isValid, message]
 */
function validateIDForBagoResidency($ocrText, $fullName) {
    $ocrUpper = strtoupper($ocrText);
    $bagoBarangays = getBagoBarangays();
    $otherLocations = getOtherNegrosLocations();
    
    // Check if "BAGO" or "BAGO CITY" is mentioned
    $hasBago = (strpos($ocrUpper, 'BAGO') !== false);
    
    // Check for specific barangay
    $foundBarangay = null;
    foreach ($bagoBarangays as $brgy) {
        if (strpos($ocrUpper, $brgy) !== false) {
            $foundBarangay = $brgy;
            break;
        }
    }
    
    // Check for other Negros locations
    $foundOtherLocation = null;
    foreach ($otherLocations as $location) {
        if (strpos($ocrUpper, $location) !== false) {
            $foundOtherLocation = $location;
            break;
        }
    }
    
    // Validation logic
    if ($foundBarangay && $hasBago) {
        return [true, "✓ ID verified: Bago City resident from Barangay " . ucwords(strtolower($foundBarangay))];
    } elseif ($foundBarangay) {
        return [true, "✓ ID verified: Barangay " . ucwords(strtolower($foundBarangay)) . " detected"];
    } elseif ($hasBago) {
        return [true, "✓ ID verified: Bago City address confirmed"];
    } elseif ($foundOtherLocation) {
        return [false, "Sorry, only Bago City residents can register. Your ID shows " . ucwords(strtolower($foundOtherLocation)) . "."];
    } elseif (strpos($ocrUpper, 'NEGROS') !== false) {
        return [false, "Sorry, only Bago City residents can register. Please ensure your ID clearly shows a Bago City address."];
    } else {
        return [false, "Unable to verify Bago City address from your ID. Please ensure your ID clearly shows your Bago City address."];
    }
}

/**
 * Generate HTML dropdown for barangay selection
 * @param string $selectedBarangay Currently selected barangay
 * @return string HTML select options
 */
function generateBarangayDropdown($selectedBarangay = '') {
    $barangays = getBagoBarangays();
    $html = '<option value="">Select Barangay</option>';
    
    foreach ($barangays as $brgy) {
        $selected = ($brgy === strtoupper($selectedBarangay)) ? 'selected' : '';
        $displayName = ucwords(strtolower($brgy));
        $html .= "<option value=\"$brgy\" $selected>$displayName</option>\n";
    }
    
    return $html;
}

/**
 * Extract barangay name from OCR text
 * @param string $ocrText OCR extracted text
 * @return string|null Barangay name or null if not found
 */
function extractBarangayFromOCR($ocrText) {
    $ocrUpper = strtoupper($ocrText);
    $barangays = getBagoBarangays();
    
    foreach ($barangays as $brgy) {
        if (strpos($ocrUpper, $brgy) !== false) {
            return $brgy;
        }
    }
    
    return null;
}

/**
 * Check if a barangay is valid for Bago City
 * @param string $barangay Barangay name
 * @return bool True if valid
 */
function isValidBagoBarangay($barangay) {
    $barangays = getBagoBarangays();
    return in_array(strtoupper($barangay), $barangays);
}

