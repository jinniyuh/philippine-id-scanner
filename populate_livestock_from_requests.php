<?php
require_once "includes/conn.php";

echo "<h1>POPULATING LIVESTOCK TABLE FROM PHARMACEUTICAL REQUESTS</h1>";

// Clear existing livestock records for test clients
echo "<h2>Clearing existing livestock records...</h2>";
$conn->query("DELETE FROM livestock_poultry WHERE client_id IN (SELECT client_id FROM clients WHERE full_name LIKE 'Test Client%')");
echo "<p>✅ Existing livestock records cleared!</p>";

// Get pharmaceutical requests with symptoms
echo "<h2>Getting pharmaceutical requests with symptoms...</h2>";
$result = $conn->query("
    SELECT DISTINCT 
        pr.client_id,
        pr.species,
        pr.symptoms,
        c.full_name,
        c.barangay
    FROM pharmaceutical_requests pr
    JOIN clients c ON pr.client_id = c.client_id
    WHERE c.full_name LIKE 'Test Client%'
    AND pr.symptoms IS NOT NULL 
    AND pr.symptoms != ''
    ORDER BY pr.request_date DESC
    LIMIT 30
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

// Create livestock records from pharmaceutical requests
echo "<h2>Creating livestock records...</h2>";
$inserted_count = 0;

foreach ($requests as $request) {
    // Create a unique animal name combining client and species
    $animal_name = $request['full_name'] . ' - ' . $request['species'];
    
    // Use symptoms as health status
    $health_status = "Symptomatic: " . $request['symptoms'];
    
    // Random quantity between 1-5
    $quantity = rand(1, 5);
    
    // Insert into livestock_poultry table
    $stmt = $conn->prepare("INSERT INTO livestock_poultry (client_id, species, health_status, quantity) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", 
        $request['client_id'],
        $request['species'],
        $health_status,
        $quantity
    );
    
    if ($stmt->execute()) {
        $inserted_count++;
        echo "<p>✅ Created livestock: {$animal_name} - {$request['symptoms']}</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed: " . $stmt->error . "</p>";
    }
}

echo "<h2>LIVESTOCK POPULATION COMPLETE!</h2>";
echo "<p><strong>Total livestock records created:</strong> $inserted_count</p>";

// Verify the data
echo "<h3>Verification:</h3>";
$result = $conn->query("SELECT COUNT(*) as count FROM livestock_poultry lp JOIN clients c ON lp.client_id = c.client_id WHERE c.full_name LIKE 'Test Client%'");
$livestock_count = $result->fetch_assoc()['count'];
echo "<p><strong>Total livestock records for test clients:</strong> $livestock_count</p>";

// Test the animal list query
echo "<h3>Testing Animal List Query:</h3>";
$sql = "SELECT 
            lp.animal_id,
            lp.species as animal_name,
            lp.species as animal_type,
            lp.health_status,
            lp.quantity,
            c.full_name as client_name,
            c.barangay,
            hra.risk_level as last_risk_level,
            hra.assessment_date as last_assessment
        FROM livestock_poultry lp
        JOIN clients c ON lp.client_id = c.client_id
        LEFT JOIN (
            SELECT animal_id, risk_level, assessment_date,
                   ROW_NUMBER() OVER (PARTITION BY animal_id ORDER BY assessment_date DESC) as rn
            FROM health_risk_assessments
        ) hra ON lp.animal_id = hra.animal_id AND hra.rn = 1
        WHERE c.full_name LIKE 'Test Client%'
        ORDER BY lp.animal_id DESC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Animal list query works! Found " . $result->num_rows . " animals</p>";
    
    echo "<h4>Sample Animals:</h4>";
    echo "<table border='1'>";
    echo "<tr><th>Animal ID</th><th>Species</th><th>Health Status</th><th>Client</th><th>Risk Level</th></tr>";
    
    $count = 0;
    while ($row = $result->fetch_assoc() && $count < 5) {
        echo "<tr>";
        echo "<td>{$row['animal_id']}</td>";
        echo "<td>{$row['animal_name']}</td>";
        echo "<td>{$row['health_status']}</td>";
        echo "<td>{$row['client_name']}</td>";
        echo "<td>" . (isset($row['last_risk_level']) ? $row['last_risk_level'] : 'N/A') . "</td>";
        echo "</tr>";
        $count++;
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ Animal list query failed: " . $conn->error . "</p>";
}

echo "<h3>🎯 WHAT TO DO NOW:</h3>";
echo "<p><a href='admin_health_risk_monitoring.php' target='_blank'>📈 REFRESH DASHBOARD - Animal Assessment List should now show $livestock_count animals!</a></p>";

$conn->close();
?>
