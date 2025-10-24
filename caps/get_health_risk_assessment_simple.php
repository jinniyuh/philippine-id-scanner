<?php
/**
 * Simplified Health Risk Assessment API with better error handling
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header first
header('Content-Type: application/json');

// Function to determine risk level based on symptoms
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
    
    // Check for high risk symptoms
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

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
        throw new Exception('Unauthorized - please login as admin');
    }
    
    // Include database connection
    if (!file_exists('includes/conn.php')) {
        throw new Exception('Database connection file not found');
    }
    
    include 'includes/conn.php';
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Check if health risk assessor exists
    if (!file_exists('includes/health_risk_assessor.php')) {
        throw new Exception('Health risk assessor file not found');
    }
    
    include 'includes/health_risk_assessor.php';
    
    if (!class_exists('HealthRiskAssessor')) {
        throw new Exception('HealthRiskAssessor class not found');
    }
    
    $assessor = new HealthRiskAssessor($conn);
    $action = $_GET['action'] ?? 'summary';
    
    switch ($action) {
        case 'summary':
            // Get risk assessment summary
            $summary = $assessor->getRiskAssessmentSummary();
            
            echo json_encode([
                'success' => true,
                'summary' => $summary,
                'generated_at' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'high_risk':
            // Get high-risk animals
            $limit = $_GET['limit'] ?? 10;
            $high_risk_animals = $assessor->getHighRiskAnimals($limit);
            
            echo json_encode([
                'success' => true,
                'high_risk_animals' => $high_risk_animals,
                'count' => count($high_risk_animals),
                'generated_at' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'animal_list':
            // Get list of animals from pharmaceutical requests
            $sql = "SELECT 
                        pr.request_id as animal_id,
                        CONCAT(c.full_name, ' - ', pr.species) as animal_name,
                        pr.species as animal_type,
                        pr.symptoms as health_status,
                        pr.quantity,
                        c.full_name as client_name,
                        c.barangay,
                        hra.risk_level as last_risk_level,
                        hra.assessment_date as last_assessment
                    FROM pharmaceutical_requests pr
                    JOIN clients c ON pr.client_id = c.client_id
                    LEFT JOIN (
                        SELECT request_id, risk_level, assessment_date,
                               ROW_NUMBER() OVER (PARTITION BY request_id ORDER BY assessment_date DESC) as rn
                        FROM health_risk_assessments
                    ) hra ON pr.request_id = hra.request_id AND hra.rn = 1
                    WHERE pr.symptoms IS NOT NULL 
                    AND pr.symptoms != ''
                    ORDER BY pr.request_id DESC
                    LIMIT 50";
            
            $result = $conn->query($sql);
            $animals = [];
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $animals[] = $row;
                }
            }
            
            echo json_encode([
                'success' => true,
                'animals' => $animals,
                'count' => count($animals),
                'generated_at' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'assess':
            // Assess specific animal using improved symptom-based logic
            $animal_id = $_GET['animal_id'] ?? null;
            if (!$animal_id) {
                throw new Exception('Animal ID is required');
            }
            
            // Get animal data from pharmaceutical requests
            $animal_sql = "SELECT 
                            pr.request_id as animal_id,
                            pr.species,
                            pr.symptoms as health_status,
                            pr.quantity,
                            c.full_name as client_name,
                            c.barangay
                          FROM pharmaceutical_requests pr
                          JOIN clients c ON pr.client_id = c.client_id
                          WHERE pr.request_id = ?";
            
            $stmt = $conn->prepare($animal_sql);
            $stmt->bind_param("i", $animal_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Animal not found');
            }
            
            $animal = $result->fetch_assoc();
            
            // Use our improved symptom-based risk assessment
            $symptoms = $animal['health_status'];
            $risk_level = getSymptomBasedRiskLevel($symptoms);
            $risk_score = $risk_level === 'Critical' ? 90 : 
                         ($risk_level === 'High' ? 75 : 
                         ($risk_level === 'Medium' ? 50 : 25));
            
            // Generate risk factors based on symptoms
            $risk_factors = [];
            if ($risk_level === 'Critical') {
                $risk_factors[] = 'Life-threatening symptoms present';
                $risk_factors[] = 'Immediate veterinary intervention required';
                $risk_factors[] = 'High mortality risk';
            } elseif ($risk_level === 'High') {
                $risk_factors[] = 'Serious symptoms requiring urgent attention';
                $risk_factors[] = 'Potential for rapid deterioration';
                $risk_factors[] = 'Contagion risk to other animals';
            } elseif ($risk_level === 'Medium') {
                $risk_factors[] = 'Moderate symptoms requiring monitoring';
                $risk_factors[] = 'Possible treatment needed';
            } else {
                $risk_factors[] = 'Mild symptoms, routine monitoring';
            }
            $risk_factors[] = 'Symptom-based assessment: ' . $symptoms;
            
            // Generate recommendations based on risk level
            $recommendations = [];
            if ($risk_level === 'Critical') {
                $recommendations[] = 'Immediate veterinary emergency care';
                $recommendations[] = 'Isolate animal from others';
                $recommendations[] = 'Monitor vital signs continuously';
                $recommendations[] = 'Prepare for possible euthanasia if condition worsens';
            } elseif ($risk_level === 'High') {
                $recommendations[] = 'Schedule urgent veterinary appointment';
                $recommendations[] = 'Isolate animal in quarantine area';
                $recommendations[] = 'Monitor closely for symptom changes';
                $recommendations[] = 'Consider antibiotic treatment';
            } elseif ($risk_level === 'Medium') {
                $recommendations[] = 'Schedule routine veterinary check-up';
                $recommendations[] = 'Monitor symptoms daily';
                $recommendations[] = 'Ensure proper nutrition and hydration';
            } else {
                $recommendations[] = 'Continue routine monitoring';
                $recommendations[] = 'Maintain current health protocols';
            }
            
            // Species-specific recommendations
            $species = $animal['species'];
            if (strtolower($species) === 'swine' || strtolower($species) === 'pig') {
                $recommendations[] = 'Check for African Swine Fever symptoms';
            } elseif (strtolower($species) === 'chicken' || strtolower($species) === 'poultry') {
                $recommendations[] = 'Check for Newcastle Disease or Avian Influenza';
            } elseif (strtolower($species) === 'cow' || strtolower($species) === 'cattle') {
                $recommendations[] = 'Check for Foot and Mouth Disease symptoms';
            }
            
            $assessment = [
                'animal_id' => $animal_id,
                'risk_score' => $risk_score,
                'risk_level' => $risk_level,
                'risk_factors' => $risk_factors,
                'recommendations' => $recommendations,
                'confidence' => 85, // High confidence for symptom-based assessment
                'assessment_date' => date('Y-m-d H:i:s'),
                'species' => $species,
                'symptoms' => $symptoms
            ];
            
            // Save the improved assessment
            $save_stmt = $conn->prepare("INSERT INTO health_risk_assessments 
                (request_id, client_id, risk_score, risk_level, risk_factors, recommendations, assessed_by, assessment_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            
            // Get client_id from the pharmaceutical request
            $client_sql = "SELECT client_id FROM pharmaceutical_requests WHERE request_id = ?";
            $client_stmt = $conn->prepare($client_sql);
            $client_stmt->bind_param("i", $animal_id);
            $client_stmt->execute();
            $client_result = $client_stmt->get_result();
            $client_data = $client_result->fetch_assoc();
            $client_id = $client_data['client_id'] ?? null;
            
            $assessed_by = $_SESSION['user_id'] ?? 1;
            $save_stmt->bind_param("iidsssi", 
                $animal_id, 
                $client_id, 
                $risk_score, 
                $risk_level, 
                json_encode($risk_factors), 
                json_encode($recommendations), 
                $assessed_by
            );
            $save_stmt->execute();
            
            echo json_encode([
                'success' => true,
                'assessment' => $assessment,
                'generated_at' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'bulk_assess':
            // Assess multiple animals
            $animal_ids = $_GET['animal_ids'] ?? [];
            if (is_string($animal_ids)) {
                $animal_ids = explode(',', $animal_ids);
            }
            
            if (empty($animal_ids)) {
                throw new Exception('Animal IDs are required');
            }
            
            $assessments = [];
            foreach ($animal_ids as $animal_id) {
                $assessment = $assessor->assessAnimalHealthRisk($animal_id);
                if (!isset($assessment['error'])) {
                    $assessor->saveAssessment($assessment, $_SESSION['user_id']);
                    $assessments[] = $assessment;
                }
            }
            
            echo json_encode([
                'success' => true,
                'assessments' => $assessments,
                'count' => count($assessments),
                'generated_at' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'get_assessment_history':
            // Get assessment history for an animal
            $animal_id = $_GET['animal_id'] ?? null;
            if (!$animal_id) {
                throw new Exception('Animal ID is required');
            }
            
            $sql = "SELECT 
                        hra.*,
                        u.name as assessed_by_name
                    FROM health_risk_assessments hra
                    LEFT JOIN users u ON hra.assessed_by = u.user_id
                    WHERE hra.animal_id = ? 
                    ORDER BY hra.assessment_date DESC
                    LIMIT 20";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $animal_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $assessments = [];
            while ($row = $result->fetch_assoc()) {
                $row['risk_factors'] = json_decode($row['risk_factors'], true);
                $row['recommendations'] = json_decode($row['recommendations'], true);
                $assessments[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'assessments' => $assessments,
                'count' => count($assessments)
            ]);
            break;
            
        default:
            throw new Exception('Invalid action: ' . $action);
    }
    
} catch (Exception $e) {
    // Return error as JSON
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
