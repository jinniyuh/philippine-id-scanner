<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize form inputs safely
    $name = trim($_POST['name'] ?? '');
    $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
    $category = trim($_POST['category'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $expiry_date = $_POST['expiry_date'] ?? '';

    // Basic validation
    if (empty($name) || empty($stock) || empty($category) || empty($unit) || empty($expiry_date)) {
        echo "All required fields must be filled out.";
        exit();
    }

    // Prepare SQL insert statement
    $stmt = $conn->prepare("INSERT INTO pharmaceuticals (name, stock, category, unit, expiry_date, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())");

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

// Corrected bind_param
$stmt->bind_param("sisss", $name, $stock, $category, $unit, $expiry_date);


    // Execute and check result
    if ($stmt->execute()) {
        
        if ($stock <= 50) {
            $admin_id = 1; // Change to the actual admin user_id or loop through multiple admins
            $message = "Low stock alert: '{$name}' added with only {$stock} units.";
            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, timestamp, status) VALUES (?, ?, NOW(), 'unread')");
            $notif_stmt->bind_param("is", $admin_id, $message);
            $notif_stmt->execute();
            $notif_stmt->close();
        }
        

        $_SESSION['success_message'] = "Pharmaceutical item added successfully.";
        header("Location: admin_pharmaceuticals.php"); 
        exit();
    } else {
        echo "Error adding pharmaceutical: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Invalid request.";
}
?>
