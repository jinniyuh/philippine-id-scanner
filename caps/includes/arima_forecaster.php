<?php
/**
 * ARIMA (AutoRegressive Integrated Moving Average) Implementation for PHP
 * Used for time series forecasting in the veterinary management system
 */

if (!class_exists('ARIMAForecaster')) {
class ARIMAForecaster {
    private $data;
    private $p; // AR order
    private $d; // Differencing order
    private $q; // MA order
    
    public function __construct($data, $p = 1, $d = 1, $q = 1) {
        $this->data = $data;
        $this->p = $p;
        $this->d = $d;
        $this->q = $q;
    }
    
    /**
     * Calculate moving average
     */
    private function movingAverage($data, $window) {
        $result = [];
        $n = count($data);
        
        for ($i = 0; $i < $n; $i++) {
            $start = max(0, $i - $window + 1);
            $end = $i + 1;
            $sum = 0;
            $count = 0;
            
            for ($j = $start; $j < $end; $j++) {
                $sum += $data[$j];
                $count++;
            }
            
            $result[] = $count > 0 ? $sum / $count : 0;
        }
        
        return $result;
    }
    
    /**
     * Calculate differencing
     */
    private function difference($data, $order = 1) {
        $result = $data;
        
        for ($d = 0; $d < $order; $d++) {
            $temp = [];
            for ($i = 1; $i < count($result); $i++) {
                $temp[] = $result[$i] - $result[$i - 1];
            }
            $result = $temp;
        }
        
        return $result;
    }
    
    /**
     * Calculate inverse differencing
     */
    private function inverseDifference($diffed, $original, $order = 1) {
        $result = $diffed;
        
        for ($d = 0; $d < $order; $d++) {
            $temp = [];
            $index = count($original) - count($result) - 1;
            $last_original = ($index >= 0 && $index < count($original)) ? $original[$index] : 0;
            
            for ($i = 0; $i < count($result); $i++) {
                if ($i == 0) {
                    $temp[] = $result[$i] + $last_original;
                } else {
                    $temp[] = $result[$i] + $temp[$i - 1];
                }
            }
            $result = $temp;
        }
        
        return $result;
    }
    
    /**
     * Calculate autocorrelation
     */
    private function autocorrelation($data, $lag) {
        $n = count($data);
        if ($lag >= $n) return 0;
        
        $mean = array_sum($data) / $n;
        $variance = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $variance += pow($data[$i] - $mean, 2);
        }
        $variance /= $n;
        
        if ($variance == 0) return 0;
        
        $autocorr = 0;
        for ($i = 0; $i < $n - $lag; $i++) {
            $autocorr += ($data[$i] - $mean) * ($data[$i + $lag] - $mean);
        }
        $autocorr /= ($n - $lag) * $variance;
        
        return $autocorr;
    }
    
    /**
     * Estimate AR parameters using Yule-Walker equations
     */
    private function estimateARParameters($data, $order) {
        if ($order == 0) return [];
        
        $n = count($data);
        $autocorr = [];
        
        for ($i = 0; $i <= $order; $i++) {
            $autocorr[] = $this->autocorrelation($data, $i);
        }
        
        // Simple estimation using first-order AR
        if ($order == 1) {
            return [$autocorr[1]];
        }
        
        // For higher orders, use a simplified approach
        $params = [];
        for ($i = 1; $i <= $order; $i++) {
            $params[] = $autocorr[$i] * 0.8; // Simplified estimation
        }
        
        return $params;
    }
    
    /**
     * Forecast using ARIMA model
     */
    public function forecast($steps = 1) {
        $n = count($this->data);
        if ($n < 3) {
            // Not enough data for meaningful forecast
            return array_fill(0, $steps, end($this->data));
        }
        
        // Apply differencing
        $diffed_data = $this->difference($this->data, $this->d);
        
        // Estimate AR parameters
        $ar_params = $this->estimateARParameters($diffed_data, $this->p);
        
        // Simple forecasting using AR component
        $forecast_diffed = [];
        $last_values = array_slice($diffed_data, -$this->p);
        
        for ($step = 0; $step < $steps; $step++) {
            $prediction = 0;
            
            // AR component
            for ($i = 0; $i < min($this->p, count($ar_params)); $i++) {
                $prediction += $ar_params[$i] * $last_values[count($last_values) - 1 - $i];
            }
            
            // Add some trend component
            if (count($diffed_data) > 1) {
                $trend = ($diffed_data[count($diffed_data) - 1] - $diffed_data[0]) / count($diffed_data);
                $prediction += $trend * 0.1;
            }
            
            $forecast_diffed[] = $prediction;
            $last_values[] = $prediction;
            array_shift($last_values);
        }
        
        // Inverse differencing to get final forecast
        $forecast = $this->inverseDifference($forecast_diffed, $this->data, $this->d);
        
        // Ensure non-negative values for counts
        foreach ($forecast as &$value) {
            $value = max(0, round($value));
        }
        
        return $forecast;
    }
    
    /**
     * Calculate forecast accuracy metrics
     */
    public function calculateAccuracy($actual, $predicted) {
        $n = count($actual);
        if ($n != count($predicted) || $n == 0) return null;
        
        $mae = 0;
        $mse = 0;
        $mape = 0;
        $total_actual = 0;
        $total_predicted = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $error = abs($actual[$i] - $predicted[$i]);
            $mae += $error;
            $mse += $error * $error;
            $total_actual += $actual[$i];
            $total_predicted += $predicted[$i];
            
            if ($actual[$i] != 0) {
                $mape += abs($error / $actual[$i]) * 100;
            }
        }
        
        // Calculate overall accuracy percentage
        $accuracy_percentage = 0;
        if ($total_actual > 0) {
            $accuracy_percentage = max(0, 100 - ($mape / $n));
        }
        
        return [
            'mae' => round($mae / $n, 2),
            'mse' => round($mse / $n, 2),
            'rmse' => round(sqrt($mse / $n), 2),
            'mape' => round($mape / $n, 2),
            'accuracy_percentage' => round($accuracy_percentage, 1)
        ];
    }
    
    /**
     * Calculate forecast accuracy using historical data for validation
     */
    public function validateForecastAccuracy($data, $validation_periods = 3) {
        if (count($data) < $validation_periods + 3) {
            return null;
        }
        
        // Use last validation_periods for testing
        $test_data = array_slice($data, -$validation_periods);
        $training_data = array_slice($data, 0, -$validation_periods);
        
        // Train on training data
        $forecaster = new ARIMAForecaster($training_data, $this->p, $this->d, $this->q);
        $predictions = $forecaster->forecast($validation_periods);
        
        // Calculate accuracy
        return $this->calculateAccuracy($test_data, $predictions);
    }
}

