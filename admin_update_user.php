<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    $fullname = $_POST['fullname']; // Changed from 'name' to 'fullname'
    $contact = $_POST['contact_number'];
    $role = $_POST['role'];
    
    // Debug to check values
    // echo "User ID: $user_id, Username: $username, Fullname: $fullname, Contact: $contact, Role: $role"; exit;
    
    // Update without changing password and status
    $sql = "UPDATE users SET 
            username = ?, 
            name = ?, 
            contact_number = ?, 
            role = ?
            WHERE user_id = ?"; // Changed from 'id' to 'user_id' to match your database column name
    
    $stmt = $conn->prepare($sql);
    
    // Check if prepare was successful
    if ($stmt === false) {
        $_SESSION['error'] = "Error preparing statement: " . $conn->error;
        // Redirect back to admin_clients.php users view
        header("Location: admin_clients.php?view=users");
        exit();
    }
    
    $stmt->bind_param("ssssi", $username, $fullname, $contact, $role, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "User updated successfully";
    } else {
        $_SESSION['error'] = "Error updating user: " . $stmt->error;
    }
    
    // Redirect back to admin_clients.php users view
    header("Location: admin_clients.php?view=users");
    exit();
}
?>