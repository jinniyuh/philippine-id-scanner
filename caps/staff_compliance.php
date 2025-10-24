<?php
session_start();
include 'includes/conn.php';
 
// Check if user is logged in as staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: login.php");
    exit();
}

// Get compliance statistics - ONLY CLIENTS WITH DISSEMINATED ANIMALS
$total_query = "SELECT COUNT(DISTINCT comp.compliance_id) AS total 
                FROM compliance comp
                INNER JOIN clients cl ON comp.client_id = cl.client_id
                INNER JOIN livestock_poultry lp ON cl.client_id = lp.client_id
                WHERE UPPER(lp.source) = 'DISSEMINATED'";
$total_result = $conn->query($total_query);
$total_clients = ($total_result && $total_result->num_rows > 0) ? ($total_result->fetch_assoc()['total'] ?? 0) : 0;

$compliant_query = "SELECT COUNT(DISTINCT comp.compliance_id) AS compliant 
                    FROM compliance comp
                    INNER JOIN clients cl ON comp.client_id = cl.client_id
                    INNER JOIN livestock_poultry lp ON cl.client_id = lp.client_id
                    WHERE comp.compliance_status = 'Complied' 
                    AND UPPER(lp.source) = 'DISSEMINATED'";
$compliant_result = $conn->query($compliant_query);
$compliant_clients = ($compliant_result && $compliant_result->num_rows > 0) ? ($compliant_result->fetch_assoc()['compliant'] ?? 0) : 0;

$non_compliant_query = "SELECT COUNT(DISTINCT comp.compliance_id) AS non_compliant 
                        FROM compliance comp
                        INNER JOIN clients cl ON comp.client_id = cl.client_id
                        INNER JOIN livestock_poultry lp ON cl.client_id = lp.client_id
                        WHERE comp.compliance_status = 'Non-Compliant' 
                        AND UPPER(lp.source) = 'DISSEMINATED'";
$non_compliant_result = $conn->query($non_compliant_query);
$non_compliant_clients = ($non_compliant_result && $non_compliant_result->num_rows > 0) ? ($non_compliant_result->fetch_assoc()['non_compliant'] ?? 0) : 0;

$pending_reviews_query = "SELECT COUNT(DISTINCT comp.compliance_id) AS pending 
                         FROM compliance comp
                         INNER JOIN clients cl ON comp.client_id = cl.client_id
                         INNER JOIN livestock_poultry lp ON cl.client_id = lp.client_id
                         WHERE comp.compliance_status = 'Pending' 
                         AND UPPER(lp.source) = 'DISSEMINATED'";
$pending_reviews_result = $conn->query($pending_reviews_query);
$pending_reviews = ($pending_reviews_result && $pending_reviews_result->num_rows > 0) ? ($pending_reviews_result->fetch_assoc()['pending'] ?? 0) : 0;


// Get compliance data for table - ONLY CLIENTS WITH DISSEMINATED ANIMALS
$compliance_query = "SELECT DISTINCT comp.*, cl.full_name AS client_name_fallback
                     FROM compliance comp
                     INNER JOIN clients cl ON comp.client_id = cl.client_id
                     INNER JOIN livestock_poultry lp ON cl.client_id = lp.client_id
                     WHERE UPPER(lp.source) = 'DISSEMINATED'
                     ORDER BY comp.created_at DESC";
$compliance_result = $conn->query($compliance_query);

