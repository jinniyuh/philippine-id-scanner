<?php
/**
 * Animal Health Risk Assessment ML Algorithm
 * Uses machine learning techniques to assess health risks for individual animals
 * Based on historical data, environmental factors, and health indicators
 */

// Include ML Health Risk Assessor
require_once __DIR__ . '/ml_health_risk_assessor.php';

if (!class_exists('HealthRiskAssessor')) {
    class HealthRiskAssessor {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Calculate health risk score for an animal
     * @param int $animal_id Animal ID to assess
     * @return array Risk assessment results
     */
    public function assessAnimalHealthRisk($animal_id) {
        try {
            // Try ML assessment first
            $ml_assessor = new MLHealthRiskAssessor($this->conn);
            $ml_result = $ml_assessor->assessAnimalHealthRiskML($animal_id);
            
            if (!isset($ml_result['error'])) {
                // Use ML result
                return $ml_result;
            }
            
            // Fallback to rule-based assessment
            return $this->assessAnimalHealthRiskRuleBased($animal_id);
            
        } catch (Exception $e) {
            // If ML fails, fallback to rule-based
            return $this->assessAnimalHealthRiskRuleBased($animal_id);
        }
    }
    
    /**
     * Rule-based health risk assessment (fallback)
     * @param int $animal_id Animal ID to assess
     * @return array Risk assessment results
     */
    private function assessAnimalHealthRiskRuleBased($animal_id) {
        try {
            // Get animal information
            $animal_info = $this->getAnimalInfo($animal_id);
            if (!$animal_info) {
                return ['error' => 'Animal not found'];
            }
            
            // Get health indicators
            $health_indicators = $this->getHealthIndicators($animal_id);
            
            // Get historical health data
            $historical_data = $this->getHistoricalHealthData($animal_id);
            
            // Get environmental factors
            $environmental_factors = $this->getEnvironmentalFactors($animal_id);
            
            // Calculate base risk score
            $base_risk = $this->calculateBaseRiskScore($animal_info, $health_indicators);
            
            // Apply seasonal adjustments
            $seasonal_risk = $this->applySeasonalAdjustments($base_risk, $animal_info['animal_type']);
            
            // Apply environmental risk factors
            $environmental_risk = $this->applyEnvironmentalRisk($seasonal_risk, $environmental_factors);
            
            // Apply historical pattern analysis
            $pattern_risk = $this->applyHistoricalPatterns($environmental_risk, $historical_data, $animal_info);
            
            // Final risk score calculation
            $final_risk_score = $this->calculateFinalRiskScore($pattern_risk, $health_indicators);
            
            // Determine risk level
            $risk_level = $this->determineRiskLevel($final_risk_score);
            
            // Identify risk factors
            $risk_factors = $this->identifyRiskFactors($animal_info, $health_indicators, $environmental_factors);
            
            // Generate recommendations
            $recommendations = $this->generateRecommendations($risk_level, $risk_factors, $animal_info);
            
            return [
                'animal_id' => $animal_id,
                'client_id' => $animal_info['client_id'] ?? 0,
                'risk_score' => round($final_risk_score, 2),
                'risk_level' => $risk_level,
                'risk_factors' => $risk_factors,
                'recommendations' => $recommendations,
                'assessment_date' => date('Y-m-d H:i:s'),
                'confidence' => $this->calculateConfidence($health_indicators, $historical_data),
                'ml_enhanced' => false
            ];
            
        } catch (Exception $e) {
            return ['error' => 'Assessment failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get animal information
     */
    private function getAnimalInfo($animal_id) {
        $sql = "SELECT lp.*, c.barangay, c.latitude, c.longitude 
                FROM livestock_poultry lp 
                JOIN clients c ON lp.client_id = c.client_id 
                WHERE lp.animal_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $animal_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Get health indicators for an animal
     */
    private function getHealthIndicators($animal_id) {
        $sql = "SELECT indicator_type, indicator_value, recorded_date 
                FROM health_indicators 
                WHERE animal_id = ? 
                ORDER BY recorded_date DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $animal_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $indicators = [];
        while ($row = $result->fetch_assoc()) {
            $indicators[$row['indicator_type']][] = $row;
        }
        
        return $indicators;
    }
    
    /**
     * Get historical health data
     */
    private function getHistoricalHealthData($animal_id) {
        // Get past health assessments
        $sql = "SELECT risk_score, risk_level, assessment_date 
                FROM health_risk_assessments 
                WHERE animal_id = ? 
                ORDER BY assessment_date DESC 
                LIMIT 10";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $animal_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $historical = [];
        while ($row = $result->fetch_assoc()) {
            $historical[] = $row;
        }
        
        return $historical;
    }
    
    /**
     * Get environmental factors
     */
    private function getEnvironmentalFactors($animal_id) {
        $sql = "SELECT indicator_value, recorded_date 
                FROM health_indicators 
                WHERE animal_id = ? AND indicator_type = 'Environmental_Factor' 
                ORDER BY recorded_date DESC 
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $animal_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $env_factor = $result->fetch_assoc();
        return $env_factor ? $env_factor['indicator_value'] : 'Unknown';
    }
    
    /**
     * Calculate base risk score
     */
    private function calculateBaseRiskScore($animal_info, $health_indicators) {
        $base_risk = 20; // Starting base risk
        
        // Health status factor
        if ($animal_info['health_status'] === 'Sick') {
            $base_risk += 40;
        } elseif ($animal_info['health_status'] === 'Under Treatment') {
            $base_risk += 25;
        } elseif ($animal_info['health_status'] === 'Healthy') {
            $base_risk -= 5;
        }
        
        // Age factor (if available)
        if (isset($animal_info['age']) && $animal_info['age'] > 0) {
            if ($animal_info['age'] > 10) { // Older animals
                $base_risk += 15;
            } elseif ($animal_info['age'] < 1) { // Very young animals
                $base_risk += 10;
            }
        }
        
        // Weight factor
        if (isset($health_indicators['Weight']) && !empty($health_indicators['Weight'])) {
            $latest_weight = $health_indicators['Weight'][0]['indicator_value'];
            $weight_unit = $health_indicators['Weight'][0]['indicator_unit'] ?? 'kg';
            
            if ($animal_info['animal_type'] === 'Livestock') {
                if ($latest_weight < 150) { // Underweight
                    $base_risk += 20;
                } elseif ($latest_weight > 400) { // Overweight
                    $base_risk += 10;
                }
            } elseif ($animal_info['animal_type'] === 'Poultry') {
                if ($latest_weight < 1.5) { // Underweight
                    $base_risk += 15;
                }
            }
        }
        
        // Vaccination status
        if (isset($health_indicators['Vaccination_Status'])) {
            $vaccination_status = $health_indicators['Vaccination_Status'][0]['indicator_value'];
            if ($vaccination_status === 'Overdue') {
                $base_risk += 25;
            } elseif ($vaccination_status === 'Up to date') {
                $base_risk -= 10;
            }
        }
        
        return max(0, min(100, $base_risk));
    }
    
    /**
     * Apply seasonal adjustments
     */
    private function applySeasonalAdjustments($base_risk, $animal_type) {
        $current_month = (int)date('n');
        $seasonal_multiplier = 1.0;
        
        // Disease patterns by season
        $disease_patterns = [
            'Poultry' => [
                'peak_months' => [11, 12, 1, 2], // Winter months
                'risk_multiplier' => 1.3
            ],
            'Livestock' => [
                'peak_months' => [3, 4, 5], // Spring months
                'risk_multiplier' => 1.2
            ]
        ];
        
        if (isset($disease_patterns[$animal_type])) {
            $pattern = $disease_patterns[$animal_type];
            if (in_array($current_month, $pattern['peak_months'])) {
                $seasonal_multiplier = $pattern['risk_multiplier'];
            }
        }
        
        return $base_risk * $seasonal_multiplier;
    }
    
    /**
     * Apply environmental risk factors
     */
    private function applyEnvironmentalRisk($risk_score, $environmental_factor) {
        $env_multiplier = 1.0;
        
        switch ($environmental_factor) {
            case 'Poor':
                $env_multiplier = 1.4;
                break;
            case 'Fair':
                $env_multiplier = 1.1;
                break;
            case 'Good':
                $env_multiplier = 0.9;
                break;
            default:
                $env_multiplier = 1.1; // Unknown is slightly risky
        }
        
        return $risk_score * $env_multiplier;
    }
    
    /**
     * Apply historical pattern analysis
     */
    private function applyHistoricalPatterns($risk_score, $historical_data, $animal_info) {
        if (empty($historical_data)) {
            return $risk_score; // No historical data
        }
        
        // Calculate trend from historical assessments
        $recent_scores = array_slice(array_column($historical_data, 'risk_score'), 0, 3);
        if (count($recent_scores) >= 2) {
            $trend = end($recent_scores) - $recent_scores[0];
            
            if ($trend > 10) { // Increasing risk trend
                $risk_score += 15;
            } elseif ($trend < -10) { // Decreasing risk trend
                $risk_score -= 10;
            }
        }
        
        // Check for repeated high-risk assessments
        $high_risk_count = count(array_filter($historical_data, function($assessment) {
            return $assessment['risk_level'] === 'High' || $assessment['risk_level'] === 'Critical';
        }));
        
        if ($high_risk_count >= 2) {
            $risk_score += 20; // Pattern of high risk
        }
        
        return max(0, min(100, $risk_score));
    }
    
    /**
     * Calculate final risk score with ML-like adjustments
     */
    private function calculateFinalRiskScore($risk_score, $health_indicators) {
        // Apply machine learning-like feature engineering
        
        // Behavioral changes factor
        if (isset($health_indicators['Behavioral_Change'])) {
            $behavioral_change = $health_indicators['Behavioral_Change'][0]['indicator_value'];
            if ($behavioral_change === 'Yes') {
                $risk_score += 20;
            }
        }
        
        // SYMPTOMS-BASED RISK ASSESSMENT
        $symptoms_risk = $this->calculateSymptomsRisk($health_indicators);
        $risk_score += $symptoms_risk;
        
        // Medication history factor
        if (isset($health_indicators['Medication_History'])) {
            $medication_history = $health_indicators['Medication_History'][0]['indicator_value'];
            if ($medication_history === 'Frequent') {
                $risk_score += 15;
            } elseif ($medication_history === 'None') {
                $risk_score -= 5;
            }
        }
        
        // Temperature factor (if available)
        if (isset($health_indicators['Temperature'])) {
            $temperature = (float)$health_indicators['Temperature'][0]['indicator_value'];
            if ($temperature > 40 || $temperature < 36) { // Abnormal temperature
                $risk_score += 25;
            }
        }
        
        return max(0, min(100, $risk_score));
    }
    
    /**
     * Calculate risk score based on symptoms from pharmaceutical requests
     */
    private function calculateSymptomsRisk($health_indicators) {
        $symptoms_risk = 0;
        
        if (isset($health_indicators['Behavioral_Change'])) {
            foreach ($health_indicators['Behavioral_Change'] as $indicator) {
                $symptom = strtolower($indicator['indicator_value']);
                
                // High-risk symptoms (30-50 points)
                if (strpos($symptom, 'sudden death') !== false || 
                    strpos($symptom, 'biglaang pagkamatay') !== false) {
                    $symptoms_risk += 50;
                } elseif (strpos($symptom, 'difficulty breathing') !== false || 
                         strpos($symptom, 'hirap huminga') !== false ||
                         strpos($symptom, 'hirap sa paghinga') !== false ||
                         strpos($symptom, 'gasping') !== false) {
                    $symptoms_risk += 40;
                } elseif (strpos($symptom, 'convulsions') !== false || 
                         strpos($symptom, 'kombulsyon') !== false ||
                         strpos($symptom, 'seizures') !== false) {
                    $symptoms_risk += 45;
                } elseif (strpos($symptom, 'paralysis') !== false || 
                         strpos($symptom, 'pagkaparalisa') !== false) {
                    $symptoms_risk += 40;
                } elseif (strpos($symptom, 'bloody vomiting') !== false || 
                         strpos($symptom, 'pagsusuka na may dugo') !== false) {
                    $symptoms_risk += 40;
                } elseif (strpos($symptom, 'bloody diarrhea') !== false || 
                         strpos($symptom, 'pagtatae na may dugo') !== false) {
                    $symptoms_risk += 40;
                }
                
                // Medium-risk symptoms (15-25 points)
                elseif (strpos($symptom, 'high fever') !== false || 
                        strpos($symptom, 'mataas na lagnat') !== false) {
                    $symptoms_risk += 25;
                } elseif (strpos($symptom, 'diarrhea') !== false || 
                         strpos($symptom, 'pagtatae') !== false) {
                    $symptoms_risk += 20;
                } elseif (strpos($symptom, 'swollen') !== false || 
                         strpos($symptom, 'namamaga') !== false) {
                    $symptoms_risk += 20;
                } elseif (strpos($symptom, 'vomiting') !== false || 
                         strpos($symptom, 'pagsusuka') !== false) {
                    $symptoms_risk += 18;
                } elseif (strpos($symptom, 'weakness') !== false || 
                         strpos($symptom, 'panghihina') !== false) {
                    $symptoms_risk += 15;
                }
                
                // Low-risk symptoms (5-15 points)
                elseif (strpos($symptom, 'weight loss') !== false || 
                        strpos($symptom, 'pagpayat') !== false) {
                    $symptoms_risk += 10;
                } elseif (strpos($symptom, 'behavior') !== false || 
                         strpos($symptom, 'ugali') !== false) {
                    $symptoms_risk += 8;
                } elseif (strpos($symptom, 'lameness') !== false || 
                         strpos($symptom, 'hirap tumayo') !== false) {
                    $symptoms_risk += 12;
                }
            }
        }
        
        return min(50, $symptoms_risk); // Cap at 50 points to prevent over-inflation
    }
    
    /**
     * Determine risk level based on score
     */
    private function determineRiskLevel($risk_score) {
        if ($risk_score >= 80) {
            return 'Critical';
        } elseif ($risk_score >= 60) {
            return 'High';
        } elseif ($risk_score >= 40) {
            return 'Medium';
        } else {
            return 'Low';
        }
    }
    
    /**
     * Identify specific risk factors
     */
    private function identifyRiskFactors($animal_info, $health_indicators, $environmental_factor) {
        $risk_factors = [];
        
        // Add symptoms-based risk factors
        if (isset($health_indicators['Behavioral_Change'])) {
            foreach ($health_indicators['Behavioral_Change'] as $indicator) {
                $symptom = $indicator['indicator_value'];
                $risk_factors[] = "Symptom reported: " . $symptom;
            }
        }
        
        // Health status risk factors
        if ($animal_info['health_status'] === 'Sick') {
            $risk_factors[] = 'Current illness';
        }
        
        // Vaccination risk factors
        if (isset($health_indicators['Vaccination_Status'])) {
            $vaccination_status = $health_indicators['Vaccination_Status'][0]['indicator_value'];
            if ($vaccination_status === 'Overdue') {
                $risk_factors[] = 'Overdue vaccinations';
            }
        }
        
        // Environmental risk factors
        if ($environmental_factor === 'Poor') {
            $risk_factors[] = 'Poor environmental conditions';
        }
        
        // Weight risk factors
        if (isset($health_indicators['Weight'])) {
            $latest_weight = $health_indicators['Weight'][0]['indicator_value'];
            if ($animal_info['animal_type'] === 'Livestock' && $latest_weight < 150) {
                $risk_factors[] = 'Underweight condition';
            }
        }
        
        // Behavioral risk factors
        if (isset($health_indicators['Behavioral_Change'])) {
            $behavioral_change = $health_indicators['Behavioral_Change'][0]['indicator_value'];
            if ($behavioral_change === 'Yes') {
                $risk_factors[] = 'Behavioral changes observed';
            }
        }
        
        return $risk_factors;
    }
    
    /**
     * Generate recommendations based on risk assessment
     */
    private function generateRecommendations($risk_level, $risk_factors, $animal_info) {
        $recommendations = [];
        
        switch ($risk_level) {
            case 'Critical':
                $recommendations[] = 'Immediate veterinary examination required';
                $recommendations[] = 'Isolate animal if showing signs of contagious disease';
                $recommendations[] = 'Monitor closely every 6 hours';
                break;
                
            case 'High':
                $recommendations[] = 'Schedule veterinary consultation within 24 hours';
                $recommendations[] = 'Increase monitoring frequency';
                $recommendations[] = 'Review and improve environmental conditions';
                break;
                
            case 'Medium':
                $recommendations[] = 'Schedule routine health check within 1 week';
                $recommendations[] = 'Monitor for any changes in behavior or condition';
                break;
                
            case 'Low':
                $recommendations[] = 'Continue routine monitoring';
                $recommendations[] = 'Maintain current health protocols';
                break;
        }
        
        // Specific recommendations based on risk factors
        foreach ($risk_factors as $factor) {
            switch ($factor) {
                case 'Overdue vaccinations':
                    $recommendations[] = 'Update vaccination schedule immediately';
                    break;
                case 'Poor environmental conditions':
                    $recommendations[] = 'Improve housing and environmental conditions';
                    break;
                case 'Underweight condition':
                    $recommendations[] = 'Review feeding program and nutrition';
                    break;
                case 'Behavioral changes observed':
                    $recommendations[] = 'Document behavioral changes and consult veterinarian';
                    break;
            }
        }
        
        return array_unique($recommendations);
    }
    
    /**
     * Calculate confidence level of assessment
     */
    private function calculateConfidence($health_indicators, $historical_data) {
        $confidence = 50; // Base confidence
        
        // More indicators = higher confidence
        $indicator_count = count($health_indicators);
        $confidence += min(30, $indicator_count * 5);
        
        // Historical data = higher confidence
        if (count($historical_data) > 0) {
            $confidence += min(20, count($historical_data) * 2);
        }
        
        return min(95, $confidence);
    }
    
    /**
     * Save assessment to database
     */
    public function saveAssessment($assessment_data, $assessed_by = null) {
        $sql = "INSERT INTO health_risk_assessments 
                (animal_id, client_id, risk_score, risk_level, risk_factors, recommendations, assessed_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $risk_factors_json = json_encode($assessment_data['risk_factors'] ?? []);
        $recommendations_json = json_encode($assessment_data['recommendations'] ?? []);
        
        // Ensure all variables are properly defined
        $animal_id = (int)$assessment_data['animal_id'];
        $client_id = (int)($assessment_data['client_id'] ?? 0);
        $risk_score = (float)$assessment_data['risk_score'];
        $risk_level = (string)$assessment_data['risk_level'];
        $assessed_by = (int)($assessed_by ?? 0);
        
        $stmt->bind_param("iidsssi", 
            $animal_id,
            $client_id,
            $risk_score,
            $risk_level,
            $risk_factors_json,
            $recommendations_json,
            $assessed_by
        );
        
        return $stmt->execute();
    }
    
    /**
     * Get risk assessment summary for dashboard
     */
    public function getRiskAssessmentSummary() {
        $sql = "SELECT 
                    risk_level,
                    COUNT(*) as count,
                    AVG(risk_score) as avg_score
                FROM health_risk_assessments 
                WHERE assessment_date >= DATE_SUB(NOW(), INTERVAL 7 DAYS)
                GROUP BY risk_level";
        
        $result = $this->conn->query($sql);
        $summary = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $summary[$row['risk_level']] = [
                    'count' => (int)$row['count'],
                    'avg_score' => round($row['avg_score'], 2)
                ];
            }
        } else {
            // Return empty summary if no assessments exist
            $summary = [
                'Low' => ['count' => 0, 'avg_score' => 0],
                'Medium' => ['count' => 0, 'avg_score' => 0],
                'High' => ['count' => 0, 'avg_score' => 0],
                'Critical' => ['count' => 0, 'avg_score' => 0]
            ];
        }
        
        return $summary;
    }
    
    /**
     * Get high-risk animals requiring attention
     */
    public function getHighRiskAnimals($limit = 10) {
        $sql = "SELECT 
                    hra.animal_id,
                    hra.risk_score,
                    hra.risk_level,
                    hra.assessment_date,
                    lp.animal_name,
                    lp.animal_type,
                    lp.health_status,
                    c.full_name as client_name,
                    c.barangay
                FROM health_risk_assessments hra
                JOIN livestock_poultry lp ON hra.animal_id = lp.animal_id
                JOIN clients c ON hra.client_id = c.client_id
                WHERE hra.risk_level IN ('High', 'Critical')
                AND hra.assessment_date >= DATE_SUB(NOW(), INTERVAL 7 DAYS)
                ORDER BY hra.risk_score DESC
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $animals = [];
        while ($row = $result->fetch_assoc()) {
            $animals[] = $row;
        }
        
        return $animals;
    }
    }
}
?>
