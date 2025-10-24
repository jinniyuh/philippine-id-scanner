<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get the most recent pending upload
$query = $conn->prepare("SELECT ap.photo_id, ap.photo_path, ap.uploaded_at, ap.status,
                        lp.species, lp.animal_type, lp.animal_id, 
                        c.full_name as client_name, c.client_id
                        FROM animal_photos ap 
                        JOIN livestock_poultry lp ON ap.animal_id = lp.animal_id 
                        JOIN clients c ON lp.client_id = c.client_id 
                        WHERE ap.status = 'Pending'
                        ORDER BY ap.uploaded_at DESC LIMIT 1");

$query->execute();
$result = $query->get_result();

if ($result->num_rows > 0) {
    $upload = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'upload' => $upload
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No pending uploads found'
    ]);
}
?>
