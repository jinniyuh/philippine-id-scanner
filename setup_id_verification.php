<?php
/**
 * Database Setup Script for ID Verification Feature
 * Run this script to add the necessary columns to the clients table
 */

include 'includes/conn.php';

echo "<h2>Setting up ID Verification Database Columns</h2>";

try {
    // Check if columns already exist
    $checkColumns = $conn->query("SHOW COLUMNS FROM clients LIKE 'id_verified'");
    
    if ($checkColumns->num_rows > 0) {
        echo "<div style='color: orange;'>⚠️ ID verification columns already exist in the clients table.</div>";
    } else {
        // Add the required columns
        $sql = "ALTER TABLE `clients` 
                ADD COLUMN `id_verified` TINYINT(1) DEFAULT 0 COMMENT 'Whether ID has been verified (0=no, 1=yes)',
                ADD COLUMN `scanned_id_data` TEXT NULL COMMENT 'JSON data from scanned ID card',
                ADD COLUMN `id_verification_date` TIMESTAMP NULL COMMENT 'Date when ID was verified'";
        
        if ($conn->query($sql)) {
            echo "<div style='color: green;'>✅ Successfully added ID verification columns to clients table.</div>";
            
            // Update existing clients to have default values
            $updateSql = "UPDATE `clients` SET `id_verified` = 0 WHERE `id_verified` IS NULL";
            if ($conn->query($updateSql)) {
                echo "<div style='color: green;'>✅ Updated existing clients with default verification status.</div>";
            } else {
                echo "<div style='color: red;'>❌ Error updating existing clients: " . $conn->error . "</div>";
            }
        } else {
            echo "<div style='color: red;'>❌ Error adding columns: " . $conn->error . "</div>";
        }
    }
    
    // Display current table structure
    echo "<h3>Current Clients Table Structure:</h3>";
    $result = $conn->query("DESCRIBE clients");
    if ($result) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>Setup Complete!</h3>";
    echo "<p>The ID verification feature is now ready to use. Clients can now scan their Philippine National ID during registration.</p>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error: " . $e->getMessage() . "</div>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Verification Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            text-align: left;
            padding: 8px;
        }
        th {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- PHP content above -->
    </div>
</body>
</html>
