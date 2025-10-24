<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Top of admin_pharmaceutical.php
$admin_id = $_SESSION['user_id'] ?? 1;

    // Detect optional soft-delete column
$has_is_active = false;
$col_check = $conn->query("SHOW COLUMNS FROM pharmaceuticals LIKE 'is_active'");
if ($col_check && $col_check->num_rows > 0) {
    $has_is_active = true;
}
// Helper where fragment
$active_where = $has_is_active ? " COALESCE(is_active,1) = 1 " : " 1=1 ";

$notif_query = "SELECT * FROM notifications WHERE user_id = ? AND status = 'unread' ORDER BY timestamp DESC LIMIT 5";
$notif_stmt = $conn->prepare($notif_query);
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();
$unread_count = $notif_result->num_rows;


if (!$notif_stmt) {
    die("Notification query prepare failed: " . $conn->error);
}

$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();
$unread_count = $notif_result->num_rows;


// Get total items count
$total_query = "SELECT COUNT(stock) as total FROM pharmaceuticals WHERE" . $active_where;
$total_result = $conn->query($total_query);
$total_items = ($total_result && $total_result->num_rows > 0) ? ($total_result->fetch_assoc()['total'] ?? 0) : 0;

// Get low stock items count (below 100)
$lowStockQuery = "SELECT name, stock FROM pharmaceuticals WHERE" . $active_where . " AND stock < 100 ORDER BY stock ASC";
$lowStockResult = $conn->query($lowStockQuery);

// Expiring soon (within 30 days)
$expiringSoonQuery = "SELECT name, expiry_date FROM pharmaceuticals 
                     WHERE expiry_date > NOW() 
                     AND expiry_date <= DATE_ADD(NOW(), INTERVAL 30 DAY)
                     AND" . $active_where . "
                     ORDER BY expiry_date ASC";
$expiringSoonResult = $conn->query($expiringSoonQuery);

