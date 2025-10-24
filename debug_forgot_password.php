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

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Debug Forgot Password - Bago City Veterinary Office</title>
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

echo "<h1>🔧 Debug Forgot Password</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    $email = trim($_POST['email']);
    $debug_info[] = "Email received: " . $email;
    
    // Validate email
    if (empty($email)) {
        $error = "Please enter your email address.";
        $debug_info[] = "Error: Empty email";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
        $debug_info[] = "Error: Invalid email format";
    } else {
        $debug_info[] = "Email validation passed";
        
        // Check if email exists in clients table first
        $debug_info[] = "Checking clients table...";
        $stmt = $conn->prepare("SELECT client_id as id, full_name as name, email, username, 'client' as user_type FROM clients WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            $debug_info[] = "Clients query result: " . ($user ? "Found user" : "No user found");
            
            // If not found in clients, check users table
            if (!$user) {
                $debug_info[] = "Checking users table...";
                $stmt = $conn->prepare("SELECT user_id as id, name, email, username, role as user_type FROM users WHERE email = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    $stmt->close();
                    
                    $debug_info[] = "Users query result: " . ($user ? "Found user" : "No user found");
                } else {
                    $debug_info[] = "Error: Failed to prepare users query";
                }
            }
            
            if ($user) {
                $debug_info[] = "User found: " . print_r($user, true);
                
                // Generate secure random token
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                $debug_info[] = "Generated token: " . substr($token, 0, 20) . "...";
                $debug_info[] = "Token expiry: " . $expiry;
                
                // Store token in appropriate database table
                if ($user['user_type'] === 'client') {
                    $debug_info[] = "Updating clients table...";
                    $updateStmt = $conn->prepare("UPDATE clients SET reset_token = ?, reset_expiry = ? WHERE client_id = ?");
                } else {
                    $debug_info[] = "Updating users table...";
                    $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE user_id = ?");
                }
                
                if ($updateStmt) {
                    $updateStmt->bind_param("ssi", $token, $expiry, $user['id']);
                    
                    if ($updateStmt->execute()) {
                        $debug_info[] = "Token stored successfully";
                        
                        // Send email with PHPMailer
                        $mail = new PHPMailer(true);
                        
                        try {
                            $debug_info[] = "Starting email send...";
                            
                            // SMTP Configuration
                            $mail->isSMTP();
                            $mail->Host       = 'smtp.hostinger.com';
                            $mail->SMTPAuth   = true;
                            $mail->Username   = 'bagovet_info@bccbsis.com';
                            $mail->Password   = 'Y^k*/[ElK4c';
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                            $mail->Port       = 465;
                            
                            $debug_info[] = "SMTP configured";
                            
                            // Email settings
                            $mail->setFrom('bagovet_info@bccbsis.com', 'Bago City Veterinary Office');
                            $mail->addAddress($email, $user['name']);
                            $mail->addReplyTo('bagovet_info@bccbsis.com', 'Bago City Veterinary Office');
                            
                            $debug_info[] = "Email recipients set";
                            
                            // Content
                            $mail->isHTML(true);
                            $mail->Subject = 'Password Reset Request - Bago City Veterinary Office';
                            
                            // Get the current domain for the reset link
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                            $domain = $_SERVER['HTTP_HOST'];
                            $reset_link = $protocol . "://" . $domain . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
                            
                            $debug_info[] = "Reset link: " . $reset_link;
                            
                            // HTML email body
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
                                    .button { display: inline-block; padding: 12px 30px; background: #667eea; color: white !important; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
                                    .footer { text-align: center; margin-top: 20px; color: #777; font-size: 12px; }
                                </style>
                            </head>
                            <body>
                                <div class="container">
                                    <div class="header">
                                        <h1>🔐 Password Reset Request</h1>
                                    </div>
                                    <div class="content">
                                        <p>Hello <strong>' . htmlspecialchars($user['name']) . '</strong>,</p>
                                        
                                        <p>We received a request to reset your password for your Bago City Veterinary Office account (<strong>' . htmlspecialchars($user['username']) . '</strong>).</p>
                                        
                                        <p>Click the button below to reset your password:</p>
                                        
                                        <p><a href="' . $reset_link . '" class="button">Reset Password</a></p>
                                        
                                        <p>Or copy and paste this link into your browser:</p>
                                        <p><a href="' . $reset_link . '">' . $reset_link . '</a></p>
                                        
                                        <p><strong>This link will expire in 1 hour for security reasons.</strong></p>
                                        
                                        <p>If you did not request this password reset, please ignore this email.</p>
                                        
                                        <p>Best regards,<br>Bago City Veterinary Office</p>
                                    </div>
                                    <div class="footer">
                                        <p>This is an automated message. Please do not reply to this email.</p>
                                    </div>
                                </div>
                            </body>
                            </html>';
                            
                            $debug_info[] = "Email body prepared";
                            
                            $mail->send();
                            $debug_info[] = "Email sent successfully!";
                            $success = "Password reset email sent successfully! Please check your email.";
                            
                        } catch (Exception $e) {
                            $debug_info[] = "Email sending failed: " . $e->getMessage();
                            $error = "Failed to send email: " . $e->getMessage();
                        }
                        
                    } else {
                        $debug_info[] = "Failed to store token";
                        $error = "Failed to store reset token.";
                    }
                    $updateStmt->close();
                } else {
                    $debug_info[] = "Failed to prepare update statement";
                    $error = "Database error occurred.";
                }
                
            } else {
                $debug_info[] = "No user found with email: " . $email;
                $error = "No account found with this email address.";
            }
        } else {
            $debug_info[] = "Failed to prepare clients query";
            $error = "Database error occurred.";
        }
    }
}

// Show current users with emails
echo "<div class='section'>";
echo "<h3>📊 Current Users with Email Addresses</h3>";

try {
    $users_with_emails = $conn->query("SELECT user_id, name, username, email, role FROM users WHERE email IS NOT NULL AND email != ''");
    if ($users_with_emails && $users_with_emails->num_rows > 0) {
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
    echo "<div class='error'>❌ " . $error . "</div>";
}

if ($success) {
    echo "<div class='success'>✅ " . $success . "</div>";
}

// Forgot password form
echo "<div class='section'>";
echo "<h3>🔐 Test Forgot Password</h3>";
echo "<form method='POST'>";
echo "<p>Enter an email address to test the forgot password functionality:</p>";
echo "<input type='email' name='email' placeholder='Enter email address' required style='width: 300px;'>";
echo "<input type='submit' name='request_reset' value='Request Password Reset'>";
echo "</form>";
echo "</div>";

echo "</body></html>";
?>
