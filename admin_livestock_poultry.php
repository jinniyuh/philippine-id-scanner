<?php
session_start();
include 'includes/conn.php';


// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Check for success message
$success_message = '';
if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']); // Clear the message after displaying
}

// Get livestock count (sum of quantities)
$livestock_query = "SELECT SUM(quantity) as total FROM livestock_poultry WHERE animal_type = 'Livestock'";
$livestock_result = $conn->query($livestock_query);
$livestock_count = $livestock_result->fetch_assoc()['total'] ?? 0; // Use 0 if null

// Get poultry count (sum of quantities)
$poultry_query = "SELECT SUM(quantity) as total FROM livestock_poultry WHERE animal_type = 'Poultry'";
$poultry_result = $conn->query($poultry_query);
$poultry_count = $poultry_result->fetch_assoc()['total'] ?? 0; // Use 0 if null

// Get livestock data
$livestock_data_query = "SELECT l.*, c.full_name as client_name, c.barangay FROM livestock_poultry l 
                        LEFT JOIN clients c ON l.client_id = c.client_id 
                        WHERE l.animal_type = 'Livestock'
                        ORDER BY l.animal_id DESC";
$livestock_data = $conn->query($livestock_data_query);

// Get poultry data
$poultry_data_query = "SELECT p.*, c.full_name as client_name, c.barangay FROM livestock_poultry p 
                      LEFT JOIN clients c ON p.client_id = c.client_id 
                      WHERE p.animal_type = 'Poultry'
                      ORDER BY p.animal_id DESC";
