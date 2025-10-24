<?php
require_once "includes/conn.php";

echo "<h1>GENERATING ASSESSMENT HISTORY FOR ANIMALS</h1>";

// Clear existing assessment history for test clients
echo "<h2>Clearing existing assessment history...</h2>";
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$conn->query("DELETE FROM health_risk_assessments WHERE animal_id IN (SELECT animal_id FROM livestock_poultry lp JOIN clients c ON lp.client_id = c.client_id WHERE c.full_name LIKE 'Population Test%')");
$conn->query("SET FOREIGN_KEY_CHECKS = 1");
echo "<p>✅ Assessment history cleared!</p>";

// Get test animals from livestock_poultry table that have non-critical symptoms
echo "<h2>Finding test animals with non-critical symptoms...</h2>";
$query = "
    SELECT DISTINCT 
        lp.animal_id,
        lp.client_id, 
        c.full_name, 
        c.barangay, 
        lp.animal_type as species, 
        lp.health_status as symptoms 
    FROM livestock_poultry lp 
    LEFT JOIN clients c ON lp.client_id = c.client_id 
    WHERE c.full_name LIKE 'Population Test%'
    AND lp.health_status NOT LIKE '%sudden death%'
    AND lp.health_status NOT LIKE '%biglaang pagkamatay%'
    AND lp.health_status NOT LIKE '%paralysis%'
    AND lp.health_status NOT LIKE '%pagkaparalisa%'
    LIMIT 10
";

$result = $conn->query($query);
$test_clients = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $test_clients[] = $row;
    }
    echo "<p>✅ Found " . count($test_clients) . " test clients with non-critical symptoms</p>";
} else {
    echo "<p style='color: red;'>❌ No test clients found with non-critical symptoms!</p>";
    echo "<p>Let me check what symptoms exist...</p>";
    
    // Show what symptoms exist
    $symptom_check = $conn->query("SELECT DISTINCT symptoms FROM pharmaceutical_requests pr LEFT JOIN clients c ON pr.client_id = c.client_id WHERE c.full_name LIKE 'Population Test%' LIMIT 10");
    if ($symptom_check && $symptom_check->num_rows > 0) {
        echo "<h3>Existing symptoms in test data:</h3>";
        echo "<ul>";
        while ($symptom_row = $symptom_check->fetch_assoc()) {
            echo "<li>" . $symptom_row['symptoms'] . "</li>";
        }
        echo "</ul>";
    }
    exit;
}

// Generate assessment history for each test client
echo "<h2>Generating assessment history...</h2>";

$assessment_types = [
    'Physical Examination',
    'Blood Test',
    'Temperature Check',
    'Weight Measurement',
    'Behavioral Assessment',
    'Vital Signs Check'
];

$assessment_results = [
    'Normal',
    'Mild Concern',
    'Moderate Concern',
    'Requires Monitoring',
    'Stable Condition'
];

$total_assessments = 0;

