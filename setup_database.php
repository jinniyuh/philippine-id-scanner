<?php
// Database setup script
echo "<h2>Database Setup</h2>";

// Connect to MySQL without selecting a database
$servername = "localhost";
$username = "root";
$password = "";

$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<p style='color: green;'>✅ Connected to MySQL server</p>";

// Create database if it doesn't exist
$database = "bagovets";
$sql = "CREATE DATABASE IF NOT EXISTS `$database`";
if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green;'>✅ Database '$database' created or already exists</p>";
} else {
    echo "<p style='color: red;'>❌ Error creating database: " . $conn->error . "</p>";
}

// Select the database
$conn->select_db($database);

// Check if clients table exists
$result = $conn->query("SHOW TABLES LIKE 'clients'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Clients table already exists</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Clients table does not exist. You need to import the SQL file.</p>";
    echo "<p>Please import the file: <code>database/bagovets (1).sql</code> into your MySQL database.</p>";
    echo "<p>You can do this through phpMyAdmin or MySQL command line.</p>";
}

$conn->close();

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Open phpMyAdmin (http://localhost/phpmyadmin)</li>";
echo "<li>Create a new database called 'bagovets' if it doesn't exist</li>";
echo "<li>Import the file: <code>database/bagovets (1).sql</code></li>";
echo "<li>Or run: <code>mysql -u root -p bagovets < database/bagovets\ \(1\).sql</code></li>";
echo "</ol>";
?>
