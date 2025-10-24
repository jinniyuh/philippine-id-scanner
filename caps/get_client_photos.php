<?php
session_start();
include 'includes/conn.php';

// Set timezone to Asia/Manila (Philippines)
date_default_timezone_set('Asia/Manila');

// Set proper character encoding
mysqli_set_charset($conn, "utf8mb4");

// Check if user is logged in as client
if (!isset($_SESSION['client_id']) || $_SESSION['role'] !== 'client') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$client_id = $_SESSION['client_id'];

try {
    // Fetch all photos for this client with animal and status information
    $query = "
        SELECT 
            ap.photo_id,
            ap.photo_path,
            ap.uploaded_at,
            ap.status,
            ap.rejection_reason,
            lp.species,
            lp.animal_type
        FROM animal_photos ap
        INNER JOIN livestock_poultry lp ON ap.animal_id = lp.animal_id
        WHERE lp.client_id = ?
        ORDER BY ap.uploaded_at DESC
    ";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $photos = [];
    while ($row = $result->fetch_assoc()) {
        // Format the uploaded date with proper timezone handling
        $timestamp = strtotime($row['uploaded_at']);
        $uploaded_at = date('M d, Y g:i A', $timestamp);
        
        $photo_path = $row['photo_path'];
        
        // Build absolute path for file existence check - use realpath for proper Windows handling
        $absolute_path = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $photo_path);
        $file_exists_check = file_exists($absolute_path) && is_file($absolute_path);
        
        $photos[] = [
            'photo_id' => (int)$row['photo_id'],
            'photo_path' => $photo_path,
            'uploaded_at' => $uploaded_at,
            'status' => $row['status'] ?: 'Pending',
            'rejection_reason' => $row['rejection_reason'] ? htmlspecialchars_decode($row['rejection_reason'], ENT_QUOTES) : null,
            'species' => $row['species'],
            'animal_type' => $row['animal_type'],
            'file_exists' => $file_exists_check ? true : false // Explicit boolean
        ];
    }
    
    echo json_encode([
        'success' => true,
        'photos' => $photos,
        'count' => count($photos)
    ]);
    
} catch (Exception $e) {
    error_log('Error in get_client_photos.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load photos: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
