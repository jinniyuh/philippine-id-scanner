<?php
ob_start();
require_once "includes/conn.php";

header("Content-Type: application/json");

try {
    // Get pharmaceutical requests from our test data only (Population Test clients)
    $query = "
        SELECT 
            pr.request_id,
            pr.client_id,
            pr.species,
            pr.symptoms,
            pr.request_date,
            c.full_name as client_name,
            c.barangay
        FROM pharmaceutical_requests pr
        LEFT JOIN clients c ON pr.client_id = c.client_id
        WHERE pr.symptoms IS NOT NULL 
        AND pr.symptoms != ''
        AND c.full_name LIKE 'Test Client%'
        AND c.barangay IN ('Abuanan', 'Alianza', 'Atipuluan', 'Bacong-Montilla', 'Bagroy', 'Balingasag', 'Binubuhan', 'Busay', 'Calumangan', 'Caridad')
        ORDER BY pr.request_date DESC
    ";
    
    $result = $conn->query($query);
    $requests = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
    }
    
    // Group requests by symptoms and barangay
    $grouped = [];
    foreach ($requests as $request) {
        $symptoms = trim($request["symptoms"]);
        $barangay = $request["barangay"] ?: "Unknown";
        
        $key = $symptoms . "|" . $barangay;
        
        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                "symptom" => $symptoms,
                "barangay" => $barangay,
                "count" => 0,
                "requests" => [],
                "disease" => detectSpecificDisease($symptoms, $request["species"]),
                "animal" => $request["species"],
                "severity" => getSeverityLevel($symptoms)
            ];
        }
        
        $grouped[$key]["count"]++;
        $grouped[$key]["requests"][] = $request;
    }
    
    // Filter groups with at least 2 people per barangay
    $valid_groups = [];
    foreach ($grouped as $key => $group) {
        if ($group["count"] >= 2) { // Minimum 2 people per barangay
            $valid_groups[] = $group;
        }
    }
    
    // Group by symptom type to find patterns across barangays
    $symptom_patterns = [];
    foreach ($valid_groups as $group) {
        $symptom = $group["symptom"];
        if (!isset($symptom_patterns[$symptom])) {
            $symptom_patterns[$symptom] = [
                "symptom" => $symptom,
                "disease" => $group["disease"],
                "animal" => $group["animal"],
                "severity" => $group["severity"],
                "affected_barangays" => [],
                "total_requests" => 0,
                "barangay_count" => 0
            ];
        }
        
        $symptom_patterns[$symptom]["affected_barangays"][] = [
            "barangay" => $group["barangay"],
            "request_count" => $group["count"],
            "requests" => $group["requests"]
        ];
        $symptom_patterns[$symptom]["total_requests"] += $group["count"];
        $symptom_patterns[$symptom]["barangay_count"]++;
    }
    
    // Check for outbreak conditions (10+ barangays with same symptoms)
    $outbreak_alerts = [];
    foreach ($symptom_patterns as $symptom => $pattern) {
        if ($pattern["barangay_count"] >= 10) {
            $outbreak_alerts[] = [
                "type" => "POSSIBLE OUTBREAK",
                "symptom" => $symptom,
                "disease" => $pattern["disease"],
                "animal" => $pattern["animal"],
                "severity" => $pattern["severity"],
                "affected_barangays" => $pattern["barangay_count"], // This should be the actual count of affected barangays
                "total_requests" => $pattern["total_requests"],
                "barangay_details" => $pattern["affected_barangays"],
                "alert_level" => "CRITICAL",
                "message" => "POSSIBLE OUTBREAK: " . $pattern["disease"] . " detected in " . $pattern["barangay_count"] . " barangays with " . $pattern["total_requests"] . " total cases"
            ];
        }
    }
    
    $response = [
        "outbreak_alerts" => $outbreak_alerts,
        "symptom_patterns" => $symptom_patterns,
        "total_patterns" => count($symptom_patterns),
        "outbreak_count" => count($outbreak_alerts),
        "detection_time" => date("Y-m-d H:i:s"),
        "criteria" => [
            "minimum_people_per_barangay" => 2,
            "minimum_affected_barangays" => 10,
            "total_barangays_in_city" => 24
        ],
        "total_requests" => count($requests),
        "valid_groups" => count($valid_groups)
    ];
    
    ob_clean();
    echo json_encode($response);
    ob_end_flush();
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode(["error" => "Failed to fetch symptom data: " . $e->getMessage()]);
    ob_end_flush();
}

