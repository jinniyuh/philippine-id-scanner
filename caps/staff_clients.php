<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: login.php");
    exit();
}

// Get staff user information
$userId = $_SESSION["user_id"];
$queryUser = "SELECT * FROM users WHERE user_id = '$userId'";
$resultUser = mysqli_query($conn, $queryUser);
if ($resultUser && mysqli_num_rows($resultUser) > 0) {
    $user = mysqli_fetch_assoc($resultUser);
    $staffName = isset($user['name']) ? $user['name'] : 'Staff Name';
} else {
    $staffName = "Staff Name";
}

// Barangay filter whitelist
$barangays = [
    'Abuanan','Alianza','Atipuluan','Bacong-Montilla','Bagroy','Balingasag','Binubuhan','Busay','Calumangan','Caridad',
    'Don Jorge L. Araneta','Dulao','Ilijan','Lag-Asan','Ma-ao Barrio','Mailum','Malingin','Napoles','Pacol','Poblacion',
    'Sagasa','Sampinit','Tabunan','Taloc'
];
$selected_barangay = isset($_GET['barangay']) ? trim($_GET['barangay']) : '';
if ($selected_barangay !== '' && !in_array($selected_barangay, $barangays, true)) {
    $selected_barangay = '';
}

// Get client counts with error checking
$where_barangay_total = $selected_barangay !== '' ? " WHERE barangay = '" . $conn->real_escape_string($selected_barangay) . "'" : '';
$total_clients_result = $conn->query("SELECT COUNT(*) as count FROM clients" . $where_barangay_total);
if (!$total_clients_result) {
    die("Query failed: " . $conn->error);
}
$total_clients = $total_clients_result->fetch_assoc()['count'];

$where_barangay_cond = $selected_barangay !== '' ? " AND barangay = '" . $conn->real_escape_string($selected_barangay) . "'" : '';
$complied_clients_result = $conn->query("SELECT COUNT(*) as count FROM clients WHERE status = 'Complied'" . $where_barangay_cond);
if (!$complied_clients_result) {
    die("Query failed: " . $conn->error);
}
$complied_clients = $complied_clients_result->fetch_assoc()['count'];

$pending_clients_result = $conn->query("SELECT COUNT(*) as count FROM clients WHERE status = 'Pending'" . $where_barangay_cond);
if (!$pending_clients_result) {
    die("Query failed: " . $conn->error);
}
$pending_clients = $pending_clients_result->fetch_assoc()['count'];

// Count clients with no disseminated animals (None status)
$none_clients_query = "SELECT COUNT(*) as count FROM clients c 
                       WHERE c.client_id NOT IN (
                           SELECT DISTINCT client_id 
                           FROM livestock_poultry 
                           WHERE source = 'Disseminated'
                       ) AND c.status != 'Complied'" . $where_barangay_cond;
$none_clients_result = $conn->query($none_clients_query);
if (!$none_clients_result) {
    die("Query failed: " . $conn->error);
}
$none_clients = $none_clients_result->fetch_assoc()['count'];

// Detailed lists for modals
$all_clients_list = $conn->query("SELECT client_id, full_name, contact_number, barangay, status FROM clients" . $where_barangay_total . " ORDER BY full_name ASC");
if (!$all_clients_list) { die("Query failed: " . $conn->error); }

$complied_clients_list = $conn->query("SELECT client_id, full_name, contact_number, barangay, status FROM clients WHERE status = 'Complied'" . $where_barangay_cond . " ORDER BY full_name ASC");
if (!$complied_clients_list) { die("Query failed: " . $conn->error); }

$pending_clients_list = $conn->query("SELECT client_id, full_name, contact_number, barangay, status FROM clients WHERE status = 'Pending'" . $where_barangay_cond . " ORDER BY full_name ASC");
if (!$pending_clients_list) { die("Query failed: " . $conn->error); }

// Get clients with no disseminated animals (None status) for modal
$none_clients_list_query = "SELECT c.client_id, c.full_name, c.contact_number, c.barangay, c.status 
                           FROM clients c 
                           WHERE c.client_id NOT IN (
                               SELECT DISTINCT client_id 
                               FROM livestock_poultry 
                               WHERE source = 'Disseminated'
                           ) AND c.status != 'Complied'" . $where_barangay_cond . " 
                           ORDER BY c.full_name ASC";
$none_clients_list = $conn->query($none_clients_list_query);
if (!$none_clients_list) { die("Query failed: " . $conn->error); }

