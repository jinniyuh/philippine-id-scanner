<?php
session_start();
include 'includes/conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $stock = intval($_POST['stock']);
    $category = trim($_POST['category']);
    $unit = trim($_POST['unit']);
    $expiry_date = trim($_POST['expiry_date']);

    if (empty($id) || empty($name) || empty($stock) || empty($category) || empty($unit) || empty($expiry_date)) {
        echo "All fields are required.";
        exit();
    }

    $stmt = $conn->prepare("UPDATE pharmaceuticals 
        SET name = ?, stock = ?, category = ?, unit = ?, expiry_date = ?
        WHERE pharma_id = ?");

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sisssi", $name, $stock, $category, $unit, $expiry_date, $id);

    if ($stmt->execute()) {
       
    if ($stock <= 50) {
        $message = "Low stock alert (updated): '{$name}' now has {$stock} units.";
        $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, timestamp, status) VALUES (?, ?, NOW(), 'unread')");
        
        if ($notif_stmt) {
            $notif_stmt->bind_param("is", $admin_id, $message);
            $notif_stmt->execute();
            $notif_stmt->close();
        } else {
            echo "Prepare failed: " . $conn->error;
        }
    }

        $_SESSION['success_message'] = "Pharmaceutical item updated successfully.";
        header("Location: admin_pharmaceuticals.php");
        exit();
    } else {
        echo "Error updating pharmaceutical: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Invalid request.";
}
?>
