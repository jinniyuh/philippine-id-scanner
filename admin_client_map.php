<?php
session_start();
include 'includes/conn.php';
include 'includes/geotagging_helper.php';
include 'includes/auto_location_assigner.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Log activity - viewing client locations map
$user_id = $_SESSION['user_id'];
$action = "Viewed Client Locations Map";
$log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, timestamp) VALUES (?, ?, NOW())");
$log_stmt->bind_param("is", $user_id, $action);
$log_stmt->execute();
$log_stmt->close();

// Get statistics first
$stats = getDisseminatedAnimalsLocationStats($conn);

// Auto-assign locations to clients who don't have coordinates
$auto_assign_results = autoAssignAllClientLocations($conn);

// Log auto-assignment activity if any clients were assigned
if ($auto_assign_results['success'] > 0) {
    $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, timestamp) VALUES (?, ?, NOW())");
    $action = "Auto-assigned locations to " . $auto_assign_results['success'] . " clients based on barangay";
    $log_stmt->bind_param("is", $user_id, $action);
    $log_stmt->execute();
    $log_stmt->close();
}

// Use helper functions for consistency
$all_clients = getClientsWithDisseminatedAnimals($conn);
$clients = getClientsWithDisseminatedAnimalsAndLocation($conn);

// All 24 barangays of Bago City
$barangays = [
    'Abuanan',
    'Alianza',
    'Atipuluan',
    'Bacong-Montilla',
    'Bagroy',
    'Balingasag',
    'Binubuhan',
    'Busay',
    'Calumangan',
    'Caridad',
    'Dulao',
    'Ilijan',
    'Lag-Asan',
    'Ma-ao',
    'Mailum',
    'Malingin',
    'Napoles',
    'Pacol',
    'Poblacion',
    'Sagasa',
    'Sampinit',
    'Tampalon',
    'Tabunan',
    'Taloc'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Locations Map - Bago City Veterinary Office</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet CSS for maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- MarkerCluster CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
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
        
        /* Enhanced Profile Section */
        .admin-profile {
            background: none;
            padding: 0;
            border-radius: 0;
            border: none;
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
        
        /* Ensure dropdown container is properly positioned */
        .dropdown {
            position: relative;
        }
        
        .dropdown-item {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
            color: #6c63ff;
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
        .activity-icon.icon-approve { background: #28a745; color: white; }
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
        .system-title {
            display: flex;
            align-items: center;
        }
        .system-title img {
            width: 50px;
            height: 50px;
            margin-right: 15px;
        }
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
            cursor: pointer;
            height: 150px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 1;
            pointer-events: auto;
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
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .metric-card {
                margin-bottom: 15px;
                padding: 15px 12px;
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
        #clientMap {
            height: 600px;
            width: 100%;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        #clientMap.expanded {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 9999;
            border-radius: 0;
            border: none;
        }
        
        .map-expand-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.8);
            z-index: 9998;
            display: none;
        }
        
        .map-expand-overlay.show {
            display: block;
        }
        
        .map-expanded-content {
            display: none;
        }
        
        .map-expanded-content.show {
            display: block;
        }
        
        .map-controls.expanded {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            background: rgba(255,255,255,0.9);
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .marker-cluster {
            background-color: rgba(220, 53, 69, 0.8);
            border-radius: 50%;
            color: white;
            height: 40px;
            line-height: 40px;
            text-align: center;
            width: 40px;
            font-weight: bold;
            font-size: 14px;
            border: 3px solid white;
            box-shadow: 0 3px 8px rgba(0,0,0,0.4);
        }
        
        .marker-cluster-small {
            background-color: rgba(220, 53, 69, 0.6);
        }
        
        .marker-cluster-medium {
            background-color: rgba(220, 53, 69, 0.8);
        }
        
        .marker-cluster-large {
            background-color: rgba(220, 53, 69, 1);
        }
        .sidebar {
            background-color: #6c63ff;
            width: 312px;
            min-width: 312px;
            max-width: 312px;
            color: #fff;
            padding: 2px 0px 10px 0;
            overflow-y: auto;
            z-index: 100;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
        }
        .sidebar .nav-link {
            font-size: 1.1rem;
            padding: 10px;
            margin-left: 0;
            border-radius: 8px;
            transition: background-color 0.3s ease;
            display: block;
            text-decoration: none;
            color: #fff;
            white-space: nowrap;
            padding-left: 23px;
        }
        .sidebar .nav-link:hover, 
        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            border-right: none;
        }
        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.3);
        }
        .sidebar .nav-link i {
            margin-right: 8px;
            margin-left: 0;
        }
        .sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        .map-controls {
            margin-bottom: 20px;
        }
        
        /* Ensure modals appear above everything */
        .modal {
            z-index: 1050;
        }
        .modal-backdrop {
            z-index: 1040;
        }
        .modal-dialog {
            z-index: 1060;
        }
        
        /* Custom location marker styles */
        .custom-location-marker {
            transition: all 0.3s ease;
        }
        
        .custom-location-marker:hover {
            transform: scale(1.1);
        }
        
        /* Marker cluster styles */
        .marker-cluster-small {
            background-color: rgba(108, 99, 255, 0.6);
        }
        
        .marker-cluster-small div {
            background-color: rgba(108, 99, 255, 0.8);
        }
        
        .marker-cluster-medium {
            background-color: rgba(255, 193, 7, 0.6);
        }
        
        .marker-cluster-medium div {
            background-color: rgba(255, 193, 7, 0.8);
        }
        
        .marker-cluster-large {
            background-color: rgba(220, 53, 69, 0.6);
        }
        
        .marker-cluster-large div {
            background-color: rgba(220, 53, 69, 0.8);
        }
        
        /* Custom popup styles */
        .custom-popup .leaflet-popup-content-wrapper {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border: 2px solid #6c63ff;
        }
        
        .custom-popup .leaflet-popup-content {
            margin: 0;
            padding: 0;
        }
        
        .custom-popup .leaflet-popup-tip {
            background: white;
            border: 2px solid #6c63ff;
        }
        
        .custom-popup .leaflet-popup-close-button {
            color: #6c63ff;
            font-size: 18px;
            font-weight: bold;
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
                    <div class="d-flex align-items-center">
                        <h2 class="mb-0 me-3">Client Locations Map</h2>
                    </div>
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

                <!-- Quick Status Check -->
                <?php if (count($all_clients) === 0 && $stats['total_with_disseminated'] > 0): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>⚠️ Data Loading Issue:</strong> Stats show <?php echo $stats['total_with_disseminated']; ?> clients, but query returned 0 records.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php elseif (count($all_clients) > 0): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>✅ Success:</strong> Loaded <?php echo count($all_clients); ?> clients with disseminated animals!
                    <?php if ($auto_assign_results['success'] > 0): ?>
                        <br><strong>📍 Auto-Assigned:</strong> <?php echo $auto_assign_results['success']; ?> clients automatically pinned to their barangays!
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="metric-card" role="button" data-bs-toggle="modal" data-bs-target="#disseminatedAnimalsModal">
                            <div class="metric-title">With Disseminated Animals</div>
                            <div class="metric-value"><?php echo number_format($stats['total_with_disseminated']); ?></div>
                            <div class="metric-detail">Click to view details</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="metric-card" role="button" data-bs-toggle="modal" data-bs-target="#withLocationModal">
                            <div class="metric-title">With Location</div>
                            <div class="metric-value"><?php echo number_format($stats['with_location']); ?></div>
                            <div class="metric-detail">Click to view details</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="metric-card" role="button" data-bs-toggle="modal" data-bs-target="#withoutLocationModal">
                            <div class="metric-title">Without Location</div>
                            <div class="metric-value"><?php echo number_format($stats['without_location']); ?></div>
                            <div class="metric-detail">Click to view details</div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter Controls -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" class="form-control" id="searchClients" placeholder="Search by client name...">
                            <button class="btn btn-outline-secondary" type="button" id="searchBtn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="filterBarangay">
                            <option value="">All Barangays</option>
                            <?php foreach ($barangays as $barangay): ?>
                                <option value="<?php echo htmlspecialchars($barangay); ?>">
                                    <?php echo htmlspecialchars($barangay); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="filterStatus">
                            <option value="">All Status</option>
                            <option value="Complied">Complied</option>
                            <option value="Pending">Pending</option>
                            <option value="Non-Compliant">Non-Compliant</option>
                        </select>
                    </div>
                </div>

                <!-- Map Controls -->
                <div class="map-controls mb-3">
                    <button class="btn btn-warning" id="expandMapBtn">
                        <i class="fas fa-expand"></i> Expand Map
                    </button>
                    <button class="btn btn-primary" id="fitBoundsBtn">
                        <i class="fas fa-expand-arrows-alt"></i> Fit All Markers
                    </button>
                    <button class="btn btn-info" id="refreshDataBtn">
                        <i class="fas fa-sync-alt"></i> Refresh Data
                    </button>
                </div>
                
                <!-- Map Container -->
                <div id="clientMap"></div>
                
                <!-- Map Expand Overlay -->
                <div class="map-expand-overlay" id="mapExpandOverlay"></div>
                
                <!-- Barangay Clients Modal -->
                <div class="modal fade" id="barangayClientsModal" tabindex="-1" aria-labelledby="barangayClientsModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="barangayClientsModalLabel">
                                    <i class="fas fa-users"></i> Clients in <span id="barangayName"></span>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <strong><span id="clientCount"></span> clients</strong> found in this barangay
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th><i class="fas fa-user"></i> Client Name</th>
                                                <th><i class="fas fa-map-marker-alt"></i> Barangay</th>
                                                <th><i class="fas fa-flag"></i> Status</th>
                                                <th><i class="fas fa-phone"></i> Contact</th>
                                            </tr>
                                        </thead>
                                        <tbody id="barangayClientsTableBody">
                                            <!-- Client data will be populated here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times"></i> Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- No Results Message -->
                <div id="noResultsMessage" class="alert alert-info mt-3" style="display: none;">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>No clients found</strong> matching the selected filters. Try adjusting your search criteria.
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet JS for maps -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- MarkerCluster JS -->
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    
    <!-- Disseminated Animals Modal -->
    <div class="modal fade" id="disseminatedAnimalsModal" tabindex="-1" aria-labelledby="disseminatedAnimalsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="disseminatedAnimalsModalLabel">
                        <i class="fas fa-users me-2"></i>Clients with Disseminated Animals
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Client Name</th>
                                    <th>Contact Number</th>
                                    <th>Location Status</th>
                                    <th>Compliance Status</th>
                                    <th>Registered Date</th>
                                </tr>
                            </thead>
                            <tbody id="disseminatedClientsTableBody">
                                <!-- Dynamically populated with JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- With Location Modal -->
    <div class="modal fade" id="withLocationModal" tabindex="-1" aria-labelledby="withLocationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="withLocationModalLabel">
                        <i class="fas fa-map-marker-alt me-2"></i>Clients with Location Data
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Client Name</th>
                                    <th>Barangay</th>
                                    <th>Coordinates</th>
                                    <th>Contact Number</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="withLocationTableBody">
                                <!-- Dynamically populated with JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Without Location Modal -->
    <div class="modal fade" id="withoutLocationModal" tabindex="-1" aria-labelledby="withoutLocationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="withoutLocationModalLabel">
                        <i class="fas fa-question-circle me-2"></i>Clients without Location Data
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Client Name</th>
                                    <th>Barangay</th>
                                    <th>Contact Number</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="withoutLocationTableBody">
                                <!-- Dynamically populated with JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Location Picker Modal -->
    <div class="modal fade" id="locationPickerModal" tabindex="-1" aria-labelledby="locationPickerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="locationPickerModalLabel">
                        <i class="fas fa-map-marker-alt me-2"></i>Add Client Location
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <h6>Client Information:</h6>
                        <p><strong>Name:</strong> <span id="updateClientName"></span></p>
                        <p><strong>Barangay:</strong> <span id="updateClientBarangay"></span></p>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Instructions:</h6>
                        <p class="text-muted">Click on the map below to select the client's location. A marker will appear at the selected location.</p>
                    </div>
                    
                    <!-- Map Container -->
                    <div id="locationPickerMap" style="height: 400px; width: 100%; border-radius: 8px; border: 2px solid #e9ecef;"></div>
                    
                    <!-- Selected Coordinates Display -->
                    <div class="mt-3">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="selectedCoordinates">Click on the map to select a location</span>
                        </div>
                    </div>
                    
                    <!-- Hidden inputs for coordinates -->
                    <input type="hidden" id="updateClientId" value="">
                    <input type="hidden" id="selectedLat" value="">
                    <input type="hidden" id="selectedLng" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveClientLocation()">
                        <i class="fas fa-save me-2"></i>Save Location
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to logout?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="logout.php" class="btn btn-danger">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Convert PHP clients data to JavaScript
        const clients = <?php echo json_encode($clients); ?>; // Clients with location only
        const allClients = <?php echo json_encode($all_clients); ?>; // All clients with disseminated animals
        
        // Initialize map
        let map;
        let markerCluster;
        let markers = [];
        
        // Initialize everything after DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap dropdowns
            const dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
            const dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
            
            // Add event listeners to populate modals when opened
            document.getElementById('disseminatedAnimalsModal').addEventListener('show.bs.modal', function() {
                populateDisseminatedModal();
            });
            
            document.getElementById('withLocationModal').addEventListener('show.bs.modal', function() {
                populateWithLocationModal();
            });
            
            document.getElementById('withoutLocationModal').addEventListener('show.bs.modal', function() {
                populateWithoutLocationModal();
            });
            
            // Initialize the map
            initializeMap();
            
            // Add all client markers
            console.log('Loading clients with location data:', clients);
            clients.forEach(client => {
                if (client.latitude && client.longitude) {
                    console.log(`Adding marker for ${client.full_name} at ${client.latitude}, ${client.longitude}`);
                    addClientMarker(client);
                }
            });
            
            // Fit map to show all markers or default to Bago City bounds
            if (markers.length > 0) {
                const group = new L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.1));
            } else {
                // If no markers, fit to all Bago City barangays
                const bagoCityBounds = L.latLngBounds(
                    L.latLng(10.51, 122.81), // Southwest corner - covers all barangays
                    L.latLng(10.56, 122.86)  // Northeast corner - covers all barangays
                );
                map.fitBounds(bagoCityBounds);
            }
            
            // Populate modals initially
            populateDisseminatedModal();
            populateWithLocationModal();
            populateWithoutLocationModal();
            
            // Add button event listeners after everything is loaded
            setupButtonEventListeners();
        });
        
        function setupButtonEventListeners() {
            // Expand Map button
            const expandMapBtn = document.getElementById('expandMapBtn');
            if (expandMapBtn) {
                expandMapBtn.addEventListener('click', function() {
                    toggleMapExpand();
                });
            }
            
            // Fit All Markers button
            const fitBoundsBtn = document.getElementById('fitBoundsBtn');
            if (fitBoundsBtn) {
                fitBoundsBtn.addEventListener('click', function() {
                    if (markers.length > 0) {
                        const group = new L.featureGroup(markers);
                        map.fitBounds(group.getBounds().pad(0.1));
                    }
                });
            }
            
            // Refresh Data button
            const refreshDataBtn = document.getElementById('refreshDataBtn');
            if (refreshDataBtn) {
                refreshDataBtn.addEventListener('click', function() {
                    location.reload();
                });
            }
        }
        
        // Map expand functionality
        let isMapExpanded = false; // Start normal by default
        
        function toggleMapExpand() {
            console.log('Toggle map expand clicked, current state:', isMapExpanded);
            
            const mapContainer = document.getElementById('clientMap');
            const expandBtn = document.getElementById('expandMapBtn');
            const overlay = document.getElementById('mapExpandOverlay');
            const mapControls = document.querySelector('.map-controls');
            
            console.log('Elements found:', { mapContainer, expandBtn, overlay });
            
            if (!isMapExpanded) {
                console.log('Expanding map...');
                
                // Expand the map
                mapContainer.classList.add('expanded');
                overlay.classList.add('show');
                if (mapControls) mapControls.classList.add('expanded');
                expandBtn.innerHTML = '<i class="fas fa-compress"></i> Exit Fullscreen';
                expandBtn.classList.remove('btn-warning');
                expandBtn.classList.add('btn-danger');
                isMapExpanded = true;
                
                console.log('Map expanded, button should now show Exit Fullscreen');
                
                // Hide page content
                document.body.style.overflow = 'hidden';
                
                // Trigger map resize after a short delay to ensure CSS transition completes
                setTimeout(() => {
                    map.invalidateSize();
                }, 300);
                
                // Add escape key listener
                document.addEventListener('keydown', handleEscapeKey);
                
            } else {
                console.log('Collapsing map...');
                
                // Collapse the map
                mapContainer.classList.remove('expanded');
                overlay.classList.remove('show');
                if (mapControls) mapControls.classList.remove('expanded');
                expandBtn.innerHTML = '<i class="fas fa-expand"></i> Expand Map';
                expandBtn.classList.remove('btn-danger');
                expandBtn.classList.add('btn-warning');
                isMapExpanded = false;
                
                console.log('Map collapsed, button should now show Expand Map');
                
                // Show page content
                document.body.style.overflow = 'auto';
                
                // Trigger map resize
                setTimeout(() => {
                    map.invalidateSize();
                }, 300);
                
                // Remove escape key listener
                document.removeEventListener('keydown', handleEscapeKey);
            }
        }
        
        function handleEscapeKey(event) {
            if (event.key === 'Escape' && isMapExpanded) {
                toggleMapExpand();
            }
        }
        
        // Helper function to get barangay from cluster
        function getBarangayFromCluster(cluster) {
            const markers = cluster.getAllChildMarkers();
            if (markers.length > 0) {
                return markers[0].clientData?.barangay || 'Unknown';
            }
            return 'Unknown';
        }
        
        // Function to show barangay clients modal
        function showBarangayClientsModal(clients, latlng) {
            console.log('Showing modal for', clients.length, 'clients');
            
            // Get barangay name from first client
            const barangayName = clients[0]?.clientData?.barangay || 'Unknown Barangay';
            
            // Update modal title and count
            document.getElementById('barangayName').textContent = barangayName;
            document.getElementById('clientCount').textContent = clients.length;
            
            // Populate table
            const tbody = document.getElementById('barangayClientsTableBody');
            tbody.innerHTML = '';
            
            clients.forEach(clientMarker => {
                const client = clientMarker.clientData;
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <strong>${client.full_name || 'Unknown'}</strong>
                    </td>
                    <td>
                        <i class="fas fa-map-marker-alt text-danger"></i>
                        ${barangayName}
                    </td>
                    <td>
                        <span class="badge ${client.status === 'Complied' ? 'bg-success' : 'bg-warning'}">
                            ${client.status || 'Unknown'}
                        </span>
                    </td>
                    <td>
                        <i class="fas fa-phone text-primary"></i>
                        ${client.contact_number || 'No contact'}
                    </td>
                `;
                tbody.appendChild(row);
            });
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('barangayClientsModal'));
            modal.show();
        }
        
        function initializeMap() {
            // Initialize Leaflet map centered on Bago City
            map = L.map('clientMap').setView([10.5378, 122.8369], 12);
            
            // Add OpenStreetMap tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(map);
            
            // Create marker cluster group
            markerCluster = L.markerClusterGroup({
                chunkedLoading: true,
                maxClusterRadius: 50,
                spiderfyOnMaxZoom: false,
                showCoverageOnHover: false,
                zoomToBoundsOnClick: false, // Prevent zooming on click
                iconCreateFunction: function(cluster) {
                    const count = cluster.getChildCount();
                    const barangay = getBarangayFromCluster(cluster);
                    
                    let className = 'marker-cluster-small';
                    if (count > 20) {
                        className = 'marker-cluster-large';
                    } else if (count > 10) {
                        className = 'marker-cluster-medium';
                    }
                    
                    return L.divIcon({
                        html: `<div><span>${count}</span></div>`,
                        className: 'marker-cluster ' + className,
                        iconSize: L.point(40, 40)
                    });
                }
            }).addTo(map);
            
            // Add click event to marker cluster to show modal
            markerCluster.on('clusterclick', function(e) {
                e.originalEvent.preventDefault();
                e.originalEvent.stopPropagation();
                
                const cluster = e.layer;
                const clients = cluster.getAllChildMarkers();
                const count = clients.length;
                
                if (count > 1) {
                    showBarangayClientsModal(clients, e.latlng);
                }
            });
            
            // Bounds to match the EXACT red boundary line of Bago City
            // Based on the actual city boundary shown in the image
            const bagoCityBounds = L.latLngBounds(
                L.latLng(10.42, 122.75), // Southwest corner - excludes Valladolid, San Enrique
                L.latLng(10.62, 122.92)  // Northeast corner - excludes La Carlota, Amayco, etc.
            );
            
            // Set bounds to show only Bago City
            map.setMaxBounds(bagoCityBounds);
            map.setMinZoom(11);  // Allow viewing whole city
            map.setMaxZoom(18);  // Allow zooming in to streets
            
            // Force map to stay within bounds
            map.on('drag', function() {
                map.panInsideBounds(bagoCityBounds, { animate: false });
            });
            
            // Fit to show whole Bago City on load
            map.fitBounds(bagoCityBounds);
        }
        
        function addClientMarker(client) {
            // All client markers are now red
            const iconColor = '#dc3545'; // Red color for all clients
            
            // Ensure coordinates are properly parsed
            const lat = parseFloat(client.latitude);
            const lng = parseFloat(client.longitude);
            
            console.log(`Creating marker for ${client.full_name} at ${lat}, ${lng}`);
            console.log('Client data:', client);
            
            const marker = L.marker([lat, lng], {
                icon: L.divIcon({
                    className: 'custom-location-marker',
                    html: `
                        <div style="
                            background-color: ${iconColor};
                            width: 30px;
                            height: 30px;
                            border-radius: 50% 50% 50% 0;
                            border: 3px solid white;
                            box-shadow: 0 3px 8px rgba(0,0,0,0.4);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-weight: bold;
                            color: white;
                            font-size: 14px;
                            transform: rotate(-45deg);
                        ">
                            <span style="transform: rotate(45deg);">📍</span>
                        </div>
                    `,
                    iconSize: [30, 30],
                    iconAnchor: [15, 30]
                })
            });
            
            // Store client data in marker for cluster functionality
            marker.clientData = client;
            
            // Create popup content with better styling
            const popupContent = `
                <div style="min-width: 250px; padding: 10px;">
                    <div style="text-align: center; margin-bottom: 15px;">
                        <h5 style="margin: 0; color: #333; font-weight: bold;">${client.full_name || 'Unknown Client'}</h5>
                    </div>
                    <div style="border-top: 1px solid #eee; padding-top: 10px;">
                        <p style="margin: 8px 0; font-size: 14px;"><strong>📞 Contact:</strong> ${client.contact_number || 'N/A'}</p>
                        <p style="margin: 8px 0; font-size: 14px;"><strong>🏘️ Barangay:</strong> ${client.barangay || 'No barangay specified'}</p>
                        <p style="margin: 8px 0; font-size: 14px;"><strong>📊 Status:</strong> 
                            <span style="
                                background-color: ${client.status === 'Complied' ? '#28a745' : '#ffc107'}; 
                                color: white; 
                                padding: 2px 8px; 
                                border-radius: 12px; 
                                font-size: 12px;
                                font-weight: bold;
                            ">${client.status || 'Unknown'}</span>
                        </p>
                        <p style="margin: 8px 0; font-size: 12px; color: #666;"><strong>📍 Coordinates:</strong> ${parseFloat(client.latitude).toFixed(6)}, ${parseFloat(client.longitude).toFixed(6)}</p>
                        <p style="margin: 8px 0; font-size: 12px; color: #666;"><strong>📅 Registered:</strong> ${client.created_at ? new Date(client.created_at).toLocaleDateString() : 'N/A'}</p>
                    </div>
                </div>
            `;
            
            // Bind popup with options
            marker.bindPopup(popupContent, {
                maxWidth: 300,
                className: 'custom-popup',
                closeButton: true,
                autoClose: true,
                closeOnClick: true
            });
            
            // Show popup on hover instead of click
            marker.on('mouseover', function() {
                this.openPopup();
            });
            
            marker.on('mouseout', function() {
                this.closePopup();
            });
            
            // Prevent zooming when clicking marker
            marker.on('click', function(e) {
                e.originalEvent.preventDefault();
                e.originalEvent.stopPropagation();
                console.log('Marker clicked for:', client.full_name);
                console.log('Popup should open now');
            });
            
            markerCluster.addLayer(marker);
            markers.push(marker);
            
            return marker;
        }
        
        // Filter functionality
        let filteredClients = [...clients]; // Copy of original clients array
        let filteredAllClients = [...allClients]; // Copy of all clients array
        
        function applyFilters() {
            const searchTerm = document.getElementById('searchClients').value.toLowerCase();
            const selectedBarangay = document.getElementById('filterBarangay').value;
            const selectedStatus = document.getElementById('filterStatus').value;
            
            filteredClients = clients.filter(client => {
                const matchesSearch = !searchTerm || client.full_name.toLowerCase().includes(searchTerm);
                const matchesBarangay = !selectedBarangay || client.barangay === selectedBarangay;
                const matchesStatus = !selectedStatus || client.status === selectedStatus;
                
                return matchesSearch && matchesBarangay && matchesStatus;
            });
            
            // Also filter ALL clients (including those without location)
            filteredAllClients = allClients.filter(client => {
                const matchesSearch = !searchTerm || client.full_name.toLowerCase().includes(searchTerm);
                const matchesBarangay = !selectedBarangay || client.barangay === selectedBarangay;
                const matchesStatus = !selectedStatus || client.status === selectedStatus;
                
                return matchesSearch && matchesBarangay && matchesStatus;
            });
            
            // Update stats cards
            updateStatsCards();
            
            // Update map markers
            updateMapMarkers();
            
            // Update modal tables with filtered data
            populateDisseminatedModal();
            populateWithLocationModal();
            populateWithoutLocationModal();
        }
        
        function updateStatsCards() {
            const totalWithDisseminated = filteredAllClients.length; // Use filteredAllClients
            const withLocation = filteredAllClients.filter(client => client.latitude && client.longitude).length;
            const withoutLocation = totalWithDisseminated - withLocation;
            
            // Update the metric values
            document.querySelectorAll('.metric-value')[0].textContent = totalWithDisseminated;
            document.querySelectorAll('.metric-value')[1].textContent = withLocation;
            document.querySelectorAll('.metric-value')[2].textContent = withoutLocation;
        }
        
        // Function to populate "Disseminated Animals" modal
        function populateDisseminatedModal() {
            const tbody = document.getElementById('disseminatedClientsTableBody');
            tbody.innerHTML = '';
            
            if (!filteredAllClients || filteredAllClients.length === 0) {
                if (allClients.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger"><i class="fas fa-exclamation-triangle"></i> No clients with disseminated animals found in database</td></tr>';
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center"><i class="fas fa-info-circle"></i> No clients match the current filters</td></tr>';
                }
                return;
            }
            
            filteredAllClients.forEach(client => {
                const hasLocation = client.latitude && client.longitude;
                const locationBadge = hasLocation 
                    ? '<span class="badge bg-success">With Location</span>'
                    : '<span class="badge bg-warning">No Location</span>';
                const statusBadge = client.status === 'Complied' 
                    ? '<span class="badge bg-success">' + client.status + '</span>'
                    : '<span class="badge bg-warning">' + client.status + '</span>';
                const createdDate = new Date(client.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                
                const row = `
                    <tr>
                        <td>${client.full_name}</td>
                        <td>${client.contact_number}</td>
                        <td>${locationBadge}</td>
                        <td>${statusBadge}</td>
                        <td>${createdDate}</td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }
        
        // Function to populate "With Location" modal
        function populateWithLocationModal() {
            const tbody = document.getElementById('withLocationTableBody');
            tbody.innerHTML = '';
            
            const clientsWithLocation = filteredAllClients.filter(c => c.latitude && c.longitude);
            
            if (clientsWithLocation.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center">No clients with location found</td></tr>';
                return;
            }
            
            clientsWithLocation.forEach(client => {
                const statusBadge = client.status === 'Complied' 
                    ? '<span class="badge bg-success">' + client.status + '</span>'
                    : '<span class="badge bg-warning">' + client.status + '</span>';
                const coords = parseFloat(client.latitude).toFixed(6) + ', ' + parseFloat(client.longitude).toFixed(6);
                
                const row = `
                    <tr>
                        <td>${client.full_name}</td>
                        <td>${client.barangay || 'N/A'}</td>
                        <td><small class="text-muted">${coords}</small></td>
                        <td>${client.contact_number}</td>
                        <td>${statusBadge}</td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }
        
        // Function to populate "Without Location" modal
        function populateWithoutLocationModal() {
            const tbody = document.getElementById('withoutLocationTableBody');
            tbody.innerHTML = '';
            
            const clientsWithoutLocation = filteredAllClients.filter(c => !c.latitude || !c.longitude);
            
            if (clientsWithoutLocation.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center">All clients have location data</td></tr>';
                return;
            }
            
            clientsWithoutLocation.forEach(client => {
                const statusBadge = client.status === 'Complied' 
                    ? '<span class="badge bg-success">' + client.status + '</span>'
                    : '<span class="badge bg-warning">' + client.status + '</span>';
                
                const row = `
                    <tr>
                        <td>${client.full_name}</td>
                        <td>${client.barangay || 'N/A'}</td>
                        <td>${client.contact_number}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="updateClientLocation(${client.client_id})">
                                <i class="fas fa-map-marker-alt"></i> Add Location
                            </button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }
        
        function updateMapMarkers() {
            // Clear existing markers
            markerCluster.clearLayers();
            markers = [];
            
            // Add filtered markers
            filteredClients.forEach(client => {
                if (client.latitude && client.longitude) {
                    addClientMarker(client);
                }
            });
            
            // Show/hide no results message
            const noResultsMsg = document.getElementById('noResultsMessage');
            if (filteredClients.length === 0) {
                noResultsMsg.style.display = 'block';
            } else {
                noResultsMsg.style.display = 'none';
            }
            
            // Fit bounds to show all filtered markers
            if (markers.length > 0) {
                const group = new L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.1));
            }
        }
        
        // Map control buttons
        
        
        // Event listeners for filters
        document.getElementById('searchClients').addEventListener('input', applyFilters);
        document.getElementById('filterBarangay').addEventListener('change', applyFilters);
        document.getElementById('filterStatus').addEventListener('change', applyFilters);
        document.getElementById('searchBtn').addEventListener('click', applyFilters);
        
        // Function to update client location (for "Add Location" button)
        function updateClientLocation(clientId) {
            // Store the client ID for the update
            document.getElementById('updateClientId').value = clientId;
            
            // Get client details for display
            const client = allClients.find(c => c.client_id == clientId);
            if (client) {
                document.getElementById('updateClientName').textContent = client.full_name;
                document.getElementById('updateClientBarangay').textContent = client.barangay || 'N/A';
            }
            
            // Initialize the location picker map
            initializeLocationPicker();
            
            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('locationPickerModal'));
            modal.show();
        }
        
        // Initialize location picker map
        function initializeLocationPicker() {
            // Remove existing map if it exists
            if (window.locationPickerMap) {
                window.locationPickerMap.remove();
            }
            
            // Create new map centered on Bago City
            window.locationPickerMap = L.map('locationPickerMap').setView([10.5378, 122.8369], 12);
            
            // Add tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(window.locationPickerMap);
            
            // Bounds to match the EXACT red boundary line of Bago City
            const bagoCityBounds = L.latLngBounds(
                L.latLng(10.42, 122.75), // Southwest corner - excludes Valladolid, San Enrique
                L.latLng(10.62, 122.92)  // Northeast corner - excludes La Carlota, Amayco, etc.
            );
            
            // Set bounds to show only Bago City
            window.locationPickerMap.setMaxBounds(bagoCityBounds);
            window.locationPickerMap.setMinZoom(11);  // Allow viewing whole city
            window.locationPickerMap.setMaxZoom(18);  // Allow zooming in to streets
            
            // Force map to stay within bounds
            window.locationPickerMap.on('drag', function() {
                window.locationPickerMap.panInsideBounds(bagoCityBounds, { animate: false });
            });
            
            // Fit to show whole Bago City on load
            window.locationPickerMap.fitBounds(bagoCityBounds);
            
            // Add click event to map
            window.locationPickerMap.on('click', function(e) {
                // Remove existing marker
                if (window.selectedMarker) {
                    window.locationPickerMap.removeLayer(window.selectedMarker);
                }
                
                // Add new marker at clicked location
                window.selectedMarker = L.marker([e.latlng.lat, e.latlng.lng]).addTo(window.locationPickerMap);
                
                // Update the coordinates display
                document.getElementById('selectedLat').value = e.latlng.lat.toFixed(6);
                document.getElementById('selectedLng').value = e.latlng.lng.toFixed(6);
                
                // Update the coordinates display text
                document.getElementById('selectedCoordinates').textContent = 
                    `Selected: ${e.latlng.lat.toFixed(6)}, ${e.latlng.lng.toFixed(6)}`;
            });
        }
        
        // Save location function
        function saveClientLocation() {
            const clientId = document.getElementById('updateClientId').value;
            const lat = document.getElementById('selectedLat').value;
            const lng = document.getElementById('selectedLng').value;
            
            if (!lat || !lng) {
                alert('Please select a location on the map first.');
                return;
            }
            
            // Send AJAX request to update location
            fetch('update_client_location.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    client_id: clientId,
                    latitude: lat,
                    longitude: lng
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Location updated successfully!');
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('locationPickerModal'));
                    modal.hide();
                    // Refresh the page to show updated data
                    location.reload();
                } else {
                    alert('Error updating location: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating location. Please try again.');
            });
        }
    </script>
</body>
</html>
