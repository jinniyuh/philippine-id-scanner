<?php

class BarangayAnomalyDetector {
    private $conn;
    private $time_window_hours = 48; // Check last 48 hours (more sensitive)
    private $anomaly_threshold = 1; // Minimum requests to trigger anomaly (more sensitive)
    private $spike_threshold = 100; // 100% increase from baseline (more sensitive)
    
    // Medicine to disease mapping - ONLY CRITICAL OUTBREAKS
    private $medicine_disease_map = [
        'Hog Colera' => ['disease' => 'Classical Swine Fever', 'animal' => 'Swine', 'severity' => 'critical'],
        'Rabies Vaccine' => ['disease' => 'Rabies', 'animal' => 'Dog', 'severity' => 'critical'],
        'Newcastle' => ['disease' => 'Newcastle Disease', 'animal' => 'Poultry', 'severity' => 'critical'],
        'FMD' => ['disease' => 'Foot and Mouth Disease', 'animal' => 'Cattle', 'severity' => 'critical'],
        'Anthrax' => ['disease' => 'Anthrax', 'animal' => 'Cattle', 'severity' => 'critical'],
        'Avian Flu' => ['disease' => 'Avian Influenza', 'animal' => 'Poultry', 'severity' => 'critical']
    ];
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Get recent transactions with barangay information
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
                c.longitude,
                c.full_name as client_name
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
     * Group transactions by medicine and barangay
     */
    private function groupTransactionsByMedicineAndBarangay($transactions) {
        $groups = [];
        
        foreach ($transactions as $transaction) {
            $medicine = $transaction['medicine_name'];
            $barangay = $transaction['barangay'];
            $key = $medicine . '_' . $barangay;
            
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'medicine' => $medicine,
                    'barangay' => $barangay,
                    'transactions' => [],
                    'total_quantity' => 0,
                    'client_count' => 0,
                    'clients' => []
                ];
            }
            
            $groups[$key]['transactions'][] = $transaction;
            $groups[$key]['total_quantity'] += $transaction['quantity'];
            
