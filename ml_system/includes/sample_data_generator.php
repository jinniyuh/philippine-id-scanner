<?php
/**
 * Sample Data Generator for ML Insights
 * Generates realistic sample data when insufficient real data is available
 */

class SampleDataGenerator {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Generate sample pharmaceutical usage data
     */
    public function generatePharmaceuticalUsage($months = 12) {
        $data = [];
        $base_usage = 15; // Base usage per month
        $seasonal_factor = 1.0;
        
        for ($i = 0; $i < $months; $i++) {
            $month = $i + 1;
            
            // Add seasonal variation (higher in rainy season)
            if ($month >= 6 && $month <= 10) {
                $seasonal_factor = 1.2; // 20% higher in rainy season
            } else {
                $seasonal_factor = 0.9; // 10% lower in dry season
            }
            
            // Add some random variation
            $variation = (rand(-20, 20) / 100); // ±20% variation
            $usage = round($base_usage * $seasonal_factor * (1 + $variation));
            
            // Ensure minimum usage
            $usage = max(5, $usage);
            
            $data[] = $usage;
        }
        
        return $data;
    }
    
    /**
     * Generate sample livestock population data
     */
    public function generateLivestockPopulation($months = 12) {
        $data = [];
        $base_population = 50; // Base population
        $growth_rate = 0.02; // 2% monthly growth
        
        for ($i = 0; $i < $months; $i++) {
            // Gradual growth with some variation
            $variation = (rand(-15, 15) / 100); // ±15% variation
            $population = round($base_population * pow(1 + $growth_rate, $i) * (1 + $variation));
            
            // Ensure minimum population
            $population = max(20, $population);
            
            $data[] = $population;
        }
        
        return $data;
    }
    
    /**
     * Generate sample poultry population data
     */
    public function generatePoultryPopulation($months = 12) {
        $data = [];
        $base_population = 120; // Base population
        $growth_rate = 0.03; // 3% monthly growth (higher than livestock)
        
        for ($i = 0; $i < $months; $i++) {
            // Gradual growth with some variation
            $variation = (rand(-20, 20) / 100); // ±20% variation
            $population = round($base_population * pow(1 + $growth_rate, $i) * (1 + $variation));
            
            // Ensure minimum population
            $population = max(50, $population);
            
            $data[] = $population;
        }
        
        return $data;
    }
    
    /**
     * Generate sample transaction volume data
     */
    public function generateTransactionVolume($months = 12) {
        $data = [];
        $base_transactions = 25; // Base transactions per month
        
        for ($i = 0; $i < $months; $i++) {
            $month = $i + 1;
            
            // Add seasonal variation (higher during planting/harvest seasons)
            if ($month == 3 || $month == 4 || $month == 9 || $month == 10) {
                $seasonal_factor = 1.3; // 30% higher during peak seasons
            } else {
                $seasonal_factor = 1.0;
            }
            
            // Add some random variation
            $variation = (rand(-25, 25) / 100); // ±25% variation
            $transactions = round($base_transactions * $seasonal_factor * (1 + $variation));
            
            // Ensure minimum transactions
            $transactions = max(10, $transactions);
            
            $data[] = $transactions;
        }
        
        return $data;
    }
    
    /**
     * Generate sample seasonal trends data
     */
    public function generateSeasonalTrends() {
        $seasonal_data = [];
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                  'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        // Base transaction counts per month (seasonal pattern)
        $base_counts = [15, 18, 25, 28, 22, 20, 18, 16, 24, 26, 20, 17];
        
        for ($i = 0; $i < 12; $i++) {
            $variation = (rand(-20, 20) / 100); // ±20% variation
            $count = round($base_counts[$i] * (1 + $variation));
            $count = max(5, $count); // Ensure minimum
            
            $seasonal_data[$i + 1] = [
                'count' => $count,
                'quantity' => $count * rand(2, 5) // Random quantity multiplier
            ];
        }
        
        return $seasonal_data;
    }
    
    /**
     * Generate sample low stock predictions
     */
    public function generateLowStockPredictions() {
        $predictions = [];
        
        // Sample pharmaceutical names
        $pharma_names = [
            'Vitamin A Supplement',
            'Antibiotic Injection',
            'Deworming Medicine',
            'Vaccine - FMD',
            'Mineral Supplement',
            'Pain Relief Medicine',
            'Antiseptic Solution',
            'Iron Supplement'
        ];
        
        // Generate 2-4 low stock items
        $num_items = rand(2, 4);
        $selected_names = array_rand($pharma_names, $num_items);
        
        foreach ($selected_names as $index) {
            $name = $pharma_names[$index];
            $current_stock = rand(1, 8);
            $predicted_demand = rand(8, 15);
            $days_until_stockout = round($current_stock / max(1, $predicted_demand / 30));
            
            $alert_level = 'info';
            if ($days_until_stockout <= 7) {
                $alert_level = 'critical';
            } elseif ($days_until_stockout <= 14) {
                $alert_level = 'warning';
            }
            
            $stockout_date = date('M d, Y', strtotime("+{$days_until_stockout} days"));
            
            $predictions[] = [
                'pharma_id' => $index + 1,
                'name' => $name,
                'current_stock' => $current_stock,
                'reorder_level' => 5,
                'predicted_demand' => $predicted_demand,
                'days_until_stockout' => $days_until_stockout,
                'stockout_date' => $stockout_date,
                'alert_level' => $alert_level,
                'recommended_order' => max(0, $predicted_demand * 2 - $current_stock)
            ];
        }
        
        return $predictions;
    }
    
    /**
     * Insert sample data into database (optional)
     */
    public function insertSampleData($data_type, $data) {
        try {
            switch ($data_type) {
                case 'pharmaceutical_usage':
                    // Insert sample transaction data
                    foreach ($data as $index => $usage) {
                        $date = date('Y-m-d', strtotime("-" . (count($data) - $index - 1) . " months"));
                        $sql = "INSERT INTO transactions (client_id, pharma_id, quantity, request_date, status) 
                                VALUES (1, 1, ?, ?, 'Approved')";
                        $stmt = $this->conn->prepare($sql);
                        $stmt->bind_param("is", $usage, $date);
                        $stmt->execute();
                    }
                    break;
                    
                case 'livestock_population':
                    // Insert sample livestock data
                    foreach ($data as $index => $population) {
                        $date = date('Y-m-d', strtotime("-" . (count($data) - $index - 1) . " months"));
                        $sql = "INSERT INTO livestock_poultry (animal_type, quantity, created_at) 
                                VALUES ('Livestock', ?, ?)";
                        $stmt = $this->conn->prepare($sql);
                        $stmt->bind_param("is", $population, $date);
                        $stmt->execute();
                    }
                    break;
                    
                case 'poultry_population':
                    // Insert sample poultry data
                    foreach ($data as $index => $population) {
                        $date = date('Y-m-d', strtotime("-" . (count($data) - $index - 1) . " months"));
                        $sql = "INSERT INTO livestock_poultry (animal_type, quantity, created_at) 
                                VALUES ('Poultry', ?, ?)";
                        $stmt = $this->conn->prepare($sql);
                        $stmt->bind_param("is", $population, $date);
                        $stmt->execute();
                    }
                    break;
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Check if we should use sample data
     */
    public function shouldUseSampleData($real_data_count, $minimum_required = 3) {
        return $real_data_count < $minimum_required;
    }
}
?>
