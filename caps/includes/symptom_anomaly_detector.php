<?php

class SymptomAnomalyDetector {
    private $conn;
    private $time_window_hours = 48; // Check last 48 hours
    private $anomaly_threshold = 2; // Minimum requests to trigger anomaly
    private $spike_threshold = 100; // 100% increase from baseline
    
    // Critical symptoms that indicate potential outbreaks
    private $critical_symptoms = [
        // Swine symptoms
        'mataas na lagnat' => ['disease' => 'Classical Swine Fever', 'animal' => 'Swine', 'severity' => 'critical'],
        'high fever' => ['disease' => 'Classical Swine Fever', 'animal' => 'Swine', 'severity' => 'critical'],
        'kawalan ng ganang kumain' => ['disease' => 'Classical Swine Fever', 'animal' => 'Swine', 'severity' => 'critical'],
        'loss of appetite' => ['disease' => 'Classical Swine Fever', 'animal' => 'Swine', 'severity' => 'critical'],
        'panghihina at panghihina ng katawan' => ['disease' => 'Classical Swine Fever', 'animal' => 'Swine', 'severity' => 'critical'],
        'weakness' => ['disease' => 'Classical Swine Fever', 'animal' => 'Swine', 'severity' => 'critical'],
        'lethargy' => ['disease' => 'Classical Swine Fever', 'animal' => 'Swine', 'severity' => 'critical'],
        'pamumula ng balat lalo na sa tenga, tiyan, at paa' => ['disease' => 'Classical Swine Fever', 'animal' => 'Swine', 'severity' => 'critical'],
        'skin redness' => ['disease' => 'Classical Swine Fever', 'animal' => 'Swine', 'severity' => 'critical'],
        'mapupulang batik o pasa' => ['disease' => 'Classical Swine Fever', 'animal' => 'Swine', 'severity' => 'critical'],
        'red/purple skin spots' => ['disease' => 'Classical Swine Fever', 'animal' => 'Swine', 'severity' => 'critical'],
        'pagtatae na may dugo' => ['disease' => 'Classical Swine Fever', 'animal' => 'Swine', 'severity' => 'critical'],
        'bloody diarrhea' => ['disease' => 'Classical Swine Fever', 'animal' => 'Swine', 'severity' => 'critical'],
        'pagsusuka na may dugo' => ['disease' => 'Classical Swine Fever', 'animal' => 'Swine', 'severity' => 'critical'],
        'bloody vomiting' => ['disease' => 'Classical Swine Fever', 'animal' => 'Swine', 'severity' => 'critical'],
        'pamamaga ng tiyan at paa' => ['disease' => 'Classical Swine Fever', 'animal' => 'Swine', 'severity' => 'critical'],
        'swollen belly and legs' => ['disease' => 'Classical Swine Fever', 'animal' => 'Swine', 'severity' => 'critical'],
        'hirap sa paghinga' => ['disease' => 'Classical Swine Fever', 'animal' => 'Swine', 'severity' => 'critical'],
        'breathing difficulties' => ['disease' => 'Classical Swine Fever', 'animal' => 'Swine', 'severity' => 'critical'],
        'biglaang pagkamatay' => ['disease' => 'Classical Swine Fever', 'animal' => 'Swine', 'severity' => 'critical'],
        'sudden death' => ['disease' => 'Classical Swine Fever', 'animal' => 'Swine', 'severity' => 'critical'],
        
        // Poultry symptoms
        'sudden death' => ['disease' => 'Newcastle Disease', 'animal' => 'Poultry', 'severity' => 'critical'],
        'respiratory signs' => ['disease' => 'Newcastle Disease', 'animal' => 'Poultry', 'severity' => 'critical'],
        'nervous signs' => ['disease' => 'Newcastle Disease', 'animal' => 'Poultry', 'severity' => 'critical'],
        'drop in egg production' => ['disease' => 'Newcastle Disease', 'animal' => 'Poultry', 'severity' => 'critical'],
        'greenish diarrhea' => ['disease' => 'Newcastle Disease', 'animal' => 'Poultry', 'severity' => 'critical'],
        'twisted neck' => ['disease' => 'Newcastle Disease', 'animal' => 'Poultry', 'severity' => 'critical'],
        
        // Dog symptoms
        'aggressive behavior' => ['disease' => 'Rabies', 'animal' => 'Dog', 'severity' => 'critical'],
        'excessive salivation' => ['disease' => 'Rabies', 'animal' => 'Dog', 'severity' => 'critical'],
        'paralysis' => ['disease' => 'Rabies', 'animal' => 'Dog', 'severity' => 'critical'],
        'fear of water' => ['disease' => 'Rabies', 'animal' => 'Dog', 'severity' => 'critical'],
        'unusual behavior' => ['disease' => 'Rabies', 'animal' => 'Dog', 'severity' => 'critical'],
        
        // Cattle symptoms
        'blisters on mouth' => ['disease' => 'Foot and Mouth Disease', 'animal' => 'Cattle', 'severity' => 'critical'],
        'lameness' => ['disease' => 'Foot and Mouth Disease', 'animal' => 'Cattle', 'severity' => 'critical'],
        'excessive salivation' => ['disease' => 'Foot and Mouth Disease', 'animal' => 'Cattle', 'severity' => 'critical'],
        'sudden death' => ['disease' => 'Anthrax', 'animal' => 'Cattle', 'severity' => 'critical'],
        'bloody discharge' => ['disease' => 'Anthrax', 'animal' => 'Cattle', 'severity' => 'critical'],
        'high fever' => ['disease' => 'Anthrax', 'animal' => 'Cattle', 'severity' => 'critical']
    ];
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Get recent pharmaceutical requests with symptoms
     */
    public function getRecentRequests() {
        $sql = "
            SELECT 
                pr.request_id,
                pr.client_id,
                pr.species,
                pr.symptoms,
                pr.request_date,
                pr.status,
                c.full_name as client_name,
                c.barangay
            FROM pharmaceutical_requests pr
            JOIN clients c ON pr.client_id = c.client_id
            WHERE pr.symptoms IS NOT NULL 
            AND pr.symptoms != ''
            AND pr.request_date >= DATE_SUB(NOW(), INTERVAL ? HOUR)
            ORDER BY pr.request_date DESC
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $this->time_window_hours);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    /**
     * Group requests by symptoms and barangay
     */
    public function groupRequestsBySymptomsAndBarangay($requests) {
        $grouped = [];
        
        while ($row = $requests->fetch_assoc()) {
            $symptoms = strtolower(trim($row['symptoms']));
            $barangay = $row['barangay'] ?: 'Unknown';
            
            // Check for critical symptoms
            foreach ($this->critical_symptoms as $symptom => $disease_info) {
                if (strpos($symptoms, $symptom) !== false) {
                    $key = $symptom . '_' . $barangay;
                    
                    if (!isset($grouped[$key])) {
                        $grouped[$key] = [
                            'symptom' => $symptom,
                            'barangay' => $barangay,
                            'disease' => $disease_info['disease'],
                            'animal' => $disease_info['animal'],
                            'severity' => $disease_info['severity'],
                            'requests' => [],
                            'count' => 0
                        ];
                    }
                    
                    $grouped[$key]['requests'][] = $row;
                    $grouped[$key]['count']++;
                }
            }
        }
        
        return $grouped;
    }
    