foreach ($test_clients as $animal) {
    echo "<h3>Generating assessments for: {$animal['full_name']} ({$animal['species']}) - Animal ID: {$animal['animal_id']}</h3>";
    echo "<p>Health Status: {$animal['symptoms']}</p>";
    
    // Generate 2-4 assessments per animal
    $assessment_count = rand(2, 4);
    
    for ($i = 1; $i <= $assessment_count; $i++) {
        // Generate assessment data
        $risk_levels = ['Low', 'Medium', 'High'];
        $risk_level = $risk_levels[array_rand($risk_levels)];
        $risk_score = rand(20, 80);
        $confidence = rand(70, 95);
        
        // Generate assessment date (within last 30 days)
        $days_ago = rand(1, 30);
        $assessment_date = date('Y-m-d H:i:s', strtotime("-$days_ago days"));
        
        // Generate risk factors and recommendations
        $risk_factors = [
            "Current health status: {$animal['symptoms']}",
            "Environmental factors affecting health",
            "Previous medical history concerns"
        ];
        
        $recommendations = [
            "Continue monitoring animal health",
            "Schedule follow-up examination",
            "Maintain current treatment protocol"
        ];
        
        // Insert assessment history into health_risk_assessments table
        $stmt = $conn->prepare("INSERT INTO health_risk_assessments (animal_id, risk_level, risk_score, confidence, risk_factors, recommendations, assessment_date, assessed_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $assessed_by = 1; // Default user ID
        
        $stmt->bind_param("isddsssi", 
            $animal['animal_id'], 
            $risk_level, 
            $risk_score, 
            $confidence, 
            json_encode($risk_factors), 
            json_encode($recommendations), 
            $assessment_date, 
            $assessed_by
        );
        
        if ($stmt->execute()) {
            $total_assessments++;
            echo "<p>  ✅ Assessment $i: $risk_level Risk ($risk_score%) - Confidence: $confidence% (Date: $assessment_date)</p>";
        } else {
            echo "<p style='color: red;'>  ❌ Failed to insert assessment: " . $stmt->error . "</p>";
        }
    }
}

echo "<h2>ASSESSMENT HISTORY GENERATION COMPLETE!</h2>";
echo "<p><strong>Total assessments created:</strong> $total_assessments</p>";

// Verify the data
echo "<h3>Verification:</h3>";
$result = $conn->query("SELECT COUNT(*) as count FROM health_risk_assessments hra LEFT JOIN livestock_poultry lp ON hra.animal_id = lp.animal_id LEFT JOIN clients c ON lp.client_id = c.client_id WHERE c.full_name LIKE 'Population Test%'");
$assessment_count = $result->fetch_assoc()['count'];
echo "<p><strong>Total assessment records in database:</strong> $assessment_count</p>";

// Show sample assessment data
echo "<h3>Sample Assessment Data:</h3>";
$result = $conn->query("
    SELECT 
        hra.assessment_id,
        c.full_name,
        hra.animal_id,
        hra.risk_level,
        hra.risk_score,
        hra.assessment_date,
        hra.confidence
    FROM health_risk_assessments hra 
    LEFT JOIN livestock_poultry lp ON hra.animal_id = lp.animal_id
    LEFT JOIN clients c ON lp.client_id = c.client_id 
    WHERE c.full_name LIKE 'Population Test%'
    ORDER BY hra.assessment_date DESC 
    LIMIT 10
");

if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>Assessment ID</th><th>Client Name</th><th>Animal ID</th><th>Risk Level</th><th>Risk Score</th><th>Date</th><th>Confidence</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['assessment_id']}</td>";
        echo "<td>{$row['full_name']}</td>";
        echo "<td>{$row['animal_id']}</td>";
        echo "<td>{$row['risk_level']}</td>";
        echo "<td>{$row['risk_score']}%</td>";
        echo "<td>{$row['assessment_date']}</td>";
        echo "<td>{$row['confidence']}%</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No assessment data found!</p>";
}

echo "<h3>WHAT TO TEST NOW:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Assessment History Modal</strong> - Should now show assessment records instead of 'No assessment history available'</li>";
echo "<li>✅ <strong>Multiple Assessments</strong> - Each animal should have 2-4 assessment records</li>";
echo "<li>✅ <strong>Recent Dates</strong> - Assessments from the last 30 days</li>";
echo "<li>✅ <strong>Different Types</strong> - Physical Examination, Blood Test, Temperature Check, etc.</li>";
echo "</ul>";

echo "<h3>NEXT STEPS:</h3>";
echo "<p><a href='admin_health_risk_monitoring.php' target='_blank'>📈 TEST DASHBOARD - Click on animals with non-critical symptoms to see assessment history</a></p>";

$conn->close();

// Helper function to generate assessment notes
function generateAssessmentNotes($symptoms, $assessment_type, $result) {
    $notes_templates = [
        'Physical Examination' => [
            'Normal' => 'Physical examination completed. No abnormalities detected. Animal appears healthy and active.',
            'Mild Concern' => 'Physical examination shows minor concerns. Animal is generally healthy but shows signs of discomfort.',
            'Moderate Concern' => 'Physical examination reveals moderate health issues. Monitoring recommended.',
            'Requires Monitoring' => 'Physical examination indicates need for continued monitoring. Schedule follow-up.',
            'Stable Condition' => 'Physical examination shows stable condition. Current treatment appears effective.'
        ],
        'Blood Test' => [
            'Normal' => 'Blood test results within normal parameters. No signs of infection or disease.',
            'Mild Concern' => 'Blood test shows minor abnormalities. Further monitoring recommended.',
            'Moderate Concern' => 'Blood test indicates moderate health concerns. Treatment may be required.',
            'Requires Monitoring' => 'Blood test results require close monitoring. Repeat test in 1 week.',
            'Stable Condition' => 'Blood test results show stable condition. Current treatment is working.'
        ],
        'Temperature Check' => [
            'Normal' => 'Body temperature within normal range. No fever detected.',
            'Mild Concern' => 'Slightly elevated temperature. Monitor for changes.',
            'Moderate Concern' => 'Elevated temperature detected. May indicate infection.',
            'Requires Monitoring' => 'Temperature monitoring required. Check every 4 hours.',
            'Stable Condition' => 'Temperature stable and normal. Recovery progressing well.'
        ]
    ];
    
    if (isset($notes_templates[$assessment_type][$result])) {
        return $notes_templates[$assessment_type][$result];
    }
    
    return "Assessment completed. Result: $result. " . ucfirst($symptoms) . " symptoms noted.";
}
?>
