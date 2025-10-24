<?php
/**
 * Training Data Collection Script for ML Models
 * Collects historical health data for machine learning training
 */

require_once '../includes/db_connection.php';

class TrainingDataCollector {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Collect all health data for ML training
     */
    public function collectHealthData() {
        $data = [];
        
        // Get all health assessments
        $stmt = $this->conn->prepare("
            SELECT 
                ha.animal_id,
                ha.risk_level,
                ha.risk_score,
                ha.assessment_date,
                lp.animal_type,
                lp.birth_date,
                c.location,
                c.environment
            FROM health_assessments ha
            JOIN livestock_poultry lp ON ha.animal_id = lp.animal_id
            JOIN clients c ON lp.client_id = c.client_id
            WHERE ha.assessment_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
            ORDER BY ha.assessment_date DESC
        ");
        $stmt->execute();
        $assessments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($assessments as $assessment) {
            $animal_data = $this->getAnimalTrainingData($assessment);
            if ($animal_data) {
                $data[] = $animal_data;
            }
        }
        
        return $data;
    }
    
    /**
     * Get training data for a specific animal assessment
     */
    private function getAnimalTrainingData($assessment) {
        $animal_id = $assessment['animal_id'];
        
        // Get symptoms from pharmaceutical requests
        $symptoms = $this->getSymptomsForAnimal($animal_id, $assessment['assessment_date']);
        
        // Get health indicators
        $health_indicators = $this->getHealthIndicatorsForAnimal($animal_id, $assessment['assessment_date']);
        
        // Prepare training data
        return [
            'animal_id' => $animal_id,
            'age' => $this->calculateAge($assessment['birth_date']),
            'breed' => $assessment['animal_type'],
            'symptoms' => $symptoms,
            'vital_signs' => $this->extractVitalSigns($health_indicators),
            'environment' => [
                'temperature' => $assessment['environment'] ?? 25.0,
                'humidity' => 60.0,
                'season' => $this->getSeasonForDate($assessment['assessment_date'])
            ],
            'outcome' => $assessment['risk_level'],
            'risk_score' => $assessment['risk_score'],
            'timestamp' => $assessment['assessment_date']
        ];
    }
    
    /**
     * Get symptoms for an animal at a specific date
     */
    private function getSymptomsForAnimal($animal_id, $assessment_date) {
        $stmt = $this->conn->prepare("
            SELECT symptoms 
            FROM pharmaceutical_requests 
            WHERE animal_id = ? 
            AND created_at <= ?
            AND symptoms IS NOT NULL 
            AND symptoms != ''
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->bind_param("is", $animal_id, $assessment_date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $symptoms = [];
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['symptoms'])) {
                $symptom_array = explode(';', $row['symptoms']);
                foreach ($symptom_array as $symptom) {
                    $symptom = trim($symptom);
                    if (!empty($symptom)) {
                        $symptoms[] = $symptom;
                    }
                }
            }
        }
        
        return array_unique($symptoms);
    }
    
    /**
     * Get health indicators for an animal at a specific date
     */
    private function getHealthIndicatorsForAnimal($animal_id, $assessment_date) {
        $stmt = $this->conn->prepare("
            SELECT indicator_type, indicator_value, recorded_date 
            FROM health_indicators 
            WHERE animal_id = ? 
            AND recorded_date <= ?
            ORDER BY recorded_date DESC
            LIMIT 20
        ");
        $stmt->bind_param("is", $animal_id, $assessment_date);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Extract vital signs from health indicators
     */
    private function extractVitalSigns($health_indicators) {
        $vital_signs = [
            'temperature' => 38.5,
            'weight' => 0,
            'heart_rate' => 0
        ];
        
        foreach ($health_indicators as $indicator) {
            $type = $indicator['indicator_type'];
            $value = floatval($indicator['indicator_value']);
            
            switch ($type) {
                case 'Temperature':
                    $vital_signs['temperature'] = $value;
                    break;
                case 'Weight':
                    $vital_signs['weight'] = $value;
                    break;
                case 'Heart_Rate':
                    $vital_signs['heart_rate'] = $value;
                    break;
            }
        }
        
        return $vital_signs;
    }
    
    /**
     * Calculate age from birth date
     */
    private function calculateAge($birth_date) {
        if (!$birth_date) return 0;
        $birth = new DateTime($birth_date);
        $now = new DateTime();
        return $now->diff($birth)->y;
    }
    
    /**
     * Get season for a specific date
     */
    private function getSeasonForDate($date) {
        $month = date('n', strtotime($date));
        if (in_array($month, [12, 1, 2])) return 'winter';
        if (in_array($month, [3, 4, 5])) return 'spring';
        if (in_array($month, [6, 7, 8])) return 'summer';
        return 'autumn';
    }
    
    /**
     * Save training data to file
     */
    public function saveTrainingData($data, $filename = 'training_data.json') {
        $json_data = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents($filename, $json_data);
        return $filename;
    }
    
    /**
     * Get data statistics
     */
    public function getDataStatistics($data) {
        $stats = [
            'total_records' => count($data),
            'risk_levels' => [],
            'breeds' => [],
            'symptoms' => []
        ];
        
        foreach ($data as $record) {
            // Count risk levels
            $risk_level = $record['outcome'];
            $stats['risk_levels'][$risk_level] = ($stats['risk_levels'][$risk_level] ?? 0) + 1;
            
            // Count breeds
            $breed = $record['breed'];
            $stats['breeds'][$breed] = ($stats['breeds'][$breed] ?? 0) + 1;
            
            // Count symptoms
            foreach ($record['symptoms'] as $symptom) {
                $stats['symptoms'][$symptom] = ($stats['symptoms'][$symptom] ?? 0) + 1;
            }
        }
        
        return $stats;
    }
}

// Usage
if (php_sapi_name() === 'cli') {
    echo "Collecting training data...\n";
    
    $collector = new TrainingDataCollector($conn);
    $training_data = $collector->collectHealthData();
    
    if (empty($training_data)) {
        echo "No training data found. Make sure you have health assessments in your database.\n";
        exit(1);
    }
    
    // Save training data
    $filename = $collector->saveTrainingData($training_data);
    echo "Training data saved to: $filename\n";
    echo "Total records: " . count($training_data) . "\n";
    
    // Show statistics
    $stats = $collector->getDataStatistics($training_data);
    echo "\nData Statistics:\n";
    echo "Risk Levels: " . json_encode($stats['risk_levels'], JSON_PRETTY_PRINT) . "\n";
    echo "Breeds: " . json_encode($stats['breeds'], JSON_PRETTY_PRINT) . "\n";
    echo "Top Symptoms: " . json_encode(array_slice($stats['symptoms'], 0, 10, true), JSON_PRETTY_PRINT) . "\n";
    
    echo "\nTraining data collection completed!\n";
    echo "Next step: Run the ML model training script.\n";
}
?>
