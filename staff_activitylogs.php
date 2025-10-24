<?php
session_start();
include 'includes/conn.php';

// Restrict access to staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: login.php");
    exit();
}

// Fetch role of current user
$userId = $_SESSION['user_id'];
$roleQuery = "SELECT role FROM users WHERE user_id = $userId";
$roleResult = mysqli_query($conn, $roleQuery);
if ($roleResult && mysqli_num_rows($roleResult) > 0) {
    $user = mysqli_fetch_assoc($roleResult);
    if ($user['role'] !== 'staff') {
        header("Location: login.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}

// ✅ Logging function (or include from file if centralized)
function logActivity($conn, $userId, $action) {
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, timestamp) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $userId, $action);
    $stmt->execute();
    $stmt->close();
}

// ✅ Log viewing the Activity Logs page
logActivity($conn, $userId, 'Viewed Activity Logs');

// ✅ Fetch activity logs for the logged-in staff member only
$logs_query = "SELECT activity_logs.*, users.name FROM activity_logs 
               JOIN users ON activity_logs.user_id = users.user_id 
               WHERE activity_logs.user_id = '$userId'
               ORDER BY timestamp DESC";
$logs_result = $conn->query($logs_query);
if (!$logs_result) {
    die("Query error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Logs - Staff</title>
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
        /* Activity Feed Styling */
        .activity-feed {
            max-width: 100%;
        }
        
        .activity-feed-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .activity-feed-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
        }
        
        .see-all-link {
            color: #6c63ff;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .see-all-link:hover {
            color: #5a52d5;
            text-decoration: underline;
        }
        
        .activity-item {
            display: flex;
            align-items: flex-start;
            padding: 20px 0;
            border-bottom: 1px solid #f5f5f5;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-item:hover {
            background-color: #fafbff;
            border-radius: 12px;
            padding: 20px 15px;
            margin: 0 -15px;
        }
        
        .activity-avatar {
            position: relative;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .avatar-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .activity-icon {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            color: white;
            border: 2px solid white;
        }
        
        .icon-login {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        
        .icon-view {
            background: linear-gradient(135deg, #6c63ff, #5a52d5);
        }
        
        .icon-edit {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
        }
        
        .icon-delete {
            background: linear-gradient(135deg, #dc3545, #e83e8c);
        }
        
        .icon-default {
            background: linear-gradient(135deg, #6c757d, #495057);
        }
        
        .activity-content {
            flex: 1;
            min-width: 0;
        }
        
        .activity-text {
            font-size: 0.95rem;
            line-height: 1.4;
            color: #2c3e50;
            margin: 0 0 5px 0;
        }
        
        .activity-text .admin-name {
            font-weight: 700;
            color: #2c3e50;
        }
        
        .activity-text .action-text {
            font-weight: 500;
            color: #495057;
        }
        
        .activity-timestamp {
            font-size: 0.8rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .activity-indicator {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 8px;
            height: 8px;
            background: #6c63ff;
            border-radius: 50%;
            opacity: 0.8;
        }
        
        .activity-item.read .activity-indicator {
            opacity: 0;
        }
        
        .activity-item.read .activity-text {
            color: #6c757d;
        }
        
        .activity-item.read .activity-text .admin-name {
            color: #6c757d;
        }
        
        /* Enhanced Search Bar */
        .search-container {
            position: relative;
            margin-bottom: 25px;
        }
        
        .search-container .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 12px 20px 12px 45px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #fafbff;
        }
        
        .search-container .form-control:focus {
            border-color: #6c63ff;
            box-shadow: 0 0 0 0.2rem rgba(108, 99, 255, 0.15);
            background: white;
        }
        
        .search-container .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c63ff;
            font-size: 1.1rem;
        }
        
        /* Action Badge Styling */
        .action-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .action-login {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .action-view {
            background: linear-gradient(135deg, #6c63ff, #5a52d5);
            color: white;
        }
        
        .action-edit {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
        }
        
        .action-delete {
            background: linear-gradient(135deg, #dc3545, #e83e8c);
            color: white;
        }
        
        .action-default {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }
        
        /* Timestamp Styling */
        .timestamp {
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            color: #6c757d;
            background: #f8f9fa;
            padding: 4px 8px;
            border-radius: 6px;
            display: inline-block;
        }
        
        /* Admin Name Styling */
        .admin-name {
            font-weight: 600;
            color: #2c3e50;
        }
        
        /* Row Number Styling */
        .row-number {
            background: linear-gradient(135deg, #e9ecef, #f8f9fa);
            color: #6c757d;
            font-weight: 600;
            border-radius: 8px;
            padding: 8px 12px;
            display: inline-block;
            min-width: 35px;
            text-align: center;
        }
        
        /* Enhanced Print Button */
        .print-btn {
            background: linear-gradient(135deg, #6c63ff, #5a52d5);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(108, 99, 255, 0.3);
        }
        
        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 99, 255, 0.4);
            background: linear-gradient(135deg, #5a52d5, #4a42c7);
        }
        
        /* No Results Styling */
        .no-results {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .no-results i {
            font-size: 3rem;
            color: #dee2e6;
            margin-bottom: 15px;
        }
        
        /* Thin scrollbar for Webkit browsers */
        .table-responsive::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .table-responsive::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #6c63ff, #5a52d5);
            border-radius: 6px;
        }
        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 6px;
        }
        /* Thin scrollbar for Firefox */
        .table-responsive {
            scrollbar-width: thin;
            scrollbar-color: #6c63ff #f1f1f1;
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
            <h2>Staff Activity Logs</h2>
        </div>

        <div class="activity-feed" id="activityFeed">
            <div class="activity-feed-header">
                <h3 class="activity-feed-title">Staff Activities</h3>
            </div>
            
            <div id="activityList">
                    <?php if ($logs_result->num_rows > 0): ?>
                        <?php while ($log = $logs_result->fetch_assoc()): ?>
                        <?php
                        // Determine icon class and icon based on action type
                        $iconClass = 'icon-default';
                        $iconSymbol = 'fas fa-circle';
                        $action = strtolower($log['action']);
                        
                        if (strpos($action, 'login') !== false) {
                            $iconClass = 'icon-login';
                            $iconSymbol = 'fas fa-sign-in-alt';
                        } elseif (strpos($action, 'view') !== false) {
                            $iconClass = 'icon-view';
                            $iconSymbol = 'fas fa-eye';
                        } elseif (strpos($action, 'edit') !== false || strpos($action, 'update') !== false) {
                            $iconClass = 'icon-edit';
                            $iconSymbol = 'fas fa-edit';
                        } elseif (strpos($action, 'delete') !== false || strpos($action, 'remove') !== false) {
                            $iconClass = 'icon-delete';
                            $iconSymbol = 'fas fa-trash';
                        } elseif (strpos($action, 'add') !== false || strpos($action, 'create') !== false) {
                            $iconClass = 'icon-login';
                            $iconSymbol = 'fas fa-plus';
                        }
                        
                        // Generate avatar (using initials if no profile picture)
                        $adminName = htmlspecialchars($log['name']);
                        $initials = '';
                        $nameParts = explode(' ', $adminName);
                        foreach ($nameParts as $part) {
                            $initials .= strtoupper(substr($part, 0, 1));
                        }
                        $initials = substr($initials, 0, 2);
                        
                        // Format timestamp
                        $timestamp = date('M d, Y H:i', strtotime($log['timestamp']));
                        $timeAgo = '';
                        $timeDiff = abs(time() - strtotime($log['timestamp'])); // Use absolute value to handle future timestamps
                        
                        if ($timeDiff < 60) {
                            $timeAgo = $timeDiff . 's';
                        } elseif ($timeDiff < 3600) {
                            $timeAgo = floor($timeDiff / 60) . 'm';
                        } elseif ($timeDiff < 86400) {
                            $timeAgo = floor($timeDiff / 3600) . 'h';
                        } elseif ($timeDiff < (7 * 86400)) {
                            $timeAgo = floor($timeDiff / 86400) . 'd';
                        } else {
                            $timeAgo = date('M d, Y', strtotime($log['timestamp']));
                        }
                        ?>
                        <div class="activity-item" data-search="<?php echo strtolower($adminName . ' ' . $log['action'] . ' ' . $timestamp); ?>">
                            <div class="activity-avatar">
                                <div class="avatar-img" style="background: linear-gradient(135deg, #6c63ff, #5a52d5); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 1.1rem;">
                                    <?php echo $initials; ?>
                                </div>
                                <div class="activity-icon <?php echo $iconClass; ?>">
                                    <i class="<?php echo $iconSymbol; ?>"></i>
                                </div>
                            </div>
                            <div class="activity-content">
                                <p class="activity-text">
                                    <span class="admin-name"><?php echo $adminName; ?></span>
                                    <span class="action-text"><?php echo htmlspecialchars($log['action']); ?></span>
                                </p>
                                <p class="activity-timestamp"><?php echo $timeAgo; ?></p>
                            </div>
                            <div class="activity-indicator"></div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-clipboard-list"></i>
                        <div>No activity logs found</div>
                    </div>
                    <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add loading state for better UX
        document.addEventListener('DOMContentLoaded', function() {
            const activityFeed = document.getElementById('activityFeed');
            activityFeed.style.opacity = '0';
            activityFeed.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                activityFeed.style.transition = 'all 0.5s ease';
                activityFeed.style.opacity = '1';
                activityFeed.style.transform = 'translateY(0)';
            }, 100);
            
            // Mark items as read when clicked
            const activityItems = document.querySelectorAll('.activity-item');
            activityItems.forEach(item => {
                item.addEventListener('click', function() {
                    this.classList.add('read');
                });
            });
        });
    </script>
</body>
</html>
