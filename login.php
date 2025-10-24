<?php
session_start();
include 'includes/conn.php';
include 'includes/activity_logger.php';

$login_error = '';
$register_error = '';
$success_msg = ''; 
$reset_error = '';
$reset_success = '';

function sanitize($data) {
    return htmlspecialchars(trim($data));
}

// Normalize text for matching
function normalize_text($str) {
    $str = strtoupper($str ?? '');
    $str = preg_replace('/BRGY\.?/', 'BARANGAY', $str);
    $str = preg_replace('/[^A-Z0-9\s]/', ' ', $str);
    $str = preg_replace('/\s+/', ' ', $str);
    return trim($str);
}

function checkNameWordsMatch($enteredName, $extractedName) {
    // Split names into words
    $enteredWords = array_filter(explode(' ', $enteredName));
    $extractedWords = array_filter(explode(' ', $extractedName));
    
    // Check if all extracted words are present in entered name
    foreach ($extractedWords as $extractedWord) {
        $found = false;
        foreach ($enteredWords as $enteredWord) {
            if (strpos($enteredWord, $extractedWord) !== false || strpos($extractedWord, $enteredWord) !== false) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            return false;
        }
    }
    
    return true;
}

function tokens_present_count($haystack, $tokens) {
    $count = 0;
    foreach ($tokens as $t) {
        if ($t && strpos($haystack, $t) !== false) $count++;
    }
    return $count;
}

function find_tesseract_binary() {
    // If exec is disabled on hosting, bail out gracefully
    if (!function_exists('exec')) {
        return null;
    }
    $candidates = [];
    $envPath = getenv('TESSERACT_PATH');
    if ($envPath) $candidates[] = $envPath;
    $candidates[] = 'tesseract';
    $candidates[] = 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe';
    foreach ($candidates as $bin) {
        $cmd = escapeshellarg($bin) . ' --version';
        @exec($cmd, $out, $ret);
        if ($ret === 0) return $bin;
    }
    return null;
}

function perform_ocr($imagePath) {
    $tesseract = find_tesseract_binary();
    // If tesseract/exec is not available (shared hosting), gracefully skip server OCR
    if (!$tesseract) {
        return [true, ''];
    }

    $outputBase = $imagePath . '_' . uniqid('ocr_');
    $cmd = escapeshellarg($tesseract) . ' ' . escapeshellarg($imagePath) . ' ' . escapeshellarg($outputBase) . ' -l eng';
    @exec($cmd, $out, $ret);
    if ($ret !== 0) return [false, 'Failed to run Tesseract OCR'];

    $txtFile = $outputBase . '.txt';
    if (!file_exists($txtFile)) return [false, 'OCR output missing'];
    $text = file_get_contents($txtFile);
    @unlink($txtFile);
    return [true, $text];
}

