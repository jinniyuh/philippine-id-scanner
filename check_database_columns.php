<?php
session_start();
require_once 'includes/conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access - Please login as admin first");
}

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Check Database Columns - Bago City Veterinary Office</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: orange; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>";

echo "<h1>🔍 Database Columns Check</h1>";

try {
    // Check clients table columns
    echo "<div class='section'>";
    echo "<h3>📋 Clients Table Columns</h3>";
    $result = $conn->query("SHOW COLUMNS FROM clients");
    if ($result) {
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    // Check users table columns
    echo "<div class='section'>";
    echo "<h3>👥 Users Table Columns</h3>";
    $result = $conn->query("SHOW COLUMNS FROM users");
    if ($result) {
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    // Check if reset columns exist
    echo "<div class='section'>";
    echo "<h3>🔑 Reset Token Columns Check</h3>";
    
    $reset_columns = ['reset_token', 'reset_expiry', 'email'];
    foreach ($reset_columns as $column) {
        // Check clients table
        $result = $conn->query("SHOW COLUMNS FROM clients LIKE '$column'");
        $clients_has = $result && $result->num_rows > 0;
        
        // Check users table
        $result = $conn->query("SHOW COLUMNS FROM users LIKE '$column'");
        $users_has = $result && $result->num_rows > 0;
        
        echo "<div class='info'>";
        echo "Column '$column': ";
        echo "Clients: " . ($clients_has ? "✅ Yes" : "❌ No") . " | ";
        echo "Users: " . ($users_has ? "✅ Yes" : "❌ No");
        echo "</div>";
    }
    echo "</div>";
    
    // Show sample data
    echo "<div class='section'>";
    echo "<h3>📊 Sample Users Data</h3>";
    $result = $conn->query("SELECT user_id, name, username, email, role FROM users LIMIT 5");
    if ($result && $result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['role']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>No users found</div>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
