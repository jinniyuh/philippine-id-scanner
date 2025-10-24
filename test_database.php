<?php
// Test database connection and structure
require_once 'includes/conn.php';

echo "<h2>Database Connection Test</h2>";

// Test 1: Check connection
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    echo "<p>Connected to database: " . $conn->database . "</p>";
}

// Test 2: Check if clients table exists
$result = $conn->query("SHOW TABLES LIKE 'clients'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Clients table exists</p>";
    
    // Test 3: Check table structure
    $result = $conn->query("DESCRIBE clients");
    if ($result) {
        echo "<h3>Clients table structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p style='color: red;'>❌ Clients table does not exist</p>";
    
    // Show available tables
    $result = $conn->query("SHOW TABLES");
    if ($result) {
        echo "<h3>Available tables:</h3>";
        echo "<ul>";
        while ($row = $result->fetch_array()) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
    }
}

// Test 4: Try a simple insert (will be rolled back)
echo "<h3>Testing INSERT statement:</h3>";
$testStmt = $conn->prepare("INSERT INTO clients (full_name, contact_number, email, barangay, username, password, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
if ($testStmt) {
    echo "<p style='color: green;'>✅ INSERT statement prepared successfully</p>";
    $testStmt->close();
} else {
    echo "<p style='color: red;'>❌ INSERT statement failed: " . $conn->error . "</p>";
}

// Test 5: Check current data in clients table
$result = $conn->query("SELECT COUNT(*) as count FROM clients");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>Current clients in database: " . $row['count'] . "</p>";
}

$conn->close();
?>
