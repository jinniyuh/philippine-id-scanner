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
    <title>Live Website Debug - Forgot Password</title>
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

echo "<h1>🔍 Live Website Debug - Forgot Password</h1>";

echo "<div class='section'>";
echo "<h3>🌐 Environment Information</h3>";
echo "<div class='info'>📋 Server: " . $_SERVER['HTTP_HOST'] . "</div>";
echo "<div class='info'>📋 PHP Version: " . phpversion() . "</div>";
echo "<div class='info'>📋 Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</div>";
echo "<div class='info'>📋 Current Directory: " . getcwd() . "</div>";
echo "</div>";

echo "<div class='section'>";
echo "<h3>📊 Database Connection</h3>";

try {
    if ($conn && !$conn->connect_error) {
        echo "<div class='success'>✅ Database connection successful</div>";
        
        // Get database info
        $result = $conn->query("SELECT DATABASE() as db_name");
        if ($result) {
            $db_info = $result->fetch_assoc();
            echo "<div class='info'>📋 Database: " . $db_info['db_name'] . "</div>";
        }
        
        // Check if we can query users table
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            echo "<div class='info'>📊 Total users: $count</div>";
        }
        
    } else {
        echo "<div class='error'>❌ Database connection failed: " . ($conn->connect_error ?? 'Unknown error') . "</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
}

echo "</div>";

echo "<div class='section'>";
echo "<h3>🔑 Users Table Structure Check</h3>";

try {
    // Check users table columns
    $result = $conn->query("SHOW COLUMNS FROM users");
    if ($result) {
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        echo "<div class='info'>📋 Users table columns: " . implode(', ', $columns) . "</div>";
        
        // Check for required columns
        $required_columns = ['reset_token', 'reset_expiry', 'email'];
        foreach ($required_columns as $col) {
            if (in_array($col, $columns)) {
                echo "<div class='success'>✅ Column '$col' exists</div>";
            } else {
                echo "<div class='error'>❌ Column '$col' MISSING</div>";
            }
        }
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Error checking table structure: " . $e->getMessage() . "</div>";
}

echo "</div>";

echo "<div class='section'>";
echo "<h3>👥 Users with Email Addresses</h3>";

try {
    // Check if email column exists before querying
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'email'");
    if ($result && $result->num_rows > 0) {
        $users_result = $conn->query("SELECT user_id, name, username, email, role FROM users WHERE email IS NOT NULL AND email != ''");
        if ($users_result && $users_result->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th></tr>";
            while ($user = $users_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($user['user_id']) . "</td>";
                echo "<td>" . htmlspecialchars($user['name']) . "</td>";
                echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                echo "<td>" . htmlspecialchars($user['role']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='warning'>⚠️ No users found with email addresses</div>";
        }
    } else {
        echo "<div class='error'>❌ Email column does not exist in users table</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Error querying users: " . $e->getMessage() . "</div>";
}

echo "</div>";

echo "<div class='section'>";
echo "<h3>📧 Email System Check</h3>";

// Check PHP extensions
echo "<div class='info'>📋 OpenSSL: " . (extension_loaded('openssl') ? '✅ Enabled' : '❌ Disabled') . "</div>";
echo "<div class='info'>📋 cURL: " . (extension_loaded('curl') ? '✅ Enabled' : '❌ Disabled') . "</div>";
echo "<div class='info'>📋 Allow URL fopen: " . (ini_get('allow_url_fopen') ? '✅ Enabled' : '❌ Disabled') . "</div>";

// Check if PHPMailer files exist
$phpmailer_files = [
    'includes/PHPMailer/PHPMailer.php',
    'includes/PHPMailer/SMTP.php',
    'includes/PHPMailer/Exception.php'
];

foreach ($phpmailer_files as $file) {
    if (file_exists($file)) {
        echo "<div class='success'>✅ $file exists</div>";
    } else {
        echo "<div class='error'>❌ $file MISSING</div>";
    }
}

echo "</div>";

echo "<div class='section'>";
echo "<h3>🔧 Quick Fix Options</h3>";

echo "<p><strong>If missing columns are detected:</strong></p>";
echo "<ol>";
echo "<li>Run the installation script: <a href='install_user_password_reset.php'>install_user_password_reset.php</a></li>";
echo "<li>Or manually run this SQL in your database:</li>";
echo "</ol>";

echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace;'>";
echo "ALTER TABLE \`users\` <br>";
echo "ADD COLUMN \`reset_token\` VARCHAR(64) NULL DEFAULT NULL,<br>";
echo "ADD COLUMN \`reset_expiry\` DATETIME NULL DEFAULT NULL,<br>";
echo "ADD COLUMN \`email\` VARCHAR(100) NULL DEFAULT NULL;<br><br>";
echo "CREATE INDEX \`idx_users_reset_token\` ON \`users\` (\`reset_token\`);<br>";
echo "CREATE INDEX \`idx_users_email\` ON \`users\` (\`email\`);";
echo "</div>";

echo "</div>";

echo "</body></html>";
?>
