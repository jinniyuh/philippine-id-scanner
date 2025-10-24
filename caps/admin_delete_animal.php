<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Check if form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $animal_id = $_POST['animal_id'];
    $type = $_POST['type'];
    
    // Validate inputs
    if (!empty($animal_id) && !empty($type)) {
        // Prepare delete statement
        $stmt = $conn->prepare("DELETE FROM livestock_poultry WHERE animal_id = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("i", $animal_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = ucfirst($type) . " deleted successfully.";
        } else {
            $_SESSION['error'] = "Error deleting " . $type . ": " . $stmt->error;
        }

        $stmt->close();
    } else {
        $_SESSION['error'] = "Invalid animal data provided.";
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
}

// Redirect back to livestock & poultry page
header("Location: admin_livestock_poultry.php");
exit();
?>