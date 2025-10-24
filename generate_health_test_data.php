<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

echo "<h2>Generating Health Monitoring Test Data</h2>";

// Check if we should generate data
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    
    // Get existing livestock/poultry records
    $result = $conn->query("SELECT client_id, species, quantity, barangay FROM livestock_poultry LIMIT 20");
    $animals = [];
    while ($row = $result->fetch_assoc()) {
        $animals[] = $row;
    }
    
    if (empty($animals)) {
        echo "<div class='alert alert-warning'>No animals found. Please add some livestock/poultry data first.</div>";
        echo "<a href='admin_livestock_poultry.php' class='btn btn-primary'>Add Animals</a>";
        exit();
    }
    
    echo "<h3>Updating Health Monitoring Data...</h3>";
    
    foreach ($animals as $animal) {
        $client_id = $animal['client_id'];
        $species = $animal['species'];
        
        // Generate random health check dates (some recent, some overdue)
        $health_check_options = [
            date('Y-m-d', strtotime('-10 days')), // Recent
            date('Y-m-d', strtotime('-30 days')), // Recent
            date('Y-m-d', strtotime('-60 days')), // Recent
            date('Y-m-d', strtotime('-120 days')), // Overdue
            date('Y-m-d', strtotime('-200 days')), // Overdue
            null // No health check
        ];
        
        $health_check_date = $health_check_options[array_rand($health_check_options)];
        $health_status = ['Healthy', 'Healthy', 'Healthy', 'Under Observation', 'Recovering'][array_rand([0,1,2,3,4])];
        
        // Generate random vaccination dates (some recent, some overdue)
        $vaccination_options = [
            date('Y-m-d', strtotime('-200 days')), // Recent (within 1 year)
            date('Y-m-d', strtotime('-300 days')), // Recent
            date('Y-m-d', strtotime('-400 days')), // Overdue
            date('Y-m-d', strtotime('-500 days')), // Overdue
            null // No vaccination
        ];
        
        $vaccination_date = $vaccination_options[array_rand($vaccination_options)];
        
        // Update the animal record
        $sql = "UPDATE livestock_poultry 
                SET health_status = ?, 
                    last_vaccination = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE client_id = ? AND species = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssis", $health_status, $vaccination_date, $client_id, $species);
        
        if ($stmt->execute()) {
            echo "✅ Updated health data for {$species} (Client ID: {$client_id})<br>";
        } else {
            echo "❌ Error updating health data for {$species}: " . $stmt->error . "<br>";
        }
        
        $stmt->close();
    }
    
    echo "<h3>✅ Health monitoring test data generation completed!</h3>";
    echo "<p><strong>Generated data includes:</strong></p>";
    echo "<ul>";
    echo "<li>Recent health checks (last 30 days)</li>";
    echo "<li>Overdue health checks (90+ days)</li>";
    echo "<li>Recent vaccinations (within 1 year)</li>";
    echo "<li>Overdue vaccinations (1+ years)</li>";
    echo "<li>Animals with no health records</li>";
    echo "</ul>";
    echo "<a href='admin_health_monitoring.php' class='btn btn-primary'>View Health Monitoring</a><br>";
    echo "<a href='admin_dashboard.php' class='btn btn-secondary'>Return to Dashboard</a><br>";
    
} else {
    echo "<p>This will generate test data for the health monitoring system by updating existing animal records with:</p>";
    echo "<ul>";
    echo "<li>Random health check dates (some recent, some overdue)</li>";
    echo "<li>Random vaccination dates (some recent, some overdue)</li>";
    echo "<li>Various health statuses</li>";
    echo "</ul>";
    echo "<p><strong>Warning:</strong> This will update existing animal records with test data.</p>";
    echo "<a href='?confirm=yes' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Generate Health Test Data</a><br><br>";
    echo "<a href='admin_dashboard.php'>Back to Dashboard</a><br>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Health Test Data</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Content is generated by PHP above -->
    </div>
</body>
</html>
