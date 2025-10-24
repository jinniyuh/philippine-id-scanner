<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get admin information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");

// Check if prepare failed
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

// Barangay list
$barangays = [
    'Abuanan','Alianza','Atipuluan','Bacong-Montilla','Bagroy','Balingasag','Binubuhan','Busay','Calumangan','Caridad',
    'Don Jorge L. Araneta','Dulao','Ilijan','Lag-Asan','Ma-ao Barrio','Mailum','Malingin','Napoles','Pacol','Poblacion',
    'Sagasa','Sampinit','Tabunan','Taloc'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Bago City Veterinary Office</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background-color: #6c63ff;
            font-family: Arial, sans-serif;
        }
        
        .container-fluid {
            padding-left: 0;
            padding-right: 0;
            overflow-x: hidden;
        }
         
        .main-content {
            background: white;
            margin: 20px;
            margin-left: 312px;
            padding: 0 25px 25px 25px;
            border-radius: 10px;
            min-height: 600px;
            height: calc(100vh - 40px);
            overflow-y: auto;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .wrapper {
            display: flex;
            align-items: flex-start;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: calc(100% + 50px);
            min-height: 80px;
            margin: 0 -25px 0 -25px;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            padding: 10px 25px 0 25px;
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
        }
        
        .admin-header h2 {
            margin: 0;
            font-weight: bold;
        }
        
        /* Minimize and style scrollbar for main-content */
        .main-content::-webkit-scrollbar {
            width: 8px;
            background: transparent;
        }
        
        .main-content::-webkit-scrollbar-thumb {
            background: #bdbdbd;
            border-radius: 8px;
        }
        
        .main-content::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .main-content {
            scrollbar-width: thin;
            scrollbar-color: #bdbdbd transparent;
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
            border: 4px solid #6c63ff;
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
        
        /* Mobile responsive styles for profile content */
        @media (max-width: 768px) {
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
        
        @media (max-width: 576px) {
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
        
        /* Success modal styling */
        .success-modal .modal-header {
            background-color: #6c63ff;
            color: white;
            border-bottom: none;
        }
        
        .success-modal .modal-body {
            text-align: center;
            padding: 1.5rem;
        }
        
        .success-modal .modal-body i {
            font-size: 3rem;
            color: #6c63ff;
            margin-bottom: 0.75rem;
        }
        
        .success-modal .modal-title {
            font-weight: 600;
        }
        
        .success-modal .modal-dialog {
            max-width: 400px;
        }
        
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="wrapper">
            <!-- Sidebar -->
            <div class="sidebar">
                <?php include 'includes/admin_sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <div class="admin-header">
                    <h2>My Profile</h2>
                </div>
                
                <div class="profile-section">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo isset($admin['profile_photo']) ? htmlspecialchars($admin['profile_photo']) : 'assets/images/default-profile.jpg'; ?>"
                                        alt="Profile Picture" class="profile-image">
                            <div class="ms-3 profile-info">
                                <h4><?php echo htmlspecialchars($admin['name'] ?? ''); ?></h4>
                                <span class="status-badge">Active Account</span>
                                <p class="registration-date">Registered: <?php echo date('F j, Y', strtotime($admin['created_at'])); ?></p>
                            <form id="profilePictureForm" enctype="multipart/form-data">
                                <input type="file" id="profilePicture" name="profile_photo" accept="image/*" style="display: none;">
                                    <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('profilePicture').click()">
                                    <i class="fas fa-camera"></i> Change Picture
                                </button>
                            </form>
                            </div>
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
                                    <label>Username:</label>
                                    <span><?php echo htmlspecialchars($admin['username'] ?? 'Not provided'); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Address:</label>
                                    <span><?php echo htmlspecialchars($admin['address'] ?? 'Not provided'); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Contact Number:</label>
                                    <span><?php echo htmlspecialchars($admin['contact_number'] ?? 'Not provided'); ?></span>
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
                                    $lastChanged = isset($admin['password_changed_at']) ? $admin['password_changed_at'] : $admin['created_at'];
                                    echo date('F j, Y', strtotime($lastChanged)); 
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
                <div class="modal-header">
                    <h5 class="modal-title" id="editInfoModalLabel">
                        <i class="fas fa-edit"></i> Edit Personal Information
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editInfoForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" id="name" 
                                           value="<?php echo htmlspecialchars($admin['name'] ?? ''); ?>" 
                                           placeholder="Full Name" required>
                                    <div class="invalid-feedback" id="name_error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Username <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="username" id="username" 
                                           value="<?php echo htmlspecialchars($admin['username'] ?? ''); ?>" 
                                           placeholder="Username" required>
                                    <div class="invalid-feedback" id="username_error"></div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address (Barangay) <span class="text-danger">*</span></label>
                            <select class="form-select" name="address" id="address" required>
                                <option value="">Select Barangay</option>
                                <?php foreach ($barangays as $barangay): ?>
                                    <option value="<?php echo htmlspecialchars($barangay); ?>" 
                                            <?php echo ($admin['address'] ?? '') === $barangay ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($barangay); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback" id="address_error"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" name="contact_number" id="contact_number" 
                                   value="<?php echo htmlspecialchars($admin['contact_number'] ?? ''); ?>" 
                                   placeholder="09XXXXXXXXX" maxlength="11" required>
                            <div class="invalid-feedback" id="contact_number_error"></div>
                        </div>
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
                <div class="modal-header" style="background-color: #6c63ff; color: white;">
                    <h5 class="modal-title" id="editPasswordModalLabel">
                        <i class="fas fa-key"></i> Change Password
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editPasswordForm">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="current_password" id="current_password" required>
                                <button class="btn btn-outline-secondary" type="button" id="toggleCurrentPassword">
                                    <i class="fas fa-eye" id="currentPasswordIcon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="new_password" id="new_password" required>
                                <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                                    <i class="fas fa-eye" id="newPasswordIcon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="fas fa-eye" id="confirmPasswordIcon"></i>
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
    <div class="modal fade success-modal" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">Success!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <i class="fas fa-check-circle"></i>
                    <h4 id="successMessage">Information Updated Successfully</h4>
                    <p>The information has been updated in the system.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="logoutModalLabel">
                        <i class="fas fa-sign-out-alt"></i> Confirm Logout
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-question-circle text-warning" style="font-size: 3rem;"></i>
                    </div>
                    <h5>Are you sure you want to logout?</h5>
                    <p class="text-muted">You will need to login again to access the system.</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <a href="logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
</script>
<script>
// Profile Picture Upload
document.getElementById('profilePicture').addEventListener('change', function() {
    const formData = new FormData(document.getElementById('profilePictureForm'));
    
    fetch('admin_update_profile_picture.php', {
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
        function validateName(name) {
            if (name.trim().length < 2) {
                return 'Name must be at least 2 characters long';
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

        function validateAddress(address) {
            if (!address || address.trim() === '') {
                return 'Please select a barangay';
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

        // Real-time validation while typing
        document.getElementById('name').addEventListener('input', function() {
            const value = this.value.trim();
            const error = validateName(value);
            
            if (value.length > 0 && error) {
                this.classList.add('is-invalid');
                document.getElementById('name_error').textContent = error;
            } else {
                this.classList.remove('is-invalid');
                document.getElementById('name_error').textContent = '';
            }
        });


        document.getElementById('username').addEventListener('input', function() {
            const value = this.value.trim();
            const error = validateUsername(value);
            
            if (value.length > 0 && error) {
                this.classList.add('is-invalid');
                document.getElementById('username_error').textContent = error;
            } else {
                this.classList.remove('is-invalid');
                document.getElementById('username_error').textContent = '';
            }
        });

        document.getElementById('address').addEventListener('change', function() {
            const value = this.value.trim();
            const error = validateAddress(value);
            
            if (error) {
                this.classList.add('is-invalid');
                document.getElementById('address_error').textContent = error;
            } else {
                this.classList.remove('is-invalid');
                document.getElementById('address_error').textContent = '';
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

        // Edit Info Modal Save Button
        document.getElementById('saveInfoBtn').addEventListener('click', function() {
            // Clear previous validation errors
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');

            // Get form values
            const name = document.getElementById('name').value.trim();
            const username = document.getElementById('username').value.trim();
            const address = document.getElementById('address').value.trim();
            const contactNumber = document.getElementById('contact_number').value.trim();

            let hasErrors = false;

            // Validate name
            const nameError = validateName(name);
            if (nameError) {
                document.getElementById('name').classList.add('is-invalid');
                document.getElementById('name_error').textContent = nameError;
                hasErrors = true;
            }

            // Validate username
            const usernameError = validateUsername(username);
            if (usernameError) {
                document.getElementById('username').classList.add('is-invalid');
                document.getElementById('username_error').textContent = usernameError;
                hasErrors = true;
            }

            // Validate address
            const addressError = validateAddress(address);
            if (addressError) {
                document.getElementById('address').classList.add('is-invalid');
                document.getElementById('address_error').textContent = addressError;
                hasErrors = true;
            }

            // Validate contact number
            const contactError = validateContactNumber(contactNumber);
            if (contactError) {
                document.getElementById('contact_number').classList.add('is-invalid');
                document.getElementById('contact_number_error').textContent = contactError;
                hasErrors = true;
            }

            if (hasErrors) {
                return;
            }

            // Submit form
            const formData = new URLSearchParams(new FormData(document.getElementById('editInfoForm')));

    fetch('admin_update_personal_info.php', {
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
                    editModal.hide();
                    
                    // Show success modal
                    document.getElementById('successMessage').textContent = 'Personal Information Updated Successfully';
                    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    successModal.show();
                    
                    // Reload page after modal is closed
                    document.getElementById('successModal').addEventListener('hidden.bs.modal', function() {
            location.reload();
                    });
        } else {
            alert('Error: ' + data.error);
        }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating information');
    });
});

        // Edit Password Modal Save Button
        document.getElementById('savePasswordBtn').addEventListener('click', function() {
            const formData = new FormData(document.getElementById('editPasswordForm'));

    if (formData.get('new_password') !== formData.get('confirm_password')) {
        alert('New passwords do not match!');
        return;
    }

    fetch('admin_update_password.php', {
        method: 'POST',
        body: formData
    })
            .then(response => response.json())
    .then(data => {
        if (data.success) {
                    // Show success modal
                    document.getElementById('successMessage').textContent = 'Password Updated Successfully';
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
            alert('Error updating password: ' + data.error);
        }
    });
});

// Password visibility toggle functionality
document.getElementById('toggleCurrentPassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('current_password');
    const icon = document.getElementById('currentPasswordIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

document.getElementById('toggleNewPassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('new_password');
    const icon = document.getElementById('newPasswordIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('confirm_password');
    const icon = document.getElementById('confirmPasswordIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});
</script>
</body>
</html>