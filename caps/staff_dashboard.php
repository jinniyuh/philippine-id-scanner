<?php
    session_start();
    include 'includes/conn.php';
    include 'includes/session_validator.php';
    
    // Validate session and ensure user is staff
    requireActiveSession($conn, 'staff');
    
    $userId = $_SESSION["user_id"];
    $queryUser = "SELECT * FROM users WHERE user_id = '$userId'";
    $resultUser = mysqli_query($conn, $queryUser);
    if ($resultUser && mysqli_num_rows($resultUser) > 0) {
        $user = mysqli_fetch_assoc($resultUser);
        $staffName = isset($user['name']) ? $user['name'] : 'Staff Name';
    } else {
        $staffName = "Staff Name";
    }

    // Add this to your PHP section at the top
    $queryClients = "SELECT COUNT(*) AS total FROM clients";
    $resultClients = mysqli_query($conn, $queryClients);
    $rowClients = mysqli_fetch_assoc($resultClients);
    $totalclients = $rowClients['total'];

    // Livestock Count (for type 'Livestock')
    $queryLivestock = "SELECT SUM(quantity) AS total FROM livestock_poultry WHERE animal_type = 'Livestock'";
    $resultLivestock = mysqli_query($conn, $queryLivestock);
    $rowLivestock = mysqli_fetch_assoc($resultLivestock);
    $livestockCount = $rowLivestock['total'] ?? 0;

    // Poultry Count (sum of quantity for type 'poultry')
    $queryPoultry = "SELECT SUM(quantity) AS total FROM livestock_poultry WHERE animal_type = 'poultry'";
    $resultPoultry = mysqli_query($conn, $queryPoultry);
    $rowPoultry = mysqli_fetch_assoc($resultPoultry);
    $poultryCount = $rowPoultry['total'] ?? 0;

    // Livestock Needing Attention
    $queryLivestockAttention = "SELECT COUNT(*) AS attention FROM livestock_poultry WHERE animal_type = 'Livestock' AND health_status = 'needs attention'";
    $resultLivestockAttention = mysqli_query($conn, $queryLivestockAttention);
    $rowLivestockAttention = mysqli_fetch_assoc($resultLivestockAttention);
    $livestockAttention = $rowLivestockAttention['attention'];

    // Pharmaceuticals Count
    $queryPharm = "SELECT COUNT(*) AS total FROM pharmaceuticals";
    $resultPharm = mysqli_query($conn, $queryPharm);
    $rowPharm = mysqli_fetch_assoc($resultPharm);
    $pharmCount = $rowPharm['total'];

    // Low stock Pharmaceuticals (stock < 10)
    $queryLowStock = "SELECT COUNT(*) AS low_stock FROM pharmaceuticals WHERE stock < 10";
    $resultLowStock = mysqli_query($conn, $queryLowStock);
    $rowLowStock = mysqli_fetch_assoc($resultLowStock);
    $lowStockCount = $rowLowStock['low_stock'];

    // Notification Count (where notif_status = 'Unread' for this staff member)
    $queryNotificationCount = "SELECT COUNT(*) AS notificationCount FROM notifications WHERE user_id = ? AND status = 'Unread'";
    $stmtNotificationCount = $conn->prepare($queryNotificationCount);
    $stmtNotificationCount->bind_param("i", $userId);
    $stmtNotificationCount->execute();
    $resultNotificationCount = $stmtNotificationCount->get_result();
    $rowNotificationCount = $resultNotificationCount->fetch_assoc();
    $notificationCount = $rowNotificationCount['notificationCount'];

    // Transactions Count (where trans_status = 'Pending')
    $queryTransactionsCount = "SELECT COUNT(*) AS transactionCount FROM transactions WHERE status = 'Pending'";
    $resultTransactionsCount = mysqli_query($conn, $queryTransactionsCount);
    $rowTransactionsCount = mysqli_fetch_assoc($resultTransactionsCount);
    $transactionCount = $rowTransactionsCount['transactionCount'];

    // Recent Activities (from the activity_logs table - only for logged-in staff)
    $queryActivities = "SELECT al.*, u.name as user_name, u.role 
                        FROM activity_logs al 
                        LEFT JOIN users u ON al.user_id = u.user_id 
                        WHERE al.user_id = '$userId'
                        ORDER BY al.timestamp DESC 
                        LIMIT 5";
    $resultActivities = mysqli_query($conn, $queryActivities);
    $activities = [];
    while ($row = mysqli_fetch_assoc($resultActivities)) {
        $activities[] = $row;
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Staff Dashboard</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
            
            /* Main content white box styling */
            .white-box {
                background: white;
                margin: 20px;
                margin-left: 312px; /* Add margin to offset fixed sidebar */
                padding: 0 25px 25px 25px;
                border-radius: 10px;
                min-height: 600px;
                height: calc(100vh - 40px);
                overflow-y: auto;
                flex: 1;
                display: flex;
                flex-direction: column;
            }
            /* Minimize and style scrollbar for white-box */
            .white-box::-webkit-scrollbar {
            width: 8px;
            background: transparent;
            }
            .white-box::-webkit-scrollbar-thumb {
            background: #bdbdbd;
            border-radius: 8px;
            }
            .white-box::-webkit-scrollbar-track {
            background: transparent;
            }
            .white-box {
            scrollbar-width: thin;
            scrollbar-color: #bdbdbd transparent;
            }
            .page-header {
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
            .page-title h2 {
            margin: 0;
            font-weight: bold;
            }
            .staff-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            }
            
            /* Notification button styling */
            .notification-btn {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            cursor: pointer;
            }
            
            .notification-btn:hover {
            background-color: #e9ecef;
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            
            .notification-btn i {
            font-size: 18px;
            color: #6c757d;
            }
            
            .notification-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            min-width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 600;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
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
                background: #28a745 !important;
            }

            .badge.bg-info {
                background: #17a2b8 !important;
            }

            .badge.bg-warning {
                background: #ffc107 !important;
                color: #212529 !important;
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

            /* Activity Item Styling */
            .activity-item {
                padding: 12px 0;
                border-bottom: 1px solid #e9ecef;
                transition: all 0.2s ease;
            }

            .activity-item:last-child {
                border-bottom: none;
            }

            .activity-item:hover {
                background: rgba(108, 99, 255, 0.05);
                padding-left: 10px;
                border-radius: 8px;
            }

            .activity-icon {
                margin-right: 12px;
                color: #6c63ff;
                font-size: 8px;
                width: 20px;
            }

            .activity-details {
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
            
            /* Search bar styling */
            .input-group .form-control {
                border-radius: 20px 0 0 20px;
                padding: 10px 15px;
            }
            
            .input-group .btn {
                border-radius: 0 20px 20px 0;
                background-color: #8B9FF7;
                border-color: #8B9FF7;
            }
            
            /* Alert styling */
            .alert {
                border-radius: 10px;
                margin-bottom: 25px;
            }
            
            /* Fix notification badge positioning */
            .position-relative {
                display: inline-block;
            }
            
            .position-absolute {
                top: -10px !important;
                right: -10px !important;
            }
            
            .wrapper {
                display: flex;
                align-items: flex-start;
            }
            
            /* Add hover effect for table rows */
            .table tbody tr:hover {
                background-color: #f8f9fa;
                cursor: pointer;
            }
            .updates-section {
                background: #FFF3CD;
                border-radius: 10px;
                padding: 20px;
            }
            
            .updates-section li {
                margin-bottom: 10px;
            }
            
            .updates-section i {
                margin-right: 10px;
            }
            
            .pharmaceuticals-table {
                background: white;
                border-radius: 10px;
                padding: 20px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .table th, .table td {
                padding: 12px;
                border-bottom: 1px solid #dee2e6;
            }
            
            .badge {
                padding: 5px 10px;
                margin-right: 5px;
            }
            
            .white-box .alert {
                margin-bottom: 25px;
                border-radius: 10px;
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
    <!-- Sidebar Section -->
    <div class="wrapper">
      <div class="sidebar">
        <?php include 'includes/staff_sidebar.php'; ?>
      </div>
      <!-- Main Content -->
      <div class="white-box">
      <!-- Page Header -->
      <div class="page-header">
        <div class="page-title">
<h2>Staff Dashboard</h2></div>
        <div class="staff-info">
            <!-- Notification Icon with Badge -->
            <div class="dropdown">
                <button class="notification-btn" id="notifBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                    <?php if ($notificationCount > 0): ?>
                    <span class="notification-badge" id="notifCount">
                        <?php echo $notificationCount; ?>
                    </span>
                    <?php endif; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="width: 320px; max-height: 400px; overflow-y: auto; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
                    <li class="dropdown-header d-flex align-items-center">
                        <i class="fas fa-bell me-2 text-primary"></i>
                        Notifications
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <?php if ($notificationCount > 0): ?>
                        <?php
                        // Fetch recent notifications for this staff member
                        $recent_notif_query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY timestamp DESC LIMIT 5";
                        $recent_notif_stmt = $conn->prepare($recent_notif_query);
                        $recent_notif_stmt->bind_param("i", $userId);
                        $recent_notif_stmt->execute();
                        $recent_notif_result = $recent_notif_stmt->get_result();
                        
                        while ($notif = $recent_notif_result->fetch_assoc()):
                        ?>
                        <li class="dropdown-item small notification-item" style="cursor: pointer;">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-bell text-primary me-2 mt-1"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-medium"><?php echo htmlspecialchars($notif['message']); ?></div>
                                    <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($notif['timestamp'])); ?></small>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php endwhile; ?>
                        <li class="dropdown-item text-center">
                            <a href="staff_notifications.php" class="text-decoration-none fw-medium">View All Notifications</a>
                        </li>
                    <?php else: ?>
                        <li class="dropdown-item text-center text-muted py-3">
                            <i class="fas fa-check-circle me-2 text-success"></i>No new notifications
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

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
                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
      </div>
                <!-- Main Cards Row -->
                <div class="row">
                    <!-- Inventory Overview -->
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
                                    <span class="badge bg-primary"><?php echo $livestockCount; ?> heads</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <a href="#" class="view-inventory" data-type="poultry" data-bs-toggle="modal" data-bs-target="#inventoryModal">
                                        Poultry
                                    </a>
                                    <span class="badge bg-success"><?php echo $poultryCount; ?> heads</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <a href="#" class="view-inventory" data-type="pharmaceuticals" data-bs-toggle="modal" data-bs-target="#inventoryModal">
                                        Pharmaceuticals
                                    </a>
                                    <span class="badge bg-info"><?php echo $pharmCount; ?> items</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <a href="staff_clients.php" class="view-inventory">
                                        Clients
                                    </a>
                                    <span class="badge bg-warning"><?php echo $totalclients; ?> registered</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activities -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex align-items-center">
                                <i class="fas fa-history me-2"></i> Recent Activities
                            </div>
                            <div class="card-body">
                                <?php if (!empty($activities)) { ?>
                                    <?php foreach ($activities as $activity) { ?>
                                        <div class="activity-item d-flex align-items-start mb-3">
                                            <div class="activity-icon">
                                                <i class="fas fa-circle"></i>
                                            </div>
                                            <div class="activity-details">
                                                <p class="activity-text"><?php echo htmlspecialchars($activity['action']); ?></p>
                                                <div class="activity-time"><?php echo date('M d, Y h:i A', strtotime($activity['timestamp'])); ?></div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                <?php } else { ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <div>No recent activities</div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Important Updates -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex align-items-center">
                                <i class="fas fa-bell me-2"></i> Important Updates
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning mb-3" style="border-radius: 10px;">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Notifications</strong>
                                    </div>
                                    <p class="mb-0">
                                        <?php if ($notificationCount > 0): ?>
                                            <?php echo $notificationCount; ?> new notifications
                                        <?php else: ?>
                                            No new notifications
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="alert alert-info mb-3" style="border-radius: 10px;">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-exchange-alt me-2"></i>
                                        <strong>Transactions</strong>
                                    </div>
                                    <p class="mb-0">
                                        <?php if ($transactionCount > 0): ?>
                                            <?php echo $transactionCount; ?> pending transactions
                                        <?php else: ?>
                                            No pending transactions
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="alert alert-danger mb-0" style="border-radius: 10px;">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-capsules me-2"></i>
                                        <strong>Low Stock</strong>
                                    </div>
                                    <p class="mb-0">
                                        <?php if ($lowStockCount > 0): ?>
                                            <?php echo $lowStockCount; ?> items low in stock
                                        <?php else: ?>
                                            All items sufficiently stocked
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
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

    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
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
        // Function to restore dropdown menu
        function restoreDropdownMenu() {
            const dropdown = document.getElementById('avatarDropdown');
            dropdown.innerHTML = `
                <li><a class="dropdown-item" href="#" id="viewActivityLogs"><i class="fas fa-history me-2"></i>Activity Logs</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            `;
            dropdown.classList.remove('activity-logs-dropdown');
            
            // Re-attach the event listener for "View Activity Logs"
            attachActivityLogsListener();
        }

        // Ensure dropdown functionality works
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
        });

        // Function to attach activity logs listener
        function attachActivityLogsListener() {
            const viewActivityLogsLink = document.getElementById('viewActivityLogs');
            if (viewActivityLogsLink) {
                viewActivityLogsLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    loadActivityLogs();
                });
            }
        }

        // Function to load activity logs
        function loadActivityLogs() {
            fetch('get_activity_logs.php')
                .then(response => response.text())
                .then(data => {
                    const dropdown = document.getElementById('avatarDropdown');
                    // Create the activity logs dropdown content
                    const dropdownContent = `
                        <div class="activity-logs-header">
                            <i class="fas fa-arrow-left me-2" id="backToMenu" style="cursor: pointer;"></i>Activity Logs
                        </div>
                        ${data}
                    `;
                    dropdown.innerHTML = dropdownContent;
                    dropdown.classList.add('activity-logs-dropdown');
                    
                    // Add back button functionality
                    const backButton = document.getElementById('backToMenu');
                    if (backButton) {
                        backButton.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            restoreDropdownMenu();
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading activity logs:', error);
                    document.getElementById('avatarDropdown').innerHTML = 
                        '<div class="activity-logs-header"><i class="fas fa-history me-2"></i>Activity Logs</div><div class="text-center text-muted p-3"><i class="fas fa-exclamation-triangle me-2"></i>Error loading activity logs</div>';
                });
        }

        // Handle inventory modal and activity logs
        document.addEventListener('DOMContentLoaded', function() {
            // Handle inventory modal
            const inventoryLinks = document.querySelectorAll('.view-inventory[data-type]');
            
            inventoryLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    const type = this.getAttribute('data-type');
                    
                    // Update modal title
                    document.getElementById('inventoryModalLabel').textContent = type.charAt(0).toUpperCase() + type.slice(1) + ' Details';
                    
                    // Show loading text immediately
                    const modalBody = document.getElementById('inventoryModalBody');
                    modalBody.innerHTML = '<p class="text-center"><i class="fas fa-spinner fa-spin me-2"></i>Loading...</p>';
                    
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
            
            // Attach activity logs listener on page load
            attachActivityLogsListener();
            
            // Reattach listener when dropdown is shown (in case it was recreated)
            const avatarDropdownElement = document.querySelector('[data-bs-toggle="dropdown"]');
            if (avatarDropdownElement) {
                avatarDropdownElement.addEventListener('shown.bs.dropdown', function() {
                    attachActivityLogsListener();
                });
            }
        });
    </script>
    </body>
    </html>