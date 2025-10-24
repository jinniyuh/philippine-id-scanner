<?php
/**
 * Minimal Health Risk Assessment API - guaranteed to work
 */

// Start session
session_start();

// Set JSON header
header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
        throw new Exception('Unauthorized - please login as admin');
    }
    
    $action = $_GET['action'] ?? 'summary';
    
    switch ($action) {
        case 'summary':
            // Return a simple summary
            echo json_encode([
                'success' => true,
                'summary' => [
                    'Low' => ['count' => 5, 'avg_score' => 25.5],
                    'Medium' => ['count' => 3, 'avg_score' => 45.2],
                    'High' => ['count' => 2, 'avg_score' => 65.8],
                    'Critical' => ['count' => 1, 'avg_score' => 85.3]
                ],
                'generated_at' => date('Y-m-d H:i:s'),
                'note' => 'This is test data - run setup script for real data'
            ]);
            break;
            
        case 'high_risk':
            // Return sample high-risk animals
            echo json_encode([
                'success' => true,
                'high_risk_animals' => [
                    [
                        'animal_id' => 1,
                        'animal_name' => 'Sample Cow 1',
                        'animal_type' => 'Livestock',
                        'risk_score' => 85.5,
                        'risk_level' => 'Critical',
                        'client_name' => 'Sample Client',
                        'barangay' => 'Sample Barangay',
                        'assessment_date' => date('Y-m-d H:i:s')
                    ],
                    [
                        'animal_id' => 2,
                        'animal_name' => 'Sample Chicken 1',
                        'animal_type' => 'Poultry',
                        'risk_score' => 72.3,
                        'risk_level' => 'High',
                        'client_name' => 'Sample Client 2',
                        'barangay' => 'Sample Barangay 2',
                        'assessment_date' => date('Y-m-d H:i:s')
                    ]
                ],
                'count' => 2,
                'generated_at' => date('Y-m-d H:i:s'),
                'note' => 'This is test data - run setup script for real data'
            ]);
            break;
            
        case 'animal_list':
            // Return sample animal list
            echo json_encode([
                'success' => true,
                'animals' => [
                    [
                        'animal_id' => 1,
                        'animal_name' => 'Sample Cow 1',
                        'animal_type' => 'Livestock',
                        'health_status' => 'Healthy',
                        'client_name' => 'Sample Client',
                        'barangay' => 'Sample Barangay',
                        'last_risk_level' => 'Low',
                        'last_assessment' => date('Y-m-d H:i:s')
                    ],
                    [
                        'animal_id' => 2,
                        'animal_name' => 'Sample Chicken 1',
                        'animal_type' => 'Poultry',
                        'health_status' => 'Healthy',
                        'client_name' => 'Sample Client 2',
                        'barangay' => 'Sample Barangay 2',
                        'last_risk_level' => 'Medium',
                        'last_assessment' => date('Y-m-d H:i:s')
                    ]
                ],
                'count' => 2,
                'generated_at' => date('Y-m-d H:i:s'),
                'note' => 'This is test data - run setup script for real data'
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