/**
 * Veterinary-specific forecasting functions
 */
if (!class_exists('VeterinaryForecaster')) {
class VeterinaryForecaster {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get pharmaceutical usage data for forecasting
     */
    public function getPharmaceuticalUsage($pharma_id = null, $months = 12) {
        $sql = "SELECT 
                    DATE_FORMAT(t.request_date, '%Y-%m') as month,
                    COALESCE(SUM(t.quantity), 0) as total_usage
                FROM transactions t
                WHERE t.status = 'Approved'";
        
        if ($pharma_id) {
            $sql .= " AND t.pharma_id = " . intval($pharma_id);
        }
        
        $sql .= " GROUP BY DATE_FORMAT(t.request_date, '%Y-%m')
                  ORDER BY month DESC
                  LIMIT " . intval($months);
        
        $result = $this->conn->query($sql);
        $data = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = (int)$row['total_usage'];
            }
        }
        
        return array_reverse($data); // Return in chronological order
    }
    
    /**
     * Get livestock population trends
     */
    public function getLivestockTrends($animal_type = null, $months = 12) {
        $sql = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COALESCE(SUM(quantity), 0) as total_population
                FROM livestock_poultry";
        
        if ($animal_type) {
            $sql .= " WHERE animal_type = '" . $this->conn->real_escape_string($animal_type) . "'";
        }
        
        $sql .= " GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                  ORDER BY month DESC
                  LIMIT " . intval($months);
        
        $result = $this->conn->query($sql);
        $data = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = (int)$row['total_population'];
            }
        }
        
        return array_reverse($data);
    }
    
    /**
     * Get transaction volume trends
     */
    public function getTransactionTrends($months = 12) {
        $sql = "SELECT 
                    DATE_FORMAT(request_date, '%Y-%m') as month,
                    COUNT(*) as transaction_count
                FROM transactions
                GROUP BY DATE_FORMAT(request_date, '%Y-%m')
                ORDER BY month DESC
                LIMIT " . intval($months);
        
        $result = $this->conn->query($sql);
        $data = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = (int)$row['transaction_count'];
            }
        }
        
        return array_reverse($data);
    }
    
    /**
     * Forecast pharmaceutical demand
     */
    public function forecastPharmaceuticalDemand($pharma_id = null, $months_ahead = 3) {
        $usage_data = $this->getPharmaceuticalUsage($pharma_id, 12);
        
        if (count($usage_data) < 3) {
            return ['error' => 'Insufficient data for forecasting. Need at least 3 months of historical data.'];
        }
        
        try {
            $forecaster = new ARIMAForecaster($usage_data, 1, 1, 1);
            $forecast = $forecaster->forecast($months_ahead);
            
            return [
                'forecast' => $forecast,
                'historical' => $usage_data,
                'months_ahead' => $months_ahead
            ];
        } catch (Exception $e) {
            return ['error' => 'Forecasting error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Forecast livestock population
     */
    public function forecastLivestockPopulation($animal_type = null, $months_ahead = 3) {
        $population_data = $this->getLivestockTrends($animal_type, 12);
        
        if (count($population_data) < 3) {
            return ['error' => 'Insufficient data for forecasting. Need at least 3 months of historical data.'];
        }
        
        try {
            $forecaster = new ARIMAForecaster($population_data, 1, 1, 1);
            $forecast = $forecaster->forecast($months_ahead);
            
            return [
                'forecast' => $forecast,
                'historical' => $population_data,
                'months_ahead' => $months_ahead
            ];
        } catch (Exception $e) {
            return ['error' => 'Forecasting error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get low stock predictions with enhanced details
     */
    public function getLowStockPredictions() {
        $sql = "SELECT p.pharma_id, p.name, p.stock, 5 as reorder_level
                FROM pharmaceuticals p
                WHERE p.stock <= 50"; // Check for low stock (increased threshold for better visibility)
        
        $result = $this->conn->query($sql);
        $predictions = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Forecast demand for this pharmaceutical
                $demand_forecast = $this->forecastPharmaceuticalDemand($row['pharma_id'], 1);
                $predicted_demand = isset($demand_forecast['forecast']) ? $demand_forecast['forecast'][0] : 1;
                
                // Calculate days until stockout
                $daily_demand = max(1, $predicted_demand / 30);
                $days_until_stockout = $row['stock'] > 0 ? round($row['stock'] / $daily_demand) : 0;
                
                // Determine alert level
                $alert_level = 'info';
                if ($days_until_stockout <= 7) {
                    $alert_level = 'critical';
                } elseif ($days_until_stockout <= 14) {
                    $alert_level = 'warning';
                }
                
                // Calculate stockout date
                $stockout_date = date('M d, Y', strtotime("+{$days_until_stockout} days"));
                
                $predictions[] = [
                    'pharma_id' => $row['pharma_id'],
                    'name' => $row['name'],
                    'current_stock' => $row['stock'],
                    'reorder_level' => $row['reorder_level'],
                    'predicted_demand' => $predicted_demand,
                    'days_until_stockout' => $days_until_stockout,
                    'stockout_date' => $stockout_date,
                    'alert_level' => $alert_level,
                    'recommended_order' => max(0, $predicted_demand * 2 - $row['stock']) // Order 2 months worth
                ];
            }
        }
        // NOTE: Sample data generation removed - only show real pharmaceutical data
        // If no low stock items, the array will be empty (which is accurate)
        
        return $predictions;
    }
    
    /**
     * Get critical alerts with specific predictions
     */
    public function getCriticalAlerts() {
        $alerts = [];
        
        // Stock alerts
        $low_stock = $this->getLowStockPredictions();
        foreach ($low_stock as $item) {
            if ($item['alert_level'] === 'critical') {
                $alerts[] = [
                    'type' => 'stockout',
                    'level' => 'critical',
                    'message' => "📉 {$item['name']} stockout in {$item['days_until_stockout']} days",
                    'details' => "Stockout predicted by {$item['stockout_date']}. Current stock: {$item['current_stock']}",
                    'action' => "Immediate restocking required - Order {$item['recommended_order']} units"
                ];
            } elseif ($item['alert_level'] === 'warning') {
                $alerts[] = [
                    'type' => 'stockout',
                    'level' => 'warning',
                    'message' => "⚠️ {$item['name']} running low",
                    'details' => "Stockout predicted by {$item['stockout_date']}. Current stock: {$item['current_stock']}",
                    'action' => "Plan restocking - Order {$item['recommended_order']} units"
                ];
            }
        }
        
        // Transaction volume anomaly detection
        $transaction_data = $this->getTransactionTrends(12);
        if (count($transaction_data) >= 6) {
            $recent_avg = array_sum(array_slice($transaction_data, -3)) / 3;
            $previous_avg = array_sum(array_slice($transaction_data, -6, 3)) / 3;
            
            if ($recent_avg < $previous_avg * 0.7) { // 30% drop
                $alerts[] = [
                    'type' => 'transaction',
                    'level' => 'warning',
                    'message' => "🛑 Transaction volume dropped unusually",
                    'details' => "Recent average: {$recent_avg} vs Previous: {$previous_avg}",
                    'action' => "Investigate service delivery issues"
                ];
            }
        }
        
        return $alerts;
    }
    
    /**
     * Get data points information with time ranges
     */
    public function getDataPointsInfo() {
        $info = [];
        
        // Pharmaceutical data
        $pharma_data = $this->getPharmaceuticalUsage(null, 12);
        $pharma_count = count($pharma_data);
        if ($pharma_count > 0) {
            $info['pharmaceutical'] = [
                'count' => $pharma_count,
                'description' => "{$pharma_count} months of pharmaceutical usage records analyzed",
                'time_range' => $this->getDataTimeRange('transactions', 'request_date')
            ];
        }
        
        // Livestock data
        $livestock_data = $this->getLivestockTrends('Livestock', 12);
        $livestock_count = count($livestock_data);
        if ($livestock_count > 0) {
            $info['livestock'] = [
                'count' => $livestock_count,
                'description' => "{$livestock_count} months of livestock population records processed",
                'time_range' => $this->getDataTimeRange('livestock_poultry', 'created_at', "animal_type = 'Livestock'")
            ];
        }
        
        // Poultry data
        $poultry_data = $this->getLivestockTrends('Poultry', 12);
        $poultry_count = count($poultry_data);
        if ($poultry_count > 0) {
            $info['poultry'] = [
                'count' => $poultry_count,
                'description' => "{$poultry_count} months of poultry population records processed",
                'time_range' => $this->getDataTimeRange('livestock_poultry', 'created_at', "animal_type = 'Poultry'")
            ];
        }
        
        // Transaction data
        $transaction_data = $this->getTransactionTrends(12);
        $transaction_count = count($transaction_data);
        if ($transaction_count > 0) {
            $info['transaction'] = [
                'count' => $transaction_count,
                'description' => "{$transaction_count} months of transaction records used for training",
                'time_range' => $this->getDataTimeRange('transactions', 'request_date')
            ];
        }
        
        return $info;
    }
    
    /**
     * Get time range for data
     */
    private function getDataTimeRange($table, $date_column, $where_clause = '') {
        $sql = "SELECT MIN({$date_column}) as start_date, MAX({$date_column}) as end_date FROM {$table}";
        if ($where_clause) {
            $sql .= " WHERE {$where_clause}";
        }
        
        $result = $this->conn->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            $start = date('M Y', strtotime($row['start_date']));
            $end = date('M Y', strtotime($row['end_date']));
            return "{$start} – {$end}";
        }
        
        return "Unknown";
    }
    
    /**
     * Get seasonal trends analysis
     */
    public function getSeasonalTrends() {
        $sql = "SELECT 
                    MONTH(request_date) as month,
                    COUNT(*) as transaction_count,
                    SUM(quantity) as total_quantity
                FROM transactions
                WHERE request_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY MONTH(request_date)
                ORDER BY month";
        
        $result = $this->conn->query($sql);
        $seasonal_data = array_fill(1, 12, ['count' => 0, 'quantity' => 0]);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $month = (int)$row['month'];
                $seasonal_data[$month]['count'] = (int)$row['transaction_count'];
                $seasonal_data[$month]['quantity'] = (int)$row['total_quantity'];
            }
        }
        
        return $seasonal_data;
    }
    
    /**
     * Generate historical month labels for the past N months
     */
    public function getHistoricalMonthLabels($months = 12) {
        $labels = [];
        $current_date = new DateTime();
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = clone $current_date;
            $date->modify("-{$i} months");
            $labels[] = $date->format('M Y');
        }
        
        return $labels;
    }
    
    /**
     * Generate forecast month labels for the next N months
     */
    public function getForecastMonthLabels($months = 3) {
        $labels = [];
        $current_date = new DateTime();
        
        for ($i = 1; $i <= $months; $i++) {
            $date = clone $current_date;
            $date->modify("+{$i} months");
            $labels[] = $date->format('M Y');
        }
        
        return $labels;
    }
    
    /**
     * Calculate accuracy metrics comparing actual vs predicted values
     * @param array $actual Actual values
     * @param array $predicted Predicted values
     * @return array Accuracy metrics including MAPE, RMSE, and accuracy percentage
     */
    public function calculateAccuracy($actual, $predicted) {
        if (count($actual) !== count($predicted) || count($actual) === 0) {
            return null;
        }
        
        $n = count($actual);
        $mape = 0; // Mean Absolute Percentage Error
        $mse = 0;  // Mean Squared Error
        $valid_count = 0;
        
        for ($i = 0; $i < $n; $i++) {
            // Calculate squared error for RMSE
            $error = $actual[$i] - $predicted[$i];
            $mse += pow($error, 2);
            
            // Calculate percentage error for MAPE (avoid division by zero)
            if ($actual[$i] != 0) {
                $mape += abs(($actual[$i] - $predicted[$i]) / $actual[$i]);
                $valid_count++;
            }
        }
        
        // Calculate final metrics
        $mse = $mse / $n;
        $rmse = sqrt($mse);
        $mape = $valid_count > 0 ? ($mape / $valid_count) * 100 : 0;
        
        // Calculate accuracy percentage (100% - MAPE)
        $accuracy_percentage = max(0, 100 - $mape);
        
        return [
            'mape' => round($mape, 2),
            'rmse' => round($rmse, 2),
            'accuracy_percentage' => round($accuracy_percentage, 1)
        ];
    }
    
    /**
     * Validate forecast accuracy using historical data
     * @param array $data Historical data points
     * @param int $validation_periods Number of periods to use for validation
     * @return array|null Accuracy metrics or null if insufficient data
     */
    public function validateForecastAccuracy($data, $validation_periods = 3) {
        if (count($data) < $validation_periods + 3) {
            return null;
        }
        
        // Split data into training and testing sets
        $test_data = array_slice($data, -$validation_periods);
        $training_data = array_slice($data, 0, -$validation_periods);
        
        // Create ARIMA forecaster with training data
        $arima = new ARIMAForecaster($training_data, 1, 1, 1);
        $predictions = $arima->forecast($validation_periods);
        
        // Calculate accuracy metrics
        $accuracy_metrics = $this->calculateAccuracy($test_data, $predictions);
        
        return $accuracy_metrics;
    }
}
} // End of VeterinaryForecaster class check
} // End of ARIMAForecaster class check
?>
