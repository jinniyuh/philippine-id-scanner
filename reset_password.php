<?php
session_start();
require_once 'includes/conn.php';

$error = '';
$success = '';
$token = '';
$token_valid = false;
$user_data = null;

// Check if token is provided in URL
if (isset($_GET['token'])) {
    $token = trim($_GET['token']);
    
    // Validate token format (should be 64 characters hex)
    if (strlen($token) === 64 && ctype_xdigit($token)) {
        // Check if token exists in clients table first
        $stmt = $conn->prepare("SELECT client_id as id, full_name as name, email, username, reset_expiry, 'client' as user_type FROM clients WHERE reset_token = ?");
        if ($stmt) {
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();
            $stmt->close();
            
            // If not found in clients, check users table
            if (!$user_data) {
                $stmt = $conn->prepare("SELECT user_id as id, name, email, username, reset_expiry, role as user_type FROM users WHERE reset_token = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $token);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user_data = $result->fetch_assoc();
                    $stmt->close();
                }
            }
            
            if ($user_data) {
                // Check if token has expired
                $expiry_time = strtotime($user_data['reset_expiry']);
                $current_time = time();
                
                if ($current_time <= $expiry_time) {
                    $token_valid = true;
                } else {
                    $error = "This password reset link has expired. Please request a new one.";
                }
            } else {
                $error = "Invalid password reset link. Please request a new one.";
            }
        }
    } else {
        $error = "Invalid token format. Please request a new password reset link.";
    }
} else {
    $error = "No reset token provided. Please use the link from your email.";
}

