<?php
session_start();
require_once 'includes/conn.php';
require_once 'includes/PHPMailer/PHPMailer.php';
require_once 'includes/PHPMailer/SMTP.php';
require_once 'includes/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = '';
$debug_info = [];

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    $email = trim($_POST['email']);
    $debug_info[] = "=== DEBUGGING FORGOT PASSWORD ===";
    $debug_info[] = "Email entered: $email";
    
    // Validate email
    if (empty($email)) {
        $error = "Please enter your email address.";
        $debug_info[] = "❌ Email is empty";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
        $debug_info[] = "❌ Email format invalid";
    } else {
        $debug_info[] = "✅ Email validation passed";
        
        // Check if email exists in clients table first
        $debug_info[] = "🔍 Checking clients table...";
        $stmt = $conn->prepare("SELECT client_id as id, full_name as name, email, username, 'client' as user_type FROM clients WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            if ($user) {
                $debug_info[] = "✅ User found in CLIENTS table: " . $user['name'] . " (ID: " . $user['id'] . ")";
            } else {
                $debug_info[] = "❌ User NOT found in clients table";
            }
            
            // If not found in clients, check users table
            if (!$user) {
                $debug_info[] = "🔍 Checking users table...";
                $stmt = $conn->prepare("SELECT user_id as id, name, email, username, role as user_type FROM users WHERE email = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    $stmt->close();
                    
                    if ($user) {
                        $debug_info[] = "✅ User found in USERS table: " . $user['name'] . " (ID: " . $user['id'] . ", Role: " . $user['user_type'] . ")";
                    } else {
                        $debug_info[] = "❌ User NOT found in users table either";
                    }
                } else {
                    $debug_info[] = "❌ Failed to prepare users query: " . $conn->error;
                }
            }
            
            if ($user) {
                $debug_info[] = "✅ User exists! Proceeding with password reset...";
                
                // Generate secure random token
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                $debug_info[] = "🔑 Token generated: " . substr($token, 0, 20) . "...";
                $debug_info[] = "⏰ Expiry: $expiry";
                
                // Store token in appropriate database table
                if ($user['user_type'] === 'client') {
                    $updateStmt = $conn->prepare("UPDATE clients SET reset_token = ?, reset_expiry = ? WHERE client_id = ?");
                    $debug_info[] = "📝 Updating CLIENTS table with token";
                } else {
                    $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE user_id = ?");
                    $debug_info[] = "📝 Updating USERS table with token";
                }
                
                if ($updateStmt) {
                    $updateStmt->bind_param("ssi", $token, $expiry, $user['id']);
                    
                    if ($updateStmt->execute()) {
                        $debug_info[] = "✅ Token stored in database successfully";
                        
                        // Send email with PHPMailer
                        $debug_info[] = "📧 Attempting to send email...";
                        $mail = new PHPMailer(true);
                        
                        try {
                            // SMTP Configuration for Hostinger (Working Configuration)
                            $mail->isSMTP();
                            $mail->Host       = 'smtp.hostinger.com';
                            $mail->SMTPAuth   = true;
                            $mail->Username   = 'bagovet_info@bccbsis.com';
                            $mail->Password   = 'Y^k*/[ElK4c';
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                            $mail->Port       = 465;
                            
                            $debug_info[] = "✅ SMTP configured";
                            
                            // Email settings
                            $mail->setFrom('bagovet_info@bccbsis.com', 'Bago City Veterinary Office');
                            $mail->addAddress($email, $user['name']);
                            $mail->addReplyTo('bagovet_info@bccbsis.com', 'Bago City Veterinary Office');
                            
                            $debug_info[] = "✅ Email recipients set";
                            
                            // Content
                            $mail->isHTML(true);
                            $mail->Subject = 'Password Reset Request - Bago City Veterinary Office';
                            
                            // Get the current domain for the reset link
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                            $domain = $_SERVER['HTTP_HOST'];
                            $reset_link = $protocol . "://" . $domain . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
                            
                            $debug_info[] = "🔗 Reset link: $reset_link";
                            
                            // Simple email body for testing
                            $mail->Body = "
                            <h2>Password Reset Request</h2>
                            <p>Hello " . htmlspecialchars($user['name']) . ",</p>
                            <p>Click the link below to reset your password:</p>
                            <p><a href='$reset_link'>Reset Password</a></p>
                            <p>This link expires in 1 hour.</p>
                            ";
                            
                            $mail->AltBody = "Password Reset Link: $reset_link";
                            
                            $debug_info[] = "✅ Email content prepared";
                            
                            $mail->send();
                            $debug_info[] = "🎉 EMAIL SENT SUCCESSFULLY!";
                            $success = "✅ SUCCESS: Password reset email sent to $email";
                            
                        } catch (Exception $e) {
                            $debug_info[] = "❌ EMAIL SENDING FAILED: " . $e->getMessage();
                            $debug_info[] = "❌ PHPMailer Error Info: " . $mail->ErrorInfo;
                            $error = "❌ FAILED to send email: " . $e->getMessage();
                        }
                        
                    } else {
                        $debug_info[] = "❌ Failed to store token: " . $updateStmt->error;
                        $error = "Database error. Please try again later.";
                    }
                    $updateStmt->close();
                } else {
                    $debug_info[] = "❌ Failed to prepare update statement: " . $conn->error;
                    $error = "Database error occurred.";
                }
                
            } else {
                $debug_info[] = "❌ NO USER FOUND with email: $email";
                $error = "❌ DEBUG: No account found with this email address. Check the debug info below.";
            }
        } else {
            $debug_info[] = "❌ Failed to prepare clients query: " . $conn->error;
            $error = "Database error occurred.";
        }
    }
}

