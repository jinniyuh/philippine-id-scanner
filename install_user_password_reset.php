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
    <title>Install User Password Reset - Bago City Veterinary Office</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>";

echo "<h1>Install User Password Reset Functionality</h1>";

try {
    // Check if columns already exist
    $check_columns = $conn->query("SHOW COLUMNS FROM users LIKE 'reset_token'");
    
    if ($check_columns && $check_columns->num_rows > 0) {
        echo "<div class='warning'>⚠️ Password reset columns already exist in users table.</div>";
    } else {
        echo "<div class='info'>📋 Adding password reset columns to users table...</div>";
        
        // Add reset_token column
        $sql1 = "ALTER TABLE `users` ADD COLUMN `reset_token` VARCHAR(64) NULL DEFAULT NULL";
        if ($conn->query($sql1)) {
            echo "<div class='success'>✅ Added reset_token column</div>";
        } else {
            echo "<div class='error'>❌ Error adding reset_token: " . $conn->error . "</div>";
        }
        
        // Add reset_expiry column
        $sql2 = "ALTER TABLE `users` ADD COLUMN `reset_expiry` DATETIME NULL DEFAULT NULL";
        if ($conn->query($sql2)) {
            echo "<div class='success'>✅ Added reset_expiry column</div>";
        } else {
            echo "<div class='error'>❌ Error adding reset_expiry: " . $conn->error . "</div>";
        }
        
        // Add email column if it doesn't exist
        $check_email = $conn->query("SHOW COLUMNS FROM users LIKE 'email'");
        if ($check_email && $check_email->num_rows == 0) {
            $sql3 = "ALTER TABLE `users` ADD COLUMN `email` VARCHAR(100) NULL DEFAULT NULL";
            if ($conn->query($sql3)) {
                echo "<div class='success'>✅ Added email column</div>";
            } else {
                echo "<div class='error'>❌ Error adding email: " . $conn->error . "</div>";
            }
        } else {
            echo "<div class='info'>ℹ️ Email column already exists</div>";
        }
        
        // Add indexes for better performance
        $sql4 = "CREATE INDEX `idx_users_reset_token` ON `users` (`reset_token`)";
        if ($conn->query($sql4)) {
            echo "<div class='success'>✅ Added reset_token index</div>";
        } else {
            echo "<div class='warning'>⚠️ Index might already exist: " . $conn->error . "</div>";
        }
        
        $sql5 = "CREATE INDEX `idx_users_email` ON `users` (`email`)";
        if ($conn->query($sql5)) {
            echo "<div class='success'>✅ Added email index</div>";
        } else {
            echo "<div class='warning'>⚠️ Index might already exist: " . $conn->error . "</div>";
        }
        
        // Update test user to have an email
        $sql6 = "UPDATE `users` SET `email` = 'admin@bagovet.com' WHERE `username` = 'test'";
        if ($conn->query($sql6)) {
            echo "<div class='success'>✅ Updated test user email</div>";
        } else {
            echo "<div class='warning'>⚠️ Could not update test user: " . $conn->error . "</div>";
        }
    }
    
    // Test the functionality
    echo "<div class='section'>";
    echo "<h3>🧪 Testing Password Reset Functionality</h3>";
    
    // Check if we can query both tables
    $test_clients = $conn->query("SELECT COUNT(*) as count FROM clients WHERE email IS NOT NULL");
    $test_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE email IS NOT NULL");
    
    if ($test_clients) {
        $client_count = $test_clients->fetch_assoc()['count'];
        echo "<div class='info'>📊 Clients with email addresses: $client_count</div>";
    }
    
    if ($test_users) {
        $user_count = $test_users->fetch_assoc()['count'];
        echo "<div class='info'>📊 Users with email addresses: $user_count</div>";
    }
    
    echo "<div class='success'>✅ Password reset functionality is now available for both clients and users!</div>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>Make sure all users have email addresses in their profiles</li>";
    echo "<li>Test the forgot password functionality at: <a href='forgot_password.php'>forgot_password.php</a></li>";
    echo "<li>Users can now reset their passwords using their email addresses</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
