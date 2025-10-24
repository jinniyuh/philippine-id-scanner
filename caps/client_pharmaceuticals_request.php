<?php
session_start();
include 'includes/conn.php';

// Redirect if not logged in
if (!isset($_SESSION['client_id'])) {
    header("Location: login.php");
    exit();
}

$client_id = $_SESSION['client_id'];

// Fetch client's species per type for form behavior
$livestock_species = [];
$poultry_species = [];
$__stmt_ls = $conn->prepare("SELECT DISTINCT species FROM livestock_poultry WHERE client_id = ? AND animal_type = 'Livestock'");
if ($__stmt_ls) {
    $__stmt_ls->bind_param('i', $client_id);
    $__stmt_ls->execute();
    $__res_ls = $__stmt_ls->get_result();
    while ($__r = $__res_ls->fetch_assoc()) { $livestock_species[] = $__r['species']; }
}
$__stmt_pl = $conn->prepare("SELECT DISTINCT species FROM livestock_poultry WHERE client_id = ? AND animal_type = 'Poultry'");
if ($__stmt_pl) {
    $__stmt_pl->bind_param('i', $client_id);
    $__stmt_pl->execute();
    $__res_pl = $__stmt_pl->get_result();
    while ($__r = $__res_pl->fetch_assoc()) { $poultry_species[] = $__r['species']; }
}

