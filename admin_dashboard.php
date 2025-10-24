
<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get livestock count (sum of quantities)
$livestock_result = $conn->query("SELECT COALESCE(SUM(quantity), 0) as count FROM livestock_poultry WHERE animal_type = 'Livestock'");
$livestock_count = $livestock_result->fetch_assoc()['count'];

// Get poultry count (sum of quantities)
$poultry_result = $conn->query("SELECT COALESCE(SUM(quantity), 0) as count FROM livestock_poultry WHERE animal_type = 'Poultry'");
$poultry_count = $poultry_result->fetch_assoc()['count'];

// Get pharmaceuticals count (number of distinct pharmaceutical items)
$pharma_result = $conn->query("SELECT COUNT(*) as count FROM pharmaceuticals");
$pharma_count = $pharma_result->fetch_assoc()['count'];

// Get recent transactions
$transactions_query = "SELECT t.*, c.full_name as client_name, p.name as pharma_name 
                      FROM transactions t 
                      JOIN clients c ON t.client_id = c.client_id 
                      JOIN pharmaceuticals p ON t.pharma_id = p.pharma_id 
                      ORDER BY t.request_date DESC LIMIT 5";
$transactions = $conn->query($transactions_query);

// Get alerts
$low_stock = $conn->query("SELECT * FROM pharmaceuticals WHERE stock <= 5 LIMIT 1");
$low_stock_item = $low_stock->fetch_assoc();

// Get expiring items
$expiring_soon = $conn->query("SELECT * FROM pharmaceuticals WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) LIMIT 1");
$expiring_item = $expiring_soon->fetch_assoc();

// Get notifications for admin
$admin_id = $_SESSION['user_id'] ?? 1;
$notif_query = "SELECT * FROM notifications WHERE user_id = ? AND (status = 'Unread' OR status = 'unread' OR status = 0) ORDER BY timestamp DESC LIMIT 5";
$notif_stmt = $conn->prepare($notif_query);
if ($notif_stmt) {
    $notif_stmt->bind_param("i", $admin_id);
    $notif_stmt->execute();
    $notif_result = $notif_stmt->get_result();
    $unread_count = $notif_result->num_rows;
} else {
    $unread_count = 0;
    $notif_result = (object)[ 'num_rows' => 0, 'fetch_assoc' => function() { return null; } ];
}
// // Get upcoming vaccinations
// $upcoming_vacc = $conn->query("SELECT * FROM vaccination_schedule WHERE schedule_date > CURDATE() ORDER BY schedule_date ASC LIMIT 1");
// $upcoming = $upcoming_vacc->fetch_assoc();

