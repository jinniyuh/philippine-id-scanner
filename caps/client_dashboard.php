<?php
session_start();
include 'includes/conn.php';
include 'includes/session_validator.php';

// Validate session and require client role
requireActiveSession($conn, 'client');

// Get client information
$client_id = $_SESSION['client_id'];
$stmt = $conn->prepare("SELECT * FROM clients WHERE client_id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$client = $stmt->get_result()->fetch_assoc();

// Get the user_id for this client to fetch notifications
$user_query = $conn->prepare("SELECT user_id FROM users WHERE name = (SELECT full_name FROM clients WHERE client_id = ?) AND role = 'client'");
if ($user_query) {
    $user_query->bind_param("i", $client_id);
    $user_query->execute();
    $user_result = $user_query->get_result();
    $user_data = $user_result->fetch_assoc();
    
    if ($user_data) {
        $user_id = $user_data['user_id'];
        // Count unread notifications for both user_id and client_id
        $notif_query = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE (user_id = ? OR client_id = ?) AND (status = 'Unread' OR status = 'unread' OR status = 0)");
        $notif_query->bind_param("ii", $user_id, $client_id);
        $notif_query->execute();
        $notif_result = $notif_query->get_result();
        $unread_data = $notif_result->fetch_assoc();
        $unread_count = $unread_data['unread_count'];
        
        // Debug: Log the notification count
        error_log("Client ID: $client_id, User ID: $user_id, Unread Count: $unread_count");
    } else {
        // If no user found, still check for notifications by client_id
        $notif_query = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE client_id = ? AND (status = 'Unread' OR status = 'unread' OR status = 0)");
        $notif_query->bind_param("i", $client_id);
        $notif_query->execute();
        $notif_result = $notif_query->get_result();
        $unread_data = $notif_result->fetch_assoc();
        $unread_count = $unread_data['unread_count'];
        
        error_log("No user found for client ID: $client_id, but found $unread_count notifications by client_id");
    }
} else {
    $unread_count = 0;
    error_log("User query failed for client ID: $client_id");
}

// Get total medicine requests
$medicine_count = $conn->query("SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN status = 'Approved' THEN 1 END) as approved,
    COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending
    FROM pharmaceutical_requests 
    WHERE client_id = " . $_SESSION['client_id'])->fetch_assoc();

// Get animals count
$stmt = $conn->prepare("SELECT 
    COUNT(CASE WHEN animal_type = 'Livestock' THEN 1 END) as livestock_count,
    SUM(CASE WHEN animal_type = 'Poultry' THEN quantity ELSE 0 END) as poultry_count,
    COUNT(CASE WHEN health_status = 'Healthy' AND animal_type = 'Livestock' THEN 1 END) as healthy_livestock,
    SUM(CASE WHEN health_status = 'Healthy' AND animal_type = 'Poultry' THEN quantity ELSE 0 END) as healthy_poultry
    FROM livestock_poultry WHERE client_id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$counts = $stmt->get_result()->fetch_assoc();




// Remove debug code
// var_dump($client);
// var_dump($medicine_count);
// var_dump($counts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - Bago City Veterinary Office</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Base styles */
        body {
            background-color: #6c63ff;
        }
        .container-fluid {
            padding-left: 0;
            padding-right: 0;
            overflow-x: hidden;
        }
        
        /* Remove duplicate sidebar styles - handled by client_sidebar.php */
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
            
            .stats-box {
                margin-bottom: 20px;
            }
            
            .stats-box .display-4 {
                font-size: 2.5rem;
            }
            
            .important-updates {
                padding: 20px;
            }
            
            .update-item {
                padding: 15px;
                margin-bottom: 10px;
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
            
            .stats-box {
                padding: 20px 15px;
            }
            
            .stats-box .display-4 {
                font-size: 2rem;
            }
            
            .important-updates {
                padding: 15px;
            }
            
            .update-item {
                padding: 12px;
                flex-direction: column;
                text-align: center;
            }
            
            .update-item .ms-3 {
                margin-left: 0 !important;
                margin-top: 10px;
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
            
            .stats-box {
                padding: 15px 10px;
            }
            
            .stats-box .display-4 {
                font-size: 1.8rem;
            }
        }

        /* Add hover effect for table rows */
        .table tbody tr:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }

        /* Update stat box styling to match dashboard */
        .stat-box {
            background: white;
            border: 1px solid #eee;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        /* Sidebar nav-link styles handled by client_sidebar.php */ 
        /* ML Insights Style Metric Cards */
        .metric-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 20px 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            height: 180px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .metric-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .metric-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }
        
        .metric-card:hover::after {
            left: 100%;
        }
        
        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: rgba(255, 255, 255, 0.3);
        }
        
        
        .metric-card .metric-title {
            font-size: 1rem;
            font-weight: 500;
            opacity: 0.9;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .metric-card .metric-value {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 12px;
            line-height: 1;
        }
        
        .metric-card .metric-detail {
            font-size: 0.75rem;
            opacity: 0.8;
            font-weight: 400;
            margin-bottom: 0;
        }
        
        .metric-card .metric-badges {
            margin-top: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            justify-content: center;
        }
        
        .metric-card .metric-badges .badge {
            font-size: 0.7rem;
            font-weight: 500;
            border-radius: 12px;
            padding: 4px 8px;
            background: rgba(255, 255, 255, 0.2) !important;
            color: white !important;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        /* Individual card color schemes */
        .metric-card:nth-child(1) {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .metric-card:nth-child(2) {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .metric-card:nth-child(3) {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .metric-card:nth-child(4) {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        /* Loading animation for metric cards */
        .metric-card.loading {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .metric-card {
                margin-bottom: 15px;
                padding: 15px 12px;
                height: 160px;
            }
            
            
            .metric-card .metric-value {
                font-size: 1.8rem;
            }
            
            .metric-card .metric-title {
                font-size: 0.9rem;
                margin-bottom: 12px;
            }
            
            .metric-card .metric-detail {
                font-size: 0.7rem;
                margin-bottom: 0;
            }
        }

        .updates-section {
            background: #FFF3CD;
            border-radius: 8px;
            padding: 10px;
            transition: transform 0.18s cubic-bezier(.4,2,.6,1), box-shadow 0.18s cubic-bezier(.4,2,.6,1);
            box-shadow: 0 2px 8px rgba(255,193,7,0.10), 0 1.5px 6px rgba(0,0,0,0.07);
            cursor: pointer;
            border: 1px solid #000;
            max-width: 100%;
            margin: 0 auto;
        }
        .updates-section:hover {
            transform: scale(1.025) translateY(-2px);
            box-shadow: 0 8px 24px rgba(255,193,7,0.18), 0 3px 12px rgba(0,0,0,0.10);
            z-index: 2;
        }
        .updates-section li {
            padding: 10px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            margin-left: 10px;
        }
        .updates-section li:last-child {
            border-bottom: none;
        }
        .updates-section a {
            color: #333;
            font-weight: 500;
            transition: all 0.2s ease;
            font-size: 1.1rem;
            padding-left: 15px;
        }
        .updates-section a:hover {
            color: #6c63ff;
        }
        .updates-section .badge {
            font-size: 0.85rem;
            padding: 0.25rem 0.5rem;
            border-radius: 40px;
            font-weight: 600;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .updates-section li {
            margin-bottom: 10px;
        }

        .updates-section i {
            margin-right: 10px;
        }



        .table th, .table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            cursor: pointer;
            border: 1px solid #000;
        }

        .badge {
            padding: 5px 10px;
            margin-right: 5px;
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
                <h2 class="mb-4">Welcome, <?php echo htmlspecialchars($client['full_name']); ?></h2>
                
                <!-- Statistics -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="metric-card" onclick="openLivestockModal()" style="cursor: pointer;" title="Click to view livestock details">
                            <div class="metric-title">Total Livestock</div>
                            <div class="metric-value"><?php echo $counts['livestock_count'] ?? 0; ?></div>
                            <div class="metric-badges">
                                <span class="badge"><?php echo $counts['healthy_livestock'] ?? 0; ?> Healthy</span>
                                <span class="badge"><?php echo ($counts['livestock_count'] - $counts['healthy_livestock']) ?? 0; ?> Need Attention</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="metric-card" onclick="openPoultryModal()" style="cursor: pointer;" title="Click to view poultry details">
                            <div class="metric-title">Poultry</div>
                            <div class="metric-value"><?php echo $counts['poultry_count'] ?? 0; ?></div>
                            <div class="metric-badges">
                                <span class="badge"><?php echo $counts['healthy_poultry'] ?? 0; ?> Healthy</span>
                                <span class="badge"><?php echo ($counts['poultry_count'] - $counts['healthy_poultry']) ?? 0; ?> Need Attention</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="metric-card" onclick="openMedicineModal()" style="cursor: pointer;" title="Click to view medicine requests">
                            <div class="metric-title">Medicines Request</div>
                            <div class="metric-value"><?php echo $medicine_count['total']; ?></div>
                            <div class="metric-badges">
                                <span class="badge"><?php echo $medicine_count['approved']; ?> Approved</span>
                                <span class="badge"><?php echo $medicine_count['pending']; ?> Pending</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Important Updates -->
                <div class="updates-section mt-4">
                    <style>
                        .updates-section h5 {
                            font-size: 1.2rem;
                            font-weight: 600;
                            margin-left: 10px;
                        }
                    </style>
                    <h5><i class="fas fa-info-circle"></i> Important Updates</h5>
                    <ul class="list-unstyled">
                        <li>
                            <a href="client_notifications.php" class="d-flex align-items-center text-decoration-none">
                                <i class="fas fa-bell text-primary me-2"></i>
                                <span>Notifications</span>
                                <span class="badge <?php echo $unread_count > 0 ? 'bg-danger' : 'bg-secondary'; ?> ms-2"><?php echo $unread_count; ?></span>
                            </a>
                        </li>
                        <li>
                            <a href="client_pharmaceuticals_request.php" class="d-flex align-items-center text-decoration-none">
                                <i class="fas fa-syringe text-primary me-2"></i>
                                <span>Medicine Requests</span>
                                <?php if ($medicine_count['pending'] > 0): ?>
                                    <span class="badge bg-warning ms-2"><?php echo $medicine_count['pending']; ?> pending</span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li>
                            <a href="client_animals_owned.php" class="d-flex align-items-center text-decoration-none">
                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                <span>Health Alerts</span>
                                <?php if (isset($counts['livestock_count']) && isset($counts['healthy_livestock']) && ($counts['livestock_count'] - $counts['healthy_livestock']) > 0): ?>
                                    <span class="badge bg-warning ms-2"><?php echo ($counts['livestock_count'] - $counts['healthy_livestock']); ?> animals</span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li>
                            <a href="client_request_history.php" class="d-flex align-items-center text-decoration-none">
                                <i class="fas fa-calendar-check text-success me-2"></i>
                                <span>Request History</span>
                            </a>
                        </li>
                    </ul>
                </div>


            </div>
        </div>
    </div>

    <!-- Livestock Details Modal -->
    <div class="modal fade" id="livestockModal" tabindex="-1" aria-labelledby="livestockModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="livestockModalLabel">Livestock Summary</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th><i class="fas fa-paw me-2"></i>Species</th>
                                    <th><i class="fas fa-hashtag me-2"></i>Quantity</th>
                                    <th><i class="fas fa-heartbeat me-2"></i>Health Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Fetch actual livestock data
                                $livestock_query = "SELECT species, quantity, health_status FROM livestock_poultry 
                                                   WHERE client_id = ? AND animal_type = 'Livestock' 
                                                   ORDER BY species";
                                $stmt = $conn->prepare($livestock_query);
                                $stmt->bind_param("i", $client_id);
                                $stmt->execute();
                                $livestock_result = $stmt->get_result();
                                
                                if ($livestock_result->num_rows > 0):
                                    while ($animal = $livestock_result->fetch_assoc()):
                                        $health_class = $animal['health_status'] === 'Healthy' ? 'text-success' : 'text-warning';
                                        $health_icon = $animal['health_status'] === 'Healthy' ? 'fa-check-circle' : 'fa-exclamation-triangle';
                                        $badge_class = $animal['health_status'] === 'Healthy' ? 'bg-success' : 'bg-warning';
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($animal['species']); ?></td>
                                    <td><span class="badge bg-primary"><?php echo $animal['quantity']; ?></span></td>
                                    <td>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <i class="fas <?php echo $health_icon; ?> me-1"></i><?php echo $animal['health_status']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        <i class="fas fa-info-circle me-2"></i>No livestock found
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="client_animals_owned.php" class="btn btn-primary">View All Animals</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Poultry Details Modal -->
    <div class="modal fade" id="poultryModal" tabindex="-1" aria-labelledby="poultryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="poultryModalLabel">Poultry Summary</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th><i class="fas fa-egg me-2"></i>Species</th>
                                    <th><i class="fas fa-hashtag me-2"></i>Quantity</th>
                                    <th><i class="fas fa-heartbeat me-2"></i>Health Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Fetch actual poultry data
                                $poultry_query = "SELECT species, quantity, health_status FROM livestock_poultry 
                                                 WHERE client_id = ? AND animal_type = 'Poultry' 
                                                 ORDER BY species";
                                $stmt = $conn->prepare($poultry_query);
                                $stmt->bind_param("i", $client_id);
                                $stmt->execute();
                                $poultry_result = $stmt->get_result();
                                
                                if ($poultry_result->num_rows > 0):
                                    while ($animal = $poultry_result->fetch_assoc()):
                                        $health_class = $animal['health_status'] === 'Healthy' ? 'text-success' : 'text-warning';
                                        $health_icon = $animal['health_status'] === 'Healthy' ? 'fa-check-circle' : 'fa-exclamation-triangle';
                                        $badge_class = $animal['health_status'] === 'Healthy' ? 'bg-success' : 'bg-warning';
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($animal['species']); ?></td>
                                    <td><span class="badge bg-primary"><?php echo $animal['quantity']; ?></span></td>
                                    <td>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <i class="fas <?php echo $health_icon; ?> me-1"></i><?php echo $animal['health_status']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        <i class="fas fa-info-circle me-2"></i>No poultry found
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="client_animals_owned.php" class="btn btn-primary">View All Animals</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Medicine Requests Modal -->
    <div class="modal fade" id="medicineModal" tabindex="-1" aria-labelledby="medicineModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="medicineModalLabel">Medicine Requests Summary</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th><i class="fas fa-paw me-2"></i>Species</th>
                                    <th><i class="fas fa-stethoscope me-2"></i>Symptoms</th>
                                    <th><i class="fas fa-tasks me-2"></i>Status</th>
                                    <th><i class="fas fa-calendar me-2"></i>Request Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Fetch actual medicine request data
                                $medicine_query = "SELECT species, symptoms, status, request_date FROM pharmaceutical_requests 
                                                 WHERE client_id = ? 
                                                 ORDER BY request_date DESC";
                                $stmt = $conn->prepare($medicine_query);
                                $stmt->bind_param("i", $client_id);
                                $stmt->execute();
                                $medicine_result = $stmt->get_result();
                                
                                if ($medicine_result->num_rows > 0):
                                    while ($request = $medicine_result->fetch_assoc()):
                                        $status_class = $request['status'] === 'Approved' ? 'bg-success' : 
                                                      ($request['status'] === 'Pending' ? 'bg-warning' : 'bg-danger');
                                        $status_icon = $request['status'] === 'Approved' ? 'fa-check-circle' : 
                                                      ($request['status'] === 'Pending' ? 'fa-clock' : 'fa-times-circle');
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($request['species']); ?></td>
                                    <td>
                                        <div class="text-wrap" style="max-width: 250px;" title="<?php echo htmlspecialchars($request['symptoms']); ?>">
                                            <?php echo htmlspecialchars($request['symptoms']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <i class="fas <?php echo $status_icon; ?> me-1"></i><?php echo $request['status']; ?>
                                        </span>
                                    </td>
                                    <td class="text-muted"><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="fas fa-info-circle me-2"></i>No medicine requests found
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="client_pharmaceuticals_request.php" class="btn btn-primary">Make New Request</a>
                    <a href="client_request_history.php" class="btn btn-outline-primary">View History</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openLivestockModal() {
            const modal = new bootstrap.Modal(document.getElementById('livestockModal'));
            modal.show();
        }

        function openPoultryModal() {
            const modal = new bootstrap.Modal(document.getElementById('poultryModal'));
            modal.show();
        }

        function openMedicineModal() {
            const modal = new bootstrap.Modal(document.getElementById('medicineModal'));
            modal.show();
        }
    </script>
</body>
</html>