<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['full_name'];
    $contact_number = $_POST['contact_number'];
    $barangay = $_POST['barangay'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if custom username and password were provided
    $username = isset($_POST['username']) && !empty($_POST['username']) ? 
                $_POST['username'] : 'client' . rand(1000, 9999);
    
    // If custom password provided, use it; otherwise generate random password
    if (isset($_POST['password']) && !empty($_POST['password'])) {
        $password = $_POST['password'];
    } else {
        $password = substr(md5(uniqid(rand(), true)), 0, 8);
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert the client with all fields including latitude and longitude
        $stmt = $conn->prepare("INSERT INTO clients (full_name, contact_number, barangay, username, password, role, status, type) VALUES (?, ?, ?, ?, ?, 'client', 'None', 'none')");
        $stmt->bind_param("sssss", $name, $contact_number, $barangay, $username, $hashed_password);


    if ($stmt->execute()) {
        $client_id = $conn->insert_id;
        
        // If no custom username was provided, update with client_id-based username
        if (!isset($_POST['username']) || empty($_POST['username'])) {
            $username = 'client' . $client_id;
            $update_stmt = $conn->prepare("UPDATE clients SET username = ? WHERE client_id = ?");
            $update_stmt->bind_param("si", $username, $client_id);
            $update_stmt->execute();
        }

        $_SESSION['success'] = "Client added successfully! Username: $username, Password: $password";
    } else {
        $_SESSION['error'] = "Error adding client: " . $conn->error;
    }

    header("Location: admin_clients.php");
    exit();
} else {
    header("Location: admin_clients.php");
    exit();
}
?>
