<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $value = trim($_POST['value'] ?? '');
    
    if (empty($value)) {
        echo json_encode(['valid' => false, 'message' => 'This field is required']);
        exit();
    }
    
    switch ($type) {
        case 'username':
            // Check if username already exists
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM clients WHERE username = ?");
            $stmt->bind_param("s", $value);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_assoc()['count'];
            
            if ($count > 0) {
                echo json_encode(['valid' => false, 'message' => 'Username already exists']);
            } else {
                echo json_encode(['valid' => true, 'message' => 'Username is available']);
            }
            break;
            
        case 'full_name':
            // Check if it has first name and last name (at least 2 words)
            $nameParts = explode(' ', $value);
            if (count($nameParts) < 2) {
                echo json_encode(['valid' => false, 'message' => 'Please enter first name and last name']);
                break;
            }
            
            // Check if full name already exists
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM clients WHERE full_name = ?");
            $stmt->bind_param("s", $value);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_assoc()['count'];
            
            if ($count > 0) {
                echo json_encode(['valid' => false, 'message' => 'Full name already exists']);
            } else {
                echo json_encode(['valid' => true, 'message' => 'Full name is valid']);
            }
            break;
            
        default:
            echo json_encode(['valid' => false, 'message' => 'Invalid validation type']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