    /**
     * Get baseline for specific symptom and barangay
     */
    public function getBaselineForSymptomAndBarangay($symptom, $barangay) {
        $sql = "
            SELECT COUNT(*) as baseline_count
            FROM pharmaceutical_requests pr
            JOIN clients c ON pr.client_id = c.client_id
            WHERE pr.symptoms LIKE ?
            AND c.barangay = ?
            AND pr.request_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND pr.request_date < DATE_SUB(NOW(), INTERVAL ? HOUR)
        ";
        
        $symptom_pattern = '%' . $symptom . '%';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssi", $symptom_pattern, $barangay, $this->time_window_hours);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result['baseline_count'] ?? 0;
    }
    
    /**
     * Analyze symptom patterns by barangay
     */
    public function analyzeSymptomPatternByBarangay($grouped_requests) {
        $anomalies = [];
        
        foreach ($grouped_requests as $key => $group) {
            $symptom = $group['symptom'];
            $barangay = $group['barangay'];
            $current_count = $group['count'];
            
            // Get baseline
            $baseline = $this->getBaselineForSymptomAndBarangay($symptom, $barangay);
            
            // Calculate increase percentage
            $increase_percentage = 0;
            if ($baseline > 0) {
                $increase_percentage = (($current_count - $baseline) / $baseline) * 100;
            } else if ($current_count > 0) {
                $increase_percentage = 100; // New pattern
            }
            
            // Check if it's an anomaly
            if ($current_count >= $this->anomaly_threshold && $increase_percentage >= $this->spike_threshold) {
                $anomalies[] = [
                    'symptom' => $symptom,
                    'barangay' => $barangay,
                    'disease' => $group['disease'],
                    'animal' => $group['animal'],
                    'severity' => $group['severity'],
                    'current_requests' => $current_count,
                    'baseline_requests' => $baseline,
                    'increase_percentage' => round($increase_percentage, 1),
                    'requests' => $group['requests']
                ];
            }
        }
        
        return $anomalies;
    }
    
    /**
     * Get anomaly summary
     */
    public function getAnomalySummary($anomalies) {
        $summary = [
            'total_anomalies' => count($anomalies),
            'critical_count' => 0,
            'high_count' => 0,
            'medium_count' => 0,
            'by_barangay' => [],
            'by_disease' => []
        ];
        
        foreach ($anomalies as $anomaly) {
            $severity = $anomaly['severity'];
            $barangay = $anomaly['barangay'];
            $disease = $anomaly['disease'];
            
            // Count by severity
            if ($severity === 'critical') $summary['critical_count']++;
            elseif ($severity === 'high') $summary['high_count']++;
            else $summary['medium_count']++;
            
            // Count by barangay
            if (!isset($summary['by_barangay'][$barangay])) {
                $summary['by_barangay'][$barangay] = 0;
            }
            $summary['by_barangay'][$barangay]++;
            
            // Count by disease
            if (!isset($summary['by_disease'][$disease])) {
                $summary['by_disease'][$disease] = 0;
            }
            $summary['by_disease'][$disease]++;
        }
        
        return $summary;
    }
    