            // Track unique clients
            if (!in_array($transaction['client_id'], $groups[$key]['clients'])) {
                $groups[$key]['clients'][] = $transaction['client_id'];
                $groups[$key]['client_count']++;
            }
        }
        
        return $groups;
    }
    
    /**
     * Get baseline data for a specific medicine in a barangay
     */
    private function getBaselineForMedicineInBarangay($medicine, $barangay) {
        $sql = "
            SELECT 
                COUNT(*) as request_count,
                AVG(quantity) as avg_quantity,
                COUNT(DISTINCT client_id) as unique_clients
            FROM transactions t
            JOIN pharmaceuticals p ON t.pharma_id = p.pharma_id
            JOIN clients c ON t.client_id = c.client_id
            WHERE t.status = 'Approved'
            AND p.name = ?
            AND c.barangay = ?
            AND t.request_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND t.request_date < DATE_SUB(NOW(), INTERVAL 1 DAY)
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $medicine, $barangay);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Analyze barangay-specific patterns for anomalies
     */
    private function analyzeBarangayPattern($medicine, $barangay, $current_data, $baseline) {
        $anomalies = [];
        
        // Check if current requests exceed baseline significantly
        $current_requests = $current_data['client_count'];
        $baseline_requests = $baseline['request_count'] ?? 0;
        
        if ($baseline_requests > 0) {
            $increase_percentage = (($current_requests - $baseline_requests) / $baseline_requests) * 100;
        } else {
            // No baseline data - any request is potentially anomalous
            $increase_percentage = $current_requests > 0 ? 200 : 0;
        }
        
        // Check for anomaly conditions
        if ($current_requests >= $this->anomaly_threshold && $increase_percentage >= $this->spike_threshold) {
            $disease_info = $this->medicine_disease_map[$medicine] ?? [
                'disease' => 'Unknown Disease',
                'animal' => 'Unknown Animal',
                'severity' => 'medium'
            ];
            
            $anomalies[] = [
                'medicine' => $medicine,
                'barangay' => $barangay,
                'disease' => $disease_info['disease'],
                'animal' => $disease_info['animal'],
                'severity' => $disease_info['severity'],
                'current_requests' => $current_requests,
                'baseline_requests' => $baseline_requests,
                'increase_percentage' => round($increase_percentage, 1),
                'total_quantity' => $current_data['total_quantity'],
                'affected_clients' => $current_data['clients']
            ];
        }
        
        return $anomalies;
    }
    
    /**
     * Detect anomalies by barangay
     */
    public function detectBarangayAnomalies() {
        $transactions = $this->getRecentTransactions();
        $grouped_data = $this->groupTransactionsByMedicineAndBarangay($transactions);
        
        $all_anomalies = [];
        
        foreach ($grouped_data as $key => $data) {
            $medicine = $data['medicine'];
            $barangay = $data['barangay'];
            
            // Get baseline for this medicine in this barangay
            $baseline = $this->getBaselineForMedicineInBarangay($medicine, $barangay);
            
            // Analyze pattern
            $anomalies = $this->analyzeBarangayPattern($medicine, $barangay, $data, $baseline);
            $all_anomalies = array_merge($all_anomalies, $anomalies);
        }
        
        return $all_anomalies;
    }
    
    /**
     * Get anomaly summary by barangay
     */
    public function getBarangayAnomalySummary() {
        $anomalies = $this->detectBarangayAnomalies();
        
        $summary = [
            'total_anomalies' => count($anomalies),
            'critical_anomalies' => 0,
            'high_anomalies' => 0,
            'medium_anomalies' => 0,
            'low_anomalies' => 0,
            'anomalies_by_barangay' => [],
            'anomalies_by_medicine' => [],
            'most_critical' => null
        ];
        
        $severity_counts = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];
        
        foreach ($anomalies as $anomaly) {
            $severity = $anomaly['severity'];
            $severity_counts[$severity]++;
            
            // Group by barangay
            if (!isset($summary['anomalies_by_barangay'][$anomaly['barangay']])) {
                $summary['anomalies_by_barangay'][$anomaly['barangay']] = [];
            }
            $summary['anomalies_by_barangay'][$anomaly['barangay']][] = $anomaly;
            
            // Group by medicine
            if (!isset($summary['anomalies_by_medicine'][$anomaly['medicine']])) {
                $summary['anomalies_by_medicine'][$anomaly['medicine']] = [];
            }
            $summary['anomalies_by_medicine'][$anomaly['medicine']][] = $anomaly;
            
            // Track most critical
            if (!$summary['most_critical'] || $severity === 'critical') {
                $summary['most_critical'] = $anomaly;
            }
        }
        
        $summary['critical_anomalies'] = $severity_counts['critical'];
        $summary['high_anomalies'] = $severity_counts['high'];
        $summary['medium_anomalies'] = $severity_counts['medium'];
        $summary['low_anomalies'] = $severity_counts['low'];
        
        return $summary;
    }
    
    /**
     * Get detailed anomaly information for popup
     */
    public function getMostCriticalBarangayAnomaly() {
        $anomalies = $this->detectBarangayAnomalies();
        
        if (empty($anomalies)) {
            return null;
        }
        
        // Sort by severity and impact
        usort($anomalies, function($a, $b) {
            $severity_order = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];
            $a_severity = $severity_order[$a['severity']] ?? 0;
            $b_severity = $severity_order[$b['severity']] ?? 0;
            
            if ($a_severity === $b_severity) {
                return $b['increase_percentage'] <=> $a['increase_percentage'];
            }
            
            return $b_severity <=> $a_severity;
        });
        
        $most_critical = $anomalies[0];
        
        // Get affected areas (nearby barangays with similar anomalies)
        $affected_areas = [$most_critical['barangay']];
        foreach ($anomalies as $anomaly) {
            if ($anomaly['medicine'] === $most_critical['medicine'] && 
                $anomaly['barangay'] !== $most_critical['barangay']) {
                $affected_areas[] = $anomaly['barangay'];
            }
        }
        
        return [
            'animal' => $most_critical['animal'],
            'medicine' => $most_critical['medicine'],
            'disease' => $most_critical['disease'],
            'severity' => $most_critical['severity'],
            'barangay' => $most_critical['barangay'],
            'count' => $most_critical['current_requests'],
            'deviationPercentage' => $most_critical['increase_percentage'],
            'affectedAreas' => array_unique($affected_areas),
            'totalQuantity' => $most_critical['total_quantity'],
            'affectedClients' => $most_critical['affected_clients']
        ];
    }
    
    /**
     * Get all barangays with their anomaly counts
     */
    public function getBarangayAnomalyMap() {
        $anomalies = $this->detectBarangayAnomalies();
        $barangay_map = [];
        
        foreach ($anomalies as $anomaly) {
            $barangay = $anomaly['barangay'];
            if (!isset($barangay_map[$barangay])) {
                $barangay_map[$barangay] = [
                    'barangay' => $barangay,
                    'total_anomalies' => 0,
                    'critical_count' => 0,
                    'high_count' => 0,
                    'medium_count' => 0,
                    'low_count' => 0,
                    'anomalies' => []
                ];
            }
            
            $barangay_map[$barangay]['total_anomalies']++;
            $barangay_map[$barangay][$anomaly['severity'] . '_count']++;
            $barangay_map[$barangay]['anomalies'][] = $anomaly;
        }
        
        return $barangay_map;
    }
}
?>
