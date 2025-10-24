<?php
// Ensure clean JSON responses (suppress notices/warnings that break JSON)
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '0');
if (function_exists('ob_get_length') && ob_get_length()) { ob_clean(); }

// Always respond 200 with JSON; capture fatals
http_response_code(200);
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && !headers_sent()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $err['message']]);
    }
});

session_start();
header('Content-Type: application/json');
include 'includes/conn.php';

// Set proper character encoding
mysqli_set_charset($conn, "utf8mb4");

// Include activity logger for tracking photo actions
include 'includes/activity_logger.php';

// Check if user is logged in with proper role (admin or staff)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','staff'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $photo_id = isset($_POST['photo_id']) ? (int)$_POST['photo_id'] : 0;
    $action = $_POST['action'] ?? ''; // 'approve' or 'reject'
    $rejection_reason = $_POST['rejection_reason'] ?? '';
    $admin_id = $_SESSION['user_id'];
    
    // Validate inputs
    if (empty($photo_id) || empty($action)) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit();
    }
    
    if ($action === 'reject' && empty($rejection_reason)) {
        echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
        exit();
    }
    
    // Get photo and client information (avoid get_result dependency)
    $photo_query = "SELECT ap.status, lp.client_id, c.full_name 
                    FROM animal_photos ap 
                    JOIN livestock_poultry lp ON ap.animal_id = lp.animal_id 
                    JOIN clients c ON lp.client_id = c.client_id 
                    WHERE ap.photo_id = ? LIMIT 1";
    $photo_stmt = $conn->prepare($photo_query);
    if (!$photo_stmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare query.']);
        exit();
    }
    $photo_stmt->bind_param("i", $photo_id);
    $photo_stmt->execute();
    $photo_stmt->store_result();
    if ($photo_stmt->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Photo not found']);
        exit();
    }
    $photo_stmt->bind_result($current_status, $client_id, $client_name);
    $photo_stmt->fetch();

    // Prevent double-processing; only allow updates from Pending state
    if ($current_status !== 'Pending') {
        echo json_encode(['success' => false, 'message' => 'This photo has already been reviewed.']);
        exit();
    }

    // Update photo status
    $status = ($action === 'approve') ? 'Approved' : 'Rejected';

    if ($action === 'approve') {
        $update_query = "UPDATE animal_photos 
                         SET status = 'Approved', reviewed_by = ?, reviewed_at = NOW(), rejection_reason = NULL 
                         WHERE photo_id = ? AND status = 'Pending'";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ii", $admin_id, $photo_id);
    } else { // reject
        // Clean and validate rejection reason
        $rejection_reason = trim($rejection_reason);
        if (empty($rejection_reason)) {
            echo json_encode(['success' => false, 'message' => 'Rejection reason cannot be empty']);
            exit();
        }
        
        $update_query = "UPDATE animal_photos 
                         SET status = 'Rejected', reviewed_by = ?, reviewed_at = NOW(), rejection_reason = ? 
                         WHERE photo_id = ? AND status = 'Pending'";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("isi", $admin_id, $rejection_reason, $photo_id);
    }

    if ($update_stmt && $update_stmt->execute() && $update_stmt->affected_rows > 0) {
        // Log activity for photo approval/rejection
        $activity_message = ($action === 'approve') 
            ? "Approved animal photo for client: {$client_name}"
            : "Rejected animal photo for client: {$client_name}" . (!empty($rejection_reason) ? " - Reason: {$rejection_reason}" : "");
        logActivity($conn, $admin_id, $activity_message);
        
        // Create a notification for the client so they see approve/reject updates
        $notification_message = ($action === 'approve')
            ? 'Your animal photo has been approved by the administrator.'
            : 'Your animal photo has been rejected.' . (!empty($rejection_reason) ? ' Reason: ' . $rejection_reason : '');

        // Try to notify by users.user_id (client role)
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
            if ($notification_stmt) {
                $notification_stmt->bind_param("is", $notify_user_id, $notification_message);
                $notification_stmt->execute();
            }
        } else {
            // Fallback: target by client_id
            $notification_stmt = $conn->prepare("INSERT INTO notifications (client_id, message, timestamp, status) VALUES (?, ?, NOW(), 'Unread')");
            if ($notification_stmt) {
                $notification_stmt->bind_param("is", $client_id, $notification_message);
                $notification_stmt->execute();
            }
        }

        // If approved, mark the client as Complied
        if ($action === 'approve') {
            $status_update = $conn->prepare("UPDATE clients SET status = 'Complied' WHERE client_id = ?");
            if ($status_update) {
                $status_update->bind_param("i", $client_id);
                $status_update->execute();
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Photo ' . $status . ' successfully',
            'status' => $status
        ]);
    } else {
        $dbError = isset($conn) ? $conn->error : 'Unknown error';
        echo json_encode(['success' => false, 'message' => 'Failed to update photo status', 'error' => $dbError]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>