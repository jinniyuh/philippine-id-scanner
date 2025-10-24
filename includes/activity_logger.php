<?php
// Enhanced activity logger with report generation activities

function logActivity($conn, $userId, $activity, $details = null) {
    // Combine activity and details into the action field
    $action = $activity;
    if ($details) {
        $action .= " - " . $details;
    }
    
    // Prepare the SQL statement to match actual table schema
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("is", $userId, $action);
        $stmt->execute();
        $stmt->close();
    } else {
        // Fallback to basic insert if prepared statement fails
        $action = mysqli_real_escape_string($conn, $action);
        $query = "INSERT INTO activity_logs (user_id, action) VALUES ('$userId', '$action')";
        mysqli_query($conn, $query);
    }
}

// Specific functions for different types of activities
function logReportGeneration($conn, $userId, $reportType, $format = 'view') {
    $activity = "Generated $format report: $reportType";
    $details = json_encode([
        'report_type' => $reportType,
        'format' => $format,
        'timestamp' => date('Y-m-d H:i:s'),
        'page' => $_SERVER['REQUEST_URI'] ?? 'Unknown'
    ]);
    
    logActivity($conn, $userId, $activity, $details);
}

function logDataExport($conn, $userId, $exportType, $format, $recordCount = null) {
    $activity = "Exported data: $exportType ($format format)";
    $details = json_encode([
        'export_type' => $exportType,
        'format' => $format,
        'record_count' => $recordCount,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    logActivity($conn, $userId, $activity, $details);
}

function logSystemAccess($conn, $userId, $module, $action = 'accessed') {
    $activity = ucfirst($action) . " $module";
    $details = json_encode([
        'module' => $module,
        'action' => $action,
        'timestamp' => date('Y-m-d H:i:s'),
        'session_id' => session_id()
    ]);
    
    logActivity($conn, $userId, $activity, $details);
}

function logDatabaseOperation($conn, $userId, $operation, $table, $recordId = null) {
    $activity = ucfirst($operation) . " record in $table";
    $details = json_encode([
        'operation' => $operation,
        'table' => $table,
        'record_id' => $recordId,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    logActivity($conn, $userId, $activity, $details);
}

// Function to get recent activities for dashboard
function getRecentActivities($conn, $limit = 10, $userId = null) {
    $whereClause = $userId ? "WHERE al.user_id = $userId" : "";
    
    $query = "SELECT al.*, u.name as user_name, u.role 
              FROM activity_logs al 
              LEFT JOIN users u ON al.user_id = u.user_id 
              $whereClause 
              ORDER BY al.timestamp DESC 
              LIMIT $limit";
    
    $result = mysqli_query($conn, $query);
    $activities = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $activities[] = $row;
        }
    }
    
    return $activities;
}

// Function to get activity statistics
function getActivityStats($conn, $days = 30) {
    $startDate = date('Y-m-d', strtotime("-$days days"));
    
    $stats = [];
    
    // Total activities
    $query = "SELECT COUNT(*) as total FROM activity_logs WHERE DATE(timestamp) >= '$startDate'";
    $result = mysqli_query($conn, $query);
    $stats['total'] = mysqli_fetch_assoc($result)['total'];
    
    // Activities by type
    $query = "SELECT 
                SUM(CASE WHEN action LIKE '%report%' THEN 1 ELSE 0 END) as reports,
                SUM(CASE WHEN action LIKE '%export%' THEN 1 ELSE 0 END) as exports,
                SUM(CASE WHEN action LIKE '%login%' THEN 1 ELSE 0 END) as logins,
                SUM(CASE WHEN action LIKE '%delete%' THEN 1 ELSE 0 END) as deletions,
                SUM(CASE WHEN action LIKE '%update%' THEN 1 ELSE 0 END) as updates,
                SUM(CASE WHEN action LIKE '%add%' THEN 1 ELSE 0 END) as additions
              FROM activity_logs 
              WHERE DATE(timestamp) >= '$startDate'";
    
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $stats['reports'] = $row['reports'];
    $stats['exports'] = $row['exports'];
    $stats['logins'] = $row['logins'];
    $stats['deletions'] = $row['deletions'];
    $stats['updates'] = $row['updates'];
    $stats['additions'] = $row['additions'];
    
    // Activities by user
    $query = "SELECT u.name, COUNT(*) as activity_count 
              FROM activity_logs al 
              LEFT JOIN users u ON al.user_id = u.user_id 
              WHERE DATE(al.timestamp) >= '$startDate' 
              GROUP BY al.user_id, u.name 
              ORDER BY activity_count DESC 
              LIMIT 10";
    
    $result = mysqli_query($conn, $query);
    $stats['top_users'] = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $stats['top_users'][] = $row;
    }
    
    return $stats;
}
?>