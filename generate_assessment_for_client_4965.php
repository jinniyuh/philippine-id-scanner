<?php
require_once "includes/conn.php";

echo "<h1>GENERATING ASSESSMENT HISTORY FOR CLIENT ID 4965</h1>";

// First, let's check what we know about this client
echo "<h2>Client Information:</h2>";
$result = $conn->query("SELECT * FROM clients WHERE client_id = 4965");
if ($result && $result->num_rows > 0) {
    $client = $result->fetch_assoc();
    echo "<p><strong>Client Name:</strong> {$client['full_name']}</p>";
    echo "<p><strong>Barangay:</strong> {$client['barangay']}</p>";
    echo "<p><strong>Contact:</strong> {$client['contact_number']}</p>";
} else {
    echo "<p style='color: red;'>❌ Client ID 4965 not found!</p>";
    exit;
}

// Check pharmaceutical requests for this client
echo "<h2>Pharmaceutical Requests:</h2>";
$result = $conn->query("SELECT * FROM pharmaceutical_requests WHERE client_id = 4965");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>Request ID</th><th>Species</th><th>Symptoms</th><th>Date</th><th>Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['request_id']}</td>";
        echo "<td>{$row['species']}</td>";
        echo "<td>{$row['symptoms']}</td>";
        echo "<td>{$row['request_date']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No pharmaceutical requests found for this client!</p>";
}

