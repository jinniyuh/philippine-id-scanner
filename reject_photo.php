<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

$photo_id = $_POST['photo_id'] ?? '';
$remarks = $_POST['remarks'] ?? '';

if (empty($photo_id)) {
    echo json_encode(['success' => false, 'error' => 'Photo ID is required']);
    exit();
}

if (empty($remarks)) {
    echo json_encode(['success' => false, 'error' => 'Rejection reason is required']);
    exit();
}

try {
    // Update photo status to rejected
    $stmt = $conn->prepare("UPDATE animal_photos SET status = 'Rejected', remarks = ?, rejected_by = ?, rejected_at = NOW() WHERE photo_id = ?");
    $stmt->bind_param("sii", $remarks, $_SESSION['user_id'], $photo_id);
    
    if ($stmt->execute()) {
        // Get photo details for notification
        $photo_query = $conn->prepare("SELECT ap.*, c.full_name, lp.species, lp.animal_type 
                                      FROM animal_photos ap 
                                      JOIN livestock_poultry lp ON ap.animal_id = lp.animal_id 
                                      JOIN clients c ON lp.client_id = c.client_id 
                                      WHERE ap.photo_id = ?");
        $photo_query->bind_param("i", $photo_id);
        $photo_query->execute();
        $photo_result = $photo_query->get_result();
        
        if ($photo_result->num_rows > 0) {
            $photo_data = $photo_result->fetch_assoc();
            
            // Create notification for client
            $notification_message = "Your photo submission for " . $photo_data['species'] . " (" . $photo_data['animal_type'] . ") has been rejected. Reason: " . $remarks;
            
            $notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, status, created_at) VALUES (?, ?, 'photo_rejection', 'Unread', NOW())");
            $notification_stmt->bind_param("is", $photo_data['client_id'], $notification_message);
            $notification_stmt->execute();
            $notification_stmt->close();
        }
        
        $photo_query->close();
        echo json_encode(['success' => true, 'message' => 'Photo rejected successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to reject photo']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>