// Get all clients (main table)
$clients_query = "SELECT * FROM clients" . $where_barangay_total . " ORDER BY full_name ASC";
$clients_result = $conn->query($clients_query);
if (!$clients_result) { die("Query failed: " . $conn->error); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Clients Management - Bago City Veterinary Office</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            .wrapper {
                display: flex;
                align-items: flex-start;
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
        .staff-header {
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
                z-index: 1040;
            }
        .staff-header h2 {
                margin: 0;
                font-weight: bold;
            }
        .staff-profile {
            display: flex;
            align-items: center;
            justify-content: space-between;
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
            padding: 12px 16px;
            border-bottom: 1px solid #f1f3f4;
            display: flex;
            align-items: center;
            transition: background-color 0.2s ease;
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
            height: 150px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            cursor: pointer;
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
        
        .metric-card:nth-child(4) {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        .stats-sublabel {
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: inline-block;
        }
        .status-complied {
            background-color: #28a745;
            color: white;
        }
        .status-pending {
            background-color: #ffc107;
            color: black;
        }
        .search-container {
            margin-bottom: 5px;
            position: relative;
        }
        .search-container input {
            width: 100%;
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            padding-right: 45px;
        }
        .search-container button {
            position: absolute;
            right: 0;
            top: 0;
            background: #4e73df;
            border: none;
            border-radius: 0 5px 5px 0;
            width: 45px;
            height: 100%;
            color: white;
        }
        
        .clients-table {
            width: 100%;
            border-collapse: collapse;
        }
        .clients-table th {
            background-color: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            position: sticky;
            top: 0;
            z-index: 5;
        }
        .clients-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
        }
        .clients-table tr:hover {
            background-color: #f8f9fa;
        }
        .filter-dropdown {
            padding: 8px 20px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 14px;
            background-color: white;
            transition: border-color 0.3s ease;
            height: 42px;
        }
        .filter-dropdown:focus {
            border-color: #6c63ff;
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(108, 99, 255, 0.25);
        }
        
        /* Custom scrollbar styling */
        .table-responsive::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        .table-responsive::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* For Firefox */
        .table-responsive {
            scrollbar-width: thin;
            scrollbar-color: #c1c1c1 #f1f1f1;
        }
        
        /* Custom spacing control */
        .stats-section {
            margin-bottom: 1.5rem;
        }
        
        .search-section {
            margin-top: 1rem;
            margin-bottom: 0.55rem;
        }
        
        .table-section {
            margin-top: 0.55rem;
        }
        
        /* Modal styling */
        .modal-header {
            background-color: #6c63ff;
            color: white;
            border-bottom: none;
        }
        
        .modal-header .btn-close {
            filter: invert(1);
        }
        
        /* Responsive adjustments for metric cards */
        @media (max-width: 768px) {
            .metric-card {
                height: 160px;
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
        </style>
</head>
<body>
    <div class="container-fluid">
        <div class="wrapper">
            <!-- Sidebar -->
            <div class="sidebar">
                <?php include 'includes/staff_sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="main-content">
                <div class="staff-header">
                    <h2>Clients Management</h2>
                    <div class="staff-profile">
                        <!-- Avatar with Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-link avatar-dropdown-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="avatarDropdownToggle">
                                <div class="avatar-container">
                                    <?php
                                        // Show staff profile photo if available, else default
                                        $profilePhoto = !empty($user['profile_photo']) ? $user['profile_photo'] : 'assets/default-avatar.png';
                                    ?>
                                    <img src="<?php echo htmlspecialchars($profilePhoto); ?>" alt="Staff Profile" class="avatar-img" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiNmOGY5ZmEiLz4KPHBhdGggZD0iTTIwIDIwQzIyLjIwOTEgMjAgMjQgMTguMjA5MSAyNCAxNkMyNCAxMy43OTA5IDIyLjIwOTEgMTIgMjAgMTJDMTcuNzkwOSAxMiAxNiAxMy43OTA5IDE2IDE2QzE2IDE4LjIwOTEgMTcuNzkwOSAyMCAyMCAyMFoiIGZpbGw9IiM2YzYzZmYiLz4KPHBhdGggZD0iTTIwIDIyQzE2LjY4NjMgMjIgMTQgMjQuNjg2MyAxNCAyOEgyNkMyNiAyNC42ODYzIDIzLjMxMzcgMjIgMjAgMjJaIiBmaWxsPSIjNmM2M2ZmIi8+Cjwvc3ZnPgo='">
                                    <div class="dropdown-indicator"><i class="fas fa-chevron-down"></i></div>
                                </div>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" id="avatarDropdown">
                                <li><a class="dropdown-item" href="#" id="viewActivityLogs"><i class="fas fa-history me-2"></i>Activity Logs</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="row g-3 stats-section">
                    <div class="col-md-3">
                        <div class="metric-card" data-bs-toggle="modal" data-bs-target="#allClientsModal" title="Click to view all clients">
                            <div class="metric-title">Total Clients</div>
                            <div class="metric-value"><?php echo number_format($total_clients); ?></div>
                            <div class="metric-detail">All registered clients</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card" data-bs-toggle="modal" data-bs-target="#compliedClientsModal" title="Click to view complied clients">
                            <div class="metric-title">Complied</div>
                            <div class="metric-value"><?php echo number_format($complied_clients); ?></div>
                            <div class="metric-detail">Fully complied clients</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card" data-bs-toggle="modal" data-bs-target="#pendingClientsModal" title="Click to view pending clients">
                            <div class="metric-title">Pending</div>
                            <div class="metric-value"><?php echo number_format($pending_clients); ?></div>
                            <div class="metric-detail">Awaiting compliance</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card" data-bs-toggle="modal" data-bs-target="#noneClientsModal" title="Click to view clients with no disseminated animals">
                            <div class="metric-title">No Animals</div>
                            <div class="metric-value"><?php echo number_format($none_clients); ?></div>
                            <div class="metric-detail">No disseminated animals</div>
                        </div>
                    </div>
                </div>
                
                <!-- Search and Filter -->
                <div class="row mt-1 search-section">
                    <div class="col-md-6">
                        <div class="search-container">
                            <input type="text" id="searchClient" placeholder="Search by name, contact number, or barangay...">
                            <button type="button"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="d-flex justify-content-end gap-2">
                            <select class="filter-dropdown" id="filterStatus">
                                <option value="">Filter by Status</option>
                                <option value="Complied">Complied</option>
                                <option value="Pending">Pending</option>
                            </select>
                            <select class="filter-dropdown" id="filterBarangay">
                                <option value="">Filter by Barangay</option>
                                <?php foreach ($barangays as $barangay): ?>
                                    <option value="<?php echo htmlspecialchars($barangay); ?>"><?php echo htmlspecialchars($barangay); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive mt-2 table-section">
                    <table class="clients-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Contact Number</th>
                                <th>Barangay</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($client = $clients_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($client['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($client['contact_number']); ?></td>
                                <td><?php echo htmlspecialchars($client['barangay']); ?></td>
                                <td>
                                    <span class="stats-sublabel <?= $client['status'] == 'Complied' ? 'status-complied' : 'status-pending'; ?>">
                                        <?= $client['status']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- All Clients Modal -->
    <div class="modal fade" id="allClientsModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">All Clients (<?php echo number_format($total_clients); ?>)</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <?php if ($all_clients_list->num_rows > 0): ?>
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Contact</th>
                      <th>Barangay</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while ($client = $all_clients_list->fetch_assoc()): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($client['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($client['contact_number']); ?></td>
                        <td><?php echo htmlspecialchars($client['barangay']); ?></td>
                        <td>
                          <span class="stats-sublabel <?= $client['status'] == 'Complied' ? 'status-complied' : 'status-pending'; ?>">
                            <?= $client['status']; ?>
                          </span>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <p class="text-muted">No clients found.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Complied Clients Modal -->
    <div class="modal fade" id="compliedClientsModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Complied Clients (<?php echo number_format($complied_clients); ?>)</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <?php if ($complied_clients_list->num_rows > 0): ?>
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Contact</th>
                      <th>Barangay</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while ($client = $complied_clients_list->fetch_assoc()): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($client['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($client['contact_number']); ?></td>
                        <td><?php echo htmlspecialchars($client['barangay']); ?></td>
                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <p class="text-muted">No complied clients found.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Pending Clients Modal -->
    <div class="modal fade" id="pendingClientsModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Pending Clients (<?php echo number_format($pending_clients); ?>)</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <?php if ($pending_clients_list->num_rows > 0): ?>
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Contact</th>
                      <th>Barangay</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while ($client = $pending_clients_list->fetch_assoc()): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($client['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($client['contact_number']); ?></td>
                        <td><?php echo htmlspecialchars($client['barangay']); ?></td>
                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <p class="text-muted">No pending clients found.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- None Status Clients Modal -->
    <div class="modal fade" id="noneClientsModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Clients with No Disseminated Animals (<?php echo number_format($none_clients); ?>)</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <?php if ($none_clients_list->num_rows > 0): ?>
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Contact</th>
                      <th>Barangay</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while ($client = $none_clients_list->fetch_assoc()): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($client['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($client['contact_number']); ?></td>
                        <td><?php echo htmlspecialchars($client['barangay']); ?></td>
                        <td>
                          <span class="stats-sublabel status-pending">
                            <?= $client['status']; ?>
                          </span>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <p class="text-muted">All clients have disseminated animals.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality for clients
        const searchClientInput = document.getElementById('searchClient');
        if (searchClientInput) {
            searchClientInput.addEventListener('keyup', function() {
                const searchValue = this.value.toLowerCase();
                const tableRows = document.querySelectorAll('.clients-table tbody tr');
                
                tableRows.forEach(row => {
                    const name = row.cells[0].textContent.toLowerCase();
                    const contact = row.cells[1].textContent.toLowerCase();
                    const barangay = row.cells[2].textContent.toLowerCase();
                    
                    if (name.includes(searchValue) || contact.includes(searchValue) || barangay.includes(searchValue)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
        
        // Filter by status
        document.getElementById('filterStatus').addEventListener('change', function() {
            const filterValue = this.value;
            const tableRows = document.querySelectorAll('.clients-table tbody tr');
            
            if (filterValue === '') {
                tableRows.forEach(row => {
                    row.style.display = '';
                });
                return;
            }
            
            tableRows.forEach(row => {
                const status = row.cells[3].textContent.trim();
                
                if (status === filterValue) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Filter by barangay
        document.getElementById('filterBarangay').addEventListener('change', function() {
            const filterValue = this.value;
            const tableRows = document.querySelectorAll('.clients-table tbody tr');
            
            if (filterValue === '') {
                tableRows.forEach(row => {
                    row.style.display = '';
                });
                return;
            }
            
            tableRows.forEach(row => {
                const barangay = row.cells[2].textContent.trim();
                
                if (barangay === filterValue) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Avatar dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            const avatarDropdownToggle = document.getElementById('avatarDropdownToggle');
            const avatarDropdown = document.getElementById('avatarDropdown');
            const avatarContainer = document.querySelector('.avatar-container');
            const avatarImg = document.querySelector('.avatar-img');
            
            if (avatarDropdownToggle && avatarDropdown) {
                // Initialize Bootstrap dropdown
                const dropdown = new bootstrap.Dropdown(avatarDropdownToggle);
                
                // Add click event to button
                avatarDropdownToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropdown.toggle();
                });
                
                // Add click event to container as backup
                if (avatarContainer) {
                    avatarContainer.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        dropdown.toggle();
                    });
                }
                
                // Add click event to image as backup
                if (avatarImg) {
                    avatarImg.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        dropdown.toggle();
                    });
                }
                
                // Force show dropdown on any click in avatar area
                const avatarArea = document.querySelector('.dropdown');
                if (avatarArea) {
                    avatarArea.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        dropdown.show();
                    });
                }
            }

            // Activity logs functionality
            const viewActivityLogsLink = document.getElementById('viewActivityLogs');
            if (viewActivityLogsLink) {
                viewActivityLogsLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation(); // Prevent dropdown from closing
                    loadActivityLogs();
                });
            }
        });

        // Function to load activity logs
        function loadActivityLogs() {
            fetch('get_staff_activity_logs.php')
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
                                <li><a class="dropdown-item" href="#" id="viewActivityLogs"><i class="fas fa-history me-2"></i>Activity Logs</a></li>
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

        // Function to restore dropdown menu
        function restoreDropdownMenu() {
            const dropdown = document.getElementById('avatarDropdown');
            dropdown.innerHTML = `
                <li><a class="dropdown-item" href="#" id="viewActivityLogs"><i class="fas fa-history me-2"></i>Activity Logs</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            `;
            
            // Re-attach the event listener for "View Activity Logs"
            const viewActivityLogsLink = document.getElementById('viewActivityLogs');
            if (viewActivityLogsLink) {
                viewActivityLogsLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    loadActivityLogs();
                });
            }
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
