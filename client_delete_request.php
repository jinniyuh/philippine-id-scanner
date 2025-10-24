<?php
session_start();
include 'includes/conn.php';

if (!isset($_SESSION['client_id']) || $_SESSION['role'] !== 'client') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (!isset($_POST['request_id'])) {
    echo json_encode(['success' => false, 'message' => 'Request ID is required']);
    exit();
}

$request_id = $_POST['request_id'];
$client_id = $_SESSION['client_id'];

// Delete the request
$stmt = $conn->prepare("DELETE FROM pharmaceutical_requests WHERE request_id = ? AND client_id = ?");
$stmt->bind_param("ii", $request_id, $client_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete request']);
}

$stmt->close();
$conn->close();
?>