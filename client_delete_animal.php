<?php
session_start();
include 'includes/conn.php';

if (!isset($_SESSION['client_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if (!isset($_POST['animal_id'])) {
    echo json_encode(['success' => false, 'error' => 'No animal ID provided']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM livestock_poultry WHERE animal_id = ? AND client_id = ?");
    $stmt->bind_param("ii", $_POST['animal_id'], $_SESSION['client_id']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Error deleting animal");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>