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
    
    // Debug logging
    error_log("Enable user request received for user_id: " . $user_id);
    
    // Update user status to 'Active'
    $sql = "UPDATE users SET status = 'Active' WHERE user_id = ?";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        $_SESSION['error'] = "Error preparing statement: " . $conn->error;
        header("Location: admin_clients.php?view=users");
        exit();
    }
    
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "User enabled successfully";
        error_log("User enabled successfully: " . $user_id);
    } else {
        $_SESSION['error'] = "Error enabling user: " . $stmt->error;
        error_log("Error enabling user: " . $stmt->error);
    }
    
    // Redirect back: prefer explicit redirect param, then HTTP_REFERER, else users view
    $redirect = $_POST['redirect'] ?? '';
    if ($redirect && preg_match('/^[\-\w\.\?=&#\/]+$/', $redirect)) {
        header("Location: " . $redirect);
    } elseif (!empty($_SERVER['HTTP_REFERER'])) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
    } else {
        header("Location: admin_clients.php?view=users");
    }
    exit();
}
?>