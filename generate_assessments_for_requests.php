<?php
require_once "includes/conn.php";

echo "<h1>GENERATING ASSESSMENTS FOR PHARMACEUTICAL REQUESTS</h1>";

// Clear existing assessment data
echo "<h2>Clearing existing assessment data...</h2>";
$conn->query("DELETE FROM health_risk_assessments WHERE client_id IN (SELECT client_id FROM clients WHERE full_name LIKE 'Population Test%')");
echo "<p>✅ Existing assessment data cleared!</p>";

// Get pharmaceutical requests with symptoms
echo "<h2>Getting pharmaceutical requests with symptoms...</h2>";
$result = $conn->query("
    SELECT 
        pr.request_id,
        pr.client_id,
        pr.species,
        pr.symptoms,
        pr.request_date,
        c.full_name,
        c.barangay
    FROM pharmaceutical_requests pr
    JOIN clients c ON pr.client_id = c.client_id
    WHERE c.full_name LIKE 'Population Test%'
    AND pr.symptoms IS NOT NULL 
    AND pr.symptoms != ''
    ORDER BY pr.request_id DESC
    LIMIT 20
");

$requests = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    echo "<p>✅ Found " . count($requests) . " pharmaceutical requests with symptoms</p>";
} else {
    echo "<p style='color: red;'>❌ No pharmaceutical requests found!</p>";
    exit;
}

// Generate assessments for each request
echo "<h2>Generating assessments for pharmaceutical requests...</h2>";
$total_assessments = 0;

foreach ($requests as $request) {
    echo "<h3>Processing Request ID: {$request['request_id']} - {$request['species']}</h3>";
    echo "<p>Client: {$request['full_name']} | Symptoms: {$request['symptoms']}</p>";
    
    // Generate 2-3 assessments per request
    $assessment_count = rand(2, 3);
    
    for ($i = 1; $i <= $assessment_count; $i++) {
        // Generate assessment data based on symptoms
        $symptoms = strtolower($request['symptoms']);
        
        if (strpos($symptoms, 'sudden death') !== false || strpos($symptoms, 'paralysis') !== false) {
            // Critical symptoms - high risk
            $risk_levels = ['Critical', 'High'];
            $risk_score = rand(80, 95);
            
            $risk_factors = [
                "Sudden death indicates critical health emergency",
                "Paralysis suggests severe neurological issues",
                "Immediate veterinary intervention required",
                "High risk of mortality"
            ];
            
            $recommendations = [
                "Immediate emergency veterinary care",
                "Isolate animal immediately",
                "Monitor for signs of contagious disease",
                "Contact veterinary emergency services"
            ];
        } elseif (strpos($symptoms, 'high fever') !== false && strpos($symptoms, 'difficulty breathing') !== false) {
            // Respiratory + fever - high risk
            $risk_levels = ['High', 'Medium'];
            $risk_score = rand(60, 85);
            
            $risk_factors = [
                "High fever indicating possible infection",
                "Difficulty breathing suggests respiratory distress",
                "Combined symptoms indicate serious illness",
                "Risk of respiratory failure"
            ];
            
            $recommendations = [
                "Immediate veterinary consultation",
                "Monitor temperature every 4 hours",
                "Ensure proper ventilation",
                "Consider antibiotic treatment"
            ];
        } elseif (strpos($symptoms, 'bloody diarrhea') !== false || strpos($symptoms, 'vomiting') !== false) {
            // Digestive issues - medium risk
            $risk_levels = ['Medium', 'High'];
            $risk_score = rand(50, 75);
            
            $risk_factors = [
                "Bloody diarrhea indicates internal bleeding",
                "Vomiting suggests digestive system issues",
                "Risk of dehydration and infection",
                "Possible bacterial or viral infection"
            ];
            
            $recommendations = [
                "Veterinary examination required",
                "Monitor hydration levels",
                "Provide clean water and electrolytes",
                "Consider stool sample testing"
            ];
        } else {
            // General symptoms - medium/low risk
            $risk_levels = ['Medium', 'Low'];
            $risk_score = rand(30, 60);
            
            $risk_factors = [
                "Current symptoms: {$request['symptoms']}",
                "Monitor for symptom progression",
                "Environmental factors may be contributing"
            ];
            
            $recommendations = [
                "Continue monitoring animal health",
                "Schedule veterinary check-up",
                "Maintain clean environment"
            ];
        }
        
        $risk_level = $risk_levels[array_rand($risk_levels)];
        
        // Generate assessment date (within last 30 days)
        $days_ago = rand(1, 30);
        $assessment_date = date('Y-m-d H:i:s', strtotime("-$days_ago days"));
        
        // Insert assessment into health_risk_assessments table
        $stmt = $conn->prepare("INSERT INTO health_risk_assessments (client_id, risk_score, risk_level, risk_factors, recommendations, assessed_by) VALUES (?, ?, ?, ?, ?, ?)");
        
        $assessed_by = 1; // Default user ID
        
        $stmt->bind_param("iisssi", 
            $request['client_id'],
            $risk_score, 
            $risk_level, 
            json_encode($risk_factors), 
            json_encode($recommendations), 
            $assessed_by
        );
        
        if ($stmt->execute()) {
            $total_assessments++;
            echo "<p>  ✅ Assessment $i: $risk_level Risk ($risk_score%) - Date: $assessment_date</p>";
        } else {
            echo "<p style='color: red;'>  ❌ Failed to insert assessment: " . $stmt->error . "</p>";
        }
    }
}

