<?php
/**
 * Anomaly Detection System for Health Monitoring
 * Analyzes pharmaceutical transaction patterns to detect potential disease outbreaks
 */

class AnomalyDetector {
    private $conn;
    private $anomaly_threshold = 3; // Minimum number of approvals to trigger anomaly
    private $time_window_hours = 24; // Time window for anomaly detection
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Detect anomalies in pharmaceutical transactions
     * @return array Array of detected anomalies
     */
    public function detectAnomalies() {
        try {
            $anomalies = [];
            
            // Get recent pharmaceutical transactions
            $transactions = $this->getRecentTransactions();
            
            if (empty($transactions)) {
                return $anomalies;
            }
            
            // Group transactions by medicine
            $medicine_groups = $this->groupTransactionsByMedicine($transactions);
            
            // Analyze each medicine group for anomalies
            foreach ($medicine_groups as $medicine => $transactions) {
                $anomaly = $this->analyzeMedicinePattern($medicine, $transactions);
                if ($anomaly) {
                    $anomalies[] = $anomaly;
                }
            }
            
            // Check for cross-medicine patterns (multiple related medicines)
            $cross_patterns = $this->detectCrossMedicinePatterns($medicine_groups);
            $anomalies = array_merge($anomalies, $cross_patterns);
            
            return $anomalies;
            
        } catch (Exception $e) {
            error_log("Anomaly detection failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent pharmaceutical transactions
     */
    private function getRecentTransactions() {
        $sql = "
            SELECT 
                t.transaction_id,
                p.name as medicine_name,
                t.quantity,
                t.status,
                t.request_date as approved_at,
                t.client_id,
                c.barangay,
                c.latitude,
                c.longitude
            FROM transactions t
            JOIN pharmaceuticals p ON t.pharma_id = p.pharma_id
            JOIN clients c ON t.client_id = c.client_id
            WHERE t.status = 'Approved'
            AND t.request_date >= DATE_SUB(NOW(), INTERVAL ? HOUR)
            ORDER BY t.request_date DESC
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $this->time_window_hours);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
        
        return $transactions;
    }
    
    /**
     * Group transactions by medicine name
     */
    private function groupTransactionsByMedicine($transactions) {
        $groups = [];
        
        foreach ($transactions as $transaction) {
            $medicine = $transaction['medicine_name'];
            if (!isset($groups[$medicine])) {
                $groups[$medicine] = [];
            }
            $groups[$medicine][] = $transaction;
        }
        
        return $groups;
    }
    
    /**
     * Analyze medicine pattern for anomalies
     */
    private function analyzeMedicinePattern($medicine, $transactions) {
        $count = count($transactions);
        $total_quantity = array_sum(array_column($transactions, 'quantity'));
        
        // Check if this exceeds normal patterns
        $baseline = $this->getBaselineForMedicine($medicine);
        
        if ($count >= $this->anomaly_threshold && $count > $baseline['count']) {
            // Calculate severity based on deviation from baseline
            $severity = $this->calculateSeverity($count, $baseline['count']);
            
            // Get disease information for this medicine
            $disease_info = $this->getDiseaseInfoForMedicine($medicine);
            
            // Get affected areas
            $affected_areas = $this->getAffectedAreas($transactions);
            
            return [
                'type' => 'medicine_spike',
                'medicine' => $medicine,
                'disease' => $disease_info['disease'],
                'animal' => $disease_info['animal'],
                'count' => $count,
                'total_quantity' => $total_quantity,
                'severity' => $severity,
                'affected_areas' => $affected_areas,
                'detected_at' => date('Y-m-d H:i:s'),
                'baseline_count' => $baseline['count'],
                'deviation_percentage' => round((($count - $baseline['count']) / max(1, $baseline['count'])) * 100, 1)
            ];
        }
        
        return null;
    }
    
    /**
     * Get baseline data for a medicine
     */
    private function getBaselineForMedicine($medicine) {
        $sql = "
            SELECT 
                COUNT(*) as count,
                AVG(t.quantity) as avg_quantity
            FROM transactions t
            JOIN pharmaceuticals p ON t.pharma_id = p.pharma_id
            WHERE p.name = ? 
            AND t.status = 'Approved'
            AND t.request_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND t.request_date < DATE_SUB(NOW(), INTERVAL 1 DAY)
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $medicine);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return [
            'count' => max(1, $result['count'] / 30), // Daily average
            'avg_quantity' => $result['avg_quantity'] ?? 1
        ];
    }
    
    /**
     * Calculate anomaly severity
     */
    private function calculateSeverity($current_count, $baseline_count) {
        $deviation = ($current_count - $baseline_count) / max(1, $baseline_count);
        
        if ($deviation >= 3.0) {
            return 'critical';
        } elseif ($deviation >= 2.0) {
            return 'high';
        } elseif ($deviation >= 1.5) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    /**
     * Get disease information for a medicine
     */
    private function getDiseaseInfoForMedicine($medicine) {
        // Medicine to disease mapping
        $medicine_disease_map = [
            'Hog Cholera Vaccine' => ['disease' => 'Hog Cholera', 'animal' => '🐖 Swine'],
            'Swine Fever Medicine' => ['disease' => 'Swine Fever', 'animal' => '🐖 Swine'],
            'Avian Influenza Vaccine' => ['disease' => 'Avian Influenza', 'animal' => '🐔 Poultry'],
            'Newcastle Disease Vaccine' => ['disease' => 'Newcastle Disease', 'animal' => '🐔 Poultry'],
            'Foot and Mouth Vaccine' => ['disease' => 'Foot and Mouth Disease', 'animal' => '🐄 Cattle'],
            'Anthrax Vaccine' => ['disease' => 'Anthrax', 'animal' => '🐄 Cattle'],
            'Brucellosis Vaccine' => ['disease' => 'Brucellosis', 'animal' => '🐄 Cattle'],
            'Mastitis Treatment' => ['disease' => 'Mastitis', 'animal' => '🐄 Cattle'],
            'Coccidiosis Treatment' => ['disease' => 'Coccidiosis', 'animal' => '🐔 Poultry'],
            'Gumboro Vaccine' => ['disease' => 'Gumboro Disease', 'animal' => '🐔 Poultry']
        ];
        
        // Check for exact match first
        if (isset($medicine_disease_map[$medicine])) {
            return $medicine_disease_map[$medicine];
        }
        
        // Check for partial matches
        foreach ($medicine_disease_map as $key => $info) {
            if (stripos($medicine, $key) !== false || stripos($key, $medicine) !== false) {
                return $info;
            }
        }
        
        // Default fallback
        return [
            'disease' => 'Unknown Disease',
            'animal' => '🐾 Animal'
        ];
    }
    
    /**
     * Get affected areas from transactions
     */
    private function getAffectedAreas($transactions) {
        $areas = [];
        foreach ($transactions as $transaction) {
            $barangay = $transaction['barangay'];
            if (!in_array($barangay, $areas)) {
                $areas[] = $barangay;
            }
        }
        return $areas;
    }
    
    /**
     * Detect cross-medicine patterns (multiple related medicines)
     */
    private function detectCrossMedicinePatterns($medicine_groups) {
        $anomalies = [];
        
        // Define disease groups
        $disease_groups = [
            'swine_diseases' => [
                'medicines' => ['Hog Cholera', 'Swine Fever', 'PRRS'],
                'disease' => 'Swine Disease Outbreak',
                'animal' => '🐖 Swine'
            ],
            'poultry_diseases' => [
                'medicines' => ['Avian Influenza', 'Newcastle Disease', 'Gumboro'],
                'disease' => 'Poultry Disease Outbreak',
                'animal' => '🐔 Poultry'
            ],
            'cattle_diseases' => [
                'medicines' => ['Foot and Mouth', 'Anthrax', 'Brucellosis'],
                'disease' => 'Cattle Disease Outbreak',
                'animal' => '🐄 Cattle'
            ]
        ];
        
        foreach ($disease_groups as $group_name => $group_info) {
            $related_medicines = [];
            $total_count = 0;
            
            foreach ($group_info['medicines'] as $medicine_keyword) {
                foreach ($medicine_groups as $medicine => $transactions) {
                    if (stripos($medicine, $medicine_keyword) !== false) {
                        $related_medicines[] = $medicine;
                        $total_count += count($transactions);
                    }
                }
            }
            
            // If multiple related medicines are approved
            if (count($related_medicines) >= 2 && $total_count >= $this->anomaly_threshold) {
                $anomalies[] = [
                    'type' => 'cross_medicine_pattern',
                    'disease' => $group_info['disease'],
                    'animal' => $group_info['animal'],
                    'related_medicines' => $related_medicines,
                    'total_count' => $total_count,
                    'severity' => 'high',
                    'detected_at' => date('Y-m-d H:i:s')
                ];
            }
        }
        
        return $anomalies;
    }
    
    /**
     * Get anomaly detection summary for dashboard
     */
    public function getAnomalySummary() {
        $anomalies = $this->detectAnomalies();
        
        $summary = [
            'total_anomalies' => count($anomalies),
            'critical_anomalies' => 0,
            'high_anomalies' => 0,
            'medium_anomalies' => 0,
            'low_anomalies' => 0,
            'anomalies' => $anomalies
        ];
        
        foreach ($anomalies as $anomaly) {
            $severity = $anomaly['severity'] ?? 'low';
            $summary[$severity . '_anomalies']++;
        }
        
        return $summary;
    }
    
    /**
     * Check if anomalies exist (for alert system)
     */
    public function hasAnomalies() {
        $anomalies = $this->detectAnomalies();
        return !empty($anomalies);
    }
    
    /**
     * Get the most critical anomaly
     */
    public function getMostCriticalAnomaly() {
        $anomalies = $this->detectAnomalies();
        
        if (empty($anomalies)) {
            return null;
        }
        
        // Sort by severity
        $severity_order = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];
        
        usort($anomalies, function($a, $b) use ($severity_order) {
            $a_severity = $severity_order[$a['severity']] ?? 0;
            $b_severity = $severity_order[$b['severity']] ?? 0;
            return $b_severity - $a_severity;
        });
        
        return $anomalies[0];
    }
}
?>