$poultry_data = $conn->query($poultry_data_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livestock & Poultry Management - Bago City Inventory Management System</title>
    <!-- Add these in the head section -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<style>
    body {
        background-color: #6c63ff;
        font-family: Arial, sans-serif;
    }
    .container-fluid {
        padding-left: 0;
        padding-right: 0;
        overflow-x: hidden
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
    .admin-header, .admin-profile {
        display: flex; 
        align-items: center; 
        justify-content: space-between;
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
        background: none;
        border: none;
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
    .health-status {
        padding: 3px 8px;
        border-radius: 15px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        display: inline-block;
    }
    .status-healthy { background: #28a745; color: #fff; }
    .status-issue { background: #e74c3c; color: #fff; }
    .status-symptomatic { background: #f8f9fa; color: #6c757d; border: 1px solid #dee2e6; }

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
    .add-btn {
        background-color: #4e73df;
        color: white;
        border-radius: 5px;
        padding: 8px 20px;
        font-weight: bold;
        }
    .nav-tabs .nav-link { color: #333; font-weight: 500; }
    .nav-tabs .nav-link.active { color: #007bff; font-weight: 600; }
    
    .tab-content {
        position: relative;
        overflow: auto;
        max-height: calc(100vh - 400px);
    }
    .table { width: 100%; border-collapse: collapse; }
    .table th {
        background-color: #f8f9fa;
        padding: 12px 15px;
        text-align: left;
        border-bottom: 2px solid #dee2e6;
        position: sticky;
        top: 0;
        z-index: 10;
        box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
    }
    .table td {
        padding: 12px 15px; border-bottom: 1px solid #dee2e6; text-align: left;
    }
    .table tr:hover { background: #f8f9fa; }
    .action-btn {
        width: 35px; height: 35px; display: inline-flex; align-items: center;
        justify-content: center; margin-right: 5px; border-radius: 5px; color: #fff;
    }
    .edit-btn { background: #4e73df; }
    .delete-btn { background: #e74a3b; }
    .view-btn {
        background: #36b9cc; color: #fff; border-radius: 15px;
        padding: 5px 10px; font-size: 12px; text-decoration: none;
    }

    .alert-item { display: flex; align-items: flex-start; margin-bottom: 10px; }
    .alert-icon { margin-right: 10px; color: #f0932b; }
    .filter-dropdown {
        width: 200px; padding: 10px 15px; border: 1px solid #ddd;
        border-radius: 5px;
    }
    
    /* Modal header styling */
    .modal-header {
        background-color: #6c63ff;
        color: white;
        border-bottom: none;
    }
    
        .modal-header .btn-close {
            filter: invert(1);
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
</style>

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
                    <h2>Livestock & Poultry Management</h2>
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
                
                <!-- Stats Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="metric-card" onclick="showStatsModal('livestock')">
                            <div class="metric-title">Total Registered Livestock</div>
                            <div class="metric-value" id="livestockCount"><?php echo number_format($livestock_count); ?></div>
                            <div class="metric-detail">Click to view details</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="metric-card" onclick="showStatsModal('poultry')">
                            <div class="metric-title">Total Registered Poultry</div>
                            <div class="metric-value" id="poultryCount"><?php echo number_format($poultry_count); ?></div>
                            <div class="metric-detail">Click to view details</div>
                        </div>
                    </div>
                </div>
                
                
                <!-- Search and Filter -->
                <div class="row mt-1 search-section">
                    <div class="col-md-6">
                        <div class="search-container">
                            <input type="text" id="searchAnimal" placeholder="Search by client, species...">
                            <button type="button"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="d-flex justify-content-end gap-2">
                            <select class="filter-dropdown" id="barangayFilter">
                                <option value="">Filter by Barangay</option>
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
                            <select class="filter-dropdown" id="sourceFilter">
                                <option value="">Filter by Source</option>
                                <option value="Disseminated">Disseminated</option>
                                <option value="Owned">Owned</option>
                            </select>
                            <button type="button" class="add-btn" data-bs-toggle="modal" data-bs-target="#addAnimalModal">
                                Add Animal
                            </button>
                        </div>
                    </div>
                </div>

        <!-- Tabs for Livestock and Poultry -->
        <ul class="nav nav-tabs" id="animalTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="livestock-tab" data-bs-toggle="tab" data-bs-target="#livestock" type="button" role="tab" aria-controls="livestock" aria-selected="true">Livestock</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="poultry-tab" data-bs-toggle="tab" data-bs-target="#poultry" type="button" role="tab" aria-controls="poultry" aria-selected="false">Poultry</button>
            </li>
        </ul>

        <div class="tab-content" id="animalTabsContent">
            <!-- Livestock Tab -->
            <div class="tab-pane fade show active" id="livestock" role="tabpanel" aria-labelledby="livestock-tab">
                <div class="table-responsive mt-3">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Species</th>
                                <th>Weight</th>
                                <th>Source</th>
                                <th>Barangay</th>
                                <th>Health Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($livestock_data && $livestock_data->num_rows > 0): ?>
                            <?php while($livestock = $livestock_data->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($livestock['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($livestock['species']); ?></td>
                                <td><?php echo htmlspecialchars($livestock['weight']); ?> kg</td>
                                <td><?php echo htmlspecialchars($livestock['source']); ?></td>
                                <td><?php echo htmlspecialchars($livestock['barangay']); ?></td>
                                <td>
                                    <?php 
                                    $is_symptomatic = strpos($livestock['health_status'], 'Symptomatic:') === 0;
                                    $display_status = $is_symptomatic ? 'SYMPTOMATIC' : $livestock['health_status'];
                                    $status_class = $livestock['health_status'] === 'Healthy' ? 'status-healthy' : ($is_symptomatic ? 'status-symptomatic' : 'status-issue');
                                    ?>
                                    <span class="health-status <?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($display_status); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="action-btn edit-btn" 
                                        onclick="editAnimal(
                                            '<?php echo $livestock['animal_id']; ?>',
                                            'Livestock',
                                            '<?php echo addslashes($livestock['species']); ?>', 
                                            '<?php echo $livestock['weight']; ?>', 
                                            '<?php echo $livestock['quantity']; ?>', 
                                            '<?php echo $livestock['client_id']; ?>', 
                                            '<?php echo addslashes($livestock['source']); ?>', 
                                            '<?php echo $livestock['health_status']; ?>'
                                        )">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="action-btn delete-btn ms-1" 
                                        onclick="deleteAnimal('livestock', '<?php echo $livestock['animal_id']; ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php if ($is_symptomatic): ?>
                                    <button type="button" class="btn btn-info btn-sm ms-1" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#symptomsModal" 
                                        data-symptoms='<?php echo htmlspecialchars($livestock['health_status'], ENT_QUOTES); ?>' 
                                        data-client='<?php echo htmlspecialchars($livestock['client_name'], ENT_QUOTES); ?>' 
                                        data-species='<?php echo htmlspecialchars($livestock['species'], ENT_QUOTES); ?>'>
                                        <i class="fas fa-expand-alt"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center">No livestock records found</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Poultry Tab -->
            <div class="tab-pane fade" id="poultry" role="tabpanel" aria-labelledby="poultry-tab">
                <div class="table-responsive mt-3">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Species</th>
                                <th>Quantity</th>
                                <th>Source</th>
                                <th>Health Status</th>
                                <th>Barangay</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($poultry_data && $poultry_data->num_rows > 0): ?>
                            <?php while($poultry = $poultry_data->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($poultry['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($poultry['species']); ?></td>
                                <td><?php echo htmlspecialchars($poultry['quantity']); ?></td>
                                <td><?php echo htmlspecialchars($poultry['source']); ?></td>
                                <td>
                                    <?php 
                                    $health_status = $poultry['health_status'] ?? 'Healthy';
                                    $status_class = '';
                                    switch(strtolower($health_status)) {
                                        case 'healthy':
                                            $status_class = 'bg-success';
                                            break;
                                        case 'sick':
                                        case 'symptomatic':
                                            $status_class = 'bg-warning';
                                            break;
                                        case 'critical':
                                            $status_class = 'bg-danger';
                                            break;
                                        default:
                                            $status_class = 'bg-secondary';
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($health_status); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($poultry['barangay']); ?></td>
                                <td>
                                    <button type="button" class="action-btn edit-btn"
                                        onclick="editAnimal(
                                            '<?php echo htmlspecialchars($poultry['animal_id'], ENT_QUOTES); ?>',
                                            'Poultry',
                                            '<?php echo addslashes($poultry['species']); ?>',
                                            '0',
                                            '<?php echo htmlspecialchars($poultry['quantity'], ENT_QUOTES); ?>',
                                            '<?php echo htmlspecialchars($poultry['client_id'], ENT_QUOTES); ?>',
                                            '<?php echo addslashes($poultry['source']); ?>',
                                            '<?php echo htmlspecialchars($poultry['health_status'], ENT_QUOTES); ?>',
                                            ''
                                        )">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="action-btn delete-btn" 
                                        onclick="deleteAnimal('poultry', '<?php echo $poultry['animal_id']; ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center">No poultry records found</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Animal Modal -->
        <div class="modal fade" id="addAnimalModal" tabindex="-1" aria-labelledby="addAnimalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form id="addAnimalForm" method="POST" action="admin_add_animal.php" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAnimalModalLabel">Add New Animal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">

                <div class="col-md-6">
                    <label for="animal_type" class="form-label">Animal Type</label>
                    <select class="form-select" id="animal_type" name="animal_type" required>
                    <option value="" disabled selected>-- Select Type --</option>
                    <option value="Livestock">Livestock</option>
                    <option value="Poultry">Poultry</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="species" class="form-label">Species</label>
                    <select class="form-select" id="species" name="species" required>
                        <option value="" disabled selected>-- Select Animal Type First --</option>
                        
                    </select>
                </div>

                <div class="col-md-6" id="weight_group">
                    <label for="weight" class="form-label">Weight (kg)</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="weight" name="weight" required>
                </div>

                <div class="col-md-6">
                    <label for="sex" class="form-label">Sex</label>
                    <select class="form-select" id="sex" name="sex" required>
                        <option value="" disabled selected>-- Select Sex --</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input type="number" min="1" class="form-control" id="quantity" name="quantity" required>
                </div>

                <div class="col-md-6">
                    <label for="health_status" class="form-label">Health Status</label>
                    <select class="form-select" id="health_status" name="health_status" required>
                    <option value="Healthy">Healthy</option>
                    <option value="Sick">Sick</option>
                    <option value="Unknown">Unknown</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="source" class="form-label">Source</label>
                    <select class="form-select" id="source" name="source" required>
                        <option value="" disabled selected>-- Select Source --</option>
                        <option value="Disseminated">Disseminated</option>
                        <option value="Owned">Owned</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="client_id" class="form-label">Client</label>
                    <select class="form-select" id="client_id" name="client_id" required>
                        <option value="" disabled selected>-- Select Client --</option>
                        <?php
                        $clients = $conn->query("SELECT client_id, full_name FROM clients ORDER BY full_name ASC");
                        if ($clients && $clients->num_rows > 0) {
                            while($client = $clients->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($client['client_id']) . '">' . htmlspecialchars($client['full_name']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Animal</button>
            </div>
            </form>
        </div> 
        </div>

        <!-- Edit Animal Modal -->
<div class="modal fade" id="editAnimalModal" tabindex="-1" aria-labelledby="editAnimalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <form id="editAnimalForm" action="admin_update_animal.php" method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAnimalModalLabel">Edit Animal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit_animal_id" name="animal_id" />
                <input type="hidden" id="edit_client_id" name="client_id" />
                <input type="hidden" id="edit_type" name="animal_type" />
                <input type="hidden" id="edit_species" name="species" />
                <input type="hidden" id="edit_quantity" name="quantity" />
                <input type="hidden" id="edit_source" name="source" />
                
                <!-- Only show weight field for Livestock -->
                <div class="mb-3" id="edit_weight_group">
                    <label for="edit_weight" class="form-label">Weight (kg)</label>
                    <input type="number" min="0" step="0.01" class="form-control" id="edit_weight" name="weight" required>
                </div>

                <div class="mb-3">
                    <label for="edit_health_status" class="form-label">Health Status</label>
                    <select class="form-select" id="edit_health_status" name="health_status" required>
                        <option value="Healthy">Healthy</option>
                        <option value="Sick">Sick</option>
                        <option value="Quarantined">Quarantined</option>
                    </select>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Stats Details Modal -->
<div class="modal fade" id="statsModal" tabindex="-1" aria-labelledby="statsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statsModalLabel">Animal Statistics Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="statsContent">
                    <!-- Content will be populated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle me-2"></i>Success!
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-check-circle text-success" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <h6 id="successMessage">Operation completed successfully!</h6>
            </div>
            <div class="modal-footer justify-content-center" style="border: none;">
                <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Symptoms Modal -->
<div class="modal fade" id="symptomsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Full Symptoms Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Client:</strong>
                        <span id="symptomsClientName" class="ms-2"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Species:</strong>
                        <span id="symptomsSpecies" class="ms-2"></span>
                    </div>
                </div>
                <div class="mb-3">
                    <strong>Symptoms:</strong>
                    <div id="symptomsText" class="mt-2 p-3 bg-light rounded border" style="white-space: pre-wrap; word-wrap: break-word;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Include jQuery and Select2 for searchable dropdowns
    $(document).ready(function() {
      // Initialize Select2 for client dropdown to make it searchable
      $('#client_id').select2({
        placeholder: 'Search and select client',
        allowClear: true,
        dropdownParent: $('#addAnimalModal'),
        width: '100%'
      });
    });
  
  // Species options based on animal type
  const speciesOptions = {
    'Livestock': [
      'Cattle', 'Water Buffalo', 'Goat', 'Sheep', 'Pig', 'Horse', 'Donkey'
    ],
    'Poultry': [
      'Chicken', 'Duck', 'Goose', 'Turkey', 'Quail', 'Guinea Fowl'
    ]
  };
  
  // Function to update species options for Add Modal
  function updateSpeciesOptions() {
    const typeSelect = document.getElementById('animal_type');
    const speciesSelect = document.getElementById('species');
    const selectedType = typeSelect.value;
    const weightGroup = document.getElementById('weight_group');
  
    // Clear existing options
    speciesSelect.innerHTML = '';
  
    if (!selectedType) {
      const placeholderOption = document.createElement('option');
      placeholderOption.value = '';
      placeholderOption.textContent = '-- Select Animal Type First --';
      placeholderOption.disabled = true;
      placeholderOption.selected = true;
      speciesSelect.appendChild(placeholderOption);
      if (weightGroup) weightGroup.style.display = '';
      return;
    }
  
    // Add placeholder
    const placeholderOption = document.createElement('option');
    placeholderOption.value = '';
    placeholderOption.textContent = `-- Select ${selectedType} Species --`;
    placeholderOption.disabled = true;
    placeholderOption.selected = true;
    speciesSelect.appendChild(placeholderOption);
  
    // Add species options based on selected type
    if (speciesOptions[selectedType]) {
      speciesOptions[selectedType].forEach(species => {
        const option = document.createElement('option');
        option.value = species;
        option.textContent = species;
        speciesSelect.appendChild(option);
      });
    }

    // Toggle weight field visibility: show for Livestock, hide for Poultry
    if (weightGroup) {
      if (selectedType === 'Poultry') {
        weightGroup.style.display = 'none';
        document.getElementById('weight').value = 0; // default when hidden
      } else {
        weightGroup.style.display = '';
      }
    }

  }
  
  // Function to update species options for Edit Modal
  function updateSpeciesOptionsEdit(callback) {
    const typeSelect = document.getElementById('edit_type');
    const speciesSelect = document.getElementById('edit_species');
    const type = typeSelect.value;
    const weightGroup = document.getElementById('edit_weight_group');
  
    speciesSelect.innerHTML = '';
  
    const placeholderOption = document.createElement('option');
    placeholderOption.value = '';
    placeholderOption.textContent = `-- Select ${type} Species --`;
    placeholderOption.disabled = true;
    placeholderOption.selected = true;
    speciesSelect.appendChild(placeholderOption);
  
    if (speciesOptions[type]) {
      speciesOptions[type].forEach(species => {
        const option = document.createElement('option');
        option.value = species;
        option.textContent = species;
        speciesSelect.appendChild(option);
      });
    }

    // Toggle weight field visibility for edit: hide for Poultry
    if (weightGroup) {
      if (type === 'Poultry') {
        weightGroup.style.display = 'none';
        document.getElementById('edit_weight').value = 0;
      } else {
        weightGroup.style.display = '';
      }
    }

  
    if (callback && typeof callback === 'function') {
      callback();
    }
  }
  
  // Add event listener for animal type change in Add Modal
  document.getElementById('animal_type').addEventListener('change', updateSpeciesOptions);
  
  // Update species select when type changes inside edit modal
  document.getElementById('edit_type').addEventListener('change', () => {
    updateSpeciesOptionsEdit();
  });

  // Ensure species select matches selected type on page load (for safety if browser restores state)
  document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('animal_type')) updateSpeciesOptions();
  });

  function editAnimal(animal_id, type, species, weight, quantity, client_id, source, health_status) {
    // Set hidden field values
    document.getElementById('edit_animal_id').value = animal_id;
    document.getElementById('edit_type').value = type;
    document.getElementById('edit_species').value = species;
    document.getElementById('edit_quantity').value = quantity;
    document.getElementById('edit_client_id').value = client_id;
    document.getElementById('edit_source').value = source;
    
    // Set visible field values
    document.getElementById('edit_health_status').value = health_status;
    document.getElementById('edit_weight').value = weight;
    
    // Show/hide weight field based on animal type
    const weightGroup = document.getElementById('edit_weight_group');
    if (type === 'Poultry') {
        weightGroup.style.display = 'none';
    } else {
        weightGroup.style.display = 'block';
    }
    

    const editModalEl = document.getElementById('editAnimalModal');
    const modal = new bootstrap.Modal(editModalEl);
    modal.show();
}

  function deleteAnimal(type, id) {
    if (confirm(`Are you sure you want to delete this ${type}?`)) {
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'admin_delete_animal.php';

      const typeInput = document.createElement('input');
      typeInput.type = 'hidden';
      typeInput.name = 'type';
      typeInput.value = type;
      form.appendChild(typeInput);

      const idInput = document.createElement('input');
      idInput.type = 'hidden';
      idInput.name = 'animal_id';
      idInput.value = id;
      form.appendChild(idInput);

      document.body.appendChild(form);
      form.submit();
    }
  }

  // Live search across both tables (Livestock and Poultry)
  function filterTableRows(query, tableSelector, searchableCellIndexes) {
    const rows = document.querySelectorAll(`${tableSelector} tbody tr`);
    const normalizedQuery = query.trim().toLowerCase();

    rows.forEach(row => {
      if (normalizedQuery === '') {
        row.style.display = '';
        return;
      }

      const matches = searchableCellIndexes.some(idx => {
        const cell = row.cells[idx];
        if (!cell) return false;
        return cell.textContent.toLowerCase().includes(normalizedQuery);
      });

      row.style.display = matches ? '' : 'none';
    });
  }

  // Old search functionality disabled - using new comprehensive filtering instead
  // const searchInput = document.getElementById('searchAnimal');
  // if (searchInput) {
  //   searchInput.addEventListener('input', function() {
  //     const q = this.value;
  //     // For Livestock table: columns [Client, Species, Source, Barangay]
  //     filterTableRows(q, '#livestock .table', [0, 1, 4, 5]);
  //     // For Poultry table: columns [Client, Species, Quantity, Source, Health Status, Barangay]
  //     filterTableRows(q, '#poultry .table', [0, 1, 3, 5]);
  //   });
  // }

  // Filter functionality
  const barangayFilter = document.getElementById('barangayFilter');
  const sourceFilter = document.getElementById('sourceFilter');
  
  if (barangayFilter) {
    barangayFilter.addEventListener('change', applyFilters);
  }
  
  if (sourceFilter) {
    sourceFilter.addEventListener('change', applyFilters);
  }

  function applyFilters() {
    const selectedBarangay = barangayFilter ? barangayFilter.value : '';
    const selectedSource = sourceFilter ? sourceFilter.value : '';
    
    filterTableData(selectedBarangay, selectedSource);
    updateStatistics(selectedBarangay, selectedSource);
  }

  function filterTableData(barangay, source) {
    const livestockRows = document.querySelectorAll('#livestock .table tbody tr');
    const poultryRows = document.querySelectorAll('#poultry .table tbody tr');
    
    // Filter livestock rows
    livestockRows.forEach(row => {
      const barangayCell = row.cells[5]; // Barangay column index
      const sourceCell = row.cells[4]; // Source column index
      
      const barangayMatch = barangay === '' || (barangayCell && barangayCell.textContent.trim() === barangay);
      const sourceMatch = source === '' || (sourceCell && sourceCell.textContent.trim() === source);
      
      if (barangayMatch && sourceMatch) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    });
    
    // Filter poultry rows
    poultryRows.forEach(row => {
      const barangayCell = row.cells[4]; // Barangay column index for poultry
      const sourceCell = row.cells[3]; // Source column index for poultry
      
      const barangayMatch = barangay === '' || (barangayCell && barangayCell.textContent.trim() === barangay);
      const sourceMatch = source === '' || (sourceCell && sourceCell.textContent.trim() === source);
      
      if (barangayMatch && sourceMatch) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    });
  }

  function updateStatistics(barangay, source) {
    const livestockRows = document.querySelectorAll('#livestock .table tbody tr');
    const poultryRows = document.querySelectorAll('#poultry .table tbody tr');
    
    let livestockCount = 0;
    let poultryCount = 0;
    
    // Count visible livestock
    livestockRows.forEach(row => {
      if (row.style.display !== 'none') {
        livestockCount += 1; // Count each row as 1 animal
      }
    });
    
    // Count visible poultry
    poultryRows.forEach(row => {
      if (row.style.display !== 'none') {
        const quantityCell = row.cells[2]; // Quantity column
        if (quantityCell) {
          poultryCount += parseInt(quantityCell.textContent) || 0;
        }
      }
    });
    
    // Update the display
    document.getElementById('livestockCount').textContent = livestockCount.toLocaleString();
    document.getElementById('poultryCount').textContent = poultryCount.toLocaleString();
    
    // Update titles based on active filters
    let titleSuffix = '';
    if (barangay && source) {
      titleSuffix = ` in ${barangay} (${source})`;
    } else if (barangay) {
      titleSuffix = ` in ${barangay}`;
    } else if (source) {
      titleSuffix = ` (${source})`;
    }
    
    if (titleSuffix) {
      document.getElementById('livestockTitle').textContent = `Livestock${titleSuffix}`;
      document.getElementById('poultryTitle').textContent = `Poultry${titleSuffix}`;
    } else {
      document.getElementById('livestockTitle').textContent = 'Total Registered Livestock';
      document.getElementById('poultryTitle').textContent = 'Total Registered Poultry';
    }
  }

  // Stats modal functionality
  function showStatsModal(type) {
    const modal = new bootstrap.Modal(document.getElementById('statsModal'));
    const modalTitle = document.getElementById('statsModalLabel');
    const modalContent = document.getElementById('statsContent');
    
    if (type === 'livestock') {
      modalTitle.textContent = 'Livestock Statistics Details';
      generateLivestockStats(modalContent);
    } else if (type === 'poultry') {
      modalTitle.textContent = 'Poultry Statistics Details';
      generatePoultryStats(modalContent);
    }
    
    modal.show();
  }

  function generateLivestockStats(container) {
    const livestockRows = document.querySelectorAll('#livestock .table tbody tr');
    const stats = {
      total: 0,
      bySpecies: {},
      byBarangay: {}
    };
    
    livestockRows.forEach(row => {
      if (row.style.display !== 'none') {
        const cells = row.cells;
        const quantity = 1; // Each row represents 1 animal
        const species = cells[1].textContent.trim();
        const barangay = cells[4].textContent.trim();
        
        stats.total += quantity;
        
        // Count by species
        stats.bySpecies[species] = (stats.bySpecies[species] || 0) + quantity;
        
        // Count by barangay
        stats.byBarangay[barangay] = (stats.byBarangay[barangay] || 0) + quantity;
      }
    });
    
    container.innerHTML = `
      <div class="row">
        <div class="col-md-6">
          <div class="card">
            <div class="card-header bg-primary text-white">
              <h6 class="mb-0"><i class="fas fa-paw me-2"></i>By Species</h6>
            </div>
            <div class="card-body p-0">
              <div class="list-group list-group-flush">
                ${Object.entries(stats.bySpecies).map(([species, count]) => 
                  `<div class="list-group-item d-flex justify-content-between align-items-center">
                    <span>${species}</span>
                    <span class="badge bg-primary rounded-pill">${count}</span>
                  </div>`
                ).join('')}
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card">
            <div class="card-header bg-success text-white">
              <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>By Barangay</h6>
            </div>
            <div class="card-body p-0">
              <div class="list-group list-group-flush">
                ${Object.entries(stats.byBarangay).map(([barangay, count]) => 
                  `<div class="list-group-item d-flex justify-content-between align-items-center">
                    <span>${barangay}</span>
                    <span class="badge bg-success rounded-pill">${count}</span>
                  </div>`
                ).join('')}
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  function generatePoultryStats(container) {
    const poultryRows = document.querySelectorAll('#poultry .table tbody tr');
    const stats = {
      total: 0,
      bySpecies: {},
      byBarangay: {}
    };
    
    poultryRows.forEach(row => {
      if (row.style.display !== 'none') {
        const cells = row.cells;
        const quantity = parseInt(cells[2].textContent) || 0;
        const species = cells[1].textContent.trim();
        const barangay = cells[4].textContent.trim();
        
        stats.total += quantity;
        
        // Count by species
        stats.bySpecies[species] = (stats.bySpecies[species] || 0) + quantity;
        
        // Count by barangay
        stats.byBarangay[barangay] = (stats.byBarangay[barangay] || 0) + quantity;
      }
    });
    
    container.innerHTML = `
      <div class="row">
        <div class="col-md-6">
          <div class="card">
            <div class="card-header bg-primary text-white">
              <h6 class="mb-0"><i class="fas fa-feather me-2"></i>By Species</h6>
            </div>
            <div class="card-body p-0">
              <div class="list-group list-group-flush">
                ${Object.entries(stats.bySpecies).map(([species, count]) => 
                  `<div class="list-group-item d-flex justify-content-between align-items-center">
                    <span>${species}</span>
                    <span class="badge bg-primary rounded-pill">${count}</span>
                  </div>`
                ).join('')}
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card">
            <div class="card-header bg-success text-white">
              <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>By Barangay</h6>
            </div>
            <div class="card-body p-0">
              <div class="list-group list-group-flush">
                ${Object.entries(stats.byBarangay).map(([barangay, count]) => 
                  `<div class="list-group-item d-flex justify-content-between align-items-center">
                    <span>${barangay}</span>
                    <span class="badge bg-success rounded-pill">${count}</span>
                  </div>`
                ).join('')}
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  // Show success modal if there's a success message
  <?php if (!empty($success_message)): ?>
  document.addEventListener('DOMContentLoaded', function() {
    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
    const successMessage = document.getElementById('successMessage');
    successMessage.textContent = '<?php echo addslashes($success_message); ?>';
    successModal.show();
  });
  <?php endif; ?>

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
  document.addEventListener('DOMContentLoaded', function() {
      const viewActivityLogsLink = document.getElementById('viewActivityLogs');
      if (viewActivityLogsLink) {
          viewActivityLogsLink.addEventListener('click', function(e) {
              e.preventDefault();
              e.stopPropagation(); // Prevent dropdown from closing
              loadActivityLogs();
          });
      }
      
      // Add filtering functionality
      const searchInput = document.getElementById('searchAnimal');
      const barangayFilter = document.getElementById('barangayFilter');
      const sourceFilter = document.getElementById('sourceFilter');
      
      function applyFilters() {
          const searchTerm = searchInput.value.toLowerCase();
          const selectedBarangay = barangayFilter.value;
          const selectedSource = sourceFilter.value;
          
          console.log('Filtering with:', { searchTerm, selectedBarangay, selectedSource });
          
          // Filter livestock table
          const livestockRows = document.querySelectorAll('#livestock .table tbody tr');
          console.log('Found livestock rows:', livestockRows.length);
          
          livestockRows.forEach((row, index) => {
              const cells = row.querySelectorAll('td');
              console.log(`Livestock row ${index}:`, cells.length, 'cells');
              if (cells.length >= 7) {
                  const clientName = cells[0].textContent.toLowerCase();
                  const species = cells[1].textContent.toLowerCase();
                  const source = cells[3].textContent;
                  const barangay = cells[4].textContent;
                  
                  console.log(`Row ${index} data:`, { clientName, species, source, barangay });
                  
                  const matchesSearch = !searchTerm || 
                      clientName.includes(searchTerm) || 
                      species.includes(searchTerm);
                  
                  const matchesBarangay = !selectedBarangay || barangay === selectedBarangay;
                  const matchesSource = !selectedSource || source === selectedSource;
                  
                  console.log(`Row ${index} matches:`, { matchesSearch, matchesBarangay, matchesSource });
                  
                  if (matchesSearch && matchesBarangay && matchesSource) {
                      row.style.display = '';
                      console.log(`Row ${index} SHOWN`);
                  } else {
                      row.style.display = 'none';
                      console.log(`Row ${index} HIDDEN`);
                  }
              }
          });
          
          // Filter poultry table
          const poultryRows = document.querySelectorAll('#poultry .table tbody tr');
          poultryRows.forEach(row => {
              const cells = row.querySelectorAll('td');
              if (cells.length >= 5) {
                  const clientName = cells[0].textContent.toLowerCase();
                  const species = cells[1].textContent.toLowerCase();
                  const source = cells[3].textContent;
                  const barangay = cells[4].textContent;
                  
                  const matchesSearch = !searchTerm || 
                      clientName.includes(searchTerm) || 
                      species.includes(searchTerm);
                  
                  const matchesBarangay = !selectedBarangay || barangay === selectedBarangay;
                  const matchesSource = !selectedSource || source === selectedSource;
                  
                  if (matchesSearch && matchesBarangay && matchesSource) {
                      row.style.display = '';
                  } else {
                      row.style.display = 'none';
                  }
              }
          });
          
          // Update stats after filtering
          updateStats();
      }
      
      function updateStats() {
          // Count visible livestock
          const livestockRows = document.querySelectorAll('#livestock .table tbody tr');
          let livestockCount = 0;
          
          livestockRows.forEach(row => {
              if (row.style.display !== 'none') {
                  livestockCount += 1; // Count each row as 1 animal
              }
          });
          
          // Count visible poultry
          const poultryRows = document.querySelectorAll('#poultry .table tbody tr');
          let poultryCount = 0;
          
          poultryRows.forEach(row => {
              if (row.style.display !== 'none') {
                  const quantityCell = row.cells[2]; // Quantity column
                  if (quantityCell) {
                      poultryCount += parseInt(quantityCell.textContent) || 0;
                  }
              }
          });
          
          // Update the display
          document.getElementById('livestockCount').textContent = livestockCount.toLocaleString();
          document.getElementById('poultryCount').textContent = poultryCount.toLocaleString();
      }
      
      // Add event listeners
      if (searchInput) {
          searchInput.addEventListener('input', applyFilters);
      }
      if (barangayFilter) {
          barangayFilter.addEventListener('change', applyFilters);
      }
      if (sourceFilter) {
          sourceFilter.addEventListener('change', applyFilters);
      }
      
      // Initialize stats on page load
      updateStats();
  });

  // Symptoms Modal Handler
  const symptomsModalEl = document.getElementById('symptomsModal');
  if (symptomsModalEl) {
      symptomsModalEl.addEventListener('show.bs.modal', function (ev) {
          const btn = ev.relatedTarget;
          const symptoms = btn.getAttribute('data-symptoms') || '';
          const client = btn.getAttribute('data-client') || '';
          const species = btn.getAttribute('data-species') || '';
          
          // Remove "Symptomatic: " prefix if present
          const cleanSymptoms = symptoms.replace(/^Symptomatic:\s*/i, '');
          
          document.getElementById('symptomsClientName').textContent = client;
          document.getElementById('symptomsSpecies').textContent = species;
          document.getElementById('symptomsText').textContent = cleanSymptoms;
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