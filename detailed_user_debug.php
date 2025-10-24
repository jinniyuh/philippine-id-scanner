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
$debug_steps = [];

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Detailed User Forgot Password Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: orange; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .debug { background: #f8f9fa; border: 1px solid #dee2e6; padding: 10px; border-radius: 5px; margin: 10px 0; font-family: monospace; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        input[type='email'], input[type='submit'] { padding: 8px; margin: 5px; }
    </style>
</head>
<body>";

echo "<h1>🔍 Detailed User Forgot Password Debug</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    $email = trim($_POST['email']);
    $debug_steps[] = "=== TESTING EMAIL: $email ===";
    
    // Step 1: Validate email
    $debug_steps[] = "Step 1: Email validation";
    if (empty($email)) {
        $error = "Please enter your email address.";
        $debug_steps[] = "❌ Email is empty";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
        $debug_steps[] = "❌ Email format invalid";
    } else {
        $debug_steps[] = "✅ Email validation passed";
        
        // Step 2: Check clients table first
        $debug_steps[] = "Step 2: Checking clients table...";
        $stmt = $conn->prepare("SELECT client_id as id, full_name as name, email, username, 'client' as user_type FROM clients WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            $debug_steps[] = "Clients query result: " . ($user ? "Found user: " . print_r($user, true) : "No user found");
            
            // Step 3: Check users table if not found in clients
            if (!$user) {
                $debug_steps[] = "Step 3: Checking users table...";
                $stmt = $conn->prepare("SELECT user_id as id, name, email, username, role as user_type FROM users WHERE email = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    $stmt->close();
                    
                    $debug_steps[] = "Users query result: " . ($user ? "Found user: " . print_r($user, true) : "No user found");
                } else {
                    $debug_steps[] = "❌ Failed to prepare users query: " . $conn->error;
                }
            }
            
            if ($user) {
                $debug_steps[] = "✅ User found in " . $user['user_type'] . " table";
                
                // Step 4: Generate token
                $debug_steps[] = "Step 4: Generating reset token...";
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                $debug_steps[] = "Token: " . substr($token, 0, 20) . "...";
                $debug_steps[] = "Expiry: $expiry";
                
                // Step 5: Store token in database
                $debug_steps[] = "Step 5: Storing token in database...";
                if ($user['user_type'] === 'client') {
                    $updateStmt = $conn->prepare("UPDATE clients SET reset_token = ?, reset_expiry = ? WHERE client_id = ?");
                } else {
                    $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE user_id = ?");
                }
                
                if ($updateStmt) {
                    $updateStmt->bind_param("ssi", $token, $expiry, $user['id']);
                    
                    if ($updateStmt->execute()) {
                        $debug_steps[] = "✅ Token stored successfully";
                        
                        // Step 6: Send email
                        $debug_steps[] = "Step 6: Sending email...";
                        $mail = new PHPMailer(true);
                        
                        try {
                            // SMTP Configuration
                            $mail->isSMTP();
                            $mail->Host       = 'smtp.hostinger.com';
                            $mail->SMTPAuth   = true;
                            $mail->Username   = 'bagovet_info@bccbsis.com';
                            $mail->Password   = 'Y^k*/[ElK4c';
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                            $mail->Port       = 465;
                            
                            $debug_steps[] = "✅ SMTP configured";
                            
                            // Email settings
                            $mail->setFrom('bagovet_info@bccbsis.com', 'Bago City Veterinary Office');
                            $mail->addAddress($email, $user['name']);
                            $mail->addReplyTo('bagovet_info@bccbsis.com', 'Bago City Veterinary Office');
                            
                            $debug_steps[] = "✅ Email recipients set";
                            
                            // Content
                            $mail->isHTML(true);
                            $mail->Subject = 'Password Reset Request - Bago City Veterinary Office';
                            
                            // Get the current domain for the reset link
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                            $domain = $_SERVER['HTTP_HOST'];
                            $reset_link = $protocol . "://" . $domain . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
                            
                            $debug_steps[] = "Reset link: $reset_link";
                            
                            // Simple email body for testing
                            $mail->Body = "
                            <h2>Password Reset Request</h2>
                            <p>Hello " . htmlspecialchars($user['name']) . ",</p>
                            <p>Click the link below to reset your password:</p>
                            <p><a href='$reset_link'>Reset Password</a></p>
                            <p>This link expires in 1 hour.</p>
                            ";
                            
                            $mail->AltBody = "Password Reset Link: $reset_link";
                            
                            $debug_steps[] = "✅ Email content prepared";
                            
                            $mail->send();
                            $debug_steps[] = "✅ Email sent successfully!";
                            $success = "Password reset email sent successfully!";
                            
                        } catch (Exception $e) {
                            $debug_steps[] = "❌ Email sending failed: " . $e->getMessage();
                            $debug_steps[] = "❌ PHPMailer Error Info: " . $mail->ErrorInfo;
                            $error = "Failed to send email: " . $e->getMessage();
                        }
                        
                    } else {
                        $debug_steps[] = "❌ Failed to store token: " . $updateStmt->error;
                        $error = "Failed to store reset token.";
                    }
                    $updateStmt->close();
                } else {
                    $debug_steps[] = "❌ Failed to prepare update statement: " . $conn->error;
                    $error = "Database error occurred.";
                }
                
            } else {
                $debug_steps[] = "❌ No user found with email: $email";
                $error = "No account found with this email address.";
            }
        } else {
            $debug_steps[] = "❌ Failed to prepare clients query: " . $conn->error;
            $error = "Database error occurred.";
        }
    }
}

// Show current users with emails
echo "<div class='section'>";
echo "<h3>👥 Current Users with Email Addresses</h3>";

try {
    $users_with_emails = $conn->query("SELECT user_id, name, username, email, role FROM users WHERE email IS NOT NULL AND email != ''");
    if ($users_with_emails && $users_with_emails->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Test</th></tr>";
        while ($user = $users_with_emails->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td><button onclick=\"document.getElementById('test_email').value='" . htmlspecialchars($user['email']) . "'\">Test This</button></td>";
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

// Show debug information
if (!empty($debug_steps)) {
    echo "<div class='section'>";
    echo "<h3>🔍 Debug Steps</h3>";
    foreach ($debug_steps as $step) {
        echo "<div class='debug'>" . htmlspecialchars($step) . "</div>";
    }
    echo "</div>";
}

// Show errors/success
if ($error) {
    echo "<div class='error'>❌ " . $error . "</div>";
}

if ($success) {
    echo "<div class='success'>✅ " . $success . "</div>";
}

// Test form
echo "<div class='section'>";
echo "<h3>🧪 Test User Forgot Password</h3>";
echo "<form method='POST'>";
echo "<p>Enter an email address to test the forgot password functionality:</p>";
echo "<input type='email' name='email' id='test_email' placeholder='Enter email address' required style='width: 300px;'>";
echo "<input type='submit' name='test_email' value='Test Forgot Password'>";
echo "</form>";
echo "</div>";

echo "</body></html>";
?>
