<?php
session_start();
header('Content-Type: application/json');
include 'includes/conn.php';
include 'upload_time_helper.php';

if (!isset($_SESSION['client_id']) || $_SESSION['role'] !== 'client') {
    echo json_encode(['can_upload' => false, 'reason' => 'Unauthorized']);
    exit();
}

$client_id = (int)$_SESSION['client_id'];
$animal_id = isset($_GET['animal_id']) ? (int)$_GET['animal_id'] : 0;
if ($animal_id <= 0) {
    echo json_encode(['can_upload' => false, 'reason' => 'No animal selected']);
    exit();
}

// Verify ownership
$own_stmt = $conn->prepare("SELECT 1 FROM livestock_poultry WHERE animal_id = ? AND client_id = ? LIMIT 1");
if (!$own_stmt) {
    echo json_encode(['can_upload' => false, 'reason' => 'Database error']);
    exit();
}
$own_stmt->bind_param('ii', $animal_id, $client_id);
$own_stmt->execute();
$own_stmt->store_result();
if ($own_stmt->num_rows === 0) {
    echo json_encode(['can_upload' => false, 'reason' => 'Invalid animal']);
    exit();
}

$result = canClientUpload($client_id, $animal_id, $conn);
echo json_encode($result);
?>