// Check if this client has animals in livestock_poultry table
echo "<h2>Animals for this client:</h2>";
$result = $conn->query("SELECT * FROM livestock_poultry WHERE client_id = 4965");
if ($result && $result->num_rows > 0) {
    $animals = [];
    while ($row = $result->fetch_assoc()) {
        $animals[] = $row;
        echo "<p><strong>Animal ID:</strong> {$row['animal_id']}, <strong>Species:</strong> {$row['species']}, <strong>Health Status:</strong> {$row['health_status']}</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ No animals found in livestock_poultry table. Creating a test animal...</p>";
    
    // Create a test animal for this client
    $species = "Swine";
    $health_status = "High fever, difficulty breathing, loss of appetite";
    $quantity = 1;
    
    $stmt = $conn->prepare("INSERT INTO livestock_poultry (client_id, species, health_status, quantity) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $client['client_id'], $species, $health_status, $quantity);
    
    if ($stmt->execute()) {
        $animal_id = $conn->insert_id;
        echo "<p style='color: green;'>✅ Created test animal with ID: $animal_id</p>";
        $animals = [['animal_id' => $animal_id, 'species' => $species, 'health_status' => $health_status]];
    } else {
        echo "<p style='color: red;'>❌ Failed to create test animal: " . $stmt->error . "</p>";
        exit;
    }
}

// Clear existing assessment history for this client's animals
echo "<h2>Clearing existing assessment history...</h2>";
$animal_ids = array_column($animals, 'animal_id');
$placeholders = str_repeat('?,', count($animal_ids) - 1) . '?';
$stmt = $conn->prepare("DELETE FROM health_risk_assessments WHERE animal_id IN ($placeholders)");
$stmt->bind_param(str_repeat('i', count($animal_ids)), ...$animal_ids);
$stmt->execute();
echo "<p>✅ Existing assessment history cleared!</p>";

// Generate assessment history for each animal
echo "<h2>Generating Assessment History:</h2>";

$total_assessments = 0;

foreach ($animals as $animal) {
    echo "<h3>Generating assessments for Animal ID: {$animal['animal_id']} ({$animal['species']})</h3>";
    echo "<p>Health Status: {$animal['health_status']}</p>";
    
    // Generate 3-5 assessments per animal
    $assessment_count = rand(3, 5);
    
    for ($i = 1; $i <= $assessment_count; $i++) {
        // Generate assessment data based on symptoms
        $symptoms = strtolower($animal['health_status']);
        
        if (strpos($symptoms, 'high fever') !== false && strpos($symptoms, 'difficulty breathing') !== false) {
            // This is a respiratory issue with fever - higher risk
            $risk_levels = ['High', 'Medium'];
            $risk_score = rand(60, 85);
            
            $risk_factors = [
                "High fever indicating possible infection",
                "Difficulty breathing suggests respiratory distress",
                "Loss of appetite may indicate systemic illness",
                "Swine are susceptible to respiratory diseases"
            ];
            
            $recommendations = [
                "Immediate veterinary consultation required",
                "Monitor temperature every 4 hours",
                "Ensure proper ventilation and clean environment",
                "Consider antibiotic treatment if bacterial infection suspected",
                "Isolate animal to prevent spread to other livestock"
            ];
        } else {
            // General health assessment
            $risk_levels = ['Low', 'Medium'];
            $risk_score = rand(30, 60);
            
            $risk_factors = [
                "Current health status: {$animal['health_status']}",
                "Environmental factors affecting health",
                "Previous medical history concerns"
            ];
            
            $recommendations = [
                "Continue monitoring animal health",
                "Schedule follow-up examination",
                "Maintain current treatment protocol"
            ];
        }
        
        $risk_level = $risk_levels[array_rand($risk_levels)];
        
        // Generate assessment date (within last 45 days)
        $days_ago = rand(1, 45);
        $assessment_date = date('Y-m-d H:i:s', strtotime("-$days_ago days"));
        
        // Insert assessment history
        $stmt = $conn->prepare("INSERT INTO health_risk_assessments (animal_id, client_id, risk_score, risk_level, risk_factors, recommendations, assessed_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $assessed_by = 1; // Default user ID
        $client_id = $client['client_id'];
        
        $stmt->bind_param("iidisss", 
            $animal['animal_id'],
            $client_id,
            $risk_score, 
            $risk_level, 
            json_encode($risk_factors), 
            json_encode($recommendations), 
            $assessed_by
        );
        
        if ($stmt->execute()) {
            $total_assessments++;
            echo "<p>  ✅ Assessment $i: $risk_level Risk ($risk_score%) (Date: $assessment_date)</p>";
        } else {
            echo "<p style='color: red;'>  ❌ Failed to insert assessment: " . $stmt->error . "</p>";
        }
    }
}

echo "<h2>ASSESSMENT GENERATION COMPLETE!</h2>";
echo "<p><strong>Total assessments created for client 4965:</strong> $total_assessments</p>";

// Verify the data
echo "<h3>Verification - Assessment History for Client 4965:</h3>";
$result = $conn->query("
    SELECT 
        hra.assessment_id,
        hra.animal_id,
        hra.risk_level,
        hra.risk_score,
        hra.assessment_date,
        lp.species,
        lp.health_status
    FROM health_risk_assessments hra 
    LEFT JOIN livestock_poultry lp ON hra.animal_id = lp.animal_id
    WHERE lp.client_id = 4965
    ORDER BY hra.assessment_date DESC 
    LIMIT 10
");

if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>Assessment ID</th><th>Animal ID</th><th>Species</th><th>Health Status</th><th>Risk Level</th><th>Risk Score</th><th>Date</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['assessment_id']}</td>";
        echo "<td>{$row['animal_id']}</td>";
        echo "<td>{$row['species']}</td>";
        echo "<td>{$row['health_status']}</td>";
        echo "<td>{$row['risk_level']}</td>";
        echo "<td>{$row['risk_score']}%</td>";
        echo "<td>{$row['assessment_date']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No assessment data found!</p>";
}

echo "<h2>WHAT IS HEALTH RISK ASSESSMENT FOR?</h2>";
echo "<div style='background: #f8f9fa; padding: 20px; border-left: 4px solid #007bff; margin: 20px 0;'>";
echo "<h3>🎯 Purpose of Health Risk Assessment:</h3>";
echo "<ul>";
echo "<li><strong>Early Disease Detection:</strong> Identifies potential health issues before they become severe</li>";
echo "<li><strong>Risk Stratification:</strong> Categorizes animals into Low, Medium, High, or Critical risk levels</li>";
echo "<li><strong>Preventive Care:</strong> Helps implement preventive measures to avoid disease outbreaks</li>";
echo "<li><strong>Treatment Planning:</strong> Guides veterinarians in creating appropriate treatment plans</li>";
echo "<li><strong>Resource Allocation:</strong> Helps prioritize which animals need immediate attention</li>";
echo "<li><strong>Outbreak Prevention:</strong> Identifies patterns that could lead to disease outbreaks</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 20px; border-left: 4px solid #ffc107; margin: 20px 0;'>";
echo "<h3>📊 For Client 4965's Swine:</h3>";
echo "<ul>";
echo "<li><strong>Symptoms:</strong> High fever, difficulty breathing, loss of appetite</li>";
echo "<li><strong>Risk Level:</strong> High/Medium (respiratory + fever = serious concern)</li>";
echo "<li><strong>Assessment Purpose:</strong> Monitor for respiratory diseases like swine flu, pneumonia</li>";
echo "<li><strong>Recommendations:</strong> Immediate veterinary care, isolation, monitoring</li>";
echo "<li><strong>Follow-up:</strong> Regular temperature checks, breathing assessment</li>";
echo "</ul>";
echo "</div>";

echo "<h3>🔗 NEXT STEPS:</h3>";
echo "<p><a href='admin_health_risk_monitoring.php' target='_blank'>📈 VIEW DASHBOARD - Click on this client's animal to see assessment history</a></p>";
echo "<p><a href='get_health_risk_assessment_simple.php?action=get_assessment_history&animal_id=" . ($animals[0]['animal_id'] ?? '') . "' target='_blank'>🔍 CHECK API - Direct assessment history for this animal</a></p>";

$conn->close();
?>
