<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $fullname = trim($_POST['fullname']);
    $contact = trim($_POST['contact_number']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $status = $_POST['status'];
     
    // Validate all fields are provided
    if (empty($username) || empty($fullname) || empty($contact) || empty($password) || empty($role) || empty($status)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: admin_users.php");
        exit();
    }
    
    // Validate contact number format (must start with 09 and be 11 digits)
    if (!preg_match('/^09\d{9}$/', $contact)) {
        $_SESSION['error'] = "Contact number must start with '09' and be exactly 11 digits.";
        header("Location: admin_users.php");
        exit();
    }
    
    // Validate password (at least 8 characters with uppercase and lowercase)
    if (strlen($password) < 8 || !preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password)) {
        $_SESSION['error'] = "Password must be at least 8 characters long and contain both uppercase and lowercase letters.";
        header("Location: admin_users.php");
        exit();
    }
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if username already exists
    $check_username_query = "SELECT * FROM users WHERE username = ?";
    $check_username_stmt = $conn->prepare($check_username_query);
    $check_username_stmt->bind_param("s", $username);
    $check_username_stmt->execute();
    $username_result = $check_username_stmt->get_result();
    
    if ($username_result->num_rows > 0) {
        $_SESSION['error'] = "Username already exists. Please choose a different username.";
        header("Location: admin_users.php");
        exit();
    }
    
    // Check if full name already exists
    $check_fullname_query = "SELECT * FROM users WHERE name = ?";
    $check_fullname_stmt = $conn->prepare($check_fullname_query);
    $check_fullname_stmt->bind_param("s", $fullname);
    $check_fullname_stmt->execute();
    $fullname_result = $check_fullname_stmt->get_result();
    
    if ($fullname_result->num_rows > 0) {
        $_SESSION['error'] = "Full name already exists. Please use a different full name.";
        header("Location: admin_users.php");
        exit();
    }
    
    // Insert new user if both username and full name are unique
    $insert_query = "INSERT INTO users (username, name, contact_number, password, role, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("ssssss", $username, $fullname, $contact, $hashed_password, $role, $status);
    
    if ($insert_stmt->execute()) {
        $_SESSION['success'] = "New user added successfully";
    } else {
        $_SESSION['error'] = "Error adding user: " . $conn->error;
    }
    
    header("Location: admin_users.php");
    exit();
}
?>