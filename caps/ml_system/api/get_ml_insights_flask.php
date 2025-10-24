<?php
/**
 * ML Insights Endpoint - Flask API Version
 * Calls Flask API to get ML insights
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

// Check authentication
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(200);
    if (ob_get_length()) { ob_clean(); }
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    if (ob_get_level()) { ob_end_flush(); }
    exit();
}

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

try {
    // Flask API URL
    $flask_url = 'http://localhost:5000/api/insights';
    
    // Initialize cURL
    $ch = curl_init($flask_url);
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Accept: application/json'
    ));
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    
    curl_close($ch);
    
    // Check for cURL errors
    if ($curl_error) {
        throw new Exception("Flask API connection failed: $curl_error. Make sure Flask server is running on port 5000.");
    }
    
    // Check HTTP status
    if ($http_code !== 200) {
        throw new Exception("Flask API returned HTTP $http_code");
    }
    
    // Parse JSON response
    $data = json_decode($response, true);
    
    if ($data === null) {
        throw new Exception("Invalid JSON from Flask API");
    }
    
    // Check if Flask reported an error
    if (isset($data['success']) && $data['success'] === false) {
        throw new Exception($data['error'] ?? 'Flask API returned error');
    }
    
    // Add summary metrics if not present
    if (!isset($data['summary_metrics'])) {
        $data['summary_metrics'] = [
            'forecast_accuracy' => 'N/A',
            'critical_alerts_count' => 0,
            'total_data_points' => 0,
            'model_version' => 'Flask ML API'
        ];
    }
    
    // Clean output and send response
    if (ob_get_length()) { ob_clean(); }
    echo json_encode($data);
    if (ob_get_level()) { ob_end_flush(); }
    
} catch (Exception $e) {
    // Fallback to PHP implementation
    http_response_code(200);
    if (ob_get_length()) { ob_clean(); }
    
    // Try to use PHP fallback
    $fallback_file = __DIR__ . '/get_ml_insights_enhanced.php';
    if (file_exists($fallback_file)) {
        // Include and execute PHP fallback
        include $fallback_file;
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Flask API unavailable and no fallback found',
            'message' => $e->getMessage(),
            'help' => 'Start Flask server with: python ml_flask_api.py'
        ]);
    }
    
    if (ob_get_level()) { ob_end_flush(); }
}
?>