// // Get completed health checks
// $completed_checks = $conn->query("SELECT * FROM health_checks WHERE status = 'Completed' ORDER BY check_date DESC LIMIT 1");
// $completed = $completed_checks->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bago City Veterinary Office</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
        .system-title {
            display: flex;
            align-items: center;
        }
        .system-title img {
            width: 50px;
            height: 50px;
            margin-right: 15px;
        }
        /* Remove underline from inventory links */
        .view-inventory {
            text-decoration: none !important;
            color: #212529 !important;
            cursor: pointer;
        }
        .view-inventory:hover {
            color: #0d6efd !important;
        }
        /* Minimize and style scrollbar for white-box */
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
        
        /* Notification dropdown styles */
        .notification-item {
            transition: background-color 0.2s ease;
        }
        
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        
        .dropdown-item.notification-item {
            border: none;
            padding: 8px 16px;
        }
        
        .dropdown-item.notification-item:hover {
            background-color: #e9ecef;
        }
        
        /* Reduce padding for recent transactions card */
        .recent-transactions-card .card-body {
            padding: 0.5rem 1rem;
        }
        
        /* Attention-grabbing alert header styles */
        .alerts-card .card-header {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
            color: white;
            font-weight: bold;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
            animation: pulse-header 2s infinite;
            border-bottom: 3px solid #ff4757;
        }
        
        .alerts-card .card-header i {
            animation: bounce-icon 1.5s infinite;
        }
        
        @keyframes pulse-header {
            0% { 
                background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
                box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);
            }
            50% { 
                background: linear-gradient(135deg, #ff5252, #ff7979);
                box-shadow: 0 4px 16px rgba(255, 107, 107, 0.5);
            }
            100% { 
                background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
                box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);
            }
        }
        
        @keyframes bounce-icon {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-3px); }
            60% { transform: translateY(-2px); }
        }
        
        /* Alerts container styling */
        #alerts-container {
            padding: 10px;
            min-height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .alert-item {
            flex: 1;
            margin-bottom: 8px;
            padding: 8px 12px;
            border-left: 3px solid #ffc107;
            background-color: #fff3cd;
            border-radius: 4px;
            font-size: 0.9rem;
            line-height: 1.4;
            transition: all 0.2s ease;
        }
        
        .alert-item:hover {
            background-color: #ffeaa7;
            transform: translateX(2px);
        }
        
        .alert-item:last-child {
            margin-bottom: 0;
        }

        /* Enhanced Card Styling */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .card-header {
            background: linear-gradient(135deg, #6c63ff, #8B9FF7);
            color: white;
            border: none;
            padding: 15px 20px;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .card-header i {
            margin-right: 8px;
            font-size: 1.2rem;
        }

        .card-body {
            padding: 20px;
            background: #fafbff;
        }

        /* Enhanced Badge Styling */
        .badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .badge.bg-primary {
            background: #6c63ff !important;
        }

        .badge.bg-success {
            background: #6c63ff !important;
        }

        .badge.bg-info {
            background: #6c63ff !important;
        }

        .badge.bg-warning {
            background: #6c63ff !important;
            color: white !important;
        }

        .badge.bg-danger {
            background: #dc3545 !important;
        }

        /* Enhanced Inventory Links */
        .view-inventory {
            text-decoration: none !important;
            color: #495057 !important;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 8px 12px;
            border-radius: 8px;
            display: inline-block;
            width: 100%;
        }

        .view-inventory:hover {
            color: #6c63ff !important;
            background: rgba(108, 99, 255, 0.1);
            transform: translateX(5px);
        }

        /* Enhanced Transaction Items */
        .transaction-item {
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
            transition: all 0.2s ease;
        }

        .transaction-item:last-child {
            border-bottom: none;
        }

        .transaction-item:hover {
            background: rgba(108, 99, 255, 0.05);
            padding-left: 10px;
            border-radius: 8px;
        }

        /* Enhanced ML Insights */
        .ml-insight-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .ml-insight-item:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .ml-insight-item.critical {
            background: #f8f9fa;
            border-color: #dc3545;
        }

        .ml-insight-item.trend {
            background: #f8f9fa;
            border-color: #6c63ff;
        }

        .ml-insight-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 1.2rem;
        }

        .ml-insight-icon.critical {
            background: #dc3545;
            color: white;
        }

        .ml-insight-icon.trend {
            background: #6c63ff;
            color: white;
        }

        /* Enhanced Button Styling */
        .btn-outline-primary {
            border: 2px solid #6c63ff;
            color: #6c63ff;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background: #6c63ff;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(108, 99, 255, 0.3);
        }

        /* Enhanced Notification Button */
        .btn-light {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 1px solid #dee2e6;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-light:hover {
            background: linear-gradient(135deg, #e9ecef, #dee2e6);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        /* Enhanced Profile Section */
        .admin-profile {
            background: none;
            padding: 0;
            border-radius: 0;
            border: none;
        }

        .admin-profile img {
            border: none;
            box-shadow: none;
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

        /* Enhanced Header */
        .admin-header h2 {
            color: #000000;
            font-weight: 700;
        }

        /* Enhanced Error States */
        .error-message {
            background: #f8f9fa;
            border: 1px solid #dc3545;
            border-radius: 10px;
            padding: 15px;
            color: #721c24;
            text-align: center;
            font-weight: 500;
        }

        /* Enhanced Loading States */
        .loading-message {
            background: #f8f9fa;
            border: 1px solid #6c63ff;
            border-radius: 10px;
            padding: 15px;
            color: #495057;
            text-align: center;
            font-weight: 500;
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

        /* Responsive Enhancements */
        @media (max-width: 768px) {
            .card {
                margin-bottom: 20px;
            }
            
            .card-header {
                padding: 12px 15px;
                font-size: 1rem;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .badge {
                padding: 6px 10px;
                font-size: 0.8rem;
            }
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
                    <h2>Admin Dashboard</h2>
                    <div class="admin-profile">
                        <!-- Notification Icon with Badge -->
                        <div class="dropdown me-3">
                            <button class="btn position-relative" id="notifBtn" data-bs-toggle="dropdown" aria-expanded="false" style="background: none; border: none;">
                                <i class="fas fa-bell"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notifCount">
                                    <?php echo $unread_count > 0 ? $unread_count : ''; ?>
                                </span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" id="notifList" style="width: 300px; max-height: 300px; overflow-y: auto;">
                                <?php if ($notif_result->num_rows > 0): ?>
                                    <?php while ($notif = $notif_result->fetch_assoc()): ?>
                                        <li class="dropdown-item small notification-item" style="cursor: pointer;" onclick="handleNotificationClick('<?php echo htmlspecialchars($notif['message']); ?>')">
                                            <div class="d-flex align-items-start">
                                                <?php if (strpos($notif['message'], 'New pharmaceutical request from') !== false): ?>
                                                    <i class="fas fa-syringe text-warning me-2 mt-1"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-bell text-primary me-2 mt-1"></i>
                                                <?php endif; ?>
                                                <div class="flex-grow-1">
                                                    <div class="fw-medium"><?php echo htmlspecialchars($notif['message']); ?></div>
                                                    <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($notif['timestamp'])); ?></small>
                                                </div>
                                            </div>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                    <?php endwhile; ?>
                                    <li class="dropdown-item text-center">
                                        <a href="admin_notifications.php" class="text-decoration-none fw-medium">View All Notifications</a>
                                    </li>
                                <?php else: ?>
                                    <li class="dropdown-item text-center text-muted">
                                        <i class="fas fa-check-circle me-2"></i>No new notifications
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>

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
                <!-- Inventory Overview -->
                <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center">
                            <i class="fas fa-box me-2"></i> Inventory Overview
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <a href="#" class="view-inventory" data-type="livestock" data-bs-toggle="modal" data-bs-target="#inventoryModal">
                                    Livestock
                                </a>
                                <span class="badge bg-primary"><?php echo $livestock_count; ?> heads</span>
                            </div> 
                            <div class="d-flex justify-content-between mb-3">
                                <a href="#" class="view-inventory" data-type="poultry" data-bs-toggle="modal" data-bs-target="#inventoryModal">
                                    Poultry
                                </a>
                                <span class="badge bg-success"><?php echo $poultry_count; ?> heads</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="#" class="view-inventory" data-type="pharmaceuticals" data-bs-toggle="modal" data-bs-target="#inventoryModal">
                                    Pharmaceuticals
                                </a>
                                <span class="badge bg-info"><?php echo $pharma_count; ?> items</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Inventory Details Modal -->
                <div class="modal fade" id="inventoryModal" tabindex="-1" aria-labelledby="inventoryModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="inventoryModalLabel">Inventory Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="inventoryModalBody">
                        <p class="text-center">Loading...</p>
                    </div>
                    </div>
                </div>
                </div>       
                    <!-- ML Insights -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex align-items-center">
                                <i class="fas fa-chart-line me-2"></i> ML Insights
                                <a href="ml_insights/admin_ml_insights.php" class="btn btn-sm btn-outline-primary ms-auto">
                                    <i class="fas fa-external-link-alt"></i> View Details
                                </a>
                            </div>
                            <div class="card-body" id="ml-insights-container">
                                <div class="loading-message">
                                    <i class="fas fa-spinner fa-spin me-2"></i>Loading insights...
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Transactions -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 recent-transactions-card">
                            <div class="card-header d-flex align-items-center">
                                <i class="fas fa-exchange-alt me-2"></i> Recent Transactions
                            </div>
                            <div class="card-body">
                                <?php if ($transactions && $transactions->num_rows > 0): ?>
                                    <?php while ($transaction = $transactions->fetch_assoc()): ?>
                                        <div class="transaction-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($transaction['client_name']); ?></div>
                                                <div class="small text-muted"><?php echo htmlspecialchars($transaction['pharma_name']); ?></div>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge <?php echo $transaction['status'] === 'Approved' || $transaction['status'] === 'Issued' ? 'bg-success' : 'bg-warning'; ?>">
                                                    <?php echo $transaction['status'] === 'Approved' || $transaction['status'] === 'Issued' ? '-' : ''; ?><?php echo $transaction['quantity']; ?> units
                                                </span>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <div>No recent transactions</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <!-- Health Risk Monitoring -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex align-items-center">
                                <i class="fas fa-heartbeat me-2"></i> Health Risk Monitoring
                                <a href="admin_health_risk_monitoring.php" class="btn btn-sm btn-outline-primary ms-auto">
                                    <i class="fas fa-external-link-alt"></i> View Details
                                </a>
                            </div>
                            <div class="card-body" id="health-risk-container">
                                <div class="loading-message">
                                    <i class="fas fa-spinner fa-spin me-2"></i>Loading health risk data...
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Alerts & Notifications -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100 alerts-card">
                        <div class="card-header d-flex align-items-center">
                            <i class="fas fa-bell me-2"></i> Alerts & Notifications
                        </div>
                        <div class="card-body" id="alerts-container">
                            <div class="loading-message">
                                <i class="fas fa-spinner fa-spin me-2"></i>Loading alerts...
                            </div>
                        </div>
                    </div>
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

        function loadHealthRiskMonitoring() {
            fetch('get_health_risk_assessment.php?action=summary')
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('health-risk-container');
                    
                    if (data.success && data.summary) {
                        let html = '';
                        const summary = data.summary;
                        
                        // Show high-risk animals count
                        const highRiskCount = (summary.High?.count || 0) + (summary.Critical?.count || 0);
                        
                        if (highRiskCount > 0) {
                            html += `
                                <div class="alert alert-warning mb-3">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>${highRiskCount} animals</strong> require immediate attention
                                </div>
                            `;
                        }
                        
                        // Risk level summary
                        html += `
                            <div class="row text-center mb-3">
                                <div class="col-3">
                                    <div class="small text-muted">Low Risk</div>
                                    <div class="fw-bold text-success">${summary.Low?.count || 0}</div>
                                </div>
                                <div class="col-3">
                                    <div class="small text-muted">Medium</div>
                                    <div class="fw-bold text-warning">${summary.Medium?.count || 0}</div>
                                </div>
                                <div class="col-3">
                                    <div class="small text-muted">High</div>
                                    <div class="fw-bold text-danger">${summary.High?.count || 0}</div>
                                </div>
                                <div class="col-3">
                                    <div class="small text-muted">Critical</div>
                                    <div class="fw-bold text-danger">${summary.Critical?.count || 0}</div>
                                </div>
                            </div>
                        `;
                        
                        // Show recent high-risk animals
                        if (highRiskCount > 0) {
                            html += `
                                <div class="mt-3 pt-3 border-top">
                                    <div class="small text-muted mb-2">Recent High-Risk Animals:</div>
                                    <div class="text-danger">
                                        <i class="fas fa-heartbeat me-1"></i>
                                        ${summary.Critical?.count || 0} Critical, ${summary.High?.count || 0} High Risk
                                    </div>
                                </div>
                            `;
                        } else {
                            html += `
                                <div class="mt-3 pt-3 border-top text-center">
                                    <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                                    <div class="text-success">All animals are at low risk</div>
                                </div>
                            `;
                        }
                        
                        container.innerHTML = html;
                    } else {
                        container.innerHTML = `
                            <div class="text-center text-muted">
                                <i class="fas fa-heartbeat fa-2x mb-2"></i>
                                <div>Loading health risk assessment data...</div>
                            </div>
                        `;
                    }
                })
                .catch(err => {
                    console.error('Health risk monitoring fetch failed', err);
                    document.getElementById('health-risk-container').innerHTML = 
                        '<div class="error-message"><i class="fas fa-exclamation-triangle me-2"></i>Error loading health risk data</div>';
                });
        }

            function loadAlerts() {
                fetch('fetch_alerts.php')
                    .then(res => res.json())
                    .then(data => {
                        const container = document.getElementById('alerts-container');
                        container.innerHTML = ''; // Clear current content

                        if (data.length === 0) {
                            container.innerHTML = `<div class="text-muted">No alerts at the moment.</div>`;
                            return;
                        }

                        data.forEach(alert => {
                            const alertHTML = `
                                <div class="alert-item mb-2 p-2 border-start border-3 border-warning bg-light rounded">
                                    <strong>${alert.type}:</strong> ${alert.message}
                                </div>`;
                            container.innerHTML += alertHTML;
                        });
                    })
                    .catch(err => console.error('Alert fetch failed', err));
            }

            function loadMLInsights() {
                fetch('ml_insights/api/get_ml_insights.php')
                    .then(res => res.json())
                    .then(data => {
                        const container = document.getElementById('ml-insights-container');
                        
                        if (data.success && data.insights) {
                            let html = '';
                            
                            // Show top recommendations
                            if (data.insights.recommendations && data.insights.recommendations.length > 0) {
                                data.insights.recommendations.slice(0, 2).forEach(rec => {
                                    const icon = rec.type === 'urgent' ? 'exclamation-triangle' : 
                                               rec.type === 'warning' ? 'exclamation-circle' : 'info-circle';
                                    const itemClass = rec.type === 'urgent' ? 'ml-insight-item critical' : 
                                                     rec.type === 'warning' ? 'ml-insight-item' : 'ml-insight-item trend';
                                    const iconClass = rec.type === 'urgent' ? 'ml-insight-icon critical' : 
                                                     rec.type === 'warning' ? 'ml-insight-icon' : 'ml-insight-icon trend';
                                    
                                    html += `
                                        <div class="${itemClass}">
                                            <div class="d-flex align-items-center">
                                                <div class="${iconClass}">
                                                    <i class="fas fa-${icon}"></i>
                                                </div>
                                                <div>
                                                    <strong>${rec.message}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                });
                            }
                            
                            // Show trend information
                            if (data.insights.pharmaceutical_demand) {
                                const trendData = data.insights.pharmaceutical_demand;
                                
                                html += `
                                    <div class="ml-insight-item trend">
                                        <div class="d-flex align-items-center">
                                            <div class="ml-insight-icon trend">
                                                <i class="fas fa-chart-line"></i>
                                            </div>
                                            <div>
                                                <strong>${trendData.trend_text}</strong>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            }
                            
                            if (html === '') {
                                html = '<div class="text-muted">No insights available</div>';
                            }
                            
                            container.innerHTML = html;
                        } else {
                            container.innerHTML = '<div class="error-message"><i class="fas fa-exclamation-triangle me-2"></i>Unable to load insights</div>';
                        }
                    })
                    .catch(err => {
                        console.error('ML insights fetch failed', err);
                        document.getElementById('ml-insights-container').innerHTML = '<div class="error-message"><i class="fas fa-exclamation-triangle me-2"></i>Error loading insights</div>';
                    });
            }

            // Run every 10 seconds
            setInterval(loadAlerts, 10000);
            loadAlerts(); // Initial load

                        // Load ML insights
            loadMLInsights();
            setInterval(loadMLInsights, 30000); // Refresh every 30 seconds

            // Function to refresh notification count
            function refreshNotificationCount() {
                fetch('get_admin_notification_count.php')
                    .then(response => response.json())
                    .then(data => {
                        const notifCount = document.getElementById('notifCount');
                        if (notifCount) {
                            notifCount.textContent = data.count > 0 ? data.count : '';
                        }
                    })
                    .catch(err => console.error('Error refreshing notification count:', err));
            }

            // Refresh notification count every 30 seconds
            setInterval(refreshNotificationCount, 30000);

            // Function to handle notification click
            function handleNotificationClick(message) {
                // Check if this is a pharmaceutical request notification
                if (message.includes('New pharmaceutical request from')) {
                    window.location.href = 'admin_pharmaceutical_request.php';
                } else {
                    // Default navigation to notifications page
                    window.location.href = 'admin_notifications.php';
                }
            }

            // Make handleNotificationClick available globally
            window.handleNotificationClick = handleNotificationClick;

            // Load health risk monitoring data
            loadHealthRiskMonitoring();

            // Load activity logs when "View Activity Logs" is clicked
            const viewActivityLogsLink = document.getElementById('viewActivityLogs');
            if (viewActivityLogsLink) {
                viewActivityLogsLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation(); // Prevent dropdown from closing
                    loadActivityLogs();
                });
            }

            document.addEventListener('DOMContentLoaded', function() {
  // Select all links with class view-inventory
  document.querySelectorAll('.view-inventory').forEach(function(link) {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      
      // Get the type (livestock, poultry, pharmaceuticals)
      const type = this.dataset.type;

      // Update modal title
      document.getElementById('inventoryModalLabel').textContent = type.charAt(0).toUpperCase() + type.slice(1) + ' Details';

      // Show loading text immediately
      const modalBody = document.getElementById('inventoryModalBody');
      modalBody.innerHTML = '<p class="text-center">Loading...</p>';

      // Since you use Bootstrap's data-bs-toggle and data-bs-target on the links,
      // modal will automatically open, so no need to call show() manually.

      // Fetch data from server
      fetch('get_inventory_details.php?type=' + encodeURIComponent(type))
        .then(response => response.text())
        .then(data => {
          modalBody.innerHTML = data;
        })
        .catch(err => {
          modalBody.innerHTML = '<p class="text-danger text-center">Error loading data.</p>';
          console.error(err);
        });
    });
  });
});

    </script>
</body>
</html>