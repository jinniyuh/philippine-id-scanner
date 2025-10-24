<?php
header("Content-Type: application/json");

try {
    require_once "includes/conn.php";
    
    // Function to analyze symptoms and assign risk level
    function getSymptomBasedRiskLevel($symptoms) {
        $symptoms_lower = strtolower($symptoms);
        
        // Critical symptoms - immediate danger
        $critical_symptoms = [
            'sudden death', 'biglaang pagkamatay',
            'bloody diarrhea', 'pagtatae na may dugo',
            'bloody vomiting', 'pagsusuka na may dugo',
            'convulsions', 'kombulsyon',
            'paralysis', 'paralisis',
            'difficulty breathing', 'hirap sa paghinga',
            'respiratory distress', 'pagkakahirap sa paghinga'
        ];
        
        // High risk symptoms - serious but treatable
        $high_symptoms = [
            'high fever', 'mataas na lagnat',
            'severe diarrhea', 'malubhang pagtatae',
            'vomiting', 'pagsusuka',
            'weakness', 'panghihina',
            'loss of appetite', 'kawalan ng ganang kumain',
            'swollen neck', 'namamagang leeg',
            'breathing difficulties', 'hirap sa paghinga'
        ];
        
        // Medium risk symptoms - concerning but manageable
        $medium_symptoms = [
            'fever', 'lagnat',
            'diarrhea', 'pagtatae',
            'lethargy', 'pagkakalanta',
            'coughing', 'ubo',
            'runny nose', 'baradong ilong'
        ];
        
        // Check for critical symptoms first
        foreach ($critical_symptoms as $symptom) {
            if (strpos($symptoms_lower, $symptom) !== false) {
                return 'Critical';
            }
        }
        
        // Enhanced logic: Check for multiple high-risk symptoms (Critical)
        $high_symptom_count = 0;
        foreach ($high_symptoms as $symptom) {
            if (strpos($symptoms_lower, $symptom) !== false) {
                $high_symptom_count++;
            }
        }
        
        // If 2 or more high-risk symptoms, classify as Critical
        if ($high_symptom_count >= 2) {
            return 'Critical';
        }
        
        // Check for single high risk symptoms
        foreach ($high_symptoms as $symptom) {
            if (strpos($symptoms_lower, $symptom) !== false) {
                return 'High';
            }
        }
        
        // Check for medium risk symptoms
        foreach ($medium_symptoms as $symptom) {
            if (strpos($symptoms_lower, $symptom) !== false) {
                return 'Medium';
            }
        }
        
        // Default to low risk if no specific symptoms found
        return 'Low';
    }
    
    // Get list of animals with their latest assessments
    $sql = "SELECT 
                lp.animal_id,
                lp.species as animal_name,
                lp.species as animal_type,
                lp.health_status,
                lp.quantity,
                c.full_name as client_name,
                c.barangay,
                hra.risk_level as last_risk_level,
                hra.assessment_date as last_assessment
            FROM livestock_poultry lp
            JOIN clients c ON lp.client_id = c.client_id
            LEFT JOIN (
                SELECT animal_id, risk_level, assessment_date,
                       ROW_NUMBER() OVER (PARTITION BY animal_id ORDER BY assessment_date DESC) as rn
                FROM health_risk_assessments
            ) hra ON lp.animal_id = hra.animal_id AND hra.rn = 1
            ORDER BY lp.animal_id DESC
            LIMIT 20";
    
    $result = $conn->query($sql);
    $animals = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // If no formal assessment exists, use symptom-based risk level
            if (empty($row['last_risk_level']) && !empty($row['health_status'])) {
                $row['last_risk_level'] = getSymptomBasedRiskLevel($row['health_status']);
                $row['last_assessment'] = 'Symptom-based'; // Indicate this is based on symptoms
            }
            $animals[] = $row;
        }
    }
    
    echo json_encode([
        'success' => true,
        'animals' => $animals,
        'count' => count($animals),
        'generated_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load animal list: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
