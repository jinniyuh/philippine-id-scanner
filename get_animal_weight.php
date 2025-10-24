<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as client
if (!isset($_SESSION['client_id']) || $_SESSION['role'] !== 'client') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get client ID and species from request
$client_id = $_SESSION['client_id'];
$species = isset($_GET['species']) ? trim($_GET['species']) : '';

if (empty($species)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Species is required']);
    exit();
}

// Query to get the weight of the animal for this client and species
$stmt = $conn->prepare("SELECT weight FROM livestock_poultry WHERE client_id = ? AND species = ? AND weight IS NOT NULL AND weight > 0 ORDER BY animal_id DESC LIMIT 1");

if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("is", $client_id, $species);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'weight' => $row['weight']
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'No weight found for this species'
    ]);
}

$stmt->close();
$conn->close();
?>
