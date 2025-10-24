<?php
// Generate sample data for forecasting testing
include 'includes/conn.php';

echo "<h2>Generating Sample Forecast Data</h2>";

// Check if we should generate data
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    
    // Generate sample transactions
    echo "<h3>Generating Sample Transactions...</h3>";
    
    $pharma_ids = [1, 2, 3, 5, 9, 10]; // Available pharmaceutical IDs
    $barangays = ['Poblacion', 'Alianza', 'Bacong-Montilla', 'Bagroy', 'Balingasag', 'Binubuhan', 'Busay', 'Calumangan', 'Caridad', 'Dulao'];
    $client_ids = [1, 4, 7, 8, 17]; // Available client IDs
    
    // Generate transactions for the last 12 months
    for ($month_offset = 11; $month_offset >= 0; $month_offset--) {
        $date = date('Y-m-d', strtotime("-$month_offset months"));
        $month_start = date('Y-m-01', strtotime($date));
        $month_end = date('Y-m-t', strtotime($date));
        
        // Generate 5-15 transactions per month
        $transaction_count = rand(5, 15);
        
        for ($i = 0; $i < $transaction_count; $i++) {
            $pharma_id = $pharma_ids[array_rand($pharma_ids)];
            $barangay = $barangays[array_rand($barangays)];
            $client_id = $client_ids[array_rand($client_ids)];
            $quantity = rand(1, 20);
            
            // Add some seasonal variation
            $month = (int)date('n', strtotime($date));
            if ($month >= 11 || $month <= 2) { // Winter months - higher demand
                $quantity = (int)($quantity * 1.3);
            } elseif ($month >= 6 && $month <= 8) { // Summer months - lower demand
                $quantity = (int)($quantity * 0.8);
            }
            
            $transaction_date = date('Y-m-d', strtotime($month_start . ' +' . rand(0, 27) . ' days'));
            
            $sql = "INSERT INTO transactions (client_id, user_id, pharma_id, quantity, barangay, status, request_date, issued_date, type) 
                    VALUES (?, 1, ?, ?, ?, 'Approved', ?, NOW(), 'Pharmaceutical')";
            
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("iiiss", $client_id, $pharma_id, $quantity, $barangay, $transaction_date);
                if ($stmt->execute()) {
                    echo "✅ Generated transaction for $barangay - $quantity units on $transaction_date<br>";
                } else {
                    echo "❌ Error generating transaction: " . $stmt->error . "<br>";
                }
                $stmt->close();
            }
        }
    }
    
    // Generate sample livestock/poultry data
    echo "<h3>Generating Sample Livestock/Poultry Data...</h3>";
    
    $animal_types = ['Livestock', 'Poultry'];
    $species = ['Cattle', 'Swine', 'Goat', 'Chicken', 'Duck'];
    
    for ($month_offset = 11; $month_offset >= 0; $month_offset--) {
        $date = date('Y-m-d', strtotime("-$month_offset months"));
        
        // Generate 3-8 livestock/poultry records per month
        $record_count = rand(3, 8);
        
        for ($i = 0; $i < $record_count; $i++) {
            $animal_type = $animal_types[array_rand($animal_types)];
            $specie = $species[array_rand($species)];
            $quantity = rand(5, 50);
            $client_id = $client_ids[array_rand($client_ids)];
            
            $created_date = date('Y-m-d H:i:s', strtotime($date . ' +' . rand(0, 27) . ' days +' . rand(0, 23) . ' hours'));
            
            $sql = "INSERT INTO livestock_poultry (client_id, animal_type, species, quantity, created_at) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("issis", $client_id, $animal_type, $specie, $quantity, $created_date);
                if ($stmt->execute()) {
                    echo "✅ Generated $animal_type record - $specie ($quantity) on $created_date<br>";
                } else {
                    echo "❌ Error generating livestock record: " . $stmt->error . "<br>";
                }
                $stmt->close();
            }
        }
    }
    
    echo "<h3>✅ Sample data generation completed!</h3>";
    echo "<a href='test_forecast_debug.php'>Test Forecasting Again</a><br>";
    echo "<a href='admin_ml_insights.php'>View ML Insights</a><br>";
    
} else {
    echo "<p>This will generate sample transaction and livestock data for the last 12 months to test the forecasting functionality.</p>";
    echo "<p><strong>Warning:</strong> This will add sample data to your database.</p>";
    echo "<a href='?confirm=yes' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Generate Sample Data</a><br><br>";
    echo "<a href='test_forecast_debug.php'>Back to Debug Test</a><br>";
}

$conn->close();
?>
