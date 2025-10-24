<?php
/**
 * PHP Client for Philippine ID Scanner Flask API
 * Handles communication with the Flask API for ID scanning
 */

class FlaskAPIClient {
    private $api_url;
    private $timeout;
    
    public function __construct($api_url = 'http://localhost:5000', $timeout = 30) {
        $this->api_url = rtrim($api_url, '/');
        $this->timeout = $timeout;
    }
    
    /**
     * Scan ID from uploaded file
     */
    public function scanIdFromFile($file_path) {
        if (!file_exists($file_path)) {
            return [
                'success' => false,
                'error' => 'File does not exist: ' . $file_path
            ];
        }
        
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->api_url . '/api/scan-id',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'image' => new CURLFile($file_path)
            ]
        ]);
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        if ($error) {
            return [
                'success' => false,
                'error' => 'cURL error: ' . $error
            ];
        }
        
        if ($http_code !== 200) {
            return [
                'success' => false,
                'error' => 'HTTP error: ' . $http_code
            ];
        }
        
        $result = json_decode($response, true);
        return $result ?: [
            'success' => false,
            'error' => 'Invalid JSON response'
        ];
    }
    
    /**
     * Scan ID from base64 encoded image
     */
    public function scanIdFromBase64($base64_image) {
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->api_url . '/api/scan-id-base64',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'image' => $base64_image
            ])
        ]);
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        if ($error) {
            return [
                'success' => false,
                'error' => 'cURL error: ' . $error
            ];
        }
        
        if ($http_code !== 200) {
            return [
                'success' => false,
                'error' => 'HTTP error: ' . $http_code
            ];
        }
        
        $result = json_decode($response, true);
        return $result ?: [
            'success' => false,
            'error' => 'Invalid JSON response'
        ];
    }
    
    /**
     * Validate name and barangay against scanned data
     */
    public function validateName($entered_name, $entered_barangay, $scanned_name, $scanned_barangay) {
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->api_url . '/api/validate-name',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'entered_name' => $entered_name,
                'entered_barangay' => $entered_barangay,
                'scanned_name' => $scanned_name,
                'scanned_barangay' => $scanned_barangay
            ])
        ]);
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        if ($error) {
            return [
                'success' => false,
                'error' => 'cURL error: ' . $error
            ];
        }
        
        if ($http_code !== 200) {
            return [
                'success' => false,
                'error' => 'HTTP error: ' . $http_code
            ];
        }
        
        $result = json_decode($response, true);
        return $result ?: [
            'success' => false,
            'error' => 'Invalid JSON response'
        ];
    }
    
    /**
     * Check if the Flask API is running
     */
    public function healthCheck() {
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->api_url . '/api/health',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 5
        ]);
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        if ($error || $http_code !== 200) {
            return false;
        }
        
        $result = json_decode($response, true);
        return $result && isset($result['status']) && $result['status'] === 'healthy';
    }
}

// Example usage and testing
if (basename($_SERVER['PHP_SELF']) === 'flask_api_client.php') {
    echo "<h2>Flask API Client Test</h2>";
    
    $client = new FlaskAPIClient();
    
    // Test health check
    echo "<h3>Health Check</h3>";
    if ($client->healthCheck()) {
        echo "<p style='color: green;'>✅ Flask API is running</p>";
    } else {
        echo "<p style='color: red;'>❌ Flask API is not running</p>";
        echo "<p>Please start the Flask API first:</p>";
        echo "<ul>";
        echo "<li>Windows: Run <code>start_flask_api.bat</code></li>";
        echo "<li>Linux: Run <code>./start_flask_api.sh</code></li>";
        echo "<li>Manual: <code>python flask_id_scanner.py</code></li>";
        echo "</ul>";
    }
    
    // Test validation
    echo "<h3>Validation Test</h3>";
    $validation_result = $client->validateName(
        'JANNAH MARIE CARMONA',
        'POBLACION',
        'JANNAH MARIE CARMONA',
        'POBLACION'
    );
    
    if ($validation_result['success']) {
        echo "<p style='color: green;'>✅ Validation test passed</p>";
    } else {
        echo "<p style='color: red;'>❌ Validation test failed: " . $validation_result['error'] . "</p>";
    }
    
    echo "<h3>Usage Example</h3>";
    echo "<pre>";
    echo htmlspecialchars('
// Initialize the client
$client = new FlaskAPIClient();

// Scan ID from uploaded file
$result = $client->scanIdFromFile("path/to/id_image.jpg");

// Scan ID from base64 image
$result = $client->scanIdFromBase64($base64_image);

// Validate name and barangay
$validation = $client->validateName(
    $entered_name,
    $entered_barangay,
    $scanned_name,
    $scanned_barangay
);

if ($validation["success"]) {
    echo "Validation passed!";
} else {
    echo "Validation failed: " . $validation["error"];
}
    ');
    echo "</pre>";
}
?>
