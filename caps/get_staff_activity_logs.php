<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Debug: Log that the script is being called
error_log("Staff activity logs requested by user: " . $_SESSION['user_id']);

$staff_id = $_SESSION['user_id'];

try {
    // Get recent activity logs for the logged-in staff member only
    $query = "SELECT 
                al.log_id,
                al.user_id,
                al.action,
                al.timestamp,
                u.name as user_name,
                u.role
              FROM activity_logs al
              LEFT JOIN users u ON al.user_id = u.user_id
              WHERE al.user_id = '$staff_id' 
                AND al.timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
              ORDER BY al.timestamp DESC
              LIMIT 15";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception("Database query failed: " . mysqli_error($conn));
    }
    
    $activities = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $activities[] = $row;
    }
    
    // Debug: Log the number of activities found
    error_log("Found " . count($activities) . " activity logs");
    
    if (empty($activities)) {
        echo '<div class="activity-item">
                <div class="activity-avatar">
                    <div class="avatar-img" style="background-color: #6c63ff;">📋</div>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-medium">No activity logs found</div>
                    <small class="text-muted">Activity will appear here as actions are performed</small>
                </div>
              </div>';
    } else {
        foreach ($activities as $activity) {
            $user_name = !empty($activity['user_name']) ? $activity['user_name'] : 'System';
            $action = htmlspecialchars($activity['action']);
            $timestamp = $activity['timestamp'];
            
            // Calculate relative time
            $time_ago = getRelativeTime($timestamp);
            
            // Get user initial for avatar
            $initial = strtoupper(substr($user_name, 0, 1));
            
            // Determine status color based on action type
            $status_color = getStatusColor($activity['action']);
            
            echo '<div class="activity-item">
                    <div class="activity-avatar">
                        <div class="avatar-img" style="background-color: #6c63ff;">' . $initial . '</div>
                        <div class="position-absolute top-0 start-100 translate-middle" style="width: 8px; height: 8px; background-color: ' . $status_color . '; border-radius: 50%; border: 1px solid white;"></div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-medium">' . $action . '</div>
                        <div class="text-muted small">' . $time_ago . '</div>
                    </div>
                  </div>';
        }
    }
    
} catch (Exception $e) {
    error_log("Error fetching staff activity logs: " . $e->getMessage());
    echo '<div class="activity-item">
            <div class="activity-avatar">
                <div class="avatar-img" style="background-color: #dc3545;">⚠️</div>
            </div>
            <div class="flex-grow-1">
                <div class="fw-medium text-danger">Error loading activity logs</div>
                <small class="text-muted">Please try again later</small>
            </div>
          </div>';
}

function getRelativeTime($timestamp) {
    $time = time() - strtotime($timestamp);
    
    if ($time < 60) {
        return $time . 's ago';
    } elseif ($time < 3600) {
        return floor($time / 60) . 'm ago';
    } elseif ($time < 86400) {
        return floor($time / 3600) . 'h ago';
    } elseif ($time < 2592000) {
        return floor($time / 86400) . 'd ago';
    } elseif ($time < 31536000) {
        return floor($time / 2592000) . 'mo ago';
    } else {
        return floor($time / 31536000) . 'y ago';
    }
}

function getStatusColor($action) {
    $action_lower = strtolower($action);
    
    if (strpos($action_lower, 'login') !== false || strpos($action_lower, 'logged in') !== false) {
        return '#28a745'; // Green for login
    } elseif (strpos($action_lower, 'logout') !== false || strpos($action_lower, 'logged out') !== false) {
        return '#dc3545'; // Red for logout
    } elseif (strpos($action_lower, 'create') !== false || strpos($action_lower, 'add') !== false || strpos($action_lower, 'insert') !== false) {
        return '#17a2b8'; // Blue for create
    } elseif (strpos($action_lower, 'update') !== false || strpos($action_lower, 'edit') !== false || strpos($action_lower, 'modify') !== false) {
        return '#ffc107'; // Yellow for update
    } elseif (strpos($action_lower, 'delete') !== false || strpos($action_lower, 'remove') !== false) {
        return '#dc3545'; // Red for delete
    } elseif (strpos($action_lower, 'view') !== false || strpos($action_lower, 'access') !== false) {
        return '#6f42c1'; // Purple for view
    } else {
        return '#6c757d'; // Gray for other actions
    }
}

mysqli_close($conn);
?>