// Submit request
if (isset($_POST['submit_request'])) {
    error_log("Received pharmaceutical request submission: " . print_r($_POST, true));
    $species = isset($_POST['species']) ? trim($_POST['species']) : '';
    $symptoms = isset($_POST['symptoms']) ? trim($_POST['symptoms']) : '';
    
    // Validate inputs
    if ($species === '') {
        $_SESSION['error_message'] = 'Please select a species.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    // Insert new simplified request (species and symptoms only)
        $stmt = $conn->prepare("INSERT INTO pharmaceutical_requests 
        (client_id, species, symptoms, status, request_date) 
        VALUES (?, ?, ?, 'Pending', NOW())");
        if (!$stmt) { 
            error_log('Prepare failed: ' . $conn->error); 
            $_SESSION['error_message'] = 'Database error: ' . $conn->error; 
            header('Location: ' . $_SERVER['PHP_SELF']); 
            exit(); 
        }
    $stmt->bind_param("iss", $client_id, $species, $symptoms);


    
    if ($stmt->execute()) {
        error_log("Successfully inserted pharmaceutical request");
        
        // Get the request ID that was just inserted
        $request_id = $conn->insert_id;
        
        // INTEGRATION: Convert symptoms to health indicators and trigger risk assessment
        if (!empty($symptoms)) {
            include 'includes/health_risk_assessor.php';
            $assessor = new HealthRiskAssessor($conn);
            
            // Get the animal ID for this client (assuming they have animals registered)
            $animal_query = $conn->prepare("SELECT animal_id FROM livestock_poultry WHERE client_id = ? AND species = ? LIMIT 1");
            $animal_query->bind_param("is", $client_id, $species);
            $animal_query->execute();
            $animal_result = $animal_query->get_result();
            
            if ($animal_result->num_rows > 0) {
                $animal_data = $animal_result->fetch_assoc();
                $animal_id = $animal_data['animal_id'];
                
                // Create health indicators from symptoms
                $symptoms_array = explode(';', $symptoms);
                foreach ($symptoms_array as $symptom) {
                    $symptom = trim($symptom);
                    if (!empty($symptom)) {
                        // Insert symptom as health indicator
                        $indicator_stmt = $conn->prepare("INSERT INTO health_indicators 
                            (animal_id, indicator_type, indicator_value, recorded_date, recorded_by, notes) 
                            VALUES (?, 'Behavioral_Change', ?, NOW(), ?, ?)");
                        $notes = "Symptom reported via pharmaceutical request";
                        $indicator_stmt->bind_param("isis", $animal_id, $symptom, $client_id, $notes);
                        $indicator_stmt->execute();
                    }
                }
                
                // Trigger automatic health risk assessment
                try {
                    $risk_assessment = $assessor->assessAnimalHealthRisk($animal_id);
                    if (!isset($risk_assessment['error'])) {
                        $assessor->saveAssessment($risk_assessment, $client_id);
                        error_log("Health risk assessment triggered for animal ID: $animal_id");
                    }
                } catch (Exception $e) {
                    error_log("Health risk assessment failed: " . $e->getMessage());
                }
            }
        }
        
        // Get client information for the notification
        $client_query = $conn->prepare("SELECT full_name FROM clients WHERE client_id = ?");
        $client_query->bind_param("i", $client_id);
        $client_query->execute();
        $client_result = $client_query->get_result();
        $client_data = $client_result->fetch_assoc();
        $client_name = $client_data['full_name'] ?? 'Unknown Client';
        
        // Create notification message
        $notification_message = "New pharmaceutical request from " . $client_name . " for " . $species;
        if (!empty($symptoms)) {
            $notification_message .= ". Symptoms: " . $symptoms;
        }
        
        // Send notification to all admin and staff users
        $admin_staff_query = $conn->prepare("SELECT user_id FROM users WHERE role IN ('admin', 'staff')");
        if ($admin_staff_query) {
            $admin_staff_query->execute();
            $admin_staff_result = $admin_staff_query->get_result();
            
            $notification_sent = false;
            while ($user = $admin_staff_result->fetch_assoc()) {
                $user_id = $user['user_id'];
                $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, timestamp, status) VALUES (?, ?, NOW(), 'Unread')");
                if ($notif_stmt) {
                    $notif_stmt->bind_param("is", $user_id, $notification_message);
                    if ($notif_stmt->execute()) {
                        $notification_sent = true;
                    }
                }
            }
            
            if ($notification_sent) {
                error_log("Notification sent to admin and staff users for pharmaceutical request ID: " . $request_id);
            } else {
                error_log("Failed to send notification to admin and staff users for pharmaceutical request ID: " . $request_id);
            }
        } else {
            error_log("Failed to prepare admin/staff query for notifications");
        }
        
        $_SESSION['success_message'] = 'Request submitted successfully!';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } else {
        error_log("Error inserting pharmaceutical request: " . $stmt->error);
        $_SESSION['error_message'] = 'Error submitting request: ' . $stmt->error;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// (No pharmaceuticals list needed here anymore)

// Get user's requests
$requests = $conn->query("SELECT pr.* 
    FROM pharmaceutical_requests pr 
    WHERE pr.client_id = $client_id 
    ORDER BY pr.request_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmaceutical Request - Bago City Veterinary Office</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #6c63ff;
            overflow-x: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container-fluid { padding: 0; }
        /* Sidebar styles handled by client_sidebar.php */
        .main-wrapper {
            background: white;
            margin-left: 312px;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: fixed;
            top: 20px;
            left: 20px;
            right: 20px;
            bottom: 20px;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 1;
            pointer-events: auto;
            min-height: calc(100vh - 40px);
            max-width: calc(100vw - 332px);
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
            
            .table th, .table td {
                padding: 0.5rem;
                font-size: 0.9rem;
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
            
            .table th, .table td {
                padding: 0.3rem;
                font-size: 0.8rem;
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
            
            .table th, .table td {
                padding: 0.2rem;
                font-size: 0.75rem;
            }
        }
        
        /* Table styling */
        .table-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            padding: 1.5rem;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 0;
        }
        .table th {
            background-color: #6c63ff;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 1rem;
            border-bottom: 2px solid #e9ecef;
            vertical-align: middle;
        }
        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-top: 1px solid #f2f2f2;
            color: #495057;
            font-size: 0.95rem;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0,0,0,.02);
        }
        .table tbody tr {
            transition: background-color 0.2s ease;
        }
        .table tbody tr:hover {
            background-color: rgba(108, 99, 255, 0.05);
        }
        /* Badge styling */
        .badge {
            font-weight: 500;
            padding: 0.5em 0.8em;
            border-radius: 30px;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .bg-success {
            background-color: #28a745 !important;
            box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
        }
        .bg-warning {
            background-color: #ffc107 !important;
            color: #212529 !important;
            box-shadow: 0 2px 4px rgba(255, 193, 7, 0.2);
        }
        .form-control, .form-select { 
            height: 40px; 
            border-radius: 6px;
            border-color: #e0e0e0;
            box-shadow: none;
            transition: all 0.2s ease;
        }
        
        /* Highlight poultry species dropdown */
        #speciesSelect option {
            padding: 8px;
        }
        
        #speciesSelect option:first-child {
            font-weight: bold;
            color: #6c757d;
        }
        .form-control:focus, .form-select:focus {
            border-color: #6c63ff;
            box-shadow: 0 0 0 0.25rem rgba(108, 99, 255, 0.25);
        }
        .btn-primary {
            background-color: #6c63ff;
            border-color: #6c63ff;
            border-radius: 6px;
            font-weight: 500;
            padding: 0.5rem 1.25rem;
            transition: all 0.2s ease;
        }
        .btn-primary:hover, .btn-primary:focus {
            background-color: #5a52d5;
            border-color: #5a52d5;
            box-shadow: 0 4px 10px rgba(108, 99, 255, 0.3);
        }
        
        /* Modal styles - Force modal to be interactive */
        .modal {
            z-index: 9999 !important;
            pointer-events: auto !important;
            position: fixed !important;
        }
        .modal-backdrop {
            display: none !important;
            opacity: 0 !important;
            z-index: -1 !important;
        }
        .modal-backdrop.show {
            display: none !important;
            opacity: 0 !important;
        }
        .modal-content {
            z-index: 10000 !important;
            pointer-events: auto !important;
            position: relative !important;
        }
        .modal-dialog {
            pointer-events: auto !important;
            z-index: 10001 !important;
            position: relative !important;
        }
        .modal-body {
            pointer-events: auto !important;
            position: relative !important;
        }
        .modal-footer {
            pointer-events: auto !important;
            position: relative !important;
        }
        .modal-header {
            pointer-events: auto !important;
            position: relative !important;
        }
        /* Force all form elements to be interactive */
        .modal input, .modal select, .modal textarea, .modal button {
            pointer-events: auto !important;
            z-index: 10002 !important;
            position: relative !important;
        }
        
        /* Completely hide any backdrop elements */
        .modal-backdrop, .modal-backdrop.show, [class*="backdrop"] {
            display: none !important;
            opacity: 0 !important;
            visibility: hidden !important;
            z-index: -9999 !important;
        }
        
        /* Ensure modal has no background overlay */
        .modal.show {
            background-color: transparent !important;
        }
        .modal-header {
            background-color: #6c63ff; 
            pointer-events: auto !important;
        }
        .modal-body {
            pointer-events: auto !important;
        }
        .modal-body input,
        .modal-body select,
        .modal-body button,
        .modal-body label,
        .modal-body form,
        .modal-body div {
            pointer-events: auto !important;
        }
        /* Override any conflicting styles */
        * {
            pointer-events: auto;
        }
        
        /* Symptoms dropdown styles */
        .symptoms-dropdown {
            max-height: 300px;
            overflow-y: auto;
        }
        
        /* Ensure symptoms dropdown is always interactive */
        #symptomsDropdown, #symptomsDropdownButton, #symptomsList {
            pointer-events: auto !important;
        }
        
        #symptomsDropdownButton {
            pointer-events: auto !important;
            cursor: pointer !important;
        }
        
        .symptom-checkbox {
            pointer-events: auto !important;
            cursor: pointer !important;
        }
        
        /* Styles for disabled fields in edit mode */
        .form-control:disabled, .form-select:disabled {
            background-color: #f8f9fa !important;
            color: #6c757d !important;
            cursor: not-allowed !important;
            opacity: 0.8 !important;
        }
        .symptom-item {
            padding: 8px 16px;
            border-bottom: 1px solid #f0f0f0;
        }
        .symptom-item:last-child {
            border-bottom: none;
        }
        .symptom-item:hover {
            background-color: #f8f9fa;
        }
        .symptom-checkbox {
            margin-right: 8px;
        }
        .selected-symptoms {
            background-color: #e8f5e8;
            border: 1px solid #c3e6cb;
            border-radius: 6px;
            padding: 10px;
            margin-top: 8px;
        }
        .symptom-tag {
            display: inline-block;
            background-color: #6c63ff;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            margin: 2px;
        }
        .symptom-tag .remove-symptom {
            margin-left: 6px;
            cursor: pointer;
            font-weight: bold;
        }
        
        /* Symptoms expander styles */
        .symptoms-text {
            max-width: 260px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: all 0.3s ease;
        }
        
        .symptoms-text.expanded {
            white-space: normal;
            max-width: none;
            word-wrap: break-word;
        }
        
        .symptoms-expander {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border-radius: 0.25rem;
            transition: all 0.3s ease;
        }
        
        .symptoms-expander:hover {
            background-color: #f8f9fa;
            border-color: #6c63ff;
        }
        
        .symptoms-expander i {
            transition: transform 0.3s ease;
        }
        
        .symptoms-expander.expanded i {
            transform: rotate(180deg);
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Pharmaceutical Requests</h2>
                    <p class="text-muted mb-0"></i></p>
                </div>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#requestModal">
                        <i class="fas fa-plus-circle me-2"></i>Request Medicine
                    </button>
                </div>
            </div>

            <!-- Request Modal -->
            <div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true" style="z-index: 10000;">
              <div class="modal-dialog" style="pointer-events: auto;">
                <div class="modal-content" style="pointer-events: auto;">
                  <div class="modal-header" style= "background-color: #6c63ff">
                    <h5 class="modal-title" style= "color: white !important">Pharmaceutical Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body" style="pointer-events: auto;">
                    <form method="POST" action="" id="requestForm" style="pointer-events: auto;">

                      <div class="mb-3" id="speciesGroup">
                        <label class="form-label">Species</label>
                        <select name="species" id="speciesSelect" class="form-select" style="pointer-events: auto !important;" required>
                          <option value="">Select species</option>
                          <?php
                          // Combine livestock and poultry species for the dropdown
                          $all_species = array_merge($livestock_species, $poultry_species);
                          foreach($all_species as $species): ?>
                            <option value="<?= htmlspecialchars($species) ?>"><?= htmlspecialchars($species) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>

                      <div class="mb-3">
                        <label class="form-label">Symptoms</label>
                        <div class="dropdown" id="symptomsDropdown">
                          <button class="btn btn-outline-secondary dropdown-toggle w-100" type="button" id="symptomsDropdownButton" data-bs-toggle="dropdown" aria-expanded="false" style="text-align: left;">
                            Select symptoms...
                          </button>
                          <ul class="dropdown-menu w-100" aria-labelledby="symptomsDropdownButton" id="symptomsList">
                            <!-- Symptoms will be populated based on animal type -->
                          </ul>
                      </div>
                        <input type="hidden" name="symptoms" id="selectedSymptoms" value="">
                        <div id="selectedSymptomsDisplay" class="mt-2"></div>
                      </div>

                      
                      <div class="mb-3" id="poultryInfo" style="display: none;">
                        <div class="alert alert-info">
                          <i class="fas fa-info-circle me-2"></i>
                          <strong>Poultry Request:</strong> Weight is not required for poultry animals. The species you select will be displayed in the request table.
                        </div>
                      </div>

                      <div class="text-center">
                        <input type="submit" name="submit_request" value="Submit Request" class="btn btn-primary" style="pointer-events: auto !important;">
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>

            <!-- Request Table -->
            <div class="table-container">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Species</th>
                                <th>Symptoms</th>
                                <th>Request Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($requests->num_rows > 0): ?>
                                <?php while ($row = $requests->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['species'] ?? ''); ?></td>
                                        <td>
                                            <div class="symptoms-text" id="symptoms-<?= $row['request_id']; ?>">
                                                <?= htmlspecialchars($row['symptoms'] ?? ''); ?>
                                            </div>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($row['request_date'])); ?></td>
                                        <td>
                                            <span class="badge <?= $row['status'] === 'Pending' ? 'bg-warning' : 'bg-success'; ?>">
                                                <?= $row['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2 align-items-center">
                                                <button type="button" class="btn btn-sm btn-outline-primary edit-request" 
                                                        data-id="<?= $row['request_id']; ?>"
                                                        data-species="<?= htmlspecialchars($row['species']); ?>"
                                                        data-symptoms="<?= htmlspecialchars($row['symptoms']); ?>"
                                                        title="Edit Request">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-request" 
                                                        data-id="<?= $row['request_id']; ?>"
                                                        title="Delete Request">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary symptoms-expander" 
                                                        onclick="toggleSymptoms(<?= $row['request_id']; ?>)"
                                                        title="Expand/Collapse Symptoms">
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="fas fa-prescription-bottle text-muted mb-3" style="font-size: 2.5rem;"></i>
                                            <h6 class="fw-bold mb-1">No Requests Found</h6>
                                            <p class="text-muted">You haven't made any medicine requests yet.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </div>
    </div>

    <!-- Success/Error Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow">
                <div class="modal-body text-center p-4">
                    <div class="mb-3">
                        <i id="modalIcon" style="font-size: 2rem;"></i>
                    </div>
                    <h6 id="modalTitle" class="mb-3"></h6>
                    <p id="modalMessage" class="text-muted mb-4"></p>
                    <button type="button" class="btn px-4" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow">
                <div class="modal-body text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-question-circle text-warning" style="font-size: 2rem;"></i>
                    </div>
                    <h6 class="mb-3">Confirm Request</h6>
                    <p class="text-muted mb-4">Are you sure you want to submit this request?</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary px-4" id="confirmSubmit">Submit</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow">
                <div class="modal-body text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-check-circle text-success" style="font-size: 2rem;"></i>
                    </div>
                    <h6 class="mb-3 text-success">Success!</h6>
                    <p class="text-muted mb-4" id="successMessage">Request updated successfully!</p>
                    <div class="d-flex justify-content-center">
                        <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal">OK</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow">
                <div class="modal-body text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-exclamation-triangle text-danger" style="font-size: 2rem;"></i>
                    </div>
                    <h6 class="mb-3 text-danger">Confirm Delete</h6>
                    <p class="text-muted mb-4">Are you sure you want to delete this pharmaceutical request?</p>
                    <p class="text-muted small mb-4">This action cannot be undone.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger px-4" id="confirmDelete">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle JS (for modal and components) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Check for session messages and show modal
        <?php if (isset($_SESSION['success_message'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const modal = new bootstrap.Modal(document.getElementById('messageModal'));
                document.getElementById('modalIcon').className = 'fas fa-check-circle text-success';
                document.getElementById('modalTitle').textContent = 'Success';
                document.getElementById('modalMessage').textContent = '<?php echo addslashes($_SESSION['success_message']); ?>';
                document.querySelector('#messageModal .btn').className = 'btn btn-success px-4';
                modal.show();
            });
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const modal = new bootstrap.Modal(document.getElementById('messageModal'));
                document.getElementById('modalIcon').className = 'fas fa-exclamation-circle text-danger';
                document.getElementById('modalTitle').textContent = 'Error';
                document.getElementById('modalMessage').textContent = '<?php echo addslashes($_SESSION['error_message']); ?>';
                document.querySelector('#messageModal .btn').className = 'btn btn-danger px-4';
                modal.show();
            });
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        // Form confirmation handling
        document.addEventListener('DOMContentLoaded', function () {
            // Fix any potential issues with pointer events
            document.querySelectorAll('.modal, .modal-dialog, .modal-content, .modal-header, .modal-body, form, select, input, button').forEach(function(el) {
                el.style.pointerEvents = 'auto';
            });
            
            // Initialize the request modal with proper options
            const requestModalEl = document.getElementById('requestModal');
            const requestModal = new bootstrap.Modal(requestModalEl, {
                backdrop: false,  // Disable backdrop to prevent interaction issues
                keyboard: true,
                focus: true
            });
            
            // Function to reset form fields for new requests
            function resetFormForNewRequest() {
                const speciesSelect = document.getElementById('speciesSelect');
                
                if (speciesSelect) {
                    speciesSelect.disabled = false;
                    speciesSelect.style.backgroundColor = '';
                    speciesSelect.style.cursor = '';
                    speciesSelect.value = '';
                }
                
                // Clear symptoms
                document.getElementById('selectedSymptoms').value = '';
                document.getElementById('selectedSymptomsDisplay').innerHTML = '';
                document.getElementById('symptomsDropdownButton').textContent = 'Select symptoms...';
                
                // Remove edit mode hidden fields
                const editField = document.getElementById('edit_request_id');
                if (editField) {
                    editField.remove();
                }
                
                const hiddenSpecies = document.getElementById('hidden_species');
                if (hiddenSpecies) {
                    hiddenSpecies.remove();
                }
            }
            
            // Initialize the confirmation modal
            const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'), {
                backdrop: false,  // Disable backdrop to prevent interaction issues
                keyboard: true
            });
            
            const confirmSubmitBtn = document.getElementById('confirmSubmit');
            const requestForm = document.getElementById('requestForm');
            
            // Reset form when "Request Medicine" button is clicked (new request)
            document.querySelector('[data-bs-target="#requestModal"]').addEventListener('click', function() {
                resetFormForNewRequest();
            });
            
            // Focus the first input when modal is shown
            requestModalEl.addEventListener('shown.bs.modal', function () {
                setTimeout(function() {
                    const firstInput = requestModalEl.querySelector('select, input');
                    if (firstInput) {
                        firstInput.focus();
                    }
                }, 100);
            });
            
            // Aggressively fix modal backdrop issues
            document.addEventListener('DOMContentLoaded', function() {
                // Remove ALL existing backdrops immediately
                const removeAllBackdrops = () => {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => {
                        backdrop.remove();
                    });
                };
                
                // Remove backdrops on page load
                removeAllBackdrops();
                
                // Remove backdrops every 100ms to catch any new ones
                setInterval(removeAllBackdrops, 100);
                
                // Ensure modal is properly configured
                const modal = document.getElementById('requestModal');
                if (modal) {
                    modal.style.pointerEvents = 'auto';
                    modal.style.zIndex = '10000';
                    modal.style.backgroundColor = 'transparent';
                    
                    // Override Bootstrap's backdrop creation
                    const originalShow = modal.show;
                    modal.show = function() {
                        removeAllBackdrops();
                        originalShow.call(this);
                        removeAllBackdrops();
                    };
                    
                    // Add event listener for when modal is shown
                    modal.addEventListener('shown.bs.modal', function() {
                        removeAllBackdrops();
                        
                        // Ensure modal content is interactive
                        const modalContent = modal.querySelector('.modal-content');
                        if (modalContent) {
                            modalContent.style.pointerEvents = 'auto';
                            modalContent.style.zIndex = '10000';
                            modalContent.style.backgroundColor = 'white';
                        }
                    });
                    
                    // Add event listener for when modal is hidden
                    modal.addEventListener('hidden.bs.modal', function() {
                        removeAllBackdrops();
                    });
                }
            });
            
            // Handle form submission - direct submit without confirmation
            requestForm.addEventListener('submit', function(e) {
                // Check if this is an edit operation
                const editRequestId = document.getElementById('edit_request_id');
                if (editRequestId && editRequestId.value) {
                    // This is an edit operation - submit to update handler
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    formData.append('action', 'update');
                    
                    // Debug: Log form data
                    console.log('Form data being sent:');
                    for (let [key, value] of formData.entries()) {
                        console.log(key + ': ' + value);
                    }
                    
                    fetch('update_pharmaceutical_request.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success modal
                            document.getElementById('successMessage').textContent = 'Request updated successfully!';
                            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                            successModal.show();
                            
                            // Reload page when modal is closed
                            document.getElementById('successModal').addEventListener('hidden.bs.modal', function() {
                                location.reload();
                            });
                        } else {
                            // Show error modal
                            document.getElementById('successMessage').textContent = 'Error updating request: ' + (data.error || 'Unknown error');
                            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                            successModal.show();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        document.getElementById('successMessage').textContent = 'Error updating request';
                        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                        successModal.show();
                    });
                } else {
                    // This is a create operation - submit normally
                return true;
                }
            });

            // Species field is now always visible and populated
            const speciesSelect = document.getElementById('speciesSelect');

            // Update symptoms dropdown when species changes
            if (speciesSelect) {
                speciesSelect.addEventListener('change', function() {
                    const selectedSpecies = this.value;
                    if (selectedSpecies) {
                        updateSymptomsDropdown(selectedSpecies);
                    }
                });
            }

            
            // Symptoms data for different animals - TRULY UNIQUE DISEASES
            const symptomsData = {
                'Carabao': [
                    'Namamagang leeg at dibdib (swollen neck & chest)',
                    'Hirap huminga, paglalaway (difficulty breathing, drooling)',
                    'Biglaang pagkamatay (sudden death)',
                    'Pamamaga ng paa at kasu-kasuan (swollen feet and joints)',
                    'Panghihina at pagtulog nang matagal (weakness and excessive sleeping)',
                    'Pamamaga ng tiyan (swollen belly)',
                    'Pamumula ng balat (skin redness)',
                    'Pagtatae na may dugo (bloody diarrhea)',
                    'Panginginig at kombulsyon (tremors and convulsions)',
                    'Pamumula ng mata (red eyes)',
                    'Panghihina sa paghinga (breathing difficulties)',
                    'Kawalan ng ganang kumain (loss of appetite)',
                    'Mataas na lagnat (high fever)',
                    'Pamamaga ng mga paa (swollen legs)',
                    'Pagtatae na may dugo (bloody diarrhea)'
                ],
                'Swine': [
                    'Mataas na lagnat (High fever)',
                    'Kawalan ng ganang kumain (Loss of appetite)',
                    'Panghihina at panghihina ng katawan (Weakness, lethargy)',
                    'Pamumula ng balat lalo na sa tenga, tiyan, at paa (Skin redness / discoloration)',
                    'Mapupulang batik o pasa (Red/Purple skin spots)',
                    'Pagtatae na may dugo (Bloody diarrhea)',
                    'Pagsusuka na may dugo (Bloody vomiting)',
                    'Pamamaga ng tiyan at paa (Swollen belly and legs)',
                    'Hirap sa paghinga (Breathing difficulties)',
                    'Biglaang pagkamatay (Sudden death)'
                ],
                'Cow': [
                    'Pagiging nerbiyoso at agresibo (nervousness, aggression)',
                    'Hirap sa paglakad, pagkatumba (loss of coordination)',
                    'Bawas gatas (low milk production)',
                    'Pagpayat (weight loss)',
                    'Pamamaga ng suso (swollen udder)',
                    'Pagtatae na may dugo (bloody diarrhea)',
                    'Panginginig at kombulsyon (tremors and convulsions)',
                    'Pamumula ng mata (red eyes)',
                    'Panghihina sa paghinga (breathing difficulties)',
                    'Kawalan ng ganang kumain (loss of appetite)',
                    'Pamamaga ng mga paa (swollen legs)',
                    'Pagtatae na may dugo (bloody diarrhea)',
                    'Panginginig at kombulsyon (tremors and convulsions)',
                    'Panghihina sa paghinga (breathing difficulties)'
                ],
                'Goat': [
                    'Pamamaga ng kasu-kasuan (swollen joints)',
                    'Hirap tumayo/lumakad (lameness)',
                    'Pulmonya at panghihina sa biik (pneumonia, wasting in kids)',
                    'Matigas na suso, kaunting gatas (hard udder, low milk)',
                    'Pagtatae na may dugo (bloody diarrhea)',
                    'Panginginig at kombulsyon (tremors and convulsions)',
                    'Pamumula ng mata (red eyes)',
                    'Pamamaga ng paa (swollen legs)',
                    'Kawalan ng ganang kumain (loss of appetite)',
                    'Panghihina sa paghinga (breathing difficulties)',
                    'Mataas na lagnat (high fever)',
                    'Pamamaga ng mga paa (swollen legs)',
                    'Panginginig at kombulsyon (tremors and convulsions)',
                    'Pamumula ng mata (red eyes)'
                ],
                'Dog': [
                    'Ubo at sipon, may muta (coughing, nasal/eye discharge)',
                    'Pagsusuka at pagtatae (vomiting, diarrhea)',
                    'Kombulsyon at pagkaparalisa (seizures, paralysis)',
                    'Pamamaga ng mga paa (swollen legs)',
                    'Pagtatae na may dugo (bloody diarrhea)',
                    'Panginginig at kombulsyon (tremors and convulsions)',
                    'Pamumula ng mata (red eyes)',
                    'Panghihina sa paghinga (breathing difficulties)',
                    'Kawalan ng ganang kumain (loss of appetite)',
                    'Mataas na lagnat (high fever)',
                    'Panginginig at kombulsyon (tremors and convulsions)',
                    'Pamumula ng mata (red eyes)',
                    'Panghihina sa paghinga (breathing difficulties)'
                ],
                'Horse': [
                    'Paulit-ulit na lagnat (recurrent fever)',
                    'Maputlang gilagid, panghihina (anemia, weakness)',
                    'Pagpayat (weight loss)',
                    'Pamamaga ng tiyan at paa (swelling of belly & legs)',
                    'Pagtatae na may dugo (bloody diarrhea)',
                    'Panginginig at kombulsyon (tremors and convulsions)',
                    'Pamumula ng mata (red eyes)',
                    'Panghihina sa paghinga (breathing difficulties)',
                    'Pamamaga ng mga paa (swollen legs)',
                    'Kawalan ng ganang kumain (loss of appetite)',
                    'Mataas na lagnat (high fever)',
                    'Panginginig at kombulsyon (tremors and convulsions)',
                    'Pamumula ng mata (red eyes)'
                ],
                'Sheep': [
                    'Matinding pangangati (intense itching)',
                    'Panginginig, hirap maglakad (tremors, loss of coordination)',
                    'Ibang ugali (behavior changes)',
                    'Pagpayat kahit kumakain (weight loss)',
                    'Pagtatae na may dugo (bloody diarrhea)',
                    'Panghihina sa paghinga (breathing difficulties)',
                    'Pamamaga ng mga paa (swollen legs)',
                    'Kawalan ng ganang kumain (loss of appetite)',
                    'Mataas na lagnat (high fever)',
                    'Panginginig at kombulsyon (tremors and convulsions)',
                    'Pamumula ng mata (red eyes)'
                ],
                'Chicken': [
                    'Hirap huminga, parang hinihingal (gasping, coughing)',
                    'Luntiang dumi (greenish diarrhea)',
                    'Bawas sa itlog (drop in egg production)',
                    'Baluktot leeg, pagkaparalisa (twisted neck, paralysis)',
                    'Panghihina sa paghinga (breathing difficulties)',
                    'Kawalan ng ganang kumain (loss of appetite)',
                    'Mataas na lagnat (high fever)',
                    'Pamamaga ng mga paa (swollen legs)',
                    'Pagtatae na may dugo (bloody diarrhea)',
                    'Panginginig at kombulsyon (tremors and convulsions)',
                    'Pamumula ng mata (red eyes)'
                ],
                'Duck': [
                    'Hirap huminga, parang hinihingal (gasping, coughing)',
                    'Luntiang dumi (greenish diarrhea)',
                    'Bawas sa itlog (drop in egg production)',
                    'Baluktot leeg, pagkaparalisa (twisted neck, paralysis)',
                    'Pamamaga ng mga paa (swollen legs)',
                    'Pagtatae na may dugo (bloody diarrhea)',
                    'Panginginig at kombulsyon (tremors and convulsions)',
                    'Panghihina sa paghinga (breathing difficulties)',
                    'Kawalan ng ganang kumain (loss of appetite)',
                    'Mataas na lagnat (high fever)',
                    'Pamumula ng mata (red eyes)'
                ]
            };
            
            // Function to update symptoms dropdown based on selected species
            function updateSymptomsDropdown(species) {
                const symptomsList = document.getElementById('symptomsList');
                const symptomsDropdownButton = document.getElementById('symptomsDropdownButton');
                
                // Clear previous symptoms
                symptomsList.innerHTML = '';
                
                if (symptomsData[species]) {
                    symptomsData[species].forEach((symptom, index) => {
                        const li = document.createElement('li');
                        li.className = 'symptom-item';
                        li.innerHTML = `
                            <div class="form-check">
                                <input class="form-check-input symptom-checkbox" type="checkbox" value="${symptom}" id="symptom_${index}">
                                <label class="form-check-label" for="symptom_${index}">
                                    ${symptom}
                                </label>
                            </div>
                        `;
                        symptomsList.appendChild(li);
                    });
                    
                    // Add event listeners to checkboxes
                    symptomsList.querySelectorAll('.symptom-checkbox').forEach(checkbox => {
                        checkbox.addEventListener('change', updateSelectedSymptoms);
                    });
                    
                    symptomsDropdownButton.textContent = 'Select symptoms...';
                    } else {
                    symptomsDropdownButton.textContent = 'No symptoms available for this species';
                }
            }
            
            // Function to update selected symptoms display
            function updateSelectedSymptoms() {
                const checkboxes = document.querySelectorAll('.symptom-checkbox:checked');
                const selectedSymptoms = Array.from(checkboxes).map(cb => cb.value);
                const selectedSymptomsInput = document.getElementById('selectedSymptoms');
                const selectedSymptomsDisplay = document.getElementById('selectedSymptomsDisplay');
                
                // Update hidden input
                selectedSymptomsInput.value = selectedSymptoms.join('; ');
                
                // Update display
                if (selectedSymptoms.length > 0) {
                    selectedSymptomsDisplay.innerHTML = `
                        <div class="selected-symptoms">
                            <strong>Selected Symptoms:</strong><br>
                            ${selectedSymptoms.map(symptom => 
                                `<span class="symptom-tag">${symptom} <span class="remove-symptom" onclick="removeSymptom('${symptom}')">×</span></span>`
                            ).join('')}
                        </div>
                    `;
                } else {
                    selectedSymptomsDisplay.innerHTML = '';
                }
            }
            
            // Function to remove a symptom
            window.removeSymptom = function(symptom) {
                const checkbox = Array.from(document.querySelectorAll('.symptom-checkbox')).find(cb => cb.value === symptom);
                if (checkbox) {
                    checkbox.checked = false;
                    updateSelectedSymptoms();
                }
            };
            
            // Function to toggle symptoms expansion
            window.toggleSymptoms = function(requestId) {
                const symptomsText = document.getElementById('symptoms-' + requestId);
                const expanderButton = event.target.closest('.symptoms-expander');
                
                if (symptomsText && expanderButton) {
                    symptomsText.classList.toggle('expanded');
                    expanderButton.classList.toggle('expanded');
                }
            };
            
            // Edit request functionality - using event delegation
            document.addEventListener('click', function(e) {
                if (e.target.closest('.edit-request')) {
                    const button = e.target.closest('.edit-request');
                    const requestId = button.getAttribute('data-id');
                    const species = button.getAttribute('data-species');
                    const symptoms = button.getAttribute('data-symptoms');
                    
                     // Populate the form with existing data
                     document.getElementById('speciesSelect').value = species;
                     document.getElementById('selectedSymptoms').value = symptoms;
                     
                     // Make Species field read-only for editing
                     const speciesSelect = document.getElementById('speciesSelect');
                     
                     if (speciesSelect) {
                         speciesSelect.disabled = true;
                         speciesSelect.style.backgroundColor = '#f8f9fa';
                         speciesSelect.style.cursor = 'not-allowed';
                         
                         // Add a hidden input to ensure species value is submitted
                         let hiddenSpecies = document.getElementById('hidden_species');
                         if (!hiddenSpecies) {
                             hiddenSpecies = document.createElement('input');
                             hiddenSpecies.type = 'hidden';
                             hiddenSpecies.name = 'species';
                             hiddenSpecies.id = 'hidden_species';
                             document.getElementById('requestForm').appendChild(hiddenSpecies);
                         }
                         hiddenSpecies.value = species;
                     }
                     
                     // Update symptoms dropdown based on species
                     updateSymptomsDropdown(species);
                     
                     // Ensure symptoms dropdown is enabled
                     const symptomsDropdown = document.getElementById('symptomsDropdown');
                     const symptomsButton = document.getElementById('symptomsDropdownButton');
                     if (symptomsDropdown && symptomsButton) {
                         symptomsDropdown.style.pointerEvents = 'auto';
                         symptomsButton.disabled = false;
                         symptomsButton.style.pointerEvents = 'auto';
                     }
                    
                    // Wait for dropdown to populate, then select the symptoms
                    setTimeout(() => {
                        // Clear all existing selections first
                        document.querySelectorAll('.symptom-checkbox').forEach(checkbox => {
                            checkbox.checked = false;
                        });
                        
                        // Split symptoms and select matching checkboxes
                        if (symptoms) {
                            const symptomList = symptoms.split('; ').map(s => s.trim());
                            symptomList.forEach(symptom => {
                                const checkbox = Array.from(document.querySelectorAll('.symptom-checkbox'))
                                    .find(cb => cb.value === symptom);
                                if (checkbox) {
                                    checkbox.checked = true;
                                }
                            });
                            
                            // Update the display
                            updateSelectedSymptoms();
                        }
                    }, 100);
                    
                    // Show the request form modal
                    const modal = new bootstrap.Modal(document.getElementById('requestModal'));
                    modal.show();
                    
                    // Add hidden field to indicate edit mode
                    let hiddenField = document.getElementById('edit_request_id');
                    if (!hiddenField) {
                        hiddenField = document.createElement('input');
                        hiddenField.type = 'hidden';
                        hiddenField.id = 'edit_request_id';
                        hiddenField.name = 'edit_request_id';
                        document.getElementById('requestForm').appendChild(hiddenField);
                    }
                    hiddenField.value = requestId;
                    
                    // Change form title and button
                    document.querySelector('#requestModal .modal-title').textContent = 'Edit Medicine Request';
                    document.querySelector('#requestForm button[type="submit"]').textContent = 'Update Request';
                }
            });
            
            // Delete request functionality - using event delegation
            document.addEventListener('click', function(e) {
                if (e.target.closest('.delete-request')) {
                    const button = e.target.closest('.delete-request');
                    const requestId = button.getAttribute('data-id');
                    
                    // Store the request ID for the delete action
                    window.pendingDeleteId = requestId;
                    
                    // Show delete confirmation modal
                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                    deleteModal.show();
                }
            });
            
            // Handle delete confirmation
            document.getElementById('confirmDelete').addEventListener('click', function() {
                const requestId = window.pendingDeleteId;
                if (requestId) {
                    // Send delete request
                    fetch('delete_pharmaceutical_request.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'request_id=' + requestId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the row from the table
                            const deleteButton = document.querySelector(`[data-id="${requestId}"]`);
                            if (deleteButton) {
                                deleteButton.closest('tr').remove();
                            }
                            
                            // Show success message
                            document.getElementById('successMessage').textContent = 'Request deleted successfully!';
                            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                            successModal.show();
                            
                            // Reload page when modal is closed
                            document.getElementById('successModal').addEventListener('hidden.bs.modal', function() {
                                location.reload();
                            });
                        } else {
                            // Show error message
                            document.getElementById('successMessage').textContent = 'Error deleting request: ' + (data.error || 'Unknown error');
                            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                            successModal.show();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        document.getElementById('successMessage').textContent = 'Error deleting request';
                        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                        successModal.show();
                    });
                    
                    // Close delete modal
                    const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
                    deleteModal.hide();
                }
            });
        });
    </script>
</body>
</html>
