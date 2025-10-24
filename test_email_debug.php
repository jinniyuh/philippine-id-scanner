<?php
session_start();
require_once 'includes/conn.php';
require_once 'includes/PHPMailer/PHPMailer.php';
require_once 'includes/PHPMailer/SMTP.php';
require_once 'includes/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access - Please login as admin first");
}

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Email Debug Test - Bago City Veterinary Office</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: orange; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { padding: 10px 20px; margin: 5px; background: #007bff; color: white; text-decoration: none; border-radius: 3px; }
        input[type='email'] { padding: 8px; margin: 5px; width: 300px; }
        input[type='submit'] { padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 3px; }
    </style>
</head>
<body>";

echo "<h1>🔧 Email Debug Test</h1>";

// Handle test email form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    $test_email = trim($_POST['email_address']);
    
    if (empty($test_email)) {
        echo "<div class='error'>❌ Please enter an email address.</div>";
    } elseif (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        echo "<div class='error'>❌ Please enter a valid email address. You entered: '" . htmlspecialchars($test_email) . "'</div>";
    } else {
        echo "<div class='section'>";
        echo "<h3>📧 Testing Email to: $test_email</h3>";
        
        try {
            $mail = new PHPMailer(true);
            
            // Enable verbose debug output
            $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
            $mail->Debugoutput = function($str, $level) {
                echo "<div class='info'>🔍 SMTP Debug: " . htmlspecialchars($str) . "</div>";
            };
            
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host       = 'smtp.hostinger.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'bagovet_info@bccbsis.com';
            $mail->Password   = 'Y^k*/[ElK4c';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            
            // Email settings
            $mail->setFrom('bagovet_info@bccbsis.com', 'Bago City Veterinary Office');
            $mail->addAddress($test_email, 'Test User');
            $mail->addReplyTo('bagovet_info@bccbsis.com', 'Bago City Veterinary Office');
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Test Email - Password Reset Debug';
            
            $mail->Body = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>✅ Test Email Successful!</h1>
                    </div>
                    <div class="content">
                        <p>Hello Test User,</p>
                        <p>This is a test email to verify that the email system is working correctly.</p>
                        <p><strong>Email Configuration:</strong></p>
                        <ul>
                            <li>SMTP Host: smtp.hostinger.com</li>
                            <li>Port: 465 (SSL)</li>
                            <li>From: bagovet_info@bccbsis.com</li>
                        </ul>
                        <p>If you received this email, the password reset functionality should work correctly.</p>
                        <p>Best regards,<br>Bago City Veterinary Office</p>
                    </div>
                </div>
            </body>
            </html>';
            
            $mail->send();
            echo "<div class='success'>✅ Test email sent successfully to $test_email!</div>";
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Email sending failed: " . $mail->ErrorInfo . "</div>";
            echo "<div class='error'>❌ Exception details: " . $e->getMessage() . "</div>";
        }
        
        echo "</div>";
    }
}

// Check database for users with emails
echo "<div class='section'>";
echo "<h3>📊 Database Email Check</h3>";

try {
    // Check clients with emails
    $clients_result = $conn->query("SELECT COUNT(*) as count FROM clients WHERE email IS NOT NULL AND email != ''");
    if ($clients_result) {
        $clients_count = $clients_result->fetch_assoc()['count'];
        echo "<div class='info'>📋 Clients with email addresses: $clients_count</div>";
    }
    
    // Check users with emails
    $users_result = $conn->query("SELECT COUNT(*) as count FROM users WHERE email IS NOT NULL AND email != ''");
    if ($users_result) {
        $users_count = $users_result->fetch_assoc()['count'];
        echo "<div class='info'>👥 Users with email addresses: $users_count</div>";
    }
    
    // Show users with emails
    $users_with_emails = $conn->query("SELECT user_id, name, username, email, role FROM users WHERE email IS NOT NULL AND email != ''");
    if ($users_with_emails && $users_with_emails->num_rows > 0) {
        echo "<h4>Users with Email Addresses:</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th></tr>";
        while ($user = $users_with_emails->fetch_assoc()) {
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
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Test email form
echo "<div class='section'>";
echo "<h3>📧 Send Test Email</h3>";
echo "<form method='POST'>";
echo "<p>Enter an email address to test the email system:</p>";
echo "<input type='email' name='email_address' placeholder='Enter email address' required>";
echo "<input type='submit' name='test_email' value='Send Test Email'>";
echo "</form>";
echo "</div>";

// Check PHP configuration
echo "<div class='section'>";
echo "<h3>⚙️ PHP Configuration Check</h3>";

echo "<div class='info'>📋 PHP Version: " . phpversion() . "</div>";
echo "<div class='info'>📋 OpenSSL: " . (extension_loaded('openssl') ? '✅ Enabled' : '❌ Disabled') . "</div>";
echo "<div class='info'>📋 cURL: " . (extension_loaded('curl') ? '✅ Enabled' : '❌ Disabled') . "</div>";
echo "<div class='info'>📋 Allow URL fopen: " . (ini_get('allow_url_fopen') ? '✅ Enabled' : '❌ Disabled') . "</div>";

echo "</div>";

echo "</body></html>";
?>
