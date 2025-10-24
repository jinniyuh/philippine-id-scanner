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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    $email = trim($_POST['email']);
    
    // Validate email
    if (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if email exists in clients table first
        $stmt = $conn->prepare("SELECT client_id as id, full_name as name, email, username, 'client' as user_type FROM clients WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            // If not found in clients, check users table
            if (!$user) {
                $stmt = $conn->prepare("SELECT user_id as id, name, email, username, role as user_type FROM users WHERE email = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    $stmt->close();
                }
            }
            
            if ($user) { 
                // Generate secure random token
                $token = bin2hex(random_bytes(32)); // 64 character token
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Store token in appropriate database table
                if ($user['user_type'] === 'client') {
                    $updateStmt = $conn->prepare("UPDATE clients SET reset_token = ?, reset_expiry = ? WHERE client_id = ?");
                } else {
                    $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE user_id = ?");
                }
                
                if ($updateStmt) {
                    $updateStmt->bind_param("ssi", $token, $expiry, $user['id']);
                    
                    if ($updateStmt->execute()) {
                        // Send email with PHPMailer
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
                            
                            // Email settings
                            $mail->setFrom('bagovet_info@bccbsis.com', 'Bago City Veterinary Office');
                            $mail->addAddress($email, $user['name']);
                            $mail->addReplyTo('bagovet_info@bccbsis.com', 'Bago City Veterinary Office');
                            
                            // Content
                            $mail->isHTML(true);
                            $mail->Subject = 'Password Reset Request - Bago City Veterinary Office';
                            
                            // Get the current domain for the reset link
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                            $domain = $_SERVER['HTTP_HOST'];
                            $base_path = rtrim(dirname($_SERVER['PHP_SELF']), '/');
                            $reset_link = $protocol . "://" . $domain . $base_path . "/reset_password.php?token=" . $token;
                            
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
                                    .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
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
                                        
                                        <center>
                                            <a href="' . $reset_link . '" class="button">Reset Password</a>
                                        </center>
                                        
                                        <p>Or copy and paste this link into your browser:</p>
                                        <p style="background: #fff; padding: 10px; border: 1px solid #ddd; word-break: break-all;">
                                            ' . $reset_link . '
                                        </p>
                                        
                                        <div class="warning">
                                            <strong>⚠️ Important:</strong>
                                            <ul>
                                                <li>This link will expire in <strong>1 hour</strong></li>
                                                <li>If you didn\'t request this reset, please ignore this email</li>
                                                <li>Never share this link with anyone</li>
                                            </ul>
                                        </div>
                                        
                                        <p>If the button doesn\'t work, you can also copy and paste the link above into your browser.</p>
                                        
                                        <p>Best regards,<br>
                                        <strong>Bago City Veterinary Office Team</strong></p>
                                    </div>
                                    <div class="footer">
                                        <p>&copy; ' . date('Y') . ' Bago City Veterinary Office. All rights reserved.</p>
                                        <p>This is an automated email. Please do not reply to this message.</p>
                                    </div>
                                </div>
                            </body>
                            </html>';
                            
                            // Plain text alternative
                            $mail->AltBody = "Hello " . $user['name'] . ",\n\n" .
                                           "We received a request to reset your password.\n\n" .
                                           "Please click the following link to reset your password:\n" .
                                           $reset_link . "\n\n" .
                                           "This link will expire in 1 hour.\n\n" .
                                           "If you didn't request this reset, please ignore this email.\n\n" .
                                           "Best regards,\nBago City Veterinary Office Team";
                            
                            $mail->send();
                            
                            $success = "Password reset instructions have been sent to your email address. Please check your inbox (and spam folder).";
                            
                        } catch (Exception $e) {
                            // Log the error (in production, use error_log instead of displaying)
                            error_log("PHPMailer Error: " . $mail->ErrorInfo);
                            $error = "Failed to send reset email. Please try again later or contact support.";
                        }
                    } else {
                        $error = "Database error. Please try again later.";
                    }
                    $updateStmt->close();
                }
            } else {
                // Don't reveal if email exists or not (security best practice)
                $success = "If that email address is registered, you will receive password reset instructions.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Bago City Veterinary Office</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .header i {
            font-size: 50px;
            margin-bottom: 15px;
        }
        
        .header h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
        }
        
        input[type="email"] {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        input[type="email"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .back-link a:hover {
            color: #764ba2;
        }
        
        @media (max-width: 600px) {
            .container {
                margin: 0;
            }
            
            .header {
                padding: 30px 20px;
            }
            
            .content {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <i class="fas fa-key"></i>
            <h1>Forgot Password?</h1>
            <p>Enter your email to receive reset instructions</p>
        </div>
        
        <div class="content">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <strong>How it works:</strong>
                <ol style="margin: 10px 0 0 20px; font-size: 13px;">
                    <li>Enter your registered email address</li>
                    <li>Check your email for a password reset link</li>
                    <li>Click the link and set your new password</li>
                    <li>The link expires in 1 hour for security</li>
                </ol>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="your.email@example.com" 
                            required
                            autocomplete="email"
                        >
                    </div>
                </div>
                
                <button type="submit" name="request_reset" class="btn">
                    <i class="fas fa-paper-plane"></i> Send Reset Link
                </button>
            </form>
            
            <div class="back-link">
                <a href="login.php">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>