// Handle password reset submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password']) && $token_valid) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate passwords
    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $new_password)) {
        $error = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $new_password)) {
        $error = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        $error = "Password must contain at least one number.";
    } else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password and clear reset token in appropriate table
        if ($user_data['user_type'] === 'client') {
            $updateStmt = $conn->prepare("UPDATE clients SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE client_id = ?");
        } else {
            $updateStmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE user_id = ?");
        }
        
        if ($updateStmt) {
            $updateStmt->bind_param("si", $hashed_password, $user_data['id']);
            
            if ($updateStmt->execute()) {
                $success = "Your password has been reset successfully! You can now login with your new password.";
                $token_valid = false; // Prevent form from showing again
                
                // Log the password reset (optional)
                error_log("Password reset successful for user ID: " . $user_data['id'] . " (" . $user_data['email'] . ")");
            } else {
                $error = "Database error. Please try again later.";
                error_log("Password reset failed for user ID: " . $user_data['id'] . " - DB Error: " . $conn->error);
            }
            $updateStmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Bago City Veterinary Office</title>
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
            max-width: 600px;
            width: 90%;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 30px;
            text-align: center;
        }
        
        .header i {
            font-size: 40px;
            margin-bottom: 10px;
        }
        
        .header h1 {
            font-size: 22px;
            margin-bottom: 8px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .content {
            padding: 30px 30px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
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
        
        .user-info {
            background: #f8f9fa;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        
        .user-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        
        .user-info strong {
            color: #667eea;
        }
        
        .form-group {
            margin-bottom: 18px;
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
        
        input[type="password"], input[type="text"] {
            width: 100%;
            padding: 12px 50px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
            box-sizing: border-box;
        }
        
        input[type="password"]:focus, input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #667eea;
            cursor: pointer;
            font-size: 16px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: all 0.2s ease;
            z-index: 10;
        }
        
        .toggle-password:hover {
            color: #764ba2;
            background-color: rgba(102, 126, 234, 0.1);
        }
        
        .toggle-password:active {
            transform: translateY(-50%) scale(0.95);
        }
        
        .toggle-password:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.3);
        }
        
        .password-strength {
            margin-top: 10px;
        }
        
        .strength-bar {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 5px;
        }
        
        .strength-bar-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
        }
        
        .strength-weak { background: #dc3545; width: 33%; }
        .strength-medium { background: #ffc107; width: 66%; }
        .strength-strong { background: #28a745; width: 100%; }
        
        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 10px;
        }
        
        .requirement {
            margin: 5px 0;
        }
        
        .requirement.met {
            color: #28a745;
        }
        
        .requirement i {
            margin-right: 5px;
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
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
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
            <i class="fas fa-lock"></i>
            <h1>Reset Your Password</h1>
            <p>Create a new secure password</p>
        </div>
        
        <div class="content">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong>Error:</strong><br>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                </div>
                
                <div class="back-link">
                    <a href="forgot_password.php">
                        <i class="fas fa-redo"></i> Request New Reset Link
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <strong>Success!</strong><br>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                </div>
                
                <div class="back-link">
                    <a href="login.php">
                        <i class="fas fa-sign-in-alt"></i> Go to Login
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if ($token_valid && !$success): ?>
                <div class="user-info">
                    <p><i class="fas fa-user"></i> <strong>Account:</strong> <?php echo htmlspecialchars($user_data['username']); ?></p>
                    <p><i class="fas fa-envelope"></i> <strong>Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>
                </div>
                
                <form method="POST" action="" id="resetForm">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="form-group">
                        <label for="new_password">
                            <i class="fas fa-key"></i> New Password
                        </label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input 
                                type="password" 
                                id="new_password" 
                                name="new_password" 
                                placeholder="Enter new password" 
                                required
                                autocomplete="new-password"
                            >
                            <button type="button" class="toggle-password" onclick="togglePassword('new_password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        
                        <div class="password-strength">
                            <div class="strength-bar">
                                <div class="strength-bar-fill" id="strengthBar"></div>
                            </div>
                            <div id="strengthText" style="font-size: 12px; color: #666;"></div>
                        </div>
                        
                        <div class="password-requirements">
                            <div class="requirement" id="req-length">
                                <i class="fas fa-circle"></i> At least 8 characters
                            </div>
                            <div class="requirement" id="req-uppercase">
                                <i class="fas fa-circle"></i> One uppercase letter
                            </div>
                            <div class="requirement" id="req-lowercase">
                                <i class="fas fa-circle"></i> One lowercase letter
                            </div>
                            <div class="requirement" id="req-number">
                                <i class="fas fa-circle"></i> One number
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-check-double"></i> Confirm Password
                        </label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                placeholder="Re-enter new password" 
                                required
                                autocomplete="new-password"
                            >
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div id="matchStatus" style="margin-top: 8px; font-size: 13px;"></div>
                    </div>
                    
                    <button type="submit" name="reset_password" class="btn" id="submitBtn">
                        <i class="fas fa-check"></i> Reset Password
                    </button>
                </form>
                
                <div class="back-link">
                    <a href="login.php">
                        <i class="fas fa-arrow-left"></i> Back to Login
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function togglePassword(fieldId, button) {
            const field = document.getElementById(fieldId);
            const icon = button.querySelector('i');
            
            if (!field || !icon) {
                console.error('Password toggle: Field or icon not found');
                return;
            }
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.className = 'fas fa-eye-slash';
                button.title = 'Hide password';
            } else {
                field.type = 'password';
                icon.className = 'fas fa-eye';
                button.title = 'Show password';
            }
        }
        
        // Password strength checker
        const newPasswordField = document.getElementById('new_password');
        const confirmPasswordField = document.getElementById('confirm_password');
        
        if (newPasswordField) {
            newPasswordField.addEventListener('input', function() {
                const password = this.value;
                const strengthBar = document.getElementById('strengthBar');
                const strengthText = document.getElementById('strengthText');
                
                // Check requirements
                const hasLength = password.length >= 8;
                const hasUppercase = /[A-Z]/.test(password);
                const hasLowercase = /[a-z]/.test(password);
                const hasNumber = /[0-9]/.test(password);
                
                // Update requirement indicators
                updateRequirement('req-length', hasLength);
                updateRequirement('req-uppercase', hasUppercase);
                updateRequirement('req-lowercase', hasLowercase);
                updateRequirement('req-number', hasNumber);
                
                // Calculate strength
                let strength = 0;
                if (hasLength) strength++;
                if (hasUppercase) strength++;
                if (hasLowercase) strength++;
                if (hasNumber) strength++;
                
                // Update strength bar
                strengthBar.className = 'strength-bar-fill';
                if (strength === 0) {
                    strengthBar.style.width = '0%';
                    strengthText.textContent = '';
                } else if (strength <= 2) {
                    strengthBar.classList.add('strength-weak');
                    strengthText.textContent = 'Weak password';
                    strengthText.style.color = '#dc3545';
                } else if (strength === 3) {
                    strengthBar.classList.add('strength-medium');
                    strengthText.textContent = 'Medium password';
                    strengthText.style.color = '#ffc107';
                } else {
                    strengthBar.classList.add('strength-strong');
                    strengthText.textContent = 'Strong password';
                    strengthText.style.color = '#28a745';
                }
                
                // Check if passwords match
                checkPasswordMatch();
            });
        }
        
        if (confirmPasswordField) {
            confirmPasswordField.addEventListener('input', checkPasswordMatch);
        }
        
        function updateRequirement(id, met) {
            const element = document.getElementById(id);
            if (met) {
                element.classList.add('met');
                element.querySelector('i').classList.remove('fa-circle');
                element.querySelector('i').classList.add('fa-check-circle');
            } else {
                element.classList.remove('met');
                element.querySelector('i').classList.remove('fa-check-circle');
                element.querySelector('i').classList.add('fa-circle');
            }
        }
        
        function checkPasswordMatch() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchStatus = document.getElementById('matchStatus');
            
            if (confirmPassword.length === 0) {
                matchStatus.textContent = '';
                return;
            }
            
            if (newPassword === confirmPassword) {
                matchStatus.innerHTML = '<i class="fas fa-check-circle" style="color: #28a745;"></i> Passwords match';
                matchStatus.style.color = '#28a745';
            } else {
                matchStatus.innerHTML = '<i class="fas fa-times-circle" style="color: #dc3545;"></i> Passwords do not match';
                matchStatus.style.color = '#dc3545';
            }
        }
        
        // Initialize password toggle buttons
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButtons = document.querySelectorAll('.toggle-password');
            toggleButtons.forEach(button => {
                // Set initial title
                const icon = button.querySelector('i');
                if (icon) {
                    if (icon.classList.contains('fa-eye')) {
                        button.title = 'Show password';
                    } else if (icon.classList.contains('fa-eye-slash')) {
                        button.title = 'Hide password';
                    }
                }
            });
        });
        
        // Form validation
        const form = document.getElementById('resetForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    return false;
                }
                
                if (newPassword.length < 8) {
                    e.preventDefault();
                    alert('Password must be at least 8 characters long!');
                    return false;
                }
            });
        }
    </script>
</body>
</html>

