<?php
// Simple test to debug ML Insights error
session_start();

// Simulate admin session for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

echo "Testing ML Insights...\n";

try {
    include 'includes/conn.php';
    echo "✓ Database connection included\n";
    
    include 'includes/arima_forecaster.php';
    echo "✓ ARIMA forecaster included\n";
    
    $forecaster = new VeterinaryForecaster($conn);
    echo "✓ VeterinaryForecaster instantiated\n";
    
    // Test a simple forecast
    $pharma_forecast = $forecaster->forecastPharmaceuticalDemand(null, 3);
    echo "✓ Pharmaceutical forecast completed\n";
    
    if (isset($pharma_forecast['error'])) {
        echo "✗ Forecast error: " . $pharma_forecast['error'] . "\n";
    } else {
        echo "✓ Forecast successful\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
