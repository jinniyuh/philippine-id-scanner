<?php
ob_start(); // Start output buffering
session_start();
include 'includes/conn.php';
include 'includes/session_validator.php';
include 'includes/activity_logger.php';

// Validate session - staff only
requireActiveSession($conn, 'staff');



if (isset($_POST['approve'])) {
    $request_id = intval($_POST['request_id']);
    $admin_id = $_SESSION['user_id'];
    $pharma_ids = isset($_POST['pharma_id']) ? (array)$_POST['pharma_id'] : [];
    $quantities = isset($_POST['quantity']) ? (array)$_POST['quantity'] : [];
    $units = isset($_POST['units']) ? (array)$_POST['units'] : [];

    if (count($pharma_ids) === 0 || count($pharma_ids) !== count($quantities) || count($pharma_ids) !== count($units)) {
        $_SESSION['error_message'] = 'Please add at least one pharmaceutical item with quantity and units.';
        header('Location: staff_pharmaceutical_request.php');
        exit();
    }

    // Fetch request and client info
    $stmt = $conn->prepare("SELECT pr.*, c.full_name, c.client_id, c.barangay FROM pharmaceutical_requests pr JOIN clients c ON pr.client_id = c.client_id WHERE pr.request_id = ? LIMIT 1");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();
    if (!$request) {
        $_SESSION['error_message'] = 'Request not found.';
        header('Location: staff_pharmaceutical_request.php');
        exit();
    }

    $client_id = intval($request['client_id']);
    $client_barangay = $request['barangay'] ?? '';

    // Begin atomic approval
    $conn->begin_transaction();
    try {
            for ($i = 0; $i < count($pharma_ids); $i++) {
        $pid = intval($pharma_ids[$i]);
        $qty = intval($quantities[$i]);
        $unit = trim($units[$i]);
        if ($pid <= 0 || $qty <= 0 || empty($unit)) {
            throw new Exception('Invalid item input.');
        }
        // Check and deduct stock
        $upd = $conn->prepare("UPDATE pharmaceuticals SET stock = stock - ? WHERE pharma_id = ? AND stock >= ?");
        if (!$upd) throw new Exception('Prepare failed: ' . $conn->error);
        $upd->bind_param("iii", $qty, $pid, $qty);
        $upd->execute();
        if ($upd->affected_rows === 0) {
            // Fetch name for message
            $nm = $conn->prepare("SELECT name, stock FROM pharmaceuticals WHERE pharma_id = ?");
            $nm->bind_param("i", $pid);
            $nm->execute();
            $info = $nm->get_result()->fetch_assoc();
            throw new Exception('Insufficient stock for ' . ($info['name'] ?? ('ID '.$pid)) . '. Available: ' . ($info['stock'] ?? 0));
        }
        // Insert transaction per item with units
        $ins = $conn->prepare("INSERT INTO transactions (client_id, user_id, pharma_id, quantity, units, barangay, status, request_date, issued_date, type) VALUES (?, ?, ?, ?, ?, ?, 'Approved', CURDATE(), NOW(), NULL)");
        if (!$ins) throw new Exception('Prepare failed: ' . $conn->error);
        $ins->bind_param("iiissi", $client_id, $admin_id, $pid, $qty, $unit, $client_barangay);
        $ins->execute();
    }

        // Mark request approved
        $conn->prepare("UPDATE pharmaceutical_requests SET status = 'Approved' WHERE request_id = ?")
             ->bind_param("i", $request_id);
        $updReq = $conn->prepare("UPDATE pharmaceutical_requests SET status = 'Approved' WHERE request_id = ?");
        if (!$updReq) throw new Exception('Prepare failed: ' . $conn->error);
        $updReq->bind_param("i", $request_id);
        $updReq->execute();

        // Notify client
        $admin_name = $_SESSION['name'] ?? 'Admin';
        $notification_message = "Your pharmaceutical request has been approved by " . $admin_name . ".";
        $notify_user_id = 0;
        $user_query = $conn->prepare("SELECT u.user_id FROM users u JOIN clients c ON u.name = c.full_name WHERE c.client_id = ? AND u.role = 'client' LIMIT 1");
        if ($user_query) {
            $user_query->bind_param("i", $client_id);
            $user_query->execute();
            $user_query->store_result();
            $user_query->bind_result($notify_user_id);
            $user_query->fetch();
        }
        if ($notify_user_id) {
            $notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, timestamp, status) VALUES (?, ?, NOW(), 'Unread')");
            if ($notification_stmt) { $notification_stmt->bind_param("is", $notify_user_id, $notification_message); $notification_stmt->execute(); }
        } else {
            $notification_stmt = $conn->prepare("INSERT INTO notifications (client_id, message, timestamp, status) VALUES (?, ?, NOW(), 'Unread')");
            if ($notification_stmt) { $notification_stmt->bind_param("is", $client_id, $notification_message); $notification_stmt->execute(); }
        }

        $conn->commit();
        
        // Log activity for staff approval
        $client_name = $request['full_name'] ?? 'Unknown Client';
        $activity_message = "Approved pharmaceutical request for " . $client_name;
        logActivity($conn, $admin_id, $activity_message);
        
        $_SESSION['success_message'] = 'Request approved and stock updated with units.';
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = 'Approval failed: ' . $e->getMessage();
    }

    header('Location: staff_pharmaceutical_request.php');
    exit(); 
}
    

