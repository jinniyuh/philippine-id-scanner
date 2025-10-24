<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

$client_name = $_POST['client_name'] ?? '';
$species = $_POST['species'] ?? '';
$animal_type = $_POST['animal_type'] ?? '';

// Debug logging
error_log("Photo details request - Client: '$client_name', Species: '$species', Animal Type: '$animal_type'");

if (empty($species) || empty($animal_type)) {
    echo json_encode(['success' => false, 'error' => 'Species and animal type are required']);
    exit();
}

try {
    // Find the most recent photo upload matching the criteria
    $query = "SELECT ap.photo_id, ap.photo_path, ap.uploaded_at, ap.status,
                     lp.species, lp.animal_type, lp.animal_id, 
                     c.full_name as client_name, c.client_id
              FROM animal_photos ap 
              JOIN livestock_poultry lp ON ap.animal_id = lp.animal_id 
              JOIN clients c ON lp.client_id = c.client_id 
              WHERE lp.species = ? AND lp.animal_type = ?";
    
    $params = [$species, $animal_type];
    $types = "ss";
    
    if (!empty($client_name)) {
        $query .= " AND c.full_name = ?";
        $params[] = $client_name;
        $types .= "s";
    }
    
    $query .= " ORDER BY ap.uploaded_at DESC LIMIT 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    error_log("Query executed. Rows found: " . $result->num_rows);
    
    if ($result->num_rows > 0) {
        $photo = $result->fetch_assoc();
        error_log("Photo found: " . json_encode($photo));
        echo json_encode(['success' => true, 'photo' => $photo]);
    } else {
        error_log("No photo found matching criteria");
        echo json_encode(['success' => false, 'error' => 'No photo found matching the criteria']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>