// Check if query failed
if (!$compliance_result) {
    // If query fails, show empty result
    $compliance_query = "SELECT comp.*, cl.full_name AS client_name_fallback
                        FROM compliance comp
                        INNER JOIN clients cl ON comp.client_id = cl.client_id
                        WHERE 1=0";
    $compliance_result = $conn->query($compliance_query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compliance Management - Bago City Inventory Management System</title>
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
        .admin-profile {
            display: flex;
            align-items: center;
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
        
        /* ML Insights Style Metric Cards (original) */
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
            padding: 8px 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 14px;
            background-color: white;
            transition: border-color 0.3s ease;
            height: 42px;
            min-width: 140px;
            max-width: 180px;
        }
        .filter-dropdown:focus {
            border-color: #6c63ff;
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(108, 99, 255, 0.25);
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
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
        }
        .table tr:hover {
            background-color: #f8f9fa;
        }
        .action-btn {
            width: 35px;
            height: 35px;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 5px;
            color: white;
            cursor: pointer;
        }
        .edit-btn {
            background-color: #4e73df;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }
        .compliant {
            background-color: #28a745;
            color: white;
        }
        .non-compliant {
            background-color: #dc3545;
            color: white;
        }
        .pending {
            background-color: #ffc107;
            color: black;
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
                <div class="admin-header">
                    <h2>Compliance Management</h2>
                    <div class="admin-profile">
                        <!-- Avatar with Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-link avatar-dropdown-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="avatar-container">
                                    <img src="assets/default-avatar.png" alt="Staff Profile" class="avatar-img">
                                    <div class="dropdown-indicator"><i class="fas fa-chevron-down"></i></div>
                                </div>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" id="avatarDropdown">
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="row g-3 stats-section">
                    <div class="col-md-3">
                        <div class="metric-card" role="button" data-bs-toggle="modal" data-bs-target="#totalClientsModal">
                            <div class="metric-title">Total Clients</div>
                            <div class="metric-value"><?php echo number_format($total_clients); ?></div>
                            <div class="metric-detail">Click to view details</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card" role="button" data-bs-toggle="modal" data-bs-target="#compliedClientsModal">
                            <div class="metric-title">Complied Clients</div>
                            <div class="metric-value"><?php echo number_format($compliant_clients); ?></div>
                            <div class="metric-detail">Click to view details</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card" role="button" data-bs-toggle="modal" data-bs-target="#pendingClientsModal">
                            <div class="metric-title">Pending Clients</div>
                            <div class="metric-value"><?php echo number_format($pending_reviews); ?></div>
                            <div class="metric-detail">Click to view details</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card" role="button" data-bs-toggle="modal" data-bs-target="#nonCompliantClientsModal">
                            <div class="metric-title">Non-Compliant Clients</div>
                            <div class="metric-value"><?php echo number_format($non_compliant_clients); ?></div>
                            <div class="metric-detail">Click to view details</div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="row mt-1 search-section">
                    <div class="col-md-6">
                        <div class="search-container">
                            <input type="text" id="searchCompliance" placeholder="Search by name, type, species...">
                            <button type="button"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="d-flex justify-content-end gap-2">
                            <select class="filter-dropdown" id="filterStatus">
                                <option value="">Filter by Status</option>
                                <option value="Complied">Complied</option>
                                <option value="Pending">Pending</option>
                                <option value="Non-Compliant">Non-Compliant</option>
                            </select>
                            <select class="filter-dropdown" id="filterType">
                                <option value="">Filter by Type</option>
                                <option value="Livestock">Livestock</option>
                                <option value="Poultry">Poultry</option>
                                <option value="Both">Both</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Compliance Table -->
                <div class="table-responsive mt-2 table-section">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Client Name</th>
                                <th>Type</th>
                                <th>Species</th>
                                <th>Transfer Date</th>
                                <th>Compliance Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($compliance_result && $compliance_result->num_rows > 0): ?>
                                <?php while($client = $compliance_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($client['client_name_fallback'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($client['animal_type'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars($client['species'] ?? '—'); ?></td>
                                        <td><?php echo !empty($client['transfer_date']) ? date('M d, Y', strtotime($client['transfer_date'])) : '—'; ?></td>
                                        <td>
                                            <?php 
                                                $status = $client['compliance_status'] ?? 'Pending';
                                                // Map to badge classes
                                                $statusClass = 'pending';
                                                if ($status === 'Complied') { 
                                                    $statusClass = 'compliant'; 
                                                }
                                                elseif ($status === 'Non-Compliant') { 
                                                    $statusClass = 'non-compliant'; 
                                                }
                                                elseif ($status === 'Pending') { 
                                                    $statusClass = 'pending'; 
                                                }
                                            ?>
                                            <span class="status-badge <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No compliance data found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        
        // Combined filter function
        function filterTable() {
            const searchValue = document.getElementById('searchCompliance').value.toLowerCase();
             const statusFilter = document.getElementById('filterStatus').value;
             const typeFilter = document.getElementById('filterType').value;
            const tableRows = document.querySelectorAll('.table tbody tr');
            
            // Initialize counters
            let totalClients = 0;
            let compliedClients = 0;
            let pendingClients = 0;
            let nonCompliantClients = 0;
            
            tableRows.forEach(row => {
                let showRow = true;
                const cells = row.querySelectorAll('td');
                
                // Only process data rows (5 cells and not empty)
                if (cells.length === 5 && cells[0].textContent.trim() !== '') {
                    const clientName = cells[0].textContent.toLowerCase();
                    const animalType = cells[1].textContent;
                    const species = cells[2].textContent.toLowerCase();
                    const statusCell = cells[4];
                    const status = statusCell.textContent.trim();
                    
                    // Search filter
                    if (searchValue) {
                        let found = false;
                        cells.forEach(cell => {
                            if (cell.textContent.toLowerCase().includes(searchValue)) {
                                found = true;
                            }
                        });
                        if (!found) showRow = false;
                    }
                    
                     // Status filter (column index 4 - Compliance Status column)
                    if (statusFilter && showRow) {
                        const statusText = statusCell.textContent.trim();
                        // Compare status text directly
                        const cleanStatusText = statusText.trim();
                        if (cleanStatusText !== statusFilter) {
                            showRow = false;
                        }
                    }
                     // Type filter (column index 1 - Type column)
                     if (typeFilter && showRow) {
                         const typeText = animalType.trim();
                         if (typeFilter === 'Both') {
                             // Show both Livestock and Poultry
                             if (typeText !== 'Livestock' && typeText !== 'Poultry') {
                                 showRow = false;
                             }
                         } else if (typeText !== typeFilter) {
                             showRow = false;
                         }
                     }
                    
                    // Count visible rows
                    if (showRow) {
                        totalClients++;
                        
                        if (status === 'Complied') {
                            compliedClients++;
                        } else if (status === 'Pending') {
                            pendingClients++;
                        } else if (status === 'Non-Compliant') {
                            nonCompliantClients++;
                        }
                    }
                    
                    row.style.display = showRow ? '' : 'none';
                } else {
                    // Hide non-data rows
                    row.style.display = 'none';
                }
            });
            
            // Update stats cards with filtered results
            updateComplianceStatsCards(totalClients, compliedClients, pendingClients, nonCompliantClients);
        }
        
        // Filter functionality for stats cards
        function applyComplianceFilters() {
            const searchTerm = document.getElementById('searchCompliance').value.toLowerCase();
            const selectedStatus = document.getElementById('filterStatus').value;
            const selectedType = document.getElementById('filterType').value;
            
            // Get all table rows
            const tableRows = document.querySelectorAll('.table tbody tr');
            let totalClients = 0;
            let compliedClients = 0;
            let pendingClients = 0;
            let nonCompliantClients = 0;
            
            tableRows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length === 5 && cells[0].textContent.trim() !== '') {
                    const clientName = cells[0].textContent.toLowerCase();
                    const animalType = cells[1].textContent;
                    const species = cells[2].textContent.toLowerCase();
                    const statusCell = cells[4];
                    const status = statusCell.textContent.trim();
                    
                    // Apply filters
                    const matchesSearch = !searchTerm || 
                        clientName.includes(searchTerm) || 
                        animalType.toLowerCase().includes(searchTerm) || 
                        species.includes(searchTerm);
                    
                    // Compare status directly
                    const cleanStatus = status.trim();
                    const matchesStatus = !selectedStatus || cleanStatus === selectedStatus;
                    
                    const matchesType = !selectedType || 
                        (selectedType === 'Both' && (animalType === 'Livestock' || animalType === 'Poultry')) ||
                        animalType === selectedType;
                    
                    if (matchesSearch && matchesStatus && matchesType) {
                        row.style.display = '';
                        totalClients++;
                        
                        if (status === 'Complied') {
                            compliedClients++;
                        } else if (status === 'Pending') {
                            pendingClients++;
                        } else if (status === 'Non-Compliant') {
                            nonCompliantClients++;
                        }
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
            
            console.log('After filtering - Total:', totalClients, 'Complied:', compliedClients, 'Pending:', pendingClients, 'Non-Compliant:', nonCompliantClients);
            
            // Update stats cards
            updateComplianceStatsCards(totalClients, compliedClients, pendingClients, nonCompliantClients);
        }
        
        // Initialize stats cards with actual table data on page load
        function initializeStatsCards() {
            // Only count rows that have data (not empty rows or header rows)
            const tableRows = document.querySelectorAll('.table tbody tr');
            console.log('Found table rows:', tableRows.length);
            
            let totalClients = 0;
            let compliedClients = 0;
            let pendingClients = 0;
            let nonCompliantClients = 0;
            
            tableRows.forEach((row, index) => {
                const cells = row.querySelectorAll('td');
                
                // Only count rows that have exactly 5 cells (data rows) and are not empty
                if (cells.length === 5 && cells[0].textContent.trim() !== '') {
                    const statusCell = cells[4];
                    const status = statusCell.textContent.trim();
                    console.log('Data Row', index, 'status:', status);
                    
                    totalClients++;
                    
                    if (status === 'Complied') {
                        compliedClients++;
                    } else if (status === 'Pending') {
                        pendingClients++;
                    } else if (status === 'Non-Compliant') {
                        nonCompliantClients++;
                    }
                }
            });
            
            console.log('Final Counts - Total:', totalClients, 'Complied:', compliedClients, 'Pending:', pendingClients, 'Non-Compliant:', nonCompliantClients);
            
            // Update stats cards with actual data
            updateComplianceStatsCards(totalClients, compliedClients, pendingClients, nonCompliantClients);
        }
        
        function updateComplianceStatsCards(total, complied, pending, nonCompliant) {
            // Update the metric values in the stats cards
            const metricValues = document.querySelectorAll('.metric-value');
            if (metricValues.length >= 4) {
                metricValues[0].textContent = total;
                metricValues[1].textContent = complied;
                metricValues[2].textContent = pending;
                metricValues[3].textContent = nonCompliant;
            }
        }
        
        // Event listeners for filters
        document.getElementById('searchCompliance').addEventListener('input', filterTable);
        document.getElementById('filterStatus').addEventListener('change', filterTable);
        document.getElementById('filterType').addEventListener('change', filterTable);
        
        // Initialize stats cards with actual table data on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeStatsCards();
        });

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

        // Initialize Bootstrap dropdowns and activity logs
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Initializing dropdowns...');
            
            // Initialize Bootstrap dropdowns
            const dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
            console.log('Found dropdown elements:', dropdownElementList.length);
            
            const dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                console.log('Initializing dropdown for:', dropdownToggleEl);
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
            
            // Load activity logs when "View Activity Logs" is clicked
            const viewActivityLogsLink = document.getElementById('viewActivityLogs');
            console.log('View activity logs link found:', viewActivityLogsLink);
            
            if (viewActivityLogsLink) {
                viewActivityLogsLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation(); // Prevent dropdown from closing
                    console.log('View activity logs clicked');
                    loadActivityLogs();
                });
            }
            
            // Add click event to the dropdown button itself for debugging
            const dropdownButton = document.querySelector('.avatar-dropdown-btn');
            if (dropdownButton) {
                console.log('Dropdown button found:', dropdownButton);
                dropdownButton.addEventListener('click', function(e) {
                    console.log('Dropdown button clicked');
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Manual dropdown toggle as fallback
                    const dropdown = document.getElementById('avatarDropdown');
                    if (dropdown) {
                        dropdown.classList.toggle('show');
                        dropdown.setAttribute('data-bs-popper', 'static');
                    }
                });
            }
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                const dropdown = document.getElementById('avatarDropdown');
                const button = document.querySelector('.avatar-dropdown-btn');
                
                if (dropdown && button && !dropdown.contains(e.target) && !button.contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            });
        });
    </script>

    <!-- Total Clients Modal -->
    <div class="modal fade" id="totalClientsModal" tabindex="-1" aria-labelledby="totalClientsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="totalClientsModalLabel">
                        <i class="fas fa-users me-2"></i>All Clients with Disseminated Animals
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Client Name</th>
                                    <th>Animal Type</th>
                                    <th>Species</th>
                                    <th>Transfer Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $all_clients_query = "SELECT DISTINCT comp.*, cl.full_name AS client_name_fallback
                                                     FROM compliance comp
                                                     INNER JOIN clients cl ON comp.client_id = cl.client_id
                                                     INNER JOIN livestock_poultry lp ON cl.client_id = lp.client_id
                                                     WHERE UPPER(lp.source) = 'DISSEMINATED'
                                                     ORDER BY comp.created_at DESC";
                                $all_clients_result = $conn->query($all_clients_query);
                                if ($all_clients_result && $all_clients_result->num_rows > 0):
                                    while($client = $all_clients_result->fetch_assoc()): 
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($client['client_name_fallback'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($client['animal_type'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($client['species'] ?? '—'); ?></td>
                                    <td><?php echo !empty($client['transfer_date']) ? date('M d, Y', strtotime($client['transfer_date'])) : '—'; ?></td>
                                    <td>
                                        <?php 
                                            $status = $client['compliance_status'] ?? 'Pending';
                                            $statusClass = 'pending';
                                            if ($status === 'Complied') { $statusClass = 'compliant'; }
                                            elseif ($status === 'Non-Compliant') { $statusClass = 'non-compliant'; }
                                            elseif ($status === 'Pending') { $statusClass = 'pending'; }
                                        ?>
                                        <span class="status-badge <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else: 
                                ?>
                                <tr><td colspan="5" class="text-center">No clients found</td></tr>
                                <?php endif; ?>
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

    <!-- Complied Clients Modal -->
    <div class="modal fade" id="compliedClientsModal" tabindex="-1" aria-labelledby="compliedClientsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="compliedClientsModalLabel">
                        <i class="fas fa-check-circle me-2"></i>Complied Clients
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Client Name</th>
                                    <th>Animal Type</th>
                                    <th>Species</th>
                                    <th>Transfer Date</th>
                                    <th>Compliance Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $complied_clients_query = "SELECT DISTINCT comp.*, cl.full_name AS client_name_fallback
                                                         FROM compliance comp
                                                         INNER JOIN clients cl ON comp.client_id = cl.client_id
                                                         INNER JOIN livestock_poultry lp ON cl.client_id = lp.client_id
                                                         WHERE comp.compliance_status = 'Complied'
                                                         AND UPPER(lp.source) = 'DISSEMINATED'
                                                         ORDER BY comp.created_at DESC";
                                $complied_clients_result = $conn->query($complied_clients_query);
                                if ($complied_clients_result && $complied_clients_result->num_rows > 0):
                                    while($client = $complied_clients_result->fetch_assoc()): 
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($client['client_name_fallback'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($client['animal_type'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($client['species'] ?? '—'); ?></td>
                                    <td><?php echo !empty($client['transfer_date']) ? date('M d, Y', strtotime($client['transfer_date'])) : '—'; ?></td>
                                    <td><?php echo !empty($client['compliance_date']) ? date('M d, Y', strtotime($client['compliance_date'])) : '—'; ?></td>
                                </tr>
                                <?php 
                                    endwhile;
                                else: 
                                ?>
                                <tr><td colspan="5" class="text-center">No complied clients found</td></tr>
                                <?php endif; ?>
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

    <!-- Pending Clients Modal -->
    <div class="modal fade" id="pendingClientsModal" tabindex="-1" aria-labelledby="pendingClientsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pendingClientsModalLabel">
                        <i class="fas fa-clock me-2"></i>Pending Clients
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Client Name</th>
                                    <th>Animal Type</th>
                                    <th>Species</th>
                                    <th>Transfer Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $pending_clients_query = "SELECT DISTINCT comp.*, cl.full_name AS client_name_fallback
                                                        FROM compliance comp
                                                        INNER JOIN clients cl ON comp.client_id = cl.client_id
                                                        INNER JOIN livestock_poultry lp ON cl.client_id = lp.client_id
                                                        WHERE comp.compliance_status = 'Pending'
                                                        AND UPPER(lp.source) = 'DISSEMINATED'
                                                        ORDER BY comp.created_at DESC";
                                $pending_clients_result = $conn->query($pending_clients_query);
                                if ($pending_clients_result && $pending_clients_result->num_rows > 0):
                                    while($client = $pending_clients_result->fetch_assoc()): 
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($client['client_name_fallback'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($client['animal_type'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($client['species'] ?? '—'); ?></td>
                                    <td><?php echo !empty($client['transfer_date']) ? date('M d, Y', strtotime($client['transfer_date'])) : '—'; ?></td>
                                </tr>
                                <?php 
                                    endwhile;
                                else: 
                                ?>
                                <tr><td colspan="4" class="text-center">No pending clients found</td></tr>
                                <?php endif; ?>
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

    <!-- Non-Compliant Clients Modal -->
    <div class="modal fade" id="nonCompliantClientsModal" tabindex="-1" aria-labelledby="nonCompliantClientsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="nonCompliantClientsModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Non-Compliant Clients
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Client Name</th>
                                    <th>Animal Type</th>
                                    <th>Species</th>
                                    <th>Transfer Date</th>
                                    <th>Reason for Non-Compliance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $non_compliant_clients_query = "SELECT DISTINCT comp.*, cl.full_name AS client_name_fallback
                                                             FROM compliance comp
                                                             INNER JOIN clients cl ON comp.client_id = cl.client_id
                                                             INNER JOIN livestock_poultry lp ON cl.client_id = lp.client_id
                                                             WHERE comp.compliance_status = 'Non-Compliant'
                                                             AND UPPER(lp.source) = 'DISSEMINATED'
                                                             ORDER BY comp.created_at DESC";
                                $non_compliant_clients_result = $conn->query($non_compliant_clients_query);
                                if ($non_compliant_clients_result && $non_compliant_clients_result->num_rows > 0):
                                    while($client = $non_compliant_clients_result->fetch_assoc()): 
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($client['client_name_fallback'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($client['animal_type'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($client['species'] ?? '—'); ?></td>
                                    <td><?php echo !empty($client['transfer_date']) ? date('M d, Y', strtotime($client['transfer_date'])) : '—'; ?></td>
                                    <td>
                                        <div class="text-wrap" style="max-width: 300px;">
                                            <?php echo htmlspecialchars($client['remarks'] ?? 'No reason provided'); ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else: 
                                ?>
                                <tr><td colspan="5" class="text-center">No non-compliant clients found</td></tr>
                                <?php endif; ?>
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

</body>
</html>
