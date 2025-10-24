<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Check if ID is provided in the URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $pharma_id = intval($_GET['id']);

    // Prepare delete statement
    $stmt = $conn->prepare("DELETE FROM pharmaceuticals WHERE pharma_id = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $pharma_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Pharmaceutical item deleted successfully.";
    } else {
        $_SESSION['error_message'] = "Error deleting pharmaceutical: " . $stmt->error;
    }

    $stmt->close();
} else {
    $_SESSION['error_message'] = "Invalid pharmaceutical ID.";
}

// Redirect back to pharmaceuticals page
header("Location: admin_pharmaceuticals.php");
exit();
?>
