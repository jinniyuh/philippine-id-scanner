<?php
session_start();
include 'includes/conn.php';
include 'includes/geotagging_helper.php';

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

// Get only clients with disseminated animals and their location data
$clients = getClientsWithDisseminatedAnimalsAndLocation($conn);

// Get statistics for clients with disseminated animals
$stats = getDisseminatedAnimalsLocationStats($conn);
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
            z-index: 500000 !important;
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
                            <option value="Abuanan">Abuanan</option>
                            <option value="Dulao">Dulao</option>
                            <option value="Lag-Asan">Lag-Asan</option>
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
                    <button class="btn btn-primary" id="fitBoundsBtn">
                        <i class="fas fa-expand-arrows-alt"></i> Fit All Markers
                    </button>
                    <button class="btn btn-info" id="refreshDataBtn">
                        <i class="fas fa-sync-alt"></i> Refresh Data
                    </button>
                </div>
                
                <!-- Map Container -->
                <div id="clientMap"></div>
            </div>
        </div>
    </div>

    <!-- Leaflet JS for maps -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        let clientMap;
        let markers = [];
        let markerLayer;
        
        // Client data from PHP
        const clients = <?php echo json_encode($clients); ?>;
        
        // Initialize map
        function initMap() {
            // Initialize map centered on Bago City, Philippines
            clientMap = L.map('clientMap').setView([10.5388, 122.8389], 12);
            
            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(clientMap);
            
            // Create marker layer group
            markerLayer = L.layerGroup().addTo(clientMap);
            
            // Add markers for each client
            addClientMarkers();
        }
        
        // Add client markers to map
        function addClientMarkers() {
            clients.forEach(function(client) {
                if (client.latitude && client.longitude) {
                    // Create custom icon based on status
                    const iconColor = client.status === 'Complied' ? 'green' : 'orange';
                    const customIcon = L.divIcon({
                        className: 'custom-marker',
                        html: `<div style="background-color: ${iconColor}; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>`,
                        iconSize: [20, 20],
                        iconAnchor: [10, 10]
                    });
                    
                    const marker = L.marker([client.latitude, client.longitude], { icon: customIcon })
                        .bindPopup(`
                            <div>
                                <h6><strong>${client.full_name}</strong></h6>
                                <p><strong>Address:</strong> ${client.address}</p>
                                <p><strong>Contact:</strong> ${client.contact_number}</p>
                                <p><strong>Status:</strong> <span class="badge ${client.status === 'Complied' ? 'bg-success' : 'bg-warning'}">${client.status}</span></p>
                                <p><strong>Registered:</strong> ${new Date(client.created_at).toLocaleDateString()}</p>
                                <p><strong>Coordinates:</strong><br>
                                Lat: ${parseFloat(client.latitude).toFixed(6)}<br>
                                Lng: ${parseFloat(client.longitude).toFixed(6)}</p>
                            </div>
                        `);
                    
                    marker.clientData = client;
                    markers.push(marker);
                    markerLayer.addLayer(marker);
                }
            });
        }
        
        // Fit map bounds to show all markers
        function fitBounds() {
            if (markers.length > 0) {
                const group = new L.featureGroup(markers);
                clientMap.fitBounds(group.getBounds().pad(0.1));
            }
        }
        
        
        
        // Event listeners
        document.getElementById('fitBoundsBtn').addEventListener('click', fitBounds);
        document.getElementById('refreshDataBtn').addEventListener('click', function() {
            location.reload();
        });
        
        // Function to update client location (placeholder)
        function updateClientLocation(clientId) {
            // This would typically redirect to a client edit page or open a location update modal
            alert('Feature coming soon: Update location for client ID ' + clientId);
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
        
        // Initialize map when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
            
            // Auto-fit bounds if there are markers
            if (markers.length > 0) {
                setTimeout(fitBounds, 1000);
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
        });
    </script>
    
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
                            <tbody>
                                <?php 
                                $disseminated_clients = getClientsWithDisseminatedAnimals($conn);
                                foreach ($disseminated_clients as $client): 
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($client['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($client['contact_number']); ?></td>
                                    <td>
                                        <?php if ($client['latitude'] && $client['longitude']): ?>
                                            <span class="badge bg-success">With Location</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">No Location</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $client['status'] == 'Complied' ? 'bg-success' : 'bg-warning'; ?>">
                                            <?php echo $client['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($client['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
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
                            <tbody>
                                <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($client['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($client['barangay'] ?? 'N/A'); ?></td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo number_format($client['latitude'], 6); ?>, <?php echo number_format($client['longitude'], 6); ?>
                                        </small>
                                    </td>
                                    <td><?php echo htmlspecialchars($client['contact_number']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $client['status'] == 'Complied' ? 'bg-success' : 'bg-warning'; ?>">
                                            <?php echo $client['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
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
                            <tbody>
                                <?php 
                                $clients_without_location = getClientsWithDisseminatedAnimalsWithoutLocation($conn);
                                foreach ($clients_without_location as $client): 
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($client['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($client['barangay'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($client['contact_number']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $client['status'] == 'Complied' ? 'bg-success' : 'bg-warning'; ?>">
                                            <?php echo $client['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="updateClientLocation(<?php echo $client['client_id']; ?>)">
                                            <i class="fas fa-map-marker-alt"></i> Add Location
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
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
        // Initialize Bootstrap dropdowns after Bootstrap is loaded
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
            const dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
            
        });
        
        // Filter functionality
        let filteredClients = [...clients]; // Copy of original clients array
        
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
            
            // Update stats cards
            updateStatsCards();
            
            // Update map markers
            updateMapMarkers();
        }
        
        function updateStatsCards() {
            const totalWithDisseminated = filteredClients.length;
            const withLocation = filteredClients.filter(client => client.latitude && client.longitude).length;
            const withoutLocation = totalWithDisseminated - withLocation;
            
            // Update the metric values
            document.querySelectorAll('.metric-value')[0].textContent = totalWithDisseminated;
            document.querySelectorAll('.metric-value')[1].textContent = withLocation;
            document.querySelectorAll('.metric-value')[2].textContent = withoutLocation;
        }
        
        function updateMapMarkers() {
            // Clear existing markers
            markerLayer.clearLayers();
            markers = [];
            
            // Add filtered markers
            filteredClients.forEach(client => {
                if (client.latitude && client.longitude) {
                    addClientMarker(client);
                }
            });
        }
        
        // Event listeners for filters
        document.getElementById('searchClients').addEventListener('input', applyFilters);
        document.getElementById('filterBarangay').addEventListener('change', applyFilters);
        document.getElementById('filterStatus').addEventListener('change', applyFilters);
        document.getElementById('searchBtn').addEventListener('click', applyFilters);
    </script>
</body>
</html>
