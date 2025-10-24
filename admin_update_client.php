<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'];
    $name = $_POST['full_name'];
    $contact_number = $_POST['contact_number'];
    $barangay = $_POST['barangay'];
    
    // Update client information
    $update_query = "UPDATE clients SET 
                    full_name = ?, 
                    contact_number = ?, 
                    barangay = ? 
                    WHERE client_id = ?";
                    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sssi", $name, $contact_number, $barangay, $client_id);

    
    if ($update_stmt->execute()) {
        $_SESSION['success'] = "Client information updated successfully";
    } else {
        $_SESSION['error'] = "Error updating client information: " . $conn->error;
    }
    
    header("Location: admin_clients.php");
    exit();
} else {
    // If accessed directly without POST data
    header("Location: admin_clients.php");
    exit();
}
?>