<?php
session_start();
include 'includes/conn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = strtolower(trim($_POST['username'] ?? ''));
    
    if (empty($username)) {
        echo json_encode(['available' => false, 'message' => 'Username is required']);
        exit();
    }
    
    // Check in clients table
    $stmt = $conn->prepare("SELECT client_id FROM clients WHERE username = ?");
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['available' => false, 'message' => 'Username already taken']);
            exit();
        }
    }
    
    // Check in users table (admin/staff)
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['available' => false, 'message' => 'Username already taken']);
            exit();
        }
    }
    
    echo json_encode(['available' => true, 'message' => 'Username available']);
} else {
    echo json_encode(['available' => false, 'message' => 'Invalid request method']);
}
?>
