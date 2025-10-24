<?php
session_start();
include 'includes/conn.php';

header('Content-Type: application/json');

// Check if user is logged in (optional - you may want to allow public access)
// Uncomment the following lines if you want to restrict access to logged-in users only
// if (!isset($_SESSION['user_id'])) {
//     echo json_encode(['success' => false, 'error' => 'Unauthorized']);
//     exit();
// }

try {
    // Get the latest announcement (only one record is used)
    $stmt = $conn->prepare("SELECT * FROM announcement ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $announcement = $result->fetch_assoc();
        
        // Format the updated_at timestamp
        if ($announcement['updated_at']) {
            $timestamp = strtotime($announcement['updated_at']);
            $announcement['updated_at'] = date('F j, Y g:i A', $timestamp);
        }
        
        echo json_encode([
            'success' => true,
            'announcement' => $announcement
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No announcement found'
        ]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>

