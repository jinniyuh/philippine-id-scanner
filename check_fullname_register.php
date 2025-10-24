<?php
session_start();
include 'includes/conn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    
    if (empty($full_name)) {
        echo json_encode(['available' => false, 'message' => 'Full name is required']);
        exit();
    }
    
    // Check in clients table
    $stmt = $conn->prepare("SELECT client_id FROM clients WHERE full_name = ?");
    if ($stmt) {
        $stmt->bind_param("s", $full_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['available' => false, 'message' => 'Full name already exists']);
            exit();
        }
    }
    
    // Check in users table (admin/staff)
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE name = ?");
    if ($stmt) {
        $stmt->bind_param("s", $full_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['available' => false, 'message' => 'Full name already exists']);
            exit();
        }
    }
    
    echo json_encode(['available' => true, 'message' => 'Full name available']);
} else {
    echo json_encode(['available' => false, 'message' => 'Invalid request method']);
}
?>