// Helper function to detect specific disease based on symptoms (Filipino and English)
function detectSpecificDisease($symptoms, $species) {
    $symptoms_lower = strtolower($symptoms);
    $species_lower = strtolower($species);
    
    // African Swine Fever (ASF) - Swine
    if ($species_lower === 'swine' || $species_lower === 'pig') {
        if (strpos($symptoms_lower, "sudden death") !== false || 
            strpos($symptoms_lower, "biglaang pagkamatay") !== false ||
            strpos($symptoms_lower, "high fever") !== false ||
            strpos($symptoms_lower, "mataas na lagnat") !== false ||
            strpos($symptoms_lower, "loss of appetite") !== false ||
            strpos($symptoms_lower, "kawalan ng ganang kumain") !== false) {
            return "African Swine Fever (ASF)";
        }
    }
    
    // Newcastle Disease - Poultry (Chicken)
    if ($species_lower === 'chicken' || $species_lower === 'poultry') {
        if (strpos($symptoms_lower, "sudden death") !== false || 
            strpos($symptoms_lower, "biglaang pagkamatay") !== false ||
            strpos($symptoms_lower, "difficulty breathing") !== false ||
            strpos($symptoms_lower, "hirap sa paghinga") !== false ||
            strpos($symptoms_lower, "breathing difficulties") !== false ||
            strpos($symptoms_lower, "convulsions") !== false ||
            strpos($symptoms_lower, "kombulsyon") !== false) {
            return "Newcastle Disease";
        }
    }
    
    // Anthrax - Cattle, Carabao
    if ($species_lower === 'cow' || $species_lower === 'cattle' || $species_lower === 'carabao') {
        if (strpos($symptoms_lower, "sudden death") !== false || 
            strpos($symptoms_lower, "biglaang pagkamatay") !== false ||
            strpos($symptoms_lower, "high fever") !== false ||
            strpos($symptoms_lower, "mataas na lagnat") !== false ||
            strpos($symptoms_lower, "swollen") !== false ||
            strpos($symptoms_lower, "pamamaga") !== false) {
            return "Anthrax";
        }
    }
    
    // Foot and Mouth Disease (FMD) - Cattle, Carabao, Goat, Swine
    if (in_array($species_lower, ['cow', 'cattle', 'carabao', 'goat', 'swine', 'pig'])) {
        if (strpos($symptoms_lower, "high fever") !== false ||
            strpos($symptoms_lower, "mataas na lagnat") !== false ||
            strpos($symptoms_lower, "swollen") !== false ||
            strpos($symptoms_lower, "pamamaga") !== false ||
            strpos($symptoms_lower, "loss of appetite") !== false ||
            strpos($symptoms_lower, "kawalan ng ganang kumain") !== false) {
            return "Foot and Mouth Disease (FMD)";
        }
    }
    
    // Avian Influenza - Poultry
    if ($species_lower === 'chicken' || $species_lower === 'poultry') {
        if (strpos($symptoms_lower, "high fever") !== false ||
            strpos($symptoms_lower, "mataas na lagnat") !== false ||
            strpos($symptoms_lower, "difficulty breathing") !== false ||
            strpos($symptoms_lower, "hirap sa paghinga") !== false ||
            strpos($symptoms_lower, "loss of appetite") !== false ||
            strpos($symptoms_lower, "kawalan ng ganang kumain") !== false) {
            return "Avian Influenza (Bird Flu)";
        }
    }
    
    // Brucellosis - Cattle, Goat
    if (in_array($species_lower, ['cow', 'cattle', 'goat'])) {
        if (strpos($symptoms_lower, "high fever") !== false ||
            strpos($symptoms_lower, "mataas na lagnat") !== false ||
            strpos($symptoms_lower, "swollen") !== false ||
            strpos($symptoms_lower, "pamamaga") !== false) {
            return "Brucellosis";
        }
    }
    
    // Porcine Reproductive and Respiratory Syndrome (PRRS) - Swine
    if ($species_lower === 'swine' || $species_lower === 'pig') {
        if (strpos($symptoms_lower, "difficulty breathing") !== false ||
            strpos($symptoms_lower, "hirap sa paghinga") !== false ||
            strpos($symptoms_lower, "breathing difficulties") !== false ||
            strpos($symptoms_lower, "high fever") !== false ||
            strpos($symptoms_lower, "mataas na lagnat") !== false) {
            return "Porcine Reproductive and Respiratory Syndrome (PRRS)";
        }
    }
    
    // Leptospirosis - All animals
    if (strpos($symptoms_lower, "sudden death") !== false || 
        strpos($symptoms_lower, "biglaang pagkamatay") !== false ||
        strpos($symptoms_lower, "high fever") !== false ||
        strpos($symptoms_lower, "mataas na lagnat") !== false ||
        strpos($symptoms_lower, "diarrhea") !== false ||
        strpos($symptoms_lower, "pagtatae") !== false) {
        return "Leptospirosis";
    }
    
    // Botulism - All animals
    if (strpos($symptoms_lower, "paralysis") !== false ||
        strpos($symptoms_lower, "pagkaparalisa") !== false ||
        strpos($symptoms_lower, "sudden death") !== false ||
        strpos($symptoms_lower, "biglaang pagkamatay") !== false) {
        return "Botulism";
    }
    
    // Colibacillosis - Poultry
    if ($species_lower === 'chicken' || $species_lower === 'poultry') {
        if (strpos($symptoms_lower, "diarrhea") !== false ||
            strpos($symptoms_lower, "pagtatae") !== false ||
            strpos($symptoms_lower, "bloody diarrhea") !== false ||
            strpos($symptoms_lower, "pagtatae na may dugo") !== false) {
            return "Colibacillosis";
        }
    }
    
    // Salmonellosis - Poultry
    if ($species_lower === 'chicken' || $species_lower === 'poultry') {
        if (strpos($symptoms_lower, "diarrhea") !== false ||
            strpos($symptoms_lower, "pagtatae") !== false ||
            strpos($symptoms_lower, "loss of appetite") !== false ||
            strpos($symptoms_lower, "kawalan ng ganang kumain") !== false) {
            return "Salmonellosis";
        }
    }
    
    // Default fallback based on severity
    if (strpos($symptoms_lower, "sudden death") !== false || 
        strpos($symptoms_lower, "biglaang pagkamatay") !== false ||
        strpos($symptoms_lower, "paralysis") !== false ||
        strpos($symptoms_lower, "pagkaparalisa") !== false) {
        return "Unknown Critical Disease";
    }
    
    if (strpos($symptoms_lower, "difficulty breathing") !== false || 
        strpos($symptoms_lower, "hirap sa paghinga") !== false ||
        strpos($symptoms_lower, "breathing difficulties") !== false) {
        return "Respiratory Disease";
    }
    
    if (strpos($symptoms_lower, "high fever") !== false || 
        strpos($symptoms_lower, "mataas na lagnat") !== false ||
        strpos($symptoms_lower, "diarrhea") !== false ||
        strpos($symptoms_lower, "pagtatae") !== false) {
        return "Digestive Disease";
    }
    
    return "General Illness";
}

