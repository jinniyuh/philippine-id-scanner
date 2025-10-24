<?php
/**
 * Simple test for health risk monitoring
 * This is a minimal test to check basic functionality
 */

// Start session and set admin user for testing
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['name'] = 'Test Admin';

echo "<h1>Simple Health Risk Test</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; }</style>";

try {
    // Include required files
    include 'includes/conn.php';
    include 'includes/health_risk_assessor.php';
    
    echo "<p>✅ Files included successfully</p>";
    
    // Test database connection
    if ($conn) {
        echo "<p>✅ Database connected</p>";
    } else {
        echo "<p>❌ Database connection failed</p>";
        exit;
    }
    
    // Check if tables exist
    $tables = ['health_risk_assessments', 'health_indicators', 'disease_patterns'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "<p>✅ Table '$table' exists</p>";
        } else {
            echo "<p>❌ Table '$table' missing - run the SQL script first</p>";
        }
    }
    
    // Test if we have animals
    $result = $conn->query("SELECT COUNT(*) as count FROM livestock_poultry");
    $animal_count = $result->fetch_assoc()['count'];
    echo "<p>Animals in database: $animal_count</p>";
    
    if ($animal_count > 0) {
        // Test health risk assessor
        $assessor = new HealthRiskAssessor($conn);
        echo "<p>✅ HealthRiskAssessor created</p>";
        
        // Get first animal
        $result = $conn->query("SELECT animal_id FROM livestock_poultry LIMIT 1");
        $animal_id = $result->fetch_assoc()['animal_id'];
        
        // Test assessment
        $assessment = $assessor->assessAnimalHealthRisk($animal_id);
        
        if (isset($assessment['error'])) {
            echo "<p>❌ Assessment failed: " . $assessment['error'] . "</p>";
        } else {
            echo "<p>✅ Assessment successful!</p>";
            echo "<p>Risk Score: " . $assessment['risk_score'] . "%</p>";
            echo "<p>Risk Level: " . $assessment['risk_level'] . "</p>";
        }
    } else {
        echo "<p>⚠️ No animals found - add some animals first</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Quick Links:</h3>";
echo "<ul>";
echo "<li><a href='debug_health_risk.php'>Full Debug Script</a></li>";
echo "<li><a href='admin_health_risk_monitoring.php'>Health Risk Monitoring</a></li>";
echo "<li><a href='admin_dashboard.php'>Admin Dashboard</a></li>";
echo "<li><a href='login.php'>Login Page</a></li>";
echo "</ul>";
?>
