<?php
/**
 * ML-Enhanced Health Risk Assessor
 * Integrates machine learning predictions with existing health risk monitoring
 */

class MLHealthRiskAssessor {
    private $conn;
    private $python_path;
    private $use_flask_api;
    
    public function __construct($conn, $use_flask_api = true) {
        $this->conn = $conn;
        $this->python_path = 'python'; // Adjust path as needed
        $this->use_flask_api = $use_flask_api;
    }
    
    /**
     * Assess animal health risk using ML predictions
     */
    public function assessAnimalHealthRiskML($animal_id) {
        try {
            // Try Flask API first if enabled
            if ($this->use_flask_api) {
                $flask_result = $this->callFlaskAPI($animal_id);
                if (!isset($flask_result['error'])) {
                    return $flask_result;
                }
                // If Flask fails, continue to Python CLI method
            }
            
            // Get animal data
            $animal_data = $this->getAnimalData($animal_id);
            if (!$animal_data) {
                return ['error' => 'Animal not found'];
            }
            
            // Get recent health indicators
            $health_indicators = $this->getHealthIndicators($animal_id);
            
            // Get symptoms from pharmaceutical requests
            $symptoms = $this->getSymptomsFromRequests($animal_id);
            
            // Prepare data for ML model
            $ml_data = $this->prepareMLData($animal_data, $health_indicators, $symptoms);
            
            // Call Python ML model (CLI method)
            $ml_result = $this->callMLModel($ml_data);
            
            // Process ML result
            $risk_assessment = $this->processMLResult($ml_result, $animal_id);
            
            return $risk_assessment;
            
        } catch (Exception $e) {
            error_log("ML Health Risk Assessment failed: " . $e->getMessage());
            return ['error' => 'ML assessment failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Call Flask API for health risk assessment
     */
    private function callFlaskAPI($animal_id) {
        try {
            $flask_url = "http://localhost:5000/api/health/assess/{$animal_id}";
            
            $ch = curl_init($flask_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            if ($curl_error || $http_code !== 200) {
                return ['error' => 'Flask API unavailable'];
            }
            
            $result = json_decode($response, true);
            
            if ($result && isset($result['success']) && $result['success']) {
                // Transform Flask response to expected format
                return [
                    'animal_id' => $animal_id,
                    'risk_level' => $result['risk_level'],
                    'risk_score' => $result['risk_score'],
                    'confidence' => $result['confidence'] * 100, // Convert to percentage
                    'risk_factors' => $result['recommendations'] ?? [],
                    'recommendations' => $result['recommendations'] ?? [],
                    'ml_enhanced' => true,
                    'assessment_date' => date('Y-m-d H:i:s'),
                    'probabilities' => $result['probabilities'] ?? [],
                    'model_version' => $result['model_version'] ?? 'flask_api'
                ];
            }
            
            return ['error' => 'Invalid Flask response'];
            
        } catch (Exception $e) {
            return ['error' => 'Flask API call failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get animal data from database
     */
    private function getAnimalData($animal_id) {
        $stmt = $this->conn->prepare("
            SELECT lp.*, c.barangay, c.latitude, c.longitude 
            FROM livestock_poultry lp 
            JOIN clients c ON lp.client_id = c.client_id 
            WHERE lp.animal_id = ?
        ");
        
        if (!$stmt) {
            error_log("Prepare failed in getAnimalData: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("i", $animal_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Get health indicators for the animal
     */
    private function getHealthIndicators($animal_id) {
        $stmt = $this->conn->prepare("
            SELECT indicator_type, indicator_value, recorded_date 
            FROM health_indicators 
            WHERE animal_id = ? 
            AND recorded_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY recorded_date DESC
        ");
        
        if (!$stmt) {
            error_log("Prepare failed in getHealthIndicators: " . $this->conn->error);
            return [];
        }
        
        $stmt->bind_param("i", $animal_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get symptoms from pharmaceutical requests
     */
    private function getSymptomsFromRequests($animal_id) {
        // Note: pharmaceutical_requests may not have animal_id column directly
        // Get client_id first from animal, then match requests by client
        $animal_query = $this->conn->prepare("
            SELECT client_id FROM livestock_poultry WHERE animal_id = ?
        ");
        
        if (!$animal_query) {
            error_log("Prepare failed in getSymptomsFromRequests (animal query): " . $this->conn->error);
            return [];
        }
        
        $animal_query->bind_param("i", $animal_id);
        $animal_query->execute();
        $animal_result = $animal_query->get_result();
        $animal_data = $animal_result->fetch_assoc();
        
        if (!$animal_data) {
            return [];
        }
        
        $client_id = $animal_data['client_id'];
        
        $stmt = $this->conn->prepare("
            SELECT symptoms, species, status, request_date
            FROM pharmaceutical_requests 
            WHERE client_id = ? 
            AND status IN ('approved', 'Pending')
            AND request_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        if (!$stmt) {
            error_log("Prepare failed in getSymptomsFromRequests: " . $this->conn->error);
            return [];
        }
        
        $stmt->bind_param("i", $client_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Prepare data for ML model
     */
    private function prepareMLData($animal_data, $health_indicators, $symptoms) {
        // Extract symptoms
        $symptom_list = [];
        foreach ($symptoms as $symptom_data) {
            if (!empty($symptom_data['symptoms'])) {
                $symptom_array = explode(';', $symptom_data['symptoms']);
                foreach ($symptom_array as $symptom) {
                    $symptom = trim($symptom);
                    if (!empty($symptom)) {
                        $symptom_list[] = $symptom;
                    }
                }
            }
        }
        
        // Prepare ML input data
        $ml_data = [
            'animal_id' => $animal_data['animal_id'],
            'age' => isset($animal_data['age']) ? $animal_data['age'] : 0,
            'breed' => $animal_data['animal_type'],
            'symptoms' => $symptom_list,
            'vital_signs' => [
                'temperature' => $this->extractTemperature($health_indicators),
                'weight' => $this->extractWeight($health_indicators),
                'heart_rate' => $this->extractHeartRate($health_indicators)
            ],
            'environment' => [
                'temperature' => 25.0, // Default temperature
                'humidity' => 60.0, // Default or from weather API
                'season' => $this->getCurrentSeason(),
                'location' => $animal_data['barangay'] ?? 'Unknown'
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        return $ml_data;
    }
    
    /**
     * Call Python ML model
     */
    private function callMLModel($ml_data) {
        // Save data to temporary file
        $temp_file = tempnam(sys_get_temp_dir(), 'ml_data_');
        file_put_contents($temp_file, json_encode($ml_data));
        
        // Updated path to use organized ML system folder
        $script_path = __DIR__ . '/../ml_system/scripts/ml_predict_advanced.py';
        
        // Call Python ML script
        $command = "{$this->python_path} " . escapeshellarg($script_path) . " " . escapeshellarg($temp_file);
        
        // Check if shell_exec is available
        if (function_exists('shell_exec')) {
            $output = shell_exec($command . " 2>&1");
        } else {
            // Fallback: Use exec() if available, otherwise return default response
            if (function_exists('exec')) {
                exec($command . " 2>&1", $output_array);
                $output = implode("\n", $output_array);
            } else {
                // If neither shell_exec nor exec is available, return a safe default
                error_log("ML Health Risk Assessor: Neither shell_exec nor exec available on this server");
                unlink($temp_file);
                return [
                    'risk_level' => 'Medium',
                    'confidence' => 0.5,
                    'recommendations' => ['Manual assessment recommended - ML system unavailable'],
                    'notes' => 'ML system not available on this server'
                ];
            }
        }
        
        // Clean up
        unlink($temp_file);
        
        if (!$output) {
            return ['error' => 'ML model execution failed'];
        }
        
        $result = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("ML model JSON decode error: " . $output);
            return ['error' => 'Invalid ML model response'];
        }
        
        return $result;
    }
    
    /**
     * Process ML result into risk assessment
     */
    private function processMLResult($ml_result, $animal_id) {
        if (!$ml_result || isset($ml_result['error'])) {
            return ['error' => 'ML prediction failed'];
        }
        
        $risk_level = $ml_result['risk_level'];
        $confidence = $ml_result['confidence'];
        
        // Determine risk factors based on ML prediction
        $risk_factors = $this->identifyMLRiskFactors($ml_result);
        
        // Calculate ML-enhanced risk score
        $ml_risk_score = $this->calculateMLRiskScore($ml_result);
        
        return [
            'animal_id' => $animal_id,
            'risk_level' => $risk_level,
            'risk_score' => $ml_risk_score,
            'confidence' => $confidence,
            'risk_factors' => $risk_factors,
            'ml_enhanced' => true,
            'assessment_date' => date('Y-m-d H:i:s'),
            'probabilities' => $ml_result['probabilities'] ?? []
        ];
    }
    
    /**
     * Identify risk factors from ML prediction
     */
    private function identifyMLRiskFactors($ml_result) {
        $risk_factors = [];
        
        // Analyze ML probabilities to identify key risk factors
        if (isset($ml_result['probabilities'])) {
            $probabilities = $ml_result['probabilities'];
            
            if (isset($probabilities['Critical']) && $probabilities['Critical'] > 0.3) {
                $risk_factors[] = 'ML predicts critical risk with ' . 
                    round($probabilities['Critical'] * 100, 1) . '% confidence';
            }
            
            if (isset($probabilities['High']) && $probabilities['High'] > 0.4) {
                $risk_factors[] = 'ML predicts high risk with ' . 
                    round($probabilities['High'] * 100, 1) . '% confidence';
            }
            
            if (isset($probabilities['Medium']) && $probabilities['Medium'] > 0.5) {
                $risk_factors[] = 'ML predicts medium risk with ' . 
                    round($probabilities['Medium'] * 100, 1) . '% confidence';
            }
        }
        
        return $risk_factors;
    }
    
    /**
     * Calculate ML-enhanced risk score
     */
    private function calculateMLRiskScore($ml_result) {
        $base_score = 0;
        $confidence = $ml_result['confidence'] ?? 0.5;
        
        // Map risk levels to scores
        $risk_levels = [
            'Low' => 25,
            'Medium' => 50,
            'High' => 75,
            'Critical' => 100
        ];
        
        $risk_level = $ml_result['risk_level'] ?? 'Low';
        $base_score = $risk_levels[$risk_level] ?? 25;
        
        // Adjust score based on confidence
        $adjusted_score = $base_score * $confidence;
        
        return min(100, max(0, $adjusted_score));
    }
    
    /**
     * Calculate animal age in years
     */
    private function calculateAge($birth_date) {
        if (!$birth_date) return 0;
        $birth = new DateTime($birth_date);
        $now = new DateTime();
        return $now->diff($birth)->y;
    }
    
    /**
     * Extract temperature from health indicators
     */
    private function extractTemperature($health_indicators) {
        foreach ($health_indicators as $indicator) {
            if ($indicator['indicator_type'] === 'Temperature') {
                return floatval($indicator['indicator_value']);
            }
        }
        return 38.5; // Default normal temperature
    }
    
    /**
     * Extract weight from health indicators
     */
    private function extractWeight($health_indicators) {
        foreach ($health_indicators as $indicator) {
            if ($indicator['indicator_type'] === 'Weight') {
                return floatval($indicator['indicator_value']);
            }
        }
        return 0; // Unknown weight
    }
    
    /**
     * Extract heart rate from health indicators
     */
    private function extractHeartRate($health_indicators) {
        foreach ($health_indicators as $indicator) {
            if ($indicator['indicator_type'] === 'Heart_Rate') {
                return floatval($indicator['indicator_value']);
            }
        }
        return 0; // Unknown heart rate
    }
    
    /**
     * Get current season
     */
    private function getCurrentSeason() {
        $month = date('n');
        if (in_array($month, [12, 1, 2])) return 'winter';
        if (in_array($month, [3, 4, 5])) return 'spring';
        if (in_array($month, [6, 7, 8])) return 'summer';
        return 'autumn';
    }
    
    /**
     * Save ML assessment to database
     */
    public function saveMLAssessment($assessment, $client_id) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO health_assessments 
                (animal_id, risk_level, risk_score, risk_factors, assessment_date, assessed_by, ml_enhanced, ml_confidence) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            if (!$stmt) {
                error_log("Prepare failed in saveMLAssessment: " . $this->conn->error);
                return false;
            }
            
            $risk_factors_json = json_encode($assessment['risk_factors']);
            $ml_enhanced = $assessment['ml_enhanced'] ? 1 : 0;
            $ml_confidence = $assessment['confidence'] ?? 0.0;
            
            $stmt->bind_param("isdsisid", 
                $assessment['animal_id'],
                $assessment['risk_level'],
                $assessment['risk_score'],
                $risk_factors_json,
                $assessment['assessment_date'],
                $client_id,
                $ml_enhanced,
                $ml_confidence
            );
            
            $stmt->execute();
            return true;
            
        } catch (Exception $e) {
            error_log("Failed to save ML assessment: " . $e->getMessage());
            return false;
        }
    }
}
?>
