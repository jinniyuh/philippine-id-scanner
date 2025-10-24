<?php
session_start();
include 'includes/conn.php';

if (!isset($_SESSION['client_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE livestock_poultry SET 
        animal_type = ?,
        species = ?,
        sex = ?,
        weight = ?,
        quantity = ?,
        source = ?,
        health_status = ?
        WHERE animal_id = ? AND client_id = ?");
        
    $stmt->bind_param("ssssssii", 
        $_POST['type'],
        $_POST['species'],
        $_POST['sex'],
        $_POST['weight'],
        $_POST['quantity'],
        $_POST['source'],
        $_POST['health_status'],
        $_POST['animal_id'],
        $_SESSION['client_id']
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Error updating animal");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>