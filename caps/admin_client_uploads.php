<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle notification click parameters
$auto_open = isset($_GET['auto_open']) ? $_GET['auto_open'] : false;
$upload_type = isset($_GET['type']) ? $_GET['type'] : '';
$species = isset($_GET['species']) ? $_GET['species'] : '';
$animal_type = isset($_GET['animal_type']) ? $_GET['animal_type'] : '';
$client_name = isset($_GET['client_name']) ? $_GET['client_name'] : '';

// If auto_open is set, find the specific upload to highlight
$target_upload = null; 
if ($auto_open && $upload_type === 'animal' && !empty($species)) {
    // Find the most recent upload matching the criteria
    $target_query = $conn->prepare("SELECT ap.photo_id, ap.photo_path, ap.uploaded_at, 
                                   lp.species, lp.animal_type, lp.animal_id, 
                                   c.full_name as client_name, c.client_id
                                   FROM animal_photos ap 
                                   JOIN livestock_poultry lp ON ap.animal_id = lp.animal_id 
                                   JOIN clients c ON lp.client_id = c.client_id 
                                   WHERE lp.species = ? AND lp.animal_type = ?
                                   " . (!empty($client_name) ? "AND c.full_name = ?" : "") . "
                                   ORDER BY ap.uploaded_at DESC LIMIT 1");
    
    if (!empty($client_name)) {
        $target_query->bind_param("sss", $species, $animal_type, $client_name);
    } else {
        $target_query->bind_param("ss", $species, $animal_type);
    }
    
    $target_query->execute();
    $target_result = $target_query->get_result();
    if ($target_result->num_rows > 0) {
        $target_upload = $target_result->fetch_assoc();
    }
}

// Get all animal photos grouped by client (all statuses)
$animal_photos_query = $conn->prepare("SELECT ap.photo_id, ap.photo_path, ap.uploaded_at, ap.status,
                                     lp.species, lp.animal_type, lp.animal_id, 
                                     c.full_name as client_name, c.client_id,
                                     COUNT(*) OVER (PARTITION BY c.client_id) as client_photo_count
                                     FROM animal_photos ap 
                                     JOIN livestock_poultry lp ON ap.animal_id = lp.animal_id 
                                     JOIN clients c ON lp.client_id = c.client_id 
                                     ORDER BY c.full_name, ap.uploaded_at DESC");
$animal_photos_query->execute();
$animal_photos_result = $animal_photos_query->get_result();

// Group photos by client
$client_albums = [];
while ($photo = $animal_photos_result->fetch_assoc()) {
    $client_id = $photo['client_id'];
    if (!isset($client_albums[$client_id])) {
        $client_albums[$client_id] = [
            'client_name' => $photo['client_name'],
            'client_id' => $client_id,
            'photos' => [],
            'total_photos' => $photo['client_photo_count']
        ];
    }
    $client_albums[$client_id]['photos'][] = $photo;
}

// Get profile pictures with client information
$profile_query = $conn->prepare("SELECT u.profile_photo, u.created_at, u.name, c.client_id, c.full_name 
                              FROM users u
                              JOIN clients c ON u.name = c.full_name
                              WHERE u.role = 'client' AND u.profile_photo IS NOT NULL
                              ORDER BY u.created_at DESC");
$profile_query->execute();
$profile_result = $profile_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Uploads - Bago City Veterinary Office</title>
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
        .admin-profile {
            display: flex;
            align-items: center;
        }
        .admin-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        /* Avatar Dropdown Button */
        .avatar-dropdown-btn {
            background: none;
            border: none;
            padding: 0;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .avatar-dropdown-btn:hover {
            transform: scale(1.05);
        }
        
        .avatar-dropdown-btn:focus {
            box-shadow: none;
        }
        
        .avatar-container {
            position: relative;
            display: inline-block;
        }
        
        .avatar-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            transition: all 0.3s ease;
        }
        
        .dropdown-indicator {
            position: absolute;
            bottom: -2px;
            right: 8px;
            background: #6c63ff;
            color: white;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            border: 2px solid white;
        }
        
        .admin-name {
            margin-left: 5px;
            color: #333;
            text-decoration: none;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .empty-state i {
            font-size: 48px;
            color: #6c63ff;
            margin-bottom: 20px;
        }
        
        .empty-state h4 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .empty-state p {
            color: #6c757d;
        }
        
        /* Minimize and style scrollbar */
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
        
        /* Client list styles */
        .client-list {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .client-list-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            transition: background-color 0.2s ease;
        }
        
        .client-list-item:last-child {
            border-bottom: none;
        }
        
        .client-list-item:hover {
            background-color: #f8f9fa;
        }
        
        .client-list-item i {
            color: #6c63ff;
            margin-right: 15px;
            font-size: 18px;
        }
        
        .client-list-item .client-name {
            font-size: 16px;
            font-weight: 500;
            color: #333;
        }
        
        .client-list-item .photo-count {
            margin-left: auto;
            background: #6c63ff;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        /* Client photos modal styles */
        .client-photos-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        
        .client-photos-grid.single-photo {
            grid-template-columns: 1fr;
            max-width: 400px;
            margin: 15px auto 0;
        }
        
        .client-photos-grid.few-photos {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            max-width: 800px;
            margin: 15px auto 0;
        }
        
        .client-photo-item {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }
        
        .client-photo-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .client-photo-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            display: block;
        }
        
        .client-photo-item .info {
            padding: 10px;
            background-color: #f8f9fa;
        }
        
        .client-photo-item .info p {
            margin: 5px 0;
            font-size: 12px;
        }
        
        .client-photo-item .date {
            color: #6c757d;
            font-size: 11px;
        }
        
        .status-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            color: white;
        }
        
        .status-pending {
            background-color: #ffc107;
        }
        
        .status-approved {
            background-color: #28a745;
        }
        
        .status-rejected {
            background-color: #dc3545;
        }
        
                 /* Ensure modal appears in front */
         .modal-backdrop {
             z-index: 9998 !important;
         }
         
         .modal {
             z-index: 9999 !important;
         }
         
         /* Photo details modal should be on top */
         #uploadReviewModal {
             z-index: 10000 !important;
         }
         
         #uploadReviewModal + .modal-backdrop {
             z-index: 9999 !important;
         }
         
         #rejectionReasonModal {
             z-index: 10001 !important;
         }
         
         #rejectionReasonModal + .modal-backdrop {
             z-index: 10000 !important;
         }
         
         /* Activity Logs Dropdown Styles */
         .activity-logs-dropdown {
             width: 350px;
             max-height: 400px;
             overflow-y: auto;
             padding: 0;
         }
         .activity-logs-header {
             padding: 12px 16px;
             background: #f8f9fa;
             border-bottom: 1px solid #e9ecef;
             font-weight: 600;
             color: #495057;
             position: sticky;
             top: 0;
             z-index: 1;
         }
         .activity-item {
             display: flex;
             align-items: flex-start;
             padding: 12px 16px;
             border-bottom: 1px solid #f5f5f5;
             transition: all 0.3s ease;
         }
         .activity-item:last-child {
             border-bottom: none;
         }
         .activity-item:hover {
             background-color: #fafbff;
         }
         .activity-avatar {
             position: relative;
             margin-right: 10px;
             flex-shrink: 0;
         }
         .activity-avatar .avatar-img {
             width: 30px;
             height: 30px;
             border-radius: 50%;
             display: flex;
             align-items: center;
             justify-content: center;
             font-weight: 600;
             font-size: 0.8rem;
             color: white;
         }
         .activity-icon {
             position: absolute;
             bottom: -2px;
             right: -2px;
             width: 14px;
             height: 14px;
             border-radius: 50%;
             display: flex;
             align-items: center;
             justify-content: center;
             font-size: 7px;
             border: 2px solid white;
         }
         .activity-icon.icon-login { background: #28a745; color: white; }
         .activity-icon.icon-logout { background: #dc3545; color: white; }
         .activity-icon.icon-add { background: #17a2b8; color: white; }
         .activity-icon.icon-edit { background: #ffc107; color: white; }
         .activity-icon.icon-delete { background: #dc3545; color: white; }
         .activity-icon.icon-view { background: #6c63ff; color: white; }
         .activity-icon.icon-default { background: #6c757d; color: white; }
         .activity-content {
             flex: 1;
         }
         .activity-text {
             margin: 0;
             font-size: 13px;
             line-height: 1.3;
             color: #333;
         }
         .activity-time {
             font-size: 11px;
             color: #6c757d;
             margin-top: 2px;
         }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="wrapper">
            <!-- Sidebar -->
            <?php include 'includes/admin_sidebar.php'; ?>
            
            <!-- Main Content -->
            <div class="main-content">
                <div class="admin-header">
                    <h2> Client Uploads</h2>
                    <div class="admin-profile">
                        <!-- Avatar with Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-link avatar-dropdown-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="avatar-container">
                                    <img src="assets/default-avatar.png" alt="Admin Profile" class="avatar-img">
                                    <div class="dropdown-indicator"><i class="fas fa-chevron-down"></i></div>
                                </div>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" id="avatarDropdown">
                                <li><a class="dropdown-item" href="#" id="viewActivityLogs"><i class="fas fa-history me-2"></i>View Activity Logs</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Client List -->
                <div class="client-list">
                    <?php if (!empty($client_albums)): ?>
                        <?php foreach ($client_albums as $client_id => $album): ?>
                            <div class="client-list-item" onclick="showClientPhotos(<?php echo $client_id; ?>)" data-client-name="<?php echo htmlspecialchars($album['client_name']); ?>" style="cursor: pointer;">
                                <i class="fas fa-user-circle"></i>
                                <span class="client-name"><?php echo htmlspecialchars($album['client_name']); ?></span>
                                <span class="photo-count"><?php echo $album['total_photos']; ?> photo<?php echo $album['total_photos'] > 1 ? 's' : ''; ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <h4>No Clients with Uploads</h4>
                            <p>No clients have uploaded photos at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Upload Review Modal -->
    <div class="modal fade" id="uploadReviewModal" tabindex="-1" aria-labelledby="uploadReviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadReviewModalLabel">Review Upload</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                                 <div class="modal-body" id="modalBodyContent">
                     <div class="text-center mb-3">
                         <img id="modalUploadImage" src="" alt="Upload Image" class="img-fluid rounded" style="max-height: 400px; max-width: 100%; object-fit: contain;">
                     </div>
                     <div id="photoDetails" class="mt-3" style="display: none;">
                         <div class="row">
                             <div class="col-md-6">
                                 <p><strong>Client:</strong> <span id="modalClientName">-</span></p>
                                 <p><strong>Animal Type:</strong> <span id="modalAnimalType">-</span></p>
                             </div>
                             <div class="col-md-6">
                                 <p><strong>Species:</strong> <span id="modalSpecies">-</span></p>
                                 <p><strong>Upload Date:</strong> <span id="modalUploadDate">-</span></p>
                             </div>
                         </div>
                         <div id="rejectionReasonSection" class="mt-3" style="display: none;">
                             <div class="alert alert-danger">
                                 <strong>Rejection Reason:</strong>
                                 <p id="modalRejectionReason" class="mb-0 mt-1"></p>
                             </div>
                         </div>
                     </div>
                 </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="approveBtn">
                        <i class="fas fa-check"></i> Approve
                    </button>
                    <button type="button" class="btn btn-danger" id="rejectBtn">
                        <i class="fas fa-times"></i> Reject
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Rejection Reason Modal -->
    <div class="modal fade" id="rejectionReasonModal" tabindex="-1" aria-labelledby="rejectionReasonModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header" style="background-color: #6c63ff; color: white;">
                    <h5 class="modal-title" id="rejectionReasonModalLabel">
                        <i class="fas fa-times-circle me-2"></i>Reject Photo
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                        <h6 class="mt-3">Please provide a reason for rejection</h6>
                        <p class="text-muted">This will help the client understand why their photo was rejected.</p>
                    </div>
                    <div class="mb-3">
                        <label for="rejectionReasonInput" class="form-label">Rejection Reason:</label>
                        <textarea class="form-control" id="rejectionReasonInput" rows="4" placeholder="Enter the reason for rejection..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn" id="confirmRejectBtn" style="background-color: #6c63ff; color: white; border-color: #6c63ff;">
                        <i class="fas fa-times"></i> Reject Photo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow">
                <div class="modal-body text-center p-4">
                    <i id="modalIcon" style="font-size: 2rem;"></i>
                    <h6 id="modalTitle" class="mb-3"></h6>
                    <p id="modalMessage" class="text-muted mb-4"></p>
                    <button type="button" class="btn px-4" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Client Photos Modal -->
    <div class="modal fade" id="clientPhotosModal" tabindex="-1" aria-labelledby="clientPhotosModalLabel" aria-hidden="true" style="z-index: 9999;">
        <div class="modal-dialog" id="clientPhotosModalDialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="clientPhotosModalLabel">
                        <i class="fas fa-user-circle me-2"></i><span id="modalClientName">Client Photos</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="clientPhotosContainer">
                        <!-- Photos will be loaded here via AJAX -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="fix_buttons.js"></script>
    <script>
        // Auto-open modal if accessed from notification
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($auto_open && $target_upload): ?>
                // Populate modal with upload data
                document.getElementById('modalUploadImage').src = '<?php echo htmlspecialchars($target_upload['photo_path']); ?>';
                document.getElementById('modalClientName').textContent = '<?php echo htmlspecialchars($target_upload['client_name']); ?>';
                document.getElementById('modalAnimalType').textContent = '<?php echo htmlspecialchars($target_upload['animal_type']); ?>';
                document.getElementById('modalSpecies').textContent = '<?php echo htmlspecialchars($target_upload['species']); ?>';
                document.getElementById('modalUploadDate').textContent = new Date('<?php echo $target_upload['uploaded_at']; ?>').toLocaleDateString();
                
                // Store upload ID for approve/reject actions
                window.currentUploadId = <?php echo $target_upload['photo_id']; ?>;
                
                // Show the modal
                var uploadModal = new bootstrap.Modal(document.getElementById('uploadReviewModal'));
                uploadModal.show();
            <?php endif; ?>
            
            // Add event listeners after a short delay to ensure DOM is fully loaded
            setTimeout(function() {
                // Handle reject button click
                var rejectBtn = document.getElementById('rejectBtn');
                if (rejectBtn) {
                    rejectBtn.addEventListener('click', function() {
                        console.log('Reject button clicked, currentUploadId:', window.currentUploadId);
                        if (window.currentUploadId) {
                            // Show rejection reason modal instead of prompt
                            const rejectionModal = new bootstrap.Modal(document.getElementById('rejectionReasonModal'));
                            rejectionModal.show();
                        } else {
                            console.error('No currentUploadId set');
                        }
                    });
                } else {
                    console.error('Reject button not found');
                }
                
                // Handle confirm reject button click
                var confirmRejectBtn = document.getElementById('confirmRejectBtn');
                if (confirmRejectBtn) {
                    confirmRejectBtn.addEventListener('click', function() {
                        const rejectionReason = document.getElementById('rejectionReasonInput').value.trim();
                        if (!rejectionReason) {
                            alert('Please provide a reason for rejection.');
                            return;
                        }
                        
                        // Close rejection modal
                        const rejectionModal = bootstrap.Modal.getInstance(document.getElementById('rejectionReasonModal'));
                        rejectionModal.hide();
                        
                        // Process the rejection
                        processUploadAction('reject', window.currentUploadId, rejectionReason);
                    });
                }
                
                // Clear rejection reason input when modal is closed
                document.getElementById('rejectionReasonModal').addEventListener('hidden.bs.modal', function() {
                    document.getElementById('rejectionReasonInput').value = '';
                });
                
                // Handle approve button click
                var approveBtn = document.getElementById('approveBtn');
                if (approveBtn) {
                    approveBtn.addEventListener('click', function() {
                        console.log('Approve button clicked, currentUploadId:', window.currentUploadId);
                        if (window.currentUploadId) {
                            processUploadAction('approve', window.currentUploadId);
                        } else {
                            console.error('No currentUploadId set');
                        }
                    });
                } else {
                    console.error('Approve button not found');
                }
            }, 100);
        });
        
        function processUploadAction(action, uploadId, rejectionReason = null) {
            // Show loading state
            var approveBtn = document.getElementById('approveBtn');
            var rejectBtn = document.getElementById('rejectBtn');
            
            if (action === 'approve') {
                approveBtn.disabled = true;
                approveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            } else {
                rejectBtn.disabled = true;
                rejectBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            }
            
            // Send AJAX request
            var formData = new FormData();
            formData.append('action', action);
            formData.append('photo_id', uploadId);
            
            // Add rejection reason if rejecting
            if (action === 'reject' && rejectionReason) {
                formData.append('rejection_reason', rejectionReason);
            }
            
            fetch('process_upload_action.php', {
                method: 'POST',
                body: formData
            })
            .then(async (response) => {
                const raw = await response.text();
                let data;
                try { data = JSON.parse(raw); } catch (e) { data = null; }
                if (!response.ok) {
                    const msg = data && data.message ? data.message : (raw || 'Server returned an error.');
                    throw new Error(msg);
                }
                if (!data) {
                    throw new Error('Invalid server response.');
                }
                return data;
            })
            .then(data => {
                // Hide upload review modal
                var uploadModal = bootstrap.Modal.getInstance(document.getElementById('uploadReviewModal'));
                uploadModal.hide();
                
                // Show success/error message
                var msg = data.message || (data.error ? String(data.error) : '');
                showMessage(data.success, msg);
                
                // Reload page after a short delay if successful
                if (data.success) {
                    setTimeout(() => {
                        window.location.href = 'admin_client_uploads.php';
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage(false, error.message || 'An error occurred while processing the request.');
            })
            .finally(() => {
                // Reset button states
                approveBtn.disabled = false;
                approveBtn.innerHTML = '<i class="fas fa-check"></i> Approve';
                rejectBtn.disabled = false;
                rejectBtn.innerHTML = '<i class="fas fa-times"></i> Reject';
            });
        }
        
        function showMessage(success, message) {
            var modalIcon = document.getElementById('modalIcon');
            var modalTitle = document.getElementById('modalTitle');
            var modalMessage = document.getElementById('modalMessage');
            var modalBtn = document.querySelector('#messageModal .btn');
            
            if (success) {
                modalIcon.className = 'fas fa-check-circle text-success';
                modalTitle.textContent = 'Success';
                modalBtn.className = 'btn btn-success px-4';
            } else {
                modalIcon.className = 'fas fa-exclamation-circle text-danger';
                modalTitle.textContent = 'Error';
                modalBtn.className = 'btn btn-danger px-4';
            }
            
            modalMessage.textContent = message;
            
            var messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
            messageModal.show();
        }
        
        // Function to open upload review modal manually
        function openUploadReviewModal(photoId, photoPath, clientName, animalType, species, uploadedAt) {
            // Populate modal with upload data
            document.getElementById('modalUploadImage').src = photoPath;
            document.getElementById('modalClientName').textContent = clientName;
            document.getElementById('modalAnimalType').textContent = animalType;
            document.getElementById('modalSpecies').textContent = species;
            document.getElementById('modalUploadDate').textContent = new Date(uploadedAt).toLocaleDateString();
            
            // Store upload ID for approve/reject actions
            window.currentUploadId = photoId;
            
            // Show the modal
            var uploadModal = new bootstrap.Modal(document.getElementById('uploadReviewModal'));
            uploadModal.show();
        }
        
        // Function to show client photos
        function showClientPhotos(clientId) {
            // Get client name from the clicked element's data attribute
            const clickedElement = event.target.closest('.client-list-item');
            const clientName = clickedElement.getAttribute('data-client-name');
            
            console.log('showClientPhotos called with clientId:', clientId, 'clientName:', clientName);
            
            // Update modal title
            document.getElementById('modalClientName').textContent = clientName + "'s Photos";
            
            // Show loading state
            document.getElementById('clientPhotosContainer').innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2">Loading photos...</p></div>';
            
            // Show the modal
            var clientModal = new bootstrap.Modal(document.getElementById('clientPhotosModal'), {
                backdrop: 'static',
                keyboard: false
            });
            clientModal.show();
            
            // Ensure modal is in front
            document.getElementById('clientPhotosModal').style.zIndex = '9999';
            
            // Fetch client photos via AJAX
            const url = 'get_admin_client_photos.php?client_id=' + clientId;
            console.log('Fetching from URL:', url);
            
            fetch(url)
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    
                    if (!response.ok) {
                        throw new Error('HTTP error! status: ' + response.status);
                    }
                    
                    return response.text().then(text => {
                        console.log('Raw response text:', text);
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            throw new Error('Invalid JSON response: ' + text);
                        }
                    });
                })
                .then(data => {
                    console.log('Response data:', data);
                    if (data.success) {
                        displayClientPhotos(data.photos);
                    } else {
                        document.getElementById('clientPhotosContainer').innerHTML = '<div class="text-center text-muted"><i class="fas fa-exclamation-circle fa-2x"></i><p class="mt-2">' + (data.message || 'Failed to load photos') + '</p></div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('clientPhotosContainer').innerHTML = '<div class="text-center text-muted"><i class="fas fa-exclamation-circle fa-2x"></i><p class="mt-2">Error loading photos: ' + error.message + '</p></div>';
                });
        }
        
        // Function to display client photos in the modal
        function displayClientPhotos(photos) {
            const container = document.getElementById('clientPhotosContainer');
            const modalDialog = document.getElementById('clientPhotosModalDialog');
            
            if (photos.length === 0) {
                container.innerHTML = '<div class="text-center text-muted"><i class="fas fa-camera fa-2x"></i><p class="mt-2">No photos found for this client</p></div>';
                return;
            }
            
            // Adjust modal size based on number of photos
            if (photos.length === 1) {
                modalDialog.className = 'modal-dialog modal-sm';
            } else if (photos.length <= 4) {
                modalDialog.className = 'modal-dialog modal-md';
            } else {
                modalDialog.className = 'modal-dialog modal-xl';
            }
            
            // Determine grid class based on number of photos
            let gridClass = 'client-photos-grid';
            if (photos.length === 1) {
                gridClass += ' single-photo';
            } else if (photos.length <= 4) {
                gridClass += ' few-photos';
            }
            
            let html = `<div class="${gridClass}">`;
            
            photos.forEach(photo => {
                const statusClass = 'status-' + photo.status.toLowerCase();
                const uploadDate = new Date(photo.uploaded_at).toLocaleDateString();
                
                // Make all photos clickable
                html += `
                    <div class="client-photo-item" onclick="showPhotoDetails(${JSON.stringify(photo).replace(/"/g, '&quot;')})" style="cursor: pointer;">
                        <div class="status-badge ${statusClass}">${photo.status}</div>
                        <img src="${photo.photo_path}" alt="Animal Photo">
                        <div class="info">
                            <p><strong>${photo.species}</strong> (${photo.animal_type})</p>
                            <p class="date">Uploaded: ${uploadDate}</p>
                            <p class="text-muted"><small>Click to view details</small></p>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
        }
        
                 // Function to show photo details in a modal
         function showPhotoDetails(photo) {
             // Update the modal content
             document.getElementById('modalUploadImage').src = photo.photo_path;
             
             // Get elements
             const photoDetails = document.getElementById('photoDetails');
             const rejectionReasonSection = document.getElementById('rejectionReasonSection');
             const modalRejectionReason = document.getElementById('modalRejectionReason');
             const approveBtn = document.getElementById('approveBtn');
             const rejectBtn = document.getElementById('rejectBtn');
             
             // Store upload ID for approve/reject actions (only for pending photos)
             window.currentUploadId = photo.status === 'Pending' ? photo.photo_id : null;
             console.log('Photo status:', photo.status, 'Photo ID:', photo.photo_id, 'Current Upload ID set to:', window.currentUploadId);
             
             if (photo.status === 'Pending') {
                 // Show all details for pending photos
                 document.getElementById('modalClientName').textContent = photo.client_name || '-';
                 document.getElementById('modalAnimalType').textContent = photo.animal_type || '-';
                 document.getElementById('modalSpecies').textContent = photo.species || '-';
                 document.getElementById('modalUploadDate').textContent = new Date(photo.uploaded_at).toLocaleDateString();
                 
                 // Show action buttons and details
                 approveBtn.style.display = 'inline-block';
                 rejectBtn.style.display = 'inline-block';
                 photoDetails.style.display = 'block';
                 rejectionReasonSection.style.display = 'none';
                 
             } else if (photo.status === 'Rejected') {
                 // Show only rejection reason for rejected photos
                 
                 // Hide action buttons, show only rejection reason
                 approveBtn.style.display = 'none';
                 rejectBtn.style.display = 'none';
                 photoDetails.style.display = 'none'; // Hide all details section
                 
                 if (photo.rejection_reason) {
                     rejectionReasonSection.style.display = 'block';
                     modalRejectionReason.textContent = photo.rejection_reason;
                 } else {
                     rejectionReasonSection.style.display = 'none';
                 }
                 
             } else if (photo.status === 'Approved') {
                 // Show only photo for approved photos
                 document.getElementById('modalClientName').textContent = '';
                 document.getElementById('modalAnimalType').textContent = '';
                 document.getElementById('modalSpecies').textContent = '';
                 document.getElementById('modalUploadDate').textContent = '';
                 
                 // Hide action buttons and all details
                 approveBtn.style.display = 'none';
                 rejectBtn.style.display = 'none';
                 photoDetails.style.display = 'none';
                 rejectionReasonSection.style.display = 'none';
             }
             
             // Ensure the modal appears in front
             const uploadReviewModal = document.getElementById('uploadReviewModal');
             uploadReviewModal.style.zIndex = '10000';
             
             // Show the modal
             var uploadModal = new bootstrap.Modal(uploadReviewModal, {
                 backdrop: 'static',
                 keyboard: false
             });
             uploadModal.show();
             
             // Force the modal to be on top
             setTimeout(() => {
                 uploadReviewModal.style.zIndex = '10000';
                 const backdrop = document.querySelector('.modal-backdrop:last-child');
                 if (backdrop) {
                     backdrop.style.zIndex = '9999';
                 }
             }, 100);
         }

         // Load activity logs function
         function loadActivityLogs() {
             fetch('get_activity_logs.php')
                 .then(response => response.text())
                 .then(data => {
                     // Create the activity logs dropdown content
                     const dropdownContent = `
                         <div class="activity-logs-header">
                             <i class="fas fa-arrow-left me-2" id="backToMenu" style="cursor: pointer;"></i>Activity Logs
                         </div>
                         ${data}
                     `;
                     document.getElementById('avatarDropdown').innerHTML = dropdownContent;
                     document.getElementById('avatarDropdown').classList.add('activity-logs-dropdown');
                     
                     // Add back button functionality
                     const backButton = document.getElementById('backToMenu');
                     if (backButton) {
                         backButton.addEventListener('click', function(e) {
                             e.preventDefault();
                             e.stopPropagation();
                             // Restore original dropdown menu
                             document.getElementById('avatarDropdown').innerHTML = `
                                 <li><a class="dropdown-item" href="#" id="viewActivityLogs"><i class="fas fa-history me-2"></i>View Activity Logs</a></li>
                                 <li><hr class="dropdown-divider"></li>
                                 <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                             `;
                             document.getElementById('avatarDropdown').classList.remove('activity-logs-dropdown');
                             
                             // Re-attach the event listener for "View Activity Logs"
                             const newViewActivityLogsLink = document.getElementById('viewActivityLogs');
                             if (newViewActivityLogsLink) {
                                 newViewActivityLogsLink.addEventListener('click', function(e) {
                                     e.preventDefault();
                                     e.stopPropagation();
                                     loadActivityLogs();
                                 });
                             }
                         });
                     }
                 })
                 .catch(error => {
                     console.error('Error loading activity logs:', error);
                     document.getElementById('avatarDropdown').innerHTML = 
                         '<div class="activity-logs-header"><i class="fas fa-history me-2"></i>Activity Logs</div><div class="text-center text-muted p-3"><i class="fas fa-exclamation-triangle me-2"></i>Error loading activity logs</div>';
                 });
         }

         // Load activity logs when "View Activity Logs" is clicked
         const viewActivityLogsLink = document.getElementById('viewActivityLogs');
         if (viewActivityLogsLink) {
             viewActivityLogsLink.addEventListener('click', function(e) {
                 e.preventDefault();
                 e.stopPropagation(); // Prevent dropdown from closing
                 loadActivityLogs();
             });
         }
    </script>
    
    <!-- Logout Confirmation Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-sign-out-alt text-primary me-3" style="font-size: 2rem;"></i>
                        <div>
                            <h6 class="mb-1">Are you sure you want to logout?</h6>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="logout.php" class="btn btn-primary">Logout</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>