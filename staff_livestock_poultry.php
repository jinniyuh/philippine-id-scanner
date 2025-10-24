<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: login.php");
    exit();
}

// Fetch user information
if (isset($_SESSION["user_id"])) {
    $userId = $_SESSION["user_id"];
    $queryUser = "SELECT * FROM users WHERE user_id = '$userId'";
    $resultUser = mysqli_query($conn, $queryUser);
    if ($resultUser && mysqli_num_rows($resultUser) > 0) {
        $user = mysqli_fetch_assoc($resultUser);
        $staffName = isset($user['name']) ? $user['name'] : 'Staff Name';
    } else {
        $staffName = "Staff Name";
    }
} else {
    $staffName = "Staff Name";
}

// Get livestock count (sum of quantities)
$livestock_query = "SELECT SUM(quantity) as total FROM livestock_poultry WHERE animal_type = 'Livestock'";
$livestock_result = $conn->query($livestock_query);
$livestock_count = $livestock_result->fetch_assoc()['total'] ?? 0;

// Get poultry count (sum of quantities)
$poultry_query = "SELECT SUM(quantity) as total FROM livestock_poultry WHERE animal_type = 'Poultry'";
$poultry_result = $conn->query($poultry_query);
$poultry_count = $poultry_result->fetch_assoc()['total'] ?? 0;

// Get livestock data with barangay
$livestock_data_query = "SELECT l.*, c.full_name as client_name, c.barangay FROM livestock_poultry l 
                        LEFT JOIN clients c ON l.client_id = c.client_id 
                        WHERE l.animal_type = 'Livestock'
                        ORDER BY l.animal_id DESC";
$livestock_data = $conn->query($livestock_data_query);

// Get poultry data with barangay
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
    .staff-header, .staff-profile {
        display: flex; 
        align-items: center; 
        justify-content: space-between;
    }
    
    .staff-profile {
        position: relative;
        z-index: 1050;
    }
    
    .staff-profile .dropdown {
        position: relative;
        z-index: 1050;
    }
    .staff-profile img {
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
        cursor: pointer;
        position: relative;
        z-index: 1050;
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
    
    .staff-name {
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
        z-index: 5;
    }
    .table td {
        padding: 12px 15px; 
        border-bottom: 1px solid #dee2e6; 
        text-align: left;
    }
    .table tr:hover { background: #f8f9fa; }
    
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
            <div class="sidebar">
                <?php include 'includes/staff_sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="main-content">
                <div class="staff-header">
                    <h2>Livestock & Poultry Management</h2>
                    <div class="staff-profile">
                        <!-- Avatar with Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-link avatar-dropdown-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="avatarDropdownToggle">
                                <div class="avatar-container">
                                    <img src="assets/default-avatar.png" alt="Staff Profile" class="avatar-img">
                                    <div class="dropdown-indicator"><i class="fas fa-chevron-down"></i></div>
                                </div>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" id="avatarDropdown" style="z-index: 1060;">
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
                                            <span class="health-status <?php echo $livestock['health_status'] === 'Healthy' ? 'status-healthy' : 'status-issue'; ?>">
                                                <?php echo htmlspecialchars($livestock['health_status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center">No livestock records found</td></tr>
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
                                        <th>Barangay</th>
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
                                        <td><?php echo htmlspecialchars($poultry['barangay']); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center">No poultry records found</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
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
            </div>
        </div>
    </div>
  
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
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
            const barangayCell = row.cells[4]; // Barangay column index
            const sourceCell = row.cells[3]; // Source column index
            
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

    // Load activity logs function
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
            
            // Filter livestock table
            const livestockRows = document.querySelectorAll('#livestock .table tbody tr');
            
            livestockRows.forEach((row) => {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 6) {
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