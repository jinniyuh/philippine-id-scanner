<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $animal_id = $_POST['animal_id'];
    $animal_type = $_POST['animal_type'];
    $species = $_POST['species'];
    $quantity = $_POST['quantity'];
    $client_id = $_POST['client_id'];
    $source = $_POST['source'];
    $health_status = $_POST['health_status'];
    $last_vaccination = ($animal_type === 'Livestock') ? $_POST['last_vaccination'] : null;
    
    // Set weight based on animal type
    $weight = ($animal_type === 'Livestock') ? $_POST['weight'] : 0;
    
    // Prepare SQL statement - only update health_status, weight (for livestock), and last_vaccination (for livestock)
    $sql = "UPDATE livestock_poultry SET 
            health_status = ?, 
            weight = ?, 
            last_vaccination = ? 
            WHERE animal_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdsi", $health_status, $weight, $last_vaccination, $animal_id);
    
    // Execute the statement
    if ($stmt->execute()) {
        // Update client type based on source
        $client_type = strcasecmp($source, 'Disseminated') === 0 ? 'disseminated' : 'owned';
        $updateClientType = $conn->prepare("UPDATE clients SET type = ? WHERE client_id = ?");
        $updateClientType->bind_param("si", $client_type, $client_id);
        $updateClientType->execute();
        $updateClientType->close();
        
        // Set success message
        $_SESSION['success'] = "$animal_type updated successfully";
    } else {
        // Set error message
        $_SESSION['error'] = "Error updating $animal_type: " . $conn->error;
    }
    
    // Close statement
    $stmt->close();
    
    // Redirect back to livestock & poultry page
    header("Location: admin_livestock_poultry.php");
    exit();
} else {
    // If not a POST request, redirect to livestock & poultry page
    header("Location: admin_livestock_poultry.php");
    exit();
}
?>