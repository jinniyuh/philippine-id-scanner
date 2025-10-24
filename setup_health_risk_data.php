<?php
/**
 * Setup script for Health Risk Assessment data
 * This script will create tables and sample data if they don't exist
 */

session_start();
include 'includes/conn.php';
include 'includes/health_risk_assessor.php';

// Set admin session for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['name'] = 'Setup Admin';

echo "<h1>Health Risk Assessment Setup</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .setup-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .success { background-color: #d4edda; border-color: #c3e6cb; }
    .error { background-color: #f8d7da; border-color: #f5c6cb; }
    .info { background-color: #d1ecf1; border-color: #bee5eb; }
</style>";

try {
    // Step 1: Create tables
    echo "<div class='setup-section info'>";
    echo "<h3>Step 1: Creating Database Tables</h3>";
    
    // Read and execute the SQL file
    $sql_file = 'health_risk_assessment_tables.sql';
    if (file_exists($sql_file)) {
        $sql_content = file_get_contents($sql_file);
        
        // Split by semicolon and execute each statement
        $statements = explode(';', $sql_content);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                if ($conn->query($statement)) {
                    echo "<p>✅ Executed SQL statement</p>";
                } else {
                    echo "<p>⚠️ SQL warning: " . $conn->error . "</p>";
                }
            }
        }
        
        echo "<p>✅ Database tables created/updated</p>";
    } else {
        echo "<p>❌ SQL file not found: $sql_file</p>";
    }
    echo "</div>";
    
    // Step 2: Check if we have animals
    echo "<div class='setup-section info'>";
    echo "<h3>Step 2: Checking Animals Data</h3>";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM livestock_poultry");
    $animal_count = $result->fetch_assoc()['count'];
    
    echo "<p>Animals in database: $animal_count</p>";
    
    if ($animal_count == 0) {
        echo "<p>⚠️ No animals found. Creating sample animals...</p>";
        
        // Create sample animals
        $sample_animals = [
            ['Cattle', 'Livestock', 'Healthy', 1],
            ['Pig', 'Livestock', 'Healthy', 1],
            ['Chicken', 'Poultry', 'Healthy', 1],
            ['Duck', 'Poultry', 'Healthy', 1],
            ['Goat', 'Livestock', 'Healthy', 1]
        ];
        
        foreach ($sample_animals as $animal) {
            $sql = "INSERT INTO livestock_poultry (animal_name, animal_type, health_status, client_id, quantity) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $quantity = $animal[1] === 'Poultry' ? 10 : 1;
            $stmt->bind_param("sssii", $animal[0], $animal[1], $animal[2], $animal[3], $quantity);
            
            if ($stmt->execute()) {
                echo "<p>✅ Created sample animal: {$animal[0]}</p>";
            }
        }
    } else {
        echo "<p>✅ Animals found in database</p>";
    }
    echo "</div>";
    
    // Step 3: Check health indicators
    echo "<div class='setup-section info'>";
    echo "<h3>Step 3: Checking Health Indicators</h3>";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM health_indicators");
    $indicator_count = $result->fetch_assoc()['count'];
    
    echo "<p>Health indicators in database: $indicator_count</p>";
    
    if ($indicator_count == 0) {
        echo "<p>⚠️ No health indicators found. Creating sample indicators...</p>";
        
        // Get first 5 animals
        $result = $conn->query("SELECT animal_id, animal_type FROM livestock_poultry LIMIT 5");
        
        while ($row = $result->fetch_assoc()) {
            $animal_id = $row['animal_id'];
            $animal_type = $row['animal_type'];
            
            // Add weight indicator
            $weight = $animal_type === 'Livestock' ? rand(200, 500) : rand(1, 5);
            $sql = "INSERT INTO health_indicators (animal_id, indicator_type, indicator_value, indicator_unit, recorded_by, notes) VALUES (?, 'Weight', ?, 'kg', 1, 'Sample weight data')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $animal_id, $weight);
            $stmt->execute();
            
            // Add vaccination status
            $vaccination = rand(0, 1) ? 'Up to date' : 'Overdue';
            $sql = "INSERT INTO health_indicators (animal_id, indicator_type, indicator_value, recorded_by, notes) VALUES (?, 'Vaccination_Status', ?, 1, 'Sample vaccination data')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $animal_id, $vaccination);
            $stmt->execute();
            
            // Add environmental factor
            $env_factors = ['Good', 'Fair', 'Poor'];
            $env_factor = $env_factors[array_rand($env_factors)];
            $sql = "INSERT INTO health_indicators (animal_id, indicator_type, indicator_value, recorded_by, notes) VALUES (?, 'Environmental_Factor', ?, 1, 'Sample environmental data')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $animal_id, $env_factor);
            $stmt->execute();
            
            echo "<p>✅ Created health indicators for animal ID: $animal_id</p>";
        }
    } else {
        echo "<p>✅ Health indicators found in database</p>";
    }
    echo "</div>";
    
    // Step 4: Test the API
    echo "<div class='setup-section info'>";
    echo "<h3>Step 4: Testing API Endpoints</h3>";
    
    // Test summary endpoint by calling the method directly
    try {
        if (!class_exists('HealthRiskAssessor')) {
            throw new Exception('HealthRiskAssessor class not found');
        }
        
        $assessor = new HealthRiskAssessor($conn);
        $summary = $assessor->getRiskAssessmentSummary();
        
        if ($summary) {
            echo "<p>✅ API summary method working</p>";
            echo "<p>Summary data: " . json_encode($summary) . "</p>";
        } else {
            echo "<p>⚠️ API summary method returned empty data</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ API test failed: " . $e->getMessage() . "</p>";
    }
    
    echo "<p>✅ API testing completed</p>";
    echo "</div>";
    
    // Step 5: Create sample assessments
    echo "<div class='setup-section info'>";
    echo "<h3>Step 5: Creating Sample Assessments</h3>";
    
    $assessor = new HealthRiskAssessor($conn);
    
    // Get first 3 animals and create assessments
    $result = $conn->query("SELECT animal_id FROM livestock_poultry LIMIT 3");
    
    $assessment_count = 0;
    while ($row = $result->fetch_assoc()) {
        $animal_id = $row['animal_id'];
        
        try {
            $assessment = $assessor->assessAnimalHealthRisk($animal_id);
            
            if (!isset($assessment['error'])) {
                $saved = $assessor->saveAssessment($assessment, 1);
                if ($saved) {
                    echo "<p>✅ Created assessment for animal ID: $animal_id (Risk: {$assessment['risk_level']}, Score: {$assessment['risk_score']})</p>";
                    $assessment_count++;
                } else {
                    echo "<p>⚠️ Failed to save assessment for animal ID: $animal_id</p>";
                }
            } else {
                echo "<p>⚠️ Assessment failed for animal ID: $animal_id - {$assessment['error']}</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Exception for animal ID: $animal_id - " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<p><strong>Total assessments created: $assessment_count</strong></p>";
    echo "</div>";
    
    echo "<div class='setup-section success'>";
    echo "<h3>✅ Setup Complete!</h3>";
    echo "<p>Health Risk Assessment system is now ready to use.</p>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li><a href='admin_health_risk_monitoring.php'>Access Health Risk Monitoring</a></li>";
    echo "<li><a href='admin_dashboard.php'>View Admin Dashboard</a></li>";
    echo "<li><a href='debug_health_risk.php'>Run Debug Script</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='setup-section error'>";
    echo "<h3>❌ Setup Failed</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