    /**
     * Get the most critical anomaly
     */
    public function getMostCriticalAnomaly($anomalies) {
        if (empty($anomalies)) return null;
        
        // Sort by severity and increase percentage
        usort($anomalies, function($a, $b) {
            $severity_order = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];
            $a_severity = $severity_order[$a['severity']] ?? 0;
            $b_severity = $severity_order[$b['severity']] ?? 0;
            
            if ($a_severity !== $b_severity) {
                return $b_severity - $a_severity;
            }
            
            return $b['increase_percentage'] - $a['increase_percentage'];
        });
        
        return $anomalies[0];
    }
    
    /**
     * Detect possible outbreak based on geographic clustering
     * Requirements:
     * - At least 2 people in same barangay report same symptoms
     * - At least 10 different barangays (out of 24 in Bago City)
     * - Each barangay has ≥2 requests with same symptoms
     */
    public function detectPossibleOutbreak() {
        try {
            // Get recent requests
            $requests = $this->getRecentRequests();
            
            // Group by symptoms and barangay
            $grouped = $this->groupRequestsBySymptomsAndBarangay($requests);
            
            // Filter groups with at least 2 people per barangay
            $valid_groups = [];
            foreach ($grouped as $key => $group) {
                if ($group['count'] >= 2) { // Minimum 2 people per barangay
                    $valid_groups[] = $group;
                }
            }
            
            // Group by symptom type to find patterns across barangays
            $symptom_patterns = [];
            foreach ($valid_groups as $group) {
                $symptom = $group['symptom'];
                if (!isset($symptom_patterns[$symptom])) {
                    $symptom_patterns[$symptom] = [
                        'symptom' => $symptom,
                        'disease' => $group['disease'],
                        'animal' => $group['animal'],
                        'severity' => $group['severity'],
                        'affected_barangays' => [],
                        'total_requests' => 0,
                        'barangay_count' => 0
                    ];
                }
                
                $symptom_patterns[$symptom]['affected_barangays'][] = [
                    'barangay' => $group['barangay'],
                    'request_count' => $group['count'],
                    'requests' => $group['requests']
                ];
                $symptom_patterns[$symptom]['total_requests'] += $group['count'];
                $symptom_patterns[$symptom]['barangay_count']++;
            }
            
            // Check for outbreak conditions
            $outbreak_alerts = [];
            foreach ($symptom_patterns as $symptom => $pattern) {
                // Check if at least 10 barangays are affected
                if ($pattern['barangay_count'] >= 10) {
                    $outbreak_alerts[] = [
                        'type' => 'POSSIBLE OUTBREAK',
                        'symptom' => $symptom,
                        'disease' => $pattern['disease'],
                        'animal' => $pattern['animal'],
                        'severity' => $pattern['severity'],
                        'affected_barangays' => $pattern['barangay_count'],
                        'total_requests' => $pattern['total_requests'],
                        'barangay_details' => $pattern['affected_barangays'],
                        'alert_level' => 'CRITICAL',
                        'message' => "POSSIBLE OUTBREAK: {$pattern['disease']} detected in {$pattern['barangay_count']} barangays with {$pattern['total_requests']} total cases"
                    ];
                }
            }
            
            return [
                'outbreak_alerts' => $outbreak_alerts,
                'symptom_patterns' => $symptom_patterns,
                'total_patterns' => count($symptom_patterns),
                'outbreak_count' => count($outbreak_alerts),
                'detection_time' => date('Y-m-d H:i:s'),
                'criteria' => [
                    'minimum_people_per_barangay' => 2,
                    'minimum_affected_barangays' => 10,
                    'total_barangays_in_city' => 24
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Outbreak detection failed: " . $e->getMessage());
            return [
                'outbreak_alerts' => [],
                'symptom_patterns' => [],
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Main detection method
     */
    public function detectSymptomAnomalies() {
        try {
            // Get recent requests
            $requests = $this->getRecentRequests();
            
            // Group by symptoms and barangay
            $grouped = $this->groupRequestsBySymptomsAndBarangay($requests);
            
            // Analyze patterns
            $anomalies = $this->analyzeSymptomPatternByBarangay($grouped);
            
            // Get summary
            $summary = $this->getAnomalySummary($anomalies);
            $most_critical = $this->getMostCriticalAnomaly($anomalies);
            
            return [
                'anomalies' => $anomalies,
                'summary' => $summary,
                'most_critical' => $most_critical,
                'detection_time' => date('Y-m-d H:i:s'),
                'time_window_hours' => $this->time_window_hours,
                'thresholds' => [
                    'anomaly_threshold' => $this->anomaly_threshold,
                    'spike_threshold' => $this->spike_threshold
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Symptom anomaly detection failed: " . $e->getMessage());
            return [
                'anomalies' => [],
                'summary' => ['total_anomalies' => 0],
                'most_critical' => null,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>
