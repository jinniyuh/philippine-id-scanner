<?php
/**
 * Global Alert System
 * Provides system-wide alert functionality that can be used across all modules
 */

if (!class_exists('GlobalAlert')) {
    class GlobalAlert {
        private $conn;
        
        public function __construct($conn) {
            $this->conn = $conn;
        }
        
        /**
         * Check for high-risk and critical animals across the system
         */
        public function checkSystemAlerts() {
            try {
                // Get health risk assessment data
                $health_query = "
                    SELECT 
                        COUNT(CASE WHEN hra.risk_level = 'Critical' THEN 1 END) as critical_count,
                        COUNT(CASE WHEN hra.risk_level = 'High' THEN 1 END) as high_count
                    FROM health_risk_assessments hra
                    WHERE hra.assessment_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    AND hra.status = 'Active'
                ";
                
                $health_result = $this->conn->query($health_query);
                $health_data = $health_result->fetch_assoc();
                
                // Get symptoms-based risk data from pharmaceutical requests
                $symptoms_query = "
                    SELECT 
                        COUNT(CASE WHEN pr.symptoms LIKE '%sudden death%' OR pr.symptoms LIKE '%biglaang pagkamatay%' 
                                   OR pr.symptoms LIKE '%paralysis%' OR pr.symptoms LIKE '%pagkaparalisa%'
                                   OR pr.symptoms LIKE '%bloody vomiting%' OR pr.symptoms LIKE '%pagsusuka na may dugo%'
                                   OR pr.symptoms LIKE '%bloody diarrhea%' OR pr.symptoms LIKE '%pagtatae na may dugo%'
                                   OR pr.symptoms LIKE '%hirap sa paghinga%' THEN 1 END) as critical_count,
                        COUNT(CASE WHEN pr.symptoms LIKE '%difficulty breathing%' OR pr.symptoms LIKE '%hirap huminga%'
                                   OR pr.symptoms LIKE '%convulsions%' OR pr.symptoms LIKE '%kombulsyon%'
                                   OR pr.symptoms LIKE '%high fever%' OR pr.symptoms LIKE '%mataas na lagnat%'
                                   OR pr.symptoms LIKE '%swollen neck%' OR pr.symptoms LIKE '%namamagang leeg%' THEN 1 END) as high_count
                    FROM pharmaceutical_requests pr
                    WHERE pr.request_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    AND pr.symptoms IS NOT NULL 
                    AND pr.symptoms != ''
                ";
                
                $symptoms_result = $this->conn->query($symptoms_query);
                $symptoms_data = $symptoms_result->fetch_assoc();
                
                // Combine counts
                $total_critical = ($health_data['critical_count'] ?? 0) + ($symptoms_data['critical_count'] ?? 0);
                $total_high = ($health_data['high_count'] ?? 0) + ($symptoms_data['high_count'] ?? 0);
                
                return [
                    'critical_count' => $total_critical,
                    'high_count' => $total_high,
                    'has_alerts' => ($total_critical > 0 || $total_high > 0)
                ];
                
            } catch (Exception $e) {
                return [
                    'critical_count' => 0,
                    'high_count' => 0,
                    'has_alerts' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        /**
         * Get alert data for JavaScript
         */
        public function getAlertData() {
            $alerts = $this->checkSystemAlerts();
            
            if ($alerts['critical_count'] > 0) {
                return [
                    'type' => 'critical',
                    'count' => $alerts['critical_count'],
                    'message' => 'CRITICAL RISK ALERT: Animals requiring immediate emergency care!',
                    'title' => 'Critical Health Alert'
                ];
            } elseif ($alerts['high_count'] > 0) {
                return [
                    'type' => 'high',
                    'count' => $alerts['high_count'],
                    'message' => 'HIGH RISK ALERT: Animals needing immediate attention!',
                    'title' => 'High Risk Alert'
                ];
            }
            
            return null;
        }
    }
}
?>