// Geocode function to get lat/lng from address using OpenStreetMap Nominatim API
function geocodeAddress($address) {
    $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($address);
    $opts = [
        "http" => [
            "header" => "User-Agent: BagoVetSystem/1.0\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    $json = @file_get_contents($url, false, $context);
    if (!$json) return null;

    $data = json_decode($json, true);
    if (!empty($data)) {
        return [
            'lat' => $data[0]['lat'],
            'lon' => $data[0]['lon']
        ];
    }
    return null;
}

// Registration Logic
if (isset($_POST['register'])) {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $contact_number = sanitize($_POST['contact_number'] ?? '');
    $email = filter_var(sanitize($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $address = sanitize($_POST['address'] ?? '');
    $username = strtolower(sanitize($_POST['username'] ?? ''));
    $password_raw = $_POST['password'] ?? '';

    // Validate required fields
    if (!$full_name || !$contact_number || !$email || !$address || !$username || !$password_raw) {
        $register_error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_error = "Please enter a valid email address.";
    } else {
        // Validate and process uploaded valid ID image
        if (!isset($_FILES['valid_id']) || $_FILES['valid_id']['error'] !== UPLOAD_ERR_OK) {
            $register_error = "Valid ID image is required.";
        } else {
            $fileTmp = $_FILES['valid_id']['tmp_name'];
            $fileName = $_FILES['valid_id']['name'];
            $fileSize = $_FILES['valid_id']['size'];

            // Basic validations
            if ($fileSize > 5 * 1024 * 1024) { // 5MB
                $register_error = "Valid ID image must be 5MB or smaller.";
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($fileTmp);
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                if (!isset($allowed[$mime])) {
                    $register_error = "Only JPG, PNG, or WEBP images are allowed for Valid ID.";
                } else {
                    $ext = $allowed[$mime];
                    $safeUser = preg_replace('/[^a-z0-9_\-]/i', '_', $username ?: 'user');
                    $targetDir = __DIR__ . '/uploads/valid_ids';
                    if (!is_dir($targetDir)) @mkdir($targetDir, 0775, true);
                    $targetPath = $targetDir . '/valid_id_' . $safeUser . '_' . time() . '.' . $ext;

                    if (!@move_uploaded_file($fileTmp, $targetPath)) {
                        $register_error = "Failed to save Valid ID image.";
                    } else {
                        // Use DocTR text recognition for Philippine ID
                        $clientExtracted = isset($_POST['scanned_full_name']) ? (string)$_POST['scanned_full_name'] : '';
                        $clientBarangay = isset($_POST['scanned_barangay']) ? (string)$_POST['scanned_barangay'] : '';
                        
                        if ($clientExtracted && $clientBarangay) {
                            // Client-side text recognition was successful
                            $enteredNorm = normalize_text($full_name);
                            $extractedNorm = normalize_text($clientExtracted);
                            $enteredBarangayNorm = normalize_text($address);
                            $extractedBarangayNorm = normalize_text($clientBarangay);
                            
                            // Debug: Log the comparison
                            error_log("DEBUG: Entered name: '$full_name' -> normalized: '$enteredNorm'");
                            error_log("DEBUG: Extracted name: '$clientExtracted' -> normalized: '$extractedNorm'");
                            error_log("DEBUG: Entered barangay: '$address' -> normalized: '$enteredBarangayNorm'");
                            error_log("DEBUG: Extracted barangay: '$clientBarangay' -> normalized: '$extractedBarangayNorm'");
                            
                            // RULE 1: Name must match exactly (strict validation)
                            $nameOk = ($enteredNorm === $extractedNorm);
                            
                            // RULE 2: Barangay must match exactly
                            $barangayOk = ($enteredBarangayNorm === $extractedBarangayNorm);
                            
                            // RULE 3: Additional validation - Check if entered name is missing words from extracted name
                            $nameWordsMatch = checkNameWordsMatch($enteredNorm, $extractedNorm);
                            
                            // RULE 4: Check if entered name is significantly shorter than extracted name (missing characters)
                            $enteredLength = strlen($enteredNorm);
                            $extractedLength = strlen($extractedNorm);
                            $lengthDifference = abs($enteredLength - $extractedLength);
                            $significantDifference = ($lengthDifference > 3); // Allow small differences but not major ones
                            
                            error_log("DEBUG: Name exact match: " . ($nameOk ? 'YES' : 'NO'));
                            error_log("DEBUG: Barangay match: " . ($barangayOk ? 'YES' : 'NO'));
                            error_log("DEBUG: Name words match: " . ($nameWordsMatch ? 'YES' : 'NO'));
                            error_log("DEBUG: Length difference: $lengthDifference (significant: " . ($significantDifference ? 'YES' : 'NO') . ")");
                            
                            // Check all validation rules
                            $validationFailed = false;
                            $errorMessages = [];
                            
                            if (!$nameOk) {
                                $validationFailed = true;
                                if ($significantDifference) {
                                    $errorMessages[] = "❌ MISMATCH: The full name you entered is missing characters compared to the Philippine ID. Please enter your complete name as shown on the ID.";
                                } else {
                                    $errorMessages[] = "❌ MISMATCH: The full name you entered does not match the name from the Philippine ID. Please check and try again.";
                                }
                            }
                            
                            if (!$barangayOk) {
                                $validationFailed = true;
                                $errorMessages[] = "❌ MISMATCH: The barangay you entered does not match the barangay from the Philippine ID. Please check and try again.";
                            }
                            
                            if ($validationFailed) {
                                $register_error = implode(" ", $errorMessages);
                                error_log("DEBUG: Validation failed - Name: " . ($nameOk ? 'OK' : 'FAIL') . ", Barangay: " . ($barangayOk ? 'OK' : 'FAIL') . ", Words: " . ($nameWordsMatch ? 'OK' : 'FAIL'));
                            } else {
                                $success_msg = "✅ Philippine ID Verified and Registration Complete.";
                                error_log("DEBUG: All validations passed - proceeding with registration");
                            }
                        } else {
                            // Check if this is a server-side Python issue
                            if (isset($_POST['fallback']) && $_POST['fallback'] === 'true') {
                                // Allow manual registration when ID scanning is unavailable
                                $register_error = null; // Clear error to allow registration
                                error_log("DEBUG: ID scanning unavailable on server - allowing manual registration");
                            } else {
                                $register_error = "❌ Failed to process Philippine ID. Please ensure the image is clear and contains a valid Philippine ID and try again.";
                                error_log("DEBUG: Missing scanned data - clientExtracted: '$clientExtracted', clientBarangay: '$clientBarangay'");
                            }
                        }


                        // Check if username or email exists in clients or users table
                        if (!$register_error) {
                            error_log("DEBUG: Starting database checks and insertion");
                            error_log("DEBUG: Database: " . $conn->database);
                            error_log("DEBUG: Connection status: " . ($conn->connect_error ? 'FAILED' : 'OK'));
                            $checkStmt = $conn->prepare("SELECT username FROM clients WHERE username = ? OR email = ? UNION SELECT username FROM users WHERE username = ?");
                            if (!$checkStmt) {
                                $register_error = "Error preparing check statement: " . $conn->error;
                            } else {
                                $checkStmt->bind_param("sss", $username, $email, $username);
                                $checkStmt->execute();
                                $checkResult = $checkStmt->get_result();

                                if ($checkResult->num_rows > 0) {
                                    // Check if it's username or email that's taken
                                    $checkEmailStmt = $conn->prepare("SELECT email FROM clients WHERE email = ?");
                                    $checkEmailStmt->bind_param("s", $email);
                                    $checkEmailStmt->execute();
                                    $emailResult = $checkEmailStmt->get_result();
                                    
                                    if ($emailResult->num_rows > 0) {
                                        $register_error = "Email address already registered. Please use a different email.";
                                    } else {
                                        $register_error = "Username already taken. Please choose another.";
                                    }
                                } else {
                                    // Geocode the full address (barangay + city + province + country)
                                    $fullAddress = $address . ", Bago City, Negros Occidental, Philippines";
                                    $coords = geocodeAddress($fullAddress);

                                    // Use geocoded coords or null if failed
                                    $latitude = $coords ? $coords['lat'] : null;
                                    $longitude = $coords ? $coords['lon'] : null;

                                    // Hash password
                                    $password = password_hash($password_raw, PASSWORD_DEFAULT);

                                    // Insert into clients table with lat/lng (clients only)
                                    $stmt1 = $conn->prepare("INSERT INTO clients (full_name, contact_number, email, barangay, username, password, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

                                    if (!$stmt1) {
                                        $register_error = "Error preparing insert statements.";
                                    } else {
                                        $stmt1->bind_param("ssssssss", $full_name, $contact_number, $email, $address, $username, $password, $latitude, $longitude);

                                        if ($stmt1->execute()) {
                                            error_log("DEBUG: Database insertion successful");
                                            if (!$success_msg) {
                                                $success_msg = "Registration successful! You may now log in.";
                                            }
                                        } else {
                                            $register_error = "Registration failed. Please try again.";
                                            error_log("DEBUG: Database insertion failed: " . $stmt1->error);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

// Login Logic
if (isset($_POST['login'])) {
    $username = strtolower(sanitize($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    $loginSuccess = false;

    if (!$username || !$password) {
        $login_error = "Please enter username and password.";
    } else {
        $clientStmt = $conn->prepare("SELECT * FROM clients WHERE username = ?");
        if ($clientStmt) {
            $clientStmt->bind_param("s", $username);
            $clientStmt->execute();
            $clientResult = $clientStmt->get_result();
            $client = $clientResult->fetch_assoc();

            if ($client && password_verify($password, $client['password'])) {
                if (password_needs_rehash($client['password'], PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $rehashStmt = $conn->prepare("UPDATE clients SET password = ? WHERE client_id = ?");
                    if ($rehashStmt) {
                        $rehashStmt->bind_param("si", $newHash, $client['client_id']);
                        $rehashStmt->execute();
                    }
                }
                $_SESSION['client_id'] = $client['client_id'];
                $_SESSION['role'] = 'client';
                $_SESSION['name'] = $client['full_name'];
                $loginSuccess = true;
            }
        }

        if (!$loginSuccess) {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();

                if ($user && password_verify($password, $user['password'])) {
                    if (isset($user['status']) && $user['status'] !== 'Active') {
                        $login_error = "Your account is disabled. Please contact an administrator.";
                        $loginSuccess = false;
                    } else {
                        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                            $newHash = password_hash($password, PASSWORD_DEFAULT);
                            $rehashStmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                            if ($rehashStmt) {
                                $rehashStmt->bind_param("si", $newHash, $user['user_id']);
                                $rehashStmt->execute();
                            }
                        }
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['name'] = $user['name'];
                        $loginSuccess = true;
                    }
                }
            }
        }

        if ($loginSuccess) {
            if ($_SESSION['role'] === 'admin') {
                if (isset($_SESSION['user_id'])) { logActivity($conn, $_SESSION['user_id'], 'Logged in'); }
                header("Location: admin_dashboard.php");
            } elseif ($_SESSION['role'] === 'staff') {
                if (isset($_SESSION['user_id'])) { logActivity($conn, $_SESSION['user_id'], 'Logged in'); }
                header("Location: staff_dashboard.php");
            } elseif ($_SESSION['role'] === 'client') {
                header("Location: client_dashboard.php");
            }
            exit;
        } else {
            $login_error = "Invalid credentials!";
        }
    }
}

// Forgot Password Logic - Send Reset Email
if (isset($_POST['request_reset'])) {
    $email = filter_var(trim($_POST['reset_email'] ?? ''), FILTER_SANITIZE_EMAIL);
    
    if (empty($email)) {
        $reset_error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $reset_error = "Please enter a valid email address.";
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
                        // Generate reset link
                        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                        $domain = $_SERVER['HTTP_HOST'];
                        $reset_link = $protocol . "://" . $domain . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
                        
                        // Send email using PHPMailer
                        try {
                            require_once 'includes/PHPMailer/PHPMailer.php';
                            require_once 'includes/PHPMailer/SMTP.php';
                            require_once 'includes/PHPMailer/Exception.php';
                            
                            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                            
                            // Hostinger SMTP Configuration
                            $mail->isSMTP();
                            $mail->Host = 'smtp.hostinger.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = 'bagovet_info@bccbsis.com';
                            $mail->Password = 'Y^k*/[ElK4c';
                            $mail->SMTPSecure = 'ssl';
                            $mail->Port = 465;
                            
                            // Email content
                            $mail->setFrom('bagovet_info@bccbsis.com', 'BagoVet IMS');
                            $mail->addAddress($user['email'], $user['name']);
                            $mail->isHTML(true);
                            
                            $mail->Subject = 'Password Reset Request - BagoVet IMS';
                            $mail->Body = '
                                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                                    <h2 style="color: #2c3e50;">Password Reset Request</h2>
                                    <p>Hello ' . htmlspecialchars($user['name']) . ',</p>
                                    
                                    <p>We received a request to reset your password for your BagoVet IMS account.</p>
                                    
                                    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
                                        <p style="margin: 0;"><strong>Account Details:</strong></p>
                                        <p style="margin: 5px 0 0 0;">Username: ' . htmlspecialchars($user['username']) . '</p>
                                        <p style="margin: 5px 0 0 0;">Email: ' . htmlspecialchars($user['email']) . '</p>
                                        <p style="margin: 5px 0 0 0;">Role: ' . htmlspecialchars($user['user_type']) . '</p>
                                    </div>
                                    
                                    <p>To reset your password, click the button below:</p>
                                    
                                    <div style="text-align: center; margin: 30px 0;">
                                        <a href="' . $reset_link . '" style="background-color: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Reset My Password</a>
                                    </div>
                                    
                                    <p><strong>Important Security Information:</strong></p>
                                    <ul>
                                        <li>This link will expire in 1 hour for security reasons</li>
                                        <li>If you did not request this password reset, please ignore this email</li>
                                        <li>Your password will not be changed until you click the link above</li>
                                    </ul>
                                    
                                    <p>If the button above doesn\'t work, you can copy and paste this link into your browser:</p>
                                    <p style="word-break: break-all; color: #007bff;">' . $reset_link . '</p>
                                    
                                    <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
                                    <p style="color: #666; font-size: 14px;">
                                        This email was sent from BagoVet Information Management System.<br>
                                        Please do not reply to this email.
                                    </p>
                                </div>
                            ';
                            
                            $mail->send();
                            
                            // Success message
                            $reset_success = "Password reset link has been sent to your email address. Please check your inbox and follow the instructions.";
                            
                            // Log successful email send
                            error_log("Password reset email sent to: " . $user['email'] . " | Token: " . $token);
                            
                        } catch (Exception $e) {
                            // Email sending failed
                            error_log("Failed to send password reset email to: " . $user['email'] . " | Error: " . $mail->ErrorInfo);
                            
                            // Show fallback with link for testing
                            $reset_success = "Password reset link generated. Email sending failed, but you can use this link for testing: <a href='" . $reset_link . "' target='_blank'>Click here</a>";
                        }
                    } else {
                        $reset_error = "Failed to generate reset link. Please try again.";
                    }
                    $updateStmt->close();
                }
            } else {
                // Don't reveal if email exists (security best practice)
                $reset_success = "If that email address is registered, you will receive password reset instructions.";
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
  <title>Login - Bago City Veterinary Office</title>
  <link rel="stylesheet" href="assets/login.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="assets/js/barcode_scanner.js"></script>
</head>
<body>
  <div class="login-container">
    <div class="left-side">
      <img src="assets/vetlogo.png" alt="Logo" class="logo">
      <h2>Bago City Veterinary Office</h2>
      <p class="welcome-text">Welcome to our Veterinary Management System.</p>
    </div>
    <div class="right-side">
      <h3 class="mb-4">Login to Your Account</h3>
      <?php if ($success_msg): ?>
      <div class="alert alert-success py-2"><?php echo $success_msg; ?></div>
      <?php endif; ?>
      <?php if ($login_error): ?>
      <div class="alert alert-danger py-2"><?php echo $login_error; ?></div>
      <?php endif; ?>
 
      <form method="POST" novalidate>
          <div class="mb-3 input-group">
            <span class="input-group-text"><i class="fa fa-user"></i></span>
            <input type="text" name="username" class="form-control" placeholder="Username" required />
          </div>
          <div class="mb-3 input-group">
            <span class="input-group-text"><i class="fa fa-lock"></i></span>
            <input type="password" name="password" class="form-control" id="loginPassword" placeholder="Password" required />
            <button class="btn btn-outline-secondary" type="button" id="toggleLoginPassword">
              <i class="fa fa-eye" id="loginToggleIcon"></i>
            </button>
          </div>
          <button type="submit" name="login" class="btn btn-login" id="loginBtn">
            Login
          </button>
        </form>

      <div class="text-center mt-2">
        <a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal" class="text-muted small">
          <i class="fas fa-key me-1"></i>Forgot Password?
        </a>
      </div>

      <div class="register-link mt-3">
        <p>Don't have an account?
          <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal">Register</a>
        </p>
      </div>
    </div>
  </div>

  <!-- Registration Modal -->
  <div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <form method="POST" enctype="multipart/form-data" class="modal-content p-4 rounded-4">
        <div class="modal-header border-0">
          <h5 class="modal-title" id="registerModalLabel">Client Registration</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="alert alert-info mx-3 mt-2 mb-0">
          <i class="fas fa-info-circle me-2"></i>
          <strong>Quick Registration:</strong> Upload a Philippine ID image and click "Scan ID" to auto-fill your details using text recognition. Then just add your contact number, username, and password.
        </div>
        <div class="modal-body">
          <?php if ($register_error): ?>
          <div class="alert alert-danger mb-3 py-2"><?php echo $register_error; ?></div>
          <?php endif; ?>
          <div class="row g-3">
            <input type="hidden" name="scanned_full_name" id="scanned_full_name" value="">
            <input type="hidden" name="scanned_barangay" id="scanned_barangay" value="">
            <div class="col-md-6">
              <label>Full Name <span class="text-success" id="autoFillIndicator1" style="display: none;"><i class="fas fa-magic me-1"></i>Auto-filled</span></label>
              <input type="text" name="full_name" id="full_name" class="form-control">
              <div id="fullNameStatus" class="form-text"></div>
            </div>
            <div class="col-md-6">
              <label>Contact Number <span class="text-danger">*</span></label>
              <input type="text" name="contact_number" class="form-control" id="contact_number" placeholder="09XXXXXXXXX" required>
              <div class="form-text text-danger d-none" id="contactError">Please enter a valid number starting with 09 and exactly 11 digits.</div>
            </div>
            <div class="col-md-6">
              <label>Email Address <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" id="email" placeholder="your.email@example.com" required>
              <div class="form-text text-danger d-none" id="emailError">Please enter a valid email address.</div>
            </div>
            <div class="col-md-6">
              <label for="address">Barangay <span class="text-success" id="autoFillIndicator2" style="display: none;"><i class="fas fa-magic me-1"></i>Auto-filled</span></label>
              <select id="address" name="address" class="form-select">
                <option value="" disabled selected>-- Choose Barangay --</option>
                <option value="Abuanan">Abuanan</option>
                <option value="Alianza">Alianza</option>
                <option value="Atipuluan">Atipuluan</option>
                <option value="Bacong-Montilla">Bacong-Montilla</option>
                <option value="Bagroy">Bagroy</option>
                <option value="Balingasag">Balingasag</option>
                <option value="Binubuhan">Binubuhan</option>
                <option value="Busay">Busay</option>
                <option value="Calumangan">Calumangan</option>
                <option value="Caridad">Caridad</option>
                <option value="Don Jorge L. Araneta">Don Jorge L. Araneta</option>
                <option value="Dulao">Dulao</option>
                <option value="Ilijan">Ilijan</option>
                <option value="Lag-Asan">Lag-Asan</option>
                <option value="Ma-ao">Ma-ao</option>
                <option value="Mailum">Mailum</option>
                <option value="Malingin">Malingin</option>
                <option value="Napoles">Napoles</option>
                <option value="Pacol">Pacol</option>
                <option value="Poblacion">Poblacion</option>
                <option value="Sagasa">Sagasa</option>
                <option value="Tabunan">Tabunan</option>
                <option value="Taloc">Taloc</option>
                <option value="Sampinit">Sampinit</option>
              </select>
            </div>
            <div class="col-md-6">
          <label>Username <span class="text-danger">*</span></label>
          <input type="text" name="username" id="username" class="form-control" placeholder="Choose a username" required>
          <div id="usernameStatus" class="form-text"></div>
        </div>

            <div class="col-md-6">
              <label>Password <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="password" name="password" class="form-control" id="password" placeholder="Create a strong password" required>
                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                  <i class="fa fa-eye" id="toggleIcon"></i>
                </button>
              </div>
              <div class="form-text text-danger d-none" id="passwordError">
                Password must be at least 8 characters, with uppercase and lowercase letters.
              </div>
            </div>
            <div class="col-12">
              <label>Valid ID (image) <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="file" name="valid_id" id="valid_id" accept="image/*" class="form-control" required>
                <button class="btn btn-outline-primary" type="button" id="scanIdBtn">
                  <i class="fa fa-id-card"></i> Scan ID
                </button>
              </div>
              <div class="form-text" id="idScanStatus">
                <i class="fas fa-info-circle me-1"></i>Upload a Philippine ID image and click "Scan ID" to auto-fill your details using text recognition.
              </div>
              <!-- Hidden fields are already defined above -->
                <div id="imagePreview" class="mt-3" style="display: none;">
                <label class="form-label">Image Preview:</label>
                <div class="text-center">
                  <img id="previewImg" src="" alt="ID Preview" class="img-fluid" style="max-width: 300px; max-height: 200px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="submit" name="register" id="registerBtn" class="btn btn-success px-4">Register</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    let idVerified = false;

    // OCR utility functions
    function normalizeText(str) {
      return (str || '')
        .toUpperCase()
        .replace(/BRGY\.?/g, 'BARANGAY')
        .replace(/[^A-Z0-9\s]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
    }

    function tokensPresentCount(haystack, tokens) {
      let count = 0;
      tokens.forEach(t => {
        if (t && haystack.includes(t)) count++;
      });
      return count;
    }

    async function scanValidId() {
      const input = document.getElementById('valid_id');
      const statusEl = document.getElementById('idScanStatus');
      const fullNameField = document.getElementById('full_name');
      const barangayField = document.getElementById('address');

      if (!input.files || !input.files[0]) {
        statusEl.className = 'form-text text-danger';
        statusEl.textContent = 'Please choose an image of your valid ID first.';
        idVerified = false;
        return;
      }

      statusEl.className = 'form-text';
      statusEl.textContent = 'Processing Philippine ID image...';

      try {
        // Use DocTR text recognition for Philippine ID
        const formData = new FormData();
        formData.append('barcode_image', input.files[0]);

        const response = await fetch('barcode_handler.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();

        if (result.success) {
          console.log('Barcode scan result:', result);
          
          // Auto-fill form fields with scanned data
          if (result.name) {
            if (!document.getElementById('full_name').value) {
              document.getElementById('full_name').value = result.name;
            }
            // Store in hidden field for server processing
            document.getElementById('scanned_full_name').value = result.name;
          }
          
          // Auto-fill barangay (address field) 
          if (result.barangay) {
            if (!document.getElementById('address').value) {
              document.getElementById('address').value = result.barangay;
            }
            // Store in hidden field for server processing
            document.getElementById('scanned_barangay').value = result.barangay;
          }
          
          // ID is accepted, show success message
          idVerified = true;
          statusEl.className = 'form-text text-success';
          statusEl.innerHTML = `
            <i class="fas fa-check-circle me-1"></i>
            ✅ ${result.message || 'Barcode scanned successfully! Bago City resident confirmed.'}
            <br><small>Extracted: ${result.name || 'Name'} from ${result.barangay || 'Barangay'}, ${result.city || 'City'}</small>
          `;
          showAutoRegistrationMessage();
        } else {
          idVerified = false;
          statusEl.className = 'form-text text-danger';
          statusEl.textContent = `❌ ${result.error || 'Failed to process Philippine ID. Please try again.'}`;
        }
      } catch (err) {
        console.error('Philippine ID processing error', err);
        idVerified = false;
        statusEl.className = 'form-text text-danger';
        statusEl.textContent = '❌ Failed to process Philippine ID. Please check your connection and try again.';
      }
    }






            function showAutoRegistrationMessage() {
      // Create a temporary success message
      const successDiv = document.createElement('div');
      successDiv.className = 'alert alert-success mt-2';
      successDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>Bago resident verified! Please complete your registration details.';
      
      const statusEl = document.getElementById('idScanStatus');
      statusEl.parentNode.insertBefore(successDiv, statusEl.nextSibling);
      
      // Remove the message after 5 seconds
      setTimeout(() => {
        if (successDiv.parentNode) {
          successDiv.parentNode.removeChild(successDiv);
        }
      }, 5000);
    }
    document.getElementById('scanIdBtn').addEventListener('click', scanValidId);
    document.getElementById('valid_id').addEventListener('change', function () {
      idVerified = false;
      const statusEl = document.getElementById('idScanStatus');
      const previewDiv = document.getElementById('imagePreview');
      const previewImg = document.getElementById('previewImg');
      
      statusEl.className = 'form-text';
      statusEl.textContent = 'Ready to scan.';
      
      // Show/hide image preview
      if (this.files && this.files[0]) {
        const file = this.files[0];
        const reader = new FileReader();
        
        reader.onload = function(e) {
          previewImg.src = e.target.result;
          previewDiv.style.display = 'block';
        };
        
        reader.readAsDataURL(file);
      } else {
        previewDiv.style.display = 'none';
      }
    });
    // Show/hide password logic
    document.getElementById('togglePassword').addEventListener('click', () => {
      const input = document.getElementById('password');
      const icon = document.getElementById('toggleIcon');
      input.type = input.type === 'password' ? 'text' : 'password';
      icon.className = input.type === 'password' ? 'fa fa-eye' : 'fa fa-eye-slash';
    });

    document.getElementById('toggleLoginPassword').addEventListener('click', () => {
      const input = document.getElementById('loginPassword');
      const icon = document.getElementById('loginToggleIcon');
      input.type = input.type === 'password' ? 'text' : 'password';
      icon.className = input.type === 'password' ? 'fa fa-eye' : 'fa fa-eye-slash';
    });

    // Real-time validation
    document.getElementById('contact_number').addEventListener('input', function () {
      const error = document.getElementById('contactError');
      const isValid = /^09\d{9}$/.test(this.value);
      error.classList.toggle('d-none', isValid);
    });

    document.getElementById('password').addEventListener('input', function () {
      const error = document.getElementById('passwordError');
      error.classList.toggle('d-none', /^(?=.*[a-z])(?=.*[A-Z]).{8,}$/.test(this.value));
    });

    // Full name validation with duplicate check
    let fullNameTimeout;
    document.getElementById('full_name').addEventListener('input', function () {
      const fullName = this.value.trim();
      const statusDiv = document.getElementById('fullNameStatus');
      
      // Clear previous timeout
      clearTimeout(fullNameTimeout);
      
      if (fullName.length < 2) {
        statusDiv.textContent = "Full name must be at least 2 characters.";
        statusDiv.className = "form-text text-danger";
        return;
      }
      
      if (!/^[a-zA-ZÑñ\s\.\-']+$/.test(fullName)) {
        statusDiv.textContent = "Full name can only contain letters, spaces, hyphens, apostrophes and periods.";
        statusDiv.className = "form-text text-danger";
        return;
      }
      
      // Debounce the AJAX call
      fullNameTimeout = setTimeout(() => {
        fetch("check_fullname_register.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "full_name=" + encodeURIComponent(fullName)
        })
        .then(response => response.json())
        .then(data => {
          if (data.available) {
            statusDiv.textContent = "✅ Full name available.";
            statusDiv.className = "form-text text-success";
          } else {
            statusDiv.textContent = "❌ " + data.message;
            statusDiv.className = "form-text text-danger";
          }
        })
        .catch(error => {
          statusDiv.textContent = "Error checking full name.";
          statusDiv.className = "form-text text-warning";
        });
      }, 500);
    });

    // Prevent form submission if invalid
    document.querySelector('#registerModal form').addEventListener('submit', function (e) {
      const fullName = document.getElementById('full_name').value.trim();
      const username = document.getElementById('username').value.trim();
      const email = document.getElementById('email').value.trim();
      const contact = document.getElementById('contact_number').value;
      const password = document.getElementById('password').value;
      
      // Validation checks
      const fullNameOk = fullName.length >= 2 && /^[a-zA-ZÑñ\s\.\-']+$/.test(fullName);
      const usernameOk = username.length >= 3 && /^[a-zA-Z0-9_]+$/.test(username);
      const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
      const contactOk = /^09\d{9}$/.test(contact);
      const passwordOk = /^(?=.*[a-z])(?=.*[A-Z]).{8,}$/.test(password);
      
      // Check if validation status shows success
      const fullNameStatus = document.getElementById('fullNameStatus');
      const usernameStatus = document.getElementById('usernameStatus');
      const fullNameAvailable = fullNameStatus.classList.contains('text-success');
      const usernameAvailable = usernameStatus.classList.contains('text-success');
      
      // If ID is verified, only require basic validation
      const formOk = idVerified ? (fullNameOk && usernameOk && emailOk && contactOk && passwordOk && 
                     fullNameAvailable && usernameAvailable) : false;
      
      if (!idVerified) {
        const statusEl = document.getElementById('idScanStatus');
        statusEl.className = 'form-text text-danger';
        statusEl.textContent = 'Please scan a clear image of your valid ID first to verify Bago residency.';
        e.preventDefault();
        alert('Please scan your valid ID first to verify Bago residency.');
        return;
      }
      
      if (!fullNameOk) {
        fullNameStatus.textContent = "Please enter a valid full name.";
        fullNameStatus.className = "form-text text-danger";
      }
      if (!usernameOk) {
        usernameStatus.textContent = "Please enter a valid username.";
        usernameStatus.className = "form-text text-danger";
      }
      if (!contactOk) {
        const contactError = document.getElementById('contactError');
        contactError.classList.remove('d-none');
      }
      if (!passwordOk) {
        const passwordError = document.getElementById('passwordError');
        passwordError.classList.remove('d-none');
      }
      
      if (!formOk) {
        e.preventDefault();
        alert('Please fix all validation errors before submitting.');
      }
    });
    // Auto-open register modal if server-side registration error
    <?php if ($register_error): ?>
    document.addEventListener('DOMContentLoaded', function () {
      const regModal = new bootstrap.Modal(document.getElementById('registerModal'));
      regModal.show();
    });
    <?php endif; ?>
    // Username validation with duplicate check
    let usernameTimeout;
    document.getElementById("username").addEventListener("input", function () {
      const username = this.value.trim();
      const statusDiv = document.getElementById("usernameStatus");
      
      // Clear previous timeout
      clearTimeout(usernameTimeout);
      
      if (username.length < 3) {
        statusDiv.textContent = "Username must be at least 3 characters.";
        statusDiv.className = "form-text text-danger";
        return;
      }
      
      if (!/^[a-zA-Z0-9_]+$/.test(username)) {
        statusDiv.textContent = "Username can only contain letters, numbers, and underscores.";
        statusDiv.className = "form-text text-danger";
        return;
      }
      
      // Debounce the AJAX call
      usernameTimeout = setTimeout(() => {
        fetch("check_username_register.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "username=" + encodeURIComponent(username)
        })
        .then(response => response.json())
        .then(data => {
          if (data.available) {
            statusDiv.textContent = "✅ Username available.";
            statusDiv.className = "form-text text-success";
          } else {
            statusDiv.textContent = "❌ " + data.message;
            statusDiv.className = "form-text text-danger";
          }
        })
        .catch(error => {
          statusDiv.textContent = "Error checking username.";
          statusDiv.className = "form-text text-warning";
        });
      }, 500);
    });

    // Email validation
    document.getElementById('email').addEventListener('input', function() {
      const email = this.value.trim();
      const emailErrorDiv = document.getElementById('emailError');
      
      if (email.length === 0) {
        emailErrorDiv.classList.add('d-none');
        return;
      }
      
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        emailErrorDiv.textContent = 'Please enter a valid email address.';
        emailErrorDiv.classList.remove('d-none');
      } else {
        emailErrorDiv.classList.add('d-none');
      }
    });
  
  </script>

  <!-- Forgot Password Modal -->
  <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <form method="POST" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="forgotPasswordModalLabel">
            <i class="fas fa-key me-2"></i>Reset Password
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <?php if ($reset_error): ?>
          <div class="alert alert-danger py-2">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $reset_error; ?>
          </div>
          <?php endif; ?>
          <?php if ($reset_success): ?>
          <div class="alert alert-success py-2">
            <i class="fas fa-check-circle me-2"></i><?php echo $reset_success; ?>
          </div>
          <?php endif; ?>
          
          <div class="alert alert-info py-2">
            <i class="fas fa-info-circle me-2"></i>
            <small><strong>How it works:</strong></small>
            <ol style="margin: 10px 0 0 20px; font-size: 13px;">
              <li>Enter your registered email address</li>
              <li>We'll send you a password reset link</li>
              <li>Click the link to set your new password</li>
              <li>The link expires in 1 hour for security</li>
            </ol>
          </div>

          <div class="mb-3">
            <label for="reset_email" class="form-label">
              <i class="fas fa-envelope"></i> Email Address <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa fa-envelope"></i></span>
              <input 
                type="email" 
                class="form-control" 
                id="reset_email" 
                name="reset_email" 
                placeholder="your.email@example.com" 
                autocomplete="email"
                required
              >
            </div>
            <small class="form-text text-muted">Enter the email address you used during registration</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="request_reset" class="btn btn-primary">
            <i class="fas fa-paper-plane me-2"></i>Send Reset Link
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Auto-show forgot password modal if there's an error or success
    <?php if ($reset_error || $reset_success): ?>
    document.addEventListener('DOMContentLoaded', function() {
      var forgotPasswordModal = new bootstrap.Modal(document.getElementById('forgotPasswordModal'));
      forgotPasswordModal.show();
    });
    <?php endif; ?>
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>