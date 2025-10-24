<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in
if (!isset($_SESSION['client_id']) || $_SESSION['role'] !== 'client') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['animal_id'])) {
    echo json_encode(['success' => false, 'error' => 'Animal ID required']);
    exit();
}

$animal_id = $_GET['animal_id'];
$client_id = $_SESSION['client_id'];

// Verify that the animal belongs to this client
$stmt = $conn->prepare("SELECT animal_id FROM livestock_poultry WHERE animal_id = ? AND client_id = ?");
$stmt->bind_param("ii", $animal_id, $client_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Animal not found or access denied']);
    exit();
}

// Get photos for this animal
$stmt = $conn->prepare("SELECT photo_id, photo_path, uploaded_at FROM animal_photos WHERE animal_id = ? ORDER BY uploaded_at DESC");
$stmt->bind_param("i", $animal_id);
$stmt->execute();
$result = $stmt->get_result();

$photos = [];
while ($row = $result->fetch_assoc()) {
    $photo_path = $row['photo_path'];
    
    // Build absolute path for file existence check - use realpath for proper Windows handling
    $absolute_path = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $photo_path);
    $file_exists_check = file_exists($absolute_path) && is_file($absolute_path);
    
    $photos[] = [
        'photo_id' => (int)$row['photo_id'],
        'photo_path' => $photo_path,
        'uploaded_at' => date('M d, Y H:i', strtotime($row['uploaded_at'])),
        'file_exists' => $file_exists_check ? true : false // Explicit boolean
    ];
}

echo json_encode(['success' => true, 'photos' => $photos]);
?> 