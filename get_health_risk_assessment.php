<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'includes/conn.php';
include 'includes/health_risk_assessor.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$assessor = new HealthRiskAssessor($conn);

try {
    $action = $_GET['action'] ?? 'assess';
    
    switch ($action) {
        case 'assess':
            // Assess specific animal
            $animal_id = $_GET['animal_id'] ?? null;
            if (!$animal_id) {
                throw new Exception('Animal ID is required');
            }
            
            $assessment = $assessor->assessAnimalHealthRisk($animal_id);
            
            // Save assessment to database
            if (!isset($assessment['error'])) {
                $assessor->saveAssessment($assessment, $_SESSION['user_id']);
            }
            
            echo json_encode([
                'success' => true,
                'assessment' => $assessment,
                'generated_at' => date('Y-m-d H:i:s')
            ]);
            break;
            
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
            
        case 'animal_list':
            // Get list of animals for assessment
            $sql = "SELECT 
                        lp.animal_id,
                        lp.animal_name,
                        lp.animal_type,
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
                    ORDER BY lp.animal_id DESC";
            
            $result = $conn->query($sql);
            $animals = [];
            
            while ($row = $result->fetch_assoc()) {
                $animals[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'animals' => $animals,
                'count' => count($animals),
                'generated_at' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'add_health_indicator':
            // Add new health indicator
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['animal_id'], $input['indicator_type'], $input['indicator_value'])) {
                throw new Exception('Required fields: animal_id, indicator_type, indicator_value');
            }
            
            $sql = "INSERT INTO health_indicators 
                    (animal_id, indicator_type, indicator_value, indicator_unit, recorded_by, notes) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssis",
                $input['animal_id'],
                $input['indicator_type'],
                $input['indicator_value'],
                $input['indicator_unit'] ?? null,
                $_SESSION['user_id'],
                $input['notes'] ?? null
            );
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Health indicator added successfully',
                    'indicator_id' => $conn->insert_id
                ]);
            } else {
                throw new Exception('Failed to add health indicator');
            }
            break;
            
        case 'get_health_indicators':
            // Get health indicators for an animal
            $animal_id = $_GET['animal_id'] ?? null;
            if (!$animal_id) {
                throw new Exception('Animal ID is required');
            }
            
            $sql = "SELECT * FROM health_indicators 
                    WHERE animal_id = ? 
                    ORDER BY recorded_date DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $animal_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $indicators = [];
            while ($row = $result->fetch_assoc()) {
                $indicators[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'indicators' => $indicators,
                'count' => count($indicators)
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
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