// Show all users with emails for reference
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Debug Forgot Password - Live</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .debug { background: #f8f9fa; border: 1px solid #dee2e6; padding: 10px; border-radius: 5px; margin: 10px 0; font-family: monospace; white-space: pre-wrap; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        input[type='email'] { padding: 8px; width: 300px; }
        input[type='submit'] { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>";

echo "<h1>🔍 DEBUG: Forgot Password - Live Version</h1>";

echo "<div class='section'>";
echo "<h3>👥 Users with Email Addresses</h3>";

try {
    // Show clients with emails
    $clients_with_emails = $conn->query("SELECT client_id as id, full_name as name, email, username, 'client' as user_type FROM clients WHERE email IS NOT NULL AND email != ''");
    if ($clients_with_emails && $clients_with_emails->num_rows > 0) {
        echo "<h4>Clients:</h4>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Test</th></tr>";
        while ($user = $clients_with_emails->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td><button onclick=\"document.getElementById('test_email').value='" . htmlspecialchars($user['email']) . "'\">Test</button></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Show users with emails
    $users_with_emails = $conn->query("SELECT user_id as id, name, email, username, role as user_type FROM users WHERE email IS NOT NULL AND email != ''");
    if ($users_with_emails && $users_with_emails->num_rows > 0) {
        echo "<h4>Users (Admin/Staff):</h4>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Test</th></tr>";
        while ($user = $users_with_emails->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['user_type']) . "</td>";
            echo "<td><button onclick=\"document.getElementById('test_email').value='" . htmlspecialchars($user['email']) . "'\">Test</button></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    if ((!$clients_with_emails || $clients_with_emails->num_rows == 0) && (!$users_with_emails || $users_with_emails->num_rows == 0)) {
        echo "<div class='error'>❌ No users found with email addresses</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Show debug information
if (!empty($debug_info)) {
    echo "<div class='section'>";
    echo "<h3>🔍 Debug Information</h3>";
    foreach ($debug_info as $info) {
        echo "<div class='debug'>" . htmlspecialchars($info) . "</div>";
    }
    echo "</div>";
}

// Show errors/success
if ($error) {
    echo "<div class='error'>" . htmlspecialchars($error) . "</div>";
}

if ($success) {
    echo "<div class='success'>" . htmlspecialchars($success) . "</div>";
}

// Test form
echo "<div class='section'>";
echo "<h3>🧪 Test Forgot Password</h3>";
echo "<form method='POST'>";
echo "<p>Enter an email address to test:</p>";
echo "<input type='email' name='email' id='test_email' placeholder='Enter email address' required>";
echo "<input type='submit' name='request_reset' value='Test Forgot Password'>";
echo "</form>";
echo "</div>";

echo "<div class='section'>";
echo "<h3>⚠️ Important</h3>";
echo "<p><strong>Delete this file after debugging!</strong> This file shows sensitive information and should not be left on a live server.</p>";
echo "</div>";

echo "</body></html>";
?>