// Get all pharmaceuticals
$pharmaceuticals_query = "SELECT * FROM pharmaceuticals WHERE" . $active_where . " ORDER BY name";
$pharmaceuticals_result = $conn->query($pharmaceuticals_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmaceuticals Management - Bago City Inventory Management System</title>
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
            /* Remove overflow-y: auto to prevent blocking modals */
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        /* Remove custom z-index for modal and backdrop to use Bootstrap defaults */
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
        .delete-btn {
            background-color: #e74a3b;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }
        .in-stock {
            background-color: #28a745;
            color: white;
        }
        .low-stock {
            background-color: #ffc107;
            color: black;
        }
        .out-of-stock {
            background-color: #dc3545;
            color: white;
        }
        .add-btn {
            background-color: #4e73df;
            color: white;
            border-radius: 5px;
            padding: 8px 20px;
            font-weight: bold;
        }
         .truncate-text {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .show-toggle {
            cursor: pointer;
            color: #007bff;
            font-size: 0.9rem;
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
                <?php include 'includes/admin_sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="main-content">
                <div class="admin-header">
                    <h2>Pharmaceuticals Management</h2>
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
                <div class="row g-3 stats-section">
                    <div class="col-md-4">
                        <div class="metric-card">
                            <div class="metric-title">Total Items</div>
                            <div class="metric-value"><?php echo number_format($total_items); ?></div>
                            <div class="metric-detail">All pharmaceuticals</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="metric-card" data-bs-toggle="modal" data-bs-target="#lowStockModal" title="Click to view low stock items">
                            <div class="metric-title">Low Stock Items</div>
                            <div class="metric-value"><?php echo $lowStockResult->num_rows; ?></div>
                            <div class="metric-detail">Need restocking</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="metric-card" data-bs-toggle="modal" data-bs-target="#expiringModal" title="Click to view expiring items">
                            <div class="metric-title">Expiring Soon</div>
                            <div class="metric-value"><?php echo $expiringSoonResult->num_rows; ?></div>
                            <div class="metric-detail">Check expiration dates</div>
                        </div>
                    </div>
                </div>

                <!-- LOW STOCK MODAL -->
                <div class="modal fade" id="lowStockModal" tabindex="-1" aria-labelledby="lowStockModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="lowStockModalLabel">Pharmaceuticals Low in Stock (Below 100 units)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php if ($lowStockResult->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Item Name</th>
                                            <th>Current Stock</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Reset the result pointer
                                        $lowStockResult->data_seek(0);
                                        while ($row = $lowStockResult->fetch_assoc()): 
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                <td>
                                                    <span class="badge bg-danger fs-6"><?php echo $row['stock']; ?> units</span>
                                                </td>
                                                <td>
                                                    <?php if ($row['stock'] <= 0): ?>
                                                        <span class="badge bg-danger">Out of Stock</span>
                                                    <?php elseif ($row['stock'] <= 10): ?>
                                                        <span class="badge bg-danger">Critical</span>
                                                    <?php elseif ($row['stock'] <= 50): ?>
                                                        <span class="badge bg-danger">Very Low</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark">Low</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No low stock items found.</p>
                        <?php endif; ?>
                    </div>
                    </div>
                </div>
                </div>

                <!-- EXPIRING MODAL -->
                <div class="modal fade" id="expiringModal" tabindex="-1" aria-labelledby="expiringModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="expiringModalLabel">Pharmaceuticals Expiring Soon (Next 30 Days)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php if ($expiringSoonResult->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Item Name</th>
                                            <th>Expiry Date</th>
                                            <th>Days Until Expiry</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Reset the result pointer
                                        $expiringSoonResult->data_seek(0);
                                        while ($row = $expiringSoonResult->fetch_assoc()): 
                                            $expiryDate = new DateTime($row['expiry_date']);
                                            $today = new DateTime();
                                            $daysUntilExpiry = $today->diff($expiryDate)->days;
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                <td><?php echo $row['expiry_date']; ?></td>
                                                <td>
                                                    <span class="badge bg-warning text-dark fs-6"><?php echo $daysUntilExpiry; ?> days</span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No pharmaceuticals expiring in the next 30 days.</p>
                        <?php endif; ?>
                    </div>
                    </div>
                </div>
                </div>

                <!-- Search and Filter -->
                <div class="row mt-1 search-section">
                    <div class="col-md-6">
                        <div class="search-container">
                            <input type="text" id="searchPharmaceutical" placeholder="Search by name, category...">
                            <button type="button"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="d-flex justify-content-end gap-2">
                            <select class="filter-dropdown" id="filterStatus">
                                <option value="">Filter by Status</option>
                                <option value="In Stock">In Stock</option>
                                <option value="Low stock">Low stock</option>
                                <option value="Out of Stock">Out of Stock</option>
                            </select>
                            <select class="filter-dropdown" id="filterCategory">
                                <option value="">Filter by Category</option>
                                <option value="Antibiotics">Antibiotics</option>
                                <option value="Vitamins">Vitamins</option>
                                <option value="Vaccine">Vaccines</option>
                                <option value="Antiparasitic">Antiparasitic</option>
                                <option value="Dewormers">Dewormers</option>
                                <option value="Supplements">Supplements</option>
                                <option value="Other">Other</option>
                            </select>
                            <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()" title="Clear all filters" style="display: none;">
                                <i class="fas fa-times"></i> Clear Filters
                            </button>
                            <button type="button" class="add-btn" data-bs-toggle="modal" data-bs-target="#addPharmaceuticalModal">
                                 Add Pharmaceutical
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Pharmaceuticals Table -->
                <div class="table-responsive mt-2 table-section">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Stock</th>
                                <th>Category</th>
                                <th>Unit</th>
                                <th>Expiration Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($pharmaceuticals_result && $pharmaceuticals_result->num_rows > 0): ?>
                                <?php while($item = $pharmaceuticals_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $item['name']; ?></td>
                                        <td><?php echo $item['stock']; ?></td>
                                        <td><?php echo $item['category']; ?></td>
                                        <td><?php echo $item['unit']; ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($item['expiry_date'])); ?></td>
                                        <td>
                                            <?php if ($item['stock'] <= 0): ?>
                                                <span class="status-badge out-of-stock">Out of Stock</span>
                                            <?php elseif ($item['stock'] <= 100): ?>
                                                <span class="status-badge low-stock">Low stock</span>
                                            <?php else: ?>
                                                <span class="status-badge in-stock">In Stock</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="action-btn edit-btn" 
                                                onclick="editPharmaceutical(
                                                    '<?php echo $item['pharma_id']; ?>',
                                                    '<?php echo $item['name']; ?>',
                                                    '<?php echo $item['stock']; ?>',
                                                    '<?php echo $item['category']; ?>',
                                                    '<?php echo $item['unit']; ?>',
                                                    '<?php echo date('Y-m-d', strtotime($item['expiry_date'])); ?>'
                                                )">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="action-btn delete-btn" 
                                                onclick="deletePharmaceutical('<?php echo $item['pharma_id']; ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No pharmaceutical items found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                </div>
                
                <!-- Add Pharmaceutical Modal -->
                <div class="modal fade" id="addPharmaceuticalModal" tabindex="-1" aria-labelledby="addPharmaceuticalModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addPharmaceuticalModalLabel">Add New Pharmaceutical</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="admin_add_pharmaceutical.php" method="post">
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Name</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="stock" class="form-label">Stock</label>
                                        <input type="number" class="form-control" id="stock" name="stock" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="category" class="form-label">Category</label>
                                        <select class="form-select" id="category" name="category" required>
                                            <option value="">Select Category</option>
                                            <option value="Antibiotics">Antibiotics</option>
                                            <option value="Vitamins">Vitamins</option>
                                            <option value="Vaccine">Vaccines</option>
                                            <option value="Antiparasitic">Antiparasitic</option>
                                            <option value="Dewormers">Dewormers</option>
                                            <option value="Supplements">Supplements</option>
                                            <option value="Other">Other Medications/Treatments</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="unit" class="form-label">Unit</label>
                                        <select class="form-select" id="unit" name="unit" required>
                                            <option value="">Select Unit</option>
                                            <option value="Bottles">Bottles</option>
                                            <option value="Vials">Vials</option>
                                            <option value="Tablets">Tablets</option>
                                            <option value="Capsules">Capsules</option>
                                            <option value="Sachets">Sachets</option>
                                            <option value="Packs">Packs</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="expiration_date" class="form-label">Expiration Date</label>
                                        <input type="date" class="form-control" id="expiry_date" name="expiry_date" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Add Item</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Edit Pharmaceutical Modal -->
                <div class="modal fade" id="editPharmaceuticalModal" tabindex="-1" aria-labelledby="editPharmaceuticalModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editPharmaceuticalModalLabel">Edit Pharmaceutical</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="admin_update_pharmaceutical.php" method="post">
                                <div class="modal-body">
                                    <input type="hidden" id="edit_id" name="id">
                                    <div class="mb-3">
                                        <label for="edit_name" class="form-label">Name</label>
                                        <input type="text" class="form-control" id="edit_name" name="name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_stock" class="form-label">Stock</label>
                                        <input type="number" class="form-control" id="edit_stock" name="stock" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_category" class="form-label">Category</label>
                                        <select class="form-select" id="edit_category" name="category" required>
                                            <option value="">Select Category</option>
                                            <option value="Antibiotics">Antibiotics</option>
                                            <option value="Vitamins">Vitamins</option>
                                            <option value="Vaccine">Vaccine</option>
                                            <option value="Antiparasitic">Antiparasitic</option>
                                            <option value="Dewormers">Dewormers</option>
                                            <option value="Supplements">Supplements</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_unit" class="form-label">Unit</label>
                                        <select class="form-select" id="edit_unit" name="unit" required>
                                            <option value="">Select Unit</option>
                                            <option value="Bottles">Bottles</option>
                                            <option value="Vials">Vials</option>
                                            <option value="Tablets">Tablets</option>
                                            <option value="Capsules">Capsules</option>
                                            <option value="Sachets">Sachets</option>
                                            <option value="Packs">Packs</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_expiration_date" class="form-label">Expiration Date</label>
                                        <input type="date" class="form-control" id="edit_expiry_date" name="expiry_date" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                <script>
                    // Combined filter function
function filterTable() {
    const searchValue = document.getElementById('searchPharmaceutical').value.toLowerCase();
    const statusFilter = document.getElementById('filterStatus').value;
    const categoryFilter = document.getElementById('filterCategory').value;
    const tableRows = document.querySelectorAll('.table tbody tr');
    
    let totalItems = 0;
    let lowStockItems = [];
    let expiringItems = [];
    
    tableRows.forEach(row => {
        let showRow = true;
        const cells = row.querySelectorAll('td');
        
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
        
        // Status filter (column index 5 - Status column)
        if (statusFilter && showRow) {
            const statusCell = cells[5];
            if (statusCell) {
                const statusText = statusCell.textContent.trim();
                if (statusText !== statusFilter) {
                    showRow = false;
                }
            }
        }
        
        // Category filter (column index 2 - Category column)
        if (categoryFilter && showRow) {
            const categoryCell = cells[2];
            if (categoryCell) {
                const categoryText = categoryCell.textContent.trim();
                if (categoryText !== categoryFilter) {
                    showRow = false;
                }
            }
        }
        
        row.style.display = showRow ? '' : 'none';
        
        // Collect data for stats update
        if (showRow) {
            totalItems++;
            
            // Check for low stock items (Status column - index 5)
            const statusCell = cells[5];
            if (statusCell && statusCell.textContent.trim() === 'Low stock') {
                lowStockItems.push({
                    name: cells[0].textContent.trim(),
                    stock: cells[1].textContent.trim(),
                    status: statusCell.textContent.trim()
                });
            }
            
            // Check for expiring items (need to check expiry date - index 4)
            const expiryCell = cells[4];
            if (expiryCell) {
                const expiryDate = new Date(expiryCell.textContent.trim());
                const today = new Date();
                const thirtyDaysFromNow = new Date();
                thirtyDaysFromNow.setDate(today.getDate() + 30);
                
                if (expiryDate <= thirtyDaysFromNow && expiryDate >= today) {
                    const daysUntilExpiry = Math.ceil((expiryDate - today) / (1000 * 60 * 60 * 24));
                    expiringItems.push({
                        name: cells[0].textContent.trim(),
                        expiry_date: expiryCell.textContent.trim(),
                        days_until_expiry: daysUntilExpiry
                    });
                }
            }
        }
    });
    
    // Update stats cards
    updateStatsCards(totalItems, lowStockItems.length, expiringItems.length);
    
    // Update modals
    updateLowStockModal(lowStockItems);
    updateExpiringModal(expiringItems);
    
    // Show/hide clear filters button based on active filters
    const hasActiveFilters = searchValue || statusFilter || categoryFilter;
    const clearButton = document.querySelector('button[onclick="clearFilters()"]');
    if (clearButton) {
        clearButton.style.display = hasActiveFilters ? 'inline-block' : 'none';
    }
    
    // Add visual indicator for filtered results
    const tableSection = document.querySelector('.table-section');
    if (tableSection) {
        if (hasActiveFilters) {
            tableSection.style.borderLeft = '4px solid #6c63ff';
            tableSection.style.paddingLeft = '10px';
        } else {
            tableSection.style.borderLeft = 'none';
            tableSection.style.paddingLeft = '0';
        }
    }
}

// Function to update stats cards
function updateStatsCards(totalItems, lowStockCount, expiringCount) {
    // Get all metric cards in the stats section
    const metricCards = document.querySelectorAll('.stats-section .metric-card .metric-value');
    
    // Update Total Items card (first card)
    if (metricCards[0]) {
        metricCards[0].textContent = totalItems.toString();
    }
    
    // Update Low Stock Items card (second card)
    if (metricCards[1]) {
        metricCards[1].textContent = lowStockCount.toString();
    }
    
    // Update Expiring Soon card (third card)
    if (metricCards[2]) {
        metricCards[2].textContent = expiringCount.toString();
    }
    
    // Debug logging
    console.log(`Stats updated: Total=${totalItems}, Low Stock=${lowStockCount}, Expiring=${expiringCount}`);
}

// Function to update Low Stock Modal
function updateLowStockModal(lowStockItems) {
    const modalBody = document.querySelector('#lowStockModal .modal-body');
    
    if (lowStockItems.length > 0) {
        let tableHTML = `
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Current Stock</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        lowStockItems.forEach(item => {
            const stockNumber = parseInt(item.stock);
            let statusBadge = '';
            
            if (stockNumber <= 0) {
                statusBadge = '<span class="badge bg-danger">Out of Stock</span>';
            } else if (stockNumber <= 10) {
                statusBadge = '<span class="badge bg-danger">Critical</span>';
            } else if (stockNumber <= 50) {
                statusBadge = '<span class="badge bg-danger">Very Low</span>';
            } else {
                statusBadge = '<span class="badge bg-warning text-dark">Low</span>';
            }
            
            tableHTML += `
                <tr>
                    <td>${item.name}</td>
                    <td><span class="badge bg-danger fs-6">${item.stock} units</span></td>
                </tr>
            `;
        });
        
        tableHTML += `
                    </tbody>
                </table>
            </div>
        `;
        
        modalBody.innerHTML = tableHTML;
    } else {
        modalBody.innerHTML = '<p class="text-muted">No low stock items found in current filter.</p>';
    }
}

// Function to update Expiring Modal
function updateExpiringModal(expiringItems) {
    const modalBody = document.querySelector('#expiringModal .modal-body');
    
    if (expiringItems.length > 0) {
        let tableHTML = `
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Expiry Date</th>
                            <th>Days Until Expiry</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        expiringItems.forEach(item => {
            tableHTML += `
                <tr>
                    <td>${item.name}</td>
                    <td>${item.expiry_date}</td>
                    <td><span class="badge bg-warning text-dark fs-6">${item.days_until_expiry} days</span></td>
                </tr>
            `;
        });
        
        tableHTML += `
                    </tbody>
                </table>
            </div>
        `;
        
        modalBody.innerHTML = tableHTML;
    } else {
        modalBody.innerHTML = '<p class="text-muted">No pharmaceuticals expiring in the next 30 days in current filter.</p>';
    }
}
                    
                    // Combined filter function
function filterTable() {
    const searchValue = document.getElementById('searchPharmaceutical').value.toLowerCase();
    const statusFilter = document.getElementById('filterStatus').value;
    const categoryFilter = document.getElementById('filterCategory').value;
    const tableRows = document.querySelectorAll('.table tbody tr');
    
    let totalItems = 0;
    let lowStockItems = [];
    let expiringItems = [];
    
    tableRows.forEach(row => {
        let showRow = true;
        const cells = row.querySelectorAll('td');
        
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
        
        // Status filter (column index 5 - Status column)
        if (statusFilter && showRow) {
            const statusCell = cells[5];
            if (statusCell) {
                const statusText = statusCell.textContent.trim();
                if (statusText !== statusFilter) {
                    showRow = false;
                }
            }
        }
        
        // Category filter (column index 2 - Category column)
        if (categoryFilter && showRow) {
            const categoryCell = cells[2];
            if (categoryCell) {
                const categoryText = categoryCell.textContent.trim();
                if (categoryText !== categoryFilter) {
                    showRow = false;
                }
            }
        }
        
        row.style.display = showRow ? '' : 'none';
        
        // Collect data for stats update
        if (showRow) {
            totalItems++;
            
            // Check for low stock items (Status column - index 5)
            const statusCell = cells[5];
            if (statusCell && statusCell.textContent.trim() === 'Low stock') {
                lowStockItems.push({
                    name: cells[0].textContent.trim(),
                    stock: cells[1].textContent.trim(),
                    status: statusCell.textContent.trim()
                });
            }
            
            // Check for expiring items (need to check expiry date - index 4)
            const expiryCell = cells[4];
            if (expiryCell) {
                const expiryDate = new Date(expiryCell.textContent.trim());
                const today = new Date();
                const thirtyDaysFromNow = new Date();
                thirtyDaysFromNow.setDate(today.getDate() + 30);
                
                if (expiryDate <= thirtyDaysFromNow && expiryDate >= today) {
                    const daysUntilExpiry = Math.ceil((expiryDate - today) / (1000 * 60 * 60 * 24));
                    expiringItems.push({
                        name: cells[0].textContent.trim(),
                        expiry_date: expiryCell.textContent.trim(),
                        days_until_expiry: daysUntilExpiry
                    });
                }
            }
        }
    });
    
    // Update stats cards
    updateStatsCards(totalItems, lowStockItems.length, expiringItems.length);
    
    // Update modals
    updateLowStockModal(lowStockItems);
    updateExpiringModal(expiringItems);
}


// Function to update Low Stock Modal
function updateLowStockModal(lowStockItems) {
    const modalBody = document.querySelector('#lowStockModal .modal-body');
    
    if (lowStockItems.length > 0) {
        let tableHTML = `
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Current Stock</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        lowStockItems.forEach(item => {
            const stockNumber = parseInt(item.stock);
            let statusBadge = '';
            
            if (stockNumber <= 0) {
                statusBadge = '<span class="badge bg-danger">Out of Stock</span>';
            } else if (stockNumber <= 10) {
                statusBadge = '<span class="badge bg-danger">Critical</span>';
            } else if (stockNumber <= 50) {
                statusBadge = '<span class="badge bg-danger">Very Low</span>';
            } else {
                statusBadge = '<span class="badge bg-warning text-dark">Low</span>';
            }
            
            tableHTML += `
                <tr>
                    <td>${item.name}</td>
                    <td><span class="badge bg-danger fs-6">${item.stock} units</span></td>
                </tr>
            `;
        });
        
        tableHTML += `
                    </tbody>
                </table>
            </div>
        `;
        
        modalBody.innerHTML = tableHTML;
    } else {
        modalBody.innerHTML = '<p class="text-muted">No low stock items found in current filter.</p>';
    }
}

// Function to update Expiring Modal
function updateExpiringModal(expiringItems) {
    const modalBody = document.querySelector('#expiringModal .modal-body');
    
    if (expiringItems.length > 0) {
        let tableHTML = `
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Expiry Date</th>
                            <th>Days Until Expiry</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        expiringItems.forEach(item => {
            tableHTML += `
                <tr>
                    <td>${item.name}</td>
                    <td>${item.expiry_date}</td>
                    <td><span class="badge bg-warning text-dark fs-6">${item.days_until_expiry} days</span></td>
                </tr>
            `;
        });
        
        tableHTML += `
                    </tbody>
                </table>
            </div>
        `;
        
        modalBody.innerHTML = tableHTML;
    } else {
        modalBody.innerHTML = '<p class="text-muted">No pharmaceuticals expiring in the next 30 days in current filter.</p>';
    }
}

// ... existing code ...

                    // Initialize stats on page load
                    document.addEventListener('DOMContentLoaded', function() {
                        filterTable(); // Call filterTable to initialize stats
                        
                        // Add event listeners for filters
                        const searchInput = document.getElementById('searchPharmaceutical');
                        const statusFilter = document.getElementById('filterStatus');
                        const categoryFilter = document.getElementById('filterCategory');
                        
                        if (searchInput) {
                            searchInput.addEventListener('input', filterTable);
                        }
                        
                        if (statusFilter) {
                            statusFilter.addEventListener('change', filterTable);
                        }
                        
                        if (categoryFilter) {
                            categoryFilter.addEventListener('change', filterTable);
                        }
                        
                        // Add clear filters functionality
                        function clearFilters() {
                            if (searchInput) searchInput.value = '';
                            if (statusFilter) statusFilter.value = '';
                            if (categoryFilter) categoryFilter.value = '';
                            filterTable();
                        }
                        
                        // Make clearFilters globally available
                        window.clearFilters = clearFilters;
                    });
                    
                    // Edit pharmaceutical function
                    function editPharmaceutical(id, name, stock, category, unit, expiry_date) {
                        document.getElementById('edit_id').value = id;
                        document.getElementById('edit_name').value = name;
                        document.getElementById('edit_stock').value = stock;
                        document.getElementById('edit_category').value = category;
                        document.getElementById('edit_unit').value = unit;
                        document.getElementById('edit_expiry_date').value = expiry_date;
                        
                        const editModal = new bootstrap.Modal(document.getElementById('editPharmaceuticalModal'));
                        editModal.show();
                    }
                    
                    // Delete pharmaceutical function
                    function deletePharmaceutical(id) {
                        if (confirm('Are you sure you want to delete this item?')) {
                            window.location.href = 'admin_delete_pharmaceutical.php?id=' + id;
                        }
                    }
                    
                    // Set today's date as min for expiration date inputs
                    document.addEventListener('DOMContentLoaded', function() {
                        const today = new Date().toISOString().split('T')[0];
                        document.getElementById('expiry_date').min = today;
                        document.getElementById('edit_expiry_date').min = today;
                    });

                      function toggleDescription(element) {
                    const desc = element.previousElementSibling;
                    if (desc.classList.contains('truncate-text')) {
                        desc.classList.remove('truncate-text');
                        element.textContent = 'Show less';
                    } else {
                        desc.classList.add('truncate-text');
                        element.textContent = 'Show more';
                    }
                }

                const notifDropdown = document.getElementById('notifDropdown');
                if (notifDropdown) {
                    notifDropdown.addEventListener('click', function () {
                        fetch('mark_notifications_read.php');
                    });
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

        <!-- Session Message Modal (consumes success/error set by actions on this page) -->
        <div class="modal fade" id="messageModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content border-0 shadow">
                    <div class="modal-body text-center p-4">
                        <div class="mb-3">
                            <i id="modalIcon" class="fas fa-info-circle text-primary" style="font-size: 2rem;"></i>
                        </div>
                        <h6 class="mb-3" id="modalTitle">Notice</h6>
                        <p class="text-muted mb-4" id="modalMessage"></p>
                        <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">OK</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
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