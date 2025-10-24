<?php
/**
 * ML Demand Forecaster
 * Integrates Python ML models for demand forecasting
 */

class MLDemandForecaster {
    private $conn;
    private $python_path;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->python_path = 'python'; // Adjust path as needed
    }
    
    /**
     * Forecast pharmaceutical demand using Python ML
     */
    public function forecastPharmaceuticalDemand($pharma_id = null, $months_ahead = 3) {
        try {
            // Get historical pharmaceutical usage data
            $historical_data = $this->getPharmaceuticalUsageData($pharma_id, 12);
            
            if (empty($historical_data)) {
                return [
                    'error' => 'No historical data available',
                    'forecast' => $this->generateFallbackForecast($months_ahead)
                ];
            }
            
            // Call Python ML forecasting script
            $result = $this->callPythonForecast('pharmaceutical', $historical_data, $months_ahead);
            
            if (isset($result['error'])) {
                // Fallback to simple forecast if ML fails
                return [
                    'forecast' => $this->generateFallbackForecast($months_ahead, $historical_data),
                    'historical' => $historical_data,
                    'model' => 'fallback',
                    'ml_error' => $result['error']
                ];
            }
            
            // Add historical data to result
            $result['historical'] = $historical_data;
            
            return $result;
            
        } catch (Exception $e) {
            error_log("ML Pharmaceutical Forecast failed: " . $e->getMessage());
            return [
                'error' => 'Forecasting failed',
                'forecast' => $this->generateFallbackForecast($months_ahead)
            ];
        }
    }
    
    /**
     * Forecast livestock population/demand
     */
    public function forecastLivestockDemand($months_ahead = 3) {
        try {
            // Get historical livestock data
            $historical_data = $this->getLivestockPopulationData(12);
            
            if (empty($historical_data)) {
                return [
                    'error' => 'No historical data available',
                    'forecast' => $this->generateFallbackForecast($months_ahead)
                ];
            }
            
            // Call Python ML forecasting script
            $result = $this->callPythonForecast('livestock', $historical_data, $months_ahead);
            
            if (isset($result['error'])) {
                return [
                    'forecast' => $this->generateFallbackForecast($months_ahead, $historical_data),
                    'historical' => $historical_data,
                    'model' => 'fallback'
                ];
            }
            
            $result['historical'] = $historical_data;
            return $result;
            
        } catch (Exception $e) {
            error_log("ML Livestock Forecast failed: " . $e->getMessage());
            return [
                'error' => 'Forecasting failed',
                'forecast' => $this->generateFallbackForecast($months_ahead)
            ];
        }
    }
    
    /**
     * Forecast poultry population/demand
     */
    public function forecastPoultryDemand($months_ahead = 3) {
        try {
            // Get historical poultry data
            $historical_data = $this->getPoultryPopulationData(12);
            
            if (empty($historical_data)) {
                return [
                    'error' => 'No historical data available',
                    'forecast' => $this->generateFallbackForecast($months_ahead)
                ];
            }
            
            // Call Python ML forecasting script
            $result = $this->callPythonForecast('poultry', $historical_data, $months_ahead);
            
            if (isset($result['error'])) {
                return [
                    'forecast' => $this->generateFallbackForecast($months_ahead, $historical_data),
                    'historical' => $historical_data,
                    'model' => 'fallback'
                ];
            }
            
            $result['historical'] = $historical_data;
            return $result;
            
        } catch (Exception $e) {
            error_log("ML Poultry Forecast failed: " . $e->getMessage());
            return [
                'error' => 'Forecasting failed',
                'forecast' => $this->generateFallbackForecast($months_ahead)
            ];
        }
    }
    
    /**
     * Call Python ML forecasting script
     */
    private function callPythonForecast($type, $historical_data, $months_ahead) {
        // Prepare configuration
        $config = [
            'type' => $type,
            'historical_data' => array_values($historical_data),
            'months_ahead' => $months_ahead
        ];
        
        // Save config to temporary file
        $temp_file = tempnam(sys_get_temp_dir(), 'ml_forecast_');
        file_put_contents($temp_file, json_encode($config));
        
        // Call Python script
        $command = "{$this->python_path} ml_demand_forecast.py " . escapeshellarg($temp_file);
        $output = shell_exec($command . ' 2>&1');
        
        // Clean up
        unlink($temp_file);
        
        if (!$output) {
            return ['error' => 'Python script execution failed - no output'];
        }
        
        $result = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'Invalid response from Python: ' . $output];
        }
        
        return $result;
    }
    
    /**
     * Get pharmaceutical usage data from database
     */
    private function getPharmaceuticalUsageData($pharma_id = null, $months = 12) {
        $data = [];
        
        // Get monthly pharmaceutical request counts
        $sql = "SELECT 
                DATE_FORMAT(request_date, '%Y-%m') as month,
                COUNT(*) as count
                FROM pharmaceutical_requests
                WHERE status = 'Approved'
                AND request_date >= DATE_SUB(NOW(), INTERVAL ? MONTH)";
        
        if ($pharma_id) {
            $sql .= " AND pharmaceutical_id = ?";
        }
        
        $sql .= " GROUP BY DATE_FORMAT(request_date, '%Y-%m')
                 ORDER BY month ASC";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($pharma_id) {
            $stmt->bind_param("ii", $months, $pharma_id);
        } else {
            $stmt->bind_param("i", $months);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $data[] = (float)$row['count'];
        }
        
        return $data;
    }
    
    /**
     * Get livestock population data from database
     */
    private function getLivestockPopulationData($months = 12) {
        $data = [];
        
        $sql = "SELECT 
                DATE_FORMAT(registration_date, '%Y-%m') as month,
                COUNT(*) as count
                FROM livestock
                WHERE registration_date >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(registration_date, '%Y-%m')
                ORDER BY month ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $months);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $data[] = (float)$row['count'];
        }
        
        return $data;
    }
    
    /**
     * Get poultry population data from database
     */
    private function getPoultryPopulationData($months = 12) {
        $data = [];
        
        $sql = "SELECT 
                DATE_FORMAT(registration_date, '%Y-%m') as month,
                COUNT(*) as count
                FROM poultry
                WHERE registration_date >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(registration_date, '%Y-%m')
                ORDER BY month ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $months);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $data[] = (float)$row['count'];
        }
        
        return $data;
    }
    
    /**
     * Generate fallback forecast (simple linear trend)
     */
    private function generateFallbackForecast($months_ahead, $historical_data = null) {
        if (!$historical_data || count($historical_data) < 2) {
            // Return flat forecast if no data
            return array_fill(0, $months_ahead, 10);
        }
        
        // Calculate simple linear trend
        $n = count($historical_data);
        $x = range(0, $n - 1);
        $y = array_values($historical_data);
        
        // Calculate slope and intercept
        $x_mean = array_sum($x) / $n;
        $y_mean = array_sum($y) / $n;
        
        $numerator = 0;
        $denominator = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $numerator += ($x[$i] - $x_mean) * ($y[$i] - $y_mean);
            $denominator += pow($x[$i] - $x_mean, 2);
        }
        
        $slope = $denominator != 0 ? $numerator / $denominator : 0;
        $intercept = $y_mean - ($slope * $x_mean);
        
        // Generate forecast
        $forecast = [];
        for ($i = 1; $i <= $months_ahead; $i++) {
            $value = $intercept + ($slope * ($n + $i - 1));
            $forecast[] = max(0, round($value, 1));
        }
        
        return $forecast;
    }
    
    /**
     * Get month labels for charts
     */
    public function getForecastMonthLabels($months_ahead) {
        $labels = [];
        for ($i = 1; $i <= $months_ahead; $i++) {
            $date = date('M Y', strtotime("+{$i} months"));
            $labels[] = $date;
        }
        return $labels;
    }
    
    public function getHistoricalMonthLabels($months_back) {
        $labels = [];
        for ($i = $months_back; $i >= 1; $i--) {
            $date = date('M Y', strtotime("-{$i} months"));
            $labels[] = $date;
        }
        return $labels;
    }
}
?>

