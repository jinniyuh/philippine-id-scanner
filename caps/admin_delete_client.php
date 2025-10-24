<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['client_id'])) {
    $client_id = intval($_POST['client_id']);
    
    // Validate client_id
    if ($client_id <= 0) {
        $_SESSION['error'] = "Invalid client ID";
        header("Location: admin_clients.php");
        exit();
    }
    
    // Check if client exists
    $check_query = "SELECT full_name FROM clients WHERE client_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $client_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Client not found";
        header("Location: admin_clients.php");
        exit();
    }
    
    $client = $result->fetch_assoc();
    $client_name = $client['full_name'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete related records first (foreign key constraints)
        
        // Delete from livestock_poultry table
        $delete_livestock = "DELETE FROM livestock_poultry WHERE client_id = ?";
        $stmt1 = $conn->prepare($delete_livestock);
        $stmt1->bind_param("i", $client_id);
        $stmt1->execute();
        
        // Delete from animal_photos table (if exists) - using animal_id from livestock_poultry
        $delete_photos = "DELETE ap FROM animal_photos ap 
                         INNER JOIN livestock_poultry lp ON ap.animal_id = lp.animal_id 
                         WHERE lp.client_id = ?";
        $stmt2 = $conn->prepare($delete_photos);
        $stmt2->bind_param("i", $client_id);
        $stmt2->execute();
        
        // Delete from pharmaceutical_requests table (if exists)
        $delete_requests = "DELETE FROM pharmaceutical_requests WHERE client_id = ?";
        $stmt3 = $conn->prepare($delete_requests);
        $stmt3->bind_param("i", $client_id);
        $stmt3->execute();
        
        // Delete from notifications table (if exists)
        $delete_notifications = "DELETE FROM notifications WHERE client_id = ?";
        $stmt4 = $conn->prepare($delete_notifications);
        $stmt4->bind_param("i", $client_id);
        $stmt4->execute();
        
        // Delete from vet_prescription table (if exists)
        $delete_prescriptions = "DELETE FROM vet_prescription WHERE client_id = ?";
        $stmt5 = $conn->prepare($delete_prescriptions);
        $stmt5->bind_param("i", $client_id);
        $stmt5->execute();
        
        // Delete from transactions table (if exists)
        $delete_transactions = "DELETE FROM transactions WHERE client_id = ?";
        $stmt6 = $conn->prepare($delete_transactions);
        $stmt6->bind_param("i", $client_id);
        $stmt6->execute();
        
        // Finally, delete the client
        $delete_client = "DELETE FROM clients WHERE client_id = ?";
        $stmt7 = $conn->prepare($delete_client);
        $stmt7->bind_param("i", $client_id);
        $stmt7->execute();
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['success'] = "Client '$client_name' has been deleted successfully";
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = "Error deleting client: " . $e->getMessage();
    }
    
    header("Location: admin_clients.php");
    exit();
    
} else {
    // If accessed directly without POST data
    $_SESSION['error'] = "Invalid request";
    header("Location: admin_clients.php");
    exit();
}
?>
