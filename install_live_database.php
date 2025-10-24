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
    <title>Install Live Database - Forgot Password</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: orange; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { padding: 10px 20px; margin: 5px; background: #007bff; color: white; text-decoration: none; border-radius: 3px; display: inline-block; }
    </style>
</head>
<body>";

echo "<h1>🔧 Install Live Database - Forgot Password</h1>";

if (isset($_POST['install'])) {
    echo "<div class='section'>";
    echo "<h3>🚀 Installing Database Changes...</h3>";
    
    try {
        $success_count = 0;
        $error_count = 0;
        
        // Check if columns already exist
        $check_columns = $conn->query("SHOW COLUMNS FROM users LIKE 'reset_token'");
        
        if ($check_columns && $check_columns->num_rows > 0) {
            echo "<div class='warning'>⚠️ Password reset columns already exist in users table.</div>";
        } else {
            echo "<div class='info'>📋 Adding password reset columns to users table...</div>";
            
            // Add reset_token column
            $sql1 = "ALTER TABLE \`users\` ADD COLUMN \`reset_token\` VARCHAR(64) NULL DEFAULT NULL";
            if ($conn->query($sql1)) {
                echo "<div class='success'>✅ Added reset_token column</div>";
                $success_count++;
            } else {
                echo "<div class='error'>❌ Error adding reset_token: " . $conn->error . "</div>";
                $error_count++;
            }
            
            // Add reset_expiry column
            $sql2 = "ALTER TABLE \`users\` ADD COLUMN \`reset_expiry\` DATETIME NULL DEFAULT NULL";
            if ($conn->query($sql2)) {
                echo "<div class='success'>✅ Added reset_expiry column</div>";
                $success_count++;
            } else {
                echo "<div class='error'>❌ Error adding reset_expiry: " . $conn->error . "</div>";
                $error_count++;
            }
            
            // Add email column if it doesn't exist
            $check_email = $conn->query("SHOW COLUMNS FROM users LIKE 'email'");
            if ($check_email && $check_email->num_rows == 0) {
                $sql3 = "ALTER TABLE \`users\` ADD COLUMN \`email\` VARCHAR(100) NULL DEFAULT NULL";
                if ($conn->query($sql3)) {
                    echo "<div class='success'>✅ Added email column</div>";
                    $success_count++;
                } else {
                    echo "<div class='error'>❌ Error adding email: " . $conn->error . "</div>";
                    $error_count++;
                }
            } else {
                echo "<div class='info'>ℹ️ Email column already exists</div>";
            }
            
            // Add indexes
            $sql4 = "CREATE INDEX \`idx_users_reset_token\` ON \`users\` (\`reset_token\`)";
            if ($conn->query($sql4)) {
                echo "<div class='success'>✅ Added reset_token index</div>";
                $success_count++;
            } else {
                echo "<div class='warning'>⚠️ Index might already exist: " . $conn->error . "</div>";
            }
            
            $sql5 = "CREATE INDEX \`idx_users_email\` ON \`users\` (\`email\`)";
            if ($conn->query($sql5)) {
                echo "<div class='success'>✅ Added email index</div>";
                $success_count++;
            } else {
                echo "<div class='warning'>⚠️ Index might already exist: " . $conn->error . "</div>";
            }
        }
        
        // Update admin user email if needed
        $sql6 = "UPDATE \`users\` SET \`email\` = 'admin@bagovet.com' WHERE \`username\` = 'test' AND (\`email\` IS NULL OR \`email\` = '')";
        if ($conn->query($sql6)) {
            echo "<div class='success'>✅ Updated test user email</div>";
            $success_count++;
        } else {
            echo "<div class='warning'>⚠️ Could not update test user: " . $conn->error . "</div>";
        }
        
        echo "<div class='info'>📊 Installation Summary: $success_count successful, $error_count errors</div>";
        
        if ($error_count == 0) {
            echo "<div class='success'>🎉 Installation completed successfully!</div>";
            echo "<p><strong>Next steps:</strong></p>";
            echo "<ul>";
            echo "<li>Test the forgot password functionality</li>";
            echo "<li>Make sure your users have email addresses</li>";
            echo "<li>Remove this installation file for security</li>";
            echo "</ul>";
        } else {
            echo "<div class='warning'>⚠️ Installation completed with some errors. Check the messages above.</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Installation failed: " . $e->getMessage() . "</div>";
    }
    
    echo "</div>";
}

echo "<div class='section'>";
echo "<h3>⚠️ Important Notice</h3>";
echo "<p>This script will add the required database columns for the forgot password functionality.</p>";
echo "<p><strong>Make sure to:</strong></p>";
echo "<ul>";
echo "<li>Backup your database before running this</li>";
echo "<li>Delete this file after installation for security</li>";
echo "<li>Test the functionality after installation</li>";
echo "</ul>";
echo "</div>";

echo "<div class='section'>";
echo "<h3>🚀 Run Installation</h3>";
echo "<form method='POST'>";
echo "<p>Click the button below to install the required database changes:</p>";
echo "<input type='submit' name='install' value='Install Database Changes' class='btn' style='background: #28a745; color: white; border: none; padding: 12px 24px; border-radius: 5px; cursor: pointer;'>";
echo "</form>";
echo "</div>";

echo "</body></html>";
?>
