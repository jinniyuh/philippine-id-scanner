<?php
session_start();
include 'includes/conn.php';
include 'includes/geotagging_helper.php';

// Check if user is logged in
if (!isset($_SESSION['client_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit();
}

// Get client information
$client_id = $_SESSION['client_id'];
$stmt = $conn->prepare("SELECT * FROM clients WHERE client_id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$client = $stmt->get_result()->fetch_assoc();

// Check if client has disseminated animals (eligible for geotagging)
$hasDisseminatedAnimals = hasDisseminatedAnimals($conn, $client_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - Bago City Veterinary Office</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Leaflet CSS for maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body {
            background-color: #6c63ff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container-fluid {
            padding-left: 0;
            padding-right: 0;
            overflow-x: hidden;
        }
        
        .main-wrapper {
            background: white;
            margin-left: 312px;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            position: fixed;
            top: 20px;
            left: 20px;
            right: 20px;
            bottom: 20px;
            overflow-y: auto;
            overflow-x: hidden;
            min-height: calc(100vh - 40px);
            max-width: calc(100vw - 332px);
        }
        
        .page-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .page-header h2 {
            color: #333;
            font-weight: 600;
            margin: 0;
        }
        
        .profile-section {
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid #e8ecf7;
        }
        
        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 4px 15px rgba(108, 99, 255, 0.2);
        }
        
        .profile-info h4 {
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .status-badge {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            display: inline-block;
            margin-bottom: 8px;
        }
        
        .registration-date {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .form-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid #e8ecf7;
        }
        
        .form-section h5 {
            color: #333;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-control {
            border: 2px solid #e8ecf7;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #6c63ff;
            box-shadow: 0 0 0 0.2rem rgba(108, 99, 255, 0.15);
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #6c63ff, #8B9FF7);
            border: none;
            border-radius: 10px;
            padding: 12px 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(45deg, #5a52e8, #7a8ee6);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(108, 99, 255, 0.3);
        }
        
        .btn-outline-primary {
            border: 2px solid #6c63ff;
            color: #6c63ff;
            border-radius: 10px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-outline-primary:hover {
            background: #6c63ff;
            color: white;
            transform: translateY(-2px);
        }
        
        /* Tablet responsive styles */
        @media (max-width: 1024px) {
            .main-wrapper {
                margin-left: 312px;
                left: 20px;
                right: 20px;
                max-width: calc(100vw - 332px);
            }
        }
        
        /* Mobile responsive styles */
        @media (max-width: 768px) {
            .main-wrapper {
                margin-left: 0;
                top: 80px;
                left: 15px;
                right: 15px;
                bottom: 15px;
                max-width: calc(100vw - 30px);
                padding: 20px;
            }
            
            .d-flex.align-items-center {
                flex-direction: column;
                text-align: center;
            }
            
            .d-flex.align-items-center .ms-3 {
                margin-left: 0 !important;
                margin-top: 20px;
            }
            
            .profile-image {
                width: 100px;
                height: 100px;
            }
        }
        
        @media (max-width: 576px) {
            .main-wrapper {
                left: 10px;
                right: 10px;
                top: 80px;
                bottom: 10px;
                max-width: calc(100vw - 20px);
                padding: 15px;
            }
            
            .row {
                margin: 0;
            }
            
            .col-md-6 {
                padding: 0;
                margin-bottom: 20px;
            }
            
            .profile-section, .form-section {
                padding: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .main-wrapper {
                left: 5px;
                right: 5px;
                top: 80px;
                bottom: 5px;
                max-width: calc(100vw - 10px);
                padding: 10px;
            }
            
            .profile-section, .form-section {
                padding: 15px;
            }
        }
        
        /* Info Display Styles */
        .info-display {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-item label {
            font-weight: 600;
            color: #495057;
            margin: 0;
            min-width: 120px;
        }
        
        .info-item span {
            color: #212529;
            text-align: right;
            flex: 1;
            margin-left: 15px;
        }
        
        .btn-edit {
            background: linear-gradient(135deg, #6f42c1, #e83e8c);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(111, 66, 193, 0.3);
            color: white;
        }
        
        /* Password toggle styles */
        .password-input-group {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            padding: 0;
            z-index: 10;
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: #6c63ff;
        }
        
        .password-toggle:focus {
            outline: none;
            color: #6c63ff;
        }
        
        .password-input-group .form-control {
            padding-right: 45px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <?php include 'includes/client_sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 main-wrapper main-content">
                <div class="page-header">
                    <h2>My Profile</h2>
                </div>
                
                <div class="profile-section">
                    <div class="d-flex align-items-center">
                    <img src="<?php echo isset($client['profile_photo']) ? htmlspecialchars($client['profile_photo']) : 'assets/default-avatar.png'; ?>"
                            alt="Profile Picture" class="profile-image">
                        <div class="ms-3 profile-info">
                            <h4><?php echo htmlspecialchars($client['full_name'] ?? ''); ?></h4>
                            <span class="status-badge">Active Account</span>
                            <p class="registration-date">Registered: <?php echo date('F j, Y', strtotime($client['created_at'])); ?></p>
                        <form id="profilePictureForm" enctype="multipart/form-data">
                            <input type="file" id="profilePicture" name="profile_photo" accept="image/*" style="display: none;">
                                <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('profilePicture').click()">
                                <i class="fas fa-camera"></i> Change Picture
                            </button>
                        </form>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-section">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Personal Information</h5>
                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editInfoModal">
                                    <i class="fas fa-edit"></i> Edit Info
                                </button>
                            </div>
                            <div class="info-display">
                                <div class="info-item">
                                    <label>Full Name:</label>
                                    <span><?php echo htmlspecialchars($client['full_name'] ?? 'Not provided'); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Contact Number:</label>
                                    <span><?php echo htmlspecialchars($client['contact_number'] ?? 'Not provided'); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Address:</label>
                                    <span><?php echo htmlspecialchars($client['barangay'] ?? 'Not provided'); ?></span>
                                </div>
                            </div>
                            </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-section">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Security</h5>
                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editPasswordModal">
                                    <i class="fas fa-key"></i> Edit Password
                                </button>
                            </div>
                            <div class="info-display">
                                <div class="info-item">
                                    <label>Password Status:</label>
                                    <span class="text-success">✓ Secured</span>
                                </div>
                                <div class="info-item">
                                    <label>Last Changed:</label>
                                    <span><?php 
                                        // Debug: Check what we have
                                        $passwordChangedDate = isset($client['password_changed_at']) && $client['password_changed_at'] 
                                            ? $client['password_changed_at'] 
                                            : $client['created_at'];
                                        
                                        // Debug output (remove this after testing)
                                        if (isset($_GET['debug'])) {
                                            echo "<br><small>Debug - password_changed_at: " . ($client['password_changed_at'] ?? 'NULL') . "</small>";
                                            echo "<br><small>Debug - created_at: " . $client['created_at'] . "</small>";
                                            echo "<br><small>Debug - Using: " . $passwordChangedDate . "</small>";
                                        }
                                        
                                        echo date('F j, Y', strtotime($passwordChangedDate)); 
                                    ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Account Status:</label>
                                    <span class="text-success">✓ Active</span>
                                </div>
                            </div>
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Info Modal -->
    <div class="modal fade" id="editInfoModal" tabindex="-1" aria-labelledby="editInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editInfoModalLabel">
                        <i class="fas fa-edit"></i> Edit Personal Information
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editInfoForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="full_name" id="full_name" 
                                           value="<?php echo htmlspecialchars($client['full_name'] ?? ''); ?>" 
                                           placeholder="First Name Last Name" required>
                                    <div class="invalid-feedback" id="full_name_error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                            <div class="mb-3">
                                    <label class="form-label">Username <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="username" id="username" 
                                           value="<?php echo htmlspecialchars($client['username'] ?? ''); ?>" required>
                                    <div class="invalid-feedback" id="username_error"></div>
                                </div>
                            </div>
                            </div>
                            <div class="mb-3">
                            <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" name="contact_number" id="contact_number" 
                                   value="<?php echo htmlspecialchars($client['contact_number'] ?? ''); ?>" 
                                   placeholder="09XXXXXXXXX" maxlength="11" required>
                            <div class="invalid-feedback" id="contact_number_error"></div>
                            </div>
                            <div class="mb-3">
                            <label class="form-label">Address (Barangay) <span class="text-danger">*</span></label>
                            <select class="form-select" name="barangay" id="barangay" required>
                                <option value="">Select Barangay</option>
                                <option value="Abuanan" <?php echo ($client['barangay'] ?? '') == 'Abuanan' ? 'selected' : ''; ?>>Abuanan</option>
                                <option value="Alianza" <?php echo ($client['barangay'] ?? '') == 'Alianza' ? 'selected' : ''; ?>>Alianza</option>
                                <option value="Atipuluan" <?php echo ($client['barangay'] ?? '') == 'Atipuluan' ? 'selected' : ''; ?>>Atipuluan</option>
                                <option value="Bacong" <?php echo ($client['barangay'] ?? '') == 'Bacong' ? 'selected' : ''; ?>>Bacong</option>
                                <option value="Bagroy" <?php echo ($client['barangay'] ?? '') == 'Bagroy' ? 'selected' : ''; ?>>Bagroy</option>
                                <option value="Balingasag" <?php echo ($client['barangay'] ?? '') == 'Balingasag' ? 'selected' : ''; ?>>Balingasag</option>
                                <option value="Binubuhan" <?php echo ($client['barangay'] ?? '') == 'Binubuhan' ? 'selected' : ''; ?>>Binubuhan</option>
                                <option value="Busay" <?php echo ($client['barangay'] ?? '') == 'Busay' ? 'selected' : ''; ?>>Busay</option>
                                <option value="Calumangan" <?php echo ($client['barangay'] ?? '') == 'Calumangan' ? 'selected' : ''; ?>>Calumangan</option>
                                <option value="Caridad" <?php echo ($client['barangay'] ?? '') == 'Caridad' ? 'selected' : ''; ?>>Caridad</option>
                                <option value="Don Jorge Araneta" <?php echo ($client['barangay'] ?? '') == 'Don Jorge Araneta' ? 'selected' : ''; ?>>Don Jorge Araneta</option>
                                <option value="Dulao" <?php echo ($client['barangay'] ?? '') == 'Dulao' ? 'selected' : ''; ?>>Dulao</option>
                                <option value="Ilijan" <?php echo ($client['barangay'] ?? '') == 'Ilijan' ? 'selected' : ''; ?>>Ilijan</option>
                                <option value="Lag-asan" <?php echo ($client['barangay'] ?? '') == 'Lag-asan' ? 'selected' : ''; ?>>Lag-asan</option>
                                <option value="Ma-ao" <?php echo ($client['barangay'] ?? '') == 'Ma-ao' ? 'selected' : ''; ?>>Ma-ao</option>
                                <option value="Mailum" <?php echo ($client['barangay'] ?? '') == 'Mailum' ? 'selected' : ''; ?>>Mailum</option>
                                <option value="Malingin" <?php echo ($client['barangay'] ?? '') == 'Malingin' ? 'selected' : ''; ?>>Malingin</option>
                                <option value="Napoles" <?php echo ($client['barangay'] ?? '') == 'Napoles' ? 'selected' : ''; ?>>Napoles</option>
                                <option value="Pacol" <?php echo ($client['barangay'] ?? '') == 'Pacol' ? 'selected' : ''; ?>>Pacol</option>
                                <option value="Poblacion" <?php echo ($client['barangay'] ?? '') == 'Poblacion' ? 'selected' : ''; ?>>Poblacion</option>
                                <option value="Sagasa" <?php echo ($client['barangay'] ?? '') == 'Sagasa' ? 'selected' : ''; ?>>Sagasa</option>
                                <option value="Sampinit" <?php echo ($client['barangay'] ?? '') == 'Sampinit' ? 'selected' : ''; ?>>Sampinit</option>
                                <option value="Tabunan" <?php echo ($client['barangay'] ?? '') == 'Tabunan' ? 'selected' : ''; ?>>Tabunan</option>
                                <option value="Taloc" <?php echo ($client['barangay'] ?? '') == 'Taloc' ? 'selected' : ''; ?>>Taloc</option>
                            </select>
                            <div class="invalid-feedback" id="barangay_error"></div>
                            </div>
                            
                            <!-- Farm Location Section - Only show if client has disseminated animals -->
                            <?php if ($hasDisseminatedAnimals): ?>
                            <div class="mb-3">
                                <label class="form-label">Farm Location <span class="text-info">(Optional)</span></label>
                                <div class="mb-3">
                                    <div id="editFarmLocationMap" style="height: 300px; width: 100%; border: 1px solid #ddd; border-radius: 5px;"></div>
                                    <div class="form-text">
                                        <i class="fas fa-map-marker-alt me-1"></i>Click on the map to mark your farm location. This helps us provide better services.
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary btn-sm mt-2" id="getCurrentLocationEditBtn">
                                        <i class="fas fa-location-arrow"></i> Use Current Location
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm mt-2 ms-2" id="clearLocationEditBtn">
                                        <i class="fas fa-times"></i> Clear Location
                                    </button>
                                </div>
                                <!-- Hidden inputs for coordinates -->
                                <input type="hidden" name="latitude" id="edit_latitude" value="<?php echo htmlspecialchars($client['latitude'] ?? ''); ?>">
                                <input type="hidden" name="longitude" id="edit_longitude" value="<?php echo htmlspecialchars($client['longitude'] ?? ''); ?>">
                            </div>
                            <?php else: ?>
                            <div class="mb-3">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Farm Location:</strong> You can mark your farm location after you receive disseminated livestock or poultry from our office. This helps us provide better veterinary services and track animal distribution.
                                </div>
                            </div>
                            <?php endif; ?>
                        </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveInfoBtn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
                    </div>

    <!-- Edit Password Modal -->
    <div class="modal fade" id="editPasswordModal" tabindex="-1" aria-labelledby="editPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editPasswordModalLabel">
                        <i class="fas fa-key"></i> Change Password
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editPasswordForm">
                            <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <div class="password-input-group">
                                <input type="password" class="form-control" name="current_password" id="current_password" required>
                                <button type="button" class="password-toggle" onclick="togglePasswordVisibility('current_password', 'current_password_icon')">
                                    <i class="fas fa-eye" id="current_password_icon"></i>
                                </button>
                            </div>
                            </div>
                            <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <div class="password-input-group">
                                <input type="password" class="form-control" name="new_password" id="new_password" required>
                                <button type="button" class="password-toggle" onclick="togglePasswordVisibility('new_password', 'new_password_icon')">
                                    <i class="fas fa-eye" id="new_password_icon"></i>
                                </button>
                            </div>
                            </div>
                            <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <div class="password-input-group">
                                <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                                <button type="button" class="password-toggle" onclick="togglePasswordVisibility('confirm_password', 'confirm_password_icon')">
                                    <i class="fas fa-eye" id="confirm_password_icon"></i>
                                </button>
                            </div>
                            </div>
                        </form>
                    </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="savePasswordBtn">
                        <i class="fas fa-save"></i> Update Password
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="successModalLabel">
                        <i class="fas fa-check-circle"></i> Success
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h5 id="successMessage">Personal information updated successfully!</h5>
                    <p class="text-muted">Your changes have been saved.</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                        <i class="fas fa-check"></i> OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Warning Modal -->
    <div class="modal fade" id="warningModal" tabindex="-1" aria-labelledby="warningModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="warningModalLabel">
                        <i class="fas fa-exclamation-triangle"></i> Warning
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                    </div>
                    <h5 id="warningMessage">Please check your input</h5>
                    <p class="text-muted" id="warningDetails">There was an issue with your request.</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-warning" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Profile Picture Upload
        document.getElementById('profilePicture').addEventListener('change', function() {
            const formData = new FormData(document.getElementById('profilePictureForm'));
            
            fetch('client_update_profile_picture.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Silent upload - no alerts, just reload to show new picture
                window.location.reload();
            })
            .catch(error => {
                // Silent upload - no alerts, just reload to show new picture
                    window.location.reload();
            });
        });

        // Validation functions
        function validateFullName(fullName) {
            const nameParts = fullName.trim().split(' ');
            if (nameParts.length < 2) {
                return 'Full name must contain at least first name and last name';
            }
            if (nameParts.some(part => part.length < 2)) {
                return 'Each name part must be at least 2 characters long';
            }
            return null;
        }

        function validateContactNumber(contactNumber) {
            const phoneRegex = /^09\d{9}$/;
            if (!phoneRegex.test(contactNumber)) {
                return 'Contact number must start with 09 and be exactly 11 digits';
            }
            return null;
        }

        function validateUsername(username) {
            if (username.length < 3) {
                return 'Username must be at least 3 characters long';
            }
            if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                return 'Username can only contain letters, numbers, and underscores';
            }
            return null;
        }

        // Debounce function to limit API calls
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Check duplicate full name
        const checkFullNameDuplicate = debounce(async function(fullName) {
            if (fullName.length < 3) return; // Don't check if too short
            
            try {
                const response = await fetch(`check_duplicate_fullname.php?full_name=${encodeURIComponent(fullName)}`);
                const data = await response.json();
                
                if (data.exists) {
                    document.getElementById('full_name').classList.add('is-invalid');
                    document.getElementById('full_name_error').textContent = 'Full name already exists. Please choose a different name.';
                } else {
                    // Only remove invalid class if no other validation errors
                    const basicError = validateFullName(fullName);
                    if (!basicError) {
                        document.getElementById('full_name').classList.remove('is-invalid');
                        document.getElementById('full_name_error').textContent = '';
                    }
                }
            } catch (error) {
                console.error('Error checking full name:', error);
            }
        }, 500);

        // Check duplicate username
        const checkUsernameDuplicate = debounce(async function(username) {
            if (username.length < 3) return; // Don't check if too short
            
            try {
                const response = await fetch(`check_duplicate_username.php?username=${encodeURIComponent(username)}`);
                const data = await response.json();
                
                if (data.exists) {
                    document.getElementById('username').classList.add('is-invalid');
                    document.getElementById('username_error').textContent = 'Username already exists. Please choose a different username.';
                } else {
                    // Only remove invalid class if no other validation errors
                    const basicError = validateUsername(username);
                    if (!basicError) {
                        document.getElementById('username').classList.remove('is-invalid');
                        document.getElementById('username_error').textContent = '';
                    }
                }
            } catch (error) {
                console.error('Error checking username:', error);
            }
        }, 500);

        // Real-time validation while typing
        document.getElementById('full_name').addEventListener('input', function() {
            const value = this.value.trim();
            const error = validateFullName(value);
            
            if (value.length > 0 && error) {
                this.classList.add('is-invalid');
                document.getElementById('full_name_error').textContent = error;
            } else if (value.length > 0) {
                // Check for duplicates if basic validation passes
                checkFullNameDuplicate(value);
            } else {
                this.classList.remove('is-invalid');
                document.getElementById('full_name_error').textContent = '';
            }
        });

        document.getElementById('contact_number').addEventListener('input', function() {
            // Only allow numbers and limit to 11 digits
            this.value = this.value.replace(/[^0-9]/g, '').substring(0, 11);
            
            const error = validateContactNumber(this.value);
            if (this.value.length > 0 && error) {
                this.classList.add('is-invalid');
                document.getElementById('contact_number_error').textContent = error;
            } else {
                this.classList.remove('is-invalid');
                document.getElementById('contact_number_error').textContent = '';
            }
        });

        document.getElementById('username').addEventListener('input', function() {
            const value = this.value.trim();
            const error = validateUsername(value);
            
            if (value.length > 0 && error) {
                this.classList.add('is-invalid');
                document.getElementById('username_error').textContent = error;
            } else if (value.length > 0) {
                // Check for duplicates if basic validation passes
                checkUsernameDuplicate(value);
            } else {
                this.classList.remove('is-invalid');
                document.getElementById('username_error').textContent = '';
            }
        });

        // Also validate on blur for additional feedback
        document.getElementById('full_name').addEventListener('blur', function() {
            const error = validateFullName(this.value);
            if (error) {
                this.classList.add('is-invalid');
                document.getElementById('full_name_error').textContent = error;
                } else {
                this.classList.remove('is-invalid');
                document.getElementById('full_name_error').textContent = '';
            }
        });

        document.getElementById('username').addEventListener('blur', function() {
            const error = validateUsername(this.value);
            if (error) {
                this.classList.add('is-invalid');
                document.getElementById('username_error').textContent = error;
            } else {
                this.classList.remove('is-invalid');
                document.getElementById('username_error').textContent = '';
            }
        });

        // Edit Info Modal Save Button
        document.getElementById('saveInfoBtn').addEventListener('click', function() {
            // Clear previous validation errors
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');

            // Get form values
            const fullName = document.getElementById('full_name').value.trim();
            const username = document.getElementById('username').value.trim();
            const contactNumber = document.getElementById('contact_number').value.trim();
            const barangay = document.getElementById('barangay').value;

            let hasErrors = false;

            // Validate full name
            const fullNameError = validateFullName(fullName);
            if (fullNameError) {
                document.getElementById('full_name').classList.add('is-invalid');
                document.getElementById('full_name_error').textContent = fullNameError;
                hasErrors = true;
            }

            // Validate username
            const usernameError = validateUsername(username);
            if (usernameError) {
                document.getElementById('username').classList.add('is-invalid');
                document.getElementById('username_error').textContent = usernameError;
                hasErrors = true;
            }

            // Validate contact number
            const contactError = validateContactNumber(contactNumber);
            if (contactError) {
                document.getElementById('contact_number').classList.add('is-invalid');
                document.getElementById('contact_number_error').textContent = contactError;
                hasErrors = true;
            }

            // Validate barangay
            if (!barangay) {
                document.getElementById('barangay').classList.add('is-invalid');
                document.getElementById('barangay_error').textContent = 'Please select a barangay';
                hasErrors = true;
            }

            if (hasErrors) {
                return;
            }

            // Check for duplicates
            const formData = new URLSearchParams(new FormData(document.getElementById('editInfoForm')));
            
            fetch('client_update_personal_info.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: formData.toString()
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close the edit modal first
                    const editModal = bootstrap.Modal.getInstance(document.getElementById('editInfoModal'));
                    if (editModal) {
                        editModal.hide();
                    }
                    
                    // Show success modal after edit modal is closed
                    setTimeout(() => {
                        document.getElementById('successMessage').textContent = 'Personal information updated successfully!';
                        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                        successModal.show();
                        
                        // Reload page after success modal is closed
                        document.getElementById('successModal').addEventListener('hidden.bs.modal', function() {
                            location.reload();
                        });
                    }, 300); // Small delay to ensure edit modal is fully closed
                } else {
                    // Handle specific validation errors from server
                    if (data.error.includes('full name')) {
                        document.getElementById('full_name').classList.add('is-invalid');
                        document.getElementById('full_name_error').textContent = data.error;
                    } else if (data.error.includes('username')) {
                        document.getElementById('username').classList.add('is-invalid');
                        document.getElementById('username_error').textContent = data.error;
                    } else if (data.error.includes('contact')) {
                        document.getElementById('contact_number').classList.add('is-invalid');
                        document.getElementById('contact_number_error').textContent = data.error;
                } else {
                    alert('Error: ' + data.error);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating information');
            });
        });

        // Password visibility toggle function
        function togglePasswordVisibility(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const eyeIcon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        // Function to show warning modal
        function showWarningModal(title, message, details) {
            document.getElementById('warningMessage').textContent = title;
            document.getElementById('warningDetails').textContent = message;
            if (details) {
                document.getElementById('warningDetails').innerHTML = message + '<br><small class="text-muted">' + details + '</small>';
            }
            const warningModal = new bootstrap.Modal(document.getElementById('warningModal'));
            warningModal.show();
        }

        // Edit Password Modal Save Button
        document.getElementById('savePasswordBtn').addEventListener('click', function() {
            const formData = new FormData(document.getElementById('editPasswordForm'));
            const currentPassword = formData.get('current_password');
            const newPassword = formData.get('new_password');
            const confirmPassword = formData.get('confirm_password');
            
            // Validate new password and confirm password match
            if (newPassword !== confirmPassword) {
                showWarningModal(
                    'Password Mismatch',
                    'New password and confirm password do not match.',
                    'Please make sure both password fields contain the same password.'
                );
                return;
            }
            
            // Validate new password is different from current password
            if (currentPassword === newPassword) {
                showWarningModal(
                    'Same Password',
                    'New password cannot be the same as your current password.',
                    'Please choose a different password for security reasons.'
                );
                return;
            }
            
            fetch('client_update_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success modal
                    document.getElementById('successMessage').textContent = 'Password updated successfully!';
                    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    successModal.show();
                    
                    // Close edit modal and reset form
                    const editModal = bootstrap.Modal.getInstance(document.getElementById('editPasswordModal'));
                    editModal.hide();
                    document.getElementById('editPasswordForm').reset();
                    
                    // Reload page after success modal is closed
                    document.getElementById('successModal').addEventListener('hidden.bs.modal', function() {
                        location.reload();
                    });
                } else {
                    // Show warning modal for server errors
                    let errorTitle = 'Password Update Failed';
                    let errorMessage = data.error;
                    let errorDetails = '';
                    
                    if (data.error.includes('Current password is incorrect')) {
                        errorTitle = 'Incorrect Current Password';
                        errorMessage = 'The current password you entered is incorrect.';
                        errorDetails = 'Please verify your current password and try again.';
                    } else if (data.error.includes('All fields are required')) {
                        errorTitle = 'Missing Information';
                        errorMessage = 'Please fill in all password fields.';
                        errorDetails = 'Current password, new password, and confirm password are all required.';
                    } else if (data.error.includes('Failed to update password')) {
                        errorTitle = 'Update Failed';
                        errorMessage = 'Unable to update your password at this time.';
                        errorDetails = 'Please try again later or contact support if the problem persists.';
                    }
                    
                    showWarningModal(errorTitle, errorMessage, errorDetails);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showWarningModal(
                    'Connection Error',
                    'Unable to connect to the server.',
                    'Please check your internet connection and try again.'
                );
            });
        });
    </script>
    
    <!-- Leaflet JS for maps -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Map functionality for edit farm location -->
    <script>
        let editFarmMap;
        let editFarmMarker;
        let editSelectedLocation = null;

        // Initialize edit farm location map when modal is shown (only if client has disseminated animals)
        document.getElementById('editInfoModal').addEventListener('shown.bs.modal', function () {
            <?php if ($hasDisseminatedAnimals): ?>
            if (!editFarmMap) {
                // Initialize map centered on Bago City, Philippines
                editFarmMap = L.map('editFarmLocationMap').setView([10.5388, 122.8389], 13);
                
                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(editFarmMap);

                // Add click event to map
                editFarmMap.on('click', function(e) {
                    const lat = e.latlng.lat;
                    const lng = e.latlng.lng;
                    
                    // Remove existing marker
                    if (editFarmMarker) {
                        editFarmMap.removeLayer(editFarmMarker);
                    }
                    
                    // Add new marker
                    editFarmMarker = L.marker([lat, lng]).addTo(editFarmMap)
                        .bindPopup(`Farm Location<br>Lat: ${lat.toFixed(6)}<br>Lng: ${lng.toFixed(6)}`)
                        .openPopup();
                    
                    // Update hidden inputs
                    document.getElementById('edit_latitude').value = lat;
                    document.getElementById('edit_longitude').value = lng;
                    
                    editSelectedLocation = { lat: lat, lng: lng };
                });

                // Load existing location if available
                const existingLat = document.getElementById('edit_latitude').value;
                const existingLng = document.getElementById('edit_longitude').value;
                
                if (existingLat && existingLng && existingLat !== '' && existingLng !== '') {
                    const lat = parseFloat(existingLat);
                    const lng = parseFloat(existingLng);
                    
                    // Add marker at existing location
                    editFarmMarker = L.marker([lat, lng]).addTo(editFarmMap)
                        .bindPopup(`Current Farm Location<br>Lat: ${lat.toFixed(6)}<br>Lng: ${lng.toFixed(6)}`)
                        .openPopup();
                    
                    // Center map on existing location
                    editFarmMap.setView([lat, lng], 15);
                    
                    editSelectedLocation = { lat: lat, lng: lng };
                }
            }
            <?php endif; ?>
        });

        // Get current location button for edit (only if client has disseminated animals)
        <?php if ($hasDisseminatedAnimals): ?>
        document.getElementById('getCurrentLocationEditBtn').addEventListener('click', function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    // Remove existing marker
                    if (editFarmMarker) {
                        editFarmMap.removeLayer(editFarmMarker);
                    }
                    
                    // Add new marker at current location
                    editFarmMarker = L.marker([lat, lng]).addTo(editFarmMap)
                        .bindPopup(`Current Location<br>Lat: ${lat.toFixed(6)}<br>Lng: ${lng.toFixed(6)}`)
                        .openPopup();
                    
                    // Update hidden inputs
                    document.getElementById('edit_latitude').value = lat;
                    document.getElementById('edit_longitude').value = lng;
                    
                    // Center map on current location
                    editFarmMap.setView([lat, lng], 15);
                    
                    editSelectedLocation = { lat: lat, lng: lng };
                }, function(error) {
                    alert('Unable to get your current location. Please click on the map to select your farm location.');
                });
            } else {
                alert('Geolocation is not supported by this browser. Please click on the map to select your farm location.');
            }
        });
        <?php endif; ?>

        // Clear location button for edit (only if client has disseminated animals)
        <?php if ($hasDisseminatedAnimals): ?>
        document.getElementById('clearLocationEditBtn').addEventListener('click', function() {
            if (editFarmMarker) {
                editFarmMap.removeLayer(editFarmMarker);
                editFarmMarker = null;
            }
            
            // Clear hidden inputs
            document.getElementById('edit_latitude').value = '';
            document.getElementById('edit_longitude').value = '';
            
            editSelectedLocation = null;
        });
        <?php endif; ?>
    </script>
</body>
</html>