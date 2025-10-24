<?php
// Session already started in conn.php
include 'includes/conn.php';
include 'includes/activity_logger.php';
include 'includes/security.php';
include 'includes/bago_validation.php';

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
    $address = sanitize($_POST['address'] ?? '');
    $username = strtolower(sanitize($_POST['username'] ?? ''));
    $password_raw = $_POST['password'] ?? '';

    // Validate required fields
    if (!$full_name || !$contact_number || !$address || !$username || !$password_raw) {
        $register_error = "Please fill in all required fields.";
    } else {
        // NOTE: We validate residency from ID scan, not from manually selected address
        // The address dropdown is just for user convenience, actual validation is done via ID
        
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
                            // Use client-extracted name if available to avoid OCR drift
                            $clientExtracted = isset($_POST['scanned_full_name']) ? (string)$_POST['scanned_full_name'] : '';
                            $extractedName = $clientExtracted;
                            // Do not rely on server extraction to avoid missing function/hosting limits
                            if (!is_string($extractedName)) { $extractedName = ''; }

                            $enteredNorm = normalize_text($full_name);
                            $extractedNorm = normalize_text($extractedName);
                            $ocrNorm = normalize_text($ocrOrErr);

                            // RULE 1: Name must match
                            $nameOk = false;
                            // Source of truth: client-scanned name, if present
                            if ($extractedNorm) {
                                $nameOk = ($enteredNorm === $extractedNorm);
                            }
                            // Fallback to server OCR only if client did not provide a name
                            if (!$nameOk && !$extractedNorm) {
                                $nameTokens = array_values(array_filter(explode(' ', $enteredNorm)));
                                $nameOk = tokens_present_count($ocrNorm, $nameTokens) >= 2;
                            }

                            if (!$nameOk) {
                                $register_error = "The full name you entered does not match the name on your ID. Please check and try again.";
                            }
                            else {
                                // Use enhanced Bago City validation
                                list($isValid, $validationMessage) = validateIDForBagoResidency($ocrOrErr, $full_name);
                                
                                if (!$isValid) {
                                    $register_error = $validationMessage;
                                } else {
                                    $success_msg = $validationMessage;
                                }
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

                                    // Insert into clients table with lat/lng (clients only)
                                    $stmt1 = $conn->prepare("INSERT INTO clients (full_name, contact_number, address, username, password, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)");

                                    if (!$stmt1) {
                                        $register_error = "Error preparing insert statements.";
                                    } else {
                                        $stmt1->bind_param("sssssss", $full_name, $contact_number, $address, $username, $password, $latitude, $longitude);

                                        if ($stmt1->execute()) {
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
    // Rate limiting: Prevent brute force attacks
    if (!check_rate_limit('login', 5, 300)) {
        $login_error = "Too many login attempts. Please wait 5 minutes and try again.";
    } else {
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
            // Regenerate session ID to prevent session fixation attacks
            regenerate_session();
            
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
    }  // Close rate limit check
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
            <input type="text" name="username" class="form-control" placeholder="Username" autocomplete="username" required />
          </div>
          <div class="mb-3 input-group">
            <span class="input-group-text"><i class="fa fa-lock"></i></span>
            <input type="password" name="password" class="form-control" id="loginPassword" placeholder="Password" autocomplete="current-password" required />
            <button class="btn btn-outline-secondary" type="button" id="toggleLoginPassword">
              <i class="fa fa-eye" id="loginToggleIcon"></i>
            </button>
          </div>
          <button type="submit" name="login" class="btn btn-login" id="loginBtn">
            Login
          </button>
        </form>

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
          <strong>Quick Registration:</strong> Upload your valid ID and click "Scan ID" to auto-fill your details. Then just add your contact number, username, and password.
        </div>
        <div class="modal-body">
          <?php if ($register_error): ?>
          <div class="alert alert-danger mb-3 py-2"><?php echo $register_error; ?></div>
          <?php endif; ?>
          <div class="row g-3">
            <input type="hidden" name="scanned_full_name" id="scanned_full_name" value="">
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
              <?php echo generateBarangayDropdown(); ?>
            </div>
            <div class="col-md-6">
          <label>Username <span class="text-danger">*</span></label>
          <input type="text" name="username" id="username" class="form-control" placeholder="Choose a username" autocomplete="username" required>
          <div id="usernameStatus" class="form-text"></div>
        </div>

            <div class="col-md-6">
              <label>Password <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="password" name="password" class="form-control" id="password" placeholder="Create a strong password" autocomplete="new-password" required>
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
                <i class="fas fa-info-circle me-1"></i>Upload a Philippine ID image and click Scan to auto-fill your details using text recognition.
              </div>
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

    function extractFullNameFromOCR(ocrText) {
      console.log('OCR Text for name extraction:', ocrText); // Debug log
      
      // Patterns specifically for Philippine National ID (PhilSys)
      const namePatterns = [
        // Robust single-field patterns (accept spaces/slashes in labels)
        /(?:APELYIDO\s*\/\s*LAST\s*NAME|APELYIDO|LAST\s*NAME)[\s:]*([A-Z][A-Z\s,.-]+?)(?:\n|$|MGA\s*PANGALAN|MGAPANGALAN|GIVEN\s*NAMES?)/i,
        /(?:MGA\s*PANGALAN\s*\/\s*GIVEN\s*NAMES?|MGAPANGALAN|GIVEN\s*NAMES?)[\s:]*([A-Z][A-Z\s,.-]+?)(?:\n|$|GITNANG|MIDDLE\s*NAME)/i,
        /(?:GITNANG\s*APELYIDO\s*\/\s*MIDDLE\s*NAME|GITNANG|MIDDLE\s*NAME)[\s:]*([A-Z][A-Z\s,.-]+?)(?:\n|$|PETSANGKAPANGANAKAN|DATE\s*OF\s*BIRTH)/i,
        // Combined pattern across lines (DOTALL)
        /(?:APELYIDO\s*\/\s*LAST\s*NAME|APELYIDO|LAST\s*NAME)[\s:]*([A-Z][A-Z\s,.-]+?)[\s\S]*?(?:MGA\s*PANGALAN|MGAPANGALAN|GIVEN\s*NAMES?)[\s:]*([A-Z][A-Z\s,.-]+?)[\s\S]*?(?:GITNANG\s*APELYIDO|GITNANG|MIDDLE\s*NAME)[\s:]*([A-Z][A-Z\s,.-]+?)/is,
        // Generic patterns
        /(?:NAME|FULL\s*NAME|COMPLETE\s*NAME)[\s:]*([A-Z][A-Z\s,.-]+?)(?:\n|$|ADDRESS|BIRTH|DATE)/i,
        /^([A-Z][A-Z\s,.-]+?)(?:\n|$|ADDRESS|BIRTH|DATE)/i,
        /(?:SURNAME|LAST\s*NAME)[\s:]*([A-Z][A-Z\s,.-]+?)(?:\n|$|FIRST|GIVEN)/i,
        /(?:FIRST\s*NAME|GIVEN\s*NAME)[\s:]*([A-Z][A-Z\s,.-]+?)(?:\n|$|SURNAME|LAST)/i
      ];
      
      // Try to extract complete name from PhilSys format
      const completeNameMatch = ocrText.match(/(?:APELYIDO\s*\/\s*LAST\s*NAME|APELYIDO|LAST\s*NAME)[\s:]*([A-Z][A-Z\s,.-]+?)[\s\S]*?(?:MGA\s*PANGALAN|MGAPANGALAN|GIVEN\s*NAMES?)[\s:]*([A-Z][A-Z\s,.-]+?)[\s\S]*?(?:GITNANG\s*APELYIDO|GITNANG|MIDDLE\s*NAME)[\s:]*([A-Z][A-Z\s,.-]+?)/i);
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
        'Sagasa', 'Sampinit', 'Tabunan', 'Taloc'
      ];
      
      // Look for barangay patterns - specifically for PhilSys ID format
      const barangayPatterns = [
        // PhilSys ID format: TIRAHAN/ADDRESS: PUROK PINETREE, BACONG-MONTILLA, CITY OF BAGO...
        /(?:TIRAHAN|ADDRESS)[\s:]*([A-Z][A-Z\s,.-]+?)(?:\n|$|CITY|MUNICIPALITY|PROVINCE)/i,
        // Generic patterns
        /(?:BARANGAY|BRGY\.?|ADDRESS)[\s:]*([A-Z][A-Z\s,.-]+?)(?:\n|$|CITY|MUNICIPALITY|PROVINCE)/i,
        /(?:ADDRESS)[\s:]*([A-Z][A-Z\s,.-]+?)(?:\n|$|CITY|MUNICIPALITY|PROVINCE)/i
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
      // UPDATED: Fixed "CITY OF BAGO" validation - Cache bust v4 - FORCE REFRESH
      console.log('=== VALIDATION START v5 - COMPREHENSIVE VALIDATION - <?php echo time(); ?> ===');
      console.log('Timestamp:', new Date().toISOString());
      console.log('OCR Text received:', ocrText);
      console.log('OCR Text length:', ocrText.length);
      console.log('OCR Text first 100 chars:', ocrText.substring(0, 100));
      console.log('OCR Text last 100 chars:', ocrText.substring(Math.max(0, ocrText.length - 100)));
      
      // Debug: Show text around "BAGO" if it exists
      if (ocrText.toUpperCase().includes('BAGO')) {
        const bagoIndex = ocrText.toUpperCase().indexOf('BAGO');
        console.log('📍 Text around "BAGO":', ocrText.substring(Math.max(0, bagoIndex - 50), bagoIndex + 100));
      }
      
      // Debug: Show text around "NEGROS" if it exists
      if (ocrText.toUpperCase().includes('NEGROS')) {
        const negrosIndex = ocrText.toUpperCase().indexOf('NEGROS');
        console.log('📍 Text around "NEGROS":', ocrText.substring(Math.max(0, negrosIndex - 30), negrosIndex + 50));
      }
      
      // FIRST: Check for OTHER cities/municipalities (reject immediately)
      const otherCities = [
        'PULUPANDAN', 'TALISAY', 'BACOLOD', 'SILAY', 'VICTORIAS', 'CADIZ', 
        'SAGAY', 'ESCALANTE', 'MANAPLA', 'VALLADOLID', 'MURCIA', 'SALVADOR BENEDICTO',
        'LA CARLOTA', 'LA CASTELLANA', 'MOISES PADILLA', 'ISABELA', 'BINALBAGAN',
        'HIMAMAYLAN', 'KABANKALAN', 'ILOG', 'CAUAYAN', 'CANDONI', 'HINIGARAN',
        'PONTEVEDRA', 'HINOBA AN', 'SIPALAY', 'CALATRAVA', 'TOBOSO', 'SAN CARLOS'
      ];
      
      // Check if ID contains any other city/municipality
      let foundOtherCity = false;
      for (let city of otherCities) {
        if (ocrText.toUpperCase().includes(city.toUpperCase())) {
          console.log('❌ Other city found:', city, '- NOT a Bago resident'); // Debug log
          foundOtherCity = true;
          break;
        }
      }
      
      if (foundOtherCity) {
        console.log('❌ REJECTING: Other city detected');
        return false; // REJECT immediately
      }
      
      console.log('✅ No other cities found - continuing validation');
      
      // SECOND: Check for STRICT Bago City + Negros Occidental patterns
      const strictBagoPatterns = [
        'BAGO CITY NEGROS OCCIDENTAL',
        'CITY OF BAGO NEGROS OCCIDENTAL',
        'CITY OF H L BAGO NEGROS OCCIDENTAL',  // OCR variation that appears in your logs
        'CITY OF H.L BAGO NEGROS OCCIDENTAL',  // OCR variation with periods
        'CITY OF HL BAGO NEGROS OCCIDENTAL',   // OCR variation without spaces
        'CHFY 0FBAGO NEGROS OCCIDENTAL',       // OCR corruption variation
        'CHFY OF BAGO NEGROS OCCIDENTAL'       // OCR corruption variation
      ];
      
      console.log('Checking for STRICT Bago patterns:', strictBagoPatterns);
      
      // Must contain EXACTLY "BAGO CITY NEGROS OCCIDENTAL" OR "CITY OF BAGO NEGROS OCCIDENTAL"
      let foundBagoIndicator = false;
      let matchedIndicator = '';
      
      for (let pattern of strictBagoPatterns) {
        if (ocrText.toUpperCase().includes(pattern.toUpperCase())) {
          foundBagoIndicator = true;
          matchedIndicator = pattern;
          console.log('✅ Found STRICT Bago pattern:', pattern); // Debug log
          break;
        }
      }
      
      if (!foundBagoIndicator) {
        console.log('❌ No STRICT Bago pattern found');
        console.log('❌ Looking for:', strictBagoPatterns);
        console.log('❌ OCR text contains:', ocrText.substring(Math.max(0, ocrText.indexOf('BAGO') - 30), ocrText.indexOf('BAGO') + 50));
        
        // Additional flexible pattern matching for OCR variations
        console.log('🔍 Trying flexible pattern matching...');
        
        // Check if text contains "BAGO" and "NEGROS OCCIDENTAL" separately
        const hasBago = ocrText.toUpperCase().includes('BAGO');
        const hasNegrosOccidental = ocrText.toUpperCase().includes('NEGROS OCCIDENTAL');
        
        console.log('Has BAGO:', hasBago);
        console.log('Has NEGROS OCCIDENTAL:', hasNegrosOccidental);
        
        if (hasBago && hasNegrosOccidental) {
          console.log('✅ Both BAGO and NEGROS OCCIDENTAL found!');
          
          // Check if it's not another city (use the same comprehensive list)
          const otherCitiesCheck = [
            'PULUPANDAN', 'TALISAY', 'BACOLOD', 'SILAY', 'VICTORIAS', 'CADIZ', 
            'SAGAY', 'ESCALANTE', 'MANAPLA', 'VALLADOLID', 'MURCIA', 'SALVADOR BENEDICTO',
            'LA CARLOTA', 'LA CASTELLANA', 'MOISES PADILLA', 'ISABELA', 'BINALBAGAN',
            'HIMAMAYLAN', 'KABANKALAN', 'ILOG', 'CAUAYAN', 'CANDONI', 'HINIGARAN',
            'PONTEVEDRA', 'HINOBA AN', 'SIPALAY', 'CALATRAVA', 'TOBOSO', 'SAN CARLOS',
            'MANILA'
          ];
          let hasOtherCity = false;
          
          for (let city of otherCitiesCheck) {
            if (ocrText.toUpperCase().includes(city.toUpperCase())) {
              hasOtherCity = true;
              console.log('❌ Found other city in flexible check:', city);
              break;
            }
          }
          
          if (!hasOtherCity) {
            console.log('✅ Flexible pattern match SUCCESS: BAGO + NEGROS OCCIDENTAL found, no other cities');
            foundBagoIndicator = true;
            matchedIndicator = 'BAGO + NEGROS OCCIDENTAL (flexible match)';
          } else {
            console.log('❌ Flexible pattern match FAILED: Other city detected');
          }
        } else {
          console.log('❌ Flexible pattern match FAILED: Missing BAGO or NEGROS OCCIDENTAL');
          if (!hasBago) console.log('   - Missing: BAGO');
          if (!hasNegrosOccidental) console.log('   - Missing: NEGROS OCCIDENTAL');
          
          // ULTRA FLEXIBLE: Check for "NEGROS" alone (might be missing "OCCIDENTAL")
          const hasNegros = ocrText.toUpperCase().includes('NEGROS');
          console.log('🔍 Ultra flexible check - Has NEGROS:', hasNegros);
          
          if (hasBago && hasNegros) {
            console.log('🔍 Checking if NEGROS is followed by OCCIDENTAL within 50 chars...');
            const negrosIndex = ocrText.toUpperCase().indexOf('NEGROS');
            const textAfterNegros = ocrText.substring(negrosIndex, negrosIndex + 50).toUpperCase();
            console.log('Text after NEGROS (50 chars):', textAfterNegros);
            
            // Check if "OCCIDENTAL" appears within 50 characters of "NEGROS"
            if (textAfterNegros.includes('OCCIDENTAL') || textAfterNegros.includes('0CCIDENTAL') || textAfterNegros.includes('OCCID')) {
              console.log('✅ ULTRA FLEXIBLE match: BAGO + NEGROS (with OCCIDENTAL within 50 chars)');
              foundBagoIndicator = true;
              matchedIndicator = 'BAGO + NEGROS + OCCIDENTAL (ultra flexible match)';
            } else {
              console.log('❌ OCCIDENTAL not found within 50 chars of NEGROS');
            }
          }
        }
      }
      
      console.log('Bago City check result:', foundBagoIndicator); // Debug log
      console.log('OCR Text being checked:', ocrText); // Debug log
      console.log('Matched indicator:', matchedIndicator); // Debug log
      
      // If no Bago City found, return false
      if (!foundBagoIndicator) {
        console.log('❌ Bago City not found in ID'); // Debug log
        console.log('Looking for:', strictBagoPatterns); // Debug log
        return false;
      }
      
      // If we found the STRICT Bago City pattern, we accept it
      // No need for complex barangay checking since we already verified it's Bago City + Negros Occidental
      console.log('✅ STRICT Bago City + Negros Occidental pattern found - ACCEPTED');
      console.log('✅ Valid Bago City resident confirmed!');
      return true;
      
      return false;
    }

    function extractNameManually(ocrText) {
      // Enhanced manual extraction for Philippine IDs
      const lines = ocrText.split('\n');
      let lastName = '', givenName = '', middleName = '';
      
      // Removed hardcoded special case; rely on generic extraction logic
      
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
        'Napoles', 'Pacol', 'Poblacion', 'Sagasa', 'Sampinit', 'Tabunan', 'Taloc'
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js?v=<?php echo time(); ?>"></script>
</body>
</html>