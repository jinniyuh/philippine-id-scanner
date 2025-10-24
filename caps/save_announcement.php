<?php
session_start();
include 'includes/conn.php';

header('Content-Type: application/json');

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Validate required fields
if (!isset($_POST['title']) || !isset($_POST['location']) || !isset($_POST['event_date']) || !isset($_POST['reminders'])) {
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit();
}

$title = trim($_POST['title']);
$location = trim($_POST['location']);
$event_date = trim($_POST['event_date']);
$reminders = trim($_POST['reminders']);

// Validate that fields are not empty
if (empty($title) || empty($location) || empty($event_date) || empty($reminders)) {
    echo json_encode(['success' => false, 'error' => 'All fields must be filled']);
    exit();
}

try {
    // Check if announcement exists
    $check_stmt = $conn->prepare("SELECT id FROM announcement LIMIT 1");
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing announcement
        $row = $result->fetch_assoc();
        $announcement_id = $row['id'];
        
        $stmt = $conn->prepare("UPDATE announcement SET title = ?, location = ?, event_date = ?, reminders = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("ssssi", $title, $location, $event_date, $reminders, $announcement_id);
    } else {
        // Insert new announcement
        $stmt = $conn->prepare("INSERT INTO announcement (title, location, event_date, reminders, updated_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
        $stmt->bind_param("ssss", $title, $location, $event_date, $reminders);
    }
    
    if ($stmt->execute()) {
        // Log activity
        if (isset($_SESSION['user_id'])) {
            include_once 'includes/activity_logger.php';
            logActivity($conn, $_SESSION['user_id'], 'Updated announcement: ' . $title);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Announcement saved successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to save announcement: ' . $stmt->error
        ]);
    }
    
    $stmt->close();
    $check_stmt->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>

