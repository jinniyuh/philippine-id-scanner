<?php
session_start();
include 'includes/conn.php';

// Simulate admin session for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

header('Content-Type: application/json');

$diagnostics = [
    'database_connection' => false,
    'database_name' => '',
    'required_tables' => [],
    'table_data_counts' => [],
    'arima_forecaster' => false,
    'errors' => [],
    'recommendations' => []
];

try {
    // Test database connection
    if ($conn && !$conn->connect_error) {
        $diagnostics['database_connection'] = true;
        $result = $conn->query("SELECT DATABASE()");
        if ($result) {
            $diagnostics['database_name'] = $result->fetch_row()[0];
        }
    } else {
        $diagnostics['errors'][] = "Database connection failed: " . ($conn->connect_error ?? 'Unknown error');
    }
    
    // Check required tables for ML Insights
    $required_tables = ['transactions', 'livestock_poultry', 'pharmaceuticals', 'pharmaceutical_requests'];
    
    foreach ($required_tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            $diagnostics['required_tables'][$table] = true;
            
            // Count records in each table
            $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
            if ($count_result) {
                $count_data = $count_result->fetch_assoc();
                $diagnostics['table_data_counts'][$table] = $count_data['count'];
            }
        } else {
            $diagnostics['required_tables'][$table] = false;
            $diagnostics['errors'][] = "Table '$table' does not exist";
        }
    }
    
    // Test ARIMA forecaster
    if (file_exists('includes/arima_forecaster.php')) {
        include 'includes/arima_forecaster.php';
        if (class_exists('VeterinaryForecaster')) {
            $diagnostics['arima_forecaster'] = true;
        } else {
            $diagnostics['errors'][] = "VeterinaryForecaster class not found";
        }
    } else {
        $diagnostics['errors'][] = "arima_forecaster.php file not found";
    }
    
    // Check if we have enough data for forecasting
    $total_records = array_sum($diagnostics['table_data_counts']);
    if ($total_records < 10) {
        $diagnostics['errors'][] = "Insufficient data for ML forecasting (need at least 10 records)";
        $diagnostics['recommendations'][] = "Add more sample data using the data generation scripts";
    }
    
    // Check for missing tables
    $missing_tables = array_keys(array_filter($diagnostics['required_tables'], function($exists) { return !$exists; }));
    if (!empty($missing_tables)) {
        $diagnostics['recommendations'][] = "Create missing tables: " . implode(', ', $missing_tables);
    }
    
} catch (Exception $e) {
    $diagnostics['errors'][] = "Exception: " . $e->getMessage();
}

echo json_encode($diagnostics, JSON_PRETTY_PRINT);
?>
