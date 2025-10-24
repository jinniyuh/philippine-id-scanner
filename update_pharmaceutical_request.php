<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as client
if (!isset($_SESSION['client_id']) || $_SESSION['role'] !== 'client') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

$request_id = $_POST['edit_request_id'] ?? '';
$species = $_POST['species'] ?? '';
$symptoms = $_POST['symptoms'] ?? '';

if (empty($request_id) || empty($species) || empty($symptoms)) {
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit();
}

try {
    // Check if the request belongs to the current client
    $check_stmt = $conn->prepare("SELECT request_id FROM pharmaceutical_requests WHERE request_id = ? AND client_id = ?");
    $check_stmt->bind_param("ii", $request_id, $_SESSION['client_id']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Request not found or access denied']);
        exit();
    }
    
    // Update the request
    $update_stmt = $conn->prepare("UPDATE pharmaceutical_requests SET species = ?, symptoms = ?, request_date = NOW() WHERE request_id = ? AND client_id = ?");
    $update_stmt->bind_param("ssii", $species, $symptoms, $request_id, $_SESSION['client_id']);
    
    if ($update_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Request updated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update request']);
    }
    
    $update_stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