echo "<h2>ASSESSMENT GENERATION COMPLETE!</h2>";
echo "<p><strong>Total assessments created:</strong> $total_assessments</p>";

// Verify the data
echo "<h3>Verification:</h3>";
$result = $conn->query("SELECT COUNT(*) as count FROM health_risk_assessments WHERE client_id IN (SELECT client_id FROM clients WHERE full_name LIKE 'Population Test%')");
$assessment_count = $result->fetch_assoc()['count'];
echo "<p><strong>Total assessment records in database:</strong> $assessment_count</p>";

// Show sample assessment data
echo "<h3>Sample Assessment Data:</h3>";
$result = $conn->query("
    SELECT 
        hra.assessment_id,
        hra.client_id,
        hra.risk_level,
        hra.risk_score,
        hra.assessment_date,
        c.full_name,
        pr.species,
        pr.symptoms
    FROM health_risk_assessments hra 
    JOIN clients c ON hra.client_id = c.client_id
    JOIN pharmaceutical_requests pr ON c.client_id = pr.client_id
    WHERE c.full_name LIKE 'Population Test%'
    ORDER BY hra.assessment_date DESC 
    LIMIT 10
");

if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>Assessment ID</th><th>Client ID</th><th>Client</th><th>Species</th><th>Symptoms</th><th>Risk Level</th><th>Risk Score</th><th>Date</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['assessment_id']}</td>";
        echo "<td>{$row['client_id']}</td>";
        echo "<td>{$row['full_name']}</td>";
        echo "<td>{$row['species']}</td>";
        echo "<td>{$row['symptoms']}</td>";
        echo "<td>{$row['risk_level']}</td>";
        echo "<td>{$row['risk_score']}%</td>";
        echo "<td>{$row['assessment_date']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No assessment data found!</p>";
}

echo "<h3>🎯 WHAT TO DO NOW:</h3>";
echo "<p><a href='admin_health_risk_monitoring.php' target='_blank'>📈 REFRESH DASHBOARD - Animal Assessment List should now show pharmaceutical requests as animals</a></p>";
echo "<p><a href='get_health_risk_assessment_simple.php?action=animal_list' target='_blank'>🔍 CHECK API - Direct API response for animal list</a></p>";

$conn->close();
?>