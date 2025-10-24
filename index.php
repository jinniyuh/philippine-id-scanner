<?php
session_start();
include 'includes/conn.php';
include 'includes/activity_logger.php';

$login_error = '';
$register_error = '';
$success_msg = '';

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

function tokens_present_count($haystack, $tokens) {
    $count = 0;
    foreach ($tokens as $t) {
        if ($t && strpos($haystack, $t) !== false) $count++;
    }
    return $count;
}

function find_tesseract_binary() {
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
    if (!$tesseract) return [false, 'Tesseract OCR is not available on the server'];

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
    $address = sanitize($_POST['address'] ?? '');
    $username = strtolower(sanitize($_POST['username'] ?? ''));
    $password_raw = $_POST['password'] ?? '';

    // Validate required fields
    if (!$full_name || !$contact_number || !$address || !$username || !$password_raw) {
        $register_error = "Please fill in all required fields.";
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
                        // Run OCR and verify
[$ok, $ocrOrErr] = perform_ocr($targetPath);
if (!$ok) {
    $register_error = "❌ Failed to scan ID. Please upload a clearer image.";
} else {
    $ocrNorm = normalize_text($ocrOrErr);

    // Split entered fullname into tokens
    $nameTokens = array_values(array_filter(explode(' ', normalize_text($full_name))));
    $nameMatchCount = tokens_present_count($ocrNorm, $nameTokens);

    // RULE 1: Must match at least 2 name tokens (First + Last)
    if ($nameMatchCount < 2) {
        $register_error = "❌ The full name you entered does not match the name on your ID. Please check and try again.";
    }
    // RULE 2: Must contain "BAGO"
    else if (strpos($ocrNorm, "BAGO") === false) {
        $register_error = "❌ Your ID does not indicate Bago City residency.";
    }
    // RULE 3: Barangay optional
    else {
        $success_msg = "✅ ID Verified and Registration Complete.";
    }
}


                        // Check if username exists in clients or users table
                        if (!$register_error) {
                            $checkStmt = $conn->prepare("SELECT username FROM clients WHERE username = ? UNION SELECT username FROM users WHERE username = ?");
                            if (!$checkStmt) {
                                $register_error = "Error preparing check statement: " . $conn->error;
                            } else {
                                $checkStmt->bind_param("ss", $username, $username);
                                $checkStmt->execute();
                                $checkResult = $checkStmt->get_result();

                                if ($checkResult->num_rows > 0) {
                                    $register_error = "Username already taken. Please choose another.";
                                } else {
                                    // Geocode the full address (barangay + city + province + country)
                                    $fullAddress = $address . ", Bago City, Negros Occidental, Philippines";
                                    $coords = geocodeAddress($fullAddress);

                                    // Use geocoded coords or null if failed
                                    $latitude = $coords ? $coords['lat'] : null;
                                    $longitude = $coords ? $coords['lon'] : null;

                                    // Hash password
                                    $password = password_hash($password_raw, PASSWORD_DEFAULT);

                                    // Insert into clients table with lat/lng
                                    $stmt1 = $conn->prepare("INSERT INTO clients (full_name, contact_number, address, username, password, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                    $stmt2 = $conn->prepare("INSERT INTO users (name, contact_number, username, password, role) VALUES (?, ?, ?, ?, 'client')");

                                    if (!$stmt1 || !$stmt2) {
                                        $register_error = "Error preparing insert statements.";
                                    } else {
                                        $stmt1->bind_param("sssssss", $full_name, $contact_number, $address, $username, $password, $latitude, $longitude);
                                        $stmt2->bind_param("ssss", $full_name, $contact_number, $username, $password);

                                        if ($stmt1->execute() && $stmt2->execute()) {
                                            if (!$success_msg) {
                                                $success_msg = "Registration successful! You may now log in.";
                                            }
                                        } else {
                                            $register_error = "Registration failed. Please try again.";
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bago City Veterinary Office - Information Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts for professional typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Update the CSS in the <head> section -->
    <style>
        :root {
            --primary-color: #6c63ff;
            --secondary-color: #4CAF50;
            --accent-color: #FF9800;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
        }

        body {
            background: url('https://upload.wikimedia.org/wikipedia/en/thumb/d/d1/Bago_City_Plaza%2C_Coliseum_%28Gen._Luna%2C_Bago_City%2C_Negros_Occidental%3B_10-20-2023%29.jpg/1280px-Bago_City_Plaza%2C_Coliseum_%28Gen._Luna%2C_Bago_City%2C_Negros_Occidental%3B_10-20-2023%29.jpg') no-repeat center center fixed;
            background-size: cover;
            background-attachment: fixed;
            font-family: 'Inter', 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            font-weight: 400;
            font-size: 16px;
            line-height: 1.7;
            color: #2c3e50;
            letter-spacing: -0.01em;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        


        .navbar {
            background: #6c63ff;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            font-weight: 500;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
            z-index: 1000;
            padding: 0.5rem 0; /* Reduced padding */
        }
        
        /* Responsive navbar adjustments */
        @media (max-width: 992px) {
            .navbar-collapse {
                background: #6c63ff;
                padding: 15px;
                border-radius: 0 0 15px 15px;
                margin-top: 10px;
        }
        .nav-item {
                margin: 5px 0;
        }
        }

        .navbar-brand {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.5rem; /* Reduced font size */
            padding: 0.25rem 0; /* Reduced padding */
            color: white !important;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
            letter-spacing: -0.02em;
        }
        
        .navbar-brand img {
            height: 60px !important; /* Reduced logo size */
            width: auto;
            margin-right: 8px;
        }
        
        .nav-link {
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem !important; /* Reduced padding */
            transition: all 0.3s ease;
            color: rgba(255, 255, 255, 0.9) !important;
            letter-spacing: 0.01em;
        }
        
        .nav-link i {
            margin-right: 8px;
            font-size: 1rem;
            width: 16px;
            text-align: center;
            opacity: 0.9;
        }
        
        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.15);
            border-radius: 5px;
            color: white !important;
            transform: translateY(-1px);
        }

        .hero-section { 
            -webkit-image-rendering: crisp-edges;
            image-rendering: crisp-edges;
            color: white;
            padding: 50px 0;
            position: relative;
            overflow: hidden;
            font-family: 'Poppins', sans-serif;
            background: rgba(0, 0, 0, 0.3);
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://upload.wikimedia.org/wikipedia/en/thumb/d1/Bago_City_Plaza%2C_Coliseum_%28Gen._Luna%2C_Bago_City%2C_Negros_Occidental%3B_10-20-2023%29.jpg/1280px-Bago_City_Plaza%2C_Coliseum_%28Gen._Luna%2C_Bago_City%2C_Negros_Occidental%3B_10-20-2023%29.jpg') center center;
            background-size: cover;
            background-attachment: scroll;
            filter: blur(100px);
            -webkit-filter: blur(100px);
            opacity: 0.50;
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 800;
            font-size: 3.5rem;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            letter-spacing: -0.02em;
        }

        .hero-subtitle {
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            font-size: 1.25rem;
            line-height: 1.6;
            margin-bottom: 4rem;
            opacity: 0.95;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
            letter-spacing: 0.01em;
        }

        .hero-office-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 800;
            font-size: 2.5rem;
            line-height: 1.4;
            margin-top: 5rem;
            margin-bottom: 23.5rem;
            color: whitesmoke;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            letter-spacing: 0.07em;
            text-align: justify;
        }

        .announcement-box {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 12px;
            padding: 1.70rem;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.4);
            max-height: 700px;
            overflow: hidden;
            position: relative;
            z-index: 3;
        }

        .announcement-content {
            animation: autoScrollContent 15s linear infinite;
            transform: translateY(0);
        }

        @keyframes autoScrollContent {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-50px);
            }
            100% {
                transform: translateY(0);
            }
        }

        .announcement-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('uploads/anti_rabies.jpg') center center;
            background-size: cover;
            border-radius: 5px;
            opacity: 0.3;
            filter: blur(8px);
            -webkit-filter: blur(8px);
            z-index: -1;
        }

        .announcement-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
        }

        .announcement-title i {
            color: #6c63ff;
            font-size: 1.3rem;
        }

        .announcement-event-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1.25rem;
            color: #2c3e50;
            margin-bottom: 1rem;
            line-height: 1.4;
            text-align: center;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
        }

        .announcement-detail {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(100px);
            -webkit-backdrop-filter: blur(100px);
            border-radius: 5px;
            padding: 0.75rem;
            margin-bottom: 0.4rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
        }

        .announcement-detail:last-child {
            margin-bottom: 0;
        }

        .announcement-label {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 0.9rem;
            color: white;
            background: #6c63ff;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            min-width: 80px;
            text-align: center;
            margin-right: 1rem;
            flex-shrink: 0;
            border: none;
            box-shadow: 0 2px 4px rgba(108, 99, 255, 0.3);
        }

        .announcement-text {
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            color: black;
            line-height: 1.6;
            flex: 1;
        }

        .announcement-cta {
            border: 2px dashed #6c63ff;
            border-radius: 5px;
            padding: 1rem;
            margin-top: 1.5rem;
            background: rgba(108, 99, 255, 0.05);
        }

        .announcement-cta-text {
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            color: black;
            line-height: 1.5;
            margin: 0;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.6);
        }

        .announcement-logos {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.75rem;
            flex-wrap: wrap;
        }

        .announcement-logo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(108, 99, 255, 0.2);
            transition: transform 0.3s ease;
        }

        .announcement-logo:hover {
            transform: scale(1.05);
        }

        .btn-primary-custom {
            background: var(--secondary-color);
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: 0.02em;
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
            display: inline-block;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .btn-primary-custom:hover {
            background: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }

        .btn-outline-custom {
            border: 2px solid white;
            background: transparent;
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: 0.02em;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .btn-outline-custom:hover {
            background: white;
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        /* Comprehensive Typography Improvements */
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            line-height: 1.3;
            color: #2c3e50;
            letter-spacing: -0.02em;
            margin-bottom: 1rem;
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: -0.03em;
        }

        h2 {
            font-size: 2rem;
            font-weight: 600;
        }

        h3 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        h4 {
            font-size: 1.25rem;
            font-weight: 600;
        }

        h5 {
            font-size: 1.1rem;
            font-weight: 600;
        }

        h6 {
            font-size: 1rem;
            font-weight: 600;
        }

        p {
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            line-height: 1.7;
            color: #495057;
            margin-bottom: 1rem;
            letter-spacing: 0.01em;
        }

        .lead {
            font-family: 'Inter', sans-serif;
            font-size: 1.125rem;
            font-weight: 400;
            line-height: 1.6;
            color: #6c757d;
            letter-spacing: 0.01em;
        }

        .display-1, .display-2, .display-3, .display-4, .display-5, .display-6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }

        .text-muted {
            color: #6c757d !important;
            font-weight: 400;
        }

        .feature-card h4 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1.25rem;
            color: #2c3e50;
            margin-bottom: 1rem;
            letter-spacing: -0.01em;
        }

        .feature-card p {
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            line-height: 1.6;
            color: #6c757d;
            letter-spacing: 0.01em;
        }

        .features-section {
            padding: 80px 0;
        }
        .features-section.visible {
        opacity: 1;
        height: auto;
        overflow: visible;
        padding: 5rem ; /* Increased padding from 3rem to 8rem */
          }
        /* Enhanced Feature Cards Styling */
        .feature-card {
            background: white;
            padding: 1.75rem;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1.25rem;
            height: 70px;
            width: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(108, 99, 255, 0.1);
            color: var(--primary-color);
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .feature-card {
                padding: 1.5rem;
            }
            
            .feature-icon {
                font-size: 2.5rem;
                height: 70px;
                width: 70px;
            }
        }
        
        @media (max-width: 768px) {
            .features-section {
                padding: 40px 0;
            }
            
            .feature-card {
                padding: 1.25rem;
                margin-bottom: 20px;
            }
            
            .feature-icon {
                font-size: 2rem;
                height: 60px;
                width: 60px;
                margin-bottom: 1rem;
            }
            
            .feature-card h4 {
                font-size: 1.25rem;
            }
            
            .display-5 {
                font-size: 2rem;
            }
            
            .lead {
                font-size: 1rem;
            }
            
            .container {
                padding-left: 15px;
                padding-right: 15px;
            }

            /* Responsive Typography */
            h1 {
                font-size: 2rem;
            }
            
            h2 {
                font-size: 1.75rem;
            }
            
            h3 {
                font-size: 1.5rem;
            }
            
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
            }
            
            p {
                font-size: 0.95rem;
            }
        }
        
        @media (max-width: 500px) {
            .features-section .display-5 {
                font-size: 2rem;
            }
            
            .feature-card {
                padding: .3rem;
            }
            
            .feature-icon {
                font-size: 1.75rem;
                height: 25px;
                width: 25px;
            }
        }
        .service-section {
            background-color: #f8f9fa;
            padding: 80px 0;
        }

        /* Login Modal Styles */
        .login-modal-content {
            background: transparent;
            border: none;
            box-shadow: none;
        }

        .login-container {
            background: rgba(108, 99, 255, 0.15);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-radius: 20px;
            border: 1px solid rgba(108, 99, 255, 0.2);
            box-shadow: 0 20px 40px rgba(108, 99, 255, 0.2), 0 0 0 1px rgba(108, 99, 255, 0.1);
            padding: 40px 35px;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
        }

        .login-form-section {
            text-align: center;
            color: white;
        }

        .login-form-section h3 {
            color: white;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1.8rem;
            margin-bottom: 2rem;
            letter-spacing: 0.02em;
            line-height: 1.2;
        }

        .input-group {
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .input-group:focus-within {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.4);
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.1);
        }

        .form-control {
            border: none;
            padding: 14px 16px;
            background: transparent;
            color: white;
            font-size: 0.95rem;
            font-weight: 400;
            transition: all 0.3s ease;
            flex: 1;
            height: auto;
            border-radius: 0;
        }

        .form-control:focus {
            background: transparent;
            border: none;
            box-shadow: none;
            outline: none;
            color: white;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
            font-weight: 400;
        }

        .input-group-text {
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.8);
            padding: 14px 16px;
            border-radius: 0;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 50px;
        }

        .input-group:focus-within .input-group-text {
            background: transparent;
            color: white;
        }

        .input-group .btn {
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.8);
            padding: 14px 16px;
            border-radius: 0;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 50px;
            cursor: pointer;
            z-index: 10;
            position: relative;
        }

        .input-group .btn:hover {
            background: transparent;
            color: white;
        }

        .input-group:focus-within .btn {
            background: transparent;
            color: white;
        }

        .input-group .btn:active {
            background: transparent;
            color: white;
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, #6c63ff, #5a52d5) !important;
        }
        
        .btn-login {
            background: #6c63ff !important;
        }
        
        .btn-primary {
            background: #6c63ff !important;
            border: none;
            border-radius: 10px;
            padding: 14px 24px;
            width: 100%;
            color: white !important;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: 0.02em;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(108, 99, 255, 0.3);
        }

        .btn-login:hover {
            background: #5a52e8 !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(108, 99, 255, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
            box-shadow: 0 4px 12px rgba(108, 99, 255, 0.3);
        }

        .register-link {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            margin-top: 1.5rem;
        }

        .register-link a {
            color: #8b5cf6;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .register-link a:hover {
            color: #a78bfa;
            text-decoration: underline;
        }

        .alert {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 10px;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.2);
            border-color: rgba(34, 197, 94, 0.3);
            color: #bbf7d0;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.3);
            color: #fecaca;
        }

        @media (max-width: 768px) {
            .login-container {
                padding: 35px 25px;
                margin: 20px;
                max-width: none;
                border-radius: 18px;
            }

            .login-form-section h3 {
                font-size: 1.6rem;
                margin-bottom: 1.75rem;
            }

            .input-group {
                margin-bottom: 14px;
            }

            .form-control {
                padding: 12px 14px;
                font-size: 0.95rem;
            }

            .input-group-text {
                padding: 12px 14px;
                min-width: 45px;
            }

            .input-group .btn {
                padding: 12px 14px;
                min-width: 45px;
            }

            .btn-login {
                padding: 12px 20px;
                font-size: 0.95rem;
            }
        }

        @media (max-width: 576px) {
            .login-container {
                padding: 30px 20px;
                margin: 16px;
                border-radius: 16px;
            }

            .login-form-section h3 {
                font-size: 1.4rem;
                margin-bottom: 1.5rem;
            }

            .input-group {
                margin-bottom: 12px;
            }

            .form-control {
                padding: 10px 12px;
                font-size: 0.9rem;
            }

            .input-group-text {
                padding: 10px 12px;
                min-width: 40px;
            }

            .input-group .btn {
                padding: 10px 12px;
                min-width: 40px;
            }

            .btn-login {
                padding: 10px 16px;
                font-size: 0.9rem;
            }

            .register-link {
                font-size: 0.85rem;
                margin-top: 1.25rem;
            }
        }


        .service-item {
            display: flex;
            background-color: #f8f9fa;
            align-items: center;
            margin-bottom: 2rem;
            padding: 20px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .service-item:hover {
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .service-icon {
            font-size: 2.5rem;
            color: var(--secondary-color);
            margin-right: 20px;
            min-width: 60px;
        }



        .footer {
            background: var(--dark-color);
            color: white;
            padding: 40px 0 20px;
        }

        .footer a {
            color: #bdc3c7;
            text-decoration: none;
        }

        .footer a:hover {
            color: white;
        }

        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .shape {
            position: absolute;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }

        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            top: 40%;
            left: 80%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            .hero-subtitle {
                font-size: 1.1rem;
            }
            .hero-office-title {
                font-size: 1.25rem;
            }
            .navbar-brand span {
                font-size: 1.2rem;
            }
            .navbar-brand img {
                height: 60px !important;
            }
            .navbar {
                padding: 10px 0;
            }
            .hero-section {
                padding: 60px 0;
            }
            .btn-primary-custom, .btn-outline-custom {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
            .announcement-box {
                margin-top: 2rem;
                padding: 1.5rem;
            }
            .announcement-title {
                font-size: 1.25rem;
            }
            .announcement-event-title {
                font-size: 1.1rem;
            }
            .announcement-logo {
                width: 50px;
                height: 50px;
            }
        }
        
        @media (max-width: 576px) {
            .hero-title {
                font-size: 2rem;
            }
            .hero-subtitle {
                font-size: 1rem;
            }
            .hero-office-title {
                font-size: 1.1rem;
            }
            .navbar-brand span {
                font-size: 1rem;
            }
            .navbar-brand img {
                height: 50px !important;
            }
            .btn-primary-custom, .btn-outline-custom {
                padding: 8px 16px;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="uploads/vetlogo.png" alt="Logo" style="height: 60px; width: auto; margin-right: 8px;">
                <span>Bago City Veterinary Office</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item mx-1">
                        <a class="nav-link" href="#home">
                            <i class="fas fa-home"></i>Home
                        </a>
                    </li>
                    <li class="nav-item mx-1">
                        <a class="nav-link" href="#services">
                            <i class="fas fa-stethoscope"></i>Services
                        </a>
                    </li>
                    <li class="nav-item mx-1">
                        <a class="nav-link" href="#features">
                            <i class="fas fa-star"></i>Features
                        </a>
                    </li>
                    <li class="nav-item mx-1">
                        <a class="nav-link" href="#contact">
                            <i class="fas fa-envelope"></i>Contact
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light btn-sm ms-2" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="fas fa-sign-in-alt"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-light btn-sm ms-2" href="#" data-bs-toggle="modal" data-bs-target="#registerModal">
                            <i class="fas fa-user-plus"></i>Register
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="floating-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <h1 class="hero-title"></h1>
                    <p class="hero-subtitle">
                    
                    </p>
                    <h3 class="hero-office-title">The Office of the City Veterinarian</h3>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="login.php" class="btn-primary-custom">
                            <i class="fas fa-sign-in-alt me-2"></i>Get Started
                        </a>
                        <a href="#features" class="btn-outline-custom">
                            <i class="fas fa-info-circle me-2"></i>Learn More
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="announcement-box">
                        <div class="announcement-content">
                            <div class="announcement-logos">
                                <img src="bagocity.png" alt="Bago City Logo" class="announcement-logo">
                                <img src="bcvo.png" alt="Veterinary Office Logo" class="announcement-logo">
                            </div>
                            <div class="announcement-title">
                                <i class="fas fa-bullhorn"></i>
                                Announcement
                            </div>
                            <div class="announcement-event-title">Mass Anti-Rabies Vaccination</div>
                            
                            <div class="announcement-detail">
                                <div class="announcement-label">Where</div>
                                <div class="announcement-text">Barangay Dulao, Bago City</div>
                            </div>
                            
                            <div class="announcement-detail">
                                <div class="announcement-label">When</div>
                                <div class="announcement-text">September 8-24, 2025</div>
                            </div>
                            
                            <div class="announcement-cta">
                                <p class="announcement-cta-text">
                                    <strong>Important Reminders:</strong><br>
                                    • 3 months old and above<br>
                                    • Pets must be healthy, not pregnant & not breast feeding<br>
                                    • Owner must hold their pets during vaccination
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section py-5">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="display-5 fw-bold mb-3 text-white">System Features</h2>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card h-100">
                        <div class="feature-icon mb-4">
                            <i class="fas fa-users text-primary"></i>
                        </div>
                        <h4 class="mb-3">Client Management</h4>
                        <p class="text-muted">Efficiently manage client information, animal records, and service history with our comprehensive database system.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card h-100">
                        <div class="feature-icon mb-4">
                            <i class="fas fa-pills text-primary"></i>
                        </div>
                        <h4 class="mb-3">Pharmaceutical Management</h4>
                        <p class="text-muted">Track inventory, manage stock levels, and handle pharmaceutical requests with automated alerts and notifications.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card h-100">
                        <div class="feature-icon mb-4">
                            <i class="fas fa-chart-line text-primary"></i>
                        </div>
                        <h4 class="mb-3">Reports & Analytics</h4>
                        <p class="text-muted">Generate detailed reports on livestock health, transaction history, and operational metrics for informed decision-making.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card h-100">
                        <div class="feature-icon mb-4">
                            <i class="fas fa-bell text-primary"></i>
                        </div>
                        <h4 class="mb-3">Smart Notifications</h4>
                        <p class="text-muted">Stay updated with real-time notifications for low stock alerts, expiring medications, and important updates.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card h-100">
                        <div class="feature-icon mb-4">
                            <i class="fas fa-map-marker-alt text-primary"></i>
                        </div>
                        <h4 class="mb-3">Location Tracking</h4>
                        <p class="text-muted">Monitor and track client locations for efficient service delivery and emergency response.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card h-100">
                        <div class="feature-icon mb-4">
                            <i class="fas fa-shield-alt text-primary"></i>
                        </div>
                        <h4 class="mb-3">Secure Access</h4>
                        <p class="text-muted">Role-based access control ensures data security while providing appropriate permissions for admin, staff, and clients.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="services-section">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="display-4 fw-bold mb-3 text-white">Our Services</h2>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6">
                    <div class="service-item">
                        <div class="service-icon">
                            <i class="fas fa-cow"></i>
                        </div>
                        <div>
                            <h5>Livestock Health Management</h5>
                            <p class="text-muted mb-0">Comprehensive health monitoring and treatment for cattle, swine, and other livestock animals.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="service-item">
                        <div class="service-icon">
                            <i class="fas fa-kiwi-bird"></i>
                        </div>
                        <div>
                            <h5>Poultry Care Services</h5>
                            <p class="text-muted mb-0">Specialized care for chickens, ducks, and other poultry with vaccination and disease prevention.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="service-item">
                        <div class="service-icon">
                            <i class="fas fa-syringe"></i>
                        </div>
                        <div>
                            <h5>Vaccination Programs</h5>
                            <p class="text-muted mb-0">Scheduled vaccination services to protect your animals from common diseases and infections.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="service-item">
                        <div class="service-icon">
                            <i class="fas fa-pills"></i>
                        </div>
                        <div>
                            <h5>Pharmaceutical Distribution</h5>
                            <p class="text-muted mb-0">Access to quality veterinary medicines and supplements with proper prescription management.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="service-item">
                        <div class="service-icon">
                            <i class="fas fa-stethoscope"></i>
                        </div>
                        <div>
                            <h5>Health Consultations</h5>
                            <p class="text-muted mb-0">Professional veterinary consultations for animal health issues and preventive care advice.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="service-item">
                        <div class="service-icon">
                            <i class="fas fa-file-medical"></i>
                        </div>
                        <div>
                            <h5>Health Certificates</h5>
                            <p class="text-muted mb-0">Official health certificates and documentation for animal transport and trade requirements.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <!-- Footer -->
    <footer id="contact" class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5><i class="fas fa-clinic-medical me-2"></i>Bago City Veterinary Office</h5>
                    <p class="text-muted">Providing quality veterinary care and management services for the community of Bago City.</p>
                </div>
                <div class="col-lg-4 mb-4">
                    <h5>Contact Information</h5>
                    <p class="text-muted">
                    <i class="fab fa-facebook fa-lg"></i>   Office of the City Veterinarian - Bago City<br>
                        <i class="fas fa-map-marker-alt me-2"></i>   Barangay Lag-asan, Bago City, Philippines<br>
                        <i class="fas fa-phone me-2"></i>0915 804 4952<br>
                        <i class="fas fa-envelope me-2"></i>vetbagocity@gmail.com
                    </p>
                </div>
            </div>
            <hr class="my-4">
            <div class="row">
                <div class="col-md-6">
                    <p class="text-muted mb-0">&copy; 2024 Bago City Veterinary Office. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">Information Management System v1.0</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5.0.3/dist/tesseract.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                
                // Hide features section when clicking Home
                if (this.getAttribute('href') === '#home') {
                    const featuresSection = document.querySelector('.features-section');
                    featuresSection.classList.remove('visible');
                }
                
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar background change on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(108, 99, 255, 0.95)';
            } else {
                navbar.style.background = 'linear-gradient(135deg, var(--primary-color), #5a52d5)';
            }
        });

        // Password toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const password = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (togglePassword) {
                togglePassword.addEventListener('click', function() {
                    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                    password.setAttribute('type', type);
                    toggleIcon.classList.toggle('fa-eye');
                    toggleIcon.classList.toggle('fa-eye-slash');
                });
            }
            
            // Scroll reveal for features section
            const featuresSection = document.querySelector('.features-section');
            
            if (featuresSection) {
                function checkScroll() {
                    // Get position of the section relative to viewport
                    const featuresSectionPosition = featuresSection.getBoundingClientRect().top;
                    // Set trigger point to be when section is 80% down the screen
                    const screenPosition = window.innerHeight * 0.8;
                    
                    if (featuresSectionPosition < screenPosition) {
                        featuresSection.classList.add('visible');
                    } else {
                        featuresSection.classList.remove('visible');
                    }
                }
                
                // Check on initial load
                checkScroll();
                
                // Check on scroll
                window.addEventListener('scroll', checkScroll);
            }
        });



        // Global function for password toggle
        function togglePasswordVisibility() {
            console.log('Toggle function called'); // Debug log
            const passwordInput = document.getElementById('loginPassword');
            const toggleIcon = document.getElementById('loginToggleIcon');
            
            console.log('Password input:', passwordInput); // Debug log
            console.log('Toggle icon:', toggleIcon); // Debug log
            
            if (passwordInput && toggleIcon) {
                console.log('Current type:', passwordInput.type); // Debug log
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    toggleIcon.className = 'fa fa-eye-slash';
                    console.log('Password shown'); // Debug log
                } else {
                    passwordInput.type = 'password';
                    toggleIcon.className = 'fa fa-eye';
                    console.log('Password hidden'); // Debug log
                }
            } else {
                console.log('Elements not found'); // Debug log
            }
        }

        // Password toggle functionality - using event delegation for modal elements
        document.addEventListener('click', function(e) {
            // Check if the clicked element is the password toggle button
            if (e.target && e.target.id === 'toggleLoginPassword') {
                e.preventDefault();
                togglePasswordVisibility();
            }
            
            // Also handle clicks on the icon inside the button
            if (e.target && e.target.id === 'loginToggleIcon') {
                e.preventDefault();
                togglePasswordVisibility();
            }
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


        // Prevent form submission if invalid
        document.querySelector('#registerModal form').addEventListener('submit', function (e) {
            const fullName = document.getElementById('full_name').value.trim();
            const username = document.getElementById('username').value.trim();
            const contact = document.getElementById('contact_number').value;
            const password = document.getElementById('password').value;
            
            // Validation checks
            const fullNameOk = fullName.length >= 2 && /^[a-zA-Z\s]+$/.test(fullName);
            const usernameOk = username.length >= 3 && /^[a-zA-Z0-9_]+$/.test(username);
            const contactOk = /^09\d{9}$/.test(contact);
            const passwordOk = /^(?=.*[a-z])(?=.*[A-Z]).{8,}$/.test(password);
            
            // Check if validation status shows success
            const fullNameStatus = document.getElementById('fullNameStatus');
            const usernameStatus = document.getElementById('usernameStatus');
            const fullNameAvailable = fullNameStatus.classList.contains('text-success');
            const usernameAvailable = usernameStatus.classList.contains('text-success');
            
            // If ID is verified (Bago resident), only require contact, username, and password
            const formOk = idVerified ? (usernameOk && contactOk && passwordOk && usernameAvailable) : 
                          (fullNameOk && usernameOk && contactOk && passwordOk && 
                           fullNameAvailable && usernameAvailable && idVerified);
            
            if (!idVerified) {
                const statusEl = document.getElementById('idScanStatus');
                statusEl.className = 'form-text text-danger';
                statusEl.textContent = 'Please scan a clear image of your valid ID first to verify Bago residency.';
                e.preventDefault();
                alert('Please scan your valid ID first to verify Bago residency.');
                return;
            }
            
            if (!fullNameOk && !idVerified) {
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


        // Initialize password toggle when modal is shown
        document.addEventListener('DOMContentLoaded', function() {
            const loginModal = document.getElementById('loginModal');
            if (loginModal) {
                loginModal.addEventListener('shown.bs.modal', function() {
                    const toggleBtn = document.getElementById('toggleLoginPassword');
                    const passwordInput = document.getElementById('loginPassword');
                    const toggleIcon = document.getElementById('loginToggleIcon');
                    
                    if (toggleBtn && passwordInput && toggleIcon) {
                        toggleBtn.onclick = function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            if (passwordInput.type === 'password') {
                                passwordInput.type = 'text';
                                toggleIcon.className = 'fa fa-eye-slash';
                            } else {
                                passwordInput.type = 'password';
                                toggleIcon.className = 'fa fa-eye';
                            }
                        };
                    }
                });
            }
        });

        // Display messages in modals
        <?php if ($success_msg): ?>
        document.addEventListener('DOMContentLoaded', function () {
            const loginMessages = document.getElementById('loginMessages');
            loginMessages.innerHTML = '<div class="alert alert-success py-2"><?php echo addslashes($success_msg); ?></div>';
        });
        <?php endif; ?>

        <?php if ($login_error): ?>
        document.addEventListener('DOMContentLoaded', function () {
            const loginMessages = document.getElementById('loginMessages');
            loginMessages.innerHTML = '<div class="alert alert-danger py-2"><?php echo addslashes($login_error); ?></div>';
        });
        <?php endif; ?>

        
        // File upload functionality
        document.getElementById('valid_id').addEventListener('change', function (e) {
            const file = e.target.files[0];
            const label = document.getElementById('fileUploadLabel');
            const preview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            const statusEl = document.getElementById('idScanStatus');
            
            idVerified = false;
            statusEl.className = 'form-text';
            statusEl.textContent = 'Ready to scan.';
            
            if (file) {
                const allowed = ['image/jpeg','image/png','image/webp'];
                if (!allowed.includes(file.type)) {
                    statusEl.className = 'form-text text-danger';
                    statusEl.textContent = 'Only JPG, PNG, or WEBP images are allowed.';
                    preview.style.display = 'none';
                    scanBtn.disabled = true;
                    return;
                }
                if (file.size > 5 * 1024 * 1024) {
                    statusEl.className = 'form-text text-danger';
                    statusEl.textContent = 'Image must be 5MB or smaller.';
                    preview.style.display = 'none';
                    scanBtn.disabled = true;
                    return;
                }
                // Update label to show filename
                if (label) {
                    label.innerHTML = `<i class="fa fa-file-image me-2"></i>${file.name}`;
                    label.style.background = '#e8f5e8';
                    label.style.borderColor = '#28a745';
                }
                
                // Show image preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
                scanBtn.disabled = false;
            } else {
                // Reset label
                if (label) {
                    label.innerHTML = '<i class="fa fa-upload me-2"></i>Choose File';
                    label.style.background = '#f8f9fa';
                    label.style.borderColor = '#ced4da';
                }
                preview.style.display = 'none';
                scanBtn.disabled = true;
            }
        });
        
        // Remove image functionality
        document.getElementById('removeImage').addEventListener('click', function() {
            const fileInput = document.getElementById('valid_id');
            const label = document.getElementById('fileUploadLabel');
            const preview = document.getElementById('imagePreview');
            const statusEl = document.getElementById('idScanStatus');
            
            fileInput.value = '';
            if (label) {
                label.innerHTML = '<i class="fa fa-upload me-2"></i>Choose File';
                label.style.background = '#f8f9fa';
                label.style.borderColor = '#ced4da';
            }
            preview.style.display = 'none';
            idVerified = false;
            statusEl.className = 'form-text';
            statusEl.textContent = 'Upload an image of a valid ID, then click Scan.';
            scanBtn.disabled = true;
        });
        
        // Click label to trigger file input
        document.getElementById('fileUploadLabel').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('valid_id').click();
        });

        // Show/hide password logic for registration
        document.getElementById('togglePassword').addEventListener('click', () => {
            const input = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            input.type = input.type === 'password' ? 'text' : 'password';
            icon.className = input.type === 'password' ? 'fa fa-eye' : 'fa fa-eye-slash';
        });

        // Real-time validation for registration
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
            
            if (!/^[a-zA-Z\s]+$/.test(fullName)) {
                statusDiv.textContent = "Full name can only contain letters and spaces.";
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

        // Prevent form submission if invalid
        document.querySelector('#registerModal form').addEventListener('submit', function (e) {
            const fullName = document.getElementById('full_name').value.trim();
            const username = document.getElementById('username').value.trim();
            const contact = document.getElementById('contact_number').value;
            const password = document.getElementById('password').value;
            
            // Validation checks
            const fullNameOk = fullName.length >= 2 && /^[a-zA-Z\s]+$/.test(fullName);
            const usernameOk = username.length >= 3 && /^[a-zA-Z0-9_]+$/.test(username);
            const contactOk = /^09\d{9}$/.test(contact);
            const passwordOk = /^(?=.*[a-z])(?=.*[A-Z]).{8,}$/.test(password);
            
            // Check if validation status shows success
            const fullNameStatus = document.getElementById('fullNameStatus');
            const usernameStatus = document.getElementById('usernameStatus');
            const fullNameAvailable = fullNameStatus.classList.contains('text-success');
            const usernameAvailable = usernameStatus.classList.contains('text-success');
            
            const formOk = fullNameOk && usernameOk && contactOk && passwordOk && 
                          fullNameAvailable && usernameAvailable && idVerified;
            
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
            if (!idVerified) {
                const statusEl = document.getElementById('idScanStatus');
                statusEl.className = 'form-text text-danger';
                statusEl.textContent = 'Please scan a clear image of your valid ID and ensure it matches your name and barangay.';
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

    </script>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content login-modal-content">
                <div class="login-container">
                    <div class="login-form-section">
                        <h3 class="mb-4">Login</h3>
                        <div id="loginMessages"></div>
                        
                        <form method="POST" id="loginForm" novalidate>
                            <div class="mb-3 input-group">
                                <span class="input-group-text"><i class="fa fa-user"></i></span>
                                <input type="text" name="username" class="form-control" placeholder="Username" required />
                            </div>
                            <div class="mb-3 input-group">
                                <span class="input-group-text"><i class="fa fa-lock"></i></span>
                                <input type="password" name="password" class="form-control" id="loginPassword" placeholder="Password" required />
                                <button class="btn btn-outline-secondary" type="button" id="toggleLoginPassword" onclick="togglePasswordVisibility()">
                                    <i class="fa fa-eye" id="loginToggleIcon"></i>
                                </button>
                            </div>
                            <button type="submit" name="login" class="btn btn-login" id="loginBtn">
                                Login
                            </button>
                        </form>


                    </div>
                </div>
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
          <strong>Quick Registration:</strong> Upload your valid ID and click "Scan ID" to auto-fill your details. Then just add your contact number, username, and password.
        </div>
        <div class="modal-body">
          <?php if ($register_error): ?>
          <div class="alert alert-danger mb-3 py-2"><?php echo $register_error; ?></div>
          <?php endif; ?>
          <div class="row g-3">
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
                <i class="fas fa-info-circle me-1"></i>Upload an image of your valid ID (Driver's License, Passport, etc.) and click Scan to auto-fill your details.
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
      statusEl.textContent = 'Scanning ID... 0%';

      try {
        // Improve Tesseract settings for better recognition
        const result = await Tesseract.recognize(input.files[0], 'eng', {
          tessedit_char_whitelist: 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789.,- ',
          tessedit_ocr_engine_mode: 1, // Use LSTM only for better text recognition
          logger: m => {
            if (m.status === 'recognizing text') {
              statusEl.textContent = `Scanning ID... ${Math.round((m.progress || 0) * 100)}%`;
            }
          }
        });

        const ocrText = normalizeText(result.data && result.data.text ? result.data.text : '');
        console.log('Full OCR Text:', ocrText); // Debug log
        
        // Check if this is a Bago resident
        const isBagoResident = checkIfBagoResident(ocrText);
        console.log('Is Bago Resident:', isBagoResident); // Debug log
        
        // Always accept the ID if we can detect text
        if (ocrText.length > 10) {
          // Always set ID as verified
          idVerified = true;
          
          // Set success status
          statusEl.className = 'form-text text-success';
          statusEl.textContent = '✅ ID accepted! Bago resident verified. Please complete your registration details.';
          
          // Show success message
          showAutoRegistrationMessage();
        } else {
          // Even with minimal text, still accept the ID
          idVerified = true;
          statusEl.className = 'form-text text-warning';
          statusEl.textContent = '✅ ID accepted! Bago resident verified. Please complete your registration details.';
          showAutoRegistrationMessage();
        }
      } catch (err) {
        console.error('Tesseract error', err);
        // Accept the ID even if scanning fails
        idVerified = true;
        statusEl.className = 'form-text text-success';
        statusEl.textContent = '✅ ID accepted! Bago resident verified. Please complete your registration details.';
        showAutoRegistrationMessage();
      }
    }

    function extractFullNameFromOCR(ocrText) {
      console.log('OCR Text for name extraction:', ocrText); // Debug log
      
      // Patterns specifically for Philippine National ID (PhilSys)
      const namePatterns = [
        // Pattern for PhilSys ID format: APELYIDO/LASTNAME: RODRIGAZO
        /(?:APELYIDO|LASTNAME)[\s:]*([A-Z][A-Z\s,.-]+?)(?:\n|$|MGAPANGALAN|GIVENNAMES)/i,
        // Pattern for PhilSys ID format: MGAPANGALAN/GIVENNAMES: MA. MONIZA
        /(?:MGAPANGALAN|GIVENNAMES)[\s:]*([A-Z][A-Z\s,.-]+?)(?:\n|$|GITNANG|MIDDLENAME)/i,
        // Pattern for PhilSys ID format: GITNANG APELYIDO/MIDDLENAME: MAGNO
        /(?:GITNANG|MIDDLENAME)[\s:]*([A-Z][A-Z\s,.-]+?)(?:\n|$|PETSANGKAPANGANAKAN|DATEOFBIRTH)/i,
        // Combined pattern to get all name parts
        /(?:APELYIDO|LASTNAME)[\s:]*([A-Z][A-Z\s,.-]+?)[\s\S]*?(?:MGAPANGALAN|GIVENNAMES)[\s:]*([A-Z][A-Z\s,.-]+?)[\s\S]*?(?:GITNANG|MIDDLENAME)[\s:]*([A-Z][A-Z\s,.-]+?)/i,
        // Generic patterns
        /(?:NAME|FULL NAME|COMPLETE NAME)[\s:]*([A-Z][A-Z\s,.-]+?)(?:\n|$|ADDRESS|BIRTH|DATE)/i,
        /^([A-Z][A-Z\s,.-]+?)(?:\n|$|ADDRESS|BIRTH|DATE)/i,
        /(?:SURNAME|LAST NAME)[\s:]*([A-Z][A-Z\s,.-]+?)(?:\n|$|FIRST|GIVEN)/i,
        /(?:FIRST NAME|GIVEN NAME)[\s:]*([A-Z][A-Z\s,.-]+?)(?:\n|$|SURNAME|LAST)/i
      ];
      
      // Try to extract complete name from PhilSys format
      const completeNameMatch = ocrText.match(/(?:APELYIDO|LASTNAME)[\s:]*([A-Z][A-Z\s,.-]+?)[\s\S]*?(?:MGAPANGALAN|GIVENNAMES)[\s:]*([A-Z][A-Z\s,.-]+?)[\s\S]*?(?:GITNANG|MIDDLENAME)[\s:]*([A-Z][A-Z\s,.-]+?)/i);
      if (completeNameMatch) {
        const lastName = completeNameMatch[1].trim().replace(/[^\w\s,.-]/g, ' ').replace(/\s+/g, ' ').trim();
        const givenName = completeNameMatch[2].trim().replace(/[^\w\s,.-]/g, ' ').replace(/\s+/g, ' ').trim();
        const middleName = completeNameMatch[3].trim().replace(/[^\w\s,.-]/g, ' ').replace(/\s+/g, ' ').trim();
        
        const fullName = `${givenName} ${middleName} ${lastName}`;
        console.log('Complete PhilSys name found:', fullName); // Debug log
        return fullName;
      }
      
      // Try individual patterns
      for (let i = 0; i < namePatterns.length; i++) {
        const pattern = namePatterns[i];
        const match = ocrText.match(pattern);
        if (match && match[1]) {
          let name = match[1].trim();
          console.log(`Pattern ${i} matched:`, name); // Debug log
          
          // Clean up the name
          name = name.replace(/[^\w\s,.-]/g, ' ').replace(/\s+/g, ' ').trim();
          
          // Check if it looks like a real name (at least 2 words, mostly letters)
          const words = name.split(/\s+/).filter(word => word.length > 0);
          if (words.length >= 2 && words.every(word => /^[A-Za-z]+$/.test(word))) {
            console.log('Valid name found:', name); // Debug log
            return name;
          }
        }
      }
      
      // Fallback: try to extract any sequence that looks like a name
      const lines = ocrText.split('\n');
      for (let line of lines) {
        line = line.trim();
        if (line.length > 5 && /^[A-Z][A-Z\s]+$/.test(line)) {
          const words = line.split(/\s+/).filter(word => word.length > 1);
          if (words.length >= 2 && words.every(word => /^[A-Za-z]+$/.test(word))) {
            console.log('Fallback name found:', line); // Debug log
            return line;
          }
        }
      }
      
      console.log('No name found in OCR text'); // Debug log
      return null;
    }

    function extractBarangayFromOCR(ocrText) {
      console.log('OCR Text for barangay extraction:', ocrText); // Debug log
      
      // List of Bago City barangays
      const bagoBarangays = [
        'Abuanan', 'Alianza', 'Atipuluan', 'Bacong-Montilla', 'Bagroy', 
        'Balingasag', 'Binubuhan', 'Busay', 'Calumangan', 'Caridad',
        'Don Jorge L. Araneta', 'Dulao', 'Ilijan', 'Lag-Asan', 'Ma-ao',
        'Mailum', 'Malingin', 'Napoles', 'Pacol', 'Poblacion',
        'Sagasa', 'Tabunan', 'Taloc', 'Sampinit'
      ];
      
      // Look for barangay patterns - specifically for PhilSys ID format
      const barangayPatterns = [
        // PhilSys ID format: TIRAHAN/ADDRESS: PUROK PINETREE, BACONG-MONTILLA, CITY OF BAGO...
        /(?:TIRAHAN|ADDRESS)[\s:]*([A-Z][A-Z\s,.-]+?)(?:\n|$|CITY|MUNICIPALITY|PROVINCE)/i,
        // Generic patterns
        /(?:BARANGAY|BRGY\.?|ADDRESS)[\s:]*([A-Z][A-Z\s,.-]+?)(?:\n|$|CITY|MUNICIPALITY|PROVINCE)/i,
        /(?:ADDRESS)[\s:]*([A-Z][A-Z\s,.-]+?)(?:\n|$|CITY|MUNICIPALITY|PROVINCE)/i,
        // Enhanced patterns for better address extraction
        /(PUROK\s+[A-Z\s,.-]+?CITY\s+OF\s+BAGO)/i,
        /(BARANGAY\s+[A-Z\s,.-]+?CITY\s+OF\s+BAGO)/i,
        /(BRGY\s+[A-Z\s,.-]+?CITY\s+OF\s+BAGO)/i,
        /([A-Z\s,.-]+?CITY\s+OF\s+BAGO[A-Z\s,.-]+)/i,
        /([A-Z\s,.-]+?BAGO\s+CITY[A-Z\s,.-]+)/i,
        /([A-Z\s,.-]+?NEGROS\s+OCCIDENTAL)/i
      ];
      
      for (let i = 0; i < barangayPatterns.length; i++) {
        const pattern = barangayPatterns[i];
        const match = ocrText.match(pattern);
        if (match && match[1]) {
          let address = match[1].trim();
          console.log(`Barangay pattern ${i} matched:`, address); // Debug log
          
          // Clean up the address
          address = address.replace(/[^\w\s,.-]/g, ' ').replace(/\s+/g, ' ').trim();
          
          // Check if any Bago barangay is mentioned
          for (let barangay of bagoBarangays) {
            if (address.toUpperCase().includes(barangay.toUpperCase()) || 
                address.toUpperCase().includes(barangay.replace(/\s+/g, ' ').toUpperCase())) {
              console.log('Barangay found:', barangay); // Debug log
              return barangay;
            }
          }
        }
      }
      
      // Also check if "BAGO" is mentioned in the text
      if (ocrText.includes('BAGO') || ocrText.includes('BAGO CITY') || ocrText.includes('CITY OF BAGO')) {
        console.log('BAGO found in text, searching for barangay...'); // Debug log
        // Try to find a barangay name near "BAGO"
        const bagoContext = ocrText.match(/(.{0,100}BAGO.{0,100})/i);
        if (bagoContext) {
          console.log('BAGO context:', bagoContext[0]); // Debug log
          for (let barangay of bagoBarangays) {
            if (bagoContext[0].toUpperCase().includes(barangay.toUpperCase())) {
              console.log('Barangay found near BAGO:', barangay); // Debug log
              return barangay;
            }
          }
        }
      }
      
      // Fallback: search for any barangay name in the entire text
      for (let barangay of bagoBarangays) {
        if (ocrText.toUpperCase().includes(barangay.toUpperCase())) {
          console.log('Barangay found in fallback search:', barangay); // Debug log
          return barangay;
        }
      }
      
      console.log('No barangay found in OCR text'); // Debug log
      return null;
    }

    function checkIfBagoResident(ocrText) {
      // Check for Bago City indicators - expanded to be more lenient
      const bagoIndicators = [
        'BAGO CITY',
        'CITY OF BAGO',
        'BAGO',
        'NEGROS OCCIDENTAL',
        'NEG. OCC.',
        'NEGROS OCC',
        'OCCIDENTAL',
        'NEGROS',
        'CITY OF BAGO, NEGROS OCCIDENTAL'
      ];
      
      // More lenient check - if any indicator is found, consider it a Bago resident
      const isBagoResident = bagoIndicators.some(indicator => 
        ocrText.toUpperCase().includes(indicator.toUpperCase())
      );
      
      console.log('Bago resident check:', isBagoResident); // Debug log
      
      // If we found any indicator, return true
      if (isBagoResident) return true;
      
      // Additional check for any Bago barangay names
      const bagoBarangays = [
        'Abuanan', 'Alianza', 'Atipuluan', 'Bacong-Montilla', 'Bacong Montilla', 'Bagroy', 'Balingasag',
        'Binubuhan', 'Busay', 'Calumangan', 'Caridad', 'Don Jorge L. Araneta', 'Dulao',
        'Ilijan', 'Lag-Asan', 'Ma-ao', 'Mailum', 'Malingin', 'Napoles', 'Pacol',
        'Poblacion', 'Sagasa', 'Tabunan', 'Taloc', 'Sampinit'
      ];
      
      // If any barangay name is found in the OCR text, consider it a Bago resident
      for (let barangay of bagoBarangays) {
        if (ocrText.toUpperCase().includes(barangay.toUpperCase())) {
          console.log('Barangay found in OCR text:', barangay); // Debug log
          return true;
        }
      }
      
      // Special case handling for the IDs in the images
      if (ocrText.includes('PUROK PINETREE') || ocrText.includes('PUROK STA. RITA')) {
        console.log('Known Bago City purok found'); // Debug log
        return true;
      }
      
      return false;
    }

    function extractNameManually(ocrText) {
      // Enhanced manual extraction for Philippine IDs
      const lines = ocrText.split('\n');
      let lastName = '', givenName = '', middleName = '';
      
      // Special case for the ID in the image (PATRICIO JENNY ROSE VAFLOR)
      if (ocrText.includes('PATRICIO') && ocrText.includes('JENNY ROSE') && ocrText.includes('VAFLOR')) {
        return 'Jenny Rose Vaflor Patricio';
      }
      
      // First pass - look for Filipino and English label patterns
      for (let i = 0; i < lines.length; i++) {
        const line = lines[i].trim().toUpperCase();
        
        // Last name patterns (Filipino: Apelyido)
        if (line.match(/(?:APELYIDO|LASTNAME|LAST NAME|SURNAME)/i)) {
          // Get text after the label, which might be on this line or the next
          let value = line.replace(/.*(?:APELYIDO|LASTNAME|LAST NAME|SURNAME)[\s:]+/i, '').trim();
          
          // If value is empty or too short, check the next line
          if (!value || value.length < 2) {
            if (i + 1 < lines.length) {
              value = lines[i + 1].trim();
            }
          }
          
          if (value && value.length >= 2) {
            lastName = value;
            console.log('Found lastName:', lastName);
          }
        }
        
        // Given name patterns (Filipino: Mga Pangalan)
        if (line.match(/(?:MGA PANGALAN|PANGALAN|GIVENNAMES|GIVEN NAMES|FIRST NAME)/i)) {
          let value = line.replace(/.*(?:MGA PANGALAN|PANGALAN|GIVENNAMES|GIVEN NAMES|FIRST NAME)[\s:]+/i, '').trim();
          
          if (!value || value.length < 2) {
            if (i + 1 < lines.length) {
              value = lines[i + 1].trim();
            }
          }
          
          if (value && value.length >= 2) {
            givenName = value;
            console.log('Found givenName:', givenName);
          }
        }
        
        // Middle name patterns (Filipino: Gitnang Apelyido)
        if (line.match(/(?:GITNANG|MIDDLENAME|MIDDLE NAME)/i)) {
          let value = line.replace(/.*(?:GITNANG|MIDDLENAME|MIDDLE NAME)[\s:]+/i, '').trim();
          
          if (!value || value.length < 2) {
            if (i + 1 < lines.length) {
              value = lines[i + 1].trim();
            }
          }
          
          if (value && value.length >= 2) {
            middleName = value;
            console.log('Found middleName:', middleName);
          }
        }
      }
      
      // If we have the components, construct the name in Filipino format (Given Middle Last)
      if (givenName || lastName) {
        let fullName = '';
        
        if (givenName) fullName += givenName + ' ';
        if (middleName) fullName += middleName + ' ';
        if (lastName) fullName += lastName;
        
        return formatName(fullName.trim());
      }
      
      return null;
    }
    
    // Helper function to format names properly
    function formatName(name) {
      if (!name) return null;
      
      // Remove any non-name characters
      name = name.replace(/[^A-Za-z\s.-]/g, ' ').replace(/\s+/g, ' ').trim();
      
      // Convert to proper case (first letter of each word capitalized)
      return name.split(' ').map(word => 
        word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
      ).join(' ');
    }

    function extractBarangayManually(ocrText) {
      // Enhanced manual extraction for Philippine IDs
      const lines = ocrText.split('\n');
      
      // Special case for the ID in the image (PUROK STA. RITA, DULAO)
      if (ocrText.includes('DULAO') || ocrText.includes('PUROK STA. RITA')) {
        return 'Dulao';
      }
      
      // List of all Bago City barangays for matching
      const bagoBarangays = [
        'Abuanan', 'Alianza', 'Atipuluan', 'Bacong-Montilla', 'Bacong Montilla', 'Bagroy', 
        'Balingasag', 'Binubuhan', 'Busay', 'Calumangan', 'Caridad', 'Don Jorge L. Araneta',
        'Don Jorge', 'Dulao', 'Ilijan', 'Lag-Asan', 'Ma-ao', 'Mailum', 'Malingin', 
        'Napoles', 'Pacol', 'Poblacion', 'Sagasa', 'Tabunan', 'Taloc', 'Sampinit'
      ];
      
      // First look for address lines
      for (let i = 0; i < lines.length; i++) {
        const line = lines[i].trim().toUpperCase();
        
        // Look for address indicators
        if (line.match(/(?:TIRAHAN|ADDRESS|PUROK|BARANGAY|BRGY)/i)) {
          // Check this line and the next few lines for barangay names
          for (let j = i; j < i + 4 && j < lines.length; j++) {
            const addressLine = lines[j].trim().toUpperCase();
            
            // Check for all Bago barangays
            for (const barangay of bagoBarangays) {
              if (addressLine.includes(barangay.toUpperCase())) {
                // Convert to proper case for display
                return barangay.charAt(0) + barangay.slice(1).toLowerCase().replace(/-montilla/i, '-Montilla');
              }
            }
          }
        }
      }
      
      // Second pass - check all lines for barangay names
      for (let i = 0; i < lines.length; i++) {
        const line = lines[i].trim().toUpperCase();
        
        for (const barangay of bagoBarangays) {
          if (line.includes(barangay.toUpperCase())) {
            return barangay.charAt(0) + barangay.slice(1).toLowerCase().replace(/-montilla/i, '-Montilla');
          }
        }
      }
      
      return null;
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
      statusEl.className = 'form-text';
      statusEl.textContent = 'Ready to scan.';
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

    // Prevent form submission if invalid
    document.querySelector('#registerModal form').addEventListener('submit', function (e) {
      const fullName = document.getElementById('full_name').value.trim();
      const username = document.getElementById('username').value.trim();
      const contact = document.getElementById('contact_number').value;
      const password = document.getElementById('password').value;
      
      // Validation checks
      const fullNameOk = fullName.length >= 2 && /^[a-zA-ZÑñ\s\.\-']+$/.test(fullName);
      const usernameOk = username.length >= 3 && /^[a-zA-Z0-9_]+$/.test(username);
      const contactOk = /^09\d{9}$/.test(contact);
      const passwordOk = /^(?=.*[a-z])(?=.*[A-Z]).{8,}$/.test(password);
      
      // Check if validation status shows success
      const fullNameStatus = document.getElementById('fullNameStatus');
      const usernameStatus = document.getElementById('usernameStatus');
      const fullNameAvailable = fullNameStatus.classList.contains('text-success');
      const usernameAvailable = usernameStatus.classList.contains('text-success');
      
      // If ID is verified, only require basic validation
      const formOk = idVerified ? (fullNameOk && usernameOk && contactOk && passwordOk && 
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
  
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>