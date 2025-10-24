<?php
/**
 * Health Risk Assessment - Flask API Connector
 * Calls Flask API for ML-based health risk predictions
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Start output buffering
if (!ob_get_level()) {
    ob_start();
}

// Suppress warnings
@ini_set('display_errors', '0');
@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

try {
    $action = $_GET['action'] ?? 'predict';
    $animal_id = isset($_GET['animal_id']) ? intval($_GET['animal_id']) : null;
    
    // Flask API base URL
    $flask_base_url = 'http://localhost:5000';
    
    if ($action === 'assess' && $animal_id) {
        // Assess specific animal by ID
        $flask_url = "{$flask_base_url}/api/health/assess/{$animal_id}";
        
        $ch = curl_init($flask_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        
    } elseif ($action === 'predict') {
        // Predict from provided data
        $input_data = file_get_contents('php://input');
        $data = json_decode($input_data, true);
        
        if (!$data) {
            throw new Exception('No data provided');
        }
        
        $flask_url = "{$flask_base_url}/api/health/predict";
        
        $ch = curl_init($flask_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $input_data);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($input_data)
        ));
        
    } else {
        throw new Exception('Invalid action or missing animal_id');
    }
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Check for errors
    if ($curl_error) {
        throw new Exception("Flask API connection failed: $curl_error. Ensure Flask server is running.");
    }
    
    if ($http_code !== 200) {
        throw new Exception("Flask API returned HTTP $http_code");
    }
    
    // Parse response
    $result = json_decode($response, true);
    
    if ($result === null) {
        throw new Exception("Invalid JSON from Flask API");
    }
    
    // Return result
    if (ob_get_length()) { ob_clean(); }
    echo json_encode($result);
    if (ob_get_level()) { ob_end_flush(); }
    
} catch (Exception $e) {
    // Fallback to PHP implementation
    http_response_code(200);
    if (ob_get_length()) { ob_clean(); }
    
    // Try to include PHP fallback
    $fallback_file = __DIR__ . '/../../includes/ml_health_risk_assessor.php';
    
    if (file_exists($fallback_file)) {
        // Use PHP-based ML assessment
        require_once __DIR__ . '/../../includes/conn.php';
        require_once $fallback_file;
        
        try {
            $ml_assessor = new MLHealthRiskAssessor($conn);
            
            if ($action === 'assess' && $animal_id) {
                $result = $ml_assessor->assessAnimalHealthRiskML($animal_id);
            } else {
                $result = ['error' => 'Invalid request'];
            }
            
            echo json_encode($result);
            
        } catch (Exception $e2) {
            echo json_encode([
                'success' => false,
                'error' => 'Both Flask and PHP fallback failed',
                'flask_error' => $e->getMessage(),
                'php_error' => $e2->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Flask API unavailable and no fallback found',
            'message' => $e->getMessage(),
            'help' => 'Start Flask server with: cd ml_system && start_flask.bat'
        ]);
    }
    
    if (ob_get_level()) { ob_end_flush(); }
}
?>