// Get statistics for the cards
$total_requests_result = $conn->query("SELECT COUNT(*) as count FROM pharmaceutical_requests WHERE status = 'Pending'");
$total_requests = $total_requests_result->fetch_assoc()['count'];

$total_clients_result = $conn->query("SELECT COUNT(DISTINCT c.client_id) as count FROM clients c JOIN pharmaceutical_requests pr ON c.client_id = pr.client_id WHERE pr.status = 'Pending'");
$total_clients = $total_clients_result->fetch_assoc()['count'];


// Get detailed lists for modals
$all_requests_list = $conn->query("SELECT pr.*, c.full_name, c.contact_number, c.barangay 
                                   FROM pharmaceutical_requests pr 
                                   JOIN clients c ON pr.client_id = c.client_id 
                                   WHERE pr.status = 'Pending'
                                   ORDER BY pr.request_date DESC");

$total_clients_list = $conn->query("SELECT DISTINCT c.client_id, c.full_name, c.contact_number, c.barangay FROM clients c JOIN pharmaceutical_requests pr ON c.client_id = pr.client_id WHERE pr.status = 'Pending' ORDER BY c.full_name ASC");


$query = "SELECT pr.*, c.full_name
          FROM pharmaceutical_requests pr
          JOIN clients c ON pr.client_id = c.client_id
          WHERE pr.status = 'Pending'
          ORDER BY pr.request_date DESC";

$requests = $conn->query($query);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff - Pharma Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
        .main-wrapper {
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
            z-index: 1040;
        }
        .admin-header h2 {
            margin: 0;
            font-weight: bold;
        }
        .admin-profile {
            display: flex;
            align-items: center;
            position: relative;
            z-index: 1050;
        }
        
        .admin-profile .dropdown {
            position: relative;
            z-index: 1050;
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
        .requests-table {
            width: 100%;
            border-collapse: collapse;
        }
        .requests-table th {
            background-color: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            position: sticky;
            top: 0;
            z-index: 5;
        }
        .requests-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
        }
        .requests-table tr:hover {
            background-color: #f8f9fa;
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
        
        /* Form layout improvements */
        .item-row .form-label {
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .item-row .form-select,
        .item-row .form-control {
            font-size: 0.875rem;
        }
        
        /* Responsive adjustments for smaller screens */
        @media (max-width: 768px) {
            .item-row .col-5,
            .item-row .col-3,
            .item-row .col-4 {
                margin-bottom: 1rem;
            }
            
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
        
        /* Modal header styling */
        .modal-header {
            background-color: #6c63ff;
            color: white;
            border-bottom: none;
        }
        
        .modal-header .btn-close {
            filter: invert(1);
        }
        
        /* Approve button styling */
        .btn-success {
            background: #4e73df;
            border: none;
            border-radius: 5px;
            padding: 5px 12px;
            font-weight: 500;
            font-size: 0.75rem;
            transition: all 0.3s ease;
        }
        .btn-success:hover {
            background: #375a7f;
        }
        
        /* Empty state styling */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        .empty-state h4 {
            margin-bottom: 10px;
            color: #495057;
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
        <div class="wrapper">
        <div class="sidebar">
            <?php include __DIR__ . '/includes/staff_sidebar.php'; ?>
        </div>

        <div class="main-wrapper">
            <div class="admin-header">
                    <h2>Pharmaceuticals Requests Management</h2>
                    <div class="admin-profile">
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
                        <div class="metric-card" role="button" data-bs-toggle="modal" data-bs-target="#allRequestsModal">
                            <div class="metric-title">Total Requests</div>
                            <div class="metric-value"><?php echo number_format($total_requests); ?></div>
                            <div class="metric-detail">Click to view details</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="metric-card" role="button" data-bs-toggle="modal" data-bs-target="#totalClientsModal">
                            <div class="metric-title">Clients with Pending Requests</div>
                            <div class="metric-value"><?php echo number_format($total_clients); ?></div>
                            <div class="metric-detail">Click to view details</div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="row mt-1 search-section">
                    <div class="col-md-6">
                        <div class="search-container">
                            <input type="text" id="searchRequest" placeholder="Search by client name, species, or symptoms...">
                            <button type="button"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="d-flex justify-content-end gap-2">
                            <select class="filter-dropdown" id="filterSpecies">
                                <option value="">Filter by Species</option>
                                <?php
                                $species = $conn->query("SELECT DISTINCT species FROM pharmaceutical_requests WHERE species IS NOT NULL AND species != '' ORDER BY species ASC");
                                if ($species && $species->num_rows > 0) {
                                    while($s = $species->fetch_assoc()) {
                                        echo '<option value="' . htmlspecialchars($s['species']) . '">' . htmlspecialchars($s['species']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                            <select class="filter-dropdown" id="filterMonth">
                                <option value="">Filter by Month</option>
                                <option value="01">January</option>
                                <option value="02">February</option>
                                <option value="03">March</option>
                                <option value="04">April</option>
                                <option value="05">May</option>
                                <option value="06">June</option>
                                <option value="07">July</option>
                                <option value="08">August</option>
                                <option value="09">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                        </div>
                    </div>
                </div>

        <div class="table-responsive mt-2">
            <table class="requests-table" id="requestsTable">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Species</th>
                            <th>Symptoms</th>
                            <th>Est. Weight</th>
                            <th>Request Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($requests && $requests->num_rows > 0): ?>
                        <?php while ($row = $requests->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['full_name']); ?></td>
                                <td><?= htmlspecialchars($row['species'] ?? ''); ?></td>
                                <td style="max-width: 240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($row['symptoms'] ?? ''); ?>"><?= htmlspecialchars($row['symptoms'] ?? ''); ?></td>
                                <td><?= $row['weight'] !== null ? $row['weight'] : 'N/A'; ?></td>
                                <td><?= date('Y-m-d', strtotime($row['request_date'])); ?></td>
                                <td>
                                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#approveModal" data-request='<?= json_encode(["request_id"=>$row['request_id'],"client"=>$row['full_name']]); ?>'>Approve</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                                    <?php else: ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <i class="fas fa-check-circle"></i>
                                    <h4>No Pending Requests</h4>
                                    <p>All pharmaceutical requests have been processed. Great job!</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Approve Modal: add multiple prescription items -->
            <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Approve Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <form method="POST">
                    <div class="modal-body">
                      <input type="hidden" name="request_id" id="approve_request_id">
                      <div class="mb-2 text-muted" id="approve_client_label"></div>
                      <div id="itemsContainer">
                        <div class="row g-2 align-items-end item-row">
                          <div class="col-5">
                            <label class="form-label">Pharmaceutical</label>
                            <select name="pharma_id[]" class="form-select" required>
                              <option value="">Select...</option>
                              <?php $today=date('Y-m-d'); $opts=$conn->query("SELECT pharma_id,name,stock FROM pharmaceuticals WHERE stock>0 AND (expiry_date IS NULL OR expiry_date>='$today') ORDER BY name"); while($p=$opts->fetch_assoc()): ?>
                                <option value="<?= $p['pharma_id']; ?>"><?= htmlspecialchars($p['name']); ?> (Stock: <?= (int)$p['stock']; ?>)</option>
                              <?php endwhile; ?>
                            </select>
                          </div>
                          <div class="col-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity[]" class="form-control" min="1" required>
                          </div>
                          <div class="col-4">
                            <label class="form-label">Units</label>
                            <select name="units[]" class="form-select" required>
                              <option value="">Select...</option>
                              <option value="tablets">Tablets</option>
                              <option value="capsules">Capsules</option>
                              <option value="ml">Milliliters (ml)</option>
                              <option value="cc">Cubic Centimeters (cc)</option>
                              <option value="mg">Milligrams (mg)</option>
                              <option value="grams">Grams</option>
                              <option value="pieces">Pieces</option>
                              <option value="vials">Vials</option>
                              <option value="ampoules">Ampoules</option>
                              <option value="sachets">Sachets</option>
                              <option value="bottles">Bottles</option>
                              <option value="tubes">Tubes</option>
                              <option value="packs">Packs</option>
                              <option value="boxes">Boxes</option>
                            </select>
                          </div>
                        </div>
                      </div>
                      <div class="mt-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="addItemRow">Add another</button>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" name="approve" class="btn btn-success">Approve & Deduct Stock</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
        </div>
    </div>

    <!-- All Requests Modal -->
    <div class="modal fade" id="allRequestsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">All Pharmaceutical Requests</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Species</th>
                                    <th>Symptoms</th>
                                    <th>Status</th>
                                    <th>Request Date</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if ($all_requests_list && $all_requests_list->num_rows > 0): ?>
                                <?php while($row = $all_requests_list->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['species'] ?? ''); ?></td>
                                    <td style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($row['symptoms'] ?? ''); ?>"><?php echo htmlspecialchars($row['symptoms'] ?? ''); ?></td>
                                    <td>
                                        <span class="badge <?php echo $row['status'] === 'Pending' ? 'bg-warning' : 'bg-success'; ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($row['request_date'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center">No requests found</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Clients Modal -->
    <div class="modal fade" id="totalClientsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Clients with Pending Requests</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
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
                            <?php if ($total_clients_list && $total_clients_list->num_rows > 0): ?>
                                <?php while($row = $total_clients_list->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['barangay']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center">No clients found</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Success/Error Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow">
                <div class="modal-body text-center p-4">
                    <div class="mb-3">
                        <i id="modalIcon" style="font-size: 2rem;"></i>
                    </div>
                    <h6 id="modalTitle" class="mb-3"></h6>
                    <p id="modalMessage" class="text-muted mb-4"></p>
                    <button type="button" class="btn px-4" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Wire approve modal with request info and dynamic rows
        document.addEventListener('DOMContentLoaded', function() {
            const approveModalEl = document.getElementById('approveModal');
            if (approveModalEl) {
                approveModalEl.addEventListener('show.bs.modal', function (ev) {
                    const btn = ev.relatedTarget;
                    try {
                        const data = JSON.parse(btn.getAttribute('data-request'));
                        document.getElementById('approve_request_id').value = data.request_id;
                        document.getElementById('approve_client_label').textContent = 'Client: ' + (data.client || '');
                        // Reset to a single empty row
                        const container = document.getElementById('itemsContainer');
                        const rows = container.querySelectorAll('.item-row');
                        rows.forEach((r, idx) => { if (idx > 0) r.remove(); });
                        const first = container.querySelector('.item-row');
                        if (first) {
                            const sel = first.querySelector('select[name="pharma_id[]"]'); if (sel) sel.selectedIndex = 0;
                            const qty = first.querySelector('input[name="quantity[]"]'); if (qty) qty.value = '';
                            const units = first.querySelector('select[name="units[]"]'); if (units) units.selectedIndex = 0;
                        }
                    } catch (e) {
                        document.getElementById('approve_request_id').value = '';
                        document.getElementById('approve_client_label').textContent = '';
                    }
                });
            }

            const addBtn = document.getElementById('addItemRow');
            if (addBtn) {
                addBtn.addEventListener('click', function() {
                    const container = document.getElementById('itemsContainer');
                    if (!container) return;
                                            const first = container.querySelector('.item-row');
                        if (!first) return;
                        const clone = first.cloneNode(true);
                        const sel = clone.querySelector('select[name="pharma_id[]"]'); if (sel) sel.selectedIndex = 0;
                        const qty = clone.querySelector('input[name="quantity[]"]'); if (qty) qty.value = '';
                        const units = clone.querySelector('select[name="units[]"]'); if (units) units.selectedIndex = 0;
                        container.appendChild(clone);
                });
            }
        });
        // Search functionality
        document.getElementById('searchRequest').addEventListener('keyup', function() {
            applyFilters();
        });

        // Filter by species
        document.getElementById('filterSpecies').addEventListener('change', function() {
            applyFilters();
        });

        // Filter by month
        document.getElementById('filterMonth').addEventListener('change', function() {
            applyFilters();
        });

        function applyFilters() {
            const searchValue = document.getElementById('searchRequest').value.toLowerCase();
            const selectedSpecies = document.getElementById('filterSpecies').value;
            const selectedMonth = document.getElementById('filterMonth').value;
            const tableRows = document.querySelectorAll('.requests-table tbody tr');
            
            tableRows.forEach(row => {
                // Skip empty state row
                if (row.cells.length === 1) return;
                
                const clientName = row.cells[0].textContent.toLowerCase();
                const species = row.cells[1].textContent.toLowerCase();
                const symptoms = row.cells[2].textContent.toLowerCase();
                const dateCell = row.cells[4].textContent.trim();
                
                let searchMatch = searchValue === '' || 
                    clientName.includes(searchValue) || 
                    species.includes(searchValue) || 
                    symptoms.includes(searchValue);
                
                let speciesMatch = selectedSpecies === '' || species === selectedSpecies.toLowerCase();
                let monthMatch = selectedMonth === '' || dateCell.includes(`-${selectedMonth}-`);
                
                if (searchMatch && speciesMatch && monthMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Check for session messages and show modal
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

        // Initialize dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap dropdown
            const avatarDropdown = new bootstrap.Dropdown(document.getElementById('avatarDropdownToggle'));
            
            // Add multiple click event listeners for better reliability
            const avatarButton = document.getElementById('avatarDropdownToggle');
            const avatarContainer = document.querySelector('.avatar-container');
            const avatarImage = document.querySelector('.avatar-img');
            const dropdownMenu = document.getElementById('avatarDropdown');
            
            if (avatarButton) {
                avatarButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    avatarDropdown.toggle();
                });
            }
            
            if (avatarContainer) {
                avatarContainer.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    avatarDropdown.toggle();
                });
            }
            
            if (avatarImage) {
                avatarImage.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    avatarDropdown.toggle();
                });
            }
            
            if (dropdownMenu) {
                dropdownMenu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        });

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
