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
    <title>Test User Forgot Password - Bago City Veterinary Office</title>
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

echo "<h1>🔧 Test User Forgot Password Functionality</h1>";

// Handle test forgot password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_forgot_password'])) {
    $test_email = trim($_POST['email_address']);
    
    if (empty($test_email)) {
        echo "<div class='error'>❌ Please enter an email address.</div>";
    } elseif (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        echo "<div class='error'>❌ Please enter a valid email address. You entered: '" . htmlspecialchars($test_email) . "'</div>";
    } else {
        echo "<div class='section'>";
        echo "<h3>🔍 Testing Forgot Password for: $test_email</h3>";
        
        // Simulate the forgot password logic
        try {
            // Check if email exists in clients table first
            $stmt = $conn->prepare("SELECT client_id as id, full_name as name, email, username, 'client' as user_type FROM clients WHERE email = ?");
            if ($stmt) {
                $stmt->bind_param("s", $test_email);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $stmt->close();
                
                // If not found in clients, check users table
                if (!$user) {
                    $stmt = $conn->prepare("SELECT user_id as id, name, email, username, role as user_type FROM users WHERE email = ?");
                    if ($stmt) {
                        $stmt->bind_param("s", $test_email);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $user = $result->fetch_assoc();
                        $stmt->close();
                    }
                }
                
                if ($user) {
                    echo "<div class='success'>✅ User found in " . $user['user_type'] . " table!</div>";
                    echo "<div class='info'>👤 Name: " . htmlspecialchars($user['name']) . "</div>";
                    echo "<div class='info'>📧 Email: " . htmlspecialchars($user['email']) . "</div>";
                    echo "<div class='info'>👤 Username: " . htmlspecialchars($user['username']) . "</div>";
                    
                    // Generate secure random token
                    $token = bin2hex(random_bytes(32));
                    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    echo "<div class='info'>🔑 Generated token: " . substr($token, 0, 20) . "...</div>";
                    echo "<div class='info'>⏰ Token expires: $expiry</div>";
                    
                    // Store token in appropriate database table
                    if ($user['user_type'] === 'client') {
                        $updateStmt = $conn->prepare("UPDATE clients SET reset_token = ?, reset_expiry = ? WHERE client_id = ?");
                    } else {
                        $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE user_id = ?");
                    }
                    
                    if ($updateStmt) {
                        $updateStmt->bind_param("ssi", $token, $expiry, $user['id']);
                        
                        if ($updateStmt->execute()) {
                            echo "<div class='success'>✅ Token stored in database successfully!</div>";
                            
                            // Try to send email
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
                                
                                // Email settings
                                $mail->setFrom('bagovet_info@bccbsis.com', 'Bago City Veterinary Office');
                                $mail->addAddress($test_email, $user['name']);
                                $mail->addReplyTo('bagovet_info@bccbsis.com', 'Bago City Veterinary Office');
                                
                                // Content
                                $mail->isHTML(true);
                                $mail->Subject = 'Password Reset Request - Bago City Veterinary Office';
                                
                                // Get the current domain for the reset link
                                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                                $domain = $_SERVER['HTTP_HOST'];
                                $reset_link = $protocol . "://" . $domain . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
                                
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
                                
                                $mail->send();
                                echo "<div class='success'>✅ Password reset email sent successfully!</div>";
                                echo "<div class='info'>🔗 Reset link: <a href='$reset_link' target='_blank'>$reset_link</a></div>";
                                
                            } catch (Exception $e) {
                                echo "<div class='error'>❌ Email sending failed: " . $mail->ErrorInfo . "</div>";
                                echo "<div class='error'>❌ Exception: " . $e->getMessage() . "</div>";
                            }
                            
                        } else {
                            echo "<div class='error'>❌ Failed to store token in database</div>";
                        }
                        $updateStmt->close();
                    } else {
                        echo "<div class='error'>❌ Failed to prepare database statement</div>";
                    }
                    
                } else {
                    echo "<div class='error'>❌ No user found with email: $test_email</div>";
                    echo "<div class='info'>💡 Make sure the user has an email address in their profile</div>";
                }
            } else {
                echo "<div class='error'>❌ Database connection error</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
        }
        
        echo "</div>";
    }
}

// Show users with emails
echo "<div class='section'>";
echo "<h3>👥 Users with Email Addresses</h3>";

try {
    $users_with_emails = $conn->query("SELECT user_id, name, username, email, role FROM users WHERE email IS NOT NULL AND email != ''");
    if ($users_with_emails && $users_with_emails->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Action</th></tr>";
        while ($user = $users_with_emails->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td><button onclick=\"document.getElementById('test_email').value='" . htmlspecialchars($user['email']) . "'\">Test This Email</button></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>⚠️ No users found with email addresses</div>";
        echo "<div class='info'>💡 You need to add email addresses to your users first</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Test form
echo "<div class='section'>";
echo "<h3>🧪 Test Forgot Password</h3>";
echo "<form method='POST'>";
echo "<p>Enter an email address to test the forgot password functionality:</p>";
echo "<input type='email' name='email_address' id='test_email' placeholder='Enter email address' required>";
echo "<input type='submit' name='test_forgot_password' value='Test Forgot Password'>";
echo "</form>";
echo "</div>";

echo "</body></html>";
?>
