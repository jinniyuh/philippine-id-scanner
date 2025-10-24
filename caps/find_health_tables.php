<?php
require_once "includes/conn.php";

echo "<h1>FINDING HEALTH-RELATED TABLES</h1>";

// Get all table names
$result = $conn->query("SHOW TABLES");
$all_tables = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_array()) {
        $all_tables[] = $row[0];
    }
}

echo "<h2>All Tables in Database:</h2>";
echo "<ul>";
foreach ($all_tables as $table) {
    echo "<li><strong>$table</strong></li>";
}
echo "</ul>";

// Check each table for columns that might indicate health/assessment data
echo "<h2>Checking tables for health/assessment related columns:</h2>";

foreach ($all_tables as $table) {
    echo "<h3>Table: $table</h3>";
    $result = $conn->query("DESCRIBE $table");
    if ($result && $result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
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
    echo "<hr>";
}

$conn->close();
?>
