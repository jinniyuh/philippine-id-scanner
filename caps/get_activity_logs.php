<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Access denied');
}

// Fetch activity logs
$logs_query = "SELECT activity_logs.*, users.name FROM activity_logs 
               JOIN users ON activity_logs.user_id = users.user_id 
               ORDER BY timestamp DESC LIMIT 20";
$logs_result = $conn->query($logs_query);

if (!$logs_result) {
    echo '<div class="text-center text-muted"><i class="fas fa-exclamation-triangle me-2"></i>Error loading activity logs</div>';
    exit();
}

if ($logs_result->num_rows > 0): ?>
    <?php while ($log = $logs_result->fetch_assoc()): ?>
        <?php
        // Get admin name and initials
        $adminName = htmlspecialchars($log['name']);
        $initials = strtoupper(substr($adminName, 0, 1));
        
        // Determine icon class and icon based on action type
        $iconClass = 'icon-default';
        $iconSymbol = 'fas fa-circle';
        $action = strtolower($log['action']);
        
        if (strpos($action, 'login') !== false) {
            $iconClass = 'icon-login';
            $iconSymbol = 'fas fa-sign-in-alt';
        } elseif (strpos($action, 'logout') !== false) {
            $iconClass = 'icon-logout';
            $iconSymbol = 'fas fa-sign-out-alt';
        } elseif (strpos($action, 'add') !== false || strpos($action, 'create') !== false) {
            $iconClass = 'icon-add';
            $iconSymbol = 'fas fa-plus';
        } elseif (strpos($action, 'edit') !== false || strpos($action, 'update') !== false) {
            $iconClass = 'icon-edit';
            $iconSymbol = 'fas fa-edit';
        } elseif (strpos($action, 'delete') !== false || strpos($action, 'remove') !== false) {
            $iconClass = 'icon-delete';
            $iconSymbol = 'fas fa-trash';
        } elseif (strpos($action, 'view') !== false) {
            $iconClass = 'icon-view';
            $iconSymbol = 'fas fa-eye';
        } elseif (strpos($action, 'approved') !== false || strpos($action, 'rejected') !== false) {
            $iconClass = 'icon-approve';
            $iconSymbol = 'fas fa-check-circle';
        }
        
        // Calculate time ago
        $timeDiff = abs(time() - strtotime($log['timestamp']));
        if ($timeDiff < 60) {
            $timeAgo = $timeDiff . 's';
        } elseif ($timeDiff < 3600) {
            $timeAgo = floor($timeDiff / 60) . 'm';
        } elseif ($timeDiff < 86400) {
            $timeAgo = floor($timeDiff / 3600) . 'h';
        } elseif ($timeDiff < 604800) {
            $timeAgo = floor($timeDiff / 86400) . 'd';
        } else {
            $timeAgo = date('M d, Y', strtotime($log['timestamp']));
        }
        ?>
        <div class="activity-item">
            <div class="activity-avatar">
                <div class="avatar-img" style="background: linear-gradient(135deg, #6c63ff, #5a52d5);">
                    <?php echo $initials; ?>
                </div>
                <div class="activity-icon <?php echo $iconClass; ?>">
                    <i class="<?php echo $iconSymbol; ?>"></i>
                </div>
            </div>
            <div class="activity-content">
                <p class="activity-text">
                    <strong><?php echo $adminName; ?></strong> <?php echo htmlspecialchars($log['action']); ?>
                </p>
                <div class="activity-time"><?php echo $timeAgo; ?></div>
            </div>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <div class="text-center text-muted py-4">
        <i class="fas fa-history fa-2x mb-2"></i>
        <div>No activity logs found</div>
    </div>
<?php endif; ?>
