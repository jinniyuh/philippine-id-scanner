<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "Admin access required.";
    exit();
}

echo "<h2>Notification Cleanup</h2>";

// Show current notification counts
$total_query = "SELECT COUNT(*) as total FROM notifications";
$total_result = $conn->query($total_query);
$total_count = $total_result->fetch_assoc()['total'];
echo "<p><strong>Total notifications in system:</strong> $total_count</p>";

// Show notifications by user
$user_counts_query = "SELECT user_id, COUNT(*) as count FROM notifications GROUP BY user_id ORDER BY count DESC";
$user_counts_result = $conn->query($user_counts_query);

echo "<h3>Notifications by User:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>User ID</th><th>Count</th></tr>";
while ($row = $user_counts_result->fetch_assoc()) {
    echo "<tr><td>" . $row['user_id'] . "</td><td>" . $row['count'] . "</td></tr>";
}
echo "</table>";

// Add cleanup options
echo "<br><h3>Cleanup Options:</h3>";

if (isset($_POST['cleanup_duplicates'])) {
    // Remove duplicate notifications (same message, same user, within 1 minute)
    $cleanup_query = "DELETE n1 FROM notifications n1
                      INNER JOIN notifications n2 
                      WHERE n1.notification_id > n2.notification_id 
                      AND n1.user_id = n2.user_id 
                      AND n1.message = n2.message 
                      AND ABS(TIMESTAMPDIFF(SECOND, n1.timestamp, n2.timestamp)) < 60";
    
    $result = $conn->query($cleanup_query);
    $affected = $conn->affected_rows;
    echo "<p style='color: green;'>Removed $affected duplicate notifications.</p>";
    echo "<script>setTimeout(() => location.reload(), 1000);</script>";
}

if (isset($_POST['cleanup_old'])) {
    // Remove notifications older than 30 days
    $cleanup_query = "DELETE FROM notifications WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $result = $conn->query($cleanup_query);
    $affected = $conn->affected_rows;
    echo "<p style='color: green;'>Removed $affected old notifications (older than 30 days).</p>";
    echo "<script>setTimeout(() => location.reload(), 1000);</script>";
}

if (isset($_POST['mark_all_read'])) {
    // Mark all notifications as read
    $cleanup_query = "UPDATE notifications SET status = 'Read' WHERE status = 'Unread'";
    $result = $conn->query($cleanup_query);
    $affected = $conn->affected_rows;
    echo "<p style='color: green;'>Marked $affected notifications as read.</p>";
    echo "<script>setTimeout(() => location.reload(), 1000);</script>";
}

echo "<form method='post' style='margin: 10px 0;'>";
echo "<button type='submit' name='cleanup_duplicates' style='background: #ffc107; color: black; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;'>Remove Duplicates</button>";
echo "</form>";

echo "<form method='post' style='margin: 10px 0;'>";
echo "<button type='submit' name='cleanup_old' style='background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;'>Remove Old (30+ days)</button>";
echo "</form>";

echo "<form method='post' style='margin: 10px 0;'>";
echo "<button type='submit' name='mark_all_read' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Mark All as Read</button>";
echo "</form>";
?>
