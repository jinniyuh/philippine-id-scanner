<?php
/**
 * Password Reset Feature Installation Script
 * 
 * This script will:
 * 1. Check system requirements
 * 2. Verify database connection
 * 3. Add required database fields
 * 4. Test email configuration
 * 
 * SECURITY: Delete this file after installation!
 */

session_start();
require_once 'includes/conn.php';

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$success = [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Installation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .content {
            padding: 30px;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding: 20px 0;
            border-bottom: 2px solid #e0e0e0;
        }
        .step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #666;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .step.active .step-number {
            background: #667eea;
            color: white;
        }
        .step.completed .step-number {
            background: #28a745;
            color: white;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
        .btn {
            padding: 12px 30px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover {
            background: #764ba2;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 13px;
        }
        .check-item {
            padding: 10px;
            margin: 10px 0;
            background: #f8f9fa;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .check-item i {
            font-size: 20px;
        }
        .check-ok { color: #28a745; }
        .check-error { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-cog"></i> Password Reset Installation</h1>
            <p>Setup the forgot password feature</p>
        </div>
        
        <div class="content">
            <div class="step-indicator">
                <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
                    <div class="step-number">1</div>
                    <div>System Check</div>
                </div>
                <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
                    <div class="step-number">2</div>
                    <div>Database</div>
                </div>
                <div class="step <?php echo $step >= 3 ? 'active' : ''; ?> <?php echo $step > 3 ? 'completed' : ''; ?>">
                    <div class="step-number">3</div>
                    <div>Complete</div>
                </div>
            </div>
            
            <?php if ($step == 1): ?>
                <h2>Step 1: System Requirements Check</h2>
                
                <?php
                $checks_passed = true;
                
                // Check PHP Version
                $php_ok = version_compare(phpversion(), '7.0', '>=');
                echo "<div class='check-item'>";
                echo "<i class='fas fa-" . ($php_ok ? "check-circle check-ok" : "times-circle check-error") . "'></i>";
                echo "<div>PHP Version: " . phpversion() . ($php_ok ? " (OK)" : " (Requires 7.0+)") . "</div>";
                echo "</div>";
                if (!$php_ok) $checks_passed = false;
                
                // Check Database Connection
                $db_ok = $conn ? true : false;
                echo "<div class='check-item'>";
                echo "<i class='fas fa-" . ($db_ok ? "check-circle check-ok" : "times-circle check-error") . "'></i>";
                echo "<div>Database Connection: " . ($db_ok ? "Connected" : "Failed") . "</div>";
                echo "</div>";
                if (!$db_ok) $checks_passed = false;
                
                // Check PHPMailer
                $phpmailer_ok = file_exists('includes/PHPMailer/PHPMailer.php');
                echo "<div class='check-item'>";
                echo "<i class='fas fa-" . ($phpmailer_ok ? "check-circle check-ok" : "times-circle check-error") . "'></i>";
                echo "<div>PHPMailer: " . ($phpmailer_ok ? "Installed" : "Not Found") . "</div>";
                echo "</div>";
                if (!$phpmailer_ok) {
                    echo "<div class='alert alert-warning'>";
                    echo "<strong>PHPMailer not found!</strong><br>";
                    echo "Install using: <code>composer require phpmailer/phpmailer</code><br>";
                    echo "Or download manually from: <a href='https://github.com/PHPMailer/PHPMailer' target='_blank'>GitHub</a>";
                    echo "</div>";
                    $checks_passed = false;
                }
                
                // Check OpenSSL
                $ssl_ok = extension_loaded('openssl');
                echo "<div class='check-item'>";
                echo "<i class='fas fa-" . ($ssl_ok ? "check-circle check-ok" : "times-circle check-error") . "'></i>";
                echo "<div>OpenSSL Extension: " . ($ssl_ok ? "Loaded" : "Not Loaded") . "</div>";
                echo "</div>";
                if (!$ssl_ok) $checks_passed = false;
                
                // Check files exist
                $files_ok = file_exists('forgot_password.php') && file_exists('reset_password.php');
                echo "<div class='check-item'>";
                echo "<i class='fas fa-" . ($files_ok ? "check-circle check-ok" : "times-circle check-error") . "'></i>";
                echo "<div>Required Files: " . ($files_ok ? "Present" : "Missing") . "</div>";
                echo "</div>";
                if (!$files_ok) $checks_passed = false;
                
                if ($checks_passed) {
                    echo "<div class='alert alert-success'>";
                    echo "<i class='fas fa-check-circle'></i> All system requirements met!";
                    echo "</div>";
                    echo "<a href='?step=2' class='btn'>Continue to Database Setup <i class='fas fa-arrow-right'></i></a>";
                } else {
                    echo "<div class='alert alert-error'>";
                    echo "<i class='fas fa-exclamation-circle'></i> Please fix the errors above before continuing.";
                    echo "</div>";
                }
                ?>
                
            <?php elseif ($step == 2): ?>
                <h2>Step 2: Database Setup</h2>
                
                <?php
                if (isset($_POST['install_db'])) {
                    // Add reset_token column
                    $sql1 = "ALTER TABLE `clients` ADD COLUMN `reset_token` VARCHAR(64) NULL DEFAULT NULL";
                    if ($conn->query($sql1)) {
                        echo "<div class='alert alert-success'><i class='fas fa-check'></i> Added reset_token column</div>";
                    } else {
                        if (strpos($conn->error, 'Duplicate column name') !== false) {
                            echo "<div class='alert alert-info'><i class='fas fa-info-circle'></i> reset_token column already exists</div>";
                        } else {
                            echo "<div class='alert alert-error'><i class='fas fa-times'></i> Error: " . $conn->error . "</div>";
                        }
                    }
                    
                    // Add reset_expiry column
                    $sql2 = "ALTER TABLE `clients` ADD COLUMN `reset_expiry` DATETIME NULL DEFAULT NULL";
                    if ($conn->query($sql2)) {
                        echo "<div class='alert alert-success'><i class='fas fa-check'></i> Added reset_expiry column</div>";
                    } else {
                        if (strpos($conn->error, 'Duplicate column name') !== false) {
                            echo "<div class='alert alert-info'><i class='fas fa-info-circle'></i> reset_expiry column already exists</div>";
                        } else {
                            echo "<div class='alert alert-error'><i class='fas fa-times'></i> Error: " . $conn->error . "</div>";
                        }
                    }
                    
                    // Add index
                    $sql3 = "ALTER TABLE `clients` ADD INDEX `idx_reset_token` (`reset_token`)";
                    if ($conn->query($sql3)) {
                        echo "<div class='alert alert-success'><i class='fas fa-check'></i> Added index on reset_token</div>";
                    } else {
                        if (strpos($conn->error, 'Duplicate key name') !== false) {
                            echo "<div class='alert alert-info'><i class='fas fa-info-circle'></i> Index already exists</div>";
                        } else {
                            echo "<div class='alert alert-error'><i class='fas fa-times'></i> Error: " . $conn->error . "</div>";
                        }
                    }
                    
                    echo "<div class='alert alert-success'>";
                    echo "<i class='fas fa-check-circle'></i> <strong>Database setup complete!</strong>";
                    echo "</div>";
                    
                    echo "<a href='?step=3' class='btn btn-success'>Continue <i class='fas fa-arrow-right'></i></a>";
                } else {
                    echo "<div class='alert alert-info'>";
                    echo "<strong>The following changes will be made to your database:</strong>";
                    echo "<ul style='margin: 10px 0 0 20px;'>";
                    echo "<li>Add <code>reset_token</code> column to clients table</li>";
                    echo "<li>Add <code>reset_expiry</code> column to clients table</li>";
                    echo "<li>Add index on <code>reset_token</code> for faster lookups</li>";
                    echo "</ul>";
                    echo "</div>";
                    
                    echo "<pre>";
                    echo "ALTER TABLE `clients` ADD COLUMN `reset_token` VARCHAR(64) NULL DEFAULT NULL;\n";
                    echo "ALTER TABLE `clients` ADD COLUMN `reset_expiry` DATETIME NULL DEFAULT NULL;\n";
                    echo "ALTER TABLE `clients` ADD INDEX `idx_reset_token` (`reset_token`);";
                    echo "</pre>";
                    
                    echo "<form method='POST'>";
                    echo "<button type='submit' name='install_db' class='btn'>Install Database Changes</button>";
                    echo "</form>";
                }
                ?>
                
            <?php elseif ($step == 3): ?>
                <h2>Installation Complete!</h2>
                
                <div class='alert alert-success'>
                    <i class='fas fa-check-circle'></i>
                    <strong>Password reset feature installed successfully!</strong>
                </div>
                
                <h3>What's Next?</h3>
                <ol style="line-height: 2;">
                    <li><strong>Test the feature:</strong> <a href="test_email_config.php" target="_blank">Test Email Configuration</a></li>
                    <li><strong>Update login page:</strong> Add a link to <code>forgot_password.php</code></li>
                    <li><strong>Delete installer files:</strong>
                        <ul style="margin-top: 10px;">
                            <li><code>install_password_reset.php</code> (this file)</li>
                            <li><code>test_email_config.php</code> (after testing)</li>
                        </ul>
                    </li>
                </ol>
                
                <div class='alert alert-warning'>
                    <i class='fas fa-exclamation-triangle'></i>
                    <strong>Security Reminder:</strong><br>
                    Delete <code>install_password_reset.php</code> and <code>test_email_config.php</code> from your server after installation!
                </div>
                
                <h3>Available Pages</h3>
                <a href="forgot_password.php" class="btn" target="_blank">
                    <i class="fas fa-key"></i> Forgot Password
                </a>
                <a href="login.php" class="btn" target="_blank">
                    <i class="fas fa-sign-in-alt"></i> Login Page
                </a>
                <a href="test_email_config.php" class="btn" target="_blank">
                    <i class="fas fa-envelope"></i> Test Email
                </a>
                
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