// Helper function to get severity level (Filipino and English)
function getSeverityLevel($symptoms) {
    $symptoms_lower = strtolower($symptoms);
    
    if (strpos($symptoms_lower, "sudden death") !== false || 
        strpos($symptoms_lower, "biglaang pagkamatay") !== false ||
        strpos($symptoms_lower, "paralysis") !== false ||
        strpos($symptoms_lower, "pagkaparalisa") !== false) {
        return "critical";
    }
    
    if (strpos($symptoms_lower, "difficulty breathing") !== false || 
        strpos($symptoms_lower, "hirap sa paghinga") !== false ||
        strpos($symptoms_lower, "breathing difficulties") !== false ||
        strpos($symptoms_lower, "convulsions") !== false ||
        strpos($symptoms_lower, "kombulsyon") !== false ||
        strpos($symptoms_lower, "seizures") !== false ||
        strpos($symptoms_lower, "tremors") !== false ||
        strpos($symptoms_lower, "panginginig") !== false) {
        return "high";
    }
    
    if (strpos($symptoms_lower, "high fever") !== false || 
        strpos($symptoms_lower, "mataas na lagnat") !== false ||
        strpos($symptoms_lower, "diarrhea") !== false ||
        strpos($symptoms_lower, "pagtatae") !== false ||
        strpos($symptoms_lower, "bloody diarrhea") !== false ||
        strpos($symptoms_lower, "pagtatae na may dugo") !== false ||
        strpos($symptoms_lower, "vomiting") !== false ||
        strpos($symptoms_lower, "pagsusuka") !== false ||
        strpos($symptoms_lower, "bloody vomiting") !== false ||
        strpos($symptoms_lower, "pagsusuka na may dugo") !== false) {
        return "medium";
    }
    
    return "low";
}

$conn->close();
?>